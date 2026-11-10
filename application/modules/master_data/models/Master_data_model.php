<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_data_model extends CI_Model
{
    /** Daftar isian Formulir Konfirmasi (FR-51): kunci => label. Satu sumber untuk validasi, daftar, form, dan contoh. */
    /** Master Rate (FR-25): satuan yang boleh per tab (kode ms_satuan) dan judul tab. */
    const RATE_SATUAN = ['fixed' => ['event', 'day'], 'variable' => ['peserta_event', 'peserta_day']];
    const RATE_TITLE  = ['fixed' => 'Fixed Cost', 'variable' => 'Variable Cost'];

    const KF_FIELDS = [
        'nama' => 'Nama Lengkap Peserta*', 'nik' => 'NIK', 'jabatan' => 'Jabatan', 'instansi' => 'Nama Instansi', 'alamat' => 'Alamat Instansi',
        'hp' => 'Nomor Hp', 'wa' => 'Nomor WhatsApp', 'email' => 'Alamat Email Aktif', 'pengalaman' => 'Masa Pengalaman Bekerja Sesuai Skema',
        'lsp' => 'LSP', 'habis' => 'Tanggal Habis Sertifikat',
    ];

    // =========================================================================
    // 1. ATRIBUT PRODUK: JENIS PRODUK, SERTIFIKAT, BIDANG
    // =========================================================================

    // Lekatkan jejak "Dibuat / Diubah" ke daftar (LOG-FR-02). Tabel tanpa kolom jejak (mis. master_customer)
    // memakai catatan Tambah / Edit di tabel logs.
    public function with_audit($rows, $table, $kodeField = 'kode')
    {
        if (empty($rows)) return $rows;

        if ($this->db->field_exists('created_date', $table)) {
            $ids = [];
            foreach ($rows as $r) {
                foreach (['created_by', 'updated_by'] as $k) if (!empty($r[$k])) $ids[(int)$r[$k]] = true;
            }
            $names = $this->user_names(array_keys($ids));
            foreach ($rows as &$r) {
                $r['_dibuat_oleh'] = $names[(int)($r['created_by'] ?? 0)] ?? null;
                $r['_dibuat_pada'] = $r['created_date'] ?? null;
                $r['_diubah_oleh'] = $names[(int)($r['updated_by'] ?? 0)] ?? null;
                $r['_diubah_pada'] = $r['updated_date'] ?? null;
            }
            unset($r);
            return $rows;
        }

        $kodes = array_values(array_filter(array_column($rows, $kodeField), 'strlen'));
        $byRef = [];
        $uids  = [];
        if ($kodes) {
            $logs = $this->db->select('ref_no, action, created_by, created_date')
                ->where('ref_table', $table)->where_in('ref_no', $kodes)->where_in('action', ['Tambah', 'Edit'])
                ->order_by('id', 'ASC')->get('logs')->result_array();
            foreach ($logs as $l) {
                $uids[(int)$l['created_by']] = true;
                $slot = $l['action'] === 'Tambah' ? 'c' : 'u';
                if ($slot === 'c' && isset($byRef[$l['ref_no']]['c'])) continue;
                $byRef[$l['ref_no']][$slot] = $l;
            }
        }
        $names = $this->user_names(array_keys($uids));
        foreach ($rows as &$r) {
            $c = $byRef[$r[$kodeField] ?? '']['c'] ?? null;
            $u = $byRef[$r[$kodeField] ?? '']['u'] ?? null;
            $r['_dibuat_oleh'] = $c ? ($names[(int)$c['created_by']] ?? null) : null;
            $r['_dibuat_pada'] = $c['created_date'] ?? null;
            $r['_diubah_oleh'] = $u ? ($names[(int)$u['created_by']] ?? null) : null;
            $r['_diubah_pada'] = $u['created_date'] ?? null;
        }
        unset($r);
        return $rows;
    }

    private function user_names($ids)
    {
        if (!$ids) return [];
        $out = [];
        $q = $this->db->select("id_user, COALESCE(NULLIF(nm_lengkap, ''), username) AS nama", false)->where_in('id_user', $ids)->get('users')->result_array();
        foreach ($q as $u) $out[(int)$u['id_user']] = $u['nama'];
        return $out;
    }

    public function get_master_attr($table, $id = null)
    {
        if ($id) {
            return $this->db->get_where($table, ['id' => $id])->row_array();
        }
        return $this->db->order_by('id', 'ASC')->get($table)->result_array();
    }

    /** Server-side DataTables untuk tabel atribut (kode, nama, aktif). */
    public function attr_datatable($table, $p)
    {
        $total = $this->db->count_all($table);

        $search = trim($p['search']['value'] ?? '');
        $status = $p['status'] ?? 'all'; // all | on | off
        $apply = function () use ($search, $status) {
            if ($status === 'on' || $status === 'off') $this->db->where('aktif', $status === 'on' ? 1 : 0);
            if ($search !== '') {
                $this->db->group_start()->like('kode', $search)->or_like('nama', $search)->group_end();
            }
        };

        $apply();
        $filtered = $this->db->count_all_results($table);

        $cols = [1 => 'kode', 2 => 'nama', 3 => 'aktif'];
        $ord  = $p['order'][0] ?? [];
        $col  = $cols[(int)($ord['column'] ?? -1)] ?? 'id';
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';

        $apply();
        $this->db->order_by($col, $dir);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        $rows = $this->db->get($table)->result_array();

        return [$total, $filtered, $this->with_audit($rows, $table)];
    }

    /**
     * Server-side DataTables generik. $cfg: select, join [[tabel, kondisi, escape?]], where [kol => nilai],
     * status (kolom aktif untuk filter on/off), filters [param => kolom], search [kolom], order [indeks kolom => ekspresi],
     * default (urutan awal), audit (tabel jejak audit), kode (kolom kode untuk audit). Semua nilai $cfg konstanta di controller.
     * Kembalian: [total, filtered, rows].
     */
    public function ssp_datatable($table, $alias, $p, $cfg)
    {
        $base = function ($join) use ($table, $alias, $cfg) {
            $this->db->from("$table $alias");
            if ($join) foreach ($cfg['join'] ?? [] as $j) $this->db->join($j[0], $j[1], 'left', $j[2] ?? null);
            foreach ($cfg['where'] ?? [] as $k => $v) $this->db->where($k, $v);
        };
        $search = trim($p['search']['value'] ?? '');
        $filter = function () use ($p, $cfg, $search) {
            $st = $p['status'] ?? 'all';
            if (isset($cfg['status']) && ($st === 'on' || $st === 'off')) $this->db->where($cfg['status'], $st === 'on' ? 1 : 0);
            foreach ($cfg['filters'] ?? [] as $name => $col) {
                $v = $p[$name] ?? 'all';
                if ($v !== 'all' && $v !== '') $this->db->where($col, $v);
            }
            if ($search !== '') {
                $this->db->group_start();
                foreach ($cfg['search'] as $c) $this->db->or_like($c, $search);
                $this->db->group_end();
            }
        };

        $base(false);
        $total = $this->db->count_all_results();
        $base(true);
        $filter();
        $filtered = $this->db->count_all_results();

        $ord  = $p['order'][0] ?? [];
        $expr = $cfg['order'][(int)($ord['column'] ?? -1)] ?? $cfg['default'];
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';

        $base(true);
        $filter();
        $this->db->select($cfg['select'] ?? "$alias.*", false)->order_by($expr, $dir, false);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        $rows = $this->db->get()->result_array();

        return [$total, $filtered, $this->with_audit($rows, $cfg['audit'] ?? $table, $cfg['kode'] ?? 'kode')];
    }

    public function generate_code($table, $prefix, $field = 'kode')
    {
        $this->db->select($field);
        $this->db->like($field, $prefix . '-', 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get($table)->row_array();

        if ($row && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/', $row[$field], $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }
        return sprintf('%s-%03d', $prefix, $next);
    }

    public function save_master_attr($table, $prefix, $moduleName, $nama, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        // shortcut: case-insensitivity bergantung collation kolom (default *_ci); tidak ada unique index DB
        $this->db->where('nama', trim($nama));
        if ($id) {
            $this->db->where('id !=', $id);
        }
        if ($this->db->count_all_results($table) > 0) {
            return ['status' => 0, 'msg' => $moduleName . ' "' . trim($nama) . '" sudah ada'];
        }

        if ($id) {
            $old = $this->db->get_where($table, ['id' => $id])->row_array();
            $data = [
                'nama'         => trim($nama),
                'updated_by'   => $userId,
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $this->db->where('id', $id)->update($table, $data);

            write_log($moduleName, 'Edit', 'Update ' . $moduleName . ' ' . $old['kode'], [
                'old' => $old,
                'new' => array_merge($old, $data)
            ], $table, $id, $old['kode']);

            return ['status' => 1, 'msg' => $moduleName . ' ' . $old['kode'] . ' diperbarui'];
        } else {
            $kode = $this->generate_code($table, $prefix);
            $data = [
                'kode'         => $kode,
                'nama'         => trim($nama),
                'aktif'        => 1,
                'created_by'   => $userId,
                'created_date' => date('Y-m-d H:i:s')
            ];
            $this->db->insert($table, $data);
            $newId = $this->db->insert_id();

            write_log($moduleName, 'Tambah', 'Tambah ' . $moduleName . ' ' . $kode, $data, $table, $newId, $kode);

            return ['status' => 1, 'msg' => $moduleName . ' ' . $kode . ' ditambahkan'];
        }
    }

    public function toggle_master_attr($table, $moduleName, $id)
    {
        $old = $this->db->get_where($table, ['id' => $id])->row_array();
        $ref = $old['kode'] ?? $old['nomor'] ?? ''; // paket_produk memakai kolom nomor
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $newAktif = $old['aktif'] ? 0 : 1;
        $this->db->where('id', $id)->update($table, [
            'aktif'        => $newAktif,
            'updated_by'   => $this->session->userdata('app_session')['id_user'] ?? 1,
            'updated_date' => date('Y-m-d H:i:s')
        ]);

        $action = $newAktif ? 'Aktifkan' : 'Nonaktifkan';
        write_log($moduleName, $action, $action . ' ' . $moduleName . ' ' . $ref, [
            'old' => $old,
            'new' => array_merge($old, ['aktif' => $newAktif])
        ], $table, $id, $ref);

        return ['status' => 1, 'msg' => $ref . ' ' . ($newAktif ? 'diaktifkan' : 'dinonaktifkan')];
    }

    // =========================================================================
    // 2. PRODUCT COMBINE (PAKET PRODUK) & KATALOG
    // =========================================================================

    public function get_paket_produk($id = null)
    {
        $this->db->select('pp.*, jp.nama as nama_produk, js.nama as nama_sertifikasi, jb.nama as nama_bidang');
        $this->db->from('paket_produk pp');
        $this->db->join('ms_jenis_produk jp', 'pp.id_jenis_produk = jp.id', 'left');
        $this->db->join('ms_jenis_sertifikasi js', 'pp.id_jenis_sertifikasi = js.id', 'left');
        $this->db->join('ms_jenis_bidang jb', 'pp.id_jenis_bidang = jb.id', 'left');

        if ($id) {
            return $this->db->where('pp.id', $id)->get()->row_array();
        }
        return $this->db->order_by('pp.id', 'ASC')->get()->result_array();
    }

    /**
     * Simpan satu paket per baris kombinasi. Gagal-semua (FR-22, NFR-03): bila ada baris kosong atau kombinasinya
     * sama dengan paket yang ada / baris lain, tidak ada yang disimpan dan 'errors' berisi pesan per indeks baris.
     */
    public function save_combine($judul, array $rows)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $labels = ['produk' => 'Produk', 'bidang' => 'Bidang', 'sertifikasi' => 'Sertifikat'];
        $errors = [];
        $seen = [];
        $clean = [];

        foreach ($rows as $i => $r) {
            $ids = ['produk' => (int)($r['produk'] ?? 0), 'bidang' => (int)($r['bidang'] ?? 0), 'sertifikasi' => (int)($r['sertifikasi'] ?? 0)];
            $kosong = [];
            foreach ($ids as $f => $v) if (!$v) $kosong[] = $labels[$f];
            if ($kosong) { $errors[$i] = 'Pilih ' . implode(', ', $kosong); continue; }

            $key = implode('|', $ids);
            $dup = $this->db->get_where('paket_produk', [
                'id_jenis_produk' => $ids['produk'], 'id_jenis_sertifikasi' => $ids['sertifikasi'], 'id_jenis_bidang' => $ids['bidang'],
            ])->row_array();
            if ($dup) $errors[$i] = 'Kombinasi sama persis dengan paket ' . $dup['nomor'] . ' — ' . $dup['judul'];
            elseif (isset($seen[$key])) $errors[$i] = 'Kombinasi sama persis dengan baris ' . ($seen[$key] + 1);
            else $seen[$key] = $i;
            $clean[$i] = $ids;
        }

        if ($errors) {
            return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];
        }

        $nomors = [];
        $this->db->trans_start();
        foreach ($clean as $ids) {
            $nomor = $this->generate_code('paket_produk', 'PC', 'nomor');
            $data = [
                'nomor'                => $nomor,
                'judul'                => trim($judul),
                'id_jenis_produk'      => $ids['produk'],
                'id_jenis_sertifikasi' => $ids['sertifikasi'],
                'id_jenis_bidang'      => $ids['bidang'],
                'aktif'                => 1,
                'created_by'           => $userId,
                'created_date'         => date('Y-m-d H:i:s')
            ];
            $this->db->insert('paket_produk', $data);
            write_log('Master Produk', 'Tambah', 'Tambah Product Combine ' . $nomor, $data, 'paket_produk', $this->db->insert_id(), $nomor);
            $nomors[] = $nomor;
        }
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return ['status' => 0, 'msg' => 'Gagal menyimpan paket produk, tidak ada data yang tersimpan'];
        }
        return ['status' => 1, 'msg' => count($nomors) . ' paket produk tersimpan: ' . implode(', ', $nomors), 'nomor' => $nomors];
    }

    /** Edit judul + 3 atribut (FR-43); keunikan kombinasi FR-22 tanpa paket itu sendiri. $ids = [produk, bidang, sertifikasi]. */
    public function update_paket($id, $judul, array $ids)
    {
        $old = $this->db->get_where('paket_produk', ['id' => $id])->row_array();
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $labels = ['produk' => 'Produk', 'bidang' => 'Bidang', 'sertifikasi' => 'Sertifikat'];
        $ids = array_map('intval', $ids);
        foreach ($labels as $f => $l) {
            if (empty($ids[$f])) return ['status' => 0, 'msg' => $l . ' wajib dipilih'];
        }
        $dup = $this->db->where('id !=', $id)->get_where('paket_produk', [
            'id_jenis_produk' => $ids['produk'], 'id_jenis_sertifikasi' => $ids['sertifikasi'], 'id_jenis_bidang' => $ids['bidang'],
        ])->row_array();
        if ($dup) return ['status' => 0, 'msg' => 'Kombinasi sama persis dengan paket ' . $dup['nomor'] . ' — ' . $dup['judul']];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $data = [
            'judul'                => trim($judul),
            'id_jenis_produk'      => $ids['produk'],
            'id_jenis_sertifikasi' => $ids['sertifikasi'],
            'id_jenis_bidang'      => $ids['bidang'],
            'updated_by'   => $userId,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id)->update('paket_produk', $data);

        write_log('Master Produk', 'Edit', 'Update Paket Produk ' . $old['nomor'], [
            'old' => $old,
            'new' => array_merge($old, $data)
        ], 'paket_produk', $id, $old['nomor']);

        return ['status' => 1, 'msg' => $old['nomor'] . ' berhasil diperbarui'];
    }

    // =========================================================================
    // 3. MASTER PESERTA
    // =========================================================================

    public function get_peserta($id = null)
    {
        $this->db->select('p.*, jb.nama as nama_bidang, c.name_customer as nama_company');
        $this->db->from('ms_peserta p');
        $this->db->join('ms_jenis_bidang jb', 'p.id_jenis_bidang = jb.id', 'left');
        $this->db->join('master_customer c', 'p.id_customer = c.id_customer', 'left');
        $this->db->where('p.deleted_date IS NULL');

        if ($id) {
            return $this->db->where('p.id', $id)->get()->row_array();
        }
        return $this->db->order_by('p.id', 'ASC')->get()->result_array();
    }

    /** Server-side DataTables daftar peserta: No, Kode, Nama, Bidang, Lokasi (FR-15). */
    public function peserta_datatable($p)
    {
        $total = $this->db->where('deleted_date IS NULL')->count_all_results('ms_peserta');

        $search = trim($p['search']['value'] ?? '');
        $apply = function () use ($search) {
            $this->db->from('ms_peserta p')->join('ms_jenis_bidang jb', 'p.id_jenis_bidang = jb.id', 'left')->where('p.deleted_date IS NULL');
            if ($search !== '') {
                $this->db->group_start()->like('p.kode', $search)->or_like('p.nama', $search)->or_like('p.instansi', $search)
                    ->or_like('jb.nama', $search)->or_like('p.kabupaten', $search)->or_like('p.provinsi', $search)->group_end();
            }
        };

        $apply();
        $filtered = $this->db->count_all_results();

        $cols = [1 => 'p.kode', 2 => 'p.nama', 3 => 'jb.nama', 4 => 'p.kabupaten'];
        $ord  = $p['order'][0] ?? [];
        $col  = $cols[(int)($ord['column'] ?? -1)] ?? 'p.id';
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';

        $apply();
        $this->db->select('p.*, jb.nama as nama_bidang, jb.aktif as bidang_aktif')->order_by($col, $dir);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        $rows = $this->db->get()->result_array();

        return [$total, $filtered, $this->with_audit($rows, 'ms_peserta')];
    }

    /** Validasi isian peserta (FR-16, NFR-06). Kembalian: [field => pesan], kosong bila valid. Kunci = nama field di POST. */
    private function validate_peserta($post)
    {
        $e = [];
        $t = function ($k) use ($post) { return trim((string)($post[$k] ?? '')); };
        $req = ['nama' => 'Nama Peserta', 'instansi' => 'Instansi/Perusahaan', 'bidang' => 'Bidang', 'id_prov' => 'Provinsi',
                'id_kab' => 'Kota/Kabupaten', 'no_telp' => 'No Telp/WhatsApp', 'email' => 'Email'];
        foreach ($req as $k => $label) if ($t($k) === '') $e[$k] = $label . ' wajib diisi';
        if (!in_array($t('gender'), ['L', 'P'], true)) $e['gender'] = 'Gender wajib diisi';
        if (!isset($e['email']) && !filter_var($t('email'), FILTER_VALIDATE_EMAIL)) $e['email'] = 'Format email tidak valid';
        if (!isset($e['no_telp']) && !preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $t('no_telp')))) $e['no_telp'] = 'Nomor hanya angka, 9–15 digit';
        if (!isset($e['nama']) && mb_strlen($t('nama')) > 100) $e['nama'] = 'Nama Peserta maksimal 100 karakter';
        if (!isset($e['instansi']) && mb_strlen($t('instansi')) > 150) $e['instansi'] = 'Instansi/Perusahaan maksimal 150 karakter';
        return $e;
    }

    public function save_peserta($post, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        // Company dipilih: Instansi & NPWP Instansi mengikuti company (FR-16); server yang menentukan.
        $idCustomer = !empty($post['id_customer']) ? $post['id_customer'] : null;
        if ($idCustomer) {
            $co = $this->db->get_where('master_customer', ['id_customer' => $idCustomer, 'deleted_date' => null])->row_array();
            if (!$co) return ['status' => 0, 'msg' => 'Company tidak ditemukan', 'errors' => ['id_customer' => 'Company tidak ditemukan']];
            $post['instansi'] = $co['name_customer'];
            $post['npwp_instansi'] = $co['npwp'] ?? '';
        }

        $errors = $this->validate_peserta($post);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $payload = [
            'nama'            => trim($post['nama']),
            'gender'          => $post['gender'],
            'instansi'        => trim($post['instansi']),
            'id_jenis_bidang' => (int)$post['bidang'],
            'id_prov'         => $post['id_prov'],
            'provinsi'        => $post['provinsi'] ?? null,
            'id_kab'          => $post['id_kab'],
            'kabupaten'       => $post['kabupaten'] ?? null,
            'no_telp'         => trim($post['no_telp']),
            'email'           => trim($post['email']),
            'npwp_instansi'   => trim($post['npwp_instansi'] ?? ''),
            'id_customer'     => $idCustomer
        ];

        if ($id) {
            $old = $this->db->get_where('ms_peserta', ['id' => $id])->row_array();
            if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
            $payload['updated_by'] = $userId;
            $payload['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('ms_peserta', $payload);

            write_log('Master Peserta', 'Edit', 'Update Peserta ' . $old['kode'], [
                'old' => $old,
                'new' => array_merge($old, $payload)
            ], 'ms_peserta', $id, $old['kode']);

            return ['status' => 1, 'msg' => 'Peserta ' . $old['kode'] . ' diperbarui'];
        } else {
            $kode = $this->generate_code('ms_peserta', 'PS');
            $payload['kode'] = $kode;
            $payload['aktif'] = 1;
            $payload['created_by'] = $userId;
            $payload['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('ms_peserta', $payload);
            $newId = $this->db->insert_id();

            write_log('Master Peserta', 'Tambah', 'Tambah Peserta ' . $kode, $payload, 'ms_peserta', $newId, $kode);
            return ['status' => 1, 'msg' => 'Peserta ' . $kode . ' ditambahkan', 'id' => $newId, 'kode' => $kode];
        }
    }

    public function delete_peserta($id)
    {
        $old = $this->db->get_where('ms_peserta', ['id' => $id])->row_array();
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        // Cek apakah peserta sudah masuk dalam quotation
        $used = $this->db->get_where('quotation_peserta', ['id_peserta' => $id])->row_array();
        if ($used) {
            return ['status' => 0, 'msg' => 'Peserta ' . $old['kode'] . ' sudah terdaftar dalam Quotation — tidak dapat dihapus.'];
        }

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $this->db->where('id', $id)->update('ms_peserta', [
            'deleted_by'   => $userId,
            'deleted_date' => date('Y-m-d H:i:s')
        ]);

        write_log('Master Peserta', 'Hapus', 'Hapus Peserta ' . $old['kode'], $old, 'ms_peserta', $id, $old['kode']);
        return ['status' => 1, 'msg' => 'Peserta ' . $old['kode'] . ' dihapus'];
    }

    // =========================================================================
    // 4. MASTER COMPANY (MASTER CUSTOMER)
    // =========================================================================

    /** $onlyActive: hanya company aktif (pilihan di form lain, JPP-3); $withId: company ini tetap ikut walau nonaktif. */
    public function get_companies($id = null, $onlyActive = false, $withId = null)
    {
        $this->db->from('master_customer');
        $this->db->where('deleted_date IS NULL');
        if ($id) {
            return $this->db->where('id_customer', $id)->get()->row_array();
        }
        if ($onlyActive) {
            // Data lama: NULL dan 'active' dianggap aktif; 'inactive' / 'nonaktif' = nonaktif.
            $this->db->group_start()->group_start()->where('activation IS NULL', null, false)->or_where_not_in('activation', ['nonaktif', 'inactive'])->group_end();
            if ($withId) $this->db->or_where('id_customer', $withId);
            $this->db->group_end();
        }
        return $this->db->order_by('id_customer', 'ASC')->get()->result_array();
    }

    /** Server-side DataTables daftar company: No, Kode, Nama (+PIC), Jenis, Lokasi, Status (FR-32). Filter: $p['jenis']. */
    public function company_datatable($p)
    {
        $total = $this->db->where('deleted_date IS NULL')->count_all_results('master_customer');

        $search = trim($p['search']['value'] ?? '');
        $jenis  = $p['jenis'] ?? 'all';
        $apply = function () use ($search, $jenis) {
            $this->db->from('master_customer')->where('deleted_date IS NULL');
            if ($jenis !== 'all' && $jenis !== '') $this->db->where('jenis_instansi', $jenis);
            if ($search !== '') {
                $this->db->group_start()->like('id_customer', $search)->or_like('name_customer', $search)->or_like('kota', $search)
                    ->or_like('provinsi', $search)->or_like('pic_name', $search)->group_end();
            }
        };

        $apply();
        $filtered = $this->db->count_all_results();

        $cols = [1 => 'id_customer', 2 => 'name_customer', 3 => 'jenis_instansi', 4 => 'kota', 5 => 'activation'];
        $ord  = $p['order'][0] ?? [];
        $col  = $cols[(int)($ord['column'] ?? -1)] ?? 'id_customer';
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';

        $apply();
        $this->db->order_by($col, $dir);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        $rows = $this->db->get()->result_array();

        return [$total, $filtered, $this->with_audit($rows, 'master_customer', 'id_customer')];
    }

    public function next_company_code()
    {
        $this->db->select('id_customer')->like('id_customer', 'CO-', 'after')->order_by('id_customer', 'DESC')->limit(1);
        $last = $this->db->get('master_customer')->row_array();
        $next = ($last && preg_match('/CO-(\d+)/', $last['id_customer'], $m)) ? (int)$m[1] + 1 : 1;
        return sprintf('CO-%03d', $next);
    }

    public function toggle_company($id)
    {
        $old = $this->db->get_where('master_customer', ['id_customer' => $id, 'deleted_date' => null])->row_array();
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $new = in_array($old['activation'], ['nonaktif', 'inactive'], true) ? 'aktif' : 'nonaktif';
        $upd = ['activation' => $new, 'modified_by' => $this->session->userdata('app_session')['id_user'] ?? 1, 'modified_on' => date('Y-m-d H:i:s')];
        $this->db->where('id_customer', $id)->update('master_customer', $upd);

        $aksi = $new === 'aktif' ? 'Aktifkan' : 'Nonaktifkan';
        write_log('Master Company', $aksi, $aksi . ' Company ' . $id, ['old' => $old, 'new' => array_merge($old, $upd)], 'master_customer', null, $id);
        return ['status' => 1, 'msg' => 'Company ' . $id . ($new === 'aktif' ? ' diaktifkan' : ' dinonaktifkan')];
    }

    /** Validasi isian company (FR-31, NFR-06): semua wajib kecuali NPWP. Kunci = nama field di POST. */
    private function validate_company($post)
    {
        $e = [];
        $t = function ($k) use ($post) { return trim((string)($post[$k] ?? '')); };
        $req = ['nama' => 'Nama Company', 'jenis' => 'Jenis Instansi', 'alamat' => 'Alamat Lengkap', 'id_prov' => 'Provinsi', 'id_kab' => 'Kota/Kabupaten',
                'telp' => 'Telepon Kantor', 'email' => 'Email Kantor', 'picNama' => 'Nama PIC', 'picJabatan' => 'Jabatan PIC', 'picTelp' => 'Telp/WA PIC', 'picEmail' => 'Email PIC'];
        foreach ($req as $k => $label) if ($t($k) === '') $e[$k] = $label . ' wajib diisi';
        if (!isset($e['jenis']) && !in_array($t('jenis'), ['Swasta', 'BUMN/BUMD', 'Pemerintah', 'Pendidikan', 'Lainnya'], true)) $e['jenis'] = 'Jenis Instansi tidak valid';
        foreach (['email', 'picEmail'] as $k) if (!isset($e[$k]) && !filter_var($t($k), FILTER_VALIDATE_EMAIL)) $e[$k] = 'Format email tidak valid';
        foreach (['telp', 'picTelp'] as $k) if (!isset($e[$k]) && !preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $t($k)))) $e[$k] = 'Nomor hanya angka, 9–15 digit';
        if ($t('npwp') !== '' && !preg_match('/^(\d{15}|\d{16})$/', preg_replace('/[.\-\s]/', '', $t('npwp')))) $e['npwp'] = 'NPWP Instansi 15 atau 16 digit angka';
        if (!isset($e['nama']) && mb_strlen($t('nama')) > 150) $e['nama'] = 'Nama Company maksimal 150 karakter';
        if (!isset($e['alamat']) && mb_strlen($t('alamat')) > 255) $e['alamat'] = 'Alamat maksimal 255 karakter';
        return $e;
    }

    public function save_company($post, $id = null)
    {
        $errors = $this->validate_company($post);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        $payload = [
            'name_customer'  => trim($post['nama']),
            'jenis_instansi' => $post['jenis'],
            'address_office' => trim($post['alamat']),
            'id_prov'        => $post['id_prov'],
            'provinsi'       => $post['provinsi'] ?? null,
            'id_city'        => $post['id_kab'],
            'kota'           => $post['kabupaten'] ?? null,
            'telephone'      => trim($post['telp']),
            'email'          => trim($post['email']),
            'pic_name'       => trim($post['picNama']),
            'pic_jabatan'    => trim($post['picJabatan']),
            'pic_hp'         => trim($post['picTelp']),
            'pic_email'      => trim($post['picEmail']),
            'npwp'           => trim($post['npwp'] ?? '')
        ];

        if ($id) {
            $old = $this->db->get_where('master_customer', ['id_customer' => $id])->row_array();
            if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
            $payload['modified_by'] = $userId;
            $payload['modified_on'] = date('Y-m-d H:i:s');
            if (!$this->db->where('id_customer', $id)->update('master_customer', $payload)) return ['status' => 0, 'msg' => 'Gagal menyimpan company'];

            write_log('Master Company', 'Edit', 'Update Company ' . $old['id_customer'], [
                'old' => $old,
                'new' => array_merge($old, $payload)
            ], 'master_customer', $id, $old['id_customer']);

            return ['status' => 1, 'msg' => 'Company ' . $old['id_customer'] . ' diperbarui'];
        }

        $kode = $this->next_company_code();
        $payload['id_customer'] = $kode;
        $payload['activation']  = 'aktif';
        $payload['created_by']  = $userId;
        $payload['created_on']  = date('Y-m-d H:i:s');
        if (!$this->db->insert('master_customer', $payload)) return ['status' => 0, 'msg' => 'Gagal menyimpan company'];

        write_log('Master Company', 'Tambah', 'Tambah Company ' . $kode, $payload, 'master_customer', null, $kode);
        return ['status' => 1, 'msg' => 'Company ' . $kode . ' ditambahkan'];
    }

    public function delete_company($id)
    {
        $old = $this->db->get_where('master_customer', ['id_customer' => $id])->row_array();
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        // Cek apakah company terikat ke Penjualan Program B2B atau Quotation
        $used = $this->db->get_where('penjualan_program', ['id_customer' => $id])->row_array();
        if ($used) {
            return ['status' => 0, 'msg' => 'Company ' . $id . ' sudah digunakan pada Penjualan Program — tidak dapat dihapus.'];
        }

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $this->db->where('id_customer', $id)->update('master_customer', [
            'deleted_by'   => $userId,
            'deleted_date' => date('Y-m-d H:i:s')
        ]);

        write_log('Master Company', 'Hapus', 'Hapus Company ' . $id, $old, 'master_customer', null, $id);
        return ['status' => 1, 'msg' => 'Company ' . $id . ' dihapus'];
    }

    // =========================================================================
    // 5. MASTER RATE (FIXED COST & VARIABLE COST)
    // =========================================================================

    /** $onlyActive: hanya item aktif (pilihan di costing program baru, FR-35). */
    public function get_rates($tipe = null, $id = null, $onlyActive = false)
    {
        $this->db->select('r.*, s.nama as nama_satuan, s.code as kode_satuan');
        $this->db->from('ms_rate r');
        $this->db->join('ms_satuan s', 'r.id_satuan = s.id', 'left');

        if ($tipe) $this->db->where('r.tipe', $tipe);
        if ($id) return $this->db->where('r.id', $id)->get()->row_array();
        if ($onlyActive) $this->db->where('r.aktif', 1);

        return $this->db->order_by('r.id', 'ASC')->get()->result_array();
    }

    public function count_rates($tipe)
    {
        return $this->db->where('tipe', $tipe)->count_all_results('ms_rate');
    }

    /** Satuan yang boleh untuk tab ini saja (FR-25); satuan lain di ms_satuan kategori rate_* (mis. Pax) tidak ditawarkan. */
    public function get_satuans_by_tipe($tipe)
    {
        $cat = ($tipe === 'fixed') ? 'rate_fixed' : 'rate_variable';
        return $this->db->where('category', $cat)->where_in('code', self::RATE_SATUAN[$tipe] ?? [])->order_by('id', 'ASC')->get('ms_satuan')->result_array();
    }

    /** Pilihan COA dari master COA (coa_master punya banyak baris per cabang/periode, jadi dikelompokkan per akun). */
    public function get_coa_options()
    {
        return $this->db->select('no_perkiraan, MAX(nama) AS nama', false)->group_by('no_perkiraan')->order_by('no_perkiraan', 'ASC')->get('coa_master')->result_array();
    }

    /** Validasi item rate (FR-19, FR-25). Kembalian: [field => pesan]. $oldCoa: nilai COA lama record (tetap diterima walau tak ada di master). */
    private function validate_rate($post, $tipe, $oldCoa)
    {
        $e = [];
        $item = trim((string)($post['item'] ?? ''));
        if ($item === '') $e['item'] = 'Item wajib diisi';
        elseif (mb_strlen($item) > 150) $e['item'] = 'Item maksimal 150 karakter';

        $ids = array_column($this->get_satuans_by_tipe($tipe), 'id');
        if (empty($post['id_satuan'])) $e['id_satuan'] = 'Satuan wajib dipilih';
        elseif (!in_array((int)$post['id_satuan'], array_map('intval', $ids), true)) $e['id_satuan'] = 'Satuan tidak sesuai dengan kategori ' . self::RATE_TITLE[$tipe];

        $cost = str_replace('.', '', trim((string)($post['default_cost'] ?? '')));
        if ($cost === '' || !ctype_digit($cost)) $e['default_cost'] = 'Default Cost wajib diisi, angka rupiah ≥ 0';

        if (mb_strlen(trim((string)($post['keterangan'] ?? ''))) > 150) $e['keterangan'] = 'Keterangan maksimal 150 karakter';

        $coa = trim((string)($post['coa'] ?? ''));
        if ($coa === '') $e['coa'] = 'COA wajib dipilih';
        elseif ($coa !== (string)$oldCoa && !$this->db->where('no_perkiraan', $coa)->count_all_results('coa_master')) $e['coa'] = 'COA tidak ditemukan di master COA';
        return $e;
    }

    public function save_rate($post, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        // Kategori tidak dapat diubah setelah disimpan (mockup); item baru memakai kategori dari form.
        $old = $id ? $this->db->get_where('ms_rate', ['id' => $id])->row_array() : null;
        if ($id && !$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
        $tipe = $old ? $old['tipe'] : ($post['tipe'] ?? '');
        if (!isset(self::RATE_TITLE[$tipe])) return ['status' => 0, 'msg' => 'Kategori tidak valid', 'errors' => ['tipe' => 'Kategori tidak valid']];

        $errors = $this->validate_rate($post, $tipe, $old['coa'] ?? null);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $payload = [
            'tipe'         => $tipe,
            'item'         => trim($post['item']),
            'id_satuan'    => (int)$post['id_satuan'],
            'default_cost' => (float)str_replace('.', '', $post['default_cost']),
            'keterangan'   => trim($post['keterangan'] ?? ''),
            'coa'          => trim($post['coa'])
        ];
        $title = self::RATE_TITLE[$tipe];

        if ($old) {
            $payload['updated_by'] = $userId;
            $payload['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('ms_rate', $payload);

            write_log('Master Rate', 'Edit', 'Update Rate ' . $old['kode'], [
                'old' => $old,
                'new' => array_merge($old, $payload)
            ], 'ms_rate', $id, $old['kode']);

            return ['status' => 1, 'msg' => $title . ' ' . $old['kode'] . ' diperbarui'];
        }

        $kode = $this->generate_code('ms_rate', $tipe === 'fixed' ? 'FC' : 'VC');
        $payload['kode'] = $kode;
        $payload['aktif'] = 1;
        $payload['created_by'] = $userId;
        $payload['created_date'] = date('Y-m-d H:i:s');
        $this->db->insert('ms_rate', $payload);
        $newId = $this->db->insert_id();

        write_log('Master Rate', 'Tambah', 'Tambah Rate ' . $kode, $payload, 'ms_rate', $newId, $kode);
        return ['status' => 1, 'msg' => $title . ' ' . $kode . ' ditambahkan'];
    }

    // =========================================================================
    // 6. MASTER DOKUMEN: TEMPLATE KETERANGAN & KONFIRMASI
    // =========================================================================

    public function get_template_keterangan($id = null)
    {
        if ($id) return $this->db->get_where('ms_template_keterangan', ['id' => $id])->row_array();
        // Urut per kategori (FR-49), lalu kode.
        return $this->db->order_by("FIELD(kategori, 'syarat', 'termasuk', 'tidak', 'dokumen', 'klausul'), id", '', false)->get('ms_template_keterangan')->result_array();
    }

    /** Validasi butir keterangan (FR-48): kategori salah satu dari 5, teks wajib ≤ 500. Kembalian: [field => pesan]. */
    private function validate_template_keterangan($post)
    {
        $e = [];
        if (!in_array($post['kategori'] ?? '', ['syarat', 'termasuk', 'tidak', 'dokumen', 'klausul'], true)) $e['kategori'] = 'Kategori wajib dipilih';
        $teks = trim((string)($post['teks'] ?? ''));
        if ($teks === '') $e['teks'] = 'Teks wajib diisi';
        elseif (mb_strlen($teks) > 500) $e['teks'] = 'Teks maksimal 500 karakter';
        if (!in_array($post['berlaku_untuk'] ?? '', ['semua', 'public', 'b2b'], true)) $e['berlaku_untuk'] = 'Berlaku untuk tidak valid';
        return $e;
    }

    public function save_template_keterangan($post, $id = null)
    {
        $errors = $this->validate_template_keterangan($post);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        $payload = [
            'kategori'      => $post['kategori'],
            'teks'          => trim($post['teks']),
            'berlaku_untuk' => $post['berlaku_untuk'],
            'is_default'    => !empty($post['is_default']) ? 1 : 0
        ];

        if ($id) {
            $old = $this->db->get_where('ms_template_keterangan', ['id' => $id])->row_array();
            if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
            $payload['updated_by'] = $userId;
            $payload['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('ms_template_keterangan', $payload);

            write_log('Template Keterangan', 'Edit', 'Update Template ' . $old['kode'], [
                'old' => $old,
                'new' => array_merge($old, $payload)
            ], 'ms_template_keterangan', $id, $old['kode']);

            return ['status' => 1, 'msg' => 'Butir ' . $old['kode'] . ' diperbarui'];
        } else {
            $kode = $this->generate_code('ms_template_keterangan', 'TK');
            $payload['kode'] = $kode;
            $payload['aktif'] = 1;
            $payload['created_by'] = $userId;
            $payload['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('ms_template_keterangan', $payload);
            $newId = $this->db->insert_id();

            write_log('Template Keterangan', 'Tambah', 'Tambah Template ' . $kode, $payload, 'ms_template_keterangan', $newId, $kode);
            return ['status' => 1, 'msg' => 'Butir ' . $kode . ' ditambahkan'];
        }
    }

    /** $onlyActive: hanya template aktif (pilihan di Quotation); $withId: template ini tetap ikut walau nonaktif. */
    public function get_template_konfirmasi($id = null, $onlyActive = false, $withId = null)
    {
        $this->db->select('k.*, jp.nama as nama_produk');
        $this->db->from('ms_template_konfirmasi k');
        $this->db->join('ms_jenis_produk jp', 'k.id_jenis_produk = jp.id', 'left');

        if ($id) return $this->db->where('k.id', $id)->get()->row_array();
        if ($onlyActive) {
            $this->db->group_start()->where('k.aktif', 1);
            if ($withId) $this->db->or_where('k.id', (int)$withId);
            $this->db->group_end();
        }
        return $this->db->order_by('k.id', 'ASC')->get()->result_array();
    }

    /** Validasi template formulir konfirmasi (FR-51). Kembalian: [field => pesan], kosong bila valid. */
    private function validate_template_konfirmasi($post)
    {
        $e = [];
        $t = function ($k) use ($post) { return trim((string)($post[$k] ?? '')); };
        if ($t('nama') === '') $e['nama'] = 'Nama template wajib diisi';
        elseif (mb_strlen($t('nama')) > 80) $e['nama'] = 'Nama template maksimal 80 karakter';
        if ($t('label_kegiatan') === '') $e['label_kegiatan'] = 'Label kegiatan wajib diisi';
        elseif (mb_strlen($t('label_kegiatan')) > 80) $e['label_kegiatan'] = 'Label kegiatan maksimal 80 karakter';
        if ($t('pernyataan') === '') $e['pernyataan'] = 'Kalimat pernyataan wajib diisi';

        $isian = $post['isian'] ?? [];
        if (!is_array($isian) || !array_intersect($isian, array_keys(self::KF_FIELDS))) $e['isian'] = 'Pilih minimal 1 isian';

        if ($t('id_jenis_produk') !== '' && !$this->db->get_where('ms_jenis_produk', ['id' => (int)$post['id_jenis_produk']])->row_array()) {
            $e['id_jenis_produk'] = 'Jenis Produk tidak ditemukan';
        }
        return $e;
    }

    public function save_template_konfirmasi($post, $id = null)
    {
        $errors = $this->validate_template_konfirmasi($post);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        // Isian mengikuti urutan KF_FIELDS; hanya kunci yang dikenal. Catatan: satu butir per baris, baris kosong dibuang.
        $isian = array_values(array_filter(array_keys(self::KF_FIELDS), function ($k) use ($post) { return in_array($k, $post['isian'], true); }));
        $catatan = implode("\n", array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($post['catatan'] ?? '')))));

        $payload = [
            'nama'            => trim($post['nama']),
            'label_kegiatan'  => trim($post['label_kegiatan']),
            'id_jenis_produk' => !empty($post['id_jenis_produk']) ? (int)$post['id_jenis_produk'] : null,
            'isian'           => json_encode($isian),
            'pernyataan'      => trim($post['pernyataan']),
            'catatan'         => $catatan
        ];

        if ($id) {
            $old = $this->db->get_where('ms_template_konfirmasi', ['id' => $id])->row_array();
            if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];
            $payload['updated_by'] = $userId;
            $payload['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('ms_template_konfirmasi', $payload);

            write_log('Template Konfirmasi', 'Edit', 'Update Formulir Konfirmasi ' . $old['kode'], [
                'old' => $old,
                'new' => array_merge($old, $payload)
            ], 'ms_template_konfirmasi', $id, $old['kode']);

            return ['status' => 1, 'msg' => 'Template ' . $old['kode'] . ' diperbarui'];
        } else {
            $kode = $this->generate_code('ms_template_konfirmasi', 'KF');
            $payload['kode'] = $kode;
            $payload['aktif'] = 1;
            $payload['created_by'] = $userId;
            $payload['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('ms_template_konfirmasi', $payload);
            $newId = $this->db->insert_id();

            write_log('Template Konfirmasi', 'Tambah', 'Tambah Formulir Konfirmasi ' . $kode, $payload, 'ms_template_konfirmasi', $newId, $kode);
            return ['status' => 1, 'msg' => 'Template ' . $kode . ' ditambahkan'];
        }
    }

    // =========================================================================
    // 7. MASTER USER (SYNC KE TABEL USERS CI3)
    // =========================================================================

    // Daftar pilihan contoh (Q-25: isi final menyusul dari data Labmania)
    public $user_gender  = ['Laki-laki', 'Perempuan'];
    public $user_agama   = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
    public $user_tipe    = ['Tetap', 'Kontrak', 'Magang', 'Freelance'];

    const ID_SUPERADMIN = 1; // group admin = Superadmin (FR-56): bawaan sistem, tidak dipilih untuk user lain

    public function get_groups()
    {
        return $this->db->select('id_group, nm_group')->order_by('id_group', 'ASC')->get('groups')->result_array();
    }

    // Matriks Override Permission satu user: modul -> menu -> aksi berlaku, hak dari role ($idGroup), hak override ($idUser)
    public function override_matrix($idGroup, $idUser)
    {
        $berlaku = [];
        foreach ($this->db->get('ms_menu_aksi')->result_array() as $r) $berlaku[(int)$r['id_menu']][] = $r['aksi'];
        $role = [];
        foreach ($this->db->get_where('ms_group_akses', ['id_group' => (int)$idGroup])->result_array() as $r) $role[(int)$r['id_menu']][$r['aksi']] = true;
        $ovr = [];
        if ($idUser) foreach ($this->db->get_where('ms_user_akses', ['id_user' => (int)$idUser])->result_array() as $r) $ovr[(int)$r['id_menu']][$r['aksi']] = true;

        $menus = $this->db->where('aktif', 1)->order_by('urutan', 'ASC')->order_by('id', 'ASC')->get('ms_menu')->result_array();
        $byParent = [];
        foreach ($menus as $m) $byParent[(int)$m['parent_id']][] = $m;

        $out = [];
        foreach ($byParent[0] ?? [] as $top) {
            $rows = [];
            foreach (($top['link'] === '#') ? ($byParent[(int)$top['id']] ?? []) : [$top] as $m) {
                $mid = (int)$m['id'];
                if (empty($berlaku[$mid])) continue;
                $rows[] = ['id' => $mid, 'judul' => $m['judul'], 'berlaku' => $berlaku[$mid], 'role' => array_keys($role[$mid] ?? []), 'override' => array_keys($ovr[$mid] ?? [])];
            }
            if ($rows) $out[] = ['judul' => $top['judul'], 'items' => $rows];
        }
        return $out;
    }

    // Simpan override ($akses: [id_menu => [aksi]]); hak yang sudah dari role dibuang, aksi apa pun menyertakan Lihat. Mengembalikan [tersimpan, perubahan-log] atau string galat.
    private function save_override($idUser, $idGroup, $akses)
    {
        $berlaku = [];
        foreach ($this->db->get('ms_menu_aksi')->result_array() as $r) $berlaku[(int)$r['id_menu']][$r['aksi']] = true;
        $judul = [];
        foreach ($this->db->get('ms_menu')->result_array() as $m) $judul[(int)$m['id']] = $m['judul'];
        $role = [];
        foreach ($this->db->get_where('ms_group_akses', ['id_group' => (int)$idGroup])->result_array() as $r) $role[(int)$r['id_menu']][$r['aksi']] = true;

        $baru = [];
        foreach ($akses as $idMenu => $list) {
            $idMenu = (int)$idMenu;
            if (!isset($judul[$idMenu]) || !is_array($list)) return 'Data Override Permission tidak valid';
            foreach ($list as $a) {
                if (!is_string($a) || empty($berlaku[$idMenu][$a])) return 'Data Override Permission tidak valid';
                $baru[$idMenu][$a] = true;
                if (!empty($berlaku[$idMenu]['lihat'])) $baru[$idMenu]['lihat'] = true;
            }
        }
        foreach ($baru as $idMenu => $list) {
            foreach ($list as $a => $_) if (!empty($role[$idMenu][$a])) unset($baru[$idMenu][$a]);
        }

        $lama = [];
        foreach ($this->db->get_where('ms_user_akses', ['id_user' => (int)$idUser])->result_array() as $r) $lama[(int)$r['id_menu']][$r['aksi']] = true;

        $label = lm_aksi_list();
        $changes = [];
        foreach ($berlaku as $idMenu => $aksiList) {
            foreach ($aksiList as $a => $_) {
                $o = !empty($lama[$idMenu][$a]);
                $n = !empty($baru[$idMenu][$a]);
                if ($o !== $n) $changes['Override ' . $judul[$idMenu] . ' - ' . ($label[$a] ?? $a)] = ['old' => $o ? 'Ya' : 'Tidak', 'new' => $n ? 'Ya' : 'Tidak'];
            }
        }

        $this->db->delete('ms_user_akses', ['id_user' => (int)$idUser]);
        foreach ($baru as $idMenu => $list) {
            foreach (array_keys($list) as $a) $this->db->insert('ms_user_akses', ['id_user' => (int)$idUser, 'id_menu' => $idMenu, 'aksi' => $a]);
        }
        return $changes;
    }

    // Satu baris per karyawan (employees) + akun login (users) + group akses
    public function get_users($id = null)
    {
        $this->db->select('e.*, u.username, g.nm_group', false);
        $this->db->from('ms_user_karyawan e');
        $this->db->join('users u', 'u.id_user = e.id_user AND IFNULL(u.deleted, 0) = 0', 'left');
        $this->db->join('`groups` g', 'g.id_group = e.id_group', 'left', false);

        if ($id) return $this->db->where('e.id', $id)->get()->row_array();
        return $this->db->order_by('e.id', 'ASC')->get()->result_array();
    }

    public function save_user($post, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now    = date('Y-m-d H:i:s');
        $v      = array_map(function ($x) { return is_string($x) ? trim($x) : $x; }, $post);
        $akun   = !empty($v['akun']);
        $pw     = (string)($post['password'] ?? '');
        $pw2    = (string)($post['password2'] ?? '');
        $fail   = function ($msg) { return ['status' => 0, 'msg' => $msg]; };

        $old = $id ? $this->get_users($id) : null;
        if ($id && !$old) return $fail('Data tidak ditemukan');
        $hasAkun = $old && !empty($old['id_user']);

        // --- Validasi (FR-37, FR-38) ---
        $req = ['nama' => 'Employee Name', 'id_group' => 'Role'];
        foreach ($req as $k => $label) {
            if (($v[$k] ?? '') === '') return $fail($label . ' wajib diisi');
        }
        $idGroup = (int)$v['id_group'];
        $oldRole = $old ? (int)$old['id_group'] : 0;
        if ($oldRole === self::ID_SUPERADMIN) $idGroup = self::ID_SUPERADMIN; // role Superadmin tidak dapat diganti
        elseif ($idGroup === self::ID_SUPERADMIN) return $fail('Role Superadmin tidak dapat dipilih');
        if (!$this->db->get_where('groups', ['id_group' => $idGroup])->row_array()) return $fail('Role tidak ditemukan');
        $v['kode'] = $old ? $old['kode'] : null; // Employee ID: dibuat otomatis saat tambah (di bawah), tidak dapat diubah
        if (($v['nik'] ?? '') !== '' && !preg_match('/^\d{16}$/', $v['nik'])) return $fail('NIK 16 digit angka');
        if (($v['telp'] ?? '') !== '' && !preg_match('/^\+?\d{9,15}$/', $v['telp'])) return $fail('Nomor telepon hanya angka (boleh diawali +), 9–15 digit');
        if (($v['email'] ?? '') !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) return $fail('Format email tidak valid');
        if (($v['npwp'] ?? '') !== '' && !preg_match('/^(\d{15}|\d{16})$/', preg_replace('/[.\-\s]/', '', $v['npwp']))) return $fail('NPWP 15 atau 16 digit angka');
        if (!empty($v['tgl_lahir']) && !empty($v['tgl_masuk']) && $v['tgl_masuk'] < $v['tgl_lahir']) return $fail('Join Date sebelum Birth Date');

        $akun = $akun || $hasAkun; // akun yang sudah ada tidak dihapus, hanya dinonaktifkan lewat Status
        if ($akun) {
            if (!preg_match('/^[a-z0-9._]{3,50}$/', $v['username'] ?? '')) return $fail('Username: huruf kecil, angka, titik, atau garis bawah; 3–50 karakter');
            $dupUser = $this->db->where('username', $v['username'])->where('id_user <>', $hasAkun ? (int)$old['id_user'] : 0)->count_all_results('users');
            if ($dupUser) return $fail('Username sudah dipakai');
            $needPw = !$hasAkun;
            if (($needPw || $pw !== '') && strlen($pw) < 8) return $fail('Password minimal 8 karakter');
            if (($needPw || $pw !== '') && $pw !== $pw2) return $fail('Konfirmasi password tidak sama');
        }

        $aktif = ($v['aktif'] ?? '1') === '1' ? 1 : 0;
        $dataEmp = [
            'kode'       => $v['kode'],
            'nama'       => $v['nama'],
            'nik'        => ($v['nik'] ?? '') !== '' ? $v['nik'] : null,
            'tmp_lahir'  => ($v['tmp_lahir'] ?? '') ?: null,
            'tgl_lahir'  => ($v['tgl_lahir'] ?? '') ?: null,
            'id_group'   => $idGroup,
            'gender'     => ($v['gender'] ?? '') ?: null,
            'agama'      => ($v['agama'] ?? '') ?: null,
            'telp'       => ($v['telp'] ?? '') ?: null,
            'email'      => ($v['email'] ?? '') ?: null,
            'alamat'     => ($v['alamat'] ?? '') ?: null,
            'npwp'       => ($v['npwp'] ?? '') ?: null,
            'tgl_masuk'  => ($v['tgl_masuk'] ?? '') ?: null,
            'tipe'       => ($v['tipe'] ?? '') ?: null,
            'jobdesc'    => ($v['jobdesc'] ?? '') ?: null,
            'aktif'      => $aktif,
        ];

        $this->db->trans_start();
        if ($id) {
            $dataEmp['updated_by']   = $userId;
            $dataEmp['updated_date'] = $now;
            $this->db->where('id', $id)->update('ms_user_karyawan', $dataEmp);
        } else {
            // ponytail: MAX+1 tanpa lock; bentrok dua simpan serentak ditolak UNIQUE(kode) -> "Gagal menyimpan", ulangi. Tambah lock/sequence bila ramai.
            $max = (int)$this->db->query("SELECT MAX(CAST(SUBSTRING(kode, 5) AS UNSIGNED)) AS m FROM ms_user_karyawan WHERE kode REGEXP '^USR-[0-9]+$'")->row()->m;
            $v['kode'] = $dataEmp['kode'] = 'USR-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
            $dataEmp['created_by']   = $userId;
            $dataEmp['created_date'] = $now;
            $this->db->insert('ms_user_karyawan', $dataEmp);
            $id = $this->db->insert_id();
        }

        $logAkun = [];
        if ($akun) {
            $dataUser = [
                'username'    => $v['username'],
                'nm_lengkap'  => $v['nama'],
                'email'       => ($v['email'] ?? '') ?: null,
                'hp'          => ($v['telp'] ?? '') ?: null,
                'alamat'      => ($v['alamat'] ?? '') ?: null,
                'st_aktif'    => $aktif,
            ];
            if ($pw !== '') $dataUser['password'] = password_hash($pw, PASSWORD_DEFAULT);

            if ($hasAkun) {
                $idUser = (int)$old['id_user'];
                $this->db->where('id_user', $idUser)->update('users', $dataUser);
            } else {
                $dataUser['created_on'] = $now;
                $this->db->insert('users', $dataUser);
                $idUser = $this->db->insert_id();
                $this->db->where('id', $id)->update('ms_user_karyawan', ['id_user' => $idUser]);
            }
            $this->db->where('id_user', $idUser)->delete('user_groups');
            $this->db->insert('user_groups', ['id_user' => $idUser, 'id_group' => $idGroup]);
            $logAkun = ['username' => $v['username'], 'password_diganti' => $pw !== ''];

            // Override Permission (FR-65): tidak untuk Superadmin (sudah semua hak)
            $ovr = $idGroup === self::ID_SUPERADMIN ? [] : (is_array($post['override'] ?? null) ? $post['override'] : []);
            $detail = $this->save_override($idUser, $idGroup, $ovr);
            if (is_string($detail)) {
                $this->db->trans_rollback();
                return $fail($detail);
            }
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return $fail('Gagal menyimpan data user');

        // Log tanpa nilai password
        $isEdit  = (bool)$old;
        $payload = $isEdit
            ? ['old' => $old, 'new' => array_merge($old, $dataEmp, $logAkun)]
            : array_merge($dataEmp, $logAkun);
        if (!empty($detail)) $payload['detail_changes'] = $detail;
        write_log('Master User', $isEdit ? 'Edit' : 'Tambah', ($isEdit ? 'Update' : 'Tambah') . ' User ' . $v['kode'], $payload, 'ms_user_karyawan', $id, $v['kode']);

        return ['status' => 1, 'msg' => 'User ' . $v['kode'] . ($isEdit ? ' diperbarui' : ' ditambahkan') . ($pw !== '' && $isEdit ? ' (password diganti)' : '')];
    }

    public function toggle_user($id)
    {
        $old = $this->get_users($id);
        if (!$old) return ['status' => 0, 'msg' => 'Data tidak ditemukan'];

        $new = $old['aktif'] ? 0 : 1;
        $this->db->trans_start();
        $this->db->where('id', $id)->update('ms_user_karyawan', [
            'aktif'        => $new,
            'updated_by'   => $this->session->userdata('app_session')['id_user'] ?? 1,
            'updated_date' => date('Y-m-d H:i:s')
        ]);
        if (!empty($old['id_user'])) {
            $this->db->where('id_user', $old['id_user'])->update('users', ['st_aktif' => $new]);
        }
        $this->db->trans_complete();

        $action = $new ? 'Aktifkan' : 'Nonaktifkan';
        write_log('Master User', $action, $action . ' User ' . $old['kode'], [
            'old' => ['aktif' => (int)$old['aktif']],
            'new' => ['aktif' => $new]
        ], 'ms_user_karyawan', $id, $old['kode']);

        return ['status' => 1, 'msg' => 'User ' . $old['kode'] . ($new ? ' diaktifkan' : ' dinonaktifkan')];
    }

    public function reset_password_user($id, $newPassword)
    {
        $old = $this->get_users($id);
        if (!$old || empty($old['id_user'])) return ['status' => 0, 'msg' => 'User tidak memiliki akun login'];
        if (strlen($newPassword) < 8) return ['status' => 0, 'msg' => 'Password minimal 8 karakter'];

        $this->db->where('id_user', $old['id_user'])->update('users', ['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

        write_log('Master User', 'Reset Password', 'Reset Password User ' . $old['username'], null, 'ms_user_karyawan', $id, $old['kode']);
        return ['status' => 1, 'msg' => 'Password ' . $old['username'] . ' diganti'];
    }
}
