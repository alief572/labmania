<?php $t = htmlspecialchars($title); $nl = htmlspecialchars($name_label); ?>
<div class="page-head">
  <div>
    <h1><?= $t; ?> <span class="ref"><?= htmlspecialchars($ref); ?></span></h1>
    <p><?= htmlspecialchars($desc); ?></p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.' . $type, 'tambah')): ?><button class="btn btn-accent" onclick="openAttrModal()">+ Tambah <?= $t; ?></button><?php endif; ?>
  </div>
</div>

<div class="notice"><span>Data master <b>tidak dapat dihapus</b> — gunakan <b>Nonaktifkan</b>. Data nonaktif tidak muncul sebagai pilihan di form Product Combine dan Peserta.</span><span class="ref">FR-08 · FR-21</span></div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari kode atau nama…" aria-label="Cari">
    </div>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <option value="on">Aktif</option>
      <option value="off">Nonaktif</option>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-filter="#status-filter" data-ajax="<?= site_url('master_data/attr_data/' . $type); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 160px;"><?= htmlspecialchars($code_label); ?></th>
          <th><?= $nl; ?></th>
          <th style="width: 120px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Aksi</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
var ATTR = { title: <?= json_encode($title); ?>, label: <?= json_encode($name_label); ?>, codeLabel: <?= json_encode($code_label); ?> };

function attrReload() { $('#data-table').DataTable().ajax.reload(null, false); }

// Tabel diredupkan selama data dimuat (cari, filter, urut, ganti halaman).
$('#data-table').on('processing.dt', function (e, s, on) { $(this).toggleClass('is-loading', on); });

function openAttrModal(id, nama, kode) {
  var isEdit = !!id;
  var esc = function (v) { return $('<div>').text(v || '').html().replace(/"/g, '&quot;'); };
  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit ' : 'Tambah ') + esc(ATTR.title) + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body">' +
        '<div class="field"><label>' + esc(ATTR.codeLabel) + '</label>' +
          '<input type="text" class="input" id="attr-kode" value="' + (isEdit ? esc(kode) : '') + '" placeholder="Memuat kode…" readonly>' +
          '<span class="hint">Diisi otomatis oleh sistem saat disimpan</span></div>' +
        '<div class="field"><label>' + esc(ATTR.label) + ' <span class="req">*</span></label>' +
          '<input type="text" class="input" id="attr-nama" maxlength="100" value="' + esc(nama) + '" placeholder="Ketik ' + esc(ATTR.label.toLowerCase()) + '">' +
          '<div class="err" id="attr-err" style="display:none;"></div></div>' +
      '</div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save" ' + (isEdit ? '' : 'disabled ') + 'onclick="saveAttr(' + (id || "''") + ')">Simpan</button>' +
      '</div>' +
    '</div>';
  openModal(html);
  setTimeout(function () { document.getElementById('attr-nama').focus(); }, 100);

  if (!isEdit) {
    // Modal langsung terbuka; kode berikutnya dimuat belakangan. Simpan aktif setelah kode siap.
    $.getJSON('<?= site_url('master_data/attr_next_code/' . $type); ?>')
      .done(function (res) { $('#attr-kode').val(res.kode); })
      .fail(function () { $('#attr-kode').attr('placeholder', 'Dibuat saat disimpan'); })
      .always(function () { $('#btn-save').prop('disabled', false); });
  }
}

function saveAttr(id) {
  var nama = document.getElementById('attr-nama').value.trim();
  var errEl = document.getElementById('attr-err');
  if (!lmRequired([['attr-nama', ATTR.label]])) return;
  errEl.style.display = 'none';
  var $btn = $('#btn-save').prop('disabled', true).addClass('is-busy').text('Menyimpan…');

  $.ajax({
    url: '<?= $save_url; ?>',
    type: 'POST',
    data: { id: id, nama: nama },
    dataType: 'json',
    success: function (res) {
      if (res.status) {
        toast(res.msg, 'ok');
        closeModal();
        attrReload();
      } else {
        errEl.textContent = res.msg;
        errEl.style.display = 'block';
        $btn.prop('disabled', false).removeClass('is-busy').text('Simpan');
      }
    },
    error: function () {
      toast('Terjadi kesalahan jaringan', 'warn');
      $btn.prop('disabled', false).removeClass('is-busy').text('Simpan');
    }
  });
}

function toggleAttr(id, btn) {
  var $tr = $(btn).closest('tr'), $b = $(btn).prop('disabled', true);
  $tr.addClass('is-loading');
  $.ajax({
    url: '<?= $toggle_url; ?>/' + id,
    type: 'POST',
    dataType: 'json',
    success: function (res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      attrReload();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); },
    complete: function () { $b.prop('disabled', false); $tr.removeClass('is-loading'); }
  });
}
</script>
