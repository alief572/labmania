<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Permission_group_model extends CI_Model
{
    const ID_ADMIN = 1; // group admin: selalu lolos semua, tidak dapat diubah izinnya atau dihapus

    private function user_id()
    {
        return $this->session->userdata('app_session')['id_user'] ?? 1;
    }

    private function len($v)
    {
        return function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);
    }

    public function get_groups()
    {
        return $this->db->query(
            "SELECT g.*,
                    (SELECT COUNT(*) FROM user_groups ug WHERE ug.id_group = g.id_group) AS n_user,
                    (SELECT COUNT(*) FROM ms_group_akses a WHERE a.id_group = g.id_group) AS n_izin
             FROM `groups` g ORDER BY g.id_group ASC"
        )->result_array();
    }

    // DataTables server-side: cari nama/keterangan, urut kolom, paging. Mengembalikan [total, terfilter, baris]
    public function group_datatable($p)
    {
        $total  = $this->db->count_all('groups');
        $search = trim($p['search']['value'] ?? '');
        $apply = function () use ($search) {
            if ($search !== '') $this->db->group_start()->like('g.nm_group', $search)->or_like('g.ket', $search)->group_end();
        };

        $apply();
        $filtered = $this->db->from('groups g')->count_all_results();

        $cols = [1 => 'g.nm_group', 2 => 'g.ket', 3 => 'n_user', 4 => 'n_izin'];
        $ord  = $p['order'][0] ?? [];
        $col  = $cols[(int)($ord['column'] ?? -1)] ?? 'g.id_group';
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';

        $apply();
        $this->db->select('g.*, (SELECT COUNT(*) FROM user_groups ug WHERE ug.id_group = g.id_group) AS n_user, (SELECT COUNT(*) FROM ms_group_akses a WHERE a.id_group = g.id_group) AS n_izin', false)
            ->from('groups g')->order_by($col, $dir);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        return [$total, $filtered, $this->db->get()->result_array()];
    }

    public function get_group($id)
    {
        return $this->db->get_where('groups', ['id_group' => $id])->row_array();
    }

    // ---------------------------------------------------------------- group

    public function save_group($post, $id = null)
    {
        $nama = trim((string)($post['nama'] ?? ''));
        $ket  = trim((string)($post['ket'] ?? ''));

        if ($nama === '') return ['status' => 0, 'msg' => 'Nama Group wajib diisi'];
        if ($this->len($nama) > 50) return ['status' => 0, 'msg' => 'Nama Group maksimal 50 karakter'];
        if ($this->len($ket) > 100) return ['status' => 0, 'msg' => 'Keterangan maksimal 100 karakter'];

        $dup = $this->db->where('LOWER(nm_group) =', mb_strtolower($nama))->where('id_group <>', (int)$id)->count_all_results('groups');
        if ($dup) return ['status' => 0, 'msg' => 'Nama Group sudah dipakai'];

        $now = date('Y-m-d H:i:s');
        if ($id) {
            $old = $this->get_group($id);
            if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
            if ((int)$id === self::ID_ADMIN && $nama !== $old['nm_group']) {
                return ['status' => 0, 'msg' => 'Nama group admin tidak dapat diubah'];
            }
            $data = ['nm_group' => $nama, 'ket' => $ket, 'updated_by' => $this->user_id(), 'updated_date' => $now];
            $this->db->where('id_group', $id)->update('groups', $data);
            write_log('Permission Group', 'Edit', 'Update Permission Group ' . $old['nm_group'],
                ['old' => $old, 'new' => array_merge($old, $data)], 'groups', $id, $old['nm_group']);
            return ['status' => 1, 'msg' => 'Group ' . $nama . ' berhasil diperbarui'];
        }

        $data = ['nm_group' => $nama, 'ket' => $ket, 'st_default' => 0, 'created_by' => $this->user_id(), 'created_date' => $now];
        if (!$this->db->insert('groups', $data)) return ['status' => 0, 'msg' => 'Gagal menyimpan group'];
        $newId = $this->db->insert_id();
        write_log('Permission Group', 'Tambah', 'Tambah Permission Group ' . $nama, $data, 'groups', $newId, $nama);
        return ['status' => 1, 'msg' => 'Group ' . $nama . ' berhasil ditambahkan', 'id' => $newId];
    }

    public function delete_group($id)
    {
        $g = $this->get_group($id);
        if (!$g) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
        if ($id === self::ID_ADMIN || !empty($g['st_default'])) {
            return ['status' => 0, 'msg' => 'Group ' . $g['nm_group'] . ' adalah group sistem dan tidak dapat dihapus'];
        }
        $n = $this->db->where('id_group', $id)->count_all_results('user_groups');
        if ($n) return ['status' => 0, 'msg' => 'Group ' . $g['nm_group'] . ' masih dipakai ' . $n . ' user — pindahkan user lebih dulu'];

        $this->db->trans_start();
        $this->db->delete('ms_group_akses', ['id_group' => $id]);
        $this->db->delete('groups', ['id_group' => $id]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return ['status' => 0, 'msg' => 'Gagal menghapus group'];

        write_log('Permission Group', 'Hapus', 'Hapus Permission Group ' . $g['nm_group'], $g, 'groups', $id, $g['nm_group']);
        return ['status' => 1, 'msg' => 'Group ' . $g['nm_group'] . ' berhasil dihapus'];
    }

    // ----------------------------------------------------------------- izin

    // Modul -> menu (semua, termasuk nonaktif) beserta aksi yang berlaku dan yang sudah diberikan
    public function get_matrix($idGroup)
    {
        $menus = $this->db->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get('ms_menu')->result_array();

        $berlaku = [];
        foreach ($this->db->get('ms_menu_aksi')->result_array() as $r) $berlaku[(int)$r['id_menu']][] = $r['aksi'];
        $diberi = [];
        foreach ($this->db->get_where('ms_group_akses', ['id_group' => $idGroup])->result_array() as $r) $diberi[(int)$r['id_menu']][$r['aksi']] = true;

        $byParent = [];
        foreach ($menus as $m) $byParent[(int)$m['parent_id']][] = $m;

        $out = [];
        foreach ($byParent[0] ?? [] as $top) {
            $items = ($top['link'] === '#') ? ($byParent[(int)$top['id']] ?? []) : [$top];
            $rows = [];
            foreach ($items as $m) {
                $mid = (int)$m['id'];
                $rows[] = ['id' => $mid, 'judul' => $m['judul'], 'aktif' => (int)$m['aktif'], 'berlaku' => $berlaku[$mid] ?? [], 'diberi' => $diberi[$mid] ?? []];
            }
            $out[] = ['judul' => $top['judul'], 'items' => $rows];
        }
        return $out;
    }

    // $akses: [id_menu => [aksi, aksi, ...]]
    public function save_akses($idGroup, $akses)
    {
        $g = $this->get_group($idGroup);
        if (!$g) return ['status' => 0, 'msg' => 'Group tidak ditemukan'];
        if ($idGroup === self::ID_ADMIN) return ['status' => 0, 'msg' => 'Izin group admin tidak dapat diubah (selalu penuh)'];

        $menus = [];
        foreach ($this->db->get('ms_menu')->result_array() as $m) $menus[(int)$m['id']] = $m['judul'];
        $berlaku = [];
        foreach ($this->db->get('ms_menu_aksi')->result_array() as $r) $berlaku[(int)$r['id_menu']][$r['aksi']] = true;
        $label = lm_aksi_list();

        // Normalisasi: hanya menu & aksi yang valid; aksi apa pun otomatis menyertakan Lihat
        $baru = [];
        foreach ($akses as $idMenu => $list) {
            $idMenu = (int)$idMenu;
            if (!isset($menus[$idMenu]) || !is_array($list)) return ['status' => 0, 'msg' => 'Data izin tidak valid'];
            $ada = false;
            foreach ($list as $a) {
                if (!is_string($a) || empty($berlaku[$idMenu][$a])) return ['status' => 0, 'msg' => 'Data izin tidak valid'];
                $baru[$idMenu][$a] = true;
                $ada = true;
            }
            if ($ada && !empty($berlaku[$idMenu]['lihat'])) $baru[$idMenu]['lihat'] = true;
        }

        $lama = [];
        foreach ($this->db->get_where('ms_group_akses', ['id_group' => $idGroup])->result_array() as $r) $lama[(int)$r['id_menu']][$r['aksi']] = true;

        // Selisih untuk log: 'Menu - Aksi' => Tidak/Ya
        $changes = [];
        foreach ($menus as $idMenu => $judul) {
            foreach ($berlaku[$idMenu] ?? [] as $a => $_) {
                $o = !empty($lama[$idMenu][$a]);
                $n = !empty($baru[$idMenu][$a]);
                if ($o !== $n) $changes[$judul . ' - ' . ($label[$a] ?? $a)] = ['old' => $o ? 'Ya' : 'Tidak', 'new' => $n ? 'Ya' : 'Tidak'];
            }
        }

        $this->db->trans_start();
        $this->db->delete('ms_group_akses', ['id_group' => $idGroup]);
        foreach ($baru as $idMenu => $list) {
            foreach (array_keys($list) as $a) {
                $this->db->insert('ms_group_akses', ['id_group' => $idGroup, 'id_menu' => $idMenu, 'aksi' => $a]);
            }
        }
        $this->db->where('id_group', $idGroup)->update('groups', ['updated_by' => $this->user_id(), 'updated_date' => date('Y-m-d H:i:s')]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return ['status' => 0, 'msg' => 'Gagal menyimpan izin'];

        if ($changes) {
            write_log('Permission Group', 'Ubah Izin', 'Ubah izin group ' . $g['nm_group'] . ' (' . count($changes) . ' perubahan)',
                ['old' => ['group' => $g['nm_group']], 'new' => ['group' => $g['nm_group']], 'detail_changes' => $changes], 'groups', $idGroup, $g['nm_group']);
        }
        return ['status' => 1, 'msg' => 'Izin group ' . $g['nm_group'] . ' disimpan' . ($changes ? ' (' . count($changes) . ' perubahan)' : ' (tidak ada perubahan)')];
    }
}
