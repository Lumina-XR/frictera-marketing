(function () {
  'use strict';

  var THEME_KEY = 'frictera-marketing-theme';

  function getSystemPreference() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function resolveEffective(theme) {
    if (theme === 'system') return getSystemPreference();
    return theme;
  }

  function applyTheme(theme) {
    var effective = resolveEffective(theme);
    document.documentElement.dataset.theme = effective;
  }

  function updateToggleState(theme) {
    var buttons = document.querySelectorAll('.theme-toggle-button');
    if (!buttons.length) return;
    buttons.forEach(function (btn) {
      var isActive = btn.dataset.value === theme;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-pressed', String(isActive));
    });
  }

  function setTheme(theme) {
    try {
      window.localStorage.setItem(THEME_KEY, theme);
    } catch (_) {}
    applyTheme(theme);
    updateToggleState(theme);
  }

  function initThemeToggle() {
    var stored = 'system';
    try {
      stored = window.localStorage.getItem(THEME_KEY) || 'system';
    } catch (_) {}
    setTheme(stored);

    document.querySelectorAll('.theme-toggle-button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setTheme(btn.dataset.value);
      });
    });

    var media = window.matchMedia('(prefers-color-scheme: dark)');
    if (media && media.addEventListener) {
      media.addEventListener('change', function () {
        var current = 'system';
        try {
          current = window.localStorage.getItem(THEME_KEY) || 'system';
        } catch (_) {}
        if (current === 'system') {
          applyTheme('system');
        }
      });
    }
  }

  function initMobileNav() {
    var toggle = document.querySelector('.menu-toggle');
    var menu = document.getElementById('mobile-menu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      menu.classList.toggle('is-open', !expanded);
    });
  }

  function initHeaderScroll() {
    var header = document.querySelector('.site-header');
    if (!header) return;

    function update() {
      if (window.scrollY > 10) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initThemeToggle();
      initMobileNav();
      initHeaderScroll();
    });
  } else {
    initThemeToggle();
    initMobileNav();
    initHeaderScroll();
  }
})();
