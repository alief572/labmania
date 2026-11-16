<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * PDF CV Trainer (FR-47), setara Master_trainer::cv_pdf di CI3: ringkasan identitas, empat daftar bernomor, lalu berkas
 * Lampiran Dokumen disisipkan satu per halaman (gambar muat A4, PDF diimpor per halaman). Berkas hilang atau tidak
 * terbaca diganti halaman keterangan agar cetakan tidak gagal. Berkas dicari di root privat lalu root legacy.
 */
class TrainerCvPdf
{
    private const MAX_W = 180;

    private const MAX_H = 240;

    private const LISTS = [
        ['pelatihan', 'Pelatihan & Seminar', 'ms_trainer_pelatihan', [['judul', 'Judul Pelatihan/Seminar/Lainnya'], ['penyelenggara', 'Penyelenggara/Tempat'], ['waktu', 'Waktu']]],
        ['pengalaman_kerja', 'Pengalaman Kerja', 'ms_trainer_pengalaman_kerja', [['jabatan', 'Jabatan'], ['institusi', 'Institusi/Perusahaan'], ['tahun', 'Tahun']]],
        ['pengalaman_mengajar', 'Pengalaman Mengajar', 'ms_trainer_pengalaman_mengajar', [['topik', 'Topik Pembelajaran'], ['tempat', 'Tempat'], ['tahun', 'Tahun']]],
        ['lampiran', 'Lampiran Dokumen', 'ms_trainer_lampiran', [['dokumen', 'Dokumen / Sertifikat Lampiran'], ['tahun', 'Tahun'], ['file_path', 'File']]],
    ];

