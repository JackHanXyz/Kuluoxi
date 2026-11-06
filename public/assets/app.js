(function () {
  'use strict';

  /* ---------- 主题切换 ---------- */
  var root = document.documentElement;
  var themeBtn = document.querySelector('[data-theme-toggle]');
  var themeIcon = document.querySelector('[data-theme-icon]');

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    try { localStorage.setItem('kuluoxi.theme', theme); } catch (e) {}
    if (themeIcon) themeIcon.textContent = theme === 'dark' ? '☀' : '☾';
  }

  // 初始主题已由内联脚本设置，这里只更新图标
  (function () {
    var t = root.getAttribute('data-theme') || 'light';
    if (themeIcon) themeIcon.textContent = t === 'dark' ? '☀' : '☾';
  })();

  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }

  /* ---------- 皮肤切换 ---------- */
  function applySkin(skin) {
    if (!skin) return;
    root.setAttribute('data-skin', skin);
    try { localStorage.setItem('kuluoxi.skin', skin); } catch (e) {}
  }

  document.querySelectorAll('[data-skin-select]').forEach(function (sel) {
    var current = root.getAttribute('data-skin');
    if (current) sel.value = current;
    sel.addEventListener('change', function () {
      applySkin(sel.value);
    });
  });

  document.addEventListener('click', function (e) {
    var pick = e.target.closest('[data-skin-pick]');
    if (!pick) return;
    applySkin(pick.getAttribute('data-skin-pick'));
    document.querySelectorAll('[data-skin-pick]').forEach(function (el) {
      el.classList.toggle('active', el === pick);
    });
    document.querySelectorAll('[data-skin-select]').forEach(function (sel) {
      sel.value = pick.getAttribute('data-skin-pick');
    });
  });

  /* ---------- 弹窗 ---------- */
  document.addEventListener('click', function (e) {
    var openBtn = e.target.closest('[data-open-modal]');
    if (openBtn) {
      var name = openBtn.getAttribute('data-open-modal');
      var modal = document.querySelector('[data-modal="' + name + '"]');
      if (modal) modal.hidden = false;
      return;
    }
    var closeBtn = e.target.closest('[data-close-modal]');
    if (closeBtn) {
      var m = closeBtn.closest('[data-modal]');
      if (m) m.hidden = true;
      return;
    }
  });

  // 点击遮罩关闭
  document.querySelectorAll('[data-modal]').forEach(function (mask) {
    mask.addEventListener('click', function (e) {
      if (e.target === mask) mask.hidden = true;
    });
  });

  // ESC 关闭
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('[data-modal]:not([hidden])').forEach(function (m) {
        m.hidden = true;
      });
    }
  });

  /* ---------- 复制按钮 ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var text = btn.getAttribute('data-copy');
    var done = function () {
      var old = btn.textContent;
      btn.textContent = '已复制';
      setTimeout(function () { btn.textContent = old; }, 1200);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () { fallback(text, done); });
    } else {
      fallback(text, done);
    }
  });

  function fallback(text, cb) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); cb(); } catch (e) {}
    document.body.removeChild(ta);
  }

  /* ---------- Flash 自动消失 + 手动关闭 ---------- */
  document.querySelectorAll('.flash').forEach(function (el) {
    var btn = el.querySelector('[data-dismiss]');
    if (btn) btn.addEventListener('click', function () { el.remove(); });
    setTimeout(function () {
      el.style.transition = 'opacity .3s, transform .3s';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      setTimeout(function () { el.remove(); }, 320);
    }, 4200);
  });

  /* ---------- 移动端侧边栏 ---------- */
  var sidebar = document.querySelector('[data-sidebar]');
  var mask = document.querySelector('[data-sidebar-mask]');
  var toggle = document.querySelector('[data-sidebar-toggle]');
  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (mask) mask.hidden = true;
  }
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      if (mask) mask.hidden = !sidebar.classList.contains('open');
    });
  }
  if (mask) mask.addEventListener('click', closeSidebar);

  /* ---------- FAQ 手风琴 ---------- */
  document.querySelectorAll('.faq-q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.closest('.faq-item');
      if (item) item.classList.toggle('open');
    });
  });

  /* ---------- 通用：点击 label.option 内的 radio 自动激活样式 ---------- */
  // 由 CSS :checked 处理，这里预留
})();
