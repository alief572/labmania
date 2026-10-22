<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    background: #1e293b;
    overflow-x: hidden;
  }

  .login-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    width: 100%;
    padding: 24px;
    /* AdminLTE Theme Colors: Deep Dark (#222d32, #1a2226) & Brand Blue (#3c8dbc) */
    background: linear-gradient(135deg, #1a2226 0%, #222d32 45%, #183756 100%);
    position: relative;
    overflow: hidden;
  }

  /* Decorative Ambient Color Glow */
  .login-wrapper::before,
  .login-wrapper::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.28;
    pointer-events: none;
  }

  .login-wrapper::before {
    width: 400px;
    height: 400px;
    background: #3c8dbc;
    top: -80px;
    left: -80px;
  }

  .login-wrapper::after {
    width: 450px;
    height: 450px;
    background: #00c0ef;
    bottom: -100px;
    right: -100px;
  }

  /* Login Container */
  .login-container {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  /* Crisp Professional Card */
  .login-card {
    width: 100%;
    background: #ffffff;
    border-radius: 16px;
    padding: 36px 30px 30px;
    box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
    animation: cardAppear 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }

  /* Top Accent Bar (AdminLTE Blue & Aqua) */
  .login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #3c8dbc 0%, #00c0ef 100%);
  }

  @keyframes cardAppear {
    from {
      opacity: 0;
      transform: translateY(18px) scale(0.98);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  /* Header Branding */
  .login-header {
    text-align: center;
    margin-bottom: 24px;
  }

  .brand-logo-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(60, 141, 188, 0.12) 0%, rgba(0, 192, 239, 0.1) 100%);
    border: 1px solid rgba(60, 141, 188, 0.25);
    margin-bottom: 14px;
  }

  .brand-logo-wrap i {
    font-size: 26px;
    color: #3c8dbc;
  }

  .login-title {
    font-size: 21px;
    font-weight: 700;
    color: #222d32;
    letter-spacing: -0.01em;
    margin-bottom: 4px;
  }

  .login-subtitle {
    font-size: 13px;
    color: #64748b;
    font-weight: 400;
  }

  /* Clean, Modern Alert Notification */
  .custom-login-alert {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    margin-bottom: 20px !important;
    padding: 12px 14px !important;
    border-radius: 10px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    line-height: 1.4 !important;
    position: relative !important;
    animation: alertSlideIn 0.3s ease forwards !important;
  }

  @keyframes alertSlideIn {
    from {
      opacity: 0;
      transform: translateY(-6px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .custom-login-alert .alert-icon {
    font-size: 16px !important;
    flex-shrink: 0 !important;
    position: static !important;
    margin: 0 !important;
  }

  .custom-login-alert .alert-text {
    flex: 1 !important;
    color: inherit !important;
    font-weight: 500 !important;
  }

  .custom-login-alert .alert-close-btn {
    background: none !important;
    border: none !important;
    color: currentColor !important;
    opacity: 0.5 !important;
    font-size: 18px !important;
    line-height: 1 !important;
    cursor: pointer !important;
    padding: 0 4px !important;
    transition: opacity 0.2s ease !important;
    display: flex !important;
    align-items: center !important;
    margin-left: auto !important;
  }

  .custom-login-alert .alert-close-btn:hover {
    opacity: 1 !important;
  }

  .custom-login-alert.danger,
  .custom-login-alert.error {
    background: #fef2f2 !important;
    color: #dc2626 !important;
    border: 1px solid #fecaca !important;
  }
  .custom-login-alert.danger .alert-icon,
  .custom-login-alert.error .alert-icon {
    color: #ef4444 !important;
  }

  .custom-login-alert.success {
    background: #f0fdf4 !important;
    color: #16a34a !important;
    border: 1px solid #bbf7d0 !important;
  }
  .custom-login-alert.success .alert-icon {
    color: #22c55e !important;
  }

  .custom-login-alert.info {
    background: #f0f9ff !important;
    color: #0284c7 !important;
    border: 1px solid #bae6fd !important;
  }
  .custom-login-alert.info .alert-icon {
    color: #0ea5e9 !important;
  }

  .custom-login-alert.warning {
    background: #fffbeb !important;
    color: #b45309 !important;
    border: 1px solid #fde68a !important;
  }
  .custom-login-alert.warning .alert-icon {
    color: #f59e0b !important;
  }

  /* Form Elements */
  .form-group-modern {
    margin-bottom: 18px;
  }

  .form-label-modern {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
  }

  .input-container {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-icon-left {
    position: absolute;
    left: 14px;
    color: #94a3b8;
    font-size: 15px;
    pointer-events: none;
    transition: color 0.2s ease;
    z-index: 3;
  }

  .form-control-modern {
    width: 100%;
    height: 46px;
    padding: 0 14px 0 42px;
    font-size: 13.5px;
    color: #1e293b;
    background: #f8fafc;
    border: 1px solid #d2d6de;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
    font-family: inherit;
  }

  .form-control-modern::placeholder {
    color: #94a3b8;
  }

  .form-control-modern:focus {
    background: #ffffff;
    border-color: #3c8dbc;
    box-shadow: 0 0 0 3.5px rgba(60, 141, 188, 0.18);
  }

  .form-control-modern:focus ~ .input-icon-left {
    color: #3c8dbc;
  }

  /* Password Toggle */
  .password-toggle-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 15px;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
    z-index: 3;
  }

  .password-toggle-btn:hover {
    color: #3c8dbc;
  }

  .password-input {
    padding-right: 42px;
  }

  /* Submit Button (AdminLTE Primary Blue Gradient) */
  .btn-submit-modern {
    width: 100%;
    height: 46px;
    margin-top: 8px;
    background: linear-gradient(135deg, #3c8dbc 0%, #2e7aab 100%);
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 6px 18px -3px rgba(60, 141, 188, 0.4);
    transition: all 0.2s ease;
    font-family: inherit;
  }

  .btn-submit-modern:hover {
    background: linear-gradient(135deg, #4898c7 0%, #367fa9 100%);
    transform: translateY(-1.5px);
    box-shadow: 0 8px 22px -3px rgba(60, 141, 188, 0.5);
  }

  .btn-submit-modern:active {
    transform: translateY(0);
    box-shadow: 0 3px 10px -2px rgba(60, 141, 188, 0.35);
  }

  /* Footer */
  .login-footer {
    text-align: center;
    margin-top: 20px;
  }

  .login-footer p {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.55);
    margin: 0;
  }

  @media (max-width: 480px) {
    .login-card {
      padding: 28px 20px 24px;
      border-radius: 14px;
    }
  }
</style>

<div class="login-wrapper">
  <div class="login-container">
    
    <div class="login-card">
      
      <!-- Branding / Logo Header -->
      <div class="login-header">
        <div class="brand-logo-wrap">
          <i class="fa fa-cubes"></i>
        </div>
        <h1 class="login-title"><?= !empty($idt->nm_perusahaan) ? htmlspecialchars($idt->nm_perusahaan) : 'CONSULTANT' ?></h1>
        <p class="login-subtitle">Silakan masuk untuk mengakses sistem</p>
      </div>

      <!-- Flash Message & Error Notification -->
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
        $icon = ($msg_type == 'danger') ? 'fa fa-exclamation-circle' : (($msg_type == 'success') ? 'fa fa-check-circle' : 'fa fa-info-circle');
        echo "<div class='custom-login-alert {$msg_type}'>
          <i class='{$icon} alert-icon'></i>
          <span class='alert-text'>" . htmlspecialchars($raw_msg) . "</span>
          <button type='button' class='alert-close-btn' onclick='this.parentElement.remove();' title='Tutup'>&times;</button>
        </div>";
      }
      ?>

      <!-- Login Form -->
      <?= form_open($this->uri->uri_string(), array('id' => 'frm_login', 'name' => 'frm_login', 'autocomplete' => 'off')) ?>
        <input type="hidden" name="login" value="1">
        
        <div class="form-group-modern">
          <label class="form-label-modern" for="input_username">Username</label>
          <div class="input-container">
            <input 
              type="text" 
              id="input_username"
              name="username" 
              class="form-control-modern" 
              placeholder="Masukkan username" 
              value="<?= set_value('username') ?>" 
              required 
              autofocus
            >
            <i class="fa fa-user input-icon-left"></i>
          </div>
        </div>

        <div class="form-group-modern">
          <label class="form-label-modern" for="input_password">Password</label>
          <div class="input-container">
            <input 
              type="password" 
              id="input_password" 
              name="password" 
              class="form-control-modern password-input" 
              placeholder="Masukkan password" 
              value="" 
              required
            >
            <i class="fa fa-lock input-icon-left"></i>
            <button type="button" class="password-toggle-btn" id="btnTogglePassword" tabindex="-1" title="Tampilkan/Sembunyikan Password">
              <i class="fa fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit-modern" name="login" id="btnSubmit">
          <span>Sign In</span>
          <i class="fa fa-sign-in"></i>
        </button>

      <?= form_close() ?>

    </div>

    <!-- Footer Copyright -->
    <div class="login-footer">
      <p>&copy; <?= date('Y'); ?> <?= !empty($idt->nm_perusahaan) ? htmlspecialchars($idt->nm_perusahaan) : 'CONSULTANT' ?>. All rights reserved.</p>
    </div>

  </div>
</div>

<script>
  $(document).ready(function() {
    // Show/Hide password toggle
    $('#btnTogglePassword').on('click', function(e) {
      e.preventDefault();
      var passwordInput = $('#input_password');
      var toggleIcon = $('#toggleIcon');
      
      if (passwordInput.attr('type') === 'password') {
        passwordInput.attr('type', 'text');
        toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
      } else {
        passwordInput.attr('type', 'password');
        toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
      }
    });

    // Form submit state feedback without disabling input submission
    $('#frm_login').on('submit', function() {
      var btn = $('#btnSubmit');
      btn.css('opacity', '0.85').html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
    });
  });
</script>