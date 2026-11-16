/**
 * Labmania - modal Reschedule Penjualan Program (PJ-FR-26, PJ-FR-27, UC-65).
 * Hanya tanggal dan trainer yang berubah; alasan dan evidence wajib. Server memeriksa ulang semuanya (PJ-NFR-10).
 * Dipakai: LmReschedule.open(idPenjualan, sesudahSimpan).
 */
(function (w) {
  'use strict';

  var EXT = ['jpg', 'jpeg', 'png', 'doc', 'docx', 'pdf'], MAX = 5 * 1024 * 1024;

  var esc = function (v) { return jQuery('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); };
  var tgl = function (iso) {
    if (!iso) return '—';
    var d = new Date(iso + 'T00:00:00'), b = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return d.getDate() + ' ' + b[d.getMonth()] + ' ' + d.getFullYear();
  };
  var rentang = function (a, b) { return !a ? '—' : (!b || a === b ? tgl(a) : tgl(a) + ' – ' + tgl(b)); };
  var addHari = function (iso, n) {
    var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  };
  var sumRow = function (l, v) { return '<div class="sum-row"><span>' + l + '</span><b>' + v + '</b></div>'; };
  var spinner = function (t) { return '<div class="overlay"><div class="modal"><div class="lm-loading"><span class="lm-spin"></span>' + t + '</div></div></div>'; };

  function setErr(name, msg) {
    var $f = jQuery('#rs-form [name="' + name + '"]').closest('.field');
    $f.toggleClass('has-err', !!msg);
    $f.find('.err').remove();
    if (msg) $f.append('<div class="err"><i>!</i>' + esc(msg) + '</div>');
  }

  function open(id, done) {
    var base = w.LmCosting ? w.LmCosting.base() : ''; // URL dasar modul diset lewat LmCosting.configure di tiap halaman
    w.openModal(spinner('Memuat jadwal…'));
    fetch(base + 'reschedule_info/' + id, { credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok || !j.status) throw new Error(j.msg || 'Gagal memuat'); return j.data; }); })
      .then(function (d) { draw(d, id, done, base); })
      .catch(function (e) { w.closeModal(); w.toast(e.message || 'Reschedule gagal dimuat', 'warn'); });
  }

  function draw(d, id, done, base) {
    var dur = d.durasi || 1;
    var so = d.so && d.so.length
      ? '<div class="notice"><span>Program ini sudah punya <b>' + d.so.length + ' Sales Order</b> (' + d.so.map(function (s) { return '<span class="code">' + esc(s.nomor) + '</span>'; }).join(', ') +
        '). Sales Order tidak dihapus: jadwalnya ikut berubah dan nomornya naik revisi bila tanggal diganti. Ganti trainer saja tidak menaikkan revisi. Kirim ulang PDF Sales Order ke customer setelah disimpan.</span><span class="ref">SO-FR-16</span></div>'
      : '';
    var tOpts = d.trainers.map(function (t) {
      return '<option value="' + t.id + '"' + (t.id === d.id_trainer ? ' selected' : '') + '>' + esc(t.kode) + ' — ' + esc(t.nama) + (t.aktif ? '' : ' (nonaktif)') + '</option>';
    }).join('');
    var jadwal = rentang(d.tgl_mulai, d.tgl_selesai) + (d.waktu ? ' · ' + esc(d.waktu) : '') + ' · ' + esc(d.lokasi || '—') + (d.trainer ? ' · Trainer: ' + esc(d.trainer) : '');

    w.openModal('<div class="overlay" onclick="if(event.target===this)closeModal()"><div class="modal wide" role="dialog" aria-modal="true">' +
      '<header><h2>Reschedule ' + esc(d.nomor) + '</h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>' +
      '<form id="rs-form" novalidate><div class="modal-body">' +
      '<p class="hint top">Jadwal baru langsung dipakai di quotation, link Konfirmasi Kehadiran, dan Formulir Konfirmasi' + (d.n_qtn ? ' (' + d.n_qtn + ' quotation aktif)' : '') +
      '. Yang dapat diganti hanya <b>tanggal</b> dan <b>trainer</b>; waktu dan lokasi tetap. Durasi tetap ' + dur + ' hari, jadi tanggal selesai mengikuti tanggal mulai. Harga dan costing tidak berubah.</p>' +
      so + '<div class="summary">' + sumRow('Jadwal sekarang', jadwal) + sumRow('Sudah di-reschedule', d.n_resched + ' kali') + '</div>' +
      '<div class="grid2 gap-t">' +
      '<div class="field"><label for="rs-mulai">Tanggal mulai baru <span class="req">*</span></label><input class="input" type="date" name="tgl_mulai" id="rs-mulai" value="' + esc(d.tgl_mulai) + '" min="' + esc(d.today) + '"></div>' +
      '<div class="field"><label>Tanggal selesai</label><output class="static" id="rs-selesai" for="rs-mulai">' + tgl(d.tgl_selesai) + '</output><span class="hint">otomatis: mulai + ' + (dur - 1) + ' hari</span></div>' +
      '<div class="field span2"><label for="rs-trainer">Trainer <span class="req">*</span></label><select class="input" name="id_trainer" id="rs-trainer"><option value="">— Pilih Trainer —</option>' + tOpts + '</select><span class="hint">boleh diganti bila trainer awal tidak bisa di jadwal baru</span></div>' +
      '<div class="field span2"><label for="rs-alasan">Alasan reschedule <span class="req">*</span></label><textarea class="input" name="alasan" id="rs-alasan" rows="3" maxlength="500" placeholder="mis. Venue tidak tersedia di tanggal awal"></textarea></div>' +
      '<div class="field span2"><label>Evidence <span class="req">*</span></label><label class="pb-drop"><input type="file" name="evidence[]" id="rs-file" multiple accept=".jpg,.jpeg,.png,.doc,.docx,.pdf" hidden><b>Pilih file evidence</b><span class="hint">JPG, PNG, DOC/DOCX, PDF · maks. 5 MB per file · boleh lebih dari satu</span></label><div class="hint" id="rs-files" aria-live="polite">Belum ada file</div></div>' +
      '</div></div>' +
      '<footer><button type="button" class="btn btn-muted" onclick="closeModal()">Batal</button><button type="submit" class="btn btn-accent" id="rs-save">Simpan jadwal baru</button></footer></form></div></div>');

    var $m = jQuery('#rs-mulai'), $file = jQuery('#rs-file');
    if (w.lmSelect2) w.lmSelect2(document.getElementById('rs-form'));
    $m.on('input change', function () { jQuery('#rs-selesai').text(this.value ? tgl(addHari(this.value, dur - 1)) : '—'); });
    $file.on('change', function () { jQuery('#rs-files').text(Array.prototype.map.call(this.files, function (f) { return f.name; }).join(', ') || 'Belum ada file'); });

    jQuery('#rs-form').on('submit', function (e) {
      e.preventDefault();
      ['tgl_mulai', 'id_trainer', 'alasan', 'evidence[]'].forEach(function (n) { setErr(n, ''); });
      var mulai = $m.val(), trainer = jQuery('#rs-trainer').val(), alasan = jQuery.trim(jQuery('#rs-alasan').val()), files = Array.prototype.slice.call($file[0].files), ok = true;
      var bad = function (n, m) { setErr(n, m); ok = false; };
      if (!mulai) bad('tgl_mulai', 'Isi tanggal mulai baru'); else if (mulai < d.today) bad('tgl_mulai', 'Tanggal mulai tidak boleh sebelum hari ini');
      if (!trainer) bad('id_trainer', 'Pilih trainer');
      if (!alasan) bad('alasan', 'Alasan reschedule wajib diisi');
      var salah = files.filter(function (f) { return EXT.indexOf(f.name.split('.').pop().toLowerCase()) < 0; })[0], besar = files.filter(function (f) { return f.size > MAX; })[0];
      if (!files.length) bad('evidence[]', 'Unggah minimal satu file evidence');
      else if (salah) bad('evidence[]', salah.name + ': hanya JPG, PNG, DOC/DOCX, atau PDF');
      else if (besar) bad('evidence[]', besar.name + ' lebih dari 5 MB');
      if (ok && mulai === d.tgl_mulai && Number(trainer) === d.id_trainer) { bad('tgl_mulai', 'Jadwal belum berubah: ganti tanggal atau trainer'); }
      if (!ok) { w.toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

      var fd = new FormData(this), $b = jQuery('#rs-save').prop('disabled', true).addClass('is-busy');
      jQuery.ajax({ url: base + 'reschedule_save/' + id, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(function (res) { w.toast(res.msg, 'ok'); w.closeModal(); if (done) done(res); })
        .fail(function (x) {
          var res = x.responseJSON || {};
          Object.keys(res.errors || {}).forEach(function (k) { setErr(k === 'evidence' ? 'evidence[]' : k, res.errors[k]); });
          w.toast(res.msg || 'Terjadi kesalahan jaringan', 'warn');
          $b.prop('disabled', false).removeClass('is-busy');
        });
    });
  }

  w.LmReschedule = { open: open };
})(window);
