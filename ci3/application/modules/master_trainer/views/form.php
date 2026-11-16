<?php
$t = $trainer ?? [];
$id = $t['id'] ?? '';
$isEdit = !empty($id);

// Daftar riwayat (FR-45): satu tab per daftar, baris dapat ditambah/dihapus. Kolom: [nama, label, kelas, maks karakter, tipe].
$lists = [
    'pelatihan' => [
        'title' => 'Pelatihan & Seminar',
        'cols'  => [['judul', 'Judul Pelatihan/Seminar/Lainnya', 'wide', 255], ['penyelenggara', 'Penyelenggara/Tempat', '', 150], ['waktu', 'Waktu', 'yr', 50]],
        'rows'  => $t['pelatihan'] ?? [],
    ],
    'pengalaman_kerja' => [
        'title' => 'Pengalaman Kerja',
        'cols'  => [['jabatan', 'Jabatan', 'wide', 150], ['institusi', 'Institusi/Perusahaan', '', 150], ['tahun', 'Tahun', 'yr', 50]],
        'rows'  => $t['pengalaman_kerja'] ?? [],
    ],
    'pengalaman_mengajar' => [
        'title' => 'Pengalaman Mengajar',
        'cols'  => [['topik', 'Topik Pembelajaran', 'wide', 255], ['tempat', 'Tempat', '', 150], ['tahun', 'Tahun', 'yr', 50]],
        'rows'  => $t['pengalaman_mengajar'] ?? [],
    ],
    'lampiran' => [
        'title' => 'Lampiran Dokumen',
        'cols'  => [['dokumen', 'Dokumen / Sertifikat Lampiran', 'wide', 255], ['tahun', 'Tahun', 'yr', 20], ['file', 'File', 'file', 0]],
        'rows'  => $t['lampiran'] ?? [],
    ],
];
$keahlian = implode("\n", Master_trainer_model::split_keahlian($t['bidang_keahlian'] ?? ''));
?>
<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit Trainer ' . htmlspecialchars($t['kode']) : 'Tambah Master Trainer'; ?> <span class="ref">FR-44 – FR-47</span></h1>
    <p>Data trainer/fasilitator beserta riwayatnya (format CV trainer). Tab riwayat tidak wajib diisi; hanya Nama lengkap yang wajib.</p>
  </div>
  <div class="page-actions">
    <a href="<?= site_url('master_trainer'); ?>" class="btn btn-muted">&larr; Kembali ke Daftar</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="notice notice-err"><span><?= htmlspecialchars($this->session->flashdata('error')); ?></span></div>
<?php endif; ?>

