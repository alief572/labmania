<div class="page-head">
  <div>
    <h1>Activity Log <span class="ref">LOG-FR-03 – LOG-FR-07</span></h1>
    <p>Semua pergerakan data di sistem: siapa, kapan (tanggal & jam), modul, aksi, dan perbandingan perubahan nilai sebelum (Before) &rarr; sesudah (After).</p>
  </div>
</div>

<?php if (!empty($filters['ref_no'])): ?>
<div class="log-scope">
  <span>Riwayat data <b><?= htmlspecialchars($filters['ref_no']); ?></b></span>
  <a href="<?= site_url('activity_log_labmania'); ?>" class="btn btn-muted sm">Tampilkan semua</a>
</div>
<?php endif; ?>

<div class="card">
  <!-- TOOLBAR FILTERS -->
  <div class="toolbar" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
      <!-- Filter Modul -->
      <select class="input sm" id="lf-module" onchange="applyLogFilters()" style="width:160px;">
        <option value="all">Semua Modul</option>
        <?php foreach ($modules as $m): ?>
          <option value="<?= htmlspecialchars($m['module']); ?>" <?= ($filters['module'] === $m['module']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($m['module']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Filter Aksi -->
      <select class="input sm" id="lf-action" onchange="applyLogFilters()" style="width:140px;">
        <option value="all">Semua Aksi</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= htmlspecialchars($a['action']); ?>" <?= ($filters['action'] === $a['action']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($a['action']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Tanggal Range -->
      <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--muted);">
        Dari: <input type="date" class="input sm" id="lf-from" value="<?= htmlspecialchars($filters['from']); ?>" onchange="applyLogFilters()">
      </label>
      <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--muted);">
        s.d.: <input type="date" class="input sm" id="lf-to" value="<?= htmlspecialchars($filters['to']); ?>" onchange="applyLogFilters()">
      </label>
    </div>

    <!-- Search Keyword -->
    <div class="search" style="min-width:240px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input sm" id="table-search" placeholder="Cari dokumen, user, ket..." value="<?= htmlspecialchars($filters['keyword']); ?>" aria-label="Cari">
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt>
      <thead>
        <tr>
          <th style="width: 45px;">No</th>
          <th style="width: 140px;">Waktu</th>
          <th style="width: 130px;">Pengguna</th>
          <th style="width: 140px;">Modul</th>
          <th style="width: 100px;">Aksi</th>
          <th style="width: 130px;">Data Dokumen</th>
          <th>Ringkasan Perubahan</th>
          <th style="width: 80px;" class="act">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($logs)): $no = 1; foreach ($logs as $l): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td style="font-size:12px;">
              <b><?= date('d/m/Y', strtotime($l['created_date'])); ?></b><br>
              <small class="muted"><?= date('H:i:s', strtotime($l['created_date'])); ?> WIB</small>
            </td>
            <td>
              <b><?= htmlspecialchars($l['nm_lengkap'] ?: ($l['username'] ?: 'System')); ?></b><br>
              <small class="muted"><?= htmlspecialchars($l['ip_address'] ?? ''); ?></small>
            </td>
            <td>
              <b><?= htmlspecialchars($l['module']); ?></b>
              <?php if (!empty($l['sub_module'])): ?>
                <br><small class="muted"><?= htmlspecialchars($l['sub_module']); ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php 
                $a = strtolower($l['action']);
                if (in_array($a, ['tambah', 'create', 'deal'])): 
              ?>
                <span class="badge on"><?= htmlspecialchars($l['action']); ?></span>
              <?php elseif (in_array($a, ['edit', 'update', 'terkirim'])): ?>
                <span class="badge" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba;"><?= htmlspecialchars($l['action']); ?></span>
              <?php elseif (in_array($a, ['finalkan', 'final'])): ?>
                <span class="badge on" style="background:#10b981;"><?= htmlspecialchars($l['action']); ?></span>
              <?php else: ?>
                <span class="badge off"><?= htmlspecialchars($l['action']); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <span class="code" style="font-weight:700;"><?= htmlspecialchars($l['ref_no'] ?: ('#' . $l['ref_id'])); ?></span>
            </td>
            <td style="font-size:12px;">
              <?php if (!empty($l['parsed_diff'])): ?>
                <?php 
                  $preview = array_slice($l['parsed_diff'], 0, 2); 
                  foreach ($preview as $df):
                ?>
                  <div>
                    <b><?= htmlspecialchars($df['field']); ?>:</b> 
                    <span style="color:#ef4444; background:#fee2e2; padding:1px 4px; border-radius:3px;"><?= htmlspecialchars(mb_strimwidth($df['old'], 0, 30, '...')); ?></span> &rarr; 
                    <span style="color:#10b981; background:#dcfce7; padding:1px 4px; border-radius:3px;"><?= htmlspecialchars(mb_strimwidth($df['new'], 0, 30, '...')); ?></span>
                  </div>
                <?php endforeach; ?>
                <?php if (count($l['parsed_diff']) > 2): ?>
                  <small class="muted">+ <?= count($l['parsed_diff']) - 2; ?> field lainnya</small>
                <?php endif; ?>
              <?php else: ?>
                <span class="muted"><?= htmlspecialchars($l['description']); ?></span>
              <?php endif; ?>
            </td>
            <td class="act">
              <button type="button" class="btn btn-muted sm" onclick="showLogDetail(<?= $l['id']; ?>)">Detail</button>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr>
            <td colspan="8" style="text-align: center; color: var(--muted); padding: 32px;">
              Belum ada riwayat aktivitas data yang sesuai filter.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


