<!-- Script Helper Wilayah -->
<script src="<?= base_url('assets/labmania/js/wilayah.js'); ?>"></script>

<div class="page-head">
  <div>
    <h1>Master Peserta <span class="ref">FR-15 · FR-16 · FR-17</span></h1>
    <p>Data peserta pelatihan/sertifikasi, dikelompokkan menurut Jenis Bidang. Peserta dapat terdaftar di satu Company; di Quotation ke Company hanya peserta company itu yang dapat dipilih.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.peserta', 'tambah')): ?><button class="btn btn-accent" onclick="openPesertaModal()">+ Tambah Peserta</button><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari kode, nama, instansi, atau bidang…" aria-label="Cari">
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-ajax="<?= site_url('master_data/peserta_data'); ?>">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 120px;">Kode Peserta</th>
          <th>Nama Peserta</th>
          <th>Bidang</th>
          <th>Lokasi</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
var bidangsList = <?= json_encode($bidangs); ?>;
var companiesList = <?= json_encode($companies); ?>;

// Server -> id field di modal
var ERR_FIELD = { nama: 'p-nama', gender: 'p-gender', id_customer: 'p-company', instansi: 'p-instansi', npwp_instansi: 'p-npwp',
                  bidang: 'p-bidang', id_prov: 'p-prov', id_kab: 'p-kab', no_telp: 'p-telp', email: 'p-email' };
var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function esc(v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); }
function pesertaReload() { $('#data-table').DataTable().ajax.reload(null, false); }

function fieldHtml(label, control, cls) {
  return '<div class="field ' + (cls || '') + '"><label>' + label + '</label>' + control + '<div class="err" style="display:none;"></div></div>';
}

// Pesan di bawah kolom + garis merah; hilang saat kolom diubah.
function setFieldErr(id, msg) {
  var el = document.getElementById(id);
  if (!el) return;
  var $f = $(el).closest('.field'), $e = $f.find('.err').first();
  lmMark(el, !!msg);
  $e.html(msg ? '<i>!</i>' + esc(msg) : '').toggle(!!msg);
  if (msg) $(el).one('input change', function () { setFieldErr(id, ''); });
}

