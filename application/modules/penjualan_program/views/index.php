<?php
$ST = ['draft' => 'Draft', 'final' => 'Final', 'batal' => 'Batal'];
$JN = ['public' => 'Public', 'b2b' => 'B2B'];
?>
<script src="<?= base_url('assets/labmania/js/lm-costing.js'); ?>"></script>

<div class="page-head">
  <div>
    <h1>Daftar Penjualan Program <span class="ref">PJ-FR-01 – PJ-FR-05</span></h1>
    <p>Tarik Master Program Final, pilih Public atau B2B, isi tanggal &amp; lokasi, lalu sesuaikan harga dan costing — form sama untuk kedua jenis, perubahan tidak mengubah Master Program. Setelah Final, Public maupun B2B dibuatkan Quotation (modul Quotation).</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('penjualan_program', 'tambah')): ?><a href="<?= site_url('penjualan_program/start'); ?>" class="btn btn-accent">+ Buat Penjualan Program</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari nomor, program, atau lokasi…" aria-label="Cari">
    </div>
    <select class="input sm" id="jenis-filter" aria-label="Filter jenis">
      <option value="all">Semua jenis</option>
      <?php foreach ($JN as $k => $l): ?><option value="<?= $k; ?>" <?= $jenis === $k ? 'selected' : ''; ?>><?= $l; ?></option><?php endforeach; ?>
    </select>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <?php foreach ($ST as $k => $l): ?><option value="<?= $k; ?>" <?= $status === $k ? 'selected' : ''; ?>><?= $l; ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-filter="#status-filter,#jenis-filter" data-filter-name="status,jenis" data-ajax="<?= site_url('penjualan_program/penjualan_data'); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 130px;">Nomor</th>
          <th>Jenis</th>
          <th>Master Program</th>
          <th>Pelaksanaan</th>
          <th class="money">Target peserta</th>
          <th class="money">Harga jual / peserta</th>
          <th style="width: 120px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
LmCosting.configure({ base: '<?= site_url('penjualan_program/'); ?>', kind: 'pj' });

// Tabel diredupkan selama data dimuat (cari, filter, urut, ganti halaman).
$('#data-table').on('processing.dt', function (e, s, on) { $(this).toggleClass('is-loading', on); });

// Popup ringkasan (Finalkan) / konfirmasi (Batalkan) dari server; setelah sukses tabel dimuat ulang tanpa pindah halaman.
function afterStatus() { $('#data-table').DataTable().ajax.reload(null, false); }
function pjFinal(id) { LmCosting.confirmFinal(id, afterStatus); }
function pjCancel(id) { LmCosting.confirmCancel(id, afterStatus); }

// Surat Penawaran (PJ-FR-20): pratinjau dimuat dari server, lalu Cetak PDF.
function pjPenawaran(id) { LmCosting.penawaran(id); }
</script>
