<?php
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$rp = function ($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
?>
<div class="pf-formbar">
  <a href="<?= site_url('penjualan_program'); ?>" class="btn btn-ghost sm">← Kembali</a>
  <h2>Buat Penjualan Program</h2>
  <span class="ref">PJ-FR-02 · PJ-FR-03</span>
</div>

<div class="csec">
  <p class="csec-h">1. Pilih Master Program <span class="muted">hanya yang berstatus Final</span></p>
  <div class="field">
    <label for="qt-mp">Master Program <span class="req">*</span></label>
    <select class="input" id="qt-mp">
      <option value="">— Pilih Master Program Final —</option>
      <?php foreach ($finals as $m): ?>
        <option value="<?= (int)$m['id']; ?>" <?= (int)$preselect === (int)$m['id'] ? 'selected' : ''; ?>
                data-durasi="<?= (int)$m['durasi_hari']; ?>" data-peserta="<?= (int)$m['target_peserta']; ?>"
                data-harga="<?= $e($rp($m['harga_jual_peserta'])); ?>" data-hpp="<?= $e($rp($m['hpp'])); ?>">
          <?= $e($m['nomor']); ?> — <?= $e($m['nama_paket']); ?> · <?= $e($m['nama_sertifikasi'] ?? ''); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span class="hint" id="mp-hint"></span>
  </div>
</div>

<div class="csec">
  <p class="csec-h">2. Pilih jenis penjualan <span class="muted">form &amp; simulasi sama; jenis hanya menentukan jalur setelah Final dan tidak dapat diubah setelah disimpan</span></p>
  <div class="choice-grid">
    <button type="button" class="choice" data-jenis="public" disabled>
      <span class="tag tag-public">Public</span><h3>Penjualan Public</h3>
      <p>Acara terbuka, tidak terikat company.</p>
      <ul><li>Tanggal, lokasi, target peserta, harga per peserta</li><li>Simulasi pendapatan, HPP, profit, titik impas</li><li>Final → <b>Quotation</b> ke Company atau Personal</li></ul>
    </button>
    <button type="button" class="choice" data-jenis="b2b" disabled>
      <span class="tag tag-b2b">B2B</span><h3>Penjualan B2B</h3>
      <p>Acara untuk satu company (in-house).</p>
      <ul><li>Company wajib; target peserta <b>minimal 10</b></li><li>Simulasi pendapatan, HPP, profit, titik impas</li><li>Final → <b>satu Quotation</b> ke Company penjualan — harga per peserta atau <b>all-in</b>, tanpa Formulir Konfirmasi</li></ul>
    </button>
  </div>
  <p class="help" id="choice-guide">Pilih Master Program dahulu.</p>
</div>

<script>
(function () {
  var sel = document.getElementById('qt-mp'), hint = document.getElementById('mp-hint'), guide = document.getElementById('choice-guide');
  var cards = document.querySelectorAll('.choice');

  function update() {
    var opt = sel.options[sel.selectedIndex], on = !!sel.value;
    hint.textContent = on ? 'Master: ' + opt.dataset.durasi + ' hari · target ' + opt.dataset.peserta + ' peserta · harga standar ' + opt.dataset.harga + ' · HPP ' + opt.dataset.hpp : '';
    cards.forEach(function (c) { c.disabled = !on; });
    guide.style.display = on ? 'none' : '';
  }
  sel.addEventListener('change', update);
  cards.forEach(function (c) {
    c.addEventListener('click', function () {
      if (!sel.value) return;
      c.classList.add('is-loading'); // pindah halaman: kartu diredupkan sampai form terbuka
      location.href = '<?= site_url('penjualan_program/create/'); ?>' + sel.value + '/' + c.dataset.jenis;
    });
  });
  update(); // nilai awal dari ?mp=
})();
</script>
