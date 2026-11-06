<?php
$ST = ['draft' => 'Draft', 'final' => 'Final', 'batal' => 'Batal'];
?>
<script src="<?= base_url('assets/labmania/js/lm-costing.js'); ?>"></script>

<div class="page-head">
  <div>
    <h1>Daftar Master Program <span class="ref">MP-FR-01 · MP-FR-02</span></h1>
    <p>Program siap jual: Paket Produk + costing baseline. Tidak ada Public/B2B, customer, tanggal, atau lokasi acara — semuanya diisi di Penjualan Program.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_program', 'tambah')): ?><a href="<?= site_url('master_program/add'); ?>" class="btn btn-accent">+ Buat Master Program</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari nomor atau judul…" aria-label="Cari">
    </div>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <?php foreach ($ST as $k => $l): ?><option value="<?= $k; ?>" <?= $status === $k ? 'selected' : ''; ?>><?= $l; ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-filter="#status-filter" data-ajax="<?= site_url('master_program/program_data'); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 130px;">Nomor</th>
          <th>Judul</th>
          <th>Durasi</th>
          <th class="money">Target peserta</th>
          <th class="money">Harga jual standar</th>
          <th class="money">HPP</th>
          <th style="width: 110px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
LmCosting.configure({ base: '<?= site_url('master_program/'); ?>' });

// Tabel diredupkan selama data dimuat (cari, filter, urut, ganti halaman).
$('#data-table').on('processing.dt', function (e, s, on) { $(this).toggleClass('is-loading', on); });

// Popup ringkasan (Finalkan) / konfirmasi (Batalkan) dari server; setelah sukses tabel dimuat ulang tanpa pindah halaman.
function afterStatus() { $('#data-table').DataTable().ajax.reload(null, false); }
function mpFinal(id) { LmCosting.confirmFinal(id, afterStatus); }
function mpCancel(id) { LmCosting.confirmCancel(id, afterStatus); }
</script>
