<?php
// Kartu Katalog Produk per Bidang (FR-61). Dirender ulang lewat master_data/katalog_list saat filter/cari berubah.
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$iconCert = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="9" r="6"/><path d="m8.5 13.5-1.5 8 5-3 5 3-1.5-8"/></svg>';
?>
<?php if (!$rows): ?>
  <div class="empty-state"><h2>Tidak ada paket produk</h2>
    <p><?= $ada_paket
      ? 'Tidak ada Paket Produk aktif yang cocok dengan filter. Ubah filter Bidang/Sertifikat atau kata kunci.'
      : 'Belum ada Paket Produk berstatus Aktif. Tambahkan atau aktifkan paket di menu Product.'; ?></p></div>
<?php else: ?>
  <div class="kt-sum" id="kt-sum" data-total="<?= count($rows); ?>"><span><b><?= count($rows); ?></b> paket produk · <b><?= count($groups); ?></b> bidang</span><span class="muted"><?= $e($filter_text); ?></span></div>
  <?php foreach ($groups as $g): ?>
    <section class="kt-group"><h3><?= $e($g['nama']); ?><span><?= count($g['items']); ?> paket</span></h3>
      <div class="kt-grid">
        <?php foreach ($g['items'] as $p): $lk = $p['lingkup']; $n = count($lk); ?>
          <article class="kt-card">
            <div class="kt-top"><span class="tag"><?= $e($p['nama_produk'] ?? '—'); ?></span><span class="code"><?= $e($p['nomor']); ?></span></div>
            <h4><?= $e($p['judul']); ?></h4>
            <p class="kt-var"><span>Varian</span> <?= $p['nama_varian'] !== null ? $e($p['nama_varian']) : '<span class="muted">Belum diisi</span>'; ?></p>
            <div class="kt-cert"><?= $iconCert; ?><span><?= $e($p['nama_sertifikasi'] ?? '—'); ?></span></div>
            <div class="kt-lk">
              <?php if ($n): ?>
                <p class="kt-lk-h">Lingkup</p><ul><?php foreach ($lk as $t): ?><li><?= $e($t); ?></li><?php endforeach; ?></ul>
              <?php else: ?><p class="muted kt-lk-empty">Lingkup belum diisi.</p><?php endif; ?>
              <?php if ($can_ubah): ?>
                <button type="button" class="btn btn-outline sm" data-lingkup="<?= $e(json_encode(['id' => (int)$p['id'], 'nomor' => $p['nomor'], 'judul' => $p['judul'],
                    'attr' => array_values(array_filter([$p['nama_produk'], $p['nama_bidang'], $p['nama_varian'], $p['nama_sertifikasi']])), 'items' => $lk], JSON_UNESCAPED_UNICODE)); ?>"
                  aria-label="<?= $n ? 'Ubah' : 'Isi'; ?> lingkup <?= $e($p['judul']); ?> (<?= $e($p['nomor']); ?>)"><?= $n ? 'Ubah lingkup (' . $n . ')' : 'Isi lingkup'; ?></button>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