function openPesertaModal(data) {
  var isEdit = !!data;
  var d = data || {};
  var optList = function (list, cur, idKey, nameKey, activeFn) {
    return list.filter(function (x) { return activeFn(x) || String(x[idKey]) === String(cur); })
      .map(function (x) {
        return '<option value="' + esc(x[idKey]) + '"' + (String(x[idKey]) === String(cur) ? ' selected' : '') + '>' +
          esc(x[nameKey]) + (activeFn(x) ? '' : ' (nonaktif)') + '</option>';
      }).join('');
  };
  var bidangOpts = optList(bidangsList, d.id_jenis_bidang, 'id', 'nama', function (b) { return b.aktif == 1; });
  var compOpts = optList(companiesList, d.id_customer, 'id_customer', 'name_customer', function (c) { return c.activation !== 'nonaktif' && c.activation !== 'inactive'; });
  var locked = !!d.id_customer;

  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 780px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit Peserta ' + esc(d.kode) : 'Tambah Master Peserta') + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body">' +
        '<p class="hint top">Semua field wajib diisi, kecuali Company dan NPWP Instansi.</p>' +
        '<div class="grid2">' +
          '<div class="field span2"><label>Kode Peserta</label>' +
            '<input class="input" id="p-kode" value="' + (isEdit ? esc(d.kode) : '') + '" placeholder="Memuat kode…" readonly>' +
            '<span class="hint">Diisi otomatis oleh sistem saat disimpan</span></div>' +
          fieldHtml('Nama Peserta <span class="req">*</span>', '<input class="input" id="p-nama" maxlength="100" value="' + esc(d.nama) + '">') +
          fieldHtml('Gender <span class="req">*</span>', '<select class="input" id="p-gender"><option value="">— Pilih —</option>' +
            '<option value="L"' + (d.gender === 'L' ? ' selected' : '') + '>Laki-laki</option>' +
            '<option value="P"' + (d.gender === 'P' ? ' selected' : '') + '>Perempuan</option></select>') +
          fieldHtml('Company', '<select class="input" id="p-company"><option value="">— Tanpa company (personal) —</option>' + compOpts + '</select>' +
            '<span class="hint">Dari Master Company; dipakai Quotation ke Company</span>') +
          fieldHtml('Instansi/Perusahaan <span class="req">*</span>', '<input class="input" id="p-instansi" maxlength="150" value="' + esc(d.instansi) + '"' + (locked ? ' readonly' : '') + '>' +
            '<span class="hint">Terisi nama Company bila Company dipilih</span>') +
          fieldHtml('NPWP Instansi <span class="muted">(opsional)</span>', '<input class="input" id="p-npwp" inputmode="numeric" placeholder="15 atau 16 digit" value="' + esc(d.npwp_instansi) + '"' + (locked ? ' readonly' : '') + '>' +
            '<span class="hint" id="p-npwp-hint">' + (locked ? 'Mengikuti NPWP Instansi company' : 'Terisi NPWP company bila Company dipilih') + '</span>', 'span2') +
          fieldHtml('Bidang <span class="req">*</span>', '<select class="input" id="p-bidang"><option value="">— Pilih Jenis Bidang —</option>' + bidangOpts + '</select>') +
          fieldHtml('Lokasi (Provinsi) <span class="req">*</span>', '<select class="input" id="p-prov"><option value="">Memuat...</option></select>') +
          fieldHtml('Kota/Kabupaten <span class="req">*</span>', '<select class="input" id="p-kab"><option value="">Pilih provinsi dahulu</option></select><span class="hint">Tersaring menurut provinsi</span>') +
          fieldHtml('No Telp/WhatsApp <span class="req">*</span>', '<input class="input" id="p-telp" inputmode="tel" placeholder="08xxxxxxxxxx" value="' + esc(d.no_telp) + '">') +
          fieldHtml('Email <span class="req">*</span>', '<input class="input" id="p-email" type="email" placeholder="nama@domain.com" value="' + esc(d.email) + '">', 'span2') +
        '</div>' +
      '</div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-peserta" ' + (isEdit ? '' : 'disabled ') + 'onclick="submitPeserta(' + (d.id || "''") + ')">Simpan</button>' +
      '</div>' +
    '</div>';

  openModal(html);
  setTimeout(function () { document.getElementById('p-nama').focus(); }, 100);

  // Company dipilih -> Instansi & NPWP mengikuti company dan terkunci (FR-16).
  $('#p-company').on('change', function () {
    var c = companiesList.filter(function (x) { return String(x.id_customer) === String(this.value); }, this)[0];
    $('#p-instansi').prop('readonly', !!c); $('#p-npwp').prop('readonly', !!c);
    if (c) { $('#p-instansi').val(c.name_customer); $('#p-npwp').val(c.npwp || ''); setFieldErr('p-instansi', ''); setFieldErr('p-npwp', ''); }
    $('#p-npwp-hint').text(c ? 'Mengikuti NPWP Instansi company' : 'Terisi NPWP company bila Company dipilih');
  });

  // Provinsi/Kota dimuat dari API wilayah.
  setTimeout(function () {
    initWilayahSelect(document.getElementById('p-prov'), document.getElementById('p-kab'),
      d.id_prov || d.provinsi || null, d.id_kab || d.kabupaten || null);
  }, 100);

  // Kode berikutnya dimuat belakangan; Simpan aktif setelah kode siap.
  if (!isEdit) {
    $.getJSON('<?= site_url('master_data/peserta_next_code'); ?>')
      .done(function (res) { $('#p-kode').val(res.kode); })
      .fail(function () { $('#p-kode').attr('placeholder', 'Dibuat saat disimpan'); })
      .always(function () { $('#btn-save-peserta').prop('disabled', false); });
  }
}

