<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_program extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index' => ['master_program', 'lihat'],
        'program_data' => ['master_program', 'lihat', true],
        'add' => ['master_program', 'tambah'],
        'edit' => ['master_program', 'ubah'],
        'view' => ['master_program', 'lihat'],
        'ringkasan' => ['master_program', 'lihat', true],
        'save' => ['master_program', 'simpan', true],
        'finalkan' => ['master_program', 'aktif', true],
        'batalkan' => ['master_program', 'aktif', true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Master_program_model', 'mp_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    public function index($status = 'all')
    {
        $this->template->title('Daftar Master Program');
        $this->template->set('breadcrumbs', 'Penjualan Program / <b>Daftar Master Program</b>');

        // Daftar dimuat lewat program_data (server-side); $status hanya nilai awal filter dari URL lama.
        $this->template->render('index', ['status' => in_array($status, ['draft', 'final', 'batal'], true) ? $status : 'all']);
    }

    /** Data tabel Daftar Master Program (server-side DataTables). */
    public function program_data()
    {
        list($total, $filtered, $rows) = $this->mp_model->program_datatable($this->input->post() ?: $this->input->get());
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $rp = function ($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
        $ST = ['draft' => 'Draft', 'final' => 'Final', 'batal' => 'Batal'];
        $svg = function ($d) { return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $d . '</svg>'; };

        $data = [];
        foreach ($rows as $i => $p) {
            $id = (int)$p['id'];
            $draft = $p['status'] === 'draft';
            $items = '';
            if ($draft) {
                if (lm_can('master_program', 'ubah')) $items .= '<a class="icon-btn" href="' . site_url('master_program/edit/' . $id) . '" title="Edit"></a>';
                if (lm_can('master_program', 'aktif')) {
                    $items .= '<button type="button" class="icon-btn" onclick="mpFinal(' . $id . ')" title="Finalkan"></button>'
                            . '<button type="button" class="icon-btn danger" onclick="mpCancel(' . $id . ')" title="Batalkan"></button>';
                }
            } else {
                $items .= '<a class="icon-btn" href="' . site_url('master_program/view/' . $id) . '" title="Lihat"></a>';
            }
            $row = [
                $i + 1, // nomor dihitung ulang di sisi klien
                '<span class="code">' . $e($p['nomor']) . '</span>',
                $e($p['nama_paket'] ?? '—') . '<br><span class="muted">' . $e($p['nomor_paket'] ?? '') . ' · ' . $e($p['nama_sertifikasi'] ?? '') . '</span>',
                (int)$p['durasi_hari'] . ' hari',
                (int)$p['target_peserta'],
                $rp($p['harga_jual_peserta']),
                $rp($p['hpp']),
                '<span class="badge st-' . $e($p['status']) . '">' . $e($ST[$p['status']] ?? $p['status']) . '</span>',
                hist_cell($p),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>' . $items . riwayat_btn('master_program', $p['nomor']) . '</span>',
            ];
            if ($p['status'] === 'batal') $row['DT_RowClass'] = 'is-off'; // DataTables membaca kolom lewat indeks 0..n
            $data[] = $row;
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'draw' => (int)$this->input->post_get('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data,
        ]));
    }

    public function add()
    {
        $this->template->title('Buat Master Program');
        $this->template->set('breadcrumbs', 'Penjualan Program / Master Program / <b>Buat Master Program</b>');
        $this->template->render('form', $this->form_data(null));
    }

    public function edit($id)
    {
        $program = $this->mp_model->get_program_by_id($id);
        if (!$program) {
            $this->session->set_flashdata('error', 'Master Program tidak ditemukan');
            redirect('master_program');
        }

        if ($program['status'] !== 'draft') {
            redirect('master_program/view/' . $id);
        }

        $this->template->title('Edit Master Program ' . $program['nomor']);
        $this->template->set('breadcrumbs', 'Penjualan Program / Master Program / <b>' . $program['nomor'] . '</b>');
        $this->template->render('form', $this->form_data($program));
    }

    /** Lihat = form yang sama dalam mode terkunci (Final / Batal), mengikuti mockup. */
    public function view($id)
    {
        $program = $this->mp_model->get_program_by_id($id);
        if (!$program) {
            show_404();
        }

        $this->template->title('Master Program ' . $program['nomor']);
        $this->template->set('breadcrumbs', 'Penjualan Program / Master Program / <b>' . $program['nomor'] . '</b>');
        $this->template->render('form', $this->form_data($program));
    }

    /** Data awal form (satu objek JSON): program, pilihan Paket, Master Rate (untuk label default & pilihan), id satuan per nama. */
    private function form_data($program)
    {
        $rates = $this->mp_model->get_rates_lookup();

        if ($program) {
            $biaya = array_map(function ($b) {
                return ['rate' => $b['id_rate'] ? (int)$b['id_rate'] : null, 'tipe' => $b['tipe'], 'item' => $b['item'],
                        'satuan' => $b['nama_satuan'], 'id_satuan' => (int)$b['id_satuan'], 'harga' => (int)round($b['harga']), 'manual' => (bool)$b['is_manual']];
            }, $program['biaya']);
            $p = ['id' => (int)$program['id'], 'nomor' => $program['nomor'], 'status' => $program['status'], 'paket' => (int)$program['id_paket_produk'],
                  'durasi' => (int)$program['durasi_hari'], 'targetPeserta' => (int)$program['target_peserta'],
                  'hargaJual' => (int)round($program['harga_jual_peserta']), 'targetProfit' => $program['target_profit'] > 0 ? (int)round($program['target_profit']) : '', 'biaya' => $biaya];
        } else {
            // Program baru: semua Fixed Cost aktif langsung terisi Default Cost (MP-FR-06); durasi 1, target peserta 10, harga jual kosong (wajib).
            $biaya = array_map(function ($r) {
                return ['rate' => (int)$r['id'], 'tipe' => 'fixed', 'item' => $r['item'], 'satuan' => $r['nama_satuan'], 'id_satuan' => (int)$r['id_satuan'],
                        'harga' => (int)round($r['default_cost']), 'manual' => false];
            }, $this->mp_model->get_default_fixed_costs());
            $p = ['id' => null, 'nomor' => null, 'status' => 'draft', 'paket' => '', 'durasi' => 1, 'targetPeserta' => 10, 'hargaJual' => '', 'targetProfit' => '', 'biaya' => $biaya];
        }

        $satuan = [];
        foreach (['fixed', 'variable'] as $t) {
            foreach ($this->md_model->get_satuans_by_tipe($t) as $s) $satuan[$t][$s['nama']] = (int)$s['id'];
        }

        $pakets = array_map(function ($pk) {
            return ['id' => (int)$pk['id'], 'nomor' => $pk['nomor'], 'judul' => $pk['judul'], 'aktif' => (int)$pk['aktif'],
                    'produk' => $pk['nama_produk'], 'bidang' => $pk['nama_bidang'], 'sertifikat' => $pk['nama_sertifikasi']];
        }, $this->mp_model->get_paket_options($program['id_paket_produk'] ?? null));

        return ['init' => ['program' => $p, 'pakets' => $pakets, 'rates' => $rates, 'satuan' => $satuan]];
    }

    /** Ringkasan untuk popup Finalkan / Batalkan. */
    public function ringkasan($id)
    {
        $r = $this->mp_model->get_ringkasan($id);
        if (!$r) {
            return $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Master Program tidak ditemukan']));
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 1, 'data' => $r]));
    }

    public function save()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->mp_model->save_program($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function finalkan($id)
    {
        $res = $this->mp_model->set_status($id, 'final');
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function batalkan($id)
    {
        $res = $this->mp_model->set_status($id, 'batal');
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }
}
