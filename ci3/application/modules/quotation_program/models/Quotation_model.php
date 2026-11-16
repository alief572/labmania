<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Quotation_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    public function get_quotations($tab = 'ongoing', $idPj = null, $status = null, $keyword = null)
    {
        $this->db->select('q.*, 
                           pj.nomor as nomor_pj, pj.jenis as jenis_pj, pj.tgl_mulai, pj.tgl_selesai, pj.lokasi, pj.durasi_hari, pj.target_peserta, pj.harga_jual_peserta,
                           mp.nomor as nomor_mp, pp.judul as nama_paket,
                           c.name_customer as nama_company, c.jenis_instansi,
                           u.nm_lengkap as nama_sales');
        $this->db->from('quotation q');
        $this->db->join('penjualan_program pj', 'q.id_penjualan = pj.id', 'left');
        $this->db->join('master_program mp', 'pj.id_master_program = mp.id', 'left');
        $this->db->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false);
        $this->db->join('master_customer c', 'q.id_customer = c.id_customer', 'left');
        $this->db->join('users u', 'q.id_sales = u.id_user', 'left');

        if ($idPj) {
            $this->db->where('q.id_penjualan', $idPj);
        }

        if ($status && $status !== 'all') {
            $this->db->where('q.status', $status);
        } else if ($tab === 'ongoing') {
            $this->db->where_in('q.status', ['draft', 'terkirim']);
        } else if ($tab === 'done') {
            $this->db->where_in('q.status', ['deal', 'batal']);
        }

        if ($keyword) {
            $this->db->group_start();
            $this->db->like('q.nomor', $keyword);
            $this->db->or_like('pp.judul', $keyword);
            $this->db->or_like('c.name_customer', $keyword);
            $this->db->or_like('q.up_nama', $keyword);
            $this->db->or_like('pj.nomor', $keyword);
            $this->db->group_end();
        }

        $list = $this->db->order_by('q.id', 'DESC')->get()->result_array();

        foreach ($list as &$row) {
            // Ambil daftar peserta
            $row['peserta'] = $this->peserta_rows($row['id']);

            // Hitung nilai total
            $row['calc'] = $this->calculate_quotation_value($row, $this->n_nilai($row, $row['peserta']));
        }

        return $list;
    }

    public function get_quotation_by_id($id)
    {
        $this->db->select('q.*, 
                           pj.nomor as nomor_pj, pj.jenis as jenis_pj, pj.tgl_mulai, pj.tgl_selesai, pj.waktu, pj.lokasi, pj.durasi_hari, pj.jam_pembelajaran, pj.target_peserta, pj.harga_jual_peserta,
                           mp.nomor as nomor_mp, pp.judul as nama_paket, pp.id_jenis_produk,
                           c.name_customer as nama_company, c.jenis_instansi, c.address_office as alamat_company, c.telephone as telp_company,
                           u.nm_lengkap as nama_sales, u.email as email_sales, u.hp as hp_sales,
                           tk.nama as nama_template_konfirmasi, tk.label_kegiatan');
        $this->db->from('quotation q');
        $this->db->join('penjualan_program pj', 'q.id_penjualan = pj.id', 'left');
        $this->db->join('master_program mp', 'pj.id_master_program = mp.id', 'left');
        $this->db->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false);
        $this->db->join('master_customer c', 'q.id_customer = c.id_customer', 'left');
        $this->db->join('users u', 'q.id_sales = u.id_user', 'left');
        $this->db->join('ms_template_konfirmasi tk', 'q.id_template_konfirmasi = tk.id', 'left');
        $this->db->where('q.id', $id);
        $q = $this->db->get()->row_array();

        if ($q) {
            // Ambil daftar peserta
            $q['peserta'] = $this->peserta_rows($id);
            $q['konf_company'] = $this->db->get_where('quotation_konfirmasi_company', ['id_quotation' => (int)$id])->row_array() ?: null;
            $q['dokumen'] = $this->db->order_by('id')->get_where('quotation_dokumen', ['id_quotation' => (int)$id])->result_array();

            // Ambil keterangan S&K
            $q['keterangan'] = $this->db->get_where('quotation_keterangan', ['id_quotation' => $id])
                                        ->result_array();

            // Kalkulasi nilai
            $q['calc'] = $this->calculate_quotation_value($q, $this->n_nilai($q, $q['peserta']));
        }

        return $q;
    }

    /** Jumlah peserta untuk nilai: Kuota peserta sampai Deal, jumlah peserta Deal setelah Deal (QTN-FR-08, QTN-FR-20; usulan Q-TB-01). */
    private function n_nilai(array $q, array $peserta)
    {
        if ($q['status'] !== 'deal') return (int)$q['kuota_peserta'];
        return count(array_filter($peserta, function ($p) { return $p['status'] === 'deal'; }));
    }

    public function calculate_quotation_value($q, $nPeserta)
    {
        $nPeserta = (int)$nPeserta ?: 0;
        $skema = $q['skema_harga'] ?? 'peserta';
        $hargaPeserta = (float)($q['harga_peserta'] ?? 0);
        $hargaAllin = (float)($q['harga_allin'] ?? 0);
        $diskon = (float)($q['diskon'] ?? 0);
        $biayaLain = (float)($q['biaya_lain'] ?? 0);
        $isPpn = !empty($q['is_ppn']);
        $ppnPersen = (float)($q['ppn_persen'] ?? 11);

        $tambahan = 0; $nTambahan = 0;
        $maks = (int)($q['paket_maks_peserta'] ?? 0);
        $tarif = (float)($q['tambahan_per_peserta'] ?? 0);
        if ($skema === 'allin') {
            // B2B: harga paket s.d. $maks peserta; peserta ke-($maks+1) dst + tarif/orang, ikut DPP dan PPN. Diskon hanya dari harga paket (QTN-FR-20).
            $nTambahan = $maks > 0 ? max($nPeserta - $maks, 0) : 0;
            $tambahan = $nTambahan * $tarif;
            $subtotal = $hargaAllin + $tambahan;
            $totalDiskon = $diskon;
            $dpp = max(0, $subtotal - $totalDiskon);
            $netPerPax = $nPeserta > 0 ? ($dpp / $nPeserta) : 0;
        } else {
            $subtotal = $hargaPeserta * $nPeserta;
            $totalDiskon = $diskon * $nPeserta;
            $dpp = max(0, $subtotal - $totalDiskon);
            $netPerPax = max(0, $hargaPeserta - $diskon);
        }

        $ppnNominal = $isPpn ? ($dpp * ($ppnPersen / 100)) : 0;
        $grandTotal = $dpp + $ppnNominal + $biayaLain;

        return [
            'skema'         => $skema,
            'n_peserta'     => $nPeserta,
            'harga_peserta' => $hargaPeserta,
            'harga_allin'   => $hargaAllin,
            'tambahan'      => $tambahan,
            'n_tambahan'    => $nTambahan,
            'paket_maks'    => $maks,
            'tarif_tambahan' => $tarif,
            'subtotal'      => $subtotal,
            'diskon'        => $diskon,
            'total_diskon'  => $totalDiskon,
            'dpp'           => $dpp,
            'net_per_pax'   => $netPerPax,
            'is_ppn'        => $isPpn,
            'ppn_persen'    => $ppnPersen,
            'ppn_nominal'   => $ppnNominal,
            'biaya_lain'    => $biayaLain,
            'grand_total'   => $grandTotal
        ];
    }

    /**
     * Nomor LMI/PW/MM/yy-zzz (QTN-FR-04): MM/yy dari TANGGAL quotation, urutan zzz di-reset tiap bulan (usulan, Q-QTN-01).
     * Nomor lama format LM-I/AM/PW/... tidak diubah dan tidak ikut hitungan urutan. $lock=true hanya di dalam transaksi.
     */
    public function generate_nomor($lock = false, $tanggal = null)
    {
        $t = $tanggal ? strtotime($tanggal) : time();
        $prefix = sprintf('LMI/PW/%s/%s-', date('m', $t), date('y', $t));
        $row = $this->db->query("SELECT nomor FROM quotation WHERE nomor LIKE ? ORDER BY id DESC LIMIT 1" . ($lock ? ' FOR UPDATE' : ''), [$prefix . '%'])->row_array();
        $next = ($row && preg_match('/-(\d+)$/', $row['nomor'], $m)) ? (int)$m[1] + 1 : 1;
        return sprintf('%s%03d', $prefix, $next);
    }

    /** Token link Konfirmasi Kehadiran: 128 bit acak, bukan ID atau nomor quotation (QTN-NFR-08). 32 hex <= VARCHAR(40). */
    public function new_token()
    {
        do { $tok = bin2hex(random_bytes(16)); } while ($this->db->where('link_token', $tok)->count_all_results('quotation') > 0);
        return $tok;
    }

    /** Baris peserta quotation (isian link) beserta nama berkas surat; urut waktu isi. Bentuk kompatibel dengan view lama (nama_peserta, no_telp). */
    public function peserta_rows($idQuotation)
    {
        $rows = $this->db->order_by('id')->get_where('quotation_peserta', ['id_quotation' => (int)$idQuotation])->result_array();
        foreach ($rows as &$r) {
            $r['nama_peserta'] = $r['nama'];
            $r['kode_peserta'] = '';
            $r['no_telp'] = $r['no_wa'] ?: $r['no_hp'];
            $r['alamat_peserta'] = $r['alamat_instansi'];
        }
        return $rows;
    }

    public function get_final_penjualans()
    {
        return $this->db->select('pj.*, mp.nomor as nomor_mp, pp.judul as nama_paket, pp.id_jenis_produk, c.name_customer as nama_company')
                        ->from('penjualan_program pj')
                        ->join('master_program mp', 'pj.id_master_program = mp.id', 'left')
                        ->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false)
                        ->join('master_customer c', 'pj.id_customer = c.id_customer', 'left')
                        ->where('pj.status', 'final')
                        ->order_by('pj.id', 'DESC')
                        ->get()->result_array();
    }

    /** Semua butir Template Keterangan aktif; form menyaring menurut jenis penjualan. */
    public function get_master_keterangan()
    {
        return $this->db->where('aktif', 1)->order_by('id', 'ASC')->get('ms_template_keterangan')->result_array();
    }

    public function get_default_keterangan($jenisPj = 'public')
    {
        $this->db->from('ms_template_keterangan');
        $this->db->where('aktif', 1);
        $this->db->where_in('berlaku_untuk', ['semua', $jenisPj]);
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_default_konfirmasi($idJenisProduk = 1)
    {
        $this->db->from('ms_template_konfirmasi');
        $this->db->where('aktif', 1);
        if ($idJenisProduk) {
            $this->db->where('id_jenis_produk', $idJenisProduk);
        }
        $row = $this->db->get()->row_array();
        if (!$row) {
            $row = $this->db->get_where('ms_template_konfirmasi', ['aktif' => 1])->row_array();
        }
        return $row;
    }

    const B2B_MIN = 10;
    /** Jumlah kuota quotation lain (selain Batal) untuk Penjualan Program yang sama; $kecualiId = quotation yang sedang diedit (QTN-FR-22). */
    public function kuota_lain($idPj, $kecualiId = null)
    {
        $this->db->select_sum('kuota_peserta', 'n')->from('quotation')->where('id_penjualan', (int)$idPj)->where('status !=', 'batal');
        if ($kecualiId) $this->db->where('id !=', (int)$kecualiId);
        return (int)($this->db->get()->row()->n ?? 0);
    }

    /** Peserta yang sudah mengisi (bukan Batal) untuk satu quotation; batas bawah kuota saat edit (QTN-FR-22). */
    public function n_terisi($idQuotation)
    {
        return (int)$this->db->where('id_quotation', (int)$idQuotation)->where('status !=', 'batal')->count_all_results('quotation_peserta');
    }

    const B2B_PAKET_MAKS = 25;          // harga paket B2B mencakup s.d. 25 peserta (QTN-FR-20, B2P-1)
    const B2B_TAMBAHAN = 300000;        // peserta ke-26 dst + Rp300.000/orang (B2P-3); disimpan per quotation saat dibuat (Q-QTN-21)

    /** Header quotation dari isian form (dipakai simpan dan pratinjau). */
    private function parse_header($post, $pj, $userId)
    {
        $tujuan = $pj['jenis'] === 'b2b' ? 'company' : (($post['tujuan'] ?? '') === 'company' ? 'company' : 'personal');
        $idCustomer = ($tujuan === 'company') ? ($pj['jenis'] === 'b2b' ? $pj['id_customer'] : ($post['id_customer'] ?? null)) : null;

        $skema = $pj['jenis'] === 'b2b' ? 'allin' : 'peserta'; // B2B hanya all-in 1 company; Public selalu per peserta (QTN-FR-20)
        $hargaPeserta = (float)$pj['harga_jual_peserta']; // QTN-NFR-02: harga dari Penjualan Program Final, bukan dari klien
        $hargaAllin = (float)str_replace('.', '', $post['harga_allin'] ?? 0);
        $diskon = (float)str_replace('.', '', $post['diskon'] ?? 0);
        $biayaLain = (float)str_replace('.', '', $post['biaya_lain'] ?? 0);
        $isPpn = !empty($post['is_ppn']) ? 1 : 0;
        $ppnPersen = (float)($post['ppn_persen'] ?? 11);
        // Deal/Batal hanya lewat set_status (QTN-FR-11/12).
        $status = ($post['status'] ?? '') === 'terkirim' ? 'terkirim' : 'draft';

        $header = [
            'id_penjualan'           => (int)$pj['id'],
            'tanggal'                => $tgl = (!empty($post['tanggal']) ? $post['tanggal'] : date('Y-m-d')),
            // Berlaku sampai default 31 Desember tahun Tanggal quotation; boleh diubah (QTN-FR-04)
            'berlaku_sampai'         => !empty($post['berlaku_sampai']) ? $post['berlaku_sampai'] : substr($tgl, 0, 4) . '-12-31',
            'id_sales'               => !empty($post['id_sales']) ? (int)$post['id_sales'] : $userId,
            'sumber_info'            => trim($post['sumber_info'] ?? ''),
            'tujuan'                 => $tujuan,
            'id_customer'            => $idCustomer,
            'up_nama'                => trim($post['up_nama'] ?? ''),
            'kepada_nama'            => $tujuan === 'personal' ? trim($post['kepada_nama'] ?? '') : null,
            'alamat'                 => trim($post['alamat'] ?? ''),
            'kuota_peserta'          => (int)($post['kuota_peserta'] ?? 0),
            'skema_harga'            => $skema,
            'harga_peserta'          => $hargaPeserta,
            'harga_allin'            => $hargaAllin,
            'diskon'                 => $diskon,
            'is_ppn'                 => $isPpn,
            'ppn_persen'             => $ppnPersen,
            'biaya_lain'             => $biayaLain,
            'id_template_konfirmasi' => !empty($post['id_template_konfirmasi']) ? (int)$post['id_template_konfirmasi'] : null,
            'konfirmasi_isian'       => is_array($post['konfirmasi_isian'] ?? null) ? json_encode($post['konfirmasi_isian']) : ($post['konfirmasi_isian'] ?? null),
            'konfirmasi_pernyataan'  => trim($post['konfirmasi_pernyataan'] ?? ''),
            // Kolom opsional daftar peserta B2B yang diminta dari PIC (QTN-FR-31); Nama selalu ada. Hanya nilai yang dikenal.
            'kolom_peserta_b2b'      => $pj['jenis'] === 'b2b'
                ? json_encode(array_values(array_intersect((array)($post['kolom_b2b'] ?? []), ['jabatan', 'email', 'wa', 'nik'])))
                : null,
            'status'                 => $status
        ];
        return $header;
    }

    /** Data pratinjau (belum disimpan) berbentuk sama dengan get_quotation_by_id, untuk view cetak. */
    public function build_preview($post)
    {
        $pj = $this->db->select('pj.*, pp.judul as nama_paket, pp.id_jenis_produk')
            ->from('penjualan_program pj')
            ->join('master_program mp', 'pj.id_master_program = mp.id', 'left')
            ->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false)
            ->where('pj.id', (int)($post['id_penjualan'] ?? 0))->get()->row_array();
        if (!$pj) return null;

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $q = $this->parse_header($post, $pj, $userId);
        $q['nomor']       = !empty($post['nomor']) ? $post['nomor'] : $this->generate_nomor();
        $q['jenis_pj']    = $pj['jenis'];
        foreach (['tgl_mulai', 'tgl_selesai', 'waktu', 'lokasi', 'durasi_hari', 'jam_pembelajaran', 'target_peserta', 'nama_paket', 'id_jenis_produk'] as $k) $q[$k] = $pj[$k] ?? null;

        $co = $q['id_customer'] ? $this->db->get_where('master_customer', ['id_customer' => $q['id_customer']])->row_array() : null;
        $q['nama_company'] = $co['name_customer'] ?? null;
        $q['alamat_company'] = $co['address_office'] ?? null;
        $sales = $this->db->get_where('users', ['id_user' => $q['id_sales']])->row_array();
        $q['nama_sales'] = $sales['nm_lengkap'] ?? null;
        $q['email_sales'] = $sales['email'] ?? null;
        $q['hp_sales'] = $sales['hp'] ?? null;

        $q['peserta'] = []; // peserta hanya lewat link Konfirmasi Kehadiran (QTN-FR-24); pratinjau memakai Kuota
        if ($pj['jenis'] === 'b2b') { $q['paket_maks_peserta'] = self::B2B_PAKET_MAKS; $q['tambahan_per_peserta'] = self::B2B_TAMBAHAN; }

        $q['keterangan'] = [];
        foreach ((array)($post['keterangan'] ?? []) as $ket) {
            if (trim($ket['teks'] ?? '') !== '' && ($ket['kategori'] ?? '') !== 'klausul') $q['keterangan'][] = ['kategori' => $ket['kategori'], 'teks' => trim($ket['teks'])];
        }
        foreach ($this->get_default_keterangan($pj['jenis']) as $t) {
            if ($t['kategori'] === 'klausul' && $t['is_default']) $q['keterangan'][] = ['kategori' => 'klausul', 'teks' => $t['teks']];
        }
        $q['calc'] = $this->calculate_quotation_value($q, (int)$q['kuota_peserta']);
        return $q;
    }

    const SUMBER = ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya'];

    /**
     * Aturan integritas QTN-FR-03/04/05/08/20/21/22 (QTN-NFR-03). Dipanggil di dalam transaksi. Kembalian: pesan galat atau null.
     * $terisi = jumlah peserta yang sudah mengisi lewat link (batas bawah Kuota saat edit).
     */
    private function validate_quotation($pj, $header, $id, $terisi = 0)
    {
        if ($pj['status'] !== 'final') return 'Penjualan Program ' . $pj['nomor'] . ' belum Final';

        $b2b = $pj['jenis'] === 'b2b';
        if ($header['kuota_peserta'] < 1) return 'Kuota peserta minimal 1';
        if ($b2b && $header['kuota_peserta'] < self::B2B_MIN) return 'Penjualan B2B: Kuota peserta minimal ' . self::B2B_MIN;
        if ($id && $header['kuota_peserta'] < $terisi) return 'Kuota tidak boleh kurang dari ' . $terisi . ' peserta yang sudah mengisi';

        if ($header['berlaku_sampai'] < $header['tanggal']) return 'Berlaku sampai tidak boleh sebelum tanggal quotation';
        if ($header['diskon'] < 0 || $header['biaya_lain'] < 0 || $header['harga_allin'] < 0) return 'Diskon, biaya lain, dan harga all-in tidak boleh negatif';
        if ($header['ppn_persen'] < 0 || $header['ppn_persen'] > 100) return 'PPN harus 0–100%';
        if ($header['sumber_info'] !== '' && !in_array($header['sumber_info'], self::SUMBER, true)) return 'Sumber informasi tidak valid';
        if (!$this->db->query('SELECT 1 FROM users WHERE id_user = ? AND IFNULL(deleted, 0) = 0', [$header['id_sales']])->row_array()) return 'Sales tidak ditemukan';
        if ($header['id_template_konfirmasi'] && !$this->db->get_where('ms_template_konfirmasi', ['id' => $header['id_template_konfirmasi']])->row_array()) return 'Template Formulir Konfirmasi tidak ditemukan';

        if ($b2b) {
            $this->db->where('id_penjualan', $pj['id'])->where('status !=', 'batal');
            if ($id) $this->db->where('id !=', $id);
            if ($this->db->count_all_results('quotation') > 0) return 'Penjualan B2B hanya boleh punya satu quotation aktif';
        }

        if ($header['tujuan'] === 'company') {
            if (empty($header['id_customer'])) return 'Company wajib dipilih';
            $co = $this->db->where('id_customer', $header['id_customer'])->where('deleted_date IS NULL', null, false)->get('master_customer')->row_array();
            if (!$co || in_array($co['activation'], ['nonaktif', 'inactive'], true)) return 'Company tidak ditemukan atau nonaktif';
        } else {
            // Personal: Kepada wajib karena peserta belum ada saat quotation dibuat (QTN-FR-05)
            if (trim((string)$header['kepada_nama']) === '') return 'Kepada wajib diisi';
            if (mb_strlen($header['kepada_nama']) > 100) return 'Kepada maksimal 100 karakter';
        }

        if ($header['skema_harga'] === 'allin') {
            if ($header['harga_allin'] <= 0) return 'Harga all-in wajib diisi (> 0)';
            if ($header['diskon'] > $header['harga_allin']) return 'Diskon tidak boleh melebihi harga all-in';
        } elseif ($header['diskon'] > $header['harga_peserta']) {
            return 'Diskon per peserta tidak boleh melebihi harga per peserta';
        }
        return null;
    }

    public function save_quotation($post, $id = null)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now = date('Y-m-d H:i:s');
        $idPj = (int)($post['id_penjualan'] ?? 0);

        $this->db->trans_start();
        // Kunci Penjualan Program: cek B2B/peserta ganda dan nomor tidak bisa balapan (QTN-NFR-03).
        $pj = $this->db->query('SELECT * FROM penjualan_program WHERE id = ? FOR UPDATE', [$idPj])->row_array();
        $err = null; $cur = null; $header = [];
        if (!$pj) {
            $err = 'Penjualan Program tidak ditemukan';
        } else {
            if ($id) {
                $cur = $this->db->query('SELECT * FROM quotation WHERE id = ? FOR UPDATE', [$id])->row_array();
                if (!$cur) $err = 'Quotation tidak ditemukan';
                elseif ($cur['status'] !== 'draft') $err = 'Hanya quotation Draft yang dapat diubah';
                elseif ((int)$cur['id_penjualan'] !== $idPj) $err = 'Penjualan Program tidak dapat diganti pada quotation yang sudah dibuat';
            }
            $header = $this->parse_header($post, $pj, $userId);
            $header['status'] = 'draft'; // Kirim/Deal/Batal hanya lewat set_status / set_deal
            $err = $err ?: $this->validate_quotation($pj, $header, $id, $id ? $this->n_terisi($id) : 0);
        }
        if ($err) { $this->db->trans_complete(); return ['status' => 0, 'msg' => $err]; }

        // Snapshot detail lama sebelum dihapus (untuk detail_changes).
        $oldKet = [];
        if ($id) {
            $oldKet = array_column($this->db->get_where('quotation_keterangan', ['id_quotation' => $id])->result_array(), 'teks');
            $header['updated_by']   = $userId;
            $header['updated_date'] = $now;
            $this->db->where('id', $id)->update('quotation', $header);
            $qId = $id;
            $nomor = $cur['nomor'];
        } else {
            // Nomor dari tanggal quotation; link dibuat otomatis saat simpan pertama (QTN-FR-23). B2B: batas paket dan tarif tambahan dikunci per quotation (Q-QTN-21).
            $nomor = $this->generate_nomor(true, $header['tanggal']);
            $header['nomor']        = $nomor;
            $header['link_token']   = $this->new_token();
            $header['link_dibuat']  = $now;
            if ($pj['jenis'] === 'b2b') { $header['paket_maks_peserta'] = self::B2B_PAKET_MAKS; $header['tambahan_per_peserta'] = self::B2B_TAMBAHAN; }
            $header['created_by']   = $userId;
            $header['created_date'] = $now;
            $this->db->insert('quotation', $header);
            $qId = $this->db->insert_id();
        }

        // Keterangan S&K: butir dari form; klausul tanda tangan disalin dari master (QTN-FR-17).
        $this->db->delete('quotation_keterangan', ['id_quotation' => $qId]);
        $urutan = 1;
        foreach ((array)($post['keterangan'] ?? []) as $ket) {
            $teks = trim($ket['teks'] ?? '');
            if ($teks === '' || !in_array($ket['kategori'] ?? '', ['syarat', 'termasuk', 'tidak', 'dokumen'], true)) continue; // klausul dari master
            $this->db->insert('quotation_keterangan', [
                'id_quotation' => $qId,
                'id_template_keterangan' => !empty($ket['id_template_keterangan']) ? (int)$ket['id_template_keterangan'] : null,
                'kategori' => $ket['kategori'], 'teks' => $teks, 'urutan' => $urutan++
            ]);
        }
        foreach ($this->get_default_keterangan($pj['jenis']) as $t) {
            if ($t['kategori'] !== 'klausul' || !$t['is_default']) continue;
            $this->db->insert('quotation_keterangan', [
                'id_quotation' => $qId, 'id_template_keterangan' => (int)$t['id'],
                'kategori' => 'klausul', 'teks' => $t['teks'], 'urutan' => $urutan++
            ]);
        }

        $newKet = array_column($this->db->get_where('quotation_keterangan', ['id_quotation' => $qId])->result_array(), 'teks');
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['status' => 0, 'msg' => 'Gagal menyimpan Quotation'];
        }

        // Log di luar transaksi agar gagal log tidak membatalkan simpan.
        $dc = [];
        if ($id && $oldKet !== $newKet) $dc['Keterangan'] = ['old' => count($oldKet) . ' butir', 'new' => count($newKet) . ' butir'];
        if ($id) {
            write_log('Quotation', 'Edit', 'Update Quotation ' . $nomor, ['old' => $cur, 'new' => array_merge($cur, $header), 'detail_changes' => $dc], 'quotation', $qId, $nomor);
        } else {
            write_log('Quotation', 'Tambah', 'Tambah Quotation ' . $nomor, ['old' => [], 'new' => array_diff_key($header, ['link_token' => 1])], 'quotation', $qId, $nomor); // token tidak dicatat di log
        }

        return ['status' => 1, 'msg' => 'Quotation ' . $nomor . ' berhasil disimpan', 'id' => $qId, 'nomor' => $nomor];
    }

    /** Draft → Terkirim | Batal, Terkirim → Batal. Deal hanya lewat set_deal (pilih peserta, QTN-FR-11). */
    public function set_status($id, $newStatus)
    {
        if (!in_array($newStatus, ['terkirim', 'batal'], true)) return ['status' => 0, 'msg' => 'Status tidak valid — Deal melalui popup pilih peserta'];

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $old = $this->db->query('SELECT * FROM quotation WHERE id = ? FOR UPDATE', [$id])->row_array();
        $allowed = ['draft' => ['terkirim', 'batal'], 'terkirim' => ['batal']];
        if (!$old || !in_array($newStatus, $allowed[$old['status']] ?? [], true)) {
            $this->db->trans_complete();
            return ['status' => 0, 'msg' => $old ? 'Quotation ' . $old['nomor'] . ' berstatus ' . $old['status'] . ', tidak dapat diubah ke ' . $newStatus : 'Quotation tidak ditemukan'];
        }

        $update = ['status' => $newStatus, 'updated_by' => $userId, 'updated_date' => $now];
        if ($newStatus === 'terkirim') {
            $update['sent_by'] = $userId; $update['sent_date'] = $now;
        } else {
            $update['closed_by'] = $userId; $update['closed_date'] = $now;
            $this->db->where('id_quotation', $id)->update('quotation_peserta', ['status' => 'batal']);
        }
        $this->db->where('id', $id)->update('quotation', $update);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal mengubah status Quotation'];

        $action = $newStatus === 'terkirim' ? 'Kirim' : 'Batalkan';
        write_log('Quotation', $action, $action . ' Quotation ' . $old['nomor'], ['old' => $old, 'new' => array_merge($old, $update)], 'quotation', $id, $old['nomor']);

        return ['status' => 1, 'msg' => 'Quotation ' . $old['nomor'] . ($newStatus === 'terkirim' ? ' terkirim' : ' dibatalkan')];
    }

    const DOK_TIPE = ['pdf' => 'pdf', 'jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'doc' => 'doc', 'docx' => 'docx', 'xls' => 'xls', 'xlsx' => 'xlsx'];
    const DOK_MAX = 5242880; // 5 MB per file (usulan, Q-QTN-20)

    /** Hanya angka (pencocokan No. WhatsApp tidak peduli spasi/strip/+). */
    private function digits($v) { return preg_replace('/\D+/', '', (string)$v); }

    /**
     * Cocokkan satu peserta Deal ke Master Peserta (QTN-FR-33): NIK, lalu email, lalu No. WhatsApp (angka saja).
     * Kembalian: baris ms_peserta atau null. Hanya peserta yang belum dihapus.
     */
    private function cari_peserta_master(array $p)
    {
        $nik = trim((string)$p['nik']);
        if ($nik !== '') {
            $r = $this->db->where('deleted_date IS NULL', null, false)->where('nik', $nik)->order_by('id')->get('ms_peserta', 1)->row_array();
            if ($r) return $r;
        }
        $email = strtolower(trim((string)$p['email']));
        if ($email !== '') {
            $r = $this->db->where('deleted_date IS NULL', null, false)->where('LOWER(email)', $email)->order_by('id')->get('ms_peserta', 1)->row_array();
            if ($r) return $r;
        }
        $wa = $this->digits($p['no_wa'] ?: $p['no_hp']);
        if ($wa !== '') {
            $r = $this->db->where('deleted_date IS NULL', null, false)
                ->where("REGEXP_REPLACE(IFNULL(no_telp, ''), '[^0-9]', '') = " . $this->db->escape($wa), null, false)->order_by('id')->get('ms_peserta', 1)->row_array();
            if ($r) return $r;
        }
        return null;
    }

    /**
     * Simpan peserta Deal ke Master Peserta (QTN-FR-33), di dalam transaksi Deal. Cocok = hanya mengisi field KOSONG (data lama tidak ditimpa);
     * tidak cocok atau NIK/email/WA kosong semua = peserta baru dengan Kode otomatis. Isian link di quotation_peserta tidak berubah.
     * Bidang = Bidang Product program (kosong bila Product berbeda Bidang); Provinsi/Kota dari Master Company bila quotation ke Company.
     * Kembalian: ['baru' => n, 'cocok' => m, 'peta' => [id quotation_peserta => id ms_peserta]].
     */
    private function simpan_ke_master(array $q, array $pjRow, array $deal, $userId, $now)
    {
        // Bidang dari semua Product program: satu nilai bila sama, selain itu dikosongkan (usulan, QTN-FR-33)
        $bidang = array_unique(array_filter(array_column($this->db->select('p.id_jenis_bidang')->from('master_program_produk m')
            ->join('paket_produk p', 'p.id = m.id_paket_produk')->where('m.id_master_program', (int)$pjRow['id_master_program'])->get()->result_array(), 'id_jenis_bidang')));
        $idBidang = count($bidang) === 1 ? (int)reset($bidang) : null;

        $co = null;
        if ($q['tujuan'] === 'company' && !empty($q['id_customer'])) $co = $this->db->get_where('master_customer', ['id_customer' => $q['id_customer']])->row_array();

        $baru = $cocok = 0; $peta = [];
        foreach ($deal as $p) {
            $m = $this->cari_peserta_master($p);
            $isi = [
                'nama'     => trim((string)$p['nama']),
                'nik'      => trim((string)$p['nik']) ?: null,
                'no_telp'  => trim((string)($p['no_wa'] ?: $p['no_hp'])) ?: null,
                'email'    => trim((string)$p['email']) ?: null,
                'instansi' => $co ? $co['name_customer'] : (trim((string)$p['instansi']) ?: null),
                'id_customer'   => $co ? $co['id_customer'] : null,
                'npwp_instansi' => $co ? ($co['npwp'] ?? null) : null,
                'id_jenis_bidang' => $idBidang,
                'id_prov'  => $co ? ($co['id_prov'] ?: null) : null,
                'provinsi' => $co ? ($co['provinsi'] ?: null) : null,
                'id_kab'   => $co ? ($co['id_city'] ?: null) : null,
                'kabupaten' => $co ? ($co['kota'] ?: null) : null,
            ];
            if ($m) {
                // Hanya field yang masih kosong yang diisi; nilai lama tidak ditimpa.
                $upd = [];
                foreach ($isi as $k => $v) if ($v !== null && $v !== '' && ($m[$k] === null || trim((string)$m[$k]) === '')) $upd[$k] = $v;
                if ($upd) {
                    $upd['updated_by'] = $userId; $upd['updated_date'] = $now;
                    $this->db->where('id', $m['id'])->update('ms_peserta', $upd);
                    write_log('Master Peserta', 'Edit', 'Update Peserta ' . $m['kode'] . ' dari Deal ' . $q['nomor'], ['old' => $m, 'new' => array_merge($m, $upd)], 'ms_peserta', $m['id'], $m['kode']);
                }
                $peta[$p['id']] = (int)$m['id']; $cocok++;
            } else {
                $kode = $this->md_model->generate_code('ms_peserta', 'PS');
                $ins = $isi + ['kode' => $kode, 'aktif' => 1, 'created_by' => $userId, 'created_date' => $now];
                $this->db->insert('ms_peserta', $ins);
                $newId = $this->db->insert_id();
                write_log('Master Peserta', 'Tambah', 'Tambah Peserta ' . $kode . ' dari Deal ' . $q['nomor'], $ins, 'ms_peserta', $newId, $kode);
                $peta[$p['id']] = (int)$newId; $baru++;
            }
        }
        return ['baru' => $baru, 'cocok' => $cocok, 'peta' => $peta];
    }

    /** Nomor Sales Order sementara `SO-nnn` per bulan-tahun (format final menunggu Labmania, Q-SO-01). Hanya di dalam transaksi. */
    private function nomor_so($tanggal)
    {
        // ponytail: format SO-YYMM-NNN adalah placeholder sampai Labmania menetapkan format (Q-SO-01); ganti prefix/pola di sini saja.
        $prefix = 'SO-' . date('ym', strtotime($tanggal)) . '-';
        $row = $this->db->query('SELECT nomor FROM sales_order WHERE nomor LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE', [$prefix . '%'])->row_array();
        $next = ($row && preg_match('/-(\d+)$/', $row['nomor'], $m)) ? (int)$m[1] + 1 : 1;
        return sprintf('%s%03d', $prefix, $next);
    }

    /** Nomor Surat Kerja Trainer sementara `SKT-YYMM-NNN` (Q-SKT-01). */
    private function nomor_skt($tanggal)
    {
        // ponytail: placeholder sampai Labmania menetapkan format nomor SKT (Q-SKT-01).
        $prefix = 'SKT-' . date('ym', strtotime($tanggal)) . '-';
        $row = $this->db->query('SELECT nomor FROM surat_kerja_trainer WHERE nomor LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE', [$prefix . '%'])->row_array();
        $next = ($row && preg_match('/-(\d+)$/', $row['nomor'], $m)) ? (int)$m[1] + 1 : 1;
        return sprintf('%s%03d', $prefix, $next);
    }

    /**
     * Deal (QTN-FR-11, 21, 33): hanya dari Terkirim. $dealIds = id quotation_peserta yang dicentang; yang lain Batal. $dokumen = berkas customer
     * hasil unggah controller [['orig','path','tipe','size'], ...], minimal satu. Satu transaksi: status, peserta, dokumen, Master Peserta,
     * Sales Order (1 per quotation Deal), Surat Kerja Trainer (1 per program, di SO pertama). Link tertutup otomatis karena status Deal.
     */
    public function set_deal($id, array $dealIds, array $dokumen = [], $ketDokumen = '')
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now = date('Y-m-d H:i:s');
        $ketDokumen = mb_substr(trim((string)$ketDokumen), 0, 200);

        $this->db->trans_start();
        $old = $this->db->query('SELECT * FROM quotation WHERE id = ? FOR UPDATE', [$id])->row_array();
        $err = null; $semua = []; $pjRow = null;
        if (!$old) $err = 'Quotation tidak ditemukan';
        elseif ($old['status'] !== 'terkirim') $err = 'Deal hanya untuk quotation berstatus Terkirim';

        if (!$err) {
            $pjRow = $this->db->query('SELECT * FROM penjualan_program WHERE id = ?', [$old['id_penjualan']])->row_array();
            $semua = $this->db->where('id_quotation', $id)->where('status !=', 'batal')->order_by('id')->get('quotation_peserta')->result_array();
            $byId = array_column($semua, null, 'id');
            $dealIds = array_values(array_intersect(array_map('intval', $dealIds), array_map('intval', array_keys($byId))));
            $b2b = ($pjRow['jenis'] ?? '') === 'b2b';
            if (!$semua) $err = 'Belum ada peserta di Data Peserta. Bagikan link Konfirmasi Kehadiran dulu.';
            elseif (!$dealIds) $err = 'Pilih minimal satu peserta Deal. Bila tidak ada yang deal, gunakan Batalkan.';
            elseif ($b2b && count($dealIds) < self::B2B_MIN) $err = 'Penjualan B2B: peserta Deal minimal ' . self::B2B_MIN;
            elseif (!$dokumen) $err = 'Unggah minimal satu dokumen dari customer (PO, SPK, atau dokumen lain)';
            elseif ($this->db->where('id_quotation', $id)->count_all_results('sales_order') > 0) $err = 'Quotation ini sudah punya Sales Order';
        }
        if ($err) { $this->db->trans_complete(); return ['status' => 0, 'msg' => $err]; }

        $deal = array_values(array_filter($semua, function ($p) use ($dealIds) { return in_array((int)$p['id'], $dealIds, true); }));
        $upd = ['status' => 'deal', 'deal_by' => $userId, 'deal_date' => $now, 'dokumen_keterangan' => $ketDokumen !== '' ? $ketDokumen : null, 'updated_by' => $userId, 'updated_date' => $now];

        // Peserta tercentang = Deal, sisanya Batal; yang sudah Batal tetap Batal.
        $this->db->where('id_quotation', $id)->where('status !=', 'batal')->update('quotation_peserta', ['status' => 'batal']);
        $this->db->where_in('id', $dealIds)->update('quotation_peserta', ['status' => 'deal']);
        $this->db->where('id', $id)->update('quotation', $upd);

        // Dokumen customer (QTN-FR-11)
        foreach ($dokumen as $d) {
            $this->db->insert('quotation_dokumen', ['id_quotation' => $id, 'nama_file' => mb_substr($d['orig'], 0, 255), 'path_file' => $d['path'], 'tipe_file' => $d['tipe'],
                                                    'ukuran_byte' => (int)$d['size'], 'created_by' => $userId, 'created_date' => $now]);
        }

        // Peserta Deal -> Master Peserta (QTN-FR-33); simpan hubungan di quotation_peserta.id_peserta
        $mst = $this->simpan_ke_master($old, $pjRow, $deal, $userId, $now);
        foreach ($mst['peta'] as $qpId => $msId) $this->db->where('id', $qpId)->update('quotation_peserta', ['id_peserta' => $msId]);

        // Sales Order: dibentuk otomatis, 1 quotation Deal = 1 SO (SO-FR-01); SKT satu per program di SO pertama (SO-FR-11)
        $tgl = date('Y-m-d');
        $soNomor = $this->nomor_so($tgl);
        $this->db->insert('sales_order', ['nomor' => $soNomor, 'id_quotation' => $id, 'id_penjualan' => $old['id_penjualan'], 'tanggal' => $tgl, 'status' => 'open',
                                          'revisi' => 0, 'created_by' => $userId, 'created_date' => $now]);
        $soId = $this->db->insert_id();
        $sktNomor = null;
        if ($pjRow['id_trainer'] && !$this->db->where('id_penjualan', $old['id_penjualan'])->count_all_results('surat_kerja_trainer')) {
            $sktNomor = $this->nomor_skt($tgl);
            $this->db->insert('surat_kerja_trainer', ['nomor' => $sktNomor, 'id_penjualan' => $old['id_penjualan'], 'id_trainer' => (int)$pjRow['id_trainer'],
                                                      'tanggal' => $tgl, 'created_by' => $userId, 'created_date' => $now]);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal menyimpan Deal, tidak ada data yang berubah'];

        $dc = [];
        foreach ($semua as $p) $dc['Peserta ' . $p['nama']] = ['old' => 'menunggu', 'new' => in_array((int)$p['id'], $dealIds, true) ? 'deal' : 'batal'];
        foreach ($dokumen as $d) $dc['Dokumen ' . $d['orig']] = ['old' => null, 'new' => 'diunggah'];
        $dc['Sales Order'] = ['old' => null, 'new' => $soNomor];
        if ($sktNomor) $dc['Surat Kerja Trainer'] = ['old' => null, 'new' => $sktNomor];
        write_log('Quotation', 'Deal', 'Deal Quotation ' . $old['nomor'] . ' — Master Peserta: ' . $mst['baru'] . ' baru, ' . $mst['cocok'] . ' dicocokkan',
            ['old' => $old, 'new' => array_merge($old, $upd), 'detail_changes' => $dc], 'quotation', $id, $old['nomor']);
        write_log('Sales Order', 'Tambah', 'Tambah Sales Order ' . $soNomor . ' dari Deal ' . $old['nomor'], ['nomor' => $soNomor, 'id_quotation' => (int)$id], 'sales_order', $soId, $soNomor);

        $batal = count($semua) - count($dealIds);
        return ['status' => 1, 'msg' => 'Quotation ' . $old['nomor'] . ' Deal (' . count($dealIds) . ' peserta' . ($batal ? ', ' . $batal . ' batal' : '') . '). Sales Order ' . $soNomor
                . ' dibuat. Master Peserta: ' . $mst['baru'] . ' baru, ' . $mst['cocok'] . ' dicocokkan.', 'so' => $soNomor, 'master_baru' => $mst['baru'], 'master_cocok' => $mst['cocok']];
    }

    // =========================================================================
    // LINK KONFIRMASI KEHADIRAN (QTN-FR-23 - QTN-FR-25, QTN-FR-31, QTN-NFR-03/08)
    // =========================================================================

    const FILE_MAX = 5242880; // surat konfirmasi: 5 MB (usulan, Q-TB-09)

    /** Quotation + Penjualan Program untuk halaman publik. Hanya field yang aman ditampilkan; token tidak dikembalikan. */
    public function by_token($token)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', (string)$token)) return null;
        return $this->db->select('q.id, q.nomor, q.status, q.tujuan, q.kepada_nama, q.up_nama, q.kuota_peserta, q.berlaku_sampai, q.link_dibuat, q.skema_harga, '
                . 'q.paket_maks_peserta, q.tambahan_per_peserta, q.kolom_peserta_b2b, q.konfirmasi_isian, q.konfirmasi_pernyataan, q.id_template_konfirmasi, q.id_customer, q.id_sales, '
                . 'pj.id AS id_pj, pj.jenis, pj.tgl_mulai, pj.tgl_selesai, pj.waktu, pj.lokasi, pj.durasi_hari, pj.jam_pembelajaran, pj.id_master_program, pp.judul AS nama_paket, '
                . 'c.name_customer AS nama_company, c.address_office AS alamat_company, c.kota AS kota_company, c.pic_name, c.pic_jabatan, c.pic_hp, c.pic_email')
            ->from('quotation q')
            ->join('penjualan_program pj', 'pj.id = q.id_penjualan', 'left')
            ->join('master_program mp', 'mp.id = pj.id_master_program', 'left')
            ->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false)
            ->join('master_customer c', 'c.id_customer = q.id_customer', 'left')
            ->where('q.link_token', $token)->get()->row_array() ?: null;
    }

    /**
     * Status link (QTN-FR-24). Kembalian: ['buka' => bool, 'alasan' => teks, 'terisi' => n, 'sisa' => n].
     * Terbuka bila Draft/Terkirim, hari ini <= Berlaku sampai, dan (Public) peserta belum mencapai Kuota / (B2B) PIC belum mengirim.
     */
    public function link_state(array $q)
    {
        $terisi = $this->n_terisi($q['id']);
        $sisa = max((int)$q['kuota_peserta'] - $terisi, 0);
        $st = ['buka' => false, 'alasan' => '', 'terisi' => $terisi, 'sisa' => $sisa, 'b2b' => ($q['jenis'] ?? '') === 'b2b'];
        if ($q['status'] === 'deal') $st['alasan'] = 'Ditutup: quotation sudah Deal';
        elseif ($q['status'] === 'batal') $st['alasan'] = 'Ditutup: quotation Batal';
        elseif (date('Y-m-d') > $q['berlaku_sampai']) $st['alasan'] = 'Ditutup: masa berlaku quotation berakhir ' . date('d-m-Y', strtotime($q['berlaku_sampai']));
        elseif ($st['b2b']) {
            $k = $this->db->get_where('quotation_konfirmasi_company', ['id_quotation' => $q['id']])->row_array();
            if ($k) $st['alasan'] = 'Ditutup: daftar ' . (int)$k['jumlah_peserta'] . ' peserta sudah dikirim PIC company ' . date('d-m-Y H:i', strtotime($k['tgl_kirim']));
            else { $st['buka'] = true; $st['alasan'] = 'Menunggu PIC company mengirim daftar peserta'; }
        } elseif ($sisa <= 0) $st['alasan'] = 'Ditutup: kuota ' . (int)$q['kuota_peserta'] . ' peserta sudah terisi';
        else { $st['buka'] = true; $st['alasan'] = 'Menerima isian · ' . $terisi . ' dari ' . (int)$q['kuota_peserta'] . ' kuota terisi'; }
        return $st;
    }

    /** Isian yang diminta di link Public (kunci KF_FIELDS); Nama Lengkap selalu ada (QTN-FR-19). */
    public function isian_diminta(array $q)
    {
        $l = json_decode((string)$q['konfirmasi_isian'], true);
        $l = is_array($l) ? array_values(array_intersect($l, array_keys(Master_data_model::KF_FIELDS))) : [];
        return array_values(array_unique(array_merge(['nama'], $l)));
    }

    /** Validasi isian satu peserta Public. Kembalian: [field => pesan]. */
    public function validate_isian(array $in)
    {
        $e = [];
        $t = function ($k) use ($in) { return trim((string)($in[$k] ?? '')); };
        if ($t('nama') === '') $e['nama'] = 'Nama Lengkap wajib diisi';
        elseif (mb_strlen($t('nama')) > 150) $e['nama'] = 'Nama Lengkap maksimal 150 karakter';
        if ($t('email') !== '' && !filter_var($t('email'), FILTER_VALIDATE_EMAIL)) $e['email'] = 'Format email tidak valid';
        foreach (['hp', 'wa'] as $k) {
            if ($t($k) !== '' && !preg_match('/^\d{9,15}$/', preg_replace('/[\s\-+]/', '', $t($k)))) $e[$k] = 'Nomor hanya angka, 9–15 digit';
        }
        if ($t('nik') !== '' && mb_strlen($t('nik')) > 20) $e['nik'] = 'NIK maksimal 20 karakter';
        foreach (['jabatan' => 100, 'instansi' => 150, 'pengalaman' => 100, 'lsp' => 100] as $k => $max) {
            if ($t($k) !== '' && mb_strlen($t($k)) > $max) $e[$k] = 'Maksimal ' . $max . ' karakter';
        }
        if ($t('habis') !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $t('habis'))) $e['habis'] = 'Tanggal tidak valid';
        return $e; // isian selain Nama bersifat opsional; yang diminta hanya menentukan kolom yang tampil di link (QTN-FR-19)
    }

    /**
     * Terima satu isian Public (QTN-FR-25). Satu transaksi dengan kunci baris quotation: link diperiksa ulang (status, masa berlaku, kuota)
     * sehingga dua kiriman bersamaan tidak melebihi kuota (QTN-NFR-03). $berkas = hasil unggah [path, nama, size, tipe].
     * Kembalian: ['status' => 1/0, 'msg' => ..., 'errors' => ...].
     */
    public function terima_isian($token, array $in, array $berkas)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $q = $this->db->query('SELECT * FROM quotation WHERE link_token = ? FOR UPDATE', [(string)$token])->row_array();
        if (!$q) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Link tidak ditemukan']; }
        $pj = $this->db->get_where('penjualan_program', ['id' => $q['id_penjualan']])->row_array();
        $q['jenis'] = $pj['jenis'] ?? 'public';
        if ($q['jenis'] === 'b2b') { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Link ini untuk PIC company']; }
        $stt = $this->link_state($q);
        if (!$stt['buka']) { $this->db->trans_complete(); return ['status' => 0, 'closed' => 1, 'msg' => 'Formulir ini sudah ditutup. ' . $stt['alasan']]; }

        $diminta = $this->isian_diminta($q);
        $err = $this->validate_isian($in);
        if ($err) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Periksa isian yang ditandai', 'errors' => $err]; }

        $v = function ($k) use ($in) { $x = trim((string)($in[$k] ?? '')); return $x === '' ? null : $x; };
        $co = $q['tujuan'] === 'company' && $q['id_customer'] ? $this->db->get_where('master_customer', ['id_customer' => $q['id_customer']])->row_array() : null;
        $extra = [];
        foreach ($diminta as $k) if (!array_key_exists($k, Master_data_model::KF_FIELDS) && isset($in['ex'][$k])) $extra[$k] = mb_substr(trim((string)$in['ex'][$k]), 0, 200);

        $this->db->insert('quotation_peserta', [
            'id_quotation' => $q['id'], 'sumber' => 'link', 'tgl_isi' => $now, 'nama' => $v('nama'),
            'nik' => $v('nik'), 'jabatan' => $v('jabatan'), 'instansi' => $co ? $co['name_customer'] : $v('instansi'),
            'alamat_instansi' => $v('alamat'), 'no_hp' => $v('hp'), 'no_wa' => $v('wa'), 'email' => $v('email'),
            'pengalaman' => $v('pengalaman'), 'lsp' => $v('lsp'), 'tgl_habis_sertifikat' => $v('habis'),
            'isian_tambahan' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null, 'status' => 'menunggu',
            'surat_konfirmasi' => $berkas['path'], 'surat_konfirmasi_nama' => mb_substr($berkas['nama'], 0, 150),
        ]);
        $newId = $this->db->insert_id();
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal menyimpan konfirmasi'];

        // Pelaku: calon peserta tanpa login (QTN-NFR-01); tidak mencatat isi pribadi, hanya nama dan nomor quotation.
        write_log('Quotation', 'Tambah', 'Konfirmasi kehadiran ' . $v('nama') . ' via link ' . $q['nomor'], ['sub_modul' => 'Data Peserta', 'nama' => $v('nama'), 'sumber' => 'link'], 'quotation_peserta', $newId, $q['nomor']);
        return ['status' => 1, 'msg' => 'Konfirmasi terkirim', 'id' => $newId, 'sisa' => max($stt['sisa'] - 1, 0)];
    }

    /** Kolom opsional daftar peserta B2B yang diminta Sales (chip), selain Nama yang selalu ada (QTN-FR-31). */
    public function kolom_b2b(array $q)
    {
        $l = json_decode((string)($q['kolom_peserta_b2b'] ?? ''), true);
        $ok = ['jabatan' => 'Jabatan', 'email' => 'Email', 'wa' => 'Nomor WhatsApp', 'nik' => 'NIK'];
        return is_array($l) ? array_values(array_intersect($l, array_keys($ok))) : [];
    }

    /**
     * Terima kiriman PIC company B2B (QTN-FR-31): data company + PIC, daftar peserta (min. 10, baris tanpa nama diabaikan), surat company.
     * Hanya sekali per quotation: dicek di dalam transaksi bersama baris quotation_konfirmasi_company (UNIQUE id_quotation).
     */
    public function terima_company($token, array $in, array $nama, array $berkas)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $q = $this->db->query('SELECT * FROM quotation WHERE link_token = ? FOR UPDATE', [(string)$token])->row_array();
        if (!$q) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Link tidak ditemukan']; }
        $pj = $this->db->get_where('penjualan_program', ['id' => $q['id_penjualan']])->row_array();
        $q['jenis'] = $pj['jenis'] ?? '';
        if ($q['jenis'] !== 'b2b') { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Link ini bukan untuk PIC company']; }
        $stt = $this->link_state($q);
        if (!$stt['buka']) { $this->db->trans_complete(); return ['status' => 0, 'closed' => 1, 'msg' => 'Formulir ini sudah ditutup. ' . $stt['alasan']]; }

        $t = function ($k) use ($in) { return trim((string)($in[$k] ?? '')); };
        $e = [];
        foreach (['nama_perusahaan' => 'Nama Perusahaan', 'alamat_perusahaan' => 'Alamat Perusahaan', 'pic_nama' => 'Nama PIC', 'pic_jabatan' => 'Jabatan PIC', 'pic_hp' => 'Nomor HP / WhatsApp PIC', 'pic_email' => 'Email PIC'] as $k => $l) {
            if ($t($k) === '') $e[$k] = $l . ' wajib diisi';
        }
        if (!isset($e['pic_email']) && !filter_var($t('pic_email'), FILTER_VALIDATE_EMAIL)) $e['pic_email'] = 'Format email tidak valid';
        if (!isset($e['pic_hp']) && !preg_match('/^\d{9,15}$/', preg_replace('/[\s\-+]/', '', $t('pic_hp')))) $e['pic_hp'] = 'Nomor hanya angka, 9–15 digit';
        foreach (['nama_perusahaan' => 150, 'pic_nama' => 100, 'pic_jabatan' => 100, 'pic_email' => 100] as $k => $mx) if (!isset($e[$k]) && mb_strlen($t($k)) > $mx) $e[$k] = 'Maksimal ' . $mx . ' karakter';
        if (empty($in['setuju'])) $e['setuju'] = 'Centang pernyataan terlebih dahulu';

        // Daftar peserta: baris tanpa nama diabaikan; kolom lain terisi tanpa nama = galat; minimal 10 nama (QTN-FR-21/31)
        $kolom = $this->kolom_b2b($q);
        $rows = []; $eRow = [];
        foreach ($nama as $i => $r) {
            if (!is_array($r)) continue;
            $n = trim((string)($r['nama'] ?? ''));
            $lain = false;
            foreach ($kolom as $k) if (trim((string)($r[$k] ?? '')) !== '') $lain = true;
            if ($n === '') { if ($lain) $eRow[$i] = 'Nama wajib diisi bila kolom lain terisi'; continue; }
            if (mb_strlen($n) > 150) { $eRow[$i] = 'Nama maksimal 150 karakter'; continue; }
            if (!empty($r['email']) && !filter_var(trim($r['email']), FILTER_VALIDATE_EMAIL)) { $eRow[$i] = 'Format email tidak valid'; continue; }
            $rows[] = $r;
        }
        if ($eRow) $e['peserta_rows'] = $eRow;
        elseif (count($rows) < self::B2B_MIN) $e['peserta'] = 'Daftar peserta minimal ' . self::B2B_MIN . ' nama (terisi ' . count($rows) . ')';
        if ($e) { $this->db->trans_complete(); return ['status' => 0, 'msg' => 'Periksa isian yang ditandai', 'errors' => $e]; }

        $this->db->insert('quotation_konfirmasi_company', [
            'id_quotation' => $q['id'], 'nama_perusahaan' => $t('nama_perusahaan'), 'alamat_perusahaan' => $t('alamat_perusahaan'), 'pic_nama' => $t('pic_nama'),
            'pic_jabatan' => $t('pic_jabatan'), 'pic_hp' => $t('pic_hp'), 'pic_email' => $t('pic_email'), 'jumlah_peserta' => count($rows),
            'surat_konfirmasi' => $berkas['path'], 'surat_konfirmasi_nama' => mb_substr($berkas['nama'], 0, 255), 'tgl_kirim' => $now,
        ]);
        foreach ($rows as $r) {
            $x = function ($k) use ($r) { $v = trim((string)($r[$k] ?? '')); return $v === '' ? null : $v; };
            $this->db->insert('quotation_peserta', [
                'id_quotation' => $q['id'], 'sumber' => 'company', 'tgl_isi' => $now, 'nama' => trim($r['nama']), 'nik' => $x('nik'), 'jabatan' => $x('jabatan'),
                'instansi' => $t('nama_perusahaan'), 'email' => $x('email'), 'no_wa' => $x('wa'), 'status' => 'menunggu',
                'surat_konfirmasi' => $berkas['path'], 'surat_konfirmasi_nama' => mb_substr($berkas['nama'], 0, 150),
            ]);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal menyimpan konfirmasi company'];

        write_log('Quotation', 'Tambah', 'Konfirmasi company ' . $t('nama_perusahaan') . ' via link ' . $q['nomor'] . ' (' . count($rows) . ' peserta)',
            ['sub_modul' => 'Data Peserta', 'jumlah_peserta' => count($rows), 'sumber' => 'company'], 'quotation', $q['id'], $q['nomor']);
        return ['status' => 1, 'msg' => 'Daftar peserta terkirim', 'jumlah' => count($rows)];
    }

    // =========================================================================
    // DATA PESERTA PER BATCH (QTN-FR-29)
    // =========================================================================

    /**
     * Satu baris per Penjualan Program Final (On going dan Selesai): jumlah peserta per status dan jumlah quotation.
     * Tahun/Bulan menurut tanggal MULAI acara; keduanya kosong = semua, termasuk program tanpa tanggal. $q mencari program, nomor quotation, nama peserta.
     */
    public function batch_list($tahun = null, $bulan = null, $q = '')
    {
        $rows = $this->get_final_penjualans();
        $out = [];
        $q = mb_strtolower(trim((string)$q));
        foreach ($rows as $pj) {
            $ts = !empty($pj['tgl_mulai']) ? strtotime($pj['tgl_mulai']) : null;
            if ($tahun && (!$ts || (int)date('Y', $ts) !== (int)$tahun)) continue;
            if ($bulan && (!$ts || (int)date('n', $ts) !== (int)$bulan)) continue;
            $ps = $this->db->select('qp.status, qp.nama, qp.instansi, q.nomor, q.id AS id_quotation')->from('quotation_peserta qp')->join('quotation q', 'q.id = qp.id_quotation')
                ->where('q.id_penjualan', (int)$pj['id'])->get()->result_array();
            $pj['n'] = ['deal' => 0, 'menunggu' => 0, 'batal' => 0];
            foreach ($ps as $p) $pj['n'][$p['status']] = ($pj['n'][$p['status']] ?? 0) + 1;
            $pj['n_total'] = count($ps);
            $pj['n_quotation'] = (int)$this->db->where('id_penjualan', $pj['id'])->count_all_results('quotation');
            $pj['selesai'] = !empty($pj['tgl_selesai']) && $pj['tgl_selesai'] < date('Y-m-d');
            if ($q !== '') {
                $hay = mb_strtolower(implode(' ', array_merge([$pj['nomor'], $pj['nama_paket'], $pj['lokasi']], array_column($ps, 'nama'), array_column($ps, 'nomor'))));
                if (mb_strpos($hay, $q) === false) continue;
            }
            $out[] = $pj;
        }
        return array_reverse($out);
    }

    /**
     * Semua peserta dari semua quotation satu Penjualan Program (batch), dengan nomor quotation dan Kepada. Peserta quotation Batal ikut tampil
     * berstatus Batal (Q-QTN-19). Filter status: all|deal|menunggu|batal; $q mencari peserta, instansi, kontak, nomor quotation.
     */
    public function batch_peserta($idPj, $status = 'all', $q = '')
    {
        $rows = $this->db->select('qp.*, q.nomor AS nomor_quotation, q.status AS status_quotation, q.tujuan, q.kepada_nama, c.name_customer AS nama_company')
            ->from('quotation_peserta qp')->join('quotation q', 'q.id = qp.id_quotation')->join('master_customer c', 'c.id_customer = q.id_customer', 'left')
            ->where('q.id_penjualan', (int)$idPj)->order_by('q.id, qp.id')->get()->result_array();
        $q = mb_strtolower(trim((string)$q));
        return array_values(array_filter($rows, function ($r) use ($status, $q) {
            if (in_array($status, ['deal', 'menunggu', 'batal'], true) && $r['status'] !== $status) return false;
            if ($q === '') return true;
            $hay = mb_strtolower(implode(' ', [$r['nama'], $r['instansi'], $r['email'], $r['no_wa'], $r['no_hp'], $r['nomor_quotation'], $r['jabatan']]));
            return mb_strpos($hay, $q) !== false;
        }));
    }
}
