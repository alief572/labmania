/* Labmania mockup to be — modul Refund (paket Invoice - Pengajuan).
   input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/ butir 6: refund punya modul sendiri dengan menu
   Daftar Refund dan Approval Refund; tampilan Proses Refund yang lama dipertahankan. Sebelumnya tab Refund di Sales Order.
   Dimuat sebelum main.js; rfCreate dipakai Quotation (peserta tidak ikut setelah reschedule) dan pembatalan Penjualan Program. */
'use strict';
  const QSO7 = q('Q-SO-07', 'Terjawab 13-11-2026: yang menyetujui Finance (hak Approve di Approval Refund); refund ditolak kembali ke Sales/Marketing untuk diperbaiki lalu diajukan ulang. Masih terbuka: nomor LMI/RF/MM/yy-zzz, faktur pajak refund (ditunda).');
  db.refund = [];
  // Menunggu Approval → Disetujui (menunggu transfer) → Selesai; Ditolak → diperbaiki Sales/Marketing → Menunggu Approval lagi
  // (input/Master - Buat Program/2026-11-13-jawaban-multi-master-refund-top/ [JMR-4], [JMR-5]).
  const RF_ST = { menunggu: 'Menunggu Approval', disetujui: 'Disetujui, menunggu transfer', ditolak: 'Ditolak, perlu diperbaiki', selesai: 'Selesai' };
  const RF_BADGE = { menunggu: 'st-terkirim', disetujui: 'st-ps-menunggu', ditolak: 'st-ps-keluar', selesai: 'st-ps-deal' };
  const RF_SEBAB = { reschedule: 'Tidak ikut setelah reschedule', batal: 'Penjualan Program dibatalkan' };
  const rfById = id => db.refund.find(x => x.id === id);
  const rfBadge = rf => `<span class="badge ${RF_BADGE[rf.status]}">${RF_ST[rf.status]}</span>`;
  function rfCreate({ k, ps, inv, alasan, nominal, sebab = 'reschedule' }) {
    const s = soOfQtn(k) || soBatalOfQtn(k).pop(); // saat menunggu Deal ulang / program batal, rujuk SO yang batal
    const rf = { id: uid(), nomor: lmiNo('RF', todayIso), tanggal: todayIso, so: s ? s.id : null, soNomor: s ? soNomor(s) : '—', qtn: k.id, ps, inv: inv.nomor, alasan, nominal, status: 'menunggu', rev: k.rev || 0, sebab };
    db.refund.push(rf);
    logAct('Refund', 'Daftar Refund', 'Tambah', rf.nomor, rf.id, `Refund ${rf.nomor} ${rp(nominal)} untuk ${ps ? (pesertaById(ps) || {}).nama : kepadaOf(k)}, ${rf.soNomor}, invoice ${inv.nomor} (sudah dibayar): ${alasan}. Menunggu approval.`);
    return rf;
  }
  const rfSiapa = rf => rf.ps ? (pesertaById(rf.ps) || {}).nama || '—' : `${kepadaOf(db.qtn.find(x => x.id === rf.qtn))} (company)`;
  const rfNominal = rf => (rf.status === 'selesai' ? rf.selesai.nominal : rf.nominal);
  const rfCols = gear => [
    NO_COL,
    { label: 'Nomor Refund', cls: 'nowrap', render: rf => `<button type="button" class="btn btn-ghost sm" data-act="rf-view" data-id="${rf.id}">${esc(rf.nomor)}</button><br><span class="muted">${tglPanjang(rf.tanggal)}</span>` },
    { label: 'Peserta / Company', render: rf => `<b>${esc(rfSiapa(rf))}</b><br><span class="muted">${esc(RF_SEBAB[rf.sebab] || '')}: ${esc(rf.alasan)}</span>` },
    { label: 'SO / Invoice', cls: 'nowrap', render: rf => `<span class="code">${esc(rf.soNomor)}</span><br><span class="code">${esc(rf.inv)}</span> <span class="muted">dibayar</span>` },
    { label: 'Nominal', cls: 'money', render: rf => `${rp(rfNominal(rf))}${rf.status === 'selesai' && rf.selesai.nominal !== rf.nominal ? `<br><span class="muted">tagihan ${rp(rf.nominal)}</span>` : ''}` },
    { label: 'Status', cls: 'nowrap', render: rf => `${rfBadge(rf)}${rf.approval ? `<br><span class="muted">${esc(rf.approval.oleh)} · ${fmtStamp(rf.approval.waktu)}</span>` : ''}${rf.status === 'selesai' ? `<br><span class="muted">transfer · ${esc(rf.selesai.oleh)}</span>` : ''}` },
    { label: 'Action', cls: 'act', render: gear }
  ];

  // ---------- Menu Daftar Refund ----------
  const viewRefund = () => `${pageHead('Daftar Refund', 'Pengembalian dana peserta atau company yang <b>sudah membayar</b> lalu tidak ikut: memilih Refund setelah reschedule, tidak ikut saat Deal ulang, atau programnya dibatalkan. Refund terbentuk otomatis; setelah <b>disetujui</b> Finance di Approval Refund, Finance menyelesaikannya dengan transfer. Refund yang <b>ditolak</b> diperbaiki Sales/Marketing lalu diajukan ulang. Invoice tidak diedit.', 'RF-FR-01 – RF-FR-04, RF-FR-07')}
    <div class="card">${toolbar('Cari nomor refund, peserta, SO, invoice…', false,
      selectFilter('fstatus', 'Filter status refund', state.status, [['all', 'Semua status'], ...Object.keys(RF_ST).map(s => [s, RF_ST[s]])]))}
      <div id="list">${listRefund()}</div></div>`;
  function listRefund() {
    const urut = ['ditolak', 'disetujui', 'menunggu', 'selesai'];
    const rows = db.refund.filter(rf => (state.status === 'all' || rf.status === state.status) && matches([rf.nomor, rfSiapa(rf), rf.soNomor, rf.inv, (db.qtn.find(x => x.id === rf.qtn) || {}).nomor]))
      .slice().sort((a, b) => urut.indexOf(a.status) - urut.indexOf(b.status) || b.id - a.id);
    if (!db.refund.length) return '<div class="empty-state"><h2>Belum ada Refund</h2><p>Refund terbentuk otomatis saat peserta yang sudah membayar tidak ikut (setelah reschedule) atau Penjualan Program dibatalkan.</p></div>';
    return listBlock(rows, rfCols(rf => gearMenu(rf, [['rf-view', 'View'], ...(rf.status === 'disetujui' && can('refund', 'm') ? [['rf-proses', 'Proses Refund']] : []), ...(rf.status === 'ditolak' && can('refund', 'a') ? [['rf-fix', 'Perbaiki & ajukan ulang']] : [])])));
  }

  // ---------- Menu Approval Refund (hak Approve) ----------
  state.rfApprTab = 'menunggu';
  const viewRfAppr = () => {
    const tabs = [['menunggu', 'Menunggu Approval', db.refund.filter(x => x.status === 'menunggu').length], ['riwayat', 'Sudah diputuskan', db.refund.filter(x => x.approval).length]].map(([t, l, n]) =>
      `<button type="button" class="tab ${state.rfApprTab === t ? 'active' : ''}" data-act="rfappr-tab" data-tab="${t}" aria-pressed="${state.rfApprTab === t}">${l}<span class="nav-count">${n}</span></button>`).join('');
    return `${pageHead('Approval Refund', 'Persetujuan <b>Finance</b> sebelum dana ditransfer. <b>Setujui</b>: refund lanjut ke proses transfer (Daftar Refund › Proses Refund). <b>Tolak</b>: alasan wajib; refund kembali ke Sales/Marketing untuk diperbaiki dan diajukan ulang.', 'RF-FR-05')}
      <div class="card"><div class="tabs">${tabs}</div>${toolbar('Cari nomor refund, peserta, invoice…', false)}
      <div id="list">${listRfAppr()}</div></div>`;
  };
  function listRfAppr() {
    const rows = db.refund.filter(rf => (state.rfApprTab === 'menunggu' ? rf.status === 'menunggu' : !!rf.approval) && matches([rf.nomor, rfSiapa(rf), rf.inv, rf.soNomor]))
      .slice().sort((a, b) => b.id - a.id);
    if (!rows.length) return `<p class="dash-empty">${state.rfApprTab === 'menunggu' ? 'Tidak ada refund yang menunggu approval.' : 'Belum ada refund yang diputuskan.'}</p>`;
    return listBlock(rows, rfCols(rf => gearMenu(rf, [['rf-view', 'View'], ...(rf.status === 'menunggu' && can('rfAppr', 'p') ? [['rf-appr', 'Proses (Setujui / Tolak)']] : [])])));
  }
  function rfAppr(rf) {
    if (rf.status !== 'menunggu') { toast(`${rf.nomor} sudah diputuskan`, 'warn'); return; }
    if (!can('rfAppr', 'p')) { toast('Anda tidak punya hak Approve di Approval Refund (Role & Permission)', 'warn'); return; }
    openModal({
      title: `Approval Refund ${esc(rf.nomor)}`, wide: true,
      body: `<div class="summary">${sumRow('Peserta / Company', esc(rfSiapa(rf)))}${sumRow('Sebab', esc(RF_SEBAB[rf.sebab] || ''))}${sumRow('Alasan', esc(rf.alasan))}${sumRow('Invoice', `<span class="code">${esc(rf.inv)}</span> · sudah dibayar`)}${sumRow('Nominal tagihan', rp(rf.nominal))}</div>
        <div class="grid2 gap-t">
          ${field('Keputusan' + req, `<label class="chk"><input type="radio" name="hasil" value="disetujui" checked> <span><b>Setujui</b> <span class="muted">refund lanjut ke Finance untuk ditransfer</span></span></label>
            <label class="chk"><input type="radio" name="hasil" value="ditolak"> <span><b>Tolak</b> <span class="muted">kembali ke Sales/Marketing untuk diperbaiki sesuai alasan</span></span></label>`, '', 'span2')}
          ${field('Catatan', '<textarea class="input" name="catatan" rows="2" maxlength="300" placeholder="Wajib bila ditolak"></textarea>', '', 'span2')}
        </div>${QSO7}`,
      saveLabel: 'Simpan Keputusan',
      onSave: f => {
        const hasil = f.hasil.value, catatan = f.catatan.value.trim();
        if (hasil === 'ditolak' && !catatan) return invalid(f.catatan, 'Isi alasan penolakan (yang harus diperbaiki Sales/Marketing)');
        rf.approval = { hasil, catatan, oleh: meName(), waktu: stampNow() }; rf.riwayat = (rf.riwayat || []).concat([Object.assign({ jenis: 'approval' }, rf.approval)]); const lama = RF_ST[rf.status];
        rf.status = hasil;
        logAct('Refund', 'Approval Refund', hasil === 'disetujui' ? 'Setujui' : 'Tolak', rf.nomor, rf.id, `Refund ${rf.nomor} ${hasil === 'disetujui' ? 'disetujui' : 'ditolak'}${catatan ? `: ${catatan}` : ''}`, [{ f: 'Status', old: lama, new: RF_ST[hasil] }]);
        toast(`Refund ${rf.nomor} ${hasil === 'disetujui' ? 'disetujui, lanjut ke proses transfer' : 'ditolak, kembali ke Sales/Marketing untuk diperbaiki'}`);
      }
    });
  }

  function rfView(rf) {
    const k = db.qtn.find(x => x.id === rf.qtn), pj = pjOfQtn(k), s = rf.selesai, a = rf.approval;
    openModal({ title: `Refund ${esc(rf.nomor)}`, wide: true, info: true,
      body: `<div class="summary">${sumRow('Status', rfBadge(rf))}${sumRow('Peserta / Company', esc(rfSiapa(rf)))}${sumRow('Sebab', esc(RF_SEBAB[rf.sebab] || ''))}${sumRow('Sales Order', `<span class="code">${esc(rf.soNomor)}</span> · quotation <span class="code">${esc(k.nomor)}</span>`)}
        ${sumRow('Invoice', `<span class="code">${esc(rf.inv)}</span> · sudah dibayar`)}${sumRow('Program', `<span class="code">${esc(pj.nomor)}</span> · ${pj.status === 'batal' ? 'program dibatalkan' : `jadwal ${fmtRange(pj.tglMulai, pj.tglSelesai)}`}`)}
        ${sumRow('Nominal tagihan', rp(rf.nominal))}${sumRow('Alasan', esc(rf.alasan))}
        ${(rf.riwayat || (a ? [Object.assign({ jenis: 'approval' }, a)] : [])).map(h => sumRow(h.jenis === 'perbaikan' ? 'Diajukan ulang' : 'Approval', h.jenis === 'perbaikan' ? `${esc(h.oleh)} · ${fmtStamp(h.waktu)} · ${esc(h.catatan)}` : `${h.hasil === 'disetujui' ? 'Disetujui' : 'Ditolak'} · ${esc(h.oleh)} · ${fmtStamp(h.waktu)}${h.catatan ? ` · ${esc(h.catatan)}` : ''}`)).join('')}
        ${s ? sumRow('Transfer', `${rp(s.nominal)} · ${esc(s.bank)} ${esc(s.rek)} a.n. ${esc(s.an)} · bukti <span class="code">${esc(s.bukti)}</span>`) + sumRow('Diselesaikan', `${esc(s.oleh)} · ${fmtStamp(s.waktu)}`) + (s.catatan ? sumRow('Keterangan', esc(s.catatan)) : '') : ''}</div>` });
  }
  // Perbaiki & ajukan ulang (Sales/Marketing) refund yang ditolak ([JMR-5]): alasan penolakan tampil di atas;
  // yang dapat diperbaiki alasan refund dan nominal tagihan (maks. nilai invoice); catatan perbaikan wajib.
  function rfFix(rf) {
    if (rf.status !== 'ditolak') { toast(`${rf.nomor} tidak sedang ditolak`, 'warn'); return; }
    if (!can('refund', 'a')) { toast('Anda tidak punya hak Ajukan ulang di Daftar Refund (Role & Permission)', 'warn'); return; }
    const inv = db.inv.find(i => i.nomor === rf.inv), maks = inv ? inv.total : rf.nominal;
    openModal({
      title: `Perbaiki & ajukan ulang ${esc(rf.nomor)}`, wide: true,
      body: `<div class="notice"><span><b>Ditolak</b> oleh ${esc(rf.approval.oleh)} · ${fmtStamp(rf.approval.waktu)}<br>Alasan: ${esc(rf.approval.catatan)}</span></div>
        <div class="summary gap-t">${sumRow('Peserta / Company', esc(rfSiapa(rf)))}${sumRow('Sebab', esc(RF_SEBAB[rf.sebab] || ''))}${sumRow('Invoice', `<span class="code">${esc(rf.inv)}</span> · ${rp(maks)} · sudah dibayar`)}</div>
        <div class="grid2 gap-t">
          ${field('Alasan refund' + req, `<textarea class="input" name="alasan" rows="2" maxlength="300">${esc(rf.alasan)}</textarea>`, '', 'span2')}
          ${field('Nominal tagihan' + req, moneyIn('name="nominal"', rf.nominal) + `<span class="hint">maksimal ${rp(maks)}</span>`)}
          ${field('Catatan perbaikan' + req, '<textarea class="input" name="catatan" rows="2" maxlength="300" placeholder="Apa yang diperbaiki sesuai alasan penolakan"></textarea>', '', 'span2')}
        </div>${QSO7}`,
      saveLabel: 'Ajukan ulang',
      onSave: f => {
        const alasan = f.alasan.value.trim(), nominal = Number(digits(f.nominal.value)), catatan = f.catatan.value.trim();
        let ok = true;
        if (!alasan) ok = invalid(f.alasan, 'Isi alasan refund');
        if (!(nominal > 0) || nominal > maks) ok = invalid(f.nominal, `Nominal 1 sampai ${rp(maks)}`);
        if (!catatan) ok = invalid(f.catatan, 'Tulis apa yang diperbaiki');
        if (!ok) return false;
        const chg = [{ f: 'Status', old: RF_ST.ditolak, new: RF_ST.menunggu }];
        if (alasan !== rf.alasan) chg.push({ f: 'Alasan', old: rf.alasan, new: alasan });
        if (nominal !== rf.nominal) chg.push({ f: 'Nominal tagihan', old: rp(rf.nominal), new: rp(nominal) });
        rf.riwayat = (rf.riwayat || [Object.assign({ jenis: 'approval' }, rf.approval)]).concat([{ jenis: 'perbaikan', catatan, oleh: meName(), waktu: stampNow() }]);
        Object.assign(rf, { alasan, nominal, status: 'menunggu', approval: null, diajukanUlang: (rf.diajukanUlang || 0) + 1 });
        logAct('Refund', 'Daftar Refund', 'Ajukan Ulang', rf.nomor, rf.id, `Refund ${rf.nomor} diperbaiki dan diajukan ulang: ${catatan}`, chg);
        toast(`Refund ${rf.nomor} diajukan ulang ke Approval Refund`);
      }
    });
  }

  // Proses Refund (Finance): tampilan sama dengan sebelumnya; hanya untuk refund yang sudah disetujui.
  function rfProses(rf) {
    if (rf.status !== 'disetujui') { toast(rf.status === 'menunggu' ? `${rf.nomor} belum disetujui (Approval Refund)` : `${rf.nomor} sudah ${RF_ST[rf.status].toLowerCase()}`, 'warn'); return; }
    openModal({
      title: `Proses Refund ${esc(rf.nomor)}`, wide: true,
      body: `<div class="summary">${sumRow('Peserta / Company', esc(rfSiapa(rf)))}${sumRow('Invoice', `<span class="code">${esc(rf.inv)}</span> · sudah dibayar`)}${sumRow('Nominal tagihan', rp(rf.nominal))}${sumRow('Alasan', esc(rf.alasan))}${sumRow('Disetujui', `${esc(rf.approval.oleh)} · ${fmtStamp(rf.approval.waktu)}`)}</div>
        <p class="hint gap-t">Dana dikembalikan dengan transfer. Invoice tidak diedit dari sini; jurnal pengembalian dikerjakan Finance di akuntansi.${QSO7}</p>
        <div class="grid2 gap-t">
          ${field('Nominal refund' + req, `<input class="input" name="nominal" inputmode="numeric" value="${rf.nominal}"><span class="hint">sesuai S&amp;K, maksimal ${rp(rf.nominal)}</span>`)}
          ${field('Bank' + req, `<input class="input" name="bank" maxlength="50" placeholder="mis. BCA">`)}
          ${field('Nomor rekening' + req, `<input class="input" name="rek" maxlength="30" inputmode="numeric">`)}
          ${field('Atas nama' + req, `<input class="input" name="an" maxlength="100">`)}
          ${field('Bukti transfer' + req, `<label class="pb-drop"><input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" hidden><b>Pilih file</b><span class="hint">PDF, JPG, PNG · maks. 5 MB</span></label><div class="hint" data-file-out="bukti">Belum ada file</div>`, '', 'span2')}
          ${field('Keterangan', `<input class="input" name="catatan" maxlength="200" placeholder="mis. dipotong biaya administrasi sesuai S&K">`, '', 'span2')}
        </div>`,
      saveLabel: 'Selesaikan Refund',
      onMount: form => { const b = form.elements.bukti; b.addEventListener('change', () => { $('[data-file-out="bukti"]', form).textContent = b.files[0] ? b.files[0].name : 'Belum ada file'; }); },
      onSave: f => {
        const nominal = Number(String(f.nominal.value).replace(/\D/g, '')), file = f.bukti.files[0];
        let ok = true;
        if (!f.nominal.value.trim() || nominal > rf.nominal) ok = invalid(f.nominal, `Nominal 0 sampai ${rp(rf.nominal)}`);
        if (nominal < rf.nominal && !f.catatan.value.trim()) ok = invalid(f.catatan, 'Nominal kurang dari tagihan: isi keterangan potongan');
        ['bank', 'rek', 'an'].forEach(n => { if (!f[n].value.trim()) ok = invalid(f[n], 'Wajib diisi'); });
        if (!file) ok = invalid(f.bukti, 'Unggah bukti transfer');
        else if (!['pdf', 'jpg', 'jpeg', 'png'].includes(file.name.split('.').pop().toLowerCase())) ok = invalid(f.bukti, 'Hanya PDF, JPG, atau PNG');
        else if (file.size > 5 * 1024 * 1024) ok = invalid(f.bukti, 'Lebih dari 5 MB');
        if (!ok) return false;
        rf.selesai = { nominal, bank: f.bank.value.trim(), rek: f.rek.value.trim(), an: f.an.value.trim(), bukti: file.name, catatan: f.catatan.value.trim(), oleh: meName(), waktu: isoNow().slice(0, 16).replace('T', ' ') };
        rf.status = 'selesai';
        logAct('Refund', 'Daftar Refund', 'Proses', rf.nomor, rf.id, `Refund ${rf.nomor} selesai: transfer ${rp(nominal)}`, [{ f: 'Status', old: RF_ST.disetujui, new: RF_ST.selesai }, { f: 'Nominal refund', old: '', new: rp(nominal) }]);
        toast(`Refund ${rf.nomor} selesai`);
      }
    });
  }

  TOUR.refund = () => [
    tS(null, 'Daftar Refund', 'Pengembalian dana untuk peserta atau company yang <b>sudah membayar</b> lalu tidak ikut. Terbentuk otomatis, bukan diinput manual.'),
    tS('.toolbar', 'Saring', 'Saring menurut status: Menunggu Approval, Disetujui, Ditolak, Selesai.'),
    tS(tKol(tRows()[0], 'Status'), 'Status', '<b>Menunggu Approval</b> → <b>Disetujui</b> (Finance, di menu Approval Refund) → <b>Selesai</b> setelah ditransfer. <b>Ditolak</b>: kembali ke Sales/Marketing untuk diperbaiki lalu diajukan ulang.'),
    tS(tKol(tRows()[0], 'Peserta / Company'), 'Peserta / Company', 'Siapa yang dikembalikan dananya, sebabnya (<b>tidak ikut setelah reschedule</b> atau <b>Penjualan Program dibatalkan</b>), dan alasannya.'),
    tS(tGear(), 'Aksi', '<b>View</b>: detail dan riwayat approval. <b>Perbaiki & ajukan ulang</b> (Sales/Marketing, refund Ditolak): perbaiki sesuai alasan penolakan. <b>Proses Refund</b> (Finance, hanya setelah disetujui): isi nominal sesuai S&K, rekening, dan unggah bukti transfer.'),
    { popover: { title: 'Approval Refund', description: 'Refund baru menunggu persetujuan di menu Approval Refund.' }, lanjut: { label: 'Ke Approval Refund', run: () => go('rfAppr'), ke: 'rfAppr' } }
  ];
  TOUR.rfAppr = () => [
    tS(null, 'Approval Refund', 'Persetujuan <b>Finance</b> sebelum dana dikembalikan. Butuh hak <b>Approve</b> di menu ini.'),
    tS('#view .card .tabs', 'Tab', '<b>Menunggu Approval</b> dan <b>Sudah diputuskan</b>.'),
    tS(tGear(), 'Proses', '<b>Setujui</b>: refund lanjut ke proses transfer. <b>Tolak</b>: alasan wajib; refund kembali ke Sales/Marketing untuk diperbaiki.'),
    { popover: { title: 'Setelah disetujui', description: 'Finance memproses transfer di Daftar Refund › <b>Proses Refund</b>.' }, lanjut: { label: 'Ke Daftar Refund', run: () => go('refund'), ke: 'refund' } }
  ];
