<?php
$canTambah = lm_can('permission_group', 'tambah');
?>
<div class="page-head">
  <div>
    <h1>Permission Group</h1>
    <p>Group user beserta izin yang diizinkan per menu dan aksi. Group <b>admin</b> selalu memiliki semua izin.</p>
  </div>
  <?php if ($canTambah): ?>
  <div class="page-actions">
    <button class="btn btn-accent" onclick="openGroupModal()">+ Tambah Group</button>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari nama group atau keterangan…" aria-label="Cari">
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-ajax="<?= site_url('permission_group/data'); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th>Nama Group</th>
          <th>Keterangan</th>
          <th class="money" style="width: 90px;">User</th>
          <th style="width: 150px;">Izin</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 64px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
function openGroupModal(data) {
  var isEdit = !!data;
  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 480px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit Group (' + data.nama + ')' : 'Tambah Group') + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body">' +
        '<div class="field"><label>Nama Group <span class="req">*</span></label>' +
          '<input type="text" class="input" id="g-nama" maxlength="50" value="' + (data ? esc(data.nama) : '') + '" placeholder="mis. Staff Marketing" ' + (isEdit && data.admin ? 'readonly' : '') + '>' +
          (isEdit && data.admin ? '<span class="hint">Nama group admin tidak dapat diubah</span>' : '') + '</div>' +
        '<div class="field"><label>Keterangan</label>' +
          '<input type="text" class="input" id="g-ket" maxlength="100" value="' + (data ? esc(data.ket || '') : '') + '" placeholder="Keterangan singkat group"></div>' +
        '<div class="err" id="g-err" style="display:none; margin-top: 10px;"></div>' +
      '</div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-group" onclick="submitGroup(' + (isEdit ? data.id : "''") + ')">Simpan</button>' +
      '</div></div>';
  openModal(html);
  setTimeout(function () { document.getElementById('g-nama').focus(); }, 100);
}

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

function submitGroup(id) {
  if (!lmRequired([['g-nama', 'Nama Group']])) return;
  document.getElementById('btn-save-group').disabled = true;
  $.ajax({
    url: '<?= site_url("permission_group/save"); ?>', type: 'POST', dataType: 'json',
    data: { id: id, nama: document.getElementById('g-nama').value.trim(), ket: document.getElementById('g-ket').value.trim() },
    success: function (res) {
      if (res.status) { toast(res.msg, 'ok'); closeModal(); setTimeout(function () { location.reload(); }, 600); }
      else {
        var e = document.getElementById('g-err'); e.textContent = res.msg; e.style.display = 'block';
        document.getElementById('btn-save-group').disabled = false;
      }
    },
    error: function (xhr) {
      var e = document.getElementById('g-err'); e.textContent = (xhr.responseJSON && xhr.responseJSON.msg) || 'Terjadi kesalahan pada server'; e.style.display = 'block';
      document.getElementById('btn-save-group').disabled = false;
    }
  });
}

function delGroup(id, nama) {
  lmConfirm({ title: 'Hapus group?', text: 'Group ' + nama + ' akan dihapus beserta izinnya.', confirmText: 'Ya, hapus', danger: true })
    .then(function (ok) { if (ok) delGroupExec(id); });
}

function delGroupExec(id) {
  $.ajax({
    url: '<?= site_url("permission_group/delete"); ?>/' + id, type: 'POST', dataType: 'json',
    success: function (res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      if (res.status) setTimeout(function () { location.reload(); }, 600);
    },
    error: function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.msg) || 'Terjadi kesalahan pada server', 'warn'); }
  });
}
</script>
