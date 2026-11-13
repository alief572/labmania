/* Labmania mockup — Inti: helper, konfigurasi, data contoh, state, navigasi, potongan tampilan bersama, modal & toast.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
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
  // Tutorial highlight driver.js (12-11-2026). TOUR.<kunci> = fungsi yang mengembalikan langkah. Dimulai hanya dari tombol
  // Tutorial di atas kartu tabel (tourBtn), yang tampil di halaman yang punya tutorial. Langkah dengan `lanjut` membuka halaman
  // berikutnya (mis. form) lalu meneruskan tutorial di sana. Butuh koneksi untuk memuat driver.js dari CDN.
  const TOUR = {};
  let tourDrv = null;
  function tourStart(key) {
    const lib = window.driver && window.driver.js && window.driver.js.driver;
    if (!key || !TOUR[key]) return;
    if (!lib) { toast('Tutorial belum termuat. Periksa koneksi internet, lalu muat ulang halaman.', 'warn'); return; }
    if (tourDrv) tourDrv.destroy();
    // Langkah tanpa elemen = popover di tengah. Langkah yang elemennya tidak ada atau tersembunyi dilewati.
    const steps = TOUR[key]().filter(Boolean).map(s => {
      if ('element' in s) {
        const el = typeof s.element === 'string' ? document.querySelector(s.element) : s.element;
        if (!el || !el.getClientRects().length) return null;
        s = Object.assign({}, s, { element: el });
      }
      if (!s.lanjut) return s;
      const l = s.lanjut, go2 = () => { tourDrv.destroy(); l.run(); if (l.ke) setTimeout(() => tourStart(l.ke), 200); };
      return Object.assign({}, s, { popover: Object.assign({}, s.popover, { nextBtnText: `${l.label} →`, doneBtnText: `${l.label} →`, onNextClick: go2 }) });
    }).filter(Boolean);
    tourDrv = lib({ showProgress: true, progressText: '{{current}} dari {{total}}', nextBtnText: 'Lanjut', prevBtnText: 'Kembali', doneBtnText: 'Selesai',
      popoverClass: 'lm-tour', overlayOpacity: 0.55, stagePadding: 6, stageRadius: 8, steps, onDestroyed: () => { tourDrv = null; } });
    tourDrv.drive();
  }
  // Helper penyusun langkah (dipakai semua modul).
  const tS = (element, title, description, extra) => Object.assign(element === null ? {} : { element }, { popover: { title, description } }, extra || {});
  const tRows = () => [...document.querySelectorAll('#list tbody tr')].filter(tr => !tr.querySelector('td.empty'));
  const tKol = (tr, label) => { if (!tr) return undefined; const i = [...document.querySelectorAll('#list thead th')].findIndex(th => th.textContent.trim().startsWith(label)); return i < 0 ? undefined : tr.children[i]; };
  const tGear = pred => { const tr = tRows().find(pred || (() => true)); return tr ? tr.querySelector('.gear') : undefined; };
  const tAdd = () => document.querySelector('.page-actions .btn-accent');
  // Kunci tutorial halaman yang sedang dibuka; null = tidak ada tutorial (Dashboard sengaja tanpa tutorial).
  const tourKeyOf = () => {
    const v = state.view;
    if (v === 'dashboard') return null;
    if (v === 'qtForm') return state.pf && state.pf.p.jenis ? 'qtForm' : 'qtStart';
    if (v === 'so') return state.soTab === 'skt' ? 'skt' : state.soTab === 'refund' ? 'refund' : 'so';
    if (v === 'mpst' && state.mpPj) return 'mpstDetail';
    if (MASTERS[v]) return 'master';
    return TOUR[v] ? v : null;
  };
  // Tombol Tutorial di atas kartu tabel (halaman daftar) atau di bilah judul form.
  function tourBtn() {
    if (!tourKeyOf()) return;
    const html = '<div class="tour-bar"><button type="button" class="btn sm tour-btn" data-act="tour"><span aria-hidden="true">?</span> Tutorial</button></div>';
    const card = document.querySelector('#view .card'), bar = document.querySelector('#view .pf-formbar');
    if (card) card.insertAdjacentHTML('beforebegin', html);
    else if (bar) bar.insertAdjacentHTML('beforeend', html.replace('tour-bar', 'tour-bar in-bar'));
    else document.getElementById('view').insertAdjacentHTML('afterbegin', html);
  }
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
    },
    // To be: Varian Pelaksanaan kembali (input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/).
    varian: {
      title: 'Varian Pelaksanaan', code: 'Kode Varian', name: 'Varian', form: 'Jenis Varian',
      prefix: 'VP', fr: 'FR-07', desc: 'Cara melaksanakan suatu jenis bidang.', tobe: true
    }
  };

  // Urutan kolom mengikuti mockup klien: Produk, Bidang, Varian, Sertifikat (istilah: input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/; Varian kembali: input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/).
  const ATTRS = [
    { key: 'jenisProduk', field: 'produk', label: 'Produk' },
    { key: 'jenisBidang', field: 'bidang', label: 'Bidang' },
    { key: 'varian', field: 'varian', label: 'Varian' },
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
  seedMaster('varian', ['In House Training', 'Workshop', 'Workshop Berbasis Kompetensi', 'Workshop Lisensi BSN']);

  const idOf = (key, nama) => db[key].find(r => r.nama === nama).id;
  const findRec = (key, id) => db[key].find(r => r.id === id);

  // Paket contoh, agar daftar tidak kosong.
  db.paket = [];
  const seedPaket = (judul, produk, bidang, varian, sertifikasi) => db.paket.push({
    id: uid(), nomor: nextCode('PC'), judul, aktif: true,
    produk: idOf('jenisProduk', produk), bidang: idOf('jenisBidang', bidang), varian: idOf('varian', varian),
    sertifikasi: idOf('jenisSertifikasi', sertifikasi)
  });
  seedPaket('Pelatihan ISO/IEC 17025:2017', 'Pelatihan', 'ISO 17025', 'Workshop', 'Sertifikat Labmania');
  seedPaket('Pelatihan ISO/IEC 17025:2017', 'Pelatihan', 'ISO 17025', 'In House Training', 'Sertifikat Labmania KAN ISO 29993');
  seedPaket('Sertifikasi Petugas Sampling', 'Sertifikasi', 'Petugas Sampling', 'Workshop Berbasis Kompetensi', 'Sertifikat BNSP');
  // Judul dari input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/ (contoh isi Katalog Produk).
  seedPaket('Sertifikasi Kompetensi Analis Kimia', 'Sertifikasi', 'Analisis Kimia', 'Workshop Berbasis Kompetensi', 'Sertifikat BNSP');
  seedPaket('Sertifikasi Laboran Kimia', 'Sertifikasi', 'Kimia', 'Workshop Berbasis Kompetensi', 'Sertifikat BNSP');
  // Lingkup contoh (bukan data klien) agar kartu dan PDF katalog memperlihatkan blok Lingkup.
  // PC-005 sengaja kosong: paket tanpa Lingkup tetap tampil, tanpa blok Lingkup.
  const ISO17025 = ['Persyaratan umum: ketidakberpihakan dan kerahasiaan', 'Persyaratan struktural', 'Persyaratan sumber daya: personel, fasilitas, peralatan', 'Persyaratan proses: metode, sampling, pelaporan hasil', 'Persyaratan sistem manajemen'];
  [ISO17025, ISO17025,
    ['Perencanaan sampling', 'Teknik pengambilan contoh', 'Penanganan, pengawetan, dan pengiriman contoh', 'Dokumentasi dan rantai pengawasan contoh'],
    ['Penyiapan larutan dan pereaksi', 'Analisis gravimetri dan volumetri', 'Analisis instrumen', 'Validasi metode dan pelaporan hasil']
  ].forEach((l, i) => { db.paket[i].lingkup = [...l]; });

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
    status: 'final', paket: [pidOf('PC-001')], durasi: 2, targetPeserta: 25, hargaJual: 600000, targetProfit: 3000000,
    biaya: allFixed().concat(fromRate(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break']), [biayaFromRate(rateByItem('Seminar Kit'), 35000)])
  });
  const mp2 = seedMP({
    status: 'final', paket: [pidOf('PC-002')], durasi: 3, targetPeserta: 20, hargaJual: 900000,
    biaya: allFixed().concat(fromRate(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break']))
  });
  seedMP({
    status: 'draft', paket: [pidOf('PC-003')], durasi: 1, targetPeserta: 20, hargaJual: 750000,
    biaya: allFixed().concat(fromRate(['Sertifikat', 'Makan Siang Training']))
  });
  // Contoh Master Program dengan lebih dari satu Product (Pelatihan + Sertifikasi), costing tetap satu
  // (input/Master - Buat Program/2026-11-09-product-multi-reschedule-evidence/).
  const mp4 = seedMP({
    status: 'final', paket: [pidOf('PC-001'), pidOf('PC-003')], durasi: 2, targetPeserta: 20, hargaJual: 900000,
    biaya: allFixed().concat(fromRate(['Modul', 'Sertifikat', 'Makan Siang Training', 'Coffee Break']))
  });

  // Durasi Penjualan Program dihitung dari tanggal mulai–selesai (inklusif); null bila tanggal belum lengkap/terbalik.
  const durasiTgl = p => p.tglMulai && p.tglSelesai && p.tglSelesai >= p.tglMulai
    ? Math.round((Date.parse(p.tglSelesai) - Date.parse(p.tglMulai)) / 864e5) + 1 : null;

  // Penjualan Program menyalin angka & costing Master Program, sama untuk Public dan B2B (PJ-FR-04).
  const fromMP = (mp, jenis) => ({
    mp: mp.id, jenis, durasi: mp.durasi, targetProfit: mp.targetProfit,
    targetPeserta: mp.targetPeserta, hargaJual: mp.hargaJual,
    tglMulai: '', tglSelesai: '', waktu: '', jamPembelajaran: '', lokasi: '', company: '', trainer: '', biaya: clone(mp.biaya)
  });
  db.quot = []; // Penjualan Program
  const seedQT = o => { const q = Object.assign({ id: uid(), status: 'draft' }, o); q.nomor = docNo(PJ_PREFIX[q.jenis]); q.durasi = durasiTgl(q) || q.durasi; db.quot.push(q); return q; };
  seedQT(Object.assign(fromMP(mp1, 'public'), { hargaJual: 650000, jamPembelajaran: 16, tglMulai: '2026-11-10', tglSelesai: '2026-11-11', lokasi: 'Offline Bekasi' }));
  seedQT(Object.assign(fromMP(mp2, 'b2b'), {
    status: 'final', hargaJual: 2250000, targetPeserta: 20, company: db.company[0].id,
    tglMulai: '2026-11-17', tglSelesai: '2026-11-19', jamPembelajaran: 24, lokasi: 'In house — Bekasi',
    biaya: clone(mp2.biaya).concat([{ rate: null, tipe: 'fixed', item: 'Sewa alat peraga', satuan: 'Event', mode: 'manual', manual: true, harga: 1500000 }])
  }));
  seedQT(Object.assign(fromMP(mp1, 'b2b'), { company: db.company[1].id, hargaJual: 700000, targetPeserta: 15, jamPembelajaran: 16, tglMulai: '2026-11-24', tglSelesai: '2026-11-25', lokasi: 'In house — Surabaya' }));
  seedQT(Object.assign(fromMP(mp4, 'public'), { status: 'final', durasi: 2, jamPembelajaran: 16, tglMulai: '2026-12-01', tglSelesai: '2026-12-02', lokasi: 'Online (Zoom)' }));

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
      // To be: sub menu yang dapat dibuka/ditutup di sidebar (input/Master - Buat Program/2026-11-07-sub-menu-master-data/).
      // Dokumen tidak disebut user; tetap sub tersendiri (usulan, Q-39).
      name: 'Master Data', icon: ICON_DB, sub: true, groups: [
        { group: 'Product', items: [['jenisProduk', 'Jenis Produk'], ['jenisSertifikasi', 'Jenis Sertifikat'], ['jenisBidang', 'Jenis Bidang'], ['varian', 'Varian Pelaksanaan'], ['paket', 'Product'], ['katalog', 'Katalog Produk'], ['rate', 'Master Rate']] },
        { group: 'User', items: [['user', 'Master User'], ['role', 'Master Role'], ['akses', 'Role & Permission']] },
        { group: 'Peserta & Trainer', items: [['peserta', 'Master Peserta'], ['company', 'Master Company'], ['trainer', 'Master Trainer']] },
        { group: 'Dokumen', items: [['ketQtn', 'Template Keterangan Quotation'], ['kfTpl', 'Template Formulir Konfirmasi']] }
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
        { group: 'Quotation', items: [['qtn', 'Daftar Quotation']] },
        // To be: 1 quotation Deal = 1 Sales Order; invoice dibuat dari SO (input/Master - Buat Program/2026-11-07-sales-order/).
        { group: 'Sales Order', items: [['so', 'Sales Order']] }
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
        { group: 'Peserta', items: [['mpst', 'Manage Peserta'], ['mon', 'Monitoring Peserta']] }
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
  // Paket final (specs/final/<paket>/): <html data-paket="..."> membatasi sidebar dan daftar Role & Permission ke modul
  // paket itu plus menu umum (Pengguna, Activity Log). Tanpa data-paket semua modul tampil (mockup kerja).
  const PAKET_MENU = {
    'master-quotation': { 'Master Data': null, 'Penjualan Program': null, Quotation: null, 'Activity Log': null },
    'invoice-pengajuan': { 'Master Data': ['User'], Invoice: null, 'Manage Peserta': null, 'Pengajuan Biaya': null, 'Activity Log': null }
  };
  const paketMenu = PAKET_MENU[document.documentElement.dataset.paket];
  if (paketMenu) {
    const keep = MODULES.filter(m => m.name in paketMenu);
    keep.forEach(m => { const g = paketMenu[m.name]; if (g) m.groups = m.groups.filter(x => g.includes(x.group)); });
    MODULES.splice(0, MODULES.length, ...keep);
  }
  const countOf = v => MASTERS[v] ? db[v].length
    : v === 'paket' ? db.paket.length
      : v === 'peserta' ? db.peserta.length
        : v === 'company' ? db.company.length
        : v === 'trainer' ? db.trainer.length
        : v === 'ketQtn' ? db.ketQtn.length
        : v === 'kfTpl' ? db.kfTpl.length
        : v === 'user' ? db.user.length
        : v === 'role' ? db.role.length
          : v === 'rate' ? db.rate.fixed.length + db.rate.variable.length
            : v === 'mprog' ? db.mprog.length
              : v === 'quot' ? db.quot.length
                : v === 'qtn' ? db.qtn.length : v === 'so' ? db.so.length : v === 'ivc' ? db.qtn.filter(k => k.status === 'deal' && soSiapOk(k) && ivcStatus(k) === 'uninvoiced').length : v === 'pb' ? pbVisible().length : v === 'pbAppr' ? db.pb.concat(db.ob).filter(x => x.status === 'menunggu').length : v === 'mpst' ? mpProgs().filter(x => !mpDone(x)).length : v === 'mon' ? monAll().length : v === 'akses' ? db.role.length : v === 'actlog' ? db.logs.length : null;
  const SUBVIEW = { combine: ['paket', 'Tambah Product'], mpForm: ['mprog', null], qtForm: ['quot', null], ivcForm: ['ivc', null], qtnDetail: ['qtn', null], soProg: ['so', null], soDetail: ['so', null], sktDetail: ['so', null], pbProg: ['pb', null], pbForm: ['pb', null] };

  function go(view) {
    state.view = view; state.q = ''; state.status = 'all'; state.jenis = 'all'; state.ktBidang = 'all'; state.ktSert = 'all'; state.page = 1; state.qtnPj = null; state.mpPj = null; state.fThn = 'all'; state.fBln = 'all';
    state.navQ = ''; $('#nav-q').value = '';
    if (view === 'actlog') state.logF = { modul: 'all', aksi: 'all', user: 'all', from: '', to: '', refId: null, ref: '' };
    document.body.classList.remove('nav-open');
    render();
    window.scrollTo(0, 0);
  }

  state.navSub = {}; // pilihan buka/tutup sub menu oleh pengguna; tanpa pilihan: terbuka bila berisi menu aktif
  const navSubOpen = (g, navView, kw) => !!kw || (g.group in state.navSub ? state.navSub[g.group] : g.items.some(([v]) => v === navView));
  function renderNav() {
    const navView = SUBVIEW[state.view] ? SUBVIEW[state.view][0] : state.view;
    // Search menu: cocokkan nama menu di semua modul; saat mencari, modul selalu terbuka.
    // Label sub modul tidak ditampilkan di sidebar (input/Master - Buat Program/2026-10-28-sidebar-tanpa-label/); grup hanya pengelompokan urutan menu.
    // To be: modul ber-flag sub (Master Data) menampilkan sub menu yang dapat dibuka/ditutup (input/Master - Buat Program/2026-11-07-sub-menu-master-data/).
    // Sub yang berisi menu aktif terbuka otomatis; saat mencari menu, semua sub terbuka.
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
        const list = items.map(([v, label]) => {
          const n = countOf(v);
          return `<button class="nav-item ${v === navView ? 'active' : ''}" data-view="${v}">${mark(label)}${n === null ? '' : `<span class="nav-count">${n}</span>`}</button>`;
        }).join('');
        if (!items.length) return '';
        if (!m.sub) return `<div class="nav-group">${list}</div>`;
        const sopen = navSubOpen(g, navView, kw), sid = `nav-sub-${m.groups.indexOf(g)}`;
        return `<div class="nav-sub ${sopen ? 'open' : ''}"><button class="nav-sub-head" data-act="nav-sub" data-sub="${esc(g.group)}" data-open="${sopen ? 1 : 0}" aria-expanded="${sopen}" aria-controls="${sid}"><span>${esc(g.group)}</span><i class="caret"></i></button>
          <div class="nav-sub-body" id="${sid}">${list}</div></div>`;
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

    let mod, label, grp;
    MODULES.forEach(m => m.groups.forEach(g => g.items.forEach(([v, l]) => { if (v === navView) { mod = m; label = l; grp = m.sub ? g.group : ''; } })));
    let leaf = label;
    if (state.view === 'combine') leaf = 'Tambah Product';
    if (state.view === 'ivcForm') leaf = 'Add Invoicing';
    if (state.view === 'pbProg') leaf = (pjById(state.pbPj) || {}).nomor || 'Program';
    if (state.view === 'pbForm') leaf = state.pbf ? `${(pjById(state.pbf.pj) || {}).nomor} / Add Pengajuan` : 'Add Pengajuan';
    if (state.view === 'qtnDetail') leaf = (db.qtn.find(x => x.id === state.qtnView) || {}).nomor || 'Detail';
    if (state.view === 'sktDetail') leaf = `Surat Kerja Trainer / ${(db.skt.find(x => x.id === state.sktView) || {}).nomor || ''}`;
    if (state.view === 'soProg') leaf = (db.quot.find(x => x.id === state.soPj) || {}).nomor || 'Program';
    if (state.view === 'soDetail') { const s = db.so.find(x => x.id === state.soView); leaf = s ? `${(db.quot.find(x => x.id === s.pj) || {}).nomor} / ${s.nomor}` : 'Detail'; }
    if (state.view === 'mpForm' || state.view === 'qtForm') leaf = state.pf.isNew ? (state.view === 'mpForm' ? 'Buat Master Program' : 'Buat Penjualan Program') : state.pf.p.nomor;
    $('#crumb').innerHTML = `${mod.name} / ${grp ? `${esc(grp)} / ` : ''}${state.view in SUBVIEW ? `${label} / ` : ''}<b>${esc(leaf)}</b>`;
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
  // Filter tahun & bulan program menurut tanggal mulai acara (to be: input/Master - Buat Program/2026-11-07-filter-tahun-bulan/).
  // Dipakai di Penjualan Program, Quotation, dan Sales Order. Program tanpa tanggal hanya tampil bila tahun & bulan = Semua.
  state.fThn = 'all'; state.fBln = 'all';
  const BULAN_F = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const ymOk = iso => (state.fThn === 'all' || String(iso || '').slice(0, 4) === state.fThn) && (state.fBln === 'all' || String(iso || '').slice(5, 7) === state.fBln);
  const ymFilter = isos => {
    const yrs = [...new Set(isos.filter(Boolean).map(d => d.slice(0, 4)))].sort().reverse();
    return selectFilter('fthn', 'Filter tahun program', state.fThn, [['all', 'Semua tahun'], ...yrs.map(y => [y, y])])
      + selectFilter('fbln', 'Filter bulan program', state.fBln, [['all', 'Semua bulan'], ...BULAN_F.map((b, i) => [String(i + 1).padStart(2, '0'), b])]);
  };

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

