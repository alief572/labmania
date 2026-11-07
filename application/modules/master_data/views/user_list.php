<div class="page-head">
  <div>
    <h1>Master User <span class="ref">FR-36 – FR-39</span></h1>
    <p>Data karyawan Labmania dan akun login ke sistem. User nonaktif tidak dapat login; data tidak dihapus.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.users', 'tambah')): ?><button class="btn btn-accent" onclick="userForm()">+ Tambah User</button><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="u-q" placeholder="Cari ID, nama, username, atau email…" aria-label="Cari">
    </div>
    <select class="input sm" id="u-status" aria-label="Filter status">
      <option value="all">Semua status</option>
      <option value="on">Aktif</option>
      <option value="off">Nonaktif</option>
    </select>
    <select class="input sm" id="u-role" aria-label="Filter role">
      <option value="all">Semua role</option>
      <?php foreach ($groups as $g): ?>
        <option value="<?= (int)$g['id_group']; ?>"><?= htmlspecialchars($g['nm_group']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div id="u-list"></div>
</div>

<script>
var GROUPS = <?= json_encode($groups); ?>;
var GENDER = <?= json_encode($gender); ?>;
var AGAMA = <?= json_encode($agama); ?>;
var EMP_TYPE = <?= json_encode($tipe); ?>;
var CAN_LOG = <?= can_view_activity_log() ? 'true' : 'false'; ?>;
var SUPERADMIN = 1; // id_group Superadmin (bawaan sistem)
var CAN_UBAH = <?= lm_can('master_data.users', 'ubah') ? 'true' : 'false'; ?>, CAN_AKTIF = <?= lm_can('master_data.users', 'aktif') ? 'true' : 'false'; ?>;
var LOG_URL = <?= json_encode(site_url('activity_log_labmania')); ?>;

var GEAR = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>';
var REQ = ' <span class="req">*</span>';

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}
function fmtWaktu(t) {
  var d = new Date(String(t).replace(' ', 'T'));
  if (isNaN(d)) return esc(t);
  function z(n) { return (n < 10 ? '0' : '') + n; }
  return z(d.getDate()) + '-' + z(d.getMonth() + 1) + '-' + d.getFullYear() + ' ' + z(d.getHours()) + ':' + z(d.getMinutes());
}
function histCell(r) {
  if (!r._dibuat_pada) return '<span class="muted">—</span>';
  var diubah = !!r._diubah_pada;
  return '<span class="hist-l">' + (diubah ? 'Diubah' : 'Dibuat') + ' <b>' + esc((diubah ? r._diubah_oleh : r._dibuat_oleh) || '—') + '</b><br>' + fmtWaktu(diubah ? r._diubah_pada : r._dibuat_pada) + '</span>';
}
// Data baris dibaca dari atribut data-json yang dikirim server (data-id = id baris).
function findUser(id) { return JSON.parse($('#data-table tr[data-id="' + id + '"]').attr('data-json')); }
function opts(list, sel) { return list.map(function (x) { return '<option' + (x === sel ? ' selected' : '') + '>' + esc(x) + '</option>'; }).join(''); }
function field(label, control, cls) { return '<div class="field ' + (cls || '') + '"><label>' + label + '</label>' + control + '</div>'; }

// ---------- Daftar ----------
var USER_DT = null;

// Daftar memakai DataTables server-side (users_data); filter Status & Role ikut dikirim ke server.
function renderUsers() {
  document.getElementById('u-list').innerHTML =
    '<div class="table-wrap"><table class="table" id="data-table" data-filter="#u-status,#u-role" data-filter-name="status,role"><thead><tr>' +
      '<th class="num">No</th><th class="nowrap">Employee ID</th><th>Nama</th><th>Role</th><th>Akun login</th><th class="nowrap">Status</th><th class="hist">Dibuat / Diubah</th><th class="act">Action</th>' +
    '</tr></thead><tbody></tbody></table></div>';
  // Ikon gear diisi klien setelah tiap draw (server hanya mengirim tombolnya).
  $('#data-table').on('draw.dt', function () { $('#data-table .gear[data-uact="gear"]:empty').html(GEAR); });
  USER_DT = lmDataTable('#data-table', { search: '#u-q', ajax: <?= json_encode(site_url('master_data/users_data')); ?> });
}

