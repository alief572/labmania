<script src="<?= base_url('assets/labmania/js/lm-costing.js'); ?>"></script>

<div id="mp-root"></div>

<script>
(function () {
  'use strict';
  var INIT = <?= json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var URL_BASE = '<?= site_url('master_program/'); ?>';
  var C = LmCosting, esc = C.esc, rp = C.rp, GROUPS = C.GROUPS;
  LmCosting.configure({ base: URL_BASE });

  var RATE = {}; INIT.rates.forEach(function (r) { RATE[r.id] = r; });
  var PAKET = {}; INIT.pakets.forEach(function (p) { PAKET[p.id] = p; });
  var S = { p: JSON.parse(JSON.stringify(INIT.program)), errors: {}, collapsed: {}, rinciOpen: false };
  var root = document.getElementById('mp-root');

  var isNew = function () { return !S.p.id; };
  var locked = function () { return S.p.status !== 'draft'; };
  var paketOf = function () { return S.p.paket ? PAKET[S.p.paket] : null; };
  var digits = function (s) { return String(s == null ? '' : s).replace(/\D/g, ''); };
  var fmtRibu = function (v) { return window.fmtRibu(v === '' || v == null ? '' : v); };
  var $q = function (sel) { return root.querySelector(sel); };

  var field = function (label, control, err, cls) {
    return '<div class="field ' + (err ? 'has-err' : '') + ' ' + (cls || '') + '"><label>' + label + '</label>' + control + (err ? '<div class="err"><i>!</i>' + esc(err) + '</div>' : '') + '</div>';
  };
  var REQ = ' <span class="req">*</span>';
  var MONEY = ['hargaJual', 'targetProfit'];

  // ---------- Bagian-bagian form ----------
  function numInput(name, val, extra) {
    var dis = locked() ? 'disabled' : '';
    return MONEY.indexOf(name) >= 0
      ? '<input class="input money-in" type="text" inputmode="numeric" autocomplete="off" data-pf="' + name + '" ' + dis + ' value="' + fmtRibu(val) + '">'
      : '<input class="input" data-pf="' + name + '" type="number" min="0" value="' + esc(val) + '" ' + dis + ' ' + (extra || '') + '>';
  }

  // Input simulasi tampil di bagian 1 dan 4, terikat ke satu nilai (MP-FR-10).
  function simInputs(note) {
    var E = S.errors, p = S.p;
    return '<div class="sim-in"><p class="sim-in-h">Input simulasi <span>' + note + '</span></p><div class="grid4">' +
      field('Durasi (hari)' + REQ, numInput('durasi', p.durasi, 'step="1" min="1"'), E.durasi) +
      field('Target peserta' + REQ, numInput('targetPeserta', p.targetPeserta, 'step="1" min="1"') + '<span class="hint">default 10, dapat diubah</span>', E.targetPeserta) +
      field('Harga jual standar / peserta' + REQ, numInput('hargaJual', p.hargaJual), E.hargaJual) +
      field('Target profit (opsional)', numInput('targetProfit', p.targetProfit), E.targetProfit) + '</div></div>';
  }

  function step(n, title, hint, body) {
    var col = S.collapsed[n];
    return '<div class="csec step ' + (col ? 'collapsed' : '') + '" data-step="' + n + '">' +
      '<p class="csec-h" role="button" tabindex="0" data-act="step" data-n="' + n + '" aria-expanded="' + !col + '">' +
      '<span class="step-chev" aria-hidden="true">▾</span>' + n + '. ' + title + (hint ? ' <span class="muted">' + hint + '</span>' : '') +
      '<span class="step-sum" data-sum="' + n + '">' + C.stepSummary(n, S.p, { paket: paketOf() }) + '</span></p>' +
      '<div class="step-body">' + body + '</div></div>';
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
    // Kelompok tanpa item: cukup judul + pilihan item, tanpa tabel kosong.
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
    if (p.status === 'final') lockInfo = 'Master Program <b>Final</b> — terkunci dan tersedia di pilihan Penjualan Program. Penyesuaian harga/costing dilakukan di Penjualan Program. Master Program Final tidak dapat dibatalkan atau disalin; perubahan master: buat Master Program baru.';
    if (p.status === 'batal') lockInfo = 'Master Program dibatalkan — tidak muncul lagi di pilihan Penjualan Program. Penjualan yang sudah terbentuk tidak terpengaruh.';

    var header = '<div class="csec"><p class="csec-h">Header</p><div class="grid3">' +
      field('No. master program', '<input class="input" value="' + esc(p.nomor || '') + '" placeholder="otomatis saat disimpan" disabled>') +
      field('Jenis', '<div class="static"><span class="muted">Umum — Public/B2B dipilih di Penjualan Program</span></div>') +
      field('Status', '<div class="static"><span class="badge st-' + p.status + '">' + STATUS_LBL[p.status] + '</span></div>') + '</div></div>';

    var paketOpts = INIT.pakets.map(function (x) {
      return '<option value="' + x.id + '" ' + (x.id === p.paket ? 'selected' : '') + '>' + esc(x.nomor) + ' — ' + esc(x.judul) + ' · ' + esc(x.sertifikat || '') + (x.aktif ? '' : ' (nonaktif)') + '</option>';
    }).join('');
    var pk = paketOf();
    var s1 = step(1, 'Informasi Program', '',
      field('Paket Produk (dari Product Combine)' + REQ, '<select class="input" data-pf="paket" ' + (lock ? 'disabled' : '') + '><option value="">— Pilih Paket Produk aktif —</option>' + paketOpts + '</select>' +
        (pk ? '<span class="hint">Produk: ' + esc(pk.produk) + ' · Bidang: ' + esc(pk.bidang) + ' · Sertifikat: ' + esc(pk.sertifikat) + '</span>' : ''), E.paket) +
      simInputs('angka baseline; bisa disesuaikan per Penjualan Program tanpa mengubah master. Juga tampil di bagian 4.'));

    var s2 = step(2, 'Fixed Cost', 'semua item aktif Master Rate langsung tampil dengan Default Cost; klik × pada item yang tidak dipakai (dapat ditambah lagi lewat + Tambah); item bernilai 0 tidak disimpan. Qty: event = 1, day = durasi.',
      costTable(0) + costTable(1) + '<div id="pf-tot-fixed">' + C.totalsFixed(p) + '</div>');
    var s3 = step(3, 'Variable Cost', 'pilih item yang dipakai saja, isi nominal per peserta; item bernilai 0 tidak disimpan. Qty otomatis dari jumlah peserta dan durasi.',
      costTable(2) + costTable(3) + '<div id="pf-tot-var">' + C.totalsVar(p) + '</div>');
    var s4 = step(4, 'Simulasi Hasil', 'apakah program ini untung, dan berapa titik impasnya',
      simInputs('sama dengan bagian 1 — ubah di sini atau di bagian 1, keduanya ikut berubah') + '<div id="pf-sim" class="gap-t">' + C.simHtml(p, { rinciOpen: S.rinciOpen }) + '</div>');

    var actions = '<button type="button" class="btn btn-ghost" data-act="back">' + (isNew() ? 'Batal' : 'Kembali') + '</button>';
    if (p.status === 'draft') {
      actions += (isNew() ? '' : '<button type="button" class="btn btn-outline" data-act="cancel">Batalkan Program</button>') +
        '<button type="button" class="btn btn-outline" id="btn-save" data-act="save">Simpan Draft</button>' +
        (isNew() ? '' : '<button type="button" class="btn btn-accent" id="btn-final" data-act="final">Finalkan program</button>');
    } else if (p.status === 'final') {
      actions += '<a class="btn btn-accent" href="<?= site_url('penjualan_program/start'); ?>?mp=' + p.id + '">Buat Penjualan Program</a>';
    }

    var title = isNew() ? 'Buat Master Program' : 'Master Program ' + esc(p.nomor);
    return '<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="back">← Kembali</button><h2>' + title + '</h2><span class="ref">MP-FR-02 – MP-FR-10</span></div>' +
      (lockInfo ? '<div class="alert">' + lockInfo + '</div>' : '') +
      '<div id="pf-warn" class="alert ' + (warns.length ? '' : 'hidden') + '">' + warns.map(esc).join('<br>') + '</div>' +
      '<div class="pub-form">' + header + s1 + s2 + s3 + s4 + '</div><div class="form-actions">' + actions + '</div>';
  }

  function render() {
    var y = window.scrollY;
    root.innerHTML = view();
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
    [1, 2, 3, 4].forEach(function (n) { var el = $q('[data-sum="' + n + '"]'); if (el) el.innerHTML = C.stepSummary(n, p, { paket: paketOf() }); });
    var w = C.warnings(p), box = $q('#pf-warn');
    box.innerHTML = w.map(esc).join('<br>');
    box.classList.toggle('hidden', !w.length);
  }

  // ---------- Validasi klien (server tetap sumber kebenaran) & simpan ----------
  function validate() {
    var p = S.p, E = {};
    var posInt = function (v) { return v !== '' && Number.isInteger(Number(v)) && Number(v) > 0; };
    if (!p.paket) E.paket = 'Pilih Paket Produk';
    if (!posInt(p.durasi)) E.durasi = 'Durasi bilangan bulat > 0';
    if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
    if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual / peserta wajib diisi';
    p.biaya.forEach(function (b, i) {
      if (b.manual && !String(b.item).trim()) E['b' + i] = 'Nama biaya lain wajib diisi';
      else if (b.harga === '' || b.harga < 0) E['b' + i] = 'Nominal ' + b.item + ' wajib diisi';
    });
    S.errors = E;
    return !Object.keys(E).length;
  }

  var SERVER_KEY = { id_paket_produk: 'paket', durasi_hari: 'durasi', target_peserta: 'targetPeserta', harga_jual_peserta: 'hargaJual', target_profit: 'targetProfit' };

  function busy(on) {
    ['btn-save', 'btn-final'].forEach(function (id) {
      var b = document.getElementById(id); if (b) { b.disabled = on; b.classList.toggle('is-busy', on && (id === 'btn-save' || S.finaling)); }
    });
  }

  // Simpan Draft. Mengembalikan promise (true bila tersimpan) agar "Finalkan" dapat menyimpan dulu.
  function save(silent) {
    var p = S.p, awal = p.biaya.length;
    p.biaya = p.biaya.filter(function (b) { return !(b.harga !== '' && Number(b.harga) === 0); }); // item bernilai 0 = tidak dipakai (MP-FR-07)
    var dibuang = awal - p.biaya.length;
    if (!validate()) { render(); window.toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn'); return Promise.resolve(false); }

    busy(true);
    return new Promise(function (resolve) {
      jQuery.ajax({
        url: URL_BASE + 'save', type: 'POST', dataType: 'json',
        data: {
          id: p.id || '', id_paket_produk: p.paket, durasi_hari: p.durasi, target_peserta: p.targetPeserta, harga_jual_peserta: p.hargaJual, target_profit: p.targetProfit,
          biaya: p.biaya.map(function (b) { return { id_rate: b.rate || '', tipe: b.tipe, item: b.item, id_satuan: b.id_satuan, harga: b.harga, is_manual: b.manual ? 1 : 0 }; })
        }
      }).done(function (res) {
        if (res.status) {
          S.p.id = res.id; S.p.nomor = res.nomor; S.errors = {};
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
      var sec = $q('.step[data-step="' + n + '"]'); sec.classList.toggle('collapsed', !!S.collapsed[n]); el.setAttribute('aria-expanded', !S.collapsed[n]);
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
    cancel: function () { LmCosting.confirmCancel(S.p.id, toView); }
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

  root.addEventListener('input', function (e) {
    var t = e.target;
    if (t.dataset.pf) {
      var name = t.dataset.pf, v = t.value;
      if (MONEY.indexOf(name) >= 0) v = digits(v) === '' ? '' : Number(digits(v));
      else if (name !== 'paket') v = v === '' ? '' : Number(v);
      S.p[name] = name === 'paket' ? (v === '' ? '' : Number(v)) : v;
      // Input simulasi muncul dua kali (bagian 1 dan 4): samakan nilainya.
      if (MONEY.indexOf(name) >= 0 || name === 'durasi' || name === 'targetPeserta') {
        root.querySelectorAll('[data-pf="' + name + '"]').forEach(function (o) { if (o !== t) o.value = MONEY.indexOf(name) >= 0 ? fmtRibu(S.p[name]) : S.p[name]; });
      }
      if (name !== 'paket') refreshCalc();
    } else if (t.dataset.bi !== undefined) {
      var b = S.p.biaya[+t.dataset.bi];
      if (t.dataset.bf === 'harga') b.harga = digits(t.value) === '' ? '' : Number(digits(t.value));
      else b.item = t.value;
      refreshCalc();
    }
  });
  root.addEventListener('change', function (e) {
    if (e.target.dataset.pf === 'paket') { render(); } // hint atribut paket & ringkasan langkah 1 ikut berubah
  });

  // labmania.js (fmtRibu, toast, openModal) dimuat di kaki halaman, setelah isi view ini: render setelah halaman selesai dimuat.
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', render); else render();
})();
</script>
