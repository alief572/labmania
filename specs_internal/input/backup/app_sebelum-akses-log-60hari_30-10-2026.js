/* Labmania — Master Data, Master Program, Penjualan Program (mockup)
   Acuan perilaku: specs/docs/fr-nfr.md draf 8, fr-nfr-master-program.md draf 4, fr-nfr-penjualan-program.md draf 4.
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
  // Isian uang: tampil dengan pemisah ribuan titik (600.000), disimpan sebagai angka (NFR format rupiah).
  const fmtRibu = v => (v === '' || v == null || isNaN(Number(v))) ? '' : String(Math.round(Number(v))).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  const digits = s => String(s == null ? '' : s).replace(/\D/g, '');
  const moneyIn = (attrs, val) => `<input class="input money-in" type="text" inputmode="numeric" autocomplete="off" ${attrs} value="${fmtRibu(val)}">`;
  // Format ulang saat mengetik; posisi kursor dijaga menurut jumlah digit di kirinya. Capture: jalan sebelum handler lain.
  document.addEventListener('input', e => {
    const t = e.target;
    if (!t.classList || !t.classList.contains('money-in')) return;
    const pos = t.selectionStart == null ? t.value.length : t.selectionStart;
    const left = digits(t.value.slice(0, pos)).length, d = digits(t.value).replace(/^0+(?=\d)/, '');
    t.value = d ? fmtRibu(d) : '';
    let i = 0, seen = 0; while (i < t.value.length && seen < left) { if (/\d/.test(t.value[i])) seen++; i++; }
    try { t.setSelectionRange(i, i); } catch (_) { /* jsdom / input tanpa fokus */ }
  }, true);
  const clone = o => JSON.parse(JSON.stringify(o));

  // Format kode belum ditetapkan (Q-01, Q-MP-02, Q-PJ-08) — prefix + 3 digit hanya contoh.
  const codeSeq = {};
  const pad = n => String(n).padStart(3, '0');
  const nextCode = p => { codeSeq[p] = (codeSeq[p] || 0) + 1; return `${p}-${pad(codeSeq[p])}`; };
  const peekCode = p => `${p}-${pad((codeSeq[p] || 0) + 1)}`;
  // Nomor transaksi usulan: PREFIX-YYMM-NNN, urut ulang tiap bulan per prefix (MP, PJP = Public, PJB = B2B).
  const YYMM = (d => String(d.getFullYear()).slice(2) + String(d.getMonth() + 1).padStart(2, '0'))(new Date());
  const docNo = p => nextCode(`${p}-${YYMM}`);
  const PJ_PREFIX = { public: 'PJP', b2b: 'PJB' };

  const q = (id, text) => `<span class="q" title="${esc(text)}">${id}</span>`;
  const Q01 = q('Q-01', 'Format penomoran kode menunggu standar dari Labmania. Contoh di mockup: PREFIX-001.');

  // ---------- Master config ----------
  const MASTERS = {
    jenisProduk: {
      title: 'Jenis Produk', code: 'Kode Jenis Product', name: 'Jenis Product', form: 'Jenis Product',
      prefix: 'JP', fr: 'FR-04', desc: 'Jenis produk yang dijual Labmania, mis. Pelatihan atau Sertifikasi.'
    },
    jenisSertifikasi: {
      title: 'Jenis Sertifikat', code: 'Kode Sertifikat', name: 'Jenis Sertifikat', form: 'Jenis Sertifikat',
      prefix: 'JS', fr: 'FR-05', desc: 'Jenis sertifikat yang diterima peserta, mis. Sertifikat BNSP.'
    },
    jenisBidang: {
      title: 'Jenis Bidang', code: 'Kode Bidang', name: 'Jenis Bidang', form: 'Jenis Bidang',
      prefix: 'JB', fr: 'FR-06', desc: 'Bidang keilmuan/standar yang dibahas di pelatihan. Juga dipakai untuk mengelompokkan peserta.'
    }
    // Master Varian Pelaksanaan dihapus (input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/).
  };

  // Urutan kolom mengikuti mockup klien: Produk, Bidang, Sertifikat (istilah: input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/; Varian dihapus: input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/).
  const ATTRS = [
    { key: 'jenisProduk', field: 'produk', label: 'Produk' },
    { key: 'jenisBidang', field: 'bidang', label: 'Bidang' },
    { key: 'jenisSertifikasi', field: 'sertifikasi', label: 'Sertifikat' }
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

  const idOf = (key, nama) => db[key].find(r => r.nama === nama).id;
  const findRec = (key, id) => db[key].find(r => r.id === id);

  // Paket contoh, agar daftar tidak kosong.
  db.paket = [];
  const seedPaket = (judul, produk, bidang, sertifikasi) => db.paket.push({
    id: uid(), nomor: nextCode('PC'), judul, aktif: true,
    produk: idOf('jenisProduk', produk), bidang: idOf('jenisBidang', bidang),
    sertifikasi: idOf('jenisSertifikasi', sertifikasi)
  });
  seedPaket('Pelatihan ISO/IEC 17025:2017', 'Pelatihan', 'ISO 17025', 'Sertifikat Labmania');
  seedPaket('Pelatihan ISO/IEC 17025:2017', 'Pelatihan', 'ISO 17025', 'Sertifikat Labmania KAN ISO 29993');
  seedPaket('Sertifikasi Petugas Sampling', 'Sertifikasi', 'Petugas Sampling', 'Sertifikat BNSP');

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
  ].map(c => Object.assign({ id: uid(), kode: nextCode('CO'), aktif: true }, c));

  const COA = ['6101 · Biaya Trainer', '6102 · Biaya Transport & Akomodasi', '6103 · Biaya Konsumsi',
    '6104 · Biaya Perlengkapan Training', '6105 · Biaya Pemasaran', '6106 · Biaya Administrasi'];

  // Tanpa mode harga: Default Cost selalu dapat diedit saat costing (FR-19).
  const seedRate = (prefix, tipe, rows) => rows.map(([item, satuan, cost, ket, coa]) =>
    ({ id: uid(), kode: nextCode(prefix), tipe, item, satuan, cost, ket, coa, aktif: true }));
  db.rate = {
    fixed: seedRate('FC', 'fixed', [
      ['Transport Trainer Luar Kota PP', 'Event', 250000, 'Transport / Hari', COA[1]],
      ['Marketing/Ads', 'Event', 0, 'Iklan', COA[4]],
      ['Dokumentasi', 'Event', 0, 'Dokumentasi', COA[5]],
      ['Banner/Promosi', 'Event', 100000, 'Promosi', COA[4]],
      ['Administrasi', 'Event', 0, 'Administrasi', COA[5]],
      ['Mandays Rate Charge Labmania', 'Event', 1600000, 'Sumber: per Jam → Event', COA[0]],
      ['Honor Trainer', 'Event', 2125000, 'Sumber: per Jam → Event', COA[0]],
      ['Hotel Trainer **', 'Day', 375000, 'Hotel bintang 2', COA[1]],
      ['Hotel Trainer ***', 'Day', 0, 'Hotel bintang 3', COA[1]],
      ['Konsumsi Trainer, di luar konsumsi selama training', 'Day', 140000, 'Konsumsi trainer / Hari', COA[2]]
    ]),
    variable: seedRate('VC', 'variable', [
      ['Modul', 'Peserta/Event', 50000, 'Modul', COA[3]],
      ['Sertifikat', 'Peserta/Event', 5000, 'Sertifikat', COA[3]],
      ['Seminar Kit', 'Peserta/Event', 35000, 'Kit', COA[3]],
      ['Goodybag', 'Peserta/Event', 5000, 'Goodybag', COA[3]],
      ['Merchandise', 'Peserta/Event', 0, 'Merchandise', COA[3]],
      ['Sertifikasi LSP', 'Peserta/Event', 0, 'Sertifikasi LSP', COA[0]],
      ['Ongkir Dokumen', 'Peserta/Event', 15000, 'Ongkir Dokumen', COA[5]],
      ['Makan Siang Training', 'Peserta/Day', 50000, 'Untuk peserta + trainer + asisten', COA[2]],
      ['Coffee Break', 'Peserta/Day', 40000, 'Untuk peserta + trainer + asisten', COA[2]]
    ])
  };
  // Satuan dibatasi per tab (FR-25): Hours dicatat sebagai Event, Mandays bukan satuan.
  const RATE_TAB = {
    fixed: { title: 'Fixed Cost', prefix: 'FC', fr: 'FR-20', satuan: ['Event', 'Day'] },
    variable: { title: 'Variable Cost', prefix: 'VC', fr: 'FR-19', satuan: ['Peserta/Event', 'Peserta/Day'] }
  };
  const rateById = id => db.rate.fixed.concat(db.rate.variable).find(r => r.id === id);
  const rateByItem = item => db.rate.fixed.concat(db.rate.variable).find(r => r.item === item);

  // ---------- Master Program & Penjualan Program (fr-nfr-master-program.md, fr-nfr-penjualan-program.md) ----------
  const MP_STATUS = { draft: 'Draft', final: 'Final', batal: 'Batal' };
  const QT_STATUS = { draft: 'Draft', final: 'Final', batal: 'Batal' };
  const STATUS = { draft: 'Draft', final: 'Final', batal: 'Batal' };
  const JENIS = { public: 'Public', b2b: 'B2B' };

  const biayaFromRate = (r, harga) => ({
    rate: r.id, tipe: r.tipe, item: r.item, satuan: r.satuan, manual: false,
    harga: harga !== undefined ? harga : r.cost
  });

  // Pengali variable cost = target peserta, sama untuk Master Program, Public, dan B2B (input/Master - Buat Program/2026-10-25-jalur-public-b2b/).
  const nPeserta = p => Number(p.targetPeserta) || 0;
  // B2B minimal 10 peserta, dari Penjualan Program sampai Quotation (input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/).
  const B2B_MIN = 10;
  // Nominal × faktor satuan: Event / Peserta/Event = 1, Day / Peserta/Day = durasi (fr-nfr-master-program.md bagian 8).
  const perUnit = (b, d) => (Number(b.harga) || 0) * ((b.satuan === 'Day' || b.satuan === 'Peserta/Day') ? d : 1);

  function calc(p) {
    const d = Number(p.durasi) || 0, n = nPeserta(p);
    const fixed = p.biaya.filter(b => b.tipe === 'fixed').reduce((s, b) => s + perUnit(b, d), 0);
    const varPer = p.biaya.filter(b => b.tipe === 'variable').reduce((s, b) => s + perUnit(b, d), 0);
    const totalVar = varPer * n;
    const hpp = fixed + totalVar;
    const harga = Number(p.hargaJual) || 0;
    const pendapatan = harga * n; // tanpa diskon untuk sementara (input/Master - Buat Program/2026-10-23-tanpa-diskon/)
    const profit = pendapatan - hpp;
    const bepHarga = n ? hpp / n : null;
    // Nilai pada x peserta (BEP & tabel profit): harga tetap, variable cost ikut jumlah peserta.
    const at = x => {
      const pd = harga * x, h = fixed + varPer * x;
      return { pendapatan: pd, hpp: h, profit: pd - h };
    };
    // BEP peserta = Fixed ÷ (harga per peserta − variable per peserta), dibulatkan ke atas; dicocokkan dengan at() agar konsisten.
    let bepPeserta = null;
    if (harga > varPer) {
      bepPeserta = Math.max(1, Math.ceil(fixed / (harga - varPer)));
      while (bepPeserta > 1 && at(bepPeserta - 1).profit >= 0) bepPeserta--;
      while (at(bepPeserta).profit < 0 && bepPeserta < 1e6) bepPeserta++;
    }
    return { fixed, varPer, totalVar, hpp, target: n, harga, pendapatan, profit, bepHarga, bepPeserta, at };
  }

  const pidOf = nomor => db.paket.find(p => p.nomor === nomor).id;
  // Data contoh: Fixed Cost aktif yang Default Cost-nya > 0 (item tidak terpakai tidak ditampilkan).
  const allFixed = () => db.rate.fixed.filter(r => r.aktif && Number(r.cost) > 0).map(r => biayaFromRate(r));
  const fromRate = names => names.map(i => biayaFromRate(rateByItem(i)));

  db.mprog = [];
  const seedMP = o => { const p = Object.assign({ id: uid(), nomor: docNo('MP'), targetProfit: '' }, o); db.mprog.push(p); return p; };
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

  // Durasi Penjualan Program dihitung dari tanggal mulai–selesai (inklusif); null bila tanggal belum lengkap/terbalik.
  const durasiTgl = p => p.tglMulai && p.tglSelesai && p.tglSelesai >= p.tglMulai
    ? Math.round((Date.parse(p.tglSelesai) - Date.parse(p.tglMulai)) / 864e5) + 1 : null;

  // Penjualan Program menyalin angka & costing Master Program, sama untuk Public dan B2B (PJ-FR-04).
  const fromMP = (mp, jenis) => ({
    mp: mp.id, jenis, durasi: mp.durasi, targetProfit: mp.targetProfit,
    targetPeserta: mp.targetPeserta, hargaJual: mp.hargaJual,
    tglMulai: '', tglSelesai: '', waktu: '', lokasi: '', company: '', trainer: '', biaya: clone(mp.biaya)
  });
  db.quot = []; // Penjualan Program
  const seedQT = o => { const q = Object.assign({ id: uid(), status: 'draft' }, o); q.nomor = docNo(PJ_PREFIX[q.jenis]); q.durasi = durasiTgl(q) || q.durasi; db.quot.push(q); return q; };
  seedQT(Object.assign(fromMP(mp1, 'public'), { hargaJual: 650000, tglMulai: '2026-11-10', tglSelesai: '2026-11-11', lokasi: 'Offline Bekasi' }));
  seedQT(Object.assign(fromMP(mp2, 'b2b'), {
    status: 'final', hargaJual: 2250000, targetPeserta: 20, company: db.company[0].id,
    tglMulai: '2026-11-17', tglSelesai: '2026-11-19', lokasi: 'In house — Bekasi',
    biaya: clone(mp2.biaya).concat([{ rate: null, tipe: 'fixed', item: 'Sewa alat peraga', satuan: 'Event', mode: 'manual', manual: true, harga: 1500000 }])
  }));
  seedQT(Object.assign(fromMP(mp1, 'b2b'), { company: db.company[1].id, hargaJual: 700000, targetPeserta: 15, tglMulai: '2026-11-24', tglSelesai: '2026-11-25', lokasi: 'In house — Surabaya' }));
  seedQT(Object.assign(fromMP(mp2, 'public'), { status: 'final', durasi: 2, tglMulai: '2026-12-01', tglSelesai: '2026-12-02', lokasi: 'Online (Zoom)' }));

  // ---------- State & navigation ----------
  const state = {
    view: 'dashboard', q: '', status: 'all', jenis: 'all', page: 1, rateTab: 'fixed', form: null, pf: null, qtnPj: null, qtnTab: 'ongoing',
    navOpen: { 'Master Data': true, 'Penjualan Program': true, Quotation: true }, navQ: ''
  };

  // Dua modul (FR-27): Master Data → Penjualan Program (sub modul Master Program & Penjualan Program); grup di bawahnya adalah sub modul.
  const ICON_DB = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>';
  const ICON_SALE = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>';
  const ICON_QTN = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H8l-4 4z"/><path d="M8 9h8M8 12h5"/></svg>';
  const ICON_LOG = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
  const MODULES = [
    {
      name: 'Master Data', icon: ICON_DB, groups: [
        { group: 'Atribut Produk', items: [['jenisProduk', 'Jenis Produk'], ['jenisSertifikasi', 'Jenis Sertifikat'], ['jenisBidang', 'Jenis Bidang']] },
        { group: 'Produk', items: [['paket', 'Product Combine'], ['katalog', 'Katalog Produk']] },
        { group: 'Customer', items: [['peserta', 'Master Peserta'], ['company', 'Master Company']] },
        { group: 'Trainer', items: [['trainer', 'Master Trainer']] },
        { group: 'Dokumen', items: [['ketQtn', 'Template Keterangan Quotation'], ['kfTpl', 'Template Formulir Konfirmasi']] },
        { group: 'Biaya', items: [['rate', 'Master Rate']] },
        { group: 'Pengguna', items: [['user', 'Master User']] }
      ]
    },
    {
      // Master Program & Penjualan Program digabung satu modul (input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/).
      name: 'Penjualan Program', icon: ICON_SALE, groups: [
        { group: 'Master Program', items: [['mprog', 'Daftar Master Program']] },
        { group: 'Penjualan Program', items: [['quot', 'Daftar Penjualan Program']] }
      ]
    },
    {
      // Penjualan Program Final (Public & B2B) masuk ke Quotation (input/Master - Buat Program/2026-10-28-quotation-company-personal/).
      name: 'Quotation', icon: ICON_QTN, groups: [
        { group: 'Quotation', items: [['qtn', 'Daftar Quotation']] }
      ]
    },
    {
      // Pergerakan semua data di sistem (input/Master - Buat Program/2026-10-30-histori-activity-log/).
      name: 'Activity Log', icon: ICON_LOG, groups: [
        { group: 'Activity Log', items: [['actlog', 'Activity Log']] }
      ]
    }
  ];
  const countOf = v => MASTERS[v] ? db[v].length
    : v === 'paket' ? db.paket.length
      : v === 'peserta' ? db.peserta.length
        : v === 'company' ? db.company.length
        : v === 'trainer' ? db.trainer.length
        : v === 'ketQtn' ? db.ketQtn.length
        : v === 'kfTpl' ? db.kfTpl.length
        : v === 'user' ? db.user.length
          : v === 'rate' ? db.rate.fixed.length + db.rate.variable.length
            : v === 'mprog' ? db.mprog.length
              : v === 'quot' ? db.quot.length
                : v === 'qtn' ? db.qtn.length : v === 'actlog' ? db.logs.length : null;
  const SUBVIEW = { combine: ['paket', 'Tambah Product'], mpForm: ['mprog', null], qtForm: ['quot', null] };

  function go(view) {
    state.view = view; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.page = 1; state.qtnPj = null;
    state.navQ = ''; $('#nav-q').value = '';
    if (view === 'actlog') state.logF = { modul: 'all', aksi: 'all', user: 'all', from: '', to: '', refId: null, ref: '' };
    document.body.classList.remove('nav-open');
    render();
    window.scrollTo(0, 0);
  }

  function renderNav() {
    const navView = SUBVIEW[state.view] ? SUBVIEW[state.view][0] : state.view;
    // Search menu: cocokkan nama menu di semua modul; saat mencari, modul selalu terbuka.
    // Label sub modul tidak ditampilkan di sidebar (input/Master - Buat Program/2026-10-28-sidebar-tanpa-label/); grup hanya pengelompokan urutan menu.
    const kw = state.navQ.trim().toLowerCase();
    const mark = s => {
      const i = kw ? s.toLowerCase().indexOf(kw) : -1;
      return i < 0 ? esc(s) : `${esc(s.slice(0, i))}<mark>${esc(s.slice(i, i + kw.length))}</mark>${esc(s.slice(i + kw.length))}`;
    };
    const HOME = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></svg>';
    const home = !kw || 'dashboard'.includes(kw)
      ? `<button class="nav-module-head nav-home ${navView === 'dashboard' ? 'active' : ''}" data-view="dashboard">${HOME}<span>${mark('Dashboard')}</span></button>` : '';
    const html = home + MODULES.map(m => {
      const groups = m.groups.map(g => {
        const items = !kw ? g.items : g.items.filter(([, label]) => label.toLowerCase().includes(kw));
        return items.length ? `<div class="nav-group">${items.map(([v, label]) => {
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
    }).join('');
    $('#nav').innerHTML = html || '<div class="nav-empty">Menu tidak ditemukan.</div>';
    if (state.view === 'dashboard') { $('#crumb').innerHTML = '<b>Dashboard</b>'; return; }

    let mod, label;
    MODULES.forEach(m => m.groups.forEach(g => g.items.forEach(([v, l]) => { if (v === navView) { mod = m; label = l; } })));
    let leaf = label;
    if (state.view === 'combine') leaf = 'Tambah Product';
    if (state.view === 'mpForm' || state.view === 'qtForm') leaf = state.pf.isNew ? (state.view === 'mpForm' ? 'Buat Master Program' : 'Buat Penjualan Program') : state.pf.p.nomor;
    $('#crumb').innerHTML = `${mod.name} / ${state.view in SUBVIEW ? `${label} / ` : ''}<b>${esc(leaf)}</b>`;
  }

  // ---------- Shared view pieces ----------
  const pageHead = (title, desc, ref, actions = '') =>
    `<div class="page-head"><div><h1>${title}<span class="ref">${ref}</span></h1><p>${desc}</p></div>${actions ? `<div class="page-actions">${actions}</div>` : ''}</div>`;

  const badge = aktif => `<span class="badge ${aktif ? 'on' : 'off'}">${aktif ? 'Aktif' : 'Nonaktif'}</span>`;
  const stBadge = s => `<span class="badge st-${s}">${STATUS[s]}</span>`;
  const jenisTag = j => `<span class="tag tag-${j}">${JENIS[j]}</span>`;

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
    // Kolom jejak Dibuat / Diubah otomatis sebelum kolom Action (LOG-FR-02).
    if (rows.some(r => r && r.createdAt)) { const ai = cols.findIndex(c => (c.cls || '').split(' ').includes('act')); cols = ai < 0 ? cols.concat(HIST_COL) : [...cols.slice(0, ai), HIST_COL, ...cols.slice(ai)]; }
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
    <template>${items.concat(r.createdAt ? [['hist-open', 'Lihat Riwayat']] : []).map(([act, label, danger]) => `<button class="menu-item ${danger ? 'danger' : ''}" role="menuitem" data-act="${act}" data-id="${r.id}">${label}</button>`).join('')}</template>`;
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
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['paket-edit', 'Edit'], toggleItem(r)]) }
    ]);
  }

  const emptyRow = () => ({ produk: '', bidang: '', sertifikasi: '' });

  // Dropdown atribut dengan kolom cari (FR-12, NFR-10). Nilai nonaktif yang sudah terpilih tetap ditampilkan (edit paket).
  const attrOptions = (a, value) => db[a.key].filter(r => r.aktif || r.id === value).map(r => [r.id, r.nama + (r.aktif ? '' : ' (nonaktif)')]);
  const attrSelect = (a, value, i) => searchSelect({ label: a.label, value, options: attrOptions(a, value),
    attrs: `data-row="${i}" data-field="${a.field}" aria-label="${a.label} baris ${i + 1}"` });

  // Dropdown dengan kolom cari: <select> asli disembunyikan dan tetap menjadi sumber nilai (event change ikut terpicu),
  // tombol membuka panel berisi input cari + daftar pilihan. Panel memakai position fixed agar tidak terpotong tabel.
  function searchSelect({ label, value, options, name = '', attrs = '' }) {
    const sel = options.find(([v]) => v === value);
    return `<div class="ss"><select class="ss-native" ${name ? `name="${name}"` : ''} ${attrs} tabindex="-1" aria-hidden="true">
        <option value="">— Pilih ${esc(label)} —</option>${options.map(([v, t]) => `<option value="${v}" ${v === value ? 'selected' : ''}>${esc(t)}</option>`).join('')}</select>
      <button type="button" class="input ss-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Pilih ${esc(label)}">${sel ? esc(sel[1]) : `<span class="muted">— Pilih ${esc(label)} —</span>`}</button>
      <div class="ss-pop" hidden><input class="input sm ss-q" type="search" placeholder="Cari ${esc(label.toLowerCase())}…" aria-label="Cari ${esc(label)}" autocomplete="off">
        <ul class="ss-list" role="listbox">${options.map(([v, t]) => `<li role="option" data-v="${v}" class="${v === value ? 'on' : ''}">${esc(t)}</li>`).join('')}
          <li class="ss-none" hidden>Tidak ada yang cocok</li></ul></div></div>`;
  }
  const ssClose = () => document.querySelectorAll('.ss-pop:not([hidden])').forEach(p => {
    p.hidden = true; p.parentElement.querySelector('.ss-btn').setAttribute('aria-expanded', 'false');
  });
  function ssOpen(box) {
    ssClose();
    const btn = box.querySelector('.ss-btn'), pop = box.querySelector('.ss-pop'), r = btn.getBoundingClientRect();
    Object.assign(pop.style, { left: `${r.left}px`, top: `${r.bottom + 4}px`, minWidth: `${Math.max(r.width, 220)}px` });
    pop.hidden = false; btn.setAttribute('aria-expanded', 'true');
    const qi = pop.querySelector('.ss-q'); qi.value = ''; ssFilter(pop, '');
    qi.focus();
  }
  function ssFilter(pop, kw) {
    let n = 0;
    pop.querySelectorAll('li[data-v]').forEach(li => { const show = li.textContent.toLowerCase().includes(kw.toLowerCase()); li.hidden = !show; li.classList.remove('hl'); if (show) n++; });
    pop.querySelector('.ss-none').hidden = n > 0;
    const first = pop.querySelector('li[data-v]:not([hidden])'); if (first) first.classList.add('hl');
  }
  function ssPick(li) {
    const box = li.closest('.ss'), s = box.querySelector('.ss-native');
    s.value = li.dataset.v;
    box.querySelectorAll('li.on').forEach(x => x.classList.remove('on')); li.classList.add('on');
    box.querySelector('.ss-btn').textContent = li.textContent;
    box.classList.remove('has-err');
    ssClose();
    box.querySelector('.ss-btn').focus();
    s.dispatchEvent(new Event('change', { bubbles: true }));
  }
  document.addEventListener('click', e => {
    const btn = e.target.closest('.ss-btn'), li = e.target.closest('.ss-list li[data-v]');
    if (btn) { const pop = btn.parentElement.querySelector('.ss-pop'); pop.hidden ? ssOpen(btn.parentElement) : ssClose(); return; }
    if (li) return ssPick(li);
    if (!e.target.closest('.ss-pop')) ssClose();
  }, true);
  document.addEventListener('input', e => { if (e.target.classList.contains('ss-q')) ssFilter(e.target.closest('.ss-pop'), e.target.value); });
  document.addEventListener('keydown', e => {
    const pop = e.target.closest && e.target.closest('.ss-pop');
    if (!pop) return;
    const vis = [...pop.querySelectorAll('li[data-v]:not([hidden])')], i = vis.findIndex(x => x.classList.contains('hl'));
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      const n = vis[Math.min(vis.length - 1, Math.max(0, i + (e.key === 'ArrowDown' ? 1 : -1)))];
      vis.forEach(x => x.classList.remove('hl')); if (n) { n.classList.add('hl'); n.scrollIntoView({ block: 'nearest' }); }
    } else if (e.key === 'Enter') { e.preventDefault(); if (vis[i]) ssPick(vis[i]); }
    else if (e.key === 'Escape') { e.stopPropagation(); ssClose(); pop.parentElement.querySelector('.ss-btn').focus(); }
  }, true);
  window.addEventListener('resize', ssClose);
  window.addEventListener('scroll', e => { if (!(e.target.closest && e.target.closest('.ss-pop'))) ssClose(); }, true);

  // Edit Paket Produk (FR-43; input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/): judul & atribut, cek keunikan tanpa paket itu sendiri.
  function paketForm(rec) {
    const used = db.mprog.filter(m => m.paket === rec.id).length;
    openModal({
      title: `Edit Paket Produk ${rec.nomor}`, wide: true,
      body: `<div class="grid2">
        ${field('Nomor Product Combine' + Q01, `<input class="input" value="${esc(rec.nomor)}" readonly>`)}
        ${field(`Judul${req}`, `<input class="input" name="judul" maxlength="150" value="${esc(rec.judul)}">`)}
        ${ATTRS.map(a => field(a.label + req, searchSelect({ label: a.label, value: rec[a.field], options: attrOptions(a, rec[a.field]), name: a.field }))).join('')}
        </div>
        <div id="pk-err"></div>
        ${used ? `<p class="hint">Paket ini dipakai ${used} Master Program — perubahan judul/atribut ikut tampil di sana (usulan).</p>` : ''}`,
      onSave: f => {
        const v = { judul: f.judul.value.trim() };
        ATTRS.forEach(a => { v[a.field] = Number(f[a.field].value) || ''; });
        let ok = true;
        if (!v.judul) ok = invalid(f.judul, 'Judul wajib diisi');
        ATTRS.forEach(a => { if (!v[a.field]) ok = invalid(f[a.field], `${a.label} wajib dipilih`); });
        if (!ok) return false;
        const k = comboKey(v), dup = db.paket.find(p => p !== rec && comboKey(p) === k);
        if (dup) { $('#pk-err').innerHTML = `<div class="err"><i>!</i>Kombinasi sama persis dengan paket ${esc(dup.nomor)} — ${esc(dup.judul)}</div>`; return false; }
        Object.assign(rec, v);
        toast(`Paket ${rec.nomor} diperbarui`);
      }
    });
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
  const npwpOk = v => /^(\d{15}|\d{16})$/.test(v.replace(/[.\-\s]/g, ''));
  // NPWP Instansi peserta: bila terdaftar di Company, ikut NPWP company (input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/, usulan).
  const pesertaNpwp = p => { const c = p && p.company && db.company.find(x => x.id === p.company); return c ? c.npwp || '' : (p && p.npwp) || ''; };
  const npwpInput = (name, value, lock) => `<input class="input" name="${name}" inputmode="numeric" value="${esc(value)}" placeholder="15 atau 16 digit" ${lock ? 'readonly' : ''}><span class="hint">${lock ? 'Mengikuti NPWP Instansi company' : 'Terisi NPWP company bila Company dipilih'}</span>`;

  // ---------- Master Peserta (FR-15 – FR-17) ----------
  const Q16 = q('Q-16', 'Apakah Peserta dan Company juga hanya boleh dinonaktifkan? Mockup mengikuti mockup klien: Edit/Delete.');

  const viewPeserta = () => `${pageHead('Master Peserta', 'Data peserta pelatihan/sertifikasi, dikelompokkan menurut Jenis Bidang. Peserta dapat terdaftar di satu Company; di Quotation ke Company hanya peserta company itu yang dapat dipilih.', 'FR-15 · FR-16 · FR-17',
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
    const r = rec || { nama: '', gender: '', company: '', instansi: '', npwp: '', bidang: '', provinsi: '', kota: '', telp: '', email: '' };
    const coOpts = db.company.filter(c => c.aktif || c.id === r.company)
      .map(c => `<option value="${c.id}" ${c.id === r.company ? 'selected' : ''}>${esc(c.nama)}${c.aktif ? '' : ' (nonaktif)'}</option>`).join('');
    const bidangOpts = db.jenisBidang.filter(b => b.aktif || b.id === r.bidang)
      .map(b => `<option value="${b.id}" ${b.id === r.bidang ? 'selected' : ''}>${esc(b.nama)}${b.aktif ? '' : ' (nonaktif)'}</option>`).join('');

    openModal({
      title: rec ? `Edit Peserta ${rec.kode}` : 'Tambah Master Peserta',
      wide: true,
      body: `<p class="hint top">Semua field wajib diisi, kecuali Company dan NPWP Instansi.</p><div class="grid2">
        ${field('Kode Peserta' + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode('PS'))}" readonly>`, '', 'span2')}
        ${field('Nama Peserta' + req, `<input class="input" name="nama" value="${esc(r.nama)}" maxlength="100">`)}
        ${field('Gender' + req, `<select class="input" name="gender"><option value="">— Pilih —</option>${opts(['Laki-laki', 'Perempuan'], r.gender)}</select>`)}
        ${field('Company', `<select class="input" name="company"><option value="">— Tanpa company (personal) —</option>${coOpts}</select><span class="hint">Dari Master Company; dipakai Quotation ke Company</span>`)}
        ${field('Instansi/Perusahaan' + req, `<input class="input" name="instansi" value="${esc(r.instansi)}" maxlength="150" ${r.company ? 'readonly' : ''}><span class="hint">Terisi nama Company bila Company dipilih</span>`)}
        ${field('NPWP Instansi <span class="muted">(opsional)</span>', npwpInput('npwp', pesertaNpwp(r), !!r.company), '', 'span2')}
        ${field('Bidang' + req, `<select class="input" name="bidang"><option value="">— Pilih Jenis Bidang —</option>${bidangOpts}</select>`)}
        ${field('Lokasi (Provinsi)' + req + q('Q-07b', 'Sumber data provinsi & kota belum ditetapkan. Data di mockup hanya contoh.'), `<select class="input" name="provinsi"><option value="">— Pilih Provinsi —</option>${provOptions(r.provinsi)}</select>`)}
        ${field('Kota/Kabupaten' + req, `<select class="input" name="kota" ${r.provinsi ? '' : 'disabled'}>${kotaOptions(r.provinsi, r.kota)}</select><span class="hint">Tersaring menurut provinsi</span>`)}
        ${field('No Telp/WhatsApp' + req, `<input class="input" name="telp" inputmode="tel" value="${esc(r.telp)}" placeholder="08xxxxxxxxxx">`)}
        ${field('Email' + req, `<input class="input" name="email" type="email" value="${esc(r.email)}" placeholder="nama@domain.com">`)}
      </div>`,
      onMount: form => {
        bindKota(form);
        form.elements.company.addEventListener('change', e => {
          const c = db.company.find(x => x.id === +e.target.value), ins = form.elements.instansi, np = form.elements.npwp;
          ins.readOnly = !!c; if (c) ins.value = c.nama;
          np.readOnly = !!c; if (c) np.value = c.npwp || '';
        });
      },
      onSave: f => {
        const v = {};
        ['nama', 'gender', 'instansi', 'npwp', 'bidang', 'provinsi', 'kota', 'telp', 'email'].forEach(k => { v[k] = f[k].value.trim(); });
        let ok = true;
        const labels = { nama: 'Nama Peserta', gender: 'Gender', instansi: 'Instansi/Perusahaan', bidang: 'Bidang', provinsi: 'Provinsi', kota: 'Kota/Kabupaten', telp: 'No Telp/WhatsApp', email: 'Email' };
        Object.keys(labels).forEach(k => { if (!v[k]) ok = invalid(f[k], `${labels[k]} wajib diisi`); });
        // NFR-06 (usulan): format email & nomor telepon.
        if (v.email && !EMAIL_RE.test(v.email)) ok = invalid(f.email, 'Format email tidak valid');
        if (v.telp && !telpOk(v.telp)) ok = invalid(f.telp, 'Nomor hanya angka, 9–15 digit');
        if (v.npwp && !npwpOk(v.npwp)) ok = invalid(f.npwp, 'NPWP Instansi 15 atau 16 digit angka');
        if (!ok) return false;
        v.bidang = Number(v.bidang);
        v.company = Number(f.company.value) || '';
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
  const viewCompany = () => `${pageHead('Master Company', 'Data perusahaan/instansi yang menjadi customer penjualan B2B. Company aktif dipilih di Penjualan Program B2B (wajib) dan sebagai tujuan Quotation ke Company.', 'FR-30 · FR-31 · FR-32',
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
      STATUS_COL,
      { label: 'Action' + Q16, cls: 'act', render: r => gearMenu(r, [['company-edit', 'Edit'], toggleItem(r), ['company-del', 'Delete', true]]) }
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
        ${field('NPWP Instansi <span class="muted">(opsional)</span>', inp('npwp', r.npwp, 'inputmode="numeric" placeholder="15 atau 16 digit"'), '', 'span2')}
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
        if (v.npwp && !npwpOk(v.npwp)) ok = invalid(f.npwp, 'NPWP Instansi 15 atau 16 digit angka');
        if (!ok) return false;
        if (rec) {
          Object.assign(rec, v);
          toast(`Company ${rec.kode} diperbarui`);
        } else {
          const c = Object.assign({ id: uid(), kode: nextCode('CO'), aktif: true }, v);
          db.company.push(c);
          state.page = Math.ceil(db.company.length / PAGE_SIZE);
          toast(`Company ${c.kode} ditambahkan`);
        }
      }
    });
  }

  // ---------- Master User (FR-36 – FR-39; format form: input/Master - Buat Program/2026-10-28-master-user/) ----------
  // Data karyawan (tabel existing `employees`) + akun login (tabel existing `users`, group akses `groups`).
  const DEPARTMENT = {
    'Manajemen': ['Direksi'],
    'Marketing & Sales': ['Sales Public', 'Sales B2B', 'Marketing'],
    'Operasional Training': ['Penyelenggaraan', 'Trainer'],
    'Keuangan & Admin': ['Keuangan', 'Administrasi']
  };
  const JABATAN = ['Direktur', 'Manager', 'Supervisor', 'Staff', 'Admin'];
  const GENDER = ['Laki-laki', 'Perempuan'];
  const RELIGION = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
  const EMP_TYPE = ['Tetap', 'Kontrak', 'Magang', 'Freelance'];
  const GROUP_AKSES = ['Manajemen', 'Admin', 'Staff'];
  const QUSR = q('Q-25', 'Daftar Group Akses, Department, Division, Jabatan, dan Employee Type di mockup hanya contoh — isi final mengikuti data aplikasi/Labmania.');
  db.user = [
    { kode: 'EMP-001', nama: 'Contoh User Satu', nik: '3275010101800001', tmpLahir: 'Bekasi', tglLahir: '1980-01-01', dept: 'Manajemen', div: 'Direksi', jabatan: 'Direktur', gender: 'Laki-laki', agama: 'Islam',
      telp: '+6281211110000', email: 'user1@contoh.id', alamat: 'Jl. Contoh No. 1, Bekasi', npwp: '', tglMasuk: '2020-01-02', tipe: 'Tetap', jobdesc: 'Menyetujui Master & Penjualan Program', aktif: true,
      akun: true, username: 'user.satu', group: 'Manajemen' },
    { kode: 'EMP-002', nama: 'Contoh User Dua', nik: '', tmpLahir: '', tglLahir: '', dept: 'Marketing & Sales', div: 'Sales B2B', jabatan: 'Staff', gender: 'Perempuan', agama: '',
      telp: '+6281322220000', email: 'user2@contoh.id', alamat: '', npwp: '', tglMasuk: '2024-03-01', tipe: 'Kontrak', jobdesc: '', aktif: true,
      akun: true, username: 'user.dua', group: 'Staff' },
    { kode: 'EMP-003', nama: 'Contoh Trainer Tiga', nik: '', tmpLahir: '', tglLahir: '', dept: 'Operasional Training', div: 'Trainer', jabatan: 'Staff', gender: 'Laki-laki', agama: '',
      telp: '+6285733330000', email: '', alamat: '', npwp: '', tglMasuk: '', tipe: 'Freelance', jobdesc: 'Trainer lepas', aktif: false,
      akun: false, username: '', group: '' }
  ].map(u => Object.assign({ id: uid() }, u));

  const viewUser = () => `${pageHead('Master User', 'Data karyawan Labmania dan akun login ke sistem. User nonaktif tidak dapat login; data tidak dihapus.', 'FR-36 – FR-39',
    '<button class="btn btn-accent" data-act="user-add">+ Tambah User</button>')}
    <div class="card">${toolbar('Cari ID, nama, username, atau email…', true,
      selectFilter('fjenis', 'Filter department', state.jenis, [['all', 'Semua department'], ...Object.keys(DEPARTMENT).map(d => [d, d])]))}
      <div id="list">${listUser()}</div></div>`;

  function listUser() {
    const rows = db.user.filter(r => statusOk(r) && (state.jenis === 'all' || r.dept === state.jenis) &&
      matches([r.kode, r.nama, r.username, r.email]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Employee ID', cls: 'nowrap', render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama', render: r => `${esc(r.nama)}<br><span class="muted">${esc(r.email || r.telp)}</span>` },
      { label: 'Department / Division', render: r => `${esc(r.dept)}<br><span class="muted">${esc(r.div)}</span>` },
      { label: 'Jabatan', render: r => `${esc(r.jabatan)}<br><span class="muted">${esc(r.tipe)}</span>` },
      { label: 'Akun login', render: r => r.akun ? `<span class="code">${esc(r.username)}</span><br><span class="muted">${esc(r.group)}</span>` : '<span class="muted">Tanpa akun</span>' },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['user-edit', 'Edit'], toggleItem(r)].concat(r.akun ? [['user-reset', 'Reset Password']] : [])) }
    ]);
  }

  const divOptions = (dept, sel) => dept
    ? `<option value="">Choose one</option>${opts(DEPARTMENT[dept] || [], sel)}`
    : '<option value="">Choose one</option>';

  function userForm(rec) {
    const r = rec || { kode: '', nama: '', nik: '', tmpLahir: '', tglLahir: '', dept: '', div: '', jabatan: '', gender: '', agama: '', telp: '', email: '', alamat: '', npwp: '',
      tglMasuk: '', tipe: '', jobdesc: '', aktif: true, akun: true, username: '', group: '' };
    const inp = (name, val, extra = '') => `<input class="input" name="${name}" value="${esc(val)}" ${extra}>`;
    const sel = (name, list, val) => `<select class="input" name="${name}"><option value="">Choose one</option>${opts(list, val)}</select>`;
    const sec = t => `<div class="form-sec span2">${t}</div>`;
    openModal({
      title: rec ? `Edit User ${rec.kode}` : 'Add New User',
      wide: true,
      body: `<div class="grid2">
        <div>
          ${field('Employee ID' + req, inp('kode', r.kode, `placeholder="Employee ID" maxlength="20" ${rec ? 'readonly' : ''}`) + (rec ? '<span class="hint">Tidak dapat diubah</span>' : ''))}
          ${field('Employee Name' + req, inp('nama', r.nama, 'placeholder="Employee Name" maxlength="100"'))}
          ${field('Personal ID Number', inp('nik', r.nik, 'placeholder="NIK" inputmode="numeric" maxlength="16"'))}
          ${field('Birth Place', inp('tmpLahir', r.tmpLahir, 'placeholder="Birth Place" maxlength="50"'))}
          ${field('Birth Date', inp('tglLahir', r.tglLahir, 'type="date"'))}
          ${field('Department' + req + QUSR, `<select class="input" name="dept"><option value="">Choose one</option>${opts(Object.keys(DEPARTMENT), r.dept)}</select>`)}
          ${field('Division' + req, `<select class="input" name="div" ${r.dept ? '' : 'disabled'}>${divOptions(r.dept, r.div)}</select><span class="hint ${r.dept ? 'hidden' : ''}" data-divhint>Pilih Department terlebih dahulu.</span>`)}
          ${field('Jabatan' + req, sel('jabatan', JABATAN, r.jabatan))}
          ${field('Gender' + req, sel('gender', GENDER, r.gender))}
          ${field('Religion', sel('agama', RELIGION, r.agama))}
        </div>
        <div>
          ${field('Phone Number' + req, inp('telp', r.telp, 'placeholder="+62..." inputmode="tel"'))}
          ${field('Email', inp('email', r.email, 'type="email" placeholder="email@domain.com"'))}
          ${field('Address', `<textarea class="input" name="alamat" rows="2" placeholder="Address" maxlength="255">${esc(r.alamat)}</textarea>`)}
          ${field('NPWP', inp('npwp', r.npwp, 'placeholder="NPWP Number" inputmode="numeric"'))}
          ${field('Join Date', inp('tglMasuk', r.tglMasuk, 'type="date"'))}
          ${field('Employee Type' + req, sel('tipe', EMP_TYPE, r.tipe))}
          ${field('Job Desc.', `<textarea class="input" name="jobdesc" rows="2" placeholder="Job Description" maxlength="500">${esc(r.jobdesc)}</textarea>`)}
          ${field('Status' + req, `<div class="static"><label><input type="radio" name="aktif" value="1" ${r.aktif ? 'checked' : ''}> Active</label>
            &nbsp; <label><input type="radio" name="aktif" value="0" ${r.aktif ? '' : 'checked'}> Non Active</label></div>`)}
        </div>
        ${sec('Akun Login <span class="muted">— tambahan di luar form acuan</span>')}
        ${field('', `<label><input type="checkbox" name="akun" ${r.akun ? 'checked' : ''}> User ini dapat login ke sistem</label>`, '', 'span2')}
        <div class="grid2 span2 ${r.akun ? '' : 'hidden'}" data-akun>
          ${field('Username' + req, inp('username', r.username, 'autocomplete="off" maxlength="50" placeholder="huruf kecil, angka, titik"'))}
          ${field('Group Akses' + req + QUSR, sel('group', GROUP_AKSES, r.group))}
          ${field('Password' + (rec && rec.akun ? '' : req), `<input class="input" name="password" type="password" autocomplete="new-password">${rec && rec.akun ? '<span class="hint">Kosongkan bila tidak diubah</span>' : '<span class="hint">Minimal 8 karakter</span>'}`)}
          ${field('Konfirmasi Password' + (rec && rec.akun ? '' : req), '<input class="input" name="password2" type="password" autocomplete="new-password">')}
        </div>
      </div>`,
      onMount: form => {
        const f = form.elements;
        f.dept.addEventListener('change', e => {
          f.div.innerHTML = divOptions(e.target.value, '');
          f.div.disabled = !e.target.value;
          form.querySelector('[data-divhint]').classList.toggle('hidden', !!e.target.value);
        });
        f.akun.addEventListener('change', e => { form.querySelector('[data-akun]').classList.toggle('hidden', !e.target.checked); });
      },
      onSave: f => {
        const keys = ['kode', 'nama', 'nik', 'tmpLahir', 'tglLahir', 'dept', 'div', 'jabatan', 'gender', 'agama', 'telp', 'email', 'alamat', 'npwp', 'tglMasuk', 'tipe', 'jobdesc', 'username', 'group'];
        const v = {};
        keys.forEach(k => { v[k] = f[k].value.trim(); });
        v.aktif = f.aktif.value === '1';
        v.akun = f.akun.checked;
        const pw = f.password.value, pw2 = f.password2.value;
        let ok = true;
        const LBL = { kode: 'Employee ID', nama: 'Employee Name', dept: 'Department', div: 'Division', jabatan: 'Jabatan', gender: 'Gender', telp: 'Phone Number', tipe: 'Employee Type' };
        Object.keys(LBL).forEach(k => { if (!v[k]) ok = invalid(f[k], `${LBL[k]} wajib diisi`); });
        if (!rec && v.kode && db.user.some(u => u.kode.toLowerCase() === v.kode.toLowerCase())) ok = invalid(f.kode, 'Employee ID sudah dipakai');
        if (v.nik && !/^\d{16}$/.test(v.nik)) ok = invalid(f.nik, 'NIK 16 digit angka');
        if (v.telp && !telpOk(v.telp)) ok = invalid(f.telp, 'Nomor hanya angka (boleh diawali +), 9–15 digit');
        if (v.email && !EMAIL_RE.test(v.email)) ok = invalid(f.email, 'Format email tidak valid');
        if (v.npwp && !/^(\d{15}|\d{16})$/.test(v.npwp.replace(/[.\-\s]/g, ''))) ok = invalid(f.npwp, 'NPWP 15 atau 16 digit angka');
        if (v.tglLahir && v.tglMasuk && v.tglMasuk < v.tglLahir) ok = invalid(f.tglMasuk, 'Join Date sebelum Birth Date');
        if (v.akun) {
          const needPw = !(rec && rec.akun);
          if (!v.username) ok = invalid(f.username, 'Username wajib diisi');
          else if (!/^[a-z0-9._]{3,50}$/.test(v.username)) ok = invalid(f.username, 'Huruf kecil, angka, titik, atau garis bawah; 3–50 karakter');
          else if (db.user.some(u => u !== rec && u.akun && u.username === v.username)) ok = invalid(f.username, 'Username sudah dipakai');
          if (!v.group) ok = invalid(f.group, 'Group Akses wajib diisi');
          if ((needPw || pw) && pw.length < 8) ok = invalid(f.password, 'Password minimal 8 karakter');
          if ((needPw || pw) && pw !== pw2) ok = invalid(f.password2, 'Konfirmasi password tidak sama');
        } else { v.username = ''; v.group = ''; }
        if (!ok) return false;
        if (rec) {
          Object.assign(rec, v);
          toast(`User ${rec.kode} diperbarui${pw ? ' (password diganti)' : ''}`);
        } else {
          const u = Object.assign({ id: uid() }, v);
          db.user.push(u);
          state.page = Math.ceil(db.user.length / PAGE_SIZE);
          toast(`User ${u.kode} ditambahkan`);
        }
      }
    });
  }

  function userReset(rec) {
    openModal({
      title: 'Reset Password',
      body: `<p style="margin:0 0 14px">Atur password baru untuk <b>${esc(rec.kode)} — ${esc(rec.nama)}</b> (username <span class="code">${esc(rec.username)}</span>).</p>
        ${field('Password baru' + req, '<input class="input" name="password" type="password" autocomplete="new-password"><span class="hint">Minimal 8 karakter</span>')}
        ${field('Konfirmasi Password' + req, '<input class="input" name="password2" type="password" autocomplete="new-password">')}`,
      saveLabel: 'Simpan Password',
      onSave: f => {
        let ok = true;
        if (f.password.value.length < 8) ok = invalid(f.password, 'Password minimal 8 karakter');
        if (f.password.value !== f.password2.value) ok = invalid(f.password2, 'Konfirmasi password tidak sama');
        if (!ok) return false;
        toast(`Password ${rec.username} diganti`);
      }
    });
  }

  // ---------- Master Trainer (FR-44 – FR-47; input/Master - Buat Program/2026-10-29-master-trainer/, format input/Master Trainer Ivan Sarifudin.xlsx) ----------
  // Identitas + empat daftar riwayat, seperti sheet Excel. Dipilih (satu trainer) di Penjualan Program.
  const TR_LISTS = {
    pelatihan: { title: 'Pelatihan & Seminar', cols: [['judul', 'Judul Pelatihan/Seminar/Lainnya', 'wide'], ['penyelenggara', 'Penyelenggara/Tempat'], ['waktu', 'Waktu', 'yr']] },
    kerja: { title: 'Pengalaman Kerja', cols: [['jabatan', 'Jabatan', 'wide'], ['institusi', 'Institusi/Perusahaan'], ['tahun', 'Tahun', 'yr']] },
    mengajar: { title: 'Pengalaman Mengajar', cols: [['topik', 'Topik Pembelajaran', 'wide'], ['tempat', 'Tempat'], ['tahun', 'Tahun', 'yr']] },
    lampiran: { title: 'Lampiran Dokumen', cols: [['dokumen', 'Dokumen / Sertifikat Lampiran', 'wide'], ['tahun', 'Tahun', 'yr'], ['file', 'File', 'file']] }
  };
  const PENDIDIKAN = [['pendSmu', 'SMU/sederajat'], ['pendDiploma', 'Diploma'], ['pendSarjana', 'Sarjana'], ['pendPasca', 'Pascasarjana']];
  const QTR = q('Q-26', 'Field wajib Master Trainer selain Nama lengkap? Siapa yang mengelola Master Trainer?');
  const trainerById = id => db.trainer.find(t => t.id === id);
  const trainerDipakai = t => db.quot.filter(x => x.trainer === t.id);
  // Penjualan Program: satu trainer per event (input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/ butir 1); opsional (usulan, Q-PJ-20).
  function trainerField(p, locked) {
    const t = trainerById(p.trainer);
    const hint = t ? `<span class="hint">${esc(t.keahlian.slice(0, 2).join(' · ') || 'Master Trainer')}${t.aktif ? '' : ' · nonaktif'}</span>` : '<span class="hint">Dari Master Trainer aktif; satu trainer per event</span>';
    const label = 'Trainer' + q('Q-PJ-20', 'Trainer wajib dipilih (mis. sebelum Final) atau boleh kosong? Honor Trainer berbeda per trainer? Usulan: opsional; honor tetap dari Master Rate.');
    if (locked) return field(label, `<div class="static">${t ? esc(t.nama) : '<span class="muted">—</span>'}</div>`);
    return field(label, searchSelect({ label: 'Trainer', value: p.trainer, attrs: 'data-pf="trainer"',
      options: db.trainer.filter(x => x.aktif || x.id === p.trainer).map(x => [x.id, `${x.nama}${x.aktif ? '' : ' (nonaktif)'}`]) }) + hint);
  }
  {
    // Data contoh dari Excel (sebagian baris riwayat); alamat, telepon, tanggal lahir diganti contoh.
    const rows = (keys, list) => list.map(v => Object.fromEntries(keys.map((k, i) => [k, v[i] || ''])));
    db.trainer = [{
      nama: 'Ivan Sarifudin, S.T.', tmpLahir: 'Jakarta', tglLahir: '1974-01-01', alamat: 'Jl. Contoh No. 5, Bekasi', telp: '081200000001',
      pendSmu: 'Sekolah Menengah Analis Kesehatan DITKESAD, lulus 1992', pendDiploma: 'Analis Kimia, STMIPA Bogor, lulus 1997', pendSarjana: '', pendPasca: '',
      keahlian: ['Pemahaman ISO/IEC 17025:2017', 'Penyusunan Dokumen ISO/IEC 17025:2017', 'Audit Internal ISO/IEC 17025:2017'],
      lainnya: 'Asesor BNSP', cvTempat: 'Bekasi', cvTanggal: '2025-08-25', ttdNama: 'Ivan Sarifudin',
      pelatihan: rows(['judul', 'penyelenggara', 'waktu'], [
        ['Workshop Pengukuran dan Kalibrasi Anak Timbangan dan Timbangan Analitik', 'LabMania', '2025'],
        ['Teknik Penyusunan SOP dan IK pada Analisis Spektrofotometri UV-Vis', 'LabMania', '2025'],
        ['Estimasi Ketidakpastian Pengujian Kimia', 'LabMania', '2025'],
        ['Audit Internal SNI ISO/IEC 17025:2017 Berbasis ISO 19011:2018', 'LabMania', '2025'],
        ['Fast-Track Leadership Workshop', 'Java Paragon Hotel Surabaya', '2025'],
        ['Interpretasi Sertifikat Hasil Kalibrasi', 'LabMania', '2023'],
        ['Training for Trainers Sertifikasi BNSP', 'LabMania', '2019']]),
      kerja: rows(['jabatan', 'institusi', 'tahun'], [
        ['Direktur Utama dan Trainer', 'PT LabMania Indonesia Jaya', '2018–sekarang'],
        ['Direktur Marketing dan Trainer', 'PT Qualyst Performa Manajemen', '2017–2018'],
        ['Manager Marketing dan Trainer', 'Guide Consulting', '2015–2017'],
        ['Area Manager', 'PT Scharlab Indonesia', '2010–2011'],
        ['Sr Technical Sales Representative', 'PT Merck Tbk', '2006–2010'],
        ['Analis Laboratorium', 'PT Merck Tbk', '2002–2006']]),
      mengajar: rows(['topik', 'tempat', 'tahun'], [
        ['Manajemen Risiko K3 Laboratorium', 'LabMania', '2025'],
        ['Audit Internal dan Kaji Ulang Manajemen', 'UPTD Laboratorium Lingkungan Kabupaten Ciamis', '2025'],
        ['Supervisor Laboratorium', 'PT Sucofindo', '2025'],
        ['Laboran Kimia', 'Politeknik ATI Padang', '2025'],
        ['In House Training Good Laboratory Practices', 'Labiomed Puskesad', '2020'],
        ['Konsultan Implementasi SNI ISO/IEC 17025:2017', 'Universitas Hasanuddin', '2018–2019']]),
      lampiran: rows(['dokumen', 'tahun', 'file'], [
        ['Ijazah', '', ''],
        ['Workshop Pemahaman Sistem Manajemen Laboratorium Sesuai SNI ISO/IEC 17025:2017', '2024', 'sertifikat-17025-2024.pdf'],
        ['Training for Trainers Sertifikasi BNSP', '2019', 'tft-bnsp-2019.pdf']])
    }, {
      nama: 'Contoh Trainer Mikrobiologi', tmpLahir: 'Bogor', tglLahir: '1985-05-12', alamat: 'Jl. Contoh No. 9, Bogor', telp: '081200000002',
      pendSmu: '', pendDiploma: '', pendSarjana: 'Biologi, Universitas Contoh, lulus 2007', pendPasca: '',
      keahlian: ['Analisis Mikrobiologi', 'Teknik Sampling Air'], lainnya: '', cvTempat: 'Bogor', cvTanggal: '2025-07-01', ttdNama: 'Contoh Trainer Mikrobiologi',
      pelatihan: [], kerja: rows(['jabatan', 'institusi', 'tahun'], [['Analis Mikrobiologi', 'Laboratorium Contoh', '2008–sekarang']]),
      mengajar: rows(['topik', 'tempat', 'tahun'], [['Analis Mikrobiologi', 'LabMania', '2024']]), lampiran: []
    }].map(t => Object.assign({ id: uid(), kode: nextCode('TR'), aktif: true }, t));
    // Penjualan Program contoh memakai trainer (satu trainer per event).
    db.quot.forEach((x, i) => { x.trainer = i % 2 === 0 ? db.trainer[0].id : ''; x.waktu = x.waktu || '09.00–15.15 WIB'; });
  }

  const viewTrainer = () => `${pageHead('Master Trainer', 'Data trainer/fasilitator beserta riwayatnya (format CV trainer). Trainer aktif dipilih di Penjualan Program — satu trainer per event.', 'FR-44 – FR-47',
    '<button class="btn btn-accent" data-act="trainer-add">+ Tambah Trainer</button>')}
    <div class="notice"><span>Trainer <b>tidak dapat dihapus</b> — gunakan <b>Nonaktifkan</b>. Trainer nonaktif tidak muncul di pilihan Penjualan Program.</span><span class="ref">FR-46</span></div>
    <div class="card">${toolbar('Cari ID, nama, atau bidang keahlian…')}<div id="list">${listTrainer()}</div></div>`;

  function listTrainer() {
    const rows = db.trainer.filter(r => statusOk(r) && matches([r.kode, r.nama, r.telp, ...r.keahlian]));
    return listBlock(rows, [
      NO_COL,
      { label: 'ID Trainer' + Q01, render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama Trainer', render: r => `${esc(r.nama)}<br><span class="muted">${esc(r.telp || '—')}</span>` },
      { label: 'Bidang Keahlian', render: r => r.keahlian.slice(0, 2).map(k => `<span class="ps-chip">${esc(k)}</span>`).join(' ') + (r.keahlian.length > 2 ? ` <span class="muted">+${r.keahlian.length - 2}</span>` : '') },
      { label: 'Riwayat', render: r => `<span class="muted">${Object.keys(TR_LISTS).map(k => `${r[k].length} ${TR_LISTS[k].title.toLowerCase()}`).join('<br>')}</span>` },
      { label: 'Penjualan Program', cls: 'money', render: r => trainerDipakai(r).length },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['trainer-edit', 'Edit'], ['trainer-cv', 'Lihat CV'], toggleItem(r)]) }
    ]);
  }

  // Form bertab: Identitas + empat daftar riwayat (baris dapat ditambah/dihapus), seperti sheet Excel.
  function trainerBody(d) {
    const E = d.err, tab = d.tab;
    const tabs = [['identitas', 'Identitas Trainer'], ...Object.keys(TR_LISTS).map(k => [k, `${TR_LISTS[k].title} <span class="nav-count">${d[k].length}</span>`])]
      .map(([k, l]) => `<button type="button" class="tab ${tab === k ? 'active' : ''}" data-q="tab" data-tab="${k}">${l}</button>`).join('');
    let body;
    if (tab === 'identitas') {
      const inp = (k, ph = '', attr = '') => `<input class="input" name="${k}" value="${esc(d[k])}" placeholder="${ph}" ${attr}>`;
      body = `<div class="grid2">
        ${field('ID Trainer' + Q01, `<input class="input" value="${esc(d.kode || peekCode('TR'))}" readonly><span class="hint">Diisi otomatis oleh sistem saat disimpan</span>`)}
        ${field('Nama lengkap' + req + QTR, inp('nama', 'Nama dan gelar', 'maxlength="150"'), E.nama)}
        ${field('Tempat lahir', inp('tmpLahir', 'mis. Jakarta'))}
        ${field('Tanggal lahir', inp('tglLahir', '', 'type="date"'))}
        ${field('Alamat rumah', `<textarea class="input" name="alamat" rows="2">${esc(d.alamat)}</textarea>`, '', 'span2')}
        ${field('No. telepon rumah/HP', inp('telp', '08xxxxxxxxxx / 08xxxxxxxxxx'), E.telp, 'span2')}
      </div>
      <p class="qtn-h">Pendidikan formal</p>
      <div class="grid2">${PENDIDIKAN.map(([k, l]) => field(l, inp(k, 'Jurusan, institusi, tahun lulus'))).join('')}</div>
      <p class="qtn-h">Keahlian & pengesahan CV</p>
      <div class="grid2">
        ${field('Bidang keahlian', `<textarea class="input" name="keahlian" rows="4" placeholder="Satu bidang per baris">${esc(d.keahlian)}</textarea><span class="hint">Satu bidang per baris</span>`)}
        ${field('Pengalaman lainnya', `<textarea class="input" name="lainnya" rows="4" placeholder="mis. Asesor BNSP">${esc(d.lainnya)}</textarea>`)}
        ${field('Tempat pengesahan CV', inp('cvTempat', 'mis. Bekasi'))}
        ${field('Tanggal pengesahan CV', inp('cvTanggal', '', 'type="date"'))}
        ${field('Nama pada tanda tangan', inp('ttdNama', 'Nama tanpa gelar'), '', 'span2')}
      </div>`;
    } else {
      const L = TR_LISTS[tab], rows = d[tab];
      body = `<div class="table-wrap"><table class="table tr-table"><thead><tr><th class="num">No.</th>${L.cols.map(([, l, c]) => `<th class="${c || ''}">${l}</th>`).join('')}<th class="act"></th></tr></thead><tbody>
        ${rows.length ? rows.map((r, i) => `<tr class="${(E.rows || {})[`${tab}-${i}`] ? 'row-err' : ''}"><td class="num">${i + 1}</td>${L.cols.map(([k, l, c]) => `<td class="${c || ''}">${c === 'file'
          ? `${r.file ? `<span class="code">${esc(r.file)}</span> ` : ''}<label class="btn btn-ghost sm file-btn">${r.file ? 'Ganti' : 'Unggah'}<input type="file" accept=".pdf,.jpg,.jpeg,.png" data-tr="${tab}" data-i="${i}" data-k="file" hidden></label>`
          : `<input class="input sm" data-tr="${tab}" data-i="${i}" data-k="${k}" value="${esc(r[k])}" aria-label="${l} baris ${i + 1}" ${c === 'yr' ? 'placeholder="mis. 2025"' : ''}>`}</td>`).join('')}
          <td class="act"><button type="button" class="btn btn-ghost sm" data-q="row-del" data-i="${i}" aria-label="Hapus baris">Hapus</button></td></tr>`).join('')
          : `<tr><td colspan="${L.cols.length + 2}" class="empty">Belum ada data. Klik + Tambah Baris.</td></tr>`}
      </tbody></table></div>
      <button type="button" class="btn btn-outline sm" data-q="row-add">+ Tambah Baris</button>
      ${(E.rows && Object.keys(E.rows).some(k => k.startsWith(tab))) ? `<div class="err"><i>!</i>Kolom pertama wajib diisi pada baris yang ditandai.</div>` : ''}
      ${tab === 'lampiran' ? `<p class="hint">File lampiran PDF/JPG/PNG${q('Q-27', 'Lampiran dokumen perlu file unggahan (ijazah, sertifikat)? Excel hanya berisi nama dokumen & tahun. Usulan: dapat diunggah, opsional.')}.</p>` : ''}`;
    }
    return `<div class="tabs tr-tabs">${tabs}</div><div class="tr-body">${body}</div>`;
  }

  function trainerForm(rec) {
    const blank = { nama: '', tmpLahir: '', tglLahir: '', alamat: '', telp: '', pendSmu: '', pendDiploma: '', pendSarjana: '', pendPasca: '', keahlian: [], lainnya: '', cvTempat: '', cvTanggal: '', ttdNama: '', pelatihan: [], kerja: [], mengajar: [], lampiran: [] };
    const d = Object.assign(clone(rec || blank), { tab: 'identitas', err: {} });
    d.keahlian = d.keahlian.join('\n');
    openModal({
      title: rec ? `Edit Trainer ${rec.kode}` : 'Tambah Master Trainer', wide: 'x',
      body: trainerBody(d),
      onMount: form => {
        const redraw = () => { form.querySelector('.modal-body').innerHTML = trainerBody(d); };
        form.addEventListener('input', e => {
          const t = e.target;
          if (t.dataset.tr && t.dataset.k !== 'file') d[t.dataset.tr][+t.dataset.i][t.dataset.k] = t.value;
          else if (t.name && t.name in d) d[t.name] = t.value;
        });
        form.addEventListener('change', e => {
          const t = e.target;
          if (t.dataset.k === 'file' && t.files[0]) { d[t.dataset.tr][+t.dataset.i].file = t.files[0].name; redraw(); }
        });
        form.addEventListener('click', e => {
          const b = e.target.closest('[data-q]'); if (!b) return;
          const a = b.dataset.q;
          if (a === 'tab') d.tab = b.dataset.tab;
          if (a === 'row-add') d[d.tab].push(Object.fromEntries(TR_LISTS[d.tab].cols.map(([k]) => [k, ''])));
          if (a === 'row-del') d[d.tab].splice(+b.dataset.i, 1);
          redraw();
          if (a === 'row-add') { const last = [...form.querySelectorAll('.tr-table tbody tr')].pop(); const i = last && last.querySelector('input.input'); if (i) i.focus(); }
        });
      },
      onSave: () => {
        const E = { rows: {} };
        if (!d.nama.trim()) E.nama = 'Nama lengkap wajib diisi';
        // Baris riwayat yang kosong seluruhnya dibuang; baris berisi wajib punya kolom pertama.
        Object.keys(TR_LISTS).forEach(k => {
          const first = TR_LISTS[k].cols[0][0];
          d[k] = d[k].filter(r => Object.values(r).some(v => String(v).trim()));
          d[k].forEach((r, i) => { if (!String(r[first]).trim()) E.rows[`${k}-${i}`] = true; });
        });
        const rowErr = Object.keys(E.rows);
        if (E.nama || rowErr.length) {
          d.err = E;
          d.tab = E.nama ? 'identitas' : rowErr[0].split('-')[0];
          $('#modal-root .modal-body').innerHTML = trainerBody(d);
          toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn');
          return false;
        }
        const v = {};
        Object.keys(blank).forEach(k => { v[k] = typeof d[k] === 'string' ? d[k].trim() : d[k]; });
        v.keahlian = d.keahlian.split('\n').map(x => x.trim()).filter(Boolean);
        Object.keys(TR_LISTS).forEach(k => { v[k] = d[k].map(r => Object.fromEntries(Object.entries(r).map(([a, b]) => [a, String(b).trim()]))); });
        if (rec) { Object.assign(rec, v); toast(`Trainer ${rec.kode} diperbarui`); }
        else {
          const t = Object.assign({ id: uid(), kode: nextCode('TR'), aktif: true }, v);
          db.trainer.push(t);
          state.page = Math.ceil(db.trainer.length / PAGE_SIZE);
          toast(`Trainer ${t.kode} ditambahkan`);
        }
      }
    });
  }

  // Lihat CV: tampilan baca seperti Excel (identitas + empat daftar).
  function trainerCv(t) {
    const tbl = k => { const L = TR_LISTS[k];
      return `<p class="qtn-h">${L.title}</p>${t[k].length ? `<div class="table-wrap"><table class="table dash-prev"><thead><tr><th>No.</th>${L.cols.map(([, l]) => `<th>${l}</th>`).join('')}</tr></thead><tbody>
        ${t[k].map((r, i) => `<tr><td>${i + 1}</td>${L.cols.map(([c]) => `<td>${esc(r[c] || '—')}</td>`).join('')}</tr>`).join('')}</tbody></table></div>` : '<p class="dash-empty">Belum ada data.</p>'}`; };
    openModal({
      title: `CV Trainer — ${t.nama}`, wide: 'x', info: true,
      body: `<div class="letter"><p><b>MASTER TRAINER — IDENTITAS FASILITATOR</b><br><span class="muted">${esc(t.kode)}</span></p>
        <div class="summary">${sumRow('Nama lengkap', esc(t.nama))}${sumRow('Tempat, tanggal lahir', `${esc(t.tmpLahir || '—')}, ${t.tglLahir ? fmtTgl(t.tglLahir) : '—'}`)}
          ${sumRow('Alamat rumah', esc(t.alamat || '—'))}${sumRow('No. telepon rumah/HP', esc(t.telp || '—'))}
          ${PENDIDIKAN.map(([k, l]) => sumRow(`Pendidikan — ${l}`, esc(t[k] || '—'))).join('')}
          ${sumRow('Bidang keahlian', esc(t.keahlian.join('; ') || '—'))}${sumRow('Pengalaman lainnya', esc(t.lainnya || '—'))}
          ${sumRow('Pengesahan CV', `${esc(t.cvTempat || '—')}, ${t.cvTanggal ? fmtTgl(t.cvTanggal) : '—'}`)}${sumRow('Nama pada tanda tangan', esc(t.ttdNama || '—'))}</div>
        ${Object.keys(TR_LISTS).map(tbl).join('')}</div>`
    });
  }

  // ---------- Master Template Keterangan Quotation (FR-48 – FR-50; input/Master - Buat Program/2026-10-29-keterangan-quotation-default/) ----------
  // Butir teks per kategori. Butir Default (aktif, berlaku untuk jenis penjualan) tersalin ke quotation baru, lalu dapat diedit per quotation.
  const KET_KAT = [['syarat', 'Syarat keikutsertaan'], ['termasuk', 'Termasuk'], ['tidak', 'Tidak termasuk'], ['dokumen', 'Dokumen persyaratan'], ['klausul', 'Klausul tanda tangan']];
  const KET_LABEL = Object.fromEntries(KET_KAT);
  const BERLAKU = { semua: 'Semua', public: 'Public', b2b: 'B2B' };
  const QKET = q('Q-28', 'Kategori, pembedaan Public/B2B, dan isi default awal belum dikonfirmasi Labmania. Butir bertanda "contoh" hanya contoh.');
  // Isi awal: dari Template Penawaran & S&K; butir "contoh" = usulan analis.
  db.ketQtn = [
    ['syarat', 'Peserta bekerja atau akan bekerja di bidang laboratorium', true, 'semua', true],
    ['termasuk', 'Materi', true, 'semua'], ['termasuk', 'Sertifikat pelatihan', true, 'semua'], ['termasuk', 'Pajak', true, 'semua'],
    ['termasuk', 'Makan siang dan coffee break', false, 'semua', true],
    ['tidak', 'Transportasi dan akomodasi peserta', true, 'semua', true],
    ['tidak', 'Sewa ruang pelatihan di lokasi pelanggan (in-house)', true, 'b2b', true],
    ['dokumen', 'Formulir konfirmasi keikutsertaan yang telah diisi', true, 'public'],
    ['dokumen', 'Fotokopi ijazah terakhir', false, 'semua', true],
    ['klausul', 'Apabila pelanggan menyetujui penawaran kami mohon untuk menandatangani surat penawaran ini, dan dikirimkan kembali ke LabMania.', true, 'semua'],
    ['klausul', 'Dengan menandatangani surat ini, pelanggan telah membaca dan menyetujui syarat dan ketentuan dari LabMania.', true, 'semua']
  ].map(([kat, teks, def, berlaku, contoh]) => ({ id: uid(), kode: nextCode('KQ'), kat, teks, def, berlaku, contoh: !!contoh, aktif: true }));
  const ketBerlaku = (r, jenis) => r.berlaku === 'semua' || r.berlaku === jenis;
  const ketDefault = jenis => Object.fromEntries(KET_KAT.map(([k]) => [k, db.ketQtn.filter(r => r.aktif && r.def && r.kat === k && ketBerlaku(r, jenis)).map(r => r.teks)]));
  const ketKosong = () => Object.fromEntries(KET_KAT.map(([k]) => [k, []]));

  const viewKetQtn = () => `${pageHead('Template Keterangan Quotation', 'Teks default blok Keterangan di Penawaran, per kategori. Butir <b>Default</b> otomatis tersalin ke quotation baru sesuai jenis penjualan, lalu dapat diedit per quotation tanpa mengubah master.', 'FR-48 – FR-50',
    '<button class="btn btn-accent" data-act="ket-add">+ Tambah Butir</button>')}
    <div class="notice"><span>Perubahan di sini hanya berlaku untuk quotation <b>baru</b>; quotation yang sudah dibuat menyimpan teksnya sendiri. Tidak dapat dihapus — gunakan Nonaktifkan.</span><span class="ref">FR-50${QKET}</span></div>
    <div class="card">${toolbar('Cari kode atau teks…', true, selectFilter('fjenis', 'Filter kategori', state.jenis, [['all', 'Semua kategori'], ...KET_KAT]))}<div id="list">${listKetQtn()}</div></div>`;

  function listKetQtn() {
    const ord = r => KET_KAT.findIndex(([k]) => k === r.kat);
    const rows = db.ketQtn.filter(r => statusOk(r) && (state.jenis === 'all' || r.kat === state.jenis) && matches([r.kode, r.teks, KET_LABEL[r.kat]]))
      .slice().sort((a, b) => ord(a) - ord(b));
    return listBlock(rows, [
      NO_COL,
      { label: 'Kode' + Q01, render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Kategori', cls: 'nowrap', render: r => esc(KET_LABEL[r.kat]) },
      { label: 'Teks', cls: 'title', render: r => esc(r.teks) + (r.contoh ? ' <span class="tag tag-manual" title="Contoh dari analis — belum dikonfirmasi Labmania">contoh</span>' : '') },
      { label: 'Berlaku untuk', cls: 'nowrap', render: r => r.berlaku === 'semua' ? 'Semua' : jenisTag(r.berlaku) },
      { label: 'Default', cls: 'nowrap', render: r => r.def ? '<b>Ya</b>' : '<span class="muted">Tidak</span>' },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['ket-edit', 'Edit'], toggleItem(r)]) }
    ]);
  }

  function ketForm(rec) {
    const r = rec || { kat: state.jenis !== 'all' && KET_LABEL[state.jenis] ? state.jenis : '', teks: '', berlaku: 'semua', def: true };
    openModal({
      title: rec ? `Edit Butir ${rec.kode}` : 'Tambah Butir Keterangan', wide: true,
      body: `<div class="grid2">
        ${field('Kode' + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode('KQ'))}" readonly>`)}
        ${field('Kategori' + req, `<select class="input" name="kat"><option value="">— Pilih —</option>${KET_KAT.map(([k, l]) => `<option value="${k}" ${r.kat === k ? 'selected' : ''}>${l}</option>`).join('')}</select>`)}
        ${field('Teks' + req, `<textarea class="input" name="teks" rows="3" maxlength="500">${esc(r.teks)}</textarea><span class="hint">Satu butir; tercetak sebagai satu baris di Keterangan Penawaran</span>`, '', 'span2')}
        ${field('Berlaku untuk', `<select class="input" name="berlaku">${Object.keys(BERLAKU).map(k => `<option value="${k}" ${r.berlaku === k ? 'selected' : ''}>${BERLAKU[k]}</option>`).join('')}</select><span class="hint">Jenis penjualan yang menerima butir ini</span>`)}
        ${field('Default', `<label class="chk static"><input type="checkbox" name="def" ${r.def ? 'checked' : ''}> Otomatis masuk quotation baru</label><span class="hint">Bila tidak, butir hanya tersedia sebagai pilihan tambahan</span>`)}
      </div>`,
      onSave: f => {
        const v = { kat: f.kat.value, teks: f.teks.value.trim(), berlaku: f.berlaku.value, def: f.def.checked };
        let ok = true;
        if (!v.kat) ok = invalid(f.kat, 'Kategori wajib dipilih');
        if (!v.teks) ok = invalid(f.teks, 'Teks wajib diisi');
        if (!ok) return false;
        if (rec) { Object.assign(rec, v, { contoh: rec.contoh && rec.teks === v.teks }); toast(`Butir ${rec.kode} diperbarui`); }
        else {
          const k = Object.assign({ id: uid(), kode: nextCode('KQ'), contoh: false, aktif: true }, v);
          db.ketQtn.push(k);
          toast(`Butir ${k.kode} ditambahkan`);
        }
      }
    });
  }

  // Blok Keterangan di form quotation (QTN-FR-17; input/Master - Buat Program/2026-10-29-keterangan-ux-preview/):
  // daftar centang butir master yang berlaku (Default tercentang) + teks tambahan; Klausul tanda tangan tidak tampil, diambil dari master.
  const KET_FORM = KET_KAT.filter(([k]) => k !== 'klausul');
  function ketItems(jenis, ket) {
    return Object.fromEntries(KET_FORM.map(([k]) => {
      const master = db.ketQtn.filter(r => r.aktif && r.kat === k && ketBerlaku(r, jenis));
      const cur = ket ? ket[k] || [] : null;
      const items = master.map(r => ({ id: r.id, teks: r.teks, on: cur ? cur.includes(r.teks) : r.def }));
      (cur || []).filter(t => !master.some(r => r.teks === t)).forEach(t => items.push({ id: null, teks: t, on: true }));
      return [k, items];
    }));
  }
  const ketFromItems = (items, jenis) => Object.assign(
    Object.fromEntries(KET_FORM.map(([k]) => [k, ((items || {})[k] || []).filter(x => x.on && x.teks.trim()).map(x => x.teks.trim())])),
    { klausul: ketDefault(jenis).klausul });
  function ketHtml(d) {
    if (!d.ketItems) return '<p class="dash-empty">Pilih Penjualan Program di langkah 1 dahulu — butir diisi sesuai jenis penjualan (Public / B2B).</p>';
    const jenis = (db.quot.find(x => x.id === d.pj) || {}).jenis || 'public';
    const acc = KET_FORM.map(([k, l]) => {
      const list = d.ketItems[k], on = list.filter(x => x.on).length;
      const rows = list.map((x, i) => d.ketEdit === `${k}:${i}`
        ? `<div class="ket-item editing"><input class="input sm" data-ket-edit="${k}:${i}" value="${esc(x.teks)}" aria-label="Ubah teks">
            <button type="button" class="btn btn-primary sm" data-q="ket-edit-ok" data-k="${k}" data-i="${i}">Simpan</button><button type="button" class="btn btn-ghost sm" data-q="ket-edit-cancel">Batal</button></div>`
        : `<div class="ket-item ${x.on ? '' : 'off'}"><label><input type="checkbox" data-kc="${k}" data-i="${i}" ${x.on ? 'checked' : ''}> <span>${esc(x.teks)}</span>${x.id ? '' : ' <span class="tag tag-manual">khusus quotation ini</span>'}</label>
            <button type="button" class="ket-pen" data-q="ket-edit" data-k="${k}" data-i="${i}" title="Ubah teks untuk quotation ini" aria-label="Ubah teks">✎</button></div>`).join('');
      return `<details class="ket-acc" data-k="${k}" ${d.ketOpen[k] === false ? '' : 'open'}><summary><b>${l}</b><span class="muted">${on} dari ${list.length} dipilih</span></summary>
        <div class="ket-list">${rows || '<p class="dash-empty">Belum ada butir di master untuk kategori ini.</p>'}
          <div class="ket-new"><input class="input sm" data-ket-new="${k}" placeholder="+ Tambah teks lain, lalu Enter" aria-label="Tambah teks ${l}"><button type="button" class="btn btn-ghost sm" data-q="ket-new" data-k="${k}">Tambah</button></div></div></details>`;
    }).join('');
    return `<div class="ket-tools"><span class="muted">Centang butir yang dicetak di Penawaran. Butir Default (${JENIS[jenis]}) sudah tercentang; perubahan hanya untuk quotation ini.${QKET}</span>
      <button type="button" class="btn btn-ghost sm" data-q="ket-reset">Kembalikan ke default</button></div>${acc}
      <p class="hint">Klausul tanda tangan (${ketDefault(jenis).klausul.length} kalimat) dicetak otomatis dari Template Keterangan — diubah lewat Master Data.</p>`;
  }

  // ---------- Master Template Formulir Konfirmasi (FR-51 – FR-53; input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/) ----------
  // Tiga template awal dari input/Tamplate Formulir Konfirmasi Workshop.doc, ...Training Sertifikasi .doc, ...Resertifikasi.doc.
  const KF_FIELDS = [['nama', 'Nama Lengkap Peserta*'], ['nik', 'NIK'], ['jabatan', 'Jabatan'], ['instansi', 'Nama Instansi'], ['alamat', 'Alamat Instansi'],
    ['hp', 'Nomor Hp'], ['wa', 'Nomor WhatsApp'], ['email', 'Alamat Email Aktif'], ['pengalaman', 'Masa Pengalaman Bekerja Sesuai Skema'], ['lsp', 'LSP'], ['habis', 'Tanggal Habis Sertifikat']];
  const KF_LABEL = Object.fromEntries(KF_FIELDS);
  const KF_CATATAN = [
    'Konfirmasi kehadiran harap dikirimkan kembali ke email {email} atau ke nomor {telp} dengan subject email **Konfirmasi Peserta** kegiatan **{kegiatan}** dengan judul **{judul}**',
    'Keikutsertaan kami hitung berdasarkan pengiriman formulir keikutsertaan ini yang tercepat.',
    'Panitia berhak menolak jika kuota telah terpenuhi.',
    '*) Nama lengkap akan menjadi acuan penulisan pada sertifikat.',
    'Jika sudah mengisi formulir berikut, maka **peserta setuju untuk tidak membatalkan keikutsertaan dalam bentuk apapun.**',
    'Formulir keikutsertaan ini maksimal diserahkan H-14 sebelum kegiatan.',
    'Apabila ada yang ingin didiskusikan, silahkan segera menghubungi ke nomor {telp}'];
  const QKF = q('Q-31', 'Template default dipilih dari Jenis Produk paket (usulan: Pelatihan → Workshop, Sertifikasi → Training & Sertifikasi). Resertifikasi dipilih manual — perlu atribut produk sendiri?');
  const ISIAN_STD = ['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'wa', 'email', 'pengalaman'];
  db.kfTpl = [
    { nama: 'Workshop', kegiatan: 'Workshop', produk: idOf('jenisProduk', 'Pelatihan'), isian: ISIAN_STD,
      pernyataan: 'Saya **akan hadir** pada kegiatan "**{judul}**" yang akan diselenggarakan pada tanggal **{tanggal}** pukul {waktu} ({durasi} hari training, {lokasi})' },
    { nama: 'Training & Sertifikasi', kegiatan: 'Training dan Sertifikasi', produk: idOf('jenisProduk', 'Sertifikasi'), isian: ISIAN_STD,
      pernyataan: 'Saya **akan hadir** pada kegiatan "**Training dan Sertifikasi {judul}**" yang akan diselenggarakan pada tanggal **{tanggal}** pukul {waktu} ({durasi} hari training dan sertifikasi, {lokasi})' },
    { nama: 'Resertifikasi', kegiatan: 'Resertifikasi', produk: '', isian: ['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'hp', 'wa', 'email', 'lsp', 'habis'],
      pernyataan: 'Saya **akan mengikuti** pada kegiatan **Resertifikasi {judul}** dan menyiapkan dokumen yang dibutuhkan dalam rentang waktu resertifikasi **(H-3 Bulan)**' }
  ].map(t => Object.assign({ id: uid(), kode: nextCode('KF'), catatan: KF_CATATAN.slice(), aktif: true }, t, { isian: t.isian.slice() }));
  const kfTplById = id => db.kfTpl.find(t => t.id === id);
  // **teks** = tebal; {penanda} diisi dari data quotation.
  const kfMark = s => esc(s).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
  const kfFill = (s, v) => s.replace(/\{(\w+)\}/g, (m, k) => k in v ? v[k] : m);
  const kfDefaultFor = pj => { const pk = paketOf(mpOf(pj)); return db.kfTpl.find(t => t.aktif && t.produk && t.produk === pk.produk) || db.kfTpl.find(t => t.aktif) || db.kfTpl[0]; };
  const kfNew = t => ({ tpl: t.id, isian: t.isian.slice(), extra: [], pernyataan: t.pernyataan });
  // Nilai mentah; kfMark yang meng-escape seluruh kalimat.
  const kfVars = pj => ({ judul: paketOf(mpOf(pj)).judul, tanggal: rangePanjang(pj.tglMulai, pj.tglSelesai), waktu: pj.waktu || '..........', durasi: pj.durasi, lokasi: pj.lokasi || '—' });
  const kfText = (s, pj) => kfMark(kfFill(s, kfVars(pj)));
  // Nilai isian dari Master Peserta; yang tidak ada di master dikosongkan untuk diisi peserta.
  function kfValue(key, p) {
    const co = db.company.find(c => c.id === p.company);
    return { nama: p.nama, instansi: pesertaCo(p), alamat: co ? `${co.alamat}, ${co.kota}` : [p.kota, p.provinsi].filter(Boolean).join(', '), wa: p.telp, email: p.email }[key] || '';
  }

  const viewKfTpl = () => `${pageHead('Template Formulir Konfirmasi', 'Template Formulir Konfirmasi Keikutsertaan yang dicetak bersama quotation. Template dipilih otomatis dari Jenis Produk paket, lalu isian dan kalimat pernyataannya dapat disesuaikan per quotation.', 'FR-51 – FR-53',
    '<button class="btn btn-accent" data-act="kf-add">+ Tambah Template</button>')}
    <div class="notice"><span>Perubahan di sini hanya berlaku untuk quotation <b>baru</b>. Tidak dapat dihapus — gunakan Nonaktifkan.</span><span class="ref">FR-53${QKF}</span></div>
    <div class="card">${toolbar('Cari kode atau nama template…')}<div id="list">${listKfTpl()}</div></div>`;

  function listKfTpl() {
    const rows = db.kfTpl.filter(r => statusOk(r) && matches([r.kode, r.nama, r.kegiatan]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Kode' + Q01, render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama Template', render: r => `<b>${esc(r.nama)}</b><br><span class="muted">subject: kegiatan ${esc(r.kegiatan)}</span>` },
      { label: 'Default untuk Jenis Produk', render: r => r.produk ? esc((findRec('jenisProduk', r.produk) || {}).nama || '—') : '<span class="muted">— (pilih manual)</span>' },
      { label: 'Isian', render: r => `<b>${r.isian.length}</b> <span class="muted">isian</span><br>${r.isian.filter(k => !['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'email'].includes(k)).map(k => `<span class="ps-chip">${esc(KF_LABEL[k])}</span>`).join(' ')}` },
      { label: 'Pernyataan', cls: 'title', render: r => `<span class="muted">${kfMark(r.pernyataan.length > 110 ? r.pernyataan.slice(0, 110) + '…' : r.pernyataan)}</span>` },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['kf-edit', 'Edit'], ['kf-preview', 'Lihat contoh'], toggleItem(r)]) }
    ]);
  }

  function kfForm(rec) {
    const r = rec || { nama: '', kegiatan: '', produk: '', isian: ['nama', 'instansi', 'wa', 'email'], pernyataan: 'Saya **akan hadir** pada kegiatan "**{judul}**" yang akan diselenggarakan pada tanggal **{tanggal}** pukul {waktu} ({durasi} hari, {lokasi})', catatan: KF_CATATAN.slice() };
    const prodOpts = db.jenisProduk.filter(p => p.aktif || p.id === r.produk).map(p => `<option value="${p.id}" ${p.id === r.produk ? 'selected' : ''}>${esc(p.nama)}</option>`).join('');
    openModal({
      title: rec ? `Edit Template ${rec.kode}` : 'Tambah Template Formulir Konfirmasi', wide: 'x',
      body: `<div class="kf-grid"><div>
        <div class="grid2">
          ${field('Kode' + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode('KF'))}" readonly>`)}
          ${field('Nama template' + req, `<input class="input" name="nama" value="${esc(r.nama)}" maxlength="80" placeholder="mis. Workshop">`)}
          ${field('Label kegiatan' + req, `<input class="input" name="kegiatan" value="${esc(r.kegiatan)}" maxlength="80" placeholder="mis. Training dan Sertifikasi"><span class="hint">Dipakai di subject email: "kegiatan … dengan judul …"</span>`)}
          ${field('Default untuk Jenis Produk' + QKF, `<select class="input" name="produk"><option value="">— Tidak ada (pilih manual) —</option>${prodOpts}</select>`)}
        </div>
        <p class="qtn-h">Isian formulir</p>
        <div class="kf-isian">${KF_FIELDS.map(([k, l]) => `<label class="chk"><input type="checkbox" name="isian" value="${k}" ${r.isian.includes(k) ? 'checked' : ''}> ${esc(l)}</label>`).join('')}</div>
        <div id="kf-isian-err"></div></div><div>
        <p class="qtn-h">Pernyataan 1</p>
        ${field('Kalimat pernyataan' + req, `<textarea class="input" name="pernyataan" rows="4">${esc(r.pernyataan)}</textarea><span class="hint">Penanda diisi otomatis: {judul} {tanggal} {waktu} {durasi} {lokasi}. **teks** = tebal. Pernyataan 2 (membaca & menyetujui S&amp;K) selalu tercetak.</span>`)}
        <p class="qtn-h">Catatan</p>
        ${field('Butir catatan', `<textarea class="input" name="catatan" rows="7">${esc(r.catatan.join('\n'))}</textarea><span class="hint">Satu butir per baris. Penanda: {email} {telp} (sales quotation), {kegiatan}, {judul}.</span>`)}
      </div></div>`,
      onSave: f => {
        const v = { nama: f.nama.value.trim(), kegiatan: f.kegiatan.value.trim(), produk: Number(f.produk.value) || '', pernyataan: f.pernyataan.value.trim(),
          isian: KF_FIELDS.map(([k]) => k).filter(k => [...f.isian].some(x => x.checked && x.value === k)), catatan: f.catatan.value.split('\n').map(x => x.trim()).filter(Boolean) };
        let ok = true;
        if (!v.nama) ok = invalid(f.nama, 'Nama template wajib diisi');
        if (!v.kegiatan) ok = invalid(f.kegiatan, 'Label kegiatan wajib diisi');
        if (!v.pernyataan) ok = invalid(f.pernyataan, 'Kalimat pernyataan wajib diisi');
        if (!v.isian.length) { $('#kf-isian-err').innerHTML = '<div class="err"><i>!</i>Pilih minimal 1 isian</div>'; ok = false; }
        if (!ok) return false;
        if (rec) { Object.assign(rec, v); toast(`Template ${rec.kode} diperbarui`); }
        else { const t = Object.assign({ id: uid(), kode: nextCode('KF'), aktif: true }, v); db.kfTpl.push(t); toast(`Template ${t.kode} ditambahkan`); }
      }
    });
  }

  // Lihat contoh: formulir dengan Penjualan Program & peserta contoh.
  function kfPreview(t) {
    const pj = db.quot.find(x => x.status === 'final'), p = db.peserta[0], s = db.user.find(u => u.aktif) || {};
    const k = { pj: pj.id, sales: s.id, peserta: [{ id: p.id }], kf: kfNew(t) };
    openModal({ title: `Contoh — ${t.nama}`, wide: 'x', info: true, body: pdfWrap(pageKonfirmasi(k, p), `Contoh dengan ${esc(pj.nomor)} dan ${esc(p.nama)}`), onMount: pdfMount });
  }

  // Langkah "Formulir Konfirmasi" di form quotation (QTN-FR-19): pilih template, centang isian, ubah pernyataan.
  function kfHtml(d) {
    if (!d.kf) return '<p class="dash-empty">Pilih Penjualan Program di langkah 1 dahulu.</p>';
    const pj = db.quot.find(x => x.id === d.pj), t = kfTplById(d.kf.tpl) || {}, def = pj ? kfDefaultFor(pj) : null;
    const opts = db.kfTpl.filter(x => x.aktif || x.id === d.kf.tpl).map(x => `<option value="${x.id}" ${x.id === d.kf.tpl ? 'selected' : ''}>${esc(x.nama)}${def && x.id === def.id ? ' — default' : ''}</option>`).join('');
    const isian = KF_FIELDS.map(([k, l]) => `<label class="kf-chip ${d.kf.isian.includes(k) ? 'on' : ''}"><input type="checkbox" data-kfi="${k}" ${d.kf.isian.includes(k) ? 'checked' : ''}> ${esc(l)}</label>`).join('')
      + d.kf.extra.map((l, i) => `<span class="kf-chip on extra">${esc(l)} <button type="button" data-q="kf-extra-del" data-i="${i}" aria-label="Hapus isian">×</button></span>`).join('');
    return `<div class="ket-tools"><span class="muted">Dicetak satu halaman per peserta. Template default dari Jenis Produk paket; perubahan hanya untuk quotation ini.</span>
        <button type="button" class="btn btn-ghost sm" data-q="kf-reset">Kembalikan ke default</button></div>
      <div class="kf-grid"><div>
        ${field('Template formulir' + QKF, `<select class="input" name="kfTpl">${opts}</select><span class="hint">Subject email: "kegiatan ${esc(t.kegiatan || '—')}"</span>`)}
        <p class="qtn-h">Isian <span class="muted">(${d.kf.isian.length + d.kf.extra.length})</span></p>
        <div class="kf-isian">${isian}</div>
        <div class="ket-new"><input class="input sm" data-kf-new placeholder="+ Tambah isian lain, lalu Enter" aria-label="Tambah isian"><button type="button" class="btn btn-ghost sm" data-q="kf-new">Tambah</button></div>
        <p class="hint">Nama, Instansi, Alamat, WhatsApp, Email terisi dari Master Peserta; isian lain dikosongkan untuk diisi peserta.</p>
      </div><div>
        ${field('Pernyataan 1', `<textarea class="input" name="kfPernyataan" rows="4">${esc(d.kf.pernyataan)}</textarea><span class="hint">Penanda: {judul} {tanggal} {waktu} {durasi} {lokasi} · **teks** = tebal</span>`)}
        <div class="kf-prev"><span class="muted">Tercetak:</span> <span id="kf-prev">${pj ? kfText(d.kf.pernyataan, pj) : ''}</span></div>
        <p class="hint">Pernyataan 2 (membaca &amp; menyetujui S&amp;K) dan ${(t.catatan || []).length} butir catatan dicetak dari template — diubah lewat Master Data.</p>
      </div></div>`;
  }
  function kfAct(d, form, a, b) {
    const pj = db.quot.find(x => x.id === d.pj);
    if (a === 'kf-reset') { d.kf = pj ? kfNew(kfDefaultFor(pj)) : null; d.kfTouched = false; }
    if (a === 'kf-extra-del') { d.kf.extra.splice(+b.dataset.i, 1); d.kfTouched = true; }
    if (a === 'kf-new') {
      const inp = form.querySelector('[data-kf-new]'), v = inp.value.trim();
      if (!v) return inp.focus();
      d.kf.extra.push(v); d.kfTouched = true;
    }
    qtnRedraw(d, form);
    if (a === 'kf-new') form.querySelector('[data-kf-new]').focus();
  }

  // ---------- Master Rate (FR-18 – FR-20, FR-25, FR-33 – FR-35) ----------
  function viewRate() {
    const tabs = Object.keys(RATE_TAB).map(t =>
      `<button class="tab ${t === state.rateTab ? 'active' : ''}" data-act="rate-tab" data-tab="${t}">${RATE_TAB[t].title}<span class="nav-count">${db.rate[t].length}</span></button>`).join('');
    const tab = RATE_TAB[state.rateTab];
    return `${pageHead('Master Rate', 'Item biaya beserta Default Cost yang dipakai costing Master Program dan Penjualan Program.', 'FR-18 · ' + tab.fr + ' · FR-33',
      `<button class="btn btn-accent" data-act="rate-add">+ Tambah ${tab.title}</button>`)}
      <div class="notice"><span>Satuan <b>Fixed Cost</b>: Event, Day (biaya per jam dicatat sebagai Event). Satuan <b>Variable Cost</b>: Peserta/Event, Peserta/Day.
        Satu item satu <b>Default Cost</b>, yang dapat disesuaikan di Master Program dan Penjualan Program tanpa mengubah master. Item tidak dapat dihapus — gunakan <b>Nonaktifkan</b>.</span><span class="ref">FR-19 · FR-25 · FR-34 · FR-35</span></div>
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
      { label: 'Default Cost', cls: 'money', render: r => rp(r.cost) },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['rate-edit', 'Edit'], toggleItem(r)]) }
    ]);
  }

  function rateForm(rec) {
    const tabKey = state.rateTab;
    const t = RATE_TAB[tabKey];
    const r = rec || { item: '', satuan: '', cost: '', ket: '', coa: '' };
    openModal({
      title: `${rec ? 'Edit' : 'Tambah'} ${t.title}`,
      wide: true,
      body: `<div class="grid2">
        ${field(`Kode ${t.title}` + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode(t.prefix))}" readonly><span class="hint">Diisi otomatis oleh sistem</span>`)}
        ${field('Kategori' + req, `<select class="input" name="kategori" ${rec ? 'disabled' : ''}>${Object.keys(RATE_TAB).map(k => `<option value="${k}" ${k === tabKey ? 'selected' : ''}>${k === 'fixed' ? 'Fixed' : 'Variable'}</option>`).join('')}</select>${rec ? '<span class="hint">Kategori tidak dapat diubah setelah disimpan</span>' : ''}`)}
        ${field('Satuan' + req, `<select class="input" name="satuan"><option value="">— Pilih Satuan —</option>${opts(t.satuan, r.satuan)}</select>`)}
        ${field('Item' + req, `<input class="input" name="itemName" value="${esc(r.item)}" maxlength="150" placeholder="Nama item biaya">`, '', 'span2')}
        ${field('Default Cost (Rp)' + req, `${moneyIn('name="cost"', r.cost)}<span class="hint">Nilai awal; dapat diedit di Master Program & Penjualan Program</span>`)}
        ${field('Keterangan', `<input class="input" name="ket" value="${esc(r.ket)}" maxlength="150" placeholder="Opsional">`, '', 'span2')}
        ${field('COA' + req, `<select class="input" name="coa"><option value="">— Pilih COA —</option>${opts(COA, r.coa)}</select><span class="hint">Daftar COA di mockup hanya contoh; di aplikasi diambil dari master COA</span>`, '', 'span2')}
      </div>`,
      onMount: form => {
        form.elements.kategori.addEventListener('change', e => {
          form.elements.satuan.innerHTML = `<option value="">— Pilih Satuan —</option>${opts(RATE_TAB[e.target.value].satuan, '')}`;
        });
      },
      onSave: f => {
        let ok = true;
        const v = { item: f.itemName.value.trim(), satuan: f.satuan.value, ket: f.ket.value.trim(), coa: f.coa.value };
        v.cost = digits(f.cost.value) === '' ? '' : Number(digits(f.cost.value));
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

  // ---------- Shared: Master Program & Penjualan Program ----------
  const paketOf = p => db.paket.find(x => x.id === p.paket);
  const mpOf = q => db.mprog.find(x => x.id === q.mp);
  const companyOf = q => db.company.find(x => x.id === q.company);
  const QMP02 = q('Q-MP-02', 'Format usulan (sementara): MP-YYMM-NNN, nomor urut ulang tiap bulan. Format final menyusul.');
  const QPJ08 = q('Q-PJ-08', 'Format usulan (sementara): Public PJP-YYMM-NNN, B2B PJB-YYMM-NNN, nomor urut ulang tiap bulan per jenis. Format final menyusul.');
  const fmtTgl = iso => iso ? new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
  const fmtRange = (a, b) => !a ? '—' : (!b || a === b ? fmtTgl(a) : `${fmtTgl(a)} – ${fmtTgl(b)}`);

  // ---------- Master Program: daftar (MP-FR-01) ----------
  const viewMprog = () => `${pageHead('Daftar Master Program', 'Program siap jual: Paket Produk + costing baseline. Tidak ada Public/B2B, customer, tanggal, atau lokasi acara — semuanya diisi di Penjualan Program.', 'MP-FR-01 · MP-FR-02',
    '<button class="btn btn-accent" data-act="mp-new">+ Buat Master Program</button>')}
    <div class="card">${toolbar('Cari nomor atau judul…', false,
      selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(MP_STATUS).map(s => [s, MP_STATUS[s]])]))}
      <div id="list">${listMprog()}</div></div>`;

  function mpGear(p) {
    // Draft: Edit, Finalkan, Batalkan; Final & Batal: Lihat (input/Master - Buat Program/2026-10-23-opsi-status-master-program/).
    if (p.status !== 'draft') return gearMenu(p, [['mp-open', 'Lihat']]);
    return gearMenu(p, [['mp-open', 'Edit'], ['mp-final', 'Finalkan'], ['mp-cancel', 'Batalkan', true]]);
  }


  function listMprog() {
    const rows = db.mprog.filter(p => (state.status === 'all' || p.status === state.status) &&
      matches([p.nomor, paketOf(p).judul, paketOf(p).nomor]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + QMP02, cls: 'nowrap', render: p => `<span class="code">${esc(p.nomor)}</span>` },
      { label: 'Judul', cls: 'title', render: p => `${esc(paketOf(p).judul)}<br><span class="muted">${esc(paketOf(p).nomor)} · ${esc(findRec('jenisSertifikasi', paketOf(p).sertifikasi).nama)}</span>` },
      { label: 'Durasi', cls: 'nowrap', render: p => `${p.durasi} hari` },
      { label: 'Target peserta', cls: 'money', render: p => p.targetPeserta },
      { label: 'Harga jual standar', cls: 'money', render: p => rp(p.hargaJual) },
      { label: 'HPP', cls: 'money', render: p => rp(calc(p).hpp) },
      { label: 'Status', cls: 'nowrap', render: p => stBadge(p.status) },
      { label: 'Action', cls: 'act', render: mpGear }
    ]);
  }

  // ---------- Penjualan Program: daftar (PJ-FR-01) ----------
  const NEXT = { public: 'Quotation', b2b: 'Quotation' };
  const viewQuot = () => `${pageHead('Daftar Penjualan Program', 'Tarik Master Program Final, pilih Public atau B2B, isi tanggal & lokasi, lalu sesuaikan harga dan costing — form sama untuk kedua jenis, perubahan tidak mengubah Master Program. Setelah Final, Public maupun B2B dibuatkan Quotation (modul Quotation).', 'PJ-FR-01 – PJ-FR-05',
    '<button class="btn btn-accent" data-act="qt-new">+ Buat Penjualan Program</button>')}
    <div class="card">${toolbar('Cari nomor, program, atau lokasi…', false,
      selectFilter('fjenis', 'Filter jenis', state.jenis, [['all', 'Semua jenis'], ['public', 'Public'], ['b2b', 'B2B']])
      + selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(QT_STATUS).map(s => [s, QT_STATUS[s]])]))}
      <div id="list">${listQuot()}</div></div>`;

  function qtGear(q) {
    const items = [['qt-open', q.status === 'draft' ? 'Edit' : 'Lihat']];
    if (q.status === 'draft') items.push(['qt-final', 'Finalkan'], ['qt-cancel', 'Batalkan', true]);
    if (q.status === 'final') items.push(...(canAddQtn(q) ? [['qtn-from-pj', '+ Buat Quotation']] : []), ['qt-print', 'Cetak Surat Penawaran']);
    return gearMenu(q, items);
  }

  function listQuot() {
    const rows = db.quot.filter(q => (state.jenis === 'all' || q.jenis === state.jenis) &&
      (state.status === 'all' || q.status === state.status) &&
      matches([q.nomor, mpOf(q).nomor, paketOf(mpOf(q)).judul, q.lokasi]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + QPJ08, cls: 'nowrap', render: q => `<span class="code">${esc(q.nomor)}</span>` },
      { label: 'Jenis', render: q => jenisTag(q.jenis) + (q.jenis === 'b2b' && companyOf(q) ? `<br><span class="muted">${esc(companyOf(q).nama)}</span>` : '') },
      { label: 'Master Program', cls: 'title', render: q => `${esc(paketOf(mpOf(q)).judul)}<br><span class="muted">${esc(mpOf(q).nomor)}</span>` },
      { label: 'Pelaksanaan', render: q => `${fmtRange(q.tglMulai, q.tglSelesai)}<br><span class="muted">${esc(q.lokasi || '—')}${q.trainer ? ` · Trainer: ${esc((trainerById(q.trainer) || {}).nama || '—')}` : ''}</span>` },
      { label: 'Target peserta', cls: 'money', render: q => q.targetPeserta + (q.status === 'final' ? `<br><span class="muted">deal ${dealOf(q)}</span>` : '') },
      { label: 'Harga jual / peserta', cls: 'money', render: q => rp(q.hargaJual) },
      { label: 'Status', cls: 'nowrap', render: q => stBadge(q.status) + (q.status === 'final' ? `<br><span class="muted">→ ${NEXT[q.jenis]}</span>` : '') },
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

  // Bagian yang terkunci: Master Program / Penjualan Program yang bukan Draft (MP-FR-13, PJ-FR-15).
  function costLocked() {
    const { kind, p } = state.pf;
    if (kind === 'mp') return p.status !== 'draft';
    return p.status !== 'draft';
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
    if (c.target && c.pendapatan) {
      if (c.profit < 0) w.push('Rugi — pendapatan belum menutup HPP.');
      if (c.bepPeserta === null) w.push('Titik impas tidak tercapai — harga per peserta belum menutup biaya variable per peserta.');
      else if (c.bepPeserta > c.target) w.push(`Titik impas ${c.bepPeserta} peserta, di atas jumlah peserta yang direncanakan (${c.target}).`);
      if (p.targetProfit !== '' && c.profit < Number(p.targetProfit)) w.push(`Profit ${rp(c.profit)} di bawah target profit ${rp(p.targetProfit)}.`);
    }
    return w;
  }

  function stepSummary(n, p) {
    const c = calc(p);
    if (n === 1) {
      if (!isQt()) return p.paket ? `${esc(paketOf(p).judul)} · ${Number(p.durasi) || 0} hari · ${nPeserta(p)} peserta · ${rp(p.hargaJual)}` : 'Paket Produk belum dipilih';
      return `${p.jenis === 'b2b' && companyOf(p) ? esc(companyOf(p).nama) + ' · ' : ''}${fmtRange(p.tglMulai, p.tglSelesai)} · ${nPeserta(p)} peserta · ${rp(c.harga)} / peserta`;
    }
    if (n === 2) return `Total ${rp(c.fixed)}`;
    if (n === 3) return `Total ${rp(c.totalVar)}`;
    return c.target && c.pendapatan ? `Profit <b class="${c.profit < 0 ? 'neg' : 'pos'}">${rp(c.profit)}</b>` : '—';
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

  const MONEY_PF = ['hargaJual', 'targetProfit'];
  const numInput = (name, val, locked, extra = '') => MONEY_PF.includes(name)
    ? moneyIn(`data-pf="${name}" ${locked ? 'disabled' : ''}`, val)
    : `<input class="input" data-pf="${name}" type="number" min="0" value="${esc(val)}" ${locked ? 'disabled' : ''} ${extra}>`;

  function costTable(gi, locked) {
    const g = COST_GROUPS[gi];
    const { p, errors: E } = state.pf;
    const rows = p.biaya.map((b, i) => ({ b, i })).filter(({ b }) => b.tipe === g.tipe && b.satuan === g.satuan);
    if (locked && !rows.length) return '';
    const body = rows.length ? rows.map(({ b, i }) => {
      const r = b.rate ? rateById(b.rate) : null;
      const err = E['b' + i];
      // Fixed & Variable Cost: semua item boleh dihapus (×) selama Draft (MP-FR-06, PJ-FR-05).
      const removable = !locked;
      const name = b.manual
        ? `<input class="input sm-in" data-bi="${i}" data-bf="item" value="${esc(b.item)}" placeholder="Nama biaya lain" ${locked ? 'disabled' : ''}>`
        : `${esc(b.item)}${r && !r.aktif ? ' <span class="muted">(nonaktif)</span>' : ''}`;
      return `<tr class="${Number(b.harga) > 0 ? '' : 'zero'} ${err ? 'row-err' : ''}" data-row="${i}">
        <td>${name}${defMeta(b)}</td>
        <td class="num">${moneyIn(`data-bi="${i}" data-bf="harga" ${locked ? 'disabled' : ''} placeholder="${b.manual ? 'Isi nominal' : ''}" aria-label="Nominal ${esc(b.item)}"`, b.harga).replace('class="input money-in"', 'class="input sm-in money-in"')}</td>
        <td class="num qty" data-fx="${i}">${lineQtyTxt(b, p)}</td>
        <td class="num" data-sub="${i}">${rp(lineTotal(b, p))}</td>
        <td class="act">${removable ? `<button type="button" class="btn btn-ghost sm row-x" data-act="pf-del" data-i="${i}" title="Tidak dipakai — hapus" aria-label="Hapus ${esc(b.item || 'biaya')}">×</button>` : ''}</td>
      </tr>${err ? `<tr class="row-msg"><td colspan="5"><i>!</i>${esc(err)}</td></tr>` : ''}`;
    }).join('') : `<tr class="norow"><td colspan="5" class="muted">Belum ada item. ${locked ? '' : 'Pilih di atas lalu klik + Tambah.'}</td></tr>`;

    let pick = '';
    if (!locked) {
      const used = p.biaya.map(b => b.rate);
      const choices = db.rate[g.tipe].filter(r => r.aktif && r.satuan === g.satuan && !used.includes(r.id));
      const select = `<select class="input" id="${g.pick}">${choices.length
        ? `<option value="">— Pilih item dari Master Rate —</option>${choices.map(r => `<option value="${r.id}">${esc(r.item)} · ${rp(r.cost)}</option>`).join('')}`
        : '<option value="" disabled selected>Semua item master sudah dipakai</option>'}</select>
        <button type="button" class="btn btn-outline sm" data-act="pf-add" data-sel="${g.pick}">+ Tambah</button>`;
      pick = `<div class="pickrow">${select}<button type="button" class="btn btn-ghost sm" data-act="pf-manual" data-g="${gi}">+ Biaya lain</button></div>`;
    }
    // Kelompok tanpa item: cukup judul + pilihan item, tanpa tabel kosong.
    if (!rows.length) return `<p class="csec-sub">${g.title} <span class="muted">— belum ada item</span></p>${pick}`;
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
      <div class="tr"><span class="muted">Fixed cost / peserta (target)</span><span class="muted">${n ? rp(c.fixed / n) : '—'}</span></div>
    </div>`;
  }
  function totalsVar(p) {
    const c = calc(p), d = Number(p.durasi) || 0;
    return `<div class="totals">
      <div class="tr"><span>Biaya variable / peserta <span class="muted">= event ${rp(groupSum(p, COST_GROUPS[2]))} + day ${rp(groupSum(p, COST_GROUPS[3]))} × ${d} hari</span></span><span>${rp(c.varPer)}</span></div>
      <div class="tr"><span><b>Total variable cost</b> <span class="muted">= per peserta × target peserta</span></span><span><b>${rp(c.totalVar)}</b></span></div>
    </div>`;
  }

  // Simulasi Hasil — tampilan mengikuti input/simulasi_hasil.html: BEP, hasil rencana, skala peserta, rincian (MP-FR-10, PJ-FR-10).
  // Satu simulasi per peserta untuk Master Program, Public, dan B2B; rincian ditulis sederhana (input/Master - Buat Program/2026-10-25-jalur-public-b2b/).
  function simView(p) {
    const c = calc(p), d = Number(p.durasi) || 0;
    if (!(c.target > 0) || !(c.pendapatan > 0)) {
      return '<div class="sim-empty">Isi <b>target peserta dan harga jual / peserta</b> di input simulasi untuk melihat hasilnya.</div>';
    }
    const tp = p.targetProfit === '' ? 0 : Number(p.targetProfit) || 0;
    const selisih = c.harga - c.varPer; // harga per peserta − biaya variable per peserta
    const firstX = (ok, from) => { for (let x = Math.max(1, from); x <= 100000; x++) if (ok(x)) return x; return null; };
    // Minimal peserta untuk capai target profit.
    const tpPeserta = tp > 0 && selisih > 0 ? firstX(x => c.at(x).profit >= tp, Math.ceil((c.fixed + tp) / selisih) - 2) : null;

    const box = (cls, tag, big, sub) => `<div class="sx-box ${cls}"><div class="sx-tag">${tag}</div><div class="sx-big">${big}</div><p>${sub}</p></div>`;

    // 1. Titik impas (BEP)
    const aman = c.bepPeserta === null ? 0 : c.target - c.bepPeserta;
    const lebih = c.harga - c.bepHarga;
    const bepBoxes = box('bep', 'BEP jumlah peserta', c.bepPeserta === null ? 'Tidak tercapai' : `${c.bepPeserta} <small>peserta</small>`,
      c.bepPeserta === null ? 'Harga jual tidak melebihi biaya variable per peserta.'
        : `Dengan harga ${rp(c.harga)}. Di bawah ${c.bepPeserta} peserta rugi. ${aman >= 0 ? `Rencana ${c.target} peserta, aman (lebih ${aman}).` : `Rencana ${c.target} peserta, kurang ${-aman}.`}`)
      + box('bep', 'BEP harga jual', `${rp(c.bepHarga)} <small>/ peserta</small>`,
        `Dengan ${c.target} peserta. Di bawah harga ini rugi. Harga rencana ${rp(c.harga)}, ${lebih >= 0 ? 'lebih' : 'kurang'} ${rp(Math.abs(lebih))}.`);

    // 2. Hasil rencana simulasi
    const untung = c.profit >= 0;
    const margin = c.pendapatan > 0 ? (c.profit / c.pendapatan * 100).toFixed(1).replace('.', ',') : '0';
    const trio = box('harga', 'Pendapatan', rp(c.pendapatan), `${c.target} peserta × ${rp(c.harga)}`)
      + box('biaya', 'Biaya (HPP)', rp(c.hpp), `Fixed ${rp(c.fixed)} + variable ${rp(c.totalVar)}`)
      + box(`profit ${untung ? 'ok' : 'bad'}`, untung ? 'Profit (untung)' : 'Profit (rugi)', rp(c.profit), `Margin ${margin}% dari pendapatan (pendapatan − biaya).`);
    let target;
    if (!tp) target = '<div class="sx-target">Target profit tidak diisi.</div>';
    else if (c.profit >= tp) target = `<div class="sx-target ok">Target profit ${rp(tp)} tercapai, lebih ${rp(c.profit - tp)}${tpPeserta ? `. Minimal ${tpPeserta} peserta untuk capai target.` : '.'}</div>`;
    else target = `<div class="sx-target bad">Target profit ${rp(tp)} belum tercapai, kurang ${rp(tp - c.profit)}${tpPeserta ? `. Perlu minimal ${tpPeserta} peserta pada harga ini.` : '.'}</div>`;

    // 3. Posisi di skala jumlah peserta
    const pct = (v, max) => Math.max(0, Math.min(100, v / max * 100));
    const mk = (v, max, label, cls, pos) => `<div class="mk ${cls} ${pos}" style="left:${pct(Math.min(v, max), max)}%">${label}</div>`;
    const maxX = Math.max(10, Math.ceil(Math.max(c.target, tpPeserta || 0, c.bepPeserta || 0) * 1.2));
    const zb = c.bepPeserta === null ? maxX : Math.min(c.bepPeserta, maxX);
    const zg = tpPeserta ? Math.min(Math.max(tpPeserta, zb), maxX) : zb;
    const bar = `<i class="z-rugi" style="width:${pct(zb, maxX)}%"></i><i class="z-kuning" style="width:${pct(zg - zb, maxX)}%"></i><i class="z-hijau" style="width:${pct(maxX - zg, maxX)}%"></i>`;
    const marks = (c.bepPeserta !== null ? mk(c.bepPeserta, maxX, `BEP ${c.bepPeserta}`, 'm-bep', 'top') : '')
      + (tpPeserta ? mk(tpPeserta, maxX, `Target profit ${tpPeserta}`, 'm-tp', 'top') : '')
      + mk(c.target, maxX, `Rencana ${c.target}`, 'm-plan', 'bot');
    const legend = '<span class="a">Rugi (di bawah BEP)</span><span class="b">Untung, belum capai target profit</span><span class="c">Target profit tercapai</span>';
    // Sumbu di luar .scale agar tinggi .scale = tinggi batang; label atas/bawah lalu berjarak tepat dari batang.
    const scale = `<div class="scale"><div class="bar">${bar}</div>${marks}</div><div class="axis"><span>0 peserta</span><span>${maxX} peserta</span></div><div class="legend">${legend}</div>`;

    // 4. Rincian — langkah hitung dalam bahasa sehari-hari, lalu tabel profit pendek.
    const ev = groupSum(p, COST_GROUPS[0]), dy = groupSum(p, COST_GROUPS[1]), pe = groupSum(p, COST_GROUPS[2]), pd = groupSum(p, COST_GROUPS[3]);
    const hari = (e, h) => h ? `${rp(e)} sekali per acara + ${rp(h)} per hari × ${d} hari` : `${rp(e)} sekali per acara`;
    const steps = [
      ['Biaya tetap acara', 'Biaya yang keluar berapa pun jumlah pesertanya (trainer, ruangan, dll.).', `${hari(ev, dy)} = <b>${rp(c.fixed)}</b>`],
      ['Biaya per peserta', 'Biaya yang bertambah setiap ada 1 peserta (modul, sertifikat, makan, dll.).', `${hari(pe, pd)} = <b>${rp(c.varPer)}</b> per peserta`],
      ['Total biaya (HPP)', `Biaya tetap + biaya per peserta untuk ${c.target} peserta.`, `${rp(c.fixed)} + ${rp(c.varPer)} × ${c.target} = <b>${rp(c.hpp)}</b>`],
      ['Pendapatan', 'Harga jual dikali jumlah peserta.', `${rp(c.harga)} × ${c.target} = <b>${rp(c.pendapatan)}</b>`],
      [untung ? 'Untung' : 'Rugi', 'Pendapatan dikurangi total biaya.', `${rp(c.pendapatan)} − ${rp(c.hpp)} = <b class="${untung ? 'pos' : 'neg'}">${rp(c.profit)}</b>`],
      ['Titik impas', 'Jumlah peserta paling sedikit supaya tidak rugi.', c.bepPeserta === null
        ? 'Harga jual belum lebih besar dari biaya per peserta, jadi berapa pun pesertanya tetap rugi.'
        : `${rp(c.fixed)} ÷ (${rp(c.harga)} − ${rp(c.varPer)}), dibulatkan ke atas = <b>${c.bepPeserta} peserta</b>`]
    ];
    const stepList = `<ol class="sx-steps">${steps.map(([k, why, how]) => `<li><b>${k}</b> <span class="muted">— ${why}</span><div>${how}</div></li>`).join('')}</ol>`;
    const set = new Set([c.target]);
    if (c.bepPeserta !== null) [c.bepPeserta - 1, c.bepPeserta].forEach(v => set.add(v));
    if (tpPeserta) set.add(tpPeserta);
    const list = [...set].filter(v => v >= 1).sort((a, b) => a - b).slice(0, 5);
    const tblP = `<p class="sx-sub">Kalau jumlah pesertanya berubah:</p><table class="sx-tbl"><thead><tr><th>Peserta</th><th class="n">Pendapatan</th><th class="n">Biaya (HPP)</th><th class="n">Untung / rugi</th></tr></thead><tbody>${list.map(n => {
      const v = c.at(n), label = [n === c.bepPeserta && 'titik impas', n === tpPeserta && 'target profit', n === c.target && 'rencana'].filter(Boolean).join(', ');
      return `<tr${n === c.target ? ' class="sum"' : ''}><td>${n}${label ? ` <span class="muted">(${label})</span>` : ''}</td><td class="n">${rp(v.pendapatan)}</td><td class="n">${rp(v.hpp)}</td><td class="n ${v.profit >= 0 ? 'pos' : 'neg'}">${rp(v.profit)}</td></tr>`;
    }).join('')}</tbody></table>`;
    const open = state.pf && state.pf.rinciOpen ? 'open' : '';

    return `<div class="simx">
      <section class="sx-panel">
        <h3>Titik impas (BEP)</h3>
        <p class="rowdesc">Batas minimal supaya tidak rugi. Tidak tergantung rencana, hanya tergantung biaya.</p>
        <div class="duo">${bepBoxes}</div>
        <h3 class="gap">Hasil rencana simulasi</h3>
        <p class="rowdesc">Dihitung dari rencana: ${d} hari, ${c.target} peserta, harga ${rp(c.harga)} / peserta.</p>
        <div class="trio">${trio}</div>
        ${target}
      </section>
      <section class="sx-panel"><h3>Posisi di skala jumlah peserta</h3>${scale}</section>
      <section class="sx-panel"><details id="sim-rinci" ${open}><summary>Rincian perhitungan</summary>${stepList}${tblP}</details></section>
    </div>`;
  }

  const simFor = simView;

  // Input simulasi tampil di bagian 1 dan bagian 4, terikat ke field yang sama: ubah di satu tempat, tempat lain ikut berubah.
  function simInputs(p, locked, E, note) {
    const mp = isQt() ? mpOf(p) : null;
    const hint = t => `<span class="hint">${t}</span>`;
    return `<div class="sim-in"><p class="sim-in-h">Input simulasi <span>${note}</span></p>
      <div class="grid4">
        ${field('Durasi (hari)' + req, numInput('durasi', p.durasi, locked || !!mp, 'step="1" min="1"') + (mp ? hint(`otomatis dari tanggal · master: ${mp.durasi} hari`) : ''), E.durasi)}
        ${field('Target peserta' + req, numInput('targetPeserta', p.targetPeserta, locked, `step="1" min="${p.jenis === 'b2b' ? B2B_MIN : 1}"`) + hint((mp ? `master: ${mp.targetPeserta}` : 'default 10, dapat diubah') + (p.jenis === 'b2b' ? ` · B2B minimal ${B2B_MIN}` : '')), E.targetPeserta)}
        ${field(`Harga jual ${mp ? '' : 'standar '}/ peserta` + req, numInput('hargaJual', p.hargaJual, locked) + (mp ? hint(`master: ${rp(mp.hargaJual)}`) : ''), E.hargaJual)}
        ${field('Target profit (opsional)', numInput('targetProfit', p.targetProfit, locked))}
      </div></div>`;
  }

  // ---------- Form: Master Program (UC-19) & Penjualan Program (UC-22) ----------
  function viewQtStart() {
    const p = state.pf.p;
    const finals = db.mprog.filter(m => m.status === 'final');
    const mp = p.mp ? mpOf(p) : null;
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button><h2>Buat Penjualan Program</h2><span class="ref">PJ-FR-02 · PJ-FR-03</span></div>
      <div class="csec"><p class="csec-h">1. Pilih Master Program <span class="muted">hanya yang berstatus Final</span></p>
        ${field('Master Program' + req, `<select class="input" id="qt-mp"><option value="">— Pilih Master Program Final —</option>${finals.map(m => `<option value="${m.id}" ${m.id === p.mp ? 'selected' : ''}>${esc(m.nomor)} — ${esc(paketOf(m).judul)} · ${esc(findRec('jenisSertifikasi', paketOf(m).sertifikasi).nama)}</option>`).join('')}</select>
          ${mp ? `<span class="hint">Master: ${mp.durasi} hari · target ${mp.targetPeserta} peserta · harga standar ${rp(mp.hargaJual)} · HPP ${rp(calc(mp).hpp)}</span>` : ''}`)}
      </div>
      <div class="csec"><p class="csec-h">2. Pilih jenis penjualan <span class="muted">form & simulasi sama; jenis hanya menentukan jalur setelah Final dan tidak dapat diubah setelah disimpan</span></p>
        <div class="choice-grid">
          <button class="choice" data-act="qt-jenis" data-jenis="public" ${mp ? '' : 'disabled'}>
            ${jenisTag('public')}<h3>Penjualan Public</h3>
            <p>Acara terbuka, tidak terikat company.</p>
            <ul><li>Tanggal, lokasi, target peserta, harga per peserta</li><li>Simulasi pendapatan, HPP, profit, titik impas</li><li>Final → <b>Quotation</b> ke Company atau Personal</li></ul>
          </button>
          <button class="choice" data-act="qt-jenis" data-jenis="b2b" ${mp ? '' : 'disabled'}>
            ${jenisTag('b2b')}<h3>Penjualan B2B</h3>
            <p>Acara untuk satu company (in-house).</p>
            <ul><li>Company wajib; target peserta <b>minimal ${B2B_MIN}</b></li><li>Simulasi pendapatan, HPP, profit, titik impas</li><li>Final → <b>satu Quotation</b> ke Company penjualan — harga per peserta atau <b>all-in</b>, tanpa Formulir Konfirmasi</li></ul>
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
    if (kind === 'mp' && p.status === 'final') lockInfo = 'Master Program <b>Final</b> — terkunci dan tersedia di pilihan Penjualan Program. Penyesuaian harga/costing dilakukan di Penjualan Program. Master Program Final tidak dapat dibatalkan atau disalin; perubahan master: buat Master Program baru.';
    if (kind === 'mp' && p.status === 'batal') lockInfo = 'Master Program dibatalkan — tidak muncul lagi di pilihan Penjualan Program. Penjualan yang sudah terbentuk tidak terpengaruh.';
    if (qt && p.status === 'final') lockInfo = `Penjualan Program <b>Final</b> — terkunci. Buat <b>Quotation</b> dari menu Quotation (${b2b ? 'ke Company penjualan' : 'ke Company atau Personal'}) — persiapan invoice dengan peserta yang sudah fix.`;
    if (qt && p.status === 'batal') lockInfo = 'Penjualan Program dibatalkan dan tidak dapat diubah.';

    const header = `<div class="csec"><p class="csec-h">Header</p><div class="grid3">
      ${field(`No. ${qt ? 'penjualan' + QPJ08 : 'master program' + QMP02}`, `<input class="input" value="${esc(p.nomor || '')}" placeholder="otomatis saat disimpan" disabled>`)}
      ${field(qt ? 'Jenis penjualan' : 'Jenis', `<div class="static">${qt ? jenisTag(p.jenis) : '<span class="muted">Umum — Public/B2B dipilih di Penjualan Program</span>'}</div>`)}
      ${field('Status', `<div class="static">${stBadge(p.status)}${qt && p.status === 'final' ? ` <span class="muted">→ ${NEXT[p.jenis]}</span>` : ''}</div>`)}
    </div></div>`;

    let s1;
    if (!qt) {
      const paketOpts = db.paket.filter(x => x.aktif || x.id === p.paket)
        .map(x => `<option value="${x.id}" ${x.id === p.paket ? 'selected' : ''}>${esc(x.nomor)} — ${esc(x.judul)} · ${esc(findRec('jenisSertifikasi', x.sertifikasi).nama)}</option>`).join('');
      const paket = p.paket ? paketOf(p) : null;
      s1 = step(1, 'Informasi Program', '', `
        ${field('Paket Produk (dari Product Combine)' + req, `<select class="input" data-pf="paket" ${locked ? 'disabled' : ''}><option value="">— Pilih Paket Produk aktif —</option>${paketOpts}</select>${paket ? `<span class="hint">${ATTRS.map(a => `${a.label}: ${esc(findRec(a.key, paket[a.field]).nama)}`).join(' · ')}</span>` : ''}`, E.paket)}
        ${simInputs(p, locked, E, 'angka baseline; bisa disesuaikan per Penjualan Program tanpa mengubah master. Juga tampil di bagian 4.')}`);
    } else {
      const mp = mpOf(p);
      // B2B: Company dipilih dari Master Company (jawaban Q-PJ-18); Public tidak terikat company.
      const co = b2b ? field('Company' + req, `<select class="input" data-pf="company" ${locked ? 'disabled' : ''}><option value="">— Pilih Company aktif —</option>${db.company.filter(c => c.aktif || c.id === p.company)
        .map(c => `<option value="${c.id}" ${c.id === p.company ? 'selected' : ''}>${esc(c.kode)} — ${esc(c.nama)}${c.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>${companyOf(p)
        ? `<span class="hint">${esc(companyOf(p).jenis)} · ${esc(companyOf(p).kota)} · PIC ${esc(companyOf(p).picNama || '—')}</span>` : '<span class="hint">dari Master Company</span>'}`, E.company) : '';
      s1 = step(1, 'Informasi Penjualan', '', `
        <div class="grid3">
          ${field('Master Program', `<div class="static"><span class="code">${esc(mp.nomor)}</span> ${esc(paketOf(mp).judul)} <span class="muted">· ${esc(findRec('jenisSertifikasi', paketOf(mp).sertifikasi).nama)}</span></div>`)}
          ${co}
          ${field('Tanggal mulai' + req, `<input class="input" type="date" data-pf="tglMulai" value="${esc(p.tglMulai)}" ${locked ? 'disabled' : ''}>`, E.tglMulai)}
          ${field('Tanggal selesai' + req, `<input class="input" type="date" data-pf="tglSelesai" value="${esc(p.tglSelesai)}" ${locked ? 'disabled' : ''}><span class="hint">durasi = selesai − mulai + 1 hari</span>`, E.tglSelesai)}
          ${field('Waktu' + q('Q-PJ-21', 'Jam pelaksanaan dibutuhkan Penawaran & Formulir Konfirmasi (template: pukul 09.00-15.15 WIB). Usulan: teks bebas di Penjualan Program.'), `<input class="input" data-pf="waktu" value="${esc(p.waktu || '')}" maxlength="50" placeholder="mis. 09.00–15.15 WIB" ${locked ? 'disabled' : ''}><span class="hint">Jam pelaksanaan; tercetak di Penawaran & Formulir Konfirmasi</span>`)}
          ${field('Lokasi acara', `<input class="input" data-pf="lokasi" value="${esc(p.lokasi)}" maxlength="150" placeholder="mis. Offline Bekasi" ${locked ? 'disabled' : ''}>`)}
          ${trainerField(p, locked)}
        </div>
        ${simInputs(p, locked, E, `terisi dari ${esc(mp.nomor)}; perubahan hanya berlaku di penjualan ini. Juga tampil di bagian 4.`)}`);
    }

    const s2 = step(2, 'Fixed Cost', qt ? 'disalin dari Master Program; nominal, item, dan biaya lain dapat diubah; item bernilai 0 tidak disimpan. Qty: event = 1, day = durasi.' : 'semua item aktif Master Rate langsung tampil dengan Default Cost; klik × pada item yang tidak dipakai (dapat ditambah lagi lewat + Tambah); item bernilai 0 tidak disimpan. Qty: event = 1, day = durasi.',
      costTable(0, locked) + costTable(1, locked) + `<div id="pf-tot-fixed">${totalsFixed(p)}</div>`);
    const s3 = step(3, 'Variable Cost', 'pilih item yang dipakai saja, isi nominal per peserta; item bernilai 0 tidak disimpan. Qty otomatis dari jumlah peserta dan durasi.',
      costTable(2, locked) + costTable(3, locked) + `<div id="pf-tot-var">${totalsVar(p)}</div>`);
    const s4 = step(4, 'Simulasi Hasil', 'apakah penjualan ini untung, dan berapa titik impasnya',
      simInputs(p, locked, E, 'sama dengan bagian 1 — ubah di sini atau di bagian 1, keduanya ikut berubah') + `<div id="pf-sim" class="gap-t">${simFor(p)}</div>`);

    let actions = `<button type="button" class="btn btn-ghost" data-act="pf-back">${isNew ? 'Batal' : 'Kembali'}</button>`;
    if (kind === 'mp') {
      if (p.status === 'draft') actions += `${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="mp-cancel" data-id="${p.id}">Batalkan Program</button>`}
        <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
        ${isNew ? '' : '<button type="button" class="btn btn-accent" data-act="pf-confirm">Finalkan program</button>'}`;
      else if (p.status === 'final') actions += `<button type="button" class="btn btn-accent" data-act="qt-from-mp" data-id="${p.id}">Buat Penjualan Program</button>`;
    } else if (p.status === 'draft') {
      actions += `${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="qt-cancel" data-id="${p.id}">Batalkan</button>`}
        <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
        ${isNew ? '' : `<button type="button" class="btn btn-accent" data-act="pf-confirm">Finalkan → ${NEXT[p.jenis]}</button>`}`;
    } else if (p.status === 'final') {
      actions += `<button type="button" class="btn btn-accent" data-act="qt-print" data-id="${p.id}">Cetak Surat Penawaran</button>`;
    }

    const title = kind === 'mp' ? (isNew ? 'Buat Master Program' : `Master Program ${esc(p.nomor)}`) : (isNew ? `Buat Penjualan Program ${JENIS[p.jenis]}` : `Penjualan Program ${esc(p.nomor)}`);
    const ref = kind === 'mp' ? 'MP-FR-02 – MP-FR-10' : 'PJ-FR-04 – PJ-FR-10';
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button><h2>${title}</h2><span class="ref">${ref}</span></div>
      ${lockInfo ? `<div class="alert">${lockInfo}</div>` : ''}
      <div id="pf-warn" class="alert ${warns.length ? '' : 'hidden'}">${warns.map(esc).join('<br>')}</div>
      <div class="pub-form">${header}${s1}${s2}${s3}${s4}</div>
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
    $('#pf-tot-fixed').innerHTML = totalsFixed(p);
    $('#pf-tot-var').innerHTML = totalsVar(p);
    $('#pf-sim').innerHTML = simFor(p);
    [1, 2, 3, 4].forEach(n => { const el = $(`[data-sum="${n}"]`); if (el) el.innerHTML = stepSummary(n, p); });
    const w = costWarnings(p), box = $('#pf-warn');
    box.innerHTML = w.map(esc).join('<br>');
    box.classList.toggle('hidden', !w.length);
  }

  function openCostForm(kind, rec, preset) {
    const blank = kind === 'mp'
      // Master Program baru: semua item Fixed Cost aktif langsung terisi Default Cost; yang tidak dipakai dihapus dengan × (MP-FR-06).
      ? { id: null, nomor: null, status: 'draft', paket: '', durasi: 1, targetPeserta: 10, hargaJual: '', targetProfit: '', biaya: db.rate.fixed.filter(r => r.aktif).map(r => biayaFromRate(r)) }
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
    if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
    else if (kind === 'qt' && p.jenis === 'b2b' && Number(p.targetPeserta) < B2B_MIN) E.targetPeserta = `Penjualan B2B minimal ${B2B_MIN} peserta`;
    if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual / peserta wajib diisi';
    if (kind === 'qt' && p.jenis === 'b2b' && !p.company) E.company = 'Pilih Company';
    if (kind === 'qt' && !p.tglMulai) E.tglMulai = 'Tanggal mulai wajib diisi';
    if (kind === 'qt' && !p.tglSelesai) E.tglSelesai = 'Tanggal selesai wajib diisi';
    else if (kind === 'qt' && p.tglMulai && p.tglSelesai < p.tglMulai) E.tglSelesai = 'Tanggal selesai sebelum tanggal mulai';
    p.biaya.forEach((b, i) => {
      if (b.manual && !String(b.item).trim()) E['b' + i] = 'Nama biaya lain wajib diisi';
      else if (b.harga === '' || b.harga < 0) E['b' + i] = `Nominal ${b.item} wajib diisi`;
    });
    state.pf.errors = E;
    return !Object.keys(E).length;
  }

  function saveCost(silent) {
    const p0 = state.pf.p, nAwal = p0.biaya.length;
    p0.biaya = p0.biaya.filter(b => !(b.harga !== '' && Number(b.harga) === 0));
    const dibuang = nAwal - p0.biaya.length;
    if (!validateCost()) {
      render();
      toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn');
      return null;
    }
    const pf = state.pf, list = pf.kind === 'mp' ? db.mprog : db.quot;
    let rec;
    if (pf.isNew) {
      rec = Object.assign(clone(pf.p), { id: uid(), nomor: docNo(pf.kind === 'mp' ? 'MP' : PJ_PREFIX[pf.p.jenis]) });
      list.push(rec);
    } else {
      rec = list.find(x => x.id === pf.p.id);
      Object.assign(rec, clone(pf.p));
    }
    state.pf = { kind: pf.kind, p: clone(rec), isNew: false, errors: {}, collapsed: pf.collapsed };
    render();
    if (!silent) toast(`${pf.kind === 'mp' ? 'Master Program' : 'Penjualan Program'} ${rec.nomor} tersimpan${rec.status === 'draft' ? ' sebagai Draft' : ''}${dibuang ? ` · ${dibuang} item bernilai 0 dibuang` : ''}`);
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
      body: `<p>Setelah dikonfirmasi, program menjadi <b>Final</b>: angka menjadi baseline resmi, terkunci, dan muncul sebagai pilihan di Penjualan Program.</p>
        <div class="summary">${sumRow('Program', `${esc(rec.nomor)} — ${esc(paketOf(rec).judul)}`)}${sumRow('Durasi', `${rec.durasi} hari`)}${sumRow('Target peserta', `${rec.targetPeserta} peserta`)}
        ${sumRow('Harga jual / peserta', rp(rec.hargaJual))}${sumRow('HPP', rp(c.hpp))}${sumRow('Profit', rp(c.profit))}
        ${sumRow('BEP', c.bepPeserta === null ? 'tidak tercapai' : `${c.bepPeserta} peserta · ${rp(c.bepHarga)} / peserta`)}</div>${warnBox(w)}`,
      saveLabel: 'Ya, finalkan',
      onSave: () => { rec.status = 'final'; syncPf(rec); toast(`Master Program ${rec.nomor} Final — tersedia di pilihan Penjualan Program`); }
    });
  }

  function mpCancel(rec) {
    const used = db.quot.filter(x => x.mp === rec.id).length;
    openModal({
      title: 'Batalkan Master Program',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(rec.nomor)} — ${esc(paketOf(rec).judul)}</b>? Program tidak akan muncul lagi di pilihan Penjualan Program.
        ${used ? `<br><span class="muted">${used} Penjualan Program yang sudah terbentuk dari program ini tidak terpengaruh.</span>` : ''}</p>`,
      saveLabel: 'Batalkan Program', danger: true,
      onSave: () => { rec.status = 'batal'; syncPf(rec); toast(`Master Program ${rec.nomor} dibatalkan`); }
    });
  }

  // ---------- Status Penjualan Program (PJ-FR-14 – PJ-FR-17) ----------
  function qtFinal(rec) {
    const c = calc(rec), w = [];
    if (c.profit < 0) w.push('Rugi — pendapatan belum menutup HPP.');
    if (c.bepPeserta === null) w.push('Titik impas tidak tercapai.');
    else if (c.bepPeserta > c.target) w.push(`Titik impas ${c.bepPeserta} peserta, di atas ${c.target} peserta.`);
    const rows = sumRow('Durasi', `${rec.durasi} hari`) + sumRow('Target peserta', `${c.target} peserta`)
      + sumRow('Harga jual', `${rp(c.harga)} / peserta`)
      + sumRow('Pendapatan', rp(c.pendapatan)) + sumRow('HPP', rp(c.hpp)) + sumRow('Profit', rp(c.profit))
      + sumRow('Titik impas', c.bepPeserta === null ? 'tidak tercapai' : `${c.bepPeserta} peserta`);
    openModal({
      title: 'Finalkan Penjualan Program',
      body: `<p>Setelah dikonfirmasi, Penjualan Program menjadi <b>Final</b> dan terkunci, lalu masuk ke <b>${NEXT[rec.jenis]}</b> untuk persiapan invoice (modul berikutnya, belum dibuat). Pastikan angka peserta dan harga sudah final.</p>
        <div class="summary">${sumRow('Penjualan', `${esc(rec.nomor)} — ${esc(paketOf(mpOf(rec)).judul)} (${JENIS[rec.jenis]})`)}${rec.jenis === 'b2b' ? sumRow('Company', esc(companyOf(rec).nama)) : ''}${rows}</div>${warnBox(w)}`,
      saveLabel: 'Ya, finalkan',
      onSave: () => { rec.status = 'final'; syncPf(rec); toast(`Penjualan Program ${rec.nomor} Final — siap ${NEXT[rec.jenis]}`); }
    });
  }

  // Surat penawaran dari Penjualan Program Final (PJ-FR-20); isi surat usulan analis, tujuan surat Q-PJ-18.
  function qtPrint(rec) {
    const c = calc(rec), mp = mpOf(rec), pk = paketOf(mp);
    openModal({
      title: 'Surat Penawaran', wide: true,
      body: `<div class="letter">
        <p><b>SURAT PENAWARAN</b><br><span class="muted">No. ${esc(rec.nomor)} ${q('Q-PJ-08', 'Format nomor belum ditetapkan.')}</span></p>
        <p>Kepada: ${rec.jenis === 'b2b' ? `<b>${esc(companyOf(rec).nama)}</b><br><span class="muted">u.p. ${esc(companyOf(rec).picNama || '—')}${companyOf(rec).picJabatan ? `, ${esc(companyOf(rec).picJabatan)}` : ''} · ${esc(companyOf(rec).alamat)}, ${esc(companyOf(rec).kota)}</span>` :`<span class="muted">calon peserta</span>`}</p>
        <p>Dengan hormat, bersama ini kami menawarkan program berikut:</p>
        <div class="summary">${sumRow('Program', `${esc(pk.judul)} <span class="muted">(${esc(findRec('jenisSertifikasi', pk.sertifikasi).nama)})</span>`)}
          ${sumRow('Pelaksanaan', `${fmtRange(rec.tglMulai, rec.tglSelesai)} · ${esc(rec.lokasi || '—')}`)}${sumRow('Durasi', `${rec.durasi} hari`)}${rec.trainer ? sumRow('Trainer', esc((trainerById(rec.trainer) || {}).nama || '—')) : ''}
          ${sumRow('Harga / peserta', rp(c.harga))}${sumRow('Jumlah peserta', `${c.target} peserta`)}${sumRow('Total', rp(c.pendapatan))}</div>
        <p class="muted">Angka diambil dari Penjualan Program Final dan tidak dapat diubah di sini.</p>
      </div>`,
      saveLabel: 'Cetak',
      onSave: () => { logAct('Penjualan Program', 'Penjualan Program', 'Cetak', rec.nomor, rec.id, `Cetak Surat Penawaran ${rec.nomor}`); toast(`Surat penawaran ${rec.nomor} dicetak (mockup)`); }
    });
  }

  function qtCancel(rec) {
    openModal({
      title: 'Batalkan Penjualan Program',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(rec.nomor)}</b>? Penjualan Program Batal tidak dapat diubah.</p>`,
      saveLabel: 'Batalkan', danger: true,
      onSave: () => { rec.status = 'batal'; syncPf(rec); toast(`Penjualan Program ${rec.nomor} dibatalkan`); }
    });
  }

  // ---------- Dashboard halaman depan (FR-40 – FR-42, usulan analis: input/Master - Buat Program/2026-10-28-dashboard-depan/) ----------
  const todayIso = (d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`)(new Date());

  function viewDashboard() {
    const finals = db.quot.filter(x => x.status === 'final');
    const sum = (list, f) => list.reduce((s, x) => s + f(x), 0);
    const pend = sum(finals, x => calc(x).pendapatan), prof = sum(finals, x => calc(x).profit), pes = sum(finals, x => nPeserta(x));
    const margin = pend ? (prof / pend * 100).toFixed(1).replace('.', ',') : '0';
    const drafts = db.mprog.filter(x => x.status === 'draft').length + db.quot.filter(x => x.status === 'draft').length;
    const byJenis = j => finals.filter(x => x.jenis === j);
    const kpi = (k, cls, l, v, sub) => `<div class="kpi dash-link ${cls}" data-act="dash-kpi" data-k="${k}" role="button" tabindex="0" title="Klik untuk melihat rincian">
      <div class="kpi-l">${l} <span class="kpi-more">rincian ›</span></div><div class="kpi-v">${v}</div><div class="kpi-s">${sub}</div></div>`;
    const kpis = `<div class="kpis">
      ${kpi('final', '', 'Total Penjualan Program Final', finals.length, `Public ${byJenis('public').length} · B2B ${byJenis('b2b').length}`)}
      ${kpi('pend', '', 'Total pendapatan rencana', rp(pend), `${pes} peserta = total target peserta dari ${finals.length} Penjualan Final`)}
      ${kpi('prof', prof < 0 ? 'bad' : 'good', 'Total profit rencana', rp(prof), `Pendapatan − biaya (HPP) · margin ${margin}%`)}
      ${kpi('draft', drafts ? 'kwarn' : '', 'Total masih Draft', drafts, 'Master Program + Penjualan Program belum Final')}
    </div>`;

    // Jadwal pelaksanaan terdekat: hanya Penjualan Program Final yang belum lewat (input/Master - Buat Program/2026-10-28-dashboard-jadwal-final/).
    const upcoming = db.quot.filter(x => x.status === 'final' && x.tglSelesai && x.tglSelesai >= todayIso)
      .sort((a, b) => a.tglMulai.localeCompare(b.tglMulai)).slice(0, 5);
    const jadwal = upcoming.length ? `<div class="table-wrap"><table class="table"><thead><tr><th>Tanggal</th><th>Program</th><th>Jenis</th><th class="money">Peserta</th></tr></thead><tbody>
      ${upcoming.map(x => `<tr class="dash-link" data-act="qt-open" data-id="${x.id}">
        <td class="nowrap">${fmtRange(x.tglMulai, x.tglSelesai)}</td>
        <td class="title">${esc(paketOf(mpOf(x)).judul)}<br><span class="muted">${esc(x.nomor)} · ${esc(x.jenis === 'b2b' && companyOf(x) ? companyOf(x).nama : x.lokasi || '—')}</span></td>
        <td>${jenisTag(x.jenis)}</td><td class="money">${nPeserta(x)}</td></tr>`).join('')}
      </tbody></table></div>` : '<p class="dash-empty">Belum ada Penjualan Program Final yang akan dilaksanakan.</p>';

    // Perlu tindakan: Draft yang belum Final, dan penjualan yang rugi / titik impas di atas rencana.
    const todo = [];
    db.mprog.filter(x => x.status === 'draft').forEach(x => todo.push(['mp-open', x.id, x.nomor, paketOf(x).judul, 'Master Program masih Draft — finalkan agar bisa dijual']));
    db.quot.filter(x => x.status !== 'batal').forEach(x => {
      const c = calc(x), judul = paketOf(mpOf(x)).judul;
      if (x.status === 'draft') todo.push(['qt-open', x.id, x.nomor, judul, 'Penjualan Program masih Draft']);
      if (c.pendapatan && c.profit < 0) todo.push(['qt-open', x.id, x.nomor, judul, `Rugi ${rp(c.profit)}`, true]);
      else if (c.bepPeserta === null || c.bepPeserta > c.target) todo.push(['qt-open', x.id, x.nomor, judul, c.bepPeserta === null ? 'Titik impas tidak tercapai' : `Titik impas ${c.bepPeserta} peserta, rencana ${c.target}`, true]);
    });
    const tindakan = todo.length ? `<ul class="dash-list">${todo.slice(0, 8).map(([act, id, no, judul, msg, warn]) =>
      `<li class="dash-link" data-act="${act}" data-id="${id}"><span class="code">${esc(no)}</span> ${esc(judul)}<br><span class="${warn ? 'neg' : 'muted'}">${esc(msg)}</span></li>`).join('')}</ul>
      ${todo.length > 8 ? `<p class="dash-empty">+${todo.length - 8} lainnya</p>` : ''}` : '<p class="dash-empty">Tidak ada yang perlu ditindaklanjuti.</p>';

    const card = (title, body, sub = '') => `<section class="card dash-card"><h2>${title}${sub ? ` <span class="muted">${sub}</span>` : ''}</h2>${body}</section>`;
    return `${pageHead('Dashboard', 'Ringkasan Master Program dan Penjualan Program. Angka adalah total seluruh Penjualan Program Final (rencana).', 'FR-40 – FR-42')}
      ${kpis}
      <div class="dash-grid">
        ${card('Jadwal pelaksanaan terdekat', jadwal, 'Penjualan Program Final · klik baris untuk membuka')}
        ${card('Perlu tindakan', tindakan)}
      </div>`;
  }

  // Rincian kartu Dashboard: dari mana angkanya, per data, dengan baris total (FR-41).
  function dashKpi(k) {
    const finals = db.quot.filter(x => x.status === 'final');
    const sum = (list, f) => list.reduce((t, x) => t + f(x), 0);
    const pj = x => `<span class="code">${esc(x.nomor)}</span> ${esc(paketOf(mpOf(x)).judul)}<br><span class="muted">${jenisTag(x.jenis)} ${esc(x.jenis === 'b2b' && companyOf(x) ? companyOf(x).nama : x.lokasi || '—')} · ${fmtRange(x.tglMulai, x.tglSelesai)}</span>`;
    const row = (act, id, cells) => `<tr class="dash-link" data-act="dash-open" data-to="${act}" data-id="${id}">${cells}</tr>`;
    const tbl = (head, rows, foot = '') => rows.length
      ? `<div class="table-wrap"><table class="table dash-prev"><thead><tr>${head}</tr></thead><tbody>${rows.join('')}</tbody>${foot ? `<tfoot><tr>${foot}</tr></tfoot>` : ''}</table></div>`
      : '<p class="dash-empty">Belum ada data.</p>';
    const src = t => `<p class="dash-src"><b>Sumber data:</b> ${t}</p>`;
    const FINAL_SRC = 'menu <b>Daftar Penjualan Program</b>, hanya yang berstatus <b>Final</b> (Draft dan Batal tidak dihitung). Angka rencana = isian Penjualan Program saat difinalkan.';
    const V = {
      final: () => ['Total Penjualan Program Final', src(FINAL_SRC) + tbl('<th>Penjualan Program</th><th class="money">Target peserta</th>',
        finals.map(x => row('qt', x.id, `<td>${pj(x)}</td><td class="money">${nPeserta(x)}</td>`)),
        `<td><b>Total ${finals.length} penjualan</b> <span class="muted">(Public ${finals.filter(x => x.jenis === 'public').length} · B2B ${finals.filter(x => x.jenis === 'b2b').length})</span></td><td class="money"><b>${sum(finals, nPeserta)}</b></td>`)],
      pend: () => ['Total pendapatan rencana', src(FINAL_SRC) + '<p class="dash-src"><b>Rumus:</b> pendapatan = harga jual / peserta × target peserta, lalu dijumlah semua penjualan.</p>' + tbl(
        '<th>Penjualan Program</th><th class="money">Harga jual / peserta</th><th class="money">Target peserta</th><th class="money">Pendapatan</th>',
        finals.map(x => { const c = calc(x); return row('qt', x.id, `<td>${pj(x)}</td><td class="money">${rp(c.harga)}</td><td class="money">× ${c.target}</td><td class="money">${rp(c.pendapatan)}</td>`); }),
        `<td colspan="2"><b>Total</b></td><td class="money"><b>${sum(finals, nPeserta)}</b></td><td class="money"><b>${rp(sum(finals, x => calc(x).pendapatan))}</b></td>`)],
      prof: () => { const pd = sum(finals, x => calc(x).pendapatan), pf = sum(finals, x => calc(x).profit);
        return ['Total profit rencana', src(FINAL_SRC) + '<p class="dash-src"><b>Rumus:</b> profit = pendapatan − HPP. HPP = Fixed Cost + Variable Cost (bagian 2 & 3 di form Penjualan Program). Margin = profit ÷ pendapatan.</p>' + tbl(
          '<th>Penjualan Program</th><th class="money">Pendapatan</th><th class="money">Fixed Cost</th><th class="money">Variable Cost</th><th class="money">Profit</th><th class="money">Margin</th>',
          finals.map(x => { const c = calc(x); return row('qt', x.id, `<td>${pj(x)}</td><td class="money">${rp(c.pendapatan)}</td><td class="money">− ${rp(c.fixed)}</td><td class="money">− ${rp(c.totalVar)}</td>
            <td class="money ${c.profit < 0 ? 'neg' : 'pos'}">${rp(c.profit)}</td><td class="money">${c.pendapatan ? (c.profit / c.pendapatan * 100).toFixed(1).replace('.', ',') + '%' : '—'}</td>`); }),
          `<td><b>Total</b></td><td class="money"><b>${rp(pd)}</b></td><td class="money"><b>− ${rp(sum(finals, x => calc(x).fixed))}</b></td><td class="money"><b>− ${rp(sum(finals, x => calc(x).totalVar))}</b></td>
           <td class="money ${pf < 0 ? 'neg' : 'pos'}"><b>${rp(pf)}</b></td><td class="money"><b>${pd ? (pf / pd * 100).toFixed(1).replace('.', ',') + '%' : '—'}</b></td>`)]; },
      draft: () => { const mp = db.mprog.filter(x => x.status === 'draft'), qt = db.quot.filter(x => x.status === 'draft');
        return ['Total masih Draft', src('menu <b>Daftar Master Program</b> dan <b>Daftar Penjualan Program</b> yang berstatus <b>Draft</b> (belum difinalkan). Klik baris untuk melanjutkan.') + tbl(
          '<th>Data</th><th>Jenis data</th>',
          mp.map(x => row('mp', x.id, `<td><span class="code">${esc(x.nomor)}</span> ${esc(paketOf(x).judul)}</td><td>Master Program</td>`))
            .concat(qt.map(x => row('qt', x.id, `<td>${pj(x)}</td><td>Penjualan Program</td>`))),
          `<td><b>Total ${mp.length + qt.length}</b></td><td class="muted">Master Program ${mp.length} · Penjualan Program ${qt.length}</td>`)]; }
    };
    const [title, body] = V[k]();
    openModal({ title: `Rincian — ${title}`, body: body + '<p class="dash-src muted">Klik baris untuk membuka datanya.</p>', wide: 'x', info: true });
  }

  // ---------- Quotation (QTN-FR-01 – QTN-FR-13; input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/, tampilan acuan input/labmania.html) ----------
  // Penjualan Program Final (Public & B2B) → Quotation. Public berkali-kali, B2B satu quotation aktif (usulan).
  // Tujuan: Company (peserta dari company yang sama) atau Personal (satu atau lebih peserta, peserta baru langsung masuk Master Peserta).
  // Harga per peserta dari Penjualan Program, diskon Rupiah per peserta. Aksi: Kirim dan Cetak, Deal, Batalkan, Confirmation Letter.
  const QTN_STATUS = { draft: 'Draft', terkirim: 'Terkirim', deal: 'Deal', batal: 'Batal' };
  const PS_STATUS = { menunggu: 'Menunggu', deal: 'Deal', batal: 'Batal' };
  const TUJUAN = { company: 'Company', personal: 'Personal' };
  const SUMBER = ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya'];
  const PPN_RATE = 11;
  const QQTN = q('Q-QTN-01', 'Format nomor dari Template Penawaran: LM-I/AM/PW/MM/YY-XXX. Arti segmen AM dan reset urutan (per bulan/tahun) belum dijelaskan; mockup: AM tetap, urutan berlanjut.');
  // Nomor quotation (input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/): LM-I/AM/PW/MM/YY-XXX.
  const QTN_MMYY = (d => `${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getFullYear()).slice(2)}`)(new Date());
  const qtnNo = (peek = false) => { const n = (codeSeq.QTN || 0) + 1; if (!peek) codeSeq.QTN = n; return `LM-I/AM/PW/${QTN_MMYY}-${String(n).padStart(3, '0')}`; };
  const QDISK = q('Q-QTN-06', 'Diskon Rupiah per peserta (usulan, mengikuti labmania.html) atau total per quotation?');
  const QALLIN = q('Q-QTN-15', 'All-in hanya untuk B2B dan boleh diskon (terjawab; mockup: Rupiah total). Masih terbuka: di Penawaran dicetak satu baris paket (usulan)? Invoice B2B dapat dipecah per peserta = (all-in − diskon) ÷ peserta.');
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
  // harga / peserta setara = (all-in − diskon) ÷ peserta, juga dasar pecah invoice per peserta.
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
    const seed = o => { const k = Object.assign({ id: uid(), nomor: qtnNo(), sales, sumber: 'Sales', ppn: true, diskon: 0, lain: 0, up: '', alamat: '' }, o); k.ket = k.ket || ketDefault(pjOfQtn(k).jenis); k.kf = k.kf || kfNew(kfDefaultFor(pjOfQtn(k))); db.qtn.push(k); };
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

  // ---------- Quotation: daftar per Penjualan Program & detail (QTN-FR-01 – QTN-FR-03, tampilan input/labmania.html) ----------
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

  function qtnGear(k) {
    const items = [];
    if (k.status === 'draft') items.push(['qtn-edit', 'Edit'], ['qtn-print', 'Kirim dan Cetak Quotation'], ['qtn-cancel', 'Batalkan', true]);
    if (k.status === 'terkirim') items.push(['qtn-deal', 'Deal'], ['qtn-print', 'Kirim dan Cetak Quotation'], ['qtn-cancel', 'Batalkan', true]);
    // B2B (in-house) tanpa formulir konfirmasi → tanpa Confirmation Letter (usulan, Q-QTN-17).
    if (k.status === 'deal') items.push(...(isB2bQtn(k) ? [] : [['qtn-cl', 'Confirmation Letter']]), ['qtn-print', 'Cetak Quotation']);
    if (k.status === 'batal') items.push(['qtn-print', 'Lihat']);
    return gearMenu(k, items);
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
      { label: 'Quotation' + QQTN, cls: 'nowrap', render: k => `<span class="code">${esc(k.nomor)}</span><br><span class="muted">${fmtTgl(k.tanggal)}${k.berlaku ? ` s.d. ${fmtTgl(k.berlaku)}` : ''}<br>Sales: ${esc((salesOf(k) || {}).nama || '—')}${k.sumber ? `, ${esc(k.sumber)}` : ''}</span>` },
      { label: 'Kepada', render: k => `${tujuanTag(k.tujuan)} ${esc(kepadaOf(k))}${k.up ? `<br><span class="muted">u.p. ${esc(k.up)}</span>` : ''}` },
      { label: 'Peserta', render: k => `<b>${k.peserta.length}</b> <span class="muted">peserta</span><br>${k.peserta.map(r => { const p = pesertaById(r.id) || {}, st = k.status === 'deal' ? r.st : k.status === 'batal' ? 'batal' : k.status === 'terkirim' ? 'menunggu' : 'draft';
        return `<span class="ps-chip ps-${st}" title="${esc(pesertaCo(p))}">${esc(p.nama || '—')} <span class="muted">· ${esc(pesertaCo(p))}</span></span>`; }).join(' ')}` },
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
        ${field('Nomor Quotation' + QQTN, `<input class="input" value="${esc(d.nomor || qtnNo(true))}" readonly>`)}
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
    const k = Object.assign({ nomor: d.nomor || qtnNo(true), status: 'draft' }, qtnData(d));
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
          const k = Object.assign({ id: uid(), nomor: qtnNo(), status: 'draft' }, data);
          db.qtn.push(k); state.page = 1;
          toast(`Quotation ${k.nomor} tersimpan sebagai Draft`);
        }
        if (state.view !== 'qtn') state.view = 'qtn';
        state.qtnPj = d.pj; state.status = 'all';
      }
    });
  }

  // ---------- Quotation: PDF cetak (QTN-FR-10, QTN-FR-14 – QTN-FR-16; input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/) ----------
  // Satu PDF = Penawaran (input/Template Penawaran Labmania.pdf) + Syarat & Ketentuan (input/SYARAT & KETENTUAN ... .pdf)
  // + Formulir Konfirmasi per peserta (input/Tamplate Formulir Konfirmasi Workshop.doc).
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
        <div>${tglPanjang(k.tanggal)}<br>${esc(s.dept || s.div || 'Divisi')}<div class="pdf-ttd"></div><b>${esc(s.nama || '—')}</b></div>
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
      body: `<p class="hint top">${esc(kepadaOf(k))} · ${esc(paketOf(mpOf(pj)).judul)} (${esc(pj.nomor)}). Centang peserta yang <b>deal</b>; yang tidak dicentang menjadi <b>Batal</b> dan dapat dibuatkan quotation lain. Peserta Deal masuk sebagai peserta aktual (persiapan invoice)${isB2bQtn(k) ? `. Penjualan B2B: minimal ${B2B_MIN} peserta deal; invoice dapat dipecah per peserta bila client minta` : " dan dapat dibuatkan <b>Confirmation Letter</b>"}.${q('Q-QTN-07', 'Deal untuk seluruh quotation, atau tetap memilih peserta yang deal? Usulan: centang, default semua.')}</p>
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

  // ---------- Modal, validation, toast ----------
  function openModal({ title, body, onSave, onMount, saveLabel = 'Simpan', cancelLabel = 'Batal', wide = false, danger = false, info = false }) {
    // wide: true = 780px, 'x' = 1080px (rincian Dashboard).
    const root = $('#modal-root');
    root.innerHTML = `<div class="overlay" data-act="modal-close">
      <form class="modal ${wide === 'x' ? 'xwide' : wide ? 'wide' : ''}" novalidate role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <header><h2 id="modal-title">${title}</h2><button type="button" class="x" data-act="modal-close" aria-label="Tutup">×</button></header>
        <div class="modal-body">${body}</div>
        <footer>${info ? '<button type="button" class="btn btn-primary" data-act="modal-close">Tutup</button>' : `<button type="button" class="btn btn-ghost" data-act="modal-close">${cancelLabel}</button>
          <button type="submit" class="btn ${danger ? 'btn-danger' : 'btn-primary'}">${saveLabel}</button>`}</footer>
      </form></div>`;
    const form = $('form', root);
    form.addEventListener('submit', e => {
      e.preventDefault();
      form.querySelectorAll('.has-err').forEach(el => el.classList.remove('has-err'));
      form.querySelectorAll('.err').forEach(el => el.remove());
      if (!onSave || onSave(form.elements) === false) {
        const first = form.querySelector('.has-err .input');
        if (first) first.focus();
        return;
      }
      closeModal();
      render();
    });
    if (onMount) onMount(form);
    const first = form.querySelector('.input:not([readonly]):not([disabled])') || form.querySelector('[type=submit]') || form.querySelector('footer .btn');
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

  // ---------- Jejak data & Activity Log (LOG-FR-01 – LOG-FR-08; input/Master - Buat Program/2026-10-30-histori-activity-log/) ----------
  // Setiap record menyimpan Dibuat oleh/tanggal-jam dan Diubah oleh/tanggal-jam. Setiap perubahan data (tambah, edit, hapus,
  // aktif/nonaktif, finalkan, batal, kirim, deal) tercatat di Activity Log beserta field sebelum/sesudah.
  // Mockup: perubahan dideteksi dengan membandingkan data sebelum & sesudah setiap aksi; di aplikasi dicatat write_log() di server.
  const AUDIT_SRC = [
    ['jenisProduk', 'Master Data', 'Jenis Produk'], ['jenisSertifikasi', 'Master Data', 'Jenis Sertifikat'], ['jenisBidang', 'Master Data', 'Jenis Bidang'],
    ['paket', 'Master Data', 'Product Combine'], ['peserta', 'Master Data', 'Master Peserta'], ['company', 'Master Data', 'Master Company'],
    ['trainer', 'Master Data', 'Master Trainer'], ['ketQtn', 'Master Data', 'Template Keterangan Quotation'], ['kfTpl', 'Master Data', 'Template Formulir Konfirmasi'],
    ['rate.fixed', 'Master Data', 'Master Rate — Fixed Cost'], ['rate.variable', 'Master Data', 'Master Rate — Variable Cost'], ['user', 'Master Data', 'Master User'],
    ['mprog', 'Penjualan Program', 'Master Program'], ['quot', 'Penjualan Program', 'Penjualan Program'], ['qtn', 'Quotation', 'Quotation']
  ];
  const STAMP_KEYS = ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
  const LOG_AKSI = ['Tambah', 'Edit', 'Hapus', 'Aktifkan', 'Nonaktifkan', 'Finalkan', 'Batalkan', 'Kirim & Cetak', 'Deal', 'Cetak'];
  const STATUS_AKSI = { final: 'Finalkan', batal: 'Batalkan', terkirim: 'Kirim & Cetak', deal: 'Deal' };
  const FIELD_LBL = { nama: 'Nama', kode: 'Kode', nomor: 'Nomor', judul: 'Judul', aktif: 'Status aktif', status: 'Status', item: 'Item', cost: 'Default Cost',
    hargaJual: 'Harga jual / peserta', targetPeserta: 'Target peserta', targetProfit: 'Target profit', durasi: 'Durasi', biaya: 'Item biaya', peserta: 'Peserta',
    diskon: 'Diskon', lain: 'Biaya lain-lain', allin: 'Harga all-in', skema: 'Skema harga', ppn: 'PPN', tanggal: 'Tanggal', berlaku: 'Berlaku sampai',
    tglMulai: 'Tanggal mulai', tglSelesai: 'Tanggal selesai', lokasi: 'Lokasi', waktu: 'Waktu', company: 'Company', instansi: 'Instansi', telp: 'Telepon', email: 'Email',
    alamat: 'Alamat', up: 'u.p.', sales: 'Sales', tujuan: 'Tujuan', ket: 'Keterangan', kf: 'Formulir Konfirmasi', teks: 'Teks', def: 'Default' };
  db.logs = [];
  const pad2 = n => String(n).padStart(2, '0');
  const isoNow = (d = new Date()) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
  const fmtWaktu = s => { if (!s) return '—'; const [d, t] = s.split(' '); const [y, m, dd] = d.split('-'); return `${dd}-${m}-${y} ${t.slice(0, 5)}`; };
  // Pengguna yang sedang login (mockup: dapat diganti di kaki sidebar untuk mencoba).
  const loginUsers = () => db.user.filter(u => u.akun && u.aktif).length ? db.user.filter(u => u.akun && u.aktif) : db.user;
  state.me = (loginUsers()[0] || {}).id;
  const meName = () => (db.user.find(u => u.id === state.me) || {}).nama || 'Pengguna';
  const auditList = key => key.startsWith('rate.') ? db.rate[key.slice(5)] : db[key];
  const refOf = r => r.nomor || r.kode || r.nama || r.judul || r.item || (r.teks ? String(r.teks).slice(0, 40) : `#${r.id}`);
  const bare = r => { const o = {}; Object.keys(r).forEach(k => { if (!STAMP_KEYS.includes(k)) o[k] = r[k]; }); return o; };
  const showVal = v => v === true ? 'Ya' : v === false ? 'Tidak' : v === '' || v == null ? '—'
    : Array.isArray(v) ? `${v.length} baris` : typeof v === 'object' ? 'diubah' : typeof v === 'number' && v >= 1000 ? fmtRibu(v) : String(v);
  function fieldChanges(o, n) {
    const keys = [...new Set([...Object.keys(o || {}), ...Object.keys(n || {})])].filter(k => !STAMP_KEYS.includes(k) && k !== 'id');
    return keys.filter(k => JSON.stringify((o || {})[k]) !== JSON.stringify((n || {})[k])).map(k => {
      const a = (o || {})[k], b = (n || {})[k];
      const same = Array.isArray(a) && Array.isArray(b) && a.length === b.length;
      return { f: FIELD_LBL[k] || k, old: o ? (same ? `${a.length} baris` : showVal(a)) : '', new: n ? (same ? `${b.length} baris (isi berubah)` : showVal(b)) : '' };
    });
  }
  function logAct(modul, sub, aksi, ref, refId, ket, changes = [], when = isoNow(), by = meName()) {
    db.logs.push({ id: uid(), waktu: when, user: by, modul, sub, aksi, ref, refId, ket, changes });
  }
  let auditSnap = null;
  function auditRun(seedTimes) {
    const snap = {};
    let t = 0;
    AUDIT_SRC.forEach(([key, modul, sub]) => {
      const m = snap[key] = new Map();
      (auditList(key) || []).forEach(r => {
        const j = JSON.stringify(bare(r)); m.set(r.id, j);
        if (!auditSnap) {
          // Data contoh: dianggap dibuat Admin Sistem pada tanggal-tanggal sebelum hari ini.
          const when = seedTimes(t++);
          Object.assign(r, { createdBy: 'Admin Sistem', createdAt: when, updatedBy: '', updatedAt: '' });
          logAct(modul, sub, 'Tambah', refOf(r), r.id, `Tambah ${sub} ${refOf(r)}`, fieldChanges(null, bare(r)).slice(0, 12), when, 'Admin Sistem');
          return;
        }
        const prev = auditSnap[key].get(r.id);
        if (prev === undefined) {
          Object.assign(r, { createdBy: meName(), createdAt: isoNow(), updatedBy: '', updatedAt: '' });
          logAct(modul, sub, 'Tambah', refOf(r), r.id, `Tambah ${sub} ${refOf(r)}`, fieldChanges(null, bare(r)).slice(0, 12));
        } else if (prev !== j) {
          const o = JSON.parse(prev), n = bare(r);
          const ch = fieldChanges(o, n), keys = Object.keys(n).filter(k => JSON.stringify(o[k]) !== JSON.stringify(n[k]));
          const aksi = keys.includes('status') && STATUS_AKSI[n.status] ? STATUS_AKSI[n.status]
            : keys.length === 1 && keys[0] === 'aktif' ? (n.aktif ? 'Aktifkan' : 'Nonaktifkan') : 'Edit';
          Object.assign(r, { updatedBy: meName(), updatedAt: isoNow() });
          logAct(modul, sub, aksi, refOf(r), r.id, `${aksi} ${sub} ${refOf(r)}`, ch);
        }
      });
      if (auditSnap) auditSnap[key].forEach((j, id) => {
        if (!m.has(id)) { const o = JSON.parse(j); logAct(modul, sub, 'Hapus', refOf(o), id, `Hapus ${sub} ${refOf(o)}`, fieldChanges(o, null).slice(0, 12)); }
      });
    });
    auditSnap = snap;
  }
  // Data contoh: waktu dibuat menyebar 20–29 Oktober 2026, berurutan.
  auditRun(i => { const d = new Date(2026, 9, 20, 8, 0, 0); d.setMinutes(d.getMinutes() + i * 97); return isoNow(d); });

  // Kolom "Dibuat / Diubah" di setiap daftar (LOG-FR-02).
  const HIST_COL = { label: 'Dibuat / Diubah', cls: 'hist', render: r => r.createdAt
    ? `<span class="hist-l">Dibuat <b>${esc(r.createdBy)}</b><br>${fmtWaktu(r.createdAt)}</span>${r.updatedAt ? `<span class="hist-l">Diubah <b>${esc(r.updatedBy)}</b><br>${fmtWaktu(r.updatedAt)}</span>` : ''}`
    : '<span class="muted">—</span>' };

  // ---------- Activity Log (LOG-FR-03 – LOG-FR-07) ----------
  state.logF = { modul: 'all', aksi: 'all', user: 'all', from: '', to: '', refId: null, ref: '' };
  const aksiBadge = a => `<span class="badge log-${a === 'Tambah' ? 'add' : a === 'Hapus' || a === 'Batalkan' || a === 'Nonaktifkan' ? 'del' : a === 'Edit' ? 'edit' : 'other'}">${esc(a)}</span>`;
  function viewActLog() {
    const F = state.logF;
    const mods = [...new Set(AUDIT_SRC.map(s => s[1]))];
    const users = [...new Set(db.logs.map(l => l.user))].sort();
    const filters = selectFilter('lf-modul', 'Filter modul', F.modul, [['all', 'Semua modul'], ...mods.map(m => [m, m])])
      + selectFilter('lf-aksi', 'Filter aksi', F.aksi, [['all', 'Semua aksi'], ...LOG_AKSI.map(a => [a, a])])
      + selectFilter('lf-user', 'Filter pengguna', F.user, [['all', 'Semua pengguna'], ...users.map(u => [u, u])])
      + `<label class="lf-date">Dari <input type="date" class="input sm" id="lf-from" value="${F.from}"></label><label class="lf-date">s.d. <input type="date" class="input sm" id="lf-to" value="${F.to}"></label>`;
    return `${pageHead('Activity Log', 'Semua pergerakan data di sistem: siapa, kapan (tanggal & jam), modul, aksi, data, dan field yang berubah (sebelum → sesudah). Catatan tidak dapat diubah atau dihapus.', 'LOG-FR-03 – LOG-FR-07')}
      ${F.refId ? `<div class="notice"><span>Riwayat data <b>${esc(F.ref)}</b> saja.</span><button class="btn btn-ghost sm" data-act="log-clear-ref">Tampilkan semua</button></div>` : ''}
      <div class="card">${toolbar('Cari data, keterangan, atau pengguna…', false, filters)}<div id="list">${listActLog()}</div></div>`;
  }
  function listActLog() {
    const F = state.logF;
    const rows = db.logs.slice().reverse().filter(l => (!F.refId || l.refId === F.refId) && (F.modul === 'all' || l.modul === F.modul)
      && (F.aksi === 'all' || l.aksi === F.aksi) && (F.user === 'all' || l.user === F.user)
      && (!F.from || l.waktu.slice(0, 10) >= F.from) && (!F.to || l.waktu.slice(0, 10) <= F.to)
      && matches([l.ref, l.ket, l.user, l.sub]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Waktu', cls: 'nowrap', render: l => fmtWaktu(l.waktu) },
      { label: 'Pengguna', render: l => esc(l.user) },
      { label: 'Modul', render: l => `${esc(l.modul)}<br><span class="muted">${esc(l.sub)}</span>` },
      { label: 'Aksi', cls: 'nowrap', render: l => aksiBadge(l.aksi) },
      { label: 'Data', render: l => `<span class="code">${esc(l.ref)}</span>` },
      { label: 'Perubahan', render: l => l.changes.length ? `${l.changes.slice(0, 2).map(c => `${esc(c.f)}: <span class="neg">${esc(c.old || '—')}</span> → <span class="pos">${esc(c.new || '—')}</span>`).join('<br>')}${l.changes.length > 2 ? `<br><span class="muted">+${l.changes.length - 2} field lain</span>` : ''}` : `<span class="muted">${esc(l.ket)}</span>` },
      { label: 'Action', cls: 'act', render: l => `<button class="btn btn-ghost sm" data-act="log-detail" data-id="${l.id}">Detail</button>` }
    ]);
  }
  function logDetail(l) {
    const rows = l.changes.length ? l.changes.map(c => `<tr><td>${esc(c.f)}</td><td class="log-old">${esc(c.old || '—')}</td><td class="log-new">${esc(c.new || '—')}</td></tr>`).join('')
      : '<tr><td colspan="3" class="muted">Tidak ada perubahan field (aksi baca / cetak).</td></tr>';
    openModal({ title: `Activity Log — ${esc(l.aksi)} ${esc(l.ref)}`, wide: true, info: true,
      body: `<div class="summary">${sumRow('Waktu', fmtWaktu(l.waktu))}${sumRow('Pengguna', esc(l.user))}${sumRow('Modul', `${esc(l.modul)} › ${esc(l.sub)}`)}${sumRow('Aksi', aksiBadge(l.aksi))}${sumRow('Keterangan', esc(l.ket))}</div>
        <div class="table-wrap"><table class="table log-diff"><thead><tr><th>Field</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody>${rows}</tbody></table></div>` });
  }

  function renderMe() {
    const box = $('#me-box'); if (!box) return;
    box.innerHTML = `<label>Login sebagai <select id="me-sel" class="input sm">${loginUsers().map(u => `<option value="${u.id}" ${u.id === state.me ? 'selected' : ''}>${esc(u.nama)}</option>`).join('')}</select></label>`;
  }

  // ---------- Render ----------
  const LIST = { paket: listPaket, peserta: listPeserta, company: listCompany, trainer: listTrainer, ketQtn: listKetQtn, kfTpl: listKfTpl, user: listUser, rate: listRate, mprog: listMprog, quot: listQuot, qtn: listQtn, actlog: listActLog };
  const currentList = () => MASTERS[state.view] ? listMaster(state.view) : LIST[state.view]();
  const VIEWS = {
    dashboard: viewDashboard, paket: viewPaket, combine: viewCombine, katalog: viewKatalog, peserta: viewPeserta, company: viewCompany, trainer: viewTrainer, ketQtn: viewKetQtn, kfTpl: viewKfTpl,
    user: viewUser, rate: viewRate, mprog: viewMprog, quot: viewQuot, qtn: viewQtn, mpForm: viewCostForm, qtForm: viewCostForm, actlog: viewActLog
  };

  function render() {
    auditRun();
    renderNav();
    renderMe();
    const v = state.view;
    $('#view').innerHTML = MASTERS[v] ? viewMaster(v) : VIEWS[v]();
  }

  // ---------- Events ----------
  const mpById = el => db.mprog.find(x => x.id === +el.dataset.id);
  const qtById = el => db.quot.find(x => x.id === +el.dataset.id);
  const qtnById = el => db.qtn.find(x => x.id === +el.dataset.id);
  const ACTIONS = {
    'nav-open': () => document.body.classList.add('nav-open'),
    'hist-open': el => { const id = +el.dataset.id, l = db.logs.find(x => x.refId === id); go('actlog'); state.logF.refId = id; state.logF.ref = l ? l.ref : `#${id}`; render(); },
    'log-detail': el => logDetail(db.logs.find(x => x.id === +el.dataset.id)),
    'log-clear-ref': () => { state.logF.refId = null; state.page = 1; render(); },
    'nav-close': () => document.body.classList.remove('nav-open'),
    'nav-module': el => { const m = el.dataset.module; state.navOpen[m] = !state.navOpen[m]; renderNav(); },
    'master-add': () => masterForm(state.view),
    'master-edit': el => masterForm(state.view, findRec(state.view, +el.dataset.id)),
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
    if (t.id === 'q') { state.q = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'cf-judul') state.form.judul = t.value;
    if (inCostForm() && (t.dataset.pf || t.dataset.bi) && t.tagName === 'INPUT') onProgramInput(t);
  });

  document.addEventListener('change', e => {
    const t = e.target;
    if (t.id === 'status' || t.id === 'fstatus') { state.status = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id && t.id.startsWith('lf-')) { state.logF[t.id.slice(3)] = t.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (t.id === 'me-sel') { state.me = +t.value; toast(`Login sebagai ${meName()} (mockup)`); }
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
})();
