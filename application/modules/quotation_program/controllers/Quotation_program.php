<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Quotation_program extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index' => ['quotation_program', 'lihat'],
        'create_from_pj' => ['quotation_program', 'tambah'],
        'add' => ['quotation_program', 'tambah'],
        'edit' => ['quotation_program', 'ubah'],
        'view' => ['quotation_program', 'lihat'],
        'pdf' => ['quotation_program', 'cetak'],
        'confirmation' => ['quotation_program', 'cetak'],
        'save' => ['quotation_program', 'simpan', true],
        'add_peserta' => ['quotation_program', 'tambah', true],
        'preview' => ['quotation_program', 'lihat', true],
        'deal' => ['quotation_program', 'aktif', true],
        'set_status' => ['quotation_program', 'aktif', true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Quotation_model', 'qtn_model');
        $this->load->model('penjualan_program/Penjualan_program_model', 'pj_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    public function index($tab = 'ongoing')
    {
        $this->template->title('Daftar Quotation');
        $this->template->set('breadcrumbs', 'Quotation / <b>Daftar Quotation</b>');

        $tab    = in_array($tab, ['ongoing', 'done']) ? $tab : 'ongoing';
        $status = $this->input->get('status') ?: 'all';
        $tujuan = $this->input->get('tujuan') ?: 'all';
        $idPj   = (int)$this->input->get('pj') ?: null;

        // Satu pass: quotation dikelompokkan per Penjualan Program (QTN-FR-01/02).
        $byPj = [];
        foreach ($this->qtn_model->get_quotations('all') as $q) {
            $byPj[$q['id_penjualan']][] = $q;
        }

        // Selesai = tanggal selesai acara sudah lewat (usulan Q-QTN-09 di mockup).
        $today = date('Y-m-d');
        $rows = [];
        foreach ($this->qtn_model->get_final_penjualans() as $pj) {
            $qs   = $byPj[$pj['id']] ?? [];
            $deal = $pend = 0;
            foreach ($qs as $q) {
                foreach ($q['peserta'] as $ps) {
                    if ($q['status'] === 'deal' && $ps['status'] === 'deal') $deal++;
                    if ($q['status'] === 'terkirim') $pend++;
                }
            }
            $pj['qs']      = $qs;
            $pj['deal']    = $deal;
            $pj['pend']    = $pend;
            $pj['selesai'] = !empty($pj['tgl_selesai']) && $pj['tgl_selesai'] < $today;
            $rows[] = $pj;
        }

        $inTab = array_values(array_filter($rows, function ($pj) use ($tab) { return $pj['selesai'] === ($tab === 'done'); }));
        $tabCount = [
            'ongoing' => count(array_filter($rows, function ($pj) { return !$pj['selesai']; })),
            'done'    => count(array_filter($rows, function ($pj) { return $pj['selesai']; })),
        ];

        // Pipeline mengikuti tab aktif (mockup qtnPipe).
        $pipe = [
            'draft'    => ['n' => 0, 'p' => 0],
            'terkirim' => ['n' => 0, 'p' => 0],
            'deal'     => ['n' => 0, 'p' => 0, 'nilai' => 0, 'dealPs' => 0],
            'batal'    => ['n' => 0, 'p' => 0],
        ];
        foreach ($inTab as $pj) {
            foreach ($pj['qs'] as $q) {
                $st = $q['status'];
                $pipe[$st]['n']++;
                $pipe[$st]['p'] += count($q['peserta']);
                if ($st === 'deal') {
                    $pipe['deal']['dealPs'] += count(array_filter($q['peserta'], function ($ps) { return $ps['status'] === 'deal'; }));
                    $pipe['deal']['nilai']  += $q['calc']['grand_total'];
                }
            }
        }

        $data = ['tab' => $tab, 'status' => $status, 'tujuan' => $tujuan, 'pipe' => $pipe, 'tabCount' => $tabCount, 'pj' => null];

        if ($idPj) {
            $pj = $this->pj_model->get_program_by_id($idPj);
            if ($pj && $pj['status'] === 'final') {
                $data['pj']     = $pj;
                $data['qs']     = array_values(array_filter($byPj[$idPj] ?? [], function ($q) use ($status, $tujuan) {
                    return ($status === 'all' || $q['status'] === $status) && ($tujuan === 'all' || $q['tujuan'] === $tujuan);
                }));
                $data['deal']   = 0;
                $data['pend']   = 0;
                foreach ($byPj[$idPj] ?? [] as $q) {
                    foreach ($q['peserta'] as $ps) {
                        if ($q['status'] === 'deal' && $ps['status'] === 'deal') $data['deal']++;
                        if ($q['status'] === 'terkirim') $data['pend']++;
                    }
                }
                $data['canAdd'] = $pj['jenis'] === 'public' || empty(array_filter($byPj[$idPj] ?? [], function ($q) { return $q['status'] !== 'batal'; }));
            }
        }

        if (!$data['pj']) {
            foreach ($inTab as &$pj) {
                $pj['bep']    = ($this->pj_model->get_program_by_id($pj['id'])['calc']['bep_peserta'] ?? null);
                $pj['canAdd'] = $pj['jenis'] === 'public' || empty(array_filter($pj['qs'], function ($q) { return $q['status'] !== 'batal'; }));
            }
            unset($pj);
            if ($status !== 'all') {
                $inTab = array_values(array_filter($inTab, function ($pj) use ($status) {
                    foreach ($pj['qs'] as $q) if ($q['status'] === $status) return true;
                    return false;
                }));
            }
            $data['rows'] = array_reverse($inTab);
        } else {
            $data['pj']['bep'] = $data['pj']['calc']['bep_peserta'] ?? null;
        }

        $this->template->render('index', $data);
    }

    public function create_from_pj($idPj)
    {
        $pj = $this->pj_model->get_program_by_id($idPj);
        if (!$pj || $pj['status'] !== 'final') {
            $this->session->set_flashdata('error', 'Penjualan Program tidak valid atau belum Final');
            redirect('penjualan_program');
        }

        $this->add($idPj);
    }

    public function add($idPj = null)
    {
        $this->template->title('Buat Quotation');
        $this->template->set('breadcrumbs', 'Quotation / <b>Buat Quotation</b>');

        $pj = $idPj ? $this->pj_model->get_program_by_id($idPj) : null;
        $jenisPj = $pj['jenis'] ?? 'public';
        $idJenisProduk = $pj['id_jenis_produk'] ?? 1;

        $defaultKet = $this->qtn_model->get_default_keterangan($jenisPj);
        $defaultKf = $this->qtn_model->get_default_konfirmasi($idJenisProduk);

        $quotation = [
            'id'                     => null,
            'nomor'                  => $this->qtn_model->generate_nomor(),
            'id_penjualan'           => $idPj,
            'tanggal'                => date('Y-m-d'),
            'berlaku_sampai'         => date('Y-m-d', strtotime('+30 days')),
            'id_sales'               => $this->session->userdata('app_session')['id_user'] ?? 1,
            'sumber_info'            => 'Website',
            'tujuan'                 => ($jenisPj === 'b2b') ? 'company' : 'personal',
            'id_customer'            => $pj['id_customer'] ?? null,
            'up_nama'                => '',
            'alamat'                 => '',
            'skema_harga'            => 'peserta',
            'harga_peserta'          => $pj['harga_jual_peserta'] ?? 0,
            'harga_allin'            => 0,
            'diskon'                 => 0,
            'is_ppn'                 => 1,
            'ppn_persen'             => 11,
            'biaya_lain'             => 0,
            'id_template_konfirmasi' => $defaultKf['id'] ?? null,
            'konfirmasi_isian'       => $defaultKf['isian'] ?? '["nama","nik","jabatan","instansi","alamat","wa","email"]',
            'konfirmasi_pernyataan'  => $defaultKf['pernyataan'] ?? '',
            'status'                 => 'draft',
            'peserta'                => [],
            'keterangan'             => $defaultKet
        ];

        // Jika B2B, isi data company otomatis
        if ($jenisPj === 'b2b' && !empty($pj['id_customer'])) {
            $cust = $this->md_model->get_companies($pj['id_customer']);
            if ($cust) {
                $quotation['alamat'] = ($cust['address_office'] ?? '') . ', ' . ($cust['kota'] ?? '');
            }
        }

        $data = [
            'q'          => $quotation,
            'is_new'     => true,
            'penjualans' => $this->qtn_model->get_final_penjualans(),
            'companies'  => $this->md_model->get_companies(null, true, $quotation['id_customer'] ?? null),
            'pesertas'   => $this->md_model->get_peserta(),
            'users'      => $this->md_model->get_users(),
            'kf_tpls'    => $this->md_model->get_template_konfirmasi(null, true, $quotation['id_template_konfirmasi'] ?? null)
        ];
        $data += $this->form_refs(null);

        $this->template->render('form', $data);
    }

    /** Referensi tambahan form: peserta terpakai per Penjualan Program & Jenis Bidang. */
    private function form_refs($idQuotation)
    {
        $dipakai = $bepHarga = [];
        foreach ($this->qtn_model->get_final_penjualans() as $pj) {
            $dipakai[$pj['id']] = (object)$this->qtn_model->peserta_dipakai($pj['id'], $idQuotation);
            // Harga BEP per peserta = HPP per peserta pada target (peringatan saja, tidak menghalangi).
            $bepHarga[$pj['id']] = (float)($this->pj_model->get_program_by_id($pj['id'])['calc']['hpp_per_pax'] ?? 0);
        }
        // Penjualan B2B yang sudah punya quotation aktif (selain yang sedang diedit) tidak dapat dipilih lagi.
        $this->db->select('q.id_penjualan')->from('quotation q')->join('penjualan_program pj', 'pj.id = q.id_penjualan')->where('pj.jenis', 'b2b')->where('q.status !=', 'batal');
        if ($idQuotation) $this->db->where('q.id !=', (int)$idQuotation);
        $b2bAktif = array_map('intval', array_column($this->db->get()->result_array(), 'id_penjualan'));
        return ['b2bAktif' => $b2bAktif, 'dipakai' => (object)$dipakai, 'bepHarga' => (object)$bepHarga, 'bidang' => $this->md_model->get_master_attr('ms_jenis_bidang'), 'ketMaster' => $this->qtn_model->get_master_keterangan()];
    }

    public function edit($id)
    {
        $quotation = $this->qtn_model->get_quotation_by_id($id);
        if (!$quotation) {
            $this->session->set_flashdata('error', 'Quotation tidak ditemukan');
            redirect('quotation_program');
        }
        if ($quotation['status'] !== 'draft') {
            $this->session->set_flashdata('error', 'Hanya quotation Draft yang dapat diubah');
            redirect('quotation_program');
        }

        $this->template->title('Edit Quotation ' . $quotation['nomor']);
        $this->template->set('breadcrumbs', 'Quotation / <b>' . $quotation['nomor'] . '</b>');

        $data = [
            'q'          => $quotation,
            'is_new'     => false,
            'penjualans' => $this->qtn_model->get_final_penjualans(),
            'companies'  => $this->md_model->get_companies(null, true, $quotation['id_customer'] ?? null),
            'pesertas'   => $this->md_model->get_peserta(),
            'users'      => $this->md_model->get_users(),
            'kf_tpls'    => $this->md_model->get_template_konfirmasi(null, true, $quotation['id_template_konfirmasi'] ?? null)
        ];

        $data += $this->form_refs($id);

        $this->template->render('form', $data);
    }

    public function view($id)
    {
        $quotation = $this->qtn_model->get_quotation_by_id($id);
        if (!$quotation) {
            show_404();
        }

        $this->template->title('Detail Quotation ' . $quotation['nomor']);
        $this->template->set('breadcrumbs', 'Quotation / <b>' . $quotation['nomor'] . '</b>');

        $data = [
            'q' => $quotation
        ];

        $this->template->render('view', $data);
    }

    /** Keluarkan PDF mPDF inline. */
    private function send_pdf($bin, $name)
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) . '"');
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
    }

    /** PDF lengkap: Penawaran + Syarat & Ketentuan + Formulir Konfirmasi per peserta (QTN-FR-10). ?pv=1 = pratinjau, tidak dicatat. */
    public function pdf($id)
    {
        $q = $this->qtn_model->get_quotation_by_id($id);
        if (!$q) show_404();
        // Pada Deal hanya peserta Deal yang dicetak (nilai dihitung dari mereka, QTN-FR-11).
        if ($q['status'] === 'deal') {
            $q['peserta'] = array_values(array_filter($q['peserta'], function ($p) { return $p['status'] === 'deal'; }));
        }
        $this->load->library('qtn_pdf');
        $bin = $this->qtn_pdf->render($q);
        if (!$this->input->get('pv')) write_log('Quotation', 'Cetak', 'Cetak Quotation ' . $q['nomor'], null, 'quotation', $id, $q['nomor']);
        $this->send_pdf($bin, 'Quotation-' . $q['nomor'] . '.pdf');
    }

    /** Confirmation Letter (QTN-FR-13): Formulir Konfirmasi peserta Deal, hanya Public. */
    public function confirmation($id)
    {
        $q = $this->qtn_model->get_quotation_by_id($id);
        if (!$q) show_404();
        if ($q['status'] !== 'deal' || $q['jenis_pj'] === 'b2b') {
            $this->session->set_flashdata('error', 'Confirmation Letter hanya untuk quotation Public berstatus Deal');
            redirect('quotation_program/view/' . (int)$id);
        }
        $q['peserta'] = array_values(array_filter($q['peserta'], function ($p) { return $p['status'] === 'deal'; }));
        $this->load->library('qtn_pdf');
        $bin = $this->qtn_pdf->render($q, 'konfirmasi');
        write_log('Quotation', 'Cetak', 'Cetak Confirmation Letter ' . $q['nomor'], null, 'quotation', $id, $q['nomor']);
        $this->send_pdf($bin, 'Confirmation-Letter-' . $q['nomor'] . '.pdf');
    }

    public function save()
    {
        $post = $this->input->post();
        if (empty($post['id_penjualan'])) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Penjualan Program wajib dipilih']));
        }

        if (!empty($post['peserta_json']) && empty($post['peserta'])) {
            $post['peserta'] = json_decode($post['peserta_json'], true);
        }

        if (!empty($post['keterangan_json']) && empty($post['keterangan'])) {
            $post['keterangan'] = json_decode($post['keterangan_json'], true);
        }

        if (!empty($post['konfirmasi_isian_json']) && empty($post['konfirmasi_isian'])) {
            $post['konfirmasi_isian'] = json_decode($post['konfirmasi_isian_json'], true);
        }

        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->qtn_model->save_quotation($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    /** Peserta baru dari form quotation: tersimpan langsung ke Master Peserta (QTN-FR-07, aturan FR-16). */
    public function add_peserta()
    {
        $p = $this->input->post();
        $err = null;
        foreach (['nama' => 'Nama Peserta', 'gender' => 'Gender', 'instansi' => 'Instansi/Perusahaan', 'bidang' => 'Bidang', 'provinsi' => 'Provinsi', 'kabupaten' => 'Kota/Kabupaten', 'no_telp' => 'No Telp/WhatsApp', 'email' => 'Email'] as $k => $l) {
            if (!trim((string)($p[$k] ?? ''))) { $err = $l . ' wajib diisi'; break; }
        }
        if (!$err && !filter_var(trim($p['email']), FILTER_VALIDATE_EMAIL)) $err = 'Format email tidak valid';
        if (!$err && !preg_match('/^\d{9,15}$/', preg_replace('/[\s\-+]/', '', $p['no_telp']))) $err = 'Nomor hanya angka, 9–15 digit';
        if (!$err && !empty($p['npwp_instansi']) && !preg_match('/^(\d{15}|\d{16})$/', preg_replace('/\D/', '', $p['npwp_instansi']))) $err = 'NPWP Instansi 15 atau 16 digit angka';

        $res = $err ? ['status' => 0, 'msg' => $err] : $this->md_model->save_peserta($p);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    /** Pratinjau PDF dari isian form, sebelum disimpan (QTN-FR-18). Galat dikirim sebagai JSON. */
    public function preview()
    {
        $post = $this->input->post();
        foreach (['peserta' => 'peserta_json', 'keterangan' => 'keterangan_json', 'konfirmasi_isian' => 'konfirmasi_isian_json'] as $k => $j) {
            if (!empty($post[$j]) && empty($post[$k])) $post[$k] = json_decode($post[$j], true);
        }
        $q = $this->qtn_model->build_preview($post);
        if (!$q) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Penjualan Program wajib dipilih']));
        }
        $this->load->library('qtn_pdf');
        $this->send_pdf($this->qtn_pdf->render($q), 'Preview-Quotation.pdf');
    }

    public function deal($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $res = $this->qtn_model->set_deal((int)$id, (array)$this->input->post('peserta'));
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function set_status($id, $newStatus)
    {
        // Mengubah status hanya lewat POST; Deal lewat deal() (pilih peserta).
        $res = $this->input->method() !== 'post'
            ? ['status' => 0, 'msg' => 'Metode tidak valid']
            : $this->qtn_model->set_status((int)$id, $newStatus);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }
}
