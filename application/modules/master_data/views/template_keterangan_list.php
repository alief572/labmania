<?php
$KAT = ['syarat' => 'Syarat keikutsertaan', 'termasuk' => 'Termasuk', 'tidak' => 'Tidak termasuk', 'dokumen' => 'Dokumen persyaratan', 'klausul' => 'Klausul tanda tangan'];
$BERLAKU = ['semua' => 'Semua', 'public' => 'Public', 'b2b' => 'B2B'];
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
<div class="page-head">
  <div>
    <h1>Template Keterangan Quotation <span class="ref">FR-48 – FR-50</span></h1>
    <p>Teks default blok Keterangan di Penawaran, per kategori. Butir <b>Default</b> otomatis tersalin ke quotation baru sesuai jenis penjualan, lalu dapat diedit per quotation tanpa mengubah master.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.template_keterangan', 'tambah')): ?><button class="btn btn-accent" onclick="openKetModal()">+ Tambah Butir</button><?php endif; ?>
  </div>
</div>

<div class="notice"><span>Perubahan di sini hanya berlaku untuk quotation <b>baru</b>; quotation yang sudah dibuat menyimpan teksnya sendiri. Tidak dapat dihapus — gunakan Nonaktifkan.</span><span class="ref">FR-50</span></div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari kode atau teks…" aria-label="Cari">
    </div>
    <select class="input sm" id="status-filter" aria-label="Filter status">
      <option value="all">Semua status</option>
      <option value="on">Aktif</option>
      <option value="off">Nonaktif</option>
    </select>
    <select class="input sm" id="kat-filter" aria-label="Filter kategori">
      <option value="all">Semua kategori</option>
      <?php foreach ($KAT as $k => $l): ?><option value="<?= $k; ?>"><?= $e($l); ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt data-search-cols="[1,2,3]">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 100px;">Kode</th>
          <th>Kategori</th>
          <th>Teks</th>
          <th>Berlaku untuk</th>
          <th style="width: 80px;">Default</th>
          <th style="width: 110px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): foreach ($items as $k): ?>
          <tr data-id="<?= (int)$k['id']; ?>" data-kat="<?= $e($k['kategori']); ?>" data-aktif="<?= $k['aktif'] ? 1 : 0; ?>" class="<?= $k['aktif'] ? '' : 'is-off'; ?>" data-json='<?= $e(json_encode($k)); ?>'>
            <td></td>
            <td><span class="code"><?= $e($k['kode']); ?></span></td>
            <td class="nowrap"><?= $e($KAT[$k['kategori']] ?? $k['kategori']); ?></td>
            <td class="title"><?= $e($k['teks']); ?></td>
            <td class="nowrap"><?= $k['berlaku_untuk'] === 'semua' ? 'Semua' : '<span class="tag tag-' . $e($k['berlaku_untuk']) . '">' . $e($BERLAKU[$k['berlaku_untuk']] ?? $k['berlaku_untuk']) . '</span>'; ?></td>
            <td class="nowrap"><?= $k['is_default'] ? '<b>Ya</b>' : '<span class="muted">Tidak</span>'; ?></td>
            <td class="nowrap"><span class="badge <?= $k['aktif'] ? 'on' : 'off'; ?>"><?= $k['aktif'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
            <td class="hist"><?= hist_cell($k); ?></td>
            <td class="act">
              <button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button>
              <span class="gear-src" hidden>
              <?php if (lm_can('master_data.template_keterangan', 'ubah')): ?><button class="icon-btn" onclick="editKet(this)" title="Edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              </button><?php endif; ?>
              <?php if (lm_can('master_data.template_keterangan', 'aktif')): ?><button class="icon-btn" onclick="toggleKet(<?= (int)$k['id']; ?>)" title="<?= $k['aktif'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
              </button><?php endif; ?>
              <?= riwayat_btn('ms_template_keterangan', $k['kode']); ?></span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
var KAT = <?= json_encode($KAT); ?>;
var BERLAKU = <?= json_encode($BERLAKU); ?>;
var ERR_FIELD = { kategori: 'k-kategori', teks: 'k-teks', berlaku_untuk: 'k-berlaku' };

function esc(v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); }

// Filter kategori & status (client-side; jumlah butir kecil): memakai atribut data-kat / data-aktif di baris.
$.fn.dataTable.ext.search.push(function (settings, data, idx) {
  if (settings.nTable.id !== 'data-table') return true;
  var tr = settings.aoData[idx].nTr, st = $('#status-filter').val(), kat = $('#kat-filter').val();
  if (kat !== 'all' && tr.getAttribute('data-kat') !== kat) return false;
  if (st !== 'all' && tr.getAttribute('data-aktif') !== (st === 'on' ? '1' : '0')) return false;
  return true;
});
$('#status-filter, #kat-filter').on('change', function () { $('#data-table').DataTable().draw(); });

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

