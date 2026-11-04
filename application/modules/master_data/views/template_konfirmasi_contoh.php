<?php
// Modal "Lihat contoh" Template Formulir Konfirmasi: formulir satu halaman dengan data contoh (mengikuti pageKonfirmasi di mockup).
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tglPanjang = function ($a, $b) use ($bulan) {
    if (!$a) return '..........';
    $x = strtotime($a); $y = $b ? strtotime($b) : $x;
    $d1 = (int)date('j', $x); $m1 = (int)date('n', $x); $y1 = date('Y', $x);
    $d2 = (int)date('j', $y); $m2 = (int)date('n', $y); $y2 = date('Y', $y);
    if ($x === $y) return "$d1 {$bulan[$m1]} $y1";
    if ($m1 === $m2 && $y1 === $y2) return "$d1 – $d2 {$bulan[$m1]} $y1";
    return "$d1 {$bulan[$m1]}" . ($y1 !== $y2 ? " $y1" : '') . " – $d2 {$bulan[$m2]} $y2";
};

$vars = [
    'judul'    => $pj['judul_paket'] ?? '..........',
    'tanggal'  => $tglPanjang($pj['tgl_mulai'] ?? null, $pj['tgl_selesai'] ?? null),
    'waktu'    => ($pj['waktu'] ?? '') !== '' ? $pj['waktu'] : '..........',
    'durasi'   => $pj['durasi_hari'] ?? '..',
    'lokasi'   => ($pj['lokasi'] ?? '') !== '' ? $pj['lokasi'] : '—',
    'email'    => ($sales['email'] ?? '') !== '' ? $sales['email'] : '—',
    'telp'     => ($sales['telp'] ?? '') !== '' ? $sales['telp'] : '—',
    'kegiatan' => $t['label_kegiatan'],
];
$fill = function ($s, $v) { return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($v) { return array_key_exists($m[1], $v) ? (string)$v[$m[1]] : $m[0]; }, $s); };
$mark = function ($s) use ($e) { return preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $e($s)); };

// Nilai isian dari Master Peserta contoh; yang tidak ada di master dikosongkan untuk diisi peserta (Q-32).
$val = [
    'nama' => $ps['nama'] ?? '', 'instansi' => $ps['instansi'] ?? '', 'wa' => $ps['no_telp'] ?? '', 'email' => $ps['email'] ?? '',
    'alamat' => trim(($ps['kabupaten'] ?? '') . (!empty($ps['provinsi']) ? ', ' . $ps['provinsi'] : ''), ', '),
];
$isian = json_decode($t['isian'] ?? '[]', true) ?: [];
$catatan = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$t['catatan']))));
$info = $pj && $ps ? 'Contoh dengan ' . $pj['nomor'] . ' dan ' . $ps['nama'] : 'Contoh dengan data kosong (belum ada Penjualan Program Final / peserta)';
?>
<div class="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal xwide" role="dialog" aria-modal="true">
    <header><h2>Contoh — <?= $e($t['nama']); ?></h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>
    <div class="modal-body">
      <div class="pdf-tools"><span class="muted"><?= $e($info); ?></span>
        <button type="button" class="btn btn-ghost sm" onclick="window.print()">Cetak / Simpan PDF</button></div>
      <div class="pdf-doc">
        <section class="pdf-page">
          <div class="pdf-kop"><div class="pdf-logo">L<i>A</i>B<small>M A N I A</small></div><div class="pdf-kop-t"><b>PT LabMania Indonesia Jaya</b>
            <span>Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143</span>
            <span>www.labmaniaindonesia.id · Customer Care: 0821-2429-3839</span></div></div>
          <div class="pdf-body">
            <h3 class="pdf-title">Konfirmasi Keikutsertaan Peserta</h3>
            <table class="pdf-form">
              <?php foreach ($kf_fields as $key => $label): if (!in_array($key, $isian, true)) continue; ?>
                <tr><td><?= $e($label); ?></td><td>:</td><td><?= $e($val[$key] ?? ''); ?></td></tr>
              <?php endforeach; ?>
            </table>
            <p>Dengan ini menyatakan:</p>
            <ol class="pdf-small-ol">
              <li><?= $mark($fill($t['pernyataan'], $vars)); ?></li>
              <li>Saya <b><u>telah membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania</li>
            </ol>
            <div class="pdf-sign1">..........., ...................... 20.....<div class="pdf-ttd"></div>(..........................................................)<br><b>Jabatan:</b></div>
            <div class="pdf-small"><b><u>Catatan :</u></b>
              <ul><?php foreach ($catatan as $c): ?><li><?= $mark($fill($c, $vars)); ?></li><?php endforeach; ?></ul>
            </div>
          </div>
          <div class="pdf-foot"><span>FR/LMI-TR-01/02/Ed.Rev-1.0</span><span><i>1 dari 1</i></span><span>Berlaku 01/08/2023</span></div>
        </section>
      </div>
    </div>
    <footer><button type="button" class="btn btn-muted" onclick="closeModal()">Tutup</button></footer>
  </div>
</div>
