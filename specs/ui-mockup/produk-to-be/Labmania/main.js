/* Labmania mockup — Perakitan: peta LIST / VIEWS / ACTIONS, render, event. Dimuat paling akhir.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Render ----------
  const LIST = { paket: listPaket, katalog: listKatalog, peserta: listPeserta, company: listCompany, trainer: listTrainer, ketQtn: listKetQtn, kfTpl: listKfTpl, user: listUser, role: listRole, rate: listRate, mprog: listMprog, quot: listQuot, qtn: listQtn, ivc: listIvc, pb: () => (state.pbTab === 'diajukan' ? listPbSent() : listPbProg()), pbAppr: listPbAppr, mpst: listMpst, akses: listAkses, actlog: listActLog };
  const currentList = () => MASTERS[state.view] ? listMaster(state.view) : LIST[state.view]();
  const VIEWS = {
    dashboard: viewDashboard, paket: viewPaket, combine: viewCombine, katalog: viewKatalog, peserta: viewPeserta, company: viewCompany, trainer: viewTrainer, ketQtn: viewKetQtn, kfTpl: viewKfTpl,
    user: viewUser, role: viewRole, rate: viewRate, mprog: viewMprog, quot: viewQuot, qtn: viewQtn, qtnDetail: viewQtnDetail, mpForm: viewCostForm, qtForm: viewCostForm, ivc: viewIvc, ivcForm: viewIvcForm, pb: viewPb, pbProg: viewPbProg, pbForm: viewPbForm, pbAppr: viewPbAppr, mpst: viewMpst, akses: viewAkses, actlog: viewActLog
  };

  function render() {
    auditRun();
    renderNav();
    renderMe();
    const v = state.view;
    const navKey = SUBVIEW[v] ? SUBVIEW[v][0] : v;
    if (!can(navKey)) { $('#view').innerHTML = `${pageHead('Tidak ada akses', `Role <b>${esc(meRole() || '—')}</b> tidak punya hak View untuk menu ini. Atur di Master Data › Role & Permission, atau beri hak khusus user ini lewat Override Permission di Master User.`, 'FR-57 · FR-65')}`; return; }
    $('#view').innerHTML = MASTERS[v] ? viewMaster(v) : VIEWS[v]();
    if (state.view === 'ivcForm') ivcBind();
    if (state.view === 'pbForm') pbBind();
  }

  // ---------- Events ----------
  const mpById = el => db.mprog.find(x => x.id === +el.dataset.id);
  const qtById = el => db.quot.find(x => x.id === +el.dataset.id);
  const qtnById = el => db.qtn.find(x => x.id === +el.dataset.id);
  const wideNav = () => window.matchMedia('(min-width: 901px)').matches; // batas sama dengan @media sidebar di style.css
  const ACTIONS = {
    // Layar lebar: tombol menu memunculkan lagi sidebar yang disembunyikan; layar sempit: membuka drawer (FR-66).
    'nav-open': () => {
      if (wideNav()) { document.body.classList.remove('nav-hidden'); $('#nav-hide') && $('#nav-hide').focus(); }
      else document.body.classList.add('nav-open');
    },
    'nav-hide': () => {
      if (wideNav()) { document.body.classList.add('nav-hidden'); $('#menu-btn').focus(); }
      else document.body.classList.remove('nav-open');
    },
    'hist-open': el => { const id = +el.dataset.id, l = db.logs.find(x => x.refId === id); go('actlog'); state.logF.refId = id; state.logF.ref = l ? l.ref : `#${id}`; render(); },
    'log-detail': el => logDetail(db.logs.find(x => x.id === +el.dataset.id)),
    'log-clear-ref': () => { state.logF.refId = null; state.page = 1; render(); },
    'nav-close': () => document.body.classList.remove('nav-open'),
    'nav-module': el => { const m = el.dataset.module; state.navOpen[m] = !state.navOpen[m]; renderNav(); },
    'master-add': () => masterForm(state.view),
    'master-edit': el => masterForm(state.view, findRec(state.view, +el.dataset.id)),
    'kt-print': () => katalogPrint(),
    'paket-edit': el => paketForm(db.paket.find(r => r.id === +el.dataset.id)),
    toggle: el => {
      const list = state.view === 'paket' ? db.paket : state.view === 'rate' ? db.rate[state.rateTab] : db[state.view];
      const r = list.find(x => x.id === +el.dataset.id);
      r.aktif = !r.aktif;
      toast(`${r.kode || r.nomor} ${r.aktif ? 'diaktifkan' : 'dinonaktifkan'}`);
      render();
    },
    'combine-new': () => {
      state.form = { judul: '', rows: [emptyRow()], errors: {}, judulErr: '' };
      state.view = 'combine';
      render();
      $('#cf-judul').focus();
    },
    'combine-cancel': () => { state.form = null; go('paket'); },
    'combine-save': saveCombine,
    'row-add': () => { state.form.rows.push(emptyRow()); state.form.errors = {}; render(); },
    'row-del': el => { state.form.rows.splice(+el.dataset.i, 1); state.form.errors = {}; render(); },
    'peserta-add': () => pesertaForm(),
    'peserta-edit': el => pesertaForm(db.peserta.find(r => r.id === +el.dataset.id)),
    'peserta-del': el => {
      const r = db.peserta.find(x => x.id === +el.dataset.id);
      const dipakai = db.qtn.find(k => k.peserta.some(x => x.id === r.id));
      if (dipakai) return toast(`Peserta ${r.kode} sudah ada di Quotation ${dipakai.nomor} — tidak dapat dihapus`, 'warn');
      confirmDelete(`${r.kode} — ${r.nama}`, () => { db.peserta.splice(db.peserta.indexOf(r), 1); toast(`Peserta ${r.kode} dihapus`); });
    },
    'company-add': () => companyForm(),
    'trainer-add': () => trainerForm(),
    'trainer-edit': el => trainerForm(trainerById(+el.dataset.id)),
    'trainer-cv': el => trainerCv(trainerById(+el.dataset.id)),
    'ket-add': () => ketForm(),
    'ket-edit': el => ketForm(db.ketQtn.find(r => r.id === +el.dataset.id)),
    'kf-add': () => kfForm(),
    'kf-edit': el => kfForm(kfTplById(+el.dataset.id)),
    'kf-preview': el => kfPreview(kfTplById(+el.dataset.id)),
    'company-edit': el => companyForm(db.company.find(r => r.id === +el.dataset.id)),
    'user-add': () => userForm(),
    'user-edit': el => userForm(db.user.find(r => r.id === +el.dataset.id)),
    'user-reset': el => userReset(db.user.find(r => r.id === +el.dataset.id)),
    'company-del': el => {
      const r = db.company.find(x => x.id === +el.dataset.id);
      confirmDelete(`${r.kode} — ${r.nama}`, () => { db.company.splice(db.company.indexOf(r), 1); toast(`Company ${r.kode} dihapus`); });
    },
    'rate-tab': el => { state.rateTab = el.dataset.tab; state.q = ''; state.status = 'all'; state.page = 1; render(); },
    'rate-add': () => rateForm(),
    'rate-edit': el => rateForm(db.rate[state.rateTab].find(r => r.id === +el.dataset.id)),
    // Master Program & Penjualan Program
    'mp-new': () => openCostForm('mp'),
    'mp-open': el => openCostForm('mp', mpById(el)),
    'mp-final': el => mpFinal(mpById(el)),
    'mp-cancel': el => mpCancel(mpById(el)),
    'qt-new': () => openCostForm('qt'),
    'qt-from-mp': el => openCostForm('qt', null, { mp: +el.dataset.id }),
    'qt-open': el => openCostForm('qt', qtById(el)),
    'dash-kpi': el => dashKpi(el.dataset.k),
    'dash-open': el => { closeModal(); (el.dataset.to === 'mp' ? ACTIONS['mp-open'] : ACTIONS['qt-open'])(el); },
    'qt-final': el => qtFinal(qtById(el)),
    'qt-cancel': el => qtCancel(qtById(el)),
    'qt-print': el => qtPrint(qtById(el)),
    'qtn-new': () => qtnForm(),
    'qtn-from-pj': el => qtnForm(null, +el.dataset.id),
    'qtn-edit': el => qtnForm(qtnById(el)),
    'qtn-print': el => qtnPrint(qtnById(el)),
    'qtn-deal': el => qtnDeal(qtnById(el)),
    'qtn-cancel': el => qtnCancel(qtnById(el)),
    'qtn-cl': el => qtnConfirm(qtnById(el)),
    'qtn-view': el => { closeModal(); qtnView(qtnById(el)); },
    'qv-back': () => { const k = db.qtn.find(x => x.id === state.qtnView); state.view = 'qtn'; state.qtnPj = k ? k.pj : null; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.page = 1; render(); window.scrollTo(0, 0); },
    'qv-filter': el => { state.qvF = el.dataset.s; render(); },
    'ivc-add': el => ivcForm(qtnById(el), state.view === 'qtn' || state.view === 'qtnDetail' ? state.view : 'ivc'),
    'ivc-back': () => { const b = state.ivp ? state.ivp.from : 'ivc'; state.ivp = null; go(b); },
    'ivc-view': el => ivcView(db.inv.find(x => x.id === +el.dataset.id)),
    'ivc-print': el => ivcPrint(db.inv.find(x => x.id === +el.dataset.id)),
    'ivc-setor': el => ivcSetor(db.inv.find(x => x.id === +el.dataset.id)),
    'mp-tab': el => { state.mpTab = el.dataset.tab; state.page = 1; render(); },
    'mp-open': el => { state.mpPj = +el.dataset.id; state.q = ''; state.page = 1; render(); window.scrollTo(0, 0); },
    'mp-back': () => { state.mpPj = null; state.q = ''; state.page = 1; render(); },
    'mp-close': el => mpClose(db.quot.find(x => x.id === +el.dataset.id)),
    'mp-print': el => mpPrintPeserta(db.quot.find(x => x.id === +el.dataset.id)),
    'mp-hadir': el => mpPrintHadir(db.quot.find(x => x.id === +el.dataset.id)),
    'mp-xls': el => mpXls(db.quot.find(x => x.id === +el.dataset.id)),
    'ivc-tab': el => { state.ivcTab = el.dataset.tab; state.ivcProg = 'all'; state.ivcYear = 'all'; state.page = 1; render(); },
    'ivc-excel': () => toast('Export Excel belum tersambung (mockup)'),
    // Role & Permission & Pengajuan Biaya
    'ak-edit': el => akEdit(+el.dataset.role),
    'role-add': () => roleForm(),
    'role-edit': el => roleForm(roleById(+el.dataset.id)),
    'role-akses': el => akEdit(+el.dataset.id),
    'role-del': el => roleDel(roleById(+el.dataset.id)),
    'pb-open': el => { state.pbPj = +el.dataset.id; go('pbProg'); },
    'pb-back': () => go('pb'),
    'pb-tab': el => { state.pbTab = el.dataset.tab; state.q = ''; state.page = 1; render(); },
    'pb-paid': el => pbPaid(db.pb.find(x => x.id === +el.dataset.id)),
    'pb-add': el => pbForm(pjById(+el.dataset.id), el.dataset.kat),
    'ob-add': el => obForm(pjById(+el.dataset.id), el.dataset.kat),
    'pbf-back': () => { const id = state.pbf ? state.pbf.pj : state.pbPj; state.pbf = null; state.pbPj = id; go('pbProg'); },
    'pb-view': el => pbView(db.pb.find(x => x.id === +el.dataset.id), false),
    'ob-view': el => pbView(db.ob.find(x => x.id === +el.dataset.id), true),
    'pb-proc': el => pbProcess(db.pb.find(x => x.id === +el.dataset.id), false),
    'ob-proc': el => pbProcess(db.ob.find(x => x.id === +el.dataset.id), true),
    'pb-cancel': el => pbCancel(db.pb.find(x => x.id === +el.dataset.id)),
    'ob-cancel': el => pbCancel(db.ob.find(x => x.id === +el.dataset.id)),
    'pb-appr-tab': el => { state.pbApprTab = el.dataset.tab; state.q = ''; state.page = 1; render(); },
    'qtn-open': el => { state.qtnPj = +el.dataset.id; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.page = 1; render(); window.scrollTo(0, 0); },
    'qtn-back': () => { state.qtnPj = null; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.page = 1; render(); },
    'qtn-tab': el => { state.qtnTab = el.dataset.tab; state.status = 'all'; state.page = 1; render(); },
    'qtn-filter': el => { state.status = state.status === el.dataset.s ? 'all' : el.dataset.s; state.page = 1; render(); },
    'qt-jenis': el => {
      const p = state.pf.p;
      Object.assign(p, fromMP(mpOf(p), el.dataset.jenis));
      // B2B minimal 10 peserta (PJ-FR-25): target master di bawah 10 dinaikkan ke 10 (usulan).
      if (p.jenis === 'b2b' && nPeserta(p) < B2B_MIN) p.targetPeserta = B2B_MIN;
      render();
    },
    'pf-back': () => { const v = state.pf && state.pf.kind === 'mp' ? 'mprog' : 'quot'; state.pf = null; go(v); },
    'pf-step': el => {
      const n = +el.dataset.n, pf = state.pf;
      pf.collapsed[n] = !pf.collapsed[n];
      el.parentElement.classList.toggle('collapsed', pf.collapsed[n]);
      el.setAttribute('aria-expanded', String(!pf.collapsed[n]));
    },
    'pf-add': el => {
      const sel = $('#' + el.dataset.sel);
      if (!sel.value) return toast('Pilih item dari Master Rate dahulu', 'warn');
      state.pf.p.biaya.push(biayaFromRate(rateById(+sel.value)));
      render();
    },
    'pf-manual': el => {
      const g = COST_GROUPS[+el.dataset.g];
      state.pf.p.biaya.push({ rate: null, tipe: g.tipe, item: '', satuan: g.satuan, mode: 'manual', manual: true, harga: '' });
      render();
    },
    'pf-del': el => { state.pf.p.biaya.splice(+el.dataset.i, 1); state.pf.errors = {}; render(); },
    'pf-save': () => saveCost(false),
    'pf-confirm': () => {
      const rec = saveCost(true);
      if (rec) (state.pf.kind === 'mp' ? mpFinal : qtFinal)(rec);
    }
  };

  function openGear(btn) {
    const root = $('#menu-root');
    const wasOpen = root.dataset.owner && btn.classList.contains('open');
    closeGear();
    if (wasOpen) return;
    root.innerHTML = `<div class="menu" role="menu">${btn.nextElementSibling.innerHTML}</div>`;
    root.dataset.owner = '1';
    btn.classList.add('open');
    const menu = root.firstChild, b = btn.getBoundingClientRect();
    const top = b.bottom + 4 + menu.offsetHeight > window.innerHeight ? b.top - 4 - menu.offsetHeight : b.bottom + 4;
    menu.style.top = `${top}px`;
    menu.style.left = `${Math.max(8, b.right - menu.offsetWidth)}px`;
  }

  function closeGear() {
    const root = $('#menu-root');
    root.innerHTML = '';
    delete root.dataset.owner;
    document.querySelectorAll('.gear.open').forEach(g => g.classList.remove('open'));
  }

  document.addEventListener('click', e => {
    const el = e.target.closest('[data-act], [data-view], [data-page]');
    if (el && el.dataset.act === 'gear') return openGear(el);
    closeGear();
    if (!el || el.disabled) return;
    if (el.dataset.view) return go(el.dataset.view);
    if (el.dataset.page) { state.page = +el.dataset.page; $('#list').innerHTML = currentList(); return; }
    if (el.dataset.act === 'modal-close') {
      if (el.classList.contains('overlay') && e.target !== el) return;
      return closeModal();
    }
    const fn = ACTIONS[el.dataset.act];
    if (fn) fn(el);
  });

  const NUM_FIELDS = ['durasi', 'targetPeserta', 'hargaJual', 'targetProfit'];
  const TEXT_FIELDS = ['tglMulai', 'tglSelesai', 'waktu', 'lokasi'];
  const inCostForm = () => state.view === 'mpForm' || state.view === 'qtForm';
  function onProgramInput(t) {
    const p = state.pf.p;
    if (t.dataset.pf) {
      const k = t.dataset.pf;
      const raw = t.classList.contains('money-in') ? digits(t.value) : t.value;
      p[k] = NUM_FIELDS.includes(k) ? num(raw) : TEXT_FIELDS.includes(k) ? raw : (raw ? Number(raw) : '');
      // Input simulasi ganda (bagian 1 & 4): salin nilai ke kembarannya.
      document.querySelectorAll(`[data-pf="${k}"]`).forEach(el => { if (el !== t) el.value = t.value; });
      // Penjualan Program: durasi mengikuti tanggal.
      const dt = isQt() && (k === 'tglMulai' || k === 'tglSelesai') ? durasiTgl(p) : null;
      if (dt) { p.durasi = dt; document.querySelectorAll('[data-pf="durasi"]').forEach(el => { el.value = dt; }); }
    } else {
      const b = p.biaya[+t.dataset.bi];
      b[t.dataset.bf] = t.dataset.bf === 'harga' ? num(digits(t.value)) : t.value;
    }
    refreshCalc();
  }

  document.addEventListener('input', e => {
    const t = e.target;
    if (t.id === 'nav-q') { state.navQ = t.value; renderNav(); }
    if (t.id === 'qv-q') { state.qvQ = t.value; render(); const el = $('#qv-q'); el.focus(); el.setSelectionRange(el.value.length, el.value.length); return; }
    if (t.id === 'q') { state.q = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'cf-judul') state.form.judul = t.value;
    if (inCostForm() && (t.dataset.pf || t.dataset.bi) && t.tagName === 'INPUT') onProgramInput(t);
  });

  document.addEventListener('change', e => {
    const t = e.target;
    if (t.id === 'status' || t.id === 'fstatus') { state.status = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id && t.id.startsWith('lf-')) { state.logF[t.id.slice(3)] = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'me-sel') { state.me = +t.value; toast(`Login sebagai ${meName()} (mockup)`); if (state.view === 'actlog' && !canLog()) go('dashboard'); else render(); }
    if (t.id === 'fprog') { state.ivcProg = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'fyear') { state.ivcYear = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'kt-bidang' || t.id === 'kt-sert') { state[t.id === 'kt-bidang' ? 'ktBidang' : 'ktSert'] = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'fpbj' || t.id === 'fpbs') { state[t.id === 'fpbj' ? 'pbJ' : 'pbS'] = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'fjenis') { state.jenis = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.dataset.field && state.form) {
      state.form.rows[+t.dataset.row][t.dataset.field] = Number(t.value) || '';
    }
    if (inCostForm() && t.dataset.pf && t.tagName === 'SELECT') {
      onProgramInput(t);
      render(); // tampilkan atribut paket terpilih
    }
    if (t.id === 'qt-mp') { state.pf.p.mp = Number(t.value) || ''; render(); }
  });

  document.addEventListener('keydown', e => {
    // Enter di search menu = buka hasil pertama; Esc = kosongkan pencarian.
    if (e.target.id === 'nav-q') {
      if (e.key === 'Enter') { const first = $('#nav .nav-item'); if (first) go(first.dataset.view); return; }
      if (e.key === 'Escape' && state.navQ) { e.target.value = ''; state.navQ = ''; renderNav(); return; }
    }
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.act === 'pf-step') { e.preventDefault(); ACTIONS['pf-step'](e.target); return; }
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.act === 'dash-kpi') { e.preventDefault(); dashKpi(e.target.dataset.k); return; }
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.act === 'qtn-filter') { e.preventDefault(); ACTIONS['qtn-filter'](e.target); return; }
    if (e.key === 'Escape') { closeGear(); closeModal(); document.body.classList.remove('nav-open'); }
  });

  document.addEventListener('toggle', e => { if (e.target.id === 'sim-rinci' && state.pf) state.pf.rinciOpen = e.target.open; }, true);
  window.addEventListener('resize', closeGear);
  window.addEventListener('scroll', closeGear, true);

  render();
