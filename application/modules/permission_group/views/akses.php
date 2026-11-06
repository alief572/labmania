<?php
$isAdmin = ((int)$group['id_group'] === 1);
$canUbah = lm_can('permission_group', 'ubah') && !$isAdmin;
?>
<div class="page-head">
  <div>
    <h1>Atur Izin — <?= htmlspecialchars($group['nm_group']); ?></h1>
    <p>Centang aksi yang diizinkan per menu. Tambah, Ubah, Ubah Status, Hapus, dan Cetak otomatis memberi izin Lihat.</p>
  </div>
  <div class="page-actions">
    <a href="<?= site_url('permission_group'); ?>" class="btn btn-muted">&larr; Kembali ke Daftar</a>
  </div>
</div>

<?php if ($isAdmin): ?>
  <div class="notice"><span>Group <b>admin</b> selalu memiliki semua izin dan tidak dapat diubah.</span></div>
<?php elseif (!$canUbah): ?>
  <div class="notice"><span>Anda hanya dapat melihat izin group ini (tidak punya izin <b>Ubah</b> pada Permission Group).</span></div>
<?php endif; ?>

<form id="akses-form" class="card akses-card">
  <input type="hidden" name="id" value="<?= (int)$group['id_group']; ?>">

  <div class="akses-bar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="akses-q" placeholder="Cari menu…" aria-label="Cari menu" autocomplete="off">
    </div>
    <span class="akses-count"><b id="akses-n">0</b> izin dipilih</span>
  </div>

  <div class="akses-scroll">
    <table class="akses-table">
      <thead>
        <tr>
          <th class="akses-menu">Menu</th>
          <?php foreach ($aksi as $k => $label): ?>
            <th class="akses-chk">
              <span class="akses-h"><?= htmlspecialchars($label); ?></span>
              <?= $canUbah ? '<input type="checkbox" data-col="' . $k . '" aria-label="Semua ' . htmlspecialchars($label) . '">' : ''; ?>
            </th>
          <?php endforeach; ?>
          <th class="akses-chk akses-all">
            <span class="akses-h">Semua</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matrix as $mod): ?>
          <tr class="akses-mod" data-mod><td colspan="<?= count($aksi) + 2; ?>"><?= htmlspecialchars($mod['judul']); ?></td></tr>
          <?php foreach ($mod['items'] as $m): ?>
            <tr class="akses-row <?= $m['aktif'] ? '' : 'is-off'; ?>" data-menu="<?= $m['id']; ?>">
              <td class="akses-menu"><?= htmlspecialchars($m['judul']); ?><?= $m['aktif'] ? '' : ' <span class="muted">(nonaktif)</span>'; ?></td>
              <?php foreach ($aksi as $k => $label):
                $ada = in_array($k, $m['berlaku'], true);
                $on  = $isAdmin ? $ada : !empty($m['diberi'][$k]); ?>
                <td class="akses-chk">
                  <?php if ($ada): ?>
                    <input type="checkbox" name="akses[<?= $m['id']; ?>][]" value="<?= $k; ?>" data-aksi="<?= $k; ?>" <?= $on ? 'checked' : ''; ?> <?= $canUbah ? '' : 'disabled'; ?> aria-label="<?= htmlspecialchars($m['judul'] . ' — ' . $label); ?>">
                  <?php else: ?><span class="akses-na" aria-hidden="true">—</span><?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td class="akses-chk akses-all"><?= $canUbah ? '<input type="checkbox" data-row aria-label="Semua aksi ' . htmlspecialchars($m['judul']) . '">' : ''; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <tr class="akses-none" hidden><td colspan="<?= count($aksi) + 2; ?>">Tidak ada menu yang cocok.</td></tr>
      </tbody>
    </table>
  </div>

  <?php if ($canUbah): ?>
  <div class="akses-foot">
    <a href="<?= site_url('permission_group'); ?>" class="btn btn-muted">Batal</a>
    <button type="submit" class="btn btn-accent" id="btn-save-akses">Simpan Izin</button>
  </div>
  <?php endif; ?>
