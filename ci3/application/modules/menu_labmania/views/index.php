<?php
$canTambah = lm_can('menu_labmania', 'tambah');
?>
<div class="page-head">
  <div>
    <h1>Menu Sidebar</h1>
    <p>Atur menu di sidebar: modul, menu, urutan, dan aksi yang dapat diberi izin. Menu hanya tampil bagi group yang diberi izin <b>Lihat</b>.</p>
  </div>
  <?php if ($canTambah): ?>
  <div class="page-actions">
    <button class="btn btn-accent" onclick="openMenuModal()">+ Tambah Menu</button>
  </div>
  <?php endif; ?>
</div>

<div class="notice"><span>Menu <b>tidak dapat dihapus</b> — gunakan <b>Nonaktifkan</b>. Kode izin menu dipakai oleh controller modul, jadi menu baru baru berefek pada hak akses setelah modul tujuannya memeriksa kode itu.</span></div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari judul, kode, link, atau modul…" aria-label="Cari">
    </div>
  </div>

  <div class="table-wrap">
    <table class="table menu-table" id="data-table" data-dt data-no-order data-ajax="<?= site_url('menu_labmania/data'); ?>">
      <thead>
        <tr>
          <th>Menu</th>
          <th>Link</th>
          <th class="num" style="width: 80px;">Kelompok</th>
          <th>Aksi</th>
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
var MODULS = <?= json_encode($moduls); ?>;
var ICONS = <?= json_encode($icons); ?>;
var AKSI = <?= json_encode($aksi); ?>;

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

function openMenuModal(data) {
  var isEdit = !!data;
  var tipe = isEdit ? data.tipe : 'menu';
  var sistem = isEdit && data.sistem == 1;

  var parentOpts = '<option value="0"' + (isEdit && data.parent_id == 0 ? ' selected' : '') + '>— Tingkat atas (tanpa modul) —</option>' + MODULS.map(function (m) {
    return '<option value="' + m.id + '"' + (isEdit && data.parent_id == m.id ? ' selected' : (!isEdit && m.id == 2 ? ' selected' : '')) + '>' + esc(m.judul) + '</option>';
  }).join('');
  var iconOpts = '<option value="">— Tanpa ikon —</option>' + ICONS.map(function (k) { return '<option value="' + k + '"' + (isEdit && data.ikon === k ? ' selected' : '') + '>' + k + '</option>'; }).join('');
  var aksiChecks = Object.keys(AKSI).map(function (k) {
    var on = isEdit ? data.aksi.indexOf(k) >= 0 : (k === 'lihat');
    return '<label class="akses-col" style="margin-right:14px"><input type="checkbox" name="mn-aksi" value="' + k + '" ' + (on ? 'checked' : '') + (k === 'lihat' ? ' disabled' : '') + '> ' + esc(AKSI[k]) + '</label>';
  }).join('');

  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 560px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit Menu (' + esc(data.judul) + ')' : 'Tambah Menu') + '</h3><button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body">' +
        '<div class="field"><label>Tipe <span class="req">*</span></label>' +
          '<select class="input" id="mn-tipe" ' + (isEdit ? 'disabled' : '') + ' onchange="toggleTipe()"><option value="menu"' + (tipe === 'menu' ? ' selected' : '') + '>Menu</option><option value="modul"' + (tipe === 'modul' ? ' selected' : '') + '>Modul (induk menu)</option></select></div>' +
        '<div class="field"><label>Judul <span class="req">*</span></label><input type="text" class="input" id="mn-judul" maxlength="100" value="' + esc(isEdit ? data.judul : '') + '" placeholder="Judul di sidebar"></div>' +
        '<div data-menu-only>' +
          '<div class="field"><label>Modul Induk</label><select class="input" id="mn-parent">' + parentOpts + '</select></div>' +
          '<div class="field"><label>Link <span class="req">*</span></label><input type="text" class="input" id="mn-link" maxlength="150" value="' + esc(isEdit ? data.link : '') + '" placeholder="mis. master_data/peserta" ' + (sistem ? 'readonly' : '') + '>' +
            '<span class="hint">' + (sistem ? 'Link menu sistem terkunci' : 'Path relatif tanpa awalan /') + '</span></div>' +
          '<div class="field"><label>Kelompok</label><input type="number" class="input" id="mn-kelompok" min="1" max="99" value="' + (isEdit ? data.kelompok : 1) + '"><span class="hint">Menu dengan nomor kelompok sama ditampilkan dalam satu blok, diberi jarak dari kelompok lain</span></div>' +
          '<div class="field"><label>Aksi yang dapat diberi izin</label><div class="static">' + aksiChecks + '</div><span class="hint">Lihat selalu berlaku</span></div>' +
        '</div>' +
        '<div class="field"><label>Ikon</label><select class="input" id="mn-ikon">' + iconOpts + '</select><span class="hint">Dipakai untuk modul dan menu tingkat atas</span></div>' +
        '<div class="err" id="mn-err" style="display:none; margin-top: 10px;"></div>' +
      '</div>' +
      '<div class="modal-foot"><button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-menu" onclick="submitMenu(' + (isEdit ? data.id : "''") + ')">Simpan</button></div>' +
    '</div>';
  openModal(html);
  toggleTipe();
  setTimeout(function () { document.getElementById('mn-judul').focus(); }, 100);
}

