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
    <p>Detail penawaran, link Konfirmasi Kehadiran, Data Peserta, dan klausul syarat ketentuan.</p>
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
    <?php if ($so): ?><span class="btn btn-ghost" title="Sales Order dibentuk otomatis saat Deal">Sales Order <b><?= htmlspecialchars($so['nomor']); ?></b></span><?php endif; ?>
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
      <label style="font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700;">Kuota Peserta</label>
      <div style="font-size:13px; font-weight:600;"><?= (int)$q['kuota_peserta']; ?> peserta</div>
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
              <?= htmlspecialchars($q['kepada_nama'] ?: '—'); ?> <span class="tag tag-public" style="font-size:10px;">Personal</span>
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
  <!-- KIRI: LINK KONFIRMASI KEHADIRAN (QTN-FR-23) -->
  <div class="card">
    <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:16px;">2. Link Konfirmasi Kehadiran</h3>
    <div class="field">
      <label for="link-url">Alamat link</label>
      <input class="input" id="link-url" readonly value="<?= htmlspecialchars($link_url); ?>" onclick="this.select()">
      <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
        <button type="button" class="btn btn-outline sm" onclick="salinLink()">Salin link</button>
        <a class="btn btn-ghost sm" href="<?= htmlspecialchars($link_url); ?>" target="_blank" rel="noopener">Buka</a>
        <span class="badge <?= $link['buka'] ? 'on' : 'off'; ?>"><?= $link['buka'] ? 'Link terbuka' : 'Link tertutup'; ?></span>
      </div>
    </div>
    <p style="font-size:13px;"><?= htmlspecialchars($link['alasan']); ?>.<br><span class="muted">Berlaku sampai <?= $tgl($q['berlaku_sampai'], 'd-m-Y'); ?> · dibuat <?= $tgl($q['link_dibuat'], 'd-m-Y H:i'); ?></span></p>
    <?php if ($jenisPj !== 'b2b'): $pct = $q['kuota_peserta'] > 0 ? min(100, round($link['terisi'] / $q['kuota_peserta'] * 100)) : 0; ?>
      <div style="font-size:13px; margin-bottom:4px;"><b><?= (int)$link['terisi']; ?></b> dari <?= (int)$q['kuota_peserta']; ?> kuota terisi
        · <?= $link['terisi'] > $q['kuota_peserta'] ? ($link['terisi'] - $q['kuota_peserta']) . ' melebihi kuota' : ($link['sisa'] > 0 ? $link['sisa'] . ' kursi tersisa' : 'kuota penuh'); ?></div>
      <div style="height:8px; background:#e4eaf3; border-radius:4px; overflow:hidden;"><div style="height:8px; width:<?= $pct; ?>%; background:var(--navy);"></div></div>
    <?php endif; ?>
    <p class="muted" style="font-size:12px; margin-top:12px;">
      <b>Isian yang diminta:</b>
      <?php if ($jenisPj === 'b2b'): ?>
        Data perusahaan dan PIC, daftar peserta (Nama<?= $kolom_b2b ? ', ' . htmlspecialchars(implode(', ', $kolom_b2b)) : ''; ?>), surat konfirmasi company bertanda tangan dan cap.
      <?php else: $kfl = Master_data_model::KF_FIELDS; foreach ($isian as $k => $kk): ?><?= $k ? ', ' : ''; ?><?= htmlspecialchars(rtrim($kfl[$kk] ?? $kk, '*')); ?><?= $kk === 'nama' ? ' (wajib)' : ''; ?><?php endforeach; ?>
      <?php endif; ?>
    </p>
  </div>

  <!-- KANAN: REKAPITULASI BIAYA -->
  <div class="card">
    <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:16px;">
      Rincian Biaya Penawaran
    </h3>
    <table style="width:100%; font-size:13px; line-height:2.2;">
      <tr>
        <td>Skema Penawaran:</td>
        <td style="text-align:right; font-weight:700;">
          <?= $q['skema_harga'] === 'allin' ? 'All-in Lumpsum (1 Company)' : 'Per Peserta'; ?>
        </td>
      </tr>
      <?php if ($c['skema'] === 'allin'): ?>
      <tr><td>Harga all-in (s.d. <?= (int)$c['paket_maks']; ?> peserta):</td><td style="text-align:right; font-weight:600;">Rp <?= number_format($c['harga_allin'], 0, ',', '.'); ?></td></tr>
      <tr><td>Tambahan peserta:<?= $c['n_tambahan'] ? '<br><span class="muted" style="font-size:11px; line-height:1.3;">' . (int)$c['n_tambahan'] . ' peserta di atas ' . (int)$c['paket_maks'] . ' × Rp ' . number_format($c['tarif_tambahan'], 0, ',', '.') . '</span>' : ''; ?></td><td style="text-align:right; font-weight:600; vertical-align:top;"><?= $c['n_tambahan'] ? 'Rp ' . number_format($c['tambahan'], 0, ',', '.') : 'tidak ada'; ?></td></tr>
      <?php endif; ?>
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
          Setara Rp <?= number_format($c['net_per_pax'], 0, ',', '.'); ?> / peserta <?= $status === 'deal' ? '(dari ' . (int)$c['n_peserta'] . ' peserta Deal)' : '(dihitung dari kuota ' . (int)$c['n_peserta'] . ')'; ?>
        </td>
      </tr>
    </table>
  </div>
</div>

