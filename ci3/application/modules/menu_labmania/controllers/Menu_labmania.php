<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Menu_labmania extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index'  => ['menu_labmania', 'lihat'],
        'data'   => ['menu_labmania', 'lihat', true],
        'save'   => ['menu_labmania', 'simpan', true],
        'toggle' => ['menu_labmania', 'aktif', true],
        'move'   => ['menu_labmania', 'ubah', true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_labmania_model', 'menu_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    private function json($res)
    {
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function index()
    {
        $this->template->title('Menu Sidebar');
        $this->template->set('breadcrumbs', 'Pengaturan / <b>Menu Sidebar</b>');

        $this->template->render('index', [
            'moduls'  => $this->menu_model->get_moduls(),
            'icons'   => array_keys(lm_icons()),
            'aksi'    => lm_aksi_list(),
        ]);
    }

    // Sumber data DataTables (server-side); urutan mengikuti pohon menu sidebar
    public function data()
    {
        $post = $this->input->post() ?: $this->input->get();
        list($total, $filtered, $rows) = $this->menu_model->menu_datatable($post);
        $rows = $this->md_model->with_audit($rows, 'ms_menu', 'kode');

        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
        $aksiLabel = lm_aksi_list();
        $canUbah  = lm_can('menu_labmania', 'ubah');
        $canAktif = lm_can('menu_labmania', 'aktif');

        $out = [];
        foreach ($rows as $m) {
            $id = (int)$m['id'];
            $isModul = ((int)$m['parent_id'] === 0 && $m['link'] === '#');
            $obj = $e(json_encode(['id' => $id, 'tipe' => $isModul ? 'modul' : 'menu', 'parent_id' => (int)$m['parent_id'], 'judul' => $m['judul'], 'link' => $m['link'],
                'ikon' => $m['ikon'], 'kelompok' => (int)$m['kelompok'], 'aksi' => $m['aksi'], 'sistem' => (int)$m['sistem'], 'kode' => $m['kode']]));

            $items = '';
            if ($canUbah) {
                $items .= '<button type="button" class="icon-btn" onclick="openMenuModal(' . $obj . ')" title="Edit"></button>'
                    . '<button type="button" class="icon-btn" onclick="moveMenu(' . $id . ', \'up\')" title="Naik"></button>'
                    . '<button type="button" class="icon-btn" onclick="moveMenu(' . $id . ', \'down\')" title="Turun"></button>';
            }
            if ($canAktif && !$m['sistem']) {
                $items .= '<button type="button" class="icon-btn" onclick="toggleMenu(' . $id . ')" title="' . ($m['aktif'] ? 'Nonaktifkan' : 'Aktifkan') . '"></button>';
            }
            $items .= riwayat_btn('ms_menu', $m['kode']);

            $aksi = '';
            foreach ($m['aksi'] as $a) $aksi .= '<span class="ps-chip">' . $e($aksiLabel[$a] ?? $a) . '</span> ';

            $out[] = [
                'DT_RowClass' => trim(($m['aktif'] ? '' : 'is-off ') . ($m['level'] === 0 ? 'menu-top' : 'menu-child')),
                ($m['level'] === 0 && $m['ikon'] ? '<span class="menu-ico">' . lm_icon_svg($m['ikon'], 15) . '</span>' : '')
                    . '<b>' . $e($m['judul']) . '</b>' . ($m['sistem'] ? ' <span class="tag tag-public">Sistem</span>' : '') . '<br><span class="muted">' . $e($m['kode']) . '</span>',
                $isModul ? '<span class="muted">Modul</span>' : '<span class="code">' . $e($m['link']) . '</span>',
                $isModul ? '—' : (int)$m['kelompok'],
                ($isModul || $aksi === '') ? '<span class="muted">—</span>' : $aksi,
                '<span class="badge ' . ($m['aktif'] ? 'on' : 'off') . '">' . ($m['aktif'] ? 'Aktif' : 'Nonaktif') . '</span>',
                hist_cell($m),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>' . $items . '</span>',
            ];
        }
        // DataTables 1.10 membaca baris berindeks angka; DT_RowClass dipisah dari data sel
        $data = [];
        foreach ($out as $r) {
            $cls = $r['DT_RowClass']; unset($r['DT_RowClass']);
            $data[] = array_merge(array_values($r), ['DT_RowClass' => $cls]);
        }
        return $this->json(['draw' => (int)($post['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
    }

    public function save()
    {
        $post = $this->input->post();
        return $this->json($this->menu_model->save_menu($post, !empty($post['id']) ? (int)$post['id'] : null));
    }

    public function toggle($id)
    {
        return $this->json($this->menu_model->toggle_menu((int)$id));
    }

    public function move($id, $dir)
    {
        return $this->json($this->menu_model->move_menu((int)$id, $dir === 'up' ? 'up' : 'down'));
    }
}
