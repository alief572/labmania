/* Labmania mockup — Pengajuan Biaya.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Pengajuan Biaya (PB-FR-01 – PB-FR-20, draf 2; input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/) ----------
  // Tim program (Sales quotation, Trainer, Manajemen) mengajukan biaya ke Manajemen; cakupan pengajuan + approval (pencairan Finance menyusul).
  const QPB = (n, t) => q(`Q-PB-${String(n).padStart(2, '0')}`, t);
  const PB_ST = { menunggu: 'Menunggu Approval', disetujui: 'Disetujui', ditolak: 'Ditolak', batal: 'Batal' };
  const PB_JENIS = { kasbon: 'Kasbon', dp: 'Direct Payment' };
  const PB_KODE = { kasbon: 'KS', dp: 'DP' };
  const PB_KAT = { fixed: 'Fixed Cost', variable: 'Variable Cost' };
  // Progres pengajuan Kasbon / Direct Payment (input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/):
  // Waiting Approval → (Setujui) Waiting Payment → (Tandai Paid) Paid. Overbudget tetap memakai PB_ST (tidak ada pembayaran).
  const PB_PROG = { menunggu: 'Waiting Approval', disetujui: 'Waiting Payment', paid: 'Paid', ditolak: 'Ditolak', batal: 'Batal' };
  const PB_STEPS = ['menunggu', 'disetujui', 'paid'];
  const PB_AKTIF = ['menunggu', 'disetujui', 'paid'];
  const pbBadge = (s, ob = false) => `<span class="badge st-pb-${s}">${(ob ? PB_ST : PB_PROG)[s]}</span>`;
  // Stepper progres: langkah selesai ✓, langkah sekarang menyala; Ditolak / Batal menghentikan progres di langkah approval.
  const pbProgress = r => {
    const stop = r.status === 'ditolak' || r.status === 'batal', at = stop ? 0 : PB_STEPS.indexOf(r.status);
    return `<ol class="pb-prog ${stop ? 'stop' : ''}">${PB_STEPS.map((k, i) => `<li class="${i < at || r.status === 'paid' ? 'done' : i === at ? (stop ? 'x' : 'on') : ''}"><i></i><span>${i === 0 && stop ? PB_PROG[r.status] : PB_PROG[k]}</span></li>`).join('')}</ol>`;
  };
  const canPay = () => isSuper() || isAdmin() || can('pb', 'm');
  const pbJenisTag = j => `<span class="tag tag-pb-${j}">${PB_JENIS[j]}</span>`;
  const userName = id => (db.user.find(u => u.id === id) || {}).nama || '—';
  const fmtQty = n => (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
  const sumBy = (list, f) => list.reduce((t, x) => t + (Number(x[f]) || 0), 0);
  const pjById = id => db.quot.find(x => x.id === id);

  // Akun login trainer (usulan Q-PB-13): contoh trainer Ivan punya akun; dipakai sebagai trainer di program B2B Final.
  db.user.push({ id: uid(), kode: 'EMP-006', nama: 'Ivan Sarifudin, S.T.', nik: '', tmpLahir: '', tglLahir: '', role: roleIdOf('Trainer'), gender: 'Laki-laki', agama: '',
    telp: '+6281200000001', email: 'ivan@contoh.id', alamat: '', npwp: '', tglMasuk: '2023-05-02', tipe: 'Freelance', jobdesc: 'Trainer', aktif: true,
    akun: true, username: 'ivan.trainer' });
  db.trainer.forEach(t => { if (t.user === undefined) t.user = ''; });
  if (db.trainer[0]) db.trainer[0].user = db.user[db.user.length - 1].id;
  { const pj = db.quot.find(x => x.status === 'final' && x.jenis === 'b2b'); if (pj && db.trainer[0]) pj.trainer = db.trainer[0].id; }

  // Tim program (PB-FR-19): Sales di quotation yang tidak Batal, akun trainer, group Manajemen.
  const timOf = pj => {
    const sales = [...new Set(db.qtn.filter(k => k.pj === pj.id && k.status === 'deal').map(k => k.sales).filter(Boolean))];
    const tr = trainerById(pj.trainer);
    return { sales, trainer: tr, trainerUser: tr && tr.user ? tr.user : '' };
  };
  const inTim = pj => { if (isMgmt()) return true; const t = timOf(pj); return t.sales.includes(state.me) || (t.trainerUser && t.trainerUser === state.me); };
  const pbProgs = () => db.quot.filter(x => x.status === 'final');
  const pbVisible = () => pbProgs().filter(x => seeAllData() || inTim(x));
  const canAjukan = pj => !pj.closed && (isSuper() || (can('pb', 'a') && (inTim(pj) || isAdmin()))); // program Closed tidak dapat diajukan (PB-FR-20)
  const canApprove = () => can('pbAppr', 'p'); // hak Approve (Q-33)

  db.pb = []; db.ob = [];
  const pbQtyEst = (pj, b) => { const d = Number(pj.durasi) || 0, n = nPeserta(pj);
    return b.satuan === 'Event' ? 1 : b.satuan === 'Day' ? d : b.satuan === 'Peserta/Event' ? n : n * d; };
  // Item = item costing (key c<index>) + item baru dari overbudget disetujui (key o<id detail>).
  function pbItems(pj, kat) {
    const obOk = db.ob.filter(o => o.pj === pj.id && o.kat === kat && o.status === 'disetujui').flatMap(o => o.items);
    const used = db.pb.filter(p => p.pj === pj.id && p.kat === kat && PB_AKTIF.includes(p.status)).flatMap(p => p.items);
    const mk = (key, item, satuan, qty, harga, baru) => {
      const ob = obOk.filter(d => d.key === key), us = used.filter(d => d.key === key);
      const est = qty * harga, obQty = sumBy(ob, 'qty'), obTot = sumBy(ob, 'total'), usedQty = sumBy(us, 'qty'), usedTot = sumBy(us, 'total'), budget = est + obTot;
      return { key, item, satuan, baru, qty, harga, est, obQty, obTot, usedQty, usedTot, budget, sisa: budget - usedTot, sisaQty: Math.max(0, qty + obQty - usedQty) };
    };
    const costing = pj.biaya.map((b, bi) => ({ b, bi })).filter(x => x.b.tipe === kat && Number(x.b.harga) > 0)
      .map(({ b, bi }) => mk('c' + bi, b.item, b.satuan, pbQtyEst(pj, b), Number(b.harga) || 0, false));
    const baru = obOk.filter(d => d.baru).map(d => mk(d.key, d.item, 'item baru', 0, d.harga, true));
    return costing.concat(baru);
  }
  const pbSum = (pj, kat) => { const it = kat ? pbItems(pj, kat) : pbItems(pj, 'fixed').concat(pbItems(pj, 'variable'));
    return { budget: sumBy(it, 'budget'), used: sumBy(it, 'usedTot'), sisa: sumBy(it, 'sisa') }; };
  const pbOf = pj => db.pb.filter(p => p.pj === pj.id);
  const obOf = pj => db.ob.filter(o => o.pj === pj.id);
  const pbWaiting = pj => pbOf(pj).concat(obOf(pj)).filter(x => x.status === 'menunggu').length;

  // Data contoh: program B2B Final — overbudget disetujui (tambah item costing + item baru), kasbon disetujui, direct payment menunggu.
  {
    const pj = db.quot.find(x => x.status === 'final' && x.jenis === 'b2b');
    if (pj) {
      const mgr = db.user[0].id, t = timOf(pj), by = t.sales[0] || t.trainerUser || mgr;
      const it = (kat, name, qty) => { const x = pbItems(pj, kat).find(i => i.item === name); return { key: x.key, item: x.item, qty, harga: x.harga, total: qty * x.harga }; };
      const nbId = uid();
      db.ob.push({ id: uid(), nomor: lmiNo('OB', '2026-11-02'), pj: pj.id, kat: 'variable', tanggal: '2026-11-02', alasan: 'Tambahan 5 peserta observer dari client selama 3 hari; perlu name tag peserta.',
        items: [Object.assign(it('variable', 'Coffee Break', 15), { total: 15 * 40000 }), { key: 'o' + nbId, item: 'Name tag & lanyard peserta', baru: true, qty: 25, harga: 12000, total: 300000 }],
        total: 900000, status: 'disetujui', by, apprBy: mgr, apprAt: '2026-11-02 14:10', alasanTolak: '' });
      const mkPb = (o, items) => db.pb.push(Object.assign({ id: uid(), pj: pj.id, items, total: sumBy(items, 'total'), files: [], apprBy: '', apprAt: '', alasanTolak: '' }, o));
      mkPb({ nomor: lmiNo('KS', '2026-11-03'), kat: 'fixed', jenis: 'kasbon', tanggal: '2026-11-03', desk: 'Akomodasi & transport trainer in house Bekasi 17–19 November 2026',
        bank: 'BCA', norek: '1234567890', nmRek: userName(by), status: 'disetujui', by, apprBy: mgr, apprAt: '2026-11-03 10:05' },
        [it('fixed', 'Hotel Trainer **', 3), it('fixed', 'Konsumsi Trainer, di luar konsumsi selama training', 3), it('fixed', 'Transport Trainer Luar Kota PP', 1)]);
      mkPb({ nomor: lmiNo('DP', '2026-11-04'), kat: 'variable', jenis: 'dp', tanggal: '2026-11-04', desk: 'Pelunasan katering makan siang peserta 3 hari (vendor)',
        bank: 'Mandiri', norek: '1310099988877', nmRek: 'CV Contoh Katering', files: ['invoice-katering.pdf'], status: 'menunggu', by },
        [it('variable', 'Makan Siang Training', 60)]);
      mkPb({ nomor: lmiNo('DP', '2026-11-04'), kat: 'variable', jenis: 'dp', tanggal: '2026-11-04', desk: 'DP katering coffee break peserta (vendor)',
        bank: 'Mandiri', norek: '1310099988877', nmRek: 'CV Contoh Katering', files: ['invoice-coffee-break.pdf'], status: 'paid', by, apprBy: mgr, apprAt: '2026-11-04 09:30',
        paidBy: mgr, paidAt: '2026-11-04', paidKet: 'Transfer Mandiri' },
        [it('variable', 'Coffee Break', 30)]);
    }
  }

  // Daftar Pengajuan Biaya (PB-FR-02): program Final yang terkait dengan user login; Admin semua.
  state.pbApprTab = 'pb'; state.pbPj = null; state.pbf = null; state.pbTab = 'ajukan'; state.pbJ = 'all'; state.pbS = 'all';
  const pbProgStatus = pj => { const w = pbWaiting(pj), n = pbOf(pj).concat(obOf(pj)).filter(x => x.status !== 'batal').length;
    return w ? `<span class="badge st-pb-menunggu">${w} Waiting Approval</span>` : n ? '<span class="badge st-pb-berjalan">Berjalan</span>' : '<span class="badge st-draft">Belum ada pengajuan</span>'; };
  const timCell = pj => { const t = timOf(pj);
    return `<b>Sales:</b> ${t.sales.length ? esc(t.sales.map(userName).join(', ')) : '<span class="muted">—</span>'}<br><span class="muted">Trainer: ${esc((t.trainer || {}).nama || '—')}</span>`; };
  const viewPb = () => `${pageHead('Pengajuan Biaya', 'Tim program (Sales di quotation, Trainer, Manajemen) mengajukan biaya program (Kasbon / Direct Payment) ke Manajemen. Satu baris = satu Penjualan Program <b>Final</b>; budget = costing Penjualan Program Final. Hanya program yang terkait dengan Anda yang tampil; Admin melihat semua.', 'PB-FR-01 – PB-FR-04 · PB-FR-21 – PB-FR-23')}
    <div class="notice"><span>Login sebagai <b>${esc(meUser().nama || '—')}</b> (${esc(meRole() || '—')}): ${isSuper() ? 'Superadmin — semua program' : isAdmin() ? 'Admin — semua program' : isMgmt() ? 'Manajemen — termasuk tim semua program' : 'program tempat Anda menjadi Sales di quotation Deal atau Trainer'}.</span></div>
    <div class="card"><div class="tabs">${[['ajukan', 'Ajukan Biaya', pbVisible().length], ['diajukan', 'Sudah Diajukan', pbSent().length]].map(([t, l, n]) =>
      `<button class="tab ${state.pbTab === t ? 'active' : ''}" data-act="pb-tab" data-tab="${t}">${l}<span class="nav-count">${n}</span></button>`).join('')}</div>
      ${state.pbTab === 'diajukan'
        ? toolbar('Cari nomor pengajuan, program, deskripsi, pengaju…', false,
          selectFilter('fpbj', 'Filter jenis pengajuan', state.pbJ, [['all', 'Kasbon & Direct Payment'], ['kasbon', 'Kasbon'], ['dp', 'Direct Payment']])
          + selectFilter('fpbs', 'Filter progres', state.pbS, [['all', 'Semua progres'], ...Object.entries(PB_PROG)]))
        : toolbar('Cari nomor program, judul, customer, sales, trainer…', false,
          selectFilter('fjenis', 'Filter jenis program', state.jenis, [['all', 'Public & B2B'], ['public', 'Program Public'], ['b2b', 'Program B2B']]))}
      <div id="list">${state.pbTab === 'diajukan' ? listPbSent() : listPbProg()}</div></div>`;

  // Tab Sudah Diajukan: semua pengajuan Kasbon / Direct Payment pada program yang dapat dilihat user, beserta progresnya.
  const pbSent = () => { const ids = pbVisible().map(pj => pj.id); return db.pb.filter(p => ids.includes(p.pj)); };
  function listPbSent() {
    const rows = pbSent().filter(r => (state.pbJ === 'all' || r.jenis === state.pbJ) && (state.pbS === 'all' || r.status === state.pbS)
      && matches([r.nomor, pjById(r.pj).nomor, paketOf(mpOf(pjById(r.pj))).judul, r.desk, userName(r.by)]))
      .sort((a, b) => b.tanggal.localeCompare(a.tanggal) || b.id - a.id);
    return listBlock(rows, [
      NO_COL,
      { label: 'No. Pengajuan', cls: 'nowrap', render: r => `<span class="code">${esc(r.nomor)}</span><br>${pbJenisTag(r.jenis)} <span class="muted">${tglPanjang(r.tanggal)}</span>` },
      { label: 'Program', render: r => { const pj = pjById(r.pj); return `<button class="btn btn-ghost sm" data-act="pb-open" data-id="${pj.id}">${esc(pj.nomor)}</button><br><span class="muted">${esc(paketOf(mpOf(pj)).judul)}</span>`; } },
      { label: 'Kategori / Deskripsi', render: r => `<b>${PB_KAT[r.kat]}</b><br>${esc(r.desk)}` },
      { label: 'Total', cls: 'money', render: r => rp(r.total) },
      { label: 'Request By', render: r => esc(userName(r.by)) },
      { label: 'Progres', render: pbProgress },
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['pb-view', 'Lihat']]
        .concat(r.status === 'menunggu' && r.by === state.me ? [['pb-cancel', 'Batalkan', true]] : [])
        .concat(r.status === 'disetujui' && canPay() ? [['pb-paid', 'Tandai Paid']] : [])) }
    ]);
  }

  // Tandai Paid (usulan, Q-PB-17): sementara sebelum modul pencairan Finance dibuat.
  function pbPaid(r) {
    if (!canPay()) return toast('Anda tidak punya hak Manage di Pengajuan Biaya (Role & Permission)', 'warn');
    openModal({ title: `Tandai Paid — ${esc(r.nomor)}`, saveLabel: 'Tandai Paid',
      body: `<p class="hint top">${PB_JENIS[r.jenis]} ${rp(r.total)} ke ${esc(r.bank)} · ${esc(r.norek)} a.n. ${esc(r.nmRek)}. Setelah ditandai, progres menjadi <b>Paid</b> dan tidak dapat diubah.${QPB(17, 'Siapa yang menandai Paid (Finance / Admin)? Perlu bukti transfer wajib? Sementara: user dengan hak Manage Pengajuan Biaya, tanggal bayar wajib, keterangan opsional.')}</p>
        ${field(`Tanggal bayar${req}`, `<input class="input" type="date" name="tgl" value="${todayIso}" max="${todayIso}">`)}
        ${field('Keterangan / bukti transfer', '<input class="input" name="ket" maxlength="150" placeholder="mis. Transfer BCA, no. referensi">')}`,
      onSave: f => {
        if (!f.tgl.value) return invalid(f.tgl, 'Tanggal bayar wajib diisi');
        if (f.tgl.value < r.tanggal) return invalid(f.tgl, 'Tanggal bayar tidak boleh sebelum tanggal pengajuan');
        Object.assign(r, { status: 'paid', paidBy: state.me, paidAt: f.tgl.value, paidKet: f.ket.value.trim() });
        toast(`${r.nomor} Paid`);
      } });
  }

  function listPbProg() {
    const rows = pbVisible().filter(pj => (state.jenis === 'all' || pj.jenis === state.jenis)
      && matches([pj.nomor, paketOf(mpOf(pj)).judul, (companyOf(pj) || {}).nama, pj.lokasi, ...timOf(pj).sales.map(userName), (timOf(pj).trainer || {}).nama]))
      .sort((a, b) => (a.tglMulai || '').localeCompare(b.tglMulai || ''));
    return listBlock(rows, [
      NO_COL,
      // Tabel ringkas (12-11-2026): nomor, judul, tanggal, dan customer dalam satu kolom; Budget memuat terpakai dan sisa.
      { label: 'Program', cls: 'title', render: pj => `${jenisTag(pj.jenis)} <button class="btn btn-ghost sm" data-act="pb-open" data-id="${pj.id}">${esc(pj.nomor)}</button><br>${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${pj.jenis === 'b2b' ? esc((companyOf(pj) || {}).nama || '—') : 'Public'}</span>` },
      { label: 'Tim', render: timCell },
      { label: 'Budget', cls: 'money', render: pj => { const s = pbSum(pj); return `${rp(s.budget)}<br><span class="muted">terpakai ${rp(s.used)}</span><br><b class="${s.sisa <= 0 ? 'neg' : ''}">sisa ${rp(s.sisa)}</b>`; } },
      { label: 'Status', render: pbProgStatus },
      { label: 'Action', cls: 'act', render: pj => gearMenu(pj, [['pb-open', 'Lihat Pengajuan']]) }
    ]);
  }

  // Halaman per program (PB-FR-03, PB-FR-04).
  const pbHead = pj => { const c = companyOf(pj), t = timOf(pj);
    const r = (l, v) => `<div class="pb-kv"><span>${l}</span><b>${v}</b></div>`;
    const trU = t.trainerUser ? (db.user.find(u => u.id === t.trainerUser) || {}).username : '';
    return `<section class="card pad pb-head"><div class="pb-kvs">
      ${r('No. Program', `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}`)}${r('Sales (quotation)', t.sales.length ? esc(t.sales.map(userName).join(', ')) : '<span class="muted">Belum ada quotation</span>')}
      ${r('Customer', pj.jenis === 'b2b' ? esc((c || {}).nama || '—') : 'Public')}${r('Trainer', t.trainer ? `${esc(t.trainer.nama)} <span class="muted">· ${trU ? `akun ${esc(trU)}` : 'belum punya akun login'}${QPB(13, 'Trainer perlu akun login (field Akun login di Master Trainer, usulan) agar dapat mengajukan.')}</span>` : '—')}
      ${r('Program', esc(paketOf(mpOf(pj)).judul))}${r('Waktu', `${fmtRange(pj.tglMulai, pj.tglSelesai)}${pj.waktu ? ` · ${esc(pj.waktu)}` : ''}`)}
      ${r('Lokasi', esc(pj.lokasi || '—'))}${r('Durasi · Target peserta', `${pj.durasi} hari · ${pj.targetPeserta} peserta`)}</div></section>`; };
  const kpi3 = s => `<div class="kpis pb-kpis"><div class="kpi"><div class="kpi-l">Budget</div><div class="kpi-v">${rp(s.budget)}</div></div>
    <div class="kpi"><div class="kpi-l">Aktual</div><div class="kpi-v">${rp(s.used)}</div><div class="kpi-s">waiting approval + waiting payment + paid</div></div>
    <div class="kpi ${s.sisa <= 0 ? 'bad' : 'good'}"><div class="kpi-l">Sisa Budget</div><div class="kpi-v">${rp(s.sisa)}</div></div></div>`;
  const rowGear = (r, view, cancel) => gearMenu(r, [[view, 'Lihat']].concat(r.status === 'menunggu' && r.by === state.me ? [[cancel, 'Batalkan', true]] : []));
  const viewPbProg = () => {
    const pj = pjById(state.pbPj); if (!pj) { state.view = 'pb'; return viewPb(); }
    if (!seeAllData() && !inTim(pj)) return `${pageHead('Pengajuan Biaya', 'Program ini bukan program terkait Anda.', 'PB-FR-20')}<div class="notice"><span>Anda tidak termasuk tim program ${esc(pj.nomor)}.</span><button class="btn btn-ghost sm" data-act="pb-back">Kembali</button></div>`;
    const can2 = canAjukan(pj);
    const blok = kat => {
      const s = pbSum(pj, kat), list = pbOf(pj).filter(p => p.kat === kat), obs = obOf(pj).filter(o => o.kat === kat);
      const btn = (act, l, cls) => can2 ? `<button class="btn ${cls} sm" data-act="${act}" data-id="${pj.id}" data-kat="${kat}">${l}</button>` : '';
      return `<section class="card pad ivc-sec"><div class="pb-blok-h"><p class="ivc-sec-h">Pengajuan ${PB_KAT[kat]}</p>${kpi3(s)}</div>
        <div class="pb-btns">${btn('pb-add', '+ Add Pengajuan', 'btn-accent')}${can2 ? '<span class="hint">Jenis Kasbon / Direct Payment dipilih di form</span>' : (pj.closed ? '<span class="hint">Program sudah Closed — tidak dapat diajukan lagi.</span>' : '<span class="hint">Anda tidak punya hak Add Pengajuan Biaya (Role & Permission).</span>')}</div>
        <div class="table-wrap"><table class="table"><thead><tr><th class="num">No</th><th>No. Pengajuan</th><th>Deskripsi</th><th class="money">Total</th><th>Jenis</th><th>Request By</th><th>Status</th><th>Tanggal</th><th class="act">Option</th></tr></thead><tbody>
          ${list.length ? list.map((p, i) => `<tr class="${p.status === 'batal' || p.status === 'ditolak' ? 'is-off' : ''}"><td class="num">${i + 1}</td><td class="nowrap"><span class="code">${esc(p.nomor)}</span></td><td>${esc(p.desk)}</td><td class="money">${rp(p.total)}</td>
            <td>${pbJenisTag(p.jenis)}</td><td>${esc(userName(p.by))}</td><td class="nowrap">${pbBadge(p.status)}</td><td class="nowrap">${tglPanjang(p.tanggal)}</td><td class="act">${rowGear(p, 'pb-view', 'pb-cancel')}</td></tr>`).join('')
            : '<tr><td colspan="9" class="empty">Belum ada pengajuan.</td></tr>'}</tbody></table></div>
        <div class="pb-ob"><p class="pb-ob-h">Overbudget ${PB_KAT[kat]} ${btn('ob-add', '+ Add Overbudget', 'btn-outline')}</p>
        <div class="table-wrap"><table class="table"><thead><tr><th class="num">No</th><th>No. Overbudget</th><th>Item</th><th class="money">Amount</th><th>Status</th><th>Pembuat Pengajuan</th><th class="act">Option</th></tr></thead><tbody>
          ${obs.length ? obs.map((o, i) => `<tr class="${o.status === 'batal' || o.status === 'ditolak' ? 'is-off' : ''}"><td class="num">${i + 1}</td><td class="nowrap"><span class="code">${esc(o.nomor)}</span></td><td>${o.items.map(d => esc(d.item) + (d.baru ? ' <span class="tag tag-manual">item baru</span>' : '')).join(', ')}</td><td class="money">${rp(o.total)}</td>
            <td class="nowrap">${pbBadge(o.status, true)}</td><td>${esc(userName(o.by))}</td><td class="act">${rowGear(o, 'ob-view', 'ob-cancel')}</td></tr>`).join('')
            : '<tr><td colspan="7" class="empty">Belum ada overbudget.</td></tr>'}</tbody></table></div></div></section>`;
    };
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pb-back">← Kembali</button><h2>Pengajuan ${esc(pj.nomor)}</h2><span class="ref">PB-FR-03, PB-FR-04</span></div>
      ${pbHead(pj)}${pj.closed ? `<div class="notice"><span>Program ini sudah <b>Closed</b> (Manage Peserta) — tidak dapat diajukan biaya lagi; pengajuan yang ada tetap dapat dilihat.</span></div>` : ''}${blok('fixed')}${blok('variable')}
      <p class="hint">Menyusul: pencairan oleh Finance, pelaporan realisasi, jurnal. Program yang sudah <b>Closed</b> (Manage Peserta) tidak dapat diajukan lagi.</p>`;
  };

  // Form Add Pengajuan (PB-FR-05 – PB-FR-11): satu tombol, jenis dipilih di form.
  function pbForm(pj, kat) {
    if (!canAjukan(pj)) return toast('Anda tidak punya hak mengajukan untuk program ini', 'warn');
    state.pbf = { pj: pj.id, kat, jenis: '', d: { desk: '', bank: '', norek: '', nmRek: '', rows: {}, files: [] } };
    state.view = 'pbForm'; render(); window.scrollTo(0, 0);
  }
  function pbSetJenis(j) {
    const F = state.pbf, d = F.d; F.jenis = j;
    if (j === 'kasbon' && !d.bank && !d.norek) {
      const last = db.pb.filter(p => p.by === state.me && p.jenis === 'kasbon').slice(-1)[0];
      Object.assign(d, last ? { bank: last.bank, norek: last.norek, nmRek: last.nmRek } : { nmRek: meUser().nama || '' });
    }
    render();
  }
  const pbRowCalc = (it, r) => { const qty = Number(r.qty) || 0, harga = r.harga === undefined ? it.harga : Number(r.harga) || 0; return { qty, harga, total: Math.round(qty * harga) }; };
  const viewPbForm = () => {
    if (!state.pbf) { state.view = 'pb'; return viewPb(); }
    const F = state.pbf, pj = pjById(F.pj), d = F.d, items = pbItems(pj, F.kat), me = meUser(), katSisa = sumBy(items, 'sisa');
    const sec = (n, title, hint, body) => `<section class="card pad ivc-sec"><p class="ivc-sec-h"><span class="ivc-no">${n}</span>${title}${hint ? ` <span class="muted">${hint}</span>` : ''}</p>${body}</section>`;
    const jenisPick = Object.keys(PB_JENIS).map(j => `<label class="pb-radio ${F.jenis === j ? 'on' : ''}"><input type="radio" name="jenis" value="${j}" ${F.jenis === j ? 'checked' : ''}> <b>${PB_JENIS[j]}</b><small>${j === 'kasbon' ? 'uang muka ke rekening pengaju' : 'bayar langsung ke rekening vendor / penerima'}</small></label>`).join('');
    const s1 = sec(1, 'Data Pengajuan', 'Request By dan tanggal dari sistem', `<div class="grid3">
      ${field('Jenis Pengajuan' + req, `<div class="pb-radios">${jenisPick}</div><span class="hint">Pengajuan PO tidak ada</span>`, '', 'span2')}
      ${field('Kategori', `<div class="static">${PB_KAT[F.kat]}</div>`)}
      ${field('Request By', `<div class="static">${esc(me.nama || '—')}</div>${inTim(pj) ? '' : `<span class="warn"><i>!</i>${esc(me.nama || 'User')} bukan tim program ini</span>`}`)}
      ${field('Tanggal', `<input class="input" value="${tglPanjang(todayIso)}" readonly>`)}
      <div></div>
      ${field('Deskripsi / Keterangan' + req, `<textarea class="input" name="desk" rows="2" placeholder="Deskripsi / Keterangan">${esc(d.desk)}</textarea>`, '', 'span3')}</div>`);
    const s2 = sec(2, 'Informasi Pengajuan', 'posisi budget per item', `<div class="table-wrap"><table class="table pb-info"><thead>
      <tr><th rowspan="2">No.</th><th rowspan="2">Item</th><th colspan="3" class="grp">Estimasi</th><th colspan="2" class="grp">Terpakai</th><th colspan="2" class="grp">Overbudget</th><th rowspan="2" class="money">Sisa</th></tr>
      <tr><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total Budget</th><th class="money">Qty</th><th class="money">Total Terpakai</th><th class="money">Qty</th><th class="money">Total</th></tr></thead><tbody>
      ${items.map((it, i) => `<tr><td>${i + 1}</td><td>${esc(it.item)}${it.baru ? ' <span class="tag tag-manual">item baru</span>' : `<br><span class="muted">${esc(it.satuan)}</span>`}</td><td class="money">${it.baru ? '—' : fmtQty(it.qty)}</td><td class="money">${rp(it.harga)}</td><td class="money">${it.baru ? '—' : rp(it.est)}</td>
        <td class="money">${fmtQty(it.usedQty)}</td><td class="money">${it.usedTot ? rp(it.usedTot) : '—'}</td><td class="money">${fmtQty(it.obQty)}</td><td class="money">${it.obTot ? rp(it.obTot) : '—'}</td><td class="money"><b class="${it.sisa <= 0 ? 'neg' : ''}">${rp(it.sisa)}</b></td></tr>`).join('')}</tbody>
      <tfoot><tr><td colspan="4"><b>Total</b></td><td class="money"><b>${rp(sumBy(items, 'est'))}</b></td><td></td><td class="money"><b>${rp(sumBy(items, 'usedTot'))}</b></td><td></td><td class="money"><b>${rp(sumBy(items, 'obTot'))}</b></td><td class="money"><b>${rp(sumBy(items, 'sisa'))}</b></td></tr></tfoot></table></div>
      <span class="hint">Budget dari Penjualan Program Final — Event = 1, Day = durasi, Peserta/Event = target peserta, Peserta/Day = peserta × durasi. Terpakai = pengajuan Menunggu Approval + Disetujui.</span>`);
    const s3 = sec(3, 'Pengajuan', `isi Qty; Price / Unit boleh berbeda — budget dicek per kategori (sisa ${PB_KAT[F.kat]} ${rp(katSisa)})`, `<div class="table-wrap"><table class="table pb-in"><thead><tr><th>No.</th><th>Item</th><th class="money">Sisa</th><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total Pengajuan</th></tr></thead><tbody>
      ${items.map((it, i) => { const r = d.rows[it.key] || {}, c = pbRowCalc(it, r), off = katSisa <= 0;
        return `<tr class="${off ? 'pb-off' : ''}"><td>${i + 1}</td><td>${esc(it.item)}<br><span class="muted">${it.baru ? 'item baru (overbudget)' : `${esc(it.satuan)} · sisa qty ${fmtQty(it.sisaQty)}`}</span></td><td class="money">${rp(it.sisa)}</td>
          <td class="money"><div class="field"><input class="input sm" type="number" min="0" step="0.5" name="q-${it.key}" value="${r.qty || ''}" ${off ? 'disabled' : ''} placeholder="0"></div></td>
          <td class="money"><div class="field">${moneyIn(`name="h-${it.key}" ${off ? 'disabled' : ''}`, c.harga)}</div></td>
          <td class="money"><b id="pbt-${it.key}">${c.total ? rp(c.total) : '—'}</b><br><span class="pb-over" id="pbo-${it.key}" ${c.total > it.sisa ? '' : 'hidden'}>melebihi sisa item</span></td></tr>`; }).join('')}</tbody>
      <tfoot><tr><td colspan="5"><b>Grand Total</b></td><td class="money"><b id="pb-grand">${rp(items.reduce((t, it) => t + pbRowCalc(it, d.rows[it.key] || {}).total, 0))}</b></td></tr><tr><td colspan="5">Sisa ${PB_KAT[F.kat]} <span class="muted">· setelah pengajuan ini</span></td><td class="money">${rp(katSisa)}<br><b id="pb-after">${rp(katSisa - items.reduce((t, it) => t + pbRowCalc(it, d.rows[it.key] || {}).total, 0))}</b></td></tr></tfoot></table></div>
      <span class="hint">Budget mencukupi = Grand Total ≤ sisa total ${PB_KAT[F.kat]} (per kategori, bukan per item). Satu item boleh melebihi sisa item-nya. Sisa kategori tidak cukup? Ajukan Overbudget dahulu.</span><div class="field" id="pb-err"></div>`);
    const dp = F.jenis === 'dp';
    const s4 = sec(4, dp ? 'Rekening Penerima & Bukti Tagihan' : 'Rekening Tujuan & Bukti', !F.jenis ? 'pilih jenis pengajuan dahulu' : dp ? 'rekening vendor / penerima; bukti tagihan wajib' : 'rekening pengaju; bukti opsional', `<div class="grid2">
      <div>${field('Bank' + req, `<input class="input" name="bank" value="${esc(d.bank)}" placeholder="mis. BCA">`)}
        ${field('No. Rekening' + req, `<input class="input" name="norek" value="${esc(d.norek)}" inputmode="numeric" placeholder="Nomor rekening">`)}
        ${field('Nama Rekening' + req, `<input class="input" name="nmRek" value="${esc(d.nmRek)}" placeholder="Atas nama">`)}</div>
      <div>${field('Bukti Penggunaan' + (dp ? req : ' <span class="muted">(opsional untuk Kasbon)</span>'), `<label class="pb-drop"><input type="file" name="files" multiple accept=".pdf,.jpg,.jpeg,.png" hidden><b>Tarik &amp; letakkan file di sini</b> atau klik untuk memilih file<span class="hint">PDF, JPG, PNG · maks. 5 MB per file</span></label>
        <div class="hint" id="pb-files">${d.files.length ? esc(d.files.join(', ')) : 'Belum ada file'}</div>`)}</div></div>`);
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pbf-back">← Kembali</button><h2>Add Pengajuan — ${PB_KAT[F.kat]}</h2><span class="ref">PB-FR-05 – PB-FR-11</span></div>
      ${pbHead(pj)}<form id="pb-page" novalidate>${s1}${s2}${s3}${s4}
      <div class="form-actions"><button type="button" class="btn btn-ghost" data-act="pbf-back">Back</button><button type="submit" class="btn btn-primary">Save</button></div></form>`;
  };
  function pbBind() {
    const form = $('#pb-page'); if (!form) return;
    const F = state.pbf, d = F.d, pj = pjById(F.pj), items = pbItems(pj, F.kat);
    const refresh = () => { let g = 0;
      items.forEach(it => { const c = pbRowCalc(it, d.rows[it.key] || {}); g += c.total; const el = $('#pbt-' + it.key); if (el) el.textContent = c.total ? rp(c.total) : '—'; });
      $('#pb-grand').textContent = rp(g); const katSisa = sumBy(items, 'sisa'), af = $('#pb-after'); af.textContent = rp(katSisa - g); af.classList.toggle('neg', g > katSisa);
      items.forEach(it => { const o = $('#pbo-' + it.key); if (o) o.hidden = !(pbRowCalc(it, d.rows[it.key] || {}).total > it.sisa); }); };
    form.addEventListener('input', e => {
      const t = e.target, n = t.name || '';
      if (n.startsWith('q-') || n.startsWith('h-')) { const k = n.slice(2), r = d.rows[k] = d.rows[k] || {};
        if (n[0] === 'q') r.qty = t.value; else r.harga = num(digits(t.value)); refresh(); }
      else if (['desk', 'bank', 'norek', 'nmRek'].includes(n)) d[n] = t.value;
    });
    form.addEventListener('change', e => {
      if (e.target.name === 'jenis') pbSetJenis(e.target.value);
      else if (e.target.name === 'files') { d.files = [...e.target.files].map(f => f.name); $('#pb-files').textContent = d.files.join(', ') || 'Belum ada file'; }
    });
    form.addEventListener('submit', e => {
      e.preventDefault();
      form.querySelectorAll('.has-err').forEach(el => el.classList.remove('has-err'));
      form.querySelectorAll('.err').forEach(el => el.remove());
      const f = form.elements; let ok = true;
      if (!F.jenis) ok = invalid(form.querySelector('.pb-radios'), 'Pilih jenis pengajuan: Kasbon atau Direct Payment');
      if (!d.desk.trim()) ok = invalid(f.desk, 'Deskripsi wajib diisi');
      const rows = [];
      pbItems(pj, F.kat).forEach(it => { const c = pbRowCalc(it, d.rows[it.key] || {});
        if (c.qty < 0) ok = invalid(f[`q-${it.key}`], 'Qty tidak boleh minus');
        if (c.total > 0) { rows.push({ key: it.key, item: it.item, qty: c.qty, harga: c.harga, total: c.total }); } });
      const katSisa = sumBy(pbItems(pj, F.kat), 'sisa');
      if (!rows.length) { ok = false; $('#pb-err').innerHTML = '<div class="err"><i>!</i>Isi Qty minimal satu item.</div>'; }
      else if (sumBy(rows, 'total') > katSisa) { ok = false; $('#pb-err').innerHTML = `<div class="err"><i>!</i>Grand Total ${rp(sumBy(rows, 'total'))} melebihi sisa ${PB_KAT[F.kat]} ${rp(katSisa)} — ajukan Overbudget dahulu.</div>`; }
      if (!d.bank.trim()) ok = invalid(f.bank, 'Bank wajib diisi');
      if (!d.norek.trim()) ok = invalid(f.norek, 'No. Rekening wajib diisi');
      if (!d.nmRek.trim()) ok = invalid(f.nmRek, 'Nama Rekening wajib diisi');
      if (F.jenis === 'dp' && !d.files.length) ok = invalid(f.files, 'Bukti tagihan wajib untuk Direct Payment');
      if (!ok) { toast('Lengkapi isian yang wajib', 'warn'); return; }
      const rec = { id: uid(), nomor: lmiNo(PB_KODE[F.jenis], todayIso), pj: pj.id, kat: F.kat, jenis: F.jenis, tanggal: todayIso, desk: d.desk.trim(), items: rows, total: sumBy(rows, 'total'),
        bank: d.bank.trim(), norek: d.norek.trim(), nmRek: d.nmRek.trim(), files: d.files, status: 'menunggu', by: state.me, apprBy: '', apprAt: '', alasanTolak: '' };
      db.pb.push(rec); toast(`${PB_JENIS[F.jenis]} ${rec.nomor} diajukan — ${rp(rec.total)}, menunggu approval Manajemen`);
      state.pbf = null; state.pbPj = pj.id; go('pbProg');
    });
  }

  // Add Overbudget (PB-FR-12, PB-FR-13): item costing dan/atau item baru.
  function obForm(pj, kat) {
    if (!canAjukan(pj)) return toast('Anda tidak punya hak mengajukan untuk program ini', 'warn');
    const items = pbItems(pj, kat);
    let nb = 0;
    const nbRow = i => `<tr data-nb="${i}"><td><div class="field"><input class="input sm" name="nbn-${i}" placeholder="Nama item baru"></div></td><td class="money muted">item baru</td>
      <td class="money"><div class="field"><input class="input sm" type="number" min="0" step="0.5" name="nbq-${i}" placeholder="0"></div></td>
      <td class="money"><div class="field">${moneyIn(`name="nbh-${i}"`, '')}</div></td><td class="money" id="nbt-${i}">—</td></tr>`;
    openModal({ title: `Add Overbudget — ${PB_KAT[kat]} · ${esc(pj.nomor)}`, wide: true, saveLabel: 'Ajukan',
      body: `<p class="hint top">Tambahan budget untuk item costing, atau <b>item baru</b> di luar costing. Setelah <b>disetujui Manajemen</b>, budget bertambah dan dapat diajukan sebagai Kasbon / Direct Payment.</p>
        <div class="table-wrap"><table class="table pb-in"><thead><tr><th>Item</th><th class="money">Sisa</th><th class="money">Qty tambahan</th><th class="money">Price / Unit</th><th class="money">Total</th></tr></thead><tbody id="ob-body">
        ${items.map(it => `<tr><td>${esc(it.item)}<br><span class="muted">${it.baru ? 'item baru' : esc(it.satuan)}</span></td><td class="money">${rp(it.sisa)}</td>
          <td class="money"><div class="field"><input class="input sm" type="number" min="0" step="0.5" name="oq-${it.key}" placeholder="0"></div></td>
          <td class="money"><div class="field">${moneyIn(`name="oh-${it.key}"`, it.harga)}</div></td><td class="money" id="obt-${it.key}">—</td></tr>`).join('')}</tbody>
        <tfoot><tr><td colspan="4"><button type="button" class="btn btn-outline sm" id="ob-nb">+ Item baru</button> <b style="float:right">Total overbudget</b></td><td class="money"><b id="ob-grand">${rp(0)}</b></td></tr></tfoot></table></div>
        <div class="field" id="ob-err"></div>${field('Alasan' + req, '<textarea class="input" name="alasan" rows="2" placeholder="Mengapa budget perlu ditambah"></textarea>')}`,
      onMount: form => {
        const val = n => form.elements[n] ? form.elements[n].value : '';
        const recalc = () => { let g = 0;
          items.forEach(it => { const t = Math.round((Number(val(`oq-${it.key}`)) || 0) * (num(digits(val(`oh-${it.key}`))) || 0)); g += t; $('#obt-' + it.key).textContent = t ? rp(t) : '—'; });
          for (let i = 0; i < nb; i++) { const t = Math.round((Number(val(`nbq-${i}`)) || 0) * (num(digits(val(`nbh-${i}`))) || 0)); g += t; $('#nbt-' + i).textContent = t ? rp(t) : '—'; }
          $('#ob-grand').textContent = rp(g); };
        form.addEventListener('input', recalc);
        form.querySelector('#ob-nb').addEventListener('click', () => { form.querySelector('#ob-body').insertAdjacentHTML('beforeend', nbRow(nb)); form.elements[`nbn-${nb}`].focus(); nb++; });
      },
      onSave: f => {
        let ok = true; const rows = [];
        items.forEach(it => { const q2 = Number(f[`oq-${it.key}`].value) || 0, h = num(digits(f[`oh-${it.key}`].value)) || 0;
          if (q2 < 0) ok = invalid(f[`oq-${it.key}`], 'Qty tidak boleh minus');
          if (q2 > 0 && h > 0) rows.push({ key: it.key, item: it.item, baru: false, qty: q2, harga: h, total: Math.round(q2 * h) }); });
        for (let i = 0; i < nb; i++) {
          const nm = f[`nbn-${i}`].value.trim(), q2 = Number(f[`nbq-${i}`].value) || 0, h = num(digits(f[`nbh-${i}`].value)) || 0;
          if (!nm && !q2 && !h) continue;
          if (!nm) { ok = invalid(f[`nbn-${i}`], 'Nama item wajib'); continue; }
          if (!(q2 > 0 && h > 0)) { ok = invalid(f[`nbq-${i}`], 'Isi Qty dan Price / Unit'); continue; }
          rows.push({ key: 'o' + uid(), item: nm, baru: true, qty: q2, harga: h, total: Math.round(q2 * h) });
        }
        if (!rows.length) { ok = false; $('#ob-err').innerHTML = '<div class="err"><i>!</i>Isi Qty tambahan dan Price / Unit minimal satu item.</div>'; }
        if (!f.alasan.value.trim()) ok = invalid(f.alasan, 'Alasan wajib diisi');
        if (!ok) return false;
        const rec = { id: uid(), nomor: lmiNo('OB', todayIso), pj: pj.id, kat, tanggal: todayIso, alasan: f.alasan.value.trim(), items: rows, total: sumBy(rows, 'total'), status: 'menunggu', by: state.me, apprBy: '', apprAt: '', alasanTolak: '' };
        db.ob.push(rec); toast(`Overbudget ${rec.nomor} diajukan — ${rp(rec.total)}`);
      } });
  }

  // Lihat / proses approval (PB-FR-14, PB-FR-15, PB-FR-18).
  const pbRiwayat = r => {
    const li = [`Diajukan <b>${esc(userName(r.by))}</b> · ${tglPanjang(r.tanggal)}`];
    if (r.status === 'disetujui' || r.status === 'paid') li.push(`Disetujui <b>${esc(userName(r.apprBy))}</b> · ${esc(r.apprAt)}${r.jenis ? ' — Waiting Payment' : ''}`);
    if (r.status === 'paid') li.push(`Paid — ditandai <b>${esc(userName(r.paidBy))}</b> · tanggal bayar ${tglPanjang(r.paidAt)}${r.paidKet ? ` · ${esc(r.paidKet)}` : ''}`);
    if (r.status === 'ditolak') li.push(`Ditolak <b>${esc(userName(r.apprBy))}</b> · ${esc(r.apprAt)} — alasan: ${esc(r.alasanTolak)}`);
    if (r.status === 'batal') li.push('Dibatalkan pengaju');
    return `<ol class="pb-hist">${li.map(x => `<li>${x}</li>`).join('')}</ol>`; };
  function pbDetailBody(r, isOb) {
    const pj = pjById(r.pj), s = pbSum(pj, r.kat);
    return `<div class="summary">${sumRow('No. Program', `${jenisTag(pj.jenis)} <span class="code">${esc(pj.nomor)}</span> · ${esc(paketOf(mpOf(pj)).judul)}`)}
      ${sumRow(isOb ? 'No. Overbudget' : 'No. Pengajuan', `<span class="code">${esc(r.nomor)}</span> ${isOb ? '' : pbJenisTag(r.jenis)} · ${PB_KAT[r.kat]}`)}${sumRow('Status', pbBadge(r.status, isOb))}${isOb ? '' : sumRow('Progres', pbProgress(r))}
      ${sumRow(isOb ? 'Alasan' : 'Deskripsi', esc(isOb ? r.alasan : r.desk))}${sumRow('Request By', esc(userName(r.by)))}
      ${isOb ? '' : sumRow('Rekening tujuan', `${esc(r.bank)} · ${esc(r.norek)} · a.n. ${esc(r.nmRek)}`) + sumRow('Bukti', r.files.length ? esc(r.files.join(', ')) : '<span class="muted">—</span>')}
      ${sumRow(`Posisi budget ${PB_KAT[r.kat]}`, `Budget ${rp(s.budget)} · Aktual ${rp(s.used)} · Sisa ${rp(s.sisa)}`)}</div>
      <div class="table-wrap"><table class="table"><thead><tr><th>Item</th><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total</th></tr></thead><tbody>
      ${r.items.map(d => `<tr><td>${esc(d.item)}${d.baru ? ' <span class="tag tag-manual">item baru</span>' : ''}</td><td class="money">${fmtQty(d.qty)}</td><td class="money">${rp(d.harga)}</td><td class="money">${rp(d.total)}</td></tr>`).join('')}</tbody>
      <tfoot><tr><td colspan="3"><b>Total</b></td><td class="money"><b>${rp(r.total)}</b></td></tr></tfoot></table></div>
      <p class="qtn-h">Riwayat status</p>${pbRiwayat(r)}`;
  }
  const pbView = (r, isOb) => openModal({ title: `${isOb ? 'Overbudget' : PB_JENIS[r.jenis]} ${esc(r.nomor)}`, wide: true, info: true, body: pbDetailBody(r, isOb) });
  function pbProcess(r, isOb) {
    if (!canApprove()) return toast('Anda tidak punya hak Manage di Approval Pengajuan (Role & Permission)', 'warn');
    if (r.by === state.me) return toast('Pengaju tidak dapat memproses pengajuannya sendiri', 'warn');
    openModal({ title: `Approval ${isOb ? 'Overbudget' : PB_JENIS[r.jenis]} ${esc(r.nomor)}`, wide: true, saveLabel: 'Simpan Keputusan',
      body: `${pbDetailBody(r, isOb)}<div class="grid2">${field('Keputusan' + req, '<select class="input" name="kep"><option value="setuju">Setujui</option><option value="tolak">Tolak</option></select>')}
        ${field('Alasan tolak', '<textarea class="input" name="alasan" rows="2" placeholder="Wajib bila ditolak"></textarea>')}</div>`,
      onSave: f => {
        if (f.kep.value === 'tolak' && !f.alasan.value.trim()) return invalid(f.alasan, 'Alasan wajib bila ditolak');
        Object.assign(r, { status: f.kep.value === 'setuju' ? 'disetujui' : 'ditolak', apprBy: state.me, apprAt: isoNow().slice(0, 16), alasanTolak: f.kep.value === 'tolak' ? f.alasan.value.trim() : '' });
        toast(`${r.nomor} ${r.status === 'disetujui' ? 'disetujui' : 'ditolak'}`);
      } });
  }
  function pbCancel(r) {
    openModal({ title: 'Batalkan pengajuan', danger: true, saveLabel: 'Batalkan', cancelLabel: 'Kembali',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(r.nomor)}</b> (${rp(r.total)})? Budget yang dipesan kembali tersedia. Tindakan ini tidak dapat diurungkan.</p>`,
      onSave: () => { r.status = 'batal'; toast(`${r.nomor} dibatalkan`); } });
  }

  // Approval Pengajuan (PB-FR-14): 1 level, hak Manage di menu ini (default Manajemen).
  const viewPbAppr = () => {
    const pbW = db.pb.filter(p => p.status === 'menunggu'), obW = db.ob.filter(o => o.status === 'menunggu');
    const tabs = [['pb', 'Pengajuan', pbW.length], ['ob', 'Overbudget', obW.length]].map(([t, l, n]) =>
      `<button class="tab ${state.pbApprTab === t ? 'active' : ''}" data-act="pb-appr-tab" data-tab="${t}">${l}<span class="nav-count">${n}</span></button>`).join('');
    return `${pageHead('Approval Pengajuan', 'Pengajuan Kasbon / Direct Payment dan Overbudget yang <b>Menunggu Approval</b>. Approval 1 level oleh Manajemen; alasan wajib bila ditolak. Overbudget yang disetujui menambah budget item atau membuat item baru. Pencairan oleh Finance menyusul.', 'PB-FR-14, PB-FR-15')}
      ${canApprove() ? '' : '<div class="notice"><span>Anda dapat melihat daftar, tetapi tidak punya hak <b>Manage</b> (proses approval) di Role & Permission.</span></div>'}
      <div class="card"><div class="tabs">${tabs}</div>${toolbar('Cari nomor pengajuan, program, pengaju…', false)}<div id="list">${listPbAppr()}</div></div>`;
  };
  function listPbAppr() {
    const isOb = state.pbApprTab === 'ob';
    const rows = (isOb ? db.ob : db.pb).filter(r => r.status === 'menunggu' && matches([r.nomor, pjById(r.pj).nomor, userName(r.by), r.desk || r.alasan]));
    return listBlock(rows, [
      NO_COL,
      { label: isOb ? 'No. Overbudget' : 'No. Pengajuan', cls: 'nowrap', render: r => `<span class="code">${esc(r.nomor)}</span><br><span class="muted">${tglPanjang(r.tanggal)}</span>` },
      { label: 'No. Program', cls: 'nowrap', render: r => { const pj = pjById(r.pj); return `${jenisTag(pj.jenis)} <span class="code">${esc(pj.nomor)}</span><br><span class="muted">${esc(paketOf(mpOf(pj)).judul)}</span>`; } },
      { label: 'Kategori / Jenis', render: r => `${PB_KAT[r.kat]}${isOb ? '' : `<br>${pbJenisTag(r.jenis)}`}` },
      { label: isOb ? 'Alasan' : 'Deskripsi', render: r => esc(isOb ? r.alasan : r.desk) },
      { label: 'Total', cls: 'money', render: r => rp(r.total) },
      { label: 'Request By', render: r => esc(userName(r.by)) },
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [[isOb ? 'ob-view' : 'pb-view', 'Lihat']].concat(canApprove() && r.by !== state.me ? [[isOb ? 'ob-proc' : 'pb-proc', 'Proses (Setujui / Tolak)']] : [])) }
    ]);
  }
  // ---------- Tutorial highlight driver.js (permintaan BA 12-11-2026; menggantikan callout Panduan) ----------
  // Dimulai dari tombol Tutorial di atas kartu tabel. Helper tS/tRows/tKol/tGear/tAdd ada di core.js; langkah yang elemennya tidak ada dilewati.

  TOUR.pb = () => [
    tS(null, 'Pengajuan Biaya', 'Tim program (Sales, Trainer, Manajemen) mengajukan <b>Kasbon / Direct Payment</b> dari budget costing Penjualan Program Final.'),
    tS('#view .card .tabs', 'Ajukan Biaya / Sudah Diajukan', '<b>Ajukan Biaya</b>: program yang Anda tangani. <b>Sudah Diajukan</b>: semua pengajuan Anda dan statusnya.'),
    tS('.toolbar', 'Cari dan saring', 'Cari program.'),
    // Program yang belum Closed, supaya rantai tutorial bisa lanjut ke form pengajuan.
    (() => { const b = [...document.querySelectorAll('#list [data-act="pb-open"]')].find(x => { const pj = pjById(+x.dataset.id); return pj && !pj.closed; }) || document.querySelector('[data-act="pb-open"]');
      return b && tS(b, 'Mulai: buka program', 'Membuka budget dan pengajuan satu program.', { lanjut: { label: 'Buka program', run: () => b.click(), ke: 'pbProg' } }); })()
  ];
  TOUR.pbProg = () => [
    tS('#view .pb-head', 'Program & budget', 'Budget = costing Penjualan Program Final; terpakai dan sisa per kategori.'),
    tS('#view .pb-kpis', 'Ringkasan', 'Total budget, terpakai, dan sisa.'),
    tS('[data-act="pb-add"]', '+ Add Pengajuan', 'Ajukan Kasbon atau Direct Payment dalam sisa budget kategori.'),
    tS('#view .pb-ob', 'Overbudget', 'Bila sisa tidak cukup, ajukan <b>+ Add Overbudget</b> dahulu; disetujui Manajemen.'),
    (() => { const b = document.querySelector('[data-act="pb-add"]:not([disabled])'); return b && { popover: { title: 'Buat pengajuan', description: 'Lanjut ke form pengajuan.' }, lanjut: { label: 'Buka form', run: () => b.click(), ke: 'pbForm' } }; })()
  ];
  TOUR.pbForm = () => [
    tS('.pf-formbar', 'Form pengajuan', 'Satu pengajuan untuk satu kategori budget.'),
    tS('#view .pb-radios', 'Jenis', '<b>Kasbon</b> (uang muka) atau <b>Direct Payment</b> (dibayar langsung).'),
    tS('#view .table.pb-in', 'Item', 'Isi item yang diajukan; estimasi, terpakai, dan sisa per item tampil sebagai acuan.'),
    tS('#pb-grand', 'Total', 'Total harus ≤ sisa budget kategori.'),
    tS('#view .form-actions [type=submit]', 'Save', 'Pengajuan tersimpan dengan status Menunggu Approval.'),
    { popover: { title: 'Langkah berikutnya', description: 'Manajemen menyetujui di <b>Approval Pengajuan</b>; Finance <b>Tandai Paid</b> setelah dibayar.' }, lanjut: { label: 'Ke Approval', run: () => go('pbAppr'), ke: 'pbAppr' } }
  ];
  TOUR.pbAppr = () => [
    tS(null, 'Approval Pengajuan', 'Pengajuan biaya dan overbudget yang menunggu persetujuan <b>Manajemen</b>.'),
    tS('#view .card .tabs', 'Tab', 'Pengajuan dan Overbudget.'),
    tS(tGear(), 'Aksi', '<b>Lihat</b>: detail. <b>Proses</b>: setujui, atau tolak dengan alasan. Pengaju tidak dapat menyetujui pengajuannya sendiri.')
  ];
