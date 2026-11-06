<?php
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$title = $titles[$tab];
$ref = $tab === 'fixed' ? 'FR-18 · FR-20 · FR-33' : 'FR-18 · FR-19 · FR-33';
?>
<div class="page-head">
  <div>
    <h1>Master Rate <span class="ref"><?= $ref; ?></span></h1>
    <p>Item biaya beserta Default Cost yang dipakai costing Master Program dan Penjualan Program.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.rate', 'tambah')): ?><button class="btn btn-accent" onclick="openRateModal()">+ Tambah <?= $e($title); ?></button><?php endif; ?>
  </div>
</div>

<div class="notice"><span>Satuan <b>Fixed Cost</b>: Event, Day (biaya per jam dicatat sebagai Event). Satuan <b>Variable Cost</b>: Peserta/Event, Peserta/Day.
  Satu item satu <b>Default Cost</b>, yang dapat disesuaikan di Master Program dan Penjualan Program tanpa mengubah master. Item tidak dapat dihapus — gunakan <b>Nonaktifkan</b>.</span><span class="ref">FR-19 · FR-25 · FR-34 · FR-35</span></div>

<div class="card">
  <div class="tabs" role="tablist">
    <?php foreach ($titles as $t => $label): ?>
      <a class="tab <?= $t === $tab ? 'active' : ''; ?>" role="tab" href="<?= site_url('master_data/rate/' . $t); ?>"><?= $e($label); ?><span class="nav-count"><?= (int)$counts[$t]; ?></span></a>
    <?php endforeach; ?>
  </div>

  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari kode atau item…" aria-label="Cari">
    </div>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <option value="on">Aktif</option>
      <option value="off">Nonaktif</option>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-ajax="<?= site_url('master_data/rate_data/' . $tab); ?>" data-filter="#status-filter">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 130px;" class="nowrap">Kode <?= $e($title); ?></th>
          <th>Item</th>
          <th class="nowrap">Satuan</th>
          <th class="money" style="width: 150px;">Default Cost</th>
          <th style="width: 110px;" class="nowrap">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
var curTab = <?= json_encode($tab); ?>;
var TITLES = <?= json_encode($titles); ?>;
var SATUAN = <?= json_encode($satuan_tabs); ?>;
var COA = <?= json_encode($coa); ?>;
var ERR_FIELD = { tipe: 'r-kategori', id_satuan: 'r-satuan', item: 'r-item', default_cost: 'r-cost', keterangan: 'r-ket', coa: 'r-coa' };

function esc(v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); }

function fieldHtml(label, control, cls) {
  return '<div class="field ' + (cls || '') + '"><label>' + label + '</label>' + control + '<div class="err" style="display:none;"></div></div>';
}

// Pesan di bawah kolom + garis merah; hilang saat kolom diubah.
function setFieldErr(id, msg) {
  var el = document.getElementById(id);
  if (!el) return;
  var $e = $(el).closest('.field').find('.err').first();
  lmMark(el, !!msg);
  $e.html(msg ? '<i>!</i>' + esc(msg) : '').toggle(!!msg);
  if (msg) $(el).one('input change', function () { setFieldErr(id, ''); });
}

function satuanOpts(tipe, cur) {
  return '<option value="">— Pilih Satuan —</option>' + SATUAN[tipe].map(function (s) {
    return '<option value="' + s.id + '"' + (String(s.id) === String(cur) ? ' selected' : '') + '>' + esc(s.nama) + '</option>';
  }).join('');
}

function coaOpts(cur) {
  var html = '<option value="">— Pilih COA —</option>', found = false;
  COA.forEach(function (c) {
    var sel = c.no_perkiraan === cur; if (sel) found = true;
    html += '<option value="' + esc(c.no_perkiraan) + '"' + (sel ? ' selected' : '') + '>' + esc(c.no_perkiraan) + ' · ' + esc(c.nama) + '</option>';
  });
  // Nilai lama yang belum ada di master COA tetap ditampilkan sampai user memilih akun yang ada.
  if (cur && !found) html += '<option value="' + esc(cur) + '" selected>' + esc(cur) + ' (belum ada di master COA)</option>';
  return html;
}