// ---------- Menu gear ----------
function closeGear() {
  var root = document.getElementById('menu-root');
  if (root) { root.innerHTML = ''; delete root.dataset.owner; }
  document.querySelectorAll('.gear.open').forEach(function (g) { g.classList.remove('open'); });
}
function openGear(btn) {
  var root = document.getElementById('menu-root');
  var wasOpen = btn.classList.contains('open');
  closeGear();
  if (wasOpen) return;
  root.innerHTML = '<div class="menu" role="menu">' + btn.nextElementSibling.innerHTML + '</div>';
  btn.classList.add('open');
  var menu = root.firstChild, b = btn.getBoundingClientRect();
  var top = b.bottom + 4 + menu.offsetHeight > window.innerHeight ? b.top - 4 - menu.offsetHeight : b.bottom + 4;
  menu.style.top = top + 'px';
  menu.style.left = Math.max(8, b.right - menu.offsetWidth) + 'px';
}

document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-uact]');
  if (el && el.dataset.uact === 'gear') return openGear(el);
  closeGear();
  if (!el || el.disabled) return;
  var act = el.dataset.uact;
  if (act === 'edit') userForm(findUser(el.dataset.id));
  else if (act === 'toggle') toggleUser(findUser(el.dataset.id));
  else if (act === 'reset') userReset(findUser(el.dataset.id));
  else if (act === 'hist') { var u = findUser(el.dataset.id); location.href = LOG_URL + '?ref_table=ms_user_karyawan&ref_no=' + encodeURIComponent(u.kode); }
});
window.addEventListener('scroll', closeGear, true);


// ---------- Modal (markup sesuai mockup) ----------
function showModal(o) {
  var root = document.getElementById('modal-root');
  root.innerHTML = '<div class="overlay" data-mclose="1">' +
    '<form class="modal ' + (o.wide ? 'wide' : '') + '" novalidate role="dialog" aria-modal="true">' +
      '<header><h2>' + o.title + '</h2><button type="button" class="x" data-mclose="1" aria-label="Tutup">×</button></header>' +
      '<div class="modal-body">' + o.body + '</div>' +
      '<footer><button type="button" class="btn btn-ghost" data-mclose="1">Batal</button>' +
      '<button type="submit" class="btn btn-primary">' + (o.saveLabel || 'Simpan') + '</button></footer>' +
    '</form></div>';
  lmSelect2(root);
  var form = root.querySelector('form');
  root.querySelector('.overlay').addEventListener('click', function (e) {
    if (e.target.dataset.mclose && (e.target.tagName === 'BUTTON' || e.target === this)) closeModal();
  });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    form.querySelectorAll('.has-err').forEach(function (x) { x.classList.remove('has-err'); });
    form.querySelectorAll('.err').forEach(function (x) { x.remove(); });
    var res = o.onSave(form.elements, form);
    if (res === false) {
      var first = form.querySelector('.has-err .input');
      var msgs = [].map.call(form.querySelectorAll('.has-err .err'), function (e) { return e.textContent.replace(/^!/, '').trim(); });
      var allReq = msgs.every(function (m) { return /wajib/i.test(m); });
      lmWarn(msgs, { title: allReq ? 'Data belum lengkap' : 'Periksa kembali isian', lead: 'Mohon periksa kolom berikut:' }).then(function () { if (first) first.focus(); });
    }
  });
  if (o.onMount) o.onMount(form);
  var f = form.querySelector('.input:not([readonly]):not([disabled])');
  if (f) f.focus();
  return form;
}
function invalid(el, msg) {
  var wrap = el.closest('.field');
  if (!wrap.classList.contains('has-err')) {
    wrap.classList.add('has-err');
    wrap.insertAdjacentHTML('beforeend', '<div class="err"><i>!</i>' + esc(msg) + '</div>');
  }
  return false;
}
function serverError(form, msg) {
  var body = form.querySelector('.modal-body');
  var old = body.querySelector('.err.server'); if (old) old.remove();
  body.insertAdjacentHTML('afterbegin', '<div class="err server" style="margin-bottom:12px"><i>!</i>' + esc(msg) + '</div>');
}
function postJson(url, data, form, done) {
  var btn = form.querySelector('[type=submit]'); btn.disabled = true;
  $.ajax({
    url: url, type: 'POST', data: data, dataType: 'json',
    success: function (res) {
      if (res.status) { toast(res.msg, 'ok'); closeModal(); setTimeout(function () { location.reload(); }, 600); }
      else { btn.disabled = false; serverError(form, res.msg); }
    },
    error: function () { btn.disabled = false; serverError(form, 'Terjadi kesalahan pada server'); }
  });
}

