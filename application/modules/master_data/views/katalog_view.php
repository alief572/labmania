<?php
// Katalog Produk (FR-14, FR-60 - FR-63, FR-67): disusun dari Paket Produk aktif; Lingkup diisi dari kartu; cetak PDF dari hasil filter.
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
<div class="page-head">
  <div>
    <h1>Katalog Produk <span class="ref">FR-14 · FR-60 – FR-63 · FR-67 – FR-68</span></h1>
    <p>Katalog disusun otomatis dari Paket Produk berstatus Aktif. Isi Lingkup tiap paket lewat tombol di kartunya. Saring berdasarkan Bidang dan Sertifikat, lalu cetak sebagai PDF untuk Promosi.</p>
  </div>
  <div class="page-actions">
    <?php if ($can_cetak): ?><a class="btn btn-accent" id="kt-print" target="_blank" rel="noopener" href="<?= site_url('master_data/katalog_pdf'); ?>">Cetak Katalog (PDF)</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="kt-q" placeholder="Cari judul, nomor, atau atribut…" aria-label="Cari" value="<?= $e($this->input->get('q')); ?>">
    </div>
    <select class="input sm" id="kt-bidang" aria-label="Filter bidang">
      <option value="all">Semua bidang</option>
      <?php foreach ($opt_bidang as $o): ?><option value="<?= (int)$o['id']; ?>"><?= $e($o['nama']); ?> (<?= (int)$o['n']; ?>)</option><?php endforeach; ?>
    </select>
    <select class="input sm" id="kt-sert" aria-label="Filter sertifikat">
      <option value="all">Semua sertifikat</option>
      <?php foreach ($opt_sert as $o): ?><option value="<?= (int)$o['id']; ?>"><?= $e($o['nama']); ?> (<?= (int)$o['n']; ?>)</option><?php endforeach; ?>
    </select>
  </div>
  <div id="list"><?= $list_html; ?></div>
</div>

<script>
var KT_MAX = <?= (int)Master_data_model::LINGKUP_MAX; ?>, KT_LEN = <?= (int)Master_data_model::LINGKUP_LEN; ?>;
var kes = function (v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); };

function ktParams() { return { bidang: $('#kt-bidang').val(), sert: $('#kt-sert').val(), q: $.trim($('#kt-q').val()) }; }

// Cetak mengikuti filter yang sedang tampil (FR-62); ditolak bila hasilnya kosong.
function ktSyncPrint() {
  $('#kt-print').attr('href', '<?= site_url('master_data/katalog_pdf'); ?>?' + $.param(ktParams()));
}

var ktTimer;
function ktReload() {
  ktSyncPrint();
  $('#list').addClass('is-loading');
  $.get('<?= site_url('master_data/katalog_list'); ?>', ktParams())
    .done(function (html) { $('#list').html(html); })
    .fail(function () { toast('Terjadi kesalahan jaringan', 'warn'); })
    .always(function () { $('#list').removeClass('is-loading'); });
}

$('#kt-bidang, #kt-sert').on('change', ktReload);
$('#kt-q').on('input', function () { clearTimeout(ktTimer); ktTimer = setTimeout(ktReload, 300); });
$('#kt-print').on('click', function (e) {
  if (!parseInt($('#kt-sum').attr('data-total') || '0', 10)) { e.preventDefault(); toast('Tidak ada paket produk untuk dicetak — ubah filter terlebih dahulu', 'warn'); }
});
ktSyncPrint();

// ----- Lingkup per Paket (FR-67): banyak item, Enter menambah baris, maks. 20 x 200 karakter -----
function lkRow(v, i) {
  return '<li class="field ling-row"><label class="sr-only" for="lk-' + i + '">Item lingkup ' + (i + 1) + '</label>' +
    '<span class="ling-no" aria-hidden="true">' + (i + 1) + '.</span>' +
    '<input class="input" id="lk-' + i + '" name="lk" value="' + kes(v) + '" maxlength="' + KT_LEN + '" placeholder="Tulis satu item lingkup">' +
    '<button type="button" class="btn btn-ghost sm" data-lk="del" aria-label="Hapus item lingkup ' + (i + 1) + '">Hapus</button>' +
    '<div class="err" style="display:none;"></div></li>';
}

