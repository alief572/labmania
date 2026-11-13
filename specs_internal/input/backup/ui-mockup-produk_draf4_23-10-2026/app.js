/* Labmania — Modul Master Data & Program Penjualan (mockup)
   Acuan perilaku: specs/docs/fr-nfr.md draf 4 dan specs/docs/fr-nfr-program-penjualan.md draf 1.
   Data disimpan di memori saja. Penanda Q-xx = pertanyaan terbuka yang belum dijawab Labmania. */
(function () {
  'use strict';

  // ---------- Helpers ----------
  let uidSeq = 0;
  const uid = () => ++uidSeq;
  const $ = (sel, root = document) => root.querySelector(sel);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const stripTags = s => String(s).replace(/<[^>]+>/g, '').trim();
  const rp = n => (n == null || n === '' || isNaN(n)) ? '—'
    : `${n < 0 ? '−' : ''}Rp ${Math.abs(Math.round(n)).toLocaleString('id-ID')}`;
  const num = v => (v === '' || v == null) ? '' : Number(v);
  const clone = o => JSON.parse(JSON.stringify(o));

  // Format kode belum ditetapkan (Q-01, Q-PP-08) — prefix + 3 digit hanya contoh.
  const codeSeq = {};
  const pad = n => String(n).padStart(3, '0');
  const nextCode = p => { codeSeq[p] = (codeSeq[p] || 0) + 1; return `${p}-${pad(codeSeq[p])}`; };
  const peekCode = p => `${p}-${pad((codeSeq[p] || 0) + 1)}`;

  const q = (id, text) => `<span class="q" title="${esc(text)}">${id}</span>`;
  const Q01 = q('Q-01', 'Format penomoran kode menunggu standar dari Labmania. Contoh di mockup: PREFIX-001.');

  // ---------- Master config ----------
  const MASTERS = {
    jenisProduk: {
      title: 'Jenis Produk', code: 'Kode Jenis Product', name: 'Jenis Product', form: 'Jenis Product',
      prefix: 'JP', fr: 'FR-04', desc: 'Jenis produk yang dijual Labmania, mis. Pelatihan atau Sertifikasi.'
    },
    jenisSertifikasi: {
      title: 'Jenis Sertifikasi', code: 'Kode Sertifikasi', name: 'Jenis Sertifikasi', form: 'Jenis Sertifikasi',
      prefix: 'JS', fr: 'FR-05', desc: 'Jenis sertifikat yang diterima peserta, mis. Sertifikat BNSP.'
    },
    jenisBidang: {
      title: 'Jenis Bidang', code: 'Kode Bidang', name: 'Jenis Bidang', form: 'Jenis Bidang',
      prefix: 'JB', fr: 'FR-06', desc: 'Bidang keilmuan/standar yang dibahas di pelatihan. Juga dipakai untuk mengelompokkan peserta.'
    },
    varian: {
      title: 'Varian Pelaksanaan', code: 'Kode Varian', name: 'Varian', form: 'Jenis Varian',
      prefix: 'VP', fr: 'FR-07', desc: 'Cara melaksanakan suatu jenis bidang.'
    }
  };

  // Urutan kolom mengikuti mockup klien: Produk, Bidang, Varian, Sertifikasi.
  const ATTRS = [
    { key: 'jenisProduk', field: 'produk', label: 'Produk' },
    { key: 'jenisBidang', field: 'bidang', label: 'Bidang' },
    { key: 'varian', field: 'varian', label: 'Varian' },
    { key: 'jenisSertifikasi', field: 'sertifikasi', label: 'Sertifikasi' }
  ];

  // ---------- Data awal (fr-nfr.md bagian 7) ----------
  const db = {};
  const seedMaster = (key, names) => {
    db[key] = names.map(nama => ({ id: uid(), kode: nextCode(MASTERS[key].prefix), nama, aktif: true }));
  };
  seedMaster('jenisProduk', ['Pelatihan', 'Sertifikasi']);
  seedMaster('jenisSertifikasi', ['Sertifikat BNSP', 'Sertifikat KAN ISO 29993', 'Sertifikat Labmania',
    'Sertifikat Labmania KAN ISO 29993', 'Sertifikat Labmania Lisensi BSN']);
  seedMaster('jenisBidang', ['Analisis Kimia', 'Analisis Mikrobiologi', 'Energi', 'ISO 17025', 'ISO 17025 Manajemen',
    'ISO 22000 Manajemen', 'ISO 9001 Manajemen', 'K3', 'Kalibrasi', 'Kimia', 'Mikrobiologi', 'Okupasi', 'Pangan',
    'Pengelolaan Limbah', 'Penyelia', 'Petugas Sampling', 'Trainer']);
  seedMaster('varian', ['In House Training', 'Workshop', 'Workshop Berbasis Kompetensi', 'Workshop Lisensi BSN']);

  const idOf = (key, nama) => db[key].find(r => r.nama === nama).id;
  const findRec = (key, id) => db[key].find(r => r.id === id);

  // Paket contoh, agar daftar tidak kosong.
  db.paket = [];
  const seedPaket = (judul, produk, bidang, varian, sertifikasi) => db.paket.push({
    id: uid(), nomor: nextCode('PC'), judul, aktif: true,
    produk: idOf('jenisProduk', produk), bidang: idOf('jenisBidang', bidang),
    varian: idOf('varian', varian), sertifikasi: idOf('jenisSertifikasi', sertifikasi)
  });
  seedPaket('Pelatihan ISO/IEC 17025:2017', 'Pelatihan', 'ISO 17025', 'In House Training', 'Sertifikat Labmania');
  seedPaket('Pelatihan ISO/IEC 17025:2017', 'Pelatihan', 'ISO 17025', 'Workshop', 'Sertifikat Labmania');
  seedPaket('Sertifikasi Petugas Sampling', 'Sertifikasi', 'Petugas Sampling', 'Workshop Berbasis Kompetensi', 'Sertifikat BNSP');

  // Contoh data wilayah — sumber data wilayah masih Q-07b.
  const WILAYAH = {
    'Banten': ['Kota Cilegon', 'Kota Serang', 'Kota Tangerang', 'Kabupaten Tangerang'],
    'DKI Jakarta': ['Jakarta Barat', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara'],
    'Jawa Barat': ['Kota Bandung', 'Kota Bekasi', 'Kota Bogor', 'Kota Depok', 'Kabupaten Karawang'],
    'Jawa Tengah': ['Kota Semarang', 'Kota Surakarta', 'Kabupaten Kudus'],
    'Jawa Timur': ['Kota Malang', 'Kota Surabaya', 'Kabupaten Gresik', 'Kabupaten Sidoarjo']
  };

  db.peserta = [
    ['Contoh Peserta A', 'Perempuan', 'PT Contoh Analitika', 'Analisis Kimia', 'Jawa Barat', 'Kota Bandung', '081234567890', 'peserta.a@contoh.id'],
    ['Contoh Peserta B', 'Laki-laki', 'Laboratorium Contoh Mandiri', 'Kalibrasi', 'Jawa Timur', 'Kota Surabaya', '082198765432', 'peserta.b@contoh.id'],
    ['Contoh Peserta C', 'Perempuan', 'CV Contoh Pangan', 'Pangan', 'DKI Jakarta', 'Jakarta Timur', '085711122233', 'peserta.c@contoh.id']
  ].map(([nama, gender, instansi, bidang, provinsi, kota, telp, email]) => ({
    id: uid(), kode: nextCode('PS'), nama, gender, instansi, bidang: idOf('jenisBidang', bidang), provinsi, kota, telp, email
  }));

  // Master Company (FR-30 – FR-32). Kode & kolom daftar masih usulan.
  const JENIS_INSTANSI = ['Swasta', 'BUMN/BUMD', 'Pemerintah', 'Pendidikan', 'Lainnya'];
  db.company = [
    { nama: 'PT Contoh Laboratorium Nusantara', jenis: 'Swasta', alamat: 'Jl. Contoh Raya No. 10', provinsi: 'Jawa Barat', kota: 'Kota Bekasi', telp: '0218881234', email: 'info@contohlab.co.id', picNama: 'Contoh PIC Satu', picJabatan: 'Manajer Mutu', picTelp: '081311112222', picEmail: 'pic1@contohlab.co.id', npwp: '012345678901000' },
    { nama: 'Balai Contoh Pengujian Daerah', jenis: 'Pemerintah', alamat: 'Jl. Contoh Dinas No. 2', provinsi: 'Jawa Timur', kota: 'Kota Surabaya', telp: '0317654321', email: 'balai@contoh.go.id', picNama: 'Contoh PIC Dua', picJabatan: 'Kepala Seksi', picTelp: '081233334444', picEmail: 'pic2@contoh.go.id', npwp: '' }
  ].map(c => Object.assign({ id: uid(), kode: nextCode('CO') }, c));

  const COA = ['6101 · Biaya Trainer', '6102 · Biaya Transport & Akomodasi', '6103 · Biaya Konsumsi',
    '6104 · Biaya Perlengkapan Training', '6105 · Biaya Pemasaran', '6106 · Biaya Administrasi'];

  // Mode harga (FR-33): default = boleh diubah di costing, fix = terkunci, manual = diisi saat costing.
  const MODE = {
    default: { label: 'Default', hint: 'Default Cost dipakai sebagai nilai awal dan boleh diubah di costing program.' },
    fix: { label: 'Fix', hint: 'Default Cost tidak bisa diubah di costing; ubah lewat Master Rate.' },
    manual: { label: 'Input manual', hint: 'Tidak ada Default Cost; harga diisi saat costing program.' }
  };
  const Q21 = q('Q-21', 'Mode harga item ini tidak tertulis di Cost Master; mockup memakai Default.');

  const seedRate = (prefix, tipe, rows) => rows.map(([item, satuan, cost, mode, ket, coa]) =>
    ({ id: uid(), kode: nextCode(prefix), tipe, item, satuan, cost, mode, ket, coa, aktif: true }));
  db.rate = {
    fixed: seedRate('FC', 'fixed', [
      ['Transport Trainer Luar Kota PP', 'Event', 250000, 'default', 'Transport / Hari', COA[1]],
      ['Marketing/Ads', 'Event', 0, 'default', 'Iklan', COA[4]],
      ['Dokumentasi', 'Event', 0, 'default', 'Dokumentasi', COA[5]],
      ['Banner/Promosi', 'Event', 100000, 'default', 'Promosi', COA[4]],
      ['Administrasi', 'Event', 0, 'default', 'Administrasi', COA[5]],
      ['Mandays Rate Charge Labmania', 'Event', 1600000, 'fix', 'Sumber: per Jam → Event', COA[0]],
      ['Honor Trainer', 'Event', 2125000, 'default', 'Sumber: per Jam → Event', COA[0]],
      ['Hotel Trainer **', 'Day', 375000, 'default', 'Hotel bintang 2', COA[1]],
      ['Hotel Trainer ***', 'Day', 0, 'default', 'Hotel bintang 3', COA[1]],
      ['Konsumsi Trainer, di luar konsumsi selama training', 'Day', 140000, 'default', 'Konsumsi trainer / Hari', COA[2]]
    ]),
    variable: seedRate('VC', 'variable', [
      ['Modul', 'Peserta/Event', 50000, 'default', 'Modul', COA[3]],
      ['Sertifikat', 'Peserta/Event', 5000, 'fix', 'Sertifikat', COA[3]],
      ['Seminar Kit', 'Peserta/Event', 0, 'manual', 'Kit (acuan Rp 35.000)', COA[3]],
      ['Goodybag', 'Peserta/Event', 5000, 'fix', 'Goodybag', COA[3]],
      ['Merchandise', 'Peserta/Event', 0, 'fix', 'Merchandise', COA[3]],
      ['Sertifikasi LSP', 'Peserta/Event', 0, 'manual', 'Sertifikasi LSP', COA[0]],
      ['Ongkir Dokumen', 'Peserta/Event', 0, 'manual', 'Ongkir Dokumen (acuan Rp 15.000)', COA[5]],
      ['Makan Siang Training', 'Peserta/Day', 50000, 'default', 'Untuk peserta + trainer + asisten', COA[2]],
      ['Coffee Break', 'Peserta/Day', 40000, 'default', 'Untuk peserta + trainer + asisten', COA[2]]
    ])
  };
  const NO_Q21 = ['Hotel Trainer ***', 'Modul'];
  // Satuan dibatasi per tab (FR-25): Hours dicatat sebagai Event, Mandays bukan satuan.
  const RATE_TAB = {
    fixed: { title: 'Fixed Cost', prefix: 'FC', fr: 'FR-20', satuan: ['Event', 'Day'] },
    variable: { title: 'Variable Cost', prefix: 'VC', fr: 'FR-19', satuan: ['Peserta/Event', 'Peserta/Day'] }
  };
  const rateById = id => db.rate.fixed.concat(db.rate.variable).find(r => r.id === id);
  const rateByItem = item => db.rate.fixed.concat(db.rate.variable).find(r => r.item === item);

  // ---------- Program Penjualan (fr-nfr-program-penjualan.md) ----------
  const STATUS = {
    draft: 'Draft', final: 'Final', deal: 'Deal', selesai: 'Selesai', batal: 'Batal'
  };
  const JENIS = { public: 'Public', b2b: 'B2B' };

  const biayaFromRate = (r, harga) => ({
    rate: r.id, tipe: r.tipe, item: r.item, satuan: r.satuan, mode: r.mode, manual: false,
    harga: harga !== undefined ? harga : (r.mode === 'manual' ? '' : r.cost)
  });

  // Faktor pengali per satuan (bagian 8): Event ×1, Day ×durasi; Peserta/Event ×1, Peserta/Day ×durasi (per peserta).
  const factor = (b, durasi) => (b.satuan === 'Day' || b.satuan === 'Peserta/Day') ? (Number(durasi) || 0) : 1;
  const subtotal = (b, durasi) => (Number(b.harga) || 0) * factor(b, durasi);

  function calc(p) {
    const d = Number(p.durasi) || 0;
    if (p.jenis === 'b2b') {
      const total = p.biaya.reduce((s, b) => s + subtotal(b, d), 0);
      const harga = Number(p.hargaPaket) || 0;
      return { total, bersih: harga - total };
    }
    const fixed = p.biaya.filter(b => b.tipe === 'fixed').reduce((s, b) => s + subtotal(b, d), 0);
    const varPer = p.biaya.filter(b => b.tipe === 'variable').reduce((s, b) => s + subtotal(b, d), 0);
    const target = Number(p.targetPeserta) || 0;
    const harga = Number(p.hargaJual) || 0;
    const totalVar = varPer * target;
    const hpp = fixed + totalVar;
    const pendapatan = harga * target;
    const profit = pendapatan - hpp;
    const bepHarga = target ? hpp / target : null;
    const bepPeserta = harga > varPer ? Math.ceil(fixed / (harga - varPer)) : null; // PP-NFR-05
    return { fixed, varPer, totalVar, hpp, pendapatan, profit, bepHarga, bepPeserta, target, harga };
  }

  const pidOf = nomor => db.paket.find(p => p.nomor === nomor).id;
  db.program = [];
  const seedProgram = o => {
    const p = Object.assign({ id: uid(), nomor: nextCode('PRG'), targetProfit: '', pesertaAktual: '' }, o);
    db.program.push(p);
    return p;
  };
  const allFixed = () => db.rate.fixed.filter(r => r.aktif).map(r => biayaFromRate(r));
  seedProgram({
    jenis: 'public', status: 'draft', paket: pidOf('PC-001'), durasi: 2, targetPeserta: 25, hargaJual: 600000, targetProfit: 3000000,
    biaya: allFixed().concat(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break'].map(i => biayaFromRate(rateByItem(i))),
      [biayaFromRate(rateByItem('Seminar Kit'), 35000)])
  });
  seedProgram({
    jenis: 'public', status: 'selesai', paket: pidOf('PC-003'), durasi: 1, targetPeserta: 20, hargaJual: 750000, pesertaAktual: 17,
    biaya: allFixed().concat(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break'].map(i => biayaFromRate(rateByItem(i))))
  });
  seedProgram({
    jenis: 'b2b', status: 'deal', paket: pidOf('PC-002'), customer: db.company[0].id, durasi: 3, hargaPaket: 45000000, jumlahPeserta: 20,
    biaya: ['Honor Trainer', 'Mandays Rate Charge Labmania', 'Hotel Trainer **', 'Transport Trainer Luar Kota PP', 'Konsumsi Trainer, di luar konsumsi selama training']
      .map(i => biayaFromRate(rateByItem(i)))
      .concat([{ rate: null, tipe: 'fixed', item: 'Sewa alat peraga', satuan: 'Event', mode: 'manual', manual: true, harga: 1500000 }])
  });

  // ---------- State & navigation ----------
  const state = {
    view: 'jenisProduk', q: '', status: 'all', jenis: 'all', page: 1, rateTab: 'fixed', form: null, pf: null,
    navOpen: { 'Master Data': true, 'Program Penjualan': true }, navQ: ''
  };

  // Dua modul utama (FR-27); grup di bawahnya adalah sub modul.
  const ICON_DB = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>';
  const ICON_PROG = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M7 14h4M7 17h7"/></svg>';
  const MODULES = [
    {
      name: 'Master Data', icon: ICON_DB, groups: [
        { group: 'Atribut Produk', items: [['jenisProduk', 'Jenis Produk'], ['jenisSertifikasi', 'Jenis Sertifikasi'], ['jenisBidang', 'Jenis Bidang'], ['varian', 'Varian Pelaksanaan']] },
        { group: 'Produk', items: [['paket', 'Product Combine'], ['katalog', 'Katalog Produk']] },
        { group: 'Customer', items: [['peserta', 'Master Peserta'], ['company', 'Master Company']] },
        { group: 'Biaya', items: [['rate', 'Master Rate']] }
      ]
    },
    {
      name: 'Program Penjualan', icon: ICON_PROG, groups: [
        { group: 'Program', items: [['program', 'Daftar Program']] },
        { group: 'Laporan', items: [['laporan', 'As Is vs Aktual']] }
      ]
    }
  ];
  const countOf = v => MASTERS[v] ? db[v].length
    : v === 'paket' ? db.paket.length
      : v === 'peserta' ? db.peserta.length
        : v === 'company' ? db.company.length
          : v === 'rate' ? db.rate.fixed.length + db.rate.variable.length
            : v === 'program' ? db.program.length : null;
  const SUBVIEW = { combine: ['paket', 'Tambah Product'], programForm: ['program', null] };

  function go(view) {
    state.view = view; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.page = 1;
    state.navQ = ''; $('#nav-q').value = '';
    document.body.classList.remove('nav-open');
    render();
    window.scrollTo(0, 0);
  }

  function renderNav() {
    const navView = SUBVIEW[state.view] ? SUBVIEW[state.view][0] : state.view;
    // Search menu: cocokkan nama menu atau nama sub modul di semua modul; saat mencari, modul selalu terbuka.
    const kw = state.navQ.trim().toLowerCase();
    const mark = s => {
      const i = kw ? s.toLowerCase().indexOf(kw) : -1;
      return i < 0 ? esc(s) : `${esc(s.slice(0, i))}<mark>${esc(s.slice(i, i + kw.length))}</mark>${esc(s.slice(i + kw.length))}`;
    };
    const html = MODULES.map(m => {
      const groups = m.groups.map(g => {
        const items = !kw || g.group.toLowerCase().includes(kw) ? g.items
          : g.items.filter(([, label]) => label.toLowerCase().includes(kw));
        return items.length ? `<div class="nav-group"><div class="nav-label">${mark(g.group)}</div>${items.map(([v, label]) => {
          const n = countOf(v);
          return `<button class="nav-item ${v === navView ? 'active' : ''}" data-view="${v}">${mark(label)}${n === null ? '' : `<span class="nav-count">${n}</span>`}</button>`;
        }).join('')}</div>` : '';
      }).join('');
      if (kw && !groups) return '';
      const open = state.navOpen[m.name] || kw;
      return `<div class="nav-module ${open ? 'open' : ''}">
        <button class="nav-module-head" data-act="nav-module" data-module="${esc(m.name)}" aria-expanded="${!!open}">
          ${m.icon}<span>${m.name}</span><i class="caret"></i>
        </button>
        <div class="nav-module-body">${groups}</div>
      </div>`;
    }).join('') || '<div class="nav-empty">Menu tidak ditemukan.</div>';
    $('#nav').innerHTML = html;

    let mod, group, label;
    MODULES.forEach(m => m.groups.forEach(g => g.items.forEach(([v, l]) => { if (v === navView) { mod = m; group = g; label = l; } })));
    let leaf = label;
    if (state.view === 'combine') leaf = 'Tambah Product';
    if (state.view === 'programForm') leaf = state.pf.isNew ? 'Buat Program' : state.pf.p.nomor;
    $('#crumb').innerHTML = `${mod.name} / ${group.group} / ${state.view in SUBVIEW ? `${label} / ` : ''}<b>${esc(leaf)}</b>`;
  }

  // ---------- Shared view pieces ----------
  const pageHead = (title, desc, ref, actions = '') =>
    `<div class="page-head"><div><h1>${title}<span class="ref">${ref}</span></h1><p>${desc}</p></div>${actions ? `<div class="page-actions">${actions}</div>` : ''}</div>`;

  const badge = aktif => `<span class="badge ${aktif ? 'on' : 'off'}">${aktif ? 'Aktif' : 'Nonaktif'}</span>`;
  const stBadge = s => `<span class="badge st-${s}">${STATUS[s]}</span>`;
  const jenisTag = j => `<span class="tag tag-${j}">${JENIS[j]}</span>`;
  const modeTag = m => `<span class="tag tag-mode-${m}" title="${esc(MODE[m].hint)}">${MODE[m].label}</span>`;

  const field = (label, control, err = '', cls = '') =>
    `<div class="field ${err ? 'has-err' : ''} ${cls}"><label>${label}</label>${control}${err ? `<div class="err"><i>!</i>${esc(err)}</div>` : ''}</div>`;

  const SEARCH_ICON = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>';
  const selectFilter = (id, label, value, opts) => `<select class="input sm" id="${id}" aria-label="${label}">
    ${opts.map(([v, l]) => `<option value="${v}" ${value === v ? 'selected' : ''}>${l}</option>`).join('')}</select>`;

  function toolbar(placeholder, withStatus = true, extra = '') {
    return `<div class="toolbar">
      <div class="search">${SEARCH_ICON}
        <input type="search" class="input" id="q" placeholder="${placeholder}" value="${esc(state.q)}" aria-label="Cari">
      </div>
      ${withStatus ? selectFilter('status', 'Filter status', state.status, [['all', 'Semua status'], ['on', 'Aktif'], ['off', 'Nonaktif']]) : ''}
      ${extra}
    </div>`;
  }

  const statusOk = r => state.status === 'all' || (state.status === 'on') === r.aktif;
  const matches = values => !state.q || values.some(v => String(v || '').toLowerCase().includes(state.q.toLowerCase()));

  const PAGE_SIZE = 10;
  function listBlock(rows, cols) {
    const total = rows.length;
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (state.page > pages) state.page = pages;
    const start = (state.page - 1) * PAGE_SIZE;
    const slice = rows.slice(start, start + PAGE_SIZE);
    const head = cols.map(c => `<th class="${c.cls || ''}">${c.label}</th>`).join('');
    const body = slice.length
      ? slice.map((r, i) => `<tr class="${r.aktif === false || r.status === 'batal' ? 'is-off' : ''}">${cols.map(c =>
        `<td class="${c.cls || ''}" data-label="${esc(stripTags(c.label))}">${c.render(r, start + i + 1)}</td>`).join('')}</tr>`).join('')
      : `<tr><td colspan="${cols.length}" class="empty">Tidak ada data yang cocok.</td></tr>`;

    let btns = `<button class="pg" data-page="${state.page - 1}" ${state.page === 1 ? 'disabled' : ''} aria-label="Sebelumnya">‹</button>`;
    for (let p = 1; p <= pages; p++) btns += `<button class="pg ${p === state.page ? 'on' : ''}" data-page="${p}">${p}</button>`;
    btns += `<button class="pg" data-page="${state.page + 1}" ${state.page === pages ? 'disabled' : ''} aria-label="Berikutnya">›</button>`;
    const info = total ? `Menampilkan ${start + 1}–${start + slice.length} dari ${total} data` : '0 data';

    return `<div class="table-wrap"><table class="table"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table></div>
      <div class="pager"><span>${info}</span><div class="pg-btns">${btns}</div></div>`;
  }

  const NO_COL = { label: 'No', cls: 'num', render: (r, n) => n };
  const STATUS_COL = { label: 'Status', cls: 'nowrap', render: r => badge(r.aktif) };
  // Kolom Action: ikon gear, opsi muncul di menu melayang (di luar .table-wrap agar tidak terpotong).
  const GEAR = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>';
  const gearMenu = (r, items) => `<button class="gear" data-act="gear" aria-haspopup="menu" aria-label="Opsi">${GEAR}</button>
    <template>${items.map(([act, label, danger]) => `<button class="menu-item ${danger ? 'danger' : ''}" role="menuitem" data-act="${act}" data-id="${r.id}">${label}</button>`).join('')}</template>`;
  const toggleItem = r => ['toggle', r.aktif ? 'Nonaktifkan' : 'Aktifkan'];

  const req = ' <span class="req">*</span>';
  const opts = (list, sel) => list.map(x => `<option ${x === sel ? 'selected' : ''}>${esc(x)}</option>`).join('');

  // ---------- Master atribut produk (FR-04 – FR-09, FR-21) ----------
  function viewMaster(key) {
    const m = MASTERS[key];
    return `${pageHead(m.title, m.desc, m.fr, `<button class="btn btn-accent" data-act="master-add">+ Tambah ${m.title}</button>`)}
      <div class="notice"><span>Data master <b>tidak dapat dihapus</b> — gunakan <b>Nonaktifkan</b>. Data nonaktif tidak muncul sebagai pilihan di form Product Combine dan Peserta.</span><span class="ref">FR-08 · FR-21</span></div>
      <div class="card">${toolbar('Cari kode atau nama…')}<div id="list">${listMaster(key)}</div></div>`;
  }

  function listMaster(key) {
    const m = MASTERS[key];
    const rows = db[key].filter(r => statusOk(r) && matches([r.kode, r.nama]));
    return listBlock(rows, [
      NO_COL,
      { label: m.code + Q01, render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: m.name, render: r => esc(r.nama) },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['master-edit', 'Edit'], toggleItem(r)]) }
    ]);
  }

  function masterForm(key, rec) {
    const m = MASTERS[key];
    openModal({
      title: `${rec ? 'Edit' : 'Tambah'} ${m.title}`,
      body: field(m.code + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode(m.prefix))}" readonly><span class="hint">Diisi otomatis oleh sistem saat disimpan</span>`)
        + field(`${m.form}${req}`, `<input class="input" name="nama" maxlength="100" value="${esc(rec ? rec.nama : '')}" placeholder="Ketik ${m.form.toLowerCase()}">`),
      onSave: f => {
        const nama = f.nama.value.trim();
        if (!nama) return invalid(f.nama, `${m.form} wajib diisi`);
        if (rec) {
          rec.nama = nama;
          toast(`${m.title} ${rec.kode} diperbarui`);
        } else {
          const r = { id: uid(), kode: nextCode(m.prefix), nama, aktif: true };
          db[key].push(r);
          state.page = Math.ceil(db[key].length / PAGE_SIZE);
          toast(`${m.title} ${r.kode} ditambahkan`);
        }
      }
    });
  }

  // ---------- Product Combine / Paket Produk (FR-10 – FR-13, FR-22 – FR-24) ----------
  function attrCell(a, id) {
    const rec = findRec(a.key, id);
    if (!rec) return '—';
    return rec.aktif ? esc(rec.nama)
      : `<span class="attr-off" title="${a.label} ini sudah dinonaktifkan">${esc(rec.nama)}</span><br><span class="muted">(nonaktif)</span>`;
  }

  function viewPaket() {
    return `${pageHead('Product Combine', 'Daftar paket produk hasil combine. Paket berstatus Aktif dapat dipilih saat membuat Program di modul Program Penjualan.', 'FR-10 · FR-23 · FR-24',
      '<button class="btn btn-accent" data-act="combine-new">+ Tambah Product</button>')}
      <div class="card paket-list">${toolbar('Cari nomor, judul, atau atribut…')}<div id="list">${listPaket()}</div></div>`;
  }

  function listPaket() {
    const rows = db.paket.filter(r => statusOk(r) &&
      matches([r.nomor, r.judul, ...ATTRS.map(a => (findRec(a.key, r[a.field]) || {}).nama)]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + Q01, cls: 'nowrap', render: r => `<span class="code">${esc(r.nomor)}</span>` },
      { label: 'Judul Topik', cls: 'title', render: r => esc(r.judul) },
      ...ATTRS.map(a => ({ label: a.label, render: r => attrCell(a, r[a.field]) })),
      { label: 'Status', cls: 'nowrap', render: r => badge(r.aktif) },
      { label: 'Action' + q('Q-14', 'Apakah paket masih bisa diedit setelah disimpan? Belum diputuskan — mockup hanya menyediakan aktif/nonaktif.'), cls: 'act', render: r => gearMenu(r, [toggleItem(r)]) }
    ]);
  }

  const emptyRow = () => ({ produk: '', bidang: '', varian: '', sertifikasi: '' });

  function attrSelect(a, value, i) {
    const o = db[a.key].filter(r => r.aktif)
      .map(r => `<option value="${r.id}" ${r.id === value ? 'selected' : ''}>${esc(r.nama)}</option>`).join('');
    return `<select class="input" data-row="${i}" data-field="${a.field}" aria-label="${a.label} baris ${i + 1}"><option value="">— Pilih ${a.label} —</option>${o}</select>`;
  }

  function viewCombine() {
    const f = state.form;
    const rows = f.rows.map((row, i) => {
      const err = f.errors[i];
      return `<tr class="${err ? 'row-err' : ''}">
          <td class="num">${i + 1}</td>
          ${ATTRS.map(a => `<td>${attrSelect(a, row[a.field], i)}</td>`).join('')}
          <td class="act"><button type="button" class="btn btn-ghost sm" data-act="row-del" data-i="${i}" ${f.rows.length === 1 ? 'disabled' : ''}>Hapus</button></td>
        </tr>${err ? `<tr class="row-msg"><td></td><td colspan="5"><i>!</i>${esc(err)}</td></tr>` : ''}`;
    }).join('');
    const n = f.rows.length;

    return `${pageHead('Tambah Product', 'Isi judul, lalu susun satu atau lebih kombinasi atribut. Saat disimpan, setiap baris menjadi satu paket produk dengan nomor sendiri.', 'FR-11 · FR-12 · FR-13 · FR-22')}
      <div class="card pad">
        ${field(`Judul${req}`, `<input class="input" id="cf-judul" maxlength="150" value="${esc(f.judul)}" placeholder="Judul / topik paket, diisi manual">`, f.judulErr)}
        <div class="sub-head">
          <h3>Kombinasi Atribut</h3>
          <span class="muted">${n} baris → akan menjadi ${n} paket produk</span>
        </div>
        <div class="table-wrap combine-table"><table class="table">
          <thead><tr><th class="num">#</th>${ATTRS.map(a => `<th>${a.label}</th>`).join('')}<th class="act">Action</th></tr></thead>
          <tbody>${rows}</tbody>
        </table></div>
        <button type="button" class="btn btn-outline" data-act="row-add">+ Tambah Baris</button>
        <p class="hint">Antar paket minimal satu dari empat atribut harus berbeda. Hanya data master berstatus Aktif yang dapat dipilih.
          ${q('Q-17', 'Apakah paket nonaktif ikut dicek keunikannya? Mockup ini mengecek semua paket, aktif maupun nonaktif.')}</p>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" data-act="combine-cancel">Batal</button>
        <button type="button" class="btn btn-primary" data-act="combine-save">Simpan ${n} Paket</button>
      </div>`;
  }

  const comboKey = r => ATTRS.map(a => r[a.field]).join('|');

  function saveCombine() {
    const f = state.form;
    f.errors = {};
    f.judulErr = f.judul.trim() ? '' : 'Judul wajib diisi';
    const seen = {};
    f.rows.forEach((r, i) => {
      const missing = ATTRS.filter(a => !r[a.field]).map(a => a.label);
      if (missing.length) { f.errors[i] = `Pilih ${missing.join(', ')}`; return; }
      const k = comboKey(r);
      const dup = db.paket.find(p => comboKey(p) === k);
      if (dup) f.errors[i] = `Kombinasi sama persis dengan paket ${dup.nomor} — ${dup.judul}`;
      else if (k in seen) f.errors[i] = `Kombinasi sama persis dengan baris ${seen[k] + 1}`;
      else seen[k] = i;
    });

    // Gagal satu baris = tidak ada yang tersimpan (NFR-03).
    if (f.judulErr || Object.keys(f.errors).length) {
      render();
      toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn');
      return;
    }
    const nomor = f.rows.map(r => {
      const p = { id: uid(), nomor: nextCode('PC'), judul: f.judul.trim(), aktif: true };
      ATTRS.forEach(a => { p[a.field] = r[a.field]; });
      db.paket.push(p);
      return p.nomor;
    });
    state.form = null;
    go('paket');
    state.page = Math.ceil(db.paket.length / PAGE_SIZE);
    render();
    toast(`${nomor.length} paket produk tersimpan: ${nomor.join(', ')}`);
  }

  // ---------- Katalog (FR-14, ditunda) ----------
  const viewKatalog = () => `${pageHead('Katalog Produk', 'Katalog disusun dari Paket Produk untuk kebutuhan Promosi.', 'FR-14')}
    <div class="card empty-state">
      <div class="icon"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v16H6.5A2.5 2.5 0 0 0 4 21.5Z"/><path d="M8 7h7M8 11h5"/></svg></div>
      <h2>Ditunda — format katalog belum tersedia</h2>
      <p>Halaman ini akan dibuat setelah Labmania menyiapkan format katalog ${q('Q-09', 'Format Katalog Produk belum ada.')}
        dan menetapkan pengguna yang membuatnya ${q('Q-06', 'Aktor pembuat katalog masih ditanyakan.')}.
        Saat ini tersedia <b>${db.paket.filter(p => p.aktif).length} paket aktif</b> yang dapat menjadi isi katalog.</p>
    </div>`;

  // ---------- Wilayah (dipakai Peserta & Company) ----------
  const kotaOptions = (prov, selected) => prov
    ? `<option value="">— Pilih Kota/Kabupaten —</option>${WILAYAH[prov].map(k => `<option ${k === selected ? 'selected' : ''}>${esc(k)}</option>`).join('')}`
    : '<option value="">Pilih provinsi dahulu</option>';
  const provOptions = sel => Object.keys(WILAYAH).map(p => `<option ${p === sel ? 'selected' : ''}>${esc(p)}</option>`).join('');
  const bindKota = form => form.elements.provinsi.addEventListener('change', e => {
    const kota = form.elements.kota;
    kota.innerHTML = kotaOptions(e.target.value, '');
    kota.disabled = !e.target.value;
  });
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const telpOk = v => /^\+?\d{9,15}$/.test(v.replace(/[\s-]/g, ''));

  // ---------- Master Peserta (FR-15 – FR-17) ----------
  const Q16 = q('Q-16', 'Apakah Peserta dan Company juga hanya boleh dinonaktifkan? Mockup mengikuti mockup klien: Edit/Delete.');

  const viewPeserta = () => `${pageHead('Master Peserta', 'Data peserta pelatihan/sertifikasi, dikelompokkan menurut Jenis Bidang. Peserta adalah customer Program Public dan dipilih saat membuat quotation.', 'FR-15 · FR-16 · FR-17',
    '<button class="btn btn-accent" data-act="peserta-add">+ Tambah Peserta</button>')}
    <div class="card">${toolbar('Cari kode, nama, instansi, atau bidang…', false)}<div id="list">${listPeserta()}</div></div>`;

  function listPeserta() {
    const rows = db.peserta.filter(r => matches([r.kode, r.nama, r.instansi, findRec('jenisBidang', r.bidang).nama, r.kota, r.provinsi]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Kode Peserta' + Q01, render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama Peserta', render: r => `${esc(r.nama)}<br><span class="muted">${esc(r.instansi)}</span>` },
      { label: 'Bidang', render: r => { const b = findRec('jenisBidang', r.bidang); return esc(b.nama) + (b.aktif ? '' : ' <span class="muted">(nonaktif)</span>'); } },
      { label: 'Lokasi' + q('Q-07b', 'Kolom Lokasi menampilkan Provinsi, Kota, atau keduanya? Mockup menampilkan keduanya.'), render: r => `${esc(r.kota)}<br><span class="muted">${esc(r.provinsi)}</span>` },
      { label: 'Action' + Q16, cls: 'act', render: r => gearMenu(r, [['peserta-edit', 'Edit'], ['peserta-del', 'Delete', true]]) }
    ]);
  }

  function pesertaForm(rec) {
    const r = rec || { nama: '', gender: '', instansi: '', bidang: '', provinsi: '', kota: '', telp: '', email: '' };
    const bidangOpts = db.jenisBidang.filter(b => b.aktif || b.id === r.bidang)
      .map(b => `<option value="${b.id}" ${b.id === r.bidang ? 'selected' : ''}>${esc(b.nama)}${b.aktif ? '' : ' (nonaktif)'}</option>`).join('');

    openModal({
      title: rec ? `Edit Peserta ${rec.kode}` : 'Tambah Master Peserta',
      wide: true,
      body: `<p class="hint top">Semua field wajib diisi.</p><div class="grid2">
        ${field('Kode Peserta' + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode('PS'))}" readonly>`, '', 'span2')}
        ${field('Nama Peserta' + req, `<input class="input" name="nama" value="${esc(r.nama)}" maxlength="100">`)}
        ${field('Gender' + req, `<select class="input" name="gender"><option value="">— Pilih —</option>${opts(['Laki-laki', 'Perempuan'], r.gender)}</select>`)}
        ${field('Instansi/Perusahaan' + req, `<input class="input" name="instansi" value="${esc(r.instansi)}" maxlength="150">`)}
        ${field('Bidang' + req, `<select class="input" name="bidang"><option value="">— Pilih Jenis Bidang —</option>${bidangOpts}</select>`)}
        ${field('Lokasi (Provinsi)' + req + q('Q-07b', 'Sumber data provinsi & kota belum ditetapkan. Data di mockup hanya contoh.'), `<select class="input" name="provinsi"><option value="">— Pilih Provinsi —</option>${provOptions(r.provinsi)}</select>`)}
        ${field('Kota/Kabupaten' + req, `<select class="input" name="kota" ${r.provinsi ? '' : 'disabled'}>${kotaOptions(r.provinsi, r.kota)}</select><span class="hint">Tersaring menurut provinsi</span>`)}
        ${field('No Telp/WhatsApp' + req, `<input class="input" name="telp" inputmode="tel" value="${esc(r.telp)}" placeholder="08xxxxxxxxxx">`)}
        ${field('Email' + req, `<input class="input" name="email" type="email" value="${esc(r.email)}" placeholder="nama@domain.com">`)}
      </div>`,
      onMount: bindKota,
      onSave: f => {
        const v = {};
        ['nama', 'gender', 'instansi', 'bidang', 'provinsi', 'kota', 'telp', 'email'].forEach(k => { v[k] = f[k].value.trim(); });
        let ok = true;
        const labels = { nama: 'Nama Peserta', gender: 'Gender', instansi: 'Instansi/Perusahaan', bidang: 'Bidang', provinsi: 'Provinsi', kota: 'Kota/Kabupaten', telp: 'No Telp/WhatsApp', email: 'Email' };
        Object.keys(labels).forEach(k => { if (!v[k]) ok = invalid(f[k], `${labels[k]} wajib diisi`); });
        // NFR-06 (usulan): format email & nomor telepon.
        if (v.email && !EMAIL_RE.test(v.email)) ok = invalid(f.email, 'Format email tidak valid');
        if (v.telp && !telpOk(v.telp)) ok = invalid(f.telp, 'Nomor hanya angka, 9–15 digit');
        if (!ok) return false;
        v.bidang = Number(v.bidang);
        if (rec) {
          Object.assign(rec, v);
          toast(`Peserta ${rec.kode} diperbarui`);
        } else {
          const p = Object.assign({ id: uid(), kode: nextCode('PS') }, v);
          db.peserta.push(p);
          state.page = Math.ceil(db.peserta.length / PAGE_SIZE);
          toast(`Peserta ${p.kode} ditambahkan`);
        }
      }
    });
  }

  // ---------- Master Company (FR-30 – FR-32) ----------
  const Q19 = q('Q-19', 'REQ-8 hanya menandai Nama sebagai wajib. Field wajib lainnya belum diputuskan.');

  const viewCompany = () => `${pageHead('Master Company', 'Data perusahaan/instansi yang menjadi customer Program B2B. Company dipilih saat program B2B dibuat.', 'FR-30 · FR-31 · FR-32',
    '<button class="btn btn-accent" data-act="company-add">+ Tambah Company</button>')}
    <div class="card">${toolbar('Cari kode, nama, kota, atau PIC…', false,
      selectFilter('fjenis', 'Filter jenis instansi', state.jenis, [['all', 'Semua jenis instansi'], ...JENIS_INSTANSI.map(j => [j, j])]))}
      <div id="list">${listCompany()}</div></div>`;

  function listCompany() {
    const rows = db.company.filter(r => (state.jenis === 'all' || r.jenis === state.jenis) &&
      matches([r.kode, r.nama, r.kota, r.provinsi, r.picNama]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Kode Company' + Q01, cls: 'nowrap', render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama Company', render: r => `${esc(r.nama)}<br><span class="muted">${esc(r.jenis || '—')}</span>` },
      { label: 'Kota', render: r => r.kota ? `${esc(r.kota)}<br><span class="muted">${esc(r.provinsi)}</span>` : '—' },
      { label: 'PIC', render: r => r.picNama ? `${esc(r.picNama)}<br><span class="muted">${esc(r.picTelp || r.picJabatan || '')}</span>` : '—' },
      { label: 'Action' + Q16, cls: 'act', render: r => gearMenu(r, [['company-edit', 'Edit'], ['company-del', 'Delete', true]]) }
    ]);
  }

  function companyForm(rec) {
    const r = rec || { nama: '', jenis: '', alamat: '', provinsi: '', kota: '', telp: '', email: '', picNama: '', picJabatan: '', picTelp: '', picEmail: '', npwp: '' };
    const inp = (name, val, extra = '') => `<input class="input" name="${name}" value="${esc(val)}" ${extra}>`;
    const sec = t => `<div class="form-sec span2">${t}</div>`;
    openModal({
      title: rec ? `Edit Company ${rec.kode}` : 'Tambah Master Company',
      wide: true,
      body: `<div class="grid2">
        ${field('Kode Company' + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode('CO'))}" readonly>`, '', 'span2')}
        ${sec('1 · Identitas')}
        ${field('Nama Company' + req, inp('nama', r.nama, 'maxlength="150"'))}
        ${field('Jenis Instansi' + Q19, `<select class="input" name="jenis"><option value="">— Pilih —</option>${opts(JENIS_INSTANSI, r.jenis)}</select>`)}
        ${sec('2 · Alamat')}
        ${field('Alamat Lengkap', `<textarea class="input" name="alamat" rows="2" maxlength="255">${esc(r.alamat)}</textarea>`, '', 'span2')}
        ${field('Provinsi' + q('Q-07b', 'Sumber data provinsi & kota belum ditetapkan. Data di mockup hanya contoh.'), `<select class="input" name="provinsi"><option value="">— Pilih Provinsi —</option>${provOptions(r.provinsi)}</select>`)}
        ${field('Kota/Kabupaten', `<select class="input" name="kota" ${r.provinsi ? '' : 'disabled'}>${kotaOptions(r.provinsi, r.kota)}</select><span class="hint">Tersaring menurut provinsi</span>`)}
        ${sec('3 · Kontak Kantor')}
        ${field('Telepon', inp('telp', r.telp, 'inputmode="tel" placeholder="021xxxxxxx"'))}
        ${field('Email', inp('email', r.email, 'type="email" placeholder="info@perusahaan.co.id"'))}
        ${sec('4 · PIC')}
        ${field('Nama PIC', inp('picNama', r.picNama, 'maxlength="100"'))}
        ${field('Jabatan', inp('picJabatan', r.picJabatan, 'maxlength="100"'))}
        ${field('Telp/WhatsApp PIC', inp('picTelp', r.picTelp, 'inputmode="tel" placeholder="08xxxxxxxxxx"'))}
        ${field('Email PIC', inp('picEmail', r.picEmail, 'type="email"'))}
        ${sec('5 · Pajak')}
        ${field('NPWP', inp('npwp', r.npwp, 'inputmode="numeric" placeholder="15 atau 16 digit"'), '', 'span2')}
      </div>`,
      onMount: bindKota,
      onSave: f => {
        const keys = ['nama', 'jenis', 'alamat', 'provinsi', 'kota', 'telp', 'email', 'picNama', 'picJabatan', 'picTelp', 'picEmail', 'npwp'];
        const v = {};
        keys.forEach(k => { v[k] = f[k].value.trim(); });
        let ok = true;
        if (!v.nama) ok = invalid(f.nama, 'Nama Company wajib diisi');
        if (v.email && !EMAIL_RE.test(v.email)) ok = invalid(f.email, 'Format email tidak valid');
        if (v.picEmail && !EMAIL_RE.test(v.picEmail)) ok = invalid(f.picEmail, 'Format email tidak valid');
        if (v.telp && !telpOk(v.telp)) ok = invalid(f.telp, 'Nomor hanya angka, 9–15 digit');
        if (v.picTelp && !telpOk(v.picTelp)) ok = invalid(f.picTelp, 'Nomor hanya angka, 9–15 digit');
        if (v.npwp && !/^(\d{15}|\d{16})$/.test(v.npwp.replace(/[.\-\s]/g, ''))) ok = invalid(f.npwp, 'NPWP 15 atau 16 digit angka');
        if (!ok) return false;
        if (rec) {
          Object.assign(rec, v);
          toast(`Company ${rec.kode} diperbarui`);
        } else {
          const c = Object.assign({ id: uid(), kode: nextCode('CO') }, v);
          db.company.push(c);
          state.page = Math.ceil(db.company.length / PAGE_SIZE);
          toast(`Company ${c.kode} ditambahkan`);
        }
      }
    });
  }

  // ---------- Master Rate (FR-18 – FR-20, FR-25, FR-33 – FR-35) ----------
  function viewRate() {
    const tabs = Object.keys(RATE_TAB).map(t =>
      `<button class="tab ${t === state.rateTab ? 'active' : ''}" data-act="rate-tab" data-tab="${t}">${RATE_TAB[t].title}<span class="nav-count">${db.rate[t].length}</span></button>`).join('');
    const tab = RATE_TAB[state.rateTab];
    return `${pageHead('Master Rate', 'Item biaya beserta Default Cost yang dipakai costing Program Penjualan (Public dan B2B).', 'FR-18 · ' + tab.fr + ' · FR-33',
      `<button class="btn btn-accent" data-act="rate-add">+ Tambah ${tab.title}</button>`)}
      <div class="notice"><span>Satuan <b>Fixed Cost</b>: Event, Day (biaya per jam dicatat sebagai Event). Satuan <b>Variable Cost</b>: Peserta/Event, Peserta/Day.
        Satu item hanya punya <b>satu Default Cost</b> untuk Public maupun B2B. Item tidak dapat dihapus — gunakan <b>Nonaktifkan</b>.</span><span class="ref">FR-25 · FR-34 · FR-35</span></div>
      <div class="card"><div class="tabs" role="tablist">${tabs}</div>${toolbar('Cari kode atau item…')}<div id="list">${listRate()}</div></div>`;
  }

  function listRate() {
    const t = RATE_TAB[state.rateTab];
    const rows = db.rate[state.rateTab].filter(r => statusOk(r) && matches([r.kode, r.item, r.satuan, r.ket]));
    return listBlock(rows, [
      NO_COL,
      { label: `Kode ${t.title}` + Q01, cls: 'nowrap', render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Item', render: r => `${esc(r.item)}${r.ket ? `<br><span class="muted">${esc(r.ket)}</span>` : ''}` },
      { label: 'Satuan', cls: 'nowrap', render: r => esc(r.satuan) },
      { label: 'Default Cost', cls: 'money', render: r => r.mode === 'manual' ? '<span class="muted">diisi saat costing</span>' : rp(r.cost) },
      { label: 'Mode Harga', cls: 'nowrap', render: r => modeTag(r.mode) + (NO_Q21.includes(r.item) ? Q21 : '') },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['rate-edit', 'Edit'], toggleItem(r)]) }
    ]);
  }

  function rateForm(rec) {
    const tabKey = state.rateTab;
    const t = RATE_TAB[tabKey];
    const r = rec || { item: '', satuan: '', cost: '', mode: 'default', ket: '', coa: '' };
    openModal({
      title: `${rec ? 'Edit' : 'Tambah'} ${t.title}`,
      wide: true,
      body: `<div class="grid2">
        ${field(`Kode ${t.title}` + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode(t.prefix))}" readonly><span class="hint">Diisi otomatis oleh sistem</span>`)}
        ${field('Satuan' + req, `<select class="input" name="satuan"><option value="">— Pilih Satuan —</option>${opts(t.satuan, r.satuan)}</select>`)}
        ${field('Item' + req, `<input class="input" name="itemName" value="${esc(r.item)}" maxlength="150" placeholder="Nama item biaya">`, '', 'span2')}
        ${field('Mode Harga' + req, `<select class="input" name="mode">${Object.keys(MODE).map(m => `<option value="${m}" ${m === r.mode ? 'selected' : ''}>${MODE[m].label}</option>`).join('')}</select><span class="hint" id="mode-hint">${esc(MODE[r.mode].hint)}</span>`)}
        ${field('Default Cost (Rp)' + req, `<input class="input" name="cost" type="number" min="0" step="1000" value="${r.mode === 'manual' ? 0 : esc(r.cost)}" ${r.mode === 'manual' ? 'disabled' : ''}>`)}
        ${field('Keterangan', `<input class="input" name="ket" value="${esc(r.ket)}" maxlength="150" placeholder="Opsional">`, '', 'span2')}
        ${field('COA' + req, `<select class="input" name="coa"><option value="">— Pilih COA —</option>${opts(COA, r.coa)}</select><span class="hint">Daftar COA di mockup hanya contoh; di aplikasi diambil dari master COA</span>`, '', 'span2')}
      </div>`,
      onMount: form => {
        form.elements.mode.addEventListener('change', e => {
          const m = e.target.value;
          $('#mode-hint').textContent = MODE[m].hint;
          form.elements.cost.disabled = m === 'manual';
          if (m === 'manual') form.elements.cost.value = 0;
        });
      },
      onSave: f => {
        let ok = true;
        const v = { item: f.itemName.value.trim(), satuan: f.satuan.value, mode: f.mode.value, ket: f.ket.value.trim(), coa: f.coa.value };
        v.cost = v.mode === 'manual' ? 0 : f.cost.value === '' ? '' : Number(f.cost.value);
        if (!v.item) ok = invalid(f.itemName, 'Item wajib diisi');
        if (!v.satuan) ok = invalid(f.satuan, 'Satuan wajib dipilih');
        if (v.cost === '' || v.cost < 0 || !Number.isInteger(v.cost)) ok = invalid(f.cost, 'Default Cost wajib diisi, angka rupiah ≥ 0');
        if (!v.coa) ok = invalid(f.coa, 'COA wajib dipilih');
        if (!ok) return false;
        if (rec) {
          Object.assign(rec, v);
          toast(`${t.title} ${rec.kode} diperbarui`);
        } else {
          const n = Object.assign({ id: uid(), kode: nextCode(t.prefix), tipe: tabKey, aktif: true }, v);
          db.rate[tabKey].push(n);
          state.page = Math.ceil(db.rate[tabKey].length / PAGE_SIZE);
          toast(`${t.title} ${n.kode} ditambahkan`);
        }
      }
    });
  }

  // ---------- Program: daftar (PP-FR-01) ----------
  const paketOf = p => db.paket.find(x => x.id === p.paket);
  const companyOf = p => db.company.find(c => c.id === p.customer);
  const QPP08 = q('Q-PP-08', 'Format Nomor Program belum ditetapkan. Contoh di mockup: PRG-001.');

  const viewProgram = () => `${pageHead('Daftar Program', 'Program = Paket Produk + costing, siap dijual. Program Public dijual per peserta lewat quotation; Program B2B dijual sebagai paket all-in ke company.', 'PP-FR-01 · PP-FR-02',
    '<button class="btn btn-accent" data-act="program-new">+ Buat Program</button>')}
    <div class="card">${toolbar('Cari nomor, judul, atau company…', false,
      selectFilter('fjenis', 'Filter jenis', state.jenis, [['all', 'Semua jenis'], ['public', 'Public'], ['b2b', 'B2B']])
      + selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(STATUS).map(s => [s, STATUS[s]])]))}
      <div id="list">${listProgram()}</div></div>`;

  function programGear(p) {
    const items = [['program-open', p.status === 'draft' ? 'Edit' : 'Lihat']];
    if (p.status === 'draft') items.push(p.jenis === 'public' ? ['program-final', 'Create quotation'] : ['program-deal', 'Deal']);
    if (p.status === 'final' || p.status === 'deal') items.push(['program-done', 'Selesaikan']);
    if (['draft', 'final', 'deal'].includes(p.status)) items.push(['program-cancel', 'Batalkan', true]);
    return gearMenu(p, items);
  }

  function listProgram() {
    const rows = db.program.filter(p => (state.jenis === 'all' || p.jenis === state.jenis) &&
      (state.status === 'all' || p.status === state.status) &&
      matches([p.nomor, paketOf(p).judul, paketOf(p).nomor, (companyOf(p) || {}).nama]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + QPP08, cls: 'nowrap', render: p => `<span class="code">${esc(p.nomor)}</span>` },
      { label: 'Jenis', render: p => jenisTag(p.jenis) },
      { label: 'Judul', cls: 'title', render: p => `${esc(paketOf(p).judul)}<br><span class="muted">${esc(paketOf(p).nomor)}</span>` },
      { label: 'Customer', render: p => p.jenis === 'b2b' ? esc(companyOf(p).nama) : '<span class="muted">— (peserta via quotation)</span>' },
      { label: 'Durasi', cls: 'nowrap', render: p => `${p.durasi} hari` },
      { label: 'Harga', cls: 'money', render: p => p.jenis === 'b2b' ? `${rp(p.hargaPaket)}<br><span class="muted">paket all-in</span>` : `${rp(p.hargaJual)}<br><span class="muted">per peserta</span>` },
      { label: 'Status', cls: 'nowrap', render: p => stBadge(p.status) },
      { label: 'Action', cls: 'act', render: programGear }
    ]);
  }

  // ---------- Program: form & costing (PP-FR-02 – PP-FR-17) ----------
  function newProgram() {
    return { id: null, nomor: null, jenis: '', status: 'draft', paket: '', durasi: 1, targetPeserta: '', hargaJual: '', targetProfit: '',
      customer: '', hargaPaket: '', jumlahPeserta: '', pesertaAktual: '', biaya: [] };
  }

  function openProgram(p) {
    state.pf = p ? { p: clone(p), isNew: false, errors: {} } : { p: newProgram(), isNew: true, errors: {} };
    state.view = 'programForm';
    render();
    window.scrollTo(0, 0);
  }

  const numInput = (name, val, locked, extra = '') =>
    `<input class="input" data-pf="${name}" type="number" min="0" value="${esc(val)}" ${locked ? 'disabled' : ''} ${extra}>`;

  function viewProgramForm() {
    const { p, isNew, errors: E } = state.pf;
    if (!p.jenis) {
      return `${pageHead('Buat Program', 'Pilih jenis program. Setelah dipilih, form dan costing mengikuti jalur masing-masing dan jenis tidak dapat diubah.', 'PP-FR-02')}
        <div class="choice-grid">
          <button class="choice" data-act="pf-jenis" data-jenis="public">
            ${jenisTag('public')}<h3>Program Public</h3>
            <p>Dipromosikan dulu, peserta mendaftar di akhir lewat quotation. Harga <b>per peserta</b>.</p>
            <ul><li>Target peserta & harga jual per peserta</li><li>Fixed Cost + Variable Cost</li><li>Simulasi HPP, profit, BEP</li><li>Draft → Final → Selesai</li></ul>
          </button>
          <button class="choice" data-act="pf-jenis" data-jenis="b2b">
            ${jenisTag('b2b')}<h3>Program B2B</h3>
            <p>Company sudah diketahui dari awal. Harga <b>paket all-in</b>.</p>
            <ul><li>Company dari Master Company</li><li>Item Fixed Cost + biaya manual</li><li>Pendapatan bersih & Mandays</li><li>Draft → Deal → Selesai</li></ul>
          </button>
        </div>
        <div class="form-actions"><button type="button" class="btn btn-ghost" data-act="pf-back">Batal</button></div>`;
    }
    if (p.jenis === 'public') return viewPublicForm();
    const locked = p.status !== 'draft';
    const pub = p.jenis === 'public';
    const paketOpts = db.paket.filter(x => x.aktif || x.id === p.paket)
      .map(x => `<option value="${x.id}" ${x.id === p.paket ? 'selected' : ''}>${esc(x.nomor)} — ${esc(x.judul)} · ${esc(findRec('varian', x.varian).nama)}</option>`).join('');
    const paket = p.paket ? paketOf(p) : null;
    const attrLine = paket ? `<span class="hint">${ATTRS.map(a => esc(findRec(a.key, paket[a.field]).nama)).join(' · ')}</span>` : '';

    const head = `<div class="card pad">
      <div class="pf-meta">
        <span>${jenisTag(p.jenis)}</span>
        <span>Nomor${QPP08}: <b class="code">${esc(p.nomor || peekCode('PRG'))}</b>${isNew ? ' <span class="muted">(otomatis saat disimpan)</span>' : ''}</span>
        <span>Status: ${stBadge(p.status)}</span>
      </div>
      <div class="grid3">
        ${field('Paket Produk' + req, `<select class="input" data-pf="paket" ${locked ? 'disabled' : ''}><option value="">— Pilih Paket Produk aktif —</option>${paketOpts}</select>${attrLine}`, E.paket, 'span2')}
        ${field('Durasi (hari)' + req, numInput('durasi', p.durasi, locked, 'step="1" min="1"'), E.durasi)}
        ${pub ? `
          ${field('Target Peserta' + req, numInput('targetPeserta', p.targetPeserta, locked, 'step="1" min="1"'), E.targetPeserta)}
          ${field('Harga Jual / Peserta (Rp)' + req, numInput('hargaJual', p.hargaJual, locked, 'step="1000"'), E.hargaJual)}
          ${field('Target Profit (Rp)', numInput('targetProfit', p.targetProfit, locked, 'step="1000" placeholder="Opsional"'))}`
        : `
          ${field('Company' + req, `<select class="input" data-pf="customer" ${locked ? 'disabled' : ''}><option value="">— Pilih dari Master Company —</option>${db.company.map(c => `<option value="${c.id}" ${c.id === p.customer ? 'selected' : ''}>${esc(c.nama)}</option>`).join('')}</select>`, E.customer, 'span2')}
          ${field('Harga Paket all-in (Rp)' + req, numInput('hargaPaket', p.hargaPaket, locked, 'step="1000"') + '<span class="hint">Sudah termasuk biaya pendukung</span>', E.hargaPaket)}
          ${field('Jumlah Peserta', numInput('jumlahPeserta', p.jumlahPeserta, locked, 'step="1" placeholder="Informasi"') + '<span class="hint">Informasi, tidak dipakai menghitung</span>')}
          ${field('Target Profit (Rp)', numInput('targetProfit', p.targetProfit, locked, 'step="1000" placeholder="Opsional"'))}`}
      </div>
    </div>`;

    const costing = pub
      ? costSection('Fixed Cost', 'Semua item Fixed Cost aktif ditampilkan sekaligus. Event = sekali per program, Day = × durasi.', 'PP-FR-08', 'fixed', false, locked)
        + costSection('Variable Cost', 'Tambah item satu per satu. Nilai per peserta: Peserta/Event = sekali, Peserta/Day = × durasi.', 'PP-FR-09', 'variable', true, locked)
      : costSection('Biaya Program', 'Tambah item Fixed Cost satu per satu sesuai kondisi program, atau biaya manual di luar master. Event = sekali, Day = × durasi.', 'PP-FR-13 – PP-FR-16', 'fixed', true, locked, true);

    const actions = locked
      ? `<button type="button" class="btn btn-ghost" data-act="pf-back">Kembali</button>
         ${['final', 'deal'].includes(p.status) ? `<button type="button" class="btn btn-outline" data-act="program-cancel" data-id="${p.id}">Batalkan</button>
         <button type="button" class="btn btn-primary" data-act="program-done" data-id="${p.id}">Selesaikan</button>` : ''}`
      : `<button type="button" class="btn btn-ghost" data-act="pf-back">Kembali</button>
         ${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="program-cancel" data-id="${p.id}">Batalkan Program</button>`}
         <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
         ${isNew ? '' : `<button type="button" class="btn btn-accent" data-act="pf-confirm">${pub ? 'Create quotation' : 'Deal'}</button>`}`;

    const title = isNew ? `Buat Program ${JENIS[p.jenis]}` : `Program ${esc(p.nomor)}`;
    const desc = locked
      ? (p.status === 'batal' ? 'Program dibatalkan dan tidak dapat diubah.' : `Program terkunci (${STATUS[p.status]}) — hanya dapat dilihat. Untuk mengubah, batalkan lalu buat program baru.`)
      : pub ? 'Isi target, sesuaikan harga sesuai Mode Harga, lalu tambah Variable Cost. Simulasi dihitung ulang setiap angka berubah.'
        : 'Pilih company, isi harga paket, lalu tambah item biaya. Aksi Deal tersedia setelah program disimpan.';
    const lockNote = locked && p.status === 'final' ? `<div class="notice"><span>Program Final tersedia di modul <b>Quotation</b> untuk pendaftaran peserta.</span><span class="ref">PP-FR-22</span></div>` : '';

    return `${pageHead(title, desc, pub ? 'PP-FR-04 · PP-FR-07 – PP-FR-12' : 'PP-FR-05 · PP-FR-13 – PP-FR-17')}
      ${lockNote}
      <div class="pf-layout">
        <div class="pf-main">${head}${costing}</div>
        <aside class="pf-side"><div class="card sim" id="sim">${simPanel(p)}</div></aside>
      </div>
      <div class="form-actions">${actions}</div>`;
  }

  function costSection(title, desc, ref, tipe, addable, locked, b2b = false) {
    const { p, errors: E } = state.pf;
    const rows = p.biaya.map((b, i) => ({ b, i })).filter(({ b }) => b.tipe === tipe);
    const perPeserta = tipe === 'variable';
    const body = rows.length ? rows.map(({ b, i }) => {
      const r = b.rate ? rateById(b.rate) : null;
      const hargaLocked = locked || b.mode === 'fix';
      const err = E['b' + i];
      const itemCell = b.manual
        ? `<input class="input sm-in" data-bi="${i}" data-bf="item" value="${esc(b.item)}" placeholder="Nama biaya manual" ${locked ? 'disabled' : ''}>`
        : `${esc(b.item)}${r && !r.aktif ? ' <span class="muted">(nonaktif)</span>' : ''}`;
      const satuanCell = b.manual && !locked
        ? `<select class="input sm-in" data-bi="${i}" data-bf="satuan">${opts(['Event', 'Day'], b.satuan)}</select>`
        : esc(b.satuan);
      return `<tr class="${err ? 'row-err' : ''}">
        <td>${itemCell}${b.manual ? ' <span class="tag tag-manual">manual</span>' : ''}</td>
        <td class="nowrap">${satuanCell}</td>
        <td class="nowrap">${b.manual ? '<span class="muted">—</span>' : modeTag(b.mode)}</td>
        <td class="money-in"><input class="input sm-in" type="number" min="0" step="1000" data-bi="${i}" data-bf="harga" value="${esc(b.harga)}" ${hargaLocked ? 'disabled' : ''} placeholder="${b.mode === 'manual' ? 'Isi harga' : ''}" aria-label="Harga ${esc(b.item)}"></td>
        <td class="nowrap muted" data-fx="${i}">${factorLabel(b, p.durasi)}</td>
        <td class="money" data-sub="${i}">${rp(subtotal(b, p.durasi))}</td>
        ${addable && !locked ? `<td class="act"><button type="button" class="btn btn-ghost sm" data-act="pf-del" data-i="${i}">Hapus</button></td>` : addable ? '<td></td>' : ''}
      </tr>${err ? `<tr class="row-msg"><td colspan="7"><i>!</i>${esc(err)}</td></tr>` : ''}`;
    }).join('') : `<tr><td colspan="7" class="empty">Belum ada item. Pilih item di bawah lalu klik + Tambah.</td></tr>`;

    const used = p.biaya.map(b => b.rate);
    const choices = db.rate[tipe].filter(r => r.aktif && !used.includes(r.id));
    const adder = addable && !locked ? `<div class="adder">
        <select class="input" id="add-${tipe}"><option value="">— Pilih item ${tipe === 'fixed' ? 'Fixed' : 'Variable'} Cost dari Master Rate —</option>
          ${choices.map(r => `<option value="${r.id}">${esc(r.item)} · ${esc(r.satuan)} · ${r.mode === 'manual' ? 'input manual' : rp(r.cost)}</option>`).join('')}</select>
        <button type="button" class="btn btn-outline" data-act="pf-add" data-tipe="${tipe}">+ Tambah</button>
        ${b2b ? `<button type="button" class="btn btn-ghost" data-act="pf-manual">+ Biaya manual</button>${q('Q-PP-10', 'Apakah biaya manual juga boleh di Program Public? Mockup hanya menyediakannya di B2B.')}` : ''}
      </div>` : '';

    return `<div class="card cost-card">
      <div class="cost-head"><div><h3>${title}<span class="ref">${ref}</span></h3><p class="muted">${desc}</p></div></div>
      <div class="table-wrap"><table class="table cost-table">
        <thead><tr><th>Item</th><th>Satuan</th><th>Mode</th><th class="money">Harga</th><th>Faktor</th><th class="money">${perPeserta ? 'Subtotal / peserta' : 'Subtotal'}</th>${addable ? '<th class="act"></th>' : ''}</tr></thead>
        <tbody>${body}</tbody>
      </table></div>${adder}
    </div>`;
  }

  const factorLabel = (b, durasi) => (b.satuan === 'Day' || b.satuan === 'Peserta/Day') ? `× ${Number(durasi) || 0} hari` : '× 1';

  function simPanel(p) {
    const c = calc(p);
    const line = (label, val, cls = '') => `<div class="sim-row ${cls}"><span>${label}</span><b>${val}</b></div>`;
    if (p.jenis === 'b2b') {
      const warn = c.bersih < 0 ? '<div class="warn"><i>!</i>Pendapatan bersih minus</div>' : '';
      return `<h3>Hasil Costing B2B <span class="ref">PP-FR-17</span></h3>
        ${line('Harga paket all-in', rp(p.hargaPaket))}
        ${line('Total biaya', rp(c.total))}
        ${line('Pendapatan bersih', rp(c.bersih), 'total ' + (c.bersih < 0 ? 'neg' : ''))}
        ${line('Mandays', `belum ditentukan ${q('Q-PP-03', 'Rumus Mandays sebagai output costing B2B belum ada.')}`)}
        ${p.targetProfit !== '' ? line('Target profit', rp(p.targetProfit)) : ''}
        ${warn}
        <p class="muted small">Rumus hasil B2B menunggu konfirmasi Labmania ${q('Q-PP-02', 'Pendapatan bersih = harga paket − total biaya masih menunggu konfirmasi.')}</p>`;
    }
    const warns = [];
    if (c.target && c.harga) {
      if (c.profit < 0) warns.push('Profit minus');
      if (c.bepPeserta === null) warns.push('BEP tidak tercapai: harga jual ≤ biaya variable per peserta');
      else if (c.bepPeserta > c.target) warns.push(`BEP ${c.bepPeserta} peserta di atas target ${c.target}`);
      if (p.targetProfit !== '' && c.profit < Number(p.targetProfit)) warns.push('Profit di bawah target profit');
    }
    return `<h3>Simulasi <span class="ref">PP-FR-10</span></h3>
      ${line('Total Fixed Cost', rp(c.fixed))}
      ${line('Biaya Variable / peserta', rp(c.varPer), 'sub')}
      ${line('Total Variable Cost', rp(c.totalVar))}
      ${line('HPP', rp(c.hpp), 'total')}
      ${line('Harga jual / peserta', rp(p.hargaJual))}
      ${line('Pendapatan', rp(c.pendapatan))}
      ${line('Profit', rp(c.profit), 'total ' + (c.profit < 0 ? 'neg' : ''))}
      ${line('Target profit', p.targetProfit !== '' ? rp(p.targetProfit) : '—')}
      ${line('Harga BEP / peserta', rp(c.bepHarga))}
      ${line('Jumlah peserta BEP', c.bepPeserta === null ? 'tidak tercapai' : `${c.bepPeserta} peserta`)}
      ${warns.map(w => `<div class="warn"><i>!</i>${esc(w)}</div>`).join('')}
      ${p.status === 'selesai' ? `<div class="sim-actual">${line('Peserta aktual', `${p.pesertaAktual} peserta`)}${line('Profit aktual', rp(Number(p.hargaJual) * Number(p.pesertaAktual) - c.hpp), 'total')}</div>` : ''}`;
  }

  // ---------- Program Public: layout mengikuti input/labmania.html (form Product Costing) ----------
  // Hanya layout yang diikuti; rumus tetap fr-nfr-program-penjualan.md bagian 8.
  const PUB_GROUPS = [
    { tipe: 'fixed', satuan: 'Event', title: 'Fixed Cost / Event', nominal: 'Nominal / event', tot: 'tot-fe' },
    { tipe: 'fixed', satuan: 'Day', title: 'Fixed Cost / Day', nominal: 'Nominal / hari', tot: 'tot-fd' },
    { tipe: 'variable', satuan: 'Peserta/Event', title: 'Variable Cost / Peserta / Event', nominal: 'Nominal / peserta', tot: 'tot-ve', pick: 'add-pe' },
    { tipe: 'variable', satuan: 'Peserta/Day', title: 'Variable Cost / Peserta / Day', nominal: 'Nominal / peserta / hari', tot: 'tot-vd', pick: 'add-pd' }
  ];

  // Total per baris: fixed = nominal × (1 event | durasi); variable = nominal × target peserta (× durasi).
  function lineQty(b, p) {
    const d = Number(p.durasi) || 0, n = Number(p.targetPeserta) || 0;
    if (b.tipe === 'fixed') return b.satuan === 'Day' ? d : 1;
    return n * (b.satuan === 'Peserta/Day' ? d : 1);
  }
  function lineQtyTxt(b, p) {
    const d = Number(p.durasi) || 0, n = Number(p.targetPeserta) || 0;
    if (b.tipe === 'fixed') return b.satuan === 'Day' ? `${d} hari` : '1 event';
    return b.satuan === 'Peserta/Day' ? `${n} peserta × ${d} hari` : `${n} peserta`;
  }
  const lineTotal = (b, p) => (Number(b.harga) || 0) * lineQty(b, p);
  const groupSum = (p, g) => p.biaya.filter(b => b.tipe === g.tipe && b.satuan === g.satuan).reduce((s, b) => s + (Number(b.harga) || 0), 0);

  function defMeta(b) {
    const r = b.rate ? rateById(b.rate) : null;
    if (b.mode === 'fix') return '<div class="meta" data-def>Fix — ubah lewat Master Rate</div>';
    if (b.mode === 'manual') return `<div class="meta ${b.harga === '' ? 'diff' : ''}" data-def>Input manual${b.harga === '' ? ' — isi harga' : ''}</div>`;
    if (!r) return '';
    const changed = Number(b.harga) !== Number(r.cost);
    return `<div class="meta ${changed ? 'diff' : ''}" data-def>default ${rp(r.cost)}${changed ? ', diubah' : ''}</div>`;
  }

  function pubWarnings(p) {
    const c = calc(p), w = [];
    const kosong = p.biaya.filter(b => b.harga === '').map(b => b.item);
    if (kosong.length) w.push(`Harga input manual belum diisi: ${kosong.join(', ')}.`);
    if (c.target && c.harga) {
      if (c.profit < 0) w.push('Profit minus.');
      if (c.bepPeserta === null) w.push('BEP tidak tercapai: harga jual ≤ biaya variable / peserta.');
      else if (c.bepPeserta > c.target) w.push(`BEP ${c.bepPeserta} peserta di atas target ${c.target} peserta.`);
      if (p.targetProfit !== '' && c.profit < Number(p.targetProfit)) w.push(`Profit ${rp(c.profit)} di bawah target profit ${rp(p.targetProfit)}.`);
    }
    return w;
  }

  function stepSummary(n, p) {
    const c = calc(p), paket = p.paket ? paketOf(p) : null;
    if (n === 1) return paket ? `${esc(paket.judul)} · ${Number(p.durasi) || 0} hari · ${Number(p.targetPeserta) || 0} peserta · ${rp(p.hargaJual)}` : 'Paket Produk belum dipilih';
    if (n === 2) return `Total ${rp(c.fixed)}`;
    if (n === 3) return `Total ${rp(c.totalVar)}`;
    return c.target && c.harga ? `Profit <b class="${c.profit < 0 ? 'neg' : 'pos'}">${rp(c.profit)}</b>` : '—';
  }

  function step(n, title, hint, body) {
    const col = state.pf.collapsed && state.pf.collapsed[n];
    return `<div class="csec step ${col ? 'collapsed' : ''}" data-step="${n}">
      <p class="csec-h" role="button" tabindex="0" data-act="pf-step" data-n="${n}" aria-expanded="${!col}">
        <span class="step-chev" aria-hidden="true">▾</span>${n}. ${title}${hint ? ` <span class="muted">${hint}</span>` : ''}
        <span class="step-sum" data-sum="${n}">${stepSummary(n, state.pf.p)}</span></p>
      <div class="step-body">${body}</div>
    </div>`;
  }

  function pubCostTable(g, locked) {
    const { p, errors: E } = state.pf;
    const rows = p.biaya.map((b, i) => ({ b, i })).filter(({ b }) => b.tipe === g.tipe && b.satuan === g.satuan);
    const removable = g.tipe === 'variable' && !locked; // Fixed Cost Public: semua item tampil, tidak dihapus (PP-FR-08)
    const body = rows.length ? rows.map(({ b, i }) => {
      const r = b.rate ? rateById(b.rate) : null;
      const err = E['b' + i];
      return `<tr class="${Number(b.harga) > 0 ? '' : 'zero'} ${err ? 'row-err' : ''}" data-row="${i}">
        <td>${esc(b.item)}${r && !r.aktif ? ' <span class="muted">(nonaktif)</span>' : ''} ${modeTag(b.mode)}${defMeta(b)}</td>
        <td class="num"><input class="input sm-in" type="number" min="0" step="1000" data-bi="${i}" data-bf="harga" value="${esc(b.harga)}"
          ${locked || b.mode === 'fix' ? 'disabled' : ''} placeholder="${b.mode === 'manual' ? 'Isi harga' : ''}" aria-label="Nominal ${esc(b.item)}"></td>
        <td class="num qty" data-fx="${i}">${lineQtyTxt(b, p)}</td>
        <td class="num" data-sub="${i}">${rp(lineTotal(b, p))}</td>
        <td class="act">${removable ? `<button type="button" class="btn btn-ghost sm" data-act="pf-del" data-i="${i}">Hapus</button>` : ''}</td>
      </tr>${err ? `<tr class="row-msg"><td colspan="5"><i>!</i>${esc(err)}</td></tr>` : ''}`;
    }).join('') : `<tr class="norow"><td colspan="5" class="muted">${g.tipe === 'fixed' ? 'Belum ada item aktif di Master Rate dengan satuan ini.' : 'Belum ada item. Pilih di atas lalu klik + Tambah.'}</td></tr>`;

    let pick = '';
    if (g.pick && !locked) {
      const used = p.biaya.map(b => b.rate);
      const choices = db.rate.variable.filter(r => r.aktif && r.satuan === g.satuan && !used.includes(r.id));
      pick = `<div class="pickrow"><select class="input" id="${g.pick}">${choices.length
        ? `<option value="">— Pilih item —</option>${choices.map(r => `<option value="${r.id}">${esc(r.item)} · ${r.mode === 'manual' ? 'input manual' : rp(r.cost)}</option>`).join('')}`
        : '<option value="" disabled selected>Semua item master sudah dipakai</option>'}</select>
        <button type="button" class="btn btn-outline sm" data-act="pf-add" data-sel="${g.pick}">+ Tambah</button></div>`;
    }
    return `<p class="csec-sub">${g.title}</p>${pick}
      <div class="table-wrap"><table class="table items">
        <thead><tr><th>Item</th><th class="num">${g.nominal}</th><th class="num">Qty</th><th class="num">Total</th><th class="act"></th></tr></thead>
        <tbody>${body}</tbody>
        <tfoot><tr><td colspan="3" class="num"><b>Total</b></td><td class="num"><b id="${g.tot}">${rp(rows.reduce((s, { b }) => s + lineTotal(b, p), 0))}</b></td><td></td></tr></tfoot>
      </table></div>`;
  }

  function pubTotalsFixed(p) {
    const c = calc(p), d = Number(p.durasi) || 0, n = Number(p.targetPeserta) || 0;
    return `<div class="totals">
      <div class="tr"><span><b>Total fixed cost</b> <span class="muted">= event ${rp(groupSum(p, PUB_GROUPS[0]))} + day ${rp(groupSum(p, PUB_GROUPS[1]))} × ${d} hari</span></span><span><b>${rp(c.fixed)}</b></span></div>
      <div class="tr"><span class="muted">Fixed cost / peserta (target)</span><span class="muted">${n ? rp(c.fixed / n) : '—'}</span></div>
    </div>`;
  }
  function pubTotalsVar(p) {
    const c = calc(p), d = Number(p.durasi) || 0;
    return `<div class="totals">
      <div class="tr"><span>Biaya variable / peserta <span class="muted">= event ${rp(groupSum(p, PUB_GROUPS[2]))} + day ${rp(groupSum(p, PUB_GROUPS[3]))} × ${d} hari</span></span><span>${rp(c.varPer)}</span></div>
      <div class="tr"><span><b>Total variable cost</b> <span class="muted">= per peserta × target peserta</span></span><span><b>${rp(c.totalVar)}</b></span></div>
    </div>`;
  }

  // Simulasi Hasil: kartu KPI, alur Pendapatan vs HPP, grafik profit per jumlah peserta, target vs realisasi.
  function pubSim(p) {
    const c = calc(p);
    if (!(c.target > 0) || !(c.harga > 0)) {
      return '<div class="sim-empty">Isi <b>target peserta</b> dan <b>harga jual / peserta</b> di bagian 1 untuk melihat simulasi.</div>';
    }
    const d = Number(p.durasi) || 0, contrib = c.harga - c.varPer;
    const gap = c.bepPeserta === null ? null : c.target - c.bepPeserta;
    const bepPct = c.bepPeserta ? Math.min(100, Math.round(c.target / c.bepPeserta * 100)) : 0;
    const card = (cls, label, val, sub, extra = '') => `<div class="kpi ${cls}"><div class="kpi-l">${label}</div><div class="kpi-v">${val}</div>${extra}<div class="kpi-s">${sub}</div></div>`;
    const tp = p.targetProfit === '' ? null : Number(p.targetProfit);
    let kpis = card(c.profit < 0 ? 'bad' : 'good', 'Profit', rp(c.profit), `Pendapatan ${rp(c.pendapatan)} − HPP ${rp(c.hpp)}`)
      + card(c.bepPeserta === null ? 'bad' : gap >= 0 ? 'good' : 'warn', 'Jumlah peserta BEP', c.bepPeserta === null ? 'Tidak tercapai' : `${c.bepPeserta} peserta`,
        c.bepPeserta === null ? 'Harga jual tidak menutup biaya variable / peserta'
          : gap >= 0 ? `Target ${c.target} peserta, aman ${gap} di atas BEP` : `Target ${c.target} peserta, kurang ${-gap} peserta`,
        c.bepPeserta !== null ? `<div class="kpi-bar"><span style="width:${bepPct}%"></span></div>` : '')
      + card(c.bepHarga > c.harga ? 'warn' : '', 'Harga BEP / peserta', rp(c.bepHarga),
        `HPP ÷ target peserta. Harga jual ${rp(c.harga)} (${c.harga >= c.bepHarga ? '+' : '−'}${rp(Math.abs(c.harga - c.bepHarga))})`)
      + card('', 'HPP', rp(c.hpp), `Fixed ${rp(c.fixed)} + Variable ${rp(c.totalVar)}`);
    if (tp !== null) {
      const ok = c.profit >= tp;
      kpis += card('tgt ' + (ok ? 'good' : 'warn'), 'Target profit', rp(tp), ok ? `Tercapai, lebih ${rp(c.profit - tp)}` : `Kurang ${rp(tp - c.profit)} dari target`);
    }

    const mx = Math.max(c.pendapatan, c.hpp, 1), w = v => `${Math.max(0, v / mx * 100).toFixed(2)}%`;
    const flow = `<div class="sim-flow">
      <div class="fl-row"><div class="fl-l">Pendapatan<small>${rp(c.harga)} × ${c.target} peserta</small></div><div class="fl-bar"><span class="seg rev" style="width:${w(c.pendapatan)}"></span></div><div class="fl-v">${rp(c.pendapatan)}</div></div>
      <div class="fl-row"><div class="fl-l">HPP<small>fixed + variable</small></div><div class="fl-bar"><span class="seg fix" style="width:${w(c.fixed)}" title="Fixed cost"></span><span class="seg var" style="width:${w(c.totalVar)}" title="Variable cost"></span></div><div class="fl-v">${rp(c.hpp)}</div></div>
      <div class="fl-legend"><span><i class="lg fix"></i>Fixed cost ${rp(c.fixed)} = event ${rp(groupSum(p, PUB_GROUPS[0]))} + day ${rp(groupSum(p, PUB_GROUPS[1]))} × ${d} hari</span>
        <span><i class="lg var"></i>Variable cost ${rp(c.totalVar)} = (${rp(groupSum(p, PUB_GROUPS[2]))} + ${rp(groupSum(p, PUB_GROUPS[3]))} × ${d} hari) × ${c.target} peserta</span></div>
    </div>`;

    // Grafik: Profit(x) = harga × x − (Total Fixed + biaya variable/peserta × x).
    const done = p.status === 'selesai' && p.pesertaAktual !== '';
    const nA = done ? Number(p.pesertaAktual) : null;
    const profitA = done ? c.harga * nA - c.hpp : null; // PP-FR-28: harga jual & cost tetap
    const profitAt = x => c.harga * x - c.fixed - c.varPer * x;
    const xmax = Math.max(10, Math.ceil(c.target * 1.6), c.bepPeserta ? Math.ceil(c.bepPeserta * 1.3) : 0, done ? Math.ceil(nA * 1.15) : 0);
    const W = 960, H = 230, L = 74, PR = 16, T = 16, B = 28;
    const ys = [profitAt(0), profitAt(xmax), 0].concat(done ? [profitA] : []);
    const y0 = Math.min(...ys), y1 = Math.max(...ys);
    const X = x => L + (x / xmax) * (W - L - PR), Y = v => T + (y1 - v) / ((y1 - y0) || 1) * (H - T - B);
    const short = v => { const a = Math.abs(v), sg = v < 0 ? '−' : ''; return sg + (a >= 1e9 ? (a / 1e9).toFixed(1) + ' M' : a >= 1e6 ? (a / 1e6).toFixed(1) + ' jt' : a >= 1e3 ? Math.round(a / 1e3) + ' rb' : String(Math.round(a))); };
    let svg = `<svg viewBox="0 0 ${W} ${H}" class="sim-svg" role="img" aria-label="Grafik profit menurut jumlah peserta">`;
    [y0, (y0 + y1) / 2, y1, 0].filter((v, i, a) => a.indexOf(v) === i).forEach(v => {
      svg += `<line x1="${L}" x2="${W - PR}" y1="${Y(v)}" y2="${Y(v)}" class="${v === 0 ? 'zero' : 'grid'}"/><text x="${L - 8}" y="${Y(v) + 4}" text-anchor="end" class="ax">${short(v)}</text>`;
    });
    [0, Math.round(xmax / 2), xmax].forEach(x => { svg += `<text x="${X(x)}" y="${H - 10}" text-anchor="middle" class="ax">${x}</text>`; });
    const xb = contrib > 0 ? Math.min(xmax, c.fixed / contrib) : xmax;
    svg += `<polygon points="${X(0)},${Y(0)} ${X(0)},${Y(profitAt(0))} ${X(xb)},${Y(Math.min(0, profitAt(xb)))} ${X(xb)},${Y(0)}" class="area-loss"/>`;
    if (contrib > 0 && xb < xmax) svg += `<polygon points="${X(xb)},${Y(0)} ${X(xmax)},${Y(profitAt(xmax))} ${X(xmax)},${Y(0)}" class="area-gain"/>`;
    svg += `<line x1="${X(0)}" y1="${Y(profitAt(0))}" x2="${X(xmax)}" y2="${Y(profitAt(xmax))}" class="pline"/>`;
    if (c.bepPeserta !== null && c.bepPeserta <= xmax) svg += `<line x1="${X(c.bepPeserta)}" x2="${X(c.bepPeserta)}" y1="${T}" y2="${H - B}" class="bepline"/><text x="${X(c.bepPeserta) + 5}" y="${T + 10}" class="lbl">BEP ${c.bepPeserta} peserta</text>`;
    svg += `<circle cx="${X(c.target)}" cy="${Y(c.profit)}" r="6" class="tdot"/><text x="${X(c.target)}" y="${Y(c.profit) + (c.profit >= 0 ? -12 : 20)}" text-anchor="middle" class="lbl strong">Target ${c.target}: ${short(c.profit)}</text>`;
    if (done) svg += `<circle cx="${X(nA)}" cy="${Y(profitA)}" r="6" class="rdot"/><text x="${X(nA)}" y="${Y(profitA) + (profitA >= c.profit ? -12 : 20)}" text-anchor="middle" class="lbl strong">Aktual ${nA}: ${short(profitA)}</text>`;
    svg += '</svg>';
    const chart = `<div class="sim-chart"><div class="sim-chart-h">Profit menurut jumlah peserta <span>(durasi dan harga jual tetap)</span></div>${svg}<div class="sim-chart-x">jumlah peserta</div></div>`;

    let riil;
    if (done) {
      const dN = nA - c.target, dP = profitA - c.profit, sg = v => (v > 0 ? '+' : v < 0 ? '−' : '') + Math.abs(v);
      const cell = (l, t, r, dl, cls = '') => `<div class="rv"><div class="rv-l">${l}</div><div class="rv-row"><span>Target</span><b>${t}</b></div><div class="rv-row"><span>Aktual</span><b class="${cls}">${r}</b></div>${dl ? `<div class="rv-d ${cls}">${dl}</div>` : ''}</div>`;
      riil = `<div class="sim-riil"><div class="rv-h">Target vs realisasi <span>dari jumlah peserta aktual saat program diselesaikan</span></div><div class="rv-grid">
        ${cell('Peserta', `${c.target} peserta`, `${nA} peserta`, dN === 0 ? 'sesuai target' : `${sg(dN)} peserta dari target`, dN < 0 ? 'neg' : dN > 0 ? 'pos' : '')}
        ${cell('Harga jual / peserta', rp(c.harga), rp(c.harga), 'tetap, tidak ikut berubah')}
        ${cell('Profit', rp(c.profit), rp(profitA), dP === 0 ? '' : `${dP > 0 ? '+' : '−'}${rp(Math.abs(dP))} dari target`, profitA < 0 || dP < 0 ? 'neg' : 'pos')}
        ${cell('Posisi BEP', c.bepPeserta === null ? '—' : `${c.bepPeserta} peserta`, `${nA} peserta`, c.bepPeserta === null ? '' : nA >= c.bepPeserta ? 'sudah lewat BEP' : `kurang ${c.bepPeserta - nA} peserta untuk BEP`, c.bepPeserta !== null && nA < c.bepPeserta ? 'neg' : 'pos')}
      </div></div>`;
    } else {
      riil = `<div class="sim-riil muted">Realisasi tampil di sini setelah program diselesaikan dan jumlah peserta aktual diisi ${q('Q-PP-07', 'Peserta aktual diisi manual atau dihitung dari Quotation? Belum ditetapkan.')}.</div>`;
    }
    riil += '<p class="help">Kalau peserta aktual kurang atau lebih dari target, harga jual / peserta tetap sama dan cost tetap. Yang berubah hanya profit (PP-FR-28).</p>';
    return `<div class="kpis">${kpis}</div>${flow}${chart}${riil}`;
  }

  function viewPublicForm() {
    const { p, isNew, errors: E } = state.pf;
    const locked = p.status !== 'draft';
    const paketOpts = db.paket.filter(x => x.aktif || x.id === p.paket)
      .map(x => `<option value="${x.id}" ${x.id === p.paket ? 'selected' : ''}>${esc(x.nomor)} — ${esc(x.judul)} · ${esc(findRec('varian', x.varian).nama)}</option>`).join('');
    const paket = p.paket ? paketOf(p) : null;
    const attrLine = paket ? `<span class="hint">${ATTRS.map(a => `${a.label}: ${esc(findRec(a.key, paket[a.field]).nama)}`).join(' · ')}</span>` : '';
    const warns = pubWarnings(p);

    const lockInfo = locked ? `<div class="alert">${p.status === 'batal' ? 'Program dibatalkan dan tidak dapat diubah.'
      : `Program berstatus <b>${STATUS[p.status]}</b> — terkunci, hanya dapat dilihat.${p.status === 'final' ? ' Program ini tersedia di modul <b>Quotation</b>.' : ''} Untuk mengubah, batalkan lalu buat program baru.`}</div>` : '';

    const header = `<div class="csec"><p class="csec-h">Header</p>
      <div class="grid3">
        ${field(`No. program${QPP08}`, `<input class="input" value="${esc(p.nomor || '')}" placeholder="otomatis saat disimpan" disabled>`)}
        ${field('Jenis program', `<div class="static">${jenisTag('public')} <span class="muted">harga per peserta, peserta via quotation</span></div>`)}
        ${field('Status', `<div class="static">${stBadge(p.status)}</div>`)}
      </div></div>`;

    const s1 = step(1, 'Informasi Program', '', `
      ${field('Paket Produk (dari Product Combine)' + req, `<select class="input" data-pf="paket" ${locked ? 'disabled' : ''}><option value="">— Pilih Paket Produk aktif —</option>${paketOpts}</select>${attrLine}`, E.paket)}
      <div class="sim-in">
        <p class="sim-in-h">Input simulasi <span>ubah angka ini untuk melihat profit dan BEP berubah di bagian 4</span></p>
        <div class="grid4">
          ${field('Durasi (hari)' + req, numInput('durasi', p.durasi, locked, 'step="1" min="1"'), E.durasi)}
          ${field('Target peserta' + req, numInput('targetPeserta', p.targetPeserta, locked, 'step="1" min="1"'), E.targetPeserta)}
          ${field('Harga jual / peserta' + req, numInput('hargaJual', p.hargaJual, locked, 'step="1000"'), E.hargaJual)}
          ${field('Target profit (opsional)', numInput('targetProfit', p.targetProfit, locked, 'step="100000" placeholder="mis. 3000000"'))}
        </div>
      </div>`);

    const s2 = step(2, 'Fixed Cost', 'semua item Fixed Cost aktif tampil; isi nominal, biarkan 0 kalau tidak dipakai. Qty otomatis: event = 1, day = durasi.',
      pubCostTable(PUB_GROUPS[0], locked) + pubCostTable(PUB_GROUPS[1], locked) + `<div id="pub-tot-fixed">${pubTotalsFixed(p)}</div>`);
    const s3 = step(3, 'Variable Cost', 'pilih item, isi nominal per peserta. Qty terisi otomatis dari target peserta dan durasi.',
      pubCostTable(PUB_GROUPS[2], locked) + pubCostTable(PUB_GROUPS[3], locked) + `<div id="pub-tot-var">${pubTotalsVar(p)}</div>`);
    const s4 = step(4, 'Simulasi Hasil', 'dihitung dari target peserta, durasi, dan harga jual / peserta', `<div id="pub-sim">${pubSim(p)}</div>`);

    const actions = locked
      ? `<button type="button" class="btn btn-ghost" data-act="pf-back">Kembali</button>
         ${p.status === 'final' ? `<button type="button" class="btn btn-outline" data-act="program-cancel" data-id="${p.id}">Batalkan</button>
         <button type="button" class="btn btn-primary" data-act="program-done" data-id="${p.id}">Selesaikan</button>` : ''}`
      : `<button type="button" class="btn btn-ghost" data-act="pf-back">Batal</button>
         ${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="program-cancel" data-id="${p.id}">Batalkan Program</button>`}
         <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
         ${isNew ? '' : '<button type="button" class="btn btn-accent" data-act="pf-confirm">Create quotation</button>'}`;

    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button>
        <h2>${isNew ? 'Buat Program Public' : `Program ${esc(p.nomor)}`}</h2><span class="ref">PP-FR-04 · PP-FR-07 – PP-FR-12</span></div>
      ${lockInfo}
      <div id="pub-warn" class="alert ${warns.length ? '' : 'hidden'}">${warns.map(esc).join('<br>')}</div>
      <div class="pub-form">${header}${s1}${s2}${s3}${s4}</div>
      <div class="form-actions">${actions}</div>`;
  }

  function refreshPublic() {
    const p = state.pf.p;
    p.biaya.forEach((b, i) => {
      const s = $(`[data-sub="${i}"]`); if (s) s.textContent = rp(lineTotal(b, p));
      const fx = $(`[data-fx="${i}"]`); if (fx) fx.textContent = lineQtyTxt(b, p);
      const tr = $(`tr[data-row="${i}"]`);
      if (tr) {
        tr.classList.toggle('zero', !(Number(b.harga) > 0));
        const dm = tr.querySelector('[data-def]'); if (dm) dm.outerHTML = defMeta(b);
      }
    });
    PUB_GROUPS.forEach(g => {
      const el = $('#' + g.tot);
      if (el) el.textContent = rp(p.biaya.filter(b => b.tipe === g.tipe && b.satuan === g.satuan).reduce((s, b) => s + lineTotal(b, p), 0));
    });
    $('#pub-tot-fixed').innerHTML = pubTotalsFixed(p);
    $('#pub-tot-var').innerHTML = pubTotalsVar(p);
    $('#pub-sim').innerHTML = pubSim(p);
    [1, 2, 3, 4].forEach(n => { const el = $(`[data-sum="${n}"]`); if (el) el.innerHTML = stepSummary(n, p); });
    const w = pubWarnings(p), box = $('#pub-warn');
    box.innerHTML = w.map(esc).join('<br>');
    box.classList.toggle('hidden', !w.length);
  }

  // Hitung ulang tanpa render penuh, agar fokus input tidak hilang saat mengetik.
  function refreshCalc() {
    const p = state.pf.p;
    if (p.jenis === 'public') return refreshPublic();
    p.biaya.forEach((b, i) => {
      const s = $(`[data-sub="${i}"]`); if (s) s.textContent = rp(subtotal(b, p.durasi));
      const fx = $(`[data-fx="${i}"]`); if (fx) fx.textContent = factorLabel(b, p.durasi);
    });
    $('#sim').innerHTML = simPanel(p);
  }

  function validateProgram() {
    const p = state.pf.p, E = {};
    const posInt = v => Number.isInteger(Number(v)) && Number(v) > 0 && v !== '';
    if (!p.paket) E.paket = 'Pilih Paket Produk';
    if (!posInt(p.durasi)) E.durasi = 'Durasi bilangan bulat > 0';
    if (p.jenis === 'public') {
      if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
      if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual wajib diisi';
    } else {
      if (!p.customer) E.customer = 'Pilih Company';
      if (p.hargaPaket === '' || p.hargaPaket < 0) E.hargaPaket = 'Harga paket wajib diisi';
    }
    p.biaya.forEach((b, i) => {
      if (b.manual && !String(b.item).trim()) E['b' + i] = 'Nama biaya manual wajib diisi';
      else if (b.harga === '' || b.harga < 0) E['b' + i] = `Harga ${b.item} wajib diisi`;
    });
    state.pf.errors = E;
    return !Object.keys(E).length;
  }

  function saveProgram(silent) {
    if (!validateProgram()) {
      render();
      toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn');
      return null;
    }
    const pf = state.pf;
    let rec;
    if (pf.isNew) {
      rec = Object.assign(clone(pf.p), { id: uid(), nomor: nextCode('PRG') });
      db.program.push(rec);
    } else {
      rec = db.program.find(x => x.id === pf.p.id);
      Object.assign(rec, clone(pf.p));
    }
    state.pf = { p: clone(rec), isNew: false, errors: {} };
    render();
    if (!silent) toast(`Program ${rec.nomor} tersimpan sebagai Draft${rec.jenis === 'b2b' ? ' — aksi Deal sekarang tersedia' : ''}`);
    return rec;
  }

  // ---------- Program: status (PP-FR-20 – PP-FR-28, bagian 6) ----------
  function confirmProgram(rec) {
    const p = rec, c = calc(p);
    const row = (l, v) => `<div class="sum-row"><span>${l}</span><b>${v}</b></div>`;
    let body, label, next;
    if (p.jenis === 'public') {
      const warns = [];
      if (c.profit < 0) warns.push('Profit minus.');
      if (c.bepPeserta === null) warns.push('BEP tidak tercapai: harga jual ≤ biaya variable per peserta.');
      else if (c.bepPeserta > c.target) warns.push(`BEP ${c.bepPeserta} peserta di atas target ${c.target} peserta.`);
      body = `<p>Setelah dikonfirmasi, program menjadi <b>Final</b>: angka menjadi target resmi, program terkunci, dan muncul di modul Quotation.</p>
        <div class="summary">${row('Program', `${esc(p.nomor)} — ${esc(paketOf(p).judul)}`)}${row('Durasi', `${p.durasi} hari`)}${row('Target peserta', `${p.targetPeserta} peserta`)}
        ${row('Harga jual / peserta', rp(p.hargaJual))}${row('HPP', rp(c.hpp))}${row('Profit', rp(c.profit))}
        ${row('BEP', c.bepPeserta === null ? 'tidak tercapai' : `${c.bepPeserta} peserta · ${rp(c.bepHarga)} / peserta`)}</div>`;
      body += warns.map(w => `<div class="warn"><i>!</i>${esc(w)}</div>`).join('');
      label = 'Konfirmasi Final'; next = 'final';
    } else {
      body = `<p>Setelah dikonfirmasi, program menjadi <b>Deal</b> dan terkunci. Program B2B tidak melalui Quotation.</p>
        <div class="summary">${row('Program', `${esc(p.nomor)} — ${esc(paketOf(p).judul)}`)}${row('Company', esc(companyOf(p).nama))}${row('Durasi', `${p.durasi} hari`)}
        ${row('Harga paket', rp(p.hargaPaket))}${row('Total biaya', rp(c.total))}${row('Pendapatan bersih', rp(c.bersih))}</div>
        ${c.bersih < 0 ? '<div class="warn"><i>!</i>Pendapatan bersih minus.</div>' : ''}`;
      label = 'Konfirmasi Deal'; next = 'deal';
    }
    openModal({
      title: p.jenis === 'public' ? 'Create quotation — konfirmasi fix' : 'Konfirmasi Deal',
      body, saveLabel: label,
      onSave: () => {
        rec.status = next;
        if (state.view === 'programForm') state.pf = { p: clone(rec), isNew: false, errors: {} };
        toast(next === 'final' ? `Program ${rec.nomor} Final — tersedia di modul Quotation` : `Program ${rec.nomor} Deal`);
      }
    });
  }

  function cancelProgram(rec) {
    openModal({
      title: 'Batalkan program',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(rec.nomor)} — ${esc(paketOf(rec).judul)}</b>? Program Batal tidak dapat diubah atau dipulihkan.
        ${q('Q-PP-11', 'Apakah pembatalan perlu alasan?')}${rec.status === 'deal' ? q('Q-PP-06', 'Apakah program B2B yang sudah Deal boleh dibatalkan?') : ''}</p>`,
      saveLabel: 'Batalkan Program', danger: true,
      onSave: () => {
        rec.status = 'batal';
        if (state.view === 'programForm') state.pf = { p: clone(rec), isNew: false, errors: {} };
        toast(`Program ${rec.nomor} dibatalkan`);
      }
    });
  }

  function finishProgram(rec) {
    const pub = rec.jenis === 'public';
    openModal({
      title: 'Selesaikan program',
      body: `<p style="margin:0 0 14px">Program <b>${esc(rec.nomor)}</b> sudah berjalan? Status menjadi Selesai dan angka realisasi menjadi angka akhir.
        ${q('Q-PP-05', 'Siapa dan kapan program diselesaikan belum ditetapkan.')}</p>
        ${pub ? field('Jumlah Peserta Aktual' + req + q('Q-PP-07', 'Diisi manual atau dihitung dari Quotation? Belum ditetapkan.'), `<input class="input" name="aktual" type="number" min="0" step="1" value="${esc(rec.targetPeserta)}">`)
          + `<p class="hint">Harga jual per peserta dan cost tetap; yang berubah hanya profit (PP-FR-28).</p>` : ''}`,
      saveLabel: 'Selesaikan',
      onSave: f => {
        if (pub) {
          const v = f.aktual.value;
          if (v === '' || !Number.isInteger(Number(v)) || Number(v) < 0) return invalid(f.aktual, 'Isi bilangan bulat ≥ 0');
          rec.pesertaAktual = Number(v);
        }
        rec.status = 'selesai';
        if (state.view === 'programForm') state.pf = { p: clone(rec), isNew: false, errors: {} };
        toast(`Program ${rec.nomor} selesai`);
      }
    });
  }

  // ---------- Laporan As Is vs Aktual (PP-FR-29) ----------
  function viewLaporan() {
    const rows = db.program.filter(p => p.jenis === 'public' && ['final', 'selesai'].includes(p.status));
    const diff = n => `<span class="${n < 0 ? 'neg' : 'pos'}">${n > 0 ? '+' : ''}${rp(n)}</span>`;
    const body = rows.length ? rows.map(p => {
      const c = calc(p);
      const done = p.status === 'selesai';
      const pend = done ? Number(p.hargaJual) * Number(p.pesertaAktual) : null;
      const prof = done ? pend - c.hpp : null;
      return `<tr>
        <td><span class="code">${esc(p.nomor)}</span><br><span class="muted">${esc(paketOf(p).judul)}</span></td>
        <td>${stBadge(p.status)}</td>
        <td class="money">${c.target}</td><td class="money">${done ? p.pesertaAktual : '—'}</td>
        <td class="money">${rp(c.pendapatan)}</td><td class="money">${done ? rp(pend) : '—'}</td>
        <td class="money">${rp(c.hpp)}</td>
        <td class="money">${rp(c.profit)}</td><td class="money">${done ? rp(prof) : '—'}</td><td class="money">${done ? diff(prof - c.profit) : '—'}</td>
      </tr>`;
    }).join('') : '<tr><td colspan="10" class="empty">Belum ada program Public berstatus Final atau Selesai.</td></tr>';
    return `${pageHead('As Is vs Aktual', 'Perbandingan angka saat program Final (As Is) dengan realisasi (Aktual) untuk Program Public. Harga jual dan cost tetap; yang berubah hanya profit.', 'PP-FR-28 · PP-FR-29')}
      <div class="notice"><span>Profit Aktual = Harga Jual × Peserta Aktual − HPP As Is. ${q('Q-PP-07', 'Apakah Variable Cost ikut jumlah peserta aktual? Teks sumber: cost tetap.')}</span><span class="ref">PP-FR-28</span></div>
      <div class="card"><div class="table-wrap"><table class="table report">
        <thead>
          <tr><th rowspan="2">Program</th><th rowspan="2">Status</th><th colspan="2" class="grp">Peserta</th><th colspan="2" class="grp">Pendapatan</th><th rowspan="2" class="money">HPP</th><th colspan="3" class="grp">Profit</th></tr>
          <tr><th class="money">As Is</th><th class="money">Aktual</th><th class="money">As Is</th><th class="money">Aktual</th><th class="money">As Is</th><th class="money">Aktual</th><th class="money">Selisih</th></tr>
        </thead><tbody>${body}</tbody></table></div></div>`;
  }

  // ---------- Modal, validation, toast ----------
  function openModal({ title, body, onSave, onMount, saveLabel = 'Simpan', wide = false, danger = false }) {
    const root = $('#modal-root');
    root.innerHTML = `<div class="overlay" data-act="modal-close">
      <form class="modal ${wide ? 'wide' : ''}" novalidate role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <header><h2 id="modal-title">${title}</h2><button type="button" class="x" data-act="modal-close" aria-label="Tutup">×</button></header>
        <div class="modal-body">${body}</div>
        <footer><button type="button" class="btn btn-ghost" data-act="modal-close">Batal</button>
          <button type="submit" class="btn ${danger ? 'btn-danger' : 'btn-primary'}">${saveLabel}</button></footer>
      </form></div>`;
    const form = $('form', root);
    form.addEventListener('submit', e => {
      e.preventDefault();
      form.querySelectorAll('.has-err').forEach(el => el.classList.remove('has-err'));
      form.querySelectorAll('.err').forEach(el => el.remove());
      if (onSave(form.elements) === false) {
        const first = form.querySelector('.has-err .input');
        if (first) first.focus();
        return;
      }
      closeModal();
      render();
    });
    if (onMount) onMount(form);
    const first = form.querySelector('.input:not([readonly]):not([disabled])') || form.querySelector('[type=submit]');
    first.focus();
  }

  const closeModal = () => { $('#modal-root').innerHTML = ''; };

  function invalid(el, msg) {
    const wrap = el.closest('.field');
    if (!wrap.classList.contains('has-err')) {
      wrap.classList.add('has-err');
      wrap.insertAdjacentHTML('beforeend', `<div class="err"><i>!</i>${esc(msg)}</div>`);
    }
    return false;
  }

  function confirmDelete(label, onYes) {
    openModal({
      title: 'Hapus data',
      body: `<p style="margin:0 0 14px">Hapus <b>${esc(label)}</b>? Tindakan ini tidak dapat dibatalkan.</p>`,
      saveLabel: 'Hapus', danger: true,
      onSave: onYes
    });
  }

  function toast(msg, type = '') {
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = msg;
    $('#toasts').appendChild(el);
    setTimeout(() => el.remove(), 3600);
  }

  // ---------- Render ----------
  const LIST = { paket: listPaket, peserta: listPeserta, company: listCompany, rate: listRate, program: listProgram };
  const currentList = () => MASTERS[state.view] ? listMaster(state.view) : LIST[state.view]();
  const VIEWS = {
    paket: viewPaket, combine: viewCombine, katalog: viewKatalog, peserta: viewPeserta, company: viewCompany,
    rate: viewRate, program: viewProgram, programForm: viewProgramForm, laporan: viewLaporan
  };

  function render() {
    renderNav();
    const v = state.view;
    $('#view').innerHTML = MASTERS[v] ? viewMaster(v) : VIEWS[v]();
  }

  // ---------- Events ----------
  const progById = el => db.program.find(x => x.id === +el.dataset.id);
  const ACTIONS = {
    'nav-open': () => document.body.classList.add('nav-open'),
    'nav-close': () => document.body.classList.remove('nav-open'),
    'nav-module': el => { const m = el.dataset.module; state.navOpen[m] = !state.navOpen[m]; renderNav(); },
    'master-add': () => masterForm(state.view),
    'master-edit': el => masterForm(state.view, findRec(state.view, +el.dataset.id)),
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
      confirmDelete(`${r.kode} — ${r.nama}`, () => { db.peserta.splice(db.peserta.indexOf(r), 1); toast(`Peserta ${r.kode} dihapus`); });
    },
    'company-add': () => companyForm(),
    'company-edit': el => companyForm(db.company.find(r => r.id === +el.dataset.id)),
    'company-del': el => {
      const r = db.company.find(x => x.id === +el.dataset.id);
      if (db.program.some(p => p.customer === r.id)) return toast(`${r.nama} sudah dipakai program B2B — tidak dapat dihapus`, 'warn');
      confirmDelete(`${r.kode} — ${r.nama}`, () => { db.company.splice(db.company.indexOf(r), 1); toast(`Company ${r.kode} dihapus`); });
    },
    'rate-tab': el => { state.rateTab = el.dataset.tab; state.q = ''; state.status = 'all'; state.page = 1; render(); },
    'rate-add': () => rateForm(),
    'rate-edit': el => rateForm(db.rate[state.rateTab].find(r => r.id === +el.dataset.id)),
    // Program Penjualan
    'program-new': () => openProgram(),
    'program-open': el => openProgram(progById(el)),
    'program-final': el => confirmProgram(progById(el)),
    'program-deal': el => confirmProgram(progById(el)),
    'program-done': el => finishProgram(progById(el)),
    'program-cancel': el => cancelProgram(progById(el)),
    'pf-back': () => { state.pf = null; go('program'); },
    'pf-jenis': el => {
      const p = state.pf.p;
      p.jenis = el.dataset.jenis;
      // Public: semua Fixed Cost aktif langsung tampil (PP-FR-08). B2B: kosong, ditambah satu per satu (PP-FR-14).
      p.biaya = p.jenis === 'public' ? allFixed() : [];
      render();
    },
    'pf-step': el => {
      const n = +el.dataset.n, pf = state.pf;
      pf.collapsed = pf.collapsed || {};
      pf.collapsed[n] = !pf.collapsed[n];
      el.parentElement.classList.toggle('collapsed', pf.collapsed[n]);
      el.setAttribute('aria-expanded', String(!pf.collapsed[n]));
    },
    'pf-add': el => {
      const sel = $('#' + (el.dataset.sel || `add-${el.dataset.tipe}`));
      if (!sel.value) return toast('Pilih item dari Master Rate dahulu', 'warn');
      state.pf.p.biaya.push(biayaFromRate(rateById(+sel.value)));
      render();
    },
    'pf-manual': () => {
      state.pf.p.biaya.push({ rate: null, tipe: 'fixed', item: '', satuan: 'Event', mode: 'manual', manual: true, harga: '' });
      render();
    },
    'pf-del': el => { state.pf.p.biaya.splice(+el.dataset.i, 1); state.pf.errors = {}; render(); },
    'pf-save': () => saveProgram(false),
    'pf-confirm': () => { const rec = saveProgram(true); if (rec) confirmProgram(rec); }
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

  const NUM_FIELDS = ['durasi', 'targetPeserta', 'hargaJual', 'targetProfit', 'hargaPaket', 'jumlahPeserta'];
  function onProgramInput(t) {
    const p = state.pf.p;
    if (t.dataset.pf) {
      const k = t.dataset.pf;
      p[k] = NUM_FIELDS.includes(k) ? num(t.value) : (t.value ? Number(t.value) : '');
    } else {
      const b = p.biaya[+t.dataset.bi];
      b[t.dataset.bf] = t.dataset.bf === 'harga' ? num(t.value) : t.value;
    }
    refreshCalc();
  }

  document.addEventListener('input', e => {
    const t = e.target;
    if (t.id === 'nav-q') { state.navQ = t.value; renderNav(); }
    if (t.id === 'q') { state.q = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'cf-judul') state.form.judul = t.value;
    if (state.view === 'programForm' && (t.dataset.pf || t.dataset.bi) && t.tagName === 'INPUT') onProgramInput(t);
  });

  document.addEventListener('change', e => {
    const t = e.target;
    if (t.id === 'status' || t.id === 'fstatus') { state.status = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'fjenis') { state.jenis = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.dataset.field && state.form) {
      state.form.rows[+t.dataset.row][t.dataset.field] = Number(t.value) || '';
    }
    if (state.view === 'programForm' && (t.dataset.pf || t.dataset.bi) && t.tagName === 'SELECT') {
      onProgramInput(t);
      if (t.dataset.pf === 'paket') render(); // tampilkan atribut paket terpilih
    }
  });

  document.addEventListener('keydown', e => {
    // Enter di search menu = buka hasil pertama; Esc = kosongkan pencarian.
    if (e.target.id === 'nav-q') {
      if (e.key === 'Enter') { const first = $('#nav .nav-item'); if (first) go(first.dataset.view); return; }
      if (e.key === 'Escape' && state.navQ) { e.target.value = ''; state.navQ = ''; renderNav(); return; }
    }
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.act === 'pf-step') { e.preventDefault(); ACTIONS['pf-step'](e.target); return; }
    if (e.key === 'Escape') { closeGear(); closeModal(); document.body.classList.remove('nav-open'); }
  });

  window.addEventListener('resize', closeGear);
  window.addEventListener('scroll', closeGear, true);

  render();
})();
