/* Labmania mockup TO BE — Sales Order (modul Quotation): 1 quotation Deal = 1 Sales Order. Daftar per program,
   halaman Sales Order per program, detail SO (quotation + surat konfirmasi dari link), PDF SO, Siap dibuatkan invoice.
   Sumber: input/Master - Buat Program/2026-11-07-sales-order/. Spesifikasi: docs-to-be/fr-nfr-sales-order.md.
   Dimuat setelah mod-invoice.js (memakai invOfQtn, ivcStatus) dan sebelum mod-activity-log.js (data contoh ikut tercatat). */
'use strict';
  // Jawaban 07-11-2026 (input/Master - Buat Program/2026-11-07-jawaban-sales-order/): nomor SO sendiri, format menyusul;
  // permission menyusul; SO tidak dapat dibatalkan.
  const QSO1 = q('Q-SO-01', 'Terjawab 12-11-2026: format resmi tidak ada; nomor dibuat mirip Penawaran dan Invoice dengan kode berbeda: LMI/SO/MM/yy-zzz. Kode SO dan urutan di-reset tiap bulan = usulan.');
  const QSO2 = q('Q-SO-02', 'Usulan: Sales Order dibuat otomatis saat quotation ditandai Deal; tanggal SO = tanggal Deal.');
  const QSO3 = q('Q-SO-03', 'Permission Sales Order (siapa yang boleh melihat, mencetak, dan menandai siap dibuatkan invoice) menyusul dari Labmania.');
  const QSO4 = q('Q-SO-04', 'Terjawab 12-11-2026: Labmania tidak punya format resmi dan kode dokumen SO. Tata letak ini (mengikuti Penawaran, ditambah daftar peserta Deal) yang dipakai.');
  db.so = [];
  const SO_ST = { open: 'Belum siap invoice', siap: 'Siap invoice', invoiced: 'Invoiced' };
  const SO_BADGE = { open: 'st-draft', siap: 'st-ivc-uninvoiced', invoiced: 'st-ivc-invoiced' };
  // Nomor SO LMI/SO/MM/yy-zzz, pola sama dengan Penawaran dan Invoice (jawaban 12-11-2026, Q-SO-01).
  const soNo = tgl => lmiNo('SO', tgl);
  // Nomor tampil: nomor dasar + "-rev{n}" setelah program di-reschedule dengan tanggal baru (SO-FR-16, sama dengan Penawaran).
  const soNomor = s => `${s.nomor}${s.rev ? `-rev${s.rev}` : ''}`;
  const QSO6 = q('Q-SO-06', 'Terjawab 12-11-2026: penanda -rev{n} di belakang nomor, sama dengan Penawaran. Usulan: naik hanya bila tanggal program berubah, bukan bila hanya trainer/asisten diganti.');
  const soOfQtn = k => db.so.find(s => s.qtn === k.id);
  const qtnOfSo = s => db.qtn.find(k => k.id === s.qtn);
  const soById = id => db.so.find(s => s.id === id);
  // Status tampil: Invoiced diturunkan dari invoice quotation-nya (semua peserta Deal sudah ditagih).
  const soStatus = s => { const k = qtnOfSo(s); return invOfQtn(k).length && ivcStatus(k) === 'invoiced' ? 'invoiced' : s.status; };
  const soBadge = s => `<span class="badge ${SO_BADGE[soStatus(s)]}">${SO_ST[soStatus(s)]}</span>`;
  const soSiapOk = k => { const s = soOfQtn(k); return !!s && s.status === 'siap'; };
  const soOfPj = pj => db.so.filter(s => s.pj === pj.id);
  const soNilai = s => { const k = qtnOfSo(s); return qtnNilai(k, dealRows(k)); };

  // Dibuat saat aksi Deal di Quotation (QTN-FR-11). Satu quotation hanya punya satu Sales Order.
  function soCreate(k, tgl = todayIso) {
    const ada = soOfQtn(k); if (ada) return ada;
    const s = { id: uid(), nomor: soNo(tgl), tanggal: tgl, qtn: k.id, pj: k.pj, status: 'open', siapBy: '', siapAt: '' };
    db.so.push(s);
    sktEnsure(k.pj, tgl); // Surat Kerja Trainer program ini (satu per program), dibuat bersama SO pertama
    return s;
  }

  // ---------- Surat Kerja Trainer (to be: input/Master - Buat Program/2026-11-07-surat-kerja-trainer/) ----------
  // Satu surat per Penjualan Program (1 event = 1 trainer, trainer wajib di Penjualan Program). Dibuat otomatis saat program
  // mendapat Sales Order pertama (usulan, Q-SKT-02). Nomor sementara SKT-nnn sampai format dari Labmania ada (Q-SKT-01).
  const QSKT1 = q('Q-SKT-01', 'Terjawab: format nomor menyusul. Mockup memakai nomor sementara SKT-nnn.');
  const QSKT2 = q('Q-SKT-02', 'Terjawab 12-11-2026: surat tugas dibuat ketika trainer dan asisten trainer sudah ditetapkan; bila berganti, surat dibuat ulang dengan nomor baru. Usulan: dibuat saat SO pertama karena trainer dan asisten wajib di Penjualan Program.');
  const QSKT3 = q('Q-SKT-03', 'Terjawab: tanpa honor dan rundown; ditandatangani atas nama PT LabMania Indonesia Jaya. Format cetak: Surat Tugas FR/LMI-TR-05/01 (10-11-2026).');
  const QSKT4 = q('Q-SKT-04', 'Terjawab 10-11-2026: Hari/Tanggal = tanggal program, tanggal tanda tangan = tanggal print. NIK trainer dan nama Operational Manager sementara kosong, diisi tangan.');
  db.skt = [];
  let sktSeq = 0;
  // Satu surat Berlaku per program. Reschedule yang mengubah tanggal/trainer/asisten: surat lama Diganti, surat baru nomor baru
  // (jawaban 12-11-2026). Surat menyimpan salinan jadwal, trainer, dan asisten saat terbit agar surat Diganti tetap tercetak apa adanya.
  const sktOfPj = id => db.skt.find(x => x.pj === id && x.status === 'berlaku');
  const sktSnap = pj => ({ tglMulai: pj.tglMulai, tglSelesai: pj.tglSelesai, waktu: pj.waktu || '', lokasi: pj.lokasi || '', durasi: pj.durasi, trainer: pj.trainer || '', asisten: pj.asisten || '' });
  function sktNew(pj, tgl, ref) {
    const r = Object.assign({ id: uid(), nomor: `SKT-${String(++sktSeq).padStart(3, '0')}`, tanggal: tgl, pj: pj.id, status: 'berlaku', ref: ref || null }, sktSnap(pj));
    db.skt.push(r);
    return r;
  }
  function sktEnsure(pjId, tgl = todayIso) {
    return sktOfPj(pjId) || sktNew(db.quot.find(x => x.id === pjId), tgl);
  }
  function sktRenew(pj, ref) {
    const lama = sktOfPj(pj.id); if (!lama) return null;
    const baru = sktNew(pj, (ref.waktuUbah || isoNow()).slice(0, 10), ref);
    // aktif false = baris redup di daftar
    Object.assign(lama, { status: 'diganti', gantiOleh: baru.id, aktif: false }); baru.gantikan = lama.id;
    logAct('Quotation', 'Surat Kerja Trainer', 'Tambah', baru.nomor, baru.id, `Surat Tugas ${baru.nomor} menggantikan ${lama.nomor} karena reschedule ${pj.nomor}: ${ref.alasan}`);
    return baru;
  }
  // Data contoh: trainer wajib di Penjualan Program (to be); program contoh tanpa trainer diisi bergantian dari Master Trainer aktif.
  { const aktif = db.trainer.filter(t => t.aktif); db.quot.forEach((pj, i) => { if (!pj.trainer && aktif.length) pj.trainer = aktif[i % aktif.length].id; }); }
  // Data contoh: asisten trainer wajib (to be 12-11-2026); diisi bergantian dari Master User aktif.
  { const us = db.user.filter(u => u.aktif); db.quot.forEach((pj, i) => { if (!pj.asisten && us.length) pj.asisten = us[(i + 1) % us.length].id; }); }
  // Data contoh: semua quotation Deal punya SO. Yang sudah ada invoice = Siap invoice; sisanya bergantian agar kedua status terlihat.
  db.qtn.filter(k => k.status === 'deal').forEach((k, i) => {
    const s = soCreate(k, k.tanggal);
    if (invOfQtn(k).length || i % 2) Object.assign(s, { status: 'siap', siapBy: 'Admin Sistem', siapAt: `${k.tanggal} 15:00` });
  });

  // ---------- Daftar Sales Order per program (SO-FR-02) ----------
  const soProgs = () => db.quot.filter(pj => soOfPj(pj).length);
  const soCount = l => Object.keys(SO_ST).map(st => [st, l.filter(s => soStatus(s) === st).length]).filter(x => x[1]);
  const progJudul = pj => `${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${esc(varianTxt(pj))} · ${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}</span>`;
  state.soTab = 'so';
  const viewSo = () => {
    const skt = state.soTab === 'skt';
    const rfTab = state.soTab === 'refund';
    const tabs = [['so', 'Sales Order', soProgs().length], ['skt', 'Surat Kerja Trainer', db.skt.length], ['refund', 'Refund', db.refund.filter(x => x.status === 'menunggu').length]].map(([t, l, n]) =>
      `<button type="button" class="tab ${state.soTab === t ? 'active' : ''}" data-act="so-tab" data-tab="${t}" aria-pressed="${state.soTab === t}">${l}<span class="nav-count">${n}</span></button>`).join('');
    if (rfTab) return `${pageHead('Sales Order', 'Pengembalian atau pengalihan dana peserta yang <b>sudah ditagih</b> tetapi tidak ikut jadwal baru setelah reschedule. Refund terbentuk otomatis saat Sales memproses peserta itu di quotation; Finance menyelesaikannya. Invoice tidak diedit.', 'SO-FR-17 – SO-FR-19')}
      <div class="card"><div class="tabs">${tabs}</div>${toolbar('Cari nomor refund, peserta, SO, invoice…', false,
        selectFilter('fstatus', 'Filter status refund', state.status, [['all', 'Semua status'], ['menunggu', 'Menunggu Finance'], ['selesai', 'Selesai']]))}
        <div id="list">${listRefund()}</div></div>`;
    return `${pageHead('Sales Order', skt
      ? 'Surat Tugas untuk trainer dan asisten trainer program. Terbit saat program mendapat Sales Order pertama; bila reschedule mengganti tanggal, trainer, atau asisten, surat lama berstatus Diganti dan surat baru terbit dengan nomor baru.'
      : 'Setiap quotation yang <b>Deal</b> otomatis menjadi satu Sales Order, yaitu surat konfirmasi pesanan dari LabMania ke customer. Sales Order <b>tidak dapat dibatalkan</b>. Buka <b>Lihat SO</b> untuk melihat, mencetak, dan menandai siap dibuatkan invoice. Invoice dibuat dari Sales Order yang sudah siap.', skt ? 'SO-FR-11 – SO-FR-14' : 'SO-FR-01 – SO-FR-03')}
    <div class="card"><div class="tabs">${tabs}</div>${toolbar(skt ? 'Cari nomor surat, program, judul, trainer…' : 'Cari nomor program, judul, nomor SO, quotation, customer…', false,
      selectFilter('fjenis', 'Filter jenis program', state.jenis, [['all', 'Public & B2B'], ['public', 'Program Public'], ['b2b', 'Program B2B']])
      + ymFilter((skt ? db.skt.map(r => db.quot.find(x => x.id === r.pj)) : soProgs()).filter(Boolean).map(pj => pj.tglMulai)))}
      <div id="list">${skt ? listSkt() : listSo()}</div></div>`;
  };
  const listSoTab = () => (state.soTab === 'skt' ? listSkt() : state.soTab === 'refund' ? listRefund() : listSo());

  // ---------- Tab Surat Kerja Trainer (SO-FR-11 – SO-FR-12) ----------
  const pesertaDealPj = pj => soOfPj(pj).reduce((t, s) => t + dealRows(qtnOfSo(s)).length, 0);
  function listSkt() {
    const rows = db.skt.map(r => ({ r, pj: db.quot.find(x => x.id === r.pj) })).filter(({ r, pj }) => pj && (state.jenis === 'all' || pj.jenis === state.jenis) && ymOk(pj.tglMulai)
      && matches([pj.nomor, paketOf(mpOf(pj)).judul, pj.lokasi, r.nomor, (trainerById(r.trainer) || {}).nama, asistenNama(r.asisten)])).map(x => x.r)
      .sort((a, b) => (a.status === b.status ? b.id - a.id : a.status === 'berlaku' ? -1 : 1));
    if (!db.skt.length) return '<div class="empty-state"><h2>Belum ada Surat Kerja Trainer</h2><p>Surat terbentuk saat Penjualan Program mendapat Sales Order pertama.</p></div>';
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor Surat', cls: 'nowrap', render: r => `<button type="button" class="btn btn-ghost sm" data-act="skt-view" data-id="${r.id}">${esc(r.nomor)}</button><br><span class="muted">${tglPanjang(r.tanggal)}</span>` },
      { label: 'Status', cls: 'nowrap', render: r => r.status === 'berlaku' ? '<span class="badge st-terkirim">Berlaku</span>' : `<span class="badge st-draft">Diganti</span><br><span class="muted">oleh ${esc((sktById(r.gantiOleh) || {}).nomor || '—')}</span>` },
      { label: 'Penjualan Program', cls: 'title', render: r => { const pj = db.quot.find(x => x.id === r.pj); return `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}<br>${progJudul(pj)}`; } },
      { label: 'Trainer & Asisten', render: r => { const t = trainerById(r.trainer); return `${t ? `<b>${esc(t.nama)}</b>` : '<span class="muted">Trainer belum dipilih</span>'}<br><span class="muted">Asisten: ${esc(asistenNama(r.asisten))}</span>`; } },
      { label: 'Peserta deal', cls: 'money', render: r => pesertaDealPj(db.quot.find(x => x.id === r.pj)) },
      { label: 'Action', cls: 'act', render: r => gearMenu(r, [['skt-view', 'View'], ['skt-print', 'Print Out Surat Kerja']]) }
    ]);
  }
  function listSo() {
    const rows = soProgs().filter(pj => (state.jenis === 'all' || pj.jenis === state.jenis) && ymOk(pj.tglMulai)
      && matches([pj.nomor, paketOf(mpOf(pj)).judul, pj.lokasi, ...soOfPj(pj).flatMap(s => { const k = qtnOfSo(s); return [soNomor(s), k.nomor, kepadaOf(k)]; })]));
    if (!soProgs().length) return '<div class="empty-state"><h2>Belum ada Sales Order</h2><p>Sales Order terbentuk saat quotation ditandai Deal di Daftar Quotation.</p></div>';
    return listBlock(rows, [
      NO_COL,
      { label: 'Penjualan Program', cls: 'title', render: pj => `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}<br>${progJudul(pj)}` },
      { label: 'Sales Order', render: pj => { const l = soOfPj(pj); return `<b>${l.length}</b> SO<br><span class="muted">${soCount(l).map(([st, n]) => `${n} ${SO_ST[st].toLowerCase()}`).join(', ')}</span>`; } },
      { label: 'Peserta deal', cls: 'money', render: pj => soOfPj(pj).reduce((t, s) => t + dealRows(qtnOfSo(s)).length, 0) },
      { label: 'Nilai SO', cls: 'money', render: pj => rp(soOfPj(pj).reduce((t, s) => t + soNilai(s).total, 0)) },
      { label: 'Action', cls: 'act', render: pj => `<button type="button" class="btn btn-outline sm" data-act="so-prog" data-id="${pj.id}">Lihat SO</button>` }
    ]);
  }

  // ---------- Halaman Sales Order satu program (SO-FR-04) ----------
  const soAksi = s => [['so-view', 'View'], ['so-print', 'Print Out SO'], ...(soStatus(s) === 'open' ? [['so-siap', 'Siap dibuatkan invoice']] : [])];
  function viewSoProg() {
    const pj = db.quot.find(x => x.id === state.soPj);
    if (!pj) { state.view = 'so'; return viewSo(); }
    const info = `${jenisTag(pj.jenis)} <b>${esc(paketOf(mpOf(pj)).judul)}</b> · ${esc(varianTxt(pj))} · ${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}${pj.jenis === 'b2b' ? ` · ${esc((db.company.find(x => x.id === pj.company) || {}).nama || '—')}` : ''}`;
    return `${pageHead(`Sales Order: ${esc(pj.nomor)}`, info, 'SO-FR-04 – SO-FR-07',
      '<button type="button" class="btn btn-ghost" data-act="so-back">← Semua program</button>')}
      <div class="card"><div id="list">${listSoProg()}</div></div>`;
  }
  function listSoProg() {
    const pj = db.quot.find(x => x.id === state.soPj);
    const rows = soOfPj(pj).sort((a, b) => a.id - b.id);
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor SO', cls: 'nowrap', render: s => `<button type="button" class="btn btn-ghost sm" data-act="so-view" data-id="${s.id}">${esc(soNomor(s))}</button><br><span class="muted">${tglPanjang(s.tanggal)}</span>` },
      { label: 'No. Quotation', cls: 'nowrap', render: s => `<span class="code">${esc(qtnOfSo(s).nomor)}</span>` },
      { label: 'Kepada', render: s => { const k = qtnOfSo(s); return `${tujuanTag(k.tujuan)} ${esc(kepadaOf(k))}${k.tujuan === 'company' && k.up ? `<br><span class="muted">u.p. ${esc(k.up)}</span>` : ''}`; } },
      { label: 'Peserta deal', render: s => { const d = dealRows(qtnOfSo(s)), nm = d.slice(0, 2).map(r => (pesertaById(r.id) || {}).nama).filter(Boolean);
        return `<b>${d.length}</b> peserta<br><span class="muted">${esc(nm.join(', '))}${d.length > 2 ? `, +${d.length - 2} lainnya` : ''}</span>`; } },
      { label: 'Nilai', cls: 'money', render: s => rp(soNilai(s).total) },
      { label: 'Status', cls: 'nowrap', render: s => `${soBadge(s)}${s.status === 'siap' && s.siapAt ? `<br><span class="muted">${esc(s.siapBy)} · ${fmtWaktu(s.siapAt)}</span>` : ''}` },
      { label: 'Action', cls: 'act', render: s => gearMenu(s, soAksi(s)) }
    ]);
  }

  // ---------- View Sales Order: detail quotation + surat konfirmasi dari link (SO-FR-05) ----------
  function viewSoDetail() {
    const s = soById(state.soView);
    if (!s) { state.view = 'so'; return viewSo(); }
    const k = qtnOfSo(s), pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), sl = salesOf(k) || {}, v = soNilai(s), b2b = isB2bQtn(k), st = soStatus(s);
    const sec = (n, t, hint, b) => `<section class="card pad ivc-sec"><p class="ivc-sec-h"><span class="ivc-no">${n}</span>${t}${hint ? ` <span class="muted">${hint}</span>` : ''}</p>${b}</section>`;
    const kv = rows => `<div class="qv-kv">${rows.map(([l, x]) => `<div><span>${l}</span><b>${x}</b></div>`).join('')}</div>`;
    const LANGKAH = { open: 'Periksa isi Sales Order dan surat konfirmasi peserta. Bila sudah benar, tandai <b>Siap dibuatkan invoice</b>.',
      siap: 'Sales Order sudah siap. Finance membuat invoice di menu Invoicing, tab Uninvoiced.', invoiced: 'Semua peserta Deal sudah ditagih.' };
    const panel = `<section class="card pad qv-panel">
      <div class="qv-panel-top">${soBadge(s)}</div>
      <p class="qv-next"><span class="muted">Langkah berikutnya:</span> ${LANGKAH[st]}${st === 'open' ? QSO3 : ''}</p>
      <div class="qv-panel-row"><div class="qv-btns">
        ${st === 'open' ? `<button type="button" class="btn btn-primary" data-act="so-siap" data-id="${s.id}">Siap dibuatkan invoice</button>` : ''}
        <button type="button" class="btn ${st === 'open' ? 'btn-outline' : 'btn-primary'}" data-act="so-print" data-id="${s.id}">Print Out SO</button>
        <button type="button" class="btn btn-outline" data-act="qtn-view" data-id="${k.id}">Buka quotation</button></div></div></section>`;
    const harga = `<div class="totals">${v.allin
      ? `<div class="tr"><span>Harga all-in <span class="muted">${v.n} peserta deal</span></span><span>${rp(v.sub)}</span></div><div class="tr"><span>Diskon <span class="muted">Rupiah total</span></span><span>− ${rp(v.diskTot)}</span></div>`
      : `<div class="tr"><span>Subtotal <span class="muted">${v.n} peserta deal × ${rp(v.harga)}</span></span><span>${rp(v.sub)}</span></div><div class="tr"><span>Diskon <span class="muted">${v.n} × ${rp(v.disk)}</span></span><span>− ${rp(v.diskTot)}</span></div>`}
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div><div class="tr"><span>PPN ${k.ppn ? `${PPN_RATE}%` : '<span class="muted">(tidak dikenakan)</span>'}</span><span>${rp(v.ppn)}</span></div><div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div>
      <div class="tr grand"><span><b>Total Sales Order</b></span><span><b>${rp(v.total)}</b></span></div></div>`;
    const ps = dealRows(k).map(r => pesertaById(r.id)).filter(Boolean);
    const psTbl = ps.length ? `<div class="table-wrap"><table class="table dp-table"><thead><tr><th class="num">No</th><th>Peserta</th><th>Instansi</th><th>Kontak</th><th>Masuk lewat</th><th class="act">Confirmation Letter</th></tr></thead><tbody>
      ${ps.map((p, n) => `<tr><td class="num">${n + 1}</td><td data-l="Peserta"><b>${esc(p.nama)}</b>${p.data && p.data.jabatan ? `<br><span class="muted">${esc(p.data.jabatan)}</span>` : ''}</td>
        <td data-l="Instansi">${esc(pesertaCo(p))}</td><td data-l="Kontak">${esc(p.telp || '—')}<br><span class="muted">${esc(p.email || '')}</span></td>
        <td data-l="Masuk lewat">${srcTag(p)} <span class="muted">${fmtStamp(p.tgl)}</span></td>
        <td class="act">${gearMenu({ id: p.id }, [['qps-prev', 'Lihat surat konfirmasi'], ['qps-pdf', 'Unduh surat konfirmasi']])}</td></tr>`).join('')}
      </tbody></table></div>` : '<p class="dash-empty">Tidak ada peserta Deal.</p>';
    const inv = invOfQtn(k);
    return `${pageHead(`Sales Order ${esc(soNomor(s))}`, `${esc(kepadaOf(k))} · ${esc(pk.judul)} · ${esc(varianTxt(pj))} · program <span class="code">${esc(pj.nomor)}</span>`, 'SO-FR-05',
      `<button type="button" class="btn btn-ghost" data-act="so-prog" data-id="${pj.id}">← Sales Order ${esc(pj.nomor)}</button>`)}
      ${panel}
      <div class="qtn-grid">${sec(1, 'Data Sales Order', '', kv([['Nomor SO', `<span class="code">${esc(soNomor(s))}</span>${QSO1}`], ['Tanggal', `${tglPanjang(s.tanggal)}${QSO2}`], ['Revisi', `${s.rev ? `rev${s.rev} · karena reschedule program ${tglPanjang(s.revAt.slice(0, 10))} <button type="button" class="btn btn-ghost sm" data-act="qt-open" data-id="${pj.id}">Lihat riwayat reschedule</button>` : 'Belum pernah direvisi'}${QSO6}`], ['Status', soBadge(s)], ['Dari quotation', `<span class="code">${esc(k.nomor)}</span> · Deal`], ['Sales', esc(sl.nama || '—')], ['Penjualan Program', `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}`]]))}
        ${sec(2, 'Ditujukan Kepada', '', kv([['Tujuan', tujuanTag(k.tujuan)], ['Kepada', esc(kepadaOf(k))], ['u.p.', esc(k.tujuan === 'company' ? k.up || '—' : kepadaOf(k))], ['Alamat', esc(k.alamat || '—')], ...(k.tujuan === 'company' ? [['Perlakuan PPN', PERLAKUAN[ivcPerlakuan(k)]]] : [])]))}</div>
      ${sec(3, 'Program', '', kv([['Paket', `${esc(pk.nomor)} · ${esc(pk.judul)}`], ['Varian', esc(varianTxt(pj))], ['Sertifikat', esc(findRec('jenisSertifikasi', pk.sertifikasi).nama)], ['Jadwal', `${rangePanjang(pj.tglMulai, pj.tglSelesai)}${pj.waktu ? ` · ${esc(pj.waktu)}` : ''}`], ['Lokasi', esc(pj.lokasi || '—')], ...(isB2bQtn(k) ? [['Skema harga', isAllin(k) ? 'All-in 1 company' : 'Per peserta']] : [])]))}
      ${sec(4, 'Harga', 'dari peserta Deal', harga)}
      ${sec(5, 'Dokumen Deal', 'dokumen dari customer yang diunggah saat quotation ditandai Deal', dokList(k))}
      ${sec(6, 'Peserta Deal & Confirmation Letter', 'surat bertanda tangan yang diunggah calon peserta lewat link Konfirmasi Kehadiran (Public dan B2B)',
        `<p class="hint top"><button type="button" class="btn btn-outline sm" data-act="qtn-cl" data-id="${k.id}" ${ps.length ? '' : 'disabled'}>Confirmation Letter (semua peserta Deal)</button></p>${psTbl}${keluarTxt(k)}`)}
      ${sec(7, 'Invoice', st === 'open' ? 'belum dapat dibuat: Sales Order belum ditandai siap' : '', inv.length
        ? `<p>${inv.map(i => `<button type="button" class="btn btn-ghost sm" data-act="ivc-view" data-id="${i.id}">${esc(i.nomor)}</button>`).join(' ')}</p>`
        : `<p class="hint">${st === 'siap' ? 'Siap dibuatkan invoice. Invoice dibuat Finance di menu <b>Invoicing</b>, tab Uninvoiced.' : 'Tandai <b>Siap dibuatkan invoice</b> agar Sales Order ini muncul di Invoicing.'}</p>`)}`;
  }
  function soView(s) { state.soView = s.id; state.view = 'soDetail'; render(); window.scrollTo(0, 0); }
  function soProgView(pj) { state.soPj = pj.id; state.view = 'soProg'; state.q = ''; render(); window.scrollTo(0, 0); }

  // ---------- PDF Sales Order (SO-FR-06; tata letak usulan, Q-SO-04) ----------
  // Tanggal print out SO dan Surat Kerja Trainer = tanggal dicetak (input/Master - Buat Program/2026-11-09-tanggal-print-out/).
  function pageSo(s) {
    const k = qtnOfSo(s), pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), v = soNilai(s), sl = salesOf(k) || {};
    const ps = dealRows(k).map(r => pesertaById(r.id)).filter(Boolean);
    return pdfPage(`
      <h3 class="pdf-title">SALES ORDER</h3>
      <div class="pdf-2col">
        <div class="pdf-box"><div class="pdf-lbl">Kepada</div><b>${esc(kepadaOf(k))}</b><br>${esc(k.alamat || '—')}${k.tujuan === 'company' && k.up ? `<br>u.p. ${esc(k.up)}` : ''}</div>
        <div class="pdf-box"><table class="pdf-kv"><tr><td>No. Sales Order</td><td>: ${esc(soNomor(s))}</td></tr><tr><td>Tanggal</td><td>: ${tglPanjang(todayIso)}</td></tr>
          <tr><td>No. Penawaran</td><td>: ${esc(k.nomor)}</td></tr><tr><td>No. Program</td><td>: ${esc(pj.nomor)}</td></tr></table></div>
      </div>
      <table class="pdf-tbl"><thead><tr><th>No Catalog</th><th>Item</th><th>Deskripsi</th><th class="r">Kts.</th><th class="r">Harga</th><th class="r">Diskon</th><th class="r">Total Harga</th></tr></thead>
        <tbody><tr><td>${esc(pk.nomor)}</td><td>${esc(pk.judul)}<br><span class="pdf-mut">${esc(varianTxt(pj))}</span></td>
          <td>${pj.durasi} hari<br>${rangePanjang(pj.tglMulai, pj.tglSelesai)}<br>${esc(pj.waktu || '—')}<br>${esc(pj.lokasi || '—')}</td>
          <td class="r">${v.n} peserta</td>${v.allin ? `<td class="r">${rp(v.sub)}<br><span class="pdf-mut">all-in</span></td><td class="r">${v.diskTot ? rp(v.diskTot) : '0'}</td>` : `<td class="r">${rp(v.harga)}</td><td class="r">${v.disk ? rp(v.disk) : '0'}</td>`}<td class="r">${rp(v.sub)}</td></tr></tbody></table>
      <div class="pdf-2col">
        <div><div class="pdf-lbl">Peserta Deal (${ps.length})</div>
          <table class="pdf-tbl pdf-ps"><thead><tr><th>No</th><th>Nama</th><th>Instansi</th></tr></thead><tbody>
            ${ps.map((p, n) => `<tr><td>${n + 1}</td><td>${esc(p.nama)}</td><td>${esc(pesertaCo(p))}</td></tr>`).join('')}</tbody></table></div>
        <table class="pdf-sum"><tr><td>Sub Total</td><td>${rp(v.sub)}</td></tr><tr><td>Diskon</td><td>${v.diskTot ? `− ${rp(v.diskTot)}` : '0'}</td></tr>
          <tr><td>PPN (${PPN_RATE}%)</td><td>${rp(v.ppn)}</td></tr><tr><td>Biaya Lain-lain</td><td>${rp(v.lain)}</td></tr><tr class="tot"><td>Total</td><td>${rp(v.total)}</td></tr></table>
      </div>
      <div class="pdf-2col pdf-sign">
        <div>${tglPanjang(todayIso)}<br>${esc(roleOf(sl) || 'Sales')}<div class="pdf-ttd"></div><b>${esc(sl.nama || '—')}</b></div>
        <div>............., .................. 20.....<br>Disetujui oleh Pelanggan,<div class="pdf-ttd"></div>Nama :</div>
      </div>`, pdfFoot('', 'Halaman 1 dari 1', '—'));
  }
  function soPrint(s) {
    openModal({
      title: `Print Out Sales Order ${esc(soNomor(s))}`, wide: 'x',
      body: pdfWrap(pageSo(s), `PDF Sales Order ${esc(soNomor(s))} dari quotation ${esc(qtnOfSo(s).nomor)}: 1 halaman. Tata letak usulan${QSO4}`),
      onMount: pdfMount, cancelLabel: 'Tutup', saveLabel: 'Cetak / Simpan PDF',
      onSave: () => {
        logAct('Quotation', 'Sales Order', 'Cetak', soNomor(s), s.id, `Cetak Sales Order ${soNomor(s)}`);
        window.print();
        return false;
      }
    });
  }

  // ---------- Siap dibuatkan invoice (SO-FR-07) ----------
  function soSiap(s) {
    if (soStatus(s) !== 'open') { toast(`${soNomor(s)} sudah ${SO_ST[soStatus(s)].toLowerCase()}`, 'warn'); return; }
    const k = qtnOfSo(s);
    openModal({
      title: 'Siap dibuatkan invoice',
      body: `<p style="margin:0 0 10px">Tandai <b>${esc(soNomor(s))}</b> (${esc(kepadaOf(k))}, ${dealRows(k).length} peserta deal, ${rp(soNilai(s).total)}) siap dibuatkan invoice?</p>
        <p class="hint">Setelah ditandai, Sales Order ini muncul di menu <b>Invoicing</b> tab Uninvoiced dan Finance dapat membuat invoice-nya.${QSO3}</p>`,
      saveLabel: 'Siap dibuatkan invoice',
      onSave: () => { Object.assign(s, { status: 'siap', siapBy: meName(), siapAt: isoNow() }); toast(`${soNomor(s)} siap dibuatkan invoice`); }
    });
  }

  // ---------- View & Print Out Surat Kerja Trainer (SO-FR-13 – SO-FR-14; tanpa honor & rundown, format Surat Tugas FR/LMI-TR-05/01) ----------
  const sktById = id => db.skt.find(x => x.id === id);
  function viewSktDetail() {
    const r = sktById(state.sktView);
    if (!r) { state.view = 'so'; return viewSo(); }
    const pj0 = db.quot.find(x => x.id === r.pj), pj = Object.assign({}, pj0, sktJadwal(r)), pk = paketOf(mpOf(pj)), t = trainerById(pj.trainer) || {}, a = asistenOf(pj.asisten) || {};
    const sec = (n, ti, hint, b) => `<section class="card pad ivc-sec"><p class="ivc-sec-h"><span class="ivc-no">${n}</span>${ti}${hint ? ` <span class="muted">${hint}</span>` : ''}</p>${b}</section>`;
    const kv = rows => `<div class="qv-kv">${rows.map(([l, x]) => `<div><span>${l}</span><b>${x}</b></div>`).join('')}</div>`;
    const sos = soOfPj(pj);
    return `${pageHead(`Surat Kerja Trainer ${esc(r.nomor)}`, `${esc(t.nama || 'Trainer belum dipilih')} · ${esc(pk.judul)} · program <span class="code">${esc(pj.nomor)}</span>`, 'SO-FR-13',
      '<button type="button" class="btn btn-ghost" data-act="skt-back">← Surat Kerja Trainer</button>')}
      <section class="card pad qv-panel"><div class="qv-panel-top">${r.status === 'berlaku' ? '<span class="badge st-terkirim">Berlaku</span>' : '<span class="badge st-draft">Diganti</span>'}</div><p class="qv-next"><span class="muted">${r.status === 'berlaku' ? 'Surat penugasan trainer dan asisten untuk program ini.' : `Surat ini sudah diganti oleh <b>${esc((sktById(r.gantiOleh) || {}).nomor || '—')}</b> karena reschedule. Isinya tetap jadwal dan tim saat surat ini terbit.`}</span>${QSKT3}</p>
        <div class="qv-btns"><button type="button" class="btn btn-primary" data-act="skt-print" data-id="${r.id}">Print Out Surat Kerja</button>
        <button type="button" class="btn btn-outline" data-act="so-prog" data-id="${pj.id}">Lihat Sales Order program</button></div></section>
      <div class="qtn-grid">${sec(1, 'Data Surat', '', kv([['Nomor', `<span class="code">${esc(r.nomor)}</span> <span class="muted">(sementara, format menyusul)</span>${QSKT1}`], ['Tanggal', `${tglPanjang(r.tanggal)}${QSKT2}`], ['Penjualan Program', `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}`], ...(r.gantikan ? [['Menggantikan', `<span class="code">${esc((sktById(r.gantikan) || {}).nomor || '—')}</span>${r.ref ? ` · reschedule: ${esc(r.ref.alasan)}` : ''}`]] : [])]))}
        ${sec(2, 'Trainer', 'dari Master Trainer', kv([['Nama', esc(t.nama || '—')], ['Bidang keahlian', esc((t.keahlian || []).join(', ') || '—')], ['Telepon', esc(t.telp || '—')], ['Email', esc(t.email || '—')], ['Alamat', esc(t.alamat || '—')]]))}</div>
      ${sec(3, 'Asisten trainer', 'dari Master User', kv([['Nama', esc(a.nama || '—')], ['Role', esc(a.nama ? roleOf(a) || '—' : '—')], ['Telepon', esc(a.telp || a.hp || '—')]]))}
      ${sec(4, 'Program yang ditugaskan', '', kv([['Paket', `${esc(pk.nomor)} · ${esc(pk.judul)}`], ['Varian', esc(varianTxt(pj))], ['Sertifikat', esc(findRec('jenisSertifikasi', pk.sertifikasi).nama)], ['Jadwal', `${rangePanjang(pj.tglMulai, pj.tglSelesai)}${pj.waktu ? ` · ${esc(pj.waktu)}` : ''} (${pj.durasi} hari)`], ['Lokasi', esc(pj.lokasi || '—')], ...(pj.jenis === 'b2b' ? [['Company', esc((db.company.find(x => x.id === pj.company) || {}).nama || '—')]] : []), ['Peserta deal', `${pesertaDealPj(pj)} peserta dari ${sos.length} Sales Order`]]))}`;
  }
  function sktView(r) { state.sktView = r.id; state.view = 'sktDetail'; render(); window.scrollTo(0, 0); }
  // Format Labmania "Surat Tugas" FR/LMI-TR-05/01/Ed.Rev-1.0 (input/FR LMI TR 05-01 Surat Tugas OK (1).doc;
  // input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/). Judul tercetak SURAT TUGAS; menu tetap Surat Kerja Trainer.
  // Tanggal tanda tangan = tanggal cetak; Hari/Tanggal = jadwal program. NIK trainer & nama Operational Manager belum ada di data (Q-SKT-04): titik-titik.
  const SKT_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const sktHari = iso => SKT_HARI[new Date(`${iso}T00:00:00`).getDay()];
  const SKT_KOP = `<div class="pdf-kop st-kop"><div class="pdf-logo">L<i>A</i>B<small>M A N I A</small></div><div class="pdf-kop-t"><b>PT. LABMANIA INDONESIA JAYA</b>
    <span>Ruby Commercial No. TC25 Summarecon Bekasi</span><span>Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bks, Jawa Barat 17143</span>
    <span>www.labmaniaindonesia.id ; Email: labjourneypartner@labmaniaindonesia.id ; Customer Care: 0821-2429-3839</span></div></div>`;
  function pageSkt(r) {
    const pj = Object.assign({}, db.quot.find(x => x.id === r.pj), sktJadwal(r)), pk = paketOf(mpOf(pj)), t = trainerById(pj.trainer) || {}, a = asistenOf(pj.asisten) || {};
    const dot = '..............................', keg = `${esc(varianTxt(pj))} "${esc(pk.judul)}"`;
    const hari = pj.tglMulai ? (pj.tglSelesai && pj.tglSelesai !== pj.tglMulai ? `${sktHari(pj.tglMulai)} s.d. ${sktHari(pj.tglSelesai)}` : sktHari(pj.tglMulai)) : dot;
    const kosong = Array.from({ length: 8 }, () => '<tr><td></td><td></td><td></td><td></td></tr>').join('');
    return `<section class="pdf-page st-page">${SKT_KOP}<div class="pdf-body">${r.status === 'diganti' ? '<p class="st-diganti">DIGANTI</p>' : ''}
      <h3 class="pdf-title st-title"><u>SURAT TUGAS</u><span>No. ${esc(r.nomor)}</span></h3>
      <p class="st-p">Menindaklanjuti kegiatan ${keg}, saya yang bertanda tangan di bawah ini:</p>
      <table class="pdf-kv st-kv"><tr><td>Nama</td><td>: ${dot}</td></tr><tr><td>Jabatan</td><td>: Operational Manager</td></tr></table>
      <p class="st-p">Dengan ini menugaskan kepada :</p>
      <table class="st-tbl"><thead><tr><th>No.</th><th>Nama</th><th>NIK</th><th>Jabatan</th></tr></thead>
        <tbody><tr><td>1</td><td>${esc(t.nama || '')}</td><td></td><td>Trainer</td></tr><tr><td>2</td><td>${esc(a.nama || '')}</td><td></td><td>Asisten Trainer</td></tr>${kosong}</tbody></table>
      <p class="st-p">sebagaimana ditugaskan sebagai <b>Trainer / Asisten Trainer</b> dalam acara ${keg} bertempat di ${esc(pj.lokasi || dot)} yang dilaksanakan pada :</p>
      <table class="pdf-kv st-kv"><tr><td>Hari/ Tanggal</td><td>: ${hari}/ ${rangePanjang(pj.tglMulai, pj.tglSelesai)}</td></tr><tr><td>Waktu</td><td>: ${esc(pj.waktu || dot)}</td></tr></table>
      <p class="st-p">Demikian Surat Tugas ini dibuat agar yang bersangkutan dapat melaksanakan dengan baik dan penuh tanggung jawab.</p>
      <div class="st-sign">Bekasi, ${tglPanjang(todayIso)}<br>Operational Manager<div class="pdf-ttd"></div>(${dot})</div>
      </div>${pdfFoot('FR/LMI-TR-05/01/Ed.Rev-1.0', '1 dari 1', '01/08/2023')}</section>`;
  }
  function sktPrint(r) {
    openModal({
      title: `Print Out Surat Kerja Trainer ${esc(r.nomor)}`, wide: 'x',
      body: pdfWrap(pageSkt(r), `PDF Surat Kerja Trainer ${esc(r.nomor)}: 1 halaman, format Surat Tugas Labmania. Tanggal tanda tangan = tanggal cetak${QSKT4}`),
      onMount: pdfMount, cancelLabel: 'Tutup', saveLabel: 'Cetak / Simpan PDF',
      onSave: () => { logAct('Quotation', 'Surat Kerja Trainer', 'Cetak', r.nomor, r.id, `Cetak Surat Kerja Trainer ${r.nomor}`); window.print(); return false; }
    });
  }

  // Jadwal dan tim yang tercetak di surat: salinan saat surat terbit (surat lama tetap seperti aslinya).
  const sktJadwal = r => r.tglMulai ? { tglMulai: r.tglMulai, tglSelesai: r.tglSelesai, waktu: r.waktu, lokasi: r.lokasi, durasi: r.durasi, trainer: r.trainer, asisten: r.asisten } : {};

  // Peserta yang keluar karena reschedule tetap tercatat di View SO (tidak dihitung di nilai).
  function keluarTxt(k) {
    const l = k.peserta.filter(r => r.st === 'keluar');
    if (!l.length) return '';
    return `<p class="hint gap-t"><b>Keluar karena reschedule (${l.length}):</b> ${l.map(r => `${esc((pesertaById(r.id) || {}).nama || '—')} (${r.keluar.jenis === 'pindah' ? 'pindah jadwal' : r.keluar.jenis === 'lepas' ? 'dicoret PIC' : 'refund'}${r.keluar.refund ? `, ${esc(r.keluar.refund)}` : ''})`).join(', ')}. Tidak dihitung di nilai Sales Order.</p>`;
  }

  // ---------- Refund (to be 12-11-2026, SO-FR-17 – SO-FR-20) ----------
  // Dibentuk otomatis saat peserta Deal yang sudah ditagih diproses Keluar (QTN-FR-36). Finance menyelesaikan:
  // Transfer (bukti wajib), Potong tagihan (invoice belum dibayar), atau Dialihkan (dana untuk quotation program tujuan).
  const QSO7 = q('Q-SO-07', 'Usulan: Refund diproses Finance tanpa approval; nominal sesuai S&K diisi Finance dengan keterangan; nomor LMI/RF/MM/yy-zzz; invoice belum dibayar cukup dicatat Potong tagihan + nomor nota kredit. Perlu persetujuan atasan sebelum transfer?');
  db.refund = [];
  const RF_ST = { menunggu: 'Menunggu Finance', selesai: 'Selesai' };
  const RF_JENIS = { refund: 'Refund', dialihkan: 'Dialihkan (pindah jadwal)' };
  const RF_SELESAI = { transfer: 'Transfer', potong: 'Potong tagihan', dialihkan: 'Dialihkan' };
  const rfById = id => db.refund.find(x => x.id === id);
  function rfCreate({ k, ps, inv, jenis, tujuan, alasan, nominal }) {
    const s = soOfQtn(k);
    const rf = { id: uid(), nomor: lmiNo('RF', todayIso), tanggal: todayIso, so: s ? s.id : null, soNomor: s ? soNomor(s) : '—', qtn: k.id, ps, inv: inv.nomor, jenis, tujuan, alasan, nominal, status: 'menunggu', rev: k.rev || 0 };
    db.refund.push(rf);
    logAct('Quotation', 'Refund', 'Tambah', rf.nomor, rf.id, `Refund ${rf.nomor} (${RF_JENIS[jenis]}) ${rp(nominal)} untuk ${ps ? (pesertaById(ps) || {}).nama : kepadaOf(k)}, ${rf.soNomor}, invoice ${inv.nomor}: ${alasan}`);
    return rf;
  }
  const rfSiapa = rf => rf.ps ? (pesertaById(rf.ps) || {}).nama || '—' : `${kepadaOf(db.qtn.find(x => x.id === rf.qtn))} (company)`;
  function listRefund() {
    const rows = db.refund.filter(rf => (state.status === 'all' || rf.status === state.status) && matches([rf.nomor, rfSiapa(rf), rf.soNomor, rf.inv, (db.qtn.find(x => x.id === rf.qtn) || {}).nomor]))
      .slice().sort((a, b) => (a.status === b.status ? b.id - a.id : a.status === 'menunggu' ? -1 : 1));
    if (!db.refund.length) return '<div class="empty-state"><h2>Belum ada Refund</h2><p>Refund terbentuk saat Sales memproses peserta Deal yang sudah ditagih tetapi tidak ikut jadwal baru setelah reschedule (Quotation › Data Peserta › Proses).</p></div>';
    return listBlock(rows, [
      NO_COL,
      { label: 'Nomor Refund', cls: 'nowrap', render: rf => `<button type="button" class="btn btn-ghost sm" data-act="rf-view" data-id="${rf.id}">${esc(rf.nomor)}</button><br><span class="muted">${tglPanjang(rf.tanggal)}</span>` },
      { label: 'Peserta / Company', render: rf => `<b>${esc(rfSiapa(rf))}</b><br><span class="muted">${esc(rf.alasan)}</span>` },
      { label: 'Sales Order', cls: 'nowrap', render: rf => `<span class="code">${esc(rf.soNomor)}</span><br><span class="muted">${esc((db.qtn.find(x => x.id === rf.qtn) || {}).nomor || '')}</span>` },
      { label: 'Invoice', cls: 'nowrap', render: rf => `<span class="code">${esc(rf.inv)}</span>` },
      { label: 'Jenis', render: rf => `${RF_JENIS[rf.jenis]}${rf.tujuan ? `<br><span class="muted">ke ${esc((db.quot.find(x => x.id === rf.tujuan) || {}).nomor || '—')}</span>` : ''}` },
      { label: 'Nominal', cls: 'money', render: rf => `${rp(rf.status === 'selesai' && rf.nominalRefund != null ? rf.nominalRefund : rf.nominal)}${rf.status === 'selesai' && rf.nominalRefund != null && rf.nominalRefund !== rf.nominal ? `<br><span class="muted">tagihan ${rp(rf.nominal)}</span>` : ''}` },
      { label: 'Status', cls: 'nowrap', render: rf => rf.status === 'menunggu' ? '<span class="badge st-terkirim">Menunggu Finance</span>' : `<span class="badge st-ps-deal">Selesai</span><br><span class="muted">${RF_SELESAI[rf.selesai.cara]} · ${esc(rf.selesai.oleh)}</span>` },
      { label: 'Action', cls: 'act', render: rf => gearMenu(rf, [['rf-view', 'View'], ...(rf.status === 'menunggu' ? [['rf-proses', 'Proses']] : [])]) }
    ]);
  }
  function rfView(rf) {
    const k = db.qtn.find(x => x.id === rf.qtn), pj = pjOfQtn(k), s = rf.selesai;
    openModal({ title: `Refund ${esc(rf.nomor)}`, wide: true, info: true,
      body: `<div class="summary">${sumRow('Status', RF_ST[rf.status])}${sumRow('Peserta / Company', esc(rfSiapa(rf)))}${sumRow('Sales Order', `<span class="code">${esc(rf.soNomor)}</span> · quotation <span class="code">${esc(k.nomor)}</span>`)}
        ${sumRow('Invoice', `<span class="code">${esc(rf.inv)}</span>`)}${sumRow('Program', `<span class="code">${esc(pj.nomor)}</span> · jadwal baru ${fmtRange(pj.tglMulai, pj.tglSelesai)}`)}${sumRow('Jenis', RF_JENIS[rf.jenis] + (rf.tujuan ? ` ke ${esc((db.quot.find(x => x.id === rf.tujuan) || {}).nomor || '—')}` : ''))}
        ${sumRow('Nominal tagihan', rp(rf.nominal))}${sumRow('Alasan dari Sales', esc(rf.alasan))}
        ${s ? sumRow('Penyelesaian', `${RF_SELESAI[s.cara]} · ${rp(s.nominal)}${s.cara === 'transfer' ? ` · ${esc(s.bank)} ${esc(s.rek)} a.n. ${esc(s.an)} · bukti <span class="code">${esc(s.bukti)}</span>` : ''}${s.ref ? ` · ${esc(s.ref)}` : ''}`) + sumRow('Diselesaikan', `${esc(s.oleh)} · ${fmtStamp(s.waktu)}`) + (s.catatan ? sumRow('Catatan Finance', esc(s.catatan)) : '') : ''}</div>` });
  }
  function rfProses(rf) {
    if (rf.status !== 'menunggu') { toast(`${rf.nomor} sudah selesai`, 'warn'); return; }
    const pilih = (v, l, on) => `<label class="chk"><input type="radio" name="cara" value="${v}" ${on ? 'checked' : ''}> ${l}</label>`;
    const qtnTujuan = rf.tujuan ? db.qtn.filter(x => x.pj === rf.tujuan && x.status !== 'batal') : [];
    openModal({
      title: `Proses Refund ${esc(rf.nomor)}`, wide: true,
      body: `<div class="summary">${sumRow('Peserta / Company', esc(rfSiapa(rf)))}${sumRow('Invoice', `<span class="code">${esc(rf.inv)}</span>`)}${sumRow('Nominal tagihan', rp(rf.nominal))}${sumRow('Diminta', RF_JENIS[rf.jenis])}</div>
        <p class="hint gap-t">Invoice tidak diedit dari sini. Jurnal atau nota kredit dikerjakan Finance di akuntansi.${QSO7}</p>
        <div class="grid2 gap-t">
          ${field('Penyelesaian' + req, pilih('transfer', 'Transfer: dana sudah dibayar, dikembalikan', rf.jenis === 'refund') + pilih('potong', 'Potong tagihan: invoice belum dibayar', false) + pilih('dialihkan', 'Dialihkan ke quotation program tujuan', rf.jenis === 'dialihkan'), '', 'span2')}
          ${field('Nominal' + req, `<input class="input" name="nominal" inputmode="numeric" value="${rf.nominal}"><span class="hint">sesuai S&amp;K, maksimal ${rp(rf.nominal)}</span>`)}
          ${field('Quotation tujuan (dialihkan)', `<select class="input" name="ref"><option value="">—</option>${qtnTujuan.map(x => `<option value="${esc(x.nomor)}">${esc(x.nomor)} · ${esc(kepadaOf(x))}</option>`).join('')}</select><span class="hint">${qtnTujuan.length ? 'quotation di program tujuan' : 'belum ada quotation di program tujuan; buat dulu di menu Quotation'}</span>`)}
          ${field('Bank', `<input class="input" name="bank" maxlength="50" placeholder="mis. BCA">`)}
          ${field('Nomor rekening', `<input class="input" name="rek" maxlength="30" inputmode="numeric">`)}
          ${field('Atas nama', `<input class="input" name="an" maxlength="100">`)}
          ${field('Bukti transfer', `<label class="pb-drop"><input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" hidden><b>Pilih file</b><span class="hint">PDF, JPG, PNG · maks. 5 MB · wajib untuk Transfer</span></label><div class="hint" data-file-out="bukti">Belum ada file</div>`)}
          ${field('Nomor nota kredit / catatan', `<input class="input" name="catatan" maxlength="200" placeholder="mis. NK/11/26-002">`, '', 'span2')}
        </div>`,
      saveLabel: 'Selesaikan Refund',
      onMount: form => { const b = form.elements.bukti; b.addEventListener('change', () => { $('[data-file-out="bukti"]', form).textContent = b.files[0] ? b.files[0].name : 'Belum ada file'; }); },
      onSave: f => {
        const cara = f.cara.value, nominal = Number(String(f.nominal.value).replace(/\D/g, '')), file = f.bukti.files[0];
        let ok = true;
        if (!cara) return invalid(f.nominal, 'Pilih penyelesaian');
        if (!(nominal >= 0) || nominal > rf.nominal) ok = invalid(f.nominal, `Nominal 0 sampai ${rp(rf.nominal)}`);
        if (cara === 'transfer') {
          ['bank', 'rek', 'an'].forEach(n => { if (!f[n].value.trim()) ok = invalid(f[n], 'Wajib untuk Transfer'); });
          if (!file) ok = invalid(f.bukti, 'Unggah bukti transfer');
          else if (!['pdf', 'jpg', 'jpeg', 'png'].includes(file.name.split('.').pop().toLowerCase())) ok = invalid(f.bukti, 'Hanya PDF, JPG, atau PNG');
          else if (file.size > 5 * 1024 * 1024) ok = invalid(f.bukti, 'Lebih dari 5 MB');
        }
        if (cara === 'dialihkan' && !f.ref.value) ok = invalid(f.ref, 'Pilih quotation tujuan');
        if (cara === 'potong' && !f.catatan.value.trim()) ok = invalid(f.catatan, 'Isi nomor nota kredit atau catatan');
        if (!ok) return false;
        rf.selesai = { cara, nominal, bank: f.bank.value.trim(), rek: f.rek.value.trim(), an: f.an.value.trim(), bukti: file ? file.name : '', ref: f.ref.value, catatan: f.catatan.value.trim(), oleh: meName(), waktu: isoNow().slice(0, 16).replace('T', ' ') };
        rf.nominalRefund = nominal; rf.status = 'selesai';
        logAct('Quotation', 'Refund', 'Proses', rf.nomor, rf.id, `Refund ${rf.nomor} selesai: ${RF_SELESAI[cara]} ${rp(nominal)}`, [{ f: 'Status', old: 'Menunggu Finance', new: 'Selesai' }, { f: 'Penyelesaian', old: '', new: `${RF_SELESAI[cara]} ${rp(nominal)}` }]);
        toast(`Refund ${rf.nomor} selesai (${RF_SELESAI[cara]})`);
      }
    });
  }

  // ---------- Data contoh untuk uji reschedule, peserta tidak ikut, dan refund (dipanggil main.js setelah qpsSeed) ----------
  // Skenario (permintaan BA 12-11-2026, untuk dites):
  //  1. Program Public Final pertama yang sudah punya SO di-reschedule +14 hari: quotation & SO rev1, link baru, Surat Tugas baru.
  //  2. Jawaban konfirmasi ulang lewat link (contoh):
  //     - quotation Personal yang sudah ditagih: peserta minta refund dan SUDAH diproses Sales -> Refund "Menunggu Finance" (uji Proses Refund).
  //     - Contoh Peserta H: ikut jadwal baru (surat baru diunggah).
  //     - Contoh Peserta I: minta refund, BELUM diproses, BELUM ditagih -> uji Proses: peserta Keluar tanpa Refund.
  //     - Contoh Peserta J: minta pindah jadwal, BELUM diproses, SUDAH ditagih -> uji Proses: Refund jenis Dialihkan.
  //     - Contoh Peserta F (quotation Terkirim): ikut; Contoh Peserta G belum menjawab -> tidak bisa dicentang saat Deal.
  //  3. Program tujuan pindah jadwal: Penjualan Program Public baru dari Master Program yang sama, Januari 2027.
  const RS_JAWAB = { 'Contoh Peserta H': 'ikut', 'Contoh Peserta I': 'refund', 'Contoh Peserta J': 'pindah', 'Contoh Peserta F': 'ikut' };
  const RS_CATATAN = { refund: 'Tidak bisa di tanggal baru, ada audit internal', pindah: 'Bisa ikut batch bulan depan' };
  function rsDemo() {
    const pj = db.quot.find(x => x.jenis === 'public' && x.status === 'final' && soOfPj(x).length);
    if (!pj || !pj.tglMulai) return;
    // Program tujuan untuk pindah jadwal (Master Program sama, jauh setelah batas H-7).
    const tujuan = Object.assign(clone(pj), { id: uid(), nomor: docNo(PJ_PREFIX.public), tglMulai: '2027-01-12', tglSelesai: addHari('2027-01-12', (Number(pj.durasi) || 1) - 1), reschedule: [] });
    db.quot.push(tujuan);
    // Quotation Contoh Peserta J diberi invoice (sudah ditagih) agar pindah jadwal menghasilkan Refund jenis Dialihkan.
    const kJ = qtnOfPj(pj).find(k => k.status === 'deal' && k.peserta.some(r => (pesertaById(r.id) || {}).nama === 'Contoh Peserta J'));
    if (kJ && !invOfQtn(kJ).length) {
      const tgl = '2026-11-02', pl = ivcPerlakuan(kJ), u = ivcNilaiUnit(kJ, 'satu'), hh = ivcHitung(u.penjualan, u.lain, pl), nomor = ivcNo(tgl), coa = coaSalesDefault(kJ);
      db.inv.push(Object.assign({ id: uid(), nomor, tanggal: tgl, qtn: kJ.id, prog: progNo(kJ), jenis: pj.jenis, cara: 'satu', peserta: u.ps, perlakuan: pl, noFaktur: '04002600000102', coaPenjualan: coa, setor: null, status: 'invoiced',
        jurnal: ivcJurnal(hh, pl, coa, nomor, kJ, tgl) }, hh));
      Object.assign(soOfQtn(kJ) || {}, { status: 'siap', siapBy: 'Admin Sistem', siapAt: `${tgl} 09:00` });
    }
    const ref = reschedApply(pj, { mulai: addHari(pj.tglMulai, 14), trainer: pj.trainer, asisten: pj.asisten, alasan: 'Kuota peserta belum terpenuhi (contoh)', evidence: ['Notulen-rapat-kuota.pdf'], oleh: 'Contoh User Dua', waktuUbah: `${todayIso}T09:15` });
    const ks = qtnOfPj(pj).filter(k => k.status !== 'batal');
    const kInv = ks.find(k => k.status === 'deal' && invOfQtn(k).length && k.tujuan === 'personal' && !k.peserta.some(r => RS_JAWAB[(pesertaById(r.id) || {}).nama]));
    let menit = 5;
    const jawab = (k, r, keputusan) => {
      r.konf = { rev: k.rev, keputusan, catatan: RS_CATATAN[keputusan] || '', tgl: `${todayIso} 10:${String(menit += 3).padStart(2, '0')}`, sumber: 'link' };
      const p = pesertaById(r.id);
      if (keputusan === 'ikut' && p) { if (p.surat) (p.suratLama = p.suratLama || []).push(Object.assign({ rev: 0 }, p.surat));
        p.surat = { nama: `Surat_Konfirmasi_rev${k.rev}_${p.nama.replace(/[^\w]+/g, '_')}.pdf`, ukuran: 201000, tipe: 'application/pdf', contoh: true }; }
    };
    ks.forEach(k => k.peserta.filter(r => perluUlang(k, r)).forEach(r => {
      const nama = (pesertaById(r.id) || {}).nama;
      if (k === kInv) jawab(k, r, 'refund');
      else if (RS_JAWAB[nama]) jawab(k, r, RS_JAWAB[nama]);
    }));
    if (kInv) {
      const r = kInv.peserta.find(x => x.st === 'deal'), inv = r && invOfQtn(kInv).find(i => i.peserta.includes(r.id));
      if (r && inv) {
        const v = qtnNilai(kInv, [r]);
        r.st = 'keluar'; r.keluar = { jenis: 'refund', tujuan: '', alasan: r.konf.catatan, rev: kInv.rev, tgl: `${todayIso}T10:30`, oleh: 'Contoh User Dua' };
        r.keluar.refund = rfCreate({ k: kInv, ps: r.id, inv, jenis: 'refund', tujuan: '', alasan: r.konf.catatan, nominal: v.dpp + v.ppn }).nomor;
      }
    }
    return ref;
  }
