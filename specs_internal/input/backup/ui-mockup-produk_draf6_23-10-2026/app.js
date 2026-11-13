/* Labmania — Master Data, Master Program, Penjualan Program (mockup)
   Acuan perilaku: specs/docs/fr-nfr.md draf 5, fr-nfr-master-program.md draf 1, fr-nfr-penjualan-program.md draf 1.
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

  // Format kode belum ditetapkan (Q-01, Q-MP-02, Q-PJ-08) — prefix + 3 digit hanya contoh.
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

  // ---------- Master Program & Penjualan Program (fr-nfr-master-program.md, fr-nfr-penjualan-program.md) ----------
  const MP_STATUS = { draft: 'Draft', final: 'Final', batal: 'Batal' };
  const QT_STATUS = { draft: 'Draft', deal: 'Deal', batal: 'Batal' };
  const STATUS = { draft: 'Draft', final: 'Final', deal: 'Deal', batal: 'Batal' };
  const JENIS = { public: 'Public', b2b: 'B2B' };

  const biayaFromRate = (r, harga) => ({
    rate: r.id, tipe: r.tipe, item: r.item, satuan: r.satuan, mode: r.mode, manual: false,
    harga: harga !== undefined ? harga : (r.mode === 'manual' ? '' : r.cost)
  });

  // Pengali variable cost: Master Program & Public = target peserta; B2B = jumlah peserta (Q-PJ-04).
  const nPeserta = p => Number(p.jenis === 'b2b' ? p.jumlahPeserta : p.targetPeserta) || 0;
  // Nominal × faktor satuan: Event / Peserta/Event = 1, Day / Peserta/Day = durasi (fr-nfr-master-program.md bagian 8).
  const perUnit = (b, d) => (Number(b.harga) || 0) * ((b.satuan === 'Day' || b.satuan === 'Peserta/Day') ? d : 1);

  function calc(p) {
    const d = Number(p.durasi) || 0, n = nPeserta(p);
    const fixed = p.biaya.filter(b => b.tipe === 'fixed').reduce((s, b) => s + perUnit(b, d), 0);
    const varPer = p.biaya.filter(b => b.tipe === 'variable').reduce((s, b) => s + perUnit(b, d), 0);
    const totalVar = varPer * n;
    const hpp = fixed + totalVar;
    if (p.jenis === 'b2b') {
      const harga = Number(p.hargaPaket) || 0;
      return { fixed, varPer, totalVar, hpp, total: hpp, harga, bersih: harga - hpp, target: n }; // PJ-FR-14
    }
    const harga = Number(p.hargaJual) || 0;
    const pendapatan = harga * n;
    const profit = pendapatan - hpp;
    const bepHarga = n ? hpp / n : null;
    const bepPeserta = harga > varPer ? Math.ceil(fixed / (harga - varPer)) : null; // MP-NFR-04
    return { fixed, varPer, totalVar, hpp, pendapatan, profit, bepHarga, bepPeserta, target: n, harga };
  }

  const pidOf = nomor => db.paket.find(p => p.nomor === nomor).id;
  const allFixed = () => db.rate.fixed.filter(r => r.aktif).map(r => biayaFromRate(r));
  const fromRate = names => names.map(i => biayaFromRate(rateByItem(i)));

  db.mprog = [];
  const seedMP = o => { const p = Object.assign({ id: uid(), nomor: nextCode('MP'), targetProfit: '' }, o); db.mprog.push(p); return p; };
  const mp1 = seedMP({
    status: 'final', paket: pidOf('PC-001'), durasi: 2, targetPeserta: 25, hargaJual: 600000, targetProfit: 3000000,
    biaya: allFixed().concat(fromRate(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break']), [biayaFromRate(rateByItem('Seminar Kit'), 35000)])
  });
  const mp2 = seedMP({
    status: 'final', paket: pidOf('PC-002'), durasi: 3, targetPeserta: 20, hargaJual: 900000,
    biaya: allFixed().concat(fromRate(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break']))
  });
  seedMP({
    status: 'draft', paket: pidOf('PC-003'), durasi: 1, targetPeserta: 20, hargaJual: 750000,
    biaya: allFixed().concat(fromRate(['Sertifikat', 'Makan Siang Training']))
  });

  // Quotation menyalin angka & costing Master Program (PJ-FR-04); harga jual standar tidak dipakai di B2B.
  const fromMP = (mp, jenis) => ({
    mp: mp.id, jenis, durasi: mp.durasi, targetProfit: mp.targetProfit,
    targetPeserta: jenis === 'public' ? mp.targetPeserta : '', hargaJual: jenis === 'public' ? mp.hargaJual : '',
    jumlahPeserta: jenis === 'b2b' ? 10 : '', hargaPaket: '', customer: '',
    tglMulai: '', tglSelesai: '', lokasi: '', biaya: clone(mp.biaya), peserta: []
  });
  db.quot = [];
  const seedQT = o => { const q = Object.assign({ id: uid(), nomor: nextCode('QT'), status: 'draft' }, o); db.quot.push(q); return q; };
  seedQT(Object.assign(fromMP(mp1, 'public'), {
    tglMulai: '2026-11-10', tglSelesai: '2026-11-11', lokasi: 'Offline Bekasi',
    peserta: [{ pid: db.peserta[0].id, diskonTipe: 'nominal', diskon: 0, status: 'deal' }, { pid: db.peserta[1].id, diskonTipe: 'persen', diskon: 10, status: 'deal' }, { pid: db.peserta[2].id, diskonTipe: 'nominal', diskon: 50000, status: 'draft' }]
  }));
  seedQT(Object.assign(fromMP(mp2, 'b2b'), {
    status: 'deal', customer: db.company[0].id, hargaPaket: 45000000, jumlahPeserta: 20,
    tglMulai: '2026-11-17', tglSelesai: '2026-11-19', lokasi: 'In house — Bekasi',
    biaya: clone(mp2.biaya).concat([{ rate: null, tipe: 'fixed', item: 'Sewa alat peraga', satuan: 'Event', mode: 'manual', manual: true, harga: 1500000 }])
  }));
  seedQT(Object.assign(fromMP(mp2, 'public'), { tglMulai: '2026-12-01', tglSelesai: '2026-12-03', lokasi: 'Online (Zoom)' }));

  // ---------- State & navigation ----------
  const state = {
    view: 'jenisProduk', q: '', status: 'all', jenis: 'all', page: 1, rateTab: 'fixed', form: null, pf: null,
    navOpen: { 'Master Data': true, 'Master Program': true, 'Penjualan Program': true }, navQ: ''
  };

  // Tiga modul (FR-27): Master Data → Master Program → Penjualan Program; grup di bawahnya adalah sub modul.
  const ICON_DB = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>';
  const ICON_PROG = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M7 14h4M7 17h7"/></svg>';
  const ICON_SALE = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>';
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
      name: 'Master Program', icon: ICON_PROG, groups: [
        { group: 'Program', items: [['mprog', 'Daftar Master Program']] }
      ]
    },
    {
      name: 'Penjualan Program', icon: ICON_SALE, groups: [
        { group: 'Quotation', items: [['quot', 'Daftar Quotation']] },
        { group: 'Laporan', items: [['laporan', 'As Is vs Aktual']] }
      ]
    }
  ];
  const countOf = v => MASTERS[v] ? db[v].length
    : v === 'paket' ? db.paket.length
      : v === 'peserta' ? db.peserta.length
        : v === 'company' ? db.company.length
          : v === 'rate' ? db.rate.fixed.length + db.rate.variable.length
            : v === 'mprog' ? db.mprog.length
              : v === 'quot' ? db.quot.length : null;
  const SUBVIEW = { combine: ['paket', 'Tambah Product'], mpForm: ['mprog', null], qtForm: ['quot', null] };

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
    if (state.view === 'mpForm' || state.view === 'qtForm') leaf = state.pf.isNew ? (state.view === 'mpForm' ? 'Buat Master Program' : 'Buat Quotation') : state.pf.p.nomor;
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
    return `${pageHead('Product Combine', 'Daftar paket produk hasil combine. Paket berstatus Aktif dapat dipilih saat membuat Master Program.', 'FR-10 · FR-23 · FR-24',
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

  const viewPeserta = () => `${pageHead('Master Peserta', 'Data peserta pelatihan/sertifikasi, dikelompokkan menurut Jenis Bidang. Peserta adalah customer penjualan Public dan dipilih saat membuat Quotation.', 'FR-15 · FR-16 · FR-17',
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
  const viewCompany = () => `${pageHead('Master Company', 'Data perusahaan/instansi yang menjadi customer penjualan B2B. Company dipilih saat membuat Quotation B2B.', 'FR-30 · FR-31 · FR-32',
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
      { label: 'Nama Company', render: r => `${esc(r.nama)}<br><span class="muted">PIC: ${esc(r.picNama || '—')}</span>` },
      { label: 'Jenis Instansi', render: r => esc(r.jenis || '—') },
      { label: 'Lokasi', render: r => r.kota ? `${esc(r.kota)}<br><span class="muted">${esc(r.provinsi)}</span>` : '—' },
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
        ${field('Jenis Instansi' + req, `<select class="input" name="jenis"><option value="">— Pilih —</option>${opts(JENIS_INSTANSI, r.jenis)}</select>`)}
        ${sec('2 · Alamat')}
        ${field('Alamat Lengkap' + req, `<textarea class="input" name="alamat" rows="2" maxlength="255">${esc(r.alamat)}</textarea>`, '', 'span2')}
        ${field('Provinsi' + req + q('Q-07b', 'Sumber data provinsi & kota belum ditetapkan. Data di mockup hanya contoh.'), `<select class="input" name="provinsi"><option value="">— Pilih Provinsi —</option>${provOptions(r.provinsi)}</select>`)}
        ${field('Kota/Kabupaten' + req, `<select class="input" name="kota" ${r.provinsi ? '' : 'disabled'}>${kotaOptions(r.provinsi, r.kota)}</select><span class="hint">Tersaring menurut provinsi</span>`)}
        ${sec('3 · Kontak Kantor')}
        ${field('Telepon Kantor' + req, inp('telp', r.telp, 'inputmode="tel" placeholder="021xxxxxxx"'))}
        ${field('Email Kantor' + req, inp('email', r.email, 'type="email" placeholder="info@perusahaan.co.id"'))}
        ${sec('4 · PIC')}
        ${field('Nama PIC' + req, inp('picNama', r.picNama, 'maxlength="100"'))}
        ${field('Jabatan PIC' + req, inp('picJabatan', r.picJabatan, 'maxlength="100"'))}
        ${field('Telp/WA PIC' + req, inp('picTelp', r.picTelp, 'inputmode="tel" placeholder="08xxxxxxxxxx"'))}
        ${field('Email PIC' + req, inp('picEmail', r.picEmail, 'type="email"'))}
        ${sec('5 · Pajak')}
        ${field('NPWP <span class="muted">(opsional)</span>', inp('npwp', r.npwp, 'inputmode="numeric" placeholder="15 atau 16 digit"'), '', 'span2')}
      </div>`,
      onMount: bindKota,
      onSave: f => {
        const keys = ['nama', 'jenis', 'alamat', 'provinsi', 'kota', 'telp', 'email', 'picNama', 'picJabatan', 'picTelp', 'picEmail', 'npwp'];
        const v = {};
        keys.forEach(k => { v[k] = f[k].value.trim(); });
        let ok = true;
        // Semua field wajib kecuali NPWP (FR-31).
        const LBL = { nama: 'Nama Company', jenis: 'Jenis Instansi', alamat: 'Alamat Lengkap', provinsi: 'Provinsi', kota: 'Kota/Kabupaten', telp: 'Telepon Kantor', email: 'Email Kantor', picNama: 'Nama PIC', picJabatan: 'Jabatan PIC', picTelp: 'Telp/WA PIC', picEmail: 'Email PIC' };
        Object.keys(LBL).forEach(k => { if (!v[k]) ok = invalid(f[k], `${LBL[k]} wajib diisi`); });
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
    return `${pageHead('Master Rate', 'Item biaya beserta Default Cost yang dipakai costing Master Program dan Quotation.', 'FR-18 · ' + tab.fr + ' · FR-33',
      `<button class="btn btn-accent" data-act="rate-add">+ Tambah ${tab.title}</button>`)}
      <div class="notice"><span>Satuan <b>Fixed Cost</b>: Event, Day (biaya per jam dicatat sebagai Event). Satuan <b>Variable Cost</b>: Peserta/Event, Peserta/Day.
        Satu item satu <b>Default Cost</b>, yang dapat disesuaikan di Master Program dan Quotation tanpa mengubah master. Item tidak dapat dihapus — gunakan <b>Nonaktifkan</b>.</span><span class="ref">FR-19 · FR-25 · FR-34 · FR-35</span></div>
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
        ${field('Kategori' + req, `<select class="input" name="kategori" ${rec ? 'disabled' : ''}>${Object.keys(RATE_TAB).map(k => `<option value="${k}" ${k === tabKey ? 'selected' : ''}>${k === 'fixed' ? 'Fixed' : 'Variable'}</option>`).join('')}</select>${rec ? '<span class="hint">Kategori tidak dapat diubah setelah disimpan</span>' : ''}`)}
        ${field('Satuan' + req, `<select class="input" name="satuan"><option value="">— Pilih Satuan —</option>${opts(t.satuan, r.satuan)}</select>`)}
        ${field('Item' + req, `<input class="input" name="itemName" value="${esc(r.item)}" maxlength="150" placeholder="Nama item biaya">`, '', 'span2')}
        ${field('Mode Harga' + req, `<select class="input" name="mode">${Object.keys(MODE).map(m => `<option value="${m}" ${m === r.mode ? 'selected' : ''}>${MODE[m].label}</option>`).join('')}</select><span class="hint" id="mode-hint">${esc(MODE[r.mode].hint)}</span>`)}
        ${field('Default Cost (Rp)' + req, `<input class="input" name="cost" type="number" min="0" step="1000" value="${r.mode === 'manual' ? 0 : esc(r.cost)}" ${r.mode === 'manual' ? 'disabled' : ''}>`)}
        ${field('Keterangan', `<input class="input" name="ket" value="${esc(r.ket)}" maxlength="150" placeholder="Opsional">`, '', 'span2')}
        ${field('COA' + req, `<select class="input" name="coa"><option value="">— Pilih COA —</option>${opts(COA, r.coa)}</select><span class="hint">Daftar COA di mockup hanya contoh; di aplikasi diambil dari master COA</span>`, '', 'span2')}
      </div>`,
      onMount: form => {
        form.elements.kategori.addEventListener('change', e => {
          form.elements.satuan.innerHTML = `<option value="">— Pilih Satuan —</option>${opts(RATE_TAB[e.target.value].satuan, '')}`;
        });
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
          const kat = f.kategori.value, kt = RATE_TAB[kat];
          const n = Object.assign({ id: uid(), kode: nextCode(kt.prefix), tipe: kat, aktif: true }, v);
          db.rate[kat].push(n);
          state.rateTab = kat;
          state.page = Math.ceil(db.rate[kat].length / PAGE_SIZE);
          toast(`${kt.title} ${n.kode} ditambahkan`);
        }
      }
    });
  }

  // ---------- Shared: Master Program & Quotation ----------
  const paketOf = p => db.paket.find(x => x.id === p.paket);
  const mpOf = q => db.mprog.find(x => x.id === q.mp);
  const companyOf = q => db.company.find(c => c.id === q.customer);
  const pesertaById = id => db.peserta.find(x => x.id === id);
  const QMP02 = q('Q-MP-02', 'Format Nomor Master Program belum ditetapkan. Contoh di mockup: MP-001.');
  const QPJ08 = q('Q-PJ-08', 'Format Nomor Quotation belum ditetapkan. Contoh di mockup: QT-001.');
  const fmtTgl = iso => iso ? new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
  const fmtRange = (a, b) => !a ? '—' : (!b || a === b ? fmtTgl(a) : `${fmtTgl(a)} – ${fmtTgl(b)}`);
  const netOf = (q, ps) => {
    const h = Number(q.hargaJual) || 0, d = Number(ps.diskon) || 0;
    return Math.max(0, Math.round(ps.diskonTipe === 'persen' ? h * (1 - d / 100) : h - d));
  };
  const diskonTxt = ps => ps.diskonTipe === 'persen' ? `${Number(ps.diskon) || 0}%` : rp(ps.diskon);
  const dealOf = q => (q.peserta || []).filter(x => x.status === 'deal');

  // ---------- Master Program: daftar (MP-FR-01) ----------
  const viewMprog = () => `${pageHead('Daftar Master Program', 'Program siap jual: Paket Produk + costing baseline. Tidak ada Public/B2B, customer, tanggal, atau lokasi acara — semuanya diisi di Quotation.', 'MP-FR-01 · MP-FR-02',
    '<button class="btn btn-accent" data-act="mp-new">+ Buat Master Program</button>')}
    <div class="card">${toolbar('Cari nomor atau judul…', false,
      selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(MP_STATUS).map(s => [s, MP_STATUS[s]])]))}
      <div id="list">${listMprog()}</div></div>`;

  function mpGear(p) {
    const items = [['mp-open', p.status === 'draft' ? 'Edit' : 'Lihat']];
    if (p.status === 'draft') items.push(['mp-final', 'Finalkan program']);
    if (p.status === 'final') items.push(['qt-from-mp', 'Buat quotation']);
    if (p.status !== 'batal') items.push(['mp-cancel', 'Batalkan', true]);
    return gearMenu(p, items);
  }

  function listMprog() {
    const rows = db.mprog.filter(p => (state.status === 'all' || p.status === state.status) &&
      matches([p.nomor, paketOf(p).judul, paketOf(p).nomor]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + QMP02, cls: 'nowrap', render: p => `<span class="code">${esc(p.nomor)}</span>` },
      { label: 'Judul', cls: 'title', render: p => `${esc(paketOf(p).judul)}<br><span class="muted">${esc(paketOf(p).nomor)} · ${esc(findRec('varian', paketOf(p).varian).nama)}</span>` },
      { label: 'Durasi', cls: 'nowrap', render: p => `${p.durasi} hari` },
      { label: 'Target peserta', cls: 'money', render: p => p.targetPeserta },
      { label: 'Harga jual standar', cls: 'money', render: p => rp(p.hargaJual) },
      { label: 'HPP', cls: 'money', render: p => rp(calc(p).hpp) },
      { label: 'Status', cls: 'nowrap', render: p => stBadge(p.status) },
      { label: 'Action', cls: 'act', render: mpGear }
    ]);
  }

  // ---------- Quotation: daftar (PJ-FR-01) ----------
  const viewQuot = () => `${pageHead('Daftar Quotation', 'Proposal penjualan. Tarik Master Program Final, pilih Public atau B2B, lalu sesuaikan harga dan costing — perubahan tidak mengubah Master Program.', 'PJ-FR-01 – PJ-FR-05',
    '<button class="btn btn-accent" data-act="qt-new">+ Buat Quotation</button>')}
    <div class="card">${toolbar('Cari nomor, program, atau customer…', false,
      selectFilter('fjenis', 'Filter jenis', state.jenis, [['all', 'Semua jenis'], ['public', 'Public'], ['b2b', 'B2B']])
      + selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(QT_STATUS).map(s => [s, QT_STATUS[s]])]))}
      <div id="list">${listQuot()}</div></div>`;

  function qtGear(q) {
    const items = [['qt-open', q.status === 'batal' || (q.jenis === 'b2b' && q.status === 'deal') ? 'Lihat' : 'Edit']];
    if (q.jenis === 'b2b' && q.status === 'draft') items.push(['qt-deal', 'Deal']);
    items.push(['qt-print', 'Cetak surat quotation']);
    if (q.status === 'draft' && !dealOf(q).length) items.push(['qt-cancel', 'Batalkan', true]);
    return gearMenu(q, items);
  }

  const qtCustomer = q => q.jenis === 'b2b' ? esc((companyOf(q) || {}).nama || '—')
    : `${dealOf(q).length} Deal / ${q.peserta.length} peserta<br><span class="muted">target ${q.targetPeserta}</span>`;

  function listQuot() {
    const rows = db.quot.filter(q => (state.jenis === 'all' || q.jenis === state.jenis) &&
      (state.status === 'all' || q.status === state.status) &&
      matches([q.nomor, mpOf(q).nomor, paketOf(mpOf(q)).judul, (companyOf(q) || {}).nama, q.lokasi]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + QPJ08, cls: 'nowrap', render: q => `<span class="code">${esc(q.nomor)}</span>` },
      { label: 'Jenis', render: q => jenisTag(q.jenis) },
      { label: 'Master Program', cls: 'title', render: q => `${esc(paketOf(mpOf(q)).judul)}<br><span class="muted">${esc(mpOf(q).nomor)}</span>` },
      { label: 'Customer', render: qtCustomer },
      { label: 'Pelaksanaan', render: q => `${fmtRange(q.tglMulai, q.tglSelesai)}<br><span class="muted">${esc(q.lokasi || '—')}</span>` },
      { label: 'Harga', cls: 'money', render: q => q.jenis === 'b2b' ? `${rp(q.hargaPaket)}<br><span class="muted">paket all-in</span>` : `${rp(q.hargaJual)}<br><span class="muted">per peserta</span>` },
      { label: 'Status', cls: 'nowrap', render: q => stBadge(q.status) },
      { label: 'Action', cls: 'act', render: qtGear }
    ]);
  }

  // ---------- Form costing (layout input/labmania.html; rumus fr-nfr-master-program.md bagian 8) ----------
  const COST_GROUPS = [
    { tipe: 'fixed', satuan: 'Event', title: 'Fixed Cost / Event', nominal: 'Nominal / event', tot: 'tot-fe', pick: 'add-fe' },
    { tipe: 'fixed', satuan: 'Day', title: 'Fixed Cost / Day', nominal: 'Nominal / hari', tot: 'tot-fd', pick: 'add-fd' },
    { tipe: 'variable', satuan: 'Peserta/Event', title: 'Variable Cost / Peserta / Event', nominal: 'Nominal / peserta', tot: 'tot-ve', pick: 'add-pe' },
    { tipe: 'variable', satuan: 'Peserta/Day', title: 'Variable Cost / Peserta / Day', nominal: 'Nominal / peserta / hari', tot: 'tot-vd', pick: 'add-pd' }
  ];
  const isQt = () => state.pf.kind === 'qt';

  // Bagian yang terkunci: Master Program bukan Draft; Quotation Batal, B2B Deal, atau Public yang sudah punya peserta Deal (PJ-FR-17).
  function costLocked() {
    const { kind, p } = state.pf;
    if (kind === 'mp') return p.status !== 'draft';
    if (p.status === 'batal') return true;
    if (p.jenis === 'b2b') return p.status !== 'draft';
    return dealOf(p).length > 0;
  }

  const lineQty = (b, p) => {
    const d = Number(p.durasi) || 0, n = nPeserta(p);
    if (b.tipe === 'fixed') return b.satuan === 'Day' ? d : 1;
    return n * (b.satuan === 'Peserta/Day' ? d : 1);
  };
  const lineQtyTxt = (b, p) => {
    const d = Number(p.durasi) || 0, n = nPeserta(p);
    if (b.tipe === 'fixed') return b.satuan === 'Day' ? `${d} hari` : '1 event';
    return b.satuan === 'Peserta/Day' ? `${n} peserta × ${d} hari` : `${n} peserta`;
  };
  const lineTotal = (b, p) => (Number(b.harga) || 0) * lineQty(b, p);
  const groupSum = (p, g) => p.biaya.filter(b => b.tipe === g.tipe && b.satuan === g.satuan).reduce((s, b) => s + (Number(b.harga) || 0), 0);

  function defMeta(b) {
    const r = b.rate ? rateById(b.rate) : null;
    if (b.manual) return '<div class="meta" data-def>Biaya lain (di luar Master Rate)</div>';
    if (b.mode === 'fix' && !(state.pf && isQt())) return '<div class="meta" data-def>Fix — ubah lewat Master Rate</div>';
    if (b.mode === 'manual') return `<div class="meta ${b.harga === '' ? 'diff' : ''}" data-def>Input manual${b.harga === '' ? ' — isi nominal' : ''}</div>`;
    if (!r) return '';
    const changed = Number(b.harga) !== Number(r.cost);
    return `<div class="meta ${changed ? 'diff' : ''}" data-def>default ${rp(r.cost)}${changed ? ', diubah' : ''}</div>`;
  }

  function costWarnings(p) {
    const c = calc(p), w = [];
    const kosong = p.biaya.filter(b => b.harga === '' || (b.manual && !String(b.item).trim())).map(b => b.item || '(biaya lain tanpa nama)');
    if (kosong.length) w.push(`Nominal / nama belum diisi: ${kosong.join(', ')}.`);
    const hotel = ['Hotel Trainer **', 'Hotel Trainer ***'].map(n => p.biaya.find(b => b.item === n)).filter(b => b && Number(b.harga) > 0);
    if (hotel.length === 2) w.push('Hotel Trainer ** dan *** keduanya terisi — biasanya cukup salah satu per program.');
    if (p.jenis === 'b2b') {
      if (c.harga && c.bersih < 0) w.push('Pendapatan bersih minus.');
      if (c.harga && p.targetProfit !== '' && c.bersih < Number(p.targetProfit)) w.push(`Pendapatan bersih ${rp(c.bersih)} di bawah target profit ${rp(p.targetProfit)}.`);
    } else if (c.target && c.harga) {
      if (c.profit < 0) w.push('Profit minus.');
      if (c.bepPeserta === null) w.push('BEP tidak tercapai: harga jual ≤ biaya variable / peserta.');
      else if (c.bepPeserta > c.target) w.push(`BEP ${c.bepPeserta} peserta di atas target ${c.target} peserta.`);
      if (p.targetProfit !== '' && c.profit < Number(p.targetProfit)) w.push(`Profit ${rp(c.profit)} di bawah target profit ${rp(p.targetProfit)}.`);
    }
    return w;
  }

  function stepSummary(n, p) {
    const c = calc(p);
    if (n === 1) {
      if (!isQt()) return p.paket ? `${esc(paketOf(p).judul)} · ${Number(p.durasi) || 0} hari · ${nPeserta(p)} peserta · ${rp(p.hargaJual)}` : 'Paket Produk belum dipilih';
      const who = p.jenis === 'b2b' ? esc((companyOf(p) || {}).nama || 'Company belum dipilih') : `${nPeserta(p)} peserta · ${rp(p.hargaJual)}`;
      return `${esc(paketOf(mpOf(p)).judul)} · ${fmtRange(p.tglMulai, p.tglSelesai)} · ${who}`;
    }
    if (n === 2) return `Total ${rp(c.fixed)}`;
    if (n === 3) return `Total ${rp(c.totalVar)}`;
    if (n === 4) {
      if (p.jenis === 'b2b') return c.harga ? `Pendapatan bersih <b class="${c.bersih < 0 ? 'neg' : 'pos'}">${rp(c.bersih)}</b>` : '—';
      return c.target && c.harga ? `Profit <b class="${c.profit < 0 ? 'neg' : 'pos'}">${rp(c.profit)}</b>` : '—';
    }
    const cnt = s => p.peserta.filter(x => x.status === s).length;
    return `${cnt('deal')} Deal · ${cnt('draft')} Draft · ${cnt('batal')} Batal`;
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

  const numInput = (name, val, locked, extra = '') =>
    `<input class="input" data-pf="${name}" type="number" min="0" value="${esc(val)}" ${locked ? 'disabled' : ''} ${extra}>`;

  function costTable(gi, locked) {
    const g = COST_GROUPS[gi];
    const { p, errors: E } = state.pf;
    const rows = p.biaya.map((b, i) => ({ b, i })).filter(({ b }) => b.tipe === g.tipe && b.satuan === g.satuan);
    const body = rows.length ? rows.map(({ b, i }) => {
      const r = b.rate ? rateById(b.rate) : null;
      const err = E['b' + i];
      // Master Program: Fixed Cost dari master selalu tampil (MP-FR-06). Quotation: item boleh dihapus (PJ-FR-05).
      const removable = !locked && (isQt() || g.tipe === 'variable' || b.manual);
      const name = b.manual
        ? `<input class="input sm-in" data-bi="${i}" data-bf="item" value="${esc(b.item)}" placeholder="Nama biaya lain" ${locked ? 'disabled' : ''}>`
        : `${esc(b.item)}${r && !r.aktif ? ' <span class="muted">(nonaktif)</span>' : ''} ${modeTag(b.mode)}`;
      return `<tr class="${Number(b.harga) > 0 ? '' : 'zero'} ${err ? 'row-err' : ''}" data-row="${i}">
        <td>${name}${defMeta(b)}</td>
        <td class="num"><input class="input sm-in" type="number" min="0" step="1000" data-bi="${i}" data-bf="harga" value="${esc(b.harga)}"
          ${locked || (b.mode === 'fix' && !isQt()) ? 'disabled' : ''} placeholder="${b.mode === 'manual' ? 'Isi nominal' : ''}" aria-label="Nominal ${esc(b.item)}"></td>
        <td class="num qty" data-fx="${i}">${lineQtyTxt(b, p)}</td>
        <td class="num" data-sub="${i}">${rp(lineTotal(b, p))}</td>
        <td class="act">${removable ? `<button type="button" class="btn btn-ghost sm" data-act="pf-del" data-i="${i}">Hapus</button>` : ''}</td>
      </tr>${err ? `<tr class="row-msg"><td colspan="5"><i>!</i>${esc(err)}</td></tr>` : ''}`;
    }).join('') : `<tr class="norow"><td colspan="5" class="muted">Belum ada item. ${locked ? '' : 'Pilih di atas lalu klik + Tambah.'}</td></tr>`;

    let pick = '';
    if (!locked) {
      const used = p.biaya.map(b => b.rate);
      const choices = db.rate[g.tipe].filter(r => r.aktif && r.satuan === g.satuan && !used.includes(r.id));
      const select = (isQt() || g.tipe === 'variable') ? `<select class="input" id="${g.pick}">${choices.length
        ? `<option value="">— Pilih item dari Master Rate —</option>${choices.map(r => `<option value="${r.id}">${esc(r.item)} · ${r.mode === 'manual' ? 'input manual' : rp(r.cost)}</option>`).join('')}`
        : '<option value="" disabled selected>Semua item master sudah dipakai</option>'}</select>
        <button type="button" class="btn btn-outline sm" data-act="pf-add" data-sel="${g.pick}">+ Tambah</button>` : '';
      pick = `<div class="pickrow">${select}<button type="button" class="btn btn-ghost sm" data-act="pf-manual" data-g="${gi}">+ Biaya lain</button></div>`;
    }
    return `<p class="csec-sub">${g.title}</p>${pick}
      <div class="table-wrap"><table class="table items">
        <thead><tr><th>Item</th><th class="num">${g.nominal}</th><th class="num">Qty</th><th class="num">Total</th><th class="act"></th></tr></thead>
        <tbody>${body}</tbody>
        <tfoot><tr><td colspan="3" class="num"><b>Total</b></td><td class="num"><b id="${g.tot}">${rp(rows.reduce((s, { b }) => s + lineTotal(b, p), 0))}</b></td><td></td></tr></tfoot>
      </table></div>`;
  }

  function totalsFixed(p) {
    const c = calc(p), d = Number(p.durasi) || 0, n = nPeserta(p);
    return `<div class="totals">
      <div class="tr"><span><b>Total fixed cost</b> <span class="muted">= event ${rp(groupSum(p, COST_GROUPS[0]))} + day ${rp(groupSum(p, COST_GROUPS[1]))} × ${d} hari</span></span><span><b>${rp(c.fixed)}</b></span></div>
      <div class="tr"><span class="muted">Fixed cost / peserta (${p.jenis === 'b2b' ? 'jumlah' : 'target'})</span><span class="muted">${n ? rp(c.fixed / n) : '—'}</span></div>
    </div>`;
  }
  function totalsVar(p) {
    const c = calc(p), d = Number(p.durasi) || 0;
    return `<div class="totals">
      <div class="tr"><span>Biaya variable / peserta <span class="muted">= event ${rp(groupSum(p, COST_GROUPS[2]))} + day ${rp(groupSum(p, COST_GROUPS[3]))} × ${d} hari</span></span><span>${rp(c.varPer)}</span></div>
      <div class="tr"><span><b>Total variable cost</b> <span class="muted">= per peserta × ${p.jenis === 'b2b' ? 'jumlah peserta' : 'target peserta'}</span></span><span><b>${rp(c.totalVar)}</b></span></div>
    </div>`;
  }

  const kpiCard = (cls, label, val, sub, extra = '') => `<div class="kpi ${cls}"><div class="kpi-l">${label}</div><div class="kpi-v">${val}</div>${extra}<div class="kpi-s">${sub}</div></div>`;
  const shortRp = v => { const a = Math.abs(v), sg = v < 0 ? '−' : ''; return sg + (a >= 1e9 ? (a / 1e9).toFixed(1) + ' M' : a >= 1e6 ? (a / 1e6).toFixed(1) + ' jt' : a >= 1e3 ? Math.round(a / 1e3) + ' rb' : String(Math.round(a))); };

  // Simulasi Public / Master Program: KPI, Pendapatan vs HPP, grafik profit, target vs realisasi.
  function simPublic(p) {
    const c = calc(p);
    if (!(c.target > 0) || !(c.harga > 0)) return '<div class="sim-empty">Isi <b>target peserta</b> dan <b>harga jual / peserta</b> di bagian 1 untuk melihat simulasi.</div>';
    const d = Number(p.durasi) || 0, contrib = c.harga - c.varPer;
    const gap = c.bepPeserta === null ? null : c.target - c.bepPeserta;
    const bepPct = c.bepPeserta ? Math.min(100, Math.round(c.target / c.bepPeserta * 100)) : 0;
    const tp = p.targetProfit === '' ? null : Number(p.targetProfit);
    let kpis = kpiCard(c.profit < 0 ? 'bad' : 'good', 'Profit', rp(c.profit), `Pendapatan ${rp(c.pendapatan)} − HPP ${rp(c.hpp)}`)
      + kpiCard(c.bepPeserta === null ? 'bad' : gap >= 0 ? 'good' : 'kwarn', 'Jumlah peserta BEP', c.bepPeserta === null ? 'Tidak tercapai' : `${c.bepPeserta} peserta`,
        c.bepPeserta === null ? 'Harga jual tidak menutup biaya variable / peserta'
          : gap >= 0 ? `Target ${c.target} peserta, aman ${gap} di atas BEP` : `Target ${c.target} peserta, kurang ${-gap} peserta`,
        c.bepPeserta !== null ? `<div class="kpi-bar"><span style="width:${bepPct}%"></span></div>` : '')
      + kpiCard(c.bepHarga > c.harga ? 'kwarn' : '', 'Harga BEP / peserta', rp(c.bepHarga), `HPP ÷ target peserta. Harga jual ${rp(c.harga)} (${c.harga >= c.bepHarga ? '+' : '−'}${rp(Math.abs(c.harga - c.bepHarga))})`)
      + kpiCard('', 'HPP', rp(c.hpp), `Fixed ${rp(c.fixed)} + Variable ${rp(c.totalVar)}`);
    if (tp !== null) {
      const ok = c.profit >= tp;
      kpis += kpiCard('tgt ' + (ok ? 'good' : 'kwarn'), 'Target profit', rp(tp), ok ? `Tercapai, lebih ${rp(c.profit - tp)}` : `Kurang ${rp(tp - c.profit)} dari target`);
    }
    const mx = Math.max(c.pendapatan, c.hpp, 1), w = v => `${Math.max(0, v / mx * 100).toFixed(2)}%`;
    const flow = `<div class="sim-flow">
      <div class="fl-row"><div class="fl-l">Pendapatan<small>${rp(c.harga)} × ${c.target} peserta</small></div><div class="fl-bar"><span class="seg rev" style="width:${w(c.pendapatan)}"></span></div><div class="fl-v">${rp(c.pendapatan)}</div></div>
      <div class="fl-row"><div class="fl-l">HPP<small>fixed + variable</small></div><div class="fl-bar"><span class="seg fix" style="width:${w(c.fixed)}" title="Fixed cost"></span><span class="seg var" style="width:${w(c.totalVar)}" title="Variable cost"></span></div><div class="fl-v">${rp(c.hpp)}</div></div>
      <div class="fl-legend"><span><i class="lg fix"></i>Fixed cost ${rp(c.fixed)} = event ${rp(groupSum(p, COST_GROUPS[0]))} + day ${rp(groupSum(p, COST_GROUPS[1]))} × ${d} hari</span>
        <span><i class="lg var"></i>Variable cost ${rp(c.totalVar)} = (${rp(groupSum(p, COST_GROUPS[2]))} + ${rp(groupSum(p, COST_GROUPS[3]))} × ${d} hari) × ${c.target} peserta</span></div>
    </div>`;

    // Realisasi (Quotation Public): peserta fix = peserta Deal; harga & cost tetap, hanya profit berubah (PJ-FR-20).
    const deals = isQt() ? dealOf(p) : [];
    const done = deals.length > 0;
    const nA = deals.length, pendA = deals.reduce((s, x) => s + netOf(p, x), 0), profitA = pendA - c.hpp;
    const profitAt = x => c.harga * x - c.fixed - c.varPer * x;
    const xmax = Math.max(10, Math.ceil(c.target * 1.6), c.bepPeserta ? Math.ceil(c.bepPeserta * 1.3) : 0, done ? Math.ceil(nA * 1.15) : 0);
    const W = 960, H = 230, L = 74, PR = 16, T = 16, B = 28;
    const ys = [profitAt(0), profitAt(xmax), 0].concat(done ? [profitA] : []);
    const y0 = Math.min(...ys), y1 = Math.max(...ys);
    const X = x => L + (x / xmax) * (W - L - PR), Y = v => T + (y1 - v) / ((y1 - y0) || 1) * (H - T - B);
    let svg = `<svg viewBox="0 0 ${W} ${H}" class="sim-svg" role="img" aria-label="Grafik profit menurut jumlah peserta">`;
    [y0, (y0 + y1) / 2, y1, 0].filter((v, i, a) => a.indexOf(v) === i).forEach(v => {
      svg += `<line x1="${L}" x2="${W - PR}" y1="${Y(v)}" y2="${Y(v)}" class="${v === 0 ? 'zero' : 'grid'}"/><text x="${L - 8}" y="${Y(v) + 4}" text-anchor="end" class="ax">${shortRp(v)}</text>`;
    });
    [0, Math.round(xmax / 2), xmax].forEach(x => { svg += `<text x="${X(x)}" y="${H - 10}" text-anchor="middle" class="ax">${x}</text>`; });
    const xb = contrib > 0 ? Math.min(xmax, c.fixed / contrib) : xmax;
    svg += `<polygon points="${X(0)},${Y(0)} ${X(0)},${Y(profitAt(0))} ${X(xb)},${Y(Math.min(0, profitAt(xb)))} ${X(xb)},${Y(0)}" class="area-loss"/>`;
    if (contrib > 0 && xb < xmax) svg += `<polygon points="${X(xb)},${Y(0)} ${X(xmax)},${Y(profitAt(xmax))} ${X(xmax)},${Y(0)}" class="area-gain"/>`;
    svg += `<line x1="${X(0)}" y1="${Y(profitAt(0))}" x2="${X(xmax)}" y2="${Y(profitAt(xmax))}" class="pline"/>`;
    if (c.bepPeserta !== null && c.bepPeserta <= xmax) svg += `<line x1="${X(c.bepPeserta)}" x2="${X(c.bepPeserta)}" y1="${T}" y2="${H - B}" class="bepline"/><text x="${X(c.bepPeserta) + 5}" y="${T + 10}" class="lbl">BEP ${c.bepPeserta} peserta</text>`;
    svg += `<circle cx="${X(c.target)}" cy="${Y(c.profit)}" r="6" class="tdot"/><text x="${X(c.target)}" y="${Y(c.profit) + (c.profit >= 0 ? -12 : 20)}" text-anchor="middle" class="lbl strong">Target ${c.target}: ${shortRp(c.profit)}</text>`;
    if (done) {
      const ly = Math.max(T + 10, Math.min(H - B - 4, Y(profitA) + (profitA >= c.profit ? -12 : 20))); // label tetap di dalam area grafik
      svg += `<circle cx="${X(nA)}" cy="${Y(profitA)}" r="6" class="rdot"/><text x="${X(nA)}" y="${ly}" text-anchor="middle" class="lbl strong">Aktual ${nA}: ${shortRp(profitA)}</text>`;
    }
    svg += '</svg>';
    const chart = `<div class="sim-chart"><div class="sim-chart-h">Profit menurut jumlah peserta <span>(durasi dan harga jual tetap)</span></div>${svg}<div class="sim-chart-x">jumlah peserta</div></div>`;

    let riil;
    if (!isQt()) {
      riil = '<div class="sim-riil muted">Master Program hanya menyimpan angka baseline. Peserta fix, realisasi, dan As Is vs Aktual dihitung di Quotation (MP-FR-17).</div>';
    } else if (done) {
      const dN = nA - c.target, dP = profitA - c.profit, sg = v => (v > 0 ? '+' : v < 0 ? '−' : '') + Math.abs(v);
      const cell = (l, t, r, dl, cls = '') => `<div class="rv"><div class="rv-l">${l}</div><div class="rv-row"><span>Target</span><b>${t}</b></div><div class="rv-row"><span>Aktual</span><b class="${cls}">${r}</b></div>${dl ? `<div class="rv-d ${cls}">${dl}</div>` : ''}</div>`;
      riil = `<div class="sim-riil"><div class="rv-h">Target vs realisasi <span>dari peserta berstatus Deal</span></div><div class="rv-grid">
        ${cell('Peserta', `${c.target} peserta`, `${nA} peserta`, dN === 0 ? 'sesuai target' : `${sg(dN)} peserta dari target`, dN < 0 ? 'neg' : dN > 0 ? 'pos' : '')}
        ${cell('Harga jual / peserta', rp(c.harga), rp(pendA / nA), Math.round(pendA / nA) !== c.harga ? 'rata-rata setelah diskon' : 'tetap, tidak ikut berubah')}
        ${cell('Profit', rp(c.profit), rp(profitA), dP === 0 ? '' : `${dP > 0 ? '+' : '−'}${rp(Math.abs(dP))} dari target`, profitA < 0 || dP < 0 ? 'neg' : 'pos')}
        ${cell('Posisi BEP', c.bepPeserta === null ? '—' : `${c.bepPeserta} peserta`, `${nA} peserta`, c.bepPeserta === null ? '' : nA >= c.bepPeserta ? 'sudah lewat BEP' : `kurang ${c.bepPeserta - nA} peserta untuk BEP`, c.bepPeserta !== null && nA < c.bepPeserta ? 'neg' : 'pos')}
      </div></div>`;
    } else {
      riil = '<div class="sim-riil muted">Realisasi tampil di sini setelah ada peserta berstatus Deal (bagian 5).</div>';
    }
    if (isQt()) riil += `<p class="help">Kalau peserta fix kurang atau lebih dari target, harga jual / peserta tetap sama dan cost tetap. Yang berubah hanya profit (PJ-FR-20) ${q('Q-PJ-09', 'HPP aktual = HPP As Is, atau variable cost ikut jumlah peserta fix?')}.</p>`;
    return `<div class="kpis">${kpis}</div>${flow}${chart}${riil}`;
  }

  // Hasil B2B: total biaya, harga paket, pendapatan bersih (PJ-FR-14). BEP tidak dipakai.
  function simB2b(p) {
    const c = calc(p);
    if (!(c.harga > 0)) return '<div class="sim-empty">Isi <b>harga paket all-in</b> di bagian 1 untuk melihat hasil.</div>';
    const tp = p.targetProfit === '' ? null : Number(p.targetProfit);
    let kpis = kpiCard(c.bersih < 0 ? 'bad' : 'good', 'Pendapatan bersih', rp(c.bersih), `Harga paket ${rp(c.harga)} − total biaya ${rp(c.total)}`)
      + kpiCard('', 'Harga paket all-in', rp(c.harga), `${nPeserta(p)} peserta`)
      + kpiCard('', 'Total biaya', rp(c.total), `Fixed ${rp(c.fixed)} + Variable ${rp(c.totalVar)}`)
      + kpiCard('kwarn', 'Mandays', 'Belum ada', `Output costing B2B, rumus belum ada ${q('Q-PJ-04', 'Rumus Mandays belum ada.')}`);
    if (tp !== null) {
      const ok = c.bersih >= tp;
      kpis += kpiCard('tgt ' + (ok ? 'good' : 'kwarn'), 'Target profit', rp(tp), ok ? `Tercapai, lebih ${rp(c.bersih - tp)}` : `Kurang ${rp(tp - c.bersih)} dari target`);
    }
    const mx = Math.max(c.harga, c.total, 1), w = v => `${Math.max(0, v / mx * 100).toFixed(2)}%`;
    const flow = `<div class="sim-flow">
      <div class="fl-row"><div class="fl-l">Harga paket<small>all-in</small></div><div class="fl-bar"><span class="seg rev" style="width:${w(c.harga)}"></span></div><div class="fl-v">${rp(c.harga)}</div></div>
      <div class="fl-row"><div class="fl-l">Total biaya<small>fixed + variable</small></div><div class="fl-bar"><span class="seg fix" style="width:${w(c.fixed)}"></span><span class="seg var" style="width:${w(c.totalVar)}"></span></div><div class="fl-v">${rp(c.total)}</div></div>
      <div class="fl-legend"><span><i class="lg fix"></i>Fixed cost ${rp(c.fixed)}</span><span><i class="lg var"></i>Variable cost ${rp(c.totalVar)} = ${rp(c.varPer)} per peserta × ${nPeserta(p)} peserta</span></div>
    </div>`;
    return `<div class="kpis">${kpis}</div>${flow}<p class="help">BEP peserta tidak dipakai di B2B karena harga paket all-in (PJ-FR-14).</p>`;
  }

  const simFor = p => p.jenis === 'b2b' ? simB2b(p) : simPublic(p);

  // Bagian 5 Quotation Public: peserta dari Master Peserta, diskon & Deal/Batal per peserta (PJ-FR-09 – PJ-FR-11, PJ-FR-16).
  function pesertaSection() {
    const { p, errors: E } = state.pf;
    const closed = p.status === 'batal';
    const used = p.peserta.map(x => x.pid);
    const choices = db.peserta.filter(x => !used.includes(x.id));
    const pick = closed ? '' : `<div class="pickrow"><select class="input" id="add-ps">${choices.length
      ? `<option value="">— Pilih dari Master Peserta —</option>${choices.map(x => `<option value="${x.id}">${esc(x.kode)} — ${esc(x.nama)} (${esc(x.instansi)})</option>`).join('')}`
      : '<option value="" disabled selected>Semua peserta sudah ditambahkan</option>'}</select>
      <button type="button" class="btn btn-outline sm" data-act="qp-add">+ Tambah peserta</button></div>`;
    const rows = p.peserta.length ? p.peserta.map((x, i) => {
      const ps = pesertaById(x.pid), draft = x.status === 'draft' && !closed, err = E['p' + i];
      const acts = draft ? `<button type="button" class="btn btn-accent sm" data-act="qp-deal" data-i="${i}">Deal</button>
        <button type="button" class="btn btn-ghost sm" data-act="qp-batal" data-i="${i}">Batal</button>
        <button type="button" class="btn btn-ghost sm" data-act="qp-del" data-i="${i}">Hapus</button>` : '';
      return `<tr class="${x.status === 'batal' ? 'zero' : ''} ${err ? 'row-err' : ''}">
        <td>${esc(ps.nama)}<div class="meta">${esc(ps.kode)} · ${esc(ps.instansi)}</div></td>
        <td class="num"><div class="disc"><select class="input sm-in" data-pt="${i}" ${draft ? '' : 'disabled'} aria-label="Tipe diskon ${esc(ps.nama)}"><option value="nominal" ${x.diskonTipe !== 'persen' ? 'selected' : ''}>Rp</option><option value="persen" ${x.diskonTipe === 'persen' ? 'selected' : ''}>%</option></select><input class="input sm-in" type="number" min="0" step="${x.diskonTipe === 'persen' ? 1 : 10000}" data-pi="${i}" value="${esc(x.diskon)}" ${draft ? '' : 'disabled'} aria-label="Diskon ${esc(ps.nama)}"></div></td>
        <td class="num" data-net="${i}">${rp(netOf(p, x))}</td>
        <td class="nowrap">${stBadge(x.status)}</td>
        <td class="act nowrap">${acts}</td>
      </tr>${err ? `<tr class="row-msg"><td colspan="5"><i>!</i>${esc(err)}</td></tr>` : ''}`;
    }).join('') : `<tr class="norow"><td colspan="5" class="muted">Belum ada peserta. Pilih dari Master Peserta lalu klik + Tambah peserta.</td></tr>`;
    const deals = dealOf(p);
    return `${pick}<div class="table-wrap"><table class="table items">
      <thead><tr><th>Peserta</th><th class="num">Diskon (Rp / %)</th><th class="num">Harga net</th><th>Status</th><th class="act"></th></tr></thead>
      <tbody>${rows}</tbody>
      <tfoot><tr><td colspan="2" class="num"><b>Peserta fix (Deal)</b></td><td class="num"><b>${deals.length} peserta · ${rp(deals.reduce((s, x) => s + netOf(p, x), 0))}</b></td><td colspan="2"></td></tr></tfoot>
    </table></div>
    <p class="help">Deal = peserta fix dan bisa ditagih; baris terkunci. Setelah ada peserta Deal, harga jual, target, dan costing quotation ikut terkunci (PJ-FR-17).</p>`;
  }

  // ---------- Form: Master Program (UC-19) & Quotation (UC-22, UC-23) ----------
  function viewQtStart() {
    const p = state.pf.p;
    const finals = db.mprog.filter(m => m.status === 'final');
    const mp = p.mp ? mpOf(p) : null;
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button><h2>Buat Quotation</h2><span class="ref">PJ-FR-02 · PJ-FR-03</span></div>
      <div class="csec"><p class="csec-h">1. Pilih Master Program <span class="muted">hanya yang berstatus Final</span></p>
        ${field('Master Program' + req, `<select class="input" id="qt-mp"><option value="">— Pilih Master Program Final —</option>${finals.map(m => `<option value="${m.id}" ${m.id === p.mp ? 'selected' : ''}>${esc(m.nomor)} — ${esc(paketOf(m).judul)} · ${esc(findRec('varian', paketOf(m).varian).nama)}</option>`).join('')}</select>
          ${mp ? `<span class="hint">${mp.durasi} hari · target ${mp.targetPeserta} peserta · harga standar ${rp(mp.hargaJual)} · HPP ${rp(calc(mp).hpp)}</span>` : ''}`)}
      </div>
      <div class="csec"><p class="csec-h">2. Pilih jenis penjualan <span class="muted">form menyesuaikan customer, harga, dan dokumen; jenis tidak dapat diubah setelah disimpan</span></p>
        <div class="choice-grid">
          <button class="choice" data-act="qt-jenis" data-jenis="public" ${mp ? '' : 'disabled'}>
            ${jenisTag('public')}<h3>Penjualan Public</h3>
            <p>Dipromosikan dulu, peserta mendaftar kemudian. Harga <b>per peserta</b>.</p>
            <ul><li>Peserta dari Master Peserta (bisa banyak)</li><li>Diskon per peserta</li><li>Simulasi HPP, profit, BEP</li><li>Deal / Batal per peserta</li></ul>
          </button>
          <button class="choice" data-act="qt-jenis" data-jenis="b2b" ${mp ? '' : 'disabled'}>
            ${jenisTag('b2b')}<h3>Penjualan B2B</h3>
            <p>Company sudah diketahui. Harga <b>paket all-in</b>.</p>
            <ul><li>Satu company dari Master Company</li><li>Total biaya & pendapatan bersih</li><li>Deal / Batal per quotation</li><li>Surat quotation ke company</li></ul>
          </button>
        </div>
        ${mp ? '' : '<p class="help">Pilih Master Program dahulu.</p>'}
      </div>`;
  }

  function viewCostForm() {
    const { kind, p, isNew, errors: E } = state.pf;
    if (kind === 'qt' && !p.jenis) return viewQtStart();
    const locked = costLocked();
    const qt = kind === 'qt', b2b = p.jenis === 'b2b';
    const warns = costWarnings(p);

    let lockInfo = '';
    if (kind === 'mp' && p.status === 'final') lockInfo = 'Master Program <b>Final</b> — terkunci dan tersedia di pilihan Quotation. Penyesuaian harga/costing untuk penjualan dilakukan di Quotation; perubahan master: batalkan lalu buat program baru.';
    if (p.status === 'batal') lockInfo = qt ? 'Quotation dibatalkan dan tidak dapat diubah.' : 'Master Program dibatalkan — tidak muncul lagi di pilihan Quotation. Quotation yang sudah terbentuk tidak terpengaruh.';
    if (qt && b2b && p.status === 'deal') lockInfo = 'Quotation <b>Deal</b> — terkunci, hanya dapat dilihat. Perubahan: batalkan lalu buat quotation baru.';
    if (qt && !b2b && p.status === 'draft' && locked) lockInfo = 'Sudah ada peserta <b>Deal</b> — harga jual, target, dan costing terkunci (As Is). Peserta baru masih dapat ditambahkan di bagian 5.';

    const header = `<div class="csec"><p class="csec-h">Header</p><div class="grid3">
      ${field(`No. ${qt ? 'quotation' + QPJ08 : 'master program' + QMP02}`, `<input class="input" value="${esc(p.nomor || '')}" placeholder="otomatis saat disimpan" disabled>`)}
      ${field(qt ? 'Jenis penjualan' : 'Jenis', `<div class="static">${qt ? jenisTag(p.jenis) : '<span class="muted">Umum — Public/B2B dipilih di Quotation</span>'}</div>`)}
      ${field('Status', `<div class="static">${stBadge(p.status)}${qt && !b2b ? ` <span class="muted">${dealOf(p).length} peserta Deal</span>` : ''}</div>`)}
    </div></div>`;

    let s1;
    if (!qt) {
      const paketOpts = db.paket.filter(x => x.aktif || x.id === p.paket)
        .map(x => `<option value="${x.id}" ${x.id === p.paket ? 'selected' : ''}>${esc(x.nomor)} — ${esc(x.judul)} · ${esc(findRec('varian', x.varian).nama)}</option>`).join('');
      const paket = p.paket ? paketOf(p) : null;
      s1 = step(1, 'Informasi Program', '', `
        ${field('Paket Produk (dari Product Combine)' + req, `<select class="input" data-pf="paket" ${locked ? 'disabled' : ''}><option value="">— Pilih Paket Produk aktif —</option>${paketOpts}</select>${paket ? `<span class="hint">${ATTRS.map(a => `${a.label}: ${esc(findRec(a.key, paket[a.field]).nama)}`).join(' · ')}</span>` : ''}`, E.paket)}
        <div class="sim-in"><p class="sim-in-h">Input simulasi <span>angka baseline; bisa diubah per quotation tanpa mengubah master</span></p>
          <div class="grid4">
            ${field('Durasi (hari)' + req, numInput('durasi', p.durasi, locked, 'step="1" min="1"'), E.durasi)}
            ${field('Target peserta' + req, numInput('targetPeserta', p.targetPeserta, locked, 'step="1" min="1"'), E.targetPeserta)}
            ${field('Harga jual standar / peserta' + req, numInput('hargaJual', p.hargaJual, locked, 'step="1000"'), E.hargaJual)}
            ${field('Target profit (opsional)', numInput('targetProfit', p.targetProfit, locked, 'step="100000"'))}
          </div></div>`);
    } else {
      const mp = mpOf(p), closed = p.status !== 'draft';
      const custField = b2b ? field('Company (dari Master Company)' + req, `<select class="input" data-pf="customer" ${closed ? 'disabled' : ''}><option value="">— Pilih Company —</option>${db.company.map(c => `<option value="${c.id}" ${c.id === p.customer ? 'selected' : ''}>${esc(c.nama)} — ${esc(c.kota || '')}</option>`).join('')}</select>`, E.customer, 'span2')
        : field('Customer', '<div class="static muted">Peserta dari Master Peserta — diisi di bagian 5</div>', '', 'span2');
      s1 = step(1, 'Informasi Penjualan', '', `
        <div class="grid3">
          ${field('Master Program', `<div class="static"><span class="code">${esc(mp.nomor)}</span> ${esc(paketOf(mp).judul)} <span class="muted">· ${esc(findRec('varian', paketOf(mp).varian).nama)}</span></div>`)}
          ${custField}
          ${field(`Tanggal mulai ${q('Q-PJ-03', 'Nama & format field tanggal/lokasi usulan analis.')}`, `<input class="input" type="date" data-pf="tglMulai" value="${esc(p.tglMulai)}" ${closed ? 'disabled' : ''}>`)}
          ${field('Tanggal selesai', `<input class="input" type="date" data-pf="tglSelesai" value="${esc(p.tglSelesai)}" ${closed ? 'disabled' : ''}>`, E.tglSelesai)}
          ${field('Lokasi acara', `<input class="input" data-pf="lokasi" value="${esc(p.lokasi)}" maxlength="150" placeholder="mis. Offline Bekasi" ${closed ? 'disabled' : ''}>`)}
        </div>
        <div class="sim-in"><p class="sim-in-h">Input simulasi <span>terisi dari ${esc(mp.nomor)}; perubahan hanya berlaku di quotation ini</span></p>
          <div class="grid4">
            ${field('Durasi (hari)' + req, numInput('durasi', p.durasi, locked, 'step="1" min="1"') + `<span class="hint">master: ${mp.durasi} hari</span>`, E.durasi)}
            ${b2b ? `
              ${field('Harga paket all-in' + req, numInput('hargaPaket', p.hargaPaket, locked, 'step="100000"') + '<span class="hint">sudah termasuk biaya pendukung</span>', E.hargaPaket)}
              ${field('Jumlah peserta' + req, numInput('jumlahPeserta', p.jumlahPeserta, locked, 'step="1" min="10"') + '<span class="hint">default 10, minimal 10 orang</span>', E.jumlahPeserta)}`
            : `
              ${field('Target peserta' + req, numInput('targetPeserta', p.targetPeserta, locked, 'step="1" min="1"') + `<span class="hint">master: ${mp.targetPeserta}</span>`, E.targetPeserta)}
              ${field('Harga jual / peserta' + req, numInput('hargaJual', p.hargaJual, locked, 'step="1000"') + `<span class="hint">standar: ${rp(mp.hargaJual)}</span>`, E.hargaJual)}`}
            ${field('Target profit (opsional)', numInput('targetProfit', p.targetProfit, locked, 'step="100000"'))}
          </div></div>`);
    }

    const s2 = step(2, 'Fixed Cost', qt ? 'disalin dari Master Program; nominal, item, dan biaya lain dapat diubah. Qty: event = 1, day = durasi.' : 'semua item Fixed Cost aktif tampil; isi nominal, biarkan 0 kalau tidak dipakai. Qty: event = 1, day = durasi.',
      costTable(0, locked) + costTable(1, locked) + `<div id="pf-tot-fixed">${totalsFixed(p)}</div>`);
    const s3 = step(3, 'Variable Cost', 'pilih item, isi nominal per peserta. Qty otomatis dari jumlah peserta dan durasi.',
      costTable(2, locked) + costTable(3, locked) + `<div id="pf-tot-var">${totalsVar(p)}</div>`);
    const s4 = step(4, b2b ? 'Hasil Costing B2B' : 'Simulasi Hasil', b2b ? 'total biaya, harga paket, pendapatan bersih' : 'dihitung dari target peserta, durasi, dan harga jual / peserta', `<div id="pf-sim">${simFor(p)}</div>`);
    const s5 = qt && !b2b ? step(5, 'Peserta & Deal', 'peserta dari Master Peserta; diskon dan Deal / Batal per peserta', pesertaSection()) : '';

    const print = qt ? `<button type="button" class="btn btn-ghost" data-act="qt-print" data-id="${p.id || ''}">Cetak surat quotation</button>` : '';
    let actions = `<button type="button" class="btn btn-ghost" data-act="pf-back">${isNew ? 'Batal' : 'Kembali'}</button>`;
    if (kind === 'mp') {
      if (p.status === 'draft') actions += `${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="mp-cancel" data-id="${p.id}">Batalkan Program</button>`}
        <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
        ${isNew ? '' : '<button type="button" class="btn btn-accent" data-act="pf-confirm">Finalkan program</button>'}`;
      else if (p.status === 'final') actions += `<button type="button" class="btn btn-outline" data-act="mp-cancel" data-id="${p.id}">Batalkan</button>
        <button type="button" class="btn btn-accent" data-act="qt-from-mp" data-id="${p.id}">Buat quotation</button>`;
    } else if (p.status === 'draft') {
      actions += `${!isNew && !dealOf(p).length ? `<button type="button" class="btn btn-outline" data-act="qt-cancel" data-id="${p.id}">Batalkan Quotation</button>` : ''}
        ${isNew ? '' : print}
        <button type="button" class="btn btn-outline" data-act="pf-save">${isNew ? 'Simpan Draft' : 'Simpan'}</button>
        ${b2b && !isNew ? '<button type="button" class="btn btn-accent" data-act="pf-confirm">Deal</button>' : ''}`;
    } else actions += print;

    const title = kind === 'mp' ? (isNew ? 'Buat Master Program' : `Master Program ${esc(p.nomor)}`) : (isNew ? `Buat Quotation ${JENIS[p.jenis]}` : `Quotation ${esc(p.nomor)}`);
    const ref = kind === 'mp' ? 'MP-FR-02 – MP-FR-10' : (b2b ? 'PJ-FR-04 – PJ-FR-08 · PJ-FR-13 · PJ-FR-14' : 'PJ-FR-04 – PJ-FR-12');
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button><h2>${title}</h2><span class="ref">${ref}</span></div>
      ${lockInfo ? `<div class="alert">${lockInfo}</div>` : ''}
      <div id="pf-warn" class="alert ${warns.length ? '' : 'hidden'}">${warns.map(esc).join('<br>')}</div>
      <div class="pub-form">${header}${s1}${s2}${s3}${s4}${s5}</div>
      <div class="form-actions">${actions}</div>`;
  }

  // Hitung ulang tanpa render penuh, agar fokus input tidak hilang saat mengetik.
  function refreshCalc() {
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
    COST_GROUPS.forEach(g => {
      const el = $('#' + g.tot);
      if (el) el.textContent = rp(p.biaya.filter(b => b.tipe === g.tipe && b.satuan === g.satuan).reduce((s, b) => s + lineTotal(b, p), 0));
    });
    (p.peserta || []).forEach((x, i) => { const el = $(`[data-net="${i}"]`); if (el) el.textContent = rp(netOf(p, x)); });
    $('#pf-tot-fixed').innerHTML = totalsFixed(p);
    $('#pf-tot-var').innerHTML = totalsVar(p);
    $('#pf-sim').innerHTML = simFor(p);
    [1, 2, 3, 4, 5].forEach(n => { const el = $(`[data-sum="${n}"]`); if (el) el.innerHTML = stepSummary(n, p); });
    const w = costWarnings(p), box = $('#pf-warn');
    box.innerHTML = w.map(esc).join('<br>');
    box.classList.toggle('hidden', !w.length);
  }

  function openCostForm(kind, rec, preset) {
    const blank = kind === 'mp'
      ? { id: null, nomor: null, status: 'draft', paket: '', durasi: 1, targetPeserta: '', hargaJual: '', targetProfit: '', biaya: allFixed() }
      : Object.assign({ id: null, nomor: null, status: 'draft', mp: '', jenis: '' }, preset || {});
    state.pf = { kind, p: rec ? clone(rec) : blank, isNew: !rec, errors: {}, collapsed: {} };
    state.view = kind === 'mp' ? 'mpForm' : 'qtForm';
    render();
    window.scrollTo(0, 0);
  }

  function validateCost() {
    const { kind, p } = state.pf, E = {};
    const posInt = v => v !== '' && Number.isInteger(Number(v)) && Number(v) > 0;
    if (kind === 'mp' && !p.paket) E.paket = 'Pilih Paket Produk';
    if (!posInt(p.durasi)) E.durasi = 'Durasi bilangan bulat > 0';
    if (p.jenis === 'b2b') {
      if (!p.customer) E.customer = 'Pilih Company';
      if (p.hargaPaket === '' || p.hargaPaket < 0) E.hargaPaket = 'Harga paket wajib diisi';
      if (!posInt(p.jumlahPeserta) || Number(p.jumlahPeserta) < 10) E.jumlahPeserta = 'Jumlah peserta B2B minimal 10 orang';
    } else {
      if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
      if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual wajib diisi';
    }
    if (kind === 'qt' && p.tglMulai && p.tglSelesai && p.tglSelesai < p.tglMulai) E.tglSelesai = 'Tanggal selesai sebelum tanggal mulai';
    p.biaya.forEach((b, i) => {
      if (b.manual && !String(b.item).trim()) E['b' + i] = 'Nama biaya lain wajib diisi';
      else if (b.harga === '' || b.harga < 0) E['b' + i] = `Nominal ${b.item} wajib diisi`;
    });
    (p.peserta || []).forEach((x, i) => {
      const d = Number(x.diskon) || 0;
      if (x.diskonTipe === 'persen' ? (d < 0 || d > 100) : d > Number(p.hargaJual)) E['p' + i] = x.diskonTipe === 'persen' ? 'Diskon persen harus 0–100' : 'Diskon melebihi harga jual / peserta';
    });
    state.pf.errors = E;
    return !Object.keys(E).length;
  }

  function saveCost(silent) {
    if (!validateCost()) {
      render();
      toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn');
      return null;
    }
    const pf = state.pf, list = pf.kind === 'mp' ? db.mprog : db.quot;
    let rec;
    if (pf.isNew) {
      rec = Object.assign(clone(pf.p), { id: uid(), nomor: nextCode(pf.kind === 'mp' ? 'MP' : 'QT') });
      list.push(rec);
    } else {
      rec = list.find(x => x.id === pf.p.id);
      Object.assign(rec, clone(pf.p));
    }
    state.pf = { kind: pf.kind, p: clone(rec), isNew: false, errors: {}, collapsed: pf.collapsed };
    render();
    if (!silent) toast(`${pf.kind === 'mp' ? 'Master Program' : 'Quotation'} ${rec.nomor} tersimpan${rec.status === 'draft' ? ' sebagai Draft' : ''}`);
    return rec;
  }

  // Setelah aksi status: sinkronkan form yang sedang terbuka dengan data tersimpan.
  const syncPf = rec => { if (state.pf && state.pf.p.id === rec.id && (state.view === 'mpForm' || state.view === 'qtForm')) state.pf.p = clone(rec); };
  const sumRow = (l, v) => `<div class="sum-row"><span>${l}</span><b>${v}</b></div>`;
  const warnBox = list => list.map(w => `<div class="warn"><i>!</i>${esc(w)}</div>`).join('');

  // ---------- Status Master Program (MP-FR-11 – MP-FR-15) ----------
  function mpFinal(rec) {
    const c = calc(rec), w = [];
    if (c.profit < 0) w.push('Profit minus.');
    if (c.bepPeserta === null) w.push('BEP tidak tercapai: harga jual ≤ biaya variable / peserta.');
    else if (c.bepPeserta > c.target) w.push(`BEP ${c.bepPeserta} peserta di atas target ${c.target} peserta.`);
    openModal({
      title: 'Finalkan Master Program',
      body: `<p>Setelah dikonfirmasi, program menjadi <b>Final</b>: angka menjadi baseline resmi, terkunci, dan muncul sebagai pilihan di Quotation.</p>
        <div class="summary">${sumRow('Program', `${esc(rec.nomor)} — ${esc(paketOf(rec).judul)}`)}${sumRow('Durasi', `${rec.durasi} hari`)}${sumRow('Target peserta', `${rec.targetPeserta} peserta`)}
        ${sumRow('Harga jual / peserta', rp(rec.hargaJual))}${sumRow('HPP', rp(c.hpp))}${sumRow('Profit', rp(c.profit))}
        ${sumRow('BEP', c.bepPeserta === null ? 'tidak tercapai' : `${c.bepPeserta} peserta · ${rp(c.bepHarga)} / peserta`)}</div>${warnBox(w)}`,
      saveLabel: 'Ya, finalkan',
      onSave: () => { rec.status = 'final'; syncPf(rec); toast(`Master Program ${rec.nomor} Final — tersedia di pilihan Quotation`); }
    });
  }

  function mpCancel(rec) {
    const used = db.quot.filter(x => x.mp === rec.id).length;
    openModal({
      title: 'Batalkan Master Program',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(rec.nomor)} — ${esc(paketOf(rec).judul)}</b>? Program tidak akan muncul lagi di pilihan Quotation.
        ${used ? `<br><span class="muted">${used} quotation yang sudah terbentuk dari program ini tidak terpengaruh.</span>` : ''}</p>`,
      saveLabel: 'Batalkan Program', danger: true,
      onSave: () => { rec.status = 'batal'; syncPf(rec); toast(`Master Program ${rec.nomor} dibatalkan`); }
    });
  }

  // ---------- Status Quotation (PJ-FR-15 – PJ-FR-19) ----------
  function qtDeal(rec) {
    const c = calc(rec);
    openModal({
      title: 'Konfirmasi Deal',
      body: `<p>Setelah dikonfirmasi, quotation menjadi <b>Deal</b> dan terkunci. Langkah berikutnya (Sales Order) belum ditentukan ${q('Q-PJ-10', 'Langkah sesudah Deal B2B belum ditentukan.')}.</p>
        <div class="summary">${sumRow('Quotation', `${esc(rec.nomor)} — ${esc(paketOf(mpOf(rec)).judul)}`)}${sumRow('Company', esc(companyOf(rec).nama))}${sumRow('Durasi', `${rec.durasi} hari`)}
        ${sumRow('Harga paket', rp(rec.hargaPaket))}${sumRow('Total biaya', rp(c.total))}${sumRow('Pendapatan bersih', rp(c.bersih))}</div>
        ${c.bersih < 0 ? warnBox(['Pendapatan bersih minus.']) : ''}`,
      saveLabel: 'Ya, Deal',
      onSave: () => { rec.status = 'deal'; syncPf(rec); toast(`Quotation ${rec.nomor} Deal`); }
    });
  }

  function qtCancel(rec) {
    if (dealOf(rec).length) return toast('Sudah ada peserta Deal — quotation tidak dapat dibatalkan', 'warn');
    openModal({
      title: 'Batalkan quotation',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(rec.nomor)}</b>? Quotation Batal tidak dapat diubah. Untuk perubahan, buat quotation baru.</p>`,
      saveLabel: 'Batalkan Quotation', danger: true,
      onSave: () => { rec.status = 'batal'; syncPf(rec); toast(`Quotation ${rec.nomor} dibatalkan`); }
    });
  }

  function pesertaStatus(i, next) {
    const rec = saveCost(true);
    if (!rec) return;
    const x = rec.peserta[i], ps = pesertaById(x.pid), c = calc(rec), net = netOf(rec, x);
    const deal = next === 'deal';
    const w = [];
    if (deal && net < c.varPer) w.push(`Harga net ${rp(net)} di bawah biaya variable / peserta ${rp(c.varPer)}.`);
    if (deal && c.profit < 0) w.push('Profit quotation (target) minus.');
    openModal({
      title: deal ? 'Konfirmasi Deal peserta' : 'Batalkan peserta',
      body: deal ? `<p>Peserta menjadi <b>Deal</b> (peserta fix, bisa ditagih) dan barisnya terkunci.</p>
        <div class="summary">${sumRow('Peserta', `${esc(ps.nama)} — ${esc(ps.instansi)}`)}${sumRow('Harga jual / peserta', rp(rec.hargaJual))}${sumRow('Diskon', diskonTxt(x))}${sumRow('Total', rp(net))}</div>${warnBox(w)}`
        : `<p style="margin:0 0 14px">Tandai <b>${esc(ps.nama)}</b> sebagai Batal (tidak jadi ikut)?</p>`,
      saveLabel: deal ? 'Ya, Deal' : 'Tandai Batal', danger: !deal,
      onSave: () => { x.status = next; syncPf(rec); toast(`${ps.nama}: ${deal ? 'Deal' : 'Batal'}`); }
    });
  }

  // ---------- Laporan As Is vs Aktual (PJ-FR-22) ----------
  function viewLaporan() {
    const rows = db.quot.filter(x => x.jenis === 'public' && x.status !== 'batal');
    const diff = n => `<span class="${n < 0 ? 'neg' : 'pos'}">${n > 0 ? '+' : ''}${rp(n)}</span>`;
    const body = rows.length ? rows.map(x => {
      const c = calc(x), deals = dealOf(x), pend = deals.reduce((s, d) => s + netOf(x, d), 0), prof = pend - c.hpp;
      return `<tr>
        <td><span class="code">${esc(x.nomor)}</span><br><span class="muted">${esc(paketOf(mpOf(x)).judul)} · ${fmtRange(x.tglMulai, x.tglSelesai)}</span></td>
        <td class="money">${c.target}</td><td class="money">${deals.length}</td>
        <td class="money">${rp(c.pendapatan)}</td><td class="money">${rp(pend)}</td>
        <td class="money">${rp(c.hpp)}</td>
        <td class="money">${rp(c.profit)}</td><td class="money">${rp(prof)}</td><td class="money">${diff(prof - c.profit)}</td>
      </tr>`;
    }).join('') : '<tr><td colspan="9" class="empty">Belum ada quotation Public.</td></tr>';
    return `${pageHead('As Is vs Aktual', 'Perbandingan angka quotation (As Is) dengan peserta fix berstatus Deal (Aktual) untuk penjualan Public. Harga jual dan cost tetap; yang berubah hanya profit.', 'PJ-FR-20 · PJ-FR-22')}
      <div class="notice"><span>Pendapatan aktual = Σ harga net peserta Deal. Profit aktual = pendapatan aktual − HPP As Is. ${q('Q-PJ-09', 'Apakah variable cost ikut jumlah peserta fix? Teks sumber: cost tetap.')}</span><span class="ref">PJ-FR-20</span></div>
      <div class="card"><div class="table-wrap"><table class="table report">
        <thead>
          <tr><th rowspan="2">Quotation</th><th colspan="2" class="grp">Peserta</th><th colspan="2" class="grp">Pendapatan</th><th rowspan="2" class="money">HPP</th><th colspan="3" class="grp">Profit</th></tr>
          <tr><th class="money">Target</th><th class="money">Deal</th><th class="money">As Is</th><th class="money">Aktual</th><th class="money">As Is</th><th class="money">Aktual</th><th class="money">Selisih</th></tr>
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
  const LIST = { paket: listPaket, peserta: listPeserta, company: listCompany, rate: listRate, mprog: listMprog, quot: listQuot };
  const currentList = () => MASTERS[state.view] ? listMaster(state.view) : LIST[state.view]();
  const VIEWS = {
    paket: viewPaket, combine: viewCombine, katalog: viewKatalog, peserta: viewPeserta, company: viewCompany,
    rate: viewRate, mprog: viewMprog, quot: viewQuot, mpForm: viewCostForm, qtForm: viewCostForm, laporan: viewLaporan
  };

  function render() {
    renderNav();
    const v = state.view;
    $('#view').innerHTML = MASTERS[v] ? viewMaster(v) : VIEWS[v]();
  }

  // ---------- Events ----------
  const mpById = el => db.mprog.find(x => x.id === +el.dataset.id);
  const qtById = el => db.quot.find(x => x.id === +el.dataset.id);
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
      if (db.quot.some(x => x.customer === r.id)) return toast(`${r.nama} sudah dipakai quotation B2B — tidak dapat dihapus`, 'warn');
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
    'qt-deal': el => qtDeal(qtById(el)),
    'qt-cancel': el => qtCancel(qtById(el)),
    'qt-print': () => toast('Format surat quotation belum ditentukan (Q-PJ-11)', 'warn'),
    'qt-jenis': el => {
      const p = state.pf.p;
      Object.assign(p, fromMP(mpOf(p), el.dataset.jenis));
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
      if (rec) (state.pf.kind === 'mp' ? mpFinal : qtDeal)(rec);
    },
    'qp-add': () => {
      const sel = $('#add-ps');
      if (!sel.value) return toast('Pilih peserta dari Master Peserta dahulu', 'warn');
      state.pf.p.peserta.push({ pid: +sel.value, diskonTipe: 'nominal', diskon: 0, status: 'draft' });
      render();
    },
    'qp-del': el => { state.pf.p.peserta.splice(+el.dataset.i, 1); state.pf.errors = {}; render(); },
    'qp-deal': el => pesertaStatus(+el.dataset.i, 'deal'),
    'qp-batal': el => pesertaStatus(+el.dataset.i, 'batal')
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
  const TEXT_FIELDS = ['tglMulai', 'tglSelesai', 'lokasi'];
  const inCostForm = () => state.view === 'mpForm' || state.view === 'qtForm';
  function onProgramInput(t) {
    const p = state.pf.p;
    if (t.dataset.pf) {
      const k = t.dataset.pf;
      p[k] = NUM_FIELDS.includes(k) ? num(t.value) : TEXT_FIELDS.includes(k) ? t.value : (t.value ? Number(t.value) : '');
    } else if (t.dataset.pi) {
      p.peserta[+t.dataset.pi].diskon = num(t.value);
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
    if (inCostForm() && (t.dataset.pf || t.dataset.bi || t.dataset.pi) && t.tagName === 'INPUT') onProgramInput(t);
  });

  document.addEventListener('change', e => {
    const t = e.target;
    if (t.id === 'status' || t.id === 'fstatus') { state.status = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'fjenis') { state.jenis = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.dataset.field && state.form) {
      state.form.rows[+t.dataset.row][t.dataset.field] = Number(t.value) || '';
    }
    if (inCostForm() && t.dataset.pf && t.tagName === 'SELECT') {
      onProgramInput(t);
      render(); // tampilkan atribut paket / ringkasan company terpilih
    }
    if (t.id === 'qt-mp') { state.pf.p.mp = Number(t.value) || ''; render(); }
    if (inCostForm() && t.dataset.pt) { state.pf.p.peserta[+t.dataset.pt].diskonTipe = t.value; render(); }
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
