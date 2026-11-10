/* Labmania mockup — Invoice.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Invoice (IVC-FR-01 – IVC-FR-14; input/Invoice & Pengajuan/2026-10-30-invoice-consulting/) ----------
  // Quotation Deal → Invoice. Perhitungan & jurnal mengikuti Invoice Consulting.xlsx (sheet Konsep, Definisi, Business Process)
  // dan Invoice Consulting.html: Penjualan → DPP (× 11/12) → PPN (DPP × 12%) → Total Tagihan; jurnal per perlakuan PPN (PKP, WAPU, Kawasan Berikat).
  // Tanpa termin (usulan, Q-IVC-02): satu unit tagih = seluruh quotation, atau satu peserta bila B2B dipecah (QTN [B2B-5], [BAD-2]).
  const PERLAKUAN = { PKP: 'PKP', WAPU: 'WAPU', KB: 'Kawasan Berikat', NON: 'Tanpa PPN' };
  const IVC_CO = 'LMI';
  const COA_FIX = {
    piutang: ['1102-01-01', 'Piutang Dagang Usaha'], ppnDn: ['2104-01-07', 'Hutang PPN DN'],
    umWapu: ['1106-01-XX', 'Uang Muka PPN WAPU'], htWapu: ['2104-01-XX', 'Hutang PPN WAPU']
  };
  // COA Penjualan dari Invoice Consulting.html; nomor masih 0000-00-00 (Q-IVC-14).
  const COA_SALES = ['Penjualan Konsultasi', 'Penjualan In House Training', 'Penjualan Public Training & Seminar', 'Penjualan HSE Pengujian'];
  const COA_SALES_NO = '0000-00-00';
  const coaSalesDefault = k => isB2bQtn(k) ? 'Penjualan In House Training' : 'Penjualan Public Training & Seminar';
  const IVC_ST = { uninvoiced: 'Uninvoiced', invoiced: 'Invoiced' };
  const QIVC = (n, t) => q(`Q-IVC-${String(n).padStart(2, '0')}`, t);
  db.inv = [];
  // Nomor invoice otomatis LMI/INV/MM/yy-zzz (input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/).
  const ivcNo = (iso, peek = false) => lmiNo('INV', iso, peek);
  const invOfQtn = k => db.inv.filter(i => i.qtn === k.id);
  // Nomor Program (nomor Penjualan Program) di setiap item invoice agar mudah ditelusuri (input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/).
  const progNo = k => (pjOfQtn(k) || {}).nomor || '—';
  const progCell = k => `<span class="code">${esc(progNo(k))}</span>`;
  const ivcCara = k => { const l = invOfQtn(k); return l.length ? l[0].cara : null; };
  // Invoice B2B mengikuti quotation (input/Invoice & Pengajuan/2026-10-30-invoice-b2b-ikut-skema/): all-in ke company = 1 invoice;
  // per peserta = quotation pecah per peserta = 1 invoice per peserta Deal. Public selalu 1 invoice per quotation.
  const ivcCaraOf = k => isB2bQtn(k) && !isAllin(k) ? 'peserta' : 'satu';
  const ivcDonePs = k => invOfQtn(k).flatMap(i => i.peserta);
  const ivcSisaPs = k => { const done = ivcDonePs(k); return dealRows(k).filter(r => !done.includes(r.id)); };
  // Dua status saja (input/Invoice & Pengajuan/2026-10-30-tab-invoiced-filter-jenis/): masih ada peserta Deal yang belum ditagih = Uninvoiced.
  const ivcStatus = k => ivcSisaPs(k).length ? 'uninvoiced' : 'invoiced';
  const ivcBadge = s => `<span class="badge st-ivc-${s}">${IVC_ST[s]}</span>`;
  // Perlakuan PPN: dari Master Company (usulan, Q-IVC-05); Personal = PKP; quotation tanpa PPN = Tanpa PPN (usulan, Q-IVC-07).
  const ivcPerlakuan = k => !k.ppn ? 'NON' : k.tujuan === 'company' ? ((db.company.find(c => c.id === k.company) || {}).ppnPerlakuan || 'PKP') : 'PKP';
  // Nilai unit tagih (IVC-FR-09): satu invoice = nilai setelah diskon peserta Deal; per peserta = bagi rata, sisa pembulatan di peserta terakhir.
  function ivcNilaiUnit(k, cara, psId) {
    const v = qtnNilai(k, dealRows(k));
    if (cara !== 'peserta') return { penjualan: v.dpp, lain: v.lain, ps: dealRows(k).map(r => r.id) };
    const n = dealRows(k).length || 1, sisa = ivcSisaPs(k);
    if (sisa.length === 1 && sisa[0].id === psId) {
      const sudah = invOfQtn(k).reduce((t, i) => ({ p: t.p + i.penjualan, l: t.l + i.lain }), { p: 0, l: 0 });
      return { penjualan: v.dpp - sudah.p, lain: v.lain - sudah.l, ps: [psId] };
    }
    return { penjualan: Math.floor(v.dpp / n), lain: Math.floor(v.lain / n), ps: [psId] };
  }
  // B2B per peserta dipecah otomatis (input/Invoice & Pengajuan/2026-11-03-b2b-per-peserta-pecah-otomatis/):
  // satu kali Save = satu invoice per peserta Deal yang belum ditagih, masing-masing bernomor sendiri.
  function ivcSplit(k) {
    const v = qtnNilai(k, dealRows(k)), n = dealRows(k).length || 1, sisa = ivcSisaPs(k);
    let accP = invOfQtn(k).reduce((t, i) => t + i.penjualan, 0), accL = invOfQtn(k).reduce((t, i) => t + i.lain, 0);
    return sisa.map((r, idx) => {
      const last = idx === sisa.length - 1;
      const p = last ? v.dpp - accP : Math.floor(v.dpp / n), l = last ? v.lain - accL : Math.floor(v.lain / n);
      accP += p; accL += l;
      return { penjualan: p, lain: l, ps: [r.id] };
    });
  }
  // Pratinjau nomor invoice ke-(off+1) dari pecahan, tanpa memakai nomor urut.
  const ivcNoAt = (iso, off) => { const s = ivcNo(iso, true); return s.slice(0, -3) + String(+s.slice(-3) + off).padStart(3, '0'); };
  // Perhitungan (IVC-FR-08): pembulatan ke rupiah di setiap tahap (Q-IVC-08).
  function ivcHitung(penjualan, lain, pl) {
    const dpp = pl === 'NON' ? 0 : Math.round(penjualan * 11 / 12), ppn = pl === 'NON' ? 0 : Math.round(dpp * 0.12);
    const total = penjualan + ppn + lain;
    return { penjualan, dpp, ppn, lain, total, piutang: pl === 'PKP' ? total : penjualan + lain };
  }
  // Jurnal per perlakuan PPN (Invoice Consulting.xlsx sheet Business Process; Invoice Consulting.html).
  function ivcJurnal(h, pl, coaSales, nomor, k, tanggal) {
    const sales = coaSales ? [COA_SALES_NO, coaSales] : ['', ''], jual = h.penjualan + h.lain;
    const R = (c, d, cr, isSales) => ({ coa: c[0], nama: c[1], d, c: cr, sales: !!isSales });
    const rows = pl === 'PKP' ? [R(COA_FIX.piutang, h.total, 0), R(COA_FIX.ppnDn, 0, h.ppn), R(sales, 0, jual, true)]
      : pl === 'WAPU' ? [R(COA_FIX.umWapu, h.ppn, 0), R(COA_FIX.piutang, jual, 0), R(COA_FIX.htWapu, 0, h.ppn), R(sales, 0, jual, true)]
        : [R(COA_FIX.piutang, jual, 0), R(sales, 0, jual, true)];
    return rows.filter(r => r.d || r.c).map(r => Object.assign(r, { tanggal, co: IVC_CO, prog: progNo(k),
      desc: r.nama ? [r.nama, nomor, k.nomor].concat(pl === 'KB' ? ['Kawasan Berikat'] : []).join(' - ') : '—' }));
  }
  const jrTable = (rows, foot = true) => {
    const d = rows.reduce((t, r) => t + r.d, 0), c = rows.reduce((t, r) => t + r.c, 0);
    return `<div class="table-wrap"><table class="table ivc-jr"><thead><tr><th>Tanggal Jurnal</th><th>Company</th><th>No. Program</th><th>COA</th><th>Nama COA</th><th>Deskripsi</th><th class="money">Debit</th><th class="money">Credit</th></tr></thead>
      <tbody>${rows.length ? rows.map(r => `<tr><td class="nowrap">${r.tanggal.split('-').reverse().join('/')}</td><td>${r.co}</td><td class="nowrap"><span class="code">${esc(r.prog || '—')}</span></td><td class="nowrap"><span class="code">${esc(r.coa || '—')}</span></td>
        <td>${r.sales && r.pick !== undefined ? r.pick : esc(r.nama || '—')}</td><td>${esc(r.desc)}</td><td class="money">${r.d ? fmtRibu(r.d) : '0'}</td><td class="money">${r.c ? fmtRibu(r.c) : '0'}</td></tr>`).join('')
        : '<tr><td colspan="8" class="empty">Belum ada jurnal.</td></tr>'}</tbody>
      ${foot ? `<tfoot><tr><td colspan="6"><b>Balancing</b> ${d === c ? '<span class="pos">· balance</span>' : '<span class="neg">· tidak balance</span>'}</td><td class="money"><b>${fmtRibu(d) || 0}</b></td><td class="money"><b>${fmtRibu(c) || 0}</b></td></tr></tfoot>` : ''}</table></div>`;
  };
  const calcCard = (h, pl) => {
    const row = (t, f, v, cls = '') => `<div class="calc-row ${cls}"><div><b>${t}</b><small>${f}</small></div><span>${v}</span></div>`;
    return `<div class="calc-card">${row('Penjualan', 'Nilai unit tagih setelah diskon', rp(h.penjualan))}
      ${row('DPP', pl === 'NON' ? 'Quotation tanpa PPN' : 'Penjualan × 11/12', rp(h.dpp))}
      ${row('PPN', pl === 'NON' ? 'Quotation tanpa PPN' : 'DPP × 12%', rp(h.ppn))}
      ${h.lain ? row('Biaya Lain-lain' + QIVC(9, 'Biaya Lain-lain quotation: usulan masuk invoice tanpa PPN dan dikreditkan ke COA Penjualan. Atau COA lain / ikut PPN?'), 'Dari quotation, tanpa PPN', rp(h.lain)) : ''}
      ${row('Total Tagihan', `Penjualan + PPN${h.lain ? ' + Biaya Lain-lain' : ''}`, rp(h.total), 'total')}
      ${row('Piutang dicatat', pl === 'PKP' ? 'PKP: Total Tagihan' : pl === 'WAPU' ? 'WAPU: PPN disetor sendiri oleh customer' : pl === 'KB' ? 'Kawasan Berikat: tidak bayar PPN' : 'Tanpa PPN', rp(h.piutang), 'sub')}</div>`;
  };

  // Data contoh: program Public dengan beberapa quotation Deal — tiap quotation Deal menjadi invoice sendiri.
  {
    const pjPub = db.quot.find(x => x.jenis === 'public' && x.status === 'final'), co1 = db.company[0], sales = db.user[1].id;
    const ps = nama => db.peserta.find(p => p.nama === nama).id;
    [{ tanggal: '2026-10-29', tujuan: 'company', company: co1.id, up: co1.picNama, alamat: `${co1.alamat}, ${co1.kota}`, sumber: 'Sales', peserta: ['H', 'I'] },
      { tanggal: '2026-10-30', tujuan: 'personal', company: '', up: '', alamat: 'Kota Bekasi, Jawa Barat', sumber: 'Website', peserta: ['J'] }]
      .forEach(o => { const k = Object.assign({ id: uid(), nomor: qtnNo(o.tanggal), pj: pjPub.id, sales, ppn: true, diskon: 0, lain: 0, status: 'deal', berlaku: addDays(o.tanggal, 30) }, o,
        { peserta: o.peserta.map(x => ({ id: ps(`Contoh Peserta ${x}`), st: 'deal' })) }); k.ket = ketDefault('public'); k.kf = kfNew(kfDefaultFor(pjPub)); db.qtn.push(k); });
  }
  // Data contoh: program B2B dengan quotation skema per peserta (Deal) — ditagih satu invoice per peserta.
  {
    const co1 = db.company[0];
    const pj = seedQT(Object.assign(fromMP(mp2, 'b2b'), { status: 'final', company: co1.id, hargaJual: 1500000, targetPeserta: 10, tglMulai: '2026-12-08', tglSelesai: '2026-12-09', lokasi: 'In house — Bekasi' }));
    const ids = db.peserta.filter(p => p.company === co1.id).slice(0, 10).map(p => p.id);
    const k = { id: uid(), nomor: qtnNo('2026-10-30'), pj: pj.id, sales: db.user[1].id, sumber: 'Sales', ppn: true, diskon: 100000, lain: 0, tanggal: '2026-10-30', berlaku: '2026-11-29',
      tujuan: 'company', company: co1.id, up: co1.picNama, alamat: `${co1.alamat}, ${co1.kota}`, status: 'deal', skema: 'peserta', peserta: ids.map(id => ({ id, st: 'deal' })) };
    k.ket = ketDefault('b2b'); k.kf = kfNew(kfDefaultFor(pj)); db.qtn.push(k);
  }
  // Data contoh: quotation Public Personal yang sudah Deal sudah ditagih.
  {
    const k = db.qtn.find(x => x.status === 'deal' && x.tujuan === 'personal');
    if (k) {
      const tgl = '2026-10-29', pl = ivcPerlakuan(k), u = ivcNilaiUnit(k, 'satu'), h = ivcHitung(u.penjualan, u.lain, pl), nomor = ivcNo(tgl), coa = coaSalesDefault(k);
      db.inv.push(Object.assign({ id: uid(), nomor, tanggal: tgl, qtn: k.id, prog: progNo(k), jenis: pjOfQtn(k).jenis, cara: 'satu', peserta: u.ps, perlakuan: pl, noFaktur: '04002600000101', coaPenjualan: coa, setor: null, status: 'invoiced',
        jurnal: ivcJurnal(h, pl, coa, nomor, k, tgl) }, h));
    }
  }

  // Daftar Invoicing (IVC-FR-02, IVC-FR-03): tab Uninvoiced (quotation Deal yang masih harus ditagih) dan tab Invoiced
  // (satu baris per invoice); Public / B2B lewat filter (input/Invoice & Pengajuan/2026-10-30-tab-invoiced-filter-jenis/).
  state.ivcYear = 'all'; state.ivcTab = 'uninvoiced'; state.ivcProg = 'all';
  // To be: hanya Sales Order berstatus Siap invoice (docs-to-be/fr-nfr-sales-order.md SO-FR-08).
  const ivcUnRows = () => db.qtn.filter(k => k.status === 'deal' && ivcStatus(k) === 'uninvoiced' && soSiapOk(k));
  const soCell = k => { const s = soOfQtn(k); return s ? `<span class="code">${esc(s.nomor)}</span>` : '—'; };
  const jenisOk = j => state.jenis === 'all' || state.jenis === j;
  const viewIvc = () => {
    const un = ivcUnRows(), inv = db.inv;
    const base = state.ivcTab === 'uninvoiced' ? un.map(k => [k.tanggal, pjOfQtn(k).jenis, progNo(k)]) : inv.map(i => [i.tanggal, i.jenis, i.prog]);
    const years = [...new Set(base.map(b => b[0].slice(0, 4)))].sort().reverse();
    const progs = [...new Set(base.filter(b => jenisOk(b[1])).map(b => b[2]))].sort();
    if (state.ivcProg !== 'all' && !progs.includes(state.ivcProg)) state.ivcProg = 'all';
    const tabs = [['uninvoiced', 'Uninvoiced', un.length], ['invoiced', 'Invoiced', inv.length]].map(([t, l, n]) =>
      `<button class="tab ${state.ivcTab === t ? 'active' : ''}" data-act="ivc-tab" data-tab="${t}">${l}<span class="nav-count">${n}</span></button>`).join('');
    return `${pageHead('Invoicing', 'Tab <b>Uninvoiced</b>: <b>Sales Order</b> yang sudah ditandai <b>Siap invoice</b> dan belum ditagih (to be: invoice dibuat dari Sales Order, bukan langsung dari quotation). Tab <b>Invoiced</b>: invoice yang sudah dibuat, satu baris per invoice. Satu quotation Deal = satu invoice, tidak digabung per program; B2B per peserta = satu invoice per peserta. Saring Public / B2B dengan filter jenis; kolom <b>No. Program</b> untuk menelusuri.', 'IVC-FR-01 – IVC-FR-03',
      '<button class="btn btn-ghost" data-act="ivc-excel">Excel</button>')}
      <div class="card"><div class="tabs">${tabs}</div>${toolbar('Cari nomor program, invoice, quotation, customer, sales…', false,
        selectFilter('fjenis', 'Filter jenis program', state.jenis, [['all', 'Public & B2B'], ['public', 'Program Public'], ['b2b', 'Program B2B']])
        + selectFilter('fprog', 'Filter program', state.ivcProg, [['all', 'Semua program'], ...progs.map(p => [p, p])])
        + selectFilter('fyear', 'Filter tahun', state.ivcYear, [['all', 'Semua tahun'], ...years.map(y => [y, y])]))}
      <div id="list">${listIvc()}</div></div>`;
  };
  const progOk = p => state.ivcProg === 'all' || p === state.ivcProg;
  const yearOk = iso => state.ivcYear === 'all' || iso.startsWith(state.ivcYear);
  const progCol = { label: 'No. Program', cls: 'nowrap', render: r => { const pj = pjOfQtn(r.qtn ? db.qtn.find(x => x.id === r.qtn) : r);
    return `${jenisTag(pj.jenis)} <span class="code">${esc(pj.nomor)}</span><br><span class="muted">${rangePanjang(pj.tglMulai, pj.tglSelesai)}</span>`; } };
  function listIvc() {
    if (state.ivcTab === 'invoiced') return listIvcDone();
    const rows = ivcUnRows().filter(k => jenisOk(pjOfQtn(k).jenis) && progOk(progNo(k)) && yearOk(k.tanggal))
      .sort((a, b) => progNo(a).localeCompare(progNo(b)) || a.id - b.id)
      .filter(k => { const pj = pjOfQtn(k); return matches([k.nomor, kepadaOf(k), pj.nomor, paketOf(mpOf(pj)).judul, (salesOf(k) || {}).nama]); });
    return listBlock(rows, [
      NO_COL, progCol,
      { label: 'No. Sales Order', cls: 'nowrap', render: k => `${soCell(k)}<br><span class="muted">${tglPanjang((soOfQtn(k) || k).tanggal)}</span>` },
      { label: 'No. Quotation', cls: 'nowrap', render: k => `<span class="code">${esc(k.nomor)}</span><br><span class="muted">${tglPanjang(k.tanggal)}</span>` },
      { label: 'Customer', render: k => `${tujuanTag(k.tujuan)} ${esc(kepadaOf(k))}<br><span class="muted">${PERLAKUAN[ivcPerlakuan(k)]}</span>` },
      { label: 'Program', render: k => { const pj = pjOfQtn(k); return `${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${esc(pj.lokasi || '')}</span>`; } },
      { label: 'Sales', render: k => esc((salesOf(k) || {}).nama || '—') },
      { label: 'Cara tagih', render: k => ivcCaraOf(k) === 'peserta'
        ? `Per peserta<br><span class="muted">${ivcSisaPs(k).length} invoice (dipecah otomatis)</span>`
        : `1 invoice<br><span class="muted">${dealRows(k).length} peserta deal${isAllin(k) ? ' · all-in' : ''}</span>` },
      { label: 'Nilai quotation', cls: 'money', render: k => rp(qtnNilai(k, dealRows(k)).total) },
      { label: 'Status', cls: 'nowrap', render: () => ivcBadge('uninvoiced') },
      { label: 'Action', cls: 'act', render: k => gearMenu(k, [['ivc-add', 'Add Invoicing']]) }
    ]);
  }
  function listIvcDone() {
    const qOf = i => db.qtn.find(x => x.id === i.qtn);
    const rows = db.inv.filter(i => jenisOk(i.jenis) && progOk(i.prog) && yearOk(i.tanggal))
      .sort((a, b) => a.prog.localeCompare(b.prog) || a.id - b.id)
      .filter(i => { const k = qOf(i); return matches([i.nomor, i.prog, k.nomor, kepadaOf(k), paketOf(mpOf(pjOfQtn(k))).judul, (salesOf(k) || {}).nama, i.noFaktur]); });
    return listBlock(rows, [
      NO_COL, progCol,
      { label: 'No. Invoice', cls: 'nowrap', render: i => `<button class="btn btn-ghost sm" data-act="ivc-view" data-id="${i.id}">${esc(i.nomor)}</button><br><span class="muted">${tglPanjang(i.tanggal)}</span>` },
      { label: 'No. SO / Quotation', cls: 'nowrap', render: i => `${soCell(qOf(i))}<br><span class="muted">${esc(qOf(i).nomor)}</span>` },
      { label: 'Customer', render: i => { const k = qOf(i), ps = i.cara === 'peserta' ? (pesertaById(i.peserta[0]) || {}).nama : '';
        return `${tujuanTag(k.tujuan)} ${esc(kepadaOf(k))}${ps ? `<br><span class="muted">Peserta: ${esc(ps)}</span>` : `<br><span class="muted">${i.peserta.length} peserta</span>`}`; } },
      { label: 'No. Faktur', render: i => esc(i.noFaktur || '—') },
      { label: 'Perlakuan PPN', render: i => PERLAKUAN[i.perlakuan] + (i.perlakuan === 'WAPU' ? `<br><span class="muted">${i.setor ? `Bukti setor ${tglPanjang(i.setor.tanggal)}` : 'Bukti setor belum'}</span>` : '') },
      { label: 'Total Tagihan', cls: 'money', render: i => `${rp(i.total)}<br><span class="muted">piutang ${rp(i.piutang)}</span>` },
      { label: 'Status', cls: 'nowrap', render: () => ivcBadge('invoiced') },
      { label: 'Action', cls: 'act', render: i => gearMenu(i, [['ivc-view', 'Lihat Invoice'], ['ivc-print', 'Print Invoice'], ...(i.perlakuan === 'WAPU' && !i.setor ? [['ivc-setor', 'Terima Bukti Setor WAPU']] : [])]) }
    ]);
  }

  // Halaman Add Invoicing (IVC-FR-04 – IVC-FR-11) — halaman penuh, bukan pop up (input/Invoice & Pengajuan/2026-10-30-add-invoice-halaman-penuh/),
  // mengikuti halaman "Add Invoicing" di Invoice Consulting.html. Urutan bagian: Data Invoice → Cara Tagih (B2B) → Rincian → Perhitungan → Jurnal.
  function ivcForm(k, from) {
    if (ivcStatus(k) === 'invoiced') { toast('Semua peserta quotation ini sudah ditagih', 'warn'); return; }
    if (!soSiapOk(k)) { toast(`Sales Order ${esc((soOfQtn(k) || {}).nomor || '')} belum ditandai siap dibuatkan invoice`, 'warn'); return; }
    // Cara tagih tidak dipilih: mengikuti skema harga quotation (ivcCaraOf).
    state.ivp = { qtn: k.id, from: from || 'ivc', d: { tanggal: todayIso, cara: ivcCaraOf(k), faktur: {}, pl: ivcPerlakuan(k), noFaktur: '', coa: coaSalesDefault(k) } };
    state.view = 'ivcForm';
    render(); window.scrollTo(0, 0);
  }
  // Potongan halaman; dipakai ulang saat isian berubah (redraw sebagian agar fokus tidak hilang).
  function ivcParts() {
    const k = db.qtn.find(x => x.id === state.ivp.qtn), d = state.ivp.d;
    const b2b = isB2bQtn(k), pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), v = qtnNilai(k, dealRows(k));
    const perPs = d.cara === 'peserta';
    const unit = () => ivcNilaiUnit(k, d.cara);
    const sec = (n, title, hint, body, id = '') => `<section class="card pad ivc-sec" ${id ? `id="${id}"` : ''}><p class="ivc-sec-h"><span class="ivc-no">${n}</span>${title}${hint ? ` <span class="muted">${hint}</span>` : ''}</p>${body}</section>`;
    const hdr = () => sec(1, 'Data Invoice', 'tujuan tagihan dari quotation, nomor dari sistem', `<div class="qtn-grid ivc-head"><div>
        ${field('Kepada', `<div class="ro">${esc(kepadaOf(k))} ${tujuanTag(k.tujuan)}</div>`)}
        ${field('Alamat', `<div class="ro">${esc(k.alamat || '—')}</div>`)}
        ${field('Ditujukan', `<div class="ro">${esc(k.tujuan === 'company' ? k.up || '—' : kepadaOf(k))}</div>`)}
        ${field('Perlakuan PPN' + QIVC(5, 'Perlakuan PPN dari Master Company (usulan); Personal = PKP; quotation tanpa PPN = Tanpa PPN. Di aplikasi terkunci; di mockup dapat diganti untuk mencoba jurnal.'),
          `<select class="input" name="pl">${Object.keys(PERLAKUAN).map(p => `<option value="${p}" ${p === d.pl ? 'selected' : ''}>${PERLAKUAN[p]}</option>`).join('')}</select><span class="hint">Default ${PERLAKUAN[ivcPerlakuan(k)]} (${k.ppn ? (k.tujuan === 'company' ? 'Master Company' : 'Personal') : 'quotation tanpa PPN'}). Mockup: dapat diganti untuk mencoba jurnal.</span>`)}
      </div><div>
        ${field('Tanggal Invoice' + req, `<input class="input" type="date" name="tanggal" value="${d.tanggal}">`)}
        ${field('Nomor Invoice' + QIVC(4, 'Format nomor LMI/INV/MM/yy-zzz (terjawab). Masih usulan: MM/yy dari Tanggal Invoice dan nomor urut di-reset tiap bulan.'), perPs
          ? (() => { const m = ivcSisaPs(k).length; return `<input class="input" value="${esc(ivcNoAt(d.tanggal, 0))}${m > 1 ? ` s.d. ${esc(ivcNoAt(d.tanggal, m - 1))}` : ''}" readonly><span class="hint">${m} nomor berbeda, satu per peserta. Otomatis oleh sistem saat Save</span>`; })()
          : `<input class="input" value="${esc(ivcNo(d.tanggal, true))}" readonly><span class="hint">Otomatis oleh sistem saat Save</span>`)}
        ${d.pl === 'NON' ? field('Nomor Faktur', '<div class="ro muted">Tidak perlu — tanpa PPN</div>') : perPs ? field('Nomor Faktur' + req, '<div class="ro muted">Diisi per invoice di bagian 2 (Cara Tagih)</div>')
          : field('Nomor Faktur' + req, `<input class="input" name="noFaktur" value="${esc(d.noFaktur)}" placeholder="Nomor Faktur">`)}
      </div></div>`);
    const cara = () => b2b ? sec(2, 'Cara Tagih', 'mengikuti skema harga quotation', `<p class="hint top">${d.cara === 'peserta'
        ? `Quotation <b>per peserta</b>: invoice <b>dipecah otomatis</b>, satu invoice untuk setiap peserta Deal dengan nomor invoice sendiri. Save akan membuat <b>${ivcSisaPs(k).length} invoice</b> sekaligus.`
        : `Quotation <b>all-in ke company</b>: satu invoice untuk seluruh ${dealRows(k).length} peserta Deal.`}</p>
      ${perPs ? (() => { const sp = ivcSplit(k);
        return `<div class="table-wrap"><table class="table"><thead><tr><th>No.</th><th>Peserta</th><th>Nomor Invoice</th><th class="money">Total Tagihan</th><th>Nomor Faktur${d.pl === 'NON' ? '' : req}</th></tr></thead><tbody>
          ${sp.map((u, i) => { const p = pesertaById(u.ps[0]) || {}, h = ivcHitung(u.penjualan, u.lain, d.pl);
            return `<tr><td>${i + 1}</td><td>${esc(p.nama)}<br><span class="muted">${esc(p.kode || '')}</span></td><td class="nowrap"><span class="code">${esc(ivcNoAt(d.tanggal, i))}</span></td><td class="money">${rp(h.total)}</td>
              <td>${d.pl === 'NON' ? '<span class="muted">Tidak perlu — tanpa PPN</span>' : `<div class="field"><input class="input sm" name="fk-${u.ps[0]}" value="${esc(d.faktur[u.ps[0]] || '')}" placeholder="Nomor Faktur"></div>`}</td></tr>`; }).join('')}</tbody></table></div>
        <span class="hint">Nilai per peserta = harga − diskon per peserta${v.lain ? `; Biaya Lain-lain dibagi rata ÷ ${dealRows(k).length}, sisa pembulatan di peserta terakhir` : ''}. Nomor Faktur diisi per invoice; Tanggal dan COA Penjualan sama untuk semua invoice.</span>`; })() : ''}`) : '';
    const n0 = b2b ? 2 : 1;
    const coaPick = jr => jr.filter(r => r.sales).forEach(r => { r.pick = `<select class="input sm" name="coa"><option value="">— Pilih COA Penjualan —</option>${COA_SALES.map(c => `<option ${c === d.coa ? 'selected' : ''}>${esc(c)}</option>`).join('')}</select>`; });
    // B2B per peserta: rincian, perhitungan, dan jurnal per invoice pecahan.
    const dynPs = () => {
      const sp = ivcSplit(k).map((u, i) => { const h = ivcHitung(u.penjualan, u.lain, d.pl), nomor = ivcNoAt(d.tanggal, i);
        return { u, h, nomor, nama: (pesertaById(u.ps[0]) || {}).nama || '—', jr: ivcJurnal(h, d.pl, d.coa, nomor, k, d.tanggal) }; });
      const sum = f => sp.reduce((t, x) => t + x.h[f], 0);
      const jr = sp.flatMap(x => x.jr); coaPick(jr);
      return sec(n0 + 1, 'Rincian Tagihan', 'satu baris = satu invoice', `<div class="table-wrap"><table class="table"><thead><tr><th>No.</th><th>No. Invoice</th><th>No. Program</th><th>Item</th><th>Peserta</th><th class="money">Harga / peserta</th><th class="money">Diskon</th><th class="money">Nominal</th></tr></thead>
        <tbody>${sp.map((x, i) => `<tr><td>${i + 1}</td><td class="nowrap"><span class="code">${esc(x.nomor)}</span></td><td class="nowrap"><span class="code">${esc(pj.nomor)}</span></td><td>${esc(pk.judul)}<br><span class="muted">${esc(pk.nomor)}</span></td>
          <td>${esc(x.nama)}</td><td class="money">${rp(v.harga)}</td><td class="money">${rp(v.disk)}</td><td class="money"><b>${rp(x.u.penjualan)}</b></td></tr>`).join('')}</tbody></table></div>`)
        + sec(n0 + 2, 'Perhitungan Invoice' + QIVC(8, 'PPN quotation = 11% × DPP, PPN invoice = (Penjualan × 11/12) × 12%. Nilainya sama, tetapi pembulatan bisa beda Rp 1. Pembulatan per tahap perlu dikonfirmasi ke tim pajak.'), 'per invoice: Penjualan → DPP → PPN → Total Tagihan',
          `<div class="table-wrap"><table class="table"><thead><tr><th>No. Invoice</th><th>Peserta</th><th class="money">Penjualan</th><th class="money">DPP</th><th class="money">PPN</th>${v.lain ? '<th class="money">Biaya Lain-lain</th>' : ''}<th class="money">Total Tagihan</th><th class="money">Piutang</th></tr></thead>
          <tbody>${sp.map(x => `<tr><td class="nowrap"><span class="code">${esc(x.nomor)}</span></td><td>${esc(x.nama)}</td><td class="money">${rp(x.h.penjualan)}</td><td class="money">${rp(x.h.dpp)}</td><td class="money">${rp(x.h.ppn)}</td>${v.lain ? `<td class="money">${rp(x.h.lain)}</td>` : ''}<td class="money"><b>${rp(x.h.total)}</b></td><td class="money">${rp(x.h.piutang)}</td></tr>`).join('')}</tbody>
          <tfoot><tr><td colspan="2"><b>Total ${sp.length} invoice</b></td><td class="money"><b>${rp(sum('penjualan'))}</b></td><td class="money"><b>${rp(sum('dpp'))}</b></td><td class="money"><b>${rp(sum('ppn'))}</b></td>${v.lain ? `<td class="money"><b>${rp(sum('lain'))}</b></td>` : ''}<td class="money"><b>${rp(sum('total'))}</b></td><td class="money"><b>${rp(sum('piutang'))}</b></td></tr></tfoot></table></div>`)
        + sec(n0 + 3, 'Jurnal' + QIVC(14, 'Kode COA dari file acuan: 1102-01-01, 2104-01-07, 1106-01-XX, 2104-01-XX; COA Penjualan 0000-00-00. Kode final Labmania?'), `jurnal ${sp.length} invoice (deskripsi memuat nomor invoice masing-masing); pilih COA Penjualan; debit dan credit harus balance`, `${jrTable(jr)}<div class="field" id="ivc-err"></div>`);
    };
    const dyn = () => {
      if (perPs) return dynPs();
      const u = unit(), h = ivcHitung(u.penjualan, u.lain, d.pl), nomor = ivcNo(d.tanggal, true);
      const per = u.ps.length ? u.penjualan / u.ps.length : 0;
      const psNames = u.ps.map(id => (pesertaById(id) || {}).nama).filter(Boolean);
      const jr = ivcJurnal(h, d.pl, d.coa, nomor, k, d.tanggal);
      coaPick(jr);
      const done = ivcDonePs(k).map(id => (pesertaById(id) || {}).nama).filter(Boolean);
      return sec(n0 + 1, 'Rincian Tagihan', 'yang ditagih di invoice ini', `<div class="table-wrap"><table class="table"><thead><tr><th>No.</th><th>No. Program</th><th>Item</th><th>Peserta</th><th class="money">Harga / peserta</th><th class="money">Diskon</th><th class="money">Nominal</th></tr></thead>
        <tbody><tr><td>1</td><td class="nowrap"><span class="code">${esc(pj.nomor)}</span></td><td>${esc(pk.judul)}<br><span class="muted">${esc(pk.nomor)}</span></td>
          <td>${u.ps.length} peserta<br><span class="muted">${esc(psNames.slice(0, 4).join(', '))}${psNames.length > 4 ? `, +${psNames.length - 4} lainnya` : ''}</span></td>
          <td class="money">${v.allin ? `All-in ÷ ${v.n}<br><span class="muted">≈ ${rp(Math.round(per))}</span>` : rp(v.harga)}</td>
          <td class="money">${v.allin ? `${rp(v.diskTot)}<br><span class="muted">total quotation</span>` : `${rp(v.disk)}<br><span class="muted">per peserta</span>`}</td>
          <td class="money"><b>${rp(u.penjualan)}</b></td></tr></tbody></table></div>
        ${done.length ? `<p class="hint">Peserta yang sudah ditagih: ${esc(done.join(', '))}.</p>` : ''}`)
        + sec(n0 + 2, 'Perhitungan Invoice' + QIVC(8, 'PPN quotation = 11% × DPP, PPN invoice = (Penjualan × 11/12) × 12%. Nilainya sama, tetapi pembulatan bisa beda Rp 1. Pembulatan per tahap perlu dikonfirmasi ke tim pajak.'), 'Penjualan → DPP → PPN → Total Tagihan', calcCard(h, d.pl))
        + sec(n0 + 3, 'Jurnal' + QIVC(14, 'Kode COA dari file acuan: 1102-01-01, 2104-01-07, 1106-01-XX, 2104-01-XX; COA Penjualan 0000-00-00. Kode final Labmania?'), 'pilih COA Penjualan; debit dan credit harus balance', `${jrTable(jr)}<div class="field" id="ivc-err"></div>`);
    };
    const top = `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="ivc-back">← Kembali</button><h2>Add Invoicing</h2><span class="ref">IVC-FR-04 – IVC-FR-11</span></div>
      <div class="notice ivc-ctx"><span>No. Program <span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)} · ${esc(pk.judul)} · Quotation <span class="code">${esc(k.nomor)}</span> · ${dealRows(k).length} peserta deal · nilai quotation <b>${rp(v.total)}</b></span></div>`;
    return { k, d, pj, unit, hdr, cara, dyn, top };
  }
  const viewIvcForm = () => {
    if (!state.ivp) { state.view = 'ivc'; return viewIvc(); }
    const P = ivcParts();
    return `${P.top}<form id="ivc-page" novalidate>
      <div id="ivc-hdr">${P.hdr()}</div><div id="ivc-cara">${P.cara()}</div><div id="ivc-dyn">${P.dyn()}</div>
      <div class="form-actions"><button type="button" class="btn btn-ghost" data-act="ivc-back">Back</button><button type="submit" class="btn btn-primary">Save</button></div></form>`;
  };
  // Pasang event setelah halaman dirender (dipanggil dari render()).
  function ivcBind() {
    const form = $('#ivc-page'); if (!form) return;
    const redraw = all => { const P = ivcParts(); if (all) { form.querySelector('#ivc-hdr').innerHTML = P.hdr(); form.querySelector('#ivc-cara').innerHTML = P.cara(); } form.querySelector('#ivc-dyn').innerHTML = P.dyn(); };
    const d = state.ivp.d;
    form.addEventListener('input', e => { const n = e.target.name; if (n === 'noFaktur') d[n] = e.target.value; else if (n && n.startsWith('fk-')) d.faktur[n.slice(3)] = e.target.value; });
    form.addEventListener('change', e => {
      const t = e.target, n = t.name;
      if (n === 'tanggal') { d.tanggal = t.value || todayIso; redraw(true); }
      else if (n === 'pl') { d.pl = t.value; redraw(true); }
      else if (n === 'coa') { d.coa = t.value; redraw(); }
    });
    form.addEventListener('submit', e => {
      e.preventDefault();
      form.querySelectorAll('.has-err').forEach(el => el.classList.remove('has-err'));
      form.querySelectorAll('.err').forEach(el => el.remove());
      const P = ivcParts(), k = P.k, f = form.elements;
      let ok = true;
      if (!d.tanggal) ok = invalid(f.tanggal, 'Tanggal Invoice wajib diisi');
      const perPs = d.cara === 'peserta';
      if (d.pl !== 'NON' && !perPs && !d.noFaktur.trim()) ok = invalid(f.noFaktur, 'Nomor Faktur wajib diisi');
      if (d.pl !== 'NON' && perPs) {
        const seen = {};
        ivcSisaPs(k).forEach(r => { const el = f[`fk-${r.id}`], val = (d.faktur[r.id] || '').trim();
          if (!val) ok = invalid(el, 'Nomor Faktur wajib diisi');
          else if (seen[val]) ok = invalid(el, 'Nomor Faktur tidak boleh sama dengan invoice lain');
          seen[val] = true; });
      }
      if (!d.coa) { ok = false; $('#ivc-err').innerHTML = '<div class="err"><i>!</i>COA Penjualan wajib dipilih di tabel jurnal.</div>'; }
      if (!ok) { toast('Lengkapi isian yang wajib', 'warn'); const first = form.querySelector('.has-err .input'); if (first) first.focus(); return; }
      if (perPs) {
        // Dipecah otomatis: satu invoice per peserta Deal, nomor berurutan (input/Invoice & Pengajuan/2026-11-03-b2b-per-peserta-pecah-otomatis/).
        const made = ivcSplit(k).map(u => { const h = ivcHitung(u.penjualan, u.lain, d.pl), nomor = ivcNo(d.tanggal);
          db.inv.push(Object.assign({ id: uid(), nomor, tanggal: d.tanggal, qtn: k.id, prog: progNo(k), jenis: P.pj.jenis, cara: 'peserta', peserta: u.ps, perlakuan: d.pl, noFaktur: d.pl === 'NON' ? '' : d.faktur[u.ps[0]].trim(),
            coaPenjualan: d.coa, setor: null, status: 'invoiced', jurnal: ivcJurnal(h, d.pl, d.coa, nomor, k, d.tanggal) }, h));
          return { nomor, total: h.total }; });
        toast(`${made.length} invoice tersimpan (${made[0].nomor} s.d. ${made[made.length - 1].nomor}) — ${rp(made.reduce((t, x) => t + x.total, 0))}`);
        const back = state.ivp.from; state.ivp = null; go(back); return;
      }
      const u = P.unit(), h = ivcHitung(u.penjualan, u.lain, d.pl), nomor = ivcNo(d.tanggal);
      const jurnal = ivcJurnal(h, d.pl, d.coa, nomor, k, d.tanggal);
      db.inv.push(Object.assign({ id: uid(), nomor, tanggal: d.tanggal, qtn: k.id, prog: progNo(k), jenis: P.pj.jenis, cara: d.cara, peserta: u.ps, perlakuan: d.pl, noFaktur: d.pl === 'NON' ? '' : d.noFaktur.trim(),
        coaPenjualan: d.coa, setor: null, status: 'invoiced', jurnal }, h));
      toast(`Invoice ${nomor} tersimpan — ${rp(h.total)}`);
      const back = state.ivp.from; state.ivp = null; go(back);
    });
  }

  // Lihat Invoice (IVC-FR-13) dan Terima Bukti Setor WAPU (IVC-FR-12).
  function ivcView(i) {
    const k = db.qtn.find(x => x.id === i.qtn), names = i.peserta.map(id => (pesertaById(id) || {}).nama).filter(Boolean);
    openModal({ title: `Invoice ${i.nomor}`, wide: 'x', info: true,
      body: `<div class="summary">${sumRow('No. Program', `${jenisTag(i.jenis)} <span class="code">${esc(i.prog)}</span> · ${esc(paketOf(mpOf(pjOfQtn(k))).judul)}`)}${sumRow('Tanggal Invoice', tglPanjang(i.tanggal))}${sumRow('Quotation', `${esc(k.nomor)} · ${esc(kepadaOf(k))}`)}${sumRow('Peserta', `${names.length} — ${esc(names.join(', '))}`)}
        ${sumRow('Perlakuan PPN', PERLAKUAN[i.perlakuan])}${sumRow('Nomor Faktur', esc(i.noFaktur || '—'))}${sumRow('COA Penjualan', esc(i.coaPenjualan))}
        ${i.setor ? sumRow('Bukti setor WAPU', `${tglPanjang(i.setor.tanggal)}${i.setor.nomor ? ` · ${esc(i.setor.nomor)}` : ''}`) : ''}</div>
        <p class="qtn-h">Perhitungan</p>${calcCard(i, i.perlakuan)}
        <p class="qtn-h">Jurnal invoice</p>${jrTable(i.jurnal)}
        ${i.setor ? `<p class="qtn-h">Jurnal bukti setor PPN WAPU</p>${jrTable(i.setor.jurnal)}` : ''}
        <p class="hint"><button type="button" class="btn btn-outline sm" data-act="ivc-print" data-id="${i.id}">Print Invoice</button> Batal / revisi invoice belum ditentukan${QIVC(11, 'Bila invoice salah, bisa dibatalkan/direvisi? Jurnalnya dibalik?')}.</p>` });
  }
  function ivcSetor(i) {
    const k = db.qtn.find(x => x.id === i.qtn);
    const jr = tgl => [
      { coa: COA_FIX.htWapu[0], nama: COA_FIX.htWapu[1], d: i.ppn, c: 0 }, { coa: COA_FIX.umWapu[0], nama: COA_FIX.umWapu[1], d: 0, c: i.ppn }
    ].map(r => Object.assign(r, { tanggal: tgl, co: IVC_CO, prog: progNo(k), desc: `${r.nama} - ${i.nomor} - ${k.nomor}` }));
    openModal({ title: `Terima Bukti Setor PPN WAPU — ${i.nomor}`, wide: true, saveLabel: 'Simpan',
      body: `<p class="hint top">Customer WAPU menyetor PPN sendiri. Saat bukti setor diterima (rekon akhir bulan), uang muka PPN WAPU dibalik.</p>
        <div class="grid2">${field('Tanggal bukti setor' + req, `<input class="input" type="date" name="tgl" value="${todayIso}">`)}${field('Nomor bukti setor <span class="muted">(opsional)</span>', '<input class="input" name="no" placeholder="Nomor bukti setor / NTPN">')}</div>
        <div id="setor-jr">${jrTable(jr(todayIso))}</div>`,
      onMount: form => form.addEventListener('change', e => { if (e.target.name === 'tgl') form.querySelector('#setor-jr').innerHTML = jrTable(jr(e.target.value || todayIso)); }),
      onSave: f => {
        if (!f.tgl.value) return invalid(f.tgl, 'Tanggal bukti setor wajib diisi');
        i.setor = { tanggal: f.tgl.value, nomor: f.no.value.trim(), jurnal: jr(f.tgl.value) };
        toast(`Bukti setor PPN WAPU ${i.nomor} dicatat`);
      }
    });
  }

  // Print Invoice (IVC-FR-15; input/Invoice & Pengajuan/2026-10-30-print-invoice/). Format belum diberikan klien:
  // template usulan analis, memakai kop & kaki halaman yang sama dengan Penawaran (Template Penawaran Labmania).
  const terbilang = n => {
    const s = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    const t = x => x < 12 ? s[x] : x < 20 ? `${t(x - 10)} belas` : x < 100 ? `${t(Math.floor(x / 10))} puluh ${t(x % 10)}`
      : x < 200 ? `seratus ${t(x - 100)}` : x < 1000 ? `${t(Math.floor(x / 100))} ratus ${t(x % 100)}` : x < 2000 ? `seribu ${t(x - 1000)}`
        : x < 1e6 ? `${t(Math.floor(x / 1000))} ribu ${t(x % 1000)}` : x < 1e9 ? `${t(Math.floor(x / 1e6))} juta ${t(x % 1e6)}`
          : x < 1e12 ? `${t(Math.floor(x / 1e9))} miliar ${t(x % 1e9)}` : `${t(Math.floor(x / 1e12))} triliun ${t(x % 1e12)}`;
    const w = t(Math.round(n)).replace(/\s+/g, ' ').trim();
    return w ? w.charAt(0).toUpperCase() + w.slice(1) + ' rupiah' : 'Nol rupiah';
  };
  const QPRINT = QIVC(10, 'Format cetak invoice belum ada dari klien; ini template usulan. Perlu dikonfirmasi: rekening bank, penanda tangan & jabatan, jatuh tempo pembayaran, kode dokumen, dan apakah dicetak per peserta untuk B2B per peserta.');
  function pageInvoice(i) {
    const k = db.qtn.find(x => x.id === i.qtn), pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), v = qtnNilai(k, dealRows(k));
    const co = k.tujuan === 'company' ? db.company.find(c => c.id === k.company) : null;
    const npwp = co ? co.npwp : pesertaNpwp(pesertaById(k.peserta[0] && k.peserta[0].id));
    const names = i.peserta.map(id => (pesertaById(id) || {}).nama).filter(Boolean);
    const n = i.peserta.length, perPs = i.cara === 'peserta';
    const harga = v.allin ? `${rp(v.sub)}<br><span class="pdf-mut">all-in ${v.n} peserta</span>` : rp(v.harga);
    const disk = v.allin ? (v.diskTot ? rp(v.diskTot) : '0') : (v.disk ? `${rp(v.disk)}<br><span class="pdf-mut">per peserta</span>` : '0');
    const rows = `<tr><td>1</td><td><b>${esc(pk.judul)}</b><br>${esc(pj.durasi)} hari · ${rangePanjang(pj.tglMulai, pj.tglSelesai)}${pj.waktu ? ` · ${esc(pj.waktu)}` : ''}<br>${esc(pj.lokasi || '—')}
        <br><span class="pdf-mut">No. Program ${esc(pj.nomor)} · Quotation ${esc(k.nomor)}</span>${names.length ? `<br><span class="pdf-mut">Peserta: ${esc(names.join(', '))}</span>` : ''}</td>
        <td class="r">${n} peserta</td><td class="r">${perPs ? rp(v.harga) : harga}</td><td class="r">${perPs ? (v.disk ? rp(v.disk) : '0') : disk}</td><td class="r">${rp(i.penjualan)}</td></tr>
      ${i.lain ? `<tr><td>2</td><td>Biaya lain-lain</td><td class="r">—</td><td class="r">—</td><td class="r">—</td><td class="r">${rp(i.lain)}</td></tr>` : ''}`;
    const pl = i.perlakuan, bayar = i.piutang;
    const sum = `<tr><td>Jumlah</td><td>${rp(i.penjualan)}</td></tr>
      ${pl === 'NON' ? '' : `<tr><td>DPP Nilai Lain (11/12 × Jumlah)</td><td>${rp(i.dpp)}</td></tr><tr><td>PPN 12% × DPP</td><td>${rp(i.ppn)}</td></tr>`}
      ${i.lain ? `<tr><td>Biaya Lain-lain</td><td>${rp(i.lain)}</td></tr>` : ''}
      <tr class="tot"><td>Total Tagihan</td><td>${rp(i.total)}</td></tr>
      ${pl === 'WAPU' ? `<tr><td>PPN dipungut & disetor oleh Pemungut (WAPU)</td><td>− ${rp(i.ppn)}</td></tr>` : ''}
      ${pl === 'KB' ? `<tr><td>PPN tidak dipungut (Kawasan Berikat)</td><td>− ${rp(i.ppn)}</td></tr>` : ''}
      <tr class="tot"><td>Jumlah yang harus dibayar</td><td>${rp(bayar)}</td></tr>`;
    return pdfPage(`
      <div class="pdf-inv-h">INVOICE</div>
      <div class="pdf-2col">
        <div class="pdf-box"><div class="pdf-lbl">Kepada</div><b>${esc(kepadaOf(k))}</b><br>${esc(k.alamat || '—')}
          <br>u.p. ${esc(k.tujuan === 'company' ? k.up || '—' : kepadaOf(k))}${npwp ? `<br>NPWP: ${esc(npwp)}` : ''}</div>
        <div class="pdf-box"><table class="pdf-kv">
          <tr><td>No. Invoice</td><td>: <b>${esc(i.nomor)}</b></td></tr><tr><td>Tanggal</td><td>: ${tglPanjang(i.tanggal)}</td></tr>
          <tr><td>No. Program</td><td>: ${esc(pj.nomor)}</td></tr><tr><td>No. Quotation</td><td>: ${esc(k.nomor)}</td></tr>
          <tr><td>No. Faktur Pajak</td><td>: ${esc(i.noFaktur || '—')}</td></tr>
          <tr><td>Perlakuan PPN</td><td>: ${PERLAKUAN[pl]}</td></tr></table></div>
      </div>
      <table class="pdf-tbl"><thead><tr><th>No</th><th>Deskripsi</th><th class="r">Kts.</th><th class="r">Harga</th><th class="r">Diskon</th><th class="r">Jumlah</th></tr></thead><tbody>${rows}</tbody></table>
      <div class="pdf-2col">
        <div><div class="pdf-lbl">Terbilang</div><div class="pdf-line"><i>${terbilang(bayar)}</i></div>
          <div class="pdf-lbl" style="margin-top:10px">Pembayaran</div>
          <div class="pdf-small">Mohon ditransfer ke rekening:<br>Bank <b>[nama bank]</b> · No. Rek. <b>[nomor rekening]</b><br>a.n. PT LabMania Indonesia Jaya
            <br>Cantumkan No. Invoice <b>${esc(i.nomor)}</b> pada berita transfer.${pl === 'WAPU' ? '<br>Mohon kirimkan bukti setor PPN (NTPN) kepada kami.' : ''}</div></div>
        <table class="pdf-sum">${sum}</table>
      </div>
      <div class="pdf-2col pdf-sign">
        <div></div>
        <div>Bekasi, ${tglPanjang(i.tanggal)}<br>Hormat kami,<br>PT LabMania Indonesia Jaya<div class="pdf-ttd"></div><b>( ............................ )</b><br>Finance</div>
      </div>`, pdfFoot('FR/LMI-FN-01/01 (usulan)', 'Halaman 1 dari 1', '—'));
  }
  function ivcPrint(i) {
    openModal({
      title: `Print Invoice ${i.nomor}`, wide: 'x', cancelLabel: 'Tutup', saveLabel: 'Cetak',
      body: pdfWrap(pageInvoice(i), `Invoice ${esc(i.nomor)} · template usulan${QPRINT}`),
      onMount: pdfMount,
      onSave: () => { logAct('Invoice', 'Invoicing', 'Cetak', i.nomor, i.id, `Cetak Invoice ${i.nomor}`); toast(`Invoice ${i.nomor} dicetak (mockup)`); }
    });
  }

