<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Permission_group extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index'      => ['permission_group', 'lihat'],
        'data'       => ['permission_group', 'lihat', true],
        'akses'      => ['permission_group', 'lihat'],
        'save'       => ['permission_group', 'simpan', true],
        'delete'     => ['permission_group', 'hapus', true],
        'save_akses' => ['permission_group', 'ubah', true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Permission_group_model', 'pg_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    private function json($res)
    {
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function index()
    {
        $this->template->title('Permission Group');
        $this->template->set('breadcrumbs', 'Pengaturan / <b>Permission Group</b>');

        $this->template->render('index', []);
    }

    // Sumber data DataTables (server-side)
    public function data()
    {
        $post = $this->input->post() ?: $this->input->get();
        list($total, $filtered, $rows) = $this->pg_model->group_datatable($post);
        $rows = $this->md_model->with_audit($rows, 'groups', 'nm_group');

        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
        $canUbah  = lm_can('permission_group', 'ubah');
        $canHapus = lm_can('permission_group', 'hapus');
        $start = (int)($post['start'] ?? 0);

        $out = [];
        foreach ($rows as $i => $g) {
            $id = (int)$g['id_group'];
            $isAdmin = ($id === 1);
            $isSistem = $isAdmin || !empty($g['st_default']);
            $obj = $e(json_encode(['id' => $id, 'nama' => $g['nm_group'], 'ket' => $g['ket'], 'admin' => $isAdmin]));

            $items = '';
            if ($canUbah) $items .= '<button type="button" class="icon-btn" onclick="openGroupModal(' . $obj . ')" title="Edit"></button>';
            $items .= '<a href="' . site_url('permission_group/akses/' . $id) . '" title="' . ($canUbah && !$isAdmin ? 'Atur Izin' : 'Lihat Izin') . '"></a>';
            if ($canHapus && !$isSistem) {
                $items .= '<button type="button" class="icon-btn" onclick="delGroup(' . $id . ', ' . $e(json_encode($g['nm_group'])) . ')" title="Hapus"></button>';
            }
            $items .= riwayat_btn('groups', $g['nm_group']);

            $out[] = [
                $start + $i + 1,
                '<b>' . $e($g['nm_group']) . '</b>' . ($isSistem ? ' <span class="tag tag-public">Sistem</span>' : ''),
                $e($g['ket'] ?: '—'),
                (int)$g['n_user'],
                $isAdmin ? '<span class="muted">Semua izin</span>' : ((int)$g['n_izin'] . ' <span class="muted">izin diberikan</span>'),
                hist_cell($g),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>' . $items . '</span>',
            ];
        }
        return $this->json(['draw' => (int)($post['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $out]);
    }

    public function save()
    {
        $post = $this->input->post();
        return $this->json($this->pg_model->save_group($post, !empty($post['id']) ? (int)$post['id'] : null));
    }

    public function delete($id)
    {
        return $this->json($this->pg_model->delete_group((int)$id));
    }

    // Halaman matriks izin satu group
    public function akses($id)
    {
        $group = $this->pg_model->get_group((int)$id);
        if (!$group) show_404();

        $this->template->title('Atur Izin — ' . $group['nm_group']);
        $this->template->set('breadcrumbs', 'Pengaturan / Permission Group / <b>Atur Izin</b>');

        $this->template->render('akses', [
            'group'  => $group,
            'matrix' => $this->pg_model->get_matrix((int)$id),
            'aksi'   => lm_aksi_list(),
        ]);
    }

    public function save_akses()
    {
        $akses = $this->input->post('akses');
        return $this->json($this->pg_model->save_akses((int)$this->input->post('id'), is_array($akses) ? $akses : []));
    }
}
