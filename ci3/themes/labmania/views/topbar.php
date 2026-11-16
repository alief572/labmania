<div class="main">
  <header class="topbar">
    <button class="menu-btn" id="menu-btn" aria-label="Buka menu">
      <span></span><span></span><span></span>
    </button>
    <div class="crumb" id="crumb">
      <?php
        $crumb_text = '';
        if (isset($breadcrumbs) && is_string($breadcrumbs) && trim($breadcrumbs) !== '') {
            $crumb_text = $breadcrumbs;
        } elseif (!empty($template['breadcrumbs']) && is_array($template['breadcrumbs'])) {
            $parts = [];
            $total = count($template['breadcrumbs']);
            $i = 0;
            foreach ($template['breadcrumbs'] as $crumb) {
                $i++;
                if (is_array($crumb)) {
                    $name = isset($crumb['name']) ? $crumb['name'] : '';
                    $uri  = isset($crumb['uri']) ? $crumb['uri'] : '';
                    if ($i === $total) {
                        $parts[] = '<b>' . htmlspecialchars($name) . '</b>';
                    } elseif (!empty($uri)) {
                        $parts[] = '<a href="' . site_url($uri) . '">' . htmlspecialchars($name) . '</a>';
                    } else {
                        $parts[] = htmlspecialchars($name);
                    }
                } elseif (is_string($crumb)) {
                    $parts[] = ($i === $total) ? '<b>' . htmlspecialchars($crumb) . '</b>' : htmlspecialchars($crumb);
                }
            }
            $crumb_text = implode(' / ', $parts);
        } elseif (!empty($template['title']) && is_string($template['title'])) {
            $crumb_text = '<b>' . htmlspecialchars($template['title']) . '</b>';
        } else {
            $crumb_text = '<b>Dashboard</b>';
        }
        echo $crumb_text;
      ?>
    </div>
    <span class="pill" title="Sistem terhubung ke database Labmania ERP">Labmania ERP</span>
  </header>
  <main class="content" id="view">
