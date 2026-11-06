/* Labmania mockup — Master Data: atribut produk, Product Combine, Katalog Produk, wilayah, Peserta, Company, Master User, Master Role, Trainer, template dokumen, Master Rate.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
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
  // (input/Invoice & Pengajuan/2026-11-04-role-permission/). Division dan Jabatan (Position) diganti satu field Role dari
  // Master Role (input/Invoice & Pengajuan/2026-11-05-master-role-override/; FR-37, FR-64).
  const GENDER = ['Laki-laki', 'Perempuan'];
  const RELIGION = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
  const EMP_TYPE = ['Tetap', 'Kontrak', 'Magang', 'Freelance'];
  const QUSR = q('Q-25', 'Daftar Role dan Employee Type di mockup hanya contoh. Isi final mengikuti data Labmania.');

  // ---------- Master Role (FR-64; tabel existing `groups`) ----------
  // Role diinput manual. Superadmin = role bawaan sistem (akun developer, group id 1): terkunci, tidak dapat dipilih untuk user lain.
  const SUPER_ROLE = 'Superadmin';
  db.role = [{ nama: SUPER_ROLE, sistem: true }, { nama: 'Direktur' }, { nama: 'Admin' }, { nama: 'Sales Public' }, { nama: 'Sales B2B' }, { nama: 'Trainer' }, { nama: 'Keuangan' }]
    .map(r => Object.assign({ id: uid() }, r));
  const roleById = id => db.role.find(r => r.id === id);
  const roleIdOf = nama => (db.role.find(r => r.nama === nama) || {}).id;
  const roleOf = u => (roleById(u.role) || {}).nama || '';
  const roleUsers = id => db.user.filter(u => u.role === id);
  const roleLabel = nama => !nama ? '—' : nama === SUPER_ROLE ? `${nama} (bawaan sistem)` : nama;

  db.user = [
    { kode: 'EMP-001', nama: 'Contoh User Satu', nik: '3275010101800001', tmpLahir: 'Bekasi', tglLahir: '1980-01-01', role: roleIdOf('Direktur'), gender: 'Laki-laki', agama: 'Islam',
      telp: '+6281211110000', email: 'user1@contoh.id', alamat: 'Jl. Contoh No. 1, Bekasi', npwp: '', tglMasuk: '2020-01-02', tipe: 'Tetap', jobdesc: 'Menyetujui Master & Penjualan Program', aktif: true,
      akun: true, username: 'user.satu' },
    { kode: 'EMP-002', nama: 'Contoh User Dua', nik: '', tmpLahir: '', tglLahir: '', role: roleIdOf('Sales B2B'), gender: 'Perempuan', agama: '',
      telp: '+6281322220000', email: 'user2@contoh.id', alamat: '', npwp: '', tglMasuk: '2024-03-01', tipe: 'Kontrak', jobdesc: '', aktif: true,
      akun: true, username: 'user.dua', ovr: { katalog: { v: true }, mprog: { v: true } } },
    { kode: 'EMP-003', nama: 'Contoh Trainer Tiga', nik: '', tmpLahir: '', tglLahir: '', role: roleIdOf('Trainer'), gender: 'Laki-laki', agama: '',
      telp: '+6285733330000', email: '', alamat: '', npwp: '', tglMasuk: '', tipe: 'Freelance', jobdesc: 'Trainer lepas', aktif: false,
      akun: false, username: '' },
    { kode: 'EMP-004', nama: 'Contoh Admin Developer', nik: '', tmpLahir: '', tglLahir: '', role: roleIdOf(SUPER_ROLE), gender: 'Laki-laki', agama: '',
      telp: '+6281244440000', email: 'dev@contoh.id', alamat: '', npwp: '', tglMasuk: '2025-01-06', tipe: 'Tetap', jobdesc: 'Admin sistem & developer', aktif: true,
      akun: true, username: 'admin.dev', superadmin: true }
  ].map(u => Object.assign({ id: uid() }, u));

  const viewRole = () => `${pageHead('Master Role', 'Daftar role yang dipakai di Master User dan diatur haknya di Role & Permission. Role diinput manual. Role yang masih dipakai user tidak dapat dihapus.', 'FR-64',
    '<button class="btn btn-accent" data-act="role-add">+ Tambah Role</button>')}
    <div class="card">${toolbar('Cari nama role…', false)}<div id="list">${listRole()}</div></div>`;
  function listRole() {
    const rows = db.role.filter(r => matches([r.nama]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nama Role', render: r => `<b>${esc(r.nama)}</b>${r.sistem ? ' <span class="tag">Bawaan sistem</span><br><span class="muted">Akun developer, semua hak. Tidak dapat diubah atau dipilih untuk user lain.</span>' : ''}` },
      { label: 'User terdaftar', cls: 'nowrap', render: r => `${roleUsers(r.id).length} user` },
      { label: 'Hak di Role & Permission', cls: 'nowrap', render: r => r.sistem ? '<span class="badge on">Semua hak aktif</span>'
        : akCount(db.akses[r.id]) ? `<span class="badge on">${akCount(db.akses[r.id])} hak diberikan</span>` : '<span class="badge off">Belum diatur</span>' },
      { label: 'Action', cls: 'act', render: r => r.sistem ? '<span class="muted">Terkunci</span>'
        : gearMenu(r, [['role-edit', 'Edit'], ['role-akses', 'Atur Akses']].concat(roleUsers(r.id).length ? [] : [['role-del', 'Hapus', true]])) }
    ]);
  }
  function roleForm(rec) {
    openModal({
      title: rec ? `Edit Role ${esc(rec.nama)}` : 'Tambah Role',
      body: `${field('Nama Role' + req, `<input class="input" name="nama" value="${esc(rec ? rec.nama : '')}" maxlength="50" placeholder="mis. Sales B2B">`)}
        <p class="hint">${rec ? `${roleUsers(rec.id).length} user memakai role ini; namanya ikut berubah di Master User.` : 'Role baru belum punya hak. Atur haknya di Role & Permission setelah disimpan.'}</p>`,
      onSave: f => {
        const nama = f.nama.value.trim();
        if (!nama) return invalid(f.nama, 'Nama Role wajib diisi');
        if (db.role.some(r => r !== rec && r.nama.toLowerCase() === nama.toLowerCase())) return invalid(f.nama, 'Nama Role sudah dipakai');
        if (rec) { rec.nama = nama; toast(`Role ${nama} diperbarui`); }
        else { db.role.push({ id: uid(), nama }); toast(`Role ${nama} ditambahkan. Atur haknya di Role & Permission.`); }
      }
    });
  }
  function roleDel(r) {
    if (r.sistem || roleUsers(r.id).length) return toast('Role masih dipakai user, tidak dapat dihapus', 'warn');
    confirmDelete(`role ${r.nama}`, () => { db.role = db.role.filter(x => x !== r); delete db.akses[r.id]; toast(`Role ${r.nama} dihapus`); });
  }

  const viewUser = () => `${pageHead('Master User', 'Data karyawan Labmania dan akun login ke sistem. <b>Role</b> dipilih dari Master Role; hak aksesnya diatur di <b>Role & Permission</b>. <b>Override Permission</b> memberi satu user hak menu tambahan di luar role-nya. User nonaktif tidak dapat login; data tidak dihapus.', 'FR-36 – FR-39 · FR-65',
    '<button class="btn btn-accent" data-act="user-add">+ Tambah User</button>')}
    <div class="card">${toolbar('Cari ID, nama, username, email, atau role…', true,
      selectFilter('fjenis', 'Filter role', state.jenis, [['all', 'Semua role'], ...db.role.map(r => [String(r.id), r.nama])]))}
      <div id="list">${listUser()}</div></div>`;

  function listUser() {
    const rows = db.user.filter(r => statusOk(r) && (state.jenis === 'all' || String(r.role) === state.jenis) &&
      matches([r.kode, r.nama, r.username, r.email, roleOf(r)]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Employee ID', cls: 'nowrap', render: r => `<span class="code">${esc(r.kode)}</span>` },
      { label: 'Nama', render: r => `${esc(r.nama)}<br><span class="muted">${esc(r.email || r.telp)}</span>` },
      { label: 'Role', render: r => `${esc(roleLabel(roleOf(r)))}<br><span class="muted">${esc(r.tipe)}</span>` },
      { label: 'Akun login', render: r => r.akun ? `<span class="code">${esc(r.username)}</span>${akCount(r.ovr) ? `<br><span class="tag" title="Hak menu tambahan di luar role">Override ${akCount(r.ovr)} hak</span>` : ''}` : '<span class="muted">Tanpa akun</span>' },
      STATUS_COL,
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['user-edit', 'Edit'], toggleItem(r)].concat(r.akun ? [['user-reset', 'Reset Password']] : [])) }
    ]);
  }

  function userForm(rec) {
    const r = rec || { kode: '', nama: '', nik: '', tmpLahir: '', tglLahir: '', role: '', gender: '', agama: '', telp: '', email: '', alamat: '', npwp: '',
      tglMasuk: '', tipe: '', jobdesc: '', aktif: true, akun: true, username: '' };
    const inp = (name, val, extra = '') => `<input class="input" name="${name}" value="${esc(val)}" ${extra}>`;
    const sel = (name, list, val) => `<select class="input" name="${name}"><option value="">Choose one</option>${opts(list, val)}</select>`;
    const sec = t => `<div class="form-sec span2">${t}</div>`;
    const O = clone(r.ovr || {}); // Override Permission yang sedang diedit (FR-65)
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
          ${field('Role' + req + QUSR, r.superadmin
            ? `<input class="input" value="${esc(SUPER_ROLE)}" readonly><input type="hidden" name="role" value="${r.role}"><span class="hint">Role bawaan sistem, tidak dapat diganti</span>`
            : `<select class="input" name="role"><option value="">Choose one</option>${db.role.filter(x => !x.sistem).map(x => `<option value="${x.id}" ${x.id === r.role ? 'selected' : ''}>${esc(x.nama)}</option>`).join('')}</select><span class="hint">Dari Master Role; hak akses diatur di Role & Permission</span>`)}
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
          ${field('Password' + (rec && rec.akun ? '' : req), `<input class="input" name="password" type="password" autocomplete="new-password">${rec && rec.akun ? '<span class="hint">Kosongkan bila tidak diubah</span>' : '<span class="hint">Minimal 8 karakter</span>'}`)}
          ${field('Konfirmasi Password' + (rec && rec.akun ? '' : req), '<input class="input" name="password2" type="password" autocomplete="new-password">')}
          ${r.superadmin ? '' : `<details class="ket-acc ovr-box span2" ${akCount(r.ovr) ? 'open' : ''}>
            <summary><b>Override Permission</b><span class="muted" data-ovrcnt></span></summary>
            <div class="ovr-body"><p class="hint top">Hak menu tambahan khusus untuk user ini, di luar hak role-nya (FR-65). Hak yang sudah dimiliki role tercentang dan terkunci. Override hanya menambah hak, tidak mengurangi.</p>
              <div class="table-wrap ak-wrap"><table class="table ak-tbl"><thead><tr><th>Modul / Menu</th><th>Hak akses</th></tr></thead><tbody data-ovr></tbody></table></div></div>
          </details>`}
        </div>
      </div>`,
      onMount: form => {
        const f = form.elements;
        f.akun.addEventListener('change', e => { form.querySelector('[data-akun]').classList.toggle('hidden', !e.target.checked); });
        if (r.superadmin) return;
        // Override Permission: matrix menu × aksi; hak dari role terkunci, sisanya dapat ditambahkan untuk user ini.
        const fromRole = (v, k) => !!(((db.akses[+f.role.value] || {})[v] || {})[k]);
        const drawOvr = () => {
          const cnt = akCount(O);
          form.querySelector('[data-ovrcnt]').textContent = cnt ? `${cnt} hak tambahan` : 'Belum ada hak tambahan';
          form.querySelector('[data-ovr]').innerHTML = akMenus().map(m => `<tr class="ak-mod"><td colspan="2">${esc(m.mod)}</td></tr>` + m.items.map(([v, l]) =>
            `<tr><td><b>${esc(l)}</b></td><td><div class="ak-chips">${akActsOf(v).map(k => {
              const lbl = AK_ACTS.find(a => a[0] === k)[1];
              if (fromRole(v, k)) return `<label class="ak-chip role" title="Sudah dari role"><input type="checkbox" checked disabled>${lbl} <span class="code">role</span></label>`;
              const on = !!((O[v] || {})[k]);
              return `<label class="ak-chip ${on ? 'on' : ''}"><input type="checkbox" data-ok="${v}" data-oa="${k}" ${on ? 'checked' : ''}>${lbl}</label>`;
            }).join('')}</div></td></tr>`).join('')).join('');
        };
        form.addEventListener('change', e => {
          const t = e.target;
          if (t.name === 'role') {
            // Hak yang kini sudah diberikan role baru tidak perlu disimpan sebagai override.
            Object.keys(O).forEach(v => { Object.keys(O[v]).forEach(k => { if (fromRole(v, k)) delete O[v][k]; }); if (!Object.keys(O[v]).length) delete O[v]; });
          } else if (t.dataset.ok) {
            const v = t.dataset.ok, k = t.dataset.oa, x = O[v] = O[v] || {};
            if (t.checked) { x[k] = true; if (k !== 'v' && !fromRole(v, 'v')) x.v = true; }
            else if (k === 'v') Object.keys(x).forEach(a => { delete x[a]; });
            else delete x[k];
            if (!Object.keys(x).length) delete O[v];
          } else return;
          drawOvr();
        });
        drawOvr();
      },
      onSave: f => {
        const keys = ['kode', 'nama', 'nik', 'tmpLahir', 'tglLahir', 'gender', 'agama', 'telp', 'email', 'alamat', 'npwp', 'tglMasuk', 'tipe', 'jobdesc', 'username'];
        const v = {};
        keys.forEach(k => { v[k] = f[k].value.trim(); });
        v.role = +f.role.value || '';
        v.aktif = f.aktif.value === '1';
        v.akun = f.akun.checked;
        const pw = f.password.value, pw2 = f.password2.value;
        let ok = true;
        const LBL = { kode: 'Employee ID', nama: 'Employee Name', role: 'Role', gender: 'Gender', telp: 'Phone Number', tipe: 'Employee Type' };
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
        // Override Permission hanya berlaku untuk user berakun login (FR-65).
        const ovr = v.akun && !r.superadmin && akCount(O) ? clone(O) : null;
        const roleMsg = ovr ? `, override ${akCount(ovr)} hak` : '';
        if (rec) {
          Object.assign(rec, v);
          if (ovr) rec.ovr = ovr; else delete rec.ovr;
          toast(`User ${rec.kode} diperbarui${pw ? ' (password diganti)' : ''}${roleMsg}`);
        } else {
          const u = Object.assign({ id: uid() }, v, ovr ? { ovr } : {});
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

