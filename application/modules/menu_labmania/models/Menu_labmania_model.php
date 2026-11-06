<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Menu_labmania_model extends CI_Model
{
    private function user_id()
    {
        return $this->session->userdata('app_session')['id_user'] ?? 1;
    }

    private function len($v)
    {
        return function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);
    }

    // Semua menu dalam urutan tampil (modul/menu atas berurutan, anak mengikuti induknya), beserta aksi yang berlaku
    public function get_all()
    {
        $rows = $this->db->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get('ms_menu')->result_array();
        $aksi = [];
        foreach ($this->db->get('ms_menu_aksi')->result_array() as $r) $aksi[(int)$r['id_menu']][] = $r['aksi'];

        $by = [];
        foreach ($rows as $r) {
            $r['aksi'] = $aksi[(int)$r['id']] ?? [];
            $by[(int)$r['parent_id']][] = $r;
        }
        $out = [];
        foreach ($by[0] ?? [] as $top) {
            $top['level'] = 0;
            $top['parent_judul'] = '';
            $out[] = $top;
            $kids = $by[(int)$top['id']] ?? [];
            usort($kids, function ($a, $b) { return [$a['kelompok'], $a['urutan'], $a['id']] <=> [$b['kelompok'], $b['urutan'], $b['id']]; });
            foreach ($kids as $k) { $k['level'] = 1; $k['parent_judul'] = $top['judul']; $out[] = $k; }
        }
        return $out;
    }

    // DataTables server-side: cari judul/kode/link/modul, paging; urutan tetap mengikuti pohon sidebar
    public function menu_datatable($p)
    {
        $all = $this->get_all();
        $total = count($all);

        $q = mb_strtolower(trim($p['search']['value'] ?? ''));
        if ($q !== '') {
            $all = array_values(array_filter($all, function ($m) use ($q) {
                foreach (['judul', 'kode', 'link', 'parent_judul'] as $f) {
                    if (mb_strpos(mb_strtolower((string)$m[$f]), $q) !== false) return true;
                }
                return false;
            }));
        }
        $filtered = count($all);

        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $all = array_slice($all, max(0, (int)($p['start'] ?? 0)), min($len, 1000));
        return [$total, $filtered, $all];
    }

    public function get_moduls()
    {
        return $this->db->select('id, judul')->where(['parent_id' => 0, 'link' => '#'])->order_by('urutan', 'ASC')->get('ms_menu')->result_array();
    }

    public function get_menu($id)
    {
        $m = $this->db->get_where('ms_menu', ['id' => $id])->row_array();
        if ($m) {
            $m['aksi'] = array_column($this->db->get_where('ms_menu_aksi', ['id_menu' => $id])->result_array(), 'aksi');
        }
        return $m;
    }

    private function slug($s)
    {
        $s = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $s), '_'));
        return $s !== '' ? $s : 'menu';
    }

    private function unique_kode($base)
    {
        $kode = $base; $i = 2;
        while ($this->db->where('kode', $kode)->count_all_results('ms_menu')) $kode = $base . '_' . $i++;
        return $kode;
    }

    // ------------------------------------------------------------------ simpan

    public function save_menu($post, $id = null)
    {
        $tipe  = ($post['tipe'] ?? 'menu') === 'modul' ? 'modul' : 'menu';
        $judul = trim((string)($post['judul'] ?? ''));
        $link  = trim((string)($post['link'] ?? ''));
        $ikon  = trim((string)($post['ikon'] ?? ''));
        $parent = (int)($post['parent_id'] ?? 0);
        $kelompok = max(1, min(99, (int)($post['kelompok'] ?? 1)));
        $aksiIn = $post['aksi'] ?? [];
        if (!is_array($aksiIn)) return ['status' => 0, 'msg' => 'Data aksi tidak valid'];

        $old = null;
        if ($id) {
            $old = $this->get_menu($id);
            if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
            $tipe = ($old['parent_id'] == 0 && $old['link'] === '#') ? 'modul' : 'menu'; // tipe tidak dapat diubah
        }

        if ($judul === '') return ['status' => 0, 'msg' => 'Judul wajib diisi'];
        if ($this->len($judul) > 100) return ['status' => 0, 'msg' => 'Judul maksimal 100 karakter'];
        if ($ikon !== '' && !array_key_exists($ikon, lm_icons())) return ['status' => 0, 'msg' => 'Ikon tidak valid'];

        $sistem = $old ? (int)$old['sistem'] : 0;
        if ($tipe === 'modul') {
            $link = '#'; $parent = 0; $aksiIn = [];
        } else {
            if ($old && $sistem) $link = $old['link']; // link menu sistem terkunci
            if ($link === '' || $link === '#') return ['status' => 0, 'msg' => 'Link wajib diisi'];
            if ($this->len($link) > 150 || !preg_match('#^[a-z0-9_\-]+(/[a-z0-9_\-]+)*$#i', $link)) return ['status' => 0, 'msg' => 'Link tidak valid (huruf, angka, _ - dan /; tanpa awalan /)'];
            if ($parent !== 0 && !$this->db->where(['id' => $parent, 'parent_id' => 0, 'link' => '#'])->count_all_results('ms_menu')) return ['status' => 0, 'msg' => 'Modul induk tidak valid'];
            if ($this->db->where('link', $link)->where('id <>', (int)$id)->count_all_results('ms_menu')) return ['status' => 0, 'msg' => 'Link sudah dipakai menu lain'];

            $valid = lm_aksi_list();
            $aksi = [];
            foreach ($aksiIn as $a) {
                if (!is_string($a) || !isset($valid[$a])) return ['status' => 0, 'msg' => 'Aksi tidak valid'];
                $aksi[$a] = true;
            }
            $aksi['lihat'] = true; // setiap menu memiliki aksi Lihat
            $aksiIn = array_keys($aksi);
            if ($parent !== 0) $ikon = ''; // ikon hanya untuk modul / menu tingkat atas
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        if ($old) {
            $data = ['parent_id' => $tipe === 'modul' ? 0 : $parent, 'judul' => $judul, 'link' => $link, 'ikon' => $ikon !== '' ? $ikon : null,
                     'kelompok' => $kelompok, 'updated_by' => $this->user_id(), 'updated_date' => $now];
            $this->db->where('id', $id)->update('ms_menu', $data);
            $menuId = $id;
        } else {
            $row = $this->db->select_max('urutan')->where('parent_id', $parent)->get('ms_menu')->row_array();
            $kode = $this->unique_kode($tipe === 'modul' ? 'm_' . $this->slug($judul) : str_replace('/', '.', strtolower($link)));
            $data = ['parent_id' => $parent, 'kode' => $kode, 'judul' => $judul, 'link' => $link, 'ikon' => $ikon !== '' ? $ikon : null,
                     'kelompok' => $kelompok, 'urutan' => (int)$row['urutan'] + 1, 'aktif' => 1, 'sistem' => 0, 'created_by' => $this->user_id(), 'created_date' => $now];
            $this->db->insert('ms_menu', $data);
            $menuId = $this->db->insert_id();
        }

        if ($menuId && $tipe === 'menu') {
            $lama = $old ? $old['aksi'] : [];
            $hapus = array_diff($lama, $aksiIn);
            if ($hapus) {
                $this->db->where('id_menu', $menuId)->where_in('aksi', $hapus)->delete('ms_menu_aksi');
                $this->db->where('id_menu', $menuId)->where_in('aksi', $hapus)->delete('ms_group_akses'); // izin yang tidak berlaku lagi dicabut
            }
            foreach (array_diff($aksiIn, $lama) as $a) $this->db->insert('ms_menu_aksi', ['id_menu' => $menuId, 'aksi' => $a]);
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status() || !$menuId) return ['status' => 0, 'msg' => 'Gagal menyimpan menu'];

        $kode = $old ? $old['kode'] : $data['kode'];
        if ($old) {
            $oldLog = array_diff_key($old, ['aksi' => 1]) + ['aksi' => implode(',', $old['aksi'])];
            $newLog = array_merge($oldLog, $data, ['aksi' => implode(',', $tipe === 'menu' ? $aksiIn : [])]);
            write_log('Menu Sidebar', 'Edit', 'Update Menu ' . $judul, ['old' => $oldLog, 'new' => $newLog], 'ms_menu', $menuId, $kode);
            return ['status' => 1, 'msg' => 'Menu ' . $judul . ' berhasil diperbarui'];
        }
        write_log('Menu Sidebar', 'Tambah', 'Tambah Menu ' . $judul, $data + ['aksi' => implode(',', $aksiIn)], 'ms_menu', $menuId, $kode);
        return ['status' => 1, 'msg' => 'Menu ' . $judul . ' berhasil ditambahkan'];
    }

    // ------------------------------------------------------------------ status

    public function toggle_menu($id)
    {
        $m = $this->get_menu($id);
        if (!$m) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $baru = $m['aktif'] ? 0 : 1;
        if (!$baru) {
            if ($m['sistem']) return ['status' => 0, 'msg' => 'Menu ' . $m['judul'] . ' adalah menu sistem dan tidak dapat dinonaktifkan'];
            // Modul yang memuat menu sistem tidak boleh dinonaktifkan (mencegah admin mengunci pengaturan sendiri)
            if ($this->db->where(['parent_id' => $id, 'sistem' => 1])->count_all_results('ms_menu')) {
                return ['status' => 0, 'msg' => 'Modul ' . $m['judul'] . ' memuat menu sistem dan tidak dapat dinonaktifkan'];
            }
        }
        $this->db->where('id', $id)->update('ms_menu', ['aktif' => $baru, 'updated_by' => $this->user_id(), 'updated_date' => date('Y-m-d H:i:s')]);

        $act = $baru ? 'Aktifkan' : 'Nonaktifkan';
        write_log('Menu Sidebar', $act, $act . ' Menu ' . $m['judul'], ['old' => ['aktif' => (int)$m['aktif']], 'new' => ['aktif' => $baru]], 'ms_menu', $id, $m['kode']);
        return ['status' => 1, 'msg' => 'Menu ' . $m['judul'] . ($baru ? ' diaktifkan' : ' dinonaktifkan')];
    }

    // ------------------------------------------------------------------ urutan

    // Naik/turun dalam kelompok yang sama (induk dan kelompok sama)
    public function move_menu($id, $dir)
    {
        $m = $this->get_menu($id);
        if (!$m) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $where = ['parent_id' => (int)$m['parent_id']];
        if ((int)$m['parent_id'] !== 0) $where['kelompok'] = (int)$m['kelompok'];
        $sib = $this->db->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get_where('ms_menu', $where)->result_array();

        $idx = null;
        foreach ($sib as $i => $s) if ((int)$s['id'] === (int)$id) $idx = $i;
        $to = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($idx === null || $to < 0 || $to >= count($sib)) return ['status' => 0, 'msg' => 'Menu sudah berada di posisi ' . ($dir === 'up' ? 'teratas' : 'terbawah')];

        $tmp = $sib[$idx]; $sib[$idx] = $sib[$to]; $sib[$to] = $tmp;
        $this->db->trans_start();
        foreach ($sib as $i => $s) $this->db->where('id', $s['id'])->update('ms_menu', ['urutan' => ($i + 1) * 10]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return ['status' => 0, 'msg' => 'Gagal memindahkan menu'];

        write_log('Menu Sidebar', 'Edit', 'Pindah menu ' . $m['judul'] . ($dir === 'up' ? ' ke atas' : ' ke bawah'), null, 'ms_menu', $id, $m['kode']);
        return ['status' => 1, 'msg' => 'Menu ' . $m['judul'] . ' dipindahkan'];
    }
}
