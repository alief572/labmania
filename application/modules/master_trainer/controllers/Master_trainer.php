<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_trainer extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index' => ['master_trainer', 'lihat'],
        'add' => ['master_trainer', 'tambah'],
        'edit' => ['master_trainer', 'ubah'],
        'save' => ['master_trainer', 'simpan'],
        'toggle' => ['master_trainer', 'aktif', true],
        'cv' => ['master_trainer', 'lihat'],
        'cv_pdf' => ['master_trainer', 'lihat'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Master_trainer_model', 'trainer_model');
        $this->load->model('master_data/Master_data_model', 'md_model');
    }

    public function index()
    {
        $this->template->title('Master Trainer');
        $this->template->set('breadcrumbs', 'Master Data / <b>Master Trainer</b>');

        $data = [
            'trainers' => $this->md_model->with_audit($this->trainer_model->get_trainers(), 'ms_trainer')
        ];
        $this->template->render('index', $data);
    }

    public function add()
    {
        $this->template->title('Tambah Trainer');
        $this->template->set('breadcrumbs', 'Master Data / Master Trainer / <b>Tambah Trainer</b>');

        $data = [
            'trainer' => $this->session->flashdata('old_input') ?: null, // isian yang gagal disimpan dikembalikan ke form
            'is_new'  => true
        ];
        $this->template->render('form', $data);
    }

    public function edit($id)
    {
        $trainer = $this->trainer_model->get_trainers($id);
        if (!$trainer) {
            $this->session->set_flashdata('error', 'Data Trainer tidak ditemukan');
            redirect('master_trainer');
        }

        $old = $this->session->flashdata('old_input');
        if ($old) {
            $trainer = array_merge($trainer, $old, ['id' => $trainer['id'], 'kode' => $trainer['kode']]);
        }

        $this->template->title('Edit Trainer ' . $trainer['kode']);
        $this->template->set('breadcrumbs', 'Master Data / Master Trainer / <b>Edit Trainer</b>');

        $data = [
            'trainer' => $trainer,
            'is_new'  => false
        ];
        $this->template->render('form', $data);
    }

    public function save()
    {
        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int)$post['id'] : null;
        $back = $id ? 'master_trainer/edit/' . $id : 'master_trainer/add';

        if ($id && !$this->trainer_model->get_trainers($id)) {
            $this->session->set_flashdata('error', 'Data Trainer tidak ditemukan');
            redirect('master_trainer');
        }

        $err = $this->trainer_model->validate_trainer($post);
        if ($err) $this->back_with_error($err, $post, $back);

        // Berkas lampiran per baris (lampiran_file[i]): PDF/JPG/PNG, maks 5 MB (Q-27, opsional).
        $uploaded = [];
        $fail = $this->upload_lampiran($post, $uploaded);
        if ($fail) {
            foreach ($uploaded as $u) @unlink($u['full_path']);
            $this->back_with_error($fail, $post, $back);
        }

        $res = $this->trainer_model->save_trainer($post, $uploaded, $id);
        if (empty($res['status'])) {
            foreach ($uploaded as $u) @unlink($u['full_path']); // simpan gagal: berkas yang baru diunggah dibuang
            $this->back_with_error($res['msg'], $post, $back);
        }

        // Berkas lama yang tak dipakai lagi (diganti / baris dihapus) dibuang setelah simpan sukses.
        foreach ($res['obsolete'] as $f) {
            $path = FCPATH . 'uploads/trainers/' . basename((string)$f);
            if (is_file($path)) @unlink($path);
        }

        $this->session->set_flashdata('success', $res['msg']);
        redirect('master_trainer');
    }

    /** Unggah $_FILES['lampiran_file'][i]; isi $uploaded[i] = data upload. Kembalian: pesan galat atau null. */
    private function upload_lampiran($post, &$uploaded)
    {
        if (empty($_FILES['lampiran_file']['name']) || !is_array($_FILES['lampiran_file']['name'])) return null;

        $dir = FCPATH . 'uploads/trainers/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $this->load->library('upload');

        foreach ($_FILES['lampiran_file']['name'] as $i => $name) {
            if ($name === '' || $_FILES['lampiran_file']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            // Berkas untuk baris yang kosong (tanpa nama dokumen) diabaikan, sama seperti barisnya.
            if (trim($post['lampiran'][$i]['dokumen'] ?? '') === '') continue;

            $_FILES['lampiran_one'] = [
                'name' => $name, 'type' => $_FILES['lampiran_file']['type'][$i], 'tmp_name' => $_FILES['lampiran_file']['tmp_name'][$i],
                'error' => $_FILES['lampiran_file']['error'][$i], 'size' => $_FILES['lampiran_file']['size'][$i],
            ];
            $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'pdf|jpg|jpeg|png', 'max_size' => 5120, 'encrypt_name' => true]);
            // finfo_close() di system/libraries/Upload.php deprecated sejak PHP 8.5; notice-nya merusak redirect di mode development.
            $level = error_reporting();
            error_reporting($level & ~E_DEPRECATED);
            $ok = $this->upload->do_upload('lampiran_one');
            error_reporting($level);
            if (!$ok) return 'Lampiran baris ' . ((int)$i + 1) . ': ' . $this->upload->display_errors('', '');
            $uploaded[$i] = $this->upload->data();
        }
        return null;
    }

    // Kembali ke form dengan pesan galat dan isian terakhir (berkas tidak dapat dikembalikan, harus dipilih ulang).
    private function back_with_error($msg, $post, $back)
    {
        $this->session->set_flashdata('error', $msg);
        $this->session->set_flashdata('old_input', $post);
        redirect($back);
    }

    public function toggle($id)
    {
        $res = $this->trainer_model->toggle_trainer($id);
        return $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function cv($id)
    {
        $trainer = $this->trainer_model->get_trainers($id);
        if (!$trainer) {
            show_404();
        }

        $this->template->title('CV Trainer — ' . $trainer['nama']);
        $this->template->set('breadcrumbs', 'Master Data / Master Trainer / <b>CV Fasilitator</b>');

        $this->template->render('cv', ['t' => $trainer] + $this->cv_sections($trainer));
    }

    /** CV Trainer sebagai PDF (A4) — isi dan urutan sama dengan halaman cv (FR-47). Dibuka inline di tab baru. */
    public function cv_pdf($id)
    {
        $trainer = $this->trainer_model->get_trainers($id);
        if (!$trainer) {
            show_404();
        }

        $html = $this->load->view('cv_pdf', ['t' => $trainer] + $this->cv_sections($trainer), true);

        // mPDF 8.3 di PHP 8.5 dapat memunculkan notice deprecated; notice di keluaran akan merusak berkas PDF.
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED);

        $tmp = sys_get_temp_dir() . '/labmania_mpdf'; // dibuat oleh user web sendiri; mPDF menambah subfolder /mpdf di dalamnya
        if (!is_dir($tmp)) @mkdir($tmp, 0775, true);

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4', 'orientation' => 'P', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 10,
            'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 15, 'margin_bottom' => 18, 'margin_footer' => 8,
        ]);
        $mpdf->SetTitle('CV Trainer — ' . $trainer['nama']);
        $mpdf->SetAuthor('Labmania');
        $mpdf->SetHTMLFooter('<div style="font-size:8pt; color:#6E7390; border-top:0.2mm solid #D3DCE8; padding-top:2mm;">'
            . htmlspecialchars($trainer['kode']) . ' · ' . htmlspecialchars($trainer['nama']) . '<span style="float:right;">Halaman {PAGENO} dari {nbpg}</span></div>');
        $mpdf->WriteHTML($html);

        // Berkas Lampiran Dokumen ditampilkan langsung di halaman baru setelah CV (gambar apa adanya, PDF per halaman).
        $this->load->library('pdf_lampiran');
        $this->pdf_lampiran->append($mpdf, $trainer['lampiran'] ?? [], FCPATH . 'uploads/trainers/');

        while (ob_get_level()) ob_end_clean();
        $file = 'CV-' . $trainer['kode'] . '-' . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $trainer['nama']), '-') . '.pdf';
        $mpdf->Output($file, \Mpdf\Output\Destination::INLINE);
        error_reporting($level);
        exit;
    }

    /** Bagian CV dalam urutan Excel (FR-47), dipakai bersama halaman cv dan PDF: ringkasan identitas + empat daftar bernomor. */
    private function cv_sections($t)
    {
        $or  = function ($v) { return trim((string)$v) === '' ? '—' : (string)$v; };
        $tgl = function ($d) { return !empty($d) ? date('d-m-Y', strtotime($d)) : '—'; };
        $keahlian = Master_trainer_model::split_keahlian($t['bidang_keahlian'] ?? '');

        $summary = [
            ['Nama lengkap', $t['nama']],
            ['Tempat, tanggal lahir', $or($t['tempat_lahir']) . ', ' . $tgl($t['tgl_lahir'])],
            ['Alamat rumah', $or($t['alamat'])],
            ['No. telepon rumah/HP', $or($t['no_telp'])],
            ['Pendidikan — SMU/sederajat', $or($t['pend_smu'])],
            ['Pendidikan — Diploma', $or($t['pend_diploma'])],
            ['Pendidikan — Sarjana', $or($t['pend_sarjana'])],
            ['Pendidikan — Pascasarjana', $or($t['pend_pascasarjana'])],
            ['Bidang keahlian', $keahlian ? implode('; ', $keahlian) : '—'],
            ['Pengalaman lainnya', $or($t['pengalaman_lain'])],
            ['Pengesahan CV', $or($t['cv_tempat']) . ', ' . $tgl($t['cv_tanggal'])],
            ['Nama pada tanda tangan', $or($t['nama_ttd'])],
        ];
        $lists = [
            ['pelatihan', 'Pelatihan & Seminar', [['judul', 'Judul Pelatihan/Seminar/Lainnya'], ['penyelenggara', 'Penyelenggara/Tempat'], ['waktu', 'Waktu']]],
            ['pengalaman_kerja', 'Pengalaman Kerja', [['jabatan', 'Jabatan'], ['institusi', 'Institusi/Perusahaan'], ['tahun', 'Tahun']]],
            ['pengalaman_mengajar', 'Pengalaman Mengajar', [['topik', 'Topik Pembelajaran'], ['tempat', 'Tempat'], ['tahun', 'Tahun']]],
            ['lampiran', 'Lampiran Dokumen', [['dokumen', 'Dokumen / Sertifikat Lampiran'], ['tahun', 'Tahun'], ['file_path', 'File']]],
        ];
        return ['summary' => $summary, 'lists' => $lists];
    }
}
