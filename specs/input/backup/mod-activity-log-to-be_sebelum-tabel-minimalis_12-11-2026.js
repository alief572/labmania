/* Labmania mockup — Jejak data & Activity Log (dipakai semua paket).
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Jejak data & Activity Log (LOG-FR-01 – LOG-FR-08; input/Master - Buat Program/2026-10-30-histori-activity-log/) ----------
  // Setiap record menyimpan Dibuat oleh/tanggal-jam dan Diubah oleh/tanggal-jam. Setiap perubahan data (tambah, edit, hapus,
  // aktif/nonaktif, finalkan, batal, kirim, deal) tercatat di Activity Log beserta field sebelum/sesudah.
  // Mockup: perubahan dideteksi dengan membandingkan data sebelum & sesudah setiap aksi; di aplikasi dicatat write_log() di server.
  const AUDIT_SRC = [
    ['jenisProduk', 'Master Data', 'Jenis Produk'], ['jenisSertifikasi', 'Master Data', 'Jenis Sertifikat'], ['jenisBidang', 'Master Data', 'Jenis Bidang'],
    ['paket', 'Master Data', 'Product'], ['peserta', 'Master Data', 'Master Peserta'], ['company', 'Master Data', 'Master Company'],
    ['trainer', 'Master Data', 'Master Trainer'], ['ketQtn', 'Master Data', 'Template Keterangan Quotation'], ['kfTpl', 'Master Data', 'Template Formulir Konfirmasi'],
    ['rate.fixed', 'Master Data', 'Master Rate — Fixed Cost'], ['rate.variable', 'Master Data', 'Master Rate — Variable Cost'], ['user', 'Master Data', 'Master User'], ['role', 'Master Data', 'Master Role'],
    ['mprog', 'Penjualan Program', 'Master Program'], ['quot', 'Penjualan Program', 'Penjualan Program'], ['qtn', 'Quotation', 'Quotation'], ['so', 'Quotation', 'Sales Order'], ['skt', 'Quotation', 'Surat Kerja Trainer'], ['inv', 'Invoice', 'Invoicing'],
    ['pb', 'Pengajuan Biaya', 'Pengajuan'], ['ob', 'Pengajuan Biaya', 'Overbudget']
  ];
  const STAMP_KEYS = ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
  const LOG_AKSI = ['Tambah', 'Edit', 'Hapus', 'Aktifkan', 'Nonaktifkan', 'Finalkan', 'Batalkan', 'Kirim & Cetak', 'Deal', 'Siap Invoice', 'Cetak', 'Setujui', 'Tolak', 'Tandai Paid', 'Export', 'Close Program'];
  const STATUS_AKSI = { final: 'Finalkan', batal: 'Batalkan', terkirim: 'Kirim & Cetak', deal: 'Deal', siap: 'Siap Invoice', disetujui: 'Setujui', ditolak: 'Tolak', paid: 'Tandai Paid' };
  const FIELD_LBL = { asisten: 'Asisten trainer', nama: 'Nama', kode: 'Kode', nomor: 'Nomor', judul: 'Judul', lingkup: 'Lingkup', aktif: 'Status aktif', status: 'Status', item: 'Item', cost: 'Default Cost',
    hargaJual: 'Harga jual / peserta', targetPeserta: 'Target peserta', targetProfit: 'Target profit', durasi: 'Durasi', biaya: 'Item biaya', peserta: 'Peserta',
    diskon: 'Diskon', lain: 'Biaya lain-lain', allin: 'Harga all-in', skema: 'Skema harga', ppn: 'PPN', tanggal: 'Tanggal', berlaku: 'Berlaku sampai',
    tglMulai: 'Tanggal mulai', tglSelesai: 'Tanggal selesai', lokasi: 'Lokasi', waktu: 'Waktu', trainer: 'Trainer', company: 'Company', instansi: 'Instansi', telp: 'Telepon', email: 'Email',
    alamat: 'Alamat', up: 'u.p.', sales: 'Sales', tujuan: 'Tujuan', ket: 'Keterangan', kf: 'Formulir Konfirmasi', teks: 'Teks', def: 'Default', noFaktur: 'Nomor Faktur', perlakuan: 'Perlakuan PPN', ppnPerlakuan: 'Perlakuan PPN', setor: 'Bukti setor WAPU', coaPenjualan: 'COA Penjualan', cara: 'Cara tagih', desk: 'Deskripsi', items: 'Item pengajuan', total: 'Total', bank: 'Bank', norek: 'No. Rekening', nmRek: 'Nama Rekening', files: 'Bukti', alasan: 'Alasan', alasanTolak: 'Alasan tolak', apprBy: 'Approval oleh', apprAt: 'Waktu approval', kat: 'Kategori', jenis: 'Jenis', by: 'Request By', user: 'Akun login', paidBy: 'Paid oleh', paidAt: 'Tanggal bayar', paidKet: 'Keterangan bayar', role: 'Role', ovr: 'Override Permission', sistem: 'Bawaan sistem' };
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
      let a = (o || {})[k], b = (n || {})[k];
      // Role disimpan sebagai id Master Role; Override Permission ditampilkan sebagai jumlah hak.
      if (k === 'trainer') { a = a && (trainerById(a) || {}).nama || a; b = b && (trainerById(b) || {}).nama || b; }
      if (k === 'asisten') { a = a && asistenNama(a); b = b && asistenNama(b); } // to be 12-11-2026: asisten trainer (Master User)
      if (k === 'role') { a = a && (roleById(a) || {}).nama || a; b = b && (roleById(b) || {}).nama || b; }
      if (k === 'ovr') { a = a ? `${akCount(a)} hak` : ''; b = b ? `${akCount(b)} hak` : ''; }
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
          // Reschedule Penjualan Program (to be): aksi sendiri, alasan masuk keterangan, riwayatnya tidak ditampilkan sebagai field.
          const resched = keys.includes('reschedule');
          const aksi = resched ? 'Reschedule' : keys.includes('status') && STATUS_AKSI[n.status] ? STATUS_AKSI[n.status]
            : keys.length === 1 && keys[0] === 'aktif' ? (n.aktif ? 'Aktifkan' : 'Nonaktifkan') : 'Edit';
          Object.assign(r, { updatedBy: meName(), updatedAt: isoNow() });
          logAct(modul, sub, aksi, refOf(r), r.id, `${aksi} ${sub} ${refOf(r)}${resched ? `: ${n.reschedule[n.reschedule.length - 1].alasan}` : ''}`, resched ? ch.filter(x => x.f !== 'reschedule') : ch);
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
  // ---------- Tutorial highlight driver.js (permintaan BA 12-11-2026; menggantikan callout Panduan) ----------
  // Dimulai dari tombol Tutorial di atas kartu tabel. Helper tS/tRows/tKol/tGear/tAdd ada di core.js; langkah yang elemennya tidak ada dilewati.

  TOUR.actlog = () => [
    tS(null, 'Activity Log', 'Catatan semua perubahan data: siapa, kapan, modul, aksi, dan isi sebelum / sesudah. Tidak dapat diubah atau dihapus.'),
    tS('.toolbar', 'Saring', 'Pilih modul, aksi, user, dan rentang tanggal.'),
    tS('[data-act="log-detail"]', 'Detail', 'Menampilkan field yang berubah, sebelum (merah) dan sesudah (hijau).')
  ];
