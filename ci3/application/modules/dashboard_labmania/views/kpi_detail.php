<?php
$e   = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$rp  = function ($n) { return ($n < 0 ? '−' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.'); };
$pct = function ($p, $d) { return $d ? str_replace('.', ',', (string)round($p / $d * 100, 1)) . '%' : '—'; };
$tgl = function ($r) {
    $t = date('d/m/Y', strtotime($r['tgl_mulai']));
    return !empty($r['tgl_selesai']) && $r['tgl_selesai'] !== $r['tgl_mulai'] ? $t . ' – ' . date('d/m/Y', strtotime($r['tgl_selesai'])) : $t;
};
$pjCell = function ($r) use ($e, $tgl) {
    $lokasi = $r['jenis'] === 'b2b' ? ($r['nama_company'] ?? '—') : ($r['lokasi'] ?? '—');
    return '<span class="code">' . $e($r['nomor']) . '</span> ' . $e($r['nama_paket'] ?? '—')
        . '<br><span class="muted"><span class="tag tag-' . $e($r['jenis']) . '">' . strtoupper($e($r['jenis'])) . '</span> '
        . $e($lokasi ?: '—') . ' · ' . $tgl($r) . '</span>';
};
$pjRow = function ($r, $cells) {
    return '<tr class="dash-link" data-href="' . site_url('penjualan_program/view/' . $r['id']) . '">' . $cells . '</tr>';
};

$FINAL_SRC = 'menu <b>Daftar Penjualan Program</b>, hanya yang berstatus <b>Final</b> (Draft dan Batal tidak dihitung). Angka rencana = isian Penjualan Program saat difinalkan.';
$sum = function ($rows, $f) { $t = 0; foreach ($rows as $r) $t += $f($r); return $t; };
$finals = $finals ?? [];
$n = count($finals);
$pend = $sum($finals, function ($r) { return $r['c']['pendapatan']; });
$prof = $sum($finals, function ($r) { return $r['c']['profit']; });
$peserta = $sum($finals, function ($r) { return (int)$r['target_peserta']; });
$empty = '<p class="dash-empty">Belum ada data.</p>';

$titles = ['final' => 'Total Penjualan Program Final', 'pend' => 'Total pendapatan rencana', 'prof' => 'Total profit rencana', 'draft' => 'Total masih Draft'];
?>
<div class="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal xwide" role="dialog" aria-modal="true">
    <header><h2>Rincian — <?= $titles[$k]; ?></h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>
    <div class="modal-body">
<?php if ($k === 'draft'): ?>
      <p class="dash-src"><b>Sumber data:</b> menu <b>Daftar Master Program</b> dan <b>Daftar Penjualan Program</b> yang berstatus <b>Draft</b> (belum difinalkan). Klik baris untuk melanjutkan.</p>
      <?php if ($mp || $pj): ?>
      <div class="table-wrap"><table class="table dash-prev">
        <thead><tr><th>Data</th><th>Jenis data</th></tr></thead>
        <tbody>
          <?php foreach ($mp as $r): ?>
            <tr class="dash-link" data-href="<?= site_url('master_program/edit/' . $r['id']); ?>"><td><span class="code"><?= $e($r['nomor']); ?></span> <?= $e($r['nama_paket'] ?? '—'); ?></td><td>Master Program</td></tr>
          <?php endforeach; foreach ($pj as $r): ?>
            <?= $pjRow($r, '<td>' . $pjCell($r) . '</td><td>Penjualan Program</td>'); ?>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td><b>Total <?= count($mp) + count($pj); ?></b></td><td class="muted">Master Program <?= count($mp); ?> · Penjualan Program <?= count($pj); ?></td></tr></tfoot>
      </table></div>
      <?php else: echo $empty; endif; ?>
<?php elseif (!$finals): ?>
      <p class="dash-src"><b>Sumber data:</b> <?= $FINAL_SRC; ?></p><?= $empty; ?>
<?php elseif ($k === 'final'): ?>
      <p class="dash-src"><b>Sumber data:</b> <?= $FINAL_SRC; ?></p>
      <div class="table-wrap"><table class="table dash-prev">
        <thead><tr><th>Penjualan Program</th><th class="money">Target peserta</th></tr></thead>
        <tbody><?php foreach ($finals as $r) echo $pjRow($r, '<td>' . $pjCell($r) . '</td><td class="money">' . (int)$r['target_peserta'] . '</td>'); ?></tbody>
        <tfoot><tr><td><b>Total <?= $n; ?> penjualan</b> <span class="muted">(Public <?= count(array_filter($finals, function ($r) { return $r['jenis'] === 'public'; })); ?> · B2B <?= count(array_filter($finals, function ($r) { return $r['jenis'] === 'b2b'; })); ?>)</span></td><td class="money"><b><?= $peserta; ?></b></td></tr></tfoot>
      </table></div>
<?php elseif ($k === 'pend'): ?>
      <p class="dash-src"><b>Sumber data:</b> <?= $FINAL_SRC; ?></p>
      <p class="dash-src"><b>Rumus:</b> pendapatan = harga jual / peserta × target peserta, lalu dijumlah semua penjualan.</p>
      <div class="table-wrap"><table class="table dash-prev">
        <thead><tr><th>Penjualan Program</th><th class="money">Harga jual / peserta</th><th class="money">Target peserta</th><th class="money">Pendapatan</th></tr></thead>
        <tbody><?php foreach ($finals as $r) echo $pjRow($r, '<td>' . $pjCell($r) . '</td><td class="money">' . $rp($r['harga_jual_peserta']) . '</td><td class="money">× ' . (int)$r['target_peserta'] . '</td><td class="money">' . $rp($r['c']['pendapatan']) . '</td>'); ?></tbody>
        <tfoot><tr><td colspan="2"><b>Total</b></td><td class="money"><b><?= $peserta; ?></b></td><td class="money"><b><?= $rp($pend); ?></b></td></tr></tfoot>
      </table></div>
<?php else: /* prof */ ?>
      <p class="dash-src"><b>Sumber data:</b> <?= $FINAL_SRC; ?></p>
      <p class="dash-src"><b>Rumus:</b> profit = pendapatan − HPP. HPP = Fixed Cost + Variable Cost (bagian 2 &amp; 3 di form Penjualan Program). Margin = profit ÷ pendapatan.</p>
      <div class="table-wrap"><table class="table dash-prev">
        <thead><tr><th>Penjualan Program</th><th class="money">Pendapatan</th><th class="money">Fixed Cost</th><th class="money">Variable Cost</th><th class="money">Profit</th><th class="money">Margin</th></tr></thead>
        <tbody><?php foreach ($finals as $r) { $c = $r['c'];
            echo $pjRow($r, '<td>' . $pjCell($r) . '</td><td class="money">' . $rp($c['pendapatan']) . '</td><td class="money">− ' . $rp($c['total_fixed']) . '</td><td class="money">− ' . $rp($c['total_var']) . '</td><td class="money ' . ($c['profit'] < 0 ? 'neg' : 'pos') . '">' . $rp($c['profit']) . '</td><td class="money">' . $pct($c['profit'], $c['pendapatan']) . '</td>'); } ?></tbody>
        <tfoot><tr><td><b>Total</b></td><td class="money"><b><?= $rp($pend); ?></b></td>
          <td class="money"><b>− <?= $rp($sum($finals, function ($r) { return $r['c']['total_fixed']; })); ?></b></td>
          <td class="money"><b>− <?= $rp($sum($finals, function ($r) { return $r['c']['total_var']; })); ?></b></td>
          <td class="money <?= $prof < 0 ? 'neg' : 'pos'; ?>"><b><?= $rp($prof); ?></b></td><td class="money"><b><?= $pct($prof, $pend); ?></b></td></tr></tfoot>
      </table></div>
<?php endif; ?>
      <p class="dash-src muted">Klik baris untuk membuka datanya.</p>
    </div>
    <footer><button type="button" class="btn btn-muted" onclick="closeModal()">Tutup</button></footer>
  </div>
</div>
