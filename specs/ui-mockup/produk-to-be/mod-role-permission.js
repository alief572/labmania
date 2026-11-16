/* Labmania mockup — Role & Permission (dipakai semua paket).
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Role & Permission (FR-54 – FR-58; input/Invoice & Pengajuan/2026-11-04-role-permission/, video Role & Permission.mp4) ----------
  // Role dari Master Role (FR-64). Hak per role atas setiap menu disimpan per id role:
  // View / Add / Manage / Delete (konvensi permission aplikasi Nama_Menu.View dst.). Tampilan mengikuti video: daftar role + modal matrix.
  // Approve terpisah dari Manage (Q-33, input/Master - Buat Program/2026-11-12-jawaban-pertanyaan-terbuka/): Finalkan Master/Penjualan Program dan proses Approval Pengajuan.
  const AK_ACTS = [['v', 'View'], ['a', 'Add'], ['m', 'Manage'], ['d', 'Delete'], ['p', 'Approve']];
  const ADMIN_ID = roleIdOf('Admin'); // Admin = akun user Labmania: menu mengikuti hak role (dapat dibatasi), data semua (Q-34; Q-35)
  const MGMT_IDS = [roleIdOf('Direktur')]; // Manajemen sementara = role Direktur (Q-PB-14, Q-37)
  const meUser = () => db.user.find(u => u.id === state.me) || {};
  const meRole = () => roleOf(meUser());
  const isSuper = () => meRole() === SUPER_ROLE;
  const isAdmin = () => meUser().role === ADMIN_ID;
  const isMgmt = () => MGMT_IDS.includes(meUser().role);
  const seeAllData = () => isSuper() || isAdmin();
  const akMenus = () => [{ mod: 'Dashboard', items: [['dashboard', 'Dashboard']] }].concat(MODULES.map(m => ({ mod: m.name, items: m.groups.flatMap(g => g.items) })));
  // Aksi yang berlaku per menu (usulan): Dashboard, Katalog, Activity Log hanya View; Approval Pengajuan View & Manage (Manage = proses approval); Manage Peserta View & Manage (Manage = Close Program).
  const akActsOf = v => ['dashboard', 'katalog', 'actlog'].includes(v) ? ['v'] : v === 'pbAppr' || v === 'rfAppr' ? ['v', 'p'] : v === 'refund' ? ['v', 'a', 'm'] : v === 'mpst' ? ['v', 'm']
    : ['mprog', 'quot'].includes(v) ? ['v', 'a', 'm', 'd', 'p'] : ['v', 'a', 'm', 'd'];
  const akFull = v => Object.fromEntries(akActsOf(v).map(x => [x, true]));
  const akAllKeys = () => akMenus().flatMap(m => m.items.map(([v]) => v));
  const akTotal = () => akAllKeys().reduce((s, v) => s + akActsOf(v).length, 0);
  const akCount = m => Object.keys(m || {}).reduce((s, v) => s + akActsOf(v).filter(k => (m[v] || {})[k]).length, 0);
  const akCode = (v, k) => `${v}.${AK_ACTS.find(a => a[0] === k)[1].toLowerCase()}`;
  // Isi awal (FR-58, usulan Q-33) — per role contoh.
  db.akses = {};
  {
    const all = except => Object.fromEntries(akAllKeys().filter(v => !except.includes(v)).map(v => [v, akFull(v)]));
    const staff = () => ({ dashboard: { v: true }, qtn: { v: true, a: true, m: true }, so: { v: true, m: true }, pb: { v: true, a: true } });
    // Sales/Marketing: kelola Master Peserta, Company, Rate, Katalog (Q-06); susun, finalkan, batalkan program (Q-MP-01, Q-PJ-02); Close Program (Q-QTN-09).
    const sales = () => Object.assign(staff(), { peserta: akFull('peserta'), company: akFull('company'), rate: akFull('rate'), katalog: akFull('katalog'),
      mprog: akFull('mprog'), quot: akFull('quot'), mpst: akFull('mpst'),
      // Refund ditolak diperbaiki Sales/Marketing lalu diajukan ulang (Add = Ajukan ulang) ([JMR-5]).
      refund: { v: true, a: true } });
    db.akses[roleIdOf('Direktur')] = all(['akses']);
    db.akses[ADMIN_ID] = all(['actlog']); // Activity Log mengikuti LOG-FR-09; dapat diberikan lewat Role & Permission
    db.akses[roleIdOf('Sales Public')] = sales();
    db.akses[roleIdOf('Sales B2B')] = sales();
    db.akses[roleIdOf('Trainer')] = staff();
    // Keuangan: Invoicing, Approval Refund ([JMR-4]: Finance yang menyetujui), dan proses transfer Refund.
    db.akses[roleIdOf('Keuangan')] = { dashboard: { v: true }, ivc: akFull('ivc'), refund: { v: true, m: true }, rfAppr: akFull('rfAppr') };
  }
  // Hak user = hak role-nya + Override Permission user itu (FR-65; user_permissions). Override hanya menambah.
  const can = (v, act = 'v') => isSuper() || !!(((db.akses[meUser().role] || {})[v] || {})[act]) || !!(((meUser().ovr || {})[v] || {})[act]);
  // Contoh user role Admin (akun user Labmania; Q-34).
  db.user.push({ id: uid(), kode: 'EMP-005', nama: 'Contoh Admin', nik: '', tmpLahir: '', tglLahir: '', role: ADMIN_ID, gender: 'Perempuan', agama: '',
    telp: '+6281355550000', email: 'admin@contoh.id', alamat: '', npwp: '', tglMasuk: '2024-01-02', tipe: 'Tetap', jobdesc: 'Admin aplikasi: semua akses, Role & Permission', aktif: true,
    akun: true, username: 'admin' });

  const viewAkses = () => {
    const roles = db.role;
    const kpi = (l, v, sub) => `<div class="kpi"><div class="kpi-l">${l}</div><div class="kpi-v">${v}</div><div class="muted">${sub}</div></div>`;
    return `${pageHead('Role & Permission', 'Role diambil dari <b>Master Role</b>. Atur setiap role: menu apa saja yang bisa dibuka dan apa yang bisa dilakukan di menu itu — <b>View</b> = membuka menu & daftar, <b>Add</b> = tambah / ajukan, <b>Manage</b> = ubah, <b>Delete</b> = hapus / batalkan / nonaktifkan, <b>Approve</b> = finalkan program dan proses approval pengajuan. Hak tambahan untuk satu user diatur lewat <b>Override Permission</b> di Master User. Hak <b>data</b> (mis. hanya program terkait di Pengajuan Biaya) diatur di modulnya.', 'FR-54 – FR-58')}
      <div class="kpis" style="margin-bottom:14px">${kpi('Total Role', roles.length, 'dari Master Role')}${kpi('User berakun', db.user.filter(u => u.akun).length, 'tersebar di semua role')}${kpi('Menu diatur', akAllKeys().length, `${akTotal()} hak (menu × aksi)`)}</div>
      <div class="card">${toolbar('Cari nama role…', false)}<div id="list">${listAkses()}</div>
      <p class="hint" style="padding:0 16px 14px">Tambah, ubah nama, dan hapus role dilakukan di <b>Master Role</b>; role baru belum punya hak sampai diatur di sini. <b>Superadmin</b> dibuat dari awal (bawaan sistem) dan tidak dapat ditambah lagi${q('Q-35', 'Terjawab: Superadmin bawaan sistem; role tanpa user boleh dihapus; hak per user lewat Override Permission di Master User. Masih terbuka: role mana yang menjadi Admin (mockup: role Admin)?')}. Isi awal hak: usulan${q('Q-33', 'Terjawab 12-11-2026: perlu hak Approve terpisah. Approve berlaku untuk Finalkan Master Program, Finalkan Penjualan Program, dan Approval Pengajuan. Menu lain yang perlu Approve masih dikonfirmasi.')}.</p></div>`;
  };
  function listAkses() {
    const rows = db.role.filter(r => matches([r.nama]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Role', render: x => x.sistem ? `<b>${esc(x.nama)}</b> <span class="tag">Full Access</span><br><span class="muted">Role bawaan sistem (akun developer), dibuat dari awal, tidak dapat ditambah lagi</span>`
        : `<b>${esc(x.nama)}</b>${x.id === ADMIN_ID ? ' <span class="tag">Admin</span>' : ''}<br><span class="muted">Master Role</span>` },
      { label: 'User terdaftar', cls: 'nowrap', render: x => { const us = roleUsers(x.id), o = us.filter(u => akCount(u.ovr)).length; return `${us.length} user${o ? `<br><span class="muted">${o} dengan override</span>` : ''}`; } },
      { label: 'Total hak', cls: 'nowrap', render: x => x.sistem ? '<span class="badge on">Semua hak aktif</span>'
        : akCount(db.akses[x.id]) ? `<span class="badge on">${akCount(db.akses[x.id])} hak diberikan</span>` : '<span class="badge off">Belum diatur</span>' },
      { label: 'Cakupan (preview)', render: x => {
        if (x.sistem) return '<span class="muted"><i>Otomatis memiliki semua hak sistem</i></span>';
        const codes = akAllKeys().flatMap(v => akActsOf(v).filter(k => ((db.akses[x.id] || {})[v] || {})[k]).map(k => akCode(v, k)));
        return codes.length ? `<div class="chips">${codes.slice(0, 3).map(c => `<span class="chip code">${esc(c)}</span>`).join('')}${codes.length > 3 ? `<span class="chip">+${codes.length - 3} lagi</span>` : ''}</div>` : '<span class="muted">—</span>';
      } },
      { label: 'Action', cls: 'act nowrap', render: x => gearMenu({ id: x.id }, [['ak-edit', x.sistem ? 'Lihat' : 'Atur Akses']]) }
    ]);
  }
  // Modal Edit Role & Permissions (video): matrix per menu, dikelompokkan per modul; Pilih Semua Modul / Kosongkan / cari.
  function akEdit(id) {
    const rr = roleById(id), role = rr.nama, locked = !!rr.sistem, D = clone(db.akses[id] || {}), total = akTotal();
    let cari = '';
    const on = (v, k) => locked || !!((D[v] || {})[k]);
    const matrix = () => {
      const s = cari.toLowerCase();
      const body = akMenus().map(m => {
        const items = m.items.filter(([v, l]) => !s || [m.mod, l, v].some(t => t.toLowerCase().includes(s)) || akActsOf(v).some(k => akCode(v, k).includes(s)));
        if (!items.length) return '';
        return `<tr class="ak-mod"><td colspan="4">${esc(m.mod)}</td></tr>` + items.map(([v, l]) => {
          const acts = akActsOf(v), n = acts.filter(k => on(v, k)).length, full = n === acts.length;
          return `<tr><td><label class="ak-menu"><input type="checkbox" data-prow="${v}" ${full ? 'checked' : ''} ${locked ? 'disabled' : ''}><span><b>${esc(l)}</b><br><span class="muted code">${esc(v)} · ${acts.length} hak</span></span></label></td>
            <td><div class="ak-chips">${acts.map(k => `<label class="ak-chip ${on(v, k) ? 'on' : ''}"><input type="checkbox" data-pk="${v}" data-pa="${k}" ${on(v, k) ? 'checked' : ''} ${locked ? 'disabled' : ''}>${AK_ACTS.find(a => a[0] === k)[1]} <span class="code">${esc(akCode(v, k))}</span></label>`).join('')}</div></td>
            <td class="ak-c">${locked ? '' : `<button type="button" class="btn btn-ghost sm" data-prow-btn="${v}">${full ? 'Batal Semua' : 'Pilih Semua'}</button>`}</td>
            <td class="ak-c"><span class="ak-scope ${n ? 'on' : ''}">${n} / ${acts.length}</span></td></tr>`;
        }).join('');
      }).join('');
      return body || '<tr><td colspan="4" class="empty">Menu tidak ditemukan.</td></tr>';
    };
    const cnt = () => locked ? total : akCount(D);
    const body = `${field('Nama Role', `<input class="input" value="${esc(role)}" readonly><span class="hint">Dari Master Role; nama diubah di sana. ${roleUsers(id).length} user memakai role ini.</span>`)}
      ${locked ? '<div class="notice"><span><b>Superadmin</b> (akun developer) — semua akses dan semua data; tidak dapat diubah (FR-56).</span></div>' : ''}
      <div class="ak-bar"><b>Matrix Hak Akses</b> <span class="tag" data-akcnt>${cnt()} dari ${total} dipilih</span>
        <span class="ak-bar-r">${locked ? '' : '<button type="button" class="btn btn-ghost sm" data-akall="1">Pilih Semua Modul</button><button type="button" class="btn btn-ghost sm" data-akall="0">Kosongkan</button>'}
        <input type="search" class="input sm" data-akq placeholder="Cari menu / hak…" aria-label="Cari menu atau hak"></span></div>
      <div class="table-wrap ak-wrap"><table class="table ak-tbl"><thead><tr><th>Modul / Menu</th><th>Hak akses / aksi</th><th class="ak-c">Per menu</th><th class="ak-c">Scope</th></tr></thead><tbody data-akm>${matrix()}</tbody></table></div>
      <p class="hint">Total: <b data-aktot>${cnt()}</b> hak dipilih. Mencentang Add / Manage / Delete otomatis mencentang View; melepas View melepas semua hak menu itu.</p>`;
    const refresh = form => {
      form.querySelector('[data-akm]').innerHTML = matrix();
      form.querySelector('[data-akcnt]').textContent = `${cnt()} dari ${total} dipilih`;
      form.querySelector('[data-aktot]').textContent = cnt();
    };
    const setRow = (v, val) => { if (val) D[v] = akFull(v); else delete D[v]; };
    openModal({
      title: locked ? `Role ${esc(role)}` : `Atur Akses Role ${esc(role)}`,
      wide: 'x', info: locked, saveLabel: 'Simpan Role',
      body,
      onMount: form => {
        form.addEventListener('change', e => {
          const t = e.target;
          if (t.dataset.prow) setRow(t.dataset.prow, t.checked);
          else if (t.dataset.pk) {
            const v = t.dataset.pk, k = t.dataset.pa, r = D[v] = D[v] || {};
            r[k] = t.checked;
            if (t.checked && k !== 'v') r.v = true;
            if (!t.checked && k === 'v') Object.keys(r).forEach(x => { r[x] = false; });
            Object.keys(r).forEach(x => { if (!r[x]) delete r[x]; });
            if (!Object.keys(r).length) delete D[v];
          } else return;
          refresh(form);
        });
        form.addEventListener('click', e => {
          const b = e.target.closest('[data-prow-btn], [data-akall]');
          if (!b) return;
          if (b.dataset.akall) akAllKeys().forEach(v => setRow(v, b.dataset.akall === '1'));
          else { const v = b.dataset.prowBtn; setRow(v, akActsOf(v).some(k => !(D[v] || {})[k])); }
          refresh(form);
        });
        form.querySelector('[data-akq]').addEventListener('input', e => { cari = e.target.value; form.querySelector('[data-akm]').innerHTML = matrix(); });
      },
      onSave: () => {
        const before = db.akses[id] || {};
        const lbl = Object.fromEntries(akMenus().flatMap(m => m.items));
        const fmt = r => AK_ACTS.filter(([k]) => r && r[k]).map(([, l]) => l).join(', ') || '—';
        const changes = akAllKeys().filter(v => fmt(before[v]) !== fmt(D[v])).map(v => ({ f: lbl[v], old: fmt(before[v]), new: fmt(D[v]) }));
        db.akses[id] = clone(D);
        logAct('Master Data', 'Role & Permission', 'Edit', role, id, `Edit Role & Permission ${role}`, changes);
        toast(`Hak akses role ${role} disimpan — ${changes.length} menu berubah`);
      }
    });
  }
  // ---------- Tutorial highlight driver.js (permintaan BA 12-11-2026; menggantikan callout Panduan) ----------
  // Dimulai dari tombol Tutorial di atas kartu tabel. Helper tS/tRows/tKol/tGear/tAdd ada di core.js; langkah yang elemennya tidak ada dilewati.

  TOUR.akses = () => [
    tS(null, 'Role & Permission', 'Mengatur menu dan aksi untuk setiap role: <b>View</b> (buka menu), <b>Add</b> (tambah / ajukan), <b>Manage</b> (ubah, finalkan, proses), <b>Delete</b> (hapus / batalkan).'),
    tS('#view .kpis', 'Ringkasan', 'Jumlah role, user berakun, dan menu yang diatur.'),
    tS(tGear(), 'Aksi: Atur Akses', 'Centang hak per menu untuk role itu. Role Superadmin hanya bisa dilihat. Hak tambahan satu orang diatur lewat Override Permission di Master User.')
  ];
