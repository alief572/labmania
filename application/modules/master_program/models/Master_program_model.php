<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_program_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    public function get_master_programs($status = null, $keyword = null)
    {
        $this->db->select('mp.*, pp.nomor as nomor_paket, pp.judul as nama_paket,
                           jp.nama as nama_produk, js.nama as nama_sertifikasi, jb.nama as nama_bidang,
                           uc.username as user_confirm');
        $this->db->from('master_program mp');
        $this->db->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left');
        $this->db->join('ms_jenis_produk jp', 'pp.id_jenis_produk = jp.id', 'left');
        $this->db->join('ms_jenis_sertifikasi js', 'pp.id_jenis_sertifikasi = js.id', 'left');
        $this->db->join('ms_jenis_bidang jb', 'pp.id_jenis_bidang = jb.id', 'left');
        $this->db->join('users uc', 'mp.confirmed_by = uc.id_user', 'left');

        if ($status && $status !== 'all') {
            $this->db->where('mp.status', $status);
        }

        if ($keyword) {
            $this->db->group_start();
            $this->db->like('mp.nomor', $keyword);
            $this->db->or_like('pp.judul', $keyword);
            $this->db->or_like('pp.nomor', $keyword);
            $this->db->group_end();
        }

        $programs = $this->db->order_by('mp.id', 'DESC')->get()->result_array();

        // Hitung kalkulasi HPP dan Profit untuk tiap program
        foreach ($programs as &$p) {
            $biayas = $this->db->select('b.*, s.nama as nama_satuan')
                               ->from('master_program_biaya b')
                               ->join('ms_satuan s', 'b.id_satuan = s.id', 'left')
                               ->where('b.id_master_program', $p['id'])
                               ->get()->result_array();

            $p['calc'] = $this->calculate_costing($p['durasi_hari'], $p['target_peserta'], $p['harga_jual_peserta'], $biayas);
            $p['jumlah_biaya'] = count($biayas);
        }

        return $programs;
    }

    /** Server-side DataTables daftar Master Program (MP-FR-01). Filter: $p['status'] (all|draft|final|batal). HPP dihitung di SQL (sama dengan calculate_costing). */
    public function program_datatable($p)
    {
        $total = $this->db->count_all('master_program');

        $search = trim($p['search']['value'] ?? '');
        $status = $p['status'] ?? 'all';
        $apply = function () use ($search, $status) {
            $this->db->from('master_program mp')->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left');
            if (in_array($status, ['draft', 'final', 'batal'], true)) $this->db->where('mp.status', $status);
            if ($search !== '') {
                $this->db->group_start()->like('mp.nomor', $search)->or_like('pp.judul', $search)->or_like('pp.nomor', $search)->group_end();
            }
        };

        $apply();
        $filtered = $this->db->count_all_results();

        $cols = [1 => 'mp.nomor', 2 => 'pp.judul', 3 => 'mp.durasi_hari', 4 => 'mp.target_peserta', 5 => 'mp.harga_jual_peserta', 6 => 'hpp', 7 => 'mp.status'];
        $ord  = $p['order'][0] ?? [];
        $col  = $cols[(int)($ord['column'] ?? -1)] ?? 'mp.id';
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : (isset($cols[(int)($ord['column'] ?? -1)]) ? 'ASC' : 'DESC');

        $hpp = "(SELECT COALESCE(SUM(CASE WHEN b.tipe = 'fixed' THEN b.harga * IF(s.nama = 'Day', mp.durasi_hari, 1)
                                          ELSE b.harga * IF(s.nama = 'Peserta/Day', mp.durasi_hari, 1) * mp.target_peserta END), 0)
                   FROM master_program_biaya b LEFT JOIN ms_satuan s ON s.id = b.id_satuan WHERE b.id_master_program = mp.id) AS hpp";

        $apply();
        $this->db->select("mp.*, pp.nomor AS nomor_paket, pp.judul AS nama_paket, (SELECT js.nama FROM paket_produk x JOIN ms_jenis_sertifikasi js ON js.id = x.id_jenis_sertifikasi WHERE x.id = pp.id) AS nama_sertifikasi, $hpp", false)
                 ->order_by($col, $dir);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        $rows = $this->db->get()->result_array();

        return [$total, $filtered, $this->md_model->with_audit($rows, 'master_program', 'nomor')];
    }

    public function get_program_by_id($id)
    {
        $this->db->select('mp.*, pp.nomor as nomor_paket, pp.judul as nama_paket,
                           jp.nama as nama_produk, js.nama as nama_sertifikasi, jb.nama as nama_bidang');
        $this->db->from('master_program mp');
        $this->db->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left');
        $this->db->join('ms_jenis_produk jp', 'pp.id_jenis_produk = jp.id', 'left');
        $this->db->join('ms_jenis_sertifikasi js', 'pp.id_jenis_sertifikasi = js.id', 'left');
        $this->db->join('ms_jenis_bidang jb', 'pp.id_jenis_bidang = jb.id', 'left');
        $this->db->where('mp.id', $id);
        $program = $this->db->get()->row_array();

        if ($program) {
            $program['biaya'] = $this->biaya_rows($id);

            $program['calc'] = $this->calculate_costing(
                $program['durasi_hari'],
                $program['target_peserta'],
                $program['harga_jual_peserta'],
                $program['biaya']
            );
        }

        return $program;
    }

    public function biaya_rows($idMp)
    {
        return $this->db->select('b.*, s.nama as nama_satuan')
                        ->from('master_program_biaya b')
                        ->join('ms_satuan s', 'b.id_satuan = s.id', 'left')
                        ->where('b.id_master_program', $idMp)
                        ->order_by('b.tipe', 'ASC')
                        ->order_by('b.id', 'ASC')
                        ->get()->result_array();
    }

    public function generate_nomor()
    {
        $yymm = date('ym');
        $prefix = 'MP-' . $yymm;

        $this->db->select('nomor');
        $this->db->like('nomor', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('master_program')->row_array();

        $next = 1;
        if ($row && preg_match('/MP-\d{4}-(\d+)/', $row['nomor'], $m)) {
            $next = (int)$m[1] + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
    }

    /** Fixed Cost aktif (MP-FR-06): semua item aktif tampil dengan Default Cost; yang tak dipakai dihapus user (item bernilai 0 dibuang saat simpan). */
    public function get_default_fixed_costs()
    {
        return $this->db->select('r.*, s.nama as nama_satuan')
                        ->from('ms_rate r')
                        ->join('ms_satuan s', 'r.id_satuan = s.id', 'left')
                        ->where('r.tipe', 'fixed')
                        ->where('r.aktif', 1)
                        ->order_by('r.id', 'ASC')
                        ->get()->result_array();
    }

    /** Semua Master Rate (aktif dan nonaktif) untuk form: label "default Rp X" dan penanda "(nonaktif)" pada baris tersimpan. */
    public function get_rates_lookup()
    {
        return $this->db->select('r.id, r.tipe, r.item, r.id_satuan, r.default_cost, r.aktif, s.nama as nama_satuan')
                        ->from('ms_rate r')
                        ->join('ms_satuan s', 'r.id_satuan = s.id', 'left')
                        ->order_by('r.id', 'ASC')
                        ->get()->result_array();
    }

    /** Paket Produk untuk pilihan: yang aktif, ditambah paket yang sedang dipakai program ini. */
    public function get_paket_options($withId = null)
    {
        $rows = $this->md_model->get_paket_produk();
        return array_values(array_filter($rows, function ($p) use ($withId) {
            return $p['aktif'] == 1 || ($withId && (int)$p['id'] === (int)$withId);
        }));
    }

    public function calculate_costing($durasi, $targetPeserta, $hargaJual, array $biayas)
    {
        $durasi = (int)$durasi ?: 1;
        $target = (int)$targetPeserta ?: 0;
        $harga = (float)$hargaJual ?: 0;

        $totalFixed = 0;
        $varPerPeserta = 0;

        foreach ($biayas as $b) {
            $satuan = $b['nama_satuan'] ?? '';
            $h = (float)$b['harga'];

            // Faktor pengali durasi untuk Day / Peserta/Day
            $factor = ($satuan === 'Day' || $satuan === 'Peserta/Day') ? $durasi : 1;
            $sub = $h * $factor;

            if ($b['tipe'] === 'fixed') {
                $totalFixed += $sub;
            } else {
                $varPerPeserta += $sub;
            }
        }

        $totalVar = $varPerPeserta * $target;
        $totalHpp = $totalFixed + $totalVar;
        $hppPerPax = $target > 0 ? ($totalHpp / $target) : 0;

        $pendapatan = $harga * $target;
        $profit = $pendapatan - $totalHpp;
        $margin = $pendapatan > 0 ? round(($profit / $pendapatan) * 100, 1) : 0;

        // BEP peserta = Fixed ÷ (harga − variable per peserta), dibulatkan ke atas, minimal 1 (MP-NFR-04)
        $kontribusi = $harga - $varPerPeserta;
        $bepPeserta = null;
        if ($kontribusi > 0) {
            $bepPeserta = max(1, (int)ceil(round($totalFixed / $kontribusi, 6)));
        }

        return [
            'total_fixed'    => $totalFixed,
            'var_per_pax'    => $varPerPeserta,
            'total_var'      => $totalVar,
            'total_hpp'      => $totalHpp,
            'hpp_per_pax'    => $hppPerPax,
            'pendapatan'     => $pendapatan,
            'profit'         => $profit,
            'margin'         => $margin,
            'bep_peserta'    => $bepPeserta
        ];
    }

    /** Peringatan Finalkan (MP-FR-12): profit minus, BEP tidak tercapai / di atas target. */
    public function final_warnings($calc, $target)
    {
        $w = [];
        if ($calc['profit'] < 0) $w[] = 'Profit minus.';
        if ($calc['bep_peserta'] === null) $w[] = 'BEP tidak tercapai: harga jual ≤ biaya variable / peserta.';
        elseif ($calc['bep_peserta'] > (int)$target) $w[] = 'BEP ' . $calc['bep_peserta'] . ' peserta di atas target ' . (int)$target . ' peserta.';
        return $w;
    }

    /** Ringkasan untuk popup Finalkan / Batalkan (angka dihitung server). */
    public function get_ringkasan($id)
    {
        $p = $this->get_program_by_id($id);
        if (!$p) return null;
        $c = $p['calc'];
        return [
            'id'          => (int)$p['id'],
            'nomor'       => $p['nomor'],
            'judul'       => $p['nama_paket'],
            'status'      => $p['status'],
            'durasi'      => (int)$p['durasi_hari'],
            'target'      => (int)$p['target_peserta'],
            'harga'       => (float)$p['harga_jual_peserta'],
            'hpp'         => $c['total_hpp'],
            'profit'      => $c['profit'],
            'bep_peserta' => $c['bep_peserta'],
            'bep_harga'   => $c['hpp_per_pax'],
            'warnings'    => $this->final_warnings($c, $p['target_peserta']),
            'n_penjualan' => $this->db->where('id_master_program', $id)->count_all_results('penjualan_program'),
        ];
    }

    public function to_int($v)
    {
        $s = str_replace('.', '', trim((string)$v));
        return ($s !== '' && ctype_digit($s)) ? (int)$s : null;
    }

    /**
     * Validasi baris biaya costing (dipakai Master Program dan Penjualan Program): satuan sesuai tipe (FR-25), item rate tidak ganda,
     * rate harus aktif untuk baris baru, nominal ≥ 0, nama biaya lain wajib, nominal 0 dibuang (MP-FR-07).
     * $oldRates: id_rate yang sudah dipakai record ini (tetap boleh walau kini nonaktif). Kembalian: [biaya bersih, errors 'b{i}', jumlah dibuang].
     */
    public function validate_biaya(array $rows, array $oldRates)
    {
        $satuan = ['fixed' => array_column($this->md_model->get_satuans_by_tipe('fixed'), 'id'), 'variable' => array_column($this->md_model->get_satuans_by_tipe('variable'), 'id')];
        $e = [];
        $biaya = [];
        $dropped = 0;
        $seenRate = [];
        foreach ($rows as $i => $b) {
            if (!is_array($b)) continue;
            $k = 'b' . $i;
            $tipe = $b['tipe'] ?? '';
            $item = trim((string)($b['item'] ?? ''));
            $isManual = !empty($b['is_manual']);
            $idRate = !empty($b['id_rate']) ? (int)$b['id_rate'] : null;
            $label = $item !== '' ? $item : '(biaya lain tanpa nama)';

            if (!in_array($tipe, ['fixed', 'variable'], true)) { $e[$k] = 'Jenis biaya tidak valid'; continue; }
            if ($isManual) {
                if ($item === '') { $e[$k] = 'Nama biaya lain wajib diisi'; continue; }
                $idRate = null;
            } else {
                $rate = $idRate ? $this->db->get_where('ms_rate', ['id' => $idRate])->row_array() : null;
                if (!$rate || $rate['tipe'] !== $tipe) { $e[$k] = 'Item Master Rate ' . $label . ' tidak ditemukan'; continue; }
                if (!$rate['aktif'] && !in_array((string)$idRate, array_map('strval', $oldRates), true)) { $e[$k] = 'Item ' . $label . ' sudah nonaktif di Master Rate'; continue; }
                if (isset($seenRate[$idRate])) { $e[$k] = 'Item ' . $label . ' sudah ada (tidak boleh dua kali)'; continue; }
                $seenRate[$idRate] = true;
                if ($item === '') $item = $rate['item'];
            }
            if (mb_strlen($item) > 150) { $e[$k] = 'Nama biaya maksimal 150 karakter'; continue; }

            $idSatuan = (int)($b['id_satuan'] ?? 0);
            if (!in_array($idSatuan, array_map('intval', $satuan[$tipe]), true)) { $e[$k] = 'Satuan ' . $label . ' tidak sesuai jenis biaya'; continue; }

            $nominal = $this->to_int($b['harga'] ?? '');
            if ($nominal === null) { $e[$k] = 'Nominal ' . $label . ' wajib diisi (angka rupiah ≥ 0)'; continue; }
            if ($nominal === 0) { $dropped++; continue; } // bernilai 0 dianggap tidak dipakai (MP-FR-07)

            $biaya[] = ['id_rate' => $idRate, 'tipe' => $tipe, 'item' => $item, 'id_satuan' => $idSatuan, 'harga' => (float)$nominal, 'is_manual' => $isManual ? 1 : 0];
        }
        return [$biaya, $e, $dropped];
    }

    /**
     * Validasi isian Master Program (MP-FR-02, MP-NFR-05). Kembalian: [header, biaya bersih, errors, jumlah item 0 dibuang].
     * errors: kolom header => pesan; 'b{i}' => pesan untuk baris biaya ke-i (indeks dari form).
     */
    private function validate_program($post, $old)
    {
        $e = [];

        $paketId = (int)($post['id_paket_produk'] ?? 0);
        $paket = $paketId ? $this->db->get_where('paket_produk', ['id' => $paketId])->row_array() : null;
        if (!$paket) $e['id_paket_produk'] = 'Paket Produk wajib dipilih';
        elseif (!$paket['aktif'] && (!$old || (int)$old['id_paket_produk'] !== $paketId)) $e['id_paket_produk'] = 'Paket Produk harus berstatus Aktif';

        $durasi = $this->to_int($post['durasi_hari'] ?? '');
        if (!$durasi) $e['durasi_hari'] = 'Durasi bilangan bulat > 0';
        $target = $this->to_int($post['target_peserta'] ?? '');
        if (!$target) $e['target_peserta'] = 'Target peserta bilangan bulat > 0';
        $harga = $this->to_int($post['harga_jual_peserta'] ?? '');
        if ($harga === null) $e['harga_jual_peserta'] = 'Harga jual / peserta wajib diisi';
        $tp = trim((string)($post['target_profit'] ?? '')) === '' ? 0 : $this->to_int($post['target_profit']);
        if ($tp === null) $e['target_profit'] = 'Target profit berupa angka rupiah ≥ 0';

        $oldRates = $old ? array_column($this->biaya_rows($old['id']), 'id_rate') : [];
        list($biaya, $eb, $dropped) = $this->validate_biaya((array)($post['biaya'] ?? []), $oldRates);
        $e += $eb;

        $header = [
            'id_paket_produk'    => $paketId,
            'durasi_hari'        => (int)$durasi,
            'target_peserta'     => (int)$target,
            'harga_jual_peserta' => (float)$harga,
            'target_profit'      => (float)$tp,
        ];
        return [$header, $biaya, $e, $dropped];
    }

    /** Selisih baris biaya lama vs baru untuk log (AGENTS.md §2): ['Item [Nama] - Harga' => ['old' => .., 'new' => ..]]. */
    public function biaya_changes(array $oldRows, array $newRows)
    {
        $key = function ($r) { return $r['tipe'] . '|' . ($r['id_rate'] ?: 'm:' . $r['item']) . '|' . $r['id_satuan']; };
        $old = [];
        foreach ($oldRows as $r) $old[$key($r)] = $r;
        $new = [];
        foreach ($newRows as $r) $new[$key($r)] = $r;

        $changes = [];
        foreach ($new as $k => $r) {
            if (!isset($old[$k])) $changes['Item [' . $r['item'] . '] - Ditambahkan'] = ['old' => null, 'new' => (float)$r['harga']];
            elseif ((float)$old[$k]['harga'] !== (float)$r['harga']) $changes['Item [' . $r['item'] . '] - Harga'] = ['old' => (float)$old[$k]['harga'], 'new' => (float)$r['harga']];
        }
        foreach ($old as $k => $r) {
            if (!isset($new[$k])) $changes['Item [' . $r['item'] . '] - Dihapus'] = ['old' => (float)$r['harga'], 'new' => null];
        }
        return $changes;
    }

    /** Simpan Draft: hanya program berstatus Draft yang dapat dibuat/diubah (MP-NFR-02). Status Final diatur lewat set_status(). */
    public function save_program($post, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        $old = $id ? $this->db->get_where('master_program', ['id' => $id])->row_array() : null;
        if ($id && !$old) return ['status' => 0, 'msg' => 'Master Program tidak ditemukan'];

        list($header, $biaya, $errors, $dropped) = $this->validate_program($post, $old);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $this->db->trans_start();

        $oldBiaya = [];
        if ($id) {
            // Status dibaca ulang di dalam transaksi dengan kunci baris: Final/Batal tidak dapat ditimpa.
            $locked = $this->db->query('SELECT status FROM master_program WHERE id = ? FOR UPDATE', [$id])->row_array();
            if (!$locked || $locked['status'] !== 'draft') {
                $this->db->trans_complete();
                return ['status' => 0, 'msg' => 'Hanya Master Program berstatus Draft yang dapat diubah'];
            }
            $oldBiaya = $this->biaya_rows($id);
            $header['updated_by']   = $userId;
            $header['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('master_program', $header);
            $mpId = $id;
            $nomor = $old['nomor'];
        } else {
            $nomor = $this->generate_nomor();
            $header['nomor']        = $nomor;
            $header['status']       = 'draft';
            $header['created_by']   = $userId;
            $header['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('master_program', $header);
            $mpId = $this->db->insert_id();
        }

        $this->db->delete('master_program_biaya', ['id_master_program' => $mpId]);
        foreach ($biaya as $b) {
            $this->db->insert('master_program_biaya', ['id_master_program' => $mpId] + $b);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['status' => 0, 'msg' => 'Gagal menyimpan Master Program'];
        }

        if ($old) {
            write_log('Master Program', 'Edit', 'Update Master Program ' . $nomor, [
                'old'            => $old,
                'new'            => array_merge($old, $header),
                'detail_changes' => $this->biaya_changes($oldBiaya, $biaya)
            ], 'master_program', $mpId, $nomor);
        } else {
            write_log('Master Program', 'Tambah', 'Tambah Master Program ' . $nomor, $header + ['jumlah_biaya' => count($biaya)], 'master_program', $mpId, $nomor);
        }

        return ['status' => 1, 'msg' => 'Master Program ' . $nomor . ' tersimpan sebagai Draft' . ($dropped ? ' · ' . $dropped . ' item bernilai 0 dibuang' : ''), 'id' => $mpId, 'nomor' => $nomor];
    }

    /** Transisi status: hanya Draft → Final atau Draft → Batal (MP-FR-11, -14, -15); diperiksa ulang di dalam transaksi. */
    public function set_status($id, $newStatus)
    {
        if (!in_array($newStatus, ['final', 'batal'], true)) return ['status' => 0, 'msg' => 'Status tidak valid'];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $old = $this->db->query('SELECT * FROM master_program WHERE id = ? FOR UPDATE', [$id])->row_array();
        if (!$old) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Master Program tidak ditemukan']; }

        if ($old['status'] !== 'draft') {
            $this->db->trans_complete();
            $msg = $old['status'] === 'final'
                ? 'Master Program ' . $old['nomor'] . ' sudah Final — tidak dapat difinalkan ulang, dibatalkan, atau diubah'
                : 'Master Program ' . $old['nomor'] . ' sudah dibatalkan';
            return ['status' => 0, 'msg' => $msg];
        }

        $update = ['status' => $newStatus];
        if ($newStatus === 'final') {
            $update['confirmed_by']   = $userId;
            $update['confirmed_date'] = $now;
        } else {
            $update['closed_by']   = $userId;
            $update['closed_date'] = $now;
        }
        $update['updated_by'] = $userId;
        $update['updated_date'] = $now;
        $this->db->where('id', $id)->update('master_program', $update);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal mengubah status Master Program'];

        $action = ($newStatus === 'final') ? 'Finalkan' : 'Batalkan';
        write_log('Master Program', $action, $action . ' Master Program ' . $old['nomor'], [
            'old' => $old,
            'new' => array_merge($old, $update)
        ], 'master_program', $id, $old['nomor']);

        return ['status' => 1, 'msg' => 'Master Program ' . $old['nomor'] . ($newStatus === 'final' ? ' Final — tersedia di pilihan Penjualan Program' : ' dibatalkan')];
    }
}
