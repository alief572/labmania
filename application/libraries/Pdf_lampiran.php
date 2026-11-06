<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menyisipkan berkas lampiran (gambar JPG/PNG dan halaman PDF) ke dokumen mPDF, masing-masing di halaman baru.
 * Gambar: diskalakan muat satu halaman A4. PDF: setiap halamannya diimpor (FPDI bawaan mPDF) pada ukuran aslinya.
 * Berkas hilang / PDF tidak dapat dibaca (mis. terenkripsi) diganti halaman keterangan agar tidak menggagalkan seluruh cetakan.
 */
class Pdf_lampiran
{
    // Area gambar pada A4 dengan margin dokumen 15 mm (kiri/kanan) dan 15/18 mm (atas/bawah), sisakan ruang keterangan.
    const MAX_W = 180;
    const MAX_H = 240;

    /**
     * @param \Mpdf\Mpdf $mpdf   dokumen yang sedang disusun
     * @param array      $rows   baris lampiran: dokumen, tahun, file_path
     * @param string     $dir    folder berkas (berakhir '/')
     * @return int jumlah lampiran yang tersisip
     */
    public function append($mpdf, array $rows, $dir)
    {
        $mpdf->SetHTMLFooter(''); // halaman lampiran tidak diberi kaki halaman agar tidak menimpa isi berkas
        $n = 0;
        foreach (array_values($rows) as $i => $r) {
            $file = basename((string)($r['file_path'] ?? ''));
            if ($file === '') continue; // baris tanpa berkas: tidak ada yang disisipkan
            $caption = 'Lampiran ' . ($i + 1) . ' — ' . ($r['dokumen'] ?? '') . (trim((string)($r['tahun'] ?? '')) !== '' ? ' (' . $r['tahun'] . ')' : '');
            $path = $dir . $file;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            try {
                if (!is_file($path)) throw new RuntimeException('Berkas tidak ditemukan');
                if ($ext === 'pdf') $this->add_pdf($mpdf, $path, $caption);
                elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true)) $this->add_image($mpdf, $path, $caption);
                else throw new RuntimeException('Jenis berkas tidak dapat ditampilkan');
                $n++;
            } catch (Throwable $e) {
                $mpdf->AddPage();
                $mpdf->WriteHTML('<p style="font-size:9pt;color:#6E7390;">' . htmlspecialchars($caption) . '</p>'
                    . '<p style="font-size:10pt;">Berkas lampiran tidak dapat disisipkan (' . htmlspecialchars($e->getMessage()) . ').</p>');
            }
        }
        return $n;
    }

    private function add_image($mpdf, $path, $caption)
    {
        $size = @getimagesize($path);
        if (!$size || $size[0] < 1 || $size[1] < 1) throw new RuntimeException('Gambar tidak valid');
        $ratio = $size[0] / $size[1];
        $w = min(self::MAX_W, self::MAX_H * $ratio);
        $h = $w / $ratio;

        $mpdf->AddPage();
        $mpdf->WriteHTML('<p style="font-size:9pt;color:#6E7390;margin:0 0 3mm 0;">' . htmlspecialchars($caption) . '</p>'
            . '<div style="text-align:center;"><img src="' . $path . '" style="width:' . round($w, 1) . 'mm;height:' . round($h, 1) . 'mm;"></div>');
    }

    private function add_pdf($mpdf, $path, $caption)
    {
        $pages = $mpdf->setSourceFile($path);
        for ($p = 1; $p <= $pages; $p++) {
            $tpl = $mpdf->importPage($p);
            $s = $mpdf->getTemplateSize($tpl);
            $mpdf->AddPageByArray([
                'orientation' => $s['width'] > $s['height'] ? 'L' : 'P',
                'sheet-size'  => [$s['width'], $s['height']],
                'margin-left' => 0, 'margin-right' => 0, 'margin-top' => 0, 'margin-bottom' => 0,
            ]);
            $mpdf->useTemplate($tpl);
            if ($p === 1) { // keterangan kecil di tepi atas halaman pertama berkas
                $mpdf->SetFont('dejavusans', '', 7);
                $mpdf->SetTextColor(110, 115, 144);
                $mpdf->Text(5, 4, $caption);
                $mpdf->SetTextColor(3, 6, 55);
            }
        }
    }
}
