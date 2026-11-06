<?php
$rp = function ($n) { return ($n < 0 ? '−' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.'); };
$kpi = function ($k, $cls, $label, $val, $sub) {
    return '<div class="kpi dash-link ' . $cls . '" data-k="' . $k . '" role="button" tabindex="0" title="Klik untuk melihat rincian">'
        . '<div class="kpi-l">' . $label . ' <span class="kpi-more">rincian ›</span></div>'
        . '<div class="kpi-v">' . $val . '</div><div class="kpi-s">' . $sub . '</div></div>';
};
$todoUrl = function ($t) {
    return $t['type'] === 'mp' ? site_url('master_program/edit/' . $t['id']) : site_url('penjualan_program/view/' . $t['id']);
};
?>
<div class="dash-page">
<div class="page-head">
  <div>
    <h1>Dashboard <span class="ref">FR-40 – FR-42</span></h1>
    <p>Ringkasan Master Program dan Penjualan Program. Angka adalah total seluruh Penjualan Program Final (rencana).</p>
  </div>
</div>

<div class="kpis">
  <?= $kpi('final', '', 'Total Penjualan Program Final', (int)$kpis['total_final'], 'Public ' . (int)$kpis['public_count'] . ' · B2B ' . (int)$kpis['b2b_count']); ?>
  <?= $kpi('pend', '', 'Total pendapatan rencana', $rp($kpis['total_pendapatan']), (int)$kpis['total_peserta'] . ' peserta = total target peserta dari ' . (int)$kpis['total_final'] . ' Penjualan Final'); ?>
  <?= $kpi('prof', $kpis['total_profit'] < 0 ? 'bad' : 'good', 'Total profit rencana', $rp($kpis['total_profit']), 'Pendapatan − biaya (HPP) · margin ' . str_replace('.', ',', (string)$kpis['margin']) . '%'); ?>
  <?= $kpi('draft', $kpis['total_draft'] ? 'kwarn' : '', 'Total masih Draft', (int)$kpis['total_draft'], 'Master Program + Penjualan Program belum Final'); ?>
</div>

<div class="dash-grid">
  <section class="card dash-card">
    <h2>Jadwal pelaksanaan terdekat <span class="muted">Penjualan Program Final · klik baris untuk membuka</span></h2>
    <?php if (!empty($upcoming)): ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Tanggal</th><th>Program</th><th>Jenis</th><th class="money">Peserta</th></tr>
          </thead>
          <tbody>
            <?php foreach ($upcoming as $u): ?>
              <tr class="dash-link" data-href="<?= site_url('penjualan_program/view/' . $u['id']); ?>">
                <td class="nowrap">
                  <?= date('d/m/Y', strtotime($u['tgl_mulai'])); ?>
                  <?= !empty($u['tgl_selesai']) && $u['tgl_selesai'] !== $u['tgl_mulai'] ? ' – ' . date('d/m/Y', strtotime($u['tgl_selesai'])) : ''; ?>
                </td>
                <td class="title">
                  <?= htmlspecialchars($u['nama_paket'] ?? '—'); ?><br>
                  <span class="muted"><?= htmlspecialchars($u['nomor']); ?> · <?= htmlspecialchars(($u['jenis'] === 'b2b' ? ($u['nama_company'] ?? '') : ($u['lokasi'] ?? '')) ?: '—'); ?></span>
                </td>
                <td><span class="tag tag-<?= htmlspecialchars($u['jenis']); ?>"><?= strtoupper(htmlspecialchars($u['jenis'])); ?></span></td>
                <td class="money"><?= (int)$u['target_peserta']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="dash-empty">Belum ada Penjualan Program Final yang akan dilaksanakan.</p>
    <?php endif; ?>
  </section>

  <section class="card dash-card">
    <h2>Perlu tindakan</h2>
    <?php if (!empty($todos)): ?>
      <ul class="dash-list">
        <?php foreach (array_slice($todos, 0, 8) as $t): ?>
          <li class="dash-link" data-href="<?= $todoUrl($t); ?>">
            <span class="code"><?= htmlspecialchars($t['nomor']); ?></span> <?= htmlspecialchars($t['judul'] ?? '—'); ?><br>
            <span class="<?= $t['is_warn'] ? 'neg' : 'muted'; ?>"><?= htmlspecialchars($t['msg']); ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count($todos) > 8): ?><p class="dash-empty">+<?= count($todos) - 8; ?> lainnya</p><?php endif; ?>
    <?php else: ?>
      <p class="dash-empty">Tidak ada yang perlu ditindaklanjuti.</p>
    <?php endif; ?>
  </section>
</div>
</div>

<script>
(function () {
  var page = document.querySelector('.dash-page');
  function openKpi(k) {
    // Tampilkan modal berisi spinner dulu agar tidak terlihat macet saat data dimuat.
    openModal('<div class="overlay" onclick="if(event.target===this)closeModal()"><div class="modal"><div class="lm-loading"><span class="lm-spin"></span>Memuat rincian…</div></div></div>');
    fetch('<?= site_url('dashboard_labmania/kpi_detail/'); ?>' + k, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw 0; return r.text(); })
      .then(function (html) { openModal(html); })
      .catch(function () { closeModal(); toast('Rincian gagal dimuat', 'error'); });
  }
  // Satu handler: kartu KPI buka modal, baris lain pindah ke dokumen (juga untuk baris di dalam modal).
  document.addEventListener('click', function (e) {
    var el = e.target.closest('.dash-link');
    if (!el || !(page.contains(el) || el.closest('#modal-root'))) return;
    if (el.dataset.k) openKpi(el.dataset.k); else if (el.dataset.href) { el.classList.add('is-loading'); document.body.style.cursor = 'progress'; location.href = el.dataset.href; }
  });
  page.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.k) { e.preventDefault(); openKpi(e.target.dataset.k); }
  });
})();
</script>
