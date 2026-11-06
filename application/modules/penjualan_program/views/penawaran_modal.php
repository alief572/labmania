<?php
// Modal pratinjau Surat Penawaran (PJ-FR-20), mengikuti qtPrint di mockup. Angka dari Penjualan Program Final, tidak dapat diubah di sini.
$e  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$rp = function ($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
$row = function ($l, $v) { return '<div class="sum-row"><span>' . $l . '</span><b>' . $v . '</b></div>'; };
?>
<div class="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal wide" role="dialog" aria-modal="true">
    <header><h2>Surat Penawaran</h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>
    <div class="modal-body">
      <div class="letter">
        <p><b>SURAT PENAWARAN</b><br><span class="muted">No. <?= $e($p['nomor']); ?></span></p>
        <p>Kepada:
          <?php if ($company): ?>
            <b><?= $e($company['name_customer']); ?></b><br><span class="muted">u.p. <?= $e($company['pic_name'] ?: '—'); ?><?= !empty($company['pic_jabatan']) ? ', ' . $e($company['pic_jabatan']) : ''; ?> · <?= $e($company['address_office']); ?><?= !empty($company['kota']) ? ', ' . $e($company['kota']) : ''; ?></span>
          <?php else: ?><span class="muted">calon peserta</span><?php endif; ?>
        </p>
        <p>Dengan hormat, bersama ini kami menawarkan program berikut:</p>
        <div class="summary">
          <?= $row('Program', $e($p['nama_paket']) . ' <span class="muted">(' . $e($p['nama_sertifikasi']) . ')</span>'); ?>
          <?= $row('Pelaksanaan', $e($pelaksanaan)); ?>
          <?= $row('Durasi', (int)$p['durasi_hari'] . ' hari'); ?>
          <?= !empty($p['nama_trainer']) ? $row('Trainer', $e($p['nama_trainer'])) : ''; ?>
          <?= $row('Harga / peserta', $rp($p['harga_jual_peserta'])); ?>
          <?= $row('Jumlah peserta', (int)$p['target_peserta'] . ' peserta'); ?>
          <?= $row('Total', $rp($total)); ?>
        </div>
        <p class="muted">Angka diambil dari Penjualan Program Final dan tidak dapat diubah di sini.</p>
      </div>
    </div>
    <footer>
      <button type="button" class="btn btn-muted" onclick="closeModal()">Tutup</button>
      <a class="btn btn-accent" target="_blank" rel="noopener" href="<?= site_url('penjualan_program/penawaran_pdf/' . (int)$p['id']); ?>" onclick="closeModal()">Cetak PDF</a>
    </footer>
  </div>
</div>
