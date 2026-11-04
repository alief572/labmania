<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hak akses Labmania: izin per menu dan aksi (tabel ms_menu, ms_menu_aksi, ms_group_akses).
 * Group id 1 (`admin`) lolos semua. Dimuat oleh Labmania_Controller (`$this->load->helper('akses')`).
 */

if (!function_exists('lm_aksi_list')) {
    // Aksi yang dikenal; 'aktif' = ubah status (aktif/nonaktif, finalkan, batalkan, deal)
    function lm_aksi_list()
    {
        return ['lihat' => 'Lihat', 'tambah' => 'Tambah', 'ubah' => 'Ubah', 'aktif' => 'Ubah Status', 'hapus' => 'Hapus', 'cetak' => 'Cetak'];
    }
}

if (!function_exists('lm_user_id')) {
    function lm_user_id()
    {
        $ci = &get_instance();
        $s = isset($ci->session) ? $ci->session->userdata('app_session') : null;
        return !empty($s['id_user']) ? (int)$s['id_user'] : 0;
    }
}

if (!function_exists('lm_is_admin')) {
    function lm_is_admin()
    {
        static $cache = [];
        $uid = lm_user_id();
        if (!$uid) return false;
        if (!isset($cache[$uid])) {
            $ci = &get_instance();
            $cache[$uid] = (bool)$ci->db->where(['id_user' => $uid, 'id_group' => 1])->count_all_results('user_groups');
        }
        return $cache[$uid];
    }
}

if (!function_exists('lm_access_map')) {
    // [kode menu => [aksi => true]] untuk menu aktif milik group user
    function lm_access_map()
    {
        static $cache = [];
        $uid = lm_user_id();
        if (!$uid) return [];
        if (!isset($cache[$uid])) {
            $ci = &get_instance();
            $rows = $ci->db->select('m.kode, a.aksi')->from('ms_group_akses a')
                ->join('ms_menu m', 'm.id = a.id_menu AND m.aktif = 1')
                ->join('user_groups ug', 'ug.id_group = a.id_group')
                ->where('ug.id_user', $uid)->get()->result_array();
            $map = [];
            foreach ($rows as $r) $map[$r['kode']][$r['aksi']] = true;
            $cache[$uid] = $map;
        }
        return $cache[$uid];
    }
}

if (!function_exists('lm_can')) {
    function lm_can($kode, $aksi = 'lihat')
    {
        if (!lm_user_id()) return false;
        if (lm_is_admin()) return true;
        $map = lm_access_map();
        return !empty($map[$kode][$aksi]);
    }
}

if (!function_exists('lm_icons')) {
    // Ikon preset sidebar (kunci => isi SVG)
    function lm_icons()
    {
        return [
            'home'     => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>',
            'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
            'document' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
            'chat'     => '<path d="M4 4h16v12H8l-4 4z"/><path d="M8 9h8M8 12h5"/>',
            'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
            'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'tag'      => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
            'chart'    => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        ];
    }
}

if (!function_exists('lm_icon_svg')) {
    function lm_icon_svg($key, $size = 16)
    {
        $icons = lm_icons();
        $d = $icons[$key] ?? $icons['document'];
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
    }
}

if (!function_exists('lm_menu_counts')) {
    /** Jumlah data per menu untuk badge sidebar (mengikuti countOf mockup): kode menu => jumlah. Menu tanpa badge tidak ada di sini. */
    function lm_menu_counts()
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $map = [
            'master_data.jenis_produk' => 'ms_jenis_produk', 'master_data.jenis_sertifikasi' => 'ms_jenis_sertifikasi',
            'master_data.jenis_bidang' => 'ms_jenis_bidang', 'master_data.paket_produk' => 'paket_produk',
            'master_data.peserta' => 'ms_peserta', 'master_data.company' => 'master_customer',
            'master_trainer' => 'ms_trainer', 'master_data.template_keterangan' => 'ms_template_keterangan',
            'master_data.template_konfirmasi' => 'ms_template_konfirmasi', 'master_data.rate' => 'ms_rate',
            'master_data.users' => 'ms_user_karyawan', 'master_program' => 'master_program',
            'penjualan_program' => 'penjualan_program', 'quotation_program' => 'quotation', 'activity_log' => 'logs',
        ];
        $ci = &get_instance();
        $parts = [];
        foreach ($map as $kode => $tbl) $parts[] = "SELECT '$kode' k, COUNT(*) n FROM `$tbl`"; // kode & tabel konstanta di atas, bukan input
        $q = @$ci->db->query(implode(' UNION ALL ', $parts));
        $cache = [];
        // shortcut: COUNT(*) tiap muat halaman; satu tabel hilang membuat semua badge kosong. Cache bila logs sangat besar.
        if ($q) foreach ($q->result_array() as $r) $cache[$r['k']] = (int)$r['n'];
        return $cache;
    }
}

if (!function_exists('lm_menu_tree')) {
    /**
     * Pohon sidebar untuk user saat ini. Elemen:
     *   modul     : ['kode','judul','ikon','modul'=>true,'groups'=>[ [menu, menu], [menu] ]]   (kelompok = pemisah)
     *   menu atas : ['kode','judul','ikon','link','modul'=>false]
     * Menu tampil bila aktif dan user boleh Lihat; modul tanpa anak yang tampil disembunyikan.
     */
    function lm_menu_tree()
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $ci = &get_instance();
        $rows = $ci->db->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get_where('ms_menu', ['aktif' => 1])->result_array();

        $byParent = [];
        foreach ($rows as $r) $byParent[(int)$r['parent_id']][] = $r;

        $tree = [];
        foreach ($byParent[0] ?? [] as $top) {
            if ($top['link'] !== '#') {
                if (lm_can($top['kode'], 'lihat')) {
                    $tree[] = ['kode' => $top['kode'], 'judul' => $top['judul'], 'ikon' => $top['ikon'], 'link' => $top['link'], 'modul' => false];
                }
                continue;
            }
            $groups = [];
            foreach ($byParent[(int)$top['id']] ?? [] as $m) {
                if (!lm_can($m['kode'], 'lihat')) continue;
                $groups[(int)$m['kelompok']][] = ['kode' => $m['kode'], 'judul' => $m['judul'], 'link' => $m['link']];
            }
            if ($groups) {
                ksort($groups);
                $tree[] = ['kode' => $top['kode'], 'judul' => $top['judul'], 'ikon' => $top['ikon'], 'modul' => true, 'groups' => array_values($groups)];
            }
        }
        return $cache = $tree;
    }
}
