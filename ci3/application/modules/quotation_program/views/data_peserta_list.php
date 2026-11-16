<?php
// Tab Data Peserta di Daftar Quotation: daftar batch (QTN-FR-29). Satu baris per Penjualan Program Final.
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$tgl = function ($d) { return $d ? date('d-m-Y', strtotime($d)) : '—'; };
$rng = function ($a, $b) use ($tgl) { return (!$b || $a === $b) ? $tgl($a) : $tgl($a) . ' s.d. ' . $tgl($b); };
$jenisTag = function ($j) { return '<span class="tag tag-' . ($j === 'b2b' ? 'b2b' : 'public') . '">' . ($j === 'b2b' ? 'B2B' : 'Public') . '</span>'; };
$ym = array_filter(['tahun' => $tahun, 'bulan' => $bulan]);
$ymQs = $ym ? '?' . http_build_query($ym) : '';
$totalPeserta = array_sum(array_column($rows, 'n_total'));
?>
<div class="page-head">
  <div>
    <h1>Daftar Quotation <span class="ref">QTN-FR-29</span></h1>
    <p>Data Peserta per Penjualan Program: calon peserta yang mengisi lewat link dari semua quotation batch itu. Klik <b>Lihat detail</b> untuk daftar peserta dan surat konfirmasi.</p>
  </div>
</div>

<div class="card">
  <div class="tabs">
    <a class="tab" href="<?= site_url('quotation_program/index/ongoing') . $ymQs; ?>">On going</a>
    <a class="tab" href="<?= site_url('quotation_program/index/done') . $ymQs; ?>">Selesai</a>
    <a class="tab active" href="<?= site_url('quotation_program/data_peserta') . $ymQs; ?>">Data Peserta<span class="nav-count"><?= (int)$totalPeserta; ?></span></a>
  </div>
  <form class="toolbar" method="get" action="<?= site_url('quotation_program/data_peserta'); ?>">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" name="q" value="<?= $e($q); ?>" placeholder="Cari program, nomor quotation, atau nama peserta…" aria-label="Cari">
    </div>
    <select class="input sm" name="tahun" aria-label="Filter tahun mulai acara" onchange="this.form.submit()">
      <option value="">Semua tahun</option>
      <?php foreach ($tahunOpts as $t): ?><option value="<?= (int)$t; ?>" <?= (int)$tahun === (int)$t ? 'selected' : ''; ?>><?= (int)$t; ?></option><?php endforeach; ?>
    </select>
    <select class="input sm" name="bulan" aria-label="Filter bulan mulai acara" onchange="this.form.submit()">
      <option value="">Semua bulan</option>
      <?php foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $b): ?><option value="<?= $i + 1; ?>" <?= (int)$bulan === $i + 1 ? 'selected' : ''; ?>><?= $b; ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline sm" type="submit">Cari</button>
  </form>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th style="width:45px;">No</th>
          <th>Penjualan Program</th>
          <th>Peserta</th>
          <th>Quotation</th>
          <th>Batch</th>
          <th class="act" style="width:130px;">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1; ?></td>
          <td class="title">
            <span class="code"><?= $e($r['nomor']); ?></span> <?= $jenisTag($r['jenis']); ?><br>
            <?= $e($r['nama_paket']); ?><br>
            <span class="muted"><?= $rng($r['tgl_mulai'], $r['tgl_selesai']); ?> · <?= $e($r['lokasi'] ?: '—'); ?></span>
          </td>
          <td>
            <b><?= (int)$r['n_total']; ?></b> <span class="muted">peserta</span><br>
            <span class="muted"><?= (int)$r['n']['deal']; ?> deal · <?= (int)$r['n']['menunggu']; ?> menunggu · <?= (int)$r['n']['batal']; ?> batal</span>
          </td>
          <td><b><?= (int)$r['n_quotation']; ?></b> <span class="muted">quotation</span></td>
          <td><span class="badge <?= $r['selesai'] ? 'off' : 'on'; ?>"><?= $r['selesai'] ? 'Selesai' : 'On going'; ?></span></td>
          <td class="act"><a class="btn btn-outline sm" href="<?= site_url('quotation_program/data_peserta?pj=' . (int)$r['id']) . ($ym ? '&' . http_build_query($ym) : ''); ?>">Lihat detail</a></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6" class="empty" style="text-align:center;">Tidak ada Penjualan Program yang cocok dengan filter.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
