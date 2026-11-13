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
        <div class="card"><div class="tabs">${[['peserta', 'Daftar Peserta'], ['sertifikat', 'Sertifikat']].map(([t, l]) => `<button class="tab ${state.mpvTab === t ? 'active' : ''}" data-act="mpv-tab" data-tab="${t}">${l}</button>`).join('')}</div>
        ${toolbar(state.mpvTab === 'sertifikat' ? 'Cari nama, instansi, quotation, atau nomor sertifikat…' : 'Cari kode, nama, instansi, kontak, atau nomor quotation…', false)}<div id="list">${listMpst()}</div>
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
    if (pj && state.mpvTab === 'sertifikat') return listSertProg(pj);
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

  // ---------- Sertifikat Peserta & Monitoring Peserta (to be) ----------
  // input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/ dan .../2026-11-09-sertifikat-monitoring-sesuai-sistem/:
  // data sertifikat diinput di tab Sertifikat halaman peserta program (Manage Peserta); Monitoring hanya membaca data
  // yang ada di sistem (peserta, program, PIC, invoice, sertifikat) dan menyimpan catatan.
  const QMON = (n, t) => q(`Q-MON-${String(n).padStart(2, '0')}`, t);
  db.sert = db.sert || {};
  state.mpvTab = 'peserta';
  const SERT_KIRIM = { belum: 'Belum dikirim', soft: 'Dikirim softcopy', hard: 'Dikirim hardcopy' };
  const SERT_METODE = { '': '—', ukom: 'Uji Kompetensi', porto: 'Portofolio' };
  const SERT_HASIL = { belum: 'Belum diuji', k: 'Kompeten', bk: 'Belum Kompeten' };
  const SERT_IDCARD = { belum: 'Belum dikirim', sudah: 'Sudah dikirim' };
  const SERT_EXT = ['pdf', 'jpg', 'jpeg', 'png'];
  const sertOf = p => db.sert[p.id] || (db.sert[p.id] = { noLatih: '', tglTerbit: '', fileLatih: '', kirim: 'belum', tglKirim: '', via: '',
    lsp: (p.data && p.data.lsp) || '', skema: '', metode: '', hasil: 'belum', noBnsp: '', bnspTerima: '', bnspKirim: '', berlaku: (p.data && p.data.habis) || '', idcard: 'belum', fileBnsp: '', catatan: '' });
  // Tipe program dari Jenis Produk semua Product di Master Program: Pelatihan, Sertifikasi, atau Training & Sertifikasi.
  const monTipe = pj => {
    const jenis = new Set(paketList(mpOf(pj)).map(x => findRec('jenisProduk', x.produk).nama));
    return jenis.has('Sertifikasi') && jenis.has('Pelatihan') ? 'Training & Sertifikasi' : jenis.has('Sertifikasi') ? 'Sertifikasi' : 'Pelatihan';
  };
  const monSert = pj => monTipe(pj) !== 'Pelatihan';
  const monInv = (k, p) => invOfQtn(k).find(i => i.peserta.includes(p.id));
  const sisaHari = iso => iso ? Math.round((new Date(iso + 'T00:00:00') - new Date(todayIso + 'T00:00:00')) / 864e5) : null;
  const MON_TL = {
    inv: ['Belum dibuatkan invoice', x => !monInv(x.k, x.p)],
    latih: ['Sertifikat pelatihan belum dikirim', x => pjSelesai(x.pj) && x.s.kirim === 'belum'],
    bnsp: ['Sertifikat BNSP belum diterima', x => monSert(x.pj) && x.s.hasil === 'k' && !x.s.bnspTerima],
    exp: ['Sertifikat habis ≤ 90 hari', x => { const d = sisaHari(x.s.berlaku); return d !== null && d <= 90; }]
  };
  let sertSeeded = false;
  const monList = () => mpProgs().flatMap(pj => mpPeserta(pj).map(({ p, k }) => ({ pj, p, k, s: sertOf(p) })));
  const monAll = () => { if (!sertSeeded) { sertSeeded = true; sertSeed(); } return monList(); };
  const monRows = () => monAll()
    .filter(x => state.jenis === 'all' || monTipe(x.pj) === state.jenis)
    .filter(x => ymOk(x.pj.tglMulai))
    .filter(x => state.status === 'all' || (MON_TL[state.status] && MON_TL[state.status][1](x)))
    .filter(x => matches([x.p.nama, pesertaCo(x.p), x.p.email, x.p.telp, x.pj.nomor, mpJudul(x.pj), x.k.nomor, (salesOf(x.k) || {}).nama, x.s.lsp, x.s.skema, x.s.noLatih, x.s.noBnsp]));
  const badgeOn = (on, txt) => `<span class="badge ${on ? 'on' : 'off'}">${esc(txt)}</span>`;
  const latihTxt = s => `${s.noLatih ? `<span class="code">${esc(s.noLatih)}</span>` : '<span class="muted">Belum terbit</span>'}<br>${badgeOn(s.kirim !== 'belum', SERT_KIRIM[s.kirim])}${s.tglKirim ? ` <span class="muted">${fmtTgl(s.tglKirim)}${s.via ? ` · ${esc(s.via)}` : ''}</span>` : ''}`;
  const bnspTxt = (pj, s) => !monSert(pj) ? '<span class="muted">— (program pelatihan)</span>'
    : `${badgeOn(s.hasil === 'k', SERT_HASIL[s.hasil])} <span class="muted">${SERT_METODE[s.metode]}</span><br><span class="muted">${esc(s.lsp || 'LSP —')} · ${esc(s.skema || 'skema —')}</span>
      <br><span class="muted">${s.noBnsp ? `No. <span class="code">${esc(s.noBnsp)}</span> · ` : ''}Diterima ${s.bnspTerima ? fmtTgl(s.bnspTerima) : '—'} · Dikirim ${s.bnspKirim ? fmtTgl(s.bnspKirim) : '—'} · ID card ${SERT_IDCARD[s.idcard].toLowerCase()}</span>`;
  const berlakuTxt = s => { const d = sisaHari(s.berlaku);
    return s.berlaku ? `${fmtTgl(s.berlaku)}<br>${d < 0 ? '<span class="neg">sudah habis</span>' : d <= 90 ? `<span class="neg">${d} hari lagi</span>` : `<span class="muted">${d} hari lagi</span>`}` : '<span class="muted">—</span>'; };

  // Tab Sertifikat di halaman peserta program (Manage Peserta › Lihat Peserta).
  // Sertifikat pelatihan dibuat di sistem dari data peserta & program; template dan format nomor sementara (input/Invoice & Pengajuan/2026-11-09-buat-sertifikat-di-sistem/).
  // Sertifikat BNSP tetap diunggah karena diterbitkan LSP/BNSP.
  const QSRT = q('Q-MON-06', 'Format sertifikat dan nomor menyusul dari LabMania. Sementara: nomor SRT/LMI/MM/yy-zzz (urut per bulan terbit), satu sertifikat per peserta per program, tanda tangan Trainer dan Direktur.');
  db.sertSeq = db.sertSeq || {};
  const sertNo = iso => { const mm = iso.slice(5, 7), yy = iso.slice(2, 4), key = yy + mm, n = (db.sertSeq[key] || 0) + 1; db.sertSeq[key] = n; return `SRT/LMI/${mm}/${yy}-${String(n).padStart(3, '0')}`; };
  function pageSertifikat(x) {
    const pj = x.pj, tr = trainerById(pj.trainer), s = x.s;
    return pdfPage(`<div class="srt-doc"><p class="srt-h">SERTIFIKAT</p><p class="srt-no">Nomor: ${esc(s.noLatih)}</p>
      <p>diberikan kepada</p><p class="srt-nama">${esc(x.p.nama)}</p><p class="srt-inst">${esc(pesertaCo(x.p))}</p>
      <p>atas partisipasinya sebagai <b>peserta</b> dalam</p><p class="srt-judul">${esc(mpJudul(pj))}</p>
      <p>${esc(varianTxt(pj))} · ${esc(rangePanjang(pj.tglMulai, pj.tglSelesai))} · ${esc(pj.lokasi || '')}${pj.jamPembelajaran ? ` · ${pj.jamPembelajaran} JP` : ''}</p>
      <div class="srt-ttd"><div>Trainer<div class="pdf-ttd"></div><b>${esc(tr ? tr.nama : '..............................')}</b></div>
        <div>Bekasi, ${tglPanjang(s.tglTerbit)}<br>PT LabMania Indonesia Jaya<div class="pdf-ttd"></div><b>(..............................)</b><br>Direktur</div></div></div>`,
      pdfFoot('[kode dokumen menyusul]', '1 dari 1', '—'));
  }
  // Buat (bila belum ada nomor) lalu tampilkan pratinjau; dipakai per peserta dan untuk semua peserta program.
  function sertBuat(list, judul) {
    const baru = list.filter(x => !x.s.noLatih);
    baru.forEach(x => { Object.assign(x.s, { noLatih: sertNo(todayIso), tglTerbit: todayIso, fileLatih: '' }); });
    if (baru.length) logAct('Manage Peserta', 'Sertifikat', 'Tambah', judul, null, `Buat sertifikat pelatihan ${baru.length} peserta: ${baru.map(x => x.s.noLatih).join(', ')}`);
    openModal({ title: `Sertifikat: ${judul}`, wide: 'x', info: true, body: pdfWrap(list.map(pageSertifikat).join(''), `${list.length} sertifikat${baru.length ? ` (${baru.length} baru dibuat)` : ''} · template sementara${QSRT}`), onMount: pdfMount });
    if (baru.length) toast(`${baru.length} sertifikat dibuat`);
  }
  const sertRowsOf = pj => mpPeserta(pj).map(({ p, k }) => ({ pj, p, k, s: sertOf(p) }));
  function listSertProg(pj) {
    const all = sertRowsOf(pj), belum = all.filter(x => !x.s.noLatih).length;
    const bar = `<div class="srt-bar"><span class="muted">${all.length - belum} dari ${all.length} sertifikat pelatihan sudah dibuat${QSRT}</span>
      <span><button type="button" class="btn btn-outline sm" data-act="sert-buat-all" data-id="${pj.id}" ${all.length - belum ? '' : 'disabled'}>Lihat semua</button>
      <button type="button" class="btn btn-primary sm" data-act="sert-buat-all" data-id="${pj.id}" data-baru="1" ${belum ? '' : 'disabled'}>Buat sertifikat (${belum} belum)</button></span></div>`;
    const rows = mpPeserta(pj).filter(({ p, k }) => matches([p.nama, pesertaCo(p), k.nomor, sertOf(p).noLatih, sertOf(p).noBnsp])).map(({ p, k }) => ({ id: p.id, p, k, s: sertOf(p) }));
    return listBlock(rows, [
      NO_COL,
      { label: 'Peserta', cls: 'title', render: x => `<b>${esc(x.p.nama)}</b><br><span class="muted">${esc(pesertaCo(x.p))}</span>` },
      { label: 'Sertifikat Pelatihan', render: x => `${latihTxt(x.s)}${x.s.tglTerbit ? `<br><span class="muted">Terbit ${fmtTgl(x.s.tglTerbit)}</span>` : ''}` },
      ...(monSert(pj) ? [{ label: 'Sertifikasi LSP / BNSP', render: x => bnspTxt(pj, x.s) }, { label: 'Berlaku sampai', cls: 'nowrap', render: x => berlakuTxt(x.s) }] : []),
      { label: 'Action', cls: 'act', render: x => gearMenu({ id: x.p.id }, [x.s.noLatih ? ['sert-lihat', 'Lihat Sertifikat'] : ['sert-lihat', 'Buat Sertifikat'], ['sert-edit', monSert(pj) ? 'Pengiriman & Sertifikasi BNSP' : 'Pengiriman Sertifikat']]) }
    ]).replace(/^/, bar);
  }
  function sertEdit(p) {
    const x = monAll().find(r => r.p.id === p.id); if (!x) return;
    const s = x.s, bnsp = monSert(x.pj);
    const opt = (o, v) => Object.entries(o).map(([k, l]) => `<option value="${k}" ${k === v ? 'selected' : ''}>${l}</option>`).join('');
    const fileField = (name, cur) => `<label class="pb-drop"><input type="file" name="${name}" accept=".pdf,.jpg,.jpeg,.png" hidden><b>${cur ? 'Ganti file' : 'Pilih file'}</b><span class="hint">PDF, JPG, PNG · maks. 5 MB</span></label><div class="hint" data-file-out="${name}">${cur ? esc(cur) : 'Belum ada file'}</div>`;
    openModal({
      title: `${bnsp ? 'Pengiriman & Sertifikasi BNSP' : 'Pengiriman Sertifikat'}: ${p.nama}`, wide: true,
      body: `<div class="summary">${sumRow('Program', `<span class="code">${esc(x.pj.nomor)}</span> ${esc(mpJudul(x.pj))} · ${esc(monTipe(x.pj))}`)}${sumRow('Instansi', esc(pesertaCo(p)))}</div>
        <p class="csec-h gap-t">Sertifikat pelatihan (LabMania)</p><div class="grid2">
          ${field('Nomor sertifikat', `<div class="static">${s.noLatih ? `<span class="code">${esc(s.noLatih)}</span> · terbit ${tglPanjang(s.tglTerbit)}` : '<span class="muted">Belum dibuat. Gunakan <b>Buat Sertifikat</b>.</span>'}</div>`, '', 'span2')}
          ${field('Status kirim', `<select class="input" name="kirim">${opt(SERT_KIRIM, s.kirim)}</select>`)}
          ${field('Tanggal kirim', `<input class="input" type="date" name="tglKirim" value="${esc(s.tglKirim)}">`)}
          ${field('Dikirim lewat', `<input class="input" name="via" maxlength="100" value="${esc(s.via)}" placeholder="mis. TIKI, GoSend a.n. PIC, email">`, '', 'span2')}</div>
        ${bnsp ? `<p class="csec-h gap-t">Sertifikasi LSP / BNSP</p><div class="grid2">
          ${field('LSP' + QMON(7, 'LSP diketik bebas (usulan sementara). Perlu Master LSP dan Master Skema agar pilihan seragam?'), `<input class="input" name="lsp" maxlength="100" value="${esc(s.lsp)}">`)}
          ${field('Skema', `<input class="input" name="skema" maxlength="150" value="${esc(s.skema)}">`)}
          ${field('Metode', `<select class="input" name="metode">${opt(SERT_METODE, s.metode)}</select>`)}
          ${field('Hasil', `<select class="input" name="hasil">${opt(SERT_HASIL, s.hasil)}</select>`)}
          ${field('Nomor sertifikat BNSP', `<input class="input" name="noBnsp" maxlength="60" value="${esc(s.noBnsp)}">`)}
          ${field('Berlaku sampai', `<input class="input" type="date" name="berlaku" value="${esc(s.berlaku)}"><span class="hint">untuk pengingat resertifikasi</span>`)}
          ${field('Diterima dari LSP', `<input class="input" type="date" name="bnspTerima" value="${esc(s.bnspTerima)}">`)}
          ${field('Dikirim ke peserta', `<input class="input" type="date" name="bnspKirim" value="${esc(s.bnspKirim)}">`)}
          ${field('ID card', `<select class="input" name="idcard">${opt(SERT_IDCARD, s.idcard)}</select>`)}
          ${field('File sertifikat BNSP', fileField('fileBnsp', s.fileBnsp))}</div>` : ''}`,
      saveLabel: 'Simpan',
      onMount: form => form.querySelectorAll('input[type=file]').forEach(el => el.addEventListener('change', () => { $(`[data-file-out="${el.name}"]`, form).textContent = el.files[0] ? el.files[0].name : 'Belum ada file'; })),
      onSave: f => {
        const keys = ['kirim', 'tglKirim', 'via'].concat(bnsp ? ['lsp', 'skema', 'metode', 'hasil', 'noBnsp', 'berlaku', 'bnspTerima', 'bnspKirim', 'idcard'] : []);
        const baru = Object.fromEntries(keys.map(k => [k, f[k].value.trim()]));
        let ok = true;
        for (const name of bnsp ? ['fileBnsp'] : []) {
          const file = f[name].files[0];
          if (!file) continue;
          if (!SERT_EXT.includes(file.name.split('.').pop().toLowerCase())) ok = invalid(f[name], `${file.name}: hanya PDF, JPG, atau PNG`);
          else if (file.size > 5 * 1024 * 1024) ok = invalid(f[name], `${file.name} lebih dari 5 MB`);
          else baru[name] = file.name;
        }
        if (baru.kirim !== 'belum' && !baru.tglKirim) ok = invalid(f.tglKirim, 'Isi tanggal kirim');
        if (baru.kirim !== 'belum' && !s.noLatih) ok = invalid(f.kirim, 'Buat sertifikat dulu sebelum ditandai dikirim');
        if (bnsp && baru.bnspKirim && !baru.bnspTerima) ok = invalid(f.bnspTerima, 'Isi tanggal diterima dari LSP dulu');
        if (bnsp && baru.bnspKirim && baru.bnspTerima && baru.bnspKirim < baru.bnspTerima) ok = invalid(f.bnspKirim, 'Tidak boleh sebelum tanggal diterima');
        if (!ok) return false;
        const ubah = Object.keys(baru).filter(k => (s[k] || '') !== baru[k]);
        Object.assign(s, baru);
        if (ubah.length) logAct('Manage Peserta', 'Sertifikat', 'Edit', p.nama, p.id, `Update sertifikat ${p.nama} (${x.pj.nomor}): ${ubah.join(', ')}`);
        toast(`Sertifikat ${p.nama} disimpan`);
      }
    });
  }

  // Sub menu Monitoring Peserta: hanya membaca data sistem; catatan monitoring dapat diisi.
  function viewMon() {
    // Satu daftar tanpa tab (input/Invoice & Pengajuan/2026-11-09-monitoring-satu-daftar/); jenis kegiatan lewat filter Tipe.
    const all = monAll(), cnt = t => all.filter(x => monTipe(x.pj) === t).length;
    const TIPE = ['Pelatihan', 'Sertifikasi', 'Training & Sertifikasi'];
    const kpi = Object.entries(MON_TL).map(([key, [label, fn]]) => { const n = all.filter(fn).length;
      return `<button type="button" class="mon-kpi ${state.status === key ? 'on' : ''} ${n ? 'ada' : ''}" data-act="mon-tl" data-s="${key}" aria-pressed="${state.status === key}"><b>${n}</b><span>${label}</span></button>`; }).join('');
    return `${pageHead('Monitoring Peserta', 'Pantauan peserta Deal dari data yang sudah ada di sistem: program, PIC, invoice, dan sertifikat. Sertifikat diinput di <b>Manage Peserta › Lihat Peserta › tab Sertifikat</b>.', 'MON-FR-01 – MON-FR-06',
      `<button class="btn btn-accent" data-act="mon-xls" ${all.length ? '' : 'disabled'}>Export Excel</button>`)}
      <div class="mon-kpis" role="group" aria-label="Perlu tindak lanjut">${kpi}</div>
      <div class="card">
        ${toolbar('Cari peserta, instansi, kontak, program, quotation, PIC, LSP, atau nomor sertifikat…', false,
          selectFilter('fjenis', 'Filter tipe program', state.jenis, [['all', 'Semua tipe'], ...TIPE.map(t => [t, `${t} (${cnt(t)})`])])
          + selectFilter('fstatus', 'Filter tindak lanjut', state.status, [['all', 'Semua status'], ...Object.entries(MON_TL).map(([k, [l]]) => [k, l])]) + ymFilter(mpProgs().map(pj => pj.tglMulai)))}
        <p class="hint mon-note">Satu daftar untuk semua peserta Deal; jenis kegiatan dipilih lewat filter Tipe.${QMON(1, 'Satu daftar dengan filter Tipe menggantikan sheet-sheet Excel (usulan). Masih perlu: rekap per program seperti sheet IHT (atau cukup Manage Peserta)? Pendampingan dikelola di sistem ini?')}</p>
        <div id="list">${listMon()}</div></div>`;
  }
  function listMon() {
    return listBlock(monRows(), [
      NO_COL,
      { label: 'Peserta', cls: 'title', render: x => `<b>${esc(x.p.nama)}</b>${x.p.data && x.p.data.jabatan ? `<br><span class="muted">${esc(x.p.data.jabatan)}</span>` : ''}<br><span class="muted">${esc(pesertaCo(x.p))}</span><br><span class="muted">${esc(x.p.telp || '—')} · ${esc(x.p.email || '—')}</span>` },
      { label: 'Program', render: x => `<span class="code">${esc(x.pj.nomor)}</span> ${jenisTag(x.pj.jenis)}<br>${esc(mpJudul(x.pj))}<br><span class="muted">${fmtRange(x.pj.tglMulai, x.pj.tglSelesai)} · ${esc(monTipe(x.pj))} · ${esc(x.pj.lokasi || '—')}</span><br><span class="muted">PIC ${esc((salesOf(x.k) || {}).nama || '—')} · <span class="code">${esc(x.k.nomor)}</span></span>` },
      { label: 'Invoice' + QMON(2, 'Status bayar belum ada di sistem (modul Invoice belum mencatat pelunasan). Monitoring menampilkan nomor invoice saja; status lunas ditambahkan setelah pelunasan tersedia di Invoice?'), cls: 'nowrap',
        render: x => { const inv = monInv(x.k, x.p); return inv ? `<span class="code">${esc(inv.nomor)}</span>` : '<span class="badge off">Uninvoiced</span>'; } },
      { label: 'Sertifikat Pelatihan', render: x => latihTxt(x.s) },
      { label: 'Sertifikasi LSP / BNSP', render: x => bnspTxt(x.pj, x.s) },
      { label: 'Berlaku sampai', cls: 'nowrap', render: x => berlakuTxt(x.s) },
      { label: 'Catatan', render: x => x.s.catatan ? esc(x.s.catatan) : '<span class="muted">—</span>' },
      { label: 'Action', cls: 'act', render: x => gearMenu({ id: x.p.id }, [['sert-open', 'Kelola Sertifikat'], ['mon-note', 'Catatan'], ['qtn-view-ps', 'Lihat quotation']])
        .replace(`data-act="qtn-view-ps" data-id="${x.p.id}"`, `data-act="qtn-view-ps" data-id="${x.k.id}"`) }
    ]);
  }
  // Buka halaman peserta program di tab Sertifikat (dari Monitoring).
  function sertOpen(p) {
    const x = monAll().find(r => r.p.id === p.id); if (!x) return;
    state.view = 'mpst'; state.mpPj = x.pj.id; state.mpvTab = 'sertifikat'; state.q = ''; state.page = 1; render(); window.scrollTo(0, 0);
  }
  function monNote(p) {
    const s = sertOf(p);
    openModal({ title: `Catatan monitoring: ${p.nama}`, body: field('Catatan', `<textarea class="input" name="catatan" rows="3" maxlength="500">${esc(s.catatan)}</textarea>`),
      saveLabel: 'Simpan', onSave: f => { const v = f.catatan.value.trim(); if (v !== s.catatan) logAct('Manage Peserta', 'Monitoring Peserta', 'Edit', p.nama, p.id, `Catatan monitoring ${p.nama}`); s.catatan = v; toast('Catatan disimpan'); } });
  }
  // Export Excel: kolom mengikuti file Excel Labmania, hanya dari data yang ada di sistem.
  function monXls() {
    const rows = monRows(), cell = v => `<td>${esc(v == null ? '' : String(v))}</td>`, txt = v => `<td style="mso-number-format:'\\@'">${esc(v || '')}</td>`;
    const H = ['No', 'Nama', 'Instansi', 'Jabatan', 'NIK KTP', 'Email', 'WA Aktif', 'Judul Program', 'No. Program', 'Tanggal Pelaksanaan', 'Tipe', 'Lokasi', 'PIC', 'Nama PIC Customer', 'No. Quotation', 'No Invoice',
      'No. Sertifikat Pelatihan', 'Status Kirim Sertifikat', 'Tanggal Kirim', 'Ukom/Porto', 'LSP', 'Skema', 'Hasil', 'No. Sertifikat BNSP', 'Penerimaan BNSP', 'Pengiriman BNSP', 'ID Card', 'Berlaku Sampai', 'Catatan'];
    const html = `<html><head><meta charset="utf-8"></head><body><table border="1"><tr><th colspan="${H.length}">Monitoring Peserta ${esc(tglPanjang(todayIso))}</th></tr><tr>${H.map(h => `<th>${h}</th>`).join('')}</tr>
      ${rows.map((x, i) => { const b = monSert(x.pj), inv = monInv(x.k, x.p), d = x.p.data || {}, s = x.s;
        return `<tr>${cell(i + 1)}${cell(x.p.nama)}${cell(pesertaCo(x.p))}${cell(d.jabatan)}${txt(d.nik)}${cell(x.p.email)}${txt(x.p.telp)}${cell(mpJudul(x.pj))}${cell(x.pj.nomor)}${cell(rangePanjang(x.pj.tglMulai, x.pj.tglSelesai))}${cell(monTipe(x.pj))}
          ${cell(x.pj.lokasi)}${cell((salesOf(x.k) || {}).nama)}${cell(x.k.tujuan === 'company' ? x.k.up : '')}${cell(x.k.nomor)}${cell(inv ? inv.nomor : 'Uninvoiced')}${cell(s.noLatih)}${cell(SERT_KIRIM[s.kirim])}${cell(s.tglKirim)}
          ${cell(b ? SERT_METODE[s.metode] : '')}${cell(b ? s.lsp : '')}${cell(b ? s.skema : '')}${cell(b ? SERT_HASIL[s.hasil] : '')}${cell(b ? s.noBnsp : '')}${cell(b ? s.bnspTerima : '')}${cell(b ? s.bnspKirim : '')}${cell(b ? SERT_IDCARD[s.idcard] : '')}${cell(b ? s.berlaku : '')}${cell(s.catatan)}</tr>`; }).join('')}</table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = `Monitoring_Peserta_${todayIso}.xls`;
    document.body.appendChild(a); a.click(); a.remove();
    logAct('Manage Peserta', 'Monitoring Peserta', 'Export', 'Monitoring Peserta', null, `Export Excel monitoring peserta (${rows.length} peserta)`);
    toast(`Excel monitoring peserta diunduh (${rows.length} peserta)`);
  }
  // Data contoh sertifikat (mockup); diisi saat pertama dibaca, setelah semua data contoh modul lain termuat.
  function sertSeed() {
    monList().forEach((x, i) => {
      const s = x.s;
      if (i % 3 === 0) { const t = x.pj.tglSelesai || todayIso; Object.assign(s, { noLatih: sertNo(t), tglTerbit: t }); }
      if (monSert(x.pj)) Object.assign(s, { metode: i % 2 ? 'porto' : 'ukom', lsp: s.lsp || 'LSP Kimia Industri', skema: 'Petugas Pengambil Contoh', hasil: i % 4 === 0 ? 'k' : 'belum', berlaku: i % 5 === 0 ? addDays(todayIso, 60) : '' });
    });
  }
