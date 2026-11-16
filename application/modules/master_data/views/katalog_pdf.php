<?php
// PDF Katalog Produk A4 (FR-62, FR-68): Sampul, Daftar Isi, Isi per Bidang (2 kolom), Sampul belakang.
// mPDF tidak mendukung flex/grid, jadi tata letak memakai tabel. Isi mengikuti mockup produk-to-be (mod-master-data.js katalogPages).
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$tglPanjang = function ($d) {
    $b = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $p = explode('-', $d); // d-m-Y
    return (int)$p[0] . ' ' . $b[(int)$p[1]] . ' ' . $p[2];
};

$KT_CAP = 16; $KT_HEAD = 1; $KT_ROW = 3; // satuan tinggi isi per halaman: judul bidang = 1, satu baris 2 kartu = 3

// Pagination: Lingkup menambah tinggi kartu (kira-kira 2 item = 1 satuan, mengikuti kartu terpanjang di baris itu).
$pages = []; $cur = []; $used = 0;
foreach ($groups as $g) {
    for ($i = 0, $n = count($g['items']); $i < $n; $i += 2) {
        $pair = array_slice($g['items'], $i, 2);
        $rowH = $KT_ROW + (int)ceil(max(array_map(function ($p) { return count($p['lingkup']); }, $pair)) / 2);
        $needHead = ($i === 0 || $used === 0);
        if ($cur && $used + $rowH + ($needHead ? $KT_HEAD : 0) > $KT_CAP) { $pages[] = $cur; $cur = []; $used = 0; }
        if ($i === 0 || $used === 0) { $cur[] = ['head' => $g, 'lanjut' => $i > 0]; $used += $KT_HEAD; }
        $cur[] = ['row' => $pair]; $used += $rowH;
    }
}
if ($cur) $pages[] = $cur;

$startOf = []; // halaman pertama tiap bidang: sampul = 1, daftar isi = 2
foreach ($pages as $pi => $pg) foreach ($pg as $x) if (isset($x['head']) && !$x['lanjut']) $startOf[$x['head']['id']] = $pi + 3;

$produk = []; $sert = [];
foreach ($rows as $r) { $produk[$r['nama_produk'] ?? '—'] = 1; $sert[$r['nama_sertifikasi'] ?? '—'] = 1; }
$produk = array_keys($produk); sort($produk, SORT_NATURAL | SORT_FLAG_CASE);
$sert = array_keys($sert); sort($sert, SORT_NATURAL | SORT_FLAG_CASE);

