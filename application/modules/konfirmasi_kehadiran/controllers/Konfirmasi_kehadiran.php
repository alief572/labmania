<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Halaman Konfirmasi Kehadiran publik, TANPA LOGIN (QTN-FR-23 - QTN-FR-25, QTN-FR-31, QTN-NFR-08, QTN-NFR-10).
 * Sengaja memakai CI_Controller (bukan Labmania_Controller): calon peserta dan PIC company tidak punya akun.
 * Token link acak 128 bit; halaman hanya menampilkan info program dan sisa kursi quotation itu, tidak ada data peserta lain.
 */
class Konfirmasi_kehadiran extends CI_Controller
{
    const LAJU_MAKS = 12;      // kiriman per IP per jam (QTN-NFR-08: pembatasan laju)
    const LAJU_DETIK = 3600;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form', 'log', 'paket']);
        $this->load->model('quotation_program/Quotation_model', 'qtn_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');        // token ada di URL; jangan bocor lewat Referer
        header('X-Robots-Tag: noindex, nofollow');
    }

    /** Halaman link. Token tidak valid dan tidak ditemukan sama-sama 404 (tidak membocorkan keberadaan quotation). */
    public function index($token = '')
    {
        $q = $this->qtn_model->by_token($token);
        if (!$q) show_404();
        $stt = $this->qtn_model->link_state($q);
        $data = [
            'q' => $q, 'stt' => $stt, 'token' => $token, 'b2b' => $q['jenis'] === 'b2b',
            'kf_fields' => Master_data_model::KF_FIELDS, 'diminta' => $this->qtn_model->isian_diminta($q), 'kolom_b2b' => $this->qtn_model->kolom_b2b($q),
            'sales' => $this->sales($q),
        ];
        $this->load->view('halaman', $data);
    }

    /** Kirim isian Public (multipart, AJAX). */
    public function kirim($token = '')
    {
        if ($this->input->method() !== 'post') show_404();
        $q = $this->qtn_model->by_token($token);
        if (!$q) return $this->json(['status' => 0, 'msg' => 'Link tidak ditemukan'], 404);
        if ($this->limit_kena()) return $this->json(['status' => 0, 'msg' => 'Terlalu banyak kiriman dari jaringan Anda. Coba lagi beberapa saat.'], 429);

        $in = $this->input->post(null, true) ?: [];
        if (empty($in['setuju'])) return $this->json(['status' => 0, 'msg' => 'Periksa isian yang ditandai', 'errors' => ['setuju' => 'Centang pernyataan terlebih dahulu']], 422);

        $berkas = null; $fail = $this->simpan_berkas('surat', $berkas);
        if ($fail) return $this->json(['status' => 0, 'msg' => 'Periksa isian yang ditandai', 'errors' => ['surat' => $fail]], 422);

        $res = $this->qtn_model->terima_isian($token, $in, $berkas);
        if (empty($res['status'])) { $this->hapus_berkas($berkas); return $this->json($res, !empty($res['closed']) ? 410 : 422); }
        $this->limit_catat();
        return $this->json($res);
    }

    /** Kirim konfirmasi company B2B oleh PIC (multipart, AJAX): data company, daftar peserta, surat bertanda tangan + cap. */
    public function kirim_company($token = '')
    {
        if ($this->input->method() !== 'post') show_404();
        $q = $this->qtn_model->by_token($token);
        if (!$q) return $this->json(['status' => 0, 'msg' => 'Link tidak ditemukan'], 404);
        if ($this->limit_kena()) return $this->json(['status' => 0, 'msg' => 'Terlalu banyak kiriman dari jaringan Anda. Coba lagi beberapa saat.'], 429);

        $in = $this->input->post(null, true) ?: [];
        $rows = $this->input->post('peserta') ?: [];   // [ ['nama'=>..,'jabatan'=>..], ... ]

        $berkas = null; $fail = $this->simpan_berkas('surat', $berkas);
        if ($fail) return $this->json(['status' => 0, 'msg' => 'Periksa isian yang ditandai', 'errors' => ['surat' => $fail]], 422);

        $res = $this->qtn_model->terima_company($token, $in, is_array($rows) ? $rows : [], $berkas);
        if (empty($res['status'])) { $this->hapus_berkas($berkas); return $this->json($res, !empty($res['closed']) ? 410 : 422); }
        $this->limit_catat();
        return $this->json($res);
    }

    /**
     * Cetak surat konfirmasi terisi (PDF) dari data yang diketik, tanggal = hari cetak (QTN-FR-16, QTN-FR-30).
     * Dibuat sebelum kirim, jadi tidak menyimpan apa pun. Mengikuti status link: link tertutup tidak mencetak.
     */
    public function cetak($token = '')
    {
        if ($this->input->method() !== 'post') show_404();
        $q = $this->qtn_model->by_token($token);
        if (!$q) show_404();
        if (!$this->qtn_model->link_state($q)['buka']) show_error('Formulir ini sudah ditutup.', 410);
        $in = $this->input->post(null, true) ?: [];

        $this->load->library('qtn_pdf');
        if ($q['jenis'] === 'b2b') {
            $rows = $this->input->post('peserta') ?: [];
            $bin = $this->qtn_pdf->surat_company($q, $in, is_array($rows) ? $rows : [], $this->qtn_model->kolom_b2b($q));
            $nama = 'Surat-Konfirmasi-Company-' . $q['nomor'] . '.pdf';
        } else {
            $err = $this->qtn_model->validate_isian($in);
            if ($err) show_error(implode('; ', $err), 422);
            $bin = $this->qtn_pdf->surat_peserta($q, $in);
            $nama = 'Surat-Konfirmasi-' . $q['nomor'] . '.pdf';
        }
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $nama) . '"');
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
    }

    // ------------------------------------------------------------------ berkas surat (QTN-NFR-08)

    /**
     * Pindahkan berkas unggahan ke uploads/konfirmasi_kehadiran/ (ditutup .htaccess; hanya dibuka lewat aplikasi).
     * Tipe ditentukan dari ISI berkas (finfo), bukan ekstensi atau tipe kiriman browser; PDF, JPG, PNG; maks 5 MB; nama acak.
     * Kembalian: pesan galat atau null; $out = ['path','nama','size','tipe'].
     */
    private function simpan_berkas($field, &$out)
    {
        $f = $_FILES[$field] ?? null;
        if (!$f || !isset($f['error']) || is_array($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return 'Unggah surat konfirmasi yang sudah ditandatangani';
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) return 'Berkas lebih dari 5 MB';
        if ($f['error'] !== UPLOAD_ERR_OK) return 'Berkas gagal diunggah, coba lagi';
        if ($f['size'] > Quotation_model::FILE_MAX) return 'Berkas lebih dari 5 MB';
        if (!is_uploaded_file($f['tmp_name'])) return 'Berkas tidak valid';

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $peta = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($peta[$mime])) return 'Hanya berkas PDF, JPG, atau PNG';
        $ext = $peta[$mime];
        $dir = FCPATH . 'uploads/konfirmasi_kehadiran/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        if (!is_file($dir . '.htaccess')) @file_put_contents($dir . '.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        $rand = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . $rand)) return 'Berkas gagal disimpan';
        $out = ['path' => 'uploads/konfirmasi_kehadiran/' . $rand, 'nama' => basename((string)$f['name']), 'size' => (int)$f['size'], 'tipe' => $ext];
        return null;
    }

    private function hapus_berkas($b) { if ($b && !empty($b['path'])) @unlink(FCPATH . $b['path']); }

    // ------------------------------------------------------------------ pembatasan laju per IP (QTN-NFR-08)

    private function limit_key() { return 'laju_' . md5($this->input->ip_address()); }

    /** True bila IP sudah melewati batas kiriman BERHASIL per jam. Disimpan di folder cache aplikasi (tanpa tabel baru). */
    private function limit_kena()
    {
        $f = APPPATH . 'cache/' . $this->limit_key();
        if (!is_file($f)) return false;
        $t = array_filter(array_map('intval', explode(',', (string)@file_get_contents($f))), function ($x) { return $x > time() - self::LAJU_DETIK; });
        return count($t) >= self::LAJU_MAKS;
    }

    private function limit_catat()
    {
        $f = APPPATH . 'cache/' . $this->limit_key();
        $t = is_file($f) ? array_filter(array_map('intval', explode(',', (string)@file_get_contents($f))), function ($x) { return $x > time() - self::LAJU_DETIK; }) : [];
        $t[] = time();
        @file_put_contents($f, implode(',', $t), LOCK_EX);
    }

    // ------------------------------------------------------------------ util

    private function sales(array $q)
    {
        return $this->db->query('SELECT u.nm_lengkap AS nama, COALESCE(k.email, u.email) AS email, COALESCE(k.telp, u.hp) AS telp FROM users u '
            . 'LEFT JOIN ms_user_karyawan k ON k.id_user = u.id_user WHERE u.id_user = ?', [(int)$q['id_sales']])->row_array() ?: [];
    }

    private function json($data, $code = 200)
    {
        return $this->output->set_status_header($code)->set_content_type('application/json')->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
