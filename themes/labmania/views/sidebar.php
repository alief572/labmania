<?php
$ci = &get_instance();
$module = $ci->router->fetch_module();
$class = $ci->router->fetch_class();
$method = $ci->router->fetch_method();

// User info
$userData = $ci->session->userdata('app_session');
$userName = !empty($userData['nm_lengkap']) ? $userData['nm_lengkap'] : (!empty($userData['username']) ? $userData['username'] : 'User Labmania');
$userRole = !empty($userData['karyawan']['nama']) ? $userData['karyawan']['nama'] : 'Administrator';
?>
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
  <div class="brand">
    <span class="brand-mark">L</span>
    <div>
      <strong>Labmania</strong>
      <small>ERP · Modul Program</small>
    </div>
  </div>

  <div class="nav-search">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" id="nav-q" placeholder="Cari menu…" autocomplete="off" aria-label="Cari menu">
  </div>

  <nav id="nav">
<?php
$ci->load->helper('akses');
$counts = lm_menu_counts();
$uri = trim($ci->uri->uri_string(), '/');
// Menu aktif: URI sama atau turunannya; link satu segmen = nama modul, aktif di semua halaman modul itu
$isActive = function ($link) use ($uri, $module) {
    if ($uri === $link || strpos($uri, $link . '/') === 0) return true;
    return strpos($link, '/') === false && $module === $link;
};
foreach (lm_menu_tree() as $node):
  if (!$node['modul']): ?>
    <a href="<?= site_url($node['link']); ?>" class="nav-module-head nav-home <?= $isActive($node['link']) ? 'active' : ''; ?>">
      <?= lm_icon_svg($node['ikon']); ?>
      <span><?= htmlspecialchars($node['judul']); ?></span>
    </a>
<?php else:
    $open = false;
    foreach ($node['groups'] as $g) foreach ($g as $m) if ($isActive($m['link'])) $open = true; ?>
    <div class="nav-module <?= $open ? 'open' : ''; ?>">
      <button class="nav-module-head" data-module="<?= htmlspecialchars($node['judul']); ?>" aria-expanded="<?= $open ? 'true' : 'false'; ?>">
        <?= lm_icon_svg($node['ikon']); ?>
        <span><?= htmlspecialchars($node['judul']); ?></span>
        <i class="caret"></i>
      </button>
      <div class="nav-module-body">
        <?php foreach ($node['groups'] as $g): ?>
        <div class="nav-group">
          <?php foreach ($g as $m): ?>
          <a href="<?= site_url($m['link']); ?>" class="nav-item <?= $isActive($m['link']) ? 'active' : ''; ?>"><?= htmlspecialchars($m['judul']); ?><?php if (isset($counts[$m['kode']])): ?><span class="nav-count"><?= $counts[$m['kode']]; ?></span><?php endif; ?></a>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
<?php endif;
endforeach; ?>
  </nav>

  <div class="sidebar-foot">
    <div id="me-box" class="me-box">
      <div class="avatar"><?= strtoupper(substr($userName, 0, 1)); ?></div>
      <div class="info">
        <div class="name"><?= htmlspecialchars($userName); ?></div>
        <div class="role"><?= htmlspecialchars($userRole); ?></div>
      </div>
      <a href="<?= site_url('users/logout'); ?>" class="btn-logout" title="Keluar / Logout" aria-label="Keluar / Logout">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
    <div class="sidebar-meta">
      Labmania ERP · v1.0<br>
      Acuan: <code>specs/final/</code>
    </div>
  </div>
</aside>
<div class="scrim"></div>
