<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PDF Quotation satu berkas (QTN-FR-14 – QTN-FR-16, QTN-NFR-06): Penawaran (1 hlm) + Syarat & Ketentuan (2 hlm)
 * + Formulir Konfirmasi per peserta (hanya Public). Isi mengikuti mockup `specs/final/Mock Up Master - Quotation` (pagePenawaran,
 * pagesSnk, pageKonfirmasi). Tabel dipakai untuk tata letak karena mPDF tidak mendukung flex.
 */
class Qtn_pdf
{
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

    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    private function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
    private function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

    public function tgl($iso)
    {
        if (!$iso || strlen($iso) < 10) return '—';
        [$y, $m, $d] = explode('-', substr($iso, 0, 10));
        return (int)$d . ' ' . self::BULAN[(int)$m - 1] . ' ' . $y;
    }

    public function rentang($a, $b)
    {
        if (!$a) return '—';
        if (!$b || $a === $b) return $this->tgl($a);
        [$y1, $m1, $d1] = explode('-', substr($a, 0, 10));
        [$y2, $m2] = explode('-', substr($b, 0, 10));
        if ($y1 === $y2 && $m1 === $m2) return (int)$d1 . '–' . $this->tgl($b);
        return $y1 === $y2 ? (int)$d1 . ' ' . self::BULAN[(int)$m1 - 1] . ' – ' . $this->tgl($b) : $this->tgl($a) . ' – ' . $this->tgl($b);
    }

