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
        'save' => ['quotation_program', 'simpan', true],
        'preview' => ['quotation_program', 'lihat', true],
        'deal' => ['quotation_program', 'aktif', true],
        'surat' => ['quotation_program', 'lihat'],
        'dokumen' => ['quotation_program', 'lihat'],
        'excel' => ['quotation_program', 'cetak'],
        'data_peserta' => ['quotation_program', 'lihat'],
        'export_batch' => ['quotation_program', 'cetak'],
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
        // Tahun & Bulan menurut tanggal MULAI acara; keduanya "Semua" menampilkan juga program tanpa tanggal (QTN-FR-01)
        $tahun  = preg_match('/^\d{4}$/', (string)$this->input->get('tahun')) ? (int)$this->input->get('tahun') : null;
        $bulan  = preg_match('/^(0?[1-9]|1[0-2])$/', (string)$this->input->get('bulan')) ? (int)$this->input->get('bulan') : null;

        // Satu pass: quotation dikelompokkan per Penjualan Program (QTN-FR-01/02).
        $byPj = [];
        foreach ($this->qtn_model->get_quotations('all') as $q) {
            $byPj[$q['id_penjualan']][] = $q;
        }

        // Selesai = tanggal selesai acara sudah lewat (usulan Q-QTN-09 di mockup).
        $today = date('Y-m-d');
        $rows = [];
        $tahunOpts = [];
        foreach ($this->qtn_model->get_final_penjualans() as $pj) {
            if (!empty($pj['tgl_mulai'])) $tahunOpts[(int)substr($pj['tgl_mulai'], 0, 4)] = true;
            $ts = !empty($pj['tgl_mulai']) ? strtotime($pj['tgl_mulai']) : null;
            if (($tahun && (!$ts || (int)date('Y', $ts) !== $tahun)) || ($bulan && (!$ts || (int)date('n', $ts) !== $bulan))) continue;
            $qs   = $byPj[$pj['id']] ?? [];
            $deal = $pend = 0;
            foreach ($qs as $q) {
                foreach ($q['peserta'] as $ps) if ($q['status'] === 'deal' && $ps['status'] === 'deal') $deal++;
                if ($q['status'] === 'terkirim') $pend += (int)$q['kuota_peserta']; // menunggu = kuota quotation Terkirim
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
                // Kartu menghitung KUOTA per status (QTN-FR-01); Deal memakai peserta Deal
                $pipe[$st]['p'] += $st === 'deal' ? count(array_filter($q['peserta'], function ($ps) { return $ps['status'] === 'deal'; })) : (int)$q['kuota_peserta'];
                if ($st === 'deal') {
                    $pipe['deal']['dealPs'] += count(array_filter($q['peserta'], function ($ps) { return $ps['status'] === 'deal'; }));
                    $pipe['deal']['nilai']  += $q['calc']['grand_total'];
                }
            }
        }

        krsort($tahunOpts);
        $data = ['tab' => $tab, 'status' => $status, 'tujuan' => $tujuan, 'pipe' => $pipe, 'tabCount' => $tabCount, 'pj' => null,
                 'tahun' => $tahun, 'bulan' => $bulan, 'tahunOpts' => array_keys($tahunOpts)];

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
                    foreach ($q['peserta'] as $ps) if ($q['status'] === 'deal' && $ps['status'] === 'deal') $data['deal']++;
                    if ($q['status'] === 'terkirim') $data['pend'] += (int)$q['kuota_peserta'];
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

    /**
     * Tab Data Peserta di Daftar Quotation (QTN-FR-29). Tanpa ?pj = daftar batch (satu baris per Penjualan Program Final);
     * dengan ?pj = halaman detail batch: peserta dari semua quotation batch itu, filter status dan cari.
     */
    public function data_peserta()
    {
        $tahun = preg_match('/^\d{4}$/', (string)$this->input->get('tahun')) ? (int)$this->input->get('tahun') : null;
        $bulan = preg_match('/^(0?[1-9]|1[0-2])$/', (string)$this->input->get('bulan')) ? (int)$this->input->get('bulan') : null;
        $q = trim((string)$this->input->get('q'));
        $idPj = (int)$this->input->get('pj');

        $this->template->title('Data Peserta');
        $this->template->set('breadcrumbs', 'Quotation / <b>Data Peserta</b>');

        $all = $this->qtn_model->get_final_penjualans();
        $tahunOpts = [];
        foreach ($all as $pj) if (!empty($pj['tgl_mulai'])) $tahunOpts[(int)substr($pj['tgl_mulai'], 0, 4)] = true;
        krsort($tahunOpts);
        $tabCount = ['ongoing' => 0, 'done' => 0];
        foreach ($all as $pj) { $done = !empty($pj['tgl_selesai']) && $pj['tgl_selesai'] < date('Y-m-d'); $tabCount[$done ? 'done' : 'ongoing']++; }

        if ($idPj) {
            $pj = $this->pj_model->get_program_by_id($idPj);
            if (!$pj || $pj['status'] !== 'final') { $this->session->set_flashdata('error', 'Penjualan Program tidak ditemukan'); redirect('quotation_program/data_peserta'); }
            $status = in_array($this->input->get('status'), ['deal', 'menunggu', 'batal'], true) ? $this->input->get('status') : 'all';
            $semua = $this->qtn_model->batch_peserta($idPj);
            $n = ['deal' => 0, 'menunggu' => 0, 'batal' => 0];
            foreach ($semua as $p) $n[$p['status']]++;
            $nQtn = count(array_unique(array_column($semua, 'id_quotation')));
            $this->template->render('data_peserta_batch', [
                'pj' => $pj, 'peserta' => $this->qtn_model->batch_peserta($idPj, $status, $q), 'semua' => $semua, 'n' => $n, 'n_qtn' => $nQtn,
                'status' => $status, 'q' => $q, 'tahun' => $tahun, 'bulan' => $bulan,
            ]);
            return;
        }
        $this->template->render('data_peserta_list', ['rows' => $this->qtn_model->batch_list($tahun, $bulan, $q), 'tahun' => $tahun, 'bulan' => $bulan, 'q' => $q,
            'tahunOpts' => array_keys($tahunOpts), 'tabCount' => $tabCount]);
    }

    /** Export PDF batch: Daftar Peserta + surat konfirmasi yang diunggah (QTN-FR-29). ?pv=1 = pratinjau (tidak dicatat). Peserta Batal tidak ikut. */
    public function export_batch($idPj)
    {
        $pj = $this->pj_model->get_program_by_id((int)$idPj);
        if (!$pj || $pj['status'] !== 'final') show_404();
        $peserta = $this->qtn_model->batch_peserta((int)$idPj);
        $this->load->library('qtn_pdf');
        $bin = $this->qtn_pdf->export_batch($pj, $peserta, FCPATH);
        if (!$this->input->get('pv')) write_log('Quotation', 'Cetak', 'Export PDF Data Peserta ' . $pj['nomor'], null, 'penjualan_program', $idPj, $pj['nomor']);
        $this->send_pdf($bin, 'Data-Peserta-' . $pj['nomor'] . '.pdf');
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
            'nomor'                  => '', // dibuat server saat simpan, dari tanggal quotation (QTN-FR-04)
            'id_penjualan'           => $idPj,
            'tanggal'                => date('Y-m-d'),
            'berlaku_sampai'         => date('Y') . '-12-31',
            'id_sales'               => $this->session->userdata('app_session')['id_user'] ?? 1,
            'sumber_info'            => 'Website',
            'tujuan'                 => ($jenisPj === 'b2b') ? 'company' : 'personal',
            'id_customer'            => $pj['id_customer'] ?? null,
            'up_nama'                => '',
            'kepada_nama'            => '',
            'kuota_peserta'          => $jenisPj === 'b2b' ? Quotation_model::B2B_MIN : '',
            'paket_maks_peserta'     => $jenisPj === 'b2b' ? Quotation_model::B2B_PAKET_MAKS : null,
            'tambahan_per_peserta'   => $jenisPj === 'b2b' ? Quotation_model::B2B_TAMBAHAN : null,
            'alamat'                 => '',
            'skema_harga'            => $jenisPj === 'b2b' ? 'allin' : 'peserta',
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
            'users'      => $this->md_model->get_users(),
            'kf_tpls'    => $this->md_model->get_template_konfirmasi(null, true, $quotation['id_template_konfirmasi'] ?? null),
            'nTerisi'    => 0,
        ];
        $data += $this->form_refs(null);

        $this->template->render('form', $data);
    }

    /** Referensi tambahan form: kuota quotation lain per Penjualan Program (QTN-FR-22) dan BEP harga. */
    private function form_refs($idQuotation)
    {
        $kuotaLain = $bepHarga = [];
        foreach ($this->qtn_model->get_final_penjualans() as $pj) {
            $kuotaLain[$pj['id']] = $this->qtn_model->kuota_lain($pj['id'], $idQuotation);
            // Harga BEP per peserta = HPP per peserta pada target (peringatan saja, tidak menghalangi).
            $bepHarga[$pj['id']] = (float)($this->pj_model->get_program_by_id($pj['id'])['calc']['hpp_per_pax'] ?? 0);
        }
        // Penjualan B2B yang sudah punya quotation aktif (selain yang sedang diedit) tidak dapat dipilih lagi.
        $this->db->select('q.id_penjualan')->from('quotation q')->join('penjualan_program pj', 'pj.id = q.id_penjualan')->where('pj.jenis', 'b2b')->where('q.status !=', 'batal');
        if ($idQuotation) $this->db->where('q.id !=', (int)$idQuotation);
        $b2bAktif = array_map('intval', array_column($this->db->get()->result_array(), 'id_penjualan'));
        return ['b2bAktif' => $b2bAktif, 'kuotaLain' => (object)$kuotaLain, 'bepHarga' => (object)$bepHarga, 'ketMaster' => $this->qtn_model->get_master_keterangan()];
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
            'users'      => $this->md_model->get_users(),
            'kf_tpls'    => $this->md_model->get_template_konfirmasi(null, true, $quotation['id_template_konfirmasi'] ?? null),
            'nTerisi'    => $this->qtn_model->n_terisi($id),
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

        $quotation['jenis'] = $quotation['jenis_pj'];
        $data = [
            'q' => $quotation,
            // Link Konfirmasi Kehadiran (QTN-FR-23); URL memakai domain aplikasi (Q-TB-07 belum ditetapkan)
            'link_url' => site_url('konfirmasi_kehadiran/' . $quotation['link_token']),
            'link' => $this->qtn_model->link_state($quotation),
            'isian' => $this->qtn_model->isian_diminta($quotation),
            'kolom_b2b' => $this->qtn_model->kolom_b2b($quotation),
            'so' => $this->db->select('id, nomor, status, revisi')->get_where('sales_order', ['id_quotation' => (int)$id])->row_array(),
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
        // Pada Deal hanya peserta Deal yang tercetak di Penawaran (nilai dihitung dari mereka, QTN-FR-11). Tanggal di PDF = tanggal cetak (QTN-FR-30).
        if ($q['status'] === 'deal') {
            $q['peserta'] = array_values(array_filter($q['peserta'], function ($p) { return $p['status'] === 'deal'; }));
        }
        $this->load->library('qtn_pdf');
        $bin = $this->qtn_pdf->render($q);
        if (!$this->input->get('pv')) write_log('Quotation', 'Cetak', 'Cetak Quotation ' . $q['nomor'], null, 'quotation', $id, $q['nomor']);
        $this->send_pdf($bin, 'Quotation-' . $q['nomor'] . '.pdf');
    }

    /**
     * Berkas surat konfirmasi bertanda tangan yang diunggah peserta (Public) atau PIC (B2B) = Confirmation Letter (QTN-FR-13, QTN-FR-28).
     * $idPeserta = baris quotation_peserta. ?unduh=1 mengunduh, selain itu dibuka (PDF/gambar inline). Setiap lihat/unduh dicatat (QTN-NFR-01).
     * B2B: peserta mana pun membuka surat company yang sama. Berkas ada di luar akses web langsung dan hanya dibuka lewat aplikasi (QTN-NFR-08/09).
     */
    public function surat($idPeserta)
    {
        $p = $this->db->get_where('quotation_peserta', ['id' => (int)$idPeserta])->row_array();
        if (!$p || empty($p['surat_konfirmasi'])) show_404();
        $q = $this->db->get_where('quotation', ['id' => $p['id_quotation']])->row_array();
        if (!$q || !in_array($q['status'], ['terkirim', 'deal'], true)) show_404(); // tersedia sejak Terkirim (usulan, Q-TB-05)
        $this->kirim_berkas($p['surat_konfirmasi'], $p['surat_konfirmasi_nama'] ?: 'surat-konfirmasi', 'uploads/konfirmasi_kehadiran/');
    }

    /** Dokumen customer yang diunggah saat Deal (QTN-FR-11): dapat dilihat/diunduh dari detail quotation dan Sales Order. */
    public function dokumen($idDokumen)
    {
        $d = $this->db->get_where('quotation_dokumen', ['id' => (int)$idDokumen])->row_array();
        if (!$d) show_404();
        $this->kirim_berkas($d['path_file'], $d['nama_file'], 'uploads/quotation_dokumen/');
    }

    private function kirim_berkas($path, $nama, $awalan)
    {
        if (strpos($path, $awalan) !== 0 || strpos($path, '..') !== false) show_404();
        $full = FCPATH . $path;
        if (!is_file($full)) show_404();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'doc' => 'application/msword',
                 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xls' => 'application/vnd.ms-excel',
                 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'][$ext] ?? 'application/octet-stream';
        $inline = !$this->input->get('unduh') && in_array($ext, ['pdf', 'jpg', 'png'], true);
        write_log('Quotation', $inline ? 'Lihat' : 'Unduh', ($inline ? 'Lihat ' : 'Unduh ') . $nama, null, 'quotation', null, $nama);
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace(['"', "\r", "\n"], '', $nama) . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    /** Unduh Excel semua peserta quotation (QTN-FR-28): semua isian, nama berkas surat, masuk lewat, waktu isi, status. Dibuat saat diminta, tidak disimpan. */
    public function excel($id)
    {
        $q = $this->qtn_model->get_quotation_by_id($id);
        if (!$q) show_404();
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Peserta');
        $head = ['No', 'Nama Lengkap', 'NIK', 'Jabatan', 'Instansi', 'Alamat Instansi', 'No HP', 'No WhatsApp', 'Email', 'Pengalaman', 'LSP', 'Tgl Habis Sertifikat', 'Surat Konfirmasi', 'Masuk Lewat', 'Waktu Isi', 'Status'];
        foreach ($head as $c => $h) $ws->setCellValueExplicit([$c + 1, 1], $h, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $ws->getStyle('A1:P1')->getFont()->setBold(true);
        $via = ['link' => 'Link', 'company' => 'Daftar PIC', 'manual' => 'Manual'];
        foreach ($q['peserta'] as $i => $p) {
            $row = [$i + 1, $p['nama'], $p['nik'], $p['jabatan'], $p['instansi'], $p['alamat_instansi'], $p['no_hp'], $p['no_wa'], $p['email'], $p['pengalaman'], $p['lsp'],
                    $p['tgl_habis_sertifikat'], $p['surat_konfirmasi_nama'], $via[$p['sumber']] ?? $p['sumber'], $p['tgl_isi'], $p['status']];
            foreach ($row as $c => $v) {
                // Semua sel disimpan sebagai TEKS (NIK/WA berawalan nol) dan awalan rumus dinetralkan agar isian peserta tidak dieksekusi Excel.
                $v = (string)$v;
                if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && $c !== 0) $v = "'" . $v;
                $ws->setCellValueExplicit([$c + 1, $i + 2], $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        foreach (range('A', 'P') as $col) $ws->getColumnDimension($col)->setAutoSize(true);
        write_log('Quotation', 'Unduh', 'Unduh Excel peserta ' . $q['nomor'], null, 'quotation', $id, $q['nomor']);
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Peserta-' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $q['nomor']) . '.xlsx"');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }

    public function save()
    {
        $post = $this->input->post();
        if (empty($post['id_penjualan'])) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Penjualan Program wajib dipilih']));
        }

        if (!empty($post['keterangan_json']) && empty($post['keterangan'])) {
            $post['keterangan'] = json_decode($post['keterangan_json'], true);
        }

        if (!empty($post['konfirmasi_isian_json']) && empty($post['konfirmasi_isian'])) {
            $post['konfirmasi_isian'] = json_decode($post['konfirmasi_isian_json'], true);
        }
        if (!empty($post['kolom_b2b_json']) && empty($post['kolom_b2b'])) {
            $post['kolom_b2b'] = json_decode($post['kolom_b2b_json'], true);
        }

        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->qtn_model->save_quotation($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    /** Pratinjau PDF dari isian form, sebelum disimpan (QTN-FR-18). Galat dikirim sebagai JSON. */
    public function preview()
    {
        $post = $this->input->post();
        foreach (['keterangan' => 'keterangan_json', 'konfirmasi_isian' => 'konfirmasi_isian_json', 'kolom_b2b' => 'kolom_b2b_json'] as $k => $j) {
            if (!empty($post[$j]) && empty($post[$k])) $post[$k] = json_decode($post[$j], true);
        }
        $q = $this->qtn_model->build_preview($post);
        if (!$q) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Penjualan Program wajib dipilih']));
        }
        $this->load->library('qtn_pdf');
        $this->send_pdf($this->qtn_pdf->render($q), 'Preview-Quotation.pdf');
    }

    /** Deal (QTN-FR-11): peserta tercentang (id quotation_peserta) + dokumen customer wajib (multipart). */
    public function deal($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $files = [];
        $fail = $this->collect_dokumen($files);
        if ($fail) { foreach ($files as $f) @unlink(FCPATH . $f['path']); return $this->output->set_status_header(422)->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => $fail])); }
        $res = $this->qtn_model->set_deal((int)$id, (array)$this->input->post('peserta'), $files, (string)$this->input->post('keterangan_dokumen'));
        if (empty($res['status'])) foreach ($files as $f) @unlink(FCPATH . $f['path']); // gagal: berkas yang baru diunggah dibuang
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    /** Pindahkan $_FILES['dokumen'][] ke uploads/quotation_dokumen/: tipe dari ISI berkas, maks 5 MB, nama acak (QTN-FR-11, Q-QTN-20). */
    private function collect_dokumen(array &$out)
    {
        if (empty($_FILES['dokumen']['name']) || !is_array($_FILES['dokumen']['name'])) return null;
        $dir = FCPATH . 'uploads/quotation_dokumen/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        if (!is_file($dir . '.htaccess')) @file_put_contents($dir . '.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        $mimeOk = [
            'pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
            'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        ];
        foreach ($_FILES['dokumen']['name'] as $i => $name) {
            $err = $_FILES['dokumen']['error'][$i];
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return $name . ' lebih dari 5 MB';
            if ($err !== UPLOAD_ERR_OK) return $name . ': gagal diunggah';
            $tmp = $_FILES['dokumen']['tmp_name'][$i]; $size = (int)$_FILES['dokumen']['size'][$i];
            if ($size > Quotation_model::DOK_MAX) return $name . ' lebih dari 5 MB';
            if (!is_uploaded_file($tmp)) return $name . ': berkas tidak valid';
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!isset($mimeOk[$ext])) return $name . ': hanya PDF, JPG, PNG, DOC/DOCX, XLS/XLSX';
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!in_array($mime, $mimeOk[$ext], true)) return $name . ': isi berkas tidak sesuai ekstensi .' . $ext;
            if (in_array($ext, ['docx', 'xlsx'], true)) { // zip biasa yang berganti nama tidak lolos
                $zip = new ZipArchive(); $need = $ext === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
                $ok = $zip->open($tmp) === true && $zip->locateName($need) !== false;
                if ($zip->numFiles) $zip->close();
                if (!$ok) return $name . ': isi berkas tidak sesuai ekstensi .' . $ext;
            }
            $tipe = Quotation_model::DOK_TIPE[$ext];
            $rand = bin2hex(random_bytes(16)) . '.' . $tipe;
            if (!move_uploaded_file($tmp, $dir . $rand)) return $name . ': gagal disimpan';
            $out[] = ['orig' => basename($name), 'path' => 'uploads/quotation_dokumen/' . $rand, 'tipe' => $tipe, 'size' => $size];
        }
        return null;
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
