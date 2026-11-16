<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_trainer_model extends CI_Model
{
    /** $onlyActive: hanya trainer aktif (pilihan di Penjualan Program, FR-46); $withId: trainer ini tetap ikut walau nonaktif. */
    public function get_trainers($id = null, $onlyActive = false, $withId = null)
    {
        if ($id) {
            $trainer = $this->db->get_where('ms_trainer', ['id' => $id])->row_array();
            if ($trainer) {
                $trainer['pelatihan'] = $this->db->order_by('urutan', 'ASC')->get_where('ms_trainer_pelatihan', ['id_trainer' => $id])->result_array();
                $trainer['pengalaman_kerja'] = $this->db->order_by('urutan', 'ASC')->get_where('ms_trainer_pengalaman_kerja', ['id_trainer' => $id])->result_array();
                $trainer['pengalaman_mengajar'] = $this->db->order_by('urutan', 'ASC')->get_where('ms_trainer_pengalaman_mengajar', ['id_trainer' => $id])->result_array();
                $trainer['lampiran'] = $this->db->order_by('urutan', 'ASC')->get_where('ms_trainer_lampiran', ['id_trainer' => $id])->result_array();
            }
            return $trainer;
        }
        $this->db->select('t.*, '
            . '(SELECT COUNT(*) FROM ms_trainer_pelatihan x WHERE x.id_trainer = t.id) AS n_pelatihan, '
            . '(SELECT COUNT(*) FROM ms_trainer_pengalaman_kerja x WHERE x.id_trainer = t.id) AS n_kerja, '
            . '(SELECT COUNT(*) FROM ms_trainer_pengalaman_mengajar x WHERE x.id_trainer = t.id) AS n_mengajar, '
            . '(SELECT COUNT(*) FROM ms_trainer_lampiran x WHERE x.id_trainer = t.id) AS n_lampiran, '
            . '(SELECT COUNT(*) FROM penjualan_program pj WHERE pj.id_trainer = t.id) AS n_penjualan', false);
        if ($onlyActive) {
            $this->db->group_start()->where('t.aktif', 1);
            if ($withId) $this->db->or_where('t.id', (int)$withId);
            $this->db->group_end();
        }
        return $this->db->order_by('t.id', 'ASC')->get('ms_trainer t')->result_array();
    }

    /** Bidang keahlian: satu per baris (FR-45); titik koma tetap dikenali untuk data lama. */
    public static function split_keahlian($text)
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n;]+/', (string)$text)), 'strlen'));
    }

    // Dipanggil di dalam transaksi save_trainer: FOR UPDATE membuat simpan paralel menunggu, sehingga kode tidak ganda.
    public function generate_code()
    {
        $row = $this->db->query("SELECT kode FROM ms_trainer WHERE kode LIKE 'TR-%' ORDER BY id DESC LIMIT 1 FOR UPDATE")->row_array();

        $next = 1;
        if ($row && preg_match('/TR-(\d+)/', $row['kode'], $m)) {
            $next = (int)$m[1] + 1;
        }
        return sprintf('TR-%03d', $next);
    }

    // Validasi sisi server (FR-33 – FR-36): panjang mengikuti kolom, tanggal valid, baris riwayat lengkap.
    // Mengembalikan pesan galat, atau null bila valid.
    public function validate_trainer($post)
    {
        $len = function ($v) { return function_exists('mb_strlen') ? mb_strlen($v) : strlen($v); };
        $str = function ($v) { return is_string($v) ? $v : null; };

        $nama = $post['nama'] ?? '';
        if (!is_string($nama) || trim($nama) === '') return 'Nama Lengkap wajib diisi';

        $max = [
            'nama' => ['Nama Lengkap', 150], 'tempat_lahir' => ['Tempat Lahir', 100], 'no_telp' => ['No. Telepon / WhatsApp', 50],
            'pend_smu' => ['Pendidikan SMU', 255], 'pend_diploma' => ['Pendidikan Diploma', 255], 'pend_sarjana' => ['Pendidikan Sarjana', 255],
            'pend_pascasarjana' => ['Pendidikan Pascasarjana', 255], 'cv_tempat' => ['Tempat Pengesahan CV', 100], 'nama_ttd' => ['Nama pada Tanda Tangan', 150],
        ];
        foreach ($max as $k => $m) {
            $v = $str($post[$k] ?? '');
            if ($v === null) return $m[0] . ' tidak valid';
            if ($len($v) > $m[1]) return $m[0] . ' maksimal ' . $m[1] . ' karakter';
        }
        foreach (['alamat' => 'Alamat Rumah', 'bidang_keahlian' => 'Bidang Keahlian', 'pengalaman_lain' => 'Pengalaman Lainnya'] as $k => $label) {
            if ($str($post[$k] ?? '') === null) return $label . ' tidak valid';
        }

        foreach (['tgl_lahir' => 'Tanggal Lahir', 'cv_tanggal' => 'Tanggal Pengesahan CV'] as $k => $label) {
            $v = trim((string)($post[$k] ?? ''));
            if ($v === '') continue;
            $d = DateTime::createFromFormat('Y-m-d', $v);
            if (!$d || $d->format('Y-m-d') !== $v) return $label . ' tidak valid';
        }
        $lahir = trim((string)($post['tgl_lahir'] ?? ''));
        if ($lahir !== '' && $lahir > date('Y-m-d')) return 'Tanggal Lahir tidak boleh di masa depan';

        $lists = [
            'pelatihan'           => ['Pelatihan & Seminar', 'judul', ['judul' => 255, 'penyelenggara' => 150, 'waktu' => 50]],
            'pengalaman_kerja'    => ['Pengalaman Kerja', 'jabatan', ['jabatan' => 150, 'institusi' => 150, 'tahun' => 50]],
            'pengalaman_mengajar' => ['Pengalaman Mengajar', 'topik', ['topik' => 255, 'tempat' => 150, 'tahun' => 50]],
            'lampiran'            => ['Lampiran Dokumen', 'dokumen', ['dokumen' => 255, 'tahun' => 20]],
        ];
        foreach ($lists as $key => $def) {
            $rows = $post[$key] ?? [];
            if (!is_array($rows)) return 'Data ' . $def[0] . ' tidak valid';
            foreach ($rows as $row) {
                if (!is_array($row)) return 'Data ' . $def[0] . ' tidak valid';
                $filled = false;
                foreach ($def[2] as $col => $m) {
                    $v = $str($row[$col] ?? '');
                    if ($v === null) return 'Data ' . $def[0] . ' tidak valid';
                    if ($len($v) > $m) return $def[0] . ': kolom ' . $col . ' maksimal ' . $m . ' karakter';
                    if (trim($v) !== '') $filled = true;
                }
                if ($filled && trim((string)($row[$def[1]] ?? '')) === '') {
                    return $def[0] . ': kolom pertama wajib diisi pada baris yang terisi';
                }
            }
        }
        return null;
    }

    /**
     * $uploaded: [indeksBarisLampiran => hasil CI upload->data()] untuk berkas baru. Baris lampiran tanpa berkas baru
     * mempertahankan file_path lama hanya bila berkas itu memang milik trainer ini (hidden input tidak dipercaya).
     * Kembalian menyertakan 'obsolete' = berkas lama yang tak terpakai lagi (dihapus pemanggil setelah sukses).
     */
    public function save_trainer($post, array $uploaded = [], $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        $header = [
            'nama'               => trim($post['nama']),
            'tempat_lahir'       => trim($post['tempat_lahir'] ?? ''),
            'tgl_lahir'          => !empty($post['tgl_lahir']) ? $post['tgl_lahir'] : null,
            'alamat'             => trim($post['alamat'] ?? ''),
            'no_telp'            => trim($post['no_telp'] ?? ''),
            'pend_smu'           => trim($post['pend_smu'] ?? ''),
            'pend_diploma'       => trim($post['pend_diploma'] ?? ''),
            'pend_sarjana'       => trim($post['pend_sarjana'] ?? ''),
            'pend_pascasarjana'  => trim($post['pend_pascasarjana'] ?? ''),
            'bidang_keahlian'    => implode("\n", self::split_keahlian($post['bidang_keahlian'] ?? '')),
            'pengalaman_lain'    => trim($post['pengalaman_lain'] ?? ''),
            'cv_tempat'          => trim($post['cv_tempat'] ?? ''),
            'cv_tanggal'         => !empty($post['cv_tanggal']) ? $post['cv_tanggal'] : null,
            'nama_ttd'           => trim($post['nama_ttd'] ?? '')
        ];

        $old = null;
        $oldLampiran = [];
        if ($id) {
            $old = $this->db->get_where('ms_trainer', ['id' => $id])->row_array();
            if (!$old) return ['status' => 0, 'msg' => 'Data Trainer tidak ditemukan'];
            $oldLampiran = $this->db->get_where('ms_trainer_lampiran', ['id_trainer' => $id])->result_array();
        }
        $ownFiles = array_column($oldLampiran, 'file_path');

        $this->db->trans_start();
        if ($id) {
            $header['updated_by'] = $userId;
            $header['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('ms_trainer', $header);
            $trainerId = $id;
            $kode = $old['kode'];
        } else {
            $kode = $this->generate_code();
            $header['kode'] = $kode;
            $header['aktif'] = 1;
            $header['created_by'] = $userId;
            $header['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('ms_trainer', $header);
            $trainerId = $this->db->insert_id();
        }
        if (!$trainerId) {
            $this->db->trans_complete(); // insert header gagal: transaksi dibatalkan, baris riwayat tidak ditulis
            return ['status' => 0, 'msg' => 'Gagal menyimpan data trainer'];
        }

        // Daftar riwayat: [tabel => [kunci POST, kolom pertama, kolom lain...]]; baris tanpa kolom pertama dilewati.
        $lists = [
            'ms_trainer_pelatihan'           => ['pelatihan', 'judul', ['penyelenggara', 'waktu']],
            'ms_trainer_pengalaman_kerja'    => ['pengalaman_kerja', 'jabatan', ['institusi', 'tahun']],
            'ms_trainer_pengalaman_mengajar' => ['pengalaman_mengajar', 'topik', ['tempat', 'tahun']],
        ];
        foreach ($lists as $table => $def) {
            $this->db->delete($table, ['id_trainer' => $trainerId]);
            $idx = 1;
            foreach ((array)($post[$def[0]] ?? []) as $row) {
                if (trim($row[$def[1]] ?? '') === '') continue;
                $ins = ['id_trainer' => $trainerId, 'urutan' => $idx++, $def[1] => trim($row[$def[1]])];
                foreach ($def[2] as $col) $ins[$col] = trim($row[$col] ?? '');
                $this->db->insert($table, $ins);
            }
        }

        // Lampiran Dokumen: Dokumen, Tahun, File (opsional)
        $this->db->delete('ms_trainer_lampiran', ['id_trainer' => $trainerId]);
        $keep = [];
        $idx = 1;
        foreach ((array)($post['lampiran'] ?? []) as $i => $row) {
            if (trim($row['dokumen'] ?? '') === '') continue;
            $file = null;
            if (isset($uploaded[$i])) {
                $file = $uploaded[$i]['file_name'];
            } else {
                $prev = basename((string)($row['file_path'] ?? ''));
                if ($prev !== '' && in_array($prev, $ownFiles, true)) $file = $prev;
            }
            if ($file) $keep[] = $file;
            $this->db->insert('ms_trainer_lampiran', [
                'id_trainer' => $trainerId,
                'urutan'     => $idx++,
                'dokumen'    => trim($row['dokumen']),
                'tahun'      => trim($row['tahun'] ?? ''),
                'file_path'  => $file
            ]);
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status() || !$trainerId) {
            return ['status' => 0, 'msg' => 'Gagal menyimpan data trainer'];
        }

        if ($old) {
            write_log('Master Trainer', 'Edit', 'Update Trainer ' . $kode, ['old' => $old, 'new' => array_merge($old, $header)], 'ms_trainer', $trainerId, $kode);
        } else {
            write_log('Master Trainer', 'Tambah', 'Tambah Trainer ' . $kode, $header, 'ms_trainer', $trainerId, $kode);
        }

        return [
            'status'   => 1,
            'msg'      => 'Trainer ' . $kode . ($old ? ' diperbarui' : ' ditambahkan'),
            'id'       => $trainerId,
            'obsolete' => array_values(array_diff($ownFiles, $keep)),
        ];
    }

    public function toggle_trainer($id)
    {
        $old = $this->db->get_where('ms_trainer', ['id' => $id])->row_array();
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $newAktif = $old['aktif'] ? 0 : 1;
        $this->db->where('id', $id)->update('ms_trainer', [
            'aktif'        => $newAktif,
            'updated_by'   => $this->session->userdata('app_session')['id_user'] ?? 1,
            'updated_date' => date('Y-m-d H:i:s')
        ]);

        $action = $newAktif ? 'Aktifkan' : 'Nonaktifkan';
        write_log('Master Trainer', $action, $action . ' Trainer ' . $old['kode'], [
            'old' => $old,
            'new' => array_merge($old, ['aktif' => $newAktif])
        ], 'ms_trainer', $id, $old['kode']);

        return ['status' => 1, 'msg' => $old['kode'] . ' ' . ($newAktif ? 'diaktifkan' : 'dinonaktifkan')];
    }
}