    /** **teks** = tebal; seluruh kalimat di-escape. */
    private function mark($s) { return preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $this->e($s)); }
    private function fill($s, array $v) { return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($v) { return array_key_exists($m[1], $v) ? $v[$m[1]] : $m[0]; }, (string)$s); }

    /** Data pelengkap yang tidak ada di baris quotation: paket, sales, template formulir. */
    protected function extra(array $q)
    {
        $db = $this->ci->db;
        $pk = $db->select('pp.nomor, pp.judul')->from('penjualan_program pj')->join('master_program mp', 'mp.id = pj.id_master_program', 'left')
            ->join('paket_produk pp', 'pp.id = mp.id_paket_produk', 'left')->where('pj.id', (int)$q['id_penjualan'])->get()->row_array() ?: [];
        $sales = $db->query('SELECT u.nm_lengkap, COALESCE(k.email, u.email) AS email, COALESCE(k.telp, u.hp) AS telp, COALESCE(k.division, k.department) AS divisi
                             FROM users u LEFT JOIN ms_user_karyawan k ON k.id_user = u.id_user WHERE u.id_user = ?', [(int)$q['id_sales']])->row_array() ?: [];
        $tpl = !empty($q['id_template_konfirmasi']) ? ($db->get_where('ms_template_konfirmasi', ['id' => (int)$q['id_template_konfirmasi']])->row_array() ?: []) : [];
        return [$pk, $sales, $tpl];
    }

    private function kop()
    {
        return '<table width="100%" style="border-bottom:0.8mm solid #111;"><tr>'
            . '<td width="34%" style="font-size:26pt;font-weight:bold;color:#1F4FD8;">L<span style="color:#F2B705;">A</span>B<br><span style="font-size:8pt;font-weight:normal;letter-spacing:3pt;color:#111;">M A N I A</span></td>'
            . '<td style="font-size:8.5pt;"><b style="font-size:13pt;">PT LabMania Indonesia Jaya</b><br>Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143<br>www.labmaniaindonesia.id · Customer Care: 0821-2429-3839</td>'
            . '</tr></table>';
    }

    private function foot($kode, $hal, $berlaku)
    {
        return '<table width="100%" style="border-top:0.6mm solid #111;font-size:8pt;"><tr><td>' . $this->e($kode) . '</td><td align="center"><i>' . $this->e($hal) . '</i></td><td align="right">Berlaku ' . $this->e($berlaku) . '</td></tr></table>';
    }

    private function page(\Mpdf\Mpdf $m, $html, $foot)
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
          table.sum { width: 100%; border-collapse: collapse; background-color: #E4E6EB; }
          table.sum td { padding: 1mm 2mm; border-bottom: 0.3mm solid #fff; }
          table.sum tr.tot td { background-color: #173A5E; color: #fff; font-weight: bold; }
          .mut { color: #666; } .small { font-size: 8pt; } .title { text-align: center; font-size: 11pt; font-weight: bold; }
          ol.snk { padding-left: 5mm; text-align: justify; font-size: 8.5pt; } ol.snk li { margin-bottom: 2mm; }
          table.form td { padding: 1.2mm 1mm; font-size: 9pt; }
        </style>';
    }

    /**
     * @param array  $q    baris quotation (get_quotation_by_id / build_preview): peserta, keterangan, calc terisi
     * @param string $mode 'all' = Penawaran + S&K + Formulir; 'konfirmasi' = hanya Formulir (Confirmation Letter)
     * @return string biner PDF
     */
    public function render(array $q, $mode = 'all')
    {
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED); // mPDF 8.3 di PHP 8.5; notice di keluaran merusak PDF
        $tmp = sys_get_temp_dir() . '/labmania_mpdf';
        if (!is_dir($tmp)) @mkdir($tmp, 0775, true);

        $m = new \Mpdf\Mpdf([
            'format' => 'A4', 'orientation' => 'P', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 9,
            'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 38, 'margin_bottom' => 22, 'margin_header' => 8, 'margin_footer' => 8,
        ]);
        $m->SetTitle(($mode === 'konfirmasi' ? 'Confirmation Letter ' : 'Quotation ') . $q['nomor']);
        $m->SetAuthor('LabMania');
        $m->SetHTMLHeader($this->kop());
        $m->WriteHTML($this->css(), \Mpdf\HTMLParserMode::HEADER_CSS); // CSS saja, tanpa membuat halaman kosong

        [$pk, $sales, $tpl] = $this->extra($q);

        if ($mode !== 'konfirmasi') {
            $this->page($m, $this->penawaran($q, $pk, $sales), $this->foot('FR/LMI-TR-01/01/Ed.Rev-1.1', 'Halaman 1 dari 1', '01/01/2025'));
            [$h1, $h2] = $this->snk();
            $this->page($m, $h1, $this->foot('DP/LMI-TR-01/01/Ed.Rev-1.1', '1 dari 2', '21/10/2024'));
            $this->page($m, $h2, $this->foot('DP/LMI-TR-01/01/Ed.Rev-1.1', '2 dari 2', '21/10/2024'));
        }
        if (($q['jenis_pj'] ?? '') !== 'b2b') {
            foreach ($q['peserta'] as $p) {
                $this->page($m, $this->konfirmasi($q, $p, $pk, $sales, $tpl), $this->foot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023'));
            }
        }

        $out = $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        error_reporting($level);
        return $out;
    }

    private function penawaran(array $q, array $pk, array $sales)
    {
        $e = [$this, 'e'];
        $c = $q['calc'];
        $kepada = $q['tujuan'] === 'company' ? ($q['nama_company'] ?? '—') : ($q['peserta'][0]['nama_peserta'] ?? '—');
        $up = $q['tujuan'] === 'company' ? ($q['up_nama'] ?? '') : $kepada;
        $nama = array_filter(array_column($q['peserta'], 'nama_peserta'));
        $allin = $c['skema'] === 'allin';

        $desk = (int)$q['durasi_hari'] . ' hari<br>' . $this->e($this->rentang($q['tgl_mulai'] ?? null, $q['tgl_selesai'] ?? null)) . '<br>' . $this->e($q['waktu'] ?? '—') . '<br>' . $this->e($q['lokasi'] ?? '—')
            . ($nama ? '<br><span class="mut">Peserta: ' . $this->e(implode(', ', $nama)) . '</span>' : '');
        $hargaCell = $allin ? $this->rp($c['subtotal']) . '<br><span class="mut">all-in</span>' : $this->rp($c['harga_peserta']);
        $diskCell = $allin ? ($c['total_diskon'] ? $this->rp($c['total_diskon']) : '0') : ($c['diskon'] ? $this->rp($c['diskon']) : '0');

        $ket = '';
        foreach (self::KET_PDF as $kat => $judul) {
            $items = array_filter($q['keterangan'] ?? [], function ($k) use ($kat) { return $k['kategori'] === $kat; });
            if (!$items) continue;
            $ket .= $judul . ':<br>';
            foreach ($items as $k) $ket .= '- ' . $this->e($k['teks']) . '<br>';
            $ket .= '<br>';
        }
        foreach ($q['keterangan'] ?? [] as $k) if ($k['kategori'] === 'klausul') $ket .= '<p>' . $this->e($k['teks']) . '</p>';

        return '<table width="100%"><tr>'
            . '<td width="50%" valign="top"><div class="box"><span class="lbl">Kepada</span><br><b>' . $this->e($kepada) . '</b><br>' . $this->e($q['alamat'] ?: '—') . '</div></td>'
            . '<td width="4%"></td><td valign="top"><div class="box"><b style="font-size:12pt;">Penawaran</b><br>Nomor : ' . $this->e($q['nomor']) . '<br>Tanggal : ' . $this->e($this->tgl($q['tanggal'])) . '</div></td></tr></table><br>'
            . '<table class="tbl"><thead><tr><th>No Catalog</th><th>Item</th><th>Deskripsi</th><th align="right">Kts.</th><th align="right">Harga</th><th align="right">Diskon</th><th align="right">Total Harga</th></tr></thead><tbody><tr>'
            . '<td>' . $this->e($pk['nomor'] ?? '') . '</td><td>' . $this->e($pk['judul'] ?? '') . '</td><td>' . $desk . '</td>'
            . '<td align="right">' . (int)$c['n_peserta'] . ' peserta</td><td align="right">' . $hargaCell . '</td><td align="right">' . $diskCell . '</td><td align="right">' . $this->rp($c['subtotal']) . '</td>'
            . '</tr></tbody></table><br>'
            . '<table width="100%"><tr><td width="55%" valign="top"><span class="lbl">Keterangan</span><br>Up. Ibu/Bapak ' . $this->e($up ?: '—') . '<br><div class="small">' . $ket . '</div></td><td width="4%"></td>'
            . '<td valign="top"><table class="sum">'
            . '<tr><td>Sub Total</td><td align="right">' . $this->rp($c['subtotal']) . '</td></tr>'
            . '<tr><td>Diskon</td><td align="right">' . ($c['total_diskon'] ? '− ' . $this->rp($c['total_diskon']) : '0') . '</td></tr>'
            . '<tr><td>PPN (' . rtrim(rtrim(number_format($c['ppn_persen'], 2, '.', ''), '0'), '.') . '%)</td><td align="right">' . $this->rp($c['ppn_nominal']) . '</td></tr>'
            . '<tr><td>Biaya Lain-lain</td><td align="right">' . $this->rp($c['biaya_lain']) . '</td></tr>'
            . '<tr class="tot"><td>Total</td><td align="right">' . $this->rp($c['grand_total']) . '</td></tr></table></td></tr></table><br><br>'
            . '<table width="100%"><tr><td width="50%">' . $this->e($this->tgl($q['tanggal'])) . '<br>' . $this->e($sales['divisi'] ?? 'Divisi') . '<br><br><br><br><b>' . $this->e($sales['nm_lengkap'] ?? '—') . '</b></td>'
            . '<td>............., .................. 20.....<br>Disetujui oleh Pelanggan,<br><br><br><br>Nama :</td></tr></table>';
    }

    private function snk()
    {
        $sec = function ($from, $to) {
            $h = '';
            foreach (array_slice(self::SNK, $from, $to - $from) as [$judul, $list]) {
                $h .= '<li><b>' . $judul . '</b><ul>';
                foreach ($list as $t) $h .= '<li>' . $t . '</li>';
                $h .= '</ul></li>';
            }
            return $h;
        };
        $p1 = '<div class="title">SYARAT &amp; KETENTUAN<br>KEBIJAKAN LAYANAN PEMBELAJARAN</div><br>'
            . '<p style="text-align:justify;font-size:8.5pt;">LabMania telah menetapkan syarat dan ketentuan yang berlaku yang harus dipatuhi oleh seluruh pelanggan (pembelajar/peserta maupun sponsor/instansi terkait yang mendaftarkan peserta). Berikut adalah beberapa syarat dan ketentuan terkait layanan pembelajaran (termasuk layanan pelatihan):</p>'
            . '<ol class="snk">' . $sec(0, 3) . '</ol>';
        $p2 = '<ol class="snk" start="4">' . $sec(3, 7) . '</ol>'
            . '<p style="text-align:justify;font-size:8.5pt;">Sebelum menyetujui layanan pembelajaran, pastikan Anda membaca kebijakan tersebut dengan cermat untuk memastikan bahwa Anda memahami syarat dan ketentuan yang berlaku.</p>'
            . '<p style="text-align:right;font-size:8.5pt;">Bekasi, 21 Oktober 2024<br>Disahkan oleh,<br>Operational Director<br><br><br><br>(Ivan Sarifudin)</p>';
        return [$p1, $p2];
    }

    private function konfirmasi(array $q, array $p, array $pk, array $sales, array $tpl)
    {
        $isian = $q['konfirmasi_isian'] ?? '[]';
        $isian = is_array($isian) ? $isian : (json_decode($isian, true) ?: []);
        $vars = [
            'judul' => $pk['judul'] ?? '', 'tanggal' => $this->rentang($q['tgl_mulai'] ?? null, $q['tgl_selesai'] ?? null),
            'waktu' => ($q['waktu'] ?? '') !== '' ? $q['waktu'] : '..........', 'durasi' => (int)$q['durasi_hari'], 'lokasi' => ($q['lokasi'] ?? '') !== '' ? $q['lokasi'] : '—',
        ];
        $alamat = $q['tujuan'] === 'company' && !empty($q['alamat_company']) ? $q['alamat_company'] : ($p['alamat_peserta'] ?? '');
        $nilai = ['nama' => $p['nama_peserta'] ?? '', 'instansi' => $p['instansi'] ?? '', 'alamat' => $alamat, 'wa' => $p['no_telp'] ?? '', 'email' => $p['email'] ?? ''];

        $rows = '';
        foreach (Master_data_model::KF_FIELDS as $key => $label) {
            if (!in_array($key, $isian, true)) continue;
            $rows .= '<tr><td width="42%">' . $this->e($label) . '</td><td width="3%">:</td><td>' . $this->e($nilai[$key] ?? '') . '</td></tr>';
        }
        foreach ($isian as $ex) { // isian lain (di luar 11 standar), dikosongkan untuk diisi peserta
            if (!array_key_exists($ex, Master_data_model::KF_FIELDS)) $rows .= '<tr><td>' . $this->e($ex) . '</td><td>:</td><td></td></tr>';
        }

        $cat = ['email' => $sales['email'] ?? '—', 'telp' => $sales['telp'] ?? '—', 'kegiatan' => $tpl['label_kegiatan'] ?? '', 'judul' => $pk['judul'] ?? ''];
        $catatan = '';
        foreach (preg_split('/\r\n|\r|\n/', (string)($tpl['catatan'] ?? '')) as $c) {
            if (trim($c) !== '') $catatan .= '<li>' . $this->mark($this->fill($c, $cat)) . '</li>';
        }

        return '<div class="title">Konfirmasi Keikutsertaan Peserta</div><br>'
            . '<table class="form" width="100%">' . $rows . '</table><br>'
            . '<p>Dengan ini menyatakan:</p><ol class="small"><li>' . $this->mark($this->fill($q['konfirmasi_pernyataan'] ?? '', $vars)) . '</li>'
            . '<li>Saya <b><u>telah membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania</li></ol><br>'
            . '<div style="text-align:right;">..........., ...................... 20.....<br><br><br><br>(..........................................................)<br><b>Jabatan:</b></div><br>'
            . '<div class="small"><b><u>Catatan :</u></b><ul>' . $catatan . '</ul></div>';
    }
}
