/**
 * Labmania ERP Core Scripts
 * Digunakan untuk menangani UI components: money-in auto-format, toast, modal, sidebar live search.
 */

(function () {
  'use strict';

  // Format ribuan titik untuk input money-in
  window.fmtRibu = function (v) {
    if (v === '' || v == null || isNaN(Number(v))) return '';
    return String(Math.round(Number(v))).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  };

  window.digitsOnly = function (s) {
    return String(s == null ? '' : s).replace(/\D/g, '');
  };

  window.parseMoney = function (v) {
    var d = window.digitsOnly(v);
    return d ? parseInt(d, 10) : 0;
  };

  // Event listener input money-in dengan penjagaan posisi kursor
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (!t.classList || !t.classList.contains('money-in')) return;
    var pos = t.selectionStart == null ? t.value.length : t.selectionStart;
    var left = window.digitsOnly(t.value.slice(0, pos)).length;
    var d = window.digitsOnly(t.value).replace(/^0+(?=\d)/, '');
    t.value = d ? window.fmtRibu(d) : '';
    var i = 0, seen = 0;
    while (i < t.value.length && seen < left) {
      if (/\d/.test(t.value[i])) seen++;
      i++;
    }
    try {
      t.setSelectionRange(i, i);
    } catch (_) {}
  }, true);

  // Toast notification
  window.toast = function (msg, type) {
    type = type || 'info';
    var el = document.createElement('div');
    el.className = 'toast toast-' + type;
    el.innerHTML = msg;
    var c = document.getElementById('toasts');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toasts';
      c.className = 'toasts';
      c.setAttribute('aria-live', 'polite');
      document.body.appendChild(c);
    }
    c.appendChild(el);
    setTimeout(function () {
      el.classList.add('fade-out');
      setTimeout(function () { el.remove(); }, 300);
    }, 3500);
  };

  // Modal helper
  window.openModal = function (html) {
    var root = document.getElementById('modal-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'modal-root';
      document.body.appendChild(root);
    }
    root.innerHTML = html;
    window.lmSelect2(root);
  };

  // Select yang bisa dicari (Select2). Opt-in per modul.
  // shortcut: aktif hanya di URL /master_data, /quotation_program, dan /penjualan_program, ganti ke penanda per halaman bila modul lain ikut.
  window.lmSelect2 = function (ctx) {
    if (!window.jQuery || !jQuery.fn.select2 || !/\/(master_data|quotation_program|penjualan_program)(\/|$)/.test(location.pathname)) return;
    var $c = jQuery(ctx || document);
    $c.find('select.input').not('.select2-hidden-accessible').each(function () {
      var $s = jQuery(this), $m = $s.closest('.modal');
      $s.select2({ width: '100%', dropdownParent: $m.length ? $m : jQuery(document.body) })
        // Kode yang sudah ada memakai addEventListener('change'); Select2 hanya memicu event jQuery.
        .on('select2:select select2:unselect', function () { this.dispatchEvent(new Event('change')); });
    });
  };

  window.closeModal = function () {
    var root = document.getElementById('modal-root');
    if (root) root.innerHTML = '';
  };

  // Sidebar controls
  document.addEventListener('DOMContentLoaded', function () {
    var menuBtn = document.getElementById('menu-btn');
    var scrim = document.querySelector('.scrim');
    var navQ = document.getElementById('nav-q');

    if (menuBtn) {
      menuBtn.addEventListener('click', function () {
        document.body.classList.toggle('nav-open');
      });
    }

    if (scrim) {
      scrim.addEventListener('click', function () {
        document.body.classList.remove('nav-open');
      });
    }

    // Toggle nav module accordion
    document.querySelectorAll('.nav-module-head[data-module]').forEach(function (head) {
      head.addEventListener('click', function (e) {
        var mod = head.closest('.nav-module');
        if (mod) {
          mod.classList.toggle('open');
          var isOpen = mod.classList.contains('open');
          head.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
      });
    });

    // Nav live search
    if (navQ) {
      navQ.addEventListener('input', function () {
        var kw = navQ.value.trim().toLowerCase();
        var navModules = document.querySelectorAll('.nav-module');

        navModules.forEach(function (mod) {
          var items = mod.querySelectorAll('.nav-item');
          var anyMatch = false;

          items.forEach(function (item) {
            var text = item.firstChild.textContent.trim().toLowerCase(); // label saja, tanpa angka badge
            if (!kw || text.indexOf(kw) !== -1) {
              item.style.display = '';
              anyMatch = true;
            } else {
              item.style.display = 'none';
            }
          });

          // Grup tanpa menu yang cocok disembunyikan, kalau tidak garis pemisahnya tertinggal kosong.
          mod.querySelectorAll('.nav-group').forEach(function (g) {
            var visible = Array.prototype.some.call(g.querySelectorAll('.nav-item'), function (i) { return i.style.display !== 'none'; });
            g.style.display = visible ? '' : 'none';
          });

          if (!kw) {
            mod.style.display = '';
            // keep normal open/closed state
          } else if (anyMatch) {
            mod.style.display = '';
            mod.classList.add('open');
          } else {
            mod.style.display = 'none';
          }
        });
      });
    }
  });

  // Menu gear di kolom Aksi: satu tombol, opsi muncul di menu melayang (sesuai mockup).
  // Opsi diambil dari tombol asli di <span class="gear-src"> dan memicu klik tombol itu.
  var GEAR_SVG = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>';

  function closeGearMenu() {
    var root = document.getElementById('menu-root');
    if (root) root.innerHTML = '';
    document.querySelectorAll('.gear.open').forEach(function (g) { g.classList.remove('open'); });
  }

  function openGearMenu(btn) {
    var root = document.getElementById('menu-root');
    var src = btn.parentNode.querySelector('.gear-src');
    var wasOpen = btn.classList.contains('open');
    closeGearMenu();
    if (!root || !src || wasOpen) return;

    var items = Array.prototype.slice.call(src.querySelectorAll('button, a'));
    var menu = document.createElement('div');
    menu.className = 'menu';
    menu.setAttribute('role', 'menu');
    items.forEach(function (orig) {
      var label = orig.getAttribute('title') || orig.textContent.trim();
      var it = document.createElement('button');
      it.type = 'button';
      it.className = 'menu-item' + (/hapus|delete|batalkan/i.test(label) ? ' danger' : '');
      it.setAttribute('role', 'menuitem');
      it.textContent = label;
      it.addEventListener('click', function () { closeGearMenu(); orig.click(); });
      menu.appendChild(it);
    });
    root.appendChild(menu);
    btn.classList.add('open');

    var b = btn.getBoundingClientRect();
    var top = b.bottom + 4 + menu.offsetHeight > window.innerHeight ? b.top - 4 - menu.offsetHeight : b.bottom + 4;
    menu.style.top = top + 'px';
    menu.style.left = Math.max(8, b.right - menu.offsetWidth) + 'px';
  }

  window.lmInitGears = function () {
    document.querySelectorAll('.gear[data-gear]').forEach(function (g) {
      if (!g.innerHTML.trim()) g.innerHTML = GEAR_SVG;
      // Tanpa satu pun aksi yang diizinkan (hak akses), tombol gear disembunyikan
      var src = g.nextElementSibling;
      if (src && src.classList.contains('gear-src')) g.style.display = src.querySelector('button, a') ? '' : 'none';
    });
  };
  document.addEventListener('DOMContentLoaded', function () { window.lmInitGears(); window.lmSelect2(); });

  document.addEventListener('click', function (e) {
    var g = e.target.closest('.gear[data-gear]');
    if (g) { e.stopPropagation(); openGearMenu(g); return; }
    if (document.querySelector('.gear[data-gear].open') && !e.target.closest('#menu-root')) closeGearMenu();
  });
  window.addEventListener('scroll', closeGearMenu, true);
  window.addEventListener('resize', closeGearMenu);

  // Peringatan validasi lewat SweetAlert2: daftar kolom yang perlu diperbaiki.
  function escHtml(t) {
    return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }

  window.lmWarn = function (messages, opts) {
    opts = opts || {};
    var list = [].concat(messages).filter(Boolean);
    if (!window.Swal) { toast(escHtml(list.join(', ')), 'warn'); return { then: function (f) { if (f) f(); } }; }
    var html = list.length > 1
      ? '<div class="swal-lead">' + escHtml(opts.lead || 'Mohon periksa kolom berikut:') + '</div><ul class="swal-list">' +
        list.map(function (m) { return '<li>' + escHtml(m) + '</li>'; }).join('') + '</ul>'
      : '<div class="swal-lead">' + escHtml(list[0]) + '</div>';
    return Swal.fire({
      icon: 'warning',
      title: opts.title || 'Data belum lengkap',
      html: html,
      confirmButtonText: 'Mengerti',
      confirmButtonColor: '#14304F',
      returnFocus: false,
      heightAuto: false
    });
  };

  // <select> yang dipasangi Select2 disembunyikan; garis merah dan fokus harus ke kotak Select2-nya.
  function visualOf(el) {
    var n = el.nextElementSibling;
    return n && n.classList && n.classList.contains('select2-container') ? n : el;
  }
  window.lmMark = function (el, on) { visualOf(el).classList.toggle('is-err', !!on); };
  function focusVisual(el) {
    var v = visualOf(el);
    var f = v !== el ? v.querySelector('.select2-selection') : el;
    if (f && f.focus) f.focus();
  }

  // items: [[id | elemen, 'Label'], ...]. Kolom kosong diberi garis merah; true bila semua terisi.
  window.lmRequired = function (items) {
    var missing = [];
    items.forEach(function (it) {
      var el = typeof it[0] === 'string' ? document.getElementById(it[0]) : it[0];
      if (!el || el.disabled) return;
      var empty = !String(el.value == null ? '' : el.value).trim();
      window.lmMark(el, empty);
      if (empty) {
        missing.push({ el: el, label: it[1] });
        var clear = function () { window.lmMark(el, false); };
        el.addEventListener('input', clear, { once: true });
        el.addEventListener('change', clear, { once: true });
      }
    });
    if (!missing.length) return true;
    var first = missing[0].el;
    var p = window.lmWarn(missing.map(function (m) { return m.label + ' wajib diisi'; }), { lead: 'Mohon lengkapi kolom wajib berikut:' });
    if (p && p.then) p.then(function () { focusVisual(first); });
    return false;
  };

  // Konfirmasi aksi lewat SweetAlert2; promise bernilai true bila dikonfirmasi.
  window.lmConfirm = function (o) {
    o = o || {};
    if (!window.Swal) return Promise.resolve(window.confirm(o.text || o.title || 'Lanjutkan?'));
    return Swal.fire({
      icon: o.icon || (o.danger ? 'warning' : 'question'),
      title: o.title || 'Apakah Anda yakin?',
      html: '<div class="swal-lead">' + escHtml(o.text || '').replace(/\n/g, '<br>') + '</div>',
      showCancelButton: true,
      confirmButtonText: o.confirmText || 'Ya, lanjutkan',
      cancelButtonText: o.cancelText || 'Batal',
      confirmButtonColor: o.danger ? '#D32F2F' : '#14304F',
      cancelButtonColor: '#6E7390',
      reverseButtons: true,
      focusCancel: !!o.danger,
      returnFocus: false,
      heightAuto: false
    }).then(function (r) { return r.isConfirmed !== undefined ? !!r.isConfirmed : !!r.value; }); // v9: result.value, v10+: isConfirmed
  };

})();
