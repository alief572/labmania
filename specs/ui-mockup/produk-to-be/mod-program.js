/* Labmania mockup — Penjualan Program: Master Program dan Penjualan Program (daftar, form costing, status).
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Shared: Master Program & Penjualan Program ----------
  // Koreksi 13-11-2026 (input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/ butir 1): Master Program kembali
  // berisi SATU Product; yang boleh lebih dari satu adalah Master Program di dalam Penjualan Program, masing-masing dengan
  // costing-nya sendiri. p.paket tetap array (isi satu) agar data lama terbaca. paketOf() menggabungkan bila isinya > 1.
  const paketIds = p => [].concat(p && p.paket || []).filter(Boolean);
  const paketList = p => paketIds(p).map(id => db.paket.find(x => x.id === id)).filter(Boolean);
  const paketOf = p => {
    const l = paketList(p);
    if (l.length <= 1) return l[0] || { judul: '—', nomor: '—', lingkup: [] };
    return Object.assign({}, l[0], { judul: l.map(x => x.judul).join(' + '), nomor: l.map(x => x.nomor).join(' + '), lingkup: [].concat(...l.map(x => x.lingkup || [])), list: l });
  };
  // Penjualan Program: q.mps = [{ mp, biaya, hargaJual, targetProfit }] satu per Master Program. q.biaya / q.hargaJual /
  // q.targetProfit menyimpan jumlah seluruh Master Program (dipakai Quotation, Invoice, Pengajuan Biaya). Durasi, target
  // peserta, tanggal, lokasi, trainer berlaku untuk seluruh program. Data tanpa mps = satu Master Program (q.mp).
  // Jadwal (tanggal, durasi, JP, trainer, asisten) juga per Master Program ([JMR-1]); data satu Master Program memakai isian program.
  const pjMps = q => (q && q.mps && q.mps.length ? q.mps : [{ mp: q.mp, biaya: q.biaya || [], hargaJual: q.hargaJual, targetProfit: q.targetProfit,
    tglMulai: q.tglMulai, tglSelesai: q.tglSelesai, durasi: q.durasi, jamPembelajaran: q.jamPembelajaran, trainer: q.trainer, asisten: q.asisten }]);
  const pjMulti = q => !!(q && q.mps && q.mps.length > 1);
  const mprogById = id => db.mprog.find(x => x.id === id);
  // Lebih dari satu Master Program: satu objek gabungan (nomor digabung "+", paket = semua Product) untuk pemakai lama.
  const mpOf = q => {
    const l = q && q.mps && q.mps.length > 1 ? q.mps.map(m => mprogById(m.mp)).filter(Boolean) : [];
    if (l.length > 1) return Object.assign({}, l[0], { nomor: l.map(x => x.nomor).join(' + '), paket: [].concat(...l.map(paketIds)), list: l });
    return mprogById(q.mp);
  };
  const companyOf = q => db.company.find(x => x.id === q.company);
  const QMP02 = q('Q-MP-02', 'Terjawab 12-11-2026: format MP-YYMM-NNN.');
  const QPJ08 = q('Q-PJ-08', 'Terjawab 12-11-2026: format Public PJP-YYMM-NNN, B2B PJB-YYMM-NNN.');
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
    return gearMenu(p, [['mp-open', 'Edit'], ...(can('mprog', 'p') ? [['mp-final', 'Finalkan']] : []), ['mp-cancel', 'Batalkan', true]]);
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
    if (q.status === 'draft') items.push(...(can('quot', 'p') ? [['qt-final', 'Finalkan']] : []), ['qt-cancel', 'Batalkan', true]);
    if (q.status === 'final') items.push(...(canAddQtn(q) ? [['qtn-from-pj', '+ Buat Quotation']] : []), ...(canResched(q) ? [['qt-resched', 'Reschedule']] : []), ['qt-print', 'Cetak Surat Penawaran'], ...(canBatalPj(q) && can('quot', 'm') ? [['qt-cancel', 'Batalkan', true]] : []));
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
      { label: 'Pelaksanaan', render: q => pjMulti(q) ? `${fmtRange(q.tglMulai, q.tglSelesai)}${q.jamPembelajaran ? ` · ${q.jamPembelajaran} JP` : ''}<br><span class="muted">${esc(q.lokasi || '—')} · ${q.mps.length} Master Program: ${q.mps.map(m => `${fmtTgl(m.tglMulai)} ${esc((trainerById(m.trainer) || {}).nama || '—')}`).join('; ')}</span>${(q.reschedule || []).length ? `<br><span class="badge st-draft">Reschedule ${q.reschedule.length}×</span>` : ''}` : `${fmtRange(q.tglMulai, q.tglSelesai)}${q.jamPembelajaran ? ` · ${q.jamPembelajaran} JP` : ''}<br><span class="muted">${esc(q.lokasi || '—')}${q.trainer ? ` · Trainer: ${esc((trainerById(q.trainer) || {}).nama || '—')}` : ''}${q.asisten ? ` · Asisten: ${esc(asistenNama(q.asisten))}` : ''}</span>${(q.reschedule || []).length ? `<br><span class="badge st-draft">Reschedule ${q.reschedule.length}×</span>` : ''}` },
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
      if (!isQt()) return paketIds(p).length ? `${esc(paketOf(p).judul)} · ${Number(p.durasi) || 0} hari · ${nPeserta(p)} peserta · ${rp(p.hargaJual)}` : 'Paket Produk belum dipilih';
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

  // ---------- Form Penjualan Program: beberapa Master Program, costing per Master Program ----------
  // Selama form terbuka, state.pf.p memegang isian Master Program yang sedang dipilih: costing (mp, biaya, hargaJual, targetProfit)
  // dan jadwalnya (tanggal, durasi, JP, trainer, asisten; [JMR-1]), sehingga bagian 1 – 4 dan hitungannya tetap sama.
  // Master Program lain disimpan di state.pf.mps; digabung saat simpan.
  const MPS_F = ['mp', 'biaya', 'hargaJual', 'targetProfit', 'tglMulai', 'tglSelesai', 'durasi', 'jamPembelajaran', 'trainer', 'asisten'];
  // Master Program tambahan: tanggal, trainer, dan asisten kosong, dipilih sendiri (input/Master - Buat Program/2026-11-13-jawaban-usulan-multi-master/ [JUM-2]).
  const mpsEntry = mp => ({ mp: mp.id, biaya: clone(mp.biaya), hargaJual: mp.hargaJual, targetProfit: mp.targetProfit, tglMulai: '', tglSelesai: '', durasi: Number(mp.durasi) || 1,
    jamPembelajaran: mp.jamPembelajaran || '', trainer: '', asisten: '' });
  function pfStore() {
    const pf = state.pf; if (!pf || pf.kind !== 'qt' || !pf.p.mp) return [];
    if (!pf.mps) { pf.mps = []; pf.mi = 0; }
    pf.mps[pf.mi] = Object.fromEntries(MPS_F.map(f => [f, pf.p[f]]));
    return pf.mps;
  }
  function pfLoad(i) { const pf = state.pf, m = pf.mps[i]; MPS_F.forEach(f => { pf.p[f] = m[f]; }); pf.mi = i; pf.errors = {}; }
  function pfFromRec(rec, mi = 0) {
    const p = clone(rec), mps = clone(pjMps(rec)).map(m => Object.assign({ tglMulai: rec.tglMulai, tglSelesai: rec.tglSelesai, durasi: rec.durasi, jamPembelajaran: rec.jamPembelajaran, trainer: rec.trainer, asisten: rec.asisten }, m)).map(m => Object.assign(m, { biaya: m.biaya.map(b => { const c = Object.assign({}, b); delete c.mpi; return c; }) }));
    delete p.mps;
    const pf = { p, mps, mi: Math.min(mi, mps.length - 1) };
    MPS_F.forEach(f => { p[f] = mps[pf.mi][f]; });
    return pf;
  }
  // Gabungan untuk disimpan: biaya diberi penanda mpi (indeks Master Program), harga & target profit dijumlah.
  // Jadwal program = mulai paling awal s.d. selesai paling akhir; trainer & asisten program = milik Master Program pertama
  // (dipakai tampilan lama); JP program = jumlah JP semua Master Program ([JMR-2]).
  function pjJadwalAgg(out, mps) {
    const mulai = mps.map(m => m.tglMulai).filter(Boolean).sort(), selesai = mps.map(m => m.tglSelesai).filter(Boolean).sort();
    Object.assign(out, { tglMulai: mulai[0] || '', tglSelesai: selesai[selesai.length - 1] || '', trainer: mps[0].trainer, asisten: mps[0].asisten,
      jamPembelajaran: mps.reduce((t, m) => t + (Number(m.jamPembelajaran) || 0), 0) || '' });
    out.durasi = durasiTgl(out) || mps.reduce((t, m) => t + (Number(m.durasi) || 0), 0);
    return out;
  }
  function pfAgg() {
    const mps = pfStore(), out = clone(state.pf.p), sum = f => mps.reduce((t, m) => t + (Number(m[f]) || 0), 0);
    Object.assign(out, { mps: clone(mps), mp: mps[0].mp, biaya: mps.flatMap((m, i) => m.biaya.map(b => Object.assign({}, b, { mpi: i }))),
      hargaJual: sum('hargaJual'), targetProfit: mps.every(m => m.targetProfit === '' || m.targetProfit == null) ? '' : sum('targetProfit') });
    return pjJadwalAgg(out, mps);
  }
  const mpCalc = (p, m) => calc({ durasi: m.durasi, targetPeserta: p.targetPeserta, biaya: m.biaya, hargaJual: m.hargaJual });
  const mpJadwalTxt = m => `${fmtRange(m.tglMulai, m.tglSelesai)}${m.durasi ? ` · ${m.durasi} hari` : ''}${m.jamPembelajaran ? ` · ${m.jamPembelajaran} JP` : ''}`;
  const mpTimTxt = m => `${esc((trainerById(m.trainer) || {}).nama || 'Trainer —')} · ${esc(m.asisten ? asistenNama(m.asisten) : 'Asisten —')}`;
  function mpsTable(p, locked) {
    const mps = pfStore(), mi = state.pf.mi, cs = mps.map(m => mpCalc(p, m)), tot = k => cs.reduce((t, c) => t + (c[k] || 0), 0);
    return `<div class="table-wrap"><table class="table pj-mps"><thead><tr><th>Master Program</th><th>Jadwal &amp; tim</th><th class="num">Harga jual / peserta</th><th class="num">HPP</th><th class="num">Profit</th><th class="act"><span class="sr-only">Aksi</span></th></tr></thead><tbody>
      ${mps.map((m, i) => { const mp = mprogById(m.mp), c = cs[i];
        return `<tr class="${i === mi ? 'on' : ''}"><td><button type="button" class="btn btn-ghost sm pj-mp-btn" data-act="pj-mp-pick" data-i="${i}" ${i === mi ? 'aria-current="true"' : ''}><span class="code">${esc(mp.nomor)}</span> ${esc(paketOf(mp).judul)}</button>${i === mi ? ' <span class="tag">tampil di bawah</span>' : ''}</td>
          <td>${mpJadwalTxt(m) || '<span class="muted">Tanggal belum diisi</span>'}<br><span class="muted">${mpTimTxt(m)}</span></td>
          <td class="num">${rp(c.harga)}</td><td class="num">${rp(c.hpp)}</td><td class="num ${c.profit < 0 ? 'neg' : ''}">${rp(c.profit)}</td>
          <td class="act">${!locked && mps.length > 1 ? `<button type="button" class="btn btn-ghost sm danger-t" data-act="pj-mp-del" data-i="${i}" aria-label="Hapus ${esc(mp.nomor)} dari penjualan ini">Hapus</button>` : ''}</td></tr>`; }).join('')}
      </tbody>${mps.length > 1 ? `<tfoot><tr><td><b>Total program</b> <span class="muted">${mps.length} Master Program</span></td><td>${(a => `${fmtRange(a.tglMulai, a.tglSelesai)} · ${a.jamPembelajaran || 0} JP`)(pjJadwalAgg({}, mps))}</td><td class="num"><b>${rp(tot('harga'))}</b></td><td class="num"><b>${rp(tot('hpp'))}</b></td><td class="num ${tot('profit') < 0 ? 'neg' : ''}"><b>${rp(tot('profit'))}</b></td><td></td></tr></tfoot>` : ''}</table></div>`;
  }
  function mpsBlock(p, locked) {
    const used = pfStore().map(m => m.mp), opts = db.mprog.filter(m => m.status === 'final' && !used.includes(m.id));
    return `<div class="csec"><p class="csec-h">Master Program <span class="muted">satu penjualan boleh berisi lebih dari satu Master Program; tanggal, Jam Pembelajaran, trainer, asisten (bagian 1) dan costing (bagian 2 – 4) diisi per Master Program. Klik nama Master Program untuk menampilkan isiannya.</span></p>
      <div id="pf-mps">${mpsTable(p, locked)}</div>
      ${locked ? '' : `<div class="pickrow"><select class="input" id="pj-mp-add" aria-label="Tambah Master Program"><option value="">+ Tambah Master Program Final…</option>${opts.map(m => `<option value="${m.id}">${esc(m.nomor)} — ${esc(paketOf(m).judul)} · ${rp(m.hargaJual)}</option>`).join('')}</select></div>
      <p class="hint">Master Program tambahan: pilih tanggal, trainer, dan asistennya sendiri (klik namanya). Harga jual / peserta di quotation = jumlah harga semua Master Program; peserta ikut semua Master Program. Target peserta, waktu, dan lokasi berlaku untuk seluruh program. Jadwal program = tanggal mulai paling awal s.d. selesai paling akhir.</p>`}
      ${state.pf.errors.mps ? `<div class="err"><i>!</i>${esc(state.pf.errors.mps)}</div>` : ''}</div>`;
  }
  const mpAktifTag = () => (state.pf && state.pf.kind === 'qt' && state.pf.mps && state.pf.mps.length > 1 ? ` · <span class="code">${esc(mprogById(state.pf.p.mp).nomor)}</span>` : '');

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
      + (canResched(p) ? ` Jadwal, trainer, dan asisten masih dapat diubah lewat <b>Reschedule</b> sampai ${fmtTgl(addHari(p.tglMulai, -RS_H))} (H-${RS_H})${hasDeal(p) ? '; bila tanggal berubah, quotation naik revisi, quotation Deal perlu Deal ulang, dan Sales Order-nya pindah ke SO Batal' : ''}.` : p.closed ? ' Program sudah Closed, jadi jadwal tidak dapat di-reschedule.' : p.tglMulai && todayIso > addHari(p.tglMulai, -RS_H) ? ` Batas reschedule H-${RS_H} (${fmtTgl(addHari(p.tglMulai, -RS_H))}) sudah lewat, jadi jadwal tidak dapat diubah lagi.` : '');
    if (qt && p.status === 'batal') lockInfo = `Penjualan Program dibatalkan dan tidak dapat diubah.${p.batal ? ` Alasan: ${esc(p.batal.alasan)} (${esc(p.batal.oleh)}, ${fmtWaktu(p.batal.waktu.replace('T', ' '))}).` : ''}`;

    const header = `<div class="csec"><p class="csec-h">Header</p><div class="grid3">
      ${field(`No. ${qt ? 'penjualan' + QPJ08 : 'master program' + QMP02}`, `<input class="input" value="${esc(p.nomor || '')}" placeholder="otomatis saat disimpan" disabled>`)}
      ${field(qt ? 'Jenis penjualan' : 'Jenis', `<div class="static">${qt ? jenisTag(p.jenis) : '<span class="muted">Umum — Public/B2B dipilih di Penjualan Program</span>'}</div>`)}
      ${field('Status', `<div class="static">${stBadge(p.status)}${qt && p.status === 'final' ? ` <span class="muted">→ ${NEXT[p.jenis]}</span>` : ''}</div>`)}
    </div></div>`;

    let s1;
    if (!qt) {
      const pid = paketIds(p)[0], x = paketList(p)[0];
      const paketOpts = db.paket.filter(y => y.aktif || y.id === pid)
        .map(y => `<option value="${y.id}" ${y.id === pid ? 'selected' : ''}>${esc(y.nomor)} — ${esc(y.judul)} · ${esc(findRec('varian', y.varian).nama)} · ${esc(findRec('jenisSertifikasi', y.sertifikasi).nama)}${y.aktif ? '' : ' (nonaktif)'}</option>`).join('');
      s1 = step(1, 'Informasi Program', '', `
        ${field('Product' + req, `<select class="input" id="mp-prod-add" aria-label="Product" ${locked ? 'disabled' : ''}><option value="">— Pilih Product aktif —</option>${paketOpts}</select>
          ${x ? `<span class="hint">${ATTRS.map(a => `${a.label}: ${esc(findRec(a.key, x[a.field]).nama)}`).join(' · ')}</span>` : ''}
          <span class="hint">Satu Master Program = satu Product. Penjualan Program dapat menggabungkan beberapa Master Program.</span>`, E.paket)}
        ${field('Jam Pembelajaran' + req, `<div class="input-unit"><input class="input" id="mf-jp" data-pf="jamPembelajaran" aria-label="Jam Pembelajaran" type="number" min="1" step="1" inputmode="numeric" value="${esc(p.jamPembelajaran)}" placeholder="mis. 16" aria-describedby="mf-jp-unit" ${locked ? 'disabled' : ''}><span class="unit" id="mf-jp-unit">JP</span></div><span class="hint">Menjadi nilai awal di Penjualan Program, masih dapat diubah di sana</span>`, E.jamPembelajaran)}
        ${simInputs(p, locked, E, 'angka baseline; bisa disesuaikan per Penjualan Program tanpa mengubah master. Juga tampil di bagian 4.')}`);
    } else {
      const mp = mpOf(p);
      // B2B: Company dipilih dari Master Company (jawaban Q-PJ-18); Public tidak terikat company.
      const co = b2b ? field('Company' + req, `<select class="input" data-pf="company" ${locked ? 'disabled' : ''}><option value="">— Pilih Company aktif —</option>${db.company.filter(c => c.aktif || c.id === p.company)
        .map(c => `<option value="${c.id}" ${c.id === p.company ? 'selected' : ''}>${esc(c.kode)} — ${esc(c.nama)}${c.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>${companyOf(p)
        ? `<span class="hint">${esc(companyOf(p).jenis)} · ${esc(companyOf(p).kota)} · PIC ${esc(companyOf(p).picNama || '—')}</span>` : '<span class="hint">dari Master Company</span>'}`, E.company) : '';
      const multiHint = state.pf.mps && state.pf.mps.length > 1 ? `<p class="hint span3 mp-aktif-note">Tanggal, Jam Pembelajaran, trainer, dan asisten di bawah milik <b>${esc(mp.nomor)}</b>. Pilih Master Program lain di tabel atas untuk mengisi jadwalnya.</p>` : '';
      s1 = step(1, 'Informasi Penjualan', '', `
        <div class="grid3">
          ${field(state.pf.mps && state.pf.mps.length > 1 ? 'Master Program (costing yang tampil)' : 'Master Program', `<div class="static"><span class="code">${esc(mp.nomor)}</span> ${esc(paketOf(mp).judul)} <span class="muted">· ${esc(findRec('varian', paketOf(mp).varian).nama)} · ${esc(findRec('jenisSertifikasi', paketOf(mp).sertifikasi).nama)}</span></div>`)}
          ${co}
          ${multiHint}
          ${field('Tanggal mulai' + req, `<input class="input" type="date" data-pf="tglMulai" value="${esc(p.tglMulai)}" ${locked ? 'disabled' : ''}>`, E.tglMulai)}
          ${field('Tanggal selesai' + req, `<input class="input" type="date" data-pf="tglSelesai" value="${esc(p.tglSelesai)}" ${locked ? 'disabled' : ''}><span class="hint">durasi = selesai − mulai + 1 hari</span>`, E.tglSelesai)}
          ${field('Waktu', `<input class="input" data-pf="waktu" value="${esc(p.waktu || '')}" maxlength="50" placeholder="mis. 09.00–15.15 WIB" ${locked ? 'disabled' : ''}><span class="hint">Jam pelaksanaan; tercetak di Penawaran & Formulir Konfirmasi</span>`)}
          ${field('Jam Pembelajaran' + req, `<div class="input-unit"><input class="input" id="pf-jp" data-pf="jamPembelajaran" aria-label="Jam Pembelajaran" type="number" min="1" step="1" inputmode="numeric" value="${esc(p.jamPembelajaran)}" placeholder="mis. 16" aria-describedby="pf-jp-unit" ${locked ? 'disabled' : ''}><span class="unit" id="pf-jp-unit">JP</span></div><span class="hint">Nilai awal dari Master Program; tercetak di Penawaran quotation</span>`, E.jamPembelajaran)}
          ${field('Lokasi acara', `<input class="input" data-pf="lokasi" value="${esc(p.lokasi)}" maxlength="150" placeholder="mis. Offline Bekasi" ${locked ? 'disabled' : ''}>`)}
          ${trainerField(p, locked, E.trainer)}
          ${asistenField(p, locked, E.asisten)}
        </div>
        ${simInputs(p, locked, E, `terisi dari ${esc(mp.nomor)}; perubahan hanya berlaku di penjualan ini. Juga tampil di bagian 4.`)}`);
    }

    const s2 = step(2, 'Fixed Cost' + (qt ? mpAktifTag() : ''), qt ? 'disalin dari Master Program; nominal, item, dan biaya lain dapat diubah; item bernilai 0 tidak disimpan. Qty: event = 1, day = durasi.' : 'semua item aktif Master Rate langsung tampil dengan Default Cost; klik × pada item yang tidak dipakai (dapat ditambah lagi lewat + Tambah); item bernilai 0 tidak disimpan. Qty: event = 1, day = durasi.',
      costTable(0, locked) + costTable(1, locked) + `<div id="pf-tot-fixed">${totalsFixed(p)}</div>`);
    const s3 = step(3, 'Variable Cost' + (qt ? mpAktifTag() : ''), 'pilih item yang dipakai saja, isi nominal per peserta; item bernilai 0 tidak disimpan. Qty otomatis dari jumlah peserta dan durasi.',
      costTable(2, locked) + costTable(3, locked) + `<div id="pf-tot-var">${totalsVar(p)}</div>`);
    const s4 = step(4, 'Simulasi Hasil' + (qt ? mpAktifTag() : ''), 'apakah penjualan ini untung, dan berapa titik impasnya',
      simInputs(p, locked, E, 'sama dengan bagian 1 — ubah di sini atau di bagian 1, keduanya ikut berubah') + `<div id="pf-sim" class="gap-t">${simFor(p)}</div>`);

    let actions = `<button type="button" class="btn btn-ghost" data-act="pf-back">${isNew ? 'Batal' : 'Kembali'}</button>`;
    if (kind === 'mp') {
      if (p.status === 'draft') actions += `${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="mp-cancel" data-id="${p.id}">Batalkan Program</button>`}
        <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
        ${isNew || !can('mprog', 'p') ? '' : '<button type="button" class="btn btn-accent" data-act="pf-confirm">Finalkan program</button>'}`;
      else if (p.status === 'final') actions += `<button type="button" class="btn btn-accent" data-act="qt-from-mp" data-id="${p.id}">Buat Penjualan Program</button>`;
    } else if (p.status === 'draft') {
      actions += `${isNew ? '' : `<button type="button" class="btn btn-outline" data-act="qt-cancel" data-id="${p.id}">Batalkan</button>`}
        <button type="button" class="btn btn-outline" data-act="pf-save">Simpan Draft</button>
        ${isNew || !can('quot', 'p') ? '' : `<button type="button" class="btn btn-accent" data-act="pf-confirm">Finalkan → ${NEXT[p.jenis]}</button>`}`;
    } else if (p.status === 'final') {
      actions += `${canBatalPj(p) && can('quot', 'm') ? `<button type="button" class="btn btn-outline" data-act="qt-cancel" data-id="${p.id}">Batalkan</button>` : ''}${canResched(p) ? `<button type="button" class="btn btn-outline" data-act="qt-resched" data-id="${p.id}">Reschedule</button>` : ''}
        <button type="button" class="btn btn-accent" data-act="qt-print" data-id="${p.id}">Cetak Surat Penawaran</button>`;
    }

    const title = kind === 'mp' ? (isNew ? 'Buat Master Program' : `Master Program ${esc(p.nomor)}`) : (isNew ? `Buat Penjualan Program ${JENIS[p.jenis]}` : `Penjualan Program ${esc(p.nomor)}`);
    const ref = kind === 'mp' ? 'MP-FR-02 – MP-FR-10' : 'PJ-FR-04 – PJ-FR-10';
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="pf-back">← Kembali</button><h2>${title}</h2><span class="ref">${ref}</span></div>
      ${lockInfo ? `<div class="alert">${lockInfo}</div>` : ''}
      <div id="pf-warn" class="alert ${warns.length ? '' : 'hidden'}">${warns.map(esc).join('<br>')}</div>
      <div class="pub-form">${header}${qt ? mpsBlock(p, locked) : ''}${s1}${qt ? reschedHistory(p) : ""}${s2}${s3}${s4}</div>
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
    const mt = $('#pf-mps'); if (mt) mt.innerHTML = mpsTable(p, costLocked());
    const w = costWarnings(p), box = $('#pf-warn');
    box.innerHTML = w.map(esc).join('<br>');
    box.classList.toggle('hidden', !w.length);
  }

  function openCostForm(kind, rec, preset) {
    const blank = kind === 'mp'
      // Master Program baru: semua item Fixed Cost aktif langsung terisi Default Cost; yang tidak dipakai dihapus dengan × (MP-FR-06).
      ? { id: null, nomor: null, status: 'draft', paket: [], durasi: 1, targetPeserta: 10, hargaJual: '', targetProfit: '', biaya: db.rate.fixed.filter(r => r.aktif).map(r => biayaFromRate(r)) }
      : Object.assign({ id: null, nomor: null, status: 'draft', mp: '', jenis: '' }, preset || {});
    state.pf = Object.assign({ kind, p: rec ? clone(rec) : blank, isNew: !rec, errors: {}, collapsed: {} }, kind === 'qt' && rec ? pfFromRec(rec) : {});
    state.view = kind === 'mp' ? 'mpForm' : 'qtForm';
    render();
    window.scrollTo(0, 0);
  }

  function validateCost() {
    const { kind, p } = state.pf, E = {};
    const posInt = v => v !== '' && Number.isInteger(Number(v)) && Number(v) > 0;
    if (kind === 'mp' && !paketIds(p).length) E.paket = 'Pilih Product';
    if (!posInt(p.durasi)) E.durasi = 'Durasi bilangan bulat > 0';
    if (!posInt(p.targetPeserta)) E.targetPeserta = 'Target peserta bilangan bulat > 0';
    else if (kind === 'qt' && p.jenis === 'b2b' && Number(p.targetPeserta) < B2B_MIN) E.targetPeserta = `Penjualan B2B minimal ${B2B_MIN} peserta`;
    if (p.hargaJual === '' || p.hargaJual < 0) E.hargaJual = 'Harga jual / peserta wajib diisi';
    if (kind === 'qt' && p.jenis === 'b2b' && !p.company) E.company = 'Pilih Company';
    if (kind === 'qt' && !p.trainer) E.trainer = 'Pilih trainer'; // to be: wajib, untuk Surat Kerja Trainer
    if (kind === 'qt' && !p.asisten) E.asisten = 'Pilih asisten trainer'; // to be 12-11-2026: PJ-FR-30
    if (!posInt(p.jamPembelajaran)) E.jamPembelajaran = 'Jam Pembelajaran bilangan bulat > 0'; // wajib di Master Program dan Penjualan Program (PJ-FR-29, Q-PJ-25)
    if (kind === 'qt' && !p.tglMulai) E.tglMulai = 'Tanggal mulai wajib diisi';
    if (kind === 'qt' && !p.tglSelesai) E.tglSelesai = 'Tanggal selesai wajib diisi';
    else if (kind === 'qt' && p.tglMulai && p.tglSelesai < p.tglMulai) E.tglSelesai = 'Tanggal selesai sebelum tanggal mulai';
    p.biaya.forEach((b, i) => {
      if (b.manual && !String(b.item).trim()) E['b' + i] = 'Nama biaya lain wajib diisi';
      else if (b.harga === '' || b.harga < 0) E['b' + i] = `Nominal ${b.item} wajib diisi`;
    });
    if (kind === 'qt') {
      const lain = pfStore().filter((m, i) => i !== state.pf.mi && (m.hargaJual === '' || m.hargaJual < 0 || !m.tglMulai || !m.tglSelesai || m.tglSelesai < m.tglMulai || !m.trainer || !m.asisten || !(Number(m.jamPembelajaran) > 0)
        || m.biaya.some(b => (b.manual && !String(b.item).trim()) || b.harga === '' || b.harga < 0)));
      if (lain.length) E.mps = `Lengkapi tanggal, Jam Pembelajaran, trainer, asisten, harga jual, dan nominal biaya: ${lain.map(m => mprogById(m.mp).nomor).join(', ')}`;
    }
    state.pf.errors = E;
    return !Object.keys(E).length;
  }

  function saveCost(silent) {
    const p0 = state.pf.p, nol = b => b.harga !== '' && Number(b.harga) === 0;
    let dibuang = p0.biaya.filter(nol).length;
    p0.biaya = p0.biaya.filter(b => !nol(b));
    if (state.pf.kind === 'qt') pfStore().forEach((m, i) => { if (i !== state.pf.mi) { dibuang += m.biaya.filter(nol).length; m.biaya = m.biaya.filter(b => !nol(b)); } });
    if (!validateCost()) {
      render();
      toast('Belum tersimpan — perbaiki isian yang ditandai', 'warn');
      return null;
    }
    const pf = state.pf, list = pf.kind === 'mp' ? db.mprog : db.quot, data = pf.kind === 'qt' ? pfAgg() : clone(pf.p);
    let rec;
    if (pf.isNew) {
      rec = Object.assign(data, { id: uid(), nomor: docNo(pf.kind === 'mp' ? 'MP' : PJ_PREFIX[pf.p.jenis]) });
      list.push(rec);
    } else {
      rec = list.find(x => x.id === pf.p.id);
      Object.assign(rec, data);
    }
    state.pf = Object.assign({ kind: pf.kind, p: clone(rec), isNew: false, errors: {}, collapsed: pf.collapsed }, pf.kind === 'qt' ? pfFromRec(rec, pf.mi) : {});
    render();
    if (!silent) toast(`${pf.kind === 'mp' ? 'Master Program' : 'Penjualan Program'} ${rec.nomor} tersimpan${rec.status === 'draft' ? ' sebagai Draft' : ''}${dibuang ? ` · ${dibuang} item bernilai 0 dibuang` : ''}`);
    return rec;
  }

  // Setelah aksi status: sinkronkan form yang sedang terbuka dengan data tersimpan.
  const syncPf = rec => { if (state.pf && state.pf.p.id === rec.id && (state.view === 'mpForm' || state.view === 'qtForm')) Object.assign(state.pf, state.pf.kind === 'qt' ? pfFromRec(rec, state.pf.mi) : { p: clone(rec) }); };
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
    const used = db.quot.filter(x => pjMps(x).some(m => m.mp === rec.id)).length;
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
        <div class="summary">${sumRow('Penjualan', `${esc(rec.nomor)} — ${esc(paketOf(mpOf(rec)).judul)} (${JENIS[rec.jenis]})`)}${pjMps(rec).length > 1 ? sumRow('Master Program', pjMps(rec).map(m => `${esc(mprogById(m.mp).nomor)} ${rp(m.hargaJual)}`).join(' + ')) : ''}${rec.jenis === 'b2b' ? sumRow('Company', esc(companyOf(rec).nama)) : ''}${rows}</div>${warnBox(w)}`,
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
        <p><b>SURAT PENAWARAN</b><br><span class="muted">No. ${esc(rec.nomor)} ${q('Q-PJ-08', 'Terjawab 12-11-2026: format Public PJP-YYMM-NNN, B2B PJB-YYMM-NNN.')}</span></p>
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
    const label = 'Asisten trainer' + req + q('Q-PJ-27', 'Terjawab 12-11-2026: wajib, tepat satu orang.');
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
  // Beberapa Master Program: boleh selama masih ada Master Program yang belum lewat H-7; per Master Program dicek di modal.
  const rsBuka = m => !!m.tglMulai && todayIso <= addHari(m.tglMulai, -RS_H);
  const canResched = pj => pj.status === 'final' && !pj.closed && !!pj.tglMulai && pjMps(pj).some(rsBuka) && can('quot', 'm');
  const QPJ28 = q('Q-PJ-28', 'Terjawab sebagian 12-11-2026: reschedule paling lambat H-7 dari jadwal lama. Masih dikonfirmasi: tanggal mulai baru minimal hari ini + 7 hari (usulan).');
  const jadwalTxt = (mulai, selesai, waktu, lokasi, trainer, asisten) => `${fmtRange(mulai, selesai)}${waktu ? ` · ${esc(waktu)}` : ''} · ${esc(lokasi || '—')}${trainer ? ` · Trainer: ${esc((trainerById(trainer) || {}).nama || '—')}` : ''}${asisten ? ` · Asisten: ${esc(asistenNama(asisten))}` : ''}`;

  function reschedHistory(p) {
    const r = p.reschedule || [];
    if (!r.length) return '';
    return `<div class="csec"><p class="csec-h">Riwayat Reschedule <span class="muted">${r.length} kali; jadwal lama, alasan, dan dampaknya</span></p><div class="step-body">
      <div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Oleh</th><th>Jadwal lama</th><th>Jadwal baru</th><th>Alasan</th><th>Evidence</th><th>Dampak</th></tr></thead><tbody>
      ${r.slice().reverse().map(x => `<tr><td class="nowrap">${esc(String(x.waktuUbah).replace('T', ' '))}${x.rev ? `<br><span class="code">rev${x.rev}</span>` : '<br><span class="muted">tanpa rev</span>'}</td><td>${esc(x.oleh)}</td>
        <td>${x.rincian ? x.rincian.map(r => `<span class="code">${esc(r.mp)}</span> ${jadwalTxt(r.lama.tglMulai, r.lama.tglSelesai, '', '', r.lama.trainer, r.lama.asisten)}`).join('<br>') : jadwalTxt(x.lama.tglMulai, x.lama.tglSelesai, x.lama.waktu, x.lama.lokasi, x.lama.trainer, x.lama.asisten)}</td>
        <td>${x.rincian ? x.rincian.map(r => `<span class="code">${esc(r.mp)}</span> ${jadwalTxt(r.baru.tglMulai, r.baru.tglSelesai, '', '', r.baru.trainer, r.baru.asisten)}`).join('<br>') : jadwalTxt(x.baru.tglMulai, x.baru.tglSelesai, x.baru.waktu, x.baru.lokasi, x.baru.trainer, x.baru.asisten)}</td><td>${esc(x.alasan)}</td><td>${(x.evidence || []).length ? x.evidence.map(f => `<span class="code">${esc(f)}</span>`).join('<br>') : '<span class="muted">—</span>'}</td>
        <td>${(x.dampak || []).length ? x.dampak.map(esc).join('<br>') : '<span class="muted">Tidak ada dokumen turunan</span>'}</td></tr>`).join('')}
      </tbody></table></div></div></div>`;
  }

  // Ringkasan dampak sebelum disimpan (PJ-FR-26): dihitung dari data saat ini.
  function reschedDampak(rec, tglBerubah, timBerubah) {
    const qs = qtnOfPj(rec).filter(k => k.status !== 'batal'), sos = soOfPj(rec);
    const ulang = qs.reduce((t, k) => t + k.peserta.filter(r => r.st === 'menunggu' || r.st === 'deal').length, 0);
    const tagih = qs.filter(k => k.status === 'deal').reduce((t, k) => t + dealRows(k).filter(r => invOfQtn(k).some(i => i.bayar && i.peserta.includes(r.id))).length, 0);
    const skt = sktOfPj(rec.id);
    const out = [];
    if (tglBerubah) {
      out.push(`Nomor program menjadi ${rec.nomorDasar || rec.nomor}-rev${(rec.rev || 0) + 1}`);
      const nDeal = qs.filter(k => k.status === 'deal').length, nInv = qs.reduce((t, k) => t + invOfQtn(k).length, 0);
      if (qs.length) out.push(`${qs.length} quotation naik revisi dan mendapat link konfirmasi baru`);
      if (nDeal) out.push(`${nDeal} quotation Deal kembali ke Terkirim dan perlu Deal ulang setelah peserta konfirmasi ulang`);
      if (ulang) out.push(`${ulang} peserta harus konfirmasi ulang dengan surat bertanggal baru`);
      if (sos.length) out.push(`${sos.length} Sales Order pindah ke tab SO Batal; SO -rev terbit saat Deal ulang`);
      if (nInv) out.push(`${nInv} invoice tetap (tidak dibatalkan), diberi penanda reschedule`);
      if (tagih) out.push(`${tagih} peserta Deal sudah membayar: boleh memilih Refund`);
    }
    if (skt && (tglBerubah || timBerubah)) out.push(`Surat Tugas ${skt.nomor} diganti surat baru dengan nomor baru`);
    return out;
  }

  // Dipakai modal Reschedule dan data contoh. Satu transaksi di aplikasi (PJ-NFR-10).
  // Program dengan beberapa Master Program ([JMR-1], [JUM-3]): satu kali reschedule boleh mengubah tanggal, trainer, dan asisten
  // beberapa Master Program sekaligus (jadwal = [{ i, mulai, trainer, asisten }]); mi = -1 = semua digeser sejumlah hari yang sama,
  // mi = n = satu Master Program (dipakai data contoh). Jadwal program dihitung ulang.
  const selisihHari = (a, b) => Math.round((Date.parse(a) - Date.parse(b)) / 864e5);
  const jadwalProg = rec => ({ tglMulai: rec.tglMulai, tglSelesai: rec.tglSelesai, waktu: rec.waktu || '', lokasi: rec.lokasi || '', trainer: rec.trainer, asisten: rec.asisten });
  function reschedApply(rec, { mulai, trainer, asisten, alasan, evidence = [], oleh = meName(), waktuUbah = isoNow().slice(0, 16), mi = 0, jadwal = null }) {
    const multi = pjMulti(rec);
    let tglBerubah, timBerubah, lama, baru, rincian = null, selesai, dampak;
    if (multi) {
      if (!jadwal) jadwal = mi === -1 ? rec.mps.map((x, i) => ({ i, mulai: addHari(x.tglMulai, selisihHari(mulai, rec.tglMulai)), trainer: x.trainer, asisten: x.asisten }))
        : [{ i: mi, mulai, trainer, asisten }];
      const ubah = jadwal.map(j => { const x = rec.mps[j.i]; return Object.assign({}, j, { x, selesai: addHari(j.mulai, (Number(x.durasi) || 1) - 1) }); })
        .filter(j => j.mulai !== j.x.tglMulai || j.trainer !== j.x.trainer || j.asisten !== j.x.asisten);
      tglBerubah = ubah.some(j => j.mulai !== j.x.tglMulai); timBerubah = ubah.some(j => j.trainer !== j.x.trainer || j.asisten !== j.x.asisten);
      lama = jadwalProg(rec);
      rincian = ubah.map(j => ({ mp: mprogById(j.x.mp).nomor, lama: { tglMulai: j.x.tglMulai, tglSelesai: j.x.tglSelesai, trainer: j.x.trainer, asisten: j.x.asisten }, baru: { tglMulai: j.mulai, tglSelesai: j.selesai, trainer: j.trainer, asisten: j.asisten } }));
      dampak = reschedDampak(rec, tglBerubah, timBerubah);
      ubah.forEach(j => Object.assign(j.x, { tglMulai: j.mulai, tglSelesai: j.selesai, trainer: j.trainer, asisten: j.asisten }));
      pjJadwalAgg(rec, rec.mps); baru = jadwalProg(rec); selesai = rec.tglSelesai;
    } else {
      const d = Number(rec.durasi) || 1; selesai = addHari(mulai, d - 1);
      lama = jadwalProg(rec); baru = Object.assign({}, lama, { tglMulai: mulai, tglSelesai: selesai, trainer, asisten });
      tglBerubah = mulai !== lama.tglMulai; timBerubah = trainer !== lama.trainer || asisten !== lama.asisten;
      dampak = reschedDampak(rec, tglBerubah, timBerubah);
      Object.assign(rec, { tglMulai: mulai, tglSelesai: selesai, trainer, asisten });
    }
    const ref = { lama, baru, alasan, evidence, oleh, waktuUbah, dampak, rincian, mp: rincian ? rincian.map(r => r.mp).join(', ') : '' };
    // Nomor Penjualan Program ikut -rev{n} bersama quotation & SO (input/Master - Buat Program/2026-11-12-opsi-konfirmasi-ulang-rev-program/).
    if (tglBerubah) { rec.nomorDasar = rec.nomorDasar || rec.nomor; rec.rev = (rec.rev || 0) + 1; rec.nomor = `${rec.nomorDasar}-rev${rec.rev}`; ref.rev = rec.rev; }
    rec.reschedule = (rec.reschedule || []).concat([ref]);
    syncPf(rec);
    // Tanggal berubah (input/Master - Buat Program/2026-11-13-reschedule-deal-ulang-so-batal/): semua quotation naik revisi
    // dan dapat link baru; quotation yang sudah Deal kembali ke Terkirim menunggu Deal ulang, SO-nya pindah ke SO Batal,
    // invoice-nya tetap tetapi diberi penanda reschedule.
    if (tglBerubah) {
      qtnOfPj(rec).filter(k => k.status !== 'batal').forEach(k => {
        const deal = k.status === 'deal';
        qtnRevisi(k, ref); // QTN-FR-34, QTN-FR-35
        if (deal) {
          soBatal(k, { rev: k.rev, alasan, waktu: waktuUbah, prog: rec.nomor });
          k.status = 'terkirim'; k.dealUlang = { rev: k.rev, waktu: waktuUbah };
          invOfQtn(k).forEach(i => { i.resched = { rev: k.rev, waktu: waktuUbah }; });
          linkPublish(k);
        } else if (k.dealUlang) k.dealUlang.rev = k.rev;
      });
    }
    if (tglBerubah || timBerubah) sktRenew(rec, ref); // SO-FR-12: surat baru, nomor baru
    return { selesai, dampak, tglBerubah };
  }

  // Modal reschedule program dengan beberapa Master Program ([JUM-3]): satu baris per Master Program; tanggal, trainer, dan asisten
  // beberapa baris boleh diubah sekaligus. "Geser semua" mengisi tanggal semua baris sejumlah hari yang sama. Baris lewat H-7 terkunci.
  function qtReschedMulti(rec) {
    const minTgl = addHari(todayIso, RS_H), mps = rec.mps;
    const opt = (list, val) => list.map(x => `<option value="${x.id}" ${x.id === val ? 'selected' : ''}>${esc(x.nama)}${x.aktif ? '' : ' (nonaktif)'}</option>`).join('');
    const rows = mps.map((x, i) => { const buka = rsBuka(x), mp = mprogById(x.mp);
      return `<tr class="${buka ? '' : 'is-off'}" data-rs-row="${i}"><td><span class="code">${esc(mp.nomor)}</span> ${esc(paketOf(mp).judul)}<br><span class="muted">sekarang ${fmtRange(x.tglMulai, x.tglSelesai)} · ${x.durasi} hari${buka ? '' : ` · lewat H-${RS_H}, tidak dapat diubah`}</span></td>
        <td><input class="input" type="date" name="mulai${i}" value="${esc(x.tglMulai)}" min="${minTgl}" ${buka ? '' : 'disabled'} aria-label="Tanggal mulai baru ${esc(mp.nomor)}"><span class="hint" data-rs-sel="${i}">selesai ${fmtTgl(x.tglSelesai)}</span></td>
        <td><select class="input" name="trainer${i}" ${buka ? '' : 'disabled'} aria-label="Trainer ${esc(mp.nomor)}">${opt(db.trainer.filter(t => t.aktif || t.id === x.trainer), x.trainer)}</select></td>
        <td><select class="input" name="asisten${i}" ${buka ? '' : 'disabled'} aria-label="Asisten ${esc(mp.nomor)}">${opt(db.user.filter(u => u.aktif || u.id === x.asisten), x.asisten)}</select></td></tr>`; }).join('');
    const dampakBox = (tgl, tim) => { const l = reschedDampak(rec, tgl, tim); return l.length ? `<b>Yang ikut berubah bila disimpan:</b><ul class="rs-dampak">${l.map(x => `<li>${esc(x)}</li>`).join('')}</ul><span class="muted">Invoice tidak diubah karena biaya tetap.</span>` : '<span class="muted">Belum ada quotation, Sales Order, atau Surat Tugas yang terdampak.</span>'; };
    const baca = f => mps.map((x, i) => ({ i, mulai: f[`mulai${i}`].value, trainer: +f[`trainer${i}`].value, asisten: +f[`asisten${i}`].value }));
    openModal({
      title: `Reschedule ${esc(rec.nomor)}`, wide: 'x',
      body: `<p class="hint top">Program ini berisi ${mps.length} Master Program dengan jadwal, trainer, dan asisten masing-masing. Ubah satu atau beberapa baris sekaligus; biaya, waktu, dan lokasi tetap, durasi tiap Master Program tetap. Master Program yang mulainya kurang dari ${RS_H} hari lagi terkunci.${q('Q-PJ-23', 'Terjawab: tanpa approval; hak Reschedule diatur di Role & Permission (usulan: Manage pada Daftar Penjualan Program).')}</p>
        <div class="rs-geser"><label for="rs-geser">Geser semua tanggal</label><div class="input-unit"><input class="input" type="number" id="rs-geser" step="1" inputmode="numeric" placeholder="0"><span class="unit">hari</span></div>
          <button type="button" class="btn btn-outline sm" data-rs="geser">Terapkan ke semua</button><span class="hint">opsional; tanggal tiap baris tetap dapat diubah sesudahnya</span></div>
        <div class="table-wrap gap-t"><table class="table rs-tbl"><thead><tr><th>Master Program</th><th>Tanggal mulai baru</th><th>Trainer</th><th>Asisten trainer</th></tr></thead><tbody>${rows}</tbody></table></div>
        <div class="grid2 gap-t">
          ${field('Alasan reschedule' + req, '<textarea class="input" name="alasan" rows="3" maxlength="500" required placeholder="mis. Kuota peserta belum terpenuhi"></textarea>', '', 'span2')}
          ${field('Evidence' + req, `<label class="pb-drop"><input type="file" name="evidence" multiple accept=".jpg,.jpeg,.png,.doc,.docx,.pdf" hidden><b>Pilih file evidence</b><span class="hint">JPG, PNG, DOC/DOCX, PDF · maks. 5 MB per file · boleh lebih dari satu</span></label>
            <div class="hint" id="rs-files" aria-live="polite">Belum ada file</div>`, '', 'span2')}
        </div>
        <div class="notice gap-t" id="rs-dampak" aria-live="polite"><span>${dampakBox(false, false)}</span><span class="ref">PJ-FR-27, PJ-FR-34</span></div>`,
      saveLabel: 'Simpan jadwal baru',
      onMount: form => {
        const box = $('#rs-dampak span', form);
        const upd = () => { const j = baca(form.elements);
          j.forEach(r => { const o = form.querySelector(`[data-rs-sel="${r.i}"]`); if (o) o.textContent = r.mulai ? `selesai ${fmtTgl(addHari(r.mulai, (Number(mps[r.i].durasi) || 1) - 1))}` : '—'; });
          box.innerHTML = dampakBox(j.some(r => r.mulai !== mps[r.i].tglMulai), j.some(r => r.trainer !== mps[r.i].trainer || r.asisten !== mps[r.i].asisten)); };
        ['input', 'change'].forEach(ev => form.addEventListener(ev, e => { if (/^(mulai|trainer|asisten)\d+$/.test(e.target.name)) upd(); }));
        form.addEventListener('click', e => { if (!e.target.closest('[data-rs="geser"]')) return; const n = Math.round(Number($('#rs-geser', form).value) || 0); if (!n) return;
          mps.forEach((x, i) => { const el = form.elements[`mulai${i}`]; if (!el.disabled) el.value = addHari(x.tglMulai, n); }); upd(); });
        const ev = form.elements.evidence, list = $('#rs-files', form);
        ev.addEventListener('change', () => { list.textContent = [...ev.files].map(x => x.name).join(', ') || 'Belum ada file'; });
      },
      onSave: f => {
        const j = baca(f), alasan = f.alasan.value.trim(), files = [...f.evidence.files];
        let ok = true;
        j.forEach(r => { const x = mps[r.i], el = f[`mulai${r.i}`]; if (el.disabled) return;
          if (!r.mulai) ok = invalid(el, 'Isi tanggal mulai');
          else if (r.mulai !== x.tglMulai && r.mulai < minTgl) ok = invalid(el, `Paling cepat ${fmtTgl(minTgl)}`); });
        if (!alasan) ok = invalid(f.alasan, 'Alasan reschedule wajib diisi');
        const salahTipe = files.find(x => !EVID_EXT.includes(x.name.split('.').pop().toLowerCase())), besar = files.find(x => x.size > EVID_MAX);
        if (!files.length) ok = invalid(f.evidence, 'Unggah minimal satu file evidence');
        else if (salahTipe) ok = invalid(f.evidence, `${salahTipe.name}: hanya JPG, PNG, DOC/DOCX, atau PDF`);
        else if (besar) ok = invalid(f.evidence, `${besar.name} lebih dari 5 MB`);
        if (!ok) return false;
        const ubah = j.filter(r => r.mulai !== mps[r.i].tglMulai || r.trainer !== mps[r.i].trainer || r.asisten !== mps[r.i].asisten);
        if (!ubah.length) return invalid(f.mulai0.disabled ? f.alasan : f.mulai0, 'Jadwal belum berubah: ganti tanggal, trainer, atau asisten minimal satu Master Program');
        const h = reschedApply(rec, { jadwal: ubah, alasan, evidence: files.map(x => x.name) });
        toast(`${rec.nomor} di-reschedule (${ubah.length} Master Program) ke ${fmtRange(rec.tglMulai, h.selesai)}${h.dampak.length ? `. ${h.dampak.join('; ')}` : ''}`);
      }
    });
  }

  function qtResched(rec) {
    if (!canResched(rec)) { toast(`Reschedule hanya untuk Penjualan Program Final, paling lambat H-${RS_H} sebelum acara`, 'warn'); return; }
    if (pjMulti(rec)) return qtReschedMulti(rec);
    const m = rec;
    const d = Number(m.durasi) || 1, minTgl = addHari(todayIso, RS_H);
    const dampakBox = (tgl, tim) => { const l = reschedDampak(rec, tgl, tim); return l.length ? `<b>Yang ikut berubah bila disimpan:</b><ul class="rs-dampak">${l.map(x => `<li>${esc(x)}</li>`).join('')}</ul><span class="muted">Invoice tidak diubah karena biaya tetap.</span>` : '<span class="muted">Belum ada quotation, Sales Order, atau Surat Tugas yang terdampak.</span>'; };
    openModal({
      title: `Reschedule ${esc(rec.nomor)}`, wide: true,
      body: `<p class="hint top">Yang dapat diganti hanya <b>tanggal</b>, <b>trainer</b>, dan <b>asisten trainer</b>. Biaya, waktu, dan lokasi tetap. Durasi tetap ${d} hari, jadi tanggal selesai mengikuti tanggal mulai. Batas reschedule H-${RS_H}: ${fmtTgl(addHari(m.tglMulai, -RS_H))}.${q('Q-PJ-23', 'Terjawab: tanpa approval; hak Reschedule diatur di Role & Permission (usulan: Manage pada Daftar Penjualan Program).')}</p>
        <div class="summary">${sumRow('Jadwal sekarang', jadwalTxt(m.tglMulai, m.tglSelesai, rec.waktu, rec.lokasi, m.trainer, m.asisten))}${sumRow('Sudah di-reschedule', `${(rec.reschedule || []).length} kali`)}</div>
        <div class="grid2 gap-t">
          ${field('Tanggal mulai baru' + req + QPJ28, `<input class="input" type="date" name="tglMulai" id="rs-mulai" value="${esc(m.tglMulai)}" min="${minTgl}" required><span class="hint">paling cepat ${fmtTgl(minTgl)}</span>`)}
          ${field('Tanggal selesai', `<output class="static" id="rs-selesai" for="rs-mulai">${fmtTgl(m.tglSelesai) || '—'}</output><span class="hint">otomatis: mulai + ${d - 1} hari</span>`)}
          ${field('Trainer', `<select class="input" name="trainer">${db.trainer.filter(x => x.aktif || x.id === m.trainer).map(x => `<option value="${x.id}" ${x.id === m.trainer ? 'selected' : ''}>${esc(x.nama)}${x.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>`)}
          ${field('Asisten trainer', `<select class="input" name="asisten">${db.user.filter(x => x.aktif || x.id === m.asisten).map(x => `<option value="${x.id}" ${x.id === m.asisten ? 'selected' : ''}>${esc(x.nama)}${x.aktif ? '' : ' (nonaktif)'}</option>`).join('')}</select>`)}
          ${field('Alasan reschedule' + req, `<textarea class="input" name="alasan" rows="3" maxlength="500" required placeholder="mis. Kuota peserta belum terpenuhi"></textarea>`, '', 'span2')}
          ${field('Evidence' + req, `<label class="pb-drop"><input type="file" name="evidence" multiple accept=".jpg,.jpeg,.png,.doc,.docx,.pdf" hidden><b>Pilih file evidence</b><span class="hint">JPG, PNG, DOC/DOCX, PDF · maks. 5 MB per file · boleh lebih dari satu</span></label>
            <div class="hint" id="rs-files" aria-live="polite">Belum ada file</div>`, '', 'span2')}
        </div>
        <div class="notice gap-t" id="rs-dampak" aria-live="polite"><span>${dampakBox(false, false)}</span><span class="ref">PJ-FR-27</span></div>`,
      saveLabel: 'Simpan jadwal baru',
      onMount: form => {
        const jd = rec, m = form.elements.tglMulai, out = $('#rs-selesai', form), box = $('#rs-dampak span', form);
        const upd = () => { out.textContent = m.value ? fmtTgl(addHari(m.value, d - 1)) : '—';
          box.innerHTML = dampakBox(!!m.value && m.value !== jd.tglMulai, +form.elements.trainer.value !== jd.trainer || +form.elements.asisten.value !== jd.asisten); };
        ['input', 'change'].forEach(ev => form.addEventListener(ev, e => { if (['tglMulai', 'trainer', 'asisten'].includes(e.target.name)) upd(); }));
        const ev = form.elements.evidence, list = $('#rs-files', form);
        ev.addEventListener('change', () => { list.textContent = [...ev.files].map(x => x.name).join(', ') || 'Belum ada file'; });
      },
      onSave: f => {
        const trainer = +f.trainer.value, asisten = +f.asisten.value, mulai = f.tglMulai.value, alasan = f.alasan.value.trim(), files = [...f.evidence.files];
        let ok = true;
        if (!mulai) ok = invalid(f.tglMulai, 'Isi tanggal mulai baru');
        else if (mulai !== m.tglMulai && mulai < minTgl) ok = invalid(f.tglMulai, `Tanggal mulai baru paling cepat ${fmtTgl(minTgl)} (hari ini + ${RS_H} hari)`);
        if (!alasan) ok = invalid(f.alasan, 'Alasan reschedule wajib diisi');
        const salahTipe = files.find(x => !EVID_EXT.includes(x.name.split('.').pop().toLowerCase())), besar = files.find(x => x.size > EVID_MAX);
        if (!files.length) ok = invalid(f.evidence, 'Unggah minimal satu file evidence');
        else if (salahTipe) ok = invalid(f.evidence, `${salahTipe.name}: hanya JPG, PNG, DOC/DOCX, atau PDF`);
        else if (besar) ok = invalid(f.evidence, `${besar.name} lebih dari 5 MB`);
        if (!ok) return false;
        if (mulai === m.tglMulai && trainer === m.trainer && asisten === m.asisten) return invalid(f.tglMulai, 'Jadwal belum berubah: ganti tanggal, trainer, atau asisten');
        const h = reschedApply(rec, { mulai, trainer, asisten, alasan, evidence: files.map(x => x.name) });
        toast(`${rec.nomor} di-reschedule ke ${fmtRange(mulai, h.selesai)}${h.dampak.length ? `. ${h.dampak.join('; ')}` : ''}`);
      }
    });
  }

  // Batalkan Penjualan Program (input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/ butir 2):
  // Draft dan Final (belum Closed) boleh dibatalkan, termasuk setelah quotation. Semua yang terbentuk sesudahnya ikut Batal:
  // quotation, Sales Order (pindah ke SO Batal), invoice belum dibayar, Surat Tugas, pengajuan biaya yang masih menunggu.
  // Invoice yang sudah dibayar tidak diubah; dananya dikembalikan lewat modul Refund (perlu approval).
  const canBatalPj = pj => !pj.closed && (pj.status === 'draft' || pj.status === 'final');
  function pjDampakBatal(pj) {
    const qs = qtnOfPj(pj).filter(k => k.status !== 'batal'), inv = qs.flatMap(k => invOfQtn(k).filter(i => i.status !== 'batal'));
    return { qs, so: soOfPj(pj), invBelum: inv.filter(i => !i.bayar), invBayar: inv.filter(i => i.bayar), skt: db.skt.filter(r => r.pj === pj.id && r.status === 'berlaku'),
      pb: db.pb.filter(p => p.pj === pj.id && p.status === 'menunggu').concat(db.ob.filter(o => o.pj === pj.id && o.status === 'menunggu')) };
  }
  function pjBatal(pj, alasan) {
    const d = pjDampakBatal(pj), waktu = isoNow().slice(0, 16), sebab = `Penjualan Program ${pj.nomor} dibatalkan: ${alasan}`;
    d.qs.forEach(k => {
      if (soOfQtn(k)) soBatal(k, { rev: k.rev || 0, alasan, waktu, prog: pj.nomor, sebab: 'program' });
      invOfQtn(k).filter(i => i.status !== 'batal').forEach(i => {
        if (!i.bayar) { ivcBatal(i, sebab); return; }
        const satu = !isB2bQtn(k) && (i.peserta || []).length === 1;
        i.refund = rfCreate({ k, ps: satu ? i.peserta[0] : null, inv: i, alasan: sebab, nominal: i.total, sebab: 'batal' }).nomor;
      });
      k.status = 'batal'; k.batalProgram = { alasan, waktu }; delete k.dealUlang;
      k.peserta.forEach(r => { if (r.st !== 'keluar') r.st = 'batal'; });
      if (k.link) linkPublish(k); // link konfirmasi ikut tertutup
    });
    d.skt.forEach(r => { r.status = 'batal'; r.aktif = false; r.batal = { alasan, waktu }; });
    d.pb.forEach(r => { r.status = 'batal'; r.alasanBatal = sebab; });
    pj.status = 'batal'; pj.batal = { alasan, waktu, oleh: meName() }; syncPf(pj);
    return d;
  }
  function qtCancel(rec) {
    if (!canBatalPj(rec)) { toast(rec.closed ? `${rec.nomor} sudah Closed dan tidak dapat dibatalkan` : `${rec.nomor} sudah Batal`, 'warn'); return; }
    const d = pjDampakBatal(rec), li = [
      d.qs.length && `${d.qs.length} quotation menjadi <b>Batal</b> dan link konfirmasinya ditutup`,
      d.so.length && `${d.so.length} Sales Order pindah ke tab <b>SO Batal</b>`,
      d.invBelum.length && `${d.invBelum.length} invoice belum dibayar dibatalkan`,
      d.invBayar.length && `${d.invBayar.length} invoice sudah dibayar tetap ada; refund ${rp(d.invBayar.reduce((t, i) => t + i.total, 0))} masuk modul <b>Refund</b> (menunggu approval)`,
      d.skt.length && 'Surat Tugas trainer menjadi Batal',
      d.pb.length && `${d.pb.length} pengajuan biaya yang menunggu approval dibatalkan`
    ].filter(Boolean);
    openModal({
      title: 'Batalkan Penjualan Program',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(rec.nomor)}</b>? Penjualan Program Batal tidak dapat diubah.</p>
        ${li.length ? `<div class="notice"><span>Yang ikut terdampak:<ul class="gap-t">${li.map(t => `<li>${t}</li>`).join('')}</ul></span></div>` : ''}
        ${field('Alasan pembatalan' + req, '<textarea class="input" name="alasan" rows="2" maxlength="300" placeholder="mis. peserta tidak memenuhi kuota minimum"></textarea>', '', 'gap-t')}`,
      saveLabel: 'Batalkan Program', danger: true,
      onSave: f => {
        const alasan = f.alasan.value.trim();
        if (!alasan) return invalid(f.alasan, 'Isi alasan pembatalan');
        pjBatal(rec, alasan);
        toast(`Penjualan Program ${rec.nomor} dibatalkan${li.length ? `; ${d.qs.length} quotation, ${d.so.length} SO, ${d.invBelum.length} invoice ikut batal${d.invBayar.length ? `, ${d.invBayar.length} refund menunggu approval` : ''}` : ''}`);
      }
    });
  }
  // ---------- Tutorial highlight (driver.js) modul Penjualan Program (permintaan BA 12-11-2026) ----------
  // Dimulai dari tombol Tutorial mengambang (kanan bawah). Daftar → form → langkah berikutnya: langkah terakhir tiap halaman
  // punya tombol lanjut yang membuka halaman berikutnya dan meneruskan tutorial. Langkah yang elemennya tidak ada dilewati.
  const tourRows = () => [...document.querySelectorAll('#list tbody tr')];
  const tourKol = (tr, label) => { if (!tr) return null; const i = [...document.querySelectorAll('#list thead th')].findIndex(th => th.textContent.trim().startsWith(label)); return i < 0 ? null : tr.children[i]; };
  const tourField = label => [...document.querySelectorAll('.field')].find(f => { const l = f.querySelector('label'); return l && l.textContent.trim().startsWith(label); });
  const tourStep = n => document.querySelector(`[data-step="${n}"]`);

  // Daftar Master Program
  TOUR.mprog = () => {
    const rows = tourRows(), final = rows.find(tr => tr.querySelector('.badge.st-final')), draft = rows.find(tr => tr.querySelector('.badge.st-draft'));
    return [
      { popover: { title: 'Daftar Master Program', description: 'Program siap jual beserta <b>costing baseline</b>. Belum ada tanggal, lokasi, atau customer; semuanya diisi di Penjualan Program. Biasanya dikelola <b>Manajemen</b>.' } },
      { element: '.toolbar', popover: { title: 'Cari dan saring', description: 'Cari nomor atau judul, dan saring menurut status Draft / Final / Batal.' } },
      draft && { element: draft.querySelector('.gear'), popover: { title: 'Aksi program Draft', description: '<b>Edit</b>: semua isian masih bisa diubah.<br><b>Finalkan</b>: program terkunci dan menjadi baseline resmi.<br><b>Batalkan</b>: program tidak muncul lagi di pilihan.' } },
      final && { element: final.querySelector('.gear'), popover: { title: 'Aksi program Final', description: '<b>Lihat</b>: membuka program (terkunci).<br><b>Buat Penjualan Program</b>: langsung membuat jadwal penjualan dari program ini.' } },
      { element: '[data-act="mp-new"]', popover: { title: 'Mulai: Buat Master Program', description: 'Klik tombol ini untuk menyusun program baru. Tutorial berlanjut di form.' },
        lanjut: { label: 'Buka form', run: () => openCostForm('mp'), ke: 'mpForm' } }
    ];
  };
  // Form Master Program
  TOUR.mpForm = () => {
    const p = state.pf.p, draft = p.status === 'draft';
    return [
      { element: '.pf-formbar', popover: { title: draft ? 'Form Master Program' : `Master Program ${p.status === 'final' ? 'Final' : 'Batal'}`, description: draft ? 'Isi dari atas ke bawah. Setiap bagian dapat dibuka/tutup dengan klik judulnya; ringkasannya tampil di sebelah judul.' : 'Program ini terkunci. Isian hanya dapat dilihat.' } },
      { element: tourStep(1), popover: { title: '1. Informasi Program', description: 'Pilih <b>satu Product</b> aktif, lalu isi Jam Pembelajaran dan angka baseline: durasi, target peserta, harga jual standar per peserta, dan target profit. Beberapa program digabung nanti di Penjualan Program.' } },
      { element: tourStep(2), popover: { title: '2. Fixed Cost', description: 'Biaya yang keluar berapa pun jumlah pesertanya (trainer, ruangan, dll.), per event atau per hari. Item terisi dari Master Rate; nominal boleh diubah, item yang tidak dipakai dihapus dengan ×.' } },
      { element: tourStep(3), popover: { title: '3. Variable Cost', description: 'Biaya per peserta (per event atau per hari), mis. makan, seminar kit. Tambah hanya item yang dipakai.' } },
      { element: tourStep(4), popover: { title: '4. Simulasi Hasil', description: 'Pendapatan, HPP, profit, dan <b>titik impas</b> dihitung otomatis setiap angka berubah. Peringatan muncul bila rugi atau titik impas di atas target peserta.' } },
      draft && { element: '[data-act="pf-save"]', popover: { title: 'Simpan Draft', description: 'Menyimpan tanpa mengunci. Program Draft masih bisa diubah atau dibatalkan.' } },
      draft && { element: '[data-act="pf-confirm"]', popover: { title: 'Finalkan program', description: 'Mengunci program sebagai baseline resmi. Tombol ini muncul setelah program pernah disimpan.' } },
      p.status === 'final' && { element: '[data-act="qt-from-mp"]', popover: { title: 'Buat Penjualan Program', description: 'Program Final dipakai untuk membuat jadwal penjualan Public atau B2B.' } },
      { popover: { title: 'Langkah berikutnya', description: 'Setelah <b>Finalkan</b>, buat jadwal penjualan di <b>Daftar Penjualan Program</b>.' },
        lanjut: { label: 'Ke Penjualan Program', run: () => go('quot'), ke: 'quot' } }
    ];
  };
  // Daftar Penjualan Program
  TOUR.quot = () => {
    const rows = tourRows(), final = rows.find(tr => tr.querySelector('.badge.st-final') && tr.querySelector('.gear')), draft = rows.find(tr => tr.querySelector('.badge.st-draft'));
    const resch = rows.find(tr => /Reschedule \d/.test(tr.textContent)), row = final || rows[0];
    return [
      { popover: { title: 'Daftar Penjualan Program', description: 'Jadwal penjualan dari Master Program Final: <b>Public</b> atau <b>B2B</b>, tanggal, lokasi, trainer, asisten trainer, dan harga. Dari sini quotation dibuat.' } },
      { element: '.toolbar', popover: { title: 'Cari dan saring', description: 'Saring menurut jenis Public / B2B, status, serta Tahun dan Bulan tanggal acara.' } },
      row && { element: tourKol(row, 'Master Program'), popover: { title: 'Master Program', description: 'Satu penjualan boleh berisi <b>lebih dari satu Master Program</b>; judulnya digabung dengan "+".' } },
      row && { element: tourKol(row, 'Pelaksanaan'), popover: { title: 'Pelaksanaan', description: 'Tanggal, Jam Pembelajaran, lokasi, trainer, dan asisten. Program yang pernah di-reschedule bertanda <b>Reschedule n×</b>.' } },
      row && { element: tourKol(row, 'Status'), popover: { title: 'Status', description: '<b>Draft</b>: masih bisa diubah. <b>Final</b>: terkunci dan siap dibuatkan quotation. <b>Batal</b>: dari Draft, atau dari Final yang belum Closed; quotation, SO, dan invoice sesudahnya ikut Batal.' } },
      draft && { element: draft.querySelector('.gear'), popover: { title: 'Aksi program Draft', description: '<b>Edit</b>: ubah isian.<br><b>Finalkan</b>: kunci angka penjualan (butuh hak Approve: Manajemen atau Sales/Marketing).<br><b>Batalkan</b>: Draft maupun Final yang belum Closed. Semua quotation, SO, invoice belum dibayar ikut Batal; yang sudah dibayar masuk Refund.' } },
      final && { element: final.querySelector('.gear'), popover: { title: 'Aksi program Final', description: '<b>Lihat</b>: buka program beserta Riwayat Reschedule.<br><b>+ Buat Quotation</b>: tawarkan ke customer.<br><b>Reschedule</b>: ganti tanggal, trainer, atau asisten, paling lambat <b>H-7</b>; alasan dan evidence wajib.<br><b>Cetak Surat Penawaran</b>: ringkasan penawaran program.<br><b>Batalkan</b>: selama belum Closed, juga setelah ada quotation; quotation, SO, dan invoice belum dibayar ikut Batal, invoice yang sudah dibayar masuk Refund.' } },
      resch && { element: tourKol(resch, 'Nomor'), popover: { title: 'Setelah reschedule', description: 'Nomor program dan quotation menjadi <b>-rev</b>. Quotation yang sudah Deal kembali ke Terkirim; Sales Order-nya pindah ke <b>SO Batal</b>. Peserta mengisi link konfirmasi baru: <b>Pindah jadwal</b>, atau <b>Refund</b> bila sudah bayar / <b>Batal ikut</b> bila belum. Setelah itu <b>Deal ulang</b> menerbitkan SO dengan nomor sama + -rev. Biaya, harga, dan invoice tidak berubah.' } },
      { element: '[data-act="qt-new"]', popover: { title: 'Mulai: Buat Penjualan Program', description: 'Klik tombol ini untuk membuat jadwal penjualan. Tutorial berlanjut di form.' },
        lanjut: { label: 'Buka form', run: () => openCostForm('qt'), ke: 'qtStart' } }
    ];
  };
  // Form Penjualan Program, layar awal: pilih Master Program dan jenis
  TOUR.qtStart = () => [
    { element: '#qt-mp', popover: { title: '1. Pilih Master Program', description: 'Hanya Master Program berstatus <b>Final</b> yang muncul. Ini Master Program pertama; durasi, target peserta, harga, dan costing disalin dari program ini. Master Program lain ditambah di form berikutnya.' } },
    { element: '[data-jenis="public"]', popover: { title: '2a. Penjualan Public', description: 'Acara terbuka; boleh banyak quotation ke Company atau Personal.' } },
    { element: '[data-jenis="b2b"]', popover: { title: '2b. Penjualan B2B', description: 'Acara untuk satu company (in-house). Company wajib, target peserta minimal 10, satu quotation ke company itu.' } },
    { popover: { title: 'Lanjut ke isian penjualan', description: 'Pilih Master Program dan jenis untuk membuka form. Untuk tutorial, sistem mengisi contoh: Master Program Final pertama, jenis Public. Tidak ada yang tersimpan sampai Anda klik Simpan Draft.' },
      lanjut: { label: 'Isi contoh & lanjut', run: () => {
        const sel = document.querySelector('#qt-mp'); if (sel && !sel.value && sel.options[1]) { sel.value = sel.options[1].value; sel.dispatchEvent(new Event('change', { bubbles: true })); }
        const b = document.querySelector('[data-jenis="public"]'); if (b && !b.disabled) b.click();
      }, ke: 'qtForm' } }
  ];
  // Form Penjualan Program, isian lengkap (baru, Draft, atau Final)
  TOUR.qtForm = () => {
    const p = state.pf.p, draft = p.status === 'draft', final = p.status === 'final', b2b = p.jenis === 'b2b';
    const riwayat = [...document.querySelectorAll('.csec-h')].find(h => h.textContent.startsWith('Riwayat Reschedule'));
    return [
      { element: '.pf-formbar', popover: { title: draft ? 'Form Penjualan Program' : `Penjualan Program ${final ? 'Final' : 'Batal'}`, description: draft ? 'Angka disalin dari Master Program dan boleh disesuaikan untuk penjualan ini tanpa mengubah Master Program.' : final ? 'Program Final terkunci. Yang masih bisa diubah hanya jadwal, trainer, dan asisten lewat Reschedule.' : 'Program Batal tidak dapat diubah.' } },
      { element: '#pf-mps', popover: { title: 'Master Program & costing', description: `Daftar Master Program dalam penjualan ini: jadwal, trainer, asisten, harga, HPP, dan profit masing-masing${draft ? '. <b>+ Tambah Master Program</b> untuk menggabungkan program lain; tiap program membawa jadwal dan costing sendiri' : ''}. Klik nama Master Program untuk menampilkan isiannya di bagian 1–4. Harga jual / peserta di quotation = jumlah semua Master Program; jadwal program = mulai paling awal s.d. selesai paling akhir.` } },
      draft && { element: '#pj-mp-add', popover: { title: '+ Tambah Master Program', description: 'Pilih Master Program Final lain. Costing dan JP disalin; <b>tanggal, trainer, dan asisten dipilih sendiri</b> di bagian 1 (klik nama Master Program-nya).' } },
      { element: tourStep(1), popover: { title: '1. Informasi Penjualan', description: `${b2b ? '<b>Company</b> wajib dipilih dari Master Company. ' : ''}Isi <b>tanggal mulai dan selesai</b> (durasi dihitung otomatis), waktu, <b>Jam Pembelajaran</b> (dicetak di Penawaran), lokasi, lalu input simulasi. Tanggal, Jam Pembelajaran, trainer, asisten, dan harga jual milik <b>Master Program yang sedang tampil</b>; target peserta, waktu, dan lokasi berlaku untuk seluruh program.` } },
      { element: tourField('Trainer'), popover: { title: 'Trainer', description: 'Wajib, dari Master Trainer aktif. Satu trainer per event; menerima Surat Tugas.' } },
      { element: tourField('Asisten trainer'), popover: { title: 'Asisten trainer', description: 'Wajib, staf LabMania dari Master User. Ikut tercetak di Surat Tugas.' } },
      { element: tourStep(2), popover: { title: '2–3. Fixed & Variable Cost', description: 'Costing Master Program yang sedang tampil (nomornya di judul bagian). Nominal dan item boleh disesuaikan; item bernilai 0 dibuang saat disimpan.' } },
      { element: tourStep(4), popover: { title: '4. Simulasi Hasil', description: 'Pendapatan, HPP, profit, dan titik impas Master Program yang sedang tampil, dihitung ulang setiap angka berubah. Total seluruh program ada di tabel Master Program.' } },
      riwayat && { element: riwayat.parentElement, popover: { title: 'Riwayat Reschedule', description: 'Jadwal lama dan baru, alasan, evidence, dan dampaknya (quotation & SO -rev, Surat Tugas baru).' } },
      draft && { element: '[data-act="pf-save"]', popover: { title: 'Simpan Draft', description: 'Menyimpan tanpa mengunci; nomor penjualan dibuat saat pertama disimpan.' } },
      draft && { element: '[data-act="pf-confirm"]', popover: { title: 'Finalkan', description: 'Mengunci angka penjualan; muncul ringkasan dan peringatan bila rugi. Tombol ini muncul setelah penjualan pernah disimpan.' } },
      final && { element: '.form-actions [data-act="qt-cancel"]', popover: { title: 'Batalkan', description: 'Membatalkan program beserta quotation, Sales Order, invoice belum dibayar, Surat Tugas, dan pengajuan biaya yang menunggu. Invoice yang sudah dibayar masuk modul Refund. Alasan wajib.' } },
      final && { element: '[data-act="qt-resched"]', popover: { title: 'Reschedule', description: 'Ganti tanggal, trainer, atau asisten paling lambat H-7. Program dengan beberapa Master Program: ubah satu atau beberapa Master Program sekaligus dalam satu modal (ada juga Geser semua). Modal menampilkan dampaknya sebelum disimpan.' } },
      final && { element: '[data-act="qt-print"]', popover: { title: 'Cetak Surat Penawaran', description: 'Ringkasan penawaran dari angka Final.' } },
      { popover: { title: 'Langkah berikutnya', description: 'Setelah <b>Final</b>, program ditawarkan lewat <b>Daftar Quotation</b>: buat quotation, bagikan link Konfirmasi Kehadiran, lalu tandai Deal.' },
        lanjut: { label: 'Ke Quotation', run: () => go('qtn'), ke: null } }
    ];
  };
