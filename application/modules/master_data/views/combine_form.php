<div class="page-head">
  <div>
    <h1>Tambah Product <span class="ref">FR-11 · FR-12 · FR-13 · FR-22</span></h1>
    <p>Isi judul, lalu susun satu atau lebih kombinasi atribut. Saat disimpan, setiap baris menjadi satu paket produk dengan nomor sendiri.</p>
  </div>
</div>

<div class="card pad">
  <div class="field">
    <label for="combine-judul">Judul <span class="req">*</span></label>
    <input type="text" class="input" id="combine-judul" maxlength="150" placeholder="Judul / topik paket, diisi manual">
  </div>

  <div class="sub-head">
    <h3>Kombinasi Atribut</h3>
    <span class="muted" id="combine-count"></span>
  </div>
  <div class="table-wrap combine-table">
    <table class="table" id="combine-table">
      <thead>
        <tr>
          <th class="num">#</th>
          <th>Produk</th>
          <th>Bidang</th>
          <th>Sertifikat</th>
          <th class="act">Action</th>
        </tr>
      </thead>
      <tbody id="combine-rows"></tbody>
    </table>
  </div>
  <button type="button" class="btn btn-outline" onclick="addRow()">+ Tambah Baris</button>
  <p class="hint">Antar paket minimal satu dari tiga atribut harus berbeda. Hanya data master berstatus Aktif yang dapat dipilih.</p>
</div>

<div class="form-actions">
  <a href="<?= site_url('master_data/paket_produk'); ?>" class="btn btn-ghost">Batal</a>
  <button type="button" class="btn btn-primary" id="btn-save-combine" onclick="saveCombine()">Simpan 1 Paket</button>
</div>

<script>
var ATTRS = [
  { field: 'produk',      label: 'Produk',     options: <?= json_encode($jenis_produk); ?> },
  { field: 'bidang',      label: 'Bidang',     options: <?= json_encode($jenis_bidang); ?> },
  { field: 'sertifikasi', label: 'Sertifikat', options: <?= json_encode($jenis_sertifikasi); ?> }
];
var esc = function (v) { return $('<div>').text(v == null ? '' : v).html(); };

function renderSelect(a) {
  var html = '<select class="input row-select" data-field="' + a.field + '" aria-label="' + a.label + '"><option value="">— Pilih ' + a.label + ' —</option>';
  a.options.forEach(function (item) {
    if (item.aktif == 1) html += '<option value="' + item.id + '">' + esc(item.nama) + '</option>';
  });
  return html + '</select>';
}

function addRow() {
  var tr = document.createElement('tr');
  tr.className = 'combine-row';
  tr.innerHTML = '<td class="num row-no"></td>' +
    ATTRS.map(function (a) { return '<td>' + renderSelect(a) + '</td>'; }).join('') +
    '<td class="act"><button type="button" class="btn btn-ghost sm" onclick="delRow(this)">Hapus</button></td>';
  document.getElementById('combine-rows').appendChild(tr);
  lmSelect2(tr);
  refreshRows();
}

function delRow(btn) {
  btn.closest('tr').remove();
  refreshRows();
}

// Hanya baris data (bukan baris pesan): nomor, Hapus dinonaktifkan bila tinggal satu, penghitung & label tombol.
function refreshRows() {
  var rows = document.querySelectorAll('#combine-rows tr.combine-row');
  rows.forEach(function (tr, i) {
    tr.querySelector('.row-no').textContent = i + 1;
    tr.querySelector('.act .btn').disabled = rows.length === 1;
  });
  document.getElementById('combine-count').textContent = rows.length + ' baris → akan menjadi ' + rows.length + ' paket produk';
  document.getElementById('btn-save-combine').textContent = 'Simpan ' + rows.length + ' Paket';
}

// errors: { indeksBaris: 'pesan' } -> baris ditandai + baris pesan di bawahnya.
function showErrors(errors) {
  $('#combine-rows tr.row-msg').remove();
  $('#combine-rows tr.combine-row').each(function (i) {
    var msg = errors[i];
    $(this).toggleClass('row-err', !!msg);
    if (msg) $('<tr class="row-msg"><td></td><td colspan="4"><i>!</i>' + esc(msg) + '</td></tr>').insertAfter(this);
  });
}

function setSaving(on) {
  var $b = $('#btn-save-combine').prop('disabled', on).toggleClass('is-busy', on);
  if (on) $b.text('Menyimpan…'); else refreshRows();
}

function saveCombine() {
  if (!lmRequired([['combine-judul', 'Judul']])) return;

  var rows = [], errors = {}, seen = {};
  document.querySelectorAll('#combine-rows tr.combine-row').forEach(function (tr, i) {
    var v = {}, kosong = [];
    tr.querySelectorAll('.row-select').forEach(function (sel, k) {
      v[ATTRS[k].field] = sel.value;
      if (!sel.value) kosong.push(ATTRS[k].label);
    });
    rows.push(v);
    if (kosong.length) { errors[i] = 'Pilih ' + kosong.join(', '); return; }
    var key = ATTRS.map(function (a) { return v[a.field]; }).join('|');
    if (key in seen) errors[i] = 'Kombinasi sama persis dengan baris ' + (seen[key] + 1);
    else seen[key] = i;
  });

  showErrors(errors);
  if (Object.keys(errors).length) { toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

  setSaving(true);
  $.ajax({
    url: '<?= site_url("master_data/save_combine"); ?>',
    type: 'POST',
    data: { judul: $('#combine-judul').val().trim(), rows: rows },
    dataType: 'json',
    success: function (res) {
      if (res.status) {
        toast(res.msg, 'ok');
        // Tombol tetap "Menyimpan…" sampai halaman daftar terbuka.
        setTimeout(function () { window.location.href = '<?= site_url("master_data/paket_produk"); ?>'; }, 800);
        return;
      }
      if (res.errors) showErrors(res.errors);
      toast(res.msg, 'warn');
      setSaving(false);
    },
    error: function () {
      toast('Terjadi kesalahan jaringan', 'warn');
      setSaving(false);
    }
  });
}

document.addEventListener('DOMContentLoaded', function () { addRow(); });
</script>