// ---------- Form Tambah / Edit User (FR-37, FR-38) ----------
// Override Permission (FR-65): hak role tampil tercentang dan terkunci; hak lain dapat ditambahkan
function loadOverride(form, idUser) {
  var gid = form.elements.id_group.value, body = form.querySelector('[data-ovr-body]');
  if (!gid || String(gid) === String(SUPERADMIN)) {
    body.innerHTML = '<p class="muted">' + (gid ? 'Superadmin sudah memiliki semua hak.' : 'Pilih Role terlebih dahulu.') + '</p>';
    countOverride(form);
    return;
  }
  body.innerHTML = '<p class="muted">Memuat…</p>';
  $.post(<?= json_encode(site_url('master_data/override_matrix')); ?>, { id_group: gid, id_user: idUser || '' }, function (res) {
    var cols = Object.keys(res.aksi), h = '<div style="max-height:260px;overflow:auto"><table class="table"><thead><tr><th>Menu</th>' +
      cols.map(function (k) { return '<th class="nowrap">' + esc(res.aksi[k]) + '</th>'; }).join('') + '</tr></thead><tbody>';
    res.matrix.forEach(function (mod) {
      h += '<tr><td colspan="' + (cols.length + 1) + '"><b>' + esc(mod.judul) + '</b></td></tr>';
      mod.items.forEach(function (m) {
        h += '<tr data-menu="' + m.id + '"><td>' + esc(m.judul) + '</td>' + cols.map(function (k) {
          if (m.berlaku.indexOf(k) < 0) return '<td class="muted">—</td>';
          var dariRole = m.role.indexOf(k) >= 0, on = dariRole || m.override.indexOf(k) >= 0;
          return '<td><input type="checkbox" data-aksi="' + k + '" ' + (dariRole ? 'data-role="1" disabled title="Dari role"' : 'name="override[' + m.id + '][]" value="' + k + '"') +
            (on ? ' checked' : '') + ' aria-label="' + esc(m.judul + ' — ' + res.aksi[k]) + '"></td>';
        }).join('') + '</tr>';
      });
    });
    body.innerHTML = h + '</tbody></table></div>';
    countOverride(form);
  }, 'json').fail(function () { body.innerHTML = '<p class="muted">Gagal memuat Override Permission.</p>'; });
}
function countOverride(form) {
  form.querySelector('[data-ovr-n]').textContent = form.querySelectorAll('[data-ovr-body] input[name]:checked').length;
}
function onOverrideChange(e, form) {
  var t = e.target, tr = t.closest('tr');
  if (!t.dataset.aksi) return;
  var lihat = tr.querySelector('input[data-aksi="lihat"]');
  if (t.dataset.aksi === 'lihat' && !t.checked) tr.querySelectorAll('input[name]').forEach(function (x) { x.checked = false; });
  else if (t.checked && lihat && !lihat.disabled) lihat.checked = true;
  countOverride(form);
}
function userForm(rec) {
  var r = rec || { kode: '', nama: '', nik: '', tmp_lahir: '', tgl_lahir: '', id_group: '', gender: '', agama: '',
    telp: '', email: '', alamat: '', npwp: '', tgl_masuk: '', tipe: '', jobdesc: '', aktif: 1, id_user: null, username: '' };
  var hasAkun = !!r.id_user;
  var akunOn = rec ? hasAkun : true;
  function inp(name, val, extra) { return '<input class="input" name="' + name + '" value="' + esc(val) + '" ' + (extra || '') + '>'; }
  function sel(name, list, val) { return '<select class="input" name="' + name + '"><option value="">Choose one</option>' + opts(list, val) + '</select>'; }
  var isSuper = String(r.id_group) === String(SUPERADMIN);
  var groupOpts = '<option value="">Choose one</option>' + GROUPS.filter(function (g) { return isSuper || String(g.id_group) !== String(SUPERADMIN); }).map(function (g) {
    return '<option value="' + g.id_group + '"' + (String(g.id_group) === String(r.id_group) ? ' selected' : '') + '>' + esc(g.nm_group) + '</option>';
  }).join('');
  var aktif = r.aktif == 1;

  var form = showModal({
    title: rec ? 'Edit User ' + esc(rec.kode || rec.nama) : 'Add New User',
    wide: true,
    body: '<div class="grid2"><div>' +
        field('Employee ID' + REQ, inp('kode', r.kode, 'placeholder="Employee ID" maxlength="20" ' + (rec && rec.kode ? 'readonly' : '')) + (rec && rec.kode ? '<span class="hint">Tidak dapat diubah</span>' : '')) +
        field('Employee Name' + REQ, inp('nama', r.nama, 'placeholder="Employee Name" maxlength="100"')) +
        field('Personal ID Number', inp('nik', r.nik, 'placeholder="NIK" inputmode="numeric" maxlength="16"')) +
        field('Birth Place', inp('tmp_lahir', r.tmp_lahir, 'placeholder="Birth Place" maxlength="50"')) +
        field('Birth Date', inp('tgl_lahir', r.tgl_lahir, 'type="date"')) +
        field('Role' + REQ, '<select class="input" name="id_group"' + (isSuper ? ' disabled' : '') + '>' + groupOpts + '</select>' + (isSuper ? '<span class="hint">Role Superadmin tidak dapat diganti</span>' : '')) +
        field('Gender' + REQ, sel('gender', GENDER, r.gender)) +
        field('Religion', sel('agama', AGAMA, r.agama)) +
      '</div><div>' +
        field('Phone Number' + REQ, inp('telp', r.telp, 'placeholder="+62..." inputmode="tel"')) +
        field('Email', inp('email', r.email, 'type="email" placeholder="email@domain.com"')) +
        field('Address', '<textarea class="input" name="alamat" rows="2" placeholder="Address" maxlength="255">' + esc(r.alamat) + '</textarea>') +
        field('NPWP', inp('npwp', r.npwp, 'placeholder="NPWP Number" inputmode="numeric"')) +
        field('Join Date', inp('tgl_masuk', r.tgl_masuk, 'type="date"')) +
        field('Employee Type' + REQ, sel('tipe', EMP_TYPE, r.tipe)) +
        field('Job Desc.', '<textarea class="input" name="jobdesc" rows="2" placeholder="Job Description" maxlength="500">' + esc(r.jobdesc) + '</textarea>') +
        field('Status' + REQ, '<div class="static"><label><input type="radio" name="aktif" value="1" ' + (aktif ? 'checked' : '') + '> Active</label>' +
          ' &nbsp; <label><input type="radio" name="aktif" value="0" ' + (aktif ? '' : 'checked') + '> Non Active</label></div>') +
      '</div>' +
      '<div class="form-sec span2">Akun Login <span class="muted">— tambahan di luar form acuan</span></div>' +
      field('', '<label><input type="checkbox" name="akun" ' + (akunOn ? 'checked' : '') + ' ' + (hasAkun ? 'disabled' : '') + '> User ini dapat login ke sistem</label>' +
        (hasAkun ? '<span class="hint">Akun yang sudah ada tidak dihapus; nonaktifkan lewat Status.</span>' : ''), 'span2') +
      '<div class="grid2 span2 ' + (akunOn ? '' : 'hidden') + '" data-akun>' +
        field('Username' + REQ, inp('username', r.username, 'autocomplete="off" maxlength="50" placeholder="huruf kecil, angka, titik"')) +
        field('Password' + (hasAkun ? '' : REQ), '<input class="input" name="password" type="password" autocomplete="new-password">' + (hasAkun ? '<span class="hint">Kosongkan bila tidak diubah</span>' : '<span class="hint">Minimal 8 karakter</span>')) +
        field('Konfirmasi Password' + (hasAkun ? '' : REQ), '<input class="input" name="password2" type="password" autocomplete="new-password">') +
      '<details class="span2"><summary>Override Permission — <span data-ovr-n>0</span> hak tambahan</summary>' +
        '<p class="hint">Menambah hak menu di luar hak Role. Hak dari Role tercentang dan terkunci; Override tidak mengurangi hak Role.</p>' +
        '<div data-ovr-body></div></details>' +
      '</div></div>',
    onMount: function (form) {
      var f = form.elements;
      f.akun.addEventListener('change', function (e) { form.querySelector('[data-akun]').classList.toggle('hidden', !e.target.checked); });
      f.id_group.addEventListener('change', function () { loadOverride(form, r.id_user); });
      form.addEventListener('change', function (e) { onOverrideChange(e, form); });
      loadOverride(form, r.id_user);
    },
    onSave: function (f, form) {
      var v = {};
      ['kode', 'nama', 'nik', 'tmp_lahir', 'tgl_lahir', 'id_group', 'gender', 'agama', 'telp', 'email', 'alamat', 'npwp', 'tgl_masuk', 'tipe', 'jobdesc', 'username'].forEach(function (k) { v[k] = f[k].value.trim(); });
      v.aktif = f.aktif.value;
      var akun = f.akun.checked || hasAkun;
      var pw = f.password.value, pw2 = f.password2.value;
      var ok = true;
      var LBL = { kode: 'Employee ID', nama: 'Employee Name', id_group: 'Role', gender: 'Gender', telp: 'Phone Number', tipe: 'Employee Type' };
      Object.keys(LBL).forEach(function (k) { if (!v[k]) ok = invalid(f[k], LBL[k] + ' wajib diisi'); });
      if (v.nik && !/^\d{16}$/.test(v.nik)) ok = invalid(f.nik, 'NIK 16 digit angka');
      if (v.telp && !/^\+?\d{9,15}$/.test(v.telp)) ok = invalid(f.telp, 'Nomor hanya angka (boleh diawali +), 9–15 digit');
      if (v.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email)) ok = invalid(f.email, 'Format email tidak valid');
      if (v.npwp && !/^(\d{15}|\d{16})$/.test(v.npwp.replace(/[.\-\s]/g, ''))) ok = invalid(f.npwp, 'NPWP 15 atau 16 digit angka');
      if (v.tgl_lahir && v.tgl_masuk && v.tgl_masuk < v.tgl_lahir) ok = invalid(f.tgl_masuk, 'Join Date sebelum Birth Date');
      if (akun) {
        var needPw = !hasAkun;
        if (!v.username) ok = invalid(f.username, 'Username wajib diisi');
        else if (!/^[a-z0-9._]{3,50}$/.test(v.username)) ok = invalid(f.username, 'Huruf kecil, angka, titik, atau garis bawah; 3–50 karakter');
        if ((needPw || pw) && pw.length < 8) ok = invalid(f.password, 'Password minimal 8 karakter');
        if ((needPw || pw) && pw !== pw2) ok = invalid(f.password2, 'Konfirmasi password tidak sama');
      }
      if (!ok) return false;
      var data = $.param($.extend({ id: rec ? rec.id : '', akun: akun ? 1 : 0, password: pw, password2: pw2 }, v)) +
        (akun ? '&' + $(form).find('[data-ovr-body] input[name]:checked').serialize() : '');
      postJson('<?= site_url("master_data/save_user"); ?>', data, form);
    }
  });
}