function toggleTipe() {
  var modul = document.getElementById('mn-tipe').value === 'modul';
  document.querySelector('[data-menu-only]').style.display = modul ? 'none' : '';
}

function submitMenu(id) {
  var modul = document.getElementById('mn-tipe').value === 'modul';
  var req = [['mn-judul', 'Judul']];
  if (!modul) req.push(['mn-link', 'Link']);
  if (!lmRequired(req)) return;

  var aksi = [].map.call(document.querySelectorAll('input[name=mn-aksi]:checked'), function (x) { return x.value; });
  var data = {
    id: id, tipe: document.getElementById('mn-tipe').value, judul: document.getElementById('mn-judul').value.trim(),
    ikon: document.getElementById('mn-ikon').value, parent_id: modul ? 0 : document.getElementById('mn-parent').value,
    link: modul ? '' : document.getElementById('mn-link').value.trim(), kelompok: modul ? 1 : document.getElementById('mn-kelompok').value, aksi: aksi
  };
  document.getElementById('btn-save-menu').disabled = true;
  $.ajax({
    url: '<?= site_url("menu_labmania/save"); ?>', type: 'POST', dataType: 'json', data: data,
    success: function (res) {
      if (res.status) { toast(res.msg, 'ok'); closeModal(); setTimeout(function () { location.reload(); }, 600); }
      else { var e = document.getElementById('mn-err'); e.textContent = res.msg; e.style.display = 'block'; document.getElementById('btn-save-menu').disabled = false; }
    },
    error: function (xhr) {
      var e = document.getElementById('mn-err'); e.textContent = (xhr.responseJSON && xhr.responseJSON.msg) || 'Terjadi kesalahan pada server'; e.style.display = 'block';
      document.getElementById('btn-save-menu').disabled = false;
    }
  });
}

function postAct(url) {
  $.ajax({
    url: url, type: 'POST', dataType: 'json',
    success: function (res) { toast(res.msg, res.status ? 'ok' : 'warn'); if (res.status) setTimeout(function () { location.reload(); }, 500); },
    error: function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.msg) || 'Terjadi kesalahan pada server', 'warn'); }
  });
}
function moveMenu(id, dir) { postAct('<?= site_url("menu_labmania/move"); ?>/' + id + '/' + dir); }
function toggleMenu(id) { postAct('<?= site_url("menu_labmania/toggle"); ?>/' + id); }
</script>
