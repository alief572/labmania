<script src="<?= base_url('assets/labmania/js/lm-costing.js'); ?>"></script>

<div id="pj-root"></div>

<script>
(function () {
  'use strict';
  var INIT = <?= json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var URL_BASE = '<?= site_url('penjualan_program/'); ?>';
  var C = LmCosting, esc = C.esc, rp = C.rp, GROUPS = C.GROUPS;
  LmCosting.configure({ base: URL_BASE, kind: 'pj' });

  var RATE = {}; INIT.rates.forEach(function (r) { RATE[r.id] = r; });
  var COMPANY = {}; INIT.companies.forEach(function (c) { COMPANY[c.id] = c; });
  var TRAINER = {}; INIT.trainers.forEach(function (t) { TRAINER[t.id] = t; });
  var MP = INIT.mpInfo, B2B_MIN = INIT.b2bMin;
  var S = { p: JSON.parse(JSON.stringify(INIT.program)), errors: {}, collapsed: {}, rinciOpen: false };
  var root = document.getElementById('pj-root');

  var isNew = function () { return !S.p.id; };
  var isB2b = function () { return S.p.jenis === 'b2b'; };
  var locked = function () { return S.p.status !== 'draft'; };
  var digits = function (s) { return String(s == null ? '' : s).replace(/\D/g, ''); };
  var fmtRibu = function (v) { return window.fmtRibu(v === '' || v == null ? '' : v); };
  var $q = function (sel) { return root.querySelector(sel); };
  var jenisTag = function (j) { return '<span class="tag tag-' + j + '">' + (j === 'b2b' ? 'B2B' : 'Public') + '</span>'; };

  var field = function (label, control, err, cls) {
    return '<div class="field ' + (err ? 'has-err' : '') + ' ' + (cls || '') + '"><label>' + label + '</label>' + control + (err ? '<div class="err"><i>!</i>' + esc(err) + '</div>' : '') + '</div>';
  };
  var REQ = ' <span class="req">*</span>';
  var MONEY = ['hargaJual', 'targetProfit'];

  // Durasi = tanggal selesai − mulai + 1 (PJ-FR-06); sebelum tanggal lengkap/valid, sementara = durasi Master Program.
  function hitungDurasi() {
    var a = S.p.tglMulai, b = S.p.tglSelesai;
    if (a && b) {
      var d = (new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000;
      if (!isNaN(d) && d >= 0) return Math.round(d) + 1;
    }
    return MP.durasi;
  }

  // ---------- Bagian-bagian form ----------
  function numInput(name, val, extra) {
    var dis = locked() || name === 'durasi' ? 'disabled' : ''; // durasi tidak diketik, mengikuti tanggal
    return MONEY.indexOf(name) >= 0
      ? '<input class="input money-in" type="text" inputmode="numeric" autocomplete="off" data-pf="' + name + '" ' + dis + ' value="' + fmtRibu(val) + '">'
      : '<input class="input" data-pf="' + name + '" type="number" min="0" value="' + esc(val) + '" ' + dis + ' ' + (extra || '') + '>';
  }

  // Input simulasi tampil di bagian 1 dan 4, terikat ke satu nilai (PJ-FR-09).
  function simInputs(note) {
    var E = S.errors, p = S.p;
    return '<div class="sim-in"><p class="sim-in-h">Input simulasi <span>' + note + '</span></p><div class="grid4">' +
      field('Durasi (hari)' + REQ, numInput('durasi', p.durasi) + '<span class="hint">otomatis dari tanggal · master: ' + MP.durasi + ' hari</span>', E.durasi) +
      field('Target peserta' + REQ, numInput('targetPeserta', p.targetPeserta, 'step="1" min="' + (isB2b() ? B2B_MIN : 1) + '"') +
        '<span class="hint">master: ' + MP.target + (isB2b() ? ' · B2B minimal ' + B2B_MIN : '') + '</span>', E.targetPeserta) +
      field('Harga jual / peserta' + REQ, numInput('hargaJual', p.hargaJual) + '<span class="hint">master: ' + rp(MP.harga) + '</span>', E.hargaJual) +
      field('Target profit (opsional)', numInput('targetProfit', p.targetProfit), E.targetProfit) + '</div></div>';
  }

  function step(n, title, hint, body) {
    var col = S.collapsed[n];
    return '<div class="csec step ' + (col ? 'collapsed' : '') + '" data-step="' + n + '">' +
      '<p class="csec-h" role="button" tabindex="0" data-act="step" data-n="' + n + '" aria-expanded="' + !col + '">' +
      '<span class="step-chev" aria-hidden="true">▾</span>' + n + '. ' + title + (hint ? ' <span class="muted">' + hint + '</span>' : '') +
      '<span class="step-sum" data-sum="' + n + '">' + stepSum(n) + '</span></p>' +
      '<div class="step-body">' + body + '</div></div>';
  }

  // Ringkasan langkah 1 untuk penjualan: company · tanggal · peserta · harga.
  function stepSum(n) {
    if (n !== 1) return C.stepSummary(n, S.p, null);
    var p = S.p, co = isB2b() && COMPANY[p.company] ? esc(COMPANY[p.company].nama) + ' · ' : '';
    var rng = p.tglMulai ? p.tglMulai + (p.tglSelesai && p.tglSelesai !== p.tglMulai ? ' – ' + p.tglSelesai : '') : '—';
    return co + rng + ' · ' + (Number(p.targetPeserta) || 0) + ' peserta · ' + rp(p.hargaJual) + ' / peserta';
  }

  function defMeta(b) {
    var r = b.rate ? RATE[b.rate] : null;
    if (b.manual) return '<div class="meta" data-def>Biaya lain (di luar Master Rate)</div>';
    if (!r) return '';
    var changed = Number(b.harga) !== Math.round(Number(r.default_cost));
    return '<div class="meta ' + (changed ? 'diff' : '') + '" data-def>default ' + rp(r.default_cost) + (changed ? ', diubah' : '') + '</div>';
  }

  function costTable(gi) {
    var g = GROUPS[gi], p = S.p, E = S.errors, lock = locked();
    var rows = []; p.biaya.forEach(function (b, i) { if (b.tipe === g.tipe && b.satuan === g.satuan) rows.push({ b: b, i: i }); });
    if (lock && !rows.length) return '';

    var body = rows.length ? rows.map(function (x) {
      var b = x.b, i = x.i, r = b.rate ? RATE[b.rate] : null, err = E['b' + i];
      var name = b.manual
        ? '<input class="input sm-in" data-bi="' + i + '" data-bf="item" value="' + esc(b.item) + '" placeholder="Nama biaya lain" ' + (lock ? 'disabled' : '') + '>'
        : esc(b.item) + (r && !r.aktif ? ' <span class="muted">(nonaktif)</span>' : '');
      return '<tr class="' + (Number(b.harga) > 0 ? '' : 'zero') + ' ' + (err ? 'row-err' : '') + '" data-row="' + i + '">' +
        '<td>' + name + defMeta(b) + '</td>' +
        '<td class="num"><input class="input sm-in money-in" type="text" inputmode="numeric" autocomplete="off" data-bi="' + i + '" data-bf="harga" ' + (lock ? 'disabled' : '') +
          ' placeholder="' + (b.manual ? 'Isi nominal' : '') + '" aria-label="Nominal ' + esc(b.item) + '" value="' + fmtRibu(b.harga) + '"></td>' +
        '<td class="num qty" data-fx="' + i + '">' + C.lineQtyTxt(b, p) + '</td>' +
        '<td class="num" data-sub="' + i + '">' + rp(C.lineTotal(b, p)) + '</td>' +
        '<td class="act">' + (lock ? '' : '<button type="button" class="btn btn-ghost sm row-x" data-act="del" data-i="' + i + '" title="Tidak dipakai — hapus" aria-label="Hapus ' + esc(b.item || 'biaya') + '">×</button>') + '</td></tr>' +
        (err ? '<tr class="row-msg"><td colspan="5"><i>!</i>' + esc(err) + '</td></tr>' : '');
    }).join('') : '';

    var pick = '';
    if (!lock) {
      var used = p.biaya.map(function (b) { return b.rate; });
      var choices = INIT.rates.filter(function (r) { return r.aktif == 1 && r.tipe === g.tipe && r.nama_satuan === g.satuan && used.indexOf(Number(r.id)) < 0; });
      var select = '<select class="input" id="' + g.pick + '">' + (choices.length
        ? '<option value="">— Pilih item dari Master Rate —</option>' + choices.map(function (r) { return '<option value="' + r.id + '">' + esc(r.item) + ' · ' + rp(r.default_cost) + '</option>'; }).join('')
        : '<option value="" disabled selected>Semua item master sudah dipakai</option>') +
        '</select><button type="button" class="btn btn-outline sm" data-act="add" data-g="' + gi + '">+ Tambah</button>';
      pick = '<div class="pickrow">' + select + '<button type="button" class="btn btn-ghost sm" data-act="manual" data-g="' + gi + '">+ Biaya lain</button></div>';
    }
    if (!rows.length) return '<p class="csec-sub">' + g.title + ' <span class="muted">— belum ada item</span></p>' + pick;
    var total = rows.reduce(function (s, x) { return s + C.lineTotal(x.b, p); }, 0);
    return '<p class="csec-sub">' + g.title + '</p>' + pick +
      '<div class="table-wrap"><table class="table items"><thead><tr><th>Item</th><th class="num">' + g.nominal + '</th><th class="num">Qty</th><th class="num">Total</th><th class="act"></th></tr></thead>' +
      '<tbody>' + body + '</tbody><tfoot><tr><td colspan="3" class="num"><b>Total</b></td><td class="num"><b id="' + g.tot + '">' + rp(total) + '</b></td><td></td></tr></tfoot></table></div>';
  }

  var STATUS_LBL = { draft: 'Draft', final: 'Final', batal: 'Batal' };

  function view() {
    var p = S.p, E = S.errors, lock = locked(), warns = C.warnings(p);

    var lockInfo = '';
    if (p.status === 'final') lockInfo = 'Penjualan Program <b>Final</b> — terkunci. Buat <b>Quotation</b> dari menu Quotation (' + (isB2b() ? 'ke Company penjualan' : 'ke Company atau Personal') + ') — persiapan invoice dengan peserta yang sudah fix.';
    if (p.status === 'batal') lockInfo = 'Penjualan Program dibatalkan dan tidak dapat diubah.';

    var header = '<div class="csec"><p class="csec-h">Header</p><div class="grid3">' +
      field('No. penjualan', '<input class="input" value="' + esc(p.nomor || '') + '" placeholder="otomatis saat disimpan" disabled>') +
      field('Jenis penjualan', '<div class="static">' + jenisTag(p.jenis) + '</div>') +
      field('Status', '<div class="static"><span class="badge st-' + p.status + '">' + STATUS_LBL[p.status] + '</span>' + (p.status === 'final' ? ' <span class="muted">→ Quotation</span>' : '') + '</div>') + '</div></div>';

    // Company (B2B): hanya yang aktif + yang sedang terpilih (PJ-FR-22)
    var co = '';
    if (isB2b()) {
      var opts = INIT.companies.filter(function (c) { return c.aktif || c.id === p.company; }).map(function (c) {
        return '<option value="' + esc(c.id) + '" ' + (c.id === p.company ? 'selected' : '') + '>' + esc(c.id) + ' — ' + esc(c.nama) + (c.aktif ? '' : ' (nonaktif)') + '</option>';
      }).join('');
      var cc = COMPANY[p.company];
      co = field('Company' + REQ, '<select class="input" data-pf="company" ' + (lock ? 'disabled' : '') + '><option value="">— Pilih Company aktif —</option>' + opts + '</select>' +
        (cc ? '<span class="hint">' + esc(cc.jenis || '') + ' · ' + esc(cc.kota || '—') + ' · PIC ' + esc(cc.pic || '—') + '</span>' : '<span class="hint">dari Master Company</span>'), E.company);
    }

    // Trainer (opsional, satu per event): aktif + yang sedang terpilih (PJ-FR-23)
    var tr = TRAINER[p.trainer];
    var topts = INIT.trainers.filter(function (t) { return t.aktif || t.id === p.trainer; }).map(function (t) {
      return '<option value="' + t.id + '" ' + (t.id === p.trainer ? 'selected' : '') + '>' + esc(t.kode) + ' — ' + esc(t.nama) + (t.aktif ? '' : ' (nonaktif)') + '</option>';
    }).join('');
    var trainerField = field('Trainer', '<select class="input" data-pf="trainer" ' + (lock ? 'disabled' : '') + '><option value="">— Pilih Trainer (opsional) —</option>' + topts + '</select>' +
      '<span class="hint">' + (tr ? esc(tr.keahlian.join(' · ') || 'Master Trainer') + (tr.aktif ? '' : ' · nonaktif') : 'Dari Master Trainer aktif; satu trainer per event') + '</span>', E.trainer);

    var s1 = step(1, 'Informasi Penjualan', '',
      '<div class="grid3">' +
        field('Master Program', '<div class="static"><span class="code">' + esc(MP.nomor) + '</span> ' + esc(MP.judul) + ' <span class="muted">· ' + esc(MP.sertifikat) + '</span></div>', E.id_master_program) +
        co +
        field('Tanggal mulai' + REQ, '<input class="input" type="date" data-pf="tglMulai" value="' + esc(p.tglMulai) + '" ' + (lock ? 'disabled' : '') + '>', E.tglMulai) +
        field('Tanggal selesai' + REQ, '<input class="input" type="date" data-pf="tglSelesai" value="' + esc(p.tglSelesai) + '" ' + (lock ? 'disabled' : '') + '><span class="hint">durasi = selesai − mulai + 1 hari</span>', E.tglSelesai) +
        field('Waktu', '<input class="input" data-pf="waktu" value="' + esc(p.waktu) + '" maxlength="50" placeholder="mis. 09.00–15.15 WIB" ' + (lock ? 'disabled' : '') + '><span class="hint">Jam pelaksanaan; tercetak di Penawaran &amp; Formulir Konfirmasi</span>', E.waktu) +
        field('Lokasi acara', '<input class="input" data-pf="lokasi" value="' + esc(p.lokasi) + '" maxlength="150" placeholder="mis. Offline Bekasi" ' + (lock ? 'disabled' : '') + '>', E.lokasi) +
        trainerField +
      '</div>' + simInputs('terisi dari ' + esc(MP.nomor) + '; perubahan hanya berlaku di penjualan ini. Juga tampil di bagian 4.'));

    var s2 = step(2, 'Fixed Cost', 'disalin dari Master Program; nominal, item, dan biaya lain dapat diubah; item bernilai 0 tidak disimpan. Qty: event = 1, day = durasi.',
      costTable(0) + costTable(1) + '<div id="pf-tot-fixed">' + C.totalsFixed(p) + '</div>');
    var s3 = step(3, 'Variable Cost', 'pilih item yang dipakai saja, isi nominal per peserta; item bernilai 0 tidak disimpan. Qty otomatis dari jumlah peserta dan durasi.',
      costTable(2) + costTable(3) + '<div id="pf-tot-var">' + C.totalsVar(p) + '</div>');
    var s4 = step(4, 'Simulasi Hasil', 'apakah penjualan ini untung, dan berapa titik impasnya',
      simInputs('sama dengan bagian 1 — ubah di sini atau di bagian 1, keduanya ikut berubah') + '<div id="pf-sim" class="gap-t">' + C.simHtml(p, { rinciOpen: S.rinciOpen }) + '</div>');

    var actions = '<button type="button" class="btn btn-ghost" data-act="back">' + (isNew() ? 'Batal' : 'Kembali') + '</button>';
    if (p.status === 'draft') {
      actions += (isNew() ? '' : '<button type="button" class="btn btn-outline" data-act="cancel">Batalkan</button>') +
        '<button type="button" class="btn btn-outline" id="btn-save" data-act="save">Simpan Draft</button>' +
        (isNew() ? '' : '<button type="button" class="btn btn-accent" id="btn-final" data-act="final">Finalkan → Quotation</button>');
    } else if (p.status === 'final') {
      actions += '<button type="button" class="btn btn-accent" data-act="print">Cetak Surat Penawaran</button>';
    }

    var title = isNew() ? 'Buat Penjualan Program ' + (isB2b() ? 'B2B' : 'Public') : 'Penjualan Program ' + esc(p.nomor);
    return '<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="back">← Kembali</button><h2>' + title + '</h2><span class="ref">PJ-FR-04 – PJ-FR-10</span></div>' +
      (lockInfo ? '<div class="alert">' + lockInfo + '</div>' : '') +
      '<div id="pf-warn" class="alert ' + (warns.length ? '' : 'hidden') + '">' + warns.map(esc).join('<br>') + '</div>' +
      '<div class="pub-form">' + header + s1 + s2 + s3 + s4 + '</div><div class="form-actions">' + actions + '</div>';
  }

  function render() {
    var y = window.scrollY;
    root.innerHTML = view();
    if (window.lmSelect2) window.lmSelect2(root); // kolom cari pada dropdown (Company, Trainer, item Master Rate)
    window.scrollTo(0, y);
  }

  // Hitung ulang tanpa render penuh, agar fokus input tidak hilang saat mengetik.
  function refreshCalc() {
    var p = S.p;
    p.biaya.forEach(function (b, i) {
      var s = $q('[data-sub="' + i + '"]'); if (s) s.textContent = rp(C.lineTotal(b, p));
      var fx = $q('[data-fx="' + i + '"]'); if (fx) fx.textContent = C.lineQtyTxt(b, p);
      var tr = $q('tr[data-row="' + i + '"]');
      if (tr) {
        tr.classList.toggle('zero', !(Number(b.harga) > 0));
        var dm = tr.querySelector('[data-def]'); if (dm) dm.outerHTML = defMeta(b);
      }
    });
    GROUPS.forEach(function (g) {
      var el = $q('#' + g.tot);
      if (el) el.textContent = rp(C.groupItems(p, g).reduce(function (s, b) { return s + C.lineTotal(b, p); }, 0));
    });
    $q('#pf-tot-fixed').innerHTML = C.totalsFixed(p);
    $q('#pf-tot-var').innerHTML = C.totalsVar(p);
    $q('#pf-sim').innerHTML = C.simHtml(p, { rinciOpen: S.rinciOpen });
    [1, 2, 3, 4].forEach(function (n) { var el = $q('[data-sum="' + n + '"]'); if (el) el.innerHTML = stepSum(n); });
    var w = C.warnings(p), box = $q('#pf-warn');
    box.innerHTML = w.map(esc).join('<br>');
    box.classList.toggle('hidden', !w.length);
  }

  // ---------- Validasi klien (server tetap sumber kebenaran) & simpan ----------
  function validate() {
    var p = S.p, E = {};
    var posInt = function (v) { return v !== '' && Number.isInteger(Number(v)) && Number(v) > 0; };
    if (isB2b() && !p.company) E.company = 'Pilih Company';
    if (!p.tglMulai) E.tglMulai = 'Tanggal mulai wajib diisi';
    if (!p.tglSelesai) E.tglSelesai = 'Tanggal selesai wajib diisi';
    else if (p.tglMulai && p.tglSelesai < p.tglMulai) E.tglSelesai = 'Tanggal selesai sebelum tanggal mulai';
    if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
    else if (isB2b() && Number(p.targetPeserta) < B2B_MIN) E.targetPeserta = 'Penjualan B2B minimal ' + B2B_MIN + ' peserta';
    if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual / peserta wajib diisi';
    p.biaya.forEach(function (b, i) {
      if (b.manual && !String(b.item).trim()) E['b' + i] = 'Nama biaya lain wajib diisi';
      else if (b.harga === '' || b.harga < 0) E['b' + i] = 'Nominal ' + b.item + ' wajib diisi';
    });
    S.errors = E;
    return !Object.keys(E).length;
  }

  var SERVER_KEY = { id_customer: 'company', id_trainer: 'trainer', tgl_mulai: 'tglMulai', tgl_selesai: 'tglSelesai', target_peserta: 'targetPeserta',
                     harga_jual_peserta: 'hargaJual', target_profit: 'targetProfit' };

  function busy(on) {
    ['btn-save', 'btn-final'].forEach(function (id) {
      var b = document.getElementById(id); if (b) { b.disabled = on; b.classList.toggle('is-busy', on && (id === 'btn-save' || S.finaling)); }
    });
  }

  // Simpan Draft. Mengembalikan promise (true bila tersimpan) agar "Finalkan" dapat menyimpan dulu.
  function save(silent) {
    var p = S.p, awal = p.biaya.length;
    p.biaya = p.biaya.filter(function (b) { return !(b.harga !== '' && Number(b.harga) === 0); }); // item bernilai 0 = tidak dipakai
    var dibuang = awal - p.biaya.length;
    if (!validate()) { render(); window.toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return Promise.resolve(false); }

    busy(true);
    return new Promise(function (resolve) {
      jQuery.ajax({
        url: URL_BASE + 'save', type: 'POST', dataType: 'json',
        data: {
          id: p.id || '', id_master_program: p.mp, jenis: p.jenis, id_customer: p.company || '', id_trainer: p.trainer || '',
          tgl_mulai: p.tglMulai, tgl_selesai: p.tglSelesai, waktu: p.waktu, lokasi: p.lokasi, durasi_hari: p.durasi,
          target_peserta: p.targetPeserta, harga_jual_peserta: p.hargaJual, target_profit: p.targetProfit,
          biaya: p.biaya.map(function (b) { return { id_rate: b.rate || '', tipe: b.tipe, item: b.item, id_satuan: b.id_satuan, harga: b.harga, is_manual: b.manual ? 1 : 0 }; })
        }
      }).done(function (res) {
        if (res.status) {
          S.p.id = res.id; S.p.nomor = res.nomor; S.p.durasi = res.durasi; S.errors = {};
          if (!silent) window.toast(res.msg, 'ok');
          history.replaceState(null, '', URL_BASE + 'edit/' + res.id); // setelah tersimpan, tombol Finalkan muncul
          render(); resolve(true);
        } else {
          S.errors = {};
          Object.keys(res.errors || {}).forEach(function (k) { S.errors[SERVER_KEY[k] || k] = res.errors[k]; });
          render(); window.toast(res.msg, 'warn'); resolve(false);
        }
      }).fail(function () { busy(false); window.toast('Terjadi kesalahan jaringan', 'warn'); resolve(false); });
    });
  }

  var toView = function () { location.href = URL_BASE + 'view/' + S.p.id; };

  // ---------- Interaksi ----------
  var ACTIONS = {
    back: function () { location.href = URL_BASE; },
    step: function (el) {
      var n = el.dataset.n; S.collapsed[n] = !S.collapsed[n];
      $q('.step[data-step="' + n + '"]').classList.toggle('collapsed', !!S.collapsed[n]); el.setAttribute('aria-expanded', !S.collapsed[n]);
    },
    del: function (el) { S.p.biaya.splice(+el.dataset.i, 1); S.errors = {}; render(); },
    add: function (el) {
      var g = GROUPS[+el.dataset.g], sel = $q('#' + g.pick), r = sel && sel.value ? RATE[sel.value] : null;
      if (!r) return;
      S.p.biaya.push({ rate: Number(r.id), tipe: r.tipe, item: r.item, satuan: g.satuan, id_satuan: INIT.satuan[g.tipe][g.satuan], harga: Math.round(Number(r.default_cost)), manual: false });
      render();
    },
    manual: function (el) {
      var g = GROUPS[+el.dataset.g];
      S.p.biaya.push({ rate: null, tipe: g.tipe, item: '', satuan: g.satuan, id_satuan: INIT.satuan[g.tipe][g.satuan], harga: '', manual: true });
      render();
      var rows = root.querySelectorAll('input[data-bf="item"]'); if (rows.length) rows[rows.length - 1].focus();
    },
    save: function () { save(false); },
    final: function () {
      S.finaling = true;
      save(true).then(function (ok) {
        S.finaling = false;
        if (ok) LmCosting.confirmFinal(S.p.id, toView); else busy(false);
      });
    },
    cancel: function () { LmCosting.confirmCancel(S.p.id, toView); },
    print: function () { LmCosting.penawaran(S.p.id); }
  };

  root.addEventListener('click', function (e) {
    var el = e.target.closest('[data-act]');
    if (el && ACTIONS[el.dataset.act]) { e.preventDefault(); ACTIONS[el.dataset.act](el); }
  });
  root.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset && e.target.dataset.act === 'step') { e.preventDefault(); ACTIONS.step(e.target); }
  });
  // Ringkasan perhitungan: ingat buka/tutup (event toggle tidak menggelembung, jadi ditangkap di fase capture).
  root.addEventListener('toggle', function (e) { if (e.target.id === 'sim-rinci') S.rinciOpen = e.target.open; }, true);

  var SYNC = MONEY.concat(['durasi', 'targetPeserta']);
  function syncInputs(name, src) {
    root.querySelectorAll('[data-pf="' + name + '"]').forEach(function (o) { if (o !== src) o.value = MONEY.indexOf(name) >= 0 ? fmtRibu(S.p[name]) : S.p[name]; });
  }

  root.addEventListener('input', function (e) {
    var t = e.target;
    if (t.dataset.pf) {
      var name = t.dataset.pf, v = t.value;
      if (MONEY.indexOf(name) >= 0) v = digits(v) === '' ? '' : Number(digits(v));
      else if (name === 'targetPeserta') v = v === '' ? '' : Number(v);
      if (['paket', 'company', 'trainer'].indexOf(name) >= 0) return; // select: ditangani di 'change'
      S.p[name] = v;
      if (name === 'tglMulai' || name === 'tglSelesai') { S.p.durasi = hitungDurasi(); syncInputs('durasi', null); }
      if (SYNC.indexOf(name) >= 0) syncInputs(name, t);
      refreshCalc();
    } else if (t.dataset.bi !== undefined) {
      var b = S.p.biaya[+t.dataset.bi];
      if (t.dataset.bf === 'harga') b.harga = digits(t.value) === '' ? '' : Number(digits(t.value));
      else b.item = t.value;
      refreshCalc();
    }
  });
  root.addEventListener('change', function (e) {
    var n = e.target.dataset.pf;
    if (n === 'company') { S.p.company = e.target.value; render(); }
    else if (n === 'trainer') { S.p.trainer = e.target.value ? Number(e.target.value) : ''; render(); }
    else if (n === 'tglMulai' || n === 'tglSelesai') { S.p[n] = e.target.value; S.p.durasi = hitungDurasi(); render(); }
  });

  // labmania.js (fmtRibu, toast, openModal, Select2) dimuat di kaki halaman, setelah isi view ini: render setelah halaman selesai dimuat.
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', render); else render();
})();
</script>
