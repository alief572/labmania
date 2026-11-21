<?php

namespace App\Support;

use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/** Layout PDF dipertahankan dari Qtn_pdf CI3; akses data memakai koneksi Laravel terpisah. */
class QuotationPdf
{
    private $level = 0;

    private function extra(array $q): array
    {
        $p = Sales::program($q['id_penjualan']);
        $numbers = DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')->where('m.id_master_program', $p->id_master_program)->orderBy('m.urutan')->pluck('p.nomor')->implode(', ');
        $sales = DB::table('ms_user_karyawan')->where('id', $q['id_sales'])->first();

        return [['nomor' => $numbers, 'judul' => Sales::title($p->id_master_program)],
            ['nm_lengkap' => $sales?->nama, 'email' => $sales?->email, 'telp' => $sales?->telp, 'divisi' => $sales?->division ?: $sales?->department],
            $q['id_template_konfirmasi'] ? (array) DB::table('ms_template_konfirmasi')->where('id', $q['id_template_konfirmasi'])->first() : []];
    }

    private static function enrich(object $q, object $p): array
    {
        $data = (array) $q;
        $data['nomor'] = Sales::display($q);
        foreach (['tgl_mulai', 'tgl_selesai', 'durasi_hari', 'jam_pembelajaran', 'waktu', 'lokasi'] as $field) {
            $data[$field] = $p->$field;
        }
        $data['jenis'] = $data['jenis_pj'] = $p->jenis;
        $data['nama_paket'] = Sales::title($p->id_master_program);
        $all = Sales::masters($p);
        $data['terdiri_dari'] = count($all) > 1 ? array_map(fn ($m) => ['judul' => Sales::title((int) $m['id_master_program']), 'tgl_mulai' => $m['tgl_mulai'], 'tgl_selesai' => $m['tgl_selesai'], 'jam_pembelajaran' => (int) $m['jam_pembelajaran']], $all) : [];
        $data['nama_company'] = $q->id_customer ? DB::table('master_customer')->where('id_customer', $q->id_customer)->value('name_customer') : null;

        return $data;
    }

    public static function response(string $pdf, string $name)
    {
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$name.'"', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public static function program(object $p): string
    {
        $p = clone $p;
        $p->nomor = Sales::display($p);
        $level = error_reporting();
        try {
            $layout = new self;
            $m = $layout->mulai('Surat Penawaran '.$p->nomor);
            $masters = Sales::masters($p);
            $parts = count($masters) > 1 ? array_map(fn ($m) => ['judul' => Sales::title((int) $m['id_master_program']), 'tgl_mulai' => $m['tgl_mulai'], 'tgl_selesai' => $m['tgl_selesai'],
                'jam_pembelajaran' => $m['jam_pembelajaran'], 'trainer' => DB::table('ms_trainer')->where('id', $m['id_trainer'])->value('nama')], $masters) : [];
            $html = view('pdf.program-offer', ['p' => $p, 'title' => Sales::title($p->id_master_program), 'parts' => $parts,
                'company' => $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->first() : null,
                'trainer' => DB::table('ms_trainer')->where('id', $p->id_trainer)->value('nama')])->render();
            $layout->page($m, $html, $layout->foot('Penjualan Program', '{PAGENO}', now()->format('d/m/Y')));

            return $layout->selesai($m);
        } finally {
            error_reporting($level);
        }
    }

    public static function offer(object $q, object $p, ?array $details = null, ?array $termin = null, ?string $previewDocument = null): string
    {
        $data = self::enrich($q, $p);
        $data['termin'] = $termin ?? Quotation::termin($q);
        $data['keterangan'] = $details ?? DB::table('quotation_keterangan')->where('id_quotation', $q->id)->orderBy('urutan')->get()->map(fn ($x) => (array) $x)->all();
        $data['peserta'] = $q->id ? DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'deal')->get()->map(fn ($x) => ['nama_peserta' => $x->nama])->all() : [];
        $calc = Quotation::calculate($q, $q->id ? Quotation::count($q) : (int) $q->kuota_peserta);
        $data['calc'] = $calc + ['skema' => $q->skema_harga, 'harga_allin' => $q->harga_allin, 'harga_peserta' => $q->harga_peserta, 'total_diskon' => $calc['diskon'], 'ppn_persen' => $q->is_ppn ? $q->ppn_persen : 0, 'ppn_nominal' => $calc['ppn'], 'paket_maks' => $q->paket_maks_peserta, 'tarif_tambahan' => $q->tambahan_per_peserta];
        $data['calc']['diskon'] = $q->diskon;
        $level = error_reporting();
        try {
            $layout = new self;

            return $previewDocument ? $layout->draftPreview($data, $previewDocument) : $layout->render($data);
        } finally {
            error_reporting($level);
        }
    }

    public static function confirmation(object $q, object $p, array $in): string
    {
        $data = self::enrich($q, $p);
        $level = error_reporting();
        try {
            $layout = new self;

            return $p->jenis === 'b2b' ? $layout->surat_company($data, $in, $in['peserta'], json_decode($q->kolom_peserta_b2b ?: '[]', true)) : $layout->surat_peserta($data, $in);
        } finally {
            error_reporting($level);
        }
    }

    public static function document(string $title, string $html, string $footer = '{PAGENO} / {nbpg}', ?string $watermark = null): string
    {
        $level = error_reporting();
        try {
            $layout = new self;
            $m = $layout->mulai($title);
            if ($watermark !== null) {
                $m->SetWatermarkText($watermark);
                $m->showWatermarkText = true;
            }
            $m->SetHTMLHeader(str_replace(' · Customer Care:', ' · Email: labjourneypartner@labmaniaindonesia.id<br>Customer Care:', $layout->kop()));
            $layout->page($m, $html, '<div style="text-align:center;font-size:8pt">'.htmlspecialchars($footer, ENT_QUOTES, 'UTF-8').'</div>');

            return $layout->selesai($m);
        } finally {
            error_reporting($level);
        }
    }

