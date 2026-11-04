<?php
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$mark = function ($s) use ($e) { return preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $e($s)); };
$STD = ['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'email']; // isian umum; selain ini tampil sebagai chip di daftar
?>
<div class="page-head">
  <div>
    <h1>Template Formulir Konfirmasi <span class="ref">FR-51 – FR-53</span></h1>
    <p>Template Formulir Konfirmasi Keikutsertaan yang dicetak bersama quotation. Template dipilih otomatis dari Jenis Produk paket, lalu isian dan kalimat pernyataannya dapat disesuaikan per quotation.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.template_konfirmasi', 'tambah')): ?><button class="btn btn-accent" onclick="openKfModal()">+ Tambah Template</button><?php endif; ?>
  </div>
</div>

<div class="notice"><span>Perubahan di sini hanya berlaku untuk quotation <b>baru</b>. Tidak dapat dihapus — gunakan Nonaktifkan.</span><span class="ref">FR-53</span></div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari kode atau nama template…" aria-label="Cari">
    </div>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <option value="on">Aktif</option>
      <option value="off">Nonaktif</option>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-search-cols="[1,2]">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 100px;">Kode</th>
          <th>Nama Template</th>
          <th>Default untuk Jenis Produk</th>
          <th>Isian</th>
          <th>Pernyataan</th>
          <th style="width: 110px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): foreach ($items as $kf):
          $isian = json_decode($kf['isian'] ?? '[]', true) ?: [];
          $p = (string)$kf['pernyataan'];
          $ring = mb_strlen($p) > 110 ? mb_substr($p, 0, 110) . '…' : $p;
        ?>
          <tr data-id="<?= (int)$kf['id']; ?>" data-aktif="<?= $kf['aktif'] ? 1 : 0; ?>" class="<?= $kf['aktif'] ? '' : 'is-off'; ?>" data-json='<?= $e(json_encode($kf)); ?>'>
            <td></td>
            <td><span class="code"><?= $e($kf['kode']); ?></span></td>
            <td><b><?= $e($kf['nama']); ?></b><br><span class="muted">subject: kegiatan <?= $e($kf['label_kegiatan']); ?></span></td>
            <td><?= !empty($kf['nama_produk']) ? $e($kf['nama_produk']) : '<span class="muted">— (pilih manual)</span>'; ?></td>
            <td><b><?= count($isian); ?></b> <span class="muted">isian</span><br>
              <?php foreach ($isian as $k): if (in_array($k, $STD, true) || !isset($kf_fields[$k])) continue; ?><span class="ps-chip"><?= $e($kf_fields[$k]); ?></span> <?php endforeach; ?></td>
            <td class="title"><span class="muted"><?= $mark($ring); ?></span></td>
            <td class="nowrap"><span class="badge <?= $kf['aktif'] ? 'on' : 'off'; ?>"><?= $kf['aktif'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
            <td class="hist"><?= hist_cell($kf); ?></td>
            <td class="act">
              <button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button>
              <span class="gear-src" hidden>
              <?php if (lm_can('master_data.template_konfirmasi', 'ubah')): ?><button class="icon-btn" onclick="editKf(this)" title="Edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              </button><?php endif; ?>
              <button class="icon-btn" onclick="contohKf(<?= (int)$kf['id']; ?>)" title="Lihat contoh">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <?php if (lm_can('master_data.template_konfirmasi', 'aktif')): ?><button class="icon-btn" onclick="toggleKf(<?= (int)$kf['id']; ?>)" title="<?= $kf['aktif'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
              </button><?php endif; ?>
              <?= riwayat_btn('ms_template_konfirmasi', $kf['kode']); ?></span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
var jpList = <?= json_encode($jenis_produk); ?>;
var KF_FIELDS = <?= json_encode($kf_fields); ?>;
var KF_DEFAULT = {
  isian: ['nama', 'instansi', 'wa', 'email'],
  pernyataan: 'Saya **akan hadir** pada kegiatan "**{judul}**" yang akan diselenggarakan pada tanggal **{tanggal}** pukul {waktu} ({durasi} hari, {lokasi})',
  catatan: [
    'Konfirmasi kehadiran harap dikirimkan kembali ke email {email} atau ke nomor {telp} dengan subject email **Konfirmasi Peserta** kegiatan **{kegiatan}** dengan judul **{judul}**',
    'Keikutsertaan kami hitung berdasarkan pengiriman formulir keikutsertaan ini yang tercepat.',
    'Panitia berhak menolak jika kuota telah terpenuhi.',
    '*) Nama lengkap akan menjadi acuan penulisan pada sertifikat.',
    'Jika sudah mengisi formulir berikut, maka **peserta setuju untuk tidak membatalkan keikutsertaan dalam bentuk apapun.**',
    'Formulir keikutsertaan ini maksimal diserahkan H-14 sebelum kegiatan.',
    'Apabila ada yang ingin didiskusikan, silahkan segera menghubungi ke nomor {telp}'
  ].join('\n')
};
var ERR_FIELD = { nama: 'kf-nama', label_kegiatan: 'kf-label', id_jenis_produk: 'kf-jp', pernyataan: 'kf-pernyataan' };

function esc(v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); }

// Filter status (client-side; jumlah template kecil): memakai atribut data-aktif di baris.
$.fn.dataTable.ext.search.push(function (settings, data, idx) {
  if (settings.nTable.id !== 'data-table') return true;
  var v = $('#status-filter').val();
  if (v === 'all') return true;
  return settings.aoData[idx].nTr.getAttribute('data-aktif') === (v === 'on' ? '1' : '0');
});
$('#status-filter').on('change', function () { $('#data-table').DataTable().draw(); });

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

function openKfModal(data) {
  var isEdit = !!data;
  var d = data || { nama: '', label_kegiatan: '', id_jenis_produk: '', isian: JSON.stringify(KF_DEFAULT.isian), pernyataan: KF_DEFAULT.pernyataan, catatan: KF_DEFAULT.catatan };
  var isian = [];
  try { isian = JSON.parse(d.isian || '[]'); } catch (e) {}

  var jpOpts = jpList.filter(function (jp) { return jp.aktif == 1 || String(jp.id) === String(d.id_jenis_produk); })
    .map(function (jp) { return '<option value="' + jp.id + '"' + (String(jp.id) === String(d.id_jenis_produk) ? ' selected' : '') + '>' + esc(jp.nama) + (jp.aktif == 1 ? '' : ' (nonaktif)') + '</option>'; }).join('');
  var isianHtml = Object.keys(KF_FIELDS).map(function (k) {
    return '<label class="chk"><input type="checkbox" name="kf-isian" value="' + k + '"' + (isian.indexOf(k) >= 0 ? ' checked' : '') + '> ' + esc(KF_FIELDS[k]) + '</label>';
  }).join('');

  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 1000px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit Template ' + esc(d.kode) : 'Tambah Template Formulir Konfirmasi') + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body"><div class="kf-grid"><div>' +
        '<div class="grid2">' +
          fieldHtml('Kode', '<input class="input" id="kf-kode" value="' + (isEdit ? esc(d.kode) : '') + '" placeholder="Memuat kode…" readonly>') +
          fieldHtml('Nama template <span class="req">*</span>', '<input class="input" id="kf-nama" maxlength="80" value="' + esc(d.nama) + '" placeholder="mis. Workshop">') +
          fieldHtml('Label kegiatan <span class="req">*</span>', '<input class="input" id="kf-label" maxlength="80" value="' + esc(d.label_kegiatan) + '" placeholder="mis. Training dan Sertifikasi">' +
            '<span class="hint">Dipakai di subject email: "kegiatan … dengan judul …"</span>') +
          fieldHtml('Default untuk Jenis Produk', '<select class="input" id="kf-jp"><option value="">— Tidak ada (pilih manual) —</option>' + jpOpts + '</select>') +
        '</div>' +
        '<p class="qtn-h">Isian formulir</p>' +
        '<div class="kf-isian" id="kf-isian">' + isianHtml + '</div>' +
        '<div class="err" id="kf-isian-err" style="display:none;"></div>' +
      '</div><div>' +
        '<p class="qtn-h">Pernyataan 1</p>' +
        fieldHtml('Kalimat pernyataan <span class="req">*</span>', '<textarea class="input" id="kf-pernyataan" rows="4">' + esc(d.pernyataan) + '</textarea>' +
          '<span class="hint">Penanda diisi otomatis: {judul} {tanggal} {waktu} {durasi} {lokasi}. **teks** = tebal. Pernyataan 2 (membaca &amp; menyetujui S&amp;K) selalu tercetak.</span>') +
        '<p class="qtn-h">Catatan</p>' +
        fieldHtml('Butir catatan', '<textarea class="input" id="kf-catatan" rows="7">' + esc(d.catatan) + '</textarea>' +
          '<span class="hint">Satu butir per baris. Penanda: {email} {telp} (sales quotation), {kegiatan}, {judul}.</span>') +
      '</div></div></div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-kf" ' + (isEdit ? '' : 'disabled ') + 'onclick="submitKf(' + (isEdit ? d.id : "''") + ')">Simpan</button>' +
      '</div>' +
    '</div>';

  openModal(html);

  // Kode berikutnya dimuat belakangan; Simpan aktif setelah kode siap.
  if (!isEdit) {
    $.getJSON('<?= site_url('master_data/template_konfirmasi_next_code'); ?>')
      .done(function (res) { $('#kf-kode').val(res.kode); })
      .fail(function () { $('#kf-kode').attr('placeholder', 'Dibuat saat disimpan'); })
      .always(function () { $('#btn-save-kf').prop('disabled', false); });
  }
}

function editKf(btn) {
  openKfModal(JSON.parse(btn.closest('tr').getAttribute('data-json')));
}

// Formulir contoh dimuat dari server; modal berisi spinner dulu agar tidak terlihat macet.
function contohKf(id) {
  openModal('<div class="overlay" onclick="if(event.target===this)closeModal()"><div class="modal"><div class="lm-loading"><span class="lm-spin"></span>Memuat contoh…</div></div></div>');
  fetch('<?= site_url('master_data/template_konfirmasi_contoh/'); ?>' + id, { credentials: 'same-origin' })
    .then(function (r) { if (!r.ok) throw 0; return r.text(); })
    .then(function (html) { openModal(html); })
    .catch(function () { closeModal(); toast('Contoh gagal dimuat', 'error'); });
}

function submitKf(id) {
  var v = function (i) { return String($('#' + i).val() || '').trim(); };
  var isian = $('input[name=kf-isian]:checked').map(function () { return this.value; }).get();
  var ok = true;
  Object.keys(ERR_FIELD).forEach(function (k) { setFieldErr(ERR_FIELD[k], ''); });
  $('#kf-isian-err').hide().empty();
  if (!v('kf-nama')) { setFieldErr('kf-nama', 'Nama template wajib diisi'); ok = false; }
  if (!v('kf-label')) { setFieldErr('kf-label', 'Label kegiatan wajib diisi'); ok = false; }
  if (!v('kf-pernyataan')) { setFieldErr('kf-pernyataan', 'Kalimat pernyataan wajib diisi'); ok = false; }
  if (!isian.length) { $('#kf-isian-err').html('<i>!</i>Pilih minimal 1 isian').show(); ok = false; }
  if (!ok) { toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

  var $btn = $('#btn-save-kf').prop('disabled', true).addClass('is-busy').text('Menyimpan…');
  var done = function () { $btn.prop('disabled', false).removeClass('is-busy').text('Simpan'); };

  $.ajax({
    url: '<?= site_url("master_data/save_template_konfirmasi"); ?>',
    type: 'POST',
    dataType: 'json',
    data: { id: id, nama: v('kf-nama'), label_kegiatan: v('kf-label'), id_jenis_produk: v('kf-jp'), isian: isian, pernyataan: v('kf-pernyataan'), catatan: $('#kf-catatan').val() },
    success: function (res) {
      if (res.status) {
        toast(res.msg, 'ok');
        setTimeout(function () { location.reload(); }, 600); // tombol tetap sibuk sampai halaman dimuat ulang
        return;
      }
      $.each(res.errors || {}, function (k, msg) {
        if (k === 'isian') $('#kf-isian-err').html('<i>!</i>' + esc(msg)).show();
        else if (ERR_FIELD[k]) setFieldErr(ERR_FIELD[k], msg);
      });
      toast(res.msg, 'warn');
      done();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); done(); }
  });
}

function toggleKf(id) {
  var $tr = $('#data-table tr[data-id="' + id + '"]').addClass('is-loading'); // baris diredupkan selama diproses
  $.ajax({
    url: '<?= site_url("master_data/toggle_template_konfirmasi"); ?>/' + id,
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
