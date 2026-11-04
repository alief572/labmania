<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_data extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'jenis_produk' => ['master_data.jenis_produk', 'lihat'],
        'save_jenis_produk' => ['master_data.jenis_produk', 'simpan', true],
        'toggle_jenis_produk' => ['master_data.jenis_produk', 'aktif', true],
        'jenis_sertifikasi' => ['master_data.jenis_sertifikasi', 'lihat'],
        'save_jenis_sertifikasi' => ['master_data.jenis_sertifikasi', 'simpan', true],
        'toggle_jenis_sertifikasi' => ['master_data.jenis_sertifikasi', 'aktif', true],
        'jenis_bidang' => ['master_data.jenis_bidang', 'lihat'],
        'save_jenis_bidang' => ['master_data.jenis_bidang', 'simpan', true],
        'toggle_jenis_bidang' => ['master_data.jenis_bidang', 'aktif', true],
        'attr_data' => ['master_data.{seg3}', 'lihat', true],
        'attr_next_code' => ['master_data.{seg3}', 'tambah', true],
        'paket_produk' => ['master_data.paket_produk', 'lihat'],
        'paket_data' => ['master_data.paket_produk', 'lihat', true],
        'combine' => ['master_data.paket_produk', 'tambah'],
        'save_combine' => ['master_data.paket_produk', 'tambah', true],
        'update_paket' => ['master_data.paket_produk', 'ubah', true],
        'toggle_paket' => ['master_data.paket_produk', 'aktif', true],
        'katalog' => ['master_data.katalog', 'lihat'],
        'peserta' => ['master_data.peserta', 'lihat'],
        'peserta_data' => ['master_data.peserta', 'lihat', true],
        'peserta_next_code' => ['master_data.peserta', 'tambah', true],
        'save_peserta' => ['master_data.peserta', 'simpan', true],
        'delete_peserta' => ['master_data.peserta', 'hapus', true],
        'company' => ['master_data.company', 'lihat'],
        'company_data' => ['master_data.company', 'lihat', true],
        'company_next_code' => ['master_data.company', 'tambah', true],
        'toggle_company' => ['master_data.company', 'aktif', true],
        'save_company' => ['master_data.company', 'simpan', true],
        'delete_company' => ['master_data.company', 'hapus', true],
        'rate' => ['master_data.rate', 'lihat'],
        'rate_data' => ['master_data.rate', 'lihat', true],
        'rate_next_code' => ['master_data.rate', 'tambah', true],
        'save_rate' => ['master_data.rate', 'simpan', true],
        'toggle_rate' => ['master_data.rate', 'aktif', true],
        'template_keterangan' => ['master_data.template_keterangan', 'lihat'],
        'template_keterangan_data' => ['master_data.template_keterangan', 'lihat', true],
        'template_keterangan_next_code' => ['master_data.template_keterangan', 'tambah', true],
        'save_template_keterangan' => ['master_data.template_keterangan', 'simpan', true],
        'toggle_template_keterangan' => ['master_data.template_keterangan', 'aktif', true],
        'template_konfirmasi' => ['master_data.template_konfirmasi', 'lihat'],
        'template_konfirmasi_data' => ['master_data.template_konfirmasi', 'lihat', true],
        'template_konfirmasi_next_code' => ['master_data.template_konfirmasi', 'tambah', true],
        'template_konfirmasi_contoh' => ['master_data.template_konfirmasi', 'lihat', true],
        'save_template_konfirmasi' => ['master_data.template_konfirmasi', 'simpan', true],
        'toggle_template_konfirmasi' => ['master_data.template_konfirmasi', 'aktif', true],
        'users' => ['master_data.users', 'lihat'],
        'users_data' => ['master_data.users', 'lihat', true],
        'save_user' => ['master_data.users', 'simpan', true],
        'toggle_user' => ['master_data.users', 'aktif', true],
        'reset_password_user' => ['master_data.users', 'ubah', true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Master_data_model', 'md_model');
    }

    // =========================================================================
    // 1. ATRIBUT PRODUK
    // =========================================================================

    public function jenis_produk()
    {
        $this->template->title('Jenis Produk');
        $this->template->set('breadcrumbs', 'Master Data / <b>Jenis Produk</b>');
        $data = [
            'type'        => 'jenis_produk',
            'title'       => 'Jenis Produk',
            'ref'         => 'FR-04',
            'desc'        => 'Jenis produk yang dijual Labmania, mis. Pelatihan atau Sertifikasi.',
            'save_url'    => site_url('master_data/save_jenis_produk'),
            'toggle_url'  => site_url('master_data/toggle_jenis_produk'),
            'code_label'  => 'Kode Jenis Product',
            'name_label'  => 'Jenis Product'
        ];
        $this->template->render('attr_list', $data);
    }

    /** Data tabel atribut (server-side DataTables). $type = jenis_produk | jenis_sertifikasi | jenis_bidang. */
    public function attr_data($type)
    {
        $map = ['jenis_produk' => 'Jenis Produk', 'jenis_sertifikasi' => 'Jenis Sertifikat', 'jenis_bidang' => 'Jenis Bidang'];
        if (!isset($map[$type])) show_404();
        $table = 'ms_' . $type;
        $kodeMenu = 'master_data.' . $type;

        list($total, $filtered, $rows) = $this->md_model->attr_datatable($table, $this->input->post() ?: $this->input->get());
        $e = function ($v) { return htmlspecialchars((string)$v); };
        $svg = function ($d) { return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $d . '</svg>'; };

        $data = [];
        foreach ($rows as $i => $r) {
            $id = (int)$r['id'];
            $data[] = [
                $i + 1, // nomor dihitung ulang di sisi klien
                '<span class="code">' . $e($r['kode']) . '</span>',
                '<b>' . $e($r['nama']) . '</b>',
                '<span class="badge ' . ($r['aktif'] ? 'on' : 'off') . '">' . ($r['aktif'] ? 'Aktif' : 'Nonaktif') . '</span>',
                hist_cell($r),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button>'
                . '<span class="gear-src" hidden>'
                . (lm_can($kodeMenu, 'ubah') ? '<button class="icon-btn" onclick="openAttrModal(' . $id . ', \'' . $e(addslashes($r['nama'])) . '\', \'' . $e($r['kode']) . '\')" title="Edit">' . $svg('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>') . '</button>' : '')
                . (lm_can($kodeMenu, 'aktif') ? '<button class="icon-btn" onclick="toggleAttr(' . $id . ', this)" title="' . ($r['aktif'] ? 'Nonaktifkan' : 'Aktifkan') . '">' . $svg('<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>') . '</button>' : '')
                . riwayat_btn($table, $r['kode']) . '</span>',
            ];
            if (!$r['aktif']) $data[count($data) - 1]['DT_RowClass'] = 'is-off'; // DataTables membaca kolom lewat indeks 0..n
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'draw' => (int)$this->input->post_get('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data,
        ]));
    }

    // ---- Helper server-side DataTables (Product Combine, Rate, Template, User) ----
    private function ssp_params() { return $this->input->post() ?: $this->input->get(); }

    private function ssp_out($total, $filtered, $data)
    {
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'draw' => (int)$this->input->post_get('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data,
        ], JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** Baris DataTables: sel indeks 0..n + atribut tr (data-id, data-json dibaca tombol Edit) + redup bila nonaktif. */
    private function ssp_row($cells, $r, $withJson = true)
    {
        $cells['DT_RowAttr'] = ['data-id' => (int)$r['id']] + ($withJson ? ['data-json' => json_encode($r, JSON_PARTIAL_OUTPUT_ON_ERROR)] : []);
        if (isset($r['aktif']) && !$r['aktif']) $cells['DT_RowClass'] = 'is-off';
        return $cells;
    }

    private function ibtn($onclick, $title, $path)
    {
        return '<button class="icon-btn" onclick="' . $onclick . '" title="' . $title . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $path . '</svg></button>';
    }

    private function gear_cell($buttons)
    {
        return '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>' . $buttons . '</span>';
    }

    private function badge_aktif($on)
    {
        return '<span class="badge ' . ($on ? 'on' : 'off') . '">' . ($on ? 'Aktif' : 'Nonaktif') . '</span>';
    }

    private static $P_EDIT = '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>';
    private static $P_POWER = '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>';
    private static $P_EYE = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';

    /** Data tabel Product Combine (server-side). */
    public function paket_data()
    {
        list($t, $f, $rows) = $this->md_model->ssp_datatable('paket_produk', 'pp', $this->ssp_params(), [
            'select' => 'pp.*, jp.nama as nama_produk, js.nama as nama_sertifikasi, jb.nama as nama_bidang',
            'join'   => [['ms_jenis_produk jp', 'pp.id_jenis_produk = jp.id'], ['ms_jenis_sertifikasi js', 'pp.id_jenis_sertifikasi = js.id'], ['ms_jenis_bidang jb', 'pp.id_jenis_bidang = jb.id']],
            'search' => ['pp.nomor', 'pp.judul', 'jp.nama', 'jb.nama', 'js.nama'],
            'order'  => [1 => 'pp.nomor', 2 => 'pp.judul', 3 => 'jp.nama', 4 => 'jb.nama', 5 => 'js.nama', 6 => 'pp.aktif'],
            'default' => 'pp.id', 'kode' => 'nomor',
        ]);
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $data = [];
        foreach ($rows as $i => $r) {
            $id = (int)$r['id'];
            $data[] = $this->ssp_row([
                $i + 1,
                '<span class="code">' . $e($r['nomor']) . '</span>',
                '<b>' . $e($r['judul']) . '</b>',
                '<span class="tag tag-public">' . $e($r['nama_produk'] ?? '—') . '</span>',
                $e($r['nama_bidang'] ?? '—'),
                '<span class="tag tag-b2b">' . $e($r['nama_sertifikasi'] ?? '—') . '</span>',
                $this->badge_aktif($r['aktif']),
                hist_cell($r),
                $this->gear_cell(
                    (lm_can('master_data.paket_produk', 'ubah') ? $this->ibtn('openEditPaketModal(' . $id . ', \'' . $e(addslashes($r['judul'])) . '\', \'' . $e($r['nomor']) . '\')', 'Edit Judul', self::$P_EDIT) : '')
                    . (lm_can('master_data.paket_produk', 'aktif') ? $this->ibtn('togglePaket(' . $id . ')', $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan', self::$P_POWER) : '')
                    . riwayat_btn('paket_produk', $r['nomor'])
                ),
            ], $r, false);
        }
        return $this->ssp_out($t, $f, $data);
    }

    /** Data tabel Master Rate per tab fixed|variable (server-side). */
    public function rate_data($tab = 'fixed')
    {
        $tab = in_array($tab, ['fixed', 'variable'], true) ? $tab : 'fixed';
        list($t, $f, $rows) = $this->md_model->ssp_datatable('ms_rate', 'r', $this->ssp_params(), [
            'select' => 'r.*, s.nama as nama_satuan, s.code as kode_satuan',
            'join'   => [['ms_satuan s', 'r.id_satuan = s.id']],
            'where'  => ['r.tipe' => $tab],
            'status' => 'r.aktif',
            'search' => ['r.kode', 'r.item', 'r.keterangan'],
            'order'  => [1 => 'r.kode', 2 => 'r.item', 3 => 's.nama', 4 => 'r.default_cost', 5 => 'r.aktif'],
            'default' => 'r.id',
        ]);
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $data = [];
        foreach ($rows as $i => $r) {
            $id = (int)$r['id'];
            $data[] = $this->ssp_row([
                $i + 1,
                '<span class="code">' . $e($r['kode']) . '</span>',
                $e($r['item']) . (trim((string)$r['keterangan']) !== '' ? '<br><span class="muted">' . $e($r['keterangan']) . '</span>' : ''),
                $e($r['nama_satuan'] ?? '—'),
                'Rp ' . number_format((float)$r['default_cost'], 0, ',', '.'),
                $this->badge_aktif($r['aktif']),
                hist_cell($r),
                $this->gear_cell(
                    (lm_can('master_data.rate', 'ubah') ? $this->ibtn('editRate(this)', 'Edit', self::$P_EDIT) : '')
                    . (lm_can('master_data.rate', 'aktif') ? $this->ibtn('toggleRate(' . $id . ')', $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan', self::$P_POWER) : '')
                    . riwayat_btn('ms_rate', $r['kode'])
                ),
            ], $r);
        }
        return $this->ssp_out($t, $f, $data);
    }

    /** Data tabel Template Keterangan Quotation (server-side). */
    public function template_keterangan_data()
    {
        $KAT = ['syarat' => 'Syarat keikutsertaan', 'termasuk' => 'Termasuk', 'tidak' => 'Tidak termasuk', 'dokumen' => 'Dokumen persyaratan', 'klausul' => 'Klausul tanda tangan'];
        $BERLAKU = ['semua' => 'Semua', 'public' => 'Public', 'b2b' => 'B2B'];
        list($t, $f, $rows) = $this->md_model->ssp_datatable('ms_template_keterangan', 'k', $this->ssp_params(), [
            'status'  => 'k.aktif',
            'filters' => ['kategori' => 'k.kategori'],
            'search'  => ['k.kode', 'k.teks'],
            'order'   => [1 => 'k.kode', 2 => 'k.kategori', 3 => 'k.teks', 4 => 'k.berlaku_untuk', 5 => 'k.is_default', 6 => 'k.aktif'],
            'default' => "FIELD(k.kategori, 'syarat', 'termasuk', 'tidak', 'dokumen', 'klausul'), k.id",
        ]);
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $data = [];
        foreach ($rows as $i => $r) {
            $id = (int)$r['id'];
            $data[] = $this->ssp_row([
                $i + 1,
                '<span class="code">' . $e($r['kode']) . '</span>',
                $e($KAT[$r['kategori']] ?? $r['kategori']),
                $e($r['teks']),
                $r['berlaku_untuk'] === 'semua' ? 'Semua' : '<span class="tag tag-' . $e($r['berlaku_untuk']) . '">' . $e($BERLAKU[$r['berlaku_untuk']] ?? $r['berlaku_untuk']) . '</span>',
                $r['is_default'] ? '<b>Ya</b>' : '<span class="muted">Tidak</span>',
                $this->badge_aktif($r['aktif']),
                hist_cell($r),
                $this->gear_cell(
                    (lm_can('master_data.template_keterangan', 'ubah') ? $this->ibtn('editKet(this)', 'Edit', self::$P_EDIT) : '')
                    . (lm_can('master_data.template_keterangan', 'aktif') ? $this->ibtn('toggleKet(' . $id . ')', $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan', self::$P_POWER) : '')
                    . riwayat_btn('ms_template_keterangan', $r['kode'])
                ),
            ], $r);
        }
        return $this->ssp_out($t, $f, $data);
    }

    /** Data tabel Template Formulir Konfirmasi (server-side). */
    public function template_konfirmasi_data()
    {
        list($t, $f, $rows) = $this->md_model->ssp_datatable('ms_template_konfirmasi', 'k', $this->ssp_params(), [
            'select' => 'k.*, jp.nama as nama_produk',
            'join'   => [['ms_jenis_produk jp', 'k.id_jenis_produk = jp.id']],
            'status' => 'k.aktif',
            'search' => ['k.kode', 'k.nama'],
            'order'  => [1 => 'k.kode', 2 => 'k.nama', 3 => 'jp.nama', 6 => 'k.aktif'],
            'default' => 'k.id',
            'audit'  => 'ms_template_konfirmasi',
        ]);
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $mark = function ($s) use ($e) { return preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $e($s)); };
        $STD = ['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'email']; // isian umum; selain ini tampil sebagai chip
        $data = [];
        foreach ($rows as $i => $r) {
            $id = (int)$r['id'];
            $isian = json_decode($r['isian'] ?? '[]', true) ?: [];
            $chips = '';
            foreach ($isian as $k) if (!in_array($k, $STD, true) && isset(Master_data_model::KF_FIELDS[$k])) $chips .= '<span class="ps-chip">' . $e(Master_data_model::KF_FIELDS[$k]) . '</span> ';
            $p = (string)$r['pernyataan'];
            $data[] = $this->ssp_row([
                $i + 1,
                '<span class="code">' . $e($r['kode']) . '</span>',
                '<b>' . $e($r['nama']) . '</b><br><span class="muted">subject: kegiatan ' . $e($r['label_kegiatan']) . '</span>',
                !empty($r['nama_produk']) ? $e($r['nama_produk']) : '<span class="muted">— (pilih manual)</span>',
                '<b>' . count($isian) . '</b> <span class="muted">isian</span><br>' . $chips,
                '<span class="muted">' . $mark(mb_strlen($p) > 110 ? mb_substr($p, 0, 110) . '…' : $p) . '</span>',
                $this->badge_aktif($r['aktif']),
                hist_cell($r),
                $this->gear_cell(
                    (lm_can('master_data.template_konfirmasi', 'ubah') ? $this->ibtn('editKf(this)', 'Edit', self::$P_EDIT) : '')
                    . $this->ibtn('contohKf(' . $id . ')', 'Lihat contoh', self::$P_EYE)
                    . (lm_can('master_data.template_konfirmasi', 'aktif') ? $this->ibtn('toggleKf(' . $id . ')', $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan', self::$P_POWER) : '')
                    . riwayat_btn('ms_template_konfirmasi', $r['kode'])
                ),
            ], $r);
        }
        return $this->ssp_out($t, $f, $data);
    }

    /** Data tabel Master User (server-side). Menu gear teks + template, ikon gear diisi klien (lihat user_list). */
    public function users_data()
    {
        list($t, $f, $rows) = $this->md_model->ssp_datatable('ms_user_karyawan', 'e', $this->ssp_params(), [
            'select'  => 'e.*, u.username, g.id_group, g.nm_group',
            'join'    => [['users u', 'u.id_user = e.id_user AND IFNULL(u.deleted, 0) = 0', false],
                          ['(SELECT id_user, MIN(id_group) AS id_group FROM user_groups GROUP BY id_user) ug', 'ug.id_user = u.id_user', false],
                          ['`groups` g', 'g.id_group = ug.id_group', false]],
            'status'  => 'e.aktif',
            'filters' => ['dept' => 'e.department'],
            'search'  => ['e.kode', 'e.nama', 'e.email', 'e.telp', 'u.username'],
            'order'   => [1 => 'e.kode', 2 => 'e.nama', 3 => 'e.department', 4 => 'e.jabatan', 5 => 'u.username', 6 => 'e.aktif'],
            'default' => 'e.id',
        ]);
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $ubah = lm_can('master_data.users', 'ubah');
        $log = can_view_activity_log();
        $data = [];
        foreach ($rows as $i => $r) {
            $id = (int)$r['id'];
            $on = (int)$r['aktif'] === 1;
            $mi = function ($act, $label) use ($id) { return '<button class="menu-item" role="menuitem" data-uact="' . $act . '" data-id="' . $id . '">' . $label . '</button>'; };
            $menu = ($ubah ? $mi('edit', 'Edit') : '')
                . (lm_can('master_data.users', 'aktif') ? $mi('toggle', $on ? 'Nonaktifkan' : 'Aktifkan') : '')
                . ($ubah && $r['id_user'] ? $mi('reset', 'Reset Password') : '')
                . ($log && $r['kode'] ? $mi('hist', 'Lihat Riwayat') : '');
            $data[] = $this->ssp_row([
                $i + 1,
                '<span class="code">' . $e($r['kode'] ?: '—') . '</span>',
                $e($r['nama']) . '<br><span class="muted">' . $e($r['email'] ?: $r['telp']) . '</span>',
                $e($r['department'] ?: '—') . '<br><span class="muted">' . $e($r['division']) . '</span>',
                $e($r['jabatan'] ?: '—') . '<br><span class="muted">' . $e($r['tipe']) . '</span>',
                $r['id_user'] ? '<span class="code">' . $e($r['username']) . '</span><br><span class="muted">' . $e($r['nm_group']) . '</span>' : '<span class="muted">Tanpa akun</span>',
                $this->badge_aktif($on),
                hist_cell($r),
                $menu ? '<button class="gear" data-uact="gear" aria-haspopup="menu" aria-label="Opsi"></button><template>' . $menu . '</template>' : '',
            ], $r);
        }
        return $this->ssp_out($t, $f, $data);
    }

    /** Pratinjau kode berikutnya untuk modal Tambah (kode final dibuat saat simpan). */
    public function attr_next_code($type)
    {
        $prefix = ['jenis_produk' => 'JP', 'jenis_sertifikasi' => 'JS', 'jenis_bidang' => 'JB'][$type] ?? show_404();
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['kode' => $this->md_model->generate_code('ms_' . $type, $prefix)]));
    }

    public function save_jenis_produk()
    {
        $id = $this->input->post('id');
        $nama = $this->input->post('nama');
        if (empty(trim($nama))) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Nama wajib diisi']));
        }
        $res = $this->md_model->save_master_attr('ms_jenis_produk', 'JP', 'Jenis Produk', $nama, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_jenis_produk($id)
    {
        $res = $this->md_model->toggle_master_attr('ms_jenis_produk', 'Jenis Produk', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function jenis_sertifikasi()
    {
        $this->template->title('Jenis Sertifikat');
        $this->template->set('breadcrumbs', 'Master Data / <b>Jenis Sertifikat</b>');
        $data = [
            'type'        => 'jenis_sertifikasi',
            'title'       => 'Jenis Sertifikat',
            'ref'         => 'FR-05',
            'desc'        => 'Jenis sertifikat yang diterima peserta, mis. Sertifikat BNSP atau Sertifikat Labmania.',
            'save_url'    => site_url('master_data/save_jenis_sertifikasi'),
            'toggle_url'  => site_url('master_data/toggle_jenis_sertifikasi'),
            'code_label'  => 'Kode Sertifikat',
            'name_label'  => 'Jenis Sertifikat'
        ];
        $this->template->render('attr_list', $data);
    }

    public function save_jenis_sertifikasi()
    {
        $id = $this->input->post('id');
        $nama = $this->input->post('nama');
        if (empty(trim($nama))) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Nama wajib diisi']));
        }
        $res = $this->md_model->save_master_attr('ms_jenis_sertifikasi', 'JS', 'Jenis Sertifikat', $nama, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_jenis_sertifikasi($id)
    {
        $res = $this->md_model->toggle_master_attr('ms_jenis_sertifikasi', 'Jenis Sertifikat', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function jenis_bidang()
    {
        $this->template->title('Jenis Bidang');
        $this->template->set('breadcrumbs', 'Master Data / <b>Jenis Bidang</b>');
        $data = [
            'type'        => 'jenis_bidang',
            'title'       => 'Jenis Bidang',
            'ref'         => 'FR-06',
            'desc'        => 'Bidang keilmuan/standar yang dibahas di pelatihan. Juga dipakai untuk mengelompokkan peserta.',
            'save_url'    => site_url('master_data/save_jenis_bidang'),
            'toggle_url'  => site_url('master_data/toggle_jenis_bidang'),
            'code_label'  => 'Kode Bidang',
            'name_label'  => 'Jenis Bidang'
        ];
        $this->template->render('attr_list', $data);
    }

    public function save_jenis_bidang()
    {
        $id = $this->input->post('id');
        $nama = $this->input->post('nama');
        if (empty(trim($nama))) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Nama wajib diisi']));
        }
        $res = $this->md_model->save_master_attr('ms_jenis_bidang', 'JB', 'Jenis Bidang', $nama, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_jenis_bidang($id)
    {
        $res = $this->md_model->toggle_master_attr('ms_jenis_bidang', 'Jenis Bidang', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    // =========================================================================
    // 2. PRODUCT COMBINE (PAKET PRODUK) & KATALOG
    // =========================================================================

    public function paket_produk()
    {
        $this->template->title('Product Combine');
        $this->template->set('breadcrumbs', 'Master Data / <b>Product Combine</b>');

        $this->template->render('paket_list', []); // daftar diisi paket_data (server-side)
    }

    public function combine()
    {
        $this->template->title('Tambah Product (Combine)');
        $this->template->set('breadcrumbs', 'Master Data / Product Combine / <b>Tambah Product</b>');

        $data = [
            'jenis_produk'      => $this->md_model->get_master_attr('ms_jenis_produk'),
            'jenis_bidang'      => $this->md_model->get_master_attr('ms_jenis_bidang'),
            'jenis_sertifikasi' => $this->md_model->get_master_attr('ms_jenis_sertifikasi')
        ];
        $this->template->render('combine_form', $data);
    }

    public function save_combine()
    {
        $judul = $this->input->post('judul');
        $rows = $this->input->post('rows');

        if (empty(trim($judul))) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Judul wajib diisi']));
        }
        if (empty($rows) || !is_array($rows)) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Minimal susun 1 baris kombinasi atribut']));
        }

        $res = $this->md_model->save_combine($judul, $rows);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function update_paket()
    {
        $id = $this->input->post('id');
        $judul = $this->input->post('judul');
        if (empty(trim($judul))) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Judul wajib diisi']));
        }
        $res = $this->md_model->update_paket($id, $judul);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_paket($id)
    {
        $res = $this->md_model->toggle_master_attr('paket_produk', 'Master Produk', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function katalog()
    {
        $this->template->title('Katalog Produk');
        $this->template->set('breadcrumbs', 'Master Data / <b>Katalog Produk</b>');

        $data = ['jumlah_aktif' => $this->db->where('aktif', 1)->count_all_results('paket_produk')];
        $this->template->render('katalog_view', $data);
    }

    // =========================================================================
    // 3. MASTER PESERTA
    // =========================================================================

    public function peserta()
    {
        $this->template->title('Master Peserta');
        $this->template->set('breadcrumbs', 'Master Data / <b>Master Peserta</b>');

        // Daftar diisi lewat peserta_data (server-side); di sini hanya pilihan untuk form.
        $data = [
            'bidangs'   => $this->md_model->get_master_attr('ms_jenis_bidang'),
            'companies' => $this->md_model->get_companies()
        ];
        $this->template->render('peserta_list', $data);
    }

    /** Data tabel Master Peserta (server-side DataTables). */
    public function peserta_data()
    {
        list($total, $filtered, $rows) = $this->md_model->peserta_datatable($this->input->post() ?: $this->input->get());
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $svg = function ($d) { return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $d . '</svg>'; };
        $fields = ['id', 'kode', 'nama', 'gender', 'instansi', 'id_jenis_bidang', 'id_prov', 'provinsi', 'id_kab', 'kabupaten', 'no_telp', 'email', 'npwp_instansi', 'id_customer'];

        $data = [];
        foreach ($rows as $i => $r) {
            $json = $e(json_encode(array_intersect_key($r, array_flip($fields))));
            $bidang = $e($r['nama_bidang'] ?? '—') . (isset($r['bidang_aktif']) && !$r['bidang_aktif'] ? ' <span class="muted">(nonaktif)</span>' : '');
            $data[] = [
                $i + 1, // nomor dihitung ulang di sisi klien
                '<span class="code">' . $e($r['kode']) . '</span>',
                $e($r['nama']) . '<br><span class="muted">' . $e($r['instansi']) . '</span>',
                $bidang,
                $e($r['kabupaten'] ?: '—') . '<br><span class="muted">' . $e($r['provinsi']) . '</span>',
                hist_cell($r),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>'
                . (lm_can('master_data.peserta', 'ubah') ? '<button class="icon-btn" data-json="' . $json . '" onclick="editPeserta(this)" title="Edit">' . $svg('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>') . '</button>' : '')
                . (lm_can('master_data.peserta', 'hapus') ? '<button class="icon-btn" onclick="delPeserta(' . (int)$r['id'] . ', \'' . $e(addslashes($r['nama'])) . '\', \'' . $e($r['kode']) . '\')" title="Delete">' . $svg('<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>') . '</button>' : '')
                . riwayat_btn('ms_peserta', $r['kode']) . '</span>',
            ];
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'draw' => (int)$this->input->post_get('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data,
        ]));
    }

    public function peserta_next_code()
    {
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['kode' => $this->md_model->generate_code('ms_peserta', 'PS')]));
    }

    public function save_peserta()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->md_model->save_peserta($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function delete_peserta($id)
    {
        $res = $this->md_model->delete_peserta($id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    // =========================================================================
    // 4. MASTER COMPANY
    // =========================================================================

    public function company()
    {
        $this->template->title('Master Company');
        $this->template->set('breadcrumbs', 'Master Data / <b>Master Company</b>');
        $this->template->render('company_list', [
            'jenis_instansi' => ['Swasta', 'BUMN/BUMD', 'Pemerintah', 'Pendidikan', 'Lainnya']
        ]);
    }

    /** Data tabel Master Company (server-side DataTables). */
    public function company_data()
    {
        $post = $this->input->post() ?: $this->input->get();
        list($total, $filtered, $rows) = $this->md_model->company_datatable($post);
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $svg = function ($d) { return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $d . '</svg>'; };
        $fields = ['id_customer', 'name_customer', 'jenis_instansi', 'address_office', 'id_prov', 'provinsi', 'id_city', 'kota', 'telephone', 'email', 'pic_name', 'pic_jabatan', 'pic_hp', 'pic_email', 'npwp'];

        $data = [];
        foreach ($rows as $i => $r) {
            $id = $r['id_customer'];
            $on = !in_array($r['activation'], ['nonaktif', 'inactive'], true);
            $json = $e(json_encode(array_intersect_key($r, array_flip($fields))));
            $lokasi = !empty($r['kota']) ? $e($r['kota']) . '<br><span class="muted">' . $e($r['provinsi']) . '</span>' : '—';
            $row = [
                $i + 1, // nomor dihitung ulang di sisi klien
                '<span class="code">' . $e($id) . '</span>',
                $e($r['name_customer']) . '<br><span class="muted">PIC: ' . $e($r['pic_name'] ?: '—') . '</span>',
                $e($r['jenis_instansi'] ?: '—'),
                $lokasi,
                '<span class="badge ' . ($on ? 'on' : 'off') . '">' . ($on ? 'Aktif' : 'Nonaktif') . '</span>',
                hist_cell($r),
                '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>'
                . (lm_can('master_data.company', 'ubah') ? '<button class="icon-btn" data-json="' . $json . '" onclick="editCompany(this)" title="Edit">' . $svg('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>') . '</button>' : '')
                . (lm_can('master_data.company', 'aktif') ? '<button class="icon-btn" onclick="toggleCompany(\'' . $e($id) . '\', this)" title="' . ($on ? 'Nonaktifkan' : 'Aktifkan') . '">' . $svg('<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>') . '</button>' : '')
                . (lm_can('master_data.company', 'hapus') ? '<button class="icon-btn" onclick="delCompany(\'' . $e($id) . '\', \'' . $e(addslashes($r['name_customer'])) . '\')" title="Delete">' . $svg('<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>') . '</button>' : '')
                . riwayat_btn('master_customer', $id) . '</span>',
            ];
            if (!$on) $row['DT_RowClass'] = 'is-off'; // DataTables membaca kolom lewat indeks 0..n
            $data[] = $row;
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'draw' => (int)$this->input->post_get('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data,
        ]));
    }

    public function company_next_code()
    {
        return $this->output->set_content_type('application/json')->set_output(json_encode(['kode' => $this->md_model->next_company_code()]));
    }

    public function toggle_company($id)
    {
        return $this->output->set_content_type('application/json')->set_output(json_encode($this->md_model->toggle_company($id)));
    }

    public function save_company()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? $post['id'] : null;
        $res = $this->md_model->save_company($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function delete_company($id)
    {
        $res = $this->md_model->delete_company($id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    // =========================================================================
    // 5. MASTER RATE
    // =========================================================================

    public function rate($tab = 'fixed')
    {
        $this->template->title('Master Rate');
        $this->template->set('breadcrumbs', 'Master Data / <b>Master Rate</b>');

        $tab = in_array($tab, ['fixed', 'variable']) ? $tab : 'fixed';

        $data = [
            'tab'         => $tab,
            'titles'      => Master_data_model::RATE_TITLE,
            'counts'      => ['fixed' => $this->md_model->count_rates('fixed'), 'variable' => $this->md_model->count_rates('variable')],
            'satuan_tabs' => ['fixed' => $this->md_model->get_satuans_by_tipe('fixed'), 'variable' => $this->md_model->get_satuans_by_tipe('variable')],
            'coa'         => $this->md_model->get_coa_options()
        ];
        $this->template->render('rate_list', $data);
    }

    public function rate_next_code($tipe = 'fixed')
    {
        $prefix = ['fixed' => 'FC', 'variable' => 'VC'][$tipe] ?? show_404();
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['kode' => $this->md_model->generate_code('ms_rate', $prefix)]));
    }

    public function save_rate()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->md_model->save_rate($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_rate($id)
    {
        $res = $this->md_model->toggle_master_attr('ms_rate', 'Master Rate', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    // =========================================================================
    // 6. MASTER DOKUMEN: TEMPLATE KETERANGAN & KONFIRMASI
    // =========================================================================

    public function template_keterangan()
    {
        $this->template->title('Template Keterangan Quotation');
        $this->template->set('breadcrumbs', 'Master Data / <b>Template Keterangan Quotation</b>');

        $this->template->render('template_keterangan_list', []); // daftar diisi template_keterangan_data
    }

    public function template_keterangan_next_code()
    {
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['kode' => $this->md_model->generate_code('ms_template_keterangan', 'TK')]));
    }

    public function save_template_keterangan()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->md_model->save_template_keterangan($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_template_keterangan($id)
    {
        $res = $this->md_model->toggle_master_attr('ms_template_keterangan', 'Template Keterangan', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function template_konfirmasi()
    {
        $this->template->title('Template Formulir Konfirmasi');
        $this->template->set('breadcrumbs', 'Master Data / <b>Template Formulir Konfirmasi</b>');

        $data = [
            'jenis_produk' => $this->md_model->get_master_attr('ms_jenis_produk'),
            'kf_fields'    => Master_data_model::KF_FIELDS
        ];
        $this->template->render('template_konfirmasi_list', $data);
    }

    public function template_konfirmasi_next_code()
    {
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['kode' => $this->md_model->generate_code('ms_template_konfirmasi', 'KF')]));
    }

    /** Modal "Lihat contoh": formulir dengan Penjualan Program Final terbaru, peserta pertama, dan pengguna sesi sebagai contoh. */
    public function template_konfirmasi_contoh($id)
    {
        $t = $this->md_model->get_template_konfirmasi($id);
        if (!$t) show_404();

        $pj = $this->db->select('pj.*, pp.judul as judul_paket')->from('penjualan_program pj')
            ->join('master_program mp', 'pj.id_master_program = mp.id', 'left')->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left')
            ->where('pj.status', 'final')->order_by('pj.id', 'DESC')->limit(1)->get()->row_array();
        $ps = $this->db->order_by('id', 'ASC')->limit(1)->get_where('ms_peserta', ['deleted_date' => null])->row_array();
        // Penanda {email} {telp} = kontak sales: pengguna yang sedang login bila terdaftar di Master User, jika tidak user aktif pertama.
        $uid = (int)($this->session->userdata('app_session')['id_user'] ?? 0);
        $sales = $this->db->get_where('ms_user_karyawan', ['id_user' => $uid, 'aktif' => 1])->row_array()
            ?: $this->db->order_by('id', 'ASC')->limit(1)->get_where('ms_user_karyawan', ['aktif' => 1])->row_array() ?: [];

        $this->load->view('template_konfirmasi_contoh', ['t' => $t, 'pj' => $pj, 'ps' => $ps, 'sales' => $sales, 'kf_fields' => Master_data_model::KF_FIELDS]);
    }

    public function save_template_konfirmasi()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->md_model->save_template_konfirmasi($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_template_konfirmasi($id)
    {
        $res = $this->md_model->toggle_master_attr('ms_template_konfirmasi', 'Template Formulir Konfirmasi', $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    // =========================================================================
    // 7. MASTER USER
    // =========================================================================

    public function users()
    {
        $this->template->title('Master User');
        $this->template->set('breadcrumbs', 'Master Data / <b>Master User</b>');

        $m = $this->md_model;
        $data = [
            'groups'     => $m->get_groups(),
            'department' => $m->user_department,
            'jabatan'    => $m->user_jabatan,
            'gender'     => $m->user_gender,
            'agama'      => $m->user_agama,
            'tipe'       => $m->user_tipe,
        ];
        $this->template->render('user_list', $data);
    }

    public function save_user()
    {
        $post = $this->input->post();
        $id = !empty($post['id']) ? (int)$post['id'] : null;
        $res = $this->md_model->save_user($post, $id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function toggle_user($id)
    {
        $res = $this->md_model->toggle_user((int)$id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function reset_password_user()
    {
        $res = $this->md_model->reset_password_user((int)$this->input->post('id'), (string)$this->input->post('password'));
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }
}