function openRateModal(data) {
  var isEdit = !!data;
  var tipe = isEdit ? data.tipe : curTab;
  var d = data || { item: '', id_satuan: '', default_cost: '', keterangan: '', coa: '' };
  var costVal = d.default_cost === '' ? '' : fmtRibu(Math.round(d.default_cost));
  var kodeLabel = function (t) { return 'Kode ' + TITLES[t]; };

  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 780px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit ' : 'Tambah ') + esc(TITLES[tipe]) + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body"><div class="grid2">' +
        fieldHtml('<span id="r-kode-label">' + esc(kodeLabel(tipe)) + '</span>', '<input class="input" id="r-kode" value="' + (isEdit ? esc(d.kode) : '') + '" placeholder="Memuat kode…" readonly><span class="hint">Diisi otomatis oleh sistem</span>') +
        fieldHtml('Kategori <span class="req">*</span>', '<select class="input" id="r-kategori"' + (isEdit ? ' disabled' : '') + '>' +
          Object.keys(TITLES).map(function (k) { return '<option value="' + k + '"' + (k === tipe ? ' selected' : '') + '>' + (k === 'fixed' ? 'Fixed' : 'Variable') + '</option>'; }).join('') +
          '</select>' + (isEdit ? '<span class="hint">Kategori tidak dapat diubah setelah disimpan</span>' : '')) +
        fieldHtml('Satuan <span class="req">*</span>', '<select class="input" id="r-satuan">' + satuanOpts(tipe, d.id_satuan) + '</select>') +
        fieldHtml('Item <span class="req">*</span>', '<input class="input" id="r-item" maxlength="150" value="' + esc(d.item) + '" placeholder="Nama item biaya">', 'span2') +
        fieldHtml('Default Cost (Rp) <span class="req">*</span>', '<input class="input money-in" id="r-cost" inputmode="numeric" value="' + costVal + '" placeholder="0"><span class="hint">Nilai awal; dapat diedit di Master Program &amp; Penjualan Program</span>') +
        fieldHtml('Keterangan', '<input class="input" id="r-ket" maxlength="150" value="' + esc(d.keterangan) + '" placeholder="Opsional">', 'span2') +
        fieldHtml('COA <span class="req">*</span>', '<select class="input" id="r-coa">' + coaOpts(d.coa) + '</select><span class="hint">Dipilih dari master COA</span>', 'span2') +
      '</div></div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-rate" ' + (isEdit ? '' : 'disabled ') + 'onclick="submitRate(' + (isEdit ? d.id : "''") + ')">Simpan</button>' +
      '</div>' +
    '</div>';

  openModal(html);

  // Kode dimuat belakangan menurut kategori; ganti kategori -> kode & pilihan Satuan ikut berganti.
  function loadKode(t) {
    $('#btn-save-rate').prop('disabled', true);
    $('#r-kode').val('').attr('placeholder', 'Memuat kode…');
    $.getJSON('<?= site_url('master_data/rate_next_code/'); ?>' + t)
      .done(function (res) { $('#r-kode').val(res.kode); })
      .fail(function () { $('#r-kode').attr('placeholder', 'Dibuat saat disimpan'); })
      .always(function () { $('#btn-save-rate').prop('disabled', false); });
  }
  if (!isEdit) {
    loadKode(tipe);
    $('#r-kategori').on('change', function () {
      var t = this.value;
      $('#r-kode-label').text(kodeLabel(t));
      $('#r-satuan').html(satuanOpts(t, ''));
      loadKode(t);
    });
  }
}

function editRate(btn) {
  openRateModal(JSON.parse(btn.closest('tr').getAttribute('data-json')));
}

function submitRate(id) {
  var v = function (i) { return String($('#' + i).val() || '').trim(); };
  var ok = true;
  Object.keys(ERR_FIELD).forEach(function (k) { setFieldErr(ERR_FIELD[k], ''); });
  var cost = v('r-cost').replace(/\./g, '');
  if (!v('r-item')) { setFieldErr('r-item', 'Item wajib diisi'); ok = false; }
  if (!v('r-satuan')) { setFieldErr('r-satuan', 'Satuan wajib dipilih'); ok = false; }
  if (!/^\d+$/.test(cost)) { setFieldErr('r-cost', 'Default Cost wajib diisi, angka rupiah ≥ 0'); ok = false; }
  if (!v('r-coa')) { setFieldErr('r-coa', 'COA wajib dipilih'); ok = false; }
  if (!ok) { toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

  var $btn = $('#btn-save-rate').prop('disabled', true).addClass('is-busy').text('Menyimpan…');
  var done = function () { $btn.prop('disabled', false).removeClass('is-busy').text('Simpan'); };

  $.ajax({
    url: '<?= site_url("master_data/save_rate"); ?>',
    type: 'POST',
    dataType: 'json',
    data: { id: id, tipe: $('#r-kategori').val(), item: v('r-item'), id_satuan: v('r-satuan'), default_cost: cost, keterangan: v('r-ket'), coa: v('r-coa') },
    success: function (res) {
      if (res.status) {
        toast(res.msg, 'ok');
        // Item baru dari kategori lain: buka tab kategori itu; selain itu muat ulang tab ini.
        var t = $('#r-kategori').val();
        setTimeout(function () { location.href = '<?= site_url('master_data/rate/'); ?>' + (id ? curTab : t); }, 600);
        return;
      }
      $.each(res.errors || {}, function (k, msg) { if (ERR_FIELD[k]) setFieldErr(ERR_FIELD[k], msg); });
      toast(res.msg, 'warn');
      done();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); done(); }
  });
}

function toggleRate(id) {
  var $tr = $('#data-table tr[data-id="' + id + '"]').addClass('is-loading'); // baris diredupkan selama diproses
  $.ajax({
    url: '<?= site_url("master_data/toggle_rate"); ?>/' + id,
    type: 'POST',
    dataType: 'json',
    success: function (res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      if (res.status) setTimeout(function () { location.reload(); }, 600);
      else $tr.removeClass('is-loading');
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); $tr.removeClass('is-loading'); }
  });
}
</script>
