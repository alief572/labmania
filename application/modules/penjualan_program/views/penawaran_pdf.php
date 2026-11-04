<?php
// Surat Penawaran PDF (mPDF): isi sama dengan modal pratinjau (PJ-FR-20). HTML + CSS sederhana yang didukung mPDF.
$e  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$rp = function ($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
$rows = [
    ['Program', $e($p['nama_paket']) . ' <span style="color:#6E7390;">(' . $e($p['nama_sertifikasi']) . ')</span>'],
    ['Pelaksanaan', $e($pelaksanaan)],
    ['Durasi', (int)$p['durasi_hari'] . ' hari'],
];
if (!empty($p['nama_trainer'])) $rows[] = ['Trainer', $e($p['nama_trainer'])];
$rows[] = ['Harga / peserta', $rp($p['harga_jual_peserta'])];
$rows[] = ['Jumlah peserta', (int)$p['target_peserta'] . ' peserta'];
$rows[] = ['Total', '<b>' . $rp($total) . '</b>'];
?>
<style>
  body { font-family: dejavusans, sans-serif; font-size: 10.5pt; color: #030637; }
  .muted { color: #6E7390; }
  h1 { font-size: 15pt; color: #14304F; margin: 0 0 1mm 0; }
  .no { font-size: 9.5pt; color: #6E7390; margin: 0 0 6mm 0; }
  table.sum { width: 100%; border-collapse: collapse; margin: 4mm 0 5mm; }
  table.sum td { padding: 2.2mm 3mm; border: 0.2mm solid #D3DCE8; vertical-align: top; }
  table.sum td.l { width: 38%; }
  table.sum td.v { text-align: right; }
  table.sum tr.odd td { background-color: #E4EAF3; }
</style>

<h1>SURAT PENAWARAN</h1>
<p class="no">No. <?= $e($p['nomor']); ?></p>

<p>Kepada:<br>
<?php if ($company): ?>
  <b><?= $e($company['name_customer']); ?></b><br>
  <span class="muted">u.p. <?= $e($company['pic_name'] ?: '—'); ?><?= !empty($company['pic_jabatan']) ? ', ' . $e($company['pic_jabatan']) : ''; ?><br>
  <?= $e($company['address_office']); ?><?= !empty($company['kota']) ? ', ' . $e($company['kota']) : ''; ?></span>
<?php else: ?>
  <span class="muted">calon peserta</span>
<?php endif; ?>
</p>

<p>Dengan hormat, bersama ini kami menawarkan program berikut:</p>

<table class="sum">
  <?php foreach ($rows as $i => $r): ?>
    <tr class="<?= $i % 2 === 0 ? 'odd' : ''; ?>"><td class="l"><?= $e($r[0]); ?></td><td class="v"><?= $r[1]; ?></td></tr>
  <?php endforeach; ?>
</table>

<p class="muted" style="font-size: 9pt;">Angka diambil dari Penjualan Program Final dan tidak dapat diubah di sini.</p>