function openKetModal(data) {
  var isEdit = !!data;
  var d = data || { kategori: KAT[$('#kat-filter').val()] ? $('#kat-filter').val() : '', teks: '', berlaku_untuk: 'semua', is_default: 1 };
  var opts = function (map, cur) {
    return Object.keys(map).map(function (k) { return '<option value="' + k + '"' + (cur === k ? ' selected' : '') + '>' + esc(map[k]) + '</option>'; }).join('');
  };

  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal" style="max-width: 780px;">' +
      '<div class="modal-head"><h3>' + (isEdit ? 'Edit Butir ' + esc(d.kode) : 'Tambah Butir Keterangan') + '</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button></div>' +
      '<div class="modal-body"><div class="grid2">' +
        fieldHtml('Kode', '<input class="input" id="k-kode" value="' + (isEdit ? esc(d.kode) : '') + '" placeholder="Memuat kode…" readonly>') +
        fieldHtml('Kategori <span class="req">*</span>', '<select class="input" id="k-kategori"><option value="">— Pilih —</option>' + opts(KAT, d.kategori) + '</select>') +
        fieldHtml('Teks <span class="req">*</span>', '<textarea class="input" id="k-teks" rows="3" maxlength="500">' + esc(d.teks) + '</textarea>' +
          '<span class="hint">Satu butir; tercetak sebagai satu baris di Keterangan Penawaran</span>', 'span2') +
        fieldHtml('Berlaku untuk', '<select class="input" id="k-berlaku">' + opts(BERLAKU, d.berlaku_untuk) + '</select>' +
          '<span class="hint">Jenis penjualan yang menerima butir ini</span>') +
        fieldHtml('Default', '<label class="chk static"><input type="checkbox" id="k-default"' + (d.is_default == 1 ? ' checked' : '') + '> Otomatis masuk quotation baru</label>' +
          '<span class="hint">Bila tidak, butir hanya tersedia sebagai pilihan tambahan</span>') +
      '</div></div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-ket" ' + (isEdit ? '' : 'disabled ') + 'onclick="submitKet(' + (isEdit ? d.id : "''") + ')">Simpan</button>' +
      '</div>' +
    '</div>';

  openModal(html);

  // Kode berikutnya dimuat belakangan; Simpan aktif setelah kode siap.
  if (!isEdit) {
    $.getJSON('<?= site_url('master_data/template_keterangan_next_code'); ?>')
      .done(function (res) { $('#k-kode').val(res.kode); })
      .fail(function () { $('#k-kode').attr('placeholder', 'Dibuat saat disimpan'); })
      .always(function () { $('#btn-save-ket').prop('disabled', false); });
  }
}

function editKet(btn) {
  openKetModal(JSON.parse(btn.closest('tr').getAttribute('data-json')));
}

function submitKet(id) {
  var kategori = $('#k-kategori').val(), teks = $('#k-teks').val().trim();
  var ok = true;
  ['k-kategori', 'k-teks'].forEach(function (i) { setFieldErr(i, ''); });
  if (!kategori) { setFieldErr('k-kategori', 'Kategori wajib dipilih'); ok = false; }
  if (!teks) { setFieldErr('k-teks', 'Teks wajib diisi'); ok = false; }
  else if (teks.length > 500) { setFieldErr('k-teks', 'Teks maksimal 500 karakter'); ok = false; }
  if (!ok) { toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return; }

  var $btn = $('#btn-save-ket').prop('disabled', true).addClass('is-busy').text('Menyimpan…');
  var done = function () { $btn.prop('disabled', false).removeClass('is-busy').text('Simpan'); };

  $.ajax({
    url: '<?= site_url("master_data/save_template_keterangan"); ?>',
    type: 'POST',
    dataType: 'json',
    data: { id: id, kategori: kategori, berlaku_untuk: $('#k-berlaku').val(), teks: teks, is_default: $('#k-default').prop('checked') ? 1 : 0 },
    success: function (res) {
      if (res.status) {
        toast(res.msg, 'ok');
        setTimeout(function () { location.reload(); }, 600); // tombol tetap sibuk sampai halaman dimuat ulang
        return;
      }
      $.each(res.errors || {}, function (k, msg) { if (ERR_FIELD[k]) setFieldErr(ERR_FIELD[k], msg); });
      toast(res.msg, 'warn');
      done();
    },
    error: function () { toast('Terjadi kesalahan jaringan', 'warn'); done(); }
  });
}

function toggleKet(id) {
  var $tr = $('#data-table tr[data-id="' + id + '"]').addClass('is-loading'); // baris diredupkan selama diproses
  $.ajax({
    url: '<?= site_url("master_data/toggle_template_keterangan"); ?>/' + id,
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