</form>

<script>
(function () {
  var form = document.getElementById('akses-form');
  var rows = [].slice.call(form.querySelectorAll('tbody tr[data-menu]'));
  var editable = !!form.querySelector('input[data-row]');

  function boxes(tr) { return [].slice.call(tr.querySelectorAll('input[data-aksi]')); }

  // Sinkron kotak "Semua" baris/kolom dan penghitung izin
  function sync() {
    var n = 0;
    rows.forEach(function (tr) {
      var b = boxes(tr), all = tr.querySelector('input[data-row]');
      n += b.filter(function (x) { return x.checked; }).length;
      if (all) all.checked = b.length > 0 && b.every(function (x) { return x.checked; });
    });
    form.querySelectorAll('input[data-col]').forEach(function (c) {
      var col = rows.map(function (tr) { return tr.querySelector('input[data-aksi="' + c.dataset.col + '"]'); }).filter(Boolean);
      c.checked = col.length > 0 && col.every(function (x) { return x.checked; });
    });
    document.getElementById('akses-n').textContent = n;
  }

  // Filter menu: baris modul disembunyikan bila tidak ada menu yang cocok di bawahnya
  function filter() {
    var q = document.getElementById('akses-q').value.toLowerCase().trim(), any = false, mod = null, modVisible = false;
    [].slice.call(form.querySelectorAll('tbody tr')).forEach(function (tr) {
      if (tr.hasAttribute('data-mod')) {
        if (mod) mod.hidden = !modVisible;
        mod = tr; modVisible = false; return;
      }
      if (!tr.hasAttribute('data-menu')) return;
      var hit = !q || tr.querySelector('.akses-menu').textContent.toLowerCase().indexOf(q) !== -1;
      tr.hidden = !hit;
      if (hit) { modVisible = true; any = true; }
    });
    if (mod) mod.hidden = !modVisible;
    form.querySelector('.akses-none').hidden = any;
  }
  document.getElementById('akses-q').addEventListener('input', filter);
  document.getElementById('akses-q').addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

  if (editable) {
    form.addEventListener('change', function (e) {
      var t = e.target, tr = t.closest('tr');
      if (t.dataset.aksi) {
        var lihat = tr.querySelector('input[data-aksi="lihat"]');
        if (t.dataset.aksi === 'lihat' && !t.checked) boxes(tr).forEach(function (x) { x.checked = false; });
        else if (t.checked && lihat) lihat.checked = true;
      } else if (t.hasAttribute('data-row')) {
        boxes(tr).forEach(function (x) { x.checked = t.checked; });
      } else if (t.dataset.col) {
        // Kolom: hanya baris yang sedang tampil (hasil pencarian) yang ikut berubah
        rows.forEach(function (r) {
          if (r.hidden) return;
          var x = r.querySelector('input[data-aksi="' + t.dataset.col + '"]');
          if (!x) return;
          x.checked = t.checked;
          var l = r.querySelector('input[data-aksi="lihat"]');
          if (t.checked && t.dataset.col !== 'lihat' && l) l.checked = true;
          if (!t.checked && t.dataset.col === 'lihat') boxes(r).forEach(function (y) { y.checked = false; });
        });
      }
      sync();
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = document.getElementById('btn-save-akses'); btn.disabled = true;
      $.ajax({
        url: '<?= site_url("permission_group/save_akses"); ?>', type: 'POST', dataType: 'json', data: $(form).serialize(),
        success: function (res) { toast(res.msg, res.status ? 'ok' : 'warn'); btn.disabled = false; },
        error: function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.msg) || 'Terjadi kesalahan pada server', 'warn'); btn.disabled = false; }
      });
    });
  }

  sync();
})();
</script>
