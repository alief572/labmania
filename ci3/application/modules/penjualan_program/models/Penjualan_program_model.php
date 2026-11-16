<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penjualan_program_model extends CI_Model
{
    const B2B_MIN = 10; // PJ-FR-25

    public function __construct()
    {
        parent::__construct();
        $this->load->model('master_program/Master_program_model', 'mp_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    /** Server-side DataTables Daftar Penjualan Program (PJ-FR-01). Filter: $p['status'], $p['jenis'] (all|…). */
    public function penjualan_datatable($p)
    {
        $total = $this->db->count_all('penjualan_program');

        $search = trim($p['search']['value'] ?? '');
        $status = $p['status'] ?? 'all';
        $jenis  = $p['jenis'] ?? 'all';
        // Tahun & Bulan menurut tanggal MULAI acara (PJ-FR-01). Keduanya 'all' = semua, termasuk program tanpa tanggal.
        $tahun  = preg_match('/^\d{4}$/', (string)($p['tahun'] ?? '')) ? (int)$p['tahun'] : null;
        $bulan  = preg_match('/^(0?[1-9]|1[0-2])$/', (string)($p['bulan'] ?? '')) ? (int)$p['bulan'] : null;
        $apply = function () use ($search, $status, $jenis, $tahun, $bulan) {
            $this->db->from('penjualan_program pj')
                ->join('master_program mp', 'pj.id_master_program = mp.id', 'left')
                ->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false)
                ->join('master_customer c', 'pj.id_customer = c.id_customer', 'left');
            if (in_array($status, ['draft', 'final', 'batal'], true)) $this->db->where('pj.status', $status);
            if (in_array($jenis, ['public', 'b2b'], true)) $this->db->where('pj.jenis', $jenis);
            if ($tahun) $this->db->where('YEAR(pj.tgl_mulai)', $tahun, false);
            if ($bulan) $this->db->where('MONTH(pj.tgl_mulai)', $bulan, false);
            if ($search !== '') {
                $this->db->group_start()->like('pj.nomor', $search)->or_like('mp.nomor', $search)->or_like('pp.judul', $search)
                    ->or_like('c.name_customer', $search)->or_like('pj.lokasi', $search)->group_end();
            }
        };

        $apply();
        $filtered = $this->db->count_all_results();

        $cols = [1 => 'pj.nomor', 2 => 'pj.jenis', 3 => 'pp.judul', 4 => 'pj.tgl_mulai', 5 => 'pj.target_peserta', 6 => 'pj.harga_jual_peserta', 7 => 'pj.status'];
        $ord  = $p['order'][0] ?? [];
        $idx  = (int)($ord['column'] ?? -1);
        $col  = $cols[$idx] ?? 'pj.id';
        $dir  = strtolower($ord['dir'] ?? '') === 'desc' ? 'DESC' : (isset($cols[$idx]) ? 'ASC' : 'DESC');

        $apply();
        $this->db->select('pj.*, mp.nomor AS nomor_mp, pp.judul AS nama_paket, c.name_customer AS nama_company, '
            . '(SELECT t.nama FROM ms_trainer t WHERE t.id = pj.id_trainer) AS nama_trainer, '
            . "(SELECT COUNT(*) FROM quotation_peserta qp JOIN quotation q ON q.id = qp.id_quotation WHERE q.id_penjualan = pj.id AND q.status = 'deal' AND qp.status = 'deal') AS n_deal, "
            . "(SELECT COUNT(*) FROM quotation q2 WHERE q2.id_penjualan = pj.id AND q2.status <> 'batal') AS n_qtn_aktif", false)
            ->order_by($col, $dir);
        $len = (int)($p['length'] ?? 10);
        if ($len > 0) $this->db->limit(min($len, 1000), max(0, (int)($p['start'] ?? 0)));
        $rows = $this->db->get()->result_array();

        return [$total, $filtered, $this->md_model->with_audit($rows, 'penjualan_program', 'nomor')];
    }

    public function get_program_by_id($id)
    {
        $this->db->select('pj.*, mp.nomor as nomor_mp, mp.durasi_hari as mp_durasi, mp.target_peserta as mp_target, mp.harga_jual_peserta as mp_harga,
                           pp.nomor as nomor_paket, pp.judul as nama_paket,
                           jp.nama as nama_produk, js.nama as nama_sertifikasi,
                           c.name_customer as nama_company, c.jenis_instansi,
                           t.nama as nama_trainer');
        $this->db->from('penjualan_program pj');
        $this->db->join('master_program mp', 'pj.id_master_program = mp.id', 'left');
        $this->db->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false);
        $this->db->join('ms_jenis_produk jp', 'pp.id_jenis_produk = jp.id', 'left');
        $this->db->join('ms_jenis_sertifikasi js', 'pp.id_jenis_sertifikasi = js.id', 'left');
        $this->db->join('master_customer c', 'pj.id_customer = c.id_customer', 'left');
        $this->db->join('ms_trainer t', 'pj.id_trainer = t.id', 'left');
        $this->db->where('pj.id', $id);
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

    public function biaya_rows($idPj)
    {
        return $this->db->select('b.*, s.nama as nama_satuan')
                        ->from('penjualan_program_biaya b')
                        ->join('ms_satuan s', 'b.id_satuan = s.id', 'left')
                        ->where('b.id_penjualan', $idPj)
                        ->order_by('b.tipe', 'ASC')
                        ->order_by('b.id', 'ASC')
                        ->get()->result_array();
    }

    public function generate_nomor($jenis)
    {
        $yymm = date('ym');
        $prefix = ($jenis === 'b2b') ? 'PJB-' . $yymm : 'PJP-' . $yymm;

        $this->db->select('nomor');
        $this->db->like('nomor', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('penjualan_program')->row_array();

        $next = 1;
        if ($row && preg_match('/PJ[PB]-\d{4}-(\d+)/', $row['nomor'], $m)) {
            $next = (int)$m[1] + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
    }

    /** Master Program Final untuk pilihan (PJ-FR-02), dengan HPP total untuk hint. */
    public function get_final_master_programs()
    {
        $hpp = "(SELECT COALESCE(SUM(CASE WHEN b.tipe = 'fixed' THEN b.harga * IF(s.nama = 'Day', mp.durasi_hari, 1)
                                          ELSE b.harga * IF(s.nama = 'Peserta/Day', mp.durasi_hari, 1) * mp.target_peserta END), 0)
                   FROM master_program_biaya b LEFT JOIN ms_satuan s ON s.id = b.id_satuan WHERE b.id_master_program = mp.id) AS hpp";
        return $this->db->select("mp.*, pp.nomor as nomor_paket, pp.judul as nama_paket, js.nama as nama_sertifikasi, $hpp", false)
                        ->from('master_program mp')
                        ->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false)
                        ->join('ms_jenis_sertifikasi js', 'pp.id_jenis_sertifikasi = js.id', 'left')
                        ->where('mp.status', 'final')
                        ->order_by('mp.id', 'DESC')
                        ->get()->result_array();
    }

    public function copy_costing_from_mp($mpId)
    {
        $mp = $this->db->get_where('master_program', ['id' => $mpId, 'status' => 'final'])->row_array();
        if (!$mp) return null;

        $biayas = $this->db->select('b.*, s.nama as nama_satuan')
                           ->from('master_program_biaya b')
                           ->join('ms_satuan s', 'b.id_satuan = s.id', 'left')
                           ->where('b.id_master_program', $mpId)
                           ->get()->result_array();

        return [
            'header' => $mp,
            'biaya'  => $biayas
        ];
    }

    public function calculate_costing($durasi, $targetPeserta, $hargaJual, array $biayas)
    {
        return $this->mp_model->calculate_costing($durasi, $targetPeserta, $hargaJual, $biayas); // satu fungsi hitung untuk MP & PJ (PJ-NFR-05)
    }

    /** Peringatan Finalkan (PJ-FR-15). */
    public function final_warnings($calc, $target)
    {
        $w = [];
        if ($calc['profit'] < 0) $w[] = 'Rugi — pendapatan belum menutup HPP.';
        if ($calc['bep_peserta'] === null) $w[] = 'Titik impas tidak tercapai.';
        elseif ($calc['bep_peserta'] > (int)$target) $w[] = 'Titik impas ' . $calc['bep_peserta'] . ' peserta, di atas ' . (int)$target . ' peserta.';
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
            'jenis'       => $p['jenis'],
            'company'     => $p['nama_company'],
            'status'      => $p['status'],
            'durasi'      => (int)$p['durasi_hari'],
            'target'      => (int)$p['target_peserta'],
            'harga'       => (float)$p['harga_jual_peserta'],
            'pendapatan'  => $c['pendapatan'],
            'hpp'         => $c['total_hpp'],
            'profit'      => $c['profit'],
            'bep_peserta' => $c['bep_peserta'],
            'warnings'    => $this->final_warnings($c, $p['target_peserta']),
        ];
    }

    /** Hari inklusif antara dua tanggal Y-m-d (PJ-FR-06); null bila tidak valid / selesai < mulai. */
    private function hari($mulai, $selesai)
    {
        $a = DateTime::createFromFormat('Y-m-d', (string)$mulai);
        $b = DateTime::createFromFormat('Y-m-d', (string)$selesai);
        if (!$a || !$b || $a->format('Y-m-d') !== $mulai || $b->format('Y-m-d') !== $selesai || $b < $a) return null;
        return (int)$a->diff($b)->days + 1;
    }

    private function company_aktif($row)
    {
        return !in_array($row['activation'] ?? null, ['nonaktif', 'inactive'], true);
    }

    /**
     * Validasi isian Penjualan Program (PJ-NFR-06). Kembalian: [header, biaya bersih, errors, jumlah item 0 dibuang].
     * $old: record lama (jenis dan Master Program terkunci) atau null untuk penjualan baru.
     */
    private function validate_penjualan($post, $old)
    {
        $e = [];
        $mp = $this->mp_model;

        // Master Program Final + jenis (terkunci setelah disimpan, PJ-FR-03)
        $idMp = $old ? (int)$old['id_master_program'] : (int)($post['id_master_program'] ?? 0);
        $mpRow = $idMp ? $this->db->get_where('master_program', ['id' => $idMp])->row_array() : null;
        if (!$mpRow) $e['id_master_program'] = 'Master Program wajib dipilih';
        elseif (!$old && $mpRow['status'] !== 'final') $e['id_master_program'] = 'Hanya Master Program berstatus Final yang dapat dipilih';
        $jenis = $old ? $old['jenis'] : ($post['jenis'] ?? '');
        if (!in_array($jenis, ['public', 'b2b'], true)) $e['jenis'] = 'Jenis penjualan wajib dipilih';

        // Company (B2B wajib, hanya yang aktif atau yang sudah terpilih)
        $idCustomer = null;
        if ($jenis === 'b2b') {
            $idCustomer = trim((string)($post['id_customer'] ?? ''));
            $co = $idCustomer !== '' ? $this->db->get_where('master_customer', ['id_customer' => $idCustomer, 'deleted_date' => null])->row_array() : null;
            if (!$co) $e['id_customer'] = 'Pilih Company';
            elseif (!$this->company_aktif($co) && (!$old || $old['id_customer'] !== $idCustomer)) $e['id_customer'] = 'Company harus berstatus Aktif';
        }

        // Trainer wajib (PJ-FR-23 to be, untuk Surat Kerja Trainer); aktif atau yang sudah terpilih
        $idTrainer = !empty($post['id_trainer']) ? (int)$post['id_trainer'] : null;
        if (!$idTrainer) {
            $e['id_trainer'] = 'Pilih trainer';
        } else {
            $tr = $this->db->get_where('ms_trainer', ['id' => $idTrainer])->row_array();
            if (!$tr) $e['id_trainer'] = 'Trainer tidak ditemukan';
            elseif (!$tr['aktif'] && (!$old || (int)$old['id_trainer'] !== $idTrainer)) $e['id_trainer'] = 'Trainer harus berstatus Aktif';
        }

        // Jam Pembelajaran (PJ-FR-29): bilangan bulat >= 1, wajib (Q-PJ-25: pecahan belum diputuskan, usulan bulat)
        $jp = $mp->to_int($post['jam_pembelajaran'] ?? '');
        if (!$jp) $e['jam_pembelajaran'] = 'Jam Pembelajaran bilangan bulat > 0';

        // Tanggal wajib; durasi dihitung dari tanggal (PJ-FR-06)
        $mulai = trim((string)($post['tgl_mulai'] ?? ''));
        $selesai = trim((string)($post['tgl_selesai'] ?? ''));
        $durasi = null;
        if ($mulai === '') $e['tgl_mulai'] = 'Tanggal mulai wajib diisi';
        if ($selesai === '') $e['tgl_selesai'] = 'Tanggal selesai wajib diisi';
        if ($mulai !== '' && $selesai !== '') {
            $durasi = $this->hari($mulai, $selesai);
            if ($durasi === null) $e['tgl_selesai'] = 'Tanggal tidak valid atau tanggal selesai sebelum tanggal mulai';
        }

        $target = $mp->to_int($post['target_peserta'] ?? '');
        if (!$target) $e['target_peserta'] = 'Target peserta bilangan bulat > 0';
        elseif ($jenis === 'b2b' && $target < self::B2B_MIN) $e['target_peserta'] = 'Penjualan B2B minimal ' . self::B2B_MIN . ' peserta';
        $harga = $mp->to_int($post['harga_jual_peserta'] ?? '');
        if ($harga === null) $e['harga_jual_peserta'] = 'Harga jual / peserta wajib diisi';
        $tp = trim((string)($post['target_profit'] ?? '')) === '' ? 0 : $mp->to_int($post['target_profit']);
        if ($tp === null) $e['target_profit'] = 'Target profit berupa angka rupiah ≥ 0';
        $waktu = trim((string)($post['waktu'] ?? ''));
        if (mb_strlen($waktu) > 50) $e['waktu'] = 'Waktu maksimal 50 karakter';
        $lokasi = trim((string)($post['lokasi'] ?? ''));
        if (mb_strlen($lokasi) > 150) $e['lokasi'] = 'Lokasi maksimal 150 karakter';

        $oldRates = $old ? array_column($this->biaya_rows($old['id']), 'id_rate') : [];
        list($biaya, $eb, $dropped) = $mp->validate_biaya((array)($post['biaya'] ?? []), $oldRates);
        $e += $eb;

        $header = [
            'id_master_program'  => $idMp,
            'jenis'              => $jenis,
            'id_customer'        => $idCustomer ?: null,
            'id_trainer'         => $idTrainer,
            'tgl_mulai'          => $mulai ?: null,
            'tgl_selesai'        => $selesai ?: null,
            'waktu'              => $waktu,
            'lokasi'             => $lokasi,
            'durasi_hari'        => (int)($durasi ?: 1),
            'jam_pembelajaran'   => $jp ? (int)$jp : null,
            'target_peserta'     => (int)$target,
            'harga_jual_peserta' => (float)$harga,
            'target_profit'      => (float)$tp,
        ];
        return [$header, $biaya, $e, $dropped];
    }

    /** Simpan Draft: hanya penjualan berstatus Draft yang dapat dibuat/diubah (PJ-NFR-03). Final/Batal lewat set_status(). */
    public function save_penjualan($post, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;

        $old = $id ? $this->db->get_where('penjualan_program', ['id' => $id])->row_array() : null;
        if ($id && !$old) return ['status' => 0, 'msg' => 'Penjualan Program tidak ditemukan'];

        list($header, $biaya, $errors, $dropped) = $this->validate_penjualan($post, $old);
        if ($errors) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $errors];

        $this->db->trans_start();

        $oldBiaya = [];
        if ($id) {
            // Status dibaca ulang di dalam transaksi dengan kunci baris: Final/Batal tidak dapat ditimpa.
            $locked = $this->db->query('SELECT status FROM penjualan_program WHERE id = ? FOR UPDATE', [$id])->row_array();
            if (!$locked || $locked['status'] !== 'draft') {
                $this->db->trans_complete();
                return ['status' => 0, 'msg' => 'Hanya Penjualan Program berstatus Draft yang dapat diubah'];
            }
            $oldBiaya = $this->biaya_rows($id);
            $header['updated_by']   = $userId;
            $header['updated_date'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('penjualan_program', $header);
            $pjId = $id;
            $nomor = $old['nomor'];
        } else {
            $nomor = $this->generate_nomor($header['jenis']);
            $header['nomor']        = $nomor;
            $header['status']       = 'draft';
            $header['created_by']   = $userId;
            $header['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('penjualan_program', $header);
            $pjId = $this->db->insert_id();
        }

        $this->db->delete('penjualan_program_biaya', ['id_penjualan' => $pjId]);
        foreach ($biaya as $b) {
            $this->db->insert('penjualan_program_biaya', ['id_penjualan' => $pjId] + $b);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['status' => 0, 'msg' => 'Gagal menyimpan Penjualan Program'];
        }

        if ($old) {
            write_log('Penjualan Program', 'Edit', 'Update Penjualan Program ' . $nomor, [
                'old'            => $old,
                'new'            => array_merge($old, $header),
                'detail_changes' => $this->mp_model->biaya_changes($oldBiaya, $biaya)
            ], 'penjualan_program', $pjId, $nomor);
        } else {
            write_log('Penjualan Program', 'Tambah', 'Tambah Penjualan Program ' . $nomor, $header + ['jumlah_biaya' => count($biaya)], 'penjualan_program', $pjId, $nomor);
        }

        return ['status' => 1, 'msg' => 'Penjualan Program ' . $nomor . ' tersimpan sebagai Draft' . ($dropped ? ' · ' . $dropped . ' item bernilai 0 dibuang' : ''),
                'id' => $pjId, 'nomor' => $nomor, 'durasi' => $header['durasi_hari']];
    }

    /** Transisi status: hanya Draft → Final atau Draft → Batal; Final tidak dapat dibatalkan (PJ-FR-17). Diperiksa ulang di dalam transaksi. */
    public function set_status($id, $newStatus)
    {
        if (!in_array($newStatus, ['final', 'batal'], true)) return ['status' => 0, 'msg' => 'Status tidak valid'];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $old = $this->db->query('SELECT * FROM penjualan_program WHERE id = ? FOR UPDATE', [$id])->row_array();
        if (!$old) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Penjualan Program tidak ditemukan']; }

        if ($old['status'] !== 'draft') {
            $this->db->trans_complete();
            $msg = $old['status'] === 'final'
                ? 'Penjualan Program ' . $old['nomor'] . ' sudah Final — tidak dapat difinalkan ulang, dibatalkan, atau diubah'
                : 'Penjualan Program ' . $old['nomor'] . ' sudah dibatalkan';
            return ['status' => 0, 'msg' => $msg];
        }

        $update = ['status' => $newStatus, 'updated_by' => $userId, 'updated_date' => $now];
        if ($newStatus === 'final') {
            $update['confirmed_by']   = $userId;
            $update['confirmed_date'] = $now;
        } else {
            $update['closed_by']   = $userId;
            $update['closed_date'] = $now;
        }
        $this->db->where('id', $id)->update('penjualan_program', $update);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal mengubah status Penjualan Program'];

        $action = ($newStatus === 'final') ? 'Finalkan' : 'Batalkan';
        write_log('Penjualan Program', $action, $action . ' Penjualan Program ' . $old['nomor'], [
            'old' => $old,
            'new' => array_merge($old, $update)
        ], 'penjualan_program', $id, $old['nomor']);

        return ['status' => 1, 'msg' => 'Penjualan Program ' . $old['nomor'] . ($newStatus === 'final' ? ' Final — siap Quotation' : ' dibatalkan')];
    }

    // =========================================================================
    // RESCHEDULE (PJ-FR-26 - PJ-FR-28, PJ-NFR-10)
    // =========================================================================

    const EVID_MAX = 5242880; // 5 MB per file (usulan, Q-PJ-26)
    /** Tipe evidence yang diterima: ekstensi => daftar MIME hasil deteksi isi berkas (bukan kiriman browser). */
    const EVID_TIPE = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    /** Boleh reschedule: Final, belum Closed, tanggal mulai ada, hari ini masih sebelum tanggal mulai (PJ-FR-26). Kembalian: null bila boleh, selain itu alasan. */
    public function reschedule_block(array $pj)
    {
        if ($pj['status'] !== 'final') return 'Reschedule hanya untuk Penjualan Program Final';
        if (!empty($pj['closed_date'])) return 'Penjualan Program sudah Closed, jadwal tidak dapat diubah';
        if (empty($pj['tgl_mulai'])) return 'Penjualan Program belum punya tanggal mulai';
        if (date('Y-m-d') >= $pj['tgl_mulai']) return 'Acara sudah berjalan — reschedule hanya boleh sebelum tanggal mulai';
        return null;
    }

    /** Riwayat reschedule satu penjualan beserta evidence, terbaru di atas (PJ-FR-28). */
    public function reschedule_history($idPj)
    {
        $rows = $this->db->select('r.*, u.nm_lengkap AS oleh, tl.nama AS trainer_lama, tb.nama AS trainer_baru')
            ->from('penjualan_program_reschedule r')
            ->join('users u', 'u.id_user = r.created_by', 'left')
            ->join('ms_trainer tl', 'tl.id = r.id_trainer_lama', 'left')
            ->join('ms_trainer tb', 'tb.id = r.id_trainer_baru', 'left')
            ->where('r.id_penjualan', (int)$idPj)->order_by('r.id', 'DESC')->get()->result_array();
        foreach ($rows as &$r) {
            $r['evidence'] = $this->db->select('id, nama_file, tipe_file, ukuran_byte')->order_by('id')->get_where('penjualan_program_reschedule_evidence', ['id_reschedule' => $r['id']])->result_array();
        }
        return $rows;
    }

    /** Info untuk modal Reschedule: jadwal sekarang, jumlah reschedule, Sales Order yang ikut berubah. */
    public function reschedule_info($id)
    {
        $pj = $this->db->get_where('penjualan_program', ['id' => (int)$id])->row_array();
        if (!$pj) return null;
        $so = $this->db->table_exists('sales_order') ? $this->db->select('nomor, revisi')->get_where('sales_order', ['id_penjualan' => $pj['id']])->result_array() : [];
        $qtn = $this->db->where(['id_penjualan' => $pj['id']])->where('status <>', 'batal')->count_all_results('quotation');
        $tr = $pj['id_trainer'] ? $this->db->get_where('ms_trainer', ['id' => (int)$pj['id_trainer']])->row_array() : null;
        return ['pj' => $pj, 'block' => $this->reschedule_block($pj), 'n_resched' => (int)$this->db->where('id_penjualan', $pj['id'])->count_all_results('penjualan_program_reschedule'),
                'so' => $so, 'n_qtn' => $qtn, 'trainer' => $tr['nama'] ?? null];
    }

    /**
     * Simpan reschedule. $files = daftar berkas hasil unggah [['orig','path','tipe','size'], ...] yang sudah divalidasi controller.
     * Satu transaksi (PJ-NFR-10): jadwal penjualan, satu baris riwayat, baris evidence, revisi Sales Order bila tanggal berubah.
     * Status Final, belum Closed, dan hari ini < tgl_mulai lama diperiksa ulang di dalam transaksi dengan kunci baris.
     */
    public function reschedule($id, $post, array $files)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $mulai = trim((string)($post['tgl_mulai'] ?? ''));
        $alasan = trim((string)($post['alasan'] ?? ''));
        $idTrainer = (int)($post['id_trainer'] ?? 0);

        $e = [];
        $dt = DateTime::createFromFormat('Y-m-d', $mulai);
        if ($mulai === '') $e['tgl_mulai'] = 'Isi tanggal mulai baru';
        elseif (!$dt || $dt->format('Y-m-d') !== $mulai) $e['tgl_mulai'] = 'Tanggal tidak valid';
        elseif ($mulai < date('Y-m-d')) $e['tgl_mulai'] = 'Tanggal mulai tidak boleh sebelum hari ini';
        if ($alasan === '') $e['alasan'] = 'Alasan reschedule wajib diisi';
        elseif (mb_strlen($alasan) > 500) $e['alasan'] = 'Alasan maksimal 500 karakter';
        if (!$files) $e['evidence'] = 'Unggah minimal satu file evidence';
        $tr = $idTrainer ? $this->db->get_where('ms_trainer', ['id' => $idTrainer])->row_array() : null;
        if (!$tr) $e['id_trainer'] = 'Pilih trainer';
        if ($e) return ['status' => 0, 'msg' => 'Belum tersimpan — perbaiki isian yang ditandai', 'errors' => $e];

        $this->db->trans_start();
        $old = $this->db->query('SELECT * FROM penjualan_program WHERE id = ? FOR UPDATE', [(int)$id])->row_array();
        if (!$old) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Penjualan Program tidak ditemukan']; }
        if ($why = $this->reschedule_block($old)) { $this->db->trans_complete(); return ['status' => 0, 'msg' => $why, 'code' => 422]; }
        if (!$tr['aktif'] && (int)$old['id_trainer'] !== $idTrainer) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Trainer harus berstatus Aktif', 'errors' => ['id_trainer' => 'Trainer harus berstatus Aktif']]; }

        // Durasi tetap: selesai = mulai baru + durasi - 1, supaya costing Final tidak berubah (PJ-FR-26).
        $durasi = max(1, (int)$old['durasi_hari']);
        $selesai = (new DateTime($mulai))->modify('+' . ($durasi - 1) . ' day')->format('Y-m-d');
        $tglBerubah = $mulai !== $old['tgl_mulai'];
        $trainerBerubah = (int)$old['id_trainer'] !== $idTrainer;
        if (!$tglBerubah && !$trainerBerubah) {
            $this->db->trans_complete();
            return ['status' => 0, 'msg' => 'Jadwal belum berubah: ganti tanggal atau trainer', 'errors' => ['tgl_mulai' => 'Jadwal belum berubah: ganti tanggal atau trainer']];
        }

        $now = date('Y-m-d H:i:s');
        $upd = ['tgl_mulai' => $mulai, 'tgl_selesai' => $selesai, 'id_trainer' => $idTrainer, 'updated_by' => $userId, 'updated_date' => $now];
        $this->db->where('id', $old['id'])->update('penjualan_program', $upd);

        // ponytail: penjualan lama bisa belum punya trainer (PJ-FR-23 baru wajib di to be) sedangkan id_trainer_lama NOT NULL; dicatat sama dengan trainer baru.
        $this->db->insert('penjualan_program_reschedule', [
            'id_penjualan' => $old['id'], 'tgl_mulai_lama' => $old['tgl_mulai'], 'tgl_selesai_lama' => $old['tgl_selesai'] ?: $old['tgl_mulai'],
            'tgl_mulai_baru' => $mulai, 'tgl_selesai_baru' => $selesai,
            'id_trainer_lama' => $old['id_trainer'] ? (int)$old['id_trainer'] : $idTrainer, 'id_trainer_baru' => $idTrainer,
            'alasan' => $alasan, 'created_by' => $userId, 'created_date' => $now,
        ]);
        $rid = $this->db->insert_id();
        foreach ($files as $f) {
            $this->db->insert('penjualan_program_reschedule_evidence', ['id_reschedule' => $rid, 'nama_file' => mb_substr($f['orig'], 0, 255), 'path_file' => $f['path'], 'tipe_file' => $f['tipe'], 'ukuran_byte' => (int)$f['size']]);
        }

        // SO-FR-16: tanggal berubah = semua Sales Order program itu naik satu revisi; ganti trainer saja tidak.
        $naik = [];
        if ($tglBerubah && $this->db->table_exists('sales_order')) {
            $naik = array_column($this->db->select('nomor')->get_where('sales_order', ['id_penjualan' => $old['id']])->result_array(), 'nomor');
            if ($naik) $this->db->set('revisi', 'revisi + 1', false)->where('id_penjualan', $old['id'])->update('sales_order');
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) return ['status' => 0, 'msg' => 'Gagal menyimpan reschedule, tidak ada data yang berubah'];

        $trLama = $old['id_trainer'] ? ($this->db->get_where('ms_trainer', ['id' => (int)$old['id_trainer']])->row_array()['nama'] ?? '—') : '—';
        write_log('Penjualan Program', 'Reschedule', 'Reschedule Penjualan Program ' . $old['nomor'] . ': ' . $alasan, [
            'old' => array_merge($old, ['trainer' => $trLama]),
            'new' => array_merge($old, $upd, ['trainer' => $tr['nama']]),
            'detail_changes' => array_merge(
                array_map(function ($f) { return ['old' => null, 'new' => $f['orig']]; }, array_combine(array_map(function ($f) { return 'Evidence - ' . $f['orig']; }, $files), $files)),
                array_combine(array_map(function ($n) { return 'Sales Order ' . $n . ' - Revisi'; }, $naik), array_map(function ($n) { return ['old' => null, 'new' => '+1']; }, $naik)) ?: []
            ),
        ], 'penjualan_program', $old['id'], $old['nomor']);

        return ['status' => 1, 'msg' => $old['nomor'] . ' di-reschedule ke ' . date('d-m-Y', strtotime($mulai)) . ' – ' . date('d-m-Y', strtotime($selesai)) . ($naik ? '; ' . count($naik) . ' Sales Order naik revisi' : ''),
                'revisi_so' => count($naik)];
    }
}
