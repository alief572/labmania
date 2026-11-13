/* Labmania — Modul Produk (mockup)
   Acuan perilaku: specs/docs/fr-nfr.md draf 2. Data disimpan di memori saja.
   Penanda Q-xx = pertanyaan terbuka yang belum dijawab Labmania. */
(function () {
  'use strict';

  // ---------- Helpers ----------
  let uidSeq = 0;
  const uid = () => ++uidSeq;
  const $ = (sel, root = document) => root.querySelector(sel);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const stripTags = s => String(s).replace(/<[^>]+>/g, '').trim();

  // Format kode belum ditetapkan (Q-01) — prefix + 3 digit hanya contoh.
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

  const COA = ['6101 · Biaya Trainer', '6102 · Biaya Transport & Akomodasi', '6103 · Biaya Konsumsi',
    '6104 · Biaya Perlengkapan Training', '6105 · Biaya Pemasaran', '6106 · Biaya Administrasi'];

  const seedRate = (prefix, rows) => rows.map(([item, satuan, coa]) => ({ id: uid(), kode: nextCode(prefix), item, satuan, coa }));
  db.rate = {
    fixed: seedRate('FC', [
      ['Transport Trainer Luar Kota PP', 'Event', COA[1]], ['Marketing/Ads', 'Event', COA[4]],
      ['Dokumentasi', 'Event', COA[5]], ['Banner/Promosi', 'Event', COA[4]], ['Administrasi', 'Event', COA[5]],
      ['Mandays Rate Charge Labmania', 'Day', COA[0]], ['Honor Trainer', 'Day', COA[0]],
      ['Hotel Trainer **', 'Day', COA[1]], ['Hotel Trainer ***', 'Day', COA[1]],
      ['Konsumsi Trainer, di luar konsumsi selama training', 'Day', COA[2]]
    ]),
    variable: seedRate('VC', [
      ['Modul', 'Peserta/Event', COA[3]], ['Sertifikat', 'Peserta/Event', COA[3]], ['Seminar Kit', 'Peserta/Event', COA[3]],
      ['Goodybag', 'Peserta/Event', COA[3]], ['Merchandise', 'Peserta/Event', COA[3]], ['Sertifikasi LSP', 'Peserta/Event', COA[0]],
      ['Ongkir Dokumen', 'Peserta/Event', COA[5]], ['Makan Siang Training', 'Peserta/Day', COA[2]], ['Coffee Break', 'Peserta/Day', COA[2]]
    ])
  };
  // Satuan dibatasi per tab (keputusan user 23-10-2026): tidak ada Hours.
  const RATE_TAB = {
    fixed: { title: 'Fixed Cost', prefix: 'FC', fr: 'FR-20', satuan: ['Event', 'Day'] },
    variable: { title: 'Variable Cost', prefix: 'VC', fr: 'FR-19', satuan: ['Peserta/Event', 'Peserta/Day'] }
  };

  // ---------- State & navigation ----------
  const state = { view: 'jenisProduk', q: '', status: 'all', page: 1, rateTab: 'fixed', form: null, navOpen: true, navQ: '' };

  // Satu modul "Master Data"; grup di bawahnya adalah sub modul.
  const MODULE = 'Master Data';
  const NAV = [
    { group: 'Atribut Produk', items: [['jenisProduk', 'Jenis Produk'], ['jenisSertifikasi', 'Jenis Sertifikasi'], ['jenisBidang', 'Jenis Bidang'], ['varian', 'Varian Pelaksanaan']] },
    { group: 'Produk', items: [['paket', 'Product Combine'], ['katalog', 'Katalog Produk']] },
    { group: 'Peserta & Rate', items: [['peserta', 'Master Peserta'], ['rate', 'Master Rate']] }
  ];
  const countOf = v => MASTERS[v] ? db[v].length
    : v === 'paket' ? db.paket.length
      : v === 'peserta' ? db.peserta.length
        : v === 'rate' ? db.rate.fixed.length + db.rate.variable.length : null;

  function go(view) {
    state.view = view; state.q = ''; state.status = 'all'; state.page = 1;
    state.navQ = ''; $('#nav-q').value = '';
    document.body.classList.remove('nav-open');
    render();
    window.scrollTo(0, 0);
  }

  function renderNav() {
    const navView = state.view === 'combine' ? 'paket' : state.view;
    // Search menu: cocokkan nama menu atau nama sub modul; saat mencari, modul selalu terbuka.
    const kw = state.navQ.trim().toLowerCase();
    const mark = s => {
      const i = kw ? s.toLowerCase().indexOf(kw) : -1;
      return i < 0 ? esc(s) : `${esc(s.slice(0, i))}<mark>${esc(s.slice(i, i + kw.length))}</mark>${esc(s.slice(i + kw.length))}`;
    };
    const groups = NAV.map(g => {
      const items = !kw || g.group.toLowerCase().includes(kw) ? g.items
        : g.items.filter(([, label]) => label.toLowerCase().includes(kw));
      return items.length ? `<div class="nav-group"><div class="nav-label">${mark(g.group)}</div>${items.map(([v, label]) => {
        const n = countOf(v);
        return `<button class="nav-item ${v === navView ? 'active' : ''}" data-view="${v}">${mark(label)}${n === null ? '' : `<span class="nav-count">${n}</span>`}</button>`;
      }).join('')}</div>` : '';
    }).join('') || '<div class="nav-empty">Menu tidak ditemukan.</div>';
    $('#nav').innerHTML = `<div class="nav-module ${state.navOpen || kw ? 'open' : ''}">
      <button class="nav-module-head" data-act="nav-module" aria-expanded="${state.navOpen}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>
        <span>${MODULE}</span><i class="caret"></i>
      </button>
      <div class="nav-module-body">${groups}</div>
    </div>`;
    const group = NAV.find(g => g.items.some(([v]) => v === navView));
    const label = group.items.find(([v]) => v === navView)[1];
    $('#crumb').innerHTML = `${MODULE} / ${group.group} / <b>${state.view === 'combine' ? 'Tambah Product' : label}</b>`;
  }

  // ---------- Shared view pieces ----------
  const pageHead = (title, desc, ref, actions = '') =>
    `<div class="page-head"><div><h1>${title}<span class="ref">${ref}</span></h1><p>${desc}</p></div>${actions ? `<div class="page-actions">${actions}</div>` : ''}</div>`;

  const badge = aktif => `<span class="badge ${aktif ? 'on' : 'off'}">${aktif ? 'Aktif' : 'Nonaktif'}</span>`;

  const field = (label, control, err = '', cls = '') =>
    `<div class="field ${err ? 'has-err' : ''} ${cls}"><label>${label}</label>${control}${err ? `<div class="err"><i>!</i>${esc(err)}</div>` : ''}</div>`;

  function toolbar(placeholder, withStatus = true) {
    return `<div class="toolbar">
      <div class="search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" class="input" id="q" placeholder="${placeholder}" value="${esc(state.q)}" aria-label="Cari">
      </div>
      ${withStatus ? `<select class="input sm" id="status" aria-label="Filter status">
        <option value="all" ${state.status === 'all' ? 'selected' : ''}>Semua status</option>
        <option value="on" ${state.status === 'on' ? 'selected' : ''}>Aktif</option>
        <option value="off" ${state.status === 'off' ? 'selected' : ''}>Nonaktif</option>
      </select>` : ''}
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
      ? slice.map((r, i) => `<tr class="${r.aktif === false ? 'is-off' : ''}">${cols.map(c =>
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
  const STATUS_COL = { label: 'Status', render: r => badge(r.aktif) };
  // Kolom Action: ikon gear, opsi muncul di menu melayang (di luar .table-wrap agar tidak terpotong).
  const GEAR = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>';
  const gearMenu = (r, items) => `<button class="gear" data-act="gear" aria-haspopup="menu" aria-label="Opsi">${GEAR}</button>
    <template>${items.map(([act, label, danger]) => `<button class="menu-item ${danger ? 'danger' : ''}" role="menuitem" data-act="${act}" data-id="${r.id}">${label}</button>`).join('')}</template>`;
  const toggleItem = r => ['toggle', r.aktif ? 'Nonaktifkan' : 'Aktifkan'];

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
        + field(`${m.form} <span class="req">*</span>`, `<input class="input" name="nama" maxlength="100" value="${esc(rec ? rec.nama : '')}" placeholder="Ketik ${m.form.toLowerCase()}">`),
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
    return `${pageHead('Product Combine', 'Daftar paket produk hasil combine. Paket berstatus Aktif siap dibuat menjadi Product Costing.', 'FR-10 · FR-23 · FR-24',
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
    const opts = db[a.key].filter(r => r.aktif)
      .map(r => `<option value="${r.id}" ${r.id === value ? 'selected' : ''}>${esc(r.nama)}</option>`).join('');
    return `<select class="input" data-row="${i}" data-field="${a.field}" aria-label="${a.label} baris ${i + 1}"><option value="">— Pilih ${a.label} —</option>${opts}</select>`;
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
        ${field('Judul <span class="req">*</span>', `<input class="input" id="cf-judul" maxlength="150" value="${esc(f.judul)}" placeholder="Judul / topik paket, diisi manual">`, f.judulErr)}
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

  // ---------- Master Peserta (FR-15 – FR-17) ----------
  const Q16 = q('Q-16', 'Apakah Peserta dan item Rate juga hanya boleh dinonaktifkan? Mockup mengikuti mockup klien: Edit/Delete.');

  const viewPeserta = () => `${pageHead('Master Peserta', 'Data peserta pelatihan/sertifikasi, dikelompokkan menurut Jenis Bidang.', 'FR-15 · FR-16 · FR-17',
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

  const kotaOptions = (prov, selected) => prov
    ? `<option value="">— Pilih Kota/Kabupaten —</option>${WILAYAH[prov].map(k => `<option ${k === selected ? 'selected' : ''}>${esc(k)}</option>`).join('')}`
    : '<option value="">Pilih provinsi dahulu</option>';

  function pesertaForm(rec) {
    const r = rec || { nama: '', gender: '', instansi: '', bidang: '', provinsi: '', kota: '', telp: '', email: '' };
    const req = ' <span class="req">*</span>';
    const bidangOpts = db.jenisBidang.filter(b => b.aktif || b.id === r.bidang)
      .map(b => `<option value="${b.id}" ${b.id === r.bidang ? 'selected' : ''}>${esc(b.nama)}${b.aktif ? '' : ' (nonaktif)'}</option>`).join('');
    const provOpts = Object.keys(WILAYAH).map(p => `<option ${p === r.provinsi ? 'selected' : ''}>${esc(p)}</option>`).join('');

    openModal({
      title: rec ? `Edit Peserta ${rec.kode}` : 'Tambah Master Peserta',
      wide: true,
      body: `<div class="grid2">
        ${field('Kode Peserta' + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode('PS'))}" readonly>`, '', 'span2')}
        ${field('Nama Peserta' + req, `<input class="input" name="nama" value="${esc(r.nama)}" maxlength="100">`)}
        ${field('Gender' + req, `<select class="input" name="gender"><option value="">— Pilih —</option>${['Laki-laki', 'Perempuan'].map(g => `<option ${g === r.gender ? 'selected' : ''}>${g}</option>`).join('')}</select>`)}
        ${field('Instansi/Perusahaan' + req, `<input class="input" name="instansi" value="${esc(r.instansi)}" maxlength="150">`)}
        ${field('Bidang' + req, `<select class="input" name="bidang"><option value="">— Pilih Jenis Bidang —</option>${bidangOpts}</select>`)}
        ${field('Lokasi (Provinsi)' + req + q('Q-07b', 'Sumber data provinsi & kota belum ditetapkan. Data di mockup hanya contoh.'), `<select class="input" name="provinsi"><option value="">— Pilih Provinsi —</option>${provOpts}</select>`)}
        ${field('Kota/Kabupaten' + req, `<select class="input" name="kota" ${r.provinsi ? '' : 'disabled'}>${kotaOptions(r.provinsi, r.kota)}</select><span class="hint">Tersaring menurut provinsi</span>`)}
        ${field('No Telp/WhatsApp' + req, `<input class="input" name="telp" inputmode="tel" value="${esc(r.telp)}" placeholder="08xxxxxxxxxx">`)}
        ${field('Email' + req, `<input class="input" name="email" type="email" value="${esc(r.email)}" placeholder="nama@domain.com">`)}
      </div>`,
      onMount: form => {
        form.elements.provinsi.addEventListener('change', e => {
          const kota = form.elements.kota;
          kota.innerHTML = kotaOptions(e.target.value, '');
          kota.disabled = !e.target.value;
        });
      },
      onSave: f => {
        const v = {};
        ['nama', 'gender', 'instansi', 'bidang', 'provinsi', 'kota', 'telp', 'email'].forEach(k => { v[k] = f[k].value.trim(); });
        let ok = true;
        const labels = { nama: 'Nama Peserta', gender: 'Gender', instansi: 'Instansi/Perusahaan', bidang: 'Bidang', provinsi: 'Provinsi', kota: 'Kota/Kabupaten', telp: 'No Telp/WhatsApp', email: 'Email' };
        Object.keys(labels).forEach(k => { if (!v[k]) ok = invalid(f[k], `${labels[k]} wajib diisi`); });
        // NFR-06 (usulan): format email & nomor telepon.
        if (v.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email)) ok = invalid(f.email, 'Format email tidak valid');
        if (v.telp && !/^\+?\d{9,15}$/.test(v.telp.replace(/[\s-]/g, ''))) ok = invalid(f.telp, 'Nomor hanya angka, 9–15 digit');
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

  // ---------- Master Rate (FR-18 – FR-20, FR-25) ----------
  function viewRate() {
    const tabs = Object.keys(RATE_TAB).map(t =>
      `<button class="tab ${t === state.rateTab ? 'active' : ''}" data-act="rate-tab" data-tab="${t}">${RATE_TAB[t].title}<span class="nav-count">${db.rate[t].length}</span></button>`).join('');
    const tab = RATE_TAB[state.rateTab];
    return `${pageHead('Master Rate', 'Item biaya yang menjadi input Product Costing.', 'FR-18 · ' + tab.fr,
      `<button class="btn btn-accent" data-act="rate-add">+ Tambah ${tab.title}</button>`)}
      <div class="notice"><span>Satuan <b>Fixed Cost</b>: Event, Day. Satuan <b>Variable Cost</b>: Peserta/Event, Peserta/Day.
        Tempat input nominal tarif belum ditetapkan ${q('Q-08b', 'Nominal tarif diinput di Master Rate atau di Product Costing?')}.</span><span class="ref">FR-25</span></div>
      <div class="card"><div class="tabs" role="tablist">${tabs}</div>${toolbar('Cari kode atau item…', false)}<div id="list">${listRate()}</div></div>`;
  }

  function listRate() {
    const t = RATE_TAB[state.rateTab];
    const rows = db.rate[state.rateTab].filter(r => matches([r.kode, r.item, r.satuan]));
    return listBlock(rows, [
      NO_COL,
      { label: `Kode ${t.title}` + Q01, render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Item', render: r => esc(r.item) },
      { label: 'Satuan', render: r => esc(r.satuan) },
      { label: 'Action' + Q16, cls: 'act', render: r => gearMenu(r, [['rate-edit', 'Edit'], ['rate-del', 'Delete', true]]) }
    ]);
  }

  function rateForm(rec) {
    const tabKey = state.rateTab;
    const t = RATE_TAB[tabKey];
    const r = rec || { item: '', satuan: '', coa: '' };
    const req = ' <span class="req">*</span>';
    const opts = (list, sel) => list.map(x => `<option ${x === sel ? 'selected' : ''}>${esc(x)}</option>`).join('');
    openModal({
      title: `${rec ? 'Edit' : 'Tambah'} ${t.title}`,
      body: field(`Kode ${t.title}` + Q01, `<input class="input" value="${esc(rec ? rec.kode : peekCode(t.prefix))}" readonly><span class="hint">Diisi otomatis oleh sistem</span>`)
        + field('Item' + req, `<input class="input" name="itemName" value="${esc(r.item)}" maxlength="150" placeholder="Nama item biaya">`)
        + field('Satuan' + req, `<select class="input" name="satuan"><option value="">— Pilih Satuan —</option>${opts(t.satuan, r.satuan)}</select>`)
        + field('COA' + req, `<select class="input" name="coa"><option value="">— Pilih COA —</option>${opts(COA, r.coa)}</select><span class="hint">Daftar COA di mockup hanya contoh; di aplikasi diambil dari master COA</span>`),
      onSave: f => {
        let ok = true;
        const v = { item: f.itemName.value.trim(), satuan: f.satuan.value, coa: f.coa.value };
        if (!v.item) ok = invalid(f.itemName, 'Item wajib diisi');
        if (!v.satuan) ok = invalid(f.satuan, 'Satuan wajib dipilih');
        if (!v.coa) ok = invalid(f.coa, 'COA wajib dipilih');
        if (!ok) return false;
        if (rec) {
          Object.assign(rec, v);
          toast(`${t.title} ${rec.kode} diperbarui`);
        } else {
          const n = Object.assign({ id: uid(), kode: nextCode(t.prefix) }, v);
          db.rate[tabKey].push(n);
          state.page = Math.ceil(db.rate[tabKey].length / PAGE_SIZE);
          toast(`${t.title} ${n.kode} ditambahkan`);
        }
      }
    });
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
  const LIST = { paket: listPaket, peserta: listPeserta, rate: listRate };
  const currentList = () => MASTERS[state.view] ? listMaster(state.view) : LIST[state.view]();

  function render() {
    renderNav();
    const v = state.view;
    $('#view').innerHTML = MASTERS[v] ? viewMaster(v)
      : v === 'paket' ? viewPaket()
        : v === 'combine' ? viewCombine()
          : v === 'katalog' ? viewKatalog()
            : v === 'peserta' ? viewPeserta()
              : viewRate();
  }

  // ---------- Events ----------
  const ACTIONS = {
    'nav-open': () => document.body.classList.add('nav-open'),
    'nav-close': () => document.body.classList.remove('nav-open'),
    'nav-module': () => { state.navOpen = !state.navOpen; renderNav(); },
    'master-add': () => masterForm(state.view),
    'master-edit': el => masterForm(state.view, findRec(state.view, +el.dataset.id)),
    toggle: el => {
      const list = state.view === 'paket' ? db.paket : db[state.view];
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
    'rate-tab': el => { state.rateTab = el.dataset.tab; state.q = ''; state.page = 1; render(); },
    'rate-add': () => rateForm(),
    'rate-edit': el => rateForm(db.rate[state.rateTab].find(r => r.id === +el.dataset.id)),
    'rate-del': el => {
      const list = db.rate[state.rateTab];
      const r = list.find(x => x.id === +el.dataset.id);
      confirmDelete(`${r.kode} — ${r.item}`, () => { list.splice(list.indexOf(r), 1); toast(`${r.kode} dihapus`); });
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

  document.addEventListener('input', e => {
    if (e.target.id === 'nav-q') { state.navQ = e.target.value; renderNav(); }
    if (e.target.id === 'q') { state.q = e.target.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (e.target.id === 'cf-judul') state.form.judul = e.target.value;
  });

  document.addEventListener('change', e => {
    if (e.target.id === 'status') { state.status = e.target.value; state.page = 1; $('#list').innerHTML = currentList(); }
    if (e.target.dataset.field && state.form) {
      state.form.rows[+e.target.dataset.row][e.target.dataset.field] = Number(e.target.value) || '';
    }
  });

  document.addEventListener('keydown', e => {
    // Enter di search menu = buka hasil pertama; Esc = kosongkan pencarian.
    if (e.target.id === 'nav-q') {
      if (e.key === 'Enter') { const first = $('#nav .nav-item'); if (first) go(first.dataset.view); return; }
      if (e.key === 'Escape' && state.navQ) { e.target.value = ''; state.navQ = ''; renderNav(); return; }
    }
    if (e.key === 'Escape') { closeGear(); closeModal(); document.body.classList.remove('nav-open'); }
  });

  window.addEventListener('resize', closeGear);
  window.addEventListener('scroll', closeGear, true);

  render();
})();
