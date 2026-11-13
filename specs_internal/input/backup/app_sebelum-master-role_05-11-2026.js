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
  // Judul dari input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/ (contoh isi Katalog Produk).
  seedPaket('Sertifikasi Kompetensi Analis Kimia', 'Sertifikasi', 'Analisis Kimia', 'Sertifikat BNSP');
  seedPaket('Sertifikasi Laboran Kimia', 'Sertifikasi', 'Kimia', 'Sertifikat BNSP');

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
    { nama: 'PT Contoh Laboratorium Nusantara', jenis: 'Swasta', alamat: 'Jl. Contoh Raya No. 10', provinsi: 'Jawa Barat', kota: 'Kota Bekasi', telp: '0218881234', email: 'info@contohlab.co.id', picNama: 'Contoh PIC Satu', picJabatan: 'Manajer Mutu', picTelp: '081311112222', picEmail: 'pic1@contohlab.co.id', npwp: '012345678901000', ppnPerlakuan: 'PKP' },
    { nama: 'Balai Contoh Pengujian Daerah', jenis: 'Pemerintah', alamat: 'Jl. Contoh Dinas No. 2', provinsi: 'Jawa Timur', kota: 'Kota Surabaya', telp: '0317654321', email: 'balai@contoh.go.id', picNama: 'Contoh PIC Dua', picJabatan: 'Kepala Seksi', picTelp: '081233334444', picEmail: 'pic2@contoh.go.id', npwp: '', ppnPerlakuan: 'WAPU' }
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
    view: 'dashboard', q: '', status: 'all', jenis: 'all', ktBidang: 'all', ktSert: 'all', page: 1, rateTab: 'fixed', form: null, pf: null, qtnPj: null, qtnTab: 'ongoing',
    navOpen: { 'Master Data': true, 'Penjualan Program': true, Quotation: true, Invoice: true, 'Manage Peserta': true, 'Pengajuan Biaya': true }, navQ: ''
  };

  // Dua modul (FR-27): Master Data → Penjualan Program (sub modul Master Program & Penjualan Program); grup di bawahnya adalah sub modul.
  const ICON_DB = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>';
  const ICON_SALE = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>';
  const ICON_QTN = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H8l-4 4z"/><path d="M8 9h8M8 12h5"/></svg>';
  const ICON_INV = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>';
  const ICON_PB = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 15h2"/><path d="M7 6V4h10v2"/></svg>';
  const ICON_MP = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 20a6.5 6.5 0 0 0-3-5.5"/></svg>';
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
        { group: 'Pengguna', items: [['user', 'Master User'], ['akses', 'Role & Permission']] }
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
      // Quotation Deal → Invoice (input/Invoice & Pengajuan/2026-10-30-invoice-consulting/).
      name: 'Invoice', icon: ICON_INV, groups: [
        { group: 'Invoicing', items: [['ivc', 'Invoicing']] }
      ]
    },
    {
      // Peserta Deal per program: daftar, cetak, export, Close Program (input/Invoice & Pengajuan/2026-11-04-manage-peserta/).
      name: 'Manage Peserta', icon: ICON_MP, groups: [
        { group: 'Peserta', items: [['mpst', 'Manage Peserta']] }
      ]
    },
    {
      // Tim program mengajukan biaya ke Manajemen (input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/); pencairan Finance menyusul.
      name: 'Pengajuan Biaya', icon: ICON_PB, groups: [
        { group: 'Pengajuan', items: [['pb', 'Pengajuan Biaya'], ['pbAppr', 'Approval Pengajuan']] }
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
                : v === 'qtn' ? db.qtn.length : v === 'ivc' ? db.qtn.filter(k => k.status === 'deal').length : v === 'pb' ? pbVisible().length : v === 'pbAppr' ? db.pb.concat(db.ob).filter(x => x.status === 'menunggu').length : v === 'mpst' ? mpProgs().filter(x => !mpDone(x)).length : v === 'akses' ? roleList().length : v === 'actlog' ? db.logs.length : null;
  const SUBVIEW = { combine: ['paket', 'Tambah Product'], mpForm: ['mprog', null], qtForm: ['quot', null], ivcForm: ['ivc', null], qtnDetail: ['qtn', null], pbProg: ['pb', null], pbForm: ['pb', null] };

  function go(view) {
    state.view = view; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.ktBidang = 'all'; state.ktSert = 'all'; state.page = 1; state.qtnPj = null; state.mpPj = null;
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
    const home = can('dashboard') && (!kw || 'dashboard'.includes(kw))
      ? `<button class="nav-module-head nav-home ${navView === 'dashboard' ? 'active' : ''}" data-view="dashboard">${HOME}<span>${mark('Dashboard')}</span></button>` : '';
    const html = home + MODULES.filter(m => m.name !== 'Activity Log' || canLog()).map(m => {
      const groups = m.groups.map(g => {
        const allowed = g.items.filter(([v]) => can(v)); // Role & Permission (FR-57)
        const items = !kw ? allowed : allowed.filter(([, label]) => label.toLowerCase().includes(kw));
        return items.length ? `<div class="nav-group">${items.map(([v, label]) => {
          const n = countOf(v);
          return `<button class="nav-item ${v === navView ? 'active' : ''}" data-view="${v}">${mark(label)}${n === null ? '' : `<span class="nav-count">${n}</span>`}</button>`;
        }).join('')}</div>` : '';
      }).join('');
      if (!groups) return '';
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
    if (state.view === 'ivcForm') leaf = 'Add Invoicing';
    if (state.view === 'pbProg') leaf = (pjById(state.pbPj) || {}).nomor || 'Program';
    if (state.view === 'pbForm') leaf = state.pbf ? `${(pjById(state.pbf.pj) || {}).nomor} / Add Pengajuan` : 'Add Pengajuan';
    if (state.view === 'qtnDetail') leaf = (db.qtn.find(x => x.id === state.qtnView) || {}).nomor || 'Detail';
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
    <template>${items.concat(r.createdAt && canLog() ? [['hist-open', 'Lihat Riwayat']] : []).map(([act, label, danger]) => `<button class="menu-item ${danger ? 'danger' : ''}" role="menuitem" data-act="${act}" data-id="${r.id}">${label}</button>`).join('')}</template>`;
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

  // ---------- Katalog Produk (FR-14, FR-60 – FR-63; input/Master - Buat Program/2026-11-05-katalog-produk/) ----------
  // Isi katalog = Paket Produk berstatus Aktif. Filter Bidang & Sertifikat; hasil filter yang sama dicetak sebagai PDF katalog.
  const ktName = (key, id) => (findRec(key, id) || {}).nama || '—';
  const ktBase = () => db.paket.filter(p => p.aktif);
  const ktRows = () => ktBase().filter(p =>
    (state.ktBidang === 'all' || String(p.bidang) === state.ktBidang)
    && (state.ktSert === 'all' || String(p.sertifikasi) === state.ktSert)
    && matches([p.nomor, p.judul, ...ATTRS.map(a => ktName(a.key, p[a.field]))]));
  // Pilihan filter hanya nilai yang dipakai paket aktif, agar tidak ada pilihan yang hasilnya kosong.
  const ktOpts = (key, field, all) => {
    const used = [...new Set(ktBase().map(p => p[field]))].map(id => findRec(key, id)).filter(Boolean)
      .sort((a, b) => a.nama.localeCompare(b.nama, 'id'));
    return [['all', all]].concat(used.map(r => [String(r.id), `${esc(r.nama)} (${ktBase().filter(p => p[field] === r.id).length})`]));
  };
  // Kelompok per Bidang (urut nama); paket di dalamnya urut Judul lalu Sertifikat.
  const ktGroups = rows => [...new Set(rows.map(p => p.bidang))]
    .map(id => ({ id, nama: ktName('jenisBidang', id), items: rows.filter(p => p.bidang === id)
      .sort((a, b) => a.judul.localeCompare(b.judul, 'id') || ktName('jenisSertifikasi', a.sertifikasi).localeCompare(ktName('jenisSertifikasi', b.sertifikasi), 'id')) }))
    .sort((a, b) => a.nama.localeCompare(b.nama, 'id'));
  const ktFilterText = () => `Bidang: ${state.ktBidang === 'all' ? 'Semua' : ktName('jenisBidang', +state.ktBidang)} · Sertifikat: ${state.ktSert === 'all' ? 'Semua' : ktName('jenisSertifikasi', +state.ktSert)}${state.q ? ` · Kata kunci: "${state.q}"` : ''}`;
  const KT_ICON_CERT = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="m8.5 13.5-1.5 8 5-3 5 3-1.5-8"/></svg>';

  const viewKatalog = () => `${pageHead('Katalog Produk', 'Katalog disusun otomatis dari Paket Produk berstatus Aktif. Saring berdasarkan Bidang dan Sertifikat, lalu cetak sebagai PDF untuk Promosi.', 'FR-14 · FR-60 – FR-63',
    '<button class="btn btn-accent" data-act="kt-print">Cetak Katalog (PDF)</button>')}
    <div class="card">${toolbar('Cari judul, nomor, atau atribut…', false,
      selectFilter('kt-bidang', 'Filter bidang', state.ktBidang, ktOpts('jenisBidang', 'bidang', 'Semua bidang'))
      + selectFilter('kt-sert', 'Filter sertifikat', state.ktSert, ktOpts('jenisSertifikasi', 'sertifikasi', 'Semua sertifikat')))}
      <div id="list">${listKatalog()}</div></div>`;

  function listKatalog() {
    const rows = ktRows(), groups = ktGroups(rows);
    if (!rows.length) return `<div class="empty-state"><h2>Tidak ada paket produk</h2><p>${ktBase().length
      ? 'Tidak ada Paket Produk aktif yang cocok dengan filter. Ubah filter Bidang/Sertifikat atau kata kunci.'
      : 'Belum ada Paket Produk berstatus Aktif. Tambahkan atau aktifkan paket di menu Product Combine.'}</p></div>`;
    return `<div class="kt-sum"><span><b>${rows.length}</b> paket produk · <b>${groups.length}</b> bidang</span><span class="muted">${esc(ktFilterText())}</span></div>
      ${groups.map(g => `<section class="kt-group"><h3>${esc(g.nama)}<span>${g.items.length} paket</span></h3>
        <div class="kt-grid">${g.items.map(p => `<article class="kt-card">
          <div class="kt-top"><span class="tag">${esc(ktName('jenisProduk', p.produk))}</span><span class="code">${esc(p.nomor)}</span></div>
          <h4>${esc(p.judul)}</h4>
          <div class="kt-cert">${KT_ICON_CERT}<span>${esc(ktName('jenisSertifikasi', p.sertifikasi))}</span></div>
        </article>`).join('')}</div></section>`).join('')}`;
  }

  // ----- PDF katalog: Sampul · Daftar Isi · Isi per Bidang (2 kolom) · Sampul belakang (kontak) -----
  const KT_CAP = 16, KT_HEAD = 1, KT_ROW = 3; // satuan tinggi isi per halaman A4: judul bidang = 1, satu baris 2 kartu = 3
  function ktPaginate(groups) {
    const pages = []; let cur = [], used = 0;
    groups.forEach(g => {
      for (let i = 0; i < g.items.length; i += 2) {
        if (used + KT_ROW + (i === 0 || used === 0 ? KT_HEAD : 0) > KT_CAP) { pages.push(cur); cur = []; used = 0; }
        if (i === 0 || used === 0) { cur.push({ head: g, lanjut: i > 0 }); used += KT_HEAD; }
        cur.push({ row: g.items.slice(i, i + 2) }); used += KT_ROW;
      }
    });
    if (cur.length) pages.push(cur);
    return pages;
  }
  const KT_LOGO = '<div class="kt-logo">L<i>A</i>B<small>M A N I A</small></div>';
  const ktItem = p => `<div class="kt-item">
      <div class="kt-item-top"><span class="kt-chip">${esc(ktName('jenisProduk', p.produk))}</span><span class="kt-kode">${esc(p.nomor)}</span></div>
      <h4>${esc(p.judul)}</h4>
      <div class="kt-item-meta"><span>Bidang</span><b>${esc(ktName('jenisBidang', p.bidang))}</b></div>
      <div class="kt-item-cert">${KT_ICON_CERT}<span>${esc(ktName('jenisSertifikasi', p.sertifikasi))}</span></div>
    </div>`;
  const ktPageFoot = n => `<div class="kt-pfoot"><span>PT LabMania Indonesia Jaya · www.labmaniaindonesia.id</span><span>${n}</span></div>`;

  function katalogPages(rows) {
    const groups = ktGroups(rows), body = ktPaginate(groups), tahun = new Date().getFullYear();
    const produk = [...new Set(rows.map(p => ktName('jenisProduk', p.produk)))].sort((a, b) => a.localeCompare(b, 'id'));
    const sert = [...new Set(rows.map(p => ktName('jenisSertifikasi', p.sertifikasi)))].sort((a, b) => a.localeCompare(b, 'id'));
    const startOf = {}; // halaman pertama tiap bidang: sampul = 1, daftar isi = 2
    body.forEach((pg, i) => pg.forEach(x => { if (x.head && !x.lanjut) startOf[x.head.id] = i + 3; }));

    const cover = `<section class="pdf-page kt-page kt-cover">
      <div class="kt-cover-top">${KT_LOGO}<span>${tahun}</span></div>
      <div class="kt-cover-mid"><small>PT LabMania Indonesia Jaya</small><h1>Katalog<br>Produk</h1>
        <p>Program ${esc(produk.join(' & '))}</p>
        <ul class="kt-cover-stat"><li><b>${rows.length}</b>Paket produk</li><li><b>${groups.length}</b>Bidang</li><li><b>${sert.length}</b>Jenis sertifikat</li></ul></div>
      <div class="kt-cover-foot">${esc(ktFilterText())}<br>Dicetak ${tglPanjang(todayIso)}</div>
    </section>`;

    const toc = `<section class="pdf-page kt-page">
      <div class="kt-phead">${KT_LOGO}<span>Katalog Produk ${tahun}</span></div>
      <div class="kt-pbody"><h2 class="kt-h2">Daftar Isi</h2>
        <p class="kt-lead">Katalog ini memuat ${rows.length} paket produk ${esc(produk.join(' dan ').toLowerCase())} LabMania yang dikelompokkan menurut bidang. Setiap paket mencantumkan jenis sertifikat yang diterima peserta.</p>
        <ol class="kt-toc">${groups.map(g => `<li><span>${esc(g.nama)} <em>${g.items.length} paket</em></span><i></i><b>${startOf[g.id]}</b></li>`).join('')}</ol>
        <h3 class="kt-h3">Jenis Sertifikat</h3>
        <ul class="kt-legend">${sert.map(s => `<li>${KT_ICON_CERT}${esc(s)}</li>`).join('')}</ul>
      </div>${ktPageFoot(2)}</section>`;

    const isi = body.map((pg, i) => `<section class="pdf-page kt-page">
      <div class="kt-phead">${KT_LOGO}<span>Katalog Produk ${tahun}</span></div>
      <div class="kt-pbody">${pg.map(x => x.head
        ? `<h2 class="kt-band"><span>Bidang</span>${esc(x.head.nama)}${x.lanjut ? ' <em>(lanjutan)</em>' : ''}</h2>`
        : `<div class="kt-row">${x.row.map(ktItem).join('')}</div>`).join('')}</div>
      ${ktPageFoot(i + 3)}</section>`).join('');

    const back = `<section class="pdf-page kt-page kt-cover kt-back">
      <div class="kt-cover-mid">${KT_LOGO}<h2>Tertarik mengikuti program kami?</h2>
        <p>Hubungi tim LabMania untuk jadwal, penawaran, dan program in house sesuai kebutuhan instansi Anda.</p>
        <ul class="kt-contact"><li><span>Customer Care</span>0821-2429-3839</li><li><span>Website</span>www.labmaniaindonesia.id</li>
          <li><span>Alamat</span>Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143</li></ul></div>
      <div class="kt-cover-foot">PT LabMania Indonesia Jaya</div>
    </section>`;
    return { html: cover + toc + isi + back, total: body.length + 3 };
  }

  function katalogPrint() {
    const rows = ktRows();
    if (!rows.length) { toast('Tidak ada paket produk untuk dicetak — ubah filter terlebih dahulu', 'warn'); return; }
    const k = katalogPages(rows), filter = ktFilterText();
    openModal({
      title: 'Cetak Katalog Produk', wide: 'x',
      body: pdfWrap(k.html, `PDF katalog: ${k.total} halaman, ${rows.length} paket produk · ${esc(filter)}`),
      onMount: pdfMount, cancelLabel: 'Tutup', saveLabel: 'Cetak / Simpan PDF',
      onSave: () => {
        logAct('Master Data', 'Katalog Produk', 'Cetak', 'Katalog Produk', null, `Cetak Katalog Produk (${rows.length} paket) — ${filter}`);
        window.print();
        return false; // modal tetap terbuka setelah dialog cetak ditutup
      }
    });
  }

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
      { label: 'Jenis Instansi', render: r => `${esc(r.jenis || '—')}<br><span class="muted">PPN: ${r.ppnPerlakuan === 'KB' ? 'Kawasan Berikat' : esc(r.ppnPerlakuan || 'PKP')}</span>` },
      { label: 'Lokasi', render: r => r.kota ? `${esc(r.kota)}<br><span class="muted">${esc(r.provinsi)}</span>` : '—' },
      STATUS_COL,
      { label: 'Action' + Q16, cls: 'act', render: r => gearMenu(r, [['company-edit', 'Edit'], toggleItem(r), ['company-del', 'Delete', true]]) }
    ]);
  }

  function companyForm(rec) {
    const r = rec || { nama: '', jenis: '', alamat: '', provinsi: '', kota: '', telp: '', email: '', picNama: '', picJabatan: '', picTelp: '', picEmail: '', npwp: '', ppnPerlakuan: 'PKP' };
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
        ${field('NPWP Instansi <span class="muted">(opsional)</span>', inp('npwp', r.npwp, 'inputmode="numeric" placeholder="15 atau 16 digit"'))}
        ${field('Perlakuan PPN' + req + q('Q-IVC-05', 'Field baru (usulan) dari konsep Invoicing: dipakai invoice untuk perhitungan & jurnal. WAPU biasanya BUMN; Kawasan Berikat tidak bayar PPN.'), `<select class="input" name="ppnPerlakuan">${[['PKP', 'PKP'], ['WAPU', 'WAPU (PPN disetor customer)'], ['KB', 'Kawasan Berikat']].map(([v, l]) => `<option value="${v}" ${(r.ppnPerlakuan || 'PKP') === v ? 'selected' : ''}>${l}</option>`).join('')}</select><span class="hint">Dipakai di Invoice (IVC-FR-04)</span>`)}
      </div>`,
      onMount: bindKota,
      onSave: f => {
        const keys = ['nama', 'jenis', 'alamat', 'provinsi', 'kota', 'telp', 'email', 'picNama', 'picJabatan', 'picTelp', 'picEmail', 'npwp', 'ppnPerlakuan'];
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
  // Data karyawan (tabel existing `employees`) + akun login (tabel existing `users`). Department tidak dipakai
  // (input/Invoice & Pengajuan/2026-11-04-role-permission/). Role akun = Division + Jabatan, terbentuk otomatis (FR-38, FR-54).
  const DIVISION = ['Direksi', 'Sales Public', 'Sales B2B', 'Marketing', 'Penyelenggaraan', 'Trainer', 'Keuangan', 'Administrasi', 'IT'];
  const JABATAN = ['Direktur', 'Manager', 'Supervisor', 'Staff', 'Admin', 'Developer'];
  const GENDER = ['Laki-laki', 'Perempuan'];
  const RELIGION = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
  const EMP_TYPE = ['Tetap', 'Kontrak', 'Magang', 'Freelance'];
  const QUSR = q('Q-25', 'Daftar Division, Jabatan, dan Employee Type di mockup hanya contoh — isi final mengikuti data aplikasi/Labmania.');
  // Akun developer = role Superadmin bawaan sistem (dibuat dari awal, tidak dapat ditambah lagi); user lain: Division + Jabatan.
  const roleOf = u => u.superadmin ? 'Superadmin' : u.div && u.jabatan ? `${u.div} – ${u.jabatan}` : '';
  db.user = [
    { kode: 'EMP-001', nama: 'Contoh User Satu', nik: '3275010101800001', tmpLahir: 'Bekasi', tglLahir: '1980-01-01', div: 'Direksi', jabatan: 'Direktur', gender: 'Laki-laki', agama: 'Islam',
      telp: '+6281211110000', email: 'user1@contoh.id', alamat: 'Jl. Contoh No. 1, Bekasi', npwp: '', tglMasuk: '2020-01-02', tipe: 'Tetap', jobdesc: 'Menyetujui Master & Penjualan Program', aktif: true,
      akun: true, username: 'user.satu' },
    { kode: 'EMP-002', nama: 'Contoh User Dua', nik: '', tmpLahir: '', tglLahir: '', div: 'Sales B2B', jabatan: 'Staff', gender: 'Perempuan', agama: '',
      telp: '+6281322220000', email: 'user2@contoh.id', alamat: '', npwp: '', tglMasuk: '2024-03-01', tipe: 'Kontrak', jobdesc: '', aktif: true,
      akun: true, username: 'user.dua' },
    { kode: 'EMP-003', nama: 'Contoh Trainer Tiga', nik: '', tmpLahir: '', tglLahir: '', div: 'Trainer', jabatan: 'Staff', gender: 'Laki-laki', agama: '',
      telp: '+6285733330000', email: '', alamat: '', npwp: '', tglMasuk: '', tipe: 'Freelance', jobdesc: 'Trainer lepas', aktif: false,
      akun: false, username: '' },
    { kode: 'EMP-004', nama: 'Contoh Admin Developer', nik: '', tmpLahir: '', tglLahir: '', div: 'IT', jabatan: 'Developer', gender: 'Laki-laki', agama: '',
      telp: '+6281244440000', email: 'dev@contoh.id', alamat: '', npwp: '', tglMasuk: '2025-01-06', tipe: 'Tetap', jobdesc: 'Admin sistem & developer', aktif: true,
      akun: true, username: 'admin.dev', superadmin: true }
  ].map(u => Object.assign({ id: uid() }, u));

  const viewUser = () => `${pageHead('Master User', 'Data karyawan Labmania dan akun login ke sistem. <b>Role</b> akun terbentuk otomatis dari <b>Division + Jabatan</b>; hak aksesnya diatur di <b>Role & Permission</b>. User nonaktif tidak dapat login; data tidak dihapus.', 'FR-36 – FR-39',
    '<button class="btn btn-accent" data-act="user-add">+ Tambah User</button>')}
    <div class="card">${toolbar('Cari ID, nama, username, atau email…', true,
      selectFilter('fjenis', 'Filter division', state.jenis, [['all', 'Semua division'], ...DIVISION.map(d => [d, d])]))}
      <div id="list">${listUser()}</div></div>`;

  function listUser() {
    const rows = db.user.filter(r => statusOk(r) && (state.jenis === 'all' || r.div === state.jenis) &&
      matches([r.kode, r.nama, r.username, r.email, roleOf(r)]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Employee ID', cls: 'nowrap', render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama', render: r => `${esc(r.nama)}<br><span class="muted">${esc(r.email || r.telp)}</span>` },
      { label: 'Division', render: r => esc(r.div) },
      { label: 'Jabatan', render: r => `${esc(r.jabatan)}<br><span class="muted">${esc(r.tipe)}</span>` },
      { label: 'Akun login / Role', render: r => r.akun ? `<span class="code">${esc(r.username)}</span><br><span class="muted">${esc(roleLabel(roleOf(r)))}</span>` : '<span class="muted">Tanpa akun</span>' },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['user-edit', 'Edit'], toggleItem(r)].concat(r.akun ? [['user-reset', 'Reset Password']] : [])) }
    ]);
  }

  function userForm(rec) {
    const r = rec || { kode: '', nama: '', nik: '', tmpLahir: '', tglLahir: '', div: '', jabatan: '', gender: '', agama: '', telp: '', email: '', alamat: '', npwp: '',
      tglMasuk: '', tipe: '', jobdesc: '', aktif: true, akun: true, username: '' };
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
          ${field('Division' + req + QUSR, sel('div', DIVISION, r.div))}
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
          ${field('Role', `<div class="static" data-role>${roleHint(r)}</div><span class="hint">${r.superadmin ? 'Role bawaan sistem — tidak berubah oleh Division / Jabatan' : 'Otomatis dari Division + Jabatan; hak akses diatur di Role & Permission'}</span>`)}
          ${field('Password' + (rec && rec.akun ? '' : req), `<input class="input" name="password" type="password" autocomplete="new-password">${rec && rec.akun ? '<span class="hint">Kosongkan bila tidak diubah</span>' : '<span class="hint">Minimal 8 karakter</span>'}`)}
          ${field('Konfirmasi Password' + (rec && rec.akun ? '' : req), '<input class="input" name="password2" type="password" autocomplete="new-password">')}
        </div>
      </div>`,
      onMount: form => {
        const f = form.elements;
        const upd = () => { form.querySelector('[data-role]').innerHTML = roleHint({ superadmin: r.superadmin, div: f.div.value, jabatan: f.jabatan.value }); };
        f.div.addEventListener('change', upd);
        f.jabatan.addEventListener('change', upd);
        f.akun.addEventListener('change', e => { form.querySelector('[data-akun]').classList.toggle('hidden', !e.target.checked); });
      },
      onSave: f => {
        const keys = ['kode', 'nama', 'nik', 'tmpLahir', 'tglLahir', 'div', 'jabatan', 'gender', 'agama', 'telp', 'email', 'alamat', 'npwp', 'tglMasuk', 'tipe', 'jobdesc', 'username'];
        const v = {};
        keys.forEach(k => { v[k] = f[k].value.trim(); });
        v.aktif = f.aktif.value === '1';
        v.akun = f.akun.checked;
        const pw = f.password.value, pw2 = f.password2.value;
        let ok = true;
        const LBL = { kode: 'Employee ID', nama: 'Employee Name', div: 'Division', jabatan: 'Jabatan', gender: 'Gender', telp: 'Phone Number', tipe: 'Employee Type' };
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
          if ((needPw || pw) && pw.length < 8) ok = invalid(f.password, 'Password minimal 8 karakter');
          if ((needPw || pw) && pw !== pw2) ok = invalid(f.password2, 'Konfirmasi password tidak sama');
        } else { v.username = ''; }
        if (!ok) return false;
        // Role terbentuk otomatis (FR-38): role baru tampil di Role & Permission tanpa hak sampai diatur.
        const role = v.akun ? roleOf(Object.assign({}, rec, v)) : '', isNew = role && !roleList().includes(role);
        if (isNew) db.akses[role] = {};
        const roleMsg = isNew ? ` — role baru "${role}" terbentuk, atur aksesnya di Role & Permission` : '';
        if (rec) {
          Object.assign(rec, v);
          toast(`User ${rec.kode} diperbarui${pw ? ' (password diganti)' : ''}${roleMsg}`);
        } else {
          const u = Object.assign({ id: uid() }, v);
          db.user.push(u);
          state.page = Math.ceil(db.user.length / PAGE_SIZE);
          toast(`User ${u.kode} ditambahkan${roleMsg}`);
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

  // ---------- Master Trainer (FR-44 – FR-47; input/Master - Buat Program/2026-10-29-master-trainer/, format input/Master - Buat Program/Master Trainer Ivan Sarifudin.xlsx) ----------
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
        ${field('Akun login (Master User)' + q('Q-PB-13', 'Usulan: trainer dihubungkan ke akun Master User agar dapat membuka dan mengajukan biaya program yang ia latih (Pengajuan Biaya).'), `<select class="input" name="user"><option value="">— Tanpa akun —</option>${db.user.filter(u => u.akun && (u.aktif || u.id === Number(d.user))).map(u => `<option value="${u.id}" ${u.id === Number(d.user) ? 'selected' : ''}>${esc(u.nama)} · ${esc(u.username)}</option>`).join('')}</select><span class="hint">Dipakai untuk hak data Pengajuan Biaya</span>`, '', 'span2')}
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
    const blank = { user: '', nama: '', tmpLahir: '', tglLahir: '', alamat: '', telp: '', pendSmu: '', pendDiploma: '', pendSarjana: '', pendPasca: '', keahlian: [], lainnya: '', cvTempat: '', cvTanggal: '', ttdNama: '', pelatihan: [], kerja: [], mengajar: [], lampiran: [] };
    const d = Object.assign(clone(rec || blank), { tab: 'identitas', err: {} });
    d.keahlian = d.keahlian.join('\n');
    if (d.user === undefined) d.user = '';
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
        v.user = d.user ? Number(d.user) : '';
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
  // Tiga template awal dari input/Master - Buat Program/Tamplate Formulir Konfirmasi Workshop.doc, ...Training Sertifikasi .doc, ...Resertifikasi.doc.
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

  // ---------- Form costing (layout input/Master - Buat Program/labmania.html; rumus fr-nfr-master-program.md bagian 8) ----------
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

  // Simulasi Hasil — tampilan mengikuti input/Master - Buat Program/simulasi_hasil.html: BEP, hasil rencana, skala peserta, rincian (MP-FR-10, PJ-FR-10).
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
        <div>${tglPanjang(k.tanggal)}<br>${esc(s.div || 'Divisi')}<div class="pdf-ttd"></div><b>${esc(s.nama || '—')}</b></div>
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
  const ivcUnRows = () => db.qtn.filter(k => k.status === 'deal' && ivcStatus(k) === 'uninvoiced');
  const jenisOk = j => state.jenis === 'all' || state.jenis === j;
  const viewIvc = () => {
    const un = ivcUnRows(), inv = db.inv;
    const base = state.ivcTab === 'uninvoiced' ? un.map(k => [k.tanggal, pjOfQtn(k).jenis, progNo(k)]) : inv.map(i => [i.tanggal, i.jenis, i.prog]);
    const years = [...new Set(base.map(b => b[0].slice(0, 4)))].sort().reverse();
    const progs = [...new Set(base.filter(b => jenisOk(b[1])).map(b => b[2]))].sort();
    if (state.ivcProg !== 'all' && !progs.includes(state.ivcProg)) state.ivcProg = 'all';
    const tabs = [['uninvoiced', 'Uninvoiced', un.length], ['invoiced', 'Invoiced', inv.length]].map(([t, l, n]) =>
      `<button class="tab ${state.ivcTab === t ? 'active' : ''}" data-act="ivc-tab" data-tab="${t}">${l}<span class="nav-count">${n}</span></button>`).join('');
    return `${pageHead('Invoicing', 'Tab <b>Uninvoiced</b>: quotation Deal yang belum ditagih. Tab <b>Invoiced</b>: invoice yang sudah dibuat, satu baris per invoice. Satu quotation Deal = satu invoice, tidak digabung per program; B2B per peserta = satu invoice per peserta. Saring Public / B2B dengan filter jenis; kolom <b>No. Program</b> untuk menelusuri.', 'IVC-FR-01 – IVC-FR-03',
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
      { label: 'No. Quotation', cls: 'nowrap', render: i => `<span class="code">${esc(qOf(i).nomor)}</span>` },
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

  // ---------- Manage Peserta (MP-FR-01 – MP-FR-10; input/Invoice & Pengajuan/2026-11-04-manage-peserta/) ----------
  // Setelah Deal quotation: peserta Deal per Penjualan Program Final dilist, dicetak (Daftar Peserta, Daftar Hadir), diekspor; program dapat di-Close.
  const QMP = (n, t) => q(`Q-MPS-${String(n).padStart(2, '0')}`, t);
  const mpProgs = () => db.quot.filter(x => x.status === 'final');
  const mpDone = pj => !!pj.closed || pjSelesai(pj); // tab Selesai: acara lewat atau sudah Closed
  const mpStatus = pj => pj.closed ? '<span class="badge off">Closed</span>' : pjSelesai(pj) ? '<span class="badge st-selesai">Selesai</span>' : '<span class="badge on">Berjalan</span>';
  const mpJudul = pj => (paketOf(mpOf(pj)) || {}).judul || '—';
  // Peserta = peserta berstatus Deal di quotation Deal program itu (satu-satunya sumber; tidak ditambah di modul ini).
  const mpPeserta = pj => qtnOfPj(pj).flatMap(k => dealRows(k).map(r => ({ p: pesertaById(r.id) || {}, k }))).filter(x => x.p.id);
  state.mpTab = 'ongoing'; state.mpPj = null;

  function viewMpst() {
    const pj = state.mpPj && db.quot.find(x => x.id === state.mpPj);
    if (!pj) state.mpPj = null;
    if (pj) {
      const tr = trainerById(pj.trainer), co = pj.jenis === 'b2b' ? companyOf(pj) : null, n = mpPeserta(pj).length;
      const info = `${jenisTag(pj.jenis)} <b>${esc(mpJudul(pj))}</b> · ${fmtRange(pj.tglMulai, pj.tglSelesai)} (${Number(pj.durasi) || 1} hari) · ${esc(pj.lokasi || '—')}
        <br>Trainer <b>${esc(tr ? tr.nama : '—')}</b>${co ? ` · Company <b>${esc(co.nama)}</b>` : ''} · Peserta Deal <b>${n}</b> / ${nPeserta(pj)} · ${mpStatus(pj)}`;
      const dis = n ? '' : 'disabled title="Belum ada peserta Deal"';
      return `${pageHead(`Peserta — ${esc(pj.nomor)}`, info, 'MP-FR-04 – MP-FR-08',
        `<button class="btn btn-ghost" data-act="mp-back">← Semua Program</button><button class="btn btn-ghost" data-act="mp-xls" data-id="${pj.id}" ${dis}>Export Excel</button>
         <button class="btn btn-ghost" data-act="mp-hadir" data-id="${pj.id}" ${dis}>Cetak Daftar Hadir</button><button class="btn btn-accent" data-act="mp-print" data-id="${pj.id}" ${dis}>Cetak Daftar Peserta</button>`)}
        ${pj.closed ? `<div class="notice"><span>Program <b>Closed</b> oleh ${esc(pj.closed.by)} pada ${fmtWaktu(pj.closed.at)} — peserta tetap dapat dilihat, dicetak, dan diekspor.</span></div>` : ''}
        <div class="card">${toolbar('Cari kode, nama, instansi, kontak, atau nomor quotation…', false)}<div id="list">${listMpst()}</div>
        <p class="hint" style="padding:0 16px 14px">Peserta diambil dari quotation berstatus <b>Deal</b> (peserta yang dicentang Deal). Data peserta diperbaiki di <b>Master Data › Master Peserta</b>, bukan di sini.</p></div>`;
    }
    const tabCnt = t => mpProgs().filter(x => (t === 'done') === mpDone(x)).length;
    const tabs = [['ongoing', 'On going'], ['done', 'Selesai']].map(([t, l]) =>
      `<button class="tab ${state.mpTab === t ? 'active' : ''}" data-act="mp-tab" data-tab="${t}">${l}<span class="nav-count">${tabCnt(t)}</span></button>`).join('');
    return `${pageHead('Manage Peserta', 'Setiap Penjualan Program Final beserta peserta yang sudah <b>Deal</b> di quotation. Buka <b>Lihat Peserta</b> untuk daftar, cetak Daftar Peserta / Daftar Hadir, dan export Excel. <b>Close Program</b> menutup program setelah selesai.', 'MP-FR-01 – MP-FR-03')}
      <div class="card"><div class="tabs">${tabs}<span class="tabs-note">${QMP(1, 'Tab Selesai = tanggal selesai acara sudah lewat atau program sudah Closed (usulan, sama dengan Quotation).')}</span></div>
        ${toolbar('Cari nomor, judul program, lokasi, atau trainer…', false,
          selectFilter('fjenis', 'Filter jenis program', state.jenis, [['all', 'Public & B2B'], ['public', 'Program Public'], ['b2b', 'Program B2B']]))}
        <div id="list">${listMpst()}</div></div>`;
  }
  function listMpst() {
    const pj = state.mpPj && db.quot.find(x => x.id === state.mpPj);
    if (pj) {
      const rows = mpPeserta(pj).filter(({ p, k }) => matches([p.kode, p.nama, pesertaCo(p), p.telp, p.email, k.nomor])).map(({ p, k }) => ({ id: p.id, p, k }));
      return listBlock(rows, [
        NO_COL,
        { label: 'Kode', cls: 'nowrap', render: x => `<span class="code">${esc(x.p.kode)}</span>` },
        { label: 'Nama', render: x => `<b>${esc(x.p.nama)}</b>` },
        { label: 'Instansi / Company', render: x => esc(pesertaCo(x.p)) },
        { label: 'No. WA', cls: 'nowrap', render: x => esc(x.p.telp || '—') },
        { label: 'Email', render: x => esc(x.p.email || '—') },
        { label: 'No. Quotation', cls: 'nowrap', render: x => `<span class="code">${esc(x.k.nomor)}</span>` }
      ]);
    }
    const rows = mpProgs().filter(x => (state.mpTab === 'done') === mpDone(x) && (state.jenis === 'all' || x.jenis === state.jenis)
      && matches([x.nomor, mpJudul(x), x.lokasi, (trainerById(x.trainer) || {}).nama]))
      .sort((a, b) => String(a.tglMulai || '').localeCompare(String(b.tglMulai || '')));
    return listBlock(rows, [
      NO_COL,
      { label: 'No. Program', cls: 'nowrap', render: x => `<span class="code">${esc(x.nomor)}</span>` },
      { label: 'Judul Program', render: x => `<b>${esc(mpJudul(x))}</b>${x.jenis === 'b2b' ? `<br><span class="muted">${esc((companyOf(x) || {}).nama || '—')}</span>` : ''}` },
      { label: 'Jenis', render: x => jenisTag(x.jenis) },
      { label: 'Tanggal & Lokasi', render: x => `${fmtRange(x.tglMulai, x.tglSelesai)}<br><span class="muted">${esc(x.lokasi || '—')}</span>` },
      { label: 'Trainer', render: x => esc((trainerById(x.trainer) || {}).nama || '—') },
      { label: 'Peserta (Deal / Target)', cls: 'nowrap', render: x => `<b>${mpPeserta(x).length}</b> / ${nPeserta(x)}` },
      { label: 'Status', cls: 'nowrap', render: x => mpStatus(x) },
      { label: 'Action', cls: 'act', render: x => gearMenu(x, [['mp-open', 'Lihat Peserta']].concat(!x.closed && can('mpst', 'm') ? [['mp-close', 'Close Program', true]] : [])) }
    ]);
  }
  // Close Program (MP-FR-03): konfirmasi + peringatan bila masih ada pekerjaan terbuka; tidak memblokir (usulan Q-MP-02).
  function mpClose(pj) {
    if (!can('mpst', 'm')) return toast('Anda tidak punya hak Manage di Manage Peserta (Role & Permission)', 'warn');
    const qs = qtnOfPj(pj), warn = [];
    if (!pjSelesai(pj)) warn.push(`acara belum selesai (tanggal selesai ${fmtRange(pj.tglSelesai)})`);
    const nTunggu = qs.filter(k => k.status === 'draft' || k.status === 'terkirim').length;
    if (nTunggu) warn.push(`${nTunggu} quotation masih Draft / Terkirim`);
    const nIvc = qs.filter(k => k.status === 'deal' && ivcStatus(k) !== 'invoiced').length;
    if (nIvc) warn.push(`${nIvc} quotation Deal belum dibuatkan invoice`);
    const nPb = (db.pb || []).concat(db.ob || []).filter(x => x.pj === pj.id && x.status === 'menunggu').length;
    if (nPb) warn.push(`${nPb} pengajuan biaya masih Menunggu Approval`);
    openModal({
      title: `Close Program ${esc(pj.nomor)}`,
      body: `<p style="margin:0 0 12px">Tutup program <b>${esc(mpJudul(pj))}</b> (${fmtRange(pj.tglMulai, pj.tglSelesai)})? Setelah Closed, program pindah ke tab <b>Selesai</b> dan <b>tidak dapat diajukan biaya lagi</b> (Pengajuan Biaya). Peserta tetap dapat dilihat dan dicetak.</p>
        ${warn.length ? `<div class="notice"><span><b>Perhatian:</b> ${warn.map(esc).join('; ')}.</span></div>` : ''}
        <p class="hint">Close tidak dapat dibatalkan${QMP(2, 'Close Program boleh kapan saja dengan peringatan (usulan), atau hanya setelah acara selesai / semua invoice dibuat? Perlu tombol Buka Kembali?')}.</p>`,
      saveLabel: 'Close Program', danger: true,
      onSave: () => {
        pj.closed = { by: meName(), at: isoNow() };
        logAct('Manage Peserta', 'Manage Peserta', 'Close Program', pj.nomor, pj.id, `Close Program ${pj.nomor}`, [{ f: 'Status program', old: 'Final', new: 'Closed' }]);
        toast(`Program ${pj.nomor} Closed`);
      }
    });
  }
  // Cetak (MP-FR-06, MP-FR-07): template usulan — Labmania belum punya format (input/Invoice & Pengajuan/2026-11-04-manage-peserta/).
  const MP_PER_PAGE = 18;
  const mpChunks = list => { const out = []; for (let i = 0; i < list.length; i += MP_PER_PAGE) out.push(list.slice(i, i + MP_PER_PAGE)); return out.length ? out : [[]]; };
  const mpInfoTbl = (pj, extra = []) => {
    const tr = trainerById(pj.trainer), co = pj.jenis === 'b2b' ? companyOf(pj) : null;
    const rows = [['Program', mpJudul(pj)], ['No. Program', pj.nomor], ['Tanggal', rangePanjang(pj.tglMulai, pj.tglSelesai)], ['Lokasi', pj.lokasi || '—'], ['Trainer', tr ? tr.nama : '—']]
      .concat(co ? [['Company', co.nama]] : []).concat(extra);
    return `<table class="pdf-form mp-info">${rows.map(([l, v]) => `<tr><td>${esc(l)}</td><td>:</td><td>${esc(v)}</td></tr>`).join('')}</table>`;
  };
  const MP_FOOT = (kode, i, n) => pdfFoot(kode, `Halaman ${i} dari ${n}`, '— (template usulan)');
  function mpPrintPeserta(pj) {
    const list = mpPeserta(pj), pages = mpChunks(list);
    const html = pages.map((ch, pi) => pdfPage(`<h3 class="pdf-title">DAFTAR PESERTA</h3>${mpInfoTbl(pj, [['Jumlah peserta', `${list.length} orang`]])}
      <table class="pdf-tbl mp-tbl"><thead><tr><th>No</th><th>Nama Peserta</th><th>Instansi / Company</th><th>No. WA</th><th>Email</th></tr></thead>
      <tbody>${ch.map(({ p }, i) => `<tr><td>${pi * MP_PER_PAGE + i + 1}</td><td>${esc(p.nama)}</td><td>${esc(pesertaCo(p))}</td><td>${esc(p.telp || '')}</td><td>${esc(p.email || '')}</td></tr>`).join('')}</tbody></table>`,
      MP_FOOT('FR/LMI-TR-… (usulan)', pi + 1, pages.length))).join('');
    openModal({ title: `Daftar Peserta ${esc(pj.nomor)}`, wide: 'x', info: true, body: pdfWrap(html, `Daftar Peserta · ${list.length} peserta · template usulan${QMP(3, 'Format Daftar Peserta belum ada dari Labmania; ini template usulan (kolom: No, Nama, Instansi, No. WA, Email). Perlu kode dokumen / kolom lain?')}`), onMount: pdfMount });
    logAct('Manage Peserta', 'Manage Peserta', 'Cetak', pj.nomor, pj.id, `Cetak Daftar Peserta ${pj.nomor}`);
  }
  function mpPrintHadir(pj) {
    const list = mpPeserta(pj), hari = Math.max(1, Number(pj.durasi) || 1), tr = trainerById(pj.trainer);
    const html = Array.from({ length: hari }, (_, d) => {
      const tgl = pj.tglMulai ? addDays(pj.tglMulai, d) : '', chunks = mpChunks(list);
      return chunks.map((ch, pi) => pdfPage(`<h3 class="pdf-title">DAFTAR HADIR PESERTA</h3>${mpInfoTbl(pj, [['Hari ke', `${d + 1} dari ${hari}${tgl ? ` — ${tglPanjang(tgl)}` : ''}`]])}
        <table class="pdf-tbl mp-tbl mp-hadir"><thead><tr><th>No</th><th>Nama Peserta</th><th>Instansi / Company</th><th colspan="2">Tanda Tangan</th></tr></thead>
        <tbody>${ch.map(({ p }, i) => { const no = pi * MP_PER_PAGE + i + 1; return `<tr><td>${no}</td><td>${esc(p.nama)}</td><td>${esc(pesertaCo(p))}</td><td class="mp-ttd">${no % 2 ? `${no}.` : ''}</td><td class="mp-ttd">${no % 2 ? '' : `${no}.`}</td></tr>`; }).join('')}</tbody></table>
        ${pi === chunks.length - 1 ? `<div class="mp-sign"><div>Trainer<div class="pdf-ttd"></div><b>(${esc(tr ? tr.nama : '..............................')})</b></div><div>Penyelenggara<div class="pdf-ttd"></div><b>(..............................)</b></div></div>` : ''}`,
        MP_FOOT('FR/LMI-TR-… (usulan)', pi + 1, chunks.length))).join('');
    }).join('');
    openModal({ title: `Daftar Hadir ${esc(pj.nomor)}`, wide: 'x', info: true, body: pdfWrap(html, `Daftar Hadir · ${hari} hari × ${list.length} peserta · template usulan${QMP(4, 'Format Daftar Hadir belum ada dari Labmania; ini template usulan: satu lembar per hari pelaksanaan, tanda tangan selang-seling, tanda tangan Trainer & Penyelenggara. Perlu kolom jam masuk / sesi pagi-siang?')}`), onMount: pdfMount });
    logAct('Manage Peserta', 'Manage Peserta', 'Cetak', pj.nomor, pj.id, `Cetak Daftar Hadir ${pj.nomor}`);
  }
  // Export Excel (MP-FR-08): mockup mengunduh tabel HTML ber-ekstensi .xls (aplikasi: PhpSpreadsheet .xlsx).
  function mpXls(pj) {
    const list = mpPeserta(pj), cell = v => `<td>${esc(v == null ? '' : String(v))}</td>`;
    const html = `<html><head><meta charset="utf-8"></head><body><table border="1"><tr><th colspan="7">Daftar Peserta ${esc(pj.nomor)} — ${esc(mpJudul(pj))} (${esc(fmtRange(pj.tglMulai, pj.tglSelesai))}, ${esc(pj.lokasi || '')})</th></tr>
      <tr><th>No</th><th>Kode</th><th>Nama</th><th>Instansi / Company</th><th>No. WA</th><th>Email</th><th>No. Quotation</th></tr>
      ${list.map(({ p, k }, i) => `<tr>${cell(i + 1)}${cell(p.kode)}${cell(p.nama)}${cell(pesertaCo(p))}<td style="mso-number-format:'\\@'">${esc(p.telp || '')}</td>${cell(p.email)}${cell(k.nomor)}</tr>`).join('')}</table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = `Peserta_${pj.nomor.replace(/[^\w-]+/g, '_')}.xls`;
    document.body.appendChild(a); a.click(); a.remove();
    logAct('Manage Peserta', 'Manage Peserta', 'Export', pj.nomor, pj.id, `Export Excel peserta ${pj.nomor}`);
    toast(`Excel peserta ${pj.nomor} diunduh (${list.length} peserta)`);
  }

  // ---------- Role & Permission (FR-54 – FR-58; input/Invoice & Pengajuan/2026-11-04-role-permission/, video Role & Permission.mp4) ----------
  // Role = Division + Jabatan akun user (terbentuk otomatis dari Master User, FR-38). Hak per role atas setiap menu:
  // View / Add / Manage / Delete (konvensi permission aplikasi Nama_Menu.View dst.). Tampilan mengikuti video: daftar role + modal matrix.
  const AK_ACTS = [['v', 'View'], ['a', 'Add'], ['m', 'Manage'], ['d', 'Delete']];
  const SUPER_ROLE = 'Superadmin'; // akun developer, group id 1 aplikasi (Q-34); role bawaan, dibuat dari awal — tidak dapat ditambah lagi (Q-35 a)
  const ADMIN_ROLE = 'Administrasi – Admin'; // Admin = akun user Labmania: menu mengikuti hak role (dapat dibatasi), data semua (Q-34; Q-35)
  const MGMT_JABATAN = ['Direktur']; // Manajemen ditentukan dari posisi (Q-PB-14) — daftar menyusul, sementara Direktur
  const meUser = () => db.user.find(u => u.id === state.me) || {};
  const meRole = () => roleOf(meUser());
  const isSuper = () => meRole() === SUPER_ROLE;
  const isAdmin = () => meRole() === ADMIN_ROLE;
  const isMgmt = () => MGMT_JABATAN.includes(meUser().jabatan);
  const seeAllData = () => isSuper() || isAdmin();
  const akMenus = () => [{ mod: 'Dashboard', items: [['dashboard', 'Dashboard']] }].concat(MODULES.map(m => ({ mod: m.name, items: m.groups.flatMap(g => g.items) })));
  // Aksi yang berlaku per menu (usulan): Dashboard, Katalog, Activity Log hanya View; Approval Pengajuan View & Manage (Manage = proses approval); Manage Peserta View & Manage (Manage = Close Program).
  const akActsOf = v => ['dashboard', 'katalog', 'actlog'].includes(v) ? ['v'] : v === 'pbAppr' || v === 'mpst' ? ['v', 'm'] : ['v', 'a', 'm', 'd'];
  const akFull = v => Object.fromEntries(akActsOf(v).map(x => [x, true]));
  const akAllKeys = () => akMenus().flatMap(m => m.items.map(([v]) => v));
  const akTotal = () => akAllKeys().reduce((s, v) => s + akActsOf(v).length, 0);
  const akCount = m => Object.keys(m || {}).reduce((s, v) => s + akActsOf(v).filter(k => (m[v] || {})[k]).length, 0);
  const akCode = (v, k) => `${v}.${AK_ACTS.find(a => a[0] === k)[1].toLowerCase()}`;
  // Isi awal (FR-58, usulan Q-33) — per role contoh.
  db.akses = {};
  {
    const all = except => Object.fromEntries(akAllKeys().filter(v => !except.includes(v)).map(v => [v, akFull(v)]));
    const staff = () => ({ dashboard: { v: true }, qtn: { v: true, a: true, m: true }, pb: { v: true, a: true } });
    db.akses['Direksi – Direktur'] = all(['akses']);
    db.akses[ADMIN_ROLE] = all(['actlog']); // Activity Log mengikuti LOG-FR-09; dapat diberikan lewat Role & Permission
    db.akses['Sales B2B – Staff'] = staff();
    db.akses['Trainer – Staff'] = staff();
  }
  const can = (v, act = 'v') => isSuper() || !!(((db.akses[meRole()] || {})[v] || {})[act]);
  // Contoh user role Admin (akun user Labmania; Q-34).
  db.user.push({ id: uid(), kode: 'EMP-005', nama: 'Contoh Admin', nik: '', tmpLahir: '', tglLahir: '', div: 'Administrasi', jabatan: 'Admin', gender: 'Perempuan', agama: '',
    telp: '+6281355550000', email: 'admin@contoh.id', alamat: '', npwp: '', tglMasuk: '2024-01-02', tipe: 'Tetap', jobdesc: 'Admin aplikasi: semua akses, Role & Permission', aktif: true,
    akun: true, username: 'admin' });
  const roleUsers = role => db.user.filter(u => u.akun && roleOf(u) === role);
  const roleList = () => [...new Set([SUPER_ROLE, ...db.user.filter(u => u.akun).map(roleOf).filter(Boolean), ...Object.keys(db.akses)])];
  const roleLabel = role => !role ? '—' : role === SUPER_ROLE ? `${role} (bawaan sistem)` : role === ADMIN_ROLE ? `${role} · Admin` : role;
  const roleHint = u => {
    const r = roleOf(u);
    if (!r) return '<span class="muted">Pilih Division dan Jabatan</span>';
    return `<b>${esc(roleLabel(r))}</b>${roleList().includes(r) ? '' : ' <span class="tag">Role baru</span>'}`;
  };

  const viewAkses = () => {
    const roles = roleList();
    const kpi = (l, v, sub) => `<div class="kpi"><div class="kpi-l">${l}</div><div class="kpi-v">${v}</div><div class="muted">${sub}</div></div>`;
    return `${pageHead('Role & Permission', 'Role terbentuk otomatis dari <b>Division + Jabatan</b> akun di Master User. Atur setiap role: menu apa saja yang bisa dibuka dan apa yang bisa dilakukan di menu itu — <b>View</b> = membuka menu & daftar, <b>Add</b> = tambah / ajukan, <b>Manage</b> = ubah, finalkan, proses approval, <b>Delete</b> = hapus / batalkan / nonaktifkan. Hak <b>data</b> (mis. hanya program terkait di Pengajuan Biaya) diatur di modulnya.', 'FR-54 – FR-58')}
      <div class="kpis" style="margin-bottom:14px">${kpi('Total Role', roles.length, 'dari Division + Jabatan')}${kpi('User berakun', db.user.filter(u => u.akun).length, 'tersebar di semua role')}${kpi('Menu diatur', akAllKeys().length, `${akTotal()} hak (menu × aksi)`)}</div>
      <div class="card">${toolbar('Cari nama role…', false)}<div id="list">${listAkses()}</div>
      <p class="hint" style="padding:0 16px 14px">Role baru muncul otomatis saat akun user dengan kombinasi Division + Jabatan baru disimpan — awalnya tanpa hak; role tanpa user dapat dihapus. <b>Superadmin</b> dibuat dari awal (bawaan sistem) dan tidak dapat ditambah lagi${q('Q-35', 'Terjawab: Superadmin bawaan sistem; role tanpa user boleh dihapus. Masih terbuka: kombinasi Division + Jabatan mana yang menjadi Admin (mockup: Administrasi – Admin)?')}. Isi awal hak: usulan${q('Q-33', 'Isi awal hak per role sudah sesuai? Aksi cukup View / Add / Manage / Delete atau perlu Approve terpisah?')}.</p></div>`;
  };
  function listAkses() {
    const rows = roleList().filter(r => matches([r, roleLabel(r)])).map(r => ({ id: r, role: r }));
    return listBlock(rows, [
      NO_COL,
      { label: 'Role', render: x => x.role === SUPER_ROLE ? `<b>${esc(x.role)}</b> <span class="tag">Full Access</span><br><span class="muted">Role bawaan sistem (akun developer) — dibuat dari awal, tidak dapat ditambah lagi</span>`
        : `<b>${esc(x.role)}</b>${x.role === ADMIN_ROLE ? ' <span class="tag">Admin</span>' : ''}<br><span class="muted">Division · Jabatan</span>` },
      { label: 'User terdaftar', cls: 'nowrap', render: x => `${roleUsers(x.role).length} user` },
      { label: 'Total hak', cls: 'nowrap', render: x => x.role === SUPER_ROLE ? '<span class="badge on">Semua hak aktif</span>'
        : akCount(db.akses[x.role]) ? `<span class="badge on">${akCount(db.akses[x.role])} hak diberikan</span>` : '<span class="badge off">Belum diatur</span>' },
      { label: 'Cakupan (preview)', render: x => {
        if (x.role === SUPER_ROLE) return '<span class="muted"><i>Otomatis memiliki semua hak sistem</i></span>';
        const codes = akAllKeys().flatMap(v => akActsOf(v).filter(k => ((db.akses[x.role] || {})[v] || {})[k]).map(k => akCode(v, k)));
        return codes.length ? `<div class="chips">${codes.slice(0, 3).map(c => `<span class="chip code">${esc(c)}</span>`).join('')}${codes.length > 3 ? `<span class="chip">+${codes.length - 3} lagi</span>` : ''}</div>` : '<span class="muted">—</span>';
      } },
      { label: 'Action', cls: 'act nowrap', render: x => `<button class="btn btn-ghost sm" data-act="ak-edit" data-role="${esc(x.role)}">${x.role === SUPER_ROLE ? 'Lihat' : 'Atur Akses'}</button>`
        + (x.role !== SUPER_ROLE && !roleUsers(x.role).length ? ` <button class="btn btn-ghost sm" data-act="ak-del" data-role="${esc(x.role)}" title="Role tanpa user">Hapus</button>` : '') }
    ]);
  }
  // Modal Edit Role & Permissions (video): matrix per menu, dikelompokkan per modul; Pilih Semua Modul / Kosongkan / cari.
  function akEdit(role) {
    const locked = role === SUPER_ROLE, D = clone(db.akses[role] || {}), total = akTotal();
    let cari = '';
    const on = (v, k) => locked || !!((D[v] || {})[k]);
    const matrix = () => {
      const s = cari.toLowerCase();
      const body = akMenus().map(m => {
        const items = m.items.filter(([v, l]) => !s || [m.mod, l, v].some(t => t.toLowerCase().includes(s)) || akActsOf(v).some(k => akCode(v, k).includes(s)));
        if (!items.length) return '';
        return `<tr class="ak-mod"><td colspan="4">${esc(m.mod)}</td></tr>` + items.map(([v, l]) => {
          const acts = akActsOf(v), n = acts.filter(k => on(v, k)).length, full = n === acts.length;
          return `<tr><td><label class="ak-menu"><input type="checkbox" data-prow="${v}" ${full ? 'checked' : ''} ${locked ? 'disabled' : ''}><span><b>${esc(l)}</b><br><span class="muted code">${esc(v)} · ${acts.length} hak</span></span></label></td>
            <td><div class="ak-chips">${acts.map(k => `<label class="ak-chip ${on(v, k) ? 'on' : ''}"><input type="checkbox" data-pk="${v}" data-pa="${k}" ${on(v, k) ? 'checked' : ''} ${locked ? 'disabled' : ''}>${AK_ACTS.find(a => a[0] === k)[1]} <span class="code">${esc(akCode(v, k))}</span></label>`).join('')}</div></td>
            <td class="ak-c">${locked ? '' : `<button type="button" class="btn btn-ghost sm" data-prow-btn="${v}">${full ? 'Batal Semua' : 'Pilih Semua'}</button>`}</td>
            <td class="ak-c"><span class="ak-scope ${n ? 'on' : ''}">${n} / ${acts.length}</span></td></tr>`;
        }).join('');
      }).join('');
      return body || '<tr><td colspan="4" class="empty">Menu tidak ditemukan.</td></tr>';
    };
    const cnt = () => locked ? total : akCount(D);
    const body = `${field('Nama Role', `<input class="input" value="${esc(role)}" readonly><span class="hint">Dari Division + Jabatan di Master User — tidak dapat diubah di sini. ${roleUsers(role).length} user memakai role ini.</span>`)}
      ${locked ? '<div class="notice"><span><b>Superadmin</b> (akun developer) — semua akses dan semua data; tidak dapat diubah (FR-56).</span></div>' : ''}
      <div class="ak-bar"><b>Matrix Hak Akses</b> <span class="tag" data-akcnt>${cnt()} dari ${total} dipilih</span>
        <span class="ak-bar-r">${locked ? '' : '<button type="button" class="btn btn-ghost sm" data-akall="1">Pilih Semua Modul</button><button type="button" class="btn btn-ghost sm" data-akall="0">Kosongkan</button>'}
        <input type="search" class="input sm" data-akq placeholder="Cari menu / hak…" aria-label="Cari menu atau hak"></span></div>
      <div class="table-wrap ak-wrap"><table class="table ak-tbl"><thead><tr><th>Modul / Menu</th><th>Hak akses / aksi</th><th class="ak-c">Per menu</th><th class="ak-c">Scope</th></tr></thead><tbody data-akm>${matrix()}</tbody></table></div>
      <p class="hint">Total: <b data-aktot>${cnt()}</b> hak dipilih. Mencentang Add / Manage / Delete otomatis mencentang View; melepas View melepas semua hak menu itu.</p>`;
    const refresh = form => {
      form.querySelector('[data-akm]').innerHTML = matrix();
      form.querySelector('[data-akcnt]').textContent = `${cnt()} dari ${total} dipilih`;
      form.querySelector('[data-aktot]').textContent = cnt();
    };
    const setRow = (v, val) => { if (val) D[v] = akFull(v); else delete D[v]; };
    openModal({
      title: locked ? `Role ${esc(role)}` : `Atur Akses Role ${esc(role)}`,
      wide: 'x', info: locked, saveLabel: 'Simpan Role',
      body,
      onMount: form => {
        form.addEventListener('change', e => {
          const t = e.target;
          if (t.dataset.prow) setRow(t.dataset.prow, t.checked);
          else if (t.dataset.pk) {
            const v = t.dataset.pk, k = t.dataset.pa, r = D[v] = D[v] || {};
            r[k] = t.checked;
            if (t.checked && k !== 'v') r.v = true;
            if (!t.checked && k === 'v') Object.keys(r).forEach(x => { r[x] = false; });
            Object.keys(r).forEach(x => { if (!r[x]) delete r[x]; });
            if (!Object.keys(r).length) delete D[v];
          } else return;
          refresh(form);
        });
        form.addEventListener('click', e => {
          const b = e.target.closest('[data-prow-btn], [data-akall]');
          if (!b) return;
          if (b.dataset.akall) akAllKeys().forEach(v => setRow(v, b.dataset.akall === '1'));
          else { const v = b.dataset.prowBtn; setRow(v, akActsOf(v).some(k => !(D[v] || {})[k])); }
          refresh(form);
        });
        form.querySelector('[data-akq]').addEventListener('input', e => { cari = e.target.value; form.querySelector('[data-akm]').innerHTML = matrix(); });
      },
      onSave: () => {
        const before = db.akses[role] || {};
        const lbl = Object.fromEntries(akMenus().flatMap(m => m.items));
        const fmt = r => AK_ACTS.filter(([k]) => r && r[k]).map(([, l]) => l).join(', ') || '—';
        const changes = akAllKeys().filter(v => fmt(before[v]) !== fmt(D[v])).map(v => ({ f: lbl[v], old: fmt(before[v]), new: fmt(D[v]) }));
        db.akses[role] = clone(D);
        logAct('Master Data', 'Role & Permission', 'Edit', role, null, `Edit Role & Permission ${role}`, changes);
        toast(`Hak akses role ${role} disimpan — ${changes.length} menu berubah`);
      }
    });
  }
  function akDel(role) {
    if (role === SUPER_ROLE || roleUsers(role).length) return toast('Role masih dipakai user — tidak dapat dihapus', 'warn');
    confirmDelete(`role ${role}`, () => {
      delete db.akses[role];
      logAct('Master Data', 'Role & Permission', 'Hapus', role, null, `Hapus role ${role} (tanpa user)`);
      toast(`Role ${role} dihapus`);
    });
  }

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
  db.user.push({ id: uid(), kode: 'EMP-006', nama: 'Ivan Sarifudin, S.T.', nik: '', tmpLahir: '', tglLahir: '', div: 'Trainer', jabatan: 'Staff', gender: 'Laki-laki', agama: '',
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
  const canApprove = () => can('pbAppr', 'm');

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
      { label: 'No. Program', cls: 'nowrap', render: pj => `${jenisTag(pj.jenis)} <button class="btn btn-ghost sm" data-act="pb-open" data-id="${pj.id}">${esc(pj.nomor)}</button><br><span class="muted">${fmtRange(pj.tglMulai, pj.tglSelesai)}</span>` },
      { label: 'Program', cls: 'title', render: pj => `${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${esc(pj.lokasi || '—')}</span>` },
      { label: 'Customer', render: pj => pj.jenis === 'b2b' ? esc((companyOf(pj) || {}).nama || '—') : '<span class="muted">Public</span>' },
      { label: 'Tim', render: timCell },
      { label: 'Budget', cls: 'money', render: pj => rp(pbSum(pj).budget) },
      { label: 'Aktual', cls: 'money', render: pj => rp(pbSum(pj).used) },
      { label: 'Sisa', cls: 'money', render: pj => { const s = pbSum(pj); return `<b class="${s.sisa <= 0 ? 'neg' : ''}">${rp(s.sisa)}</b>`; } },
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

  // ---------- Jejak data & Activity Log (LOG-FR-01 – LOG-FR-08; input/Master - Buat Program/2026-10-30-histori-activity-log/) ----------
  // Setiap record menyimpan Dibuat oleh/tanggal-jam dan Diubah oleh/tanggal-jam. Setiap perubahan data (tambah, edit, hapus,
  // aktif/nonaktif, finalkan, batal, kirim, deal) tercatat di Activity Log beserta field sebelum/sesudah.
  // Mockup: perubahan dideteksi dengan membandingkan data sebelum & sesudah setiap aksi; di aplikasi dicatat write_log() di server.
  const AUDIT_SRC = [
    ['jenisProduk', 'Master Data', 'Jenis Produk'], ['jenisSertifikasi', 'Master Data', 'Jenis Sertifikat'], ['jenisBidang', 'Master Data', 'Jenis Bidang'],
    ['paket', 'Master Data', 'Product Combine'], ['peserta', 'Master Data', 'Master Peserta'], ['company', 'Master Data', 'Master Company'],
    ['trainer', 'Master Data', 'Master Trainer'], ['ketQtn', 'Master Data', 'Template Keterangan Quotation'], ['kfTpl', 'Master Data', 'Template Formulir Konfirmasi'],
    ['rate.fixed', 'Master Data', 'Master Rate — Fixed Cost'], ['rate.variable', 'Master Data', 'Master Rate — Variable Cost'], ['user', 'Master Data', 'Master User'],
    ['mprog', 'Penjualan Program', 'Master Program'], ['quot', 'Penjualan Program', 'Penjualan Program'], ['qtn', 'Quotation', 'Quotation'], ['inv', 'Invoice', 'Invoicing'],
    ['pb', 'Pengajuan Biaya', 'Pengajuan'], ['ob', 'Pengajuan Biaya', 'Overbudget']
  ];
  const STAMP_KEYS = ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
  const LOG_AKSI = ['Tambah', 'Edit', 'Hapus', 'Aktifkan', 'Nonaktifkan', 'Finalkan', 'Batalkan', 'Kirim & Cetak', 'Deal', 'Cetak', 'Setujui', 'Tolak', 'Tandai Paid', 'Export', 'Close Program'];
  const STATUS_AKSI = { final: 'Finalkan', batal: 'Batalkan', terkirim: 'Kirim & Cetak', deal: 'Deal', disetujui: 'Setujui', ditolak: 'Tolak', paid: 'Tandai Paid' };
  const FIELD_LBL = { nama: 'Nama', kode: 'Kode', nomor: 'Nomor', judul: 'Judul', aktif: 'Status aktif', status: 'Status', item: 'Item', cost: 'Default Cost',
    hargaJual: 'Harga jual / peserta', targetPeserta: 'Target peserta', targetProfit: 'Target profit', durasi: 'Durasi', biaya: 'Item biaya', peserta: 'Peserta',
    diskon: 'Diskon', lain: 'Biaya lain-lain', allin: 'Harga all-in', skema: 'Skema harga', ppn: 'PPN', tanggal: 'Tanggal', berlaku: 'Berlaku sampai',
    tglMulai: 'Tanggal mulai', tglSelesai: 'Tanggal selesai', lokasi: 'Lokasi', waktu: 'Waktu', company: 'Company', instansi: 'Instansi', telp: 'Telepon', email: 'Email',
    alamat: 'Alamat', up: 'u.p.', sales: 'Sales', tujuan: 'Tujuan', ket: 'Keterangan', kf: 'Formulir Konfirmasi', teks: 'Teks', def: 'Default', noFaktur: 'Nomor Faktur', perlakuan: 'Perlakuan PPN', ppnPerlakuan: 'Perlakuan PPN', setor: 'Bukti setor WAPU', coaPenjualan: 'COA Penjualan', cara: 'Cara tagih', desk: 'Deskripsi', items: 'Item pengajuan', total: 'Total', bank: 'Bank', norek: 'No. Rekening', nmRek: 'Nama Rekening', files: 'Bukti', alasan: 'Alasan', alasanTolak: 'Alasan tolak', apprBy: 'Approval oleh', apprAt: 'Waktu approval', kat: 'Kategori', jenis: 'Jenis', by: 'Request By', user: 'Akun login', paidBy: 'Paid oleh', paidAt: 'Tanggal bayar', paidKet: 'Keterangan bayar' };
  db.logs = [];
  const pad2 = n => String(n).padStart(2, '0');
  const isoNow = (d = new Date()) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
  const fmtWaktu = s => { if (!s) return '—'; const [d, t] = s.split(' '); const [y, m, dd] = d.split('-'); return `${dd}-${m}-${y} ${t.slice(0, 5)}`; };
  // Pengguna yang sedang login (mockup: dapat diganti di kaki sidebar untuk mencoba).
  const loginUsers = () => db.user.filter(u => u.akun && u.aktif).length ? db.user.filter(u => u.akun && u.aktif) : db.user;
  state.me = (loginUsers()[0] || {}).id;
  const meName = () => (db.user.find(u => u.id === state.me) || {}).nama || 'Pengguna';
  // Activity Log & Lihat Riwayat hanya untuk Manajemen dan Superadmin (LOG-FR-09); Admin bila diberi hak di Role & Permission.
  const canLog = () => can('actlog') && (isSuper() || isAdmin() || isMgmt());
  // Log disimpan 60 hari; yang lebih lama dihapus otomatis oleh sistem (LOG-NFR-06).
  const LOG_DAYS = 60;
  const auditList = key => key.startsWith('rate.') ? db.rate[key.slice(5)] : db[key];
  const refOf = r => r.nomor || r.kode || r.nama || r.judul || r.item || (r.teks ? String(r.teks).slice(0, 40) : `#${r.id}`);
  // closed (Close Program) dicatat sendiri oleh Manage Peserta, tidak ikut audit otomatis Penjualan Program.
  const bare = r => { const o = {}; Object.keys(r).forEach(k => { if (!STAMP_KEYS.includes(k) && k !== 'closed') o[k] = r[k]; }); return o; };
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
    // Retensi 60 hari (LOG-NFR-06).
    const batas = new Date(); batas.setDate(batas.getDate() - LOG_DAYS);
    db.logs = db.logs.filter(l => new Date(l.waktu.replace(' ', 'T')) >= batas);
  }
  // Data contoh: waktu dibuat menyebar 20–29 Oktober 2026, berurutan.
  auditRun(i => { const d = new Date(2026, 9, 20, 8, 0, 0); d.setMinutes(d.getMinutes() + i * 97); return isoNow(d); });

  // Kolom "Dibuat / Diubah" di setiap daftar (LOG-FR-02).
  const HIST_COL = { label: 'Dibuat / Diubah', cls: 'hist', render: r => r.createdAt
    ? `<span class="hist-l">Dibuat <b>${esc(r.createdBy)}</b><br>${fmtWaktu(r.createdAt)}</span>${r.updatedAt ? `<span class="hist-l">Diubah <b>${esc(r.updatedBy)}</b><br>${fmtWaktu(r.updatedAt)}</span>` : ''}`
    : '<span class="muted">—</span>' };

  // ---------- Activity Log (LOG-FR-03 – LOG-FR-07) ----------
  state.logF = { modul: 'all', aksi: 'all', user: 'all', from: '', to: '', refId: null, ref: '' };
  const aksiBadge = a => `<span class="badge log-${a === 'Tambah' ? 'add' : a === 'Hapus' || a === 'Batalkan' || a === 'Nonaktifkan' || a === 'Tolak' ? 'del' : a === 'Edit' ? 'edit' : 'other'}">${esc(a)}</span>`;
  function viewActLog() {
    const F = state.logF;
    const mods = [...new Set(AUDIT_SRC.map(s => s[1]))];
    const users = [...new Set(db.logs.map(l => l.user))].sort();
    const filters = selectFilter('lf-modul', 'Filter modul', F.modul, [['all', 'Semua modul'], ...mods.map(m => [m, m])])
      + selectFilter('lf-aksi', 'Filter aksi', F.aksi, [['all', 'Semua aksi'], ...LOG_AKSI.map(a => [a, a])])
      + selectFilter('lf-user', 'Filter pengguna', F.user, [['all', 'Semua pengguna'], ...users.map(u => [u, u])])
      + `<label class="lf-date">Dari <input type="date" class="input sm" id="lf-from" value="${F.from}"></label><label class="lf-date">s.d. <input type="date" class="input sm" id="lf-to" value="${F.to}"></label>`;
    return `${pageHead('Activity Log', `Semua pergerakan data di sistem: siapa, kapan (tanggal & jam), modul, aksi, data, dan field yang berubah (sebelum → sesudah). Catatan tidak dapat diubah atau dihapus pengguna; disimpan <b>${LOG_DAYS} hari</b>, lalu dihapus otomatis oleh sistem. Hanya untuk Manajemen dan Superadmin (Admin bila diberi hak di Role &amp; Permission).`, 'LOG-FR-03 – LOG-FR-07')}
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
    box.innerHTML = `<label>Login sebagai <select id="me-sel" class="input sm">${loginUsers().map(u => `<option value="${u.id}" ${u.id === state.me ? 'selected' : ''}>${esc(u.nama)} · ${esc(roleOf(u))}</option>`).join('')}</select></label>`;
  }

  // ---------- Render ----------
  const LIST = { paket: listPaket, katalog: listKatalog, peserta: listPeserta, company: listCompany, trainer: listTrainer, ketQtn: listKetQtn, kfTpl: listKfTpl, user: listUser, rate: listRate, mprog: listMprog, quot: listQuot, qtn: listQtn, ivc: listIvc, pb: () => (state.pbTab === 'diajukan' ? listPbSent() : listPbProg()), pbAppr: listPbAppr, mpst: listMpst, akses: listAkses, actlog: listActLog };
  const currentList = () => MASTERS[state.view] ? listMaster(state.view) : LIST[state.view]();
  const VIEWS = {
    dashboard: viewDashboard, paket: viewPaket, combine: viewCombine, katalog: viewKatalog, peserta: viewPeserta, company: viewCompany, trainer: viewTrainer, ketQtn: viewKetQtn, kfTpl: viewKfTpl,
    user: viewUser, rate: viewRate, mprog: viewMprog, quot: viewQuot, qtn: viewQtn, qtnDetail: viewQtnDetail, mpForm: viewCostForm, qtForm: viewCostForm, ivc: viewIvc, ivcForm: viewIvcForm, pb: viewPb, pbProg: viewPbProg, pbForm: viewPbForm, pbAppr: viewPbAppr, mpst: viewMpst, akses: viewAkses, actlog: viewActLog
  };

  function render() {
    auditRun();
    renderNav();
    renderMe();
    const v = state.view;
    const navKey = SUBVIEW[v] ? SUBVIEW[v][0] : v;
    if (!can(navKey)) { $('#view').innerHTML = `${pageHead('Tidak ada akses', `Role <b>${esc(meRole() || '—')}</b> tidak punya hak View untuk menu ini. Atur di Master Data › Role & Permission.`, 'FR-57')}`; return; }
    $('#view').innerHTML = MASTERS[v] ? viewMaster(v) : VIEWS[v]();
    if (state.view === 'ivcForm') ivcBind();
    if (state.view === 'pbForm') pbBind();
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
    'ak-edit': el => akEdit(el.dataset.role),
    'ak-del': el => akDel(el.dataset.role),
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
})();
