<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penjualan_program extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index' => ['penjualan_program', 'lihat'],
        'penjualan_data' => ['penjualan_program', 'lihat', true],
        'start' => ['penjualan_program', 'tambah'],
        'create' => ['penjualan_program', 'tambah'],
        'edit' => ['penjualan_program', 'ubah'],
        'view' => ['penjualan_program', 'lihat'],
        'ringkasan' => ['penjualan_program', 'lihat', true],
        'save' => ['penjualan_program', 'simpan', true],
        'finalkan' => ['penjualan_program', 'aktif', true],
        'batalkan' => ['penjualan_program', 'aktif', true],
        'cetak_penawaran' => ['penjualan_program', 'lihat', true],
        'penawaran_pdf' => ['penjualan_program', 'lihat'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Penjualan_program_model', 'pj_model');
        $this->load->model('master_program/Master_program_model', 'mp_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
        $this->load->model('master_trainer/Master_trainer_model', 'trainer_model');
    }

    public function index($status = 'all', $jenis = 'all')
    {
        $this->template->title('Daftar Penjualan Program');
        $this->template->set('breadcrumbs', 'Penjualan Program / <b>Daftar Penjualan Program</b>');

        // Daftar dimuat lewat penjualan_data (server-side); $status/$jenis hanya nilai awal filter dari URL lama.
        $this->template->render('index', [
            'status' => in_array($status, ['draft', 'final', 'batal'], true) ? $status : 'all',
            'jenis'  => in_array($jenis, ['public', 'b2b'], true) ? $jenis : 'all',
        ]);
    }

    /** Data tabel Daftar Penjualan Program (server-side DataTables). */
    public function penjualan_data()
    {
        list($total, $filtered, $rows) = $this->pj_model->penjualan_datatable($this->input->post() ?: $this->input->get());
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $rp = function ($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
        $ST = ['draft' => 'Draft', 'final' => 'Final', 'batal' => 'Batal'];

        $data = [];
        foreach ($rows as $i => $p) {
            $id = (int)$p['id'];
            $b2b = $p['jenis'] === 'b2b';
            $items = '';
            if ($p['status'] === 'draft') {
                if (lm_can('penjualan_program', 'ubah')) $items .= '<a class="icon-btn" href="' . site_url('penjualan_program/edit/' . $id) . '" title="Edit"></a>';
                if (lm_can('penjualan_program', 'aktif')) {
                    $items .= '<button type="button" class="icon-btn" onclick="pjFinal(' . $id . ')" title="Finalkan"></button>'
                            . '<button type="button" class="icon-btn danger" onclick="pjCancel(' . $id . ')" title="Batalkan"></button>';
                }
            } else {
                $items .= '<a class="icon-btn" href="' . site_url('penjualan_program/view/' . $id) . '" title="Lihat"></a>';
                if ($p['status'] === 'final') {
                    // Public boleh banyak quotation; B2B satu quotation aktif (PJ-FR-16, QTN-FR-03).
                    $canAdd = !$b2b || (int)$p['n_qtn_aktif'] === 0;
                    if ($canAdd && lm_can('quotation_program', 'tambah')) $items .= '<a class="icon-btn" href="' . site_url('quotation_program/create_from_pj/' . $id) . '" title="+ Buat Quotation"></a>';
                    $items .= '<button type="button" class="icon-btn" onclick="pjPenawaran(' . $id . ')" title="Cetak Surat Penawaran"></button>';
                }
            }

            $pel = '—';
            if (!empty($p['tgl_mulai'])) {
                $pel = $this->fmt_tgl($p['tgl_mulai']) . (!empty($p['tgl_selesai']) && $p['tgl_selesai'] !== $p['tgl_mulai'] ? ' – ' . $this->fmt_tgl($p['tgl_selesai']) : '');
            }
            $row = [
                $i + 1, // nomor dihitung ulang di sisi klien
                '<span class="code">' . $e($p['nomor']) . '</span>',
                '<span class="tag tag-' . $e($p['jenis']) . '">' . ($b2b ? 'B2B' : 'Public') . '</span>' . ($b2b && $p['nama_company'] ? '<br><span class="muted">' . $e($p['nama_company']) . '</span>' : ''),
                $e($p['nama_paket'] ?? '—') . '<br><span class="muted">' . $e($p['nomor_mp'] ?? '') . '</span>',
                $e($pel) . '<br><span class="muted">' . $e($p['lokasi'] ?: '—') . ($p['nama_trainer'] ? ' · Trainer: ' . $e($p['nama_trainer']) : '') . '</span>',
                (int)$p['target_peserta'] . ($p['status'] === 'final' ? '<br><span class="muted">deal ' . (int)$p['n_deal'] . '</span>' : ''),
                $rp($p['harga_jual_peserta']),
                '<span class="badge st-' . $e($p['status']) . '">' . $e($ST[$p['status']] ?? $p['status']) . '</span>' . ($p['status'] === 'final' ? '<br><span class="muted">→ Quotation</span>' : ''),
                hist_cell($p),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>' . $items . riwayat_btn('penjualan_program', $p['nomor']) . '</span>',
            ];
            if ($p['status'] === 'batal') $row['DT_RowClass'] = 'is-off'; // DataTables membaca kolom lewat indeks 0..n
            $data[] = $row;
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'draw' => (int)$this->input->post_get('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data,
        ]));
    }

    public function start()
    {
        $this->template->title('Buat Penjualan Program — Pilih Master Program');
        $this->template->set('breadcrumbs', 'Penjualan Program / <b>Buat Penjualan</b>');

        $this->template->render('start', [
            'finals'  => $this->pj_model->get_final_master_programs(),
            'preselect' => (int)$this->input->get('mp'), // dari tautan "Buat Penjualan Program" di Master Program Final
        ]);
    }

    public function create($mpId = null, $jenis = 'public')
    {
        if (!$mpId) {
            redirect('penjualan_program/start');
        }

        $copy = $this->pj_model->copy_costing_from_mp($mpId);
        if (!$copy) {
            $this->session->set_flashdata('error', 'Master Program tidak ditemukan atau belum final');
            redirect('penjualan_program/start');
        }

        $jenis = in_array($jenis, ['public', 'b2b']) ? $jenis : 'public';
        $mp = $copy['header'];

        $this->template->title('Buat Penjualan Program ' . strtoupper($jenis));
        $this->template->set('breadcrumbs', 'Penjualan Program / Buat Penjualan / <b>' . strtoupper($jenis) . '</b>');

        // Salinan dari Master Program (PJ-FR-04); target B2B di bawah 10 dinaikkan ke 10 (PJ-FR-25). Waktu/lokasi/tanggal kosong.
        $program = [
            'id' => null, 'nomor' => null, 'status' => 'draft', 'id_master_program' => (int)$mp['id'], 'jenis' => $jenis,
            'id_customer' => '', 'id_trainer' => '', 'tgl_mulai' => '', 'tgl_selesai' => '', 'waktu' => '', 'lokasi' => '',
            'durasi_hari' => (int)$mp['durasi_hari'],
            'target_peserta' => ($jenis === 'b2b' && $mp['target_peserta'] < Penjualan_program_model::B2B_MIN) ? Penjualan_program_model::B2B_MIN : (int)$mp['target_peserta'],
            'harga_jual_peserta' => $mp['harga_jual_peserta'], 'target_profit' => $mp['target_profit'],
            'biaya' => $copy['biaya'],
        ];
        $mpFull = $this->mp_model->get_program_by_id($mpId);
        $this->template->render('form', $this->form_data($program, $mpFull));
    }

    public function edit($id)
    {
        $program = $this->pj_model->get_program_by_id($id);
        if (!$program) {
            $this->session->set_flashdata('error', 'Penjualan Program tidak ditemukan');
            redirect('penjualan_program');
        }

        if ($program['status'] !== 'draft') {
            redirect('penjualan_program/view/' . $id);
        }

        $this->template->title('Edit Penjualan Program ' . $program['nomor']);
        $this->template->set('breadcrumbs', 'Penjualan Program / <b>' . $program['nomor'] . '</b>');
        $this->template->render('form', $this->form_data($program, $this->mp_model->get_program_by_id($program['id_master_program'])));
    }

    /** Lihat = form yang sama dalam mode terkunci (Final / Batal), mengikuti mockup. */
    public function view($id)
    {
        $program = $this->pj_model->get_program_by_id($id);
        if (!$program) {
            show_404();
        }

        $this->template->title('Penjualan Program ' . $program['nomor']);
        $this->template->set('breadcrumbs', 'Penjualan Program / <b>' . $program['nomor'] . '</b>');
        $this->template->render('form', $this->form_data($program, $this->mp_model->get_program_by_id($program['id_master_program'])));
    }

    /** Data awal form (satu objek JSON): penjualan, info Master Program, Company, Trainer, Master Rate, id satuan per nama. */
    private function form_data($pj, $mp)
    {
        $biaya = array_map(function ($b) {
            return ['rate' => !empty($b['id_rate']) ? (int)$b['id_rate'] : null, 'tipe' => $b['tipe'], 'item' => $b['item'], 'satuan' => $b['nama_satuan'],
                    'id_satuan' => (int)$b['id_satuan'], 'harga' => (int)round($b['harga']), 'manual' => (bool)$b['is_manual']];
        }, $pj['biaya']);

        $p = [
            'id' => $pj['id'] ? (int)$pj['id'] : null, 'nomor' => $pj['nomor'], 'status' => $pj['status'], 'jenis' => $pj['jenis'],
            'mp' => (int)$pj['id_master_program'], 'company' => $pj['id_customer'] ?: '', 'trainer' => $pj['id_trainer'] ? (int)$pj['id_trainer'] : '',
            'tglMulai' => $pj['tgl_mulai'] ?: '', 'tglSelesai' => $pj['tgl_selesai'] ?: '', 'waktu' => $pj['waktu'] ?: '', 'lokasi' => $pj['lokasi'] ?: '',
            'durasi' => (int)$pj['durasi_hari'], 'targetPeserta' => (int)$pj['target_peserta'],
            'hargaJual' => (int)round($pj['harga_jual_peserta']), 'targetProfit' => $pj['target_profit'] > 0 ? (int)round($pj['target_profit']) : '',
            'biaya' => $biaya,
        ];

        $satuan = [];
        foreach (['fixed', 'variable'] as $t) {
            foreach ($this->md_model->get_satuans_by_tipe($t) as $s) $satuan[$t][$s['nama']] = (int)$s['id'];
        }

        $companies = array_map(function ($c) {
            return ['id' => $c['id_customer'], 'nama' => $c['name_customer'], 'jenis' => $c['jenis_instansi'], 'kota' => $c['kota'] ?? '', 'pic' => $c['pic_name'] ?? '',
                    'aktif' => !in_array($c['activation'] ?? null, ['nonaktif', 'inactive'], true) ? 1 : 0];
        }, $this->md_model->get_companies());

        $trainers = array_map(function ($t) {
            $k = Master_trainer_model::split_keahlian($t['bidang_keahlian'] ?? '');
            return ['id' => (int)$t['id'], 'kode' => $t['kode'], 'nama' => $t['nama'], 'aktif' => (int)$t['aktif'], 'keahlian' => array_slice($k, 0, 2)];
        }, $this->trainer_model->get_trainers());

        return ['init' => [
            'program' => $p,
            'mpInfo'  => ['nomor' => $mp['nomor'] ?? '', 'judul' => $mp['nama_paket'] ?? '', 'sertifikat' => $mp['nama_sertifikasi'] ?? '',
                          'durasi' => (int)($mp['durasi_hari'] ?? 0), 'target' => (int)($mp['target_peserta'] ?? 0), 'harga' => (int)round($mp['harga_jual_peserta'] ?? 0)],
            'companies' => $companies, 'trainers' => $trainers, 'rates' => $this->mp_model->get_rates_lookup(), 'satuan' => $satuan,
            'b2bMin' => Penjualan_program_model::B2B_MIN,
        ]];
    }

    /** Ringkasan untuk popup Finalkan / Batalkan. */
    public function ringkasan($id)
    {
        $r = $this->pj_model->get_ringkasan($id);
        if (!$r) {
            return $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Penjualan Program tidak ditemukan']));
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 1, 'data' => $r]));
    }

    public function save()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->pj_model->save_penjualan($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function finalkan($id)
    {
        $res = $this->pj_model->set_status($id, 'final');
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function batalkan($id)
    {
        $res = $this->pj_model->set_status($id, 'batal');
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    // ---------- Surat Penawaran (PJ-FR-20): hanya Penjualan Program Final ----------

    private function fmt_tgl($d)
    {
        $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t = strtotime($d);
        return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t);
    }

    /** Data surat penawaran; null bila bukan Final. */
    private function penawaran_data($id)
    {
        $p = $this->pj_model->get_program_by_id($id);
        if (!$p || $p['status'] !== 'final') return null;

        $company = $p['jenis'] === 'b2b' && $p['id_customer'] ? $this->md_model->get_companies($p['id_customer']) : null;
        $pel = '—';
        if (!empty($p['tgl_mulai'])) $pel = $this->fmt_tgl($p['tgl_mulai']) . (!empty($p['tgl_selesai']) && $p['tgl_selesai'] !== $p['tgl_mulai'] ? ' – ' . $this->fmt_tgl($p['tgl_selesai']) : '');
        return ['p' => $p, 'company' => $company, 'pelaksanaan' => $pel . ' · ' . ($p['lokasi'] ?: '—'), 'total' => $p['calc']['pendapatan']];
    }

    /** Modal pratinjau Surat Penawaran (isi sama dengan PDF). */
    public function cetak_penawaran($id)
    {
        $d = $this->penawaran_data($id);
        if (!$d) {
            return $this->output->set_status_header(400)->set_content_type('text/plain')->set_output('Hanya Penjualan Program Final yang dapat dicetak sebagai surat penawaran');
        }
        $this->load->view('penawaran_modal', $d);
    }

    public function penawaran_pdf($id)
    {
        $d = $this->penawaran_data($id);
        if (!$d) {
            show_error('Hanya Penjualan Program Final yang dapat dicetak sebagai surat penawaran', 400);
        }

        $html = $this->load->view('penawaran_pdf', $d, true);

        // mPDF 8.3 di PHP 8.5 dapat memunculkan notice deprecated; notice di keluaran akan merusak berkas PDF.
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED);

        $tmp = sys_get_temp_dir() . '/labmania_mpdf';
        if (!is_dir($tmp)) @mkdir($tmp, 0775, true);

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4', 'orientation' => 'P', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 10,
            'margin_left' => 20, 'margin_right' => 20, 'margin_top' => 18, 'margin_bottom' => 18,
        ]);
        $mpdf->SetTitle('Surat Penawaran ' . $d['p']['nomor']);
        $mpdf->SetAuthor('Labmania');
        $mpdf->WriteHTML($html);

        while (ob_get_level()) ob_end_clean();
        $mpdf->Output('Surat-Penawaran-' . $d['p']['nomor'] . '.pdf', \Mpdf\Output\Destination::INLINE);
        error_reporting($level);
        exit;
    }
}