$NAVY = '#14304F'; $GOLD = '#D5A320';
$logo = function ($bg = false) use ($NAVY, $GOLD) {
    return '<span style="font-family: Arial; font-weight: bold; font-size: 24pt; color: ' . ($bg ? '#14304F' : '#14304F') . ';">L<span style="color:' . $GOLD . ';">A</span>B</span>'
        . '<span style="font-size: 6pt; letter-spacing: 3px; color: #14304F;"> M A N I A</span>';
};
$phead = function () use ($tahun, $logo, $GOLD) {
    return '<table width="100%" style="border-bottom: 3px solid ' . $GOLD . '; margin-bottom: 14px;" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="padding: 18px 0 8px 40px;">' . $logo() . '</td>'
        . '<td align="right" style="padding: 18px 40px 8px 0; font-size: 8pt; color: #6E7390; letter-spacing: 2px;">KATALOG PRODUK ' . $tahun . '</td></tr></table>';
};
$pfoot = function ($n) {
    return '<table width="100%" style="border-top: 1px solid #D3DCE8; margin-top: 8px;" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="padding: 8px 0 0 40px; font-size: 7.5pt; color: #6E7390;">PT LabMania Indonesia Jaya · www.labmaniaindonesia.id</td>'
        . '<td align="right" style="padding: 8px 40px 0 0; font-size: 8pt; font-weight: bold; color: #14304F;">' . $n . '</td></tr></table>';
};
$item = function ($p) use ($e, $NAVY, $GOLD) {
    $h = '<div style="border: 1px solid #D3DCE8; padding: 9px 11px; min-height: 120px;">'
        . '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td><span style="background-color: ' . $GOLD . '; color: #030637; font-size: 7pt; font-weight: bold; padding: 1px 6px;">' . $e(mb_strtoupper($p['nama_produk'] ?? '—')) . '</span></td>'
        . '<td align="right" style="font-family: monospace; font-size: 7pt; color: #6E7390;">' . $e($p['nomor']) . '</td></tr></table>'
        . '<div style="font-size: 10.5pt; font-weight: bold; color: ' . $NAVY . '; margin: 5px 0 5px 0;">' . $e($p['judul']) . '</div>'
        . '<div style="font-size: 8pt; color: #3C4161;"><span style="color: #6E7390;">Bidang</span> <b>' . $e($p['nama_bidang'] ?? '—') . '</b></div>'
        . '<div style="font-size: 8pt; color: #3C4161;"><span style="color: #6E7390;">Varian</span> <b>' . ($p['nama_varian'] !== null ? $e($p['nama_varian']) : '—') . '</b></div>';
    if ($p['lingkup']) {
        $h .= '<div style="font-size: 7.5pt; color: #3C4161; margin-top: 4px;"><span style="color: #6E7390;">Lingkup</span><ul style="margin: 1px 0 0 0; padding-left: 12px;">';
        foreach ($p['lingkup'] as $t) $h .= '<li>' . $e($t) . '</li>';
        $h .= '</ul></div>';
    }
    return $h . '<div style="border-top: 1px dashed #D3DCE8; margin-top: 6px; padding-top: 5px; font-size: 8pt; font-weight: bold; color: #B88A12;">&#9679; ' . $e($p['nama_sertifikasi'] ?? '—') . '</div></div>';
};
?>
<html><head><meta charset="utf-8"><style>
  body { font-family: dejavusans, sans-serif; color: #3C4161; margin: 0; padding: 0; }
  h1, h2, h3, h4 { margin: 0; }
  ul { list-style-type: disc; }
</style></head><body>

<!-- 1. Sampul depan -->
<div style="background-color: <?= $NAVY; ?>; color: #FFFFFF; height: 297mm; width: 210mm; padding: 0; position: relative;">
  <table width="100%" cellpadding="0" cellspacing="0"><tr>
    <td style="padding: 48px 0 0 56px;"><span style="background-color: #FFFFFF; padding: 10px 14px;"><?= $logo(true); ?></span></td>
    <td align="right" style="padding: 48px 56px 0 0; font-size: 20pt; letter-spacing: 6px; color: <?= $GOLD; ?>;"><?= $e($tahun); ?></td></tr></table>
  <div style="padding: 150px 56px 0 56px;">
    <div style="font-size: 9pt; letter-spacing: 3px; color: #C7D2E2;">PT LABMANIA INDONESIA JAYA</div>
    <div style="font-size: 60pt; font-weight: bold; line-height: 0.95; margin: 12px 0 14px 0; color: #FFFFFF;">Katalog<br>Produk</div>
    <div style="font-size: 14pt; color: <?= $GOLD; ?>; font-weight: bold; margin-bottom: 36px;">Program <?= $e(implode(' & ', $produk)); ?></div>
    <table cellpadding="0" cellspacing="0"><tr>
      <td style="padding-right: 36px;"><div style="font-size: 26pt; font-weight: bold; color: #FFFFFF;"><?= count($rows); ?></div><div style="font-size: 8pt; letter-spacing: 1px; color: #C7D2E2;">PAKET PRODUK</div></td>
      <td style="padding-right: 36px;"><div style="font-size: 26pt; font-weight: bold; color: #FFFFFF;"><?= count($groups); ?></div><div style="font-size: 8pt; letter-spacing: 1px; color: #C7D2E2;">BIDANG</div></td>
      <td><div style="font-size: 26pt; font-weight: bold; color: #FFFFFF;"><?= count($sert); ?></div><div style="font-size: 8pt; letter-spacing: 1px; color: #C7D2E2;">JENIS SERTIFIKAT</div></td>
    </tr></table>
  </div>
  <div style="position: absolute; left: 56px; bottom: 40px; font-size: 8.5pt; color: #C7D2E2; width: 420px;"><?= $e($filter_text); ?><br>Dicetak <?= $e($tglPanjang($tanggal)); ?></div>
</div>
<pagebreak />

<!-- 2. Daftar isi -->
<?= $phead(); ?>
<div style="padding: 10px 40px 0 40px; height: 235mm;">
  <h2 style="font-size: 22pt; color: <?= $NAVY; ?>; margin-bottom: 6px;">Daftar Isi</h2>
  <p style="font-size: 9.5pt; color: #3C4161; margin: 0 0 16px 0;">Katalog ini memuat <?= count($rows); ?> paket produk <?= $e(mb_strtolower(implode(' dan ', $produk))); ?> LabMania yang dikelompokkan menurut bidang. Setiap paket mencantumkan jenis sertifikat yang diterima peserta dan lingkupnya bila sudah diisi.</p>
  <table width="100%" cellpadding="0" cellspacing="0">
    <?php foreach ($groups as $gi => $g): ?>
      <tr>
        <td width="28" style="padding: 6px 0; font-weight: bold; color: <?= $GOLD; ?>; font-size: 10pt;"><?= sprintf('%02d', $gi + 1); ?></td>
        <td style="padding: 6px 0; font-size: 10pt; border-bottom: 1px dotted #C7D2E2;"><?= $e($g['nama']); ?> <span style="font-size: 8pt; color: #6E7390;"><?= count($g['items']); ?> paket</span></td>
        <td align="right" width="30" style="padding: 6px 0; font-weight: bold; color: <?= $NAVY; ?>; font-size: 10pt; border-bottom: 1px dotted #C7D2E2;"><?= (int)$startOf[$g['id']]; ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <h3 style="font-size: 10pt; color: <?= $NAVY; ?>; margin: 22px 0 8px 0; letter-spacing: 1.5px;">JENIS SERTIFIKAT</h3>
  <table width="100%" cellpadding="0" cellspacing="0">
    <?php foreach (array_chunk($sert, 2) as $pair): ?>
      <tr><?php foreach ($pair as $s): ?><td width="50%" style="padding: 3px 0; font-size: 9pt; color: #3C4161;"><span style="color: #B88A12;">&#9679;</span> <?= $e($s); ?></td><?php endforeach; ?><?= count($pair) < 2 ? '<td></td>' : ''; ?></tr>
    <?php endforeach; ?>
  </table>
</div>
<?= $pfoot(2); ?>
<pagebreak />

<!-- 3. Isi per Bidang, kartu 2 kolom -->
<?php foreach ($pages as $pi => $pg): ?>
  <?= $phead(); ?>
  <div style="padding: 0 40px; height: 235mm;">
    <?php foreach ($pg as $x): ?>
      <?php if (isset($x['head'])): ?>
        <div style="background-color: <?= $NAVY; ?>; color: #FFFFFF; padding: 7px 12px; margin: 2px 0 10px 0; font-size: 12pt; font-weight: bold;">
          <span style="font-size: 7pt; letter-spacing: 2px; color: <?= $GOLD; ?>;">BIDANG</span>&nbsp; <?= $e($x['head']['nama']); ?><?= $x['lanjut'] ? ' <span style="font-size: 8pt; font-weight: normal;">(lanjutan)</span>' : ''; ?>
        </div>
      <?php else: ?>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;"><tr>
          <td width="49%" valign="top"><?= $item($x['row'][0]); ?></td>
          <td width="2%"></td>
          <td width="49%" valign="top"><?= isset($x['row'][1]) ? $item($x['row'][1]) : ''; ?></td>
        </tr></table>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?= $pfoot($pi + 3); ?>
  <pagebreak />
<?php endforeach; ?>

<!-- 4. Sampul belakang -->
<div style="background-color: <?= $NAVY; ?>; color: #FFFFFF; height: 297mm; width: 210mm; text-align: center;">
  <div style="padding: 190px 56px 0 56px;">
    <div style="margin-bottom: 18px;"><span style="background-color: #FFFFFF; padding: 10px 14px;"><?= $logo(true); ?></span></div>
    <div style="font-size: 20pt; font-weight: bold; color: #FFFFFF; margin: 24px 0 10px 0;">Tertarik mengikuti program kami?</div>
    <div style="font-size: 10.5pt; color: #C7D2E2; margin-bottom: 28px;">Hubungi tim LabMania untuk jadwal, penawaran, dan program in house sesuai kebutuhan instansi Anda.</div>
    <div style="font-size: 8pt; letter-spacing: 2px; color: <?= $GOLD; ?>;">CUSTOMER CARE</div><div style="font-size: 11pt; margin-bottom: 12px;">0821-2429-3839</div>
    <div style="font-size: 8pt; letter-spacing: 2px; color: <?= $GOLD; ?>;">WEBSITE</div><div style="font-size: 11pt; margin-bottom: 12px;">www.labmaniaindonesia.id</div>
    <div style="font-size: 8pt; letter-spacing: 2px; color: <?= $GOLD; ?>;">ALAMAT</div>
    <div style="font-size: 9pt; color: #C7D2E2;">Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143</div>
  </div>
</div>
</body></html>
