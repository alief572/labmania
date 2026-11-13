/* Labmania mockup — Manage Peserta.
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
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