<form action="<?= site_url('master_trainer/save'); ?>" method="POST" enctype="multipart/form-data" id="trainer-form" novalidate>
  <input type="hidden" name="id" value="<?= $id; ?>">

  <div class="card trf">
    <div class="tabs trf-tabs" role="tablist">
      <button type="button" class="tab active" role="tab" data-tab="identitas">Identitas Trainer</button>
      <?php foreach ($lists as $key => $l): ?>
        <button type="button" class="tab" role="tab" data-tab="<?= $key; ?>"><?= $l['title']; ?> <span class="nav-count" data-count="<?= $key; ?>"><?= count($l['rows']); ?></span></button>
      <?php endforeach; ?>
    </div>

    <div class="trf-body">
      <!-- IDENTITAS -->
      <section data-panel="identitas">
        <div class="grid2">
          <div class="field">
            <label>ID Trainer</label>
            <input type="text" class="input" value="<?= $isEdit ? htmlspecialchars($t['kode']) : ''; ?>" placeholder="Otomatis saat disimpan" readonly>
            <span class="hint">Diisi otomatis oleh sistem saat disimpan</span>
          </div>
          <div class="field">
            <label for="trf-nama">Nama lengkap <span class="req">*</span></label>
            <input type="text" class="input" id="trf-nama" name="nama" value="<?= htmlspecialchars($t['nama'] ?? ''); ?>" maxlength="150" placeholder="Nama dan gelar">
          </div>
          <div class="field">
            <label>Tempat lahir</label>
            <input type="text" class="input" name="tempat_lahir" maxlength="100" value="<?= htmlspecialchars($t['tempat_lahir'] ?? ''); ?>" placeholder="mis. Jakarta">
          </div>
          <div class="field">
            <label>Tanggal lahir</label>
            <input type="date" class="input" name="tgl_lahir" value="<?= htmlspecialchars($t['tgl_lahir'] ?? ''); ?>">
          </div>
          <div class="field span2">
            <label>Alamat rumah</label>
            <textarea class="input" name="alamat" rows="2"><?= htmlspecialchars($t['alamat'] ?? ''); ?></textarea>
          </div>
          <div class="field span2">
            <label>No. telepon rumah/HP</label>
            <input type="text" class="input" name="no_telp" maxlength="50" value="<?= htmlspecialchars($t['no_telp'] ?? ''); ?>" placeholder="08xxxxxxxxxx / 08xxxxxxxxxx">
          </div>
        </div>

        <p class="qtn-h">Pendidikan formal</p>
        <div class="grid2">
          <div class="field"><label>SMU/sederajat</label><input type="text" class="input" name="pend_smu" maxlength="255" value="<?= htmlspecialchars($t['pend_smu'] ?? ''); ?>" placeholder="Jurusan, institusi, tahun lulus"></div>
          <div class="field"><label>Diploma</label><input type="text" class="input" name="pend_diploma" maxlength="255" value="<?= htmlspecialchars($t['pend_diploma'] ?? ''); ?>" placeholder="Jurusan, institusi, tahun lulus"></div>
          <div class="field"><label>Sarjana</label><input type="text" class="input" name="pend_sarjana" maxlength="255" value="<?= htmlspecialchars($t['pend_sarjana'] ?? ''); ?>" placeholder="Jurusan, institusi, tahun lulus"></div>
          <div class="field"><label>Pascasarjana</label><input type="text" class="input" name="pend_pascasarjana" maxlength="255" value="<?= htmlspecialchars($t['pend_pascasarjana'] ?? ''); ?>" placeholder="Jurusan, institusi, tahun lulus"></div>
        </div>

        <p class="qtn-h">Keahlian &amp; pengesahan CV</p>
        <div class="grid2">
          <div class="field">
            <label>Bidang keahlian</label>
            <textarea class="input" name="bidang_keahlian" rows="4" placeholder="Satu bidang per baris"><?= htmlspecialchars($keahlian); ?></textarea>
            <span class="hint">Satu bidang per baris</span>
          </div>
          <div class="field">
            <label>Pengalaman lainnya</label>
            <textarea class="input" name="pengalaman_lain" rows="4" placeholder="mis. Asesor BNSP"><?= htmlspecialchars($t['pengalaman_lain'] ?? ''); ?></textarea>
          </div>
          <div class="field">
            <label>Tempat pengesahan CV</label>
            <input type="text" class="input" name="cv_tempat" maxlength="100" value="<?= htmlspecialchars($t['cv_tempat'] ?? ''); ?>" placeholder="mis. Bekasi">
          </div>
          <div class="field">
            <label>Tanggal pengesahan CV</label>
            <input type="date" class="input" name="cv_tanggal" value="<?= htmlspecialchars($t['cv_tanggal'] ?? ''); ?>">
          </div>
          <div class="field span2">
            <label>Nama pada tanda tangan</label>
            <input type="text" class="input" name="nama_ttd" maxlength="150" value="<?= htmlspecialchars($t['nama_ttd'] ?? ''); ?>" placeholder="Nama tanpa gelar">
          </div>
        </div>
      </section>

      <!-- DAFTAR RIWAYAT (baris dibuat oleh skrip dari data di bawah) -->
      <?php foreach ($lists as $key => $l): ?>
        <section data-panel="<?= $key; ?>" hidden>
          <div class="table-wrap">
            <table class="table tr-table">
              <thead>
                <tr>
                  <th class="num">No.</th>
                  <?php foreach ($l['cols'] as $c): ?><th class="<?= $c[2]; ?>"><?= $c[1]; ?></th><?php endforeach; ?>
                  <th class="act"></th>
                </tr>
              </thead>
              <tbody id="rows-<?= $key; ?>">
                <tr class="tr-empty"><td colspan="<?= count($l['cols']) + 2; ?>" class="empty">Belum ada data. Klik + Tambah Baris.</td></tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline sm" data-add="<?= $key; ?>">+ Tambah Baris</button>
          <span class="hint trf-hint">Baris tanpa kolom pertama tidak akan disimpan.</span>
          <?php if ($key === 'lampiran'): ?>
            <p class="hint">File lampiran PDF/JPG/PNG, maksimal 5 MB, opsional. Disimpan di <code>uploads/trainers/</code>.</p>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
    </div>

    <div class="trf-foot">
      <a href="<?= site_url('master_trainer'); ?>" class="btn btn-muted">Batal</a>
      <button type="submit" class="btn btn-accent" id="btn-save-trainer">Simpan Data Trainer</button>
    </div>
  </div>
</form>

