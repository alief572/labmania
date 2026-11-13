/* Labmania mockup — Penjualan Program: Master Program dan Penjualan Program (daftar, form costing, status).
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Shared: Master Program & Penjualan Program ----------
  // Master Program dapat memuat lebih dari satu Product; costing tetap satu per Master Program (input/Master - Buat Program/2026-11-09-product-multi-reschedule-evidence/).
  // p.paket = daftar id Product. paketOf() mengembalikan satu objek untuk semua pemakai lama (judul, nomor, varian, produk):
  // satu Product = Product itu sendiri; lebih dari satu = gabungan (judul dan nomor digabung "+", atribut dari Product pertama).
  const paketIds = p => [].concat(p && p.paket || []).filter(Boolean);
  const paketList = p => paketIds(p).map(id => db.paket.find(x => x.id === id)).filter(Boolean);
  const paketOf = p => {
    const l = paketList(p);
    if (l.length <= 1) return l[0] || { judul: '—', nomor: '—', lingkup: [] };
    return Object.assign({}, l[0], { judul: l.map(x => x.judul).join(' + '), nomor: l.map(x => x.nomor).join(' + '), lingkup: [].concat(...l.map(x => x.lingkup || [])), list: l });
  };
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
      { label: 'Judul', cls: 'title', render: p => `${esc(paketOf(p).judul)}<br><span class="muted">${esc(paketOf(p).nomor)} · ${esc(findRec('varian', paketOf(p).varian).nama)} · ${esc(findRec('jenisSertifikasi', paketOf(p).sertifikasi).nama)}</span>` },
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
      + selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(QT_STATUS).map(s => [s, QT_STATUS[s]])])
      + ymFilter(db.quot.map(q => q.tglMulai)))}
      <div id="list">${listQuot()}</div></div>`;

  function qtGear(q) {
    const items = [['qt-open', q.status === 'draft' ? 'Edit' : 'Lihat']];
    if (q.status === 'draft') items.push(['qt-final', 'Finalkan'], ['qt-cancel', 'Batalkan', true]);
    if (q.status === 'final') items.push(...(canAddQtn(q) ? [['qtn-from-pj', '+ Buat Quotation']] : []), ...(canResched(q) ? [['qt-resched', 'Reschedule']] : []), ['qt-print', 'Cetak Surat Penawaran']);
    return gearMenu(q, items);
  }

  function listQuot() {
    const rows = db.quot.filter(q => (state.jenis === 'all' || q.jenis === state.jenis) &&
      (state.status === 'all' || q.status === state.status) && ymOk(q.tglMulai) &&
      matches([q.nomor, mpOf(q).nomor, paketOf(mpOf(q)).judul, q.lokasi]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor' + QPJ08, cls: 'nowrap', render: q => `<span class="code">${esc(q.nomor)}</span>` },
      { label: 'Jenis', render: q => jenisTag(q.jenis) + (q.jenis === 'b2b' && companyOf(q) ? `<br><span class="muted">${esc(companyOf(q).nama)}</span>` : '') },
      { label: 'Master Program', cls: 'title', render: q => `${esc(paketOf(mpOf(q)).judul)}<br><span class="muted">${esc(mpOf(q).nomor)}</span>` },
      { label: 'Pelaksanaan', render: q => `${fmtRange(q.tglMulai, q.tglSelesai)}${q.jamPembelajaran ? ` · ${q.jamPembelajaran} JP` : ''}<br><span class="muted">${esc(q.lokasi || '—')}${q.trainer ? ` · Trainer: ${esc((trainerById(q.trainer) || {}).nama || '—')}` : ''}${q.asisten ? ` · Asisten: ${esc(asistenNama(q.asisten))}` : ''}</span>${(q.reschedule || []).length ? `<br><span class="badge st-draft">Reschedule ${q.reschedule.length}×</span>` : ''}` },
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
      if (!isQt()) return paketIds(p).length ? `${paketIds(p).length > 1 ? `${paketIds(p).length} Product: ` : ''}${esc(paketOf(p).judul)} · ${Number(p.durasi) || 0} hari · ${nPeserta(p)} peserta · ${rp(p.hargaJual)}` : 'Paket Produk belum dipilih';
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
    if (qt && p.status === 'final') lockInfo = `Penjualan Program <b>Final</b> — terkunci. Buat <b>Quotation</b> dari menu Quotation (${b2b ? 'ke Company penjualan' : 'ke Company atau Personal'}) — persiapan invoice dengan peserta yang sudah fix.`
      + (canResched(p) ? ` Jadwal, trainer, dan asisten masih dapat diubah lewat <b>Reschedule</b> sampai ${fmtTgl(addHari(p.tglMulai, -RS_H))} (H-${RS_H})${hasDeal(p) ? '; quotation dan Sales Order program ini ikut naik revisi bila tanggal berubah' : ''}.` : p.closed ? ' Program sudah Closed, jadi jadwal tidak dapat di-reschedule.' : p.tglMulai && todayIso > addHari(p.tglMulai, -RS_H) ? ` Batas reschedule H-${RS_H} (${fmtTgl(addHari(p.tglMulai, -RS_H))}) sudah lewat, jadi jadwal tidak dapat diubah lagi.` : '');
    if (qt && p.status === 'batal') lockInfo = 'Penjualan Program dibatalkan dan tidak dapat diubah.';

    const header = `<div class="csec"><p class="csec-h">Header</p><div class="grid3">
      ${field(`No. ${qt ? 'penjualan' + QPJ08 : 'master program' + QMP02}`, `<input class="input" value="${esc(p.nomor || '')}" placeholder="otomatis saat disimpan" disabled>`)}
      ${field(qt ? 'Jenis penjualan' : 'Jenis', `<div class="static">${qt ? jenisTag(p.jenis) : '<span class="muted">Umum — Public/B2B dipilih di Penjualan Program</span>'}</div>`)}
      ${field('Status', `<div class="static">${stBadge(p.status)}${qt && p.status === 'final' ? ` <span class="muted">→ ${NEXT[p.jenis]}</span>` : ''}</div>`)}
    </div></div>`;

    let s1;
    if (!qt) {
      const ids = paketIds(p), terpilih = paketList(p);
      const paketOpts = db.paket.filter(x => x.aktif && !ids.includes(x.id))
        .map(x => `<option value="${x.id}">${esc(x.nomor)} — ${esc(x.judul)} · ${esc(findRec('varian', x.varian).nama)} · ${esc(findRec('jenisSertifikasi', x.sertifikasi).nama)}</option>`).join('');
      const rows = terpilih.map((x, i) => `<li class="mp-prod"><span><span class="code">${esc(x.nomor)}</span> <b>${esc(x.judul)}</b>
          <span class="hint">${ATTRS.map(a => `${a.label}: ${esc(findRec(a.key, x[a.field]).nama)}`).join(' · ')}${x.aktif ? '' : ' · nonaktif'}</span></span>
          ${locked ? '' : `<button type="button" class="btn btn-ghost sm danger-t" data-act="mp-prod-del" data-i="${i}" aria-label="Hapus ${esc(x.judul)} dari Master Program">Hapus</button>`}</li>`).join('');
      s1 = step(1, 'Informasi Program', '', `
        ${field(`Product${req} <span class="muted">(boleh lebih dari satu)</span>`, `${terpilih.length ? `<ol class="mp-prods">${rows}</ol>` : '<p class="muted mp-prods-empty">Belum ada Product dipilih.</p>'}
          ${locked ? '' : `<select class="input" id="mp-prod-add" aria-label="Tambah Product"><option value="">+ Tambah Product aktif…</option>${paketOpts}</select>`}
          <span class="hint">Costing tetap satu untuk seluruh Master Program (bagian 2 – 4), berapa pun Product yang dipilih.</span>`, E.paket)}
        ${simInputs(p, locked, E, 'angka baseline; bisa disesuaikan per Penjualan Program tanpa mengubah master. Juga tampil di bagian 4.')}`);
    } else {
      const mp = mpOf(p);
      // B2B: Company dipilih dari Master Company (jawaban Q-PJ-18); Public tidak terikat company.
      const co = b2b ? field('Company' + req, `<select class="input" data-pf="company" ${locked ? 'disabled' : ''}><option value="">— Pilih Company aktif —</option>${db.company.filter(c => c.aktif || c.id === p.company)
        .map(c => `<option value="${c.id}" ${c.id === p.company ? 'selected' : ''}>${esc(c.kode)} — ${esc(c.nama)}${c.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>${companyOf(p)
        ? `<span class="hint">${esc(companyOf(p).jenis)} · ${esc(companyOf(p).kota)} · PIC ${esc(companyOf(p).picNama || '—')}</span>` : '<span class="hint">dari Master Company</span>'}`, E.company) : '';
      s1 = step(1, 'Informasi Penjualan', '', `
        <div class="grid3">
          ${field('Master Program', `<div class="static"><span class="code">${esc(mp.nomor)}</span> ${esc(paketOf(mp).judul)} <span class="muted">· ${esc(findRec('varian', paketOf(mp).varian).nama)} · ${esc(findRec('jenisSertifikasi', paketOf(mp).sertifikasi).nama)}</span></div>`)}
          ${co}
          ${field('Tanggal mulai' + req, `<input class="input" type="date" data-pf="tglMulai" value="${esc(p.tglMulai)}" ${locked ? 'disabled' : ''}>`, E.tglMulai)}
          ${field('Tanggal selesai' + req, `<input class="input" type="date" data-pf="tglSelesai" value="${esc(p.tglSelesai)}" ${locked ? 'disabled' : ''}><span class="hint">durasi = selesai − mulai + 1 hari</span>`, E.tglSelesai)}
          ${field('Waktu' + q('Q-PJ-21', 'Jam pelaksanaan dibutuhkan Penawaran & Formulir Konfirmasi (template: pukul 09.00-15.15 WIB). Usulan: teks bebas di Penjualan Program.'), `<input class="input" data-pf="waktu" value="${esc(p.waktu || '')}" maxlength="50" placeholder="mis. 09.00–15.15 WIB" ${locked ? 'disabled' : ''}><span class="hint">Jam pelaksanaan; tercetak di Penawaran & Formulir Konfirmasi</span>`)}
          ${field('Jam Pembelajaran' + req, `<div class="input-unit"><input class="input" id="pf-jp" data-pf="jamPembelajaran" aria-label="Jam Pembelajaran" type="number" min="1" step="1" inputmode="numeric" value="${esc(p.jamPembelajaran)}" placeholder="mis. 16" aria-describedby="pf-jp-unit" ${locked ? 'disabled' : ''}><span class="unit" id="pf-jp-unit">JP</span></div><span class="hint">Tercetak di Penawaran quotation</span>`, E.jamPembelajaran)}
          ${field('Lokasi acara', `<input class="input" data-pf="lokasi" value="${esc(p.lokasi)}" maxlength="150" placeholder="mis. Offline Bekasi" ${locked ? 'disabled' : ''}>`)}
          ${trainerField(p, locked, E.trainer)}
          ${asistenField(p, locked, E.asisten)}
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
      actions += `${canResched(p) ? `<button type="button" class="btn btn-outline" data-act="qt-resched" data-id="${p.id}">Reschedule</button>` : ''}
        <button type="button" class="btn btn-accent" data-act="qt-print" data-id="${p.id}">Cetak Surat Penawaran</button>`;
    }

    const title = kind === 'mp' ? (isNew ? 'Buat Master Program' : `Master Program ${esc(p.nomor)}`) : (isNew ? `Buat Penjualan Program ${JENIS[p.jenis]}` : `Penjualan Program ${esc(p.nomor)}`);
    const ref = kind === 'mp' ? 'MP-FR-02 – MP-FR-10' : 'PJ-FR-04 – PJ-FR-10';
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button><h2>${title}</h2><span class="ref">${ref}</span></div>
      ${lockInfo ? `<div class="alert">${lockInfo}</div>` : ''}
      <div id="pf-warn" class="alert ${warns.length ? '' : 'hidden'}">${warns.map(esc).join('<br>')}</div>
      <div class="pub-form">${header}${s1}${qt ? reschedHistory(p) : ""}${s2}${s3}${s4}</div>
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
      ? { id: null, nomor: null, status: 'draft', paket: [], durasi: 1, targetPeserta: 10, hargaJual: '', targetProfit: '', biaya: db.rate.fixed.filter(r => r.aktif).map(r => biayaFromRate(r)) }
      : Object.assign({ id: null, nomor: null, status: 'draft', mp: '', jenis: '' }, preset || {});
    state.pf = { kind, p: rec ? clone(rec) : blank, isNew: !rec, errors: {}, collapsed: {} };
    state.view = kind === 'mp' ? 'mpForm' : 'qtForm';
    render();
    window.scrollTo(0, 0);
  }

  function validateCost() {
    const { kind, p } = state.pf, E = {};
    const posInt = v => v !== '' && Number.isInteger(Number(v)) && Number(v) > 0;
    if (kind === 'mp' && !paketIds(p).length) E.paket = 'Pilih minimal satu Product';
    if (!posInt(p.durasi)) E.durasi = 'Durasi bilangan bulat > 0';
    if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
    else if (kind === 'qt' && p.jenis === 'b2b' && Number(p.targetPeserta) < B2B_MIN) E.targetPeserta = `Penjualan B2B minimal ${B2B_MIN} peserta`;
    if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual / peserta wajib diisi';
    if (kind === 'qt' && p.jenis === 'b2b' && !p.company) E.company = 'Pilih Company';
    if (kind === 'qt' && !p.trainer) E.trainer = 'Pilih trainer'; // to be: wajib, untuk Surat Kerja Trainer
    if (kind === 'qt' && !p.asisten) E.asisten = 'Pilih asisten trainer'; // to be 12-11-2026: PJ-FR-30
    if (kind === 'qt' && !posInt(p.jamPembelajaran)) E.jamPembelajaran = 'Jam Pembelajaran bilangan bulat > 0'; // to be: PJ-FR-29
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
        <div class="summary">${sumRow('Program', `${esc(pk.judul)} <span class="muted">(${esc(findRec('varian', pk.varian).nama)} · ${esc(findRec('jenisSertifikasi', pk.sertifikasi).nama)})</span>`)}
          ${sumRow('Pelaksanaan', `${fmtRange(rec.tglMulai, rec.tglSelesai)} · ${esc(rec.lokasi || '—')}`)}${sumRow('Durasi', `${rec.durasi} hari`)}${rec.trainer ? sumRow('Trainer', esc((trainerById(rec.trainer) || {}).nama || '—')) : ''}
          ${sumRow('Harga / peserta', rp(c.harga))}${sumRow('Jumlah peserta', `${c.target} peserta`)}${sumRow('Total', rp(c.pendapatan))}</div>
        <p class="muted">Angka diambil dari Penjualan Program Final dan tidak dapat diubah di sini.</p>
      </div>`,
      saveLabel: 'Cetak',
      onSave: () => { logAct('Penjualan Program', 'Penjualan Program', 'Cetak', rec.nomor, rec.id, `Cetak Surat Penawaran ${rec.nomor}`); toast(`Surat penawaran ${rec.nomor} dicetak (mockup)`); }
    });
  }

  // ---------- Asisten trainer (to be 12-11-2026, PJ-FR-30) ----------
  // Staf LabMania dari Master User aktif; wajib, satu orang (usulan, Q-PJ-27). Dipakai Surat Tugas.
  const asistenOf = id => db.user.find(u => u.id === id);
  const asistenNama = id => (asistenOf(id) || {}).nama || '—';
  function asistenField(p, locked, err = '') {
    const u = asistenOf(p.asisten);
    const label = 'Asisten trainer' + req + q('Q-PJ-27', 'Usulan: wajib dan tepat satu orang, dipilih dari Master User aktif. Boleh lebih dari satu, atau kosong untuk program tertentu?');
    const hint = u ? `<span class="hint">${esc(roleOf(u) || 'Master User')}</span>` : '<span class="hint">Staf LabMania dari Master User; ikut tercetak di Surat Tugas.</span>';
    if (locked) return field(label, `<div class="static">${u ? esc(u.nama) : '<span class="muted">—</span>'}</div>`);
    return field(label, searchSelect({ label: 'Asisten trainer', value: p.asisten, attrs: 'data-pf="asisten"',
      options: db.user.filter(x => x.aktif || x.id === p.asisten).map(x => [x.id, `${x.nama}${x.aktif ? '' : ' (nonaktif)'}`]) }) + hint, err);
  }

  // ---------- Reschedule Penjualan Program Final ----------
  // Sumber: input/Master - Buat Program/2026-11-08-jawaban-reschedule/, 2026-11-09-product-multi-reschedule-evidence/,
  // 2026-11-11-reschedule-setelah-deal/, 2026-11-12-jawaban-reschedule-refund-asisten/.
  // Paling lambat H-7; yang diganti tanggal, trainer, asisten; durasi tetap sehingga costing Final tidak berubah.
  // Tanggal berubah = satu putaran revisi: quotation -rev{n} + link baru + konfirmasi ulang, SO -rev{n}.
  // Tanggal/trainer/asisten berubah = Surat Tugas baru dengan nomor baru (lama Diganti). Invoice tidak diubah.
  const EVID_EXT = ['jpg', 'jpeg', 'png', 'doc', 'docx', 'pdf'], EVID_MAX = 5 * 1024 * 1024;
  const RS_H = 7; // batas H-7 (jawaban Labmania 12-11-2026, butir 12)
  const hasDeal = pj => qtnOfPj(pj).some(k => k.status === 'deal');
  const addHari = (iso, n) => { const d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
  // Hak: Manage pada menu Daftar Penjualan Program di Role & Permission (jawaban Q-PJ-23).
  const canResched = pj => pj.status === 'final' && !pj.closed && !!pj.tglMulai && todayIso <= addHari(pj.tglMulai, -RS_H) && can('quot', 'm');
  const QPJ28 = q('Q-PJ-28', 'Usulan: tanggal mulai baru minimal hari ini + 7 hari, supaya peserta sempat konfirmasi ulang dan jadwal baru tidak langsung melewati batas H-7.');
  const jadwalTxt = (mulai, selesai, waktu, lokasi, trainer, asisten) => `${fmtRange(mulai, selesai)}${waktu ? ` · ${esc(waktu)}` : ''} · ${esc(lokasi || '—')}${trainer ? ` · Trainer: ${esc((trainerById(trainer) || {}).nama || '—')}` : ''}${asisten ? ` · Asisten: ${esc(asistenNama(asisten))}` : ''}`;

  function reschedHistory(p) {
    const r = p.reschedule || [];
    if (!r.length) return '';
    return `<div class="csec"><p class="csec-h">Riwayat Reschedule <span class="muted">${r.length} kali; jadwal lama, alasan, dan dampaknya</span></p><div class="step-body">
      <div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Oleh</th><th>Jadwal lama</th><th>Jadwal baru</th><th>Alasan</th><th>Evidence</th><th>Dampak</th></tr></thead><tbody>
      ${r.slice().reverse().map(x => `<tr><td class="nowrap">${esc(x.waktuUbah)}</td><td>${esc(x.oleh)}</td>
        <td>${jadwalTxt(x.lama.tglMulai, x.lama.tglSelesai, x.lama.waktu, x.lama.lokasi, x.lama.trainer, x.lama.asisten)}</td>
        <td>${jadwalTxt(x.baru.tglMulai, x.baru.tglSelesai, x.baru.waktu, x.baru.lokasi, x.baru.trainer, x.baru.asisten)}</td><td>${esc(x.alasan)}</td><td>${(x.evidence || []).length ? x.evidence.map(f => `<span class="code">${esc(f)}</span>`).join('<br>') : '<span class="muted">—</span>'}</td>
        <td>${(x.dampak || []).length ? x.dampak.map(esc).join('<br>') : '<span class="muted">Tidak ada dokumen turunan</span>'}</td></tr>`).join('')}
      </tbody></table></div></div></div>`;
  }

  // Ringkasan dampak sebelum disimpan (PJ-FR-26): dihitung dari data saat ini.
  function reschedDampak(rec, tglBerubah, timBerubah) {
    const qs = qtnOfPj(rec).filter(k => k.status !== 'batal'), sos = soOfPj(rec);
    const ulang = qs.reduce((t, k) => t + k.peserta.filter(r => r.st === 'menunggu' || r.st === 'deal').length, 0);
    const tagih = qs.filter(k => k.status === 'deal').reduce((t, k) => t + dealRows(k).filter(r => invOfQtn(k).some(i => i.peserta.includes(r.id))).length, 0);
    const skt = sktOfPj(rec.id);
    const out = [];
    if (tglBerubah) {
      if (qs.length) out.push(`${qs.length} quotation naik revisi dan mendapat link konfirmasi baru`);
      if (ulang) out.push(`${ulang} peserta harus konfirmasi ulang dengan surat bertanggal baru`);
      if (sos.length) out.push(`${sos.length} Sales Order naik revisi`);
      if (tagih) out.push(`${tagih} peserta Deal sudah ditagih: bila tidak ikut, dibuatkan Refund`);
    }
    if (skt && (tglBerubah || timBerubah)) out.push(`Surat Tugas ${skt.nomor} diganti surat baru dengan nomor baru`);
    return out;
  }

  // Dipakai modal Reschedule dan data contoh. Satu transaksi di aplikasi (PJ-NFR-10).
  function reschedApply(rec, { mulai, trainer, asisten, alasan, evidence = [], oleh = meName(), waktuUbah = isoNow().slice(0, 16) }) {
    const d = Number(rec.durasi) || 1, selesai = addHari(mulai, d - 1);
    const lama = { tglMulai: rec.tglMulai, tglSelesai: rec.tglSelesai, waktu: rec.waktu || '', lokasi: rec.lokasi || '', trainer: rec.trainer, asisten: rec.asisten };
    const baru = Object.assign({}, lama, { tglMulai: mulai, tglSelesai: selesai, trainer, asisten });
    const tglBerubah = mulai !== lama.tglMulai, timBerubah = trainer !== lama.trainer || asisten !== lama.asisten;
    const dampak = reschedDampak(rec, tglBerubah, timBerubah);
    Object.assign(rec, { tglMulai: mulai, tglSelesai: selesai, trainer, asisten });
    const ref = { lama, baru, alasan, evidence, oleh, waktuUbah, dampak };
    rec.reschedule = (rec.reschedule || []).concat([ref]);
    syncPf(rec);
    if (tglBerubah) {
      qtnOfPj(rec).filter(k => k.status !== 'batal').forEach(k => qtnRevisi(k, ref)); // QTN-FR-34, QTN-FR-35
      soOfPj(rec).forEach(s => {
        const dulu = soNomor(s);
        s.rev = (s.rev || 0) + 1; s.revAt = waktuUbah;
        logAct('Quotation', 'Sales Order', 'Revisi', soNomor(s), s.id, `Revisi Sales Order ${dulu} → ${soNomor(s)} karena reschedule ${rec.nomor}: ${alasan}`);
      });
    }
    if (tglBerubah || timBerubah) sktRenew(rec, ref); // SO-FR-12: surat baru, nomor baru
    return { selesai, dampak, tglBerubah };
  }

  function qtResched(rec) {
    if (!canResched(rec)) { toast(`Reschedule hanya untuk Penjualan Program Final, paling lambat H-${RS_H} sebelum acara`, 'warn'); return; }
    const d = Number(rec.durasi) || 1, minTgl = addHari(todayIso, RS_H);
    const dampakBox = (tgl, tim) => { const l = reschedDampak(rec, tgl, tim); return l.length ? `<b>Yang ikut berubah bila disimpan:</b><ul class="rs-dampak">${l.map(x => `<li>${esc(x)}</li>`).join('')}</ul><span class="muted">Invoice tidak diubah karena biaya tetap.</span>` : '<span class="muted">Belum ada quotation, Sales Order, atau Surat Tugas yang terdampak.</span>'; };
    openModal({
      title: `Reschedule ${esc(rec.nomor)}`, wide: true,
      body: `<p class="hint top">Yang dapat diganti hanya <b>tanggal</b>, <b>trainer</b>, dan <b>asisten trainer</b>. Biaya, waktu, dan lokasi tetap. Durasi tetap ${d} hari, jadi tanggal selesai mengikuti tanggal mulai. Batas reschedule H-${RS_H}: ${fmtTgl(addHari(rec.tglMulai, -RS_H))}.${q('Q-PJ-23', 'Terjawab: tanpa approval; hak Reschedule diatur di Role & Permission (usulan: Manage pada Daftar Penjualan Program).')}</p>
        <div class="summary">${sumRow('Jadwal sekarang', jadwalTxt(rec.tglMulai, rec.tglSelesai, rec.waktu, rec.lokasi, rec.trainer, rec.asisten))}${sumRow('Sudah di-reschedule', `${(rec.reschedule || []).length} kali`)}</div>
        <div class="grid2 gap-t">
          ${field('Tanggal mulai baru' + req + QPJ28, `<input class="input" type="date" name="tglMulai" id="rs-mulai" value="${esc(rec.tglMulai)}" min="${minTgl}" required><span class="hint">paling cepat ${fmtTgl(minTgl)}</span>`)}
          ${field('Tanggal selesai', `<output class="static" id="rs-selesai" for="rs-mulai">${fmtTgl(rec.tglSelesai) || '—'}</output><span class="hint">otomatis: mulai + ${d - 1} hari</span>`)}
          ${field('Trainer', `<select class="input" name="trainer">${db.trainer.filter(x => x.aktif || x.id === rec.trainer).map(x => `<option value="${x.id}" ${x.id === rec.trainer ? 'selected' : ''}>${esc(x.nama)}${x.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>`)}
          ${field('Asisten trainer', `<select class="input" name="asisten">${db.user.filter(x => x.aktif || x.id === rec.asisten).map(x => `<option value="${x.id}" ${x.id === rec.asisten ? 'selected' : ''}>${esc(x.nama)}${x.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>`)}
          ${field('Alasan reschedule' + req, `<textarea class="input" name="alasan" rows="3" maxlength="500" required placeholder="mis. Kuota peserta belum terpenuhi"></textarea>`, '', 'span2')}
          ${field('Evidence' + req, `<label class="pb-drop"><input type="file" name="evidence" multiple accept=".jpg,.jpeg,.png,.doc,.docx,.pdf" hidden><b>Pilih file evidence</b><span class="hint">JPG, PNG, DOC/DOCX, PDF · maks. 5 MB per file · boleh lebih dari satu</span></label>
            <div class="hint" id="rs-files" aria-live="polite">Belum ada file</div>`, '', 'span2')}
        </div>
        <div class="notice gap-t" id="rs-dampak" aria-live="polite"><span>${dampakBox(false, false)}</span><span class="ref">PJ-FR-27</span></div>`,
      saveLabel: 'Simpan jadwal baru',
      onMount: form => {
        const m = form.elements.tglMulai, out = $('#rs-selesai', form), box = $('#rs-dampak span', form);
        const upd = () => { out.textContent = m.value ? fmtTgl(addHari(m.value, d - 1)) : '—';
          box.innerHTML = dampakBox(!!m.value && m.value !== rec.tglMulai, +form.elements.trainer.value !== rec.trainer || +form.elements.asisten.value !== rec.asisten); };
        ['input', 'change'].forEach(ev => form.addEventListener(ev, e => { if (['tglMulai', 'trainer', 'asisten'].includes(e.target.name)) upd(); }));
        const ev = form.elements.evidence, list = $('#rs-files', form);
        ev.addEventListener('change', () => { list.textContent = [...ev.files].map(x => x.name).join(', ') || 'Belum ada file'; });
      },
      onSave: f => {
        const trainer = +f.trainer.value, asisten = +f.asisten.value, mulai = f.tglMulai.value, alasan = f.alasan.value.trim(), files = [...f.evidence.files];
        let ok = true;
        if (!mulai) ok = invalid(f.tglMulai, 'Isi tanggal mulai baru');
        else if (mulai !== rec.tglMulai && mulai < minTgl) ok = invalid(f.tglMulai, `Tanggal mulai baru paling cepat ${fmtTgl(minTgl)} (hari ini + ${RS_H} hari)`);
        if (!alasan) ok = invalid(f.alasan, 'Alasan reschedule wajib diisi');
        const salahTipe = files.find(x => !EVID_EXT.includes(x.name.split('.').pop().toLowerCase())), besar = files.find(x => x.size > EVID_MAX);
        if (!files.length) ok = invalid(f.evidence, 'Unggah minimal satu file evidence');
        else if (salahTipe) ok = invalid(f.evidence, `${salahTipe.name}: hanya JPG, PNG, DOC/DOCX, atau PDF`);
        else if (besar) ok = invalid(f.evidence, `${besar.name} lebih dari 5 MB`);
        if (!ok) return false;
        if (mulai === rec.tglMulai && trainer === rec.trainer && asisten === rec.asisten) return invalid(f.tglMulai, 'Jadwal belum berubah: ganti tanggal, trainer, atau asisten');
        const h = reschedApply(rec, { mulai, trainer, asisten, alasan, evidence: files.map(x => x.name) });
        toast(`${rec.nomor} di-reschedule ke ${fmtRange(mulai, h.selesai)}${h.dampak.length ? `. ${h.dampak.join('; ')}` : ''}`);
      }
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

