<style>
  :root {
    --navy: #14304F;
    --navy-700: #030637;
    --navy-100: #C7D2E2;
    --navy-50: #E4EAF3;
    --orange: #D5A320;
    --orange-600: #B88A12;
    --orange-50: #F7EFD8;
    --ink: #030637;
    --ink-70: #3C4161;
    --ink-50: #6E7390;
    --line: #D3DCE8;
    --surface: #FFFFFF;
    --radius: 12px;
    --shadow: 0 2px 4px rgba(3, 6, 55, .04), 0 16px 36px rgba(3, 6, 55, .08);
  }

  body {
    background: var(--navy-50) !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    color: var(--ink) !important;
    min-height: 100vh;
    margin: 0;
    padding: 0;
  }

  .login-page-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    background: linear-gradient(180deg, #F0F4FA 0%, #E4EAF3 100%);
    position: relative;
  }

  /* Decorative Subtle Grid Lines for Professional Atmosphere */
  .login-page-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
      linear-gradient(to right, rgba(20, 48, 79, 0.04) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(20, 48, 79, 0.04) 1px, transparent 1px);
    background-size: 36px 36px;
    pointer-events: none;
    z-index: 1;
  }

  .login-box-custom {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 440px;
    margin: 0 auto;
  }

  /* Main Elevated Form Card */
  .login-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 36px 32px 30px;
    position: relative;
    overflow: hidden;
  }

  /* Labmania Signature Gold Accent Line */
  .login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--orange) 0%, var(--orange-600) 100%);
  }

  /* Branding Header */
  .brand-header {
    text-align: center;
    margin-bottom: 28px;
  }

  .brand-mark-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: var(--orange);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(213, 163, 32, 0.3);
    margin-bottom: 12px;
    transition: transform 0.2s ease;
  }

  .brand-mark-wrap:hover {
    transform: scale(1.04);
  }

  .brand-mark-letter {
    font-size: 26px;
    font-weight: 800;
    color: var(--ink);
    line-height: 1;
  }

  .brand-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -0.2px;
    margin: 0 0 4px 0;
    line-height: 1.25;
  }

  .brand-subtitle {
    font-size: 13px;
    color: var(--ink-50);
    margin: 0;
    font-weight: 500;
  }

  /* Clean Notification Alert */
  .lab-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.45;
    margin-bottom: 22px;
    position: relative;
  }

  .lab-alert.danger {
    background: #FDF3F3;
    border: 1px solid #F5C6C6;
    color: #9C2525;
  }

  .lab-alert.warning {
    background: var(--orange-50);
    border: 1px solid #EAD49E;
    color: #7A5703;
  }

  .lab-alert.success {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #15803D;
  }

  .lab-alert.info {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #1E40AF;
  }

  .lab-alert-icon {
    font-size: 15px;
    margin-top: 1px;
    flex-shrink: 0;
  }

  .lab-alert-content {
    flex: 1;
    font-weight: 500;
  }

  .lab-alert-close {
    background: none;
    border: none;
    color: currentColor;
    opacity: 0.55;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    padding: 0 2px;
    margin-left: 6px;
    transition: opacity 0.15s ease;
  }

  .lab-alert-close:hover {
    opacity: 1;
  }

  /* Form Controls */
  .form-group-lab {
    margin-bottom: 20px;
    text-align: left;
  }

  .form-label-lab {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 7px;
  }

  .input-wrap-lab {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-icon-lab {
    position: absolute;
    left: 14px;
    color: var(--ink-50);
    font-size: 14px;
    pointer-events: none;
    transition: color 0.15s ease;
    z-index: 2;
  }

  .input-control-lab {
    width: 100%;
    height: 42px;
    padding: 0 14px 0 40px;
    font-size: 14px;
    color: var(--ink);
    background: #FBFDFE;
    border: 1px solid var(--line);
    border-radius: 8px;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    font-family: inherit;
  }

  .input-control-lab::placeholder {
    color: var(--ink-50);
    opacity: 0.8;
  }

  .input-control-lab:focus {
    background: #FFFFFF;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(20, 48, 79, 0.14);
  }

  .input-control-lab:focus ~ .input-icon-lab {
    color: var(--navy);
  }

  /* Password Toggle */
  .btn-pw-toggle {
    position: absolute;
    right: 8px;
    background: transparent;
    border: none;
    color: var(--ink-50);
    font-size: 14px;
    padding: 8px 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: color 0.15s ease, background-color 0.15s ease;
    z-index: 2;
  }

  .btn-pw-toggle:hover {
    color: var(--navy);
    background: var(--navy-50);
  }

  .input-control-lab.has-toggle {
    padding-right: 42px;
  }

  /* Primary Action Button */
  .btn-submit-lab {
    width: 100%;
    height: 44px;
    margin-top: 10px;
    background: var(--navy);
    color: #FFFFFF;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
    box-shadow: 0 2px 6px rgba(20, 48, 79, 0.25);
    font-family: inherit;
    white-space: nowrap;
  }

  .btn-submit-lab:hover {
    background: var(--navy-700);
    box-shadow: 0 4px 12px rgba(3, 6, 55, 0.3);
  }

  .btn-submit-lab:focus-visible {
    outline: 3px solid rgba(213, 163, 32, 0.6);
    outline-offset: 2px;
  }

  .btn-submit-lab:active {
    transform: scale(0.99);
  }

  /* Footer Note */
  .login-footer-text {
    text-align: center;
    margin-top: 24px;
    font-size: 12.5px;
    color: var(--ink-50);
  }

  .login-footer-text p {
    margin: 0;
  }

  @media (max-width: 480px) {
    .login-card {
      padding: 28px 20px 24px;
      border-radius: 10px;
    }
  }
</style>

<div class="login-page-wrapper">
  <div class="login-box-custom">
    
    <div class="login-card">
      
      <!-- Brand Identity Header -->
      <div class="brand-header">
        <div class="brand-mark-wrap" title="Labmania">
          <span class="brand-mark-letter">L</span>
        </div>
        <h1 class="brand-title">Labmania Indonesia</h1>
        <p class="brand-subtitle">Enterprise Resource Planning</p>
      </div>

      <!-- Flash Message & Notification Handler -->
      <?php
      $raw_msg  = '';
      $msg_type = 'danger';

      $flash = $this->session->flashdata('tmessage');
      if (!$flash) {
        $flash = $this->session->userdata('tmessage');
      }

      if ($flash) {
        $parts    = explode('::', $flash, 2);
        $msg_type = isset($parts[0]) ? $parts[0] : 'danger';
        $raw_msg  = isset($parts[1]) ? $parts[1] : $flash;
      } else {
        $tmpl_msg = $this->template->message();
        if (!empty($tmpl_msg)) {
          $clean = preg_replace('/<button.*?<\/button>/is', '', $tmpl_msg);
          $clean = preg_replace('/<i.*?<\/i>/is', '', $clean);
          $clean = preg_replace('/<b.*?<\/b>/is', '', $clean);
          $clean = strip_tags($clean);
          $raw_msg = trim($clean);
          if (stripos($tmpl_msg, 'alert-success') !== false) {
            $msg_type = 'success';
          } elseif (stripos($tmpl_msg, 'alert-info') !== false) {
            $msg_type = 'info';
          } elseif (stripos($tmpl_msg, 'alert-warning') !== false) {
            $msg_type = 'warning';
          } else {
            $msg_type = 'danger';
          }
        }
      }

      if (!empty($raw_msg)) {
        if ($msg_type == 'error') $msg_type = 'danger';
        $iconClass = 'fa fa-exclamation-circle';
        if ($msg_type === 'warning') $iconClass = 'fa fa-exclamation-triangle';
        elseif ($msg_type === 'success') $iconClass = 'fa fa-check-circle';
        elseif ($msg_type === 'info') $iconClass = 'fa fa-info-circle';

        echo "<div class='lab-alert {$msg_type}'>
          <i class='{$iconClass} lab-alert-icon'></i>
          <span class='lab-alert-content'>" . htmlspecialchars($raw_msg) . "</span>
          <button type='button' class='lab-alert-close' onclick='this.parentElement.remove();' aria-label='Tutup' title='Tutup'>&times;</button>
        </div>";
      }
      ?>

      <!-- Authentication Form -->
      <?= form_open($this->uri->uri_string(), array('id' => 'frm_login', 'name' => 'frm_login', 'autocomplete' => 'off')) ?>
        <input type="hidden" name="login" value="1">
        
        <div class="form-group-lab">
          <label class="form-label-lab" for="input_username">Username</label>
          <div class="input-wrap-lab">
            <input 
              type="text" 
              id="input_username"
              name="username" 
              class="input-control-lab" 
              placeholder="Masukkan username Anda" 
              value="<?= set_value('username') ?>" 
              required 
              autofocus
            >
            <i class="fa fa-user input-icon-lab"></i>
          </div>
        </div>

        <div class="form-group-lab">
          <label class="form-label-lab" for="input_password">Password</label>
          <div class="input-wrap-lab">
            <input 
              type="password" 
              id="input_password" 
              name="password" 
              class="input-control-lab has-toggle" 
              placeholder="Masukkan password Anda" 
              value="" 
              required
            >
            <i class="fa fa-lock input-icon-lab"></i>
            <button type="button" class="btn-pw-toggle" id="btnTogglePassword" tabindex="-1" title="Tampilkan/Sembunyikan Password" aria-label="Tampilkan password">
              <i class="fa fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit-lab" name="login" id="btnSubmit">
          <span>Masuk ke Sistem</span>
          <i class="fa fa-sign-in"></i>
        </button>

      <?= form_close() ?>

    </div>

    <!-- Footer Copyright -->
    <div class="login-footer-text">
      <p>&copy; <?= date('Y'); ?> Labmania Indonesia. Seluruh hak cipta dilindungi.</p>
    </div>

  </div>
</div>

<script>
  $(document).ready(function() {
    // Accessible Show/Hide password toggle
    $('#btnTogglePassword').on('click', function(e) {
      e.preventDefault();
      var passwordInput = $('#input_password');
      var toggleIcon = $('#toggleIcon');
      
      if (passwordInput.attr('type') === 'password') {
        passwordInput.attr('type', 'text');
        toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        $(this).attr('aria-label', 'Sembunyikan password');
      } else {
        passwordInput.attr('type', 'password');
        toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        $(this).attr('aria-label', 'Tampilkan password');
      }
    });

    // Clean submission feedback
    $('#frm_login').on('submit', function() {
      var btn = $('#btnSubmit');
      btn.css({
        'opacity': '0.9',
        'pointer-events': 'none'
      }).html('<i class="fa fa-circle-o-notch fa-spin"></i> Memproses...');
    });
  });
</script>