function lkValues() { return $('#lk-list input[name="lk"]').map(function () { return this.value; }).get(); }

function lkDraw(list, focusIdx) {
  $('#lk-list').html(list.map(lkRow).join(''));
  $('[data-lk="add"]').prop('disabled', list.length >= KT_MAX);
  $('#lk-count').text(list.length + ' dari maks. ' + KT_MAX + ' item');
  if (focusIdx !== undefined) { var el = document.getElementById('lk-' + focusIdx) || document.querySelector('[data-lk="add"]'); if (el) el.focus(); }
}

function lkErr(i, msg) {
  var $li = $('#lk-list li').eq(i);
  $li.find('.err').html(msg ? '<i>!</i>' + kes(msg) : '').toggle(!!msg);
  if (msg) $li.find('input').one('input', function () { lkErr(i, ''); });
}

function openLingkup(d) {
  var html = '<div class="modal-scrim" onclick="closeModal()"></div><div class="modal wide">' +
    '<div class="modal-head"><h3>Lingkup: ' + kes(d.judul) + '</h3><button class="close-btn" onclick="closeModal()">&times;</button></div>' +
    '<div class="modal-body"><p class="hint top"><span class="code">' + kes(d.nomor) + '</span> · ' + d.attr.map(kes).join(' · ') +
    '. Lingkup tampil di kartu Katalog Produk dan di PDF katalog. Satu baris untuk satu item; tekan Enter untuk menambah baris.</p>' +
    '<ol class="ling-list" id="lk-list"></ol>' +
    '<div class="ling-tools"><button type="button" class="btn btn-outline sm" data-lk="add">+ Tambah item lingkup</button><span class="muted" id="lk-count"></span></div></div>' +
    '<div class="modal-foot"><button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
    '<button class="btn btn-accent" id="btn-save-lk">Simpan lingkup</button></div></div>';
  openModal(html);
  lkDraw(d.items.length ? d.items : ['']);

  $('.modal').on('click', '[data-lk]', function () {
    var list = lkValues(), kind = $(this).attr('data-lk');
    if (kind === 'add') { if (list.length < KT_MAX) { list.push(''); lkDraw(list, list.length - 1); } return; }
    var i = $('[data-lk="del"]').index(this);
    list.splice(i, 1);
    lkDraw(list.length ? list : [''], Math.min(i, Math.max(list.length - 1, 0)));
  }).on('keydown', 'input[name="lk"]', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var list = lkValues(), i = $('#lk-list input[name="lk"]').index(this);
    if (list.length >= KT_MAX) return;
    list.splice(i + 1, 0, ''); lkDraw(list, i + 1);
  });

  $('#btn-save-lk').on('click', function () {
    var vals = lkValues(), seen = {}, ok = true;
    vals.forEach(function (v, i) {
      lkErr(i, '');
      var k = $.trim(v).toLowerCase(); if (!k) return;
      if (seen[k]) { lkErr(i, 'Item ini sama dengan item lain'); ok = false; } else seen[k] = true;
    });
    if (!ok) { toast('Belum tersimpan — perbaiki item yang ditandai', 'warn'); return; }
    var $b = $(this).prop('disabled', true).addClass('is-busy').text('Menyimpan…');
    $.ajax({ url: '<?= site_url('master_data/save_lingkup'); ?>', type: 'POST', dataType: 'json', data: { id: d.id, items: vals },
      success: function (res) {
        if (res.status) { toast(res.msg, 'ok'); closeModal(); ktReload(); return; }
        $.each(res.errors || {}, function (i, m) { lkErr(+i, m); });
        toast(res.msg, 'warn'); $b.prop('disabled', false).removeClass('is-busy').text('Simpan lingkup');
      },
      error: function () { toast('Terjadi kesalahan jaringan', 'warn'); $b.prop('disabled', false).removeClass('is-busy').text('Simpan lingkup'); }
    });
  });
  setTimeout(function () { var el = $('#lk-list input').first()[0]; if (el) el.focus(); }, 100);
}

$(document).on('click', '[data-lingkup]', function () { openLingkup(JSON.parse($(this).attr('data-lingkup'))); });
</script>