    /** Nama berkas unduhan: CV-<kode>-<nama>.pdf */
    public static function filename(object $t): string
    {
        return 'CV-'.$t->kode.'-'.trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $t->nama), '-').'.pdf';
    }

    public static function sections(object $t): array
    {
        $or = fn ($v) => trim((string) $v) === '' ? '—' : (string) $v;
        $tgl = fn ($d) => $d ? date('d-m-Y', strtotime((string) $d)) : '—';
        $keahlian = array_values(array_filter(array_map('trim', preg_split('/[\r\n;]+/', (string) $t->bidang_keahlian)), 'strlen'));
        $summary = [
            ['Nama lengkap', $t->nama], ['Tempat, tanggal lahir', $or($t->tempat_lahir).', '.$tgl($t->tgl_lahir)], ['Alamat rumah', $or($t->alamat)],
            ['No. telepon rumah/HP', $or($t->no_telp)], ['Pendidikan — SMU/sederajat', $or($t->pend_smu)], ['Pendidikan — Diploma', $or($t->pend_diploma)],
            ['Pendidikan — Sarjana', $or($t->pend_sarjana)], ['Pendidikan — Pascasarjana', $or($t->pend_pascasarjana)],
            ['Bidang keahlian', $keahlian ? implode('; ', $keahlian) : '—'], ['Pengalaman lainnya', $or($t->pengalaman_lain)],
            ['Pengesahan CV', $or($t->cv_tempat).', '.$tgl($t->cv_tanggal)], ['Nama pada tanda tangan', $or($t->nama_ttd)],
        ];
        $lists = [];
        foreach (self::LISTS as [$key, $title, $table, $cols]) {
            $lists[] = ['key' => $key, 'title' => $title, 'cols' => $cols, 'rows' => DB::table($table)->where('id_trainer', $t->id)->orderBy('urutan')->get()->map(fn ($r) => (array) $r)->all()];
        }

        return [$summary, $lists];
    }

    /** Path absolut berkas lampiran di bawah uploads/trainers, atau null bila tidak ada / mencoba keluar folder. */
    public static function locate(string $stored): ?string
    {
        $name = basename(str_replace('\\', '/', $stored));
        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }
        foreach (array_filter([config('labmania.document_root'), config('labmania.legacy_document_root')]) as $root) {
            $base = realpath($root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'trainers');
            $path = $base ? realpath($base.DIRECTORY_SEPARATOR.$name) : false;
            if ($path && str_starts_with($path, $base.DIRECTORY_SEPARATOR) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function make(object $t): string
    {
        [$summary, $lists] = self::sections($t);
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED); // mPDF di PHP 8.5 memunculkan deprecated yang merusak keluaran PDF
        try {
            $tmp = storage_path('app/mpdf');
            if (! is_dir($tmp)) {
                mkdir($tmp, 0775, true);
            }
            $m = new Mpdf(['format' => 'A4', 'orientation' => 'P', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 10,
                'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 15, 'margin_bottom' => 18, 'margin_footer' => 8]);
            $m->SetTitle('CV Trainer — '.$t->nama);
            $m->SetAuthor('Labmania');
            $m->SetHTMLFooter('<div style="font-size:8pt;color:#6E7390;border-top:0.2mm solid #D3DCE8;padding-top:2mm;">'.e($t->kode).' · '.e($t->nama).'<span style="float:right;">Halaman {PAGENO} dari {nbpg}</span></div>');
            $m->WriteHTML(view('pdf.trainer-cv', ['t' => $t, 'summary' => $summary, 'lists' => $lists])->render());
            self::attachments($m, collect($lists)->firstWhere('key', 'lampiran')['rows']);

            return $m->Output('', Destination::STRING_RETURN);
        } finally {
            error_reporting($level);
        }
    }

    private static function attachments(Mpdf $m, array $rows): void
    {
        $m->SetHTMLFooter(''); // halaman lampiran tanpa kaki halaman agar tidak menimpa isi berkas
        foreach (array_values($rows) as $i => $r) {
            if (trim((string) ($r['file_path'] ?? '')) === '') {
                continue;
            }
            $caption = 'Lampiran '.($i + 1).' — '.($r['dokumen'] ?? '').(trim((string) ($r['tahun'] ?? '')) !== '' ? ' ('.$r['tahun'].')' : '');
            try {
                $path = self::locate((string) $r['file_path']) ?? throw new \RuntimeException('Berkas tidak ditemukan');
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                match ($ext) {
                    'pdf' => self::addPdf($m, $path, $caption),
                    'jpg', 'jpeg', 'png' => self::addImage($m, $path, $caption),
                    default => throw new \RuntimeException('Jenis berkas tidak dapat ditampilkan'),
                };
            } catch (\Throwable $e) {
                $m->AddPage();
                $m->WriteHTML('<p style="font-size:9pt;color:#6E7390;">'.e($caption).'</p><p style="font-size:10pt;">Berkas lampiran tidak dapat disisipkan ('.e($e->getMessage()).').</p>');
            }
        }
    }

    private static function addImage(Mpdf $m, string $path, string $caption): void
    {
        $size = @getimagesize($path);
        if (! $size || $size[0] < 1 || $size[1] < 1) {
            throw new \RuntimeException('Gambar tidak valid');
        }
        $ratio = $size[0] / $size[1];
        $w = min(self::MAX_W, self::MAX_H * $ratio);
        $m->AddPage();
        $m->WriteHTML('<p style="font-size:9pt;color:#6E7390;margin:0 0 3mm 0;">'.e($caption).'</p><div style="text-align:center;"><img src="'.$path.'" style="width:'.round($w, 1).'mm;height:'.round($w / $ratio, 1).'mm;"></div>');
    }

    private static function addPdf(Mpdf $m, string $path, string $caption): void
    {
        $pages = $m->setSourceFile($path);
        for ($p = 1; $p <= $pages; $p++) {
            $tpl = $m->importPage($p);
            $s = $m->getTemplateSize($tpl);
            $m->AddPageByArray(['orientation' => $s['width'] > $s['height'] ? 'L' : 'P', 'sheet-size' => [$s['width'], $s['height']],
                'margin-left' => 0, 'margin-right' => 0, 'margin-top' => 0, 'margin-bottom' => 0]);
            $m->useTemplate($tpl);
            if ($p === 1) {
                $m->SetFont('dejavusans', '', 7);
                $m->SetTextColor(110, 115, 144);
                $m->Text(5, 4, $caption);
                $m->SetTextColor(3, 6, 55);
            }
        }
    }
}
