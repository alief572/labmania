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
var ATTRS = [
  { field: 'produk',      id: 'id_jenis_produk',      label: 'Produk',     options: <?= json_encode($jenis_produk); ?> },
  { field: 'bidang',      id: 'id_jenis_bidang',      label: 'Bidang',     options: <?= json_encode($jenis_bidang); ?> },
  { field: 'sertifikasi', id: 'id_jenis_sertifikasi', label: 'Sertifikat', options: <?= json_encode($jenis_sertifikasi); ?> }
];
var esc = function (v) { return $('<div>').text(v == null ? '' : v).html(); };

// Master nonaktif yang sedang terpilih tetap tampil sebagai pilihan (FR-43).
function attrSelect(a, cur) {
  var h = '<select class="input" id="paket-' + a.field + '" aria-label="' + a.label + '"><option value="">— Pilih ' + a.label + ' —</option>';
  a.options.forEach(function (o) {
    if (o.aktif == 1 || String(o.id) === String(cur)) h += '<option value="' + o.id + '"' + (String(o.id) === String(cur) ? ' selected' : '') + '>' + esc(o.nama) + (o.aktif == 1 ? '' : ' (nonaktif)') + '</option>';
  });
  return h + '</select>';
}

function openEditPaketModal(id) {
  $.getJSON('<?= site_url("master_data/paket_detail"); ?>/' + id, function (res) {
    if (!res.status) { toast(res.msg, 'warn'); return; }
    var d = res.data;
    var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
      '<div class="modal wide">' +
        '<div class="modal-head">' +
          '<h3>Edit Paket Produk ' + esc(d.nomor) + '</h3>' +
          '<button class="close-btn" onclick="closeModal()">&times;</button>' +
        '</div>' +
        '<div class="modal-body">' +
          '<div class="grid2">' +
            '<div class="field"><label>Nomor Product Combine</label><input type="text" class="input" value="' + esc(d.nomor) + '" readonly></div>' +
            '<div class="field"><label>Judul <span class="req">*</span></label><input type="text" class="input" id="paket-judul" maxlength="150" value="' + esc(d.judul) + '"></div>' +
            ATTRS.map(function (a) { return '<div class="field"><label>' + a.label + ' <span class="req">*</span></label>' + attrSelect(a, d[a.id]) + '</div>'; }).join('') +
          '</div>' +
          '<div class="err" id="paket-err" style="display:none;"></div>' +
          (d.dipakai > 0 ? '<span class="hint">Paket ini dipakai ' + d.dipakai + ' Master Program — perubahan judul/atribut ikut tampil di sana.</span>' : '') +
        '</div>' +
        '<div class="modal-foot">' +
          '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
          '<button class="btn btn-accent" id="btn-save-paket" onclick="savePaketTitle(' + id + ')">Simpan</button>' +
        '</div>' +
      '</div>';
    openModal(html);
    lmSelect2(document.querySelector('.modal'));
    setTimeout(function () { document.getElementById('paket-judul').focus(); }, 100);
  });
}

function savePaketTitle(id) {
  var errEl = document.getElementById('paket-err');
  if (!lmRequired([['paket-judul', 'Judul'], ['paket-produk', 'Produk'], ['paket-bidang', 'Bidang'], ['paket-sertifikasi', 'Sertifikat']])) return;
  document.getElementById('btn-save-paket').disabled = true;

  $.ajax({
    url: '<?= site_url("master_data/update_paket"); ?>',
    type: 'POST',
    data: { id: id, judul: document.getElementById('paket-judul').value.trim(),
            produk: $('#paket-produk').val(), bidang: $('#paket-bidang').val(), sertifikasi: $('#paket-sertifikasi').val() },
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
