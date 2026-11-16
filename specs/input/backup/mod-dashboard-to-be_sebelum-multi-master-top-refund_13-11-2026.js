/* Labmania mockup — Dashboard halaman depan (FR-40 - FR-42).
   Bagian dari app.js yang dipecah per modul (05-11-2026). Semua file berbagi lingkup global;
   urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Dashboard halaman depan (FR-40 – FR-42, usulan analis: input/Master - Buat Program/2026-10-28-dashboard-depan/) ----------
  const todayIso = (d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`)(new Date());

  function viewDashboard() {
    const finals = db.quot.filter(x => x.status === 'final');
    const sum = (list, f) => list.reduce((s, x) => s + f(x), 0);
    const pend = sum(finals, x => calc(x).pendapatan), prof = sum(finals, x => calc(x).profit), pes = sum(finals, x => nPeserta(x));
    const margin = pend ? (prof / pend * 100).toFixed(1).replace('.', ',') : '0';
    const drafts = db.mprog.filter(x => x.status === 'draft').length + db.quot.filter(x => x.status === 'draft').length;
    const byJenis = j => finals.filter(x => x.jenis === j);
    const kpi = (k, cls, l, v, sub) => `<div class="kpi dash-link ${cls}" data-act="dash-kpi" data-k="${k}" role="button" tabindex="0" title="Klik untuk melihat rincian">
      <div class="kpi-l">${l} <span class="kpi-more">rincian ›</span></div><div class="kpi-v">${v}</div><div class="kpi-s">${sub}</div></div>`;
    const kpis = `<div class="kpis">
      ${kpi('final', '', 'Total Penjualan Program Final', finals.length, `Public ${byJenis('public').length} · B2B ${byJenis('b2b').length}`)}
      ${kpi('pend', '', 'Total pendapatan rencana', rp(pend), `${pes} peserta = total target peserta dari ${finals.length} Penjualan Final`)}
      ${kpi('prof', prof < 0 ? 'bad' : 'good', 'Total profit rencana', rp(prof), `Pendapatan − biaya (HPP) · margin ${margin}%`)}
      ${kpi('draft', drafts ? 'kwarn' : '', 'Total masih Draft', drafts, 'Master Program + Penjualan Program belum Final')}
    </div>`;

    // Jadwal pelaksanaan terdekat: hanya Penjualan Program Final yang belum lewat (input/Master - Buat Program/2026-10-28-dashboard-jadwal-final/).
    const upcoming = db.quot.filter(x => x.status === 'final' && x.tglSelesai && x.tglSelesai >= todayIso)
      .sort((a, b) => a.tglMulai.localeCompare(b.tglMulai)).slice(0, 5);
    const jadwal = upcoming.length ? `<div class="table-wrap"><table class="table"><thead><tr><th>Tanggal</th><th>Program</th><th>Jenis</th><th class="money">Peserta</th></tr></thead><tbody>
      ${upcoming.map(x => `<tr class="dash-link" data-act="qt-open" data-id="${x.id}">
        <td class="nowrap">${fmtRange(x.tglMulai, x.tglSelesai)}</td>
        <td class="title">${esc(paketOf(mpOf(x)).judul)}<br><span class="muted">${esc(x.nomor)} · ${esc(x.jenis === 'b2b' && companyOf(x) ? companyOf(x).nama : x.lokasi || '—')}</span></td>
        <td>${jenisTag(x.jenis)}</td><td class="money">${nPeserta(x)}</td></tr>`).join('')}
      </tbody></table></div>` : '<p class="dash-empty">Belum ada Penjualan Program Final yang akan dilaksanakan.</p>';

    // Perlu tindakan: Draft yang belum Final, dan penjualan yang rugi / titik impas di atas rencana.
    const todo = [];
    db.mprog.filter(x => x.status === 'draft').forEach(x => todo.push(['mp-open', x.id, x.nomor, paketOf(x).judul, 'Master Program masih Draft — finalkan agar bisa dijual']));
    db.quot.filter(x => x.status !== 'batal').forEach(x => {
      const c = calc(x), judul = paketOf(mpOf(x)).judul;
      if (x.status === 'draft') todo.push(['qt-open', x.id, x.nomor, judul, 'Penjualan Program masih Draft']);
      if (c.pendapatan && c.profit < 0) todo.push(['qt-open', x.id, x.nomor, judul, `Rugi ${rp(c.profit)}`, true]);
      else if (c.bepPeserta === null || c.bepPeserta > c.target) todo.push(['qt-open', x.id, x.nomor, judul, c.bepPeserta === null ? 'Titik impas tidak tercapai' : `Titik impas ${c.bepPeserta} peserta, rencana ${c.target}`, true]);
    });
    const tindakan = todo.length ? `<ul class="dash-list">${todo.slice(0, 8).map(([act, id, no, judul, msg, warn]) =>
      `<li class="dash-link" data-act="${act}" data-id="${id}"><span class="code">${esc(no)}</span> ${esc(judul)}<br><span class="${warn ? 'neg' : 'muted'}">${esc(msg)}</span></li>`).join('')}</ul>
      ${todo.length > 8 ? `<p class="dash-empty">+${todo.length - 8} lainnya</p>` : ''}` : '<p class="dash-empty">Tidak ada yang perlu ditindaklanjuti.</p>';

    const card = (title, body, sub = '') => `<section class="card dash-card"><h2>${title}${sub ? ` <span class="muted">${sub}</span>` : ''}</h2>${body}</section>`;
    return `${pageHead('Dashboard', 'Ringkasan Master Program dan Penjualan Program. Angka adalah total seluruh Penjualan Program Final (rencana).', 'FR-40 – FR-42')}
      ${kpis}
      <div class="dash-grid">
        ${card('Jadwal pelaksanaan terdekat', jadwal, 'Penjualan Program Final · klik baris untuk membuka')}
        ${card('Perlu tindakan', tindakan)}
      </div>`;
  }

  // Rincian kartu Dashboard: dari mana angkanya, per data, dengan baris total (FR-41).
  function dashKpi(k) {
    const finals = db.quot.filter(x => x.status === 'final');
    const sum = (list, f) => list.reduce((t, x) => t + f(x), 0);
    const pj = x => `<span class="code">${esc(x.nomor)}</span> ${esc(paketOf(mpOf(x)).judul)}<br><span class="muted">${jenisTag(x.jenis)} ${esc(x.jenis === 'b2b' && companyOf(x) ? companyOf(x).nama : x.lokasi || '—')} · ${fmtRange(x.tglMulai, x.tglSelesai)}</span>`;
    const row = (act, id, cells) => `<tr class="dash-link" data-act="dash-open" data-to="${act}" data-id="${id}">${cells}</tr>`;
    const tbl = (head, rows, foot = '') => rows.length
      ? `<div class="table-wrap"><table class="table dash-prev"><thead><tr>${head}</tr></thead><tbody>${rows.join('')}</tbody>${foot ? `<tfoot><tr>${foot}</tr></tfoot>` : ''}</table></div>`
      : '<p class="dash-empty">Belum ada data.</p>';
    const src = t => `<p class="dash-src"><b>Sumber data:</b> ${t}</p>`;
    const FINAL_SRC = 'menu <b>Daftar Penjualan Program</b>, hanya yang berstatus <b>Final</b> (Draft dan Batal tidak dihitung). Angka rencana = isian Penjualan Program saat difinalkan.';
    const V = {
      final: () => ['Total Penjualan Program Final', src(FINAL_SRC) + tbl('<th>Penjualan Program</th><th class="money">Target peserta</th>',
        finals.map(x => row('qt', x.id, `<td>${pj(x)}</td><td class="money">${nPeserta(x)}</td>`)),
        `<td><b>Total ${finals.length} penjualan</b> <span class="muted">(Public ${finals.filter(x => x.jenis === 'public').length} · B2B ${finals.filter(x => x.jenis === 'b2b').length})</span></td><td class="money"><b>${sum(finals, nPeserta)}</b></td>`)],
      pend: () => ['Total pendapatan rencana', src(FINAL_SRC) + '<p class="dash-src"><b>Rumus:</b> pendapatan = harga jual / peserta × target peserta, lalu dijumlah semua penjualan.</p>' + tbl(
        '<th>Penjualan Program</th><th class="money">Harga jual / peserta</th><th class="money">Target peserta</th><th class="money">Pendapatan</th>',
        finals.map(x => { const c = calc(x); return row('qt', x.id, `<td>${pj(x)}</td><td class="money">${rp(c.harga)}</td><td class="money">× ${c.target}</td><td class="money">${rp(c.pendapatan)}</td>`); }),
        `<td colspan="2"><b>Total</b></td><td class="money"><b>${sum(finals, nPeserta)}</b></td><td class="money"><b>${rp(sum(finals, x => calc(x).pendapatan))}</b></td>`)],
      prof: () => { const pd = sum(finals, x => calc(x).pendapatan), pf = sum(finals, x => calc(x).profit);
        return ['Total profit rencana', src(FINAL_SRC) + '<p class="dash-src"><b>Rumus:</b> profit = pendapatan − HPP. HPP = Fixed Cost + Variable Cost (bagian 2 & 3 di form Penjualan Program). Margin = profit ÷ pendapatan.</p>' + tbl(
          '<th>Penjualan Program</th><th class="money">Pendapatan</th><th class="money">Fixed Cost</th><th class="money">Variable Cost</th><th class="money">Profit</th><th class="money">Margin</th>',
          finals.map(x => { const c = calc(x); return row('qt', x.id, `<td>${pj(x)}</td><td class="money">${rp(c.pendapatan)}</td><td class="money">− ${rp(c.fixed)}</td><td class="money">− ${rp(c.totalVar)}</td>
            <td class="money ${c.profit < 0 ? 'neg' : 'pos'}">${rp(c.profit)}</td><td class="money">${c.pendapatan ? (c.profit / c.pendapatan * 100).toFixed(1).replace('.', ',') + '%' : '—'}</td>`); }),
          `<td><b>Total</b></td><td class="money"><b>${rp(pd)}</b></td><td class="money"><b>− ${rp(sum(finals, x => calc(x).fixed))}</b></td><td class="money"><b>− ${rp(sum(finals, x => calc(x).totalVar))}</b></td>
           <td class="money ${pf < 0 ? 'neg' : 'pos'}"><b>${rp(pf)}</b></td><td class="money"><b>${pd ? (pf / pd * 100).toFixed(1).replace('.', ',') + '%' : '—'}</b></td>`)]; },
      draft: () => { const mp = db.mprog.filter(x => x.status === 'draft'), qt = db.quot.filter(x => x.status === 'draft');
        return ['Total masih Draft', src('menu <b>Daftar Master Program</b> dan <b>Daftar Penjualan Program</b> yang berstatus <b>Draft</b> (belum difinalkan). Klik baris untuk melanjutkan.') + tbl(
          '<th>Data</th><th>Jenis data</th>',
          mp.map(x => row('mp', x.id, `<td><span class="code">${esc(x.nomor)}</span> ${esc(paketOf(x).judul)}</td><td>Master Program</td>`))
            .concat(qt.map(x => row('qt', x.id, `<td>${pj(x)}</td><td>Penjualan Program</td>`))),
          `<td><b>Total ${mp.length + qt.length}</b></td><td class="muted">Master Program ${mp.length} · Penjualan Program ${qt.length}</td>`)]; }
    };
    const [title, body] = V[k]();
    openModal({ title: `Rincian — ${title}`, body: body + '<p class="dash-src muted">Klik baris untuk membuka datanya.</p>', wide: 'x', info: true });
  }

