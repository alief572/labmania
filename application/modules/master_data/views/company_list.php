<!-- Script Helper Wilayah -->
<script src="<?= base_url('assets/labmania/js/wilayah.js'); ?>"></script>

<div class="page-head">
  <div>
    <h1>Master Company <span class="ref">FR-30 · FR-31 · FR-32</span></h1>
    <p>Data perusahaan/instansi yang menjadi customer penjualan B2B. Company aktif dipilih di Penjualan Program B2B (wajib) dan sebagai tujuan Quotation ke Company.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.company', 'tambah')): ?><button class="btn btn-accent" onclick="openCompanyModal()">+ Tambah Company</button><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari kode, nama, kota, atau PIC…" aria-label="Cari">
    </div>
    <select class="input sm" id="jenis-filter" aria-label="Filter jenis instansi">
      <option value="all">Semua jenis instansi</option>
      <?php foreach ($jenis_instansi as $j): ?><option value="<?= htmlspecialchars($j); ?>"><?= htmlspecialchars($j); ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-filter="#jenis-filter" data-filter-name="jenis" data-ajax="<?= site_url('master_data/company_data'); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 120px;">Kode Company</th>
          <th>Nama Company</th>
          <th>Jenis Instansi</th>
          <th>Lokasi</th>
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
var JENIS = <?= json_encode($jenis_instansi); ?>;
// Server -> id field di modal
var ERR_FIELD = { nama: 'c-nama', jenis: 'c-jenis', alamat: 'c-alamat', id_prov: 'c-prov', id_kab: 'c-kab', telp: 'c-telp', email: 'c-email',
                  picNama: 'c-pic-nama', picJabatan: 'c-pic-jabatan', picTelp: 'c-pic-hp', picEmail: 'c-pic-email', npwp: 'c-npwp' };
var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
var TELP_RE = /^\+?\d{9,15}$/;

function esc(v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); }
function companyReload() { $('#data-table').DataTable().ajax.reload(null, false); }

function fieldHtml(label, control, cls) {
  return '<div class="field ' + (cls || '') + '"><label>' + label + '</label>' + control + '<div class="err" style="display:none;"></div></div>';
}
function inp(id, val, extra) { return '<input class="input" id="' + id + '" value="' + esc(val) + '" ' + (extra || '') + '>'; }
function sec(t) { return '<div class="form-sec span2">' + t + '</div>'; }

// Pesan di bawah kolom + garis merah; hilang saat kolom diubah.
function setFieldErr(id, msg) {
  var el = document.getElementById(id);
  if (!el) return;
  var $e = $(el).closest('.field').find('.err').first();
  lmMark(el, !!msg);
  $e.html(msg ? '<i>!</i>' + esc(msg) : '').toggle(!!msg);
  if (msg) $(el).one('input change', function () { setFieldErr(id, ''); });
}

function openCompanyModal(data) {
  var isEdit = !!data;
  var d = data || {};
  var jenisOpts = JENIS.map(function (j) { return '<option value="' + esc(j) + '"' + (d.jenis_instansi === j ? ' selected' : '') + '>' + esc(j) + '</option>'; }).join('');

  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 780px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit Company ' + esc(d.id_customer) : 'Tambah Master Company') + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body"><div class="grid2">' +
        '<div class="field span2"><label>Kode Company</label>' +
          '<input class="input" id="c-kode" value="' + (isEdit ? esc(d.id_customer) : '') + '" placeholder="Memuat kode…" readonly>' +
          '<span class="hint">Diisi otomatis oleh sistem saat disimpan</span></div>' +
        sec('1 · Identitas') +
        fieldHtml('Nama Company <span class="req">*</span>', inp('c-nama', d.name_customer, 'maxlength="150"')) +
        fieldHtml('Jenis Instansi <span class="req">*</span>', '<select class="input" id="c-jenis"><option value="">— Pilih —</option>' + jenisOpts + '</select>') +
        sec('2 · Alamat') +
        fieldHtml('Alamat Lengkap <span class="req">*</span>', '<textarea class="input" id="c-alamat" rows="2" maxlength="255">' + esc(d.address_office) + '</textarea>', 'span2') +
        fieldHtml('Provinsi <span class="req">*</span>', '<select class="input" id="c-prov"><option value="">Memuat...</option></select>') +
        fieldHtml('Kota/Kabupaten <span class="req">*</span>', '<select class="input" id="c-kab"><option value="">Pilih provinsi dahulu</option></select><span class="hint">Tersaring menurut provinsi</span>') +
        sec('3 · Kontak Kantor') +
        fieldHtml('Telepon Kantor <span class="req">*</span>', inp('c-telp', d.telephone, 'inputmode="tel" placeholder="021xxxxxxx"')) +
        fieldHtml('Email Kantor <span class="req">*</span>', inp('c-email', d.email, 'type="email" placeholder="info@perusahaan.co.id"')) +
        sec('4 · PIC') +
        fieldHtml('Nama PIC <span class="req">*</span>', inp('c-pic-nama', d.pic_name, 'maxlength="100"')) +
        fieldHtml('Jabatan PIC <span class="req">*</span>', inp('c-pic-jabatan', d.pic_jabatan, 'maxlength="100"')) +
        fieldHtml('Telp/WA PIC <span class="req">*</span>', inp('c-pic-hp', d.pic_hp, 'inputmode="tel" placeholder="08xxxxxxxxxx"')) +
        fieldHtml('Email PIC <span class="req">*</span>', inp('c-pic-email', d.pic_email, 'type="email"')) +
        sec('5 · Pajak') +
        fieldHtml('NPWP Instansi <span class="muted">(opsional)</span>', inp('c-npwp', d.npwp, 'inputmode="numeric" placeholder="15 atau 16 digit"'), 'span2') +
      '</div></div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-company" ' + (isEdit ? '' : 'disabled ') + 'onclick="submitCompany(' + (isEdit ? "'" + esc(d.id_customer) + "'" : "''") + ')">Simpan</button>' +
      '</div>' +
    '</div>';

  openModal(html);
  setTimeout(function () { document.getElementById('c-nama').focus(); }, 100);

  setTimeout(function () {
    initWilayahSelect(document.getElementById('c-prov'), document.getElementById('c-kab'),
      d.id_prov || d.provinsi || null, d.id_city || d.kota || null);
  }, 100);

  // Kode berikutnya dimuat belakangan; Simpan aktif setelah kode siap.
  if (!isEdit) {
    $.getJSON('<?= site_url('master_data/company_next_code'); ?>')
      .done(function (res) { $('#c-kode').val(res.kode); })
      .fail(function () { $('#c-kode').attr('placeholder', 'Dibuat saat disimpan'); })
      .always(function () { $('#btn-save-company').prop('disabled', false); });
  }
}

