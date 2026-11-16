<?php
// Detail batch Data Peserta (QTN-FR-29): peserta dari semua quotation satu Penjualan Program, Lihat/Unduh surat, Export PDF.
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$tgl = function ($d, $f = 'd-m-Y') { return $d ? date($f, strtotime($d)) : '—'; };
$rng = function ($a, $b) use ($tgl) { return (!$b || $a === $b) ? $tgl($a) : $tgl($a) . ' s.d. ' . $tgl($b); };
$ym = array_filter(['tahun' => $tahun, 'bulan' => $bulan]);
$back = site_url('quotation_program/data_peserta') . ($ym ? '?' . http_build_query($ym) : '');
$self = function ($extra = []) use ($pj, $ym) { return site_url('quotation_program/data_peserta') . '?' . http_build_query($ym + ['pj' => (int)$pj['id']] + $extra); };
$stl = ['deal' => 'Deal', 'menunggu' => 'Menunggu', 'batal' => 'Batal'];
$jumlah = count($semua);
?>
<div class="page-head">
  <div>
    <h1>Data Peserta — <?= $e($pj['nomor']); ?> <span class="ref">QTN-FR-29</span></h1>
    <p>
      <span class="tag tag-<?= $pj['jenis'] === 'b2b' ? 'b2b' : 'public'; ?>"><?= $pj['jenis'] === 'b2b' ? 'B2B' : 'Public'; ?></span>
      <b><?= $e($pj['nama_paket']); ?></b> · <?= $rng($pj['tgl_mulai'], $pj['tgl_selesai']); ?> · <?= $e($pj['lokasi'] ?: '—'); ?><br>
      <?= (int)$jumlah; ?> peserta dari <?= (int)$n_qtn; ?> quotation · <?= (int)$n['deal']; ?> deal · <?= (int)$n['menunggu']; ?> menunggu · <?= (int)$n['batal']; ?> batal
    </p>
  </div>
  <div class="page-actions" style="display:flex; gap:8px; flex-wrap:wrap;">
    <a class="btn btn-ghost" href="<?= $back; ?>">← Semua batch</a>
    <?php if ($jumlah && lm_can('quotation_program', 'cetak')): ?>
      <button type="button" class="btn btn-accent" onclick="exportPreview()">Export PDF</button>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <form class="toolbar" method="get" action="<?= site_url('quotation_program/data_peserta'); ?>">
    <input type="hidden" name="pj" value="<?= (int)$pj['id']; ?>">
    <?php foreach ($ym as $k => $v): ?><input type="hidden" name="<?= $k; ?>" value="<?= (int)$v; ?>"><?php endforeach; ?>
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" name="q" value="<?= $e($q); ?>" placeholder="Cari peserta, instansi, kontak, atau nomor quotation…" aria-label="Cari">
    </div>
    <select class="input sm" name="status" aria-label="Filter status peserta" onchange="this.form.submit()">
      <option value="all">Semua status (<?= (int)$jumlah; ?>)</option>
      <?php foreach ($stl as $k => $l): ?><option value="<?= $k; ?>" <?= $status === $k ? 'selected' : ''; ?>><?= $l; ?> (<?= (int)$n[$k]; ?>)</option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline sm" type="submit">Cari</button>
  </form>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th style="width:45px;">No</th><th>Peserta</th><th>Instansi</th><th>Kontak</th><th>Quotation</th><th>Masuk lewat</th><th>Status</th><th class="act" style="width:150px;">Action</th></tr></thead>
      <tbody>
      <?php if ($peserta): foreach ($peserta as $i => $p):
        $bisaSurat = !empty($p['surat_konfirmasi']) && in_array($p['status_quotation'], ['terkirim', 'deal'], true); ?>
        <tr<?= $p['status'] === 'batal' ? ' class="is-off"' : ''; ?>>
          <td><?= $i + 1; ?></td>
          <td><b><?= $e($p['nama']); ?></b><?= $p['jabatan'] ? '<br><small class="muted">' . $e($p['jabatan']) . '</small>' : ''; ?></td>
          <td><?= $e($p['instansi'] ?: '—'); ?></td>
          <td><?= $e($p['no_wa'] ?: ($p['no_hp'] ?: '—')); ?><?= $p['email'] ? '<br><small class="muted">' . $e($p['email']) . '</small>' : ''; ?></td>
          <td>
            <a href="<?= site_url('quotation_program/view/' . (int)$p['id_quotation'] . '#data-peserta'); ?>"><span class="code"><?= $e($p['nomor_quotation']); ?></span></a><br>
            <small class="muted"><?= $e($p['tujuan'] === 'company' ? ($p['nama_company'] ?: '—') : ($p['kepada_nama'] ?: '—')); ?></small>
          </td>
          <td><?= $p['sumber'] === 'company' ? 'Daftar PIC' : 'Link'; ?><br><small class="muted"><?= $tgl($p['tgl_isi'], 'd-m-Y H:i'); ?></small></td>
          <td><span class="badge <?= $p['status'] === 'deal' ? 'on' : ($p['status'] === 'batal' ? 'off' : ''); ?>"><?= $e($stl[$p['status']] ?? $p['status']); ?></span></td>
          <td class="act nowrap">
            <?php if ($bisaSurat): ?>
              <a class="btn btn-ghost sm" href="<?= site_url('quotation_program/surat/' . (int)$p['id']); ?>" target="_blank" rel="noopener">Lihat</a>
              <a class="btn btn-ghost sm" href="<?= site_url('quotation_program/surat/' . (int)$p['id'] . '?unduh=1'); ?>">Unduh</a>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="8" class="empty" style="text-align:center;"><?= $jumlah ? 'Tidak ada peserta yang cocok dengan filter.' : 'Belum ada calon peserta yang mengisi.'; ?></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Export PDF (QTN-FR-29): pratinjau dulu, lalu cetak / simpan PDF. Pratinjau tidak dicatat; mengunduh dicatat di Activity Log.
function exportPreview() {
  var pv = '<?= site_url('quotation_program/export_batch/' . (int)$pj['id']); ?>';
  openModal('<div class="overlay" onclick="if(event.target===this)closeModal()"><div class="modal xwide">' +
    '<header><h2>Export PDF — <?= $e($pj['nomor']); ?></h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>' +
    '<div class="modal-body"><p class="muted">Halaman 1 Daftar Peserta, lalu surat konfirmasi yang diunggah tiap peserta (B2B: surat company sekali per quotation). Peserta Batal tidak ikut.</p>' +
    '<div class="lm-loading" id="ex-load"><span class="lm-spin"></span> Menyusun PDF…</div>' +
    '<iframe id="ex-frame" class="pv-frame" style="display:none" title="Pratinjau Export PDF" src="' + pv + '?pv=1" onload="document.getElementById(\'ex-load\').style.display=\'none\'; this.style.display=\'block\'"></iframe></div>' +
    '<footer><button type="button" class="btn btn-ghost" onclick="closeModal()">Tutup</button><a class="btn btn-primary" href="' + pv + '" target="_blank" rel="noopener" onclick="closeModal()">Cetak / Simpan PDF</a></footer></div></div>');
}
</script>