<script>
(function () {
  var LISTS = <?= json_encode(array_map(function ($l) { return ['cols' => $l['cols']]; }, $lists)); ?>;
  var DATA = <?= json_encode(array_map(function ($l) { return array_values($l['rows']); }, $lists)); ?>;
  var DL = '<?= base_url('uploads/trainers/'); ?>';
  var next = {}; // indeks berikutnya per daftar; tidak memakai jumlah baris agar tidak bentrok setelah hapus
  var form = document.getElementById('trainer-form');
  var esc = function (v) { return $('<div>').text(v == null ? '' : v).html().replace(/"/g, '&quot;'); };

  function showTab(key) {
    form.querySelectorAll('.trf-tabs .tab').forEach(function (b) { b.classList.toggle('active', b.dataset.tab === key); });
    form.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== key; });
  }

  function refresh(key) {
    var tbody = document.getElementById('rows-' + key);
    var rows = tbody.querySelectorAll('tr:not(.tr-empty)');
    rows.forEach(function (tr, i) { tr.querySelector('.num').textContent = i + 1; });
    tbody.querySelector('.tr-empty').hidden = rows.length > 0;
    form.querySelector('[data-count="' + key + '"]').textContent = rows.length;
  }

  function cell(key, i, c, row) {
    if (c[2] === 'file') {
      var cur = row.file_path || '';
      return '<td class="file"><input type="hidden" name="' + key + '[' + i + '][file_path]" value="' + esc(cur) + '">' +
        '<span class="file-name">' + (cur ? '<a href="' + DL + encodeURIComponent(cur) + '" target="_blank" rel="noopener" class="code">' + esc(cur) + '</a> ' : '') + '</span>' +
        '<label class="btn btn-ghost sm file-btn"><span class="file-label">' + (cur ? 'Ganti' : 'Unggah') + '</span>' +
        '<input type="file" name="lampiran_file[' + i + ']" accept=".pdf,.jpg,.jpeg,.png" hidden></label></td>';
    }
    return '<td class="' + c[2] + '"><input type="text" class="input sm" name="' + key + '[' + i + '][' + c[0] + ']" maxlength="' + c[3] + '" value="' + esc(row[c[0]]) + '" aria-label="' + esc(c[1]) + '"></td>';
  }

  function addRow(key, row, quiet) {
    row = row || {};
    var i = next[key]++;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td class="num"></td>' + LISTS[key].cols.map(function (c) { return cell(key, i, c, row); }).join('') +
      '<td class="act"><button type="button" class="btn btn-ghost sm" data-del aria-label="Hapus baris">Hapus</button></td>';
    document.getElementById('rows-' + key).insertBefore(tr, document.querySelector('#rows-' + key + ' .tr-empty'));
    refresh(key);
    if (!quiet) tr.querySelector('input.input').focus();
  }

  Object.keys(LISTS).forEach(function (key) {
    next[key] = 0;
    DATA[key].forEach(function (row) { addRow(key, row, true); });
  });

  form.addEventListener('click', function (e) {
    var tab = e.target.closest('.trf-tabs .tab');
    if (tab) return showTab(tab.dataset.tab);
    var add = e.target.closest('[data-add]');
    if (add) return addRow(add.dataset.add);
    var del = e.target.closest('[data-del]');
    if (del) {
      var tr = del.closest('tr'), key = tr.parentNode.id.replace('rows-', '');
      tr.remove();
      refresh(key);
    }
  });

  // Nama berkas yang dipilih tampil segera (bukti unggahan akan diproses saat simpan).
  form.addEventListener('change', function (e) {
    var f = e.target;
    if (f.type !== 'file' || !f.files[0]) return;
    var td = f.closest('td');
    td.querySelector('.file-name').textContent = f.files[0].name + ' ';
    td.querySelector('.file-label').textContent = 'Ganti';
  });

  form.addEventListener('submit', function (e) {
    var bad = null;
    // Baris yang terisi tetapi kolom pertamanya kosong tidak akan disimpan: tandai dan peringatkan.
    Object.keys(LISTS).forEach(function (key) {
      document.querySelectorAll('#rows-' + key + ' tr:not(.tr-empty)').forEach(function (tr) {
        var ins = tr.querySelectorAll('input.input');
        var file = tr.querySelector('input[type=file]'), kept = tr.querySelector('input[type=hidden]');
        var filled = [].some.call(ins, function (x) { return x.value.trim(); }) || (file && file.files.length) || (kept && kept.value);
        var miss = filled && !ins[0].value.trim();
        lmMark(ins[0], miss);
        if (miss && !bad) bad = { key: key, el: ins[0] };
      });
    });

    showTab('identitas');
    if (!lmRequired([[form.elements.nama, 'Nama lengkap']])) return e.preventDefault();

    if (bad) {
      e.preventDefault();
      showTab(bad.key);
      var tabName = form.querySelector('[data-tab="' + bad.key + '"]').firstChild.textContent.trim();
      lmWarn('Kolom pertama wajib diisi pada baris yang ditandai di tab ' + tabName + '.', { title: 'Data belum lengkap' })
        .then(function () { bad.el.focus(); });
      return;
    }
    // Form klasik: tombol tetap sibuk sampai halaman daftar terbuka.
    var b = document.getElementById('btn-save-trainer');
    b.classList.add('is-busy'); b.textContent = 'Menyimpan…';
  });
})();
</script>