    public static function batch(object $p, ?int $quotationId = null, bool $dealOnly = false): string
    {
        $p = clone $p;
        $p->nomor = Sales::display($p);
        $rows = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $p->id)->when($quotationId, fn ($q) => $q->where('q.id', $quotationId))->whereIn('x.status', $dealOnly ? ['deal'] : ['deal', 'menunggu'])->orderBy('x.id')->get(['x.*', 'q.nomor as nomor_quotation', 'q.revisi']);
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['pdf' => 'Belum ada peserta aktif untuk dicetak.']);
        }
        $layout = new self;
        $level = error_reporting();
        try {
            $m = $layout->mulai('Data Peserta '.$p->nomor);
            $html = '<h2 class="title">Daftar Peserta</h2><p>'.$layout->e($p->nomor.' · '.Sales::title($p->id_master_program).' · '.$layout->rentang($p->tgl_mulai, $p->tgl_selesai).' · '.$p->lokasi).'</p><table class="tbl participant-list"><thead><tr><th>No</th><th>Nama Lengkap</th><th>Instansi</th><th>Kontak</th><th>Quotation</th><th>Status</th></tr></thead><tbody>';
            foreach ($rows as $i => $r) {
                $r->nomor_quotation = Sales::display((object) ['nomor' => $r->nomor_quotation, 'revisi' => $r->revisi]);
                $html .= '<tr><td>'.($i + 1).'</td><td>'.$layout->e($r->nama).'</td><td>'.$layout->e($r->instansi).'</td><td>'.$layout->e(($r->no_wa ?: $r->no_hp).' '.$r->email).'</td><td>'.$layout->e($r->nomor_quotation).'</td><td>'.$layout->e($r->status === 'deal' ? 'Deal' : 'Menunggu').'</td></tr>';
            }
            $layout->page($m, $html.'</tbody></table><p>'.$rows->count().' peserta. Halaman berikutnya: surat konfirmasi bertanda tangan yang diunggah peserta, urut sesuai daftar.</p>', $layout->foot('Data Peserta', '{PAGENO}', now()->format('d/m/Y')));
            $seen = [];
            foreach ($rows as $r) {
                $relative = $r->surat_konfirmasi;
                if ($r->sumber === 'company') {
                    if (isset($seen[$r->id_quotation])) {
                        continue;
                    } $seen[$r->id_quotation] = true;
                    $relative = DB::table('quotation_konfirmasi_company')->where('id_quotation', $r->id_quotation)->where('revisi', $r->revisi)->value('surat_konfirmasi');
                }
                if (! $relative) {
                    throw ValidationException::withMessages(['pdf' => 'Surat konfirmasi peserta #'.$r->id.' ('.$r->nomor_quotation.') belum tersedia.']);
                }
                try {
                    $path = QuotationFiles::path($relative);
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                        QuotationFiles::validateImage($path);
                        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                        $layout->page($m, '<p>'.$layout->e($r->nomor_quotation).'</p><img src="data:'.$mime.';base64,'.base64_encode(file_get_contents($path)).'" style="max-width:160mm;max-height:220mm;">', $layout->foot('Surat Konfirmasi', '{PAGENO}', now()->format('d/m/Y')));
                    } else {
                        $pages = $m->setSourceFile($path);
                        for ($i = 1; $i <= $pages; $i++) {
                            $tpl = $m->importPage($i);
                            $size = $m->getTemplateSize($tpl);
                            $m->SetHTMLHeader('');
                            $m->SetHTMLFooter('');
                            $m->AddPageByArray(['orientation' => $size['orientation'], 'newformat' => [$size['width'], $size['height']]]);
                            $m->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                        }
                    }
                } catch (\Throwable $e) {
                    throw ValidationException::withMessages(['pdf' => 'Surat peserta #'.$r->id.' tidak dapat digabung (hilang, rusak, terenkripsi, atau format PDF tidak didukung). Unduh surat asli atau ganti dengan berkas valid.']);
                }
            }

            return $layout->selesai($m);
        } finally {
            error_reporting($level);
        }
    }

    const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const KET_PDF = ['syarat' => 'Syarat keikutsertaan', 'termasuk' => 'Biaya sudah termasuk mendapatkan', 'tidak' => 'Biaya tidak termasuk', 'dokumen' => 'Dokumen persyaratan'];

    const SNK = [
        ['Pembatalan Kepesertaan, Penarikan dan Pengembalian Uang', [
            'Pembatalan kepesertaan tidak bisa dilakukan jika pelanggan telah mengirimkan penawaran harga yang telah ditandatangani dan formulir konfirmasi keikutsertaan kegiatan yang telah diisi (khusus in house training tidak perlu formulir konfirmasi keikutsertaan, melainkan hanya dari penawaran harga yang sudah ditandatangan) dan/atau telah melakukan pembayaran.',
            'Jika pelanggan membatalkan pesanan layanan pembelajaran dan telah membayar, maka biaya layanan pembelajaran yang telah dibayarkan tidak bisa ditarik kembali.',
            'Jika pelanggan telah mengonfirmasi pesanan melalui penawaran harga yang telah ditandatangani dan formulir konfirmasi keikutsertaan kegiatan yang telah diisi (khusus in house training tidak perlu formulir konfirmasi keikutsertaan, melainkan hanya dari penawaran harga yang sudah ditandatangan) dan belum melakukan pembayaran, namun dibatalkan sepihak tanpa ada konfirmasi kepada penyelenggara setelah H-7 kegiatan layanan pembelajaran, maka akan dikenakan biaya pembatalan sebesar 20% dari total biaya layanan pembelajaran.',
            'Pengembalian uang bisa dilakukan jika LabMania membatalkan layanan pembelajaran atau mengubah jadwal layanan pembelajaran dikarenakan kuota peserta belum memenuhi atau ada hal teknis lainnya.',
            'Jika peserta tidak hadir pada pembelajaran atau membatalkan pesanan setelah batas waktu yang telah ditentukan, maka tidak ada pengembalian uang.']],
        ['Pembayaran', [
            'Apabila pembayaran pelanggan menggunakan pajak, maka pelanggan <b>wajib</b> membayarkan pajak (PPN &amp; PPh) sesuai dengan ketentuan perpajakan yang berlaku di Indonesia dan menyerahkan bukti potong/setor pajak tersebut ke LabMania dalam bentuk soft copy/hard copy.',
            'Pelanggan yang melakukan pembayaran menggunakan sistem <i>guarantee letter</i> (<i>pay later</i>) maka, pelanggan <b>wajib</b> melakukan pelunasan dengan batas waktu sesuai dengan yang tercantum di guarantee letter. Apabila pembayaran dilakukan melebihi batas waktu yang telah ditetapkan sebelumnya maka, akan dikenakan <b>denda</b> dengan ketentuan sebagai berikut:<br>- Terlambat 1-7 hari : 10% dari nilai project<br>- Terlambat &gt;7 hari : 30% dari nilai project',
            'Pelanggan yang telah melakukan pembayaran <b>wajib</b> memberikan bukti pembayaran kepada pihak LabMania dalam bentuk soft copy/hard copy.',
            'Pelanggan yang belum menyelesaikan administrasi pembayaran maka, pelanggan tersebut belum dapat mengikuti kegiatan LabMania selanjutnya dan belum dapat menerima sertifikat pelatihan tersebut.']],
        ['Penggantian Barang Rusak/Hilang', [
            'Selama kegiatan pembelajaran peserta <b>wajib</b> menjaga barang milik LabMania yang digunakan selama pembelajaran.',
            'Jika peserta selama pembelajaran merusak atau menghilangkan barang milik LabMania yang dapat menimbulkan kerugian, sehingga mengganggu/ menghambat kegiatan pembelajaran, maka akan dikenakan biaya ganti rugi terhadap barang yang rusak/ hilang.',
            'Setiap terjadi kerusakan atau kehilangan barang, peserta <b>wajib</b> mengisi berita acara dan menyetujui penggantian biaya ganti rugi sesuai dengan nilai dari barang tersebut. Penggantian dilakukan pada hari kegiatan tersebut berlangsung.',
            'Jika peserta tidak melakukan penggantian biaya ganti rugi sesuai dengan waktu yang telah ditentukan, maka sertifikat pelatihan tidak bisa diserahkan.']],
        ['Kerahasiaan', [
            'Peserta <b>wajib</b> menjaga kerahasiaan informasi milik LabMania termasuk modul pembelajaran.',
            'Peserta tidak diperkenankan menyalin atau menduplikasi, mengubah isi dan menyebarluaskan modul pembelajaran tanpa seizin dari pihak LabMania.']],
        ['Sertifikat Pelatihan', [
            'Sertifikat hanya diterbitkan jika peserta telah mengikuti kegiatan pembelajaran dan mengisi <i>post test</i> dengan syarat memenuhi kriteria penilaian minimal.',
            'Sertifikat diserahkan setelah peserta telah melakukan pembayaran layanan pembelajaran.',
            'Sertifikat dikirimkan maksimal 10 hari kerja (senin-jumat) setelah kegiatan, dengan catatan sudah dilakukan pembayaran (lunas).',
            'Jika sertifikat yang telah dicetak, nama yang tertera tidak sesuai karena kesalahan dari peserta dalam memberikan/ menuliskan nama peserta, maka revisi sertifikat diberikan dalam bentuk <i>softcopy</i>. Jika dibutuhkan <i>hardcopy</i>, maka dikenakan biaya sebesar Rp. 30.000,- (belum termasuk biaya pengiriman melalui ekspedisi). Ketentuan ini berlaku jika kegiatan di luar kantor LabMania.',
            'Jika peserta menghilangkan sertifikat pelatihan lebih dari 6 bulan, maka sertifikat dan materi pelatihan tidak bisa diberikan kembali. Jika masih dalam jangka waktu kurang dari 6 bulan, maka peserta masih bisa diberikan <i>softcopy</i> sertifikat dan materi pelatihan.']],
        ['Lisensi', [
            'Kegiatan yang dilakukan di tempat pelanggan, tanggung jawab lisensi terkait perangkat lunak jika digunakan, maka sepenuhnya tanggung jawab dari pelanggan.',
            'LabMania menjamin bahwa setiap perangkat lunak milik LabMania yang digunakan dalam pembelajaran telah terlisensi.']],
        ['Umpan Balik, Saran dan Keluhan', [
            'Setelah pelatihan, setiap peserta harus mengisi umpan balik kuisioner sebagai <i>feedback</i> kepada LabMania dalam peningkatan layanan pembelajaran.',
            'Saran dan keluhan dapat disampaikan melalui kuisioner atau melalui personil LabMania untuk ditindak lanjuti sesuai prosedur di LabMania.']],
    ];

    private function e($v)
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    private function rp($n)
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }

    public function tgl($iso)
    {
        if (! $iso || strlen($iso) < 10) {
            return '—';
        }
        [$y, $m, $d] = explode('-', substr($iso, 0, 10));

        return (int) $d.' '.self::BULAN[(int) $m - 1].' '.$y;
    }

    public function rentang($a, $b)
    {
        if (! $a) {
            return '—';
        }
        if (! $b || $a === $b) {
            return $this->tgl($a);
        }
        [$y1, $m1, $d1] = explode('-', substr($a, 0, 10));
        [$y2, $m2] = explode('-', substr($b, 0, 10));
        if ($y1 === $y2 && $m1 === $m2) {
            return (int) $d1.'–'.$this->tgl($b);
        }

        return $y1 === $y2 ? (int) $d1.' '.self::BULAN[(int) $m1 - 1].' – '.$this->tgl($b) : $this->tgl($a).' – '.$this->tgl($b);
    }

    private function mark($s)
    {
        return preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $this->e($s));
    }

    private function fill($s, array $v)
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($v) {
            return array_key_exists($m[1], $v) ? $v[$m[1]] : $m[0];
        }, (string) $s);
    }

    private function kop()
    {
        return '<table width="100%" style="border-bottom:0.8mm solid #111;"><tr>'
            .'<td width="34%" style="font-size:26pt;font-weight:bold;color:#1F4FD8;">L<span style="color:#F2B705;">A</span>B<br><span style="font-size:8pt;font-weight:normal;letter-spacing:3pt;color:#111;">M A N I A</span></td>'
            .'<td style="font-size:8.5pt;"><b style="font-size:13pt;">PT LabMania Indonesia Jaya</b><br>Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143<br>www.labmaniaindonesia.id · Customer Care: 0821-2429-3839</td>'
            .'</tr></table>';
    }

    private function foot($kode, $hal, $berlaku)
    {
        return '<table width="100%" style="border-top:0.6mm solid #111;font-size:8pt;"><tr><td>'.$this->e($kode).'</td><td align="center"><i>'.$this->e($hal).'</i></td><td align="right">Berlaku '.$this->e($berlaku).'</td></tr></table>';
    }

    private function page(Mpdf $m, $html, $foot)
    {
        $m->AddPage();
        $m->SetHTMLFooter($foot); // footer dirender saat halaman ditutup, jadi diset setelah AddPage
        $m->WriteHTML($html);
    }

    private function css()
    {
        return '<style>
          body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #111; }
          .box { background-color: #E4E6EB; padding: 2mm; }
          .lbl { font-size: 7.5pt; color: #555; }
          table.tbl { border-collapse: collapse; width: 100%; border-bottom: 0.4mm solid #111; }
          table.tbl th { background-color: #173A5E; color: #fff; font-weight: normal; text-align: left; padding: 1.5mm; font-size: 8.5pt; }
          table.tbl td { padding: 2mm 1.5mm 6mm; vertical-align: top; font-size: 8.5pt; }
          table.participant-list td { padding: 1.2mm 1.5mm; }
          table.sum { width: 100%; border-collapse: collapse; background-color: #E4E6EB; }
          table.sum td { padding: 1mm 2mm; border-bottom: 0.3mm solid #fff; }
          table.sum tr.tot td { background-color: #173A5E; color: #fff; font-weight: bold; }
          .mut { color: #666; } .small { font-size: 8pt; } .title { text-align: center; font-size: 11pt; font-weight: bold; }
          ol.snk { padding-left: 5mm; text-align: justify; font-size: 8.5pt; } ol.snk li { margin-bottom: 2mm; }
          table.form td { padding: 1.2mm 1mm; font-size: 9pt; }
        </style>';
    }

    public function render(array $q, $mode = 'all')
    {
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED); // mPDF 8.3 di PHP 8.5; notice di keluaran merusak PDF
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $m = new Mpdf([
            'format' => 'A4', 'orientation' => 'P', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 9,
            'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 38, 'margin_bottom' => 22, 'margin_header' => 8, 'margin_footer' => 8,
        ]);
        $m->SetTitle(($mode === 'konfirmasi' ? 'Confirmation Letter ' : 'Quotation ').$q['nomor']);
        $m->SetAuthor('LabMania');
        $m->SetHTMLHeader($this->kop());
        $m->WriteHTML($this->css(), HTMLParserMode::HEADER_CSS); // CSS saja, tanpa membuat halaman kosong

        [$pk, $sales, $tpl] = $this->extra($q);

        if ($mode !== 'konfirmasi') {
            $this->page($m, $this->penawaran($q, $pk, $sales), $this->foot('FR/LMI-TR-01/01/Ed.Rev-1.1', 'Halaman 1 dari 1', '01/01/2025'));
            [$h1, $h2] = $this->snk();
            $this->page($m, $h1, $this->foot('DP/LMI-TR-01/01/Ed.Rev-1.1', '1 dari 2', '21/10/2024'));
            $this->page($m, $h2, $this->foot('DP/LMI-TR-01/01/Ed.Rev-1.1', '2 dari 2', '21/10/2024'));
        }
        if ($mode === 'konfirmasi' && ($q['jenis_pj'] ?? '') !== 'b2b') {
            // Dipakai export PDF Data Peserta (QTN-FR-29): satu halaman per peserta dari isian link.
            foreach ($q['peserta'] as $p) {
                $this->page($m, $this->konfirmasi($q, $p, $pk, $sales, $tpl), $this->foot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023'));
            }
        }

        $out = $m->Output('', Destination::STRING_RETURN);
        error_reporting($level);

        return $out;
    }

    private function penawaran(array $q, array $pk, array $sales)
    {
        $e = [$this, 'e'];
        $c = $q['calc'];
        // Personal: "Kepada" diisi di form (peserta belum ada saat quotation dibuat, QTN-FR-05).
        $kepada = $q['tujuan'] === 'company' ? ($q['nama_company'] ?? '—') : (($q['kepada_nama'] ?? '') !== '' ? $q['kepada_nama'] : '—');
        $up = $q['tujuan'] === 'company' ? ($q['up_nama'] ?? '') : $kepada;
        // Setelah Deal: daftar nama peserta Deal; sebelumnya peserta belum fix (usulan QTN-FR-14).
        $nama = ($q['status'] ?? '') === 'deal' ? array_filter(array_column($q['peserta'], 'nama_peserta')) : [];
        $allin = $c['skema'] === 'allin';
        $jp = ! empty($q['jam_pembelajaran']) ? (int) $q['jam_pembelajaran'].' JP<br>' : '';

        $desk = (int) $q['durasi_hari'].' hari<br>'.$jp.$this->e($this->rentang($q['tgl_mulai'] ?? null, $q['tgl_selesai'] ?? null)).'<br>'.$this->e($q['waktu'] ?? '—').'<br>'.$this->e($q['lokasi'] ?? '—')
            .(! empty($q['terdiri_dari']) ? '<br><span class="mut">Terdiri dari:<br>'.implode('<br>', array_map(fn ($x) => $this->e($x['judul']).' · '.$this->e($this->rentang($x['tgl_mulai'], $x['tgl_selesai'])).' · '.(int) $x['jam_pembelajaran'].' JP', $q['terdiri_dari'])).'</span>' : '')
            .($nama ? '<br><span class="mut">Peserta: '.$this->e(implode(', ', $nama)).'</span>' : '');
        $hargaCell = $allin ? $this->rp($c['harga_allin']).'<br><span class="mut">all-in paket</span>' : $this->rp($c['harga_peserta']);
        $diskCell = $allin ? ($c['total_diskon'] ? $this->rp($c['total_diskon']) : '0') : ($c['diskon'] ? $this->rp($c['diskon']) : '0');
        // B2B all-in: baris paket (1 paket, s.d. N peserta) dan, bila ada, baris Tambahan peserta (QTN-FR-20)
        $ktsCell = $allin ? '1 paket<br><span class="mut">s.d. '.(int) $c['paket_maks'].' peserta</span>' : (int) $c['n_peserta'].' peserta';
        $totalCell = $allin ? $this->rp($c['harga_allin']) : $this->rp($c['subtotal']);
        $barisTambahan = ($allin && $c['n_tambahan'] > 0)
            ? '<tr><td></td><td>Tambahan peserta</td><td><span class="mut">Peserta di atas '.(int) $c['paket_maks'].' orang</span></td><td align="right">'.(int) $c['n_tambahan'].' peserta</td><td align="right">'.$this->rp($c['tarif_tambahan']).'</td><td align="right">0</td><td align="right">'.$this->rp($c['tambahan']).'</td></tr>'
            : '';
        // Tanggal pada print out = tanggal dokumen dicetak, bukan Tanggal quotation (QTN-FR-30)
        $tglCetak = today()->toDateString();
        $linkRow = ! empty($q['link_token']) ? '<br><span class="mut">Konfirmasi kehadiran peserta: isi lewat link quotation.</span>' : '';

        $ket = '';
        if (! empty($q['termin'])) {
            // QTN-FR-44: tiap termin dicetak dengan nominal, persentase, dan keterangan; termin terakhir menampung selisih pembulatan.
            $ket .= 'Cara pembayaran: TOP '.count($q['termin']).' termin<br>';
            $paid = '0';
            foreach ($q['termin'] as $i => $t) {
                $nominal = Quotation::terminShare($q['termin'], $i + 1, $c['grand_total'], $paid);
                $paid = (string) \Brick\Math\BigDecimal::of($paid)->plus($nominal);
                $ket .= '- Termin '.($i + 1).': '.$this->rp($nominal).' ('.rtrim(rtrim(number_format((float) $t->persen, 2, ',', '.'), '0'), ',').'%) '.$this->e($t->keterangan).'<br>';
            }
            $ket .= '<br>';
        }
        foreach (self::KET_PDF as $kat => $judul) {
            $items = array_filter($q['keterangan'] ?? [], function ($k) use ($kat) {
                return $k['kategori'] === $kat;
            });
            if (! $items) {
                continue;
            }
            $ket .= $judul.':<br>';
            foreach ($items as $k) {
                $ket .= '- '.$this->e($k['teks']).'<br>';
            }
            $ket .= '<br>';
        }
        foreach ($q['keterangan'] ?? [] as $k) {
            if ($k['kategori'] === 'klausul') {
                $ket .= '<p>'.$this->e($k['teks']).'</p>';
            }
        }

        return (($q['status'] ?? '') === 'draft' ? '<p><b>Preview - belum dikirim</b></p>' : '').'<table width="100%"><tr>'
            .'<td width="50%" valign="top"><div class="box"><span class="lbl">Kepada</span><br><b>'.$this->e($kepada).'</b><br>'.$this->e($q['alamat'] ?: '—').'</div></td>'
            .'<td width="4%"></td><td valign="top"><div class="box"><b style="font-size:12pt;">Penawaran</b><br>Nomor : '.$this->e($q['nomor']).'<br>Tanggal : '.$this->e($this->tgl($tglCetak)).'</div></td></tr></table><br>'
            .'<table class="tbl"><thead><tr><th>No Catalog</th><th>Item</th><th>Deskripsi</th><th align="right">Kts.</th><th align="right">Harga</th><th align="right">Diskon</th><th align="right">Total Harga</th></tr></thead><tbody><tr>'
            .'<td>'.$this->e($pk['nomor'] ?? '').'</td><td>'.$this->e($pk['judul'] ?? '').'</td><td>'.$desk.'</td>'
            .'<td align="right">'.$ktsCell.'</td><td align="right">'.$hargaCell.'</td><td align="right">'.$diskCell.'</td><td align="right">'.$totalCell.'</td>'
            .'</tr>'.$barisTambahan.'</tbody></table><br>'
            .'<table width="100%"><tr><td width="55%" valign="top"><span class="lbl">Keterangan</span><br>Up. Ibu/Bapak '.$this->e($up ?: '—').$linkRow.'<br><div class="small">'.$ket.'</div></td><td width="4%"></td>'
            .'<td valign="top"><table class="sum">'
            .'<tr><td>Sub Total</td><td align="right">'.$this->rp($c['subtotal']).'</td></tr>'
            .'<tr><td>Diskon</td><td align="right">'.($c['total_diskon'] ? '− '.$this->rp($c['total_diskon']) : '0').'</td></tr>'
            .'<tr><td>PPN ('.rtrim(rtrim(number_format($c['ppn_persen'], 2, '.', ''), '0'), '.').'%)</td><td align="right">'.$this->rp($c['ppn_nominal']).'</td></tr>'
            .'<tr><td>Biaya Lain-lain</td><td align="right">'.$this->rp($c['biaya_lain']).'</td></tr>'
            .'<tr class="tot"><td>Total</td><td align="right">'.$this->rp($c['grand_total']).'</td></tr></table></td></tr></table><br><br>'
            .'<table width="100%"><tr><td width="50%">'.$this->e($this->tgl($tglCetak)).'<br>'.$this->e($sales['divisi'] ?? 'Divisi').'<br><br><br><br><b>'.$this->e($sales['nm_lengkap'] ?? '—').'</b></td>'
            .'<td>............., .................. 20.....<br>Disetujui oleh Pelanggan,<br><br><br><br>Nama :</td></tr></table>';
    }

    private function snk()
    {
        $sec = function ($from, $to) {
            $h = '';
            foreach (array_slice(self::SNK, $from, $to - $from) as [$judul, $list]) {
                $h .= '<li><b>'.$judul.'</b><ul>';
                foreach ($list as $t) {
                    $h .= '<li>'.$t.'</li>';
                }
                $h .= '</ul></li>';
            }

            return $h;
        };
        $p1 = '<div class="title">SYARAT &amp; KETENTUAN<br>KEBIJAKAN LAYANAN PEMBELAJARAN</div><br>'
            .'<p style="text-align:justify;font-size:8.5pt;">LabMania telah menetapkan syarat dan ketentuan yang berlaku yang harus dipatuhi oleh seluruh pelanggan (pembelajar/peserta maupun sponsor/instansi terkait yang mendaftarkan peserta). Berikut adalah beberapa syarat dan ketentuan terkait layanan pembelajaran (termasuk layanan pelatihan):</p>'
            .'<ol class="snk">'.$sec(0, 3).'</ol>';
        $p2 = '<ol class="snk" start="4">'.$sec(3, 7).'</ol>'
            .'<p style="text-align:justify;font-size:8.5pt;">Sebelum menyetujui layanan pembelajaran, pastikan Anda membaca kebijakan tersebut dengan cermat untuk memastikan bahwa Anda memahami syarat dan ketentuan yang berlaku.</p>'
            .'<p style="text-align:right;font-size:8.5pt;">Bekasi, 21 Oktober 2024<br>Disahkan oleh,<br>Operational Director<br><br><br><br>(Ivan Sarifudin)</p>';

        return [$p1, $p2];
    }

    private function previewPages(array $pages): string
    {
        return view('pdf.document-preview', ['header' => $this->kop(), 'styles' => $this->css(), 'pages' => $pages])->render();
    }

    private function draftPreview(array $q, string $document): string
    {
        [$pk, $sales, $tpl] = $this->extra($q);
        if ($document === 'confirmation') {
            if ($q['jenis_pj'] === 'b2b') {
                return $this->surat_company($q, [], [], json_decode($q['kolom_peserta_b2b'] ?: '[]', true), true);
            }

            return $this->previewPages([['body' => $this->konfirmasi($q, [], $pk, $sales, $tpl, true),
                'footer' => $this->foot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023')]]);
        }
        [$h1, $h2] = $this->snk();

        return $this->previewPages([
            ['body' => $this->penawaran($q, $pk, $sales), 'footer' => $this->foot('FR/LMI-TR-01/01/Ed.Rev-1.1', 'Halaman 1 dari 1', '01/01/2025')],
            ['body' => $h1, 'footer' => $this->foot('DP/LMI-TR-01/01/Ed.Rev-1.1', '1 dari 2', '21/10/2024')],
            ['body' => $h2, 'footer' => $this->foot('DP/LMI-TR-01/01/Ed.Rev-1.1', '2 dari 2', '21/10/2024')],
        ]);
    }

    public static function confirmationPreview(array $template, ?object $program, ?string $title): string
    {
        $layout = new self;
        $data = (array) $program;
        $data['konfirmasi_isian'] = json_decode((string) $template['isian'], true) ?: [];
        $data['konfirmasi_pernyataan'] = $template['pernyataan'];
        $data['durasi_hari'] = $program?->durasi_hari;

        return view('pdf.confirmation-preview', [
            'header' => $layout->kop(),
            'body' => $layout->konfirmasi($data, [], ['judul' => $title ?? '—'], [], $template, true),
            'footer' => $layout->foot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023'),
        ])->render();
    }

    private function konfirmasi(array $q, array $p, array $pk, array $sales, array $tpl, bool $blank = false)
    {
        $isian = $q['konfirmasi_isian'] ?? '[]';
        $isian = is_array($isian) ? $isian : (json_decode($isian, true) ?: []);
        $vars = [
            'judul' => $pk['judul'] ?? '', 'tanggal' => $this->rentang($q['tgl_mulai'] ?? null, $q['tgl_selesai'] ?? null),
            'waktu' => ($q['waktu'] ?? '') !== '' ? $q['waktu'] : '..........', 'durasi' => $q['durasi_hari'] === null ? '—' : (int) $q['durasi_hari'], 'lokasi' => ($q['lokasi'] ?? '') !== '' ? $q['lokasi'] : '—',
        ];
        // Semua isian dari isian link peserta itu, bukan Master Peserta (QTN-FR-16, TB-3)
        $nilai = [
            'nama' => $p['nama'] ?? '', 'nik' => $p['nik'] ?? '', 'jabatan' => $p['jabatan'] ?? '', 'instansi' => $p['instansi'] ?? '', 'alamat' => $p['alamat_instansi'] ?? '',
            'hp' => $p['no_hp'] ?? '', 'wa' => $p['no_wa'] ?? '', 'email' => $p['email'] ?? '', 'pengalaman' => $p['pengalaman'] ?? '', 'lsp' => $p['lsp'] ?? '',
            'habis' => ! empty($p['tgl_habis_sertifikat']) ? $this->tgl($p['tgl_habis_sertifikat']) : '',
        ];
        $extra = json_decode((string) ($p['isian_tambahan'] ?? ''), true) ?: [];

        $rows = '';
        foreach (TemplateController::KF_FIELDS as $key => $label) {
            if (! in_array($key, $isian, true)) {
                continue;
            }
            $rows .= '<tr><td width="42%">'.$this->e($label).'</td><td width="3%">:</td><td>'.$this->e($nilai[$key] ?? '').'</td></tr>';
        }
        foreach ($isian as $ex) { // isian lain (di luar 11 standar), dikosongkan untuk diisi peserta
            if (! array_key_exists($ex, TemplateController::KF_FIELDS)) {
                $rows .= '<tr><td>'.$this->e($ex).'</td><td>:</td><td>'.$this->e($extra[$ex] ?? '').'</td></tr>';
            }
        }

        $cat = ['email' => $sales['email'] ?? '—', 'telp' => $sales['telp'] ?? '—', 'kegiatan' => $tpl['label_kegiatan'] ?? '', 'judul' => $pk['judul'] ?? ''];
        $catatan = '';
        foreach (preg_split('/\r\n|\r|\n/', (string) ($tpl['catatan'] ?? '')) as $c) {
            if (trim($c) !== '') {
                $catatan .= '<li>'.$this->mark($this->fill($c, $cat)).'</li>';
            }
        }

        $revision = ! empty($q['revisi']) ? '<p class="small">Quotation '.$this->e($q['nomor']).'<br>'.$this->e($vars['judul']).' · '.$this->e($vars['tanggal']).' · '.$this->e($vars['waktu']).' · '.$this->e($vars['lokasi']).'</p>' : '';

        return '<div class="title">Konfirmasi Keikutsertaan Peserta</div>'.$revision.'<br>'
            .'<table class="form" width="100%">'.$rows.'</table><br>'
            .'<p>Dengan ini menyatakan:</p><ol class="small"><li>'.$this->mark($this->fill($q['konfirmasi_pernyataan'] ?? '', $vars)).'</li>'
            .'<li>Saya <b><u>telah membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania</li></ol><br>'
            .'<div class="confirmation-sign" style="text-align:right;">'.($blank ? '..........., ...................... 20.....' : '..........., '.$this->e($this->tgl(today()->toDateString()))).'<br><br><br><br><br>'.($blank ? '(..........................................................)' : $this->e($p['nama'] ?? '')).'<br><b>Jabatan:</b> '.$this->e($p['jabatan'] ?? '').'</div><br>'
            .'<div class="small"><b><u>Catatan :</u></b><ul>'.$catatan.'</ul></div>';
    }

    public function surat_peserta(array $q, array $in)
    {
        $m = $this->mulai('Surat Konfirmasi '.$q['nomor']);
        [$pk, $sales, $tpl] = $this->extra(['id_penjualan' => $q['id_penjualan'], 'id_sales' => $q['id_sales'], 'id_template_konfirmasi' => $q['id_template_konfirmasi']]);
        $q['jenis_pj'] = $q['jenis'];
        $q['alamat_company'] = $q['alamat_company'] ?? '';
        $p = [
            'nama' => trim($in['nama'] ?? ''), 'nik' => trim($in['nik'] ?? ''), 'jabatan' => trim($in['jabatan'] ?? ''), 'instansi' => $q['tujuan'] === 'company' ? ($q['nama_company'] ?? '') : trim($in['instansi'] ?? ''),
            'alamat_instansi' => trim($in['alamat'] ?? ''), 'no_hp' => trim($in['hp'] ?? ''), 'no_wa' => trim($in['wa'] ?? ''), 'email' => trim($in['email'] ?? ''),
            'pengalaman' => trim($in['pengalaman'] ?? ''), 'lsp' => trim($in['lsp'] ?? ''), 'tgl_habis_sertifikat' => trim($in['habis'] ?? '') ?: null,
            'isian_tambahan' => json_encode(is_array($in['ex'] ?? null) ? $in['ex'] : []),
        ];
        $this->page($m, $this->konfirmasi($q, $p, $pk, $sales, $tpl), $this->foot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023'));

        return $this->selesai($m);
    }

    public function surat_company(array $q, array $in, array $rows, array $kolom, bool $preview = false)
    {
        $m = $preview ? null : $this->mulai('Surat Konfirmasi Company '.$q['nomor']);
        $t = function ($k) use ($in) {
            return trim((string) ($in[$k] ?? ''));
        };
        $peserta = array_values(array_filter($rows, function ($r) {
            return is_array($r) && trim((string) ($r['nama'] ?? '')) !== '';
        }));
        [, $sales] = $this->extra($q);
        $maks = (int) ($q['paket_maks_peserta'] ?: 25);
        $tarif = (float) ($q['tambahan_per_peserta'] ?: 300000);
        $rg = $this->rentang($q['tgl_mulai'] ?? null, $q['tgl_selesai'] ?? null);

        $tbl = '<table class="form" width="100%">';
        foreach (['Nama Perusahaan' => 'nama_perusahaan', 'Alamat Perusahaan' => 'alamat_perusahaan', 'Nama PIC' => 'pic_nama', 'Jabatan' => 'pic_jabatan', 'Nomor HP / WhatsApp' => 'pic_hp', 'Email' => 'pic_email'] as $l => $k) {
            $tbl .= '<tr><td width="38%">'.$this->e($l).'</td><td width="3%">:</td><td>'.$this->e($t($k)).'</td></tr>';
        }
        $tbl .= '</table>';
        $hal1 = '<div class="title">Konfirmasi Keikutsertaan Perusahaan (In-House)</div><div style="text-align:center;" class="small">Quotation '.$this->e($q['nomor']).' · '.$this->e($q['nama_paket'] ?? '').'</div><br>'.$tbl.'<br>'
            .'<p>Dengan ini menyatakan:</p><ol class="small"><li>Mengikutsertakan <b>'.($preview ? '..........' : count($peserta)).' peserta</b> (daftar terlampir) pada kegiatan <b>'.$this->e($q['nama_paket'] ?? '').'</b> tanggal '.$this->e($rg)
            .', pukul '.$this->e(($q['waktu'] ?? '') !== '' ? $q['waktu'] : '..........').', '.(int) $q['durasi_hari'].' hari, di '.$this->e(($q['lokasi'] ?? '') !== '' ? $q['lokasi'] : '—').'.</li>'
            .'<li>Telah <b><u>membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan LabMania, termasuk ketentuan biaya peserta di atas '.$maks.' orang sesuai quotation.</li></ol><br>'
            .'<div style="text-align:right;">..........., '.$this->e($this->tgl(today()->toDateString())).'<br>Hormat kami,<br><br><br><br><br>'.$this->e($t('pic_nama')).'<br><b>Jabatan:</b> '.$this->e($t('pic_jabatan')).'<br><span class="mut">tanda tangan dan cap perusahaan</span></div><br>'
            .'<div class="small"><b><u>Catatan :</u></b><ul><li>Nama lengkap pada lampiran menjadi acuan penulisan di sertifikat.</li>'
            .'<li>Harga paket mencakup s.d. '.$maks.' peserta; peserta ke-'.($maks + 1).' dan seterusnya dikenakan tambahan '.$this->rp($tarif).'/orang.</li>'
            .'<li>Perubahan daftar peserta hubungi sales quotation: '.$this->e($sales['nm_lengkap'] ?? '—').', '.$this->e($sales['email'] ?? '—').', '.$this->e($sales['telp'] ?? '—').'.</li></ul></div>';
        if (! $preview) {
            $this->page($m, $hal1, $this->foot('[kode dokumen menyusul]', '{PAGENO} dari {nbpg}', '—'));
        }

        $lab = ['jabatan' => 'Jabatan', 'email' => 'Email', 'wa' => 'WhatsApp', 'nik' => 'NIK'];
        $head = '<th width="8%">No</th><th>Nama Lengkap</th>';
        foreach ($kolom as $k) {
            $head .= '<th>'.$this->e($lab[$k] ?? $k).'</th>';
        }
        $body = '';
        foreach ($peserta as $i => $r) {
            $body .= '<tr><td>'.($i + 1).'</td><td>'.$this->e($r['nama']).'</td>';
            foreach ($kolom as $k) {
                $body .= '<td>'.$this->e($r[$k] ?? '').'</td>';
            }
            $body .= '</tr>';
        }
        $hal2 = '<div class="title">Lampiran: Daftar Peserta</div><div style="text-align:center;" class="small">'.$this->e($t('nama_perusahaan')).' · '.$this->e($q['nomor']).' · '.$this->e($q['nama_paket'] ?? '').' · '.$this->e($rg).'</div><br>'
            .'<table class="tbl participant-list"><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table>';
        if ($preview) {
            return $this->previewPages([
                ['body' => $hal1, 'footer' => $this->foot('[kode dokumen menyusul]', '1 dari 2', '—')],
                ['body' => $hal2, 'footer' => $this->foot('[kode dokumen menyusul]', '2 dari 2', '—')],
            ]);
        }
        $this->page($m, $hal2, $this->foot('[kode dokumen menyusul]', '{PAGENO} dari {nbpg}', '—'));

        return $this->selesai($m);
    }

    private function mulai($judul)
    {
        $this->level = error_reporting();
        error_reporting($this->level & ~E_DEPRECATED);
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        $m = new Mpdf(['format' => 'A4', 'orientation' => 'P', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 9,
            'margin_left' => 25, 'margin_right' => 25, 'margin_top' => 38, 'margin_bottom' => 22, 'margin_header' => 8, 'margin_footer' => 8]);
        $m->SetTitle($judul);
        $m->SetAuthor('LabMania');
        $m->SetHTMLHeader($this->kop());
        $m->WriteHTML($this->css(), HTMLParserMode::HEADER_CSS);

        return $m;
    }

    private function selesai(Mpdf $m)
    {
        $out = $m->Output('', Destination::STRING_RETURN);
        error_reporting($this->level);

        return $out;
    }
}