function editPeserta(btn) {
  openPesertaModal(JSON.parse(btn.getAttribute('data-json')));
}

function submitPeserta(id) {
  var v = function (i) { return String(document.getElementById(i).value || '').trim(); };
  var provEl = document.getElementById('p-prov'), kabEl = document.getElementById('p-kab');
  var ok = true, first = null;
  var bad = function (i, msg) { setFieldErr(i, msg); ok = false; if (!first) first = i; };

  [['p-nama', 'Nama Peserta'], ['p-gender', 'Gender'], ['p-instansi', 'Instansi/Perusahaan'], ['p-bidang', 'Bidang'],
   ['p-prov', 'Provinsi'], ['p-kab', 'Kota/Kabupaten'], ['p-telp', 'No Telp/WhatsApp'], ['p-email', 'Email']]
    .forEach(function (f) { setFieldErr(f[0], ''); if (!v(f[0])) bad(f[0], f[1] + ' wajib diisi'); });
  setFieldErr('p-npwp', '');
  if (v('p-email') && !EMAIL_RE.test(v('p-email'))) bad('p-email', 'Format email tidak valid');
  if (v('p-telp') && !/^\+?\d{9,15}$/.test(v('p-telp').replace(/[\s-]/g, ''))) bad('p-telp', 'Nomor hanya angka, 9–15 digit');
  if (v('p-npwp') && !/^(\d{15}|\d{16})$/.test(v('p-npwp').replace(/[.\-\s]/g, ''))) bad('p-npwp', 'NPWP Instansi 15 atau 16 digit angka');
  if (!ok) { document.getElementById(first).focus(); toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

  var $btn = $('#btn-save-peserta').prop('disabled', true).addClass('is-busy').text('Menyimpan…');
  var done = function () { $btn.prop('disabled', false).removeClass('is-busy').text('Simpan'); };
  var opt = function (el) { return el.options[el.selectedIndex]; };

  $.ajax({
    url: '<?= site_url("master_data/save_peserta"); ?>',
    type: 'POST',
    dataType: 'json',
    data: {
      id: id, nama: v('p-nama'), gender: v('p-gender'), bidang: v('p-bidang'), instansi: v('p-instansi'),
      id_customer: v('p-company'),
      id_prov: provEl.value, provinsi: opt(provEl) ? opt(provEl).getAttribute('data-name') : '',
      id_kab: kabEl.value, kabupaten: opt(kabEl) ? opt(kabEl).getAttribute('data-name') : '',
      no_telp: v('p-telp'), email: v('p-email'), npwp_instansi: v('p-npwp')
    },
    success: function (res) {
      if (res.status) { toast(res.msg, 'ok'); closeModal(); pesertaReload(); return; }
      $.each(res.errors || {}, function (k, msg) { if (ERR_FIELD[k]) setFieldErr(ERR_FIELD[k], msg); });
      toast(res.msg, 'warn');
      done();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); done(); }
  });
}

function delPeserta(id, nama, kode) {
  lmConfirm({ title: 'Hapus peserta?', text: 'Peserta ' + kode + ' — ' + nama + ' akan dihapus.', confirmText: 'Ya, hapus', danger: true })
    .then(function (ok) { if (ok) delPesertaExec(id); });
}

function delPesertaExec(id) {
  $('#data-table').addClass('is-loading'); // tabel diredupkan sampai respons datang
  $.ajax({
    url: '<?= site_url("master_data/delete_peserta"); ?>/' + id,
    type: 'POST',
    dataType: 'json',
    success: function (res) {
      toast(res.msg, res.status ? 'ok' : 'warn');
      if (res.status) pesertaReload();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); },
    complete: function () { $('#data-table').removeClass('is-loading'); }
  });
}

// Tabel diredupkan selama data dimuat (cari, urut, ganti halaman).
$('#data-table').on('processing.dt', function (e, s, on) { $(this).toggleClass('is-loading', on); });
</script>
