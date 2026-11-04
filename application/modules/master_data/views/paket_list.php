<div class="page-head">
  <div>
    <h1>Product Combine <span class="ref">FR-07 – FR-13</span></h1>
    <p>Daftar seluruh paket produk hasil kombinasi 3 atribut master. Satu paket produk dibentuk dari kombinasi unik.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.paket_produk', 'tambah')): ?><a href="<?= site_url('master_data/combine'); ?>" class="btn btn-accent">+ Tambah Product (Combine)</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari nomor atau judul paket…" aria-label="Cari">
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-ajax="<?= site_url('master_data/paket_data'); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 130px;">Kode</th>
          <th>Judul Paket</th>
          <th>Produk</th>
          <th>Bidang</th>
          <th>Sertifikat</th>
          <th style="width: 110px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Aksi</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
function openEditPaketModal(id, judul, nomor) {
  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal">' +
      '<div class="modal-head">' +
        '<h3>Edit Judul Paket (' + nomor + ')</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button>' +
      '</div>' +
      '<div class="modal-body">' +
        '<div class="field">' +
          '<label>Judul Paket Produk <span class="req">*</span></label>' +
          '<input type="text" class="input" id="paket-judul" value="' + judul + '" autofocus>' +
          '<div class="err" id="paket-err" style="display:none;"></div>' +
        '</div>' +
      '</div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-paket" onclick="savePaketTitle(' + id + ')">Simpan</button>' +
      '</div>' +
    '</div>';
  openModal(html);
  setTimeout(function() { document.getElementById('paket-judul').focus(); }, 100);
}

function savePaketTitle(id) {
  var judul = document.getElementById('paket-judul').value.trim();
  var errEl = document.getElementById('paket-err');
  if (!lmRequired([['paket-judul', 'Judul Paket Produk']])) return;
  document.getElementById('btn-save-paket').disabled = true;

  $.ajax({
    url: '<?= site_url("master_data/update_paket"); ?>',
    type: 'POST',
    data: { id: id, judul: judul },
    dataType: 'json',
    success: function(res) {
      if (res.status) {
        toast(res.msg, 'ok');
        closeModal();
        setTimeout(function() { location.reload(); }, 600);
      } else {
        errEl.textContent = res.msg;
        errEl.style.display = 'block';
        document.getElementById('btn-save-paket').disabled = false;
      }
    }
  });
}

function togglePaket(id) {
  $.ajax({
    url: '<?= site_url("master_data/toggle_paket"); ?>/' + id,
    type: 'POST',
    dataType: 'json',
    success: function(res) {
      if (res.status) {
        toast(res.msg, 'ok');
        setTimeout(function() { location.reload(); }, 600);
      } else {
        toast(res.msg, 'warn');
      }
    }
  });
}

</script>
