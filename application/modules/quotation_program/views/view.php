<?php
$c = $q['calc'];
$jenisPj = $q['jenis_pj'];
$status = $q['status'];
$tgl = function ($d, $fmt = 'd F Y') { return $d ? date($fmt, strtotime($d)) : '—'; };
$stPs = ['menunggu' => 'Menunggu', 'deal' => 'Deal', 'batal' => 'Batal'];
?>
<div class="page-head">
  <div>
    <h1>Quotation <?= htmlspecialchars($q['nomor']); ?> <span class="ref">QTN-FR-04 – QTN-FR-13</span></h1>
    <p>Pratinjau detail penawaran resmi, daftar peserta terdaftar, dan klausul syarat ketentuan.</p>
  </div>
  <div class="page-actions" style="display:flex; gap:8px;">
    <a href="<?= site_url('quotation_program'); ?>" class="btn btn-muted">&larr; Kembali ke Daftar</a>
    <?php if ($status === 'draft' && lm_can('quotation_program', 'ubah')): ?>
      <a href="<?= site_url('quotation_program/edit/' . $q['id']); ?>" class="btn btn-outline">Edit Quotation</a>
    <?php endif; ?>
    <a href="<?= site_url('quotation_program/pdf/' . $q['id']); ?>" target="_blank" class="btn btn-accent">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Cetak Quotation (PDF)
    </a>
    <?php if ($jenisPj !== 'b2b' && $status === 'deal'): ?>
      <a href="<?= site_url('quotation_program/confirmation/' . $q['id']); ?>" target="_blank" class="btn btn-primary">
        Cetak Confirmation Letter
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- 1. INFORMASI SURAT & PROGRAM -->
<div class="card" style="margin-bottom: 20px;">
  <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:16px;">
    <h3 style="margin:0; color:var(--navy);">1. Informasi Dokumen & Program</h3>
    <div>
      <?php if ($status === 'draft'): ?>
        <span class="badge" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba;">Draft</span>
      <?php elseif ($status === 'terkirim'): ?>
        <span class="badge" style="background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;">Terkirim</span>
      <?php elseif ($status === 'deal'): ?>
        <span class="badge on">Deal (Won)</span>
      <?php else: ?>
        <span class="badge off">Batal (Lost)</span>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:16px;">
    <div>
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Nomor Quotation</label>
      <div style="font-size:15px; font-weight:700; color:var(--navy);"><span class="code"><?= htmlspecialchars($q['nomor']); ?></span></div>
    </div>
    <div>
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Tanggal Surat</label>
      <div style="font-size:13px; font-weight:600;"><?= $tgl($q['tanggal']); ?></div>
    </div>
    <div>
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Berlaku Sampai</label>
      <div style="font-size:13px; font-weight:600;"><?= $tgl($q['berlaku_sampai']); ?></div>
    </div>
    <div>
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Sales / Marketing</label>
      <div style="font-size:13px; font-weight:600;"><?= htmlspecialchars($q['nama_sales'] ?? '—'); ?></div>
    </div>

    <div>
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Penjualan Program</label>
      <div style="font-size:13px; font-weight:600;">
        <span class="code"><?= htmlspecialchars($q['nomor_pj']); ?></span>
        <?php if ($jenisPj === 'b2b'): ?>
          <span class="tag tag-b2b" style="font-size:10px;">B2B</span>
        <?php else: ?>
          <span class="tag tag-public" style="font-size:10px;">Public</span>
        <?php endif; ?>
      </div>
    </div>
    <div style="grid-column: span 2;">
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Paket Pelatihan</label>
      <div style="font-size:13px; font-weight:700; color:var(--navy);"><?= htmlspecialchars($q['nama_paket'] ?? '—'); ?></div>
    </div>
    <div>
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Jadwal Pelaksanaan</label>
      <div style="font-size:13px; font-weight:600;">
        <?= $tgl($q['tgl_mulai'], 'd M Y'); ?> – <?= $tgl($q['tgl_selesai'], 'd M Y'); ?> (<?= (int)$q['durasi_hari']; ?> Hari)
      </div>
    </div>

    <div style="grid-column: 1 / -1; background:#f8fafc; border:1px solid var(--line); border-radius:6px; padding:12px 16px;">
      <div style="display:grid; grid-template-columns: 1fr 2fr; gap:16px;">
        <div>
          <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Ditujukan Kepada</label>
          <div style="font-size:14px; font-weight:700; color:var(--navy);">
            <?php if ($q['tujuan'] === 'company'): ?>
              <?= htmlspecialchars($q['nama_company'] ?? '—'); ?> <span class="tag tag-b2b" style="font-size:10px;">Company</span>
            <?php else: ?>
              Peserta Personal <span class="tag tag-public" style="font-size:10px;">Personal</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($q['up_nama'])): ?>
            <div style="font-size:12px; color:var(--muted); margin-top:2px;">u.p. <b><?= htmlspecialchars($q['up_nama']); ?></b></div>
          <?php endif; ?>
        </div>
        <div>
          <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Alamat</label>
          <div style="font-size:13px; color:#334155;"><?= nl2br(htmlspecialchars($q['alamat'] ?: '—')); ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 2. PESERTA & NILAI PENAWARAN -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
  <!-- KIRI: PESERTA -->
  <div class="card">
    <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:16px;">
      2. Peserta Terdaftar (<?= count($q['peserta']); ?>)
    </h3>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:40px;">No</th>
            <th>Nama & NIK</th>
            <th>Instansi</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($q['peserta'])): $no = 1; foreach ($q['peserta'] as $ps): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td>
                <b><?= htmlspecialchars($ps['nama_peserta']); ?></b><br>
                <small class="muted"><span class="code"><?= htmlspecialchars($ps['kode_peserta']); ?></span> · <?= htmlspecialchars($ps['no_telp'] ?? ''); ?></small>
              </td>
              <td><?= htmlspecialchars($ps['instansi'] ?: 'Personal'); ?></td>
              <td><span class="badge <?= $ps['status'] === 'deal' ? 'on' : ($ps['status'] === 'batal' ? 'off' : ''); ?>"><?= $stPs[$ps['status']] ?? htmlspecialchars($ps['status']); ?></span></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="4" class="muted" style="text-align:center;">Belum ada peserta terdaftar</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- KANAN: REKAPITULASI BIAYA -->
  <div class="card">
    <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:16px;">
      3. Rincian Biaya Penawaran
    </h3>
    <table style="width:100%; font-size:13px; line-height:2.2;">
      <tr>
        <td>Skema Penawaran:</td>
        <td style="text-align:right; font-weight:700;">
          <?= $q['skema_harga'] === 'allin' ? 'All-in Lumpsum (1 Company)' : 'Per Peserta'; ?>
        </td>
      </tr>
      <tr>
        <td>Subtotal Biaya:</td>
        <td style="text-align:right; font-weight:600;">Rp <?= number_format($c['subtotal'], 0, ',', '.'); ?></td>
      </tr>
      <tr>
        <td>Potongan Diskon:</td>
        <td style="text-align:right; color:#e74c3c; font-weight:600;">- Rp <?= number_format($c['total_diskon'], 0, ',', '.'); ?></td>
      </tr>
      <tr style="border-top:1px dashed var(--line);">
        <td>Dasar Pengenaan Pajak (DPP):</td>
        <td style="text-align:right; font-weight:700;">Rp <?= number_format($c['dpp'], 0, ',', '.'); ?></td>
      </tr>
      <tr>
        <td>PPN (<?= $q['is_ppn'] ? $q['ppn_persen'] . '%' : '0%'; ?>):</td>
        <td style="text-align:right; font-weight:600;">Rp <?= number_format($c['ppn_nominal'], 0, ',', '.'); ?></td>
      </tr>
      <tr>
        <td>Biaya Lain-lain:</td>
        <td style="text-align:right; font-weight:600;">Rp <?= number_format($c['biaya_lain'], 0, ',', '.'); ?></td>
      </tr>
      <tr style="border-top:2px solid var(--navy); font-size:16px;">
        <td style="color:var(--navy); font-weight:700; padding-top:8px;">Grand Total:</td>
        <td style="text-align:right; color:var(--navy); font-weight:800; padding-top:8px;">Rp <?= number_format($c['grand_total'], 0, ',', '.'); ?></td>
      </tr>
      <tr>
        <td colspan="2" style="text-align:right; color:var(--muted); font-size:11px;">
          Setara Rp <?= number_format($c['net_per_pax'], 0, ',', '.'); ?> / peserta
        </td>
      </tr>
    </table>
  </div>
</div>

<!-- 4. KLAUSUL SYARAT & KETENTUAN -->
<div class="card" style="margin-bottom: 24px;">
  <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:16px;">
    4. Syarat & Ketentuan Penawaran
  </h3>
  <div style="font-size:13px; line-height:1.7;">
    <?php if (!empty($q['keterangan'])): ?>
      <ol style="padding-left:20px;">
        <?php foreach ($q['keterangan'] as $ket): ?>
          <li style="margin-bottom:6px;">
            <b>[<?= ucfirst($ket['kategori']); ?>]</b> <?= htmlspecialchars($ket['teks']); ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p class="muted">Tidak ada klausul keterangan.</p>
    <?php endif; ?>
  </div>
</div>
