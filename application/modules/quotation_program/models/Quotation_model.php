<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Quotation_model extends CI_Model
{
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
        $this->db->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left');
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
            $row['peserta'] = $this->db->select('qp.*, p.kode as kode_peserta, p.nama as nama_peserta, p.instansi, p.no_telp, p.email')
                                       ->from('quotation_peserta qp')
                                       ->join('ms_peserta p', 'qp.id_peserta = p.id', 'left')
                                       ->where('qp.id_quotation', $row['id'])
                                       ->get()->result_array();

            // Hitung nilai total
            $row['calc'] = $this->calculate_quotation_value($row, $this->n_nilai($row['status'], $row['peserta']));
        }

        return $list;
    }

    public function get_quotation_by_id($id)
    {
        $this->db->select('q.*, 
                           pj.nomor as nomor_pj, pj.jenis as jenis_pj, pj.tgl_mulai, pj.tgl_selesai, pj.waktu, pj.lokasi, pj.durasi_hari, pj.target_peserta, pj.harga_jual_peserta,
                           mp.nomor as nomor_mp, pp.judul as nama_paket, pp.id_jenis_produk,
                           c.name_customer as nama_company, c.jenis_instansi, c.address_office as alamat_company, c.telephone as telp_company,
                           u.nm_lengkap as nama_sales, u.email as email_sales, u.hp as hp_sales,
                           tk.nama as nama_template_konfirmasi, tk.label_kegiatan');
        $this->db->from('quotation q');
        $this->db->join('penjualan_program pj', 'q.id_penjualan = pj.id', 'left');
        $this->db->join('master_program mp', 'pj.id_master_program = mp.id', 'left');
        $this->db->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left');
        $this->db->join('master_customer c', 'q.id_customer = c.id_customer', 'left');
        $this->db->join('users u', 'q.id_sales = u.id_user', 'left');
        $this->db->join('ms_template_konfirmasi tk', 'q.id_template_konfirmasi = tk.id', 'left');
        $this->db->where('q.id', $id);
        $q = $this->db->get()->row_array();

        if ($q) {
            // Ambil daftar peserta
            $q['peserta'] = $this->db->select('qp.*, p.kode as kode_peserta, p.nama as nama_peserta, p.gender, p.instansi, p.no_telp, p.email, p.provinsi, p.kabupaten, CONCAT_WS(\', \', p.kabupaten, p.provinsi) as alamat_peserta')
                                     ->from('quotation_peserta qp')
                                     ->join('ms_peserta p', 'qp.id_peserta = p.id', 'left')
                                     ->where('qp.id_quotation', $id)
                                     ->get()->result_array();

            // Ambil keterangan S&K
            $q['keterangan'] = $this->db->get_where('quotation_keterangan', ['id_quotation' => $id])
                                        ->result_array();

            // Kalkulasi nilai
            $q['calc'] = $this->calculate_quotation_value($q, $this->n_nilai($q['status'], $q['peserta']));
        }

        return $q;
    }

    /** Jumlah peserta untuk nilai: pada Deal hanya peserta Deal (QTN-FR-11). */
    private function n_nilai($status, array $peserta)
    {
        if ($status !== 'deal') return count($peserta);
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

        if ($skema === 'allin') {
            $subtotal = $hargaAllin;
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

    /** Nomor LM-I/AM/PW/MM/YY-XXX (QTN-FR-04; AM tetap, urutan berlanjut — Q-QTN-01). $lock=true hanya di dalam transaksi. */
    public function generate_nomor($lock = false)
    {
        $sql = "SELECT nomor FROM quotation WHERE nomor LIKE 'LM-I/AM/PW/%' ORDER BY id DESC LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
        $row = $this->db->query($sql)->row_array();
        $next = ($row && preg_match('/-(\d+)$/', $row['nomor'], $m)) ? (int)$m[1] + 1 : 1;
        return sprintf('LM-I/AM/PW/%s/%s-%03d', date('m'), date('y'), $next);
    }

    public function get_final_penjualans()
    {
        return $this->db->select('pj.*, mp.nomor as nomor_mp, pp.judul as nama_paket, pp.id_jenis_produk, c.name_customer as nama_company')
                        ->from('penjualan_program pj')
                        ->join('master_program mp', 'pj.id_master_program = mp.id', 'left')
                        ->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left')
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

    /** Peserta yang sudah ada di quotation lain (bukan Batal) untuk Penjualan Program yang sama: [id_peserta => nomor]. */
    public function peserta_dipakai($idPj, $kecualiId = null)
    {
        $this->db->select('qp.id_peserta, q.nomor')
                 ->from('quotation_peserta qp')
                 ->join('quotation q', 'q.id = qp.id_quotation')
                 ->where('q.id_penjualan', (int)$idPj)
                 ->where('q.status !=', 'batal')
                 ->where('qp.status !=', 'batal');
        if ($kecualiId) $this->db->where('q.id !=', (int)$kecualiId);
        return array_column($this->db->get()->result_array(), 'nomor', 'id_peserta');
    }

    /** Header quotation dari isian form (dipakai simpan dan pratinjau). */
    private function parse_header($post, $pj, $userId)
    {
        $tujuan = $pj['jenis'] === 'b2b' ? 'company' : (($post['tujuan'] ?? '') === 'company' ? 'company' : 'personal');
        $idCustomer = ($tujuan === 'company') ? ($pj['jenis'] === 'b2b' ? $pj['id_customer'] : ($post['id_customer'] ?? null)) : null;

        $skema = ($pj['jenis'] === 'b2b' && ($post['skema_harga'] ?? '') === 'allin') ? 'allin' : 'peserta';
        $hargaPeserta = (float)$pj['harga_jual_peserta']; // QTN-NFR-02: harga dari Penjualan Program Final, bukan dari klien
        $hargaAllin = (float)str_replace('.', '', $post['harga_allin'] ?? 0);
        $diskon = (float)str_replace('.', '', $post['diskon'] ?? 0);
        $biayaLain = (float)str_replace('.', '', $post['biaya_lain'] ?? 0);
        $isPpn = !empty($post['is_ppn']) ? 1 : 0;
        $ppnPersen = (float)($post['ppn_persen'] ?? 11);
        // Deal/Batal hanya lewat set_status (QTN-FR-11/12).
        $status = ($post['status'] ?? '') === 'terkirim' ? 'terkirim' : 'draft';

        $header = [
            'id_penjualan'           => $idPj,
            'tanggal'                => !empty($post['tanggal']) ? $post['tanggal'] : date('Y-m-d'),
            'berlaku_sampai'         => !empty($post['berlaku_sampai']) ? $post['berlaku_sampai'] : date('Y-m-d', strtotime('+30 days')),
            'id_sales'               => !empty($post['id_sales']) ? (int)$post['id_sales'] : $userId,
            'sumber_info'            => trim($post['sumber_info'] ?? ''),
            'tujuan'                 => $tujuan,
            'id_customer'            => $idCustomer,
            'up_nama'                => trim($post['up_nama'] ?? ''),
            'alamat'                 => trim($post['alamat'] ?? ''),
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
            ->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left')
            ->where('pj.id', (int)($post['id_penjualan'] ?? 0))->get()->row_array();
        if (!$pj) return null;

        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $q = $this->parse_header($post, $pj, $userId);
        $q['nomor']       = !empty($post['nomor']) ? $post['nomor'] : $this->generate_nomor();
        $q['jenis_pj']    = $pj['jenis'];
        foreach (['tgl_mulai', 'tgl_selesai', 'waktu', 'lokasi', 'durasi_hari', 'target_peserta', 'nama_paket', 'id_jenis_produk'] as $k) $q[$k] = $pj[$k] ?? null;

        $co = $q['id_customer'] ? $this->db->get_where('master_customer', ['id_customer' => $q['id_customer']])->row_array() : null;
        $q['nama_company'] = $co['name_customer'] ?? null;
        $q['alamat_company'] = $co['address_office'] ?? null;
        $sales = $this->db->get_where('users', ['id_user' => $q['id_sales']])->row_array();
        $q['nama_sales'] = $sales['nm_lengkap'] ?? null;
        $q['email_sales'] = $sales['email'] ?? null;
        $q['hp_sales'] = $sales['hp'] ?? null;

        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($post['peserta'] ?? [])))));
        $q['peserta'] = $ids ? $this->db->select('p.id as id_peserta, p.kode as kode_peserta, p.nama as nama_peserta, p.gender, p.instansi, p.no_telp, p.email, p.provinsi, p.kabupaten, CONCAT_WS(\', \', p.kabupaten, p.provinsi) as alamat_peserta, \'menunggu\' as status')
            ->from('ms_peserta p')->where_in('p.id', $ids)->get()->result_array() : [];

        $q['keterangan'] = [];
        foreach ((array)($post['keterangan'] ?? []) as $ket) {
            if (trim($ket['teks'] ?? '') !== '' && ($ket['kategori'] ?? '') !== 'klausul') $q['keterangan'][] = ['kategori' => $ket['kategori'], 'teks' => trim($ket['teks'])];
        }
        foreach ($this->get_default_keterangan($pj['jenis']) as $t) {
            if ($t['kategori'] === 'klausul' && $t['is_default']) $q['keterangan'][] = ['kategori' => 'klausul', 'teks' => $t['teks']];
        }
        $q['calc'] = $this->calculate_quotation_value($q, count($q['peserta']));
        return $q;
    }

    const SUMBER = ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya'];

    /** Aturan integritas QTN-FR-03/04/06/07/20/21 (QTN-NFR-03). Dipanggil di dalam transaksi. Mengembalikan pesan galat atau null. */
    private function validate_quotation($pj, $header, array $peserta, $id)
    {
        if ($pj['status'] !== 'final') return 'Penjualan Program ' . $pj['nomor'] . ' belum Final';
        if (!$peserta) return 'Pilih minimal 1 peserta';

        if ($header['berlaku_sampai'] < $header['tanggal']) return 'Berlaku sampai tidak boleh sebelum tanggal quotation';
        if ($header['diskon'] < 0 || $header['biaya_lain'] < 0 || $header['harga_allin'] < 0) return 'Diskon, biaya lain, dan harga all-in tidak boleh negatif';
        if ($header['ppn_persen'] < 0 || $header['ppn_persen'] > 100) return 'PPN harus 0–100%';
        if ($header['sumber_info'] !== '' && !in_array($header['sumber_info'], self::SUMBER, true)) return 'Sumber informasi tidak valid';
        if (!$this->db->query('SELECT 1 FROM users WHERE id_user = ? AND IFNULL(deleted, 0) = 0', [$header['id_sales']])->row_array()) return 'Sales tidak ditemukan';
        if ($header['id_template_konfirmasi'] && !$this->db->get_where('ms_template_konfirmasi', ['id' => $header['id_template_konfirmasi']])->row_array()) return 'Template Formulir Konfirmasi tidak ditemukan';
        if (count($peserta) !== $this->db->where_in('id', $peserta)->count_all_results('ms_peserta')) return 'Ada peserta yang tidak ditemukan di Master Peserta';

        $b2b = $pj['jenis'] === 'b2b';
        if ($b2b && count($peserta) < self::B2B_MIN) return 'Penjualan B2B minimal ' . self::B2B_MIN . ' peserta';

        if ($b2b) {
            $this->db->where('id_penjualan', $pj['id'])->where('status !=', 'batal');
            if ($id) $this->db->where('id !=', $id);
            if ($this->db->count_all_results('quotation') > 0) return 'Penjualan B2B hanya boleh punya satu quotation aktif';
        }

        $dipakai = $this->peserta_dipakai($pj['id'], $id);
        $ganda = array_intersect($peserta, array_keys($dipakai));
        if ($ganda) return 'Peserta sudah ada di quotation ' . $dipakai[reset($ganda)];

        if ($header['tujuan'] === 'company') {
            if (empty($header['id_customer'])) return 'Company wajib dipilih';
            $co = $this->db->where('id_customer', $header['id_customer'])->where('deleted_date IS NULL', null, false)->get('master_customer')->row_array();
            if (!$co || in_array($co['activation'], ['nonaktif', 'inactive'], true)) return 'Company tidak ditemukan atau nonaktif';
            $salah = $this->db->where_in('id', $peserta)->where('(id_customer IS NULL OR id_customer != ' . $this->db->escape($header['id_customer']) . ')', null, false)->count_all_results('ms_peserta');
            if ($salah > 0) return 'Ada peserta yang bukan dari company terpilih';
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
        $pesertaIds = array_values(array_unique(array_filter(array_map('intval', (array)($post['peserta'] ?? [])))));

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
            $err = $err ?: $this->validate_quotation($pj, $header, $pesertaIds, $id);
        }
        if ($err) { $this->db->trans_complete(); return ['status' => 0, 'msg' => $err]; }

        // Snapshot detail lama sebelum dihapus (untuk detail_changes).
        $oldPeserta = $oldKet = [];
        if ($id) {
            $oldPeserta = array_column($this->db->select('qp.id_peserta, p.nama')->from('quotation_peserta qp')->join('ms_peserta p', 'p.id = qp.id_peserta', 'left')->where('qp.id_quotation', $id)->get()->result_array(), 'nama', 'id_peserta');
            $oldKet = array_column($this->db->get_where('quotation_keterangan', ['id_quotation' => $id])->result_array(), 'teks');
            $header['updated_by']   = $userId;
            $header['updated_date'] = $now;
            $this->db->where('id', $id)->update('quotation', $header);
            $qId = $id;
            $nomor = $cur['nomor'];
        } else {
            $nomor = $this->generate_nomor(true);
            $header['nomor']        = $nomor;
            $header['created_by']   = $userId;
            $header['created_date'] = $now;
            $this->db->insert('quotation', $header);
            $qId = $this->db->insert_id();
        }

        $this->db->delete('quotation_peserta', ['id_quotation' => $qId]);
        foreach ($pesertaIds as $pId) {
            $this->db->insert('quotation_peserta', ['id_quotation' => $qId, 'id_peserta' => $pId, 'status' => 'menunggu']);
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
        $newPeserta = array_column($this->db->select('id, nama')->where_in('id', $pesertaIds)->get('ms_peserta')->result_array(), 'nama', 'id');
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['status' => 0, 'msg' => 'Gagal menyimpan Quotation'];
        }

        // Log di luar transaksi agar gagal log tidak membatalkan simpan.
        $dc = [];
        foreach (array_diff_key($newPeserta, $oldPeserta) as $n) $dc['Peserta ' . $n] = ['old' => '-', 'new' => 'ditambahkan'];
        foreach (array_diff_key($oldPeserta, $newPeserta) as $n) $dc['Peserta ' . $n] = ['old' => 'ada', 'new' => 'dihapus'];
        if ($id && $oldKet !== $newKet) $dc['Keterangan'] = ['old' => count($oldKet) . ' butir', 'new' => count($newKet) . ' butir'];
        if ($id) {
            write_log('Quotation', 'Edit', 'Update Quotation ' . $nomor, ['old' => $cur, 'new' => array_merge($cur, $header), 'detail_changes' => $dc], 'quotation', $qId, $nomor);
        } else {
            write_log('Quotation', 'Tambah', 'Tambah Quotation ' . $nomor, ['old' => [], 'new' => $header, 'detail_changes' => ['Jumlah peserta' => ['old' => 0, 'new' => count($pesertaIds)]]], 'quotation', $qId, $nomor);
        }

        return ['status' => 1, 'msg' => 'Quotation ' . $nomor . ' berhasil disimpan', 'id' => $qId];
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

    /** Deal (QTN-FR-11/21): peserta tercentang = deal, sisanya batal. */
    public function set_deal($id, array $dealIds)
    {
        $userId = $this->session->userdata('app_session')['id_user'] ?? 1;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $old = $this->db->query('SELECT * FROM quotation WHERE id = ? FOR UPDATE', [$id])->row_array();
        $err = null; $nama = [];
        if (!$old) $err = 'Quotation tidak ditemukan';
        elseif ($old['status'] !== 'terkirim') $err = 'Deal hanya untuk quotation berstatus Terkirim';

        if (!$err) {
            $semua = $this->db->select('qp.id_peserta, p.nama')->from('quotation_peserta qp')->join('ms_peserta p', 'p.id = qp.id_peserta', 'left')->where('qp.id_quotation', $id)->get()->result_array();
            $nama = array_column($semua, 'nama', 'id_peserta');
            $dealIds = array_values(array_intersect(array_map('intval', $dealIds), array_map('intval', array_keys($nama))));
            $b2b = ($this->db->get_where('penjualan_program', ['id' => $old['id_penjualan']])->row_array()['jenis'] ?? '') === 'b2b';
            if (!$dealIds) $err = 'Pilih minimal satu peserta Deal. Bila tidak ada yang deal, gunakan Batalkan.';
            elseif ($b2b && count($dealIds) < self::B2B_MIN) $err = 'Penjualan B2B: peserta Deal minimal ' . self::B2B_MIN;
        }
        if ($err) { $this->db->trans_complete(); return ['status' => 0, 'msg' => $err]; }

        $upd = ['status' => 'deal', 'deal_by' => $userId, 'deal_date' => $now, 'updated_by' => $userId, 'updated_date' => $now];
        $this->db->where('id_quotation', $id)->update('quotation_peserta', ['status' => 'batal']);
        $this->db->where('id_quotation', $id)->where_in('id_peserta', $dealIds)->update('quotation_peserta', ['status' => 'deal']);
        $this->db->where('id', $id)->update('quotation', $upd);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) return ['status' => 0, 'msg' => 'Gagal menyimpan Deal'];

        $dc = [];
        foreach ($nama as $pid => $n) $dc['Peserta ' . $n] = ['old' => 'menunggu', 'new' => in_array((int)$pid, $dealIds, true) ? 'deal' : 'batal'];
        write_log('Quotation', 'Deal', 'Deal Quotation ' . $old['nomor'], ['old' => $old, 'new' => array_merge($old, $upd), 'detail_changes' => $dc], 'quotation', $id, $old['nomor']);

        $batal = count($nama) - count($dealIds);
        return ['status' => 1, 'msg' => 'Quotation ' . $old['nomor'] . ' Deal (' . count($dealIds) . ' peserta' . ($batal ? ', ' . $batal . ' peserta batal' : '') . ')'];
    }
}