<!-- 3. DATA PESERTA (QTN-FR-26 - QTN-FR-28) -->
<div class="card" style="margin-bottom:20px;" id="data-peserta">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:12px;">
    <h3 style="margin:0; color:var(--navy);">Data Peserta (<?= count($q['peserta']); ?>)</h3>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <?php if (!empty($q['peserta']) && lm_can('quotation_program', 'cetak')): ?>
        <a class="btn btn-outline sm" href="<?= site_url('quotation_program/excel/' . $q['id']); ?>">Unduh Excel</a>
      <?php endif; ?>
    </div>
  </div>
  <p class="muted" style="font-size:13px; margin-top:0;">
    <?= $jenisPj === 'b2b' ? 'Daftar dikirim PIC company lewat link.' : 'Semua peserta mengisi sendiri lewat link Konfirmasi Kehadiran.'; ?>
    <?php if ($jenisPj === 'b2b' && !empty($q['konf_company'])): $kc = $q['konf_company']; ?>
      <br><b>Konfirmasi company:</b> <?= htmlspecialchars($kc['nama_perusahaan']); ?> · PIC <?= htmlspecialchars($kc['pic_nama']); ?> (<?= htmlspecialchars($kc['pic_jabatan']); ?>) · <?= htmlspecialchars($kc['pic_hp']); ?> · <?= htmlspecialchars($kc['pic_email']); ?>
      · surat: <?= htmlspecialchars($kc['surat_konfirmasi_nama']); ?> · diunggah <?= $tgl($kc['tgl_kirim'], 'd-m-Y H:i'); ?>
    <?php endif; ?>
  </p>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th style="width:40px;">No</th><th>Peserta</th><th>Instansi</th><th>Kontak</th><th>Masuk lewat</th><th>Status</th><th class="act">Surat</th></tr></thead>
      <tbody>
      <?php if (!empty($q['peserta'])): $no = 1; foreach ($q['peserta'] as $ps): ?>
        <tr>
          <td><?= $no++; ?></td>
          <td><b><?= htmlspecialchars($ps['nama']); ?></b><?= $ps['jabatan'] ? '<br><small class="muted">' . htmlspecialchars($ps['jabatan']) . '</small>' : ''; ?></td>
          <td><?= htmlspecialchars($ps['instansi'] ?: '—'); ?></td>
          <td><?= htmlspecialchars($ps['no_wa'] ?: ($ps['no_hp'] ?: '—')); ?><?= $ps['email'] ? '<br><small class="muted">' . htmlspecialchars($ps['email']) . '</small>' : ''; ?></td>
          <td><?= $ps['sumber'] === 'company' ? 'Daftar PIC' : 'Link'; ?><br><small class="muted"><?= $tgl($ps['tgl_isi'], 'd-m-Y H:i'); ?></small></td>
          <td><span class="badge <?= $ps['status'] === 'deal' ? 'on' : ($ps['status'] === 'batal' ? 'off' : ''); ?>"><?= $stPs[$ps['status']] ?? htmlspecialchars($ps['status']); ?></span></td>
          <td class="act nowrap">
            <?php if ($ps['surat_konfirmasi'] && in_array($status, ['terkirim', 'deal'], true)): ?>
              <a class="btn btn-ghost sm" href="<?= site_url('quotation_program/surat/' . $ps['id']); ?>" target="_blank" rel="noopener">Lihat</a>
              <a class="btn btn-ghost sm" href="<?= site_url('quotation_program/surat/' . $ps['id'] . '?unduh=1'); ?>">Unduh</a>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7" class="muted" style="text-align:center;">Belum ada calon peserta yang mengisi. <button type="button" class="btn btn-ghost sm" onclick="salinLink()">Salin link</button></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($status === 'draft' && !empty($q['peserta'])): ?><p class="hint">Surat konfirmasi dapat dilihat dan diunduh sejak quotation Terkirim.</p><?php endif; ?>
</div>

<?php if (!empty($q['dokumen'])): ?>
<!-- DOKUMEN DEAL (QTN-FR-11) -->
<div class="card" style="margin-bottom:20px;">
  <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid var(--line); padding-bottom:8px; margin-bottom:12px;">Dokumen Deal</h3>
  <?php if (!empty($q['dokumen_keterangan'])): ?><p style="font-size:13px; margin-top:0;"><b>Keterangan:</b> <?= htmlspecialchars($q['dokumen_keterangan']); ?></p><?php endif; ?>
  <ul style="padding-left:18px; font-size:13px;">
    <?php foreach ($q['dokumen'] as $d): ?>
      <li><?= htmlspecialchars($d['nama_file']); ?> <span class="muted">· <?= $d['ukuran_byte'] >= 1048576 ? round($d['ukuran_byte'] / 1048576, 1) . ' MB' : max(1, round($d['ukuran_byte'] / 1024)) . ' KB'; ?></span>
        — <a href="<?= site_url('quotation_program/dokumen/' . $d['id']); ?>" target="_blank" rel="noopener">Lihat</a> · <a href="<?= site_url('quotation_program/dokumen/' . $d['id'] . '?unduh=1'); ?>">Unduh</a></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<script>
function salinLink() {
  var el = document.getElementById('link-url');
  var done = function () { toast('Link disalin', 'success'); };
  if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(el.value).then(done, function () { el.select(); document.execCommand('copy'); done(); }); }
  else { el.select(); document.execCommand('copy'); done(); }
}
</script>

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
