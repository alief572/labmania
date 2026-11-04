/**
 * Labmania — costing Master Program (dan nanti Penjualan Program): rumus, tabel kelompok satuan, simulasi hasil, popup status.
 * Port dari mockup `specs/final/Mock Up Master - Quotation/app.js` (calc, costWarnings, simView, mpFinal, mpCancel) dan
 * `specs/docs/fr-nfr-master-program.md` bagian 8. Murni tampilan/hitungan di klien; server menghitung ulang saat simpan/finalkan.
 */
(function (w) {
  'use strict';

  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  };
  var rp = function (n) {
    return (n == null || n === '' || isNaN(n)) ? '—' : (n < 0 ? '−' : '') + 'Rp ' + Math.abs(Math.round(n)).toLocaleString('id-ID');
  };
  var num = function (v) { return Number(v) || 0; };

  // Kelompok satuan (FR-25): Fixed = Event, Day; Variable = Peserta/Event, Peserta/Day.
  var GROUPS = [
    { tipe: 'fixed', satuan: 'Event', title: 'Fixed Cost / Event', nominal: 'Nominal / event', tot: 'tot-fe', pick: 'add-fe' },
    { tipe: 'fixed', satuan: 'Day', title: 'Fixed Cost / Day', nominal: 'Nominal / hari', tot: 'tot-fd', pick: 'add-fd' },
    { tipe: 'variable', satuan: 'Peserta/Event', title: 'Variable Cost / Peserta / Event', nominal: 'Nominal / peserta', tot: 'tot-ve', pick: 'add-pe' },
    { tipe: 'variable', satuan: 'Peserta/Day', title: 'Variable Cost / Peserta / Day', nominal: 'Nominal / peserta / hari', tot: 'tot-vd', pick: 'add-pd' }
  ];

  var isDay = function (b) { return b.satuan === 'Day' || b.satuan === 'Peserta/Day'; };
  var perUnit = function (b, d) { return num(b.harga) * (isDay(b) ? d : 1); };

  /** Rumus costing (bagian 8): Fixed, variable / peserta, HPP, pendapatan, profit, BEP; at(x) = nilai pada x peserta. */
  function calc(p) {
    var d = num(p.durasi), n = num(p.targetPeserta);
    var fixed = 0, varPer = 0;
    (p.biaya || []).forEach(function (b) { if (b.tipe === 'fixed') fixed += perUnit(b, d); else varPer += perUnit(b, d); });
    var totalVar = varPer * n, hpp = fixed + totalVar, harga = num(p.hargaJual), pendapatan = harga * n, profit = pendapatan - hpp;
    var bepHarga = n ? hpp / n : null;
    var at = function (x) { var pd = harga * x, h = fixed + varPer * x; return { pendapatan: pd, hpp: h, profit: pd - h }; };
    // BEP peserta = Fixed ÷ (harga − variable per peserta), dibulatkan ke atas, minimal 1; dicocokkan dengan at() agar konsisten.
    var bepPeserta = null;
    if (harga > varPer) {
      bepPeserta = Math.max(1, Math.ceil(fixed / (harga - varPer)));
      while (bepPeserta > 1 && at(bepPeserta - 1).profit >= 0) bepPeserta--;
      while (at(bepPeserta).profit < 0 && bepPeserta < 1e6) bepPeserta++;
    }
    return { fixed: fixed, varPer: varPer, totalVar: totalVar, hpp: hpp, target: n, harga: harga, pendapatan: pendapatan, profit: profit, bepHarga: bepHarga, bepPeserta: bepPeserta, at: at };
  }

  function lineQty(b, p) {
    var d = num(p.durasi), n = num(p.targetPeserta);
    if (b.tipe === 'fixed') return b.satuan === 'Day' ? d : 1;
    return n * (b.satuan === 'Peserta/Day' ? d : 1);
  }
  function lineQtyTxt(b, p) {
    var d = num(p.durasi), n = num(p.targetPeserta);
    if (b.tipe === 'fixed') return b.satuan === 'Day' ? d + ' hari' : '1 event';
    return b.satuan === 'Peserta/Day' ? n + ' peserta × ' + d + ' hari' : n + ' peserta';
  }
  var lineTotal = function (b, p) { return num(b.harga) * lineQty(b, p); };
  var groupItems = function (p, g) { return (p.biaya || []).filter(function (b) { return b.tipe === g.tipe && b.satuan === g.satuan; }); };
  var groupSum = function (p, g) { return groupItems(p, g).reduce(function (s, b) { return s + num(b.harga); }, 0); };

  /** Peringatan (tidak menghalangi simpan Draft). */
  function warnings(p) {
    var c = calc(p), w = [];
    var kosong = (p.biaya || []).filter(function (b) { return b.harga === '' || b.harga == null || (b.manual && !String(b.item).trim()); })
      .map(function (b) { return b.item || '(biaya lain tanpa nama)'; });
    if (kosong.length) w.push('Nominal / nama belum diisi: ' + kosong.join(', ') + '.');
    var hotel = ['Hotel Trainer **', 'Hotel Trainer ***'].map(function (n) { return (p.biaya || []).filter(function (b) { return b.item === n; })[0]; })
      .filter(function (b) { return b && num(b.harga) > 0; });
    if (hotel.length === 2) w.push('Hotel Trainer ** dan *** keduanya terisi — biasanya cukup salah satu per program.');
    if (c.target && c.pendapatan) {
      if (c.profit < 0) w.push('Rugi — pendapatan belum menutup HPP.');
      if (c.bepPeserta === null) w.push('Titik impas tidak tercapai — harga per peserta belum menutup biaya variable per peserta.');
      else if (c.bepPeserta > c.target) w.push('Titik impas ' + c.bepPeserta + ' peserta, di atas jumlah peserta yang direncanakan (' + c.target + ').');
      if (p.targetProfit !== '' && p.targetProfit != null && c.profit < num(p.targetProfit)) w.push('Profit ' + rp(c.profit) + ' di bawah target profit ' + rp(p.targetProfit) + '.');
    }
    return w;
  }

  /** Ringkasan di judul tiap langkah. ctx.paket = objek paket terpilih (atau null). */
  function stepSummary(n, p, ctx) {
    var c = calc(p);
    if (n === 1) return ctx && ctx.paket ? esc(ctx.paket.judul) + ' · ' + num(p.durasi) + ' hari · ' + num(p.targetPeserta) + ' peserta · ' + rp(p.hargaJual) : 'Paket Produk belum dipilih';
    if (n === 2) return 'Total ' + rp(c.fixed);
    if (n === 3) return 'Total ' + rp(c.totalVar);
    return c.target && c.pendapatan ? 'Profit <b class="' + (c.profit < 0 ? 'neg' : 'pos') + '">' + rp(c.profit) + '</b>' : '—';
  }

  function totalsFixed(p) {
    var c = calc(p), d = num(p.durasi), n = num(p.targetPeserta);
    return '<div class="totals">' +
      '<div class="tr"><span><b>Total fixed cost</b> <span class="muted">= event ' + rp(groupSum(p, GROUPS[0])) + ' + day ' + rp(groupSum(p, GROUPS[1])) + ' × ' + d + ' hari</span></span><span><b>' + rp(c.fixed) + '</b></span></div>' +
      '<div class="tr"><span class="muted">Fixed cost / peserta (target)</span><span class="muted">' + (n ? rp(c.fixed / n) : '—') + '</span></div></div>';
  }
  function totalsVar(p) {
    var c = calc(p), d = num(p.durasi);
    return '<div class="totals">' +
      '<div class="tr"><span>Biaya variable / peserta <span class="muted">= event ' + rp(groupSum(p, GROUPS[2])) + ' + day ' + rp(groupSum(p, GROUPS[3])) + ' × ' + d + ' hari</span></span><span>' + rp(c.varPer) + '</span></div>' +
      '<div class="tr"><span><b>Total variable cost</b> <span class="muted">= per peserta × target peserta</span></span><span><b>' + rp(c.totalVar) + '</b></span></div></div>';
  }

  /** Simulasi Hasil (MP-FR-10): BEP, hasil rencana, skala posisi peserta, rincian perhitungan. */
  function simHtml(p, opts) {
    var c = calc(p), d = num(p.durasi);
    if (!(c.target > 0) || !(c.pendapatan > 0)) {
      return '<div class="sim-empty">Isi <b>target peserta dan harga jual / peserta</b> di input simulasi untuk melihat hasilnya.</div>';
    }
    var tp = p.targetProfit === '' || p.targetProfit == null ? 0 : num(p.targetProfit);
    var selisih = c.harga - c.varPer; // harga per peserta − biaya variable per peserta
    var firstX = function (ok, from) { for (var x = Math.max(1, from); x <= 100000; x++) if (ok(x)) return x; return null; };
    var tpPeserta = tp > 0 && selisih > 0 ? firstX(function (x) { return c.at(x).profit >= tp; }, Math.ceil((c.fixed + tp) / selisih) - 2) : null;

    var box = function (cls, tag, big, sub) { return '<div class="sx-box ' + cls + '"><div class="sx-tag">' + tag + '</div><div class="sx-big">' + big + '</div><p>' + sub + '</p></div>'; };

    // 1. Titik impas (BEP)
    var aman = c.bepPeserta === null ? 0 : c.target - c.bepPeserta;
    var lebih = c.harga - c.bepHarga;
    var bepBoxes = box('bep', 'BEP jumlah peserta', c.bepPeserta === null ? 'Tidak tercapai' : c.bepPeserta + ' <small>peserta</small>',
      c.bepPeserta === null ? 'Harga jual tidak melebihi biaya variable per peserta.'
        : 'Dengan harga ' + rp(c.harga) + '. Di bawah ' + c.bepPeserta + ' peserta rugi. ' + (aman >= 0 ? 'Rencana ' + c.target + ' peserta, aman (lebih ' + aman + ').' : 'Rencana ' + c.target + ' peserta, kurang ' + (-aman) + '.')) +
      box('bep', 'BEP harga jual', rp(c.bepHarga) + ' <small>/ peserta</small>',
        'Dengan ' + c.target + ' peserta. Di bawah harga ini rugi. Harga rencana ' + rp(c.harga) + ', ' + (lebih >= 0 ? 'lebih' : 'kurang') + ' ' + rp(Math.abs(lebih)) + '.');

    // 2. Hasil rencana simulasi
    var untung = c.profit >= 0;
    var margin = c.pendapatan > 0 ? (c.profit / c.pendapatan * 100).toFixed(1).replace('.', ',') : '0';
    var trio = box('harga', 'Pendapatan', rp(c.pendapatan), c.target + ' peserta × ' + rp(c.harga)) +
      box('biaya', 'Biaya (HPP)', rp(c.hpp), 'Fixed ' + rp(c.fixed) + ' + variable ' + rp(c.totalVar)) +
      box('profit ' + (untung ? 'ok' : 'bad'), untung ? 'Profit (untung)' : 'Profit (rugi)', rp(c.profit), 'Margin ' + margin + '% dari pendapatan (pendapatan − biaya).');
    var target;
    if (!tp) target = '<div class="sx-target">Target profit tidak diisi.</div>';
    else if (c.profit >= tp) target = '<div class="sx-target ok">Target profit ' + rp(tp) + ' tercapai, lebih ' + rp(c.profit - tp) + (tpPeserta ? '. Minimal ' + tpPeserta + ' peserta untuk capai target.' : '.') + '</div>';
    else target = '<div class="sx-target bad">Target profit ' + rp(tp) + ' belum tercapai, kurang ' + rp(tp - c.profit) + (tpPeserta ? '. Perlu minimal ' + tpPeserta + ' peserta pada harga ini.' : '.') + '</div>';

    // 3. Posisi di skala jumlah peserta
    var pct = function (v, max) { return Math.max(0, Math.min(100, v / max * 100)); };
    var mk = function (v, max, label, cls, pos) { return '<div class="mk ' + cls + ' ' + pos + '" style="left:' + pct(Math.min(v, max), max) + '%">' + label + '</div>'; };
    var maxX = Math.max(10, Math.ceil(Math.max(c.target, tpPeserta || 0, c.bepPeserta || 0) * 1.2));
    var zb = c.bepPeserta === null ? maxX : Math.min(c.bepPeserta, maxX);
    var zg = tpPeserta ? Math.min(Math.max(tpPeserta, zb), maxX) : zb;
    var bar = '<i class="z-rugi" style="width:' + pct(zb, maxX) + '%"></i><i class="z-kuning" style="width:' + pct(zg - zb, maxX) + '%"></i><i class="z-hijau" style="width:' + pct(maxX - zg, maxX) + '%"></i>';
    var marks = (c.bepPeserta !== null ? mk(c.bepPeserta, maxX, 'BEP ' + c.bepPeserta, 'm-bep', 'top') : '') +
      (tpPeserta ? mk(tpPeserta, maxX, 'Target profit ' + tpPeserta, 'm-tp', 'top') : '') + mk(c.target, maxX, 'Rencana ' + c.target, 'm-plan', 'bot');
    var legend = '<span class="a">Rugi (di bawah BEP)</span><span class="b">Untung, belum capai target profit</span><span class="c">Target profit tercapai</span>';
    var scale = '<div class="scale"><div class="bar">' + bar + '</div>' + marks + '</div><div class="axis"><span>0 peserta</span><span>' + maxX + ' peserta</span></div><div class="legend">' + legend + '</div>';

    // 4. Rincian perhitungan
    var ev = groupSum(p, GROUPS[0]), dy = groupSum(p, GROUPS[1]), pe = groupSum(p, GROUPS[2]), pd = groupSum(p, GROUPS[3]);
    var hari = function (e, h) { return h ? rp(e) + ' sekali per acara + ' + rp(h) + ' per hari × ' + d + ' hari' : rp(e) + ' sekali per acara'; };
    var steps = [
      ['Biaya tetap acara', 'Biaya yang keluar berapa pun jumlah pesertanya (trainer, ruangan, dll.).', hari(ev, dy) + ' = <b>' + rp(c.fixed) + '</b>'],
      ['Biaya per peserta', 'Biaya yang bertambah setiap ada 1 peserta (modul, sertifikat, makan, dll.).', hari(pe, pd) + ' = <b>' + rp(c.varPer) + '</b> per peserta'],
      ['Total biaya (HPP)', 'Biaya tetap + biaya per peserta untuk ' + c.target + ' peserta.', rp(c.fixed) + ' + ' + rp(c.varPer) + ' × ' + c.target + ' = <b>' + rp(c.hpp) + '</b>'],
      ['Pendapatan', 'Harga jual dikali jumlah peserta.', rp(c.harga) + ' × ' + c.target + ' = <b>' + rp(c.pendapatan) + '</b>'],
      [untung ? 'Untung' : 'Rugi', 'Pendapatan dikurangi total biaya.', rp(c.pendapatan) + ' − ' + rp(c.hpp) + ' = <b class="' + (untung ? 'pos' : 'neg') + '">' + rp(c.profit) + '</b>'],
      ['Titik impas', 'Jumlah peserta paling sedikit supaya tidak rugi.', c.bepPeserta === null
        ? 'Harga jual belum lebih besar dari biaya per peserta, jadi berapa pun pesertanya tetap rugi.'
        : rp(c.fixed) + ' ÷ (' + rp(c.harga) + ' − ' + rp(c.varPer) + '), dibulatkan ke atas = <b>' + c.bepPeserta + ' peserta</b>']
    ];
    var stepList = '<ol class="sx-steps">' + steps.map(function (s) { return '<li><b>' + s[0] + '</b> <span class="muted">— ' + s[1] + '</span><div>' + s[2] + '</div></li>'; }).join('') + '</ol>';
    var set = [c.target];
    if (c.bepPeserta !== null) { set.push(c.bepPeserta - 1, c.bepPeserta); }
    if (tpPeserta) set.push(tpPeserta);
    var list = set.filter(function (v, i) { return v >= 1 && set.indexOf(v) === i; }).sort(function (a, b) { return a - b; }).slice(0, 5);
    var tblP = '<p class="sx-sub">Kalau jumlah pesertanya berubah:</p><table class="sx-tbl"><thead><tr><th>Peserta</th><th class="n">Pendapatan</th><th class="n">Biaya (HPP)</th><th class="n">Untung / rugi</th></tr></thead><tbody>' +
      list.map(function (n) {
        var v = c.at(n), label = [n === c.bepPeserta && 'titik impas', n === tpPeserta && 'target profit', n === c.target && 'rencana'].filter(Boolean).join(', ');
        return '<tr' + (n === c.target ? ' class="sum"' : '') + '><td>' + n + (label ? ' <span class="muted">(' + label + ')</span>' : '') + '</td><td class="n">' + rp(v.pendapatan) + '</td><td class="n">' + rp(v.hpp) + '</td><td class="n ' + (v.profit >= 0 ? 'pos' : 'neg') + '">' + rp(v.profit) + '</td></tr>';
      }).join('') + '</tbody></table>';
    var open = opts && opts.rinciOpen ? 'open' : '';

    return '<div class="simx">' +
      '<section class="sx-panel"><h3>Titik impas (BEP)</h3><p class="rowdesc">Batas minimal supaya tidak rugi. Tidak tergantung rencana, hanya tergantung biaya.</p><div class="duo">' + bepBoxes + '</div>' +
      '<h3 class="gap">Hasil rencana simulasi</h3><p class="rowdesc">Dihitung dari rencana: ' + d + ' hari, ' + c.target + ' peserta, harga ' + rp(c.harga) + ' / peserta.</p><div class="trio">' + trio + '</div>' + target + '</section>' +
      '<section class="sx-panel"><h3>Posisi di skala jumlah peserta</h3>' + scale + '</section>' +
      '<section class="sx-panel"><details id="sim-rinci" ' + open + '><summary>Rincian perhitungan</summary>' + stepList + tblP + '</details></section></div>';
  }

  // ---------- Popup status (MP-FR-12, MP-FR-14): ringkasan dari server, lalu konfirmasi ----------
  var cfg = { base: '', kind: 'mp' }; // kind: 'mp' (Master Program) | 'pj' (Penjualan Program)
  var sumRow = function (l, v) { return '<div class="sum-row"><span>' + l + '</span><b>' + v + '</b></div>'; };
  var spinner = function (t) { return '<div class="overlay"><div class="modal"><div class="lm-loading"><span class="lm-spin"></span>' + t + '</div></div></div>'; };
  var modal = function (title, body, label, danger) {
    return '<div class="overlay" onclick="if(event.target===this)closeModal()"><div class="modal wide" role="dialog" aria-modal="true">' +
      '<header><h2>' + title + '</h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>' +
      '<div class="modal-body">' + body + '</div>' +
      '<footer><button type="button" class="btn btn-muted" onclick="closeModal()">Batal</button><button type="button" class="btn ' + (danger ? 'btn-danger' : 'btn-accent') + '" id="mp-confirm">' + label + '</button></footer></div></div>';
  };

  function ringkasan(id) {
    return fetch(cfg.base + 'ringkasan/' + id, { credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw 0; return r.json(); }).then(function (j) { if (!j.status) throw 0; return j.data; });
  }

  // POST aksi status; tombol konfirmasi menjadi sibuk sampai respons datang.
  function post(action, id, done) {
    var btn = document.getElementById('mp-confirm');
    btn.disabled = true; btn.classList.add('is-busy');
    jQuery.ajax({ url: cfg.base + action + '/' + id, type: 'POST', dataType: 'json' })
      .done(function (res) {
        w.toast(res.msg, res.status ? 'ok' : 'warn');
        if (res.status) { w.closeModal(); if (done) done(res); } else { btn.disabled = false; btn.classList.remove('is-busy'); }
      })
      .fail(function () { w.toast('Terjadi kesalahan jaringan', 'warn'); btn.disabled = false; btn.classList.remove('is-busy'); });
  }

  // Isi popup per jenis dokumen (MP-FR-12/14, PJ-FR-15/17): judul, ringkasan, dan kalimat konfirmasi.
  var KINDS = {
    mp: {
      finalTitle: 'Finalkan Master Program', finalLabel: 'Ya, finalkan',
      finalIntro: 'Setelah dikonfirmasi, program menjadi <b>Final</b>: angka menjadi baseline resmi, terkunci, dan muncul sebagai pilihan di Penjualan Program.',
      rows: function (r) {
        var bep = r.bep_peserta === null ? 'tidak tercapai' : r.bep_peserta + ' peserta · ' + rp(r.bep_harga) + ' / peserta';
        return sumRow('Program', esc(r.nomor) + ' — ' + esc(r.judul)) + sumRow('Durasi', r.durasi + ' hari') + sumRow('Target peserta', r.target + ' peserta') +
          sumRow('Harga jual / peserta', rp(r.harga)) + sumRow('HPP', rp(r.hpp)) + sumRow('Profit', rp(r.profit)) + sumRow('BEP', bep);
      },
      cancelTitle: 'Batalkan Master Program', cancelLabel: 'Batalkan Program',
      cancelBody: function (r) {
        return 'Batalkan <b>' + esc(r.nomor) + ' — ' + esc(r.judul) + '</b>? Program tidak akan muncul lagi di pilihan Penjualan Program.' +
          (r.n_penjualan ? '<br><span class="muted">' + r.n_penjualan + ' Penjualan Program yang sudah terbentuk dari program ini tidak terpengaruh.</span>' : '');
      }
    },
    pj: {
      finalTitle: 'Finalkan Penjualan Program', finalLabel: 'Ya, finalkan',
      finalIntro: function (r) { return 'Setelah dikonfirmasi, Penjualan Program menjadi <b>Final</b> dan terkunci, lalu masuk ke <b>Quotation</b> untuk persiapan invoice. Pastikan angka peserta dan harga sudah final.'; },
      rows: function (r) {
        return sumRow('Penjualan', esc(r.nomor) + ' — ' + esc(r.judul) + ' (' + (r.jenis === 'b2b' ? 'B2B' : 'Public') + ')') + (r.jenis === 'b2b' ? sumRow('Company', esc(r.company || '—')) : '') +
          sumRow('Durasi', r.durasi + ' hari') + sumRow('Target peserta', r.target + ' peserta') + sumRow('Harga jual', rp(r.harga) + ' / peserta') +
          sumRow('Pendapatan', rp(r.pendapatan)) + sumRow('HPP', rp(r.hpp)) + sumRow('Profit', rp(r.profit)) +
          sumRow('Titik impas', r.bep_peserta === null ? 'tidak tercapai' : r.bep_peserta + ' peserta');
      },
      cancelTitle: 'Batalkan Penjualan Program', cancelLabel: 'Batalkan',
      cancelBody: function (r) { return 'Batalkan <b>' + esc(r.nomor) + '</b>? Penjualan Program Batal tidak dapat diubah.'; }
    }
  };
  var kind = function () { return KINDS[cfg.kind || 'mp']; };

  function confirmFinal(id, done) {
    w.openModal(spinner('Memuat ringkasan…'));
    ringkasan(id).then(function (r) {
      var k = kind(), warn = r.warnings.map(function (t) { return '<div class="warn"><i>!</i>' + esc(t) + '</div>'; }).join('');
      var intro = typeof k.finalIntro === 'function' ? k.finalIntro(r) : k.finalIntro;
      w.openModal(modal(k.finalTitle, '<p>' + intro + '</p><div class="summary">' + k.rows(r) + '</div>' + warn, k.finalLabel));
      document.getElementById('mp-confirm').onclick = function () { post('finalkan', id, done); };
    }).catch(function () { w.closeModal(); w.toast('Ringkasan gagal dimuat', 'error'); });
  }

  function confirmCancel(id, done) {
    w.openModal(spinner('Memuat…'));
    ringkasan(id).then(function (r) {
      var k = kind();
      w.openModal(modal(k.cancelTitle, '<p style="margin:0 0 14px">' + k.cancelBody(r) + '</p>', k.cancelLabel, true));
      document.getElementById('mp-confirm').onclick = function () { post('batalkan', id, done); };
    }).catch(function () { w.closeModal(); w.toast('Data gagal dimuat', 'error'); });
  }

  // Pratinjau Surat Penawaran (PJ-FR-20): dimuat dari server (spinner dulu); tombol Cetak PDF ada di dalam modal.
  function penawaran(id) {
    w.openModal(spinner('Memuat surat…'));
    fetch(cfg.base + 'cetak_penawaran/' + id, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) return r.text().then(function (t) { throw new Error(t); }); return r.text(); })
      .then(function (html) { w.openModal(html); })
      .catch(function (e) { w.closeModal(); w.toast(e.message || 'Surat gagal dimuat', 'error'); });
  }

  w.LmCosting = {
    esc: esc, rp: rp, GROUPS: GROUPS, calc: calc, lineQty: lineQty, lineQtyTxt: lineQtyTxt, lineTotal: lineTotal, groupItems: groupItems, groupSum: groupSum,
    warnings: warnings, stepSummary: stepSummary, totalsFixed: totalsFixed, totalsVar: totalsVar, simHtml: simHtml,
    configure: function (o) { cfg = Object.assign(cfg, o); },
    confirmFinal: confirmFinal, confirmCancel: confirmCancel, penawaran: penawaran
  };
})(window);
