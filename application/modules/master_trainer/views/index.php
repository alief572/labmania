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
    <table class="table" id="data-table" data-dt data-ajax="<?= site_url('master_trainer/data'); ?>" data-filter="#status-filter">
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
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
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