// ---------- Aktifkan / Nonaktifkan ----------
function toggleUser(rec) {
  var on = rec.aktif == 1;
  lmConfirm({
    title: (on ? 'Nonaktifkan' : 'Aktifkan') + ' user?',
    text: 'User ' + (rec.kode || rec.nama) + ' akan di' + (on ? 'nonaktifkan' : 'aktifkan') + '.' + (on && rec.id_user ? '\nUser tidak dapat login sampai diaktifkan kembali.' : ''),
    confirmText: on ? 'Ya, nonaktifkan' : 'Ya, aktifkan',
    danger: on
  }).then(function (ok) {
    if (!ok) return;
    $.ajax({
      url: '<?= site_url("master_data/toggle_user"); ?>/' + rec.id, type: 'POST', dataType: 'json',
      success: function (res) {
        toast(res.msg, res.status ? 'ok' : 'warn');
        if (res.status) setTimeout(function () { location.reload(); }, 600);
      }
    });
  });
}

// ---------- Reset Password ----------
function userReset(rec) {
  showModal({
    title: 'Reset Password',
    saveLabel: 'Simpan Password',
    body: '<p style="margin:0 0 14px">Atur password baru untuk <b>' + esc(rec.kode) + ' — ' + esc(rec.nama) + '</b> (username <span class="code">' + esc(rec.username) + '</span>).</p>' +
      field('Password baru' + REQ, '<input class="input" name="password" type="password" autocomplete="new-password"><span class="hint">Minimal 8 karakter</span>') +
      field('Konfirmasi Password' + REQ, '<input class="input" name="password2" type="password" autocomplete="new-password">'),
    onSave: function (f, form) {
      var ok = true;
      if (f.password.value.length < 8) ok = invalid(f.password, 'Password minimal 8 karakter');
      if (f.password.value !== f.password2.value) ok = invalid(f.password2, 'Konfirmasi password tidak sama');
      if (!ok) return false;
      postJson('<?= site_url("master_data/reset_password_user"); ?>', { id: rec.id, password: f.password.value }, form);
    }
  });
}

renderUsers();
</script>
