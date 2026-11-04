<div class="page-head">
  <div>
    <h1>Master Trainer <span class="ref">FR-44 – FR-47</span></h1>
    <p>Data trainer/fasilitator beserta riwayatnya (format CV trainer). Trainer aktif dipilih di Penjualan Program — satu trainer per event.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_trainer', 'tambah')): ?><a href="<?= site_url('master_trainer/add'); ?>" class="btn btn-accent">+ Tambah Trainer</a><?php endif; ?>
  </div>
</div>

<div class="notice"><span>Trainer <b>tidak dapat dihapus</b> — gunakan <b>Nonaktifkan</b>. Trainer nonaktif tidak muncul di pilihan Penjualan Program.</span><span class="ref">FR-46</span></div>

<?php if ($this->session->flashdata('success')): ?>
  <div class="notice" style="background:#eafaf1; border-left:4px solid #2ecc71; color:#27ae60; margin-bottom:16px;">
    <?= $this->session->flashdata('success'); ?>
  </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
  <div class="notice" style="background:#fdeeee; border-left:4px solid #e74c3c; color:#c0392b; margin-bottom:16px;">
    <?= $this->session->flashdata('error'); ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari ID, nama, atau bidang keahlian…" aria-label="Cari">
    </div>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <option value="on">Aktif</option>
      <option value="off">Nonaktif</option>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-search-cols="[1,2,3]">
      <thead>
        <tr>
          <th style="width: 45px;">No</th>
          <th style="width: 100px;">ID Trainer</th>
          <th>Nama Trainer</th>
          <th>Bidang Keahlian</th>
          <th>Riwayat</th>
          <th class="money">Penjualan Program</th>
          <th style="width: 110px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 64px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($trainers)): $no = 1; foreach ($trainers as $t): ?>
          <tr data-id="<?= $t['id']; ?>" data-aktif="<?= $t['aktif'] ? 1 : 0; ?>" class="<?= $t['aktif'] ? '' : 'is-off'; ?>">
            <td><?= $no++; ?></td>
            <td><span class="code"><?= htmlspecialchars($t['kode']); ?></span></td>
            <td><?= htmlspecialchars($t['nama']); ?><br><span class="muted"><?= htmlspecialchars($t['no_telp'] ?: '—'); ?></span></td>
            <td>
              <?php
                $keahlian = Master_trainer_model::split_keahlian($t['bidang_keahlian'] ?? '');
                foreach (array_slice($keahlian, 0, 2) as $k) echo '<span class="ps-chip">' . htmlspecialchars($k) . '</span> ';
                if (count($keahlian) > 2) echo '<span class="muted">+' . (count($keahlian) - 2) . '</span>';
                if (!$keahlian) echo '—';
              ?>
            </td>
            <td><span class="muted"><?= (int)$t['n_pelatihan']; ?> pelatihan &amp; seminar<br><?= (int)$t['n_kerja']; ?> pengalaman kerja<br><?= (int)$t['n_mengajar']; ?> pengalaman mengajar<br><?= (int)$t['n_lampiran']; ?> lampiran dokumen</span></td>
            <td class="money"><?= (int)$t['n_penjualan']; ?></td>
            <td class="nowrap">
              <span class="badge <?= $t['aktif'] ? 'on' : 'off'; ?>">
                <?= $t['aktif'] ? 'Aktif' : 'Nonaktif'; ?>
              </span>
            </td>
            <td class="hist"><?= hist_cell($t); ?></td>
            <td class="act">
              <button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button>
              <span class="gear-src" hidden>
                <?php if (lm_can('master_trainer', 'ubah')): ?><a href="<?= site_url('master_trainer/edit/' . $t['id']); ?>" title="Edit"></a><?php endif; ?>
                <a href="<?= site_url('master_trainer/cv/' . $t['id']); ?>" title="Lihat CV"></a>
                <?php if (lm_can('master_trainer', 'aktif')): ?><button type="button" class="icon-btn" onclick="toggleTrainer(<?= $t['id']; ?>)" title="<?= $t['aktif'] ? 'Nonaktifkan' : 'Aktifkan'; ?>"></button><?php endif; ?>
                <?= riwayat_btn('ms_trainer', $t['kode']); ?>
              </span>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="9" class="empty">Belum ada data trainer. Silakan tambahkan trainer baru.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Filter status (client-side; jumlah trainer kecil): memakai atribut data-aktif di baris.
$.fn.dataTable.ext.search.push(function (settings, data, idx) {
  if (settings.nTable.id !== 'data-table') return true;
  var v = $('#status-filter').val();
  if (v === 'all') return true;
  return settings.aoData[idx].nTr.getAttribute('data-aktif') === (v === 'on' ? '1' : '0');
});
$('#status-filter').on('change', function () { $('#data-table').DataTable().draw(); });

function toggleTrainer(id) {
  var $tr = $('#data-table tr[data-id="' + id + '"]').addClass('is-loading'); // baris diredupkan selama diproses
  $.ajax({
    url: '<?= site_url("master_trainer/toggle"); ?>/' + id,
    type: 'POST',
    dataType: 'json',
    success: function(res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      if (res.status) setTimeout(function() { location.reload(); }, 600);
      else $tr.removeClass('is-loading');
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); $tr.removeClass('is-loading'); }
  });
}
</script>