function editCompany(btn) {
  openCompanyModal(JSON.parse(btn.getAttribute('data-json')));
}

function submitCompany(id) {
  var v = function (i) { return String(document.getElementById(i).value || '').trim(); };
  var provEl = document.getElementById('c-prov'), kabEl = document.getElementById('c-kab');
  var ok = true, first = null;
  var bad = function (i, msg) { setFieldErr(i, msg); ok = false; if (!first) first = i; };

  [['c-nama', 'Nama Company'], ['c-jenis', 'Jenis Instansi'], ['c-alamat', 'Alamat Lengkap'], ['c-prov', 'Provinsi'], ['c-kab', 'Kota/Kabupaten'],
   ['c-telp', 'Telepon Kantor'], ['c-email', 'Email Kantor'], ['c-pic-nama', 'Nama PIC'], ['c-pic-jabatan', 'Jabatan PIC'],
   ['c-pic-hp', 'Telp/WA PIC'], ['c-pic-email', 'Email PIC']]
    .forEach(function (f) { setFieldErr(f[0], ''); if (!v(f[0])) bad(f[0], f[1] + ' wajib diisi'); });
  setFieldErr('c-npwp', '');
  [['c-email'], ['c-pic-email']].forEach(function (f) { if (v(f[0]) && !EMAIL_RE.test(v(f[0]))) bad(f[0], 'Format email tidak valid'); });
  [['c-telp'], ['c-pic-hp']].forEach(function (f) { if (v(f[0]) && !TELP_RE.test(v(f[0]).replace(/[\s-]/g, ''))) bad(f[0], 'Nomor hanya angka, 9–15 digit'); });
  if (v('c-npwp') && !/^(\d{15}|\d{16})$/.test(v('c-npwp').replace(/[.\-\s]/g, ''))) bad('c-npwp', 'NPWP Instansi 15 atau 16 digit angka');
  if (!ok) { document.getElementById(first).focus(); toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

  var $btn = $('#btn-save-company').prop('disabled', true).addClass('is-busy').text('Menyimpan…');
  var done = function () { $btn.prop('disabled', false).removeClass('is-busy').text('Simpan'); };
  var opt = function (el) { return el.options[el.selectedIndex]; };

  $.ajax({
    url: '<?= site_url("master_data/save_company"); ?>',
    type: 'POST',
    dataType: 'json',
    data: {
      id: id, nama: v('c-nama'), jenis: v('c-jenis'), alamat: v('c-alamat'),
      id_prov: provEl.value, provinsi: opt(provEl) ? opt(provEl).getAttribute('data-name') : '',
      id_kab: kabEl.value, kabupaten: opt(kabEl) ? opt(kabEl).getAttribute('data-name') : '',
      telp: v('c-telp'), email: v('c-email'),
      picNama: v('c-pic-nama'), picJabatan: v('c-pic-jabatan'), picTelp: v('c-pic-hp'), picEmail: v('c-pic-email'), npwp: v('c-npwp')
    },
    success: function (res) {
      if (res.status) { toast(res.msg, 'ok'); closeModal(); companyReload(); return; }
      $.each(res.errors || {}, function (k, msg) { if (ERR_FIELD[k]) setFieldErr(ERR_FIELD[k], msg); });
      toast(res.msg, 'warn');
      done();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); done(); }
  });
}

function toggleCompany(id, btn) {
  var $b = $(btn).prop('disabled', true);
  $('#data-table').addClass('is-loading');
  $.ajax({
    url: '<?= site_url("master_data/toggle_company"); ?>/' + encodeURIComponent(id),
    type: 'POST',
    dataType: 'json',
    success: function (res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      if (res.status) companyReload();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); },
    complete: function () { $b.prop('disabled', false); $('#data-table').removeClass('is-loading'); }
  });
}

function delCompany(id, nama) {
  lmConfirm({ title: 'Hapus company?', text: 'Company ' + id + ' — ' + nama + ' akan dihapus.', confirmText: 'Ya, hapus', danger: true })
    .then(function (ok) { if (ok) delCompanyExec(id); });
}

function delCompanyExec(id) {
  $('#data-table').addClass('is-loading'); // tabel diredupkan sampai respons datang
  $.ajax({
    url: '<?= site_url("master_data/delete_company"); ?>/' + encodeURIComponent(id),
    type: 'POST',
    dataType: 'json',
    success: function (res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      if (res.status) companyReload();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); },
    complete: function () { $('#data-table').removeClass('is-loading'); }
  });
}

// Tabel diredupkan selama data dimuat (cari, filter, urut, ganti halaman).
$('#data-table').on('processing.dt', function (e, s, on) { $(this).toggleClass('is-loading', on); });
</script>
