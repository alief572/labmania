<?php
// Halaman Konfirmasi Kehadiran publik (tanpa login). Public: satu orang per formulir. B2B: PIC company mengirim daftar peserta sekali.
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$tgl = function ($d) { return $d ? date('d-m-Y', strtotime($d)) : '—'; };
$rentang = $q['tgl_mulai'] ? $tgl($q['tgl_mulai']) . ($q['tgl_selesai'] && $q['tgl_selesai'] !== $q['tgl_mulai'] ? ' – ' . $tgl($q['tgl_selesai']) : '') : '—';
$kepada = $q['tujuan'] === 'company' ? ($q['nama_company'] ?: '—') : ($q['kepada_nama'] ?: '—');
$base = site_url('konfirmasi_kehadiran');
$tokenUrl = $e($token);
// pernyataan 1 dengan penanda terisi data program (QTN-FR-19)
$pern = strtr((string)$q['konfirmasi_pernyataan'], [
    '{judul}' => $q['nama_paket'], '{tanggal}' => $rentang, '{waktu}' => $q['waktu'] ?: '—', '{durasi}' => (int)$q['durasi_hari'] . ' hari', '{lokasi}' => $q['lokasi'] ?: '—',
]);
$pernHtml = preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $e($pern));
$labels = array_map(function ($l) { return rtrim($l, '*'); }, $kf_fields);
$tipeIn = ['email' => 'email', 'hp' => 'tel', 'wa' => 'tel', 'habis' => 'date'];
$colB2b = ['jabatan' => 'Jabatan', 'email' => 'Email', 'wa' => 'Nomor WhatsApp', 'nik' => 'NIK'];
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Konfirmasi Kehadiran — <?= $e($q['nama_paket']); ?></title>
<style>
  :root { --navy:#14304F; --gold:#D5A320; --ink:#1b2333; --mut:#5b6478; --line:#d3dce8; --bg:#f3f5f9; --err:#c62828; --ok:#1b7a3a; }
  * { box-sizing: border-box; }
  body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color:var(--ink); background:var(--bg); line-height:1.5; }
  .wrap { max-width: 720px; margin: 0 auto; padding: 16px; }
  header.top { background:var(--navy); color:#fff; padding:16px; border-bottom:4px solid var(--gold); }
  header.top .in { max-width:720px; margin:0 auto; display:flex; align-items:center; gap:10px; }
  header.top b { font-size:20px; letter-spacing:-.5px; } header.top b i { color:var(--gold); font-style:normal; }
  header.top small { letter-spacing:3px; font-size:10px; opacity:.85; }
  .card { background:#fff; border:1px solid var(--line); border-radius:10px; padding:16px; margin:14px 0; }
  h1 { font-size:20px; margin:0 0 4px; color:var(--navy); line-height:1.3; }
  h2 { font-size:16px; margin:0 0 10px; color:var(--navy); }
  .meta { display:grid; grid-template-columns: 1fr; gap:6px 16px; margin-top:10px; font-size:14px; }
  .meta span { color:var(--mut); } .meta b { font-weight:600; }
  @media (min-width:560px){ .meta { grid-template-columns: 1fr 1fr; } }
  .pill { display:inline-block; padding:3px 10px; border-radius:99px; font-size:12px; font-weight:600; background:#e6f4ea; color:var(--ok); }
  .pill.off { background:#fde8e8; color:var(--err); }
  .field { margin:0 0 14px; } label { display:block; font-size:14px; font-weight:600; margin:0 0 4px; }
  .req { color:var(--err); } .hint { font-size:12.5px; color:var(--mut); margin-top:3px; }
  input[type=text], input[type=email], input[type=tel], input[type=date], input[type=number], textarea { width:100%; font:inherit; font-size:16px; padding:10px 12px; border:1px solid #9aa6bb; border-radius:8px; background:#fff; color:var(--ink); }
  input:focus, textarea:focus, button:focus-visible, a:focus-visible { outline:3px solid #ffbf47; outline-offset:2px; }
  .bad input, .bad textarea { border:2px solid var(--err); }
  .err { color:var(--err); font-size:13px; margin-top:4px; }
  .btn { display:inline-block; font:inherit; font-weight:600; padding:12px 18px; border-radius:8px; border:2px solid var(--navy); background:#fff; color:var(--navy); cursor:pointer; text-decoration:none; min-height:44px; }
  .btn.pri { background:var(--navy); color:#fff; } .btn[disabled] { opacity:.6; cursor:wait; }
  .btn.sm { padding:8px 12px; min-height:40px; font-size:14px; }
  .steps { margin:0; padding-left:20px; font-size:14px; } .steps li { margin:4px 0; }
  .alert { padding:12px 14px; border-radius:8px; background:#fff7e0; border:1px solid #f0d58a; font-size:14px; margin:10px 0; }
  .alert.err { background:#fde8e8; border-color:#f0b4b4; color:#7a1c1c; }
  .alert.ok { background:#e6f4ea; border-color:#a8d5b4; color:#14532d; }
  .chk { display:flex; gap:10px; align-items:flex-start; font-weight:400; } .chk input { width:22px; height:22px; margin-top:2px; flex:none; }
  .actions { display:flex; gap:10px; flex-wrap:wrap; }
  table.lst { width:100%; border-collapse:collapse; font-size:14px; } table.lst td { padding:4px 4px 4px 0; vertical-align:top; }
  .row { display:grid; grid-template-columns: 28px 1fr; gap:6px; align-items:start; margin-bottom:8px; }
  .row .n { padding-top:11px; color:var(--mut); font-size:13px; text-align:right; }
  .row .cols { display:grid; grid-template-columns:1fr; gap:6px; } .row .x { grid-column: 2; justify-self:start; }
  @media (min-width:560px){ .row .cols { grid-template-columns: repeat(<?= 1 + max(count($kolom_b2b), 1); ?>, 1fr); } }
  .sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
  [hidden] { display:none !important; }
</style>
</head>
<body>
<header class="top"><div class="in"><b>L<i>A</i>B</b><small>M A N I A</small></div></header>
<main class="wrap">

<section class="card" aria-labelledby="jdl">
  <h1 id="jdl"><?= $e($q['nama_paket']); ?></h1>
  <div class="meta">
    <div><span>Jadwal</span><br><b><?= $e($rentang); ?></b></div>
    <div><span>Waktu</span><br><b><?= $e($q['waktu'] ?: '—'); ?></b></div>
    <div><span>Lokasi</span><br><b><?= $e($q['lokasi'] ?: '—'); ?></b></div>
    <div><span>Nomor Quotation</span><br><b><?= $e($q['nomor']); ?></b></div>
    <div><span>Kepada</span><br><b><?= $e($kepada); ?></b></div>
    <?php if (!$b2b): ?><div><span>Sisa kursi</span><br><b><?= (int)$stt['sisa']; ?> dari <?= (int)$q['kuota_peserta']; ?></b></div><?php endif; ?>
  </div>
</section>

<?php if (!$stt['buka']): ?>
  <section class="card" role="alert">
    <h2>Formulir ini sudah ditutup</h2>
    <p><?= $e($stt['alasan']); ?>.</p>
    <p class="hint">Silakan hubungi sales kami<?= !empty($sales['nama']) ? ': <b>' . $e($sales['nama']) . '</b>' : ''; ?><?= !empty($sales['telp']) ? ' · ' . $e($sales['telp']) : ''; ?><?= !empty($sales['email']) ? ' · ' . $e($sales['email']) : ''; ?>.</p>
  </section>
<?php else: ?>

<div id="done" class="card" hidden role="status" tabindex="-1">
  <h2>Terima kasih</h2>
  <p id="done-msg">Konfirmasi Anda sudah kami terima.</p>
  <?php if (!$b2b): ?><p><button type="button" class="btn" id="again">Isi untuk peserta lain</button></p><?php endif; ?>
</div>

<?php if (!$b2b): /* ================= PUBLIK: satu orang ================= */ ?>
<form id="frm" class="card" novalidate autocomplete="on">
  <h2>1. Data diri</h2>
  <?php foreach ($diminta as $k): $lab = $labels[$k] ?? $k; $wajib = $k === 'nama'; $std = isset($kf_fields[$k]);
    $name = $std ? $k : 'ex[' . $k . ']'; $type = $tipeIn[$k] ?? 'text'; ?>
    <div class="field" data-f="<?= $e($std ? $k : 'ex_' . $k); ?>">
      <label for="f-<?= $e($k); ?>"><?= $e($lab); ?><?= $wajib ? ' <span class="req" aria-hidden="true">*</span><span class="sr"> wajib</span>' : ''; ?></label>
      <?php if ($k === 'alamat'): ?><textarea id="f-<?= $e($k); ?>" name="<?= $e($name); ?>" rows="2" maxlength="300"></textarea>
      <?php else: ?><input id="f-<?= $e($k); ?>" name="<?= $e($name); ?>" type="<?= $e($type); ?>" <?= in_array($k, ['hp', 'wa'], true) ? 'inputmode="tel"' : ''; ?> maxlength="150" <?= $wajib ? 'required' : ''; ?>
        <?= $k === 'instansi' && $q['tujuan'] === 'company' ? 'value="' . $e($q['nama_company']) . '" readonly' : ''; ?>><?php endif; ?>
      <?php if ($k === 'nama'): ?><div class="hint">Nama lengkap menjadi acuan penulisan di sertifikat.</div><?php endif; ?>
      <div class="err" hidden></div>
    </div>
  <?php endforeach; ?>

  <h2>2. Pernyataan</h2>
  <p><?= $pernHtml; ?></p>
  <p>Saya <b><u>telah membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania.</p>
  <div class="field" data-f="setuju"><label class="chk"><input type="checkbox" name="setuju" value="1" id="f-setuju"> Saya menyetujui pernyataan di atas <span class="req" aria-hidden="true">*</span></label><div class="err" hidden></div></div>

  <h2>3. Surat konfirmasi bertanda tangan</h2>
  <ol class="steps">
    <li>Tekan <b>Cetak surat konfirmasi</b> (data diri di atas ikut tercetak).</li>
    <li>Isi tempat, lalu tanda tangani dengan pena.</li>
    <li>Foto atau pindai surat, lalu unggah di bawah.</li>
  </ol>
  <p><button type="button" class="btn sm" id="cetak">Cetak surat konfirmasi</button></p>
  <div class="field" data-f="surat">
    <label for="f-surat">Surat konfirmasi yang sudah ditandatangani <span class="req" aria-hidden="true">*</span></label>
    <input id="f-surat" name="surat" type="file" accept="application/pdf,image/jpeg,image/png" required style="font-size:16px;width:100%">
    <div class="hint">PDF, JPG, atau PNG · maks. 5 MB. Dari ponsel Anda dapat langsung memotret.</div>
    <div class="err" hidden></div>
  </div>
  <div class="alert err" id="frm-err" hidden role="alert"></div>
  <div class="actions"><button type="submit" class="btn pri" id="kirim">Kirim konfirmasi</button></div>
</form>

<?php else: /* ================= B2B: PIC company ================= */ ?>
<form id="frm" class="card" novalidate>
  <h2>1. Data perusahaan dan PIC</h2>
  <?php $pre = [
    'nama_perusahaan' => ['Nama Perusahaan', $q['nama_company'], 'text'], 'alamat_perusahaan' => ['Alamat Perusahaan', trim(($q['alamat_company'] ?? '') . ($q['kota_company'] ? ', ' . $q['kota_company'] : '')), 'text'],
    'pic_nama' => ['Nama PIC', $q['pic_name'], 'text'], 'pic_jabatan' => ['Jabatan PIC', $q['pic_jabatan'], 'text'],
    'pic_hp' => ['Nomor HP / WhatsApp PIC', $q['pic_hp'], 'tel'], 'pic_email' => ['Email PIC', $q['pic_email'], 'email']]; ?>
  <?php foreach ($pre as $k => $d): ?>
    <div class="field" data-f="<?= $k; ?>"><label for="f-<?= $k; ?>"><?= $e($d[0]); ?> <span class="req" aria-hidden="true">*</span></label>
      <input id="f-<?= $k; ?>" name="<?= $k; ?>" type="<?= $d[2]; ?>" value="<?= $e($d[1]); ?>" maxlength="150" required <?= $d[2] === 'tel' ? 'inputmode="tel"' : ''; ?>><div class="err" hidden></div></div>
  <?php endforeach; ?>

  <h2>2. Daftar peserta</h2>
  <p class="hint" style="margin-top:-6px">Minimal <?= (int)Quotation_model::B2B_MIN; ?> peserta. Harga paket mencakup s.d. <?= (int)($q['paket_maks_peserta'] ?: Quotation_model::B2B_PAKET_MAKS); ?> peserta; peserta di atas itu dikenakan tambahan Rp<?= number_format((float)($q['tambahan_per_peserta'] ?: Quotation_model::B2B_TAMBAHAN), 0, ',', '.'); ?>/orang. Nama lengkap menjadi acuan penulisan di sertifikat.</p>
  <div id="rows"></div>
  <div class="actions"><button type="button" class="btn sm" id="add">+ Tambah peserta</button><button type="button" class="btn sm" id="paste-toggle">Tempel daftar nama</button></div>
  <div id="paste-box" hidden class="field" style="margin-top:10px"><label for="paste">Tempel dari Excel atau WhatsApp (satu nama per baris)</label><textarea id="paste" rows="5"></textarea><button type="button" class="btn sm" id="paste-ok" style="margin-top:6px">Masukkan ke daftar</button></div>
  <p id="count" aria-live="polite" style="margin:10px 0 0"></p>
  <div class="err" id="e-peserta" hidden></div>

  <h2>3. Pernyataan</h2>
  <p>Atas nama perusahaan, kami mengikutsertakan peserta pada daftar di atas dan <b><u>telah membaca</u></b> serta <b><u>menyetujui</u></b> syarat &amp; ketentuan LabMania, termasuk ketentuan biaya peserta di atas <?= (int)($q['paket_maks_peserta'] ?: Quotation_model::B2B_PAKET_MAKS); ?> orang.</p>
  <div class="field" data-f="setuju"><label class="chk"><input type="checkbox" name="setuju" value="1" id="f-setuju"> Kami menyetujui pernyataan di atas <span class="req" aria-hidden="true">*</span></label><div class="err" hidden></div></div>

  <h2>4. Surat konfirmasi perusahaan</h2>
  <ol class="steps"><li>Tekan <b>Cetak surat</b> (data dan daftar peserta ikut tercetak).</li><li>Tanda tangani dan beri <b>cap perusahaan</b>.</li><li>Foto atau pindai, lalu unggah di bawah.</li></ol>
  <p><button type="button" class="btn sm" id="cetak">Cetak surat konfirmasi</button></p>
  <div class="field" data-f="surat"><label for="f-surat">Surat yang sudah ditandatangani dan dicap <span class="req" aria-hidden="true">*</span></label>
    <input id="f-surat" name="surat" type="file" accept="application/pdf,image/jpeg,image/png" required style="font-size:16px;width:100%">
    <div class="hint">PDF, JPG, atau PNG · maks. 5 MB (surat dan lampiran daftar peserta digabung).</div><div class="err" hidden></div></div>
  <div class="alert err" id="frm-err" hidden role="alert"></div>
  <div class="actions"><button type="submit" class="btn pri" id="kirim">Kirim daftar peserta</button></div>
  <p class="hint">Daftar hanya dapat dikirim satu kali. Untuk perubahan setelah terkirim, hubungi sales kami.</p>
</form>
<?php endif; ?>

<script>
(function () {
  'use strict';
  var B2B = <?= $b2b ? 'true' : 'false'; ?>, BASE = <?= json_encode($base . '/'); ?>, TOKEN = <?= json_encode($token); ?>, MIN = <?= (int)Quotation_model::B2B_MIN; ?>,
      KOLOM = <?= json_encode($colB2b ? array_intersect_key($colB2b, array_flip($kolom_b2b)) : new stdClass(), JSON_UNESCAPED_UNICODE); ?>,
      MAKS = <?= (int)($q['paket_maks_peserta'] ?: Quotation_model::B2B_PAKET_MAKS); ?>, TARIF = <?= (float)($q['tambahan_per_peserta'] ?: Quotation_model::B2B_TAMBAHAN); ?>,
      MAXB = 5 * 1024 * 1024, MIME = ['application/pdf', 'image/jpeg', 'image/png'];
  var $ = function (s, c) { return (c || document).querySelector(s); }, $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var frm = $('#frm'); if (!frm) return;

  function setErr(name, msg) {
    var f = $('[data-f="' + name + '"]', frm); if (!f) return false;
    f.classList.toggle('bad', !!msg); var e = $('.err', f); if (e) { e.textContent = msg || ''; e.hidden = !msg; }
    return !!msg;
  }
  function clear() { $$('[data-f]', frm).forEach(function (f) { f.classList.remove('bad'); var e = $('.err', f); if (e) e.hidden = true; }); var g = $('#frm-err'); g.hidden = true; var p = $('#e-peserta'); if (p) p.hidden = true; }
  function digits(v) { return String(v || '').replace(/[\s\-+]/g, ''); }
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function cekBerkas() {
    var f = $('#f-surat').files[0];
    if (!f) return 'Unggah surat konfirmasi yang sudah ditandatangani';
    if (f.size > MAXB) return 'Berkas lebih dari 5 MB';
    if (MIME.indexOf(f.type) < 0 && !/\.(pdf|jpe?g|png)$/i.test(f.name)) return 'Hanya berkas PDF, JPG, atau PNG';
    return '';
  }

  // ---------- Public ----------
  function cekPublik() {
    var bad = 0; clear();
    var nama = $('#f-nama'); if (!nama.value.trim()) bad += setErr('nama', 'Nama lengkap wajib diisi');
    var em = $('#f-email'); if (em && em.value.trim() && !EMAIL.test(em.value.trim())) bad += setErr('email', 'Format email tidak valid');
    ['hp', 'wa'].forEach(function (k) { var i = $('#f-' + k); if (i && i.value.trim() && !/^\d{9,15}$/.test(digits(i.value))) bad += setErr(k, 'Nomor hanya angka, 9–15 digit'); });
    return bad;
  }

  // ---------- B2B: baris peserta ----------
  var rows = $('#rows');
  function row(v) {
    v = v || {}; var i = rows.children.length;
    var d = document.createElement('div'); d.className = 'row';
    var cols = '<div><label class="sr">Nama peserta ' + (i + 1) + '</label><input type="text" data-k="nama" placeholder="Nama lengkap" maxlength="150" value=""></div>';
    Object.keys(KOLOM).forEach(function (k) { cols += '<div><label class="sr">' + KOLOM[k] + '</label><input type="' + (k === 'email' ? 'email' : (k === 'wa' ? 'tel' : 'text')) + '" data-k="' + k + '" placeholder="' + KOLOM[k] + '" maxlength="100"></div>'; });
    d.innerHTML = '<div class="n">' + (i + 1) + '.</div><div class="cols">' + cols + '</div><button type="button" class="btn sm x" aria-label="Hapus peserta ' + (i + 1) + '">Hapus</button>';
    $$('input', d).forEach(function (inp) { inp.value = v[inp.dataset.k] || ''; inp.addEventListener('input', hitung); });
    $('.x', d).addEventListener('click', function () { d.remove(); renumber(); hitung(); });
    rows.appendChild(d);
  }
  function renumber() { $$('.row', rows).forEach(function (d, i) { $('.n', d).textContent = (i + 1) + '.'; }); }
  function terisi() { return $$('.row', rows).filter(function (d) { return $('[data-k="nama"]', d).value.trim() !== ''; }).length; }
  function hitung() {
    var n = terisi(), t = Math.max(n - MAKS, 0);
    var c = $('#count'); if (!c) return;
    c.textContent = n + ' peserta. Minimal ' + MIN + '; harga paket mencakup s.d. ' + MAKS + ' peserta.' + (t ? ' ' + t + ' peserta di atas ' + MAKS + ' dikenakan tambahan Rp' + (t * TARIF).toLocaleString('id-ID') + '.' : '');
  }
  if (B2B) {
    for (var i = 0; i < <?= max((int)$q['kuota_peserta'], (int)Quotation_model::B2B_MIN); ?>; i++) row();
    hitung();
    $('#add').addEventListener('click', function () { row(); renumber(); hitung(); var l = $$('input', rows); l[l.length - $$('input', rows.lastChild).length].focus(); });
    $('#paste-toggle').addEventListener('click', function () { $('#paste-box').hidden = !$('#paste-box').hidden; });
    $('#paste-ok').addEventListener('click', function () {
      var baris = $('#paste').value.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
      if (!baris.length) return;
      var kosong = $$('.row', rows).filter(function (d) { return !$('[data-k="nama"]', d).value.trim(); });
      baris.forEach(function (ln) {
        var p = ln.split('\t'); var d = kosong.shift();
        if (!d) { row(); d = rows.lastChild; }
        $('[data-k="nama"]', d).value = p[0].trim();
        Object.keys(KOLOM).forEach(function (k, j) { var el = $('[data-k="' + k + '"]', d); if (el && p[j + 1]) el.value = p[j + 1].trim(); });
      });
      renumber(); hitung(); $('#paste').value = ''; $('#paste-box').hidden = true;
    });
  }

  function dataB2b() {
    var f = new FormData(frm), out = [];
    $$('.row', rows).forEach(function (d) { var o = {}; $$('input', d).forEach(function (i) { o[i.dataset.k] = i.value; }); out.push(o); });
    return { fd: f, rows: out };
  }
  function cekB2b() {
    var bad = 0; clear();
    ['nama_perusahaan', 'alamat_perusahaan', 'pic_nama', 'pic_jabatan', 'pic_hp', 'pic_email'].forEach(function (k) { var v = $('#f-' + k).value.trim(); if (!v) bad += setErr(k, 'Wajib diisi'); });
    var em = $('#f-pic_email').value.trim(); if (em && !EMAIL.test(em)) bad += setErr('pic_email', 'Format email tidak valid');
    var hp = $('#f-pic_hp').value; if (hp.trim() && !/^\d{9,15}$/.test(digits(hp))) bad += setErr('pic_hp', 'Nomor hanya angka, 9–15 digit');
    var n = terisi(); if (n < MIN) { var p = $('#e-peserta'); p.textContent = 'Daftar peserta minimal ' + MIN + ' nama (terisi ' + n + ')'; p.hidden = false; bad++; }
    return bad;
  }

  // ---------- cetak surat (PDF) lewat POST ke tab baru ----------
  $('#cetak').addEventListener('click', function () {
    if (B2B ? cekB2b() : cekPublik()) { $('#frm-err').textContent = 'Lengkapi isian yang ditandai sebelum mencetak.'; $('#frm-err').hidden = false; return; }
    var f = document.createElement('form'); f.method = 'post'; f.action = BASE + 'cetak/' + TOKEN; f.target = '_blank'; f.hidden = true;
    var add = function (n, v) { var i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
    if (B2B) { var d = dataB2b(); d.fd.forEach(function (v, k) { if (typeof v === 'string') add(k, v); }); d.rows.forEach(function (r, i) { Object.keys(r).forEach(function (k) { add('peserta[' + i + '][' + k + ']', r[k]); }); }); }
    else { $$('input[name], textarea[name]', frm).forEach(function (el) { if (el.type !== 'file' && el.type !== 'checkbox') add(el.name, el.value); }); }
    document.body.appendChild(f); f.submit(); f.remove();
  });

  // ---------- kirim ----------
  frm.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var bad = B2B ? cekB2b() : cekPublik();
    if (!$('#f-setuju').checked) bad += setErr('setuju', 'Centang pernyataan terlebih dahulu');
    var eb = cekBerkas(); if (eb) bad += setErr('surat', eb);
    if (bad) { var first = $('.bad, #e-peserta:not([hidden])', frm); if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }

    var btn = $('#kirim'); btn.disabled = true; btn.textContent = 'Mengirim…';
    var fd = new FormData(frm);
    if (B2B) dataB2b().rows.forEach(function (r, i) { Object.keys(r).forEach(function (k) { fd.append('peserta[' + i + '][' + k + ']', r[k]); }); });
    var x = new XMLHttpRequest();
    x.open('POST', BASE + (B2B ? 'kirim_company/' : 'kirim/') + TOKEN);
    x.onload = function () {
      var res = {}; try { res = JSON.parse(x.responseText); } catch (e) { res = { msg: 'Terjadi kesalahan, coba lagi' }; }
      btn.disabled = false; btn.textContent = B2B ? 'Kirim daftar peserta' : 'Kirim konfirmasi';
      if (x.status >= 200 && x.status < 300 && res.status) {
        frm.hidden = true; var d = $('#done'); d.hidden = false;
        $('#done-msg').textContent = B2B ? 'Daftar ' + res.jumlah + ' peserta dan surat konfirmasi perusahaan sudah kami terima. Sales kami akan menindaklanjuti.' : 'Konfirmasi Anda sudah kami terima.';
        d.focus(); d.scrollIntoView(); return;
      }
      if (x.status === 410) { location.reload(); return; }
      var er = res.errors || {};
      Object.keys(er).forEach(function (k) { if (k === 'peserta' || k === 'peserta_rows') { var p = $('#e-peserta'); p.textContent = typeof er[k] === 'string' ? er[k] : 'Periksa baris peserta'; p.hidden = false; } else setErr(k.replace(/^ex\./, 'ex_'), er[k]); });
      var g = $('#frm-err'); g.textContent = res.msg || 'Gagal mengirim'; g.hidden = false; g.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    x.onerror = function () { btn.disabled = false; btn.textContent = B2B ? 'Kirim daftar peserta' : 'Kirim konfirmasi'; var g = $('#frm-err'); g.textContent = 'Terjadi kesalahan jaringan, coba lagi'; g.hidden = false; };
    x.send(fd);
  });

  var again = $('#again');
  if (again) again.addEventListener('click', function () { frm.reset(); frm.hidden = false; $('#done').hidden = true; clear(); location.reload(); });
})();
</script>
<?php endif; ?>
</main>
</body>
</html>