<script>

function applyLogFilters() {
  const module = document.getElementById('lf-module').value;
  const action = document.getElementById('lf-action').value;
  const from = document.getElementById('lf-from').value;
  const to = document.getElementById('lf-to').value;
  const q = document.getElementById('table-search').value.trim();

  const params = [];
  if (module && module !== 'all') params.push('module=' + encodeURIComponent(module));
  if (action && action !== 'all') params.push('action=' + encodeURIComponent(action));
  if (from) params.push('from=' + encodeURIComponent(from));
  if (to) params.push('to=' + encodeURIComponent(to));
  if (q) params.push('q=' + encodeURIComponent(q));
  const refNo = <?= json_encode($filters['ref_no']); ?>, refTable = <?= json_encode($filters['ref_table']); ?>;
  if (refNo && refTable) { params.push('ref_table=' + encodeURIComponent(refTable)); params.push('ref_no=' + encodeURIComponent(refNo)); }

  window.location.href = '<?= site_url("activity_log_labmania"); ?>' + (params.length ? '?' + params.join('&') : '');
}

function showLogDetail(id) {
  fetch('<?= site_url("activity_log_labmania/detail/"); ?>' + id)
    .then(res => res.json())
    .then(res => {
      if (res.status === 1) {
        const d = res.data;
        const rows = (d.parsed_diff && d.parsed_diff.length)
          ? d.parsed_diff.map(df => `<tr><td>${escapeHtml(df.field)}</td><td class="log-old">${escapeHtml(df.old || '—')}</td><td class="log-new">${escapeHtml(df.new || '—')}</td></tr>`).join('')
          : '<tr><td colspan="3" class="muted">Tidak ada perubahan field (aksi baca / cetak).</td></tr>';

        openModal(`<div class="overlay" data-log-close>
          <form class="modal wide" novalidate role="dialog" aria-modal="true">
            <header><h2>Activity Log — ${escapeHtml(d.action)} ${escapeHtml(d.ref_no || ('#' + d.ref_id))}</h2><button type="button" class="x" data-log-close aria-label="Tutup">×</button></header>
            <div class="modal-body">
              <div class="summary">
                <div class="sum-row"><span>Waktu</span><b>${escapeHtml(d.created_date)} WIB</b></div>
                <div class="sum-row"><span>Pengguna</span><b>${escapeHtml(d.nm_lengkap || d.username || '—')} <span class="muted">(${escapeHtml(d.ip_address || '—')})</span></b></div>
                <div class="sum-row"><span>Modul</span><b>${escapeHtml(d.module)}${d.sub_module ? ' › ' + escapeHtml(d.sub_module) : ''}</b></div>
                <div class="sum-row"><span>Aksi</span><b>${escapeHtml(d.action)}</b></div>
                <div class="sum-row"><span>Keterangan</span><b>${escapeHtml(d.description)}</b></div>
              </div>
              <div class="table-wrap">
                <table class="table log-diff">
                  <thead><tr><th>Field</th><th>Sebelum</th><th>Sesudah</th></tr></thead>
                  <tbody>${rows}</tbody>
                </table>
              </div>
            </div>
            <footer><button type="button" class="btn btn-primary" data-log-close>Tutup</button></footer>
          </form>
        </div>`);

        document.querySelectorAll('[data-log-close]').forEach(el => el.addEventListener('click', function (e) {
          if (el.classList.contains('overlay') && e.target !== el) return;
          closeModal();
        }));
      } else {
        toast(res.msg, 'warn');
      }
    });
}

function escapeHtml(text) {
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
