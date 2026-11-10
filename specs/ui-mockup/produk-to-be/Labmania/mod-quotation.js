/* Labmania mockup — Quotation: daftar, detail, form, PDF cetak.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Quotation (QTN-FR-01 – QTN-FR-13; input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/, tampilan acuan input/Master - Buat Program/labmania.html) ----------
  // Penjualan Program Final (Public & B2B) → Quotation. Public berkali-kali, B2B satu quotation aktif (usulan).
  // Tujuan: Company (peserta dari company yang sama) atau Personal (satu atau lebih peserta, peserta baru langsung masuk Master Peserta).
  // Harga per peserta dari Penjualan Program, diskon Rupiah per peserta. Aksi: Kirim dan Cetak, Deal, Batalkan, Confirmation Letter.
  const QTN_STATUS = { draft: 'Draft', terkirim: 'Terkirim', deal: 'Deal', batal: 'Batal' };
  const PS_STATUS = { menunggu: 'Menunggu', deal: 'Deal', batal: 'Batal' };
  const TUJUAN = { company: 'Company', personal: 'Personal' };
  const SUMBER = ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya'];
  const PPN_RATE = 11;
  const QQTN = q('Q-QTN-01', 'Format nomor LMI/PW/MM/yy-zzz (terjawab). Masih usulan: MM/yy dari Tanggal quotation dan nomor urut di-reset tiap bulan.');
  // Nomor dokumen (input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/): LMI/{kode}/MM/yy-zzz,
  // kode PW = Penawaran, INV = Invoice. MM/yy dari tanggal dokumen; urutan per kode & bulan (usulan: reset tiap bulan).
  const lmiNo = (kode, iso, peek = false) => { const t = iso || todayIso, mm = t.slice(5, 7), yy = t.slice(2, 4), key = `${kode}${yy}${mm}`, n = (codeSeq[key] || 0) + 1; if (!peek) codeSeq[key] = n; return `LMI/${kode}/${mm}/${yy}-${String(n).padStart(3, '0')}`; };
  const qtnNo = (iso, peek = false) => lmiNo('PW', iso, peek);
  const QDISK = q('Q-QTN-06', 'Diskon Rupiah per peserta (usulan, mengikuti labmania.html) atau total per quotation?');
  const QALLIN = q('Q-QTN-15', 'All-in hanya untuk B2B dan boleh diskon (terjawab; mockup: Rupiah total). Masih terbuka: di Penawaran dicetak satu baris paket (usulan)? Invoice B2B mengikuti skema: all-in = 1 invoice, per peserta = 1 invoice per peserta.');
  const addDays = (iso, n) => { const d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
  const pjOfQtn = k => db.quot.find(x => x.id === k.pj);
  const pesertaById = id => db.peserta.find(x => x.id === id);
  const salesOf = k => db.user.find(x => x.id === k.sales);
  // Company peserta ikut ditarik dari Master Peserta (QTN-FR-07): nama Company bila terdaftar, selain itu Instansi.
  const pesertaCo = p => { const c = p && db.company.find(x => x.id === p.company); return c ? c.nama : (p && p.instansi) || '—'; };
  const tujuanTag = t => `<span class="tag tag-${t === 'company' ? 'b2b' : 'public'}">${TUJUAN[t]}</span>`;
  const psBadge = s => `<span class="badge st-ps-${s}">${PS_STATUS[s]}</span>`;
  const qtnBadge = s => `<span class="badge st-${s}">${QTN_STATUS[s]}</span>`;
  // Nilai: peserta × harga jual Penjualan Program − diskon Rp / peserta, + PPN dari DPP bila dicentang (QTN-FR-08).
  // B2B all-in (QTN-FR-20): satu harga untuk seluruh peserta; diskon Rupiah total (input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/);
  // harga / peserta setara = (all-in − diskon) ÷ peserta (informasi; invoice all-in tetap satu, IVC-FR-06).
  function qtnNilai(k, rows = k.peserta) {
    const pj = pjOfQtn(k), harga = pj ? Number(pj.hargaJual) || 0 : 0, n = rows.length;
    const lain = Math.max(Number(k.lain) || 0, 0);
    if (isAllin(k)) {
      const sub = Math.max(Number(k.allin) || 0, 0), diskTot = Math.min(Math.max(Number(k.diskon) || 0, 0), sub), dpp = sub - diskTot;
      const ppn = k.ppn ? Math.round(dpp * PPN_RATE / 100) : 0;
      return { allin: true, harga, disk: 0, net: n ? Math.round(dpp / n) : 0, n, sub, diskTot, dpp, ppn, lain, total: dpp + ppn + lain };
    }
    const disk = Math.min(Math.max(Number(k.diskon) || 0, 0), harga), sub = n * harga, diskTot = n * disk, dpp = sub - diskTot;
    const ppn = k.ppn ? Math.round(dpp * PPN_RATE / 100) : 0;
    return { harga, disk, net: harga - disk, n, sub, diskTot, dpp, ppn, lain, total: dpp + ppn + lain };
  }
  const isB2bQtn = k => ((pjOfQtn(k) || {}).jenis) === 'b2b';
  const isAllin = k => k.skema === 'allin' && isB2bQtn(k);
  const kepadaOf = k => k.tujuan === 'company'
    ? (db.company.find(c => c.id === k.company) || {}).nama || '—'
    : (pesertaById(k.peserta[0] && k.peserta[0].id) || {}).nama || '—';
  // Peserta yang sudah ada di quotation lain (selain Batal; peserta tidak Batal) untuk Penjualan Program yang sama tidak dapat dipilih lagi.
  const pesertaDipakai = (pj, kecuali) => {
    const m = {};
    db.qtn.filter(k => k.pj === pj && k.id !== kecuali && k.status !== 'batal')
      .forEach(k => k.peserta.forEach(r => { if (r.st !== 'batal') m[r.id] = k.nomor; }));
    return m;
  };
  const qtnOfPj = pj => db.qtn.filter(k => k.pj === pj.id);
  const dealRows = k => k.status === 'deal' ? k.peserta.filter(r => r.st === 'deal') : [];
  const dealOf = pj => qtnOfPj(pj).reduce((t, k) => t + dealRows(k).length, 0);
  const pendOf = pj => qtnOfPj(pj).filter(k => k.status === 'terkirim').reduce((t, k) => t + k.peserta.length, 0);
  // QTN-FR-03: Public selalu dapat ditambah quotation; B2B satu quotation — baru boleh lagi bila yang lama Batal (usulan, Q-QTN-08).
  const canAddQtn = pj => pj.status === 'final' && (pj.jenis === 'public' || !qtnOfPj(pj).some(k => k.status !== 'batal'));
  // Tab Selesai = tanggal selesai acara sudah lewat (usulan, Q-QTN-09).
  const pjSelesai = pj => !!pj.tglSelesai && pj.tglSelesai < todayIso;
  const qtnTabOk = pj => pj.status === 'final' && (state.qtnTab === 'done') === pjSelesai(pj);

  // Data contoh: peserta dihubungkan ke Company agar Quotation ke Company bisa memilih peserta.
  db.qtn = [];
  {
    const [co1, co2] = db.company;
    db.peserta[0].company = co1.id; db.peserta[0].instansi = co1.nama;
    [['Contoh Peserta D', 'Laki-laki', co1, 'ISO 17025', 'Jawa Barat', 'Kota Bekasi', '081355566677', 'peserta.d@contoh.id'],
      ['Contoh Peserta E', 'Perempuan', co1, 'Analisis Kimia', 'Jawa Barat', 'Kota Bekasi', '081377788899', 'peserta.e@contoh.id'],
      ['Contoh Peserta F', 'Laki-laki', co2, 'Pangan', 'Jawa Timur', 'Kota Surabaya', '082144455566', 'peserta.f@contoh.id'],
      ['Contoh Peserta G', 'Perempuan', co2, 'Pangan', 'Jawa Timur', 'Kota Surabaya', '082166677788', 'peserta.g@contoh.id']
    ].concat(['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'].map((x, i) => [`Contoh Peserta ${x}`, i % 2 ? 'Perempuan' : 'Laki-laki', co1, 'ISO 17025', 'Jawa Barat', 'Kota Bekasi', `08131000${String(i).padStart(4, '0')}`, `peserta.${x.toLowerCase()}@contoh.id`]))
    .forEach(([nama, gender, co, bidang, provinsi, kota, telp, email]) => db.peserta.push({
      id: uid(), kode: nextCode('PS'), nama, gender, company: co.id, instansi: co.nama, bidang: idOf('jenisBidang', bidang), provinsi, kota, telp, email
    }));
    const ps = nama => db.peserta.find(p => p.nama === nama).id;
    const pjB2b = db.quot.find(x => x.jenis === 'b2b' && x.status === 'final');
    const pjPub = db.quot.find(x => x.jenis === 'public' && x.status === 'final');
    const sales = db.user[1].id;
    const seed = o => { const k = Object.assign({ id: uid(), nomor: qtnNo(o.tanggal), sales, sumber: 'Sales', ppn: true, diskon: 0, lain: 0, up: '', alamat: '' }, o); k.ket = k.ket || ketDefault(pjOfQtn(k).jenis); k.kf = k.kf || kfNew(kfDefaultFor(pjOfQtn(k))); db.qtn.push(k); };
    // B2B: satu quotation, minimal 10 peserta, skema all-in (input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/).
    seed({ pj: pjB2b.id, tanggal: '2026-10-26', berlaku: '2026-11-25', tujuan: 'company', company: co1.id, up: co1.picNama, alamat: `${co1.alamat}, ${co1.kota}`, status: 'deal',
      skema: 'allin', allin: 24000000, diskon: 1000000,
      peserta: ['A', 'D', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'E'].map((x, i) => ({ id: ps(`Contoh Peserta ${x}`), st: i < 10 ? 'deal' : 'batal' })) });
    seed({ pj: pjPub.id, tanggal: '2026-10-27', berlaku: '2026-11-26', tujuan: 'company', company: co2.id, up: co2.picNama, alamat: `${co2.alamat}, ${co2.kota}`, status: 'terkirim', sumber: 'Referensi / repeat order', diskon: 100000,
      peserta: [{ id: ps('Contoh Peserta F'), st: 'menunggu' }, { id: ps('Contoh Peserta G'), st: 'menunggu' }] });
    seed({ pj: pjPub.id, tanggal: '2026-10-27', berlaku: '2026-11-26', tujuan: 'personal', company: '', alamat: 'Kota Bekasi, Jawa Barat', status: 'deal', sumber: 'Website',
      peserta: [{ id: ps('Contoh Peserta B'), st: 'deal' }] });
    seed({ pj: pjPub.id, tanggal: '2026-10-28', berlaku: '2026-11-27', tujuan: 'personal', company: '', alamat: 'Jakarta Timur, DKI Jakarta', status: 'draft', sumber: 'Media sosial', ppn: false, diskon: 50000,
      peserta: [{ id: ps('Contoh Peserta C'), st: 'menunggu' }, { id: ps('Contoh Peserta D'), st: 'menunggu' }] });
    seed({ pj: pjPub.id, tanggal: '2026-10-28', berlaku: '2026-11-27', tujuan: 'personal', company: '', alamat: 'Kota Bekasi, Jawa Barat', status: 'batal', sumber: 'Sales',
      peserta: [{ id: ps('Contoh Peserta E'), st: 'batal' }] });
  }

  // ---------- Quotation: daftar per Penjualan Program & detail (QTN-FR-01 – QTN-FR-03, tampilan input/Master - Buat Program/labmania.html) ----------
  const bepTxt = pj => {
    const bep = calc(pj).bepPeserta, deal = dealOf(pj);
    return bep === null ? '<span class="neg">BEP tidak tercapai</span>' : deal >= bep ? `<span class="pos">Lewat BEP (${bep})</span>` : `BEP ${bep}, kurang ${bep - deal}`;
  };
  // Bar progres: deal + menunggu terhadap target, tanda BEP (dan target pada tampilan besar).
  function qtnBar(pj, big) {
    const t = Math.max(nPeserta(pj), 1), deal = dealOf(pj), pend = pendOf(pj), bep = calc(pj).bepPeserta;
    const scale = big ? Math.max(t, deal + pend, bep || 0) : t;
    const pct = v => `${Math.min(100, v / scale * 100).toFixed(1)}%`;
    return `<div class="qbar ${big ? 'big' : ''}"><div class="qb-fill"><span class="qb-deal" style="width:${pct(deal)}"></span><span class="qb-pend" style="width:${pct(pend)}"></span></div>
      ${big ? `<i class="qb-t" style="left:${pct(t)}" title="Target ${t}"></i>` : ''}${bep !== null ? `<i class="qb-bep" style="left:${pct(bep)}" title="BEP ${bep}"></i>` : ''}</div>`;
  }

  function qtnPipe(scope) {
    const agg = s => { const l = scope.filter(k => k.status === s); return { n: l.length, p: l.reduce((t, k) => t + k.peserta.length, 0) }; };
    const A = { draft: agg('draft'), terkirim: agg('terkirim'), deal: agg('deal'), batal: agg('batal') };
    const dealP = scope.reduce((t, k) => t + dealRows(k).length, 0);
    const dealV = scope.filter(k => k.status === 'deal').reduce((t, k) => t + qtnNilai(k, dealRows(k)).total, 0);
    const conv = A.deal.n + A.batal.n ? Math.round(A.deal.n / (A.deal.n + A.batal.n) * 100) : null;
    const card = (s, cls, sub) => `<div class="kpi dash-link ${cls} ${state.status === s ? 'on' : ''}" data-act="qtn-filter" data-s="${s}" role="button" tabindex="0" title="Saring menurut status">
      <div class="kpi-l">${QTN_STATUS[s]}</div><div class="kpi-v">${A[s].n} <small>quotation</small></div><div class="kpi-s">${sub}</div></div>`;
    return `<div class="qtn-flow">
      ${card('draft', '', `${A.draft.p} peserta, belum dikirim`)}<span class="qf-arrow">›</span>
      ${card('terkirim', 'kwarn', `${A.terkirim.p} peserta, menunggu deal`)}<span class="qf-arrow">›</span>
      ${card('deal', 'good', `${dealP} peserta deal · ${rp(dealV)}${conv !== null ? `<br>tingkat deal ${conv}%` : ''}`)}<span class="qf-gap"></span>
      ${card('batal', 'lost', `${A.batal.p} peserta`)}</div>`;
  }

  function viewQtn() {
    const pj = state.qtnPj && db.quot.find(x => x.id === state.qtnPj);
    if (!pj) state.qtnPj = null;
    if (pj) {
      // Detail batch tanpa kartu; tombol Create Quotation untuk batch ini (input/Master - Buat Program/2026-10-29-detail-batch-tanpa-kartu/).
      const add = canAddQtn(pj);
      const info = `${jenisTag(pj.jenis)} <b>${esc(paketOf(mpOf(pj)).judul)}</b> · ${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')} · ${rp(pj.hargaJual)} / peserta${pj.jenis === 'b2b' ? ` · ${esc((db.company.find(x => x.id === pj.company) || {}).nama || '—')}` : ''}
        <br>Peserta deal <b>${dealOf(pj)}</b> / ${nPeserta(pj)}${pendOf(pj) ? ` · +${pendOf(pj)} menunggu` : ''} · ${bepTxt(pj)}`;
      const createBtn = add
        ? `<button class="btn btn-accent" data-act="qtn-from-pj" data-id="${pj.id}">+ Create Quotation</button>`
        : `<button class="btn btn-accent" disabled title="Penjualan B2B sudah punya quotation aktif">+ Create Quotation</button>${q('Q-QTN-08', 'B2B boleh lebih dari satu quotation seperti Public? Usulan: satu quotation aktif; quotation baru setelah yang lama Batal.')}`;
      return `${pageHead(`Quotation — ${esc(pj.nomor)}`, info, 'QTN-FR-02',
        `<button class="btn btn-ghost" data-act="qtn-back">← Semua Penjualan Program</button>${createBtn}`)}
        <div class="card">${toolbar('Cari nomor, kepada, peserta, atau sales…', false,
          selectFilter('fjenis', 'Filter tujuan', state.jenis, [['all', 'Semua tujuan'], ['company', 'Company'], ['personal', 'Personal']])
          + selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(QTN_STATUS).map(s => [s, QTN_STATUS[s]])]))}
          <div id="list">${listQtn()}</div></div>`;
    }
    const inTab = db.quot.filter(qtnTabOk);
    const tabCnt = t => db.quot.filter(x => x.status === 'final' && (t === 'done') === pjSelesai(x)).length;
    const tabs = [['ongoing', 'On going'], ['done', 'Selesai']].map(([t, l]) =>
      `<button class="tab ${state.qtnTab === t ? 'active' : ''}" data-act="qtn-tab" data-tab="${t}">${l}<span class="nav-count">${tabCnt(t)}</span></button>`).join('');
    return `${pageHead('Daftar Quotation', 'Penjualan Program Final — Public maupun B2B — dibuatkan quotation sebagai persiapan invoice. Satu baris per Penjualan Program; Public dapat dibuatkan quotation berkali-kali. Klik <b>Lihat detail</b> untuk quotation-nya.', 'QTN-FR-01 – QTN-FR-13',
      '<button class="btn btn-accent" data-act="qtn-new">+ Buat Quotation</button>')}
      ${qtnPipe(db.qtn.filter(k => inTab.some(x => x.id === k.pj)))}
      <div class="card"><div class="tabs">${tabs}<span class="tabs-note">${q('Q-QTN-09', 'Kapan Penjualan Program dianggap Selesai? Usulan: tanggal selesai acara sudah lewat.')}</span></div>
        ${toolbar('Cari program, nomor quotation, kepada, atau peserta…', false)}
        <div id="list">${listQtn()}</div></div>`;
  }

  // ---------- Detail Quotation — halaman penuh (QTN-FR-22; input/Master - Buat Program/2026-10-30-detail-quotation-halaman/) ----------
  // Tabel daftar hanya menampilkan ringkasan peserta; daftar lengkap peserta, harga, dan invoice ada di halaman ini.
  const qtnAksi = k => {
    const a = [];
    if (k.status === 'draft') a.push(['qtn-edit', 'Edit'], ['qtn-print', 'Kirim dan Cetak Quotation'], ['qtn-cancel', 'Batalkan', true]);
    if (k.status === 'terkirim') a.push(['qtn-deal', 'Deal'], ['qtn-print', 'Kirim dan Cetak Quotation'], ['qtn-cancel', 'Batalkan', true]);
    if (k.status === 'deal') a.push(...(isB2bQtn(k) ? [] : [['qtn-cl', 'Confirmation Letter']]), ['qtn-print', 'Cetak Quotation'], ...(ivcStatus(k) !== 'invoiced' ? [['ivc-add', 'Buat Invoice']] : []));
    if (k.status === 'batal') a.push(['qtn-print', 'Lihat PDF']);
    return a;
  };
  const psStOf = (k, r) => k.status === 'deal' ? r.st : k.status === 'batal' ? 'batal' : 'menunggu';
  // Ringkasan peserta di tabel: jumlah per status + 2 nama pertama.
  const psRingkas = k => {
    const cnt = {}; k.peserta.forEach(r => { const s = psStOf(k, r); cnt[s] = (cnt[s] || 0) + 1; });
    const names = k.peserta.slice(0, 2).map(r => (pesertaById(r.id) || {}).nama).filter(Boolean);
    return `<b>${k.peserta.length}</b> <span class="muted">peserta</span>${k.status === 'deal' ? ` <span class="muted">· ${cnt.deal || 0} deal${cnt.batal ? `, ${cnt.batal} batal` : ''}</span>` : ''}
      <br><span class="muted">${esc(names.join(', '))}${k.peserta.length > 2 ? `, +${k.peserta.length - 2} lainnya` : ''}</span>
      <br><button class="btn btn-ghost sm" data-act="qtn-view" data-id="${k.id}">Lihat detail peserta →</button>`;
  };
  function qtnView(k) { state.qtnView = k.id; state.view = 'qtnDetail'; state.qvF = 'all'; render(); window.scrollTo(0, 0); }
  function viewQtnDetail() {
    const k = db.qtn.find(x => x.id === state.qtnView);
    if (!k) { state.view = 'qtn'; return viewQtn(); }
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), s = salesOf(k) || {};
    const v = qtnNilai(k), vd = k.status === 'deal' ? qtnNilai(k, dealRows(k)) : null;
    const sec = (n, t, hint, b) => `<section class="card pad ivc-sec"><p class="ivc-sec-h"><span class="ivc-no">${n}</span>${t}${hint ? ` <span class="muted">${hint}</span>` : ''}</p>${b}</section>`;
    const kv = rows => `<div class="qv-kv">${rows.map(([l, x]) => `<div><span>${l}</span><b>${x}</b></div>`).join('')}</div>`;
    const btns = qtnAksi(k).map(([a, l, danger]) => `<button type="button" class="btn ${danger ? 'btn-ghost danger-t' : a === 'qtn-deal' || a === 'ivc-add' ? 'btn-accent' : 'btn-outline'} sm" data-act="${a}" data-id="${k.id}">${l}</button>`).join('');
    // Peserta: invoice per peserta (B2B per peserta) atau invoice quotation.
    const invOfPs = id => invOfQtn(k).find(i => i.peserta.includes(id));
    const cnt = st => k.peserta.filter(r => psStOf(k, r) === st).length;
    const F = state.qvF || 'all';
    const list = k.peserta.filter(r => F === 'all' || psStOf(k, r) === F).filter(r => { const p = pesertaById(r.id) || {}; return !state.qvQ || [p.nama, p.kode, pesertaCo(p), p.email].some(x => String(x || '').toLowerCase().includes(state.qvQ.toLowerCase())); });
    const chips = [['all', 'Semua', k.peserta.length], ...['deal', 'menunggu', 'batal'].map(st => [st, PS_STATUS[st], cnt(st)]).filter(x => x[2])]
      .map(([st, l, n]) => `<button type="button" class="tab ${F === st ? 'active' : ''}" data-act="qv-filter" data-s="${st}">${l}<span class="nav-count">${n}</span></button>`).join('');
    const psTable = `<div class="tabs qv-tabs">${chips}</div>
      <div class="toolbar"><div class="search">${SEARCH_ICON}<input type="search" class="input" id="qv-q" placeholder="Cari nama, kode, company, email…" value="${esc(state.qvQ || '')}" aria-label="Cari peserta"></div></div>
      <div class="table-wrap"><table class="table"><thead><tr><th class="num">No</th><th>Peserta</th><th>Company / Instansi</th><th>Kontak</th><th>Status</th>${k.status === 'deal' ? '<th>Invoice</th>' : ''}</tr></thead><tbody>
      ${list.length ? list.map((r, n) => { const p = pesertaById(r.id) || {}, st = psStOf(k, r), inv = st === 'deal' ? invOfPs(r.id) : null;
        return `<tr class="${st === 'batal' ? 'is-off' : ''}"><td class="num">${n + 1}</td><td><b>${esc(p.nama || '—')}</b><br><span class="code">${esc(p.kode || '')}</span></td><td>${esc(pesertaCo(p))}</td>
          <td>${esc(p.telp || '—')}<br><span class="muted">${esc(p.email || '')}</span></td><td>${psBadge(st)}</td>
          ${k.status === 'deal' ? `<td>${inv ? `<button class="btn btn-ghost sm" data-act="ivc-view" data-id="${inv.id}">${esc(inv.nomor)}</button>` : st === 'deal' ? '<span class="badge st-ivc-uninvoiced">Uninvoiced</span>' : '—'}</td>` : ''}</tr>`; }).join('')
        : `<tr><td colspan="6" class="empty">Tidak ada peserta yang cocok.</td></tr>`}</tbody></table></div>`;
    const harga = `<div class="totals">${v.allin
      ? `<div class="tr"><span>Harga all-in <span class="muted">${v.n} peserta</span></span><span>${rp(v.sub)}</span></div><div class="tr"><span>Diskon <span class="muted">Rupiah total</span></span><span>− ${rp(v.diskTot)}</span></div>`
      : `<div class="tr"><span>Subtotal <span class="muted">${v.n} peserta × ${rp(v.harga)}</span></span><span>${rp(v.sub)}</span></div><div class="tr"><span>Diskon <span class="muted">${v.n} × ${rp(v.disk)}</span></span><span>− ${rp(v.diskTot)}</span></div>`}
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div><div class="tr"><span>PPN ${k.ppn ? `${PPN_RATE}%` : '<span class="muted">(tidak dikenakan)</span>'}</span><span>${rp(v.ppn)}</span></div>
      <div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div><div class="tr grand"><span><b>Grand total quotation</b></span><span><b>${rp(v.total)}</b></span></div>
      ${vd && vd.n !== v.n ? `<div class="tr"><span><b>Nilai Deal</b> <span class="muted">${vd.n} peserta deal</span></span><span><b>${rp(vd.total)}</b></span></div>` : ''}</div>`;
    const invSec = k.status === 'deal' ? sec(5, 'Invoice', `${ivcCaraOf(k) === 'peserta' ? 'per peserta' : 'satu invoice untuk quotation ini'}`, invOfQtn(k).length
      ? `<div class="table-wrap"><table class="table"><thead><tr><th>No. Invoice</th><th>Tanggal</th><th>Peserta</th><th>Perlakuan PPN</th><th class="money">Total Tagihan</th><th></th></tr></thead><tbody>
        ${invOfQtn(k).map(i => `<tr><td><span class="code">${esc(i.nomor)}</span></td><td>${tglPanjang(i.tanggal)}</td><td>${i.cara === 'peserta' ? esc((pesertaById(i.peserta[0]) || {}).nama || '—') : `${i.peserta.length} peserta`}</td><td>${PERLAKUAN[i.perlakuan]}</td><td class="money">${rp(i.total)}</td>
          <td class="act"><button class="btn btn-ghost sm" data-act="ivc-view" data-id="${i.id}">Lihat</button><button class="btn btn-ghost sm" data-act="ivc-print" data-id="${i.id}">Print</button></td></tr>`).join('')}</tbody></table></div>
        <p class="hint">${ivcStatus(k) === 'invoiced' ? 'Semua peserta Deal sudah ditagih.' : `${ivcSisaPs(k).length} peserta Deal belum ditagih.`}</p>`
      : `<p class="hint">Belum ada invoice. ${ivcStatus(k) !== 'invoiced' ? `<button type="button" class="btn btn-accent sm" data-act="ivc-add" data-id="${k.id}">Buat Invoice</button>` : ''}</p>`) : '';
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="qv-back">← Kembali</button><h2>Quotation <span class="code">${esc(k.nomor)}</span></h2>${qtnBadge(k.status)}<span class="ref">QTN-FR-22</span></div>
      <div class="notice ivc-ctx"><span>No. Program <span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)} · ${esc(pk.judul)} · ${rangePanjang(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}</span></div>
      ${btns ? `<div class="qv-actions">${btns}</div>` : ''}
      <div class="qtn-grid">${sec(1, 'Data Quotation', '', kv([['Nomor', `<span class="code">${esc(k.nomor)}</span>`], ['Tanggal', tglPanjang(k.tanggal)], ['Berlaku sampai', tglPanjang(k.berlaku)], ['Sales', esc(s.nama || '—')], ['Sumber informasi', esc(k.sumber || '—')], ...(isB2bQtn(k) ? [['Skema harga', isAllin(k) ? 'All-in 1 company' : 'Per peserta']] : [])]))}
        ${sec(2, 'Ditujukan Kepada', '', kv([['Tujuan', tujuanTag(k.tujuan)], ['Kepada', esc(kepadaOf(k))], ['u.p.', esc(k.tujuan === 'company' ? k.up || '—' : kepadaOf(k))], ['Alamat', esc(k.alamat || '—')], ...(k.tujuan === 'company' ? [['Perlakuan PPN', PERLAKUAN[ivcPerlakuan(k)]]] : [])]))}</div>
      ${sec(3, 'Peserta', `${k.peserta.length} peserta${k.status === 'deal' ? ` · ${cnt('deal')} deal` : ''}`, psTable)}
      ${sec(4, 'Harga', '', harga)}
      ${invSec}`;
  }

  function qtnGear(k) {
    // Lihat Detail untuk semua quotation; aksi lain sama dengan tombol di halaman detail.
    return gearMenu(k, [['qtn-view', 'Lihat Detail'], ...qtnAksi(k)]);
  }


  function listQtn() {
    if (!state.qtnPj) {
      // Daftar: satu baris per Penjualan Program Final.
      const rows = db.quot.filter(qtnTabOk).slice().reverse()
        .filter(pj => state.status === 'all' || qtnOfPj(pj).some(k => k.status === state.status))
        .filter(pj => matches([pj.nomor, paketOf(mpOf(pj)).judul, pj.lokasi, ...qtnOfPj(pj).flatMap(k => [k.nomor, kepadaOf(k), k.up, ...k.peserta.map(r => (pesertaById(r.id) || {}).nama)])]));
      return listBlock(rows, [
        NO_COL,
        { label: 'Penjualan Program', cls: 'title', render: pj => `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}<br>${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}</span>` },
        { label: 'Peserta deal', cls: 'money', render: pj => `<b>${dealOf(pj)}</b> / ${nPeserta(pj)}${pendOf(pj) ? `<br><span class="muted">+${pendOf(pj)} menunggu</span>` : ''}` },
        { label: 'Progres', render: pj => `${qtnBar(pj)}<span class="muted">${bepTxt(pj)}</span>` },
        { label: 'Quotation', render: pj => { const l = qtnOfPj(pj);
          return `<b>${l.length}</b> quotation<br><span class="muted">${Object.keys(QTN_STATUS).map(s => { const n = l.filter(k => k.status === s).length; return n ? `${n} ${QTN_STATUS[s].toLowerCase()}` : ''; }).filter(Boolean).join(', ') || 'belum ada'}</span>`; } },
        { label: 'Action', cls: 'act', render: pj => gearMenu(pj, [['qtn-open', 'Lihat detail'], ...(canAddQtn(pj) ? [['qtn-from-pj', '+ Buat Quotation']] : [])]) }
      ]);
    }
    // Detail: quotation dalam satu Penjualan Program.
    const rows = db.qtn.filter(k => k.pj === state.qtnPj).slice().reverse()
      .filter(k => (state.jenis === 'all' || k.tujuan === state.jenis) && (state.status === 'all' || k.status === state.status) &&
        matches([k.nomor, kepadaOf(k), k.up, (salesOf(k) || {}).nama, ...k.peserta.map(r => (pesertaById(r.id) || {}).nama)]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Quotation' + QQTN, cls: 'nowrap', render: k => `<button class="btn btn-ghost sm code" data-act="qtn-view" data-id="${k.id}">${esc(k.nomor)}</button><br><span class="muted">${fmtTgl(k.tanggal)}${k.berlaku ? ` s.d. ${fmtTgl(k.berlaku)}` : ''}<br>Sales: ${esc((salesOf(k) || {}).nama || '—')}${k.sumber ? `, ${esc(k.sumber)}` : ''}</span>` },
      { label: 'Kepada', render: k => `${tujuanTag(k.tujuan)} ${esc(kepadaOf(k))}${k.up ? `<br><span class="muted">u.p. ${esc(k.up)}</span>` : ''}` },
      { label: 'Peserta', render: psRingkas },
      { label: 'Nilai', cls: 'money', render: k => { const v = qtnNilai(k); return `${rp(v.total)}<br><span class="muted">${v.allin ? `All-in ${rp(v.sub)}${v.diskTot ? ` − diskon ${rp(v.diskTot)}` : ''} (≈ ${rp(v.net)} / peserta)` : `${rp(v.net)} / peserta${v.disk ? ` (diskon ${rp(v.disk)})` : ''}`}${k.ppn ? ` + PPN ${PPN_RATE}%` : ''}</span>`; } },
      { label: 'Status', cls: 'nowrap', render: k => qtnBadge(k.status) + (k.status === 'deal' ? `<br><span class="muted">${dealRows(k).length} dari ${k.peserta.length} peserta deal</span>` : '') },
      { label: 'Action', cls: 'act', render: qtnGear }
    ]);
  }

  // ---------- Quotation: form (QTN-FR-04 – QTN-FR-08) ----------
  const NP_FIELDS = { nama: 'Nama Peserta', gender: 'Gender', instansi: 'Instansi/Perusahaan', bidang: 'Bidang', provinsi: 'Provinsi', kota: 'Kota/Kabupaten', telp: 'No Telp/WhatsApp', email: 'Email' };
  const emptyNp = () => ({ nama: '', gender: '', company: '', instansi: '', npwp: '', bidang: '', provinsi: '', kota: '', telp: '', email: '', err: {} });

  // Isian peserta baru di dalam form quotation (field Master Peserta, FR-16); tersimpan ke Master Peserta saat Tambahkan.
  function npHtml(np) {
    const E = np.err, f = (k, label, ctl, cls = '') => field(label + (k === 'company' || k === 'npwp' ? '' : req), ctl, E[k], cls);
    const coOpts = db.company.filter(c => c.aktif).map(c => `<option value="${c.id}" ${c.id === np.company ? 'selected' : ''}>${esc(c.nama)}</option>`).join('');
    const bidOpts = db.jenisBidang.filter(b => b.aktif).map(b => `<option value="${b.id}" ${b.id === np.bidang ? 'selected' : ''}>${esc(b.nama)}</option>`).join('');
    return `<div class="np-box"><p class="np-h">Peserta baru <span class="muted">— otomatis tersimpan di Master Peserta</span></p><div class="grid2">
      ${f('nama', 'Nama Peserta', `<input class="input" name="np_nama" value="${esc(np.nama)}" maxlength="100">`)}
      ${f('gender', 'Gender', `<select class="input" name="np_gender"><option value="">— Pilih —</option>${opts(['Laki-laki', 'Perempuan'], np.gender)}</select>`)}
      ${f('company', 'Company', `<select class="input" name="np_company"><option value="">— Tanpa company —</option>${coOpts}</select>`)}
      ${f('instansi', 'Instansi/Perusahaan', `<input class="input" name="np_instansi" value="${esc(np.instansi)}" maxlength="150" ${np.company ? 'readonly' : ''}>`)}
      ${f('npwp', 'NPWP Instansi', npwpInput('np_npwp', np.npwp, !!np.company), 'span2')}
      ${f('bidang', 'Bidang', `<select class="input" name="np_bidang"><option value="">— Pilih Jenis Bidang —</option>${bidOpts}</select>`)}
      ${f('provinsi', 'Provinsi', `<select class="input" name="np_provinsi"><option value="">— Pilih Provinsi —</option>${provOptions(np.provinsi)}</select>`)}
      ${f('kota', 'Kota/Kabupaten', `<select class="input" name="np_kota" ${np.provinsi ? '' : 'disabled'}>${kotaOptions(np.provinsi, np.kota)}</select>`)}
      ${f('telp', 'No Telp/WhatsApp', `<input class="input" name="np_telp" inputmode="tel" value="${esc(np.telp)}" placeholder="08xxxxxxxxxx">`)}
      ${f('email', 'Email', `<input class="input" name="np_email" type="email" value="${esc(np.email)}" placeholder="nama@domain.com">`, 'span2')}
      </div><div class="ps-tools"><button type="button" class="btn btn-primary sm" data-q="np-save">Tambahkan ke Master & quotation</button><button type="button" class="btn btn-ghost sm" data-q="np-cancel">Batal</button></div></div>`;
  }

  function qtnTotals(d) {
    const pj = db.quot.find(x => x.id === d.pj), v = qtnNilai({ pj: d.pj, ppn: d.ppn, diskon: d.diskon, lain: d.lain, skema: d.skema, allin: d.allin }, d.peserta);
    const bepH = pj ? calc(pj).bepHarga : null;
    if (v.allin) {
      const low = bepH !== null && v.n && v.net < bepH;
      return `<div class="tr"><span>Harga all-in <span class="muted">${v.n} peserta, satu harga untuk company</span></span><span>${rp(v.sub)}</span></div>
      <div class="tr"><span>Diskon <span class="muted">Rupiah total</span></span><span>− ${rp(v.diskTot)}</span></div>
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div>
      <div class="tr"><label class="chk"><input type="checkbox" name="ppn" ${d.ppn ? 'checked' : ''}> PPN ${PPN_RATE}%</label><span>${rp(v.ppn)}</span></div>
      <div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div>
      <div class="tr grand"><span><b>Grand total</b></span><span><b>${rp(v.total)}</b></span></div>
      <div class="tr"><span class="muted">Setara harga / peserta <span class="muted">(harga Penjualan Program ${pj ? rp(v.harga) : '—'})</span></span><span class="${low ? 'neg' : 'muted'}">${v.n ? rp(v.net) : '—'}${low ? ` (di bawah BEP harga ${rp(bepH)})` : ''}</span></div>`;
    }
    return `<div class="tr"><span>Harga / peserta <span class="muted">dari Penjualan Program, tidak dapat diubah</span></span><span>${pj ? rp(v.harga) : '—'}</span></div>
      <div class="tr"><span>Subtotal <span class="muted">${v.n} peserta × ${rp(v.harga)}</span></span><span>${rp(v.sub)}</span></div>
      <div class="tr"><span>Diskon <span class="muted">${v.n} × ${rp(v.disk)}</span></span><span>− ${rp(v.diskTot)}</span></div>
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div>
      <div class="tr"><label class="chk"><input type="checkbox" name="ppn" ${d.ppn ? 'checked' : ''}> PPN ${PPN_RATE}%</label><span>${rp(v.ppn)}</span></div>
      <div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div>
      <div class="tr grand"><span><b>Grand total</b></span><span><b>${rp(v.total)}</b></span></div>
      <div class="tr"><span class="muted">Harga / peserta setelah diskon</span><span class="${bepH !== null && v.disk && v.net < bepH ? 'neg' : 'muted'}">${rp(v.net)}${bepH !== null && v.disk && v.net < bepH ? ` (di bawah BEP harga ${rp(bepH)})` : ''}</span></div>`;
  }

  function qtnStep1(d) {
    const E = d.err || {};
    const pj = db.quot.find(x => x.id === d.pj), b2b = pj && pj.jenis === 'b2b';
    const pjOpts = db.quot.filter(x => x.status === 'final' || x.id === d.pj).map(x => { const ok = canAddQtn(x) || x.id === d.pj;
      return `<option value="${x.id}" ${x.id === d.pj ? 'selected' : ''} ${ok ? '' : 'disabled'}>${esc(x.nomor)} · ${esc(paketOf(mpOf(x)).judul)} · ${JENIS[x.jenis]} · ${fmtRange(x.tglMulai, x.tglSelesai)}${ok ? '' : ' (sudah ada quotation)'}</option>`; }).join('');
    const salesOpts = db.user.filter(u => u.aktif || u.id === d.sales).map(u => `<option value="${u.id}" ${u.id === d.sales ? 'selected' : ''}>${esc(u.nama)}</option>`).join('');
    const coOpts = db.company.filter(c => c.aktif || c.id === d.company).map(c => `<option value="${c.id}" ${c.id === d.company ? 'selected' : ''}>${esc(c.nama)}</option>`).join('');
    const pjInfo = pj ? `${jenisTag(pj.jenis)} ${esc(pj.lokasi || '—')} · harga ${rp(pj.hargaJual)} / peserta · target ${nPeserta(pj)} peserta` : 'Hanya Penjualan Program berstatus Final.';

    // Tata letak atas → bawah: Data Quotation (lebar penuh) → Ditujukan kepada | Peserta (sejajar) → Harga | Total.
    const top = `<p class="qtn-h">Data Quotation</p>
      <div class="grid4 qtn-top">
        ${field('Penjualan Program' + req, `<select class="input" name="pj"><option value="">— Pilih Penjualan Program Final —</option>${pjOpts}</select><span class="hint">${pjInfo}</span>`, E.pj, 'span3')}
        ${field('Nomor Quotation' + QQTN, `<input class="input" value="${esc(d.nomor || qtnNo(d.tanggal, true))}" readonly><span class="hint">Otomatis oleh sistem saat Simpan</span>`)}
        ${field('Tanggal quotation' + req, `<input class="input" type="date" name="tanggal" value="${esc(d.tanggal)}">`, E.tanggal)}
        ${field('Berlaku sampai', `<input class="input" type="date" name="berlaku" value="${esc(d.berlaku)}">`, E.berlaku)}
        ${field('Sales / marketing' + req, `<select class="input" name="sales"><option value="">— Pilih —</option>${salesOpts}</select><span class="hint">Dari Master User aktif</span>`, E.sales)}
        ${field('Sumber informasi', `<select class="input" name="sumber"><option value="">—</option>${opts(SUMBER, d.sumber)}</select>`)}
      </div>`;

    const left = `<p class="qtn-h">Ditujukan kepada</p>
      ${field('Quotation ke' + req, `<div class="static seg">
          <label><input type="radio" name="tujuan" value="company" ${d.tujuan === 'company' ? 'checked' : ''}> Company</label>
          <label class="${b2b ? 'is-dis' : ''}"><input type="radio" name="tujuan" value="personal" ${d.tujuan === 'personal' ? 'checked' : ''} ${b2b ? 'disabled' : ''}> Personal</label></div>
        <span class="hint">${b2b ? 'Penjualan B2B: quotation ke Company penjualan tersebut.' : 'Company = peserta dari satu company · Personal = satu atau lebih peserta, dapat menambah peserta baru.'}</span>`, E.tujuan)}
      ${d.tujuan === 'company' ? `<div class="grid2">
        ${field('Company' + req, `<select class="input" name="company" ${b2b ? 'disabled' : ''}><option value="">— Pilih Company —</option>${coOpts}</select>${b2b ? '<span class="hint">Mengikuti Company Penjualan Program B2B</span>' : '<span class="hint">Dari Master Company aktif</span>'}`, E.company, 'span2')}
        ${field('u.p.', `<input class="input" name="up" value="${esc(d.up)}" maxlength="100" placeholder="Nama yang dituju">`, '', 'span2')}
      </div>` : ''}
      ${d.tujuan ? field('Alamat', `<textarea class="input" name="alamat" rows="2">${esc(d.alamat)}</textarea><span class="hint">Terisi otomatis dari ${d.tujuan === 'company' ? 'Company' : 'peserta pertama'}; dapat diubah untuk surat ini.</span>`) : ''}`;

    const dipakai = d.pj ? pesertaDipakai(d.pj, d.id) : {};
    let pesertaHtml;
    if (!d.tujuan) pesertaHtml = '<p class="dash-empty">Pilih tujuan quotation dahulu.</p>';
    else if (d.tujuan === 'company') {
      // Company → centang peserta yang terdaftar di company itu (QTN-FR-06).
      const list = d.company ? db.peserta.filter(p => p.company === d.company) : [];
      pesertaHtml = !d.company ? '<p class="dash-empty">Pilih Company dahulu — peserta yang tampil hanya dari company tersebut.</p>'
        : !list.length ? '<p class="dash-empty">Belum ada peserta di company ini. Tambahkan lewat <b>Master Peserta</b> (pilih Company).</p>'
          : `<div class="ps-pick">${list.map(p => { const lain = dipakai[p.id]; const on = d.peserta.includes(p.id);
            return `<label class="ps-row ${lain ? 'is-dis' : ''}"><input type="checkbox" name="ps" value="${p.id}" ${on ? 'checked' : ''} ${lain ? 'disabled' : ''}>
              <span><b>${esc(p.nama)}</b> <span class="code">${esc(p.kode)}</span><br><span class="muted">${esc(findRec('jenisBidang', p.bidang).nama)} · ${esc(p.telp)}${lain ? ` · sudah di ${esc(lain)}` : ''}</span></span></label>`; }).join('')}</div>
            <div class="ps-tools"><button type="button" class="btn btn-ghost sm" data-q="all">Pilih semua</button><span class="muted">${d.peserta.length} dari ${list.length} peserta dipilih</span></div>`;
    } else {
      // Personal → satu atau lebih peserta; company peserta ikut tampil; peserta baru langsung masuk Master Peserta (QTN-FR-07).
      const chosen = d.peserta.map((id, i) => { const p = pesertaById(id) || {};
        return `<div class="ps-row"><span class="ps-no">${i + 1}</span><span class="grow"><b>${esc(p.nama)}</b> <span class="code">${esc(p.kode)}</span><br>
          <span class="muted">Company / Instansi: <b>${esc(pesertaCo(p))}</b> · ${esc(p.telp)} · ${esc(p.email)}</span></span>
          <button type="button" class="btn btn-ghost sm" data-q="ps-del" data-i="${i}" aria-label="Hapus peserta">×</button></div>`; }).join('');
      const pOpts = db.peserta.filter(p => !d.peserta.includes(p.id)).sort((a, b) => a.nama.localeCompare(b.nama)).map(p => { const lain = dipakai[p.id];
        return `<option value="${p.id}" ${lain ? 'disabled' : ''}>${esc(p.nama)} — ${esc(pesertaCo(p))}${lain ? ` (sudah di ${esc(lain)})` : ''}</option>`; }).join('');
      pesertaHtml = `${chosen ? `<div class="ps-pick">${chosen}</div>` : '<p class="dash-empty">Belum ada peserta. Pilih dari Master Peserta atau isi peserta baru.</p>'}
        <div class="ps-add"><select class="input" name="psAdd" aria-label="Tambah peserta dari Master Peserta"><option value="">+ Tambah peserta dari Master Peserta…</option>${pOpts}</select>
          ${d.newPs ? '' : '<button type="button" class="btn btn-ghost sm" data-q="np-open">+ Peserta baru</button>'}</div>
        ${d.newPs ? npHtml(d.newPs) : ''}`;
    }

    const target = pj ? nPeserta(pj) : 0, sudah = pj ? Object.keys(dipakai).length : 0;
    const right = `<p class="qtn-h">Peserta <span class="muted">${d.peserta.length ? `(${d.peserta.length})` : ''}</span></p>
      <div class="${E.peserta ? 'field has-err' : ''}">${pesertaHtml}${E.peserta ? `<div class="err"><i>!</i>${esc(E.peserta)}</div>` : ''}</div>
      ${pj ? `<p class="hint">${b2b ? `<b>Penjualan B2B: minimal ${B2B_MIN} peserta</b> (juga saat Deal) · ` : ''}Target Penjualan Program ${target} peserta · di quotation lain ${sudah} · quotation ini ${d.peserta.length}. Selisih dengan target tidak menghalangi — dipantau di laporan Rencana vs Aktual.</p>` : ''}`;
    // B2B: skema harga per peserta atau all-in 1 company (QTN-FR-20). All-in boleh diskon, Rupiah total.
    const allin = b2b && d.skema === 'allin';
    const skemaHtml = b2b ? field('Skema harga' + req + QALLIN, `<div class="static seg">
          <label><input type="radio" name="skema" value="peserta" ${allin ? '' : 'checked'}> Per peserta</label>
          <label><input type="radio" name="skema" value="allin" ${allin ? 'checked' : ''}> All-in 1 company</label></div>
        <span class="hint">${allin ? 'Satu harga untuk seluruh peserta company; diskon Rupiah total.' : 'Harga / peserta dari Penjualan Program × jumlah peserta, dapat diberi diskon.'}</span>`) : '';
    const harga = allin ? `<div>${skemaHtml}
        ${field('Harga all-in (Rp)' + req, `${moneyIn('name="allin" placeholder="0"', d.allin || '')}<span class="hint">Terisi awal harga / peserta × jumlah peserta; ubah sesuai kesepakatan.</span>`, E.allin)}
        ${field('Diskon (Rp total)', `${moneyIn('name="diskon" placeholder="0"', d.diskon || '')}<span class="hint">Rupiah saja, tanpa %. Satu nilai untuk seluruh quotation, maksimal harga all-in.</span>`, E.diskon)}
        ${field('Biaya lain-lain (Rp)', `${moneyIn('name="lain" placeholder="0"', d.lain || '')}<span class="hint">Baris "Biaya Lain-lain" di Penawaran</span>`)}
      </div><div class="totals" id="qtn-totals">${qtnTotals(d)}</div>` : `<div>${skemaHtml}
        ${field('Diskon (Rp / peserta)' + QDISK, `${moneyIn('name="diskon" placeholder="0"', d.diskon || '')}<span class="hint">Rupiah saja, tanpa %. Maksimal harga / peserta.</span>`, E.diskon)}
        ${field('Biaya lain-lain (Rp)', `${moneyIn('name="lain" placeholder="0"', d.lain || '')}<span class="hint">Baris "Biaya Lain-lain" di Penawaran</span>`)}
      </div><div class="totals" id="qtn-totals">${qtnTotals(d)}</div>`;
    return `<div class="qtn-form">${top}
      <div class="qtn-grid"><div>${left}</div><div>${right}</div></div>
      <p class="qtn-h">Harga</p><div class="qtn-grid">${harga}</div></div>`;
  }

  // Ubah pilihan di form: field struktural menggambar ulang isi popup, sisanya hanya disimpan.
  function qtnChange(d, form, t) {
    const n = t.name, val = t.value;
    if (n && n.startsWith('np_')) {
      const k = n.slice(3), np = d.newPs;
      np[k] = k === 'company' || k === 'bidang' ? Number(val) || '' : val;
      delete np.err[k];
      if (k === 'company') { const c = db.company.find(x => x.id === np.company); if (c) { np.instansi = c.nama; np.npwp = c.npwp || ''; } }
      if (k === 'provinsi') np.kota = '';
      if (k === 'company' || k === 'provinsi') qtnRedraw(d, form);
      return;
    }
    if (n === 'pj') {
      d.pj = Number(val) || ''; d.peserta = []; d.err = {}; d.skema = 'peserta'; d.allin = '';
      const pj = db.quot.find(x => x.id === d.pj);
      if (!d.ketTouched) d.ketItems = pj ? ketItems(pj.jenis, null) : null;
      if (!d.kfTouched) d.kf = pj ? kfNew(kfDefaultFor(pj)) : null;
      if (pj && pj.jenis === 'b2b') qtnSetCompany(d, pj.company, 'company');
      else { d.tujuan = d.tujuan || ''; if (d.tujuan === 'company' && d.company) qtnSetCompany(d, d.company, 'company'); }
    } else if (n === 'tujuan') { d.tujuan = val; d.peserta = []; d.company = ''; d.up = ''; d.alamat = ''; d.newPs = null; }
    else if (n === 'company') qtnSetCompany(d, Number(val) || '', 'company');
    else if (n === 'ps') d.peserta = [...form.querySelectorAll('[name=ps]:checked')].map(x => +x.value);
    else if (n === 'psAdd') { if (val) qtnAddPeserta(d, +val); }
    else if (n === 'ppn') { d.ppn = t.checked; form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); return; }
    else if (n === 'skema') {
      d.skema = val; d.diskon = ''; // arti diskon berubah: Rp / peserta ↔ Rp total
      const pj = db.quot.find(x => x.id === d.pj);
      if (val === 'allin' && !d.allin && pj) d.allin = (Number(pj.hargaJual) || 0) * Math.max(d.peserta.length, B2B_MIN);
    }
    else if (n === 'diskon' || n === 'lain' || n === 'allin') return;
    else if (n === 'sales') { d.sales = Number(val) || ''; return; }
    else { d[n] = val; if (n === 'tanggal' && val && !d.berlaku) { d.berlaku = addDays(val, 30); form.elements.berlaku.value = d.berlaku; } return; }
    if (d.err) delete d.err[n === 'ps' || n === 'psAdd' ? 'peserta' : n];
    qtnRedraw(d, form);
  }
  function qtnAddPeserta(d, id) {
    d.peserta.push(id);
    if (d.peserta.length === 1) { const p = pesertaById(id); d.alamat = p ? `${p.kota}, ${p.provinsi}` : ''; }
  }
  function qtnSetCompany(d, id, tujuan) {
    const c = db.company.find(x => x.id === id);
    Object.assign(d, { tujuan, company: id, peserta: [], up: c ? c.picNama || '' : '', alamat: c ? `${c.alamat}, ${c.kota}` : '' });
  }
  // Simpan peserta baru ke Master Peserta (validasi sama dengan form Master Peserta, FR-16) lalu masukkan ke quotation.
  function npSave(d) {
    const np = d.newPs, E = {};
    Object.keys(NP_FIELDS).forEach(k => { if (!String(np[k] || '').trim()) E[k] = `${NP_FIELDS[k]} wajib diisi`; });
    if (np.email && !EMAIL_RE.test(np.email.trim())) E.email = 'Format email tidak valid';
    if (np.telp && !telpOk(np.telp)) E.telp = 'Nomor hanya angka, 9–15 digit';
    if (np.npwp && !npwpOk(np.npwp)) E.npwp = 'NPWP Instansi 15 atau 16 digit angka';
    np.err = E;
    if (Object.keys(E).length) return false;
    const p = { id: uid(), kode: nextCode('PS') };
    Object.keys(NP_FIELDS).forEach(k => { p[k] = typeof np[k] === 'string' ? np[k].trim() : np[k]; });
    p.company = np.company || ''; p.npwp = (np.npwp || '').trim();
    db.peserta.push(p);
    qtnAddPeserta(d, p.id);
    d.newPs = null;
    if (d.err) delete d.err.peserta;
    toast(`Peserta ${p.kode} — ${p.nama} tersimpan di Master Peserta`);
    return true;
  }

  // Form quotation 3 langkah: Data Quotation → Keterangan → Preview (input/Master - Buat Program/2026-10-29-keterangan-ux-preview/).
  // B2B (in-house) tanpa Formulir Konfirmasi → 3 langkah (input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/).
  const QTN_STEPS = [['data', 'Data Quotation'], ['ket', 'Keterangan'], ['kf', 'Formulir Konfirmasi'], ['prev', 'Preview']];
  const qtnSteps = d => QTN_STEPS.filter(([s]) => s !== 'kf' || (db.quot.find(x => x.id === d.pj) || {}).jenis !== 'b2b');
  function qtnBody(d) {
    const steps = qtnSteps(d);
    if (d.step > steps.length) d.step = steps.length;
    const nav = `<ol class="stepper">${steps.map(([, l], i) => `<li class="${d.step === i + 1 ? 'on' : d.step > i + 1 ? 'done' : ''}"><button type="button" data-q="step" data-n="${i + 1}" ${d.step === i + 1 ? 'aria-current="step"' : ''}><span>${i + 1}</span>${l}</button></li>`).join('')}</ol>`;
    const s = steps[d.step - 1][0];
    return nav + (s === 'data' ? qtnStep1(d) : s === 'ket' ? ketHtml(d) : s === 'kf' ? kfHtml(d) : qtnPreview(d));
  }
  // Halaman PDF quotation: Penawaran + S&K (+ Formulir Konfirmasi per peserta, kecuali B2B).
  const qtnPages = (k, ps) => pagePenawaran(k) + pagesSnk() + (isB2bQtn(k) ? '' : ps.map(p => pageKonfirmasi(k, p)).join(''));
  const qtnPagesNote = (k, ps) => `Penawaran (1 hlm) + Syarat &amp; Ketentuan (2 hlm)${isB2bQtn(k) ? ' — B2B / in-house tanpa Formulir Konfirmasi' : ` + Formulir Konfirmasi ${esc((kfTplById((k.kf || {}).tpl) || {}).nama || '')} (${ps.length} peserta)`}`;
  // Preview sebelum disimpan: PDF yang sama dengan cetak.
  function qtnPreview(d) {
    const k = Object.assign({ nomor: d.nomor || qtnNo(d.tanggal, true), status: 'draft' }, qtnData(d));
    const ps = k.peserta.map(r => pesertaById(r.id)).filter(Boolean);
    return `<div class="pdf-tools"><span><span class="badge st-draft">Preview — belum disimpan</span> <span class="muted">${qtnPagesNote(k, ps)}. Periksa, lalu Simpan atau ← Kembali.</span></span></div>
      <div class="pdf-doc">${qtnPages(k, ps)}</div>`;
  }
  const qtnRedraw = (d, form) => {
    const body = form.querySelector('.modal-body');
    body.innerHTML = qtnBody(d);
    const sub = form.querySelector('footer [type=submit]'), back = form.querySelector('footer [data-q="back"]');
    if (sub) sub.textContent = d.step < qtnSteps(d).length ? 'Lanjut →' : d.isEdit ? 'Simpan Perubahan' : 'Simpan Draft';
    if (back) back.hidden = d.step === 1;
    if (d.lastStep !== d.step) { body.scrollTop = 0; d.lastStep = d.step; }
  };
  function qtnErrors(d, rec) {
    const E = {}, pj = db.quot.find(x => x.id === d.pj);
    if (!pj) E.pj = 'Penjualan Program wajib dipilih';
    else if (pj.status !== 'final') E.pj = 'Hanya Penjualan Program Final yang dapat dibuatkan quotation';
    else if (!rec && !canAddQtn(pj)) E.pj = 'Penjualan B2B ini sudah punya quotation aktif';
    if (!d.tanggal) E.tanggal = 'Tanggal quotation wajib diisi';
    if (d.berlaku && d.tanggal && d.berlaku < d.tanggal) E.berlaku = 'Tidak boleh sebelum tanggal quotation';
    if (!d.sales) E.sales = 'Sales / marketing wajib dipilih';
    if (!d.tujuan) E.tujuan = 'Pilih Company atau Personal';
    else if (d.tujuan === 'company' && !d.company) E.company = 'Company wajib dipilih';
    if (d.tujuan && !d.peserta.length) E.peserta = d.tujuan === 'company' ? 'Centang minimal 1 peserta' : 'Pilih atau tambahkan minimal 1 peserta';
    if (d.tujuan === 'company' && d.peserta.some(id => (pesertaById(id) || {}).company !== d.company)) E.peserta = 'Semua peserta harus dari company yang sama';
    const b2b = pj && pj.jenis === 'b2b', allin = b2b && d.skema === 'allin';
    if (b2b && d.tujuan && d.peserta.length && d.peserta.length < B2B_MIN && !E.peserta) E.peserta = `Penjualan B2B minimal ${B2B_MIN} peserta (baru ${d.peserta.length})`;
    if (allin && !(Number(d.allin) > 0)) E.allin = 'Harga all-in wajib diisi';
    if (pj && !allin && (Number(d.diskon) || 0) > (Number(pj.hargaJual) || 0)) E.diskon = 'Diskon tidak boleh melebihi harga / peserta';
    if (allin && (Number(d.diskon) || 0) > (Number(d.allin) || 0)) E.diskon = 'Diskon tidak boleh melebihi harga all-in';
    return E;
  }
  function qtnData(d) {
    const jenis = (db.quot.find(x => x.id === d.pj) || {}).jenis || 'public';
    return { pj: d.pj, tanggal: d.tanggal, berlaku: d.berlaku, sales: d.sales, sumber: d.sumber, tujuan: d.tujuan,
      company: d.tujuan === 'company' ? d.company : '', up: d.tujuan === 'company' ? (d.up || '').trim() : '', alamat: (d.alamat || '').trim(), ppn: d.ppn,
      skema: jenis === 'b2b' && d.skema === 'allin' ? 'allin' : 'peserta', allin: jenis === 'b2b' && d.skema === 'allin' ? Number(d.allin) || 0 : 0,
      diskon: Number(d.diskon) || 0, lain: Number(d.lain) || 0, ket: ketFromItems(d.ketItems, jenis),
      kf: jenis === 'b2b' ? null : d.kf ? clone(d.kf) : null,
      peserta: d.peserta.map(id => ({ id, st: 'menunggu' })) };
  }
  // Langkah lain hanya bisa dibuka bila langkah 1 valid; bila tidak, kembali ke langkah 1 dengan pesan.
  function qtnGo(d, form, rec, n) {
    if (n > 1) {
      const E = qtnErrors(d, rec);
      if (Object.keys(E).length) { d.err = E; d.step = 1; qtnRedraw(d, form); toast('Lengkapi Data Quotation dahulu', 'warn'); return false; }
    }
    d.err = {}; d.step = n; qtnRedraw(d, form);
    return true;
  }
  function qtnKetAct(d, form, a, b) {
    const k = b.dataset.k, i = +b.dataset.i, jenis = (db.quot.find(x => x.id === d.pj) || {}).jenis || 'public';
    if (a === 'ket-edit') d.ketEdit = `${k}:${i}`;
    if (a === 'ket-edit-cancel') d.ketEdit = null;
    if (a === 'ket-edit-ok') {
      const v = form.querySelector('[data-ket-edit]').value.trim(), x = d.ketItems[k][i];
      if (v && v !== x.teks) { x.teks = v; x.id = null; }
      x.on = true; d.ketEdit = null; d.ketTouched = true;
    }
    if (a === 'ket-new') {
      const inp = form.querySelector(`[data-ket-new="${k}"]`), v = inp.value.trim();
      if (!v) return inp.focus();
      d.ketItems[k].push({ id: null, teks: v, on: true }); d.ketTouched = true;
    }
    if (a === 'ket-reset') { d.ketItems = ketItems(jenis, null); d.ketTouched = false; d.ketEdit = null; }
    qtnRedraw(d, form);
    if (a === 'ket-new') form.querySelector(`[data-ket-new="${k}"]`).focus();
    if (a === 'ket-edit') form.querySelector('[data-ket-edit]').focus();
  }

  function qtnForm(rec, preset) {
    const pjRec = rec && db.quot.find(x => x.id === rec.pj);
    const d = rec ? Object.assign(clone(rec), { peserta: rec.peserta.map(r => r.id), err: {}, newPs: null, isEdit: true, ketItems: ketItems(pjRec.jenis, rec.ket), ketTouched: true, kf: clone(rec.kf || kfNew(kfDefaultFor(pjRec))), kfTouched: true })
      : { pj: '', tanggal: todayIso, berlaku: addDays(todayIso, 30), sales: (db.user.find(u => u.aktif) || {}).id || '', sumber: '', tujuan: '', company: '', up: '', alamat: '', peserta: [], ppn: true, diskon: '', lain: '', ketItems: null, ketTouched: false, newPs: null, err: {} };
    Object.assign(d, { step: 1, lastStep: 1, ketOpen: {}, ketEdit: null });
    if (preset) { d.pj = preset; const pj = db.quot.find(x => x.id === preset); d.ketItems = ketItems(pj.jenis, null); d.kf = kfNew(kfDefaultFor(pj)); if (pj.jenis === 'b2b') qtnSetCompany(d, pj.company, 'company'); }
    openModal({
      title: rec ? `Edit Quotation ${rec.nomor}` : 'Buat Quotation', wide: 'x',
      body: qtnBody(d), saveLabel: 'Lanjut →',
      onMount: form => {
        form.querySelector('footer [type=submit]').insertAdjacentHTML('beforebegin', '<button type="button" class="btn btn-ghost" data-q="back" hidden>← Kembali</button>');
        form.addEventListener('change', e => {
          const t = e.target;
          if (t.name === 'kfTpl') { d.kf = kfNew(kfTplById(+t.value)); d.kfTouched = true; qtnRedraw(d, form); return; }
          if (t.dataset.kfi) { const k = t.dataset.kfi; d.kf.isian = KF_FIELDS.map(([x]) => x).filter(x => x === k ? t.checked : d.kf.isian.includes(x)); d.kfTouched = true; qtnRedraw(d, form); return; }
          if (t.name === 'kfPernyataan' || t.dataset.kfNew !== undefined) return;
          if (t.dataset.kc) { d.ketItems[t.dataset.kc][+t.dataset.i].on = t.checked; d.ketTouched = true; qtnRedraw(d, form); return; }
          if (t.dataset.ketNew !== undefined || t.dataset.ketEdit !== undefined) return;
          qtnChange(d, form, t);
        });
        form.addEventListener('toggle', e => { if (e.target.classList && e.target.classList.contains('ket-acc')) d.ketOpen[e.target.dataset.k] = e.target.open; }, true);
        form.addEventListener('input', e => {
          const t = e.target;
          if (t.name === 'up' || t.name === 'alamat') d[t.name] = t.value;
          if (t.name === 'kfPernyataan') { d.kf.pernyataan = t.value; d.kfTouched = true; const pj = db.quot.find(x => x.id === d.pj), pv = form.querySelector('#kf-prev'); if (pj && pv) pv.innerHTML = kfText(t.value, pj); }
          if (t.name === 'lain') { d.lain = num(t.value.replace(/\D/g, '')); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
          if (t.name === 'diskon') { d.diskon = num(t.value.replace(/\D/g, '')); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
          if (t.name === 'allin') { d.allin = num(t.value.replace(/\D/g, '')); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
          if (t.name && t.name.startsWith('np_') && t.tagName === 'INPUT') d.newPs[t.name.slice(3)] = t.value;
        });
        // Enter: isian peserta baru = Tambahkan; teks keterangan = Tambah/Simpan — bukan lanjut langkah.
        form.addEventListener('keydown', e => {
          if (e.key !== 'Enter') return;
          const t = e.target;
          if (t.name && t.name.startsWith('np_')) { e.preventDefault(); npSave(d); qtnRedraw(d, form); }
          else if (t.dataset.kfNew !== undefined) { e.preventDefault(); kfAct(d, form, 'kf-new', t); }
          else if (t.dataset.ketNew) { e.preventDefault(); qtnKetAct(d, form, 'ket-new', { dataset: { k: t.dataset.ketNew } }); }
          else if (t.dataset.ketEdit) { e.preventDefault(); const [k, i] = t.dataset.ketEdit.split(':'); qtnKetAct(d, form, 'ket-edit-ok', { dataset: { k, i } }); }
        });
        form.addEventListener('click', e => {
          const b = e.target.closest('[data-q]'); if (!b) return;
          const a = b.dataset.q;
          if (a === 'step') return qtnGo(d, form, rec, +b.dataset.n);
          if (a === 'back') return qtnGo(d, form, rec, d.step - 1);
          if (a.startsWith('ket-')) return qtnKetAct(d, form, a, b);
          if (a.startsWith('kf-')) return kfAct(d, form, a, b);
          if (a === 'all') d.peserta = [...form.querySelectorAll('[name=ps]:not(:disabled)')].map(x => +x.value);
          if (a === 'ps-del') d.peserta.splice(+b.dataset.i, 1);
          if (a === 'np-open') d.newPs = emptyNp();
          if (a === 'np-cancel') d.newPs = null;
          if (a === 'np-save') npSave(d);
          qtnRedraw(d, form);
          if (a === 'np-open') form.elements.np_nama.focus();
        });
      },
      onSave: () => {
        const form = $('#modal-root form');
        const last = qtnSteps(d).length;
        if (d.step < last) { qtnGo(d, form, rec, d.step + 1); return false; }
        if (!qtnGo(d, form, rec, last)) return false;
        const data = qtnData(d);
        if (rec) { Object.assign(rec, data); toast(`Quotation ${rec.nomor} diperbarui`); }
        else {
          const k = Object.assign({ id: uid(), nomor: qtnNo(data.tanggal), status: 'draft' }, data);
          db.qtn.push(k); state.page = 1;
          toast(`Quotation ${k.nomor} tersimpan sebagai Draft`);
        }
        if (state.view !== 'qtn') state.view = 'qtn';
        state.qtnPj = d.pj; state.status = 'all';
      }
    });
  }

  // ---------- Quotation: PDF cetak (QTN-FR-10, QTN-FR-14 – QTN-FR-16; input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/) ----------
  // Satu PDF = Penawaran (input/Master - Buat Program/Template Penawaran Labmania.pdf) + Syarat & Ketentuan (input/Master - Buat Program/SYARAT & KETENTUAN ... .pdf)
  // + Formulir Konfirmasi per peserta (input/Master - Buat Program/Tamplate Formulir Konfirmasi Workshop.doc).
  const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const tglPanjang = iso => { if (!iso) return '—'; const [y, m, d] = iso.split('-'); return `${+d} ${BULAN[+m - 1]} ${y}`; };
  const rangePanjang = (a, b) => {
    if (!a) return '—';
    if (!b || a === b) return tglPanjang(a);
    const [y1, m1, d1] = a.split('-'), [y2, m2] = b.split('-');
    if (y1 === y2 && m1 === m2) return `${+d1}–${tglPanjang(b)}`;
    return y1 === y2 ? `${+d1} ${BULAN[+m1 - 1]} – ${tglPanjang(b)}` : `${tglPanjang(a)} – ${tglPanjang(b)}`;
  };
  const KOP = `<div class="pdf-kop"><div class="pdf-logo">L<i>A</i>B<small>M A N I A</small></div><div class="pdf-kop-t"><b>PT LabMania Indonesia Jaya</b>
    <span>Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143</span>
    <span>www.labmaniaindonesia.id · Customer Care: 0821-2429-3839</span></div></div>`;
  const pdfFoot = (kode, hal, berlaku) => `<div class="pdf-foot"><span>${kode}</span><span><i>${hal}</i></span><span>Berlaku ${berlaku}</span></div>`;
  const pdfPage = (inner, foot) => `<section class="pdf-page">${KOP}<div class="pdf-body">${inner}</div>${foot}</section>`;

  // 1. Penawaran — mengikuti Template Penawaran Labmania (FR/LMI-TR-01/01/Ed.Rev-1.1).
  // Judul kategori Keterangan di PDF (template: "Biaya sudah termasuk mendapatkan:").
  const KET_PDF = { syarat: 'Syarat keikutsertaan', termasuk: 'Biaya sudah termasuk mendapatkan', tidak: 'Biaya tidak termasuk', dokumen: 'Dokumen persyaratan' };
  function pagePenawaran(k) {
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), v = qtnNilai(k), s = salesOf(k) || {};
    const up = k.tujuan === 'company' ? k.up : kepadaOf(k);
    const ket = k.ket || ketKosong();
    const nama = k.peserta.map(r => (pesertaById(r.id) || {}).nama).filter(Boolean);
    return pdfPage(`
      <div class="pdf-2col">
        <div class="pdf-box"><div class="pdf-lbl">Kepada</div><b>${esc(kepadaOf(k))}</b><br>${esc(k.alamat || '—')}</div>
        <div class="pdf-box"><div class="pdf-h">Penawaran</div>
          <table class="pdf-kv"><tr><td>Nomor</td><td>: ${esc(k.nomor)}</td></tr><tr><td>Tanggal</td><td>: ${tglPanjang(k.tanggal)}</td></tr></table></div>
      </div>
      <table class="pdf-tbl"><thead><tr><th>No Catalog</th><th>Item</th><th>Deskripsi</th><th class="r">Kts.</th><th class="r">Harga</th><th class="r">Diskon</th><th class="r">Total Harga</th></tr></thead>
        <tbody><tr><td>${esc(pk.nomor)}</td><td>${esc(pk.judul)}</td>
          <td>${pj.durasi} hari<br>${rangePanjang(pj.tglMulai, pj.tglSelesai)}<br>${esc(pj.waktu || '—')}<br>${esc(pj.lokasi || '—')}${nama.length ? `<br><span class="pdf-mut">Peserta: ${esc(nama.join(', '))}</span>` : ''}</td>
          <td class="r">${v.n} peserta</td>${v.allin ? `<td class="r">${rp(v.sub)}<br><span class="pdf-mut">all-in</span></td><td class="r">${v.diskTot ? rp(v.diskTot) : '0'}</td>` : `<td class="r">${rp(v.harga)}</td><td class="r">${v.disk ? rp(v.disk) : '0'}</td>`}<td class="r">${rp(v.sub)}</td></tr></tbody></table>
      <div class="pdf-2col">
        <div><div class="pdf-lbl">Keterangan</div><div class="pdf-line">Up. Ibu/Bapak ${esc(up || '—')}</div>
          <div class="pdf-small">${KET_KAT.filter(([c]) => c !== 'klausul' && ket[c].length).map(([c]) => `${KET_PDF[c]}:<ul class="pdf-dash">${ket[c].map(t => `<li>${esc(t)}</li>`).join('')}</ul>`).join('')}
            ${ket.klausul.map(t => `<p>${esc(t)}</p>`).join('')}</div></div>
        <table class="pdf-sum"><tr><td>Sub Total</td><td>${rp(v.sub)}</td></tr><tr><td>Diskon</td><td>${v.diskTot ? `− ${rp(v.diskTot)}` : '0'}</td></tr>
          <tr><td>PPN (${PPN_RATE}%)</td><td>${rp(v.ppn)}</td></tr><tr><td>Biaya Lain-lain</td><td>${rp(v.lain)}</td></tr><tr class="tot"><td>Total</td><td>${rp(v.total)}</td></tr></table>
      </div>
      <div class="pdf-2col pdf-sign">
        <div>${tglPanjang(k.tanggal)}<br>${esc(roleOf(s) || 'Sales')}<div class="pdf-ttd"></div><b>${esc(s.nama || '—')}</b></div>
        <div>............., .................. 20.....<br>Disetujui oleh Pelanggan,<div class="pdf-ttd"></div>Nama :</div>
      </div>`, pdfFoot('FR/LMI-TR-01/01/Ed.Rev-1.1', 'Halaman 1 dari 1', '01/01/2025'));
  }

  // 2. Syarat & Ketentuan Kebijakan Layanan Pembelajaran (DP/LMI-TR-01/01/Ed.Rev-1.1) — teks tetap sesuai dokumen.
  const SNK = [
    ['Pembatalan Kepesertaan, Penarikan dan Pengembalian Uang', [
      'Pembatalan kepesertaan tidak bisa dilakukan jika pelanggan telah mengirimkan penawaran harga yang telah ditandatangani dan formulir konfirmasi keikutsertaan kegiatan yang telah diisi (khusus in house training tidak perlu formulir konfirmasi keikutsertaan, melainkan hanya dari penawaran harga yang sudah ditandatangan) dan/atau telah melakukan pembayaran.',
      'Jika pelanggan membatalkan pesanan layanan pembelajaran dan telah membayar, maka biaya layanan pembelajaran yang telah dibayarkan tidak bisa ditarik kembali.',
      'Jika pelanggan telah mengonfirmasi pesanan melalui penawaran harga yang telah ditandatangani dan formulir konfirmasi keikutsertaan kegiatan yang telah diisi (khusus in house training tidak perlu formulir konfirmasi keikutsertaan, melainkan hanya dari penawaran harga yang sudah ditandatangan) dan belum melakukan pembayaran, namun dibatalkan sepihak tanpa ada konfirmasi kepada penyelenggara setelah H-7 kegiatan layanan pembelajaran, maka akan dikenakan biaya pembatalan sebesar 20% dari total biaya layanan pembelajaran.',
      'Pengembalian uang bisa dilakukan jika LabMania membatalkan layanan pembelajaran atau mengubah jadwal layanan pembelajaran dikarenakan kuota peserta belum memenuhi atau ada hal teknis lainnya.',
      'Jika peserta tidak hadir pada pembelajaran atau membatalkan pesanan setelah batas waktu yang telah ditentukan, maka tidak ada pengembalian uang.']],
    ['Pembayaran', [
      'Apabila pembayaran pelanggan menggunakan pajak, maka pelanggan <b>wajib</b> membayarkan pajak (PPN &amp; PPh) sesuai dengan ketentuan perpajakan yang berlaku di Indonesia dan menyerahkan bukti potong/setor pajak tersebut ke LabMania dalam bentuk soft copy/hard copy.',
      'Pelanggan yang melakukan pembayaran menggunakan sistem <i>guarantee letter</i> (<i>pay later</i>) maka, pelanggan <b>wajib</b> melakukan pelunasan dengan batas waktu sesuai dengan yang tercantum di guarantee letter. Apabila pembayaran dilakukan melebihi batas waktu yang telah ditetapkan sebelumnya maka, akan dikenakan <b>denda</b> dengan ketentuan sebagai berikut:<br>- Terlambat 1-7 hari : 10% dari nilai project<br>- Terlambat &gt;7 hari : 30% dari nilai project',
      'Pelanggan yang telah melakukan pembayaran <b>wajib</b> memberikan bukti pembayaran kepada pihak LabMania dalam bentuk soft copy/hard copy.',
      'Pelanggan yang belum menyelesaikan administrasi pembayaran maka, pelanggan tersebut belum dapat mengikuti kegiatan LabMania selanjutnya dan belum dapat menerima sertifikat pelatihan tersebut.']],
    ['Penggantian Barang Rusak/Hilang', [
      'Selama kegiatan pembelajaran peserta <b>wajib</b> menjaga barang milik LabMania yang digunakan selama pembelajaran.',
      'Jika peserta selama pembelajaran merusak atau menghilangkan barang milik LabMania yang dapat menimbulkan kerugian, sehingga mengganggu/ menghambat kegiatan pembelajaran, maka akan dikenakan biaya ganti rugi terhadap barang yang rusak/ hilang.',
      'Setiap terjadi kerusakan atau kehilangan barang, peserta <b>wajib</b> mengisi berita acara dan menyetujui penggantian biaya ganti rugi sesuai dengan nilai dari barang tersebut. Penggantian dilakukan pada hari kegiatan tersebut berlangsung.',
      'Jika peserta tidak melakukan penggantian biaya ganti rugi sesuai dengan waktu yang telah ditentukan, maka sertifikat pelatihan tidak bisa diserahkan.']],
    ['Kerahasiaan', [
      'Peserta <b>wajib</b> menjaga kerahasiaan informasi milik LabMania termasuk modul pembelajaran.',
      'Peserta tidak diperkenankan menyalin atau menduplikasi, mengubah isi dan menyebarluaskan modul pembelajaran tanpa seizin dari pihak LabMania.']],
    ['Sertifikat Pelatihan', [
      'Sertifikat hanya diterbitkan jika peserta telah mengikuti kegiatan pembelajaran dan mengisi <i>post test</i> dengan syarat memenuhi kriteria penilaian minimal.',
      'Sertifikat diserahkan setelah peserta telah melakukan pembayaran layanan pembelajaran.',
      'Sertifikat dikirimkan maksimal 10 hari kerja (senin-jumat) setelah kegiatan, dengan catatan sudah dilakukan pembayaran (lunas).',
      'Jika sertifikat yang telah dicetak, nama yang tertera tidak sesuai karena kesalahan dari peserta dalam memberikan/ menuliskan nama peserta, maka revisi sertifikat diberikan dalam bentuk <i>softcopy</i>. Jika dibutuhkan <i>hardcopy</i>, maka dikenakan biaya sebesar Rp. 30.000,- (belum termasuk biaya pengiriman melalui ekspedisi). Ketentuan ini berlaku jika kegiatan di luar kantor LabMania.',
      'Jika peserta menghilangkan sertifikat pelatihan lebih dari 6 bulan, maka sertifikat dan materi pelatihan tidak bisa diberikan kembali. Jika masih dalam jangka waktu kurang dari 6 bulan, maka peserta masih bisa diberikan <i>softcopy</i> sertifikat dan materi pelatihan.']],
    ['Lisensi', [
      'Kegiatan yang dilakukan di tempat pelanggan, tanggung jawab lisensi terkait perangkat lunak jika digunakan, maka sepenuhnya tanggung jawab dari pelanggan.',
      'LabMania menjamin bahwa setiap perangkat lunak milik LabMania yang digunakan dalam pembelajaran telah terlisensi.']],
    ['Umpan Balik, Saran dan Keluhan', [
      'Setelah pelatihan, setiap peserta harus mengisi umpan balik kuisioner sebagai <i>feedback</i> kepada LabMania dalam peningkatan layanan pembelajaran.',
      'Saran dan keluhan dapat disampaikan melalui kuisioner atau melalui personil LabMania untuk ditindak lanjuti sesuai prosedur di LabMania.']]
  ];
  function pagesSnk() {
    const sec = (from, to) => SNK.slice(from, to).map(([h, list], i) => `<li><b>${h}</b><ul>${list.map(x => `<li>${x}</li>`).join('')}</ul></li>`).join('');
    const head = '<h3 class="pdf-title">SYARAT &amp; KETENTUAN<br>KEBIJAKAN LAYANAN PEMBELAJARAN</h3>';
    return pdfPage(`${head}<p>LabMania telah menetapkan syarat dan ketentuan yang berlaku yang harus dipatuhi oleh seluruh pelanggan (pembelajar/peserta maupun sponsor/instansi terkait yang mendaftarkan peserta). Berikut adalah beberapa syarat dan ketentuan terkait layanan pembelajaran (termasuk layanan pelatihan):</p>
        <ol class="pdf-snk">${sec(0, 3)}</ol>`, pdfFoot('DP/LMI-TR-01/01/Ed.Rev-1.1', '1 dari 2', '21/10/2024'))
      + pdfPage(`<ol class="pdf-snk" start="4">${sec(3, 7)}</ol>
        <p>Sebelum menyetujui layanan pembelajaran, pastikan Anda membaca kebijakan tersebut dengan cermat untuk memastikan bahwa Anda memahami syarat dan ketentuan yang berlaku.</p>
        <p class="pdf-pengesahan">Bekasi, 21 Oktober 2024<br>Disahkan oleh,<br>Operational Director<span class="pdf-ttd"></span>(Ivan Sarifudin)</p>`, pdfFoot('DP/LMI-TR-01/01/Ed.Rev-1.1', '2 dari 2', '21/10/2024'));
  }

  // 3. Formulir Konfirmasi Keikutsertaan Peserta (FR/LMI-TR-01/02/Ed.Rev-1.0) — satu halaman per peserta, mengikuti template formulir quotation (QTN-FR-16, FR-51).
  function pageKonfirmasi(k, p) {
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), s = salesOf(k) || {};
    const kf = k.kf || kfNew(kfDefaultFor(pj)), t = kfTplById(kf.tpl) || kfDefaultFor(pj);
    const isi = KF_FIELDS.filter(([key]) => kf.isian.includes(key)).map(([key, l]) => [l, kfValue(key, p)]).concat((kf.extra || []).map(l => [l, '']));
    const cat = { email: s.email || '—', telp: s.telp || '—', kegiatan: t.kegiatan, judul: pk.judul };
    return pdfPage(`<h3 class="pdf-title">Konfirmasi Keikutsertaan Peserta</h3>
      <table class="pdf-form">${isi.map(([l, v]) => `<tr><td>${esc(l)}</td><td>:</td><td>${esc(v || '')}</td></tr>`).join('')}</table>
      <p>Dengan ini menyatakan:</p>
      <ol class="pdf-small-ol"><li>${kfText(kf.pernyataan, pj)}</li>
        <li>Saya <b><u>telah membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania</li></ol>
      <div class="pdf-sign1">..........., ...................... 20.....<div class="pdf-ttd"></div>(..........................................................)<br><b>Jabatan:</b></div>
      <div class="pdf-small"><b><u>Catatan :</u></b><ul>${(t.catatan || []).map(c => `<li>${kfMark(kfFill(c, cat))}</li>`).join('')}</ul></div>`,
      pdfFoot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023'));
  }

  // Pratinjau PDF: halaman A4 berurutan + tombol cetak/simpan PDF (window.print, CSS @media print).
  const pdfWrap = (pages, info) => `<div class="pdf-tools"><span class="muted">${info}</span>
      <button type="button" class="btn btn-ghost sm" data-q="pdf-print">Cetak / Simpan PDF</button></div><div class="pdf-doc">${pages}</div>`;
  const pdfMount = form => form.addEventListener('click', e => { if (e.target.closest('[data-q="pdf-print"]')) window.print(); });

  // Kirim dan Cetak Quotation: Draft → Terkirim (menunggu deal); Terkirim/Deal cetak ulang; Batal hanya lihat.
  function qtnPrint(k) {
    const kirim = k.status === 'draft';
    const ps = k.peserta.map(r => pesertaById(r.id)).filter(Boolean);
    const pages = qtnPages(k, ps);
    openModal({
      title: k.status === 'batal' ? `Quotation ${k.nomor} (Batal)` : k.status === 'deal' ? 'Cetak Quotation' : 'Kirim dan Cetak Quotation', wide: 'x', info: k.status === 'batal',
      body: pdfWrap(pages, `${kirim ? '<span class="badge st-draft">Preview — belum dikirim</span> ' : ''}PDF ${esc(k.nomor)}: ${qtnPagesNote(k, ps)}`)
        + (kirim ? '<p class="hint">Setelah dikirim, quotation tidak dapat diedit dan berstatus <b>Terkirim</b> — menunggu peserta deal. Lalu tandai <b>Deal</b>, atau <b>Batalkan</b> bila tidak jadi.</p>' : ''),
      onMount: pdfMount, cancelLabel: 'Tutup',
      saveLabel: kirim || k.status === 'terkirim' ? 'Kirim & Cetak' : 'Cetak',
      onSave: () => {
        if (kirim) { k.status = 'terkirim'; toast(`Quotation ${k.nomor} terkirim — menunggu deal`); }
        else { logAct('Quotation', 'Quotation', 'Cetak', k.nomor, k.id, `Cetak ulang Quotation ${k.nomor}`); toast(`Quotation ${k.nomor} ${k.status === 'terkirim' ? 'dikirim ulang & ' : ''}dicetak (mockup)`); }
      }
    });
  }

  // Deal: centang peserta yang deal (default semua); yang tidak dicentang Batal (QTN-FR-11, menjaga [JPP-15]).
  function qtnDeal(k) {
    const pj = pjOfQtn(k);
    openModal({
      title: `Deal — ${k.nomor}`, wide: true,
      body: `<p class="hint top">${esc(kepadaOf(k))} · ${esc(paketOf(mpOf(pj)).judul)} (${esc(pj.nomor)}). Centang peserta yang <b>deal</b>; yang tidak dicentang menjadi <b>Batal</b> dan dapat dibuatkan quotation lain. Peserta Deal masuk sebagai peserta aktual (persiapan invoice)${isB2bQtn(k) ? `. Penjualan B2B: minimal ${B2B_MIN} peserta deal; invoice mengikuti skema harga (all-in = 1 invoice, per peserta = 1 invoice per peserta)` : " dan dapat dibuatkan <b>Confirmation Letter</b>"}.${q('Q-QTN-07', 'Deal untuk seluruh quotation, atau tetap memilih peserta yang deal? Usulan: centang, default semua.')}</p>
        <div class="ps-pick">${k.peserta.map(r => { const p = pesertaById(r.id) || {};
          return `<label class="ps-row"><input type="checkbox" name="deal" value="${r.id}" checked><span><b>${esc(p.nama)}</b> <span class="code">${esc(p.kode)}</span><br><span class="muted">${esc(pesertaCo(p))} · ${esc(p.telp)}</span></span></label>`; }).join('')}</div>
        <div class="field" id="deal-err"></div>`,
      saveLabel: 'Tandai Deal',
      onSave: () => {
        const on = [...$('#modal-root form').querySelectorAll('[name=deal]:checked')].map(x => +x.value);
        if (!on.length) { $('#deal-err').innerHTML = '<div class="err"><i>!</i>Centang minimal 1 peserta. Bila tidak ada yang deal, gunakan Batalkan.</div>'; return false; }
        // B2B: peserta Deal minimal 10 (usulan, Q-QTN-18).
        if (isB2bQtn(k) && on.length < B2B_MIN) { $('#deal-err').innerHTML = `<div class="err"><i>!</i>Penjualan B2B minimal ${B2B_MIN} peserta deal (baru ${on.length}).</div>`; return false; }
        k.peserta.forEach(r => { r.st = on.includes(r.id) ? 'deal' : 'batal'; });
        k.status = 'deal';
        toast(`${k.nomor} Deal — ${on.length} peserta deal${k.peserta.length - on.length ? `, ${k.peserta.length - on.length} batal` : ''}`);
      }
    });
  }

  function qtnCancel(k) {
    openModal({
      title: 'Batalkan Quotation',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(k.nomor)}</b> (${esc(kepadaOf(k))}) karena tidak jadi deal? Quotation Batal tidak dapat diubah; pesertanya dapat dibuatkan quotation baru.</p>`,
      saveLabel: 'Batalkan', danger: true,
      onSave: () => { k.status = 'batal'; k.peserta.forEach(r => { r.st = 'batal'; }); toast(`Quotation ${k.nomor} dibatalkan`); }
    });
  }

  // Confirmation Letter: Formulir Konfirmasi Keikutsertaan untuk peserta Deal (format dari Tamplate Formulir Konfirmasi Workshop; QTN-FR-13).
  function qtnConfirm(k) {
    const ps = dealRows(k).map(r => pesertaById(r.id)).filter(Boolean);
    openModal({
      title: `Confirmation Letter — ${k.nomor}`, wide: 'x',
      body: pdfWrap(ps.map(p => pageKonfirmasi(k, p)).join(''), `Formulir Konfirmasi Keikutsertaan untuk ${ps.length} peserta Deal${q('Q-QTN-05', 'Confirmation Letter memakai format Formulir Konfirmasi Workshop (usulan). Masih perlu dokumen terpisah setelah Deal, atau cukup bagian konfirmasi di PDF quotation?')}`),
      onMount: pdfMount,
      saveLabel: 'Cetak',
      onSave: () => { logAct('Quotation', 'Quotation', 'Cetak', k.nomor, k.id, `Cetak Confirmation Letter ${k.nomor}`); toast(`Confirmation Letter ${k.nomor} dicetak (mockup)`); }
    });
  }

