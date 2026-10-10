/* RDSCo Fresh Template — front-end script (vanilla, no dependencies). */
(function () {
  'use strict';

  var header = document.querySelector('.rds-header');
  if (!header) return;

  var navButtons = header.querySelectorAll('.rds-nav__link[aria-expanded]');
  var burger = header.querySelector('.rds-burger');
  var drawer = document.getElementById('rds-drawer');
  var searchBtn = header.querySelector('[data-rds-search]');
  var searchBar = document.getElementById('rds-search');

  /* ---------- Desktop menus ---------- */
  function closeMenus(except) {
    navButtons.forEach(function (b) { if (b !== except) b.setAttribute('aria-expanded', 'false'); });
  }
  navButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = btn.getAttribute('aria-expanded') === 'true';
      closeMenus(btn);
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.rds-nav')) closeMenus();
  });

  /* ---------- Mobile drawer ---------- */
  function toggleDrawer(force) {
    if (!drawer || !burger) return;
    var open = typeof force === 'boolean' ? force : drawer.hidden;
    drawer.hidden = !open;
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', burger.getAttribute(open ? 'data-label-close' : 'data-label-open'));
    var use = burger.querySelector('use');
    if (use) use.setAttribute('href', open ? '#i-close' : '#i-menu');
    document.documentElement.style.overflow = open ? 'hidden' : '';
  }
  if (burger) burger.addEventListener('click', function () { toggleDrawer(); });
  if (drawer) drawer.addEventListener('click', function (e) {
    var a = e.target.closest('a');
    if (a && a.hash && a.pathname === location.pathname) toggleDrawer(false);
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 1100 && drawer && !drawer.hidden) toggleDrawer(false);
  });

  /* ---------- Search ---------- */
  function toggleSearch(force) {
    if (!searchBar || !searchBtn) return;
    var open = typeof force === 'boolean' ? force : searchBar.hidden;
    searchBar.hidden = !open;
    searchBtn.setAttribute('aria-expanded', String(open));
    if (open) searchBar.querySelector('input').focus();
  }
  if (searchBtn) searchBtn.addEventListener('click', function () { toggleSearch(); });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    closeMenus();
    if (drawer && !drawer.hidden) toggleDrawer(false);
    if (searchBar && !searchBar.hidden) toggleSearch(false);
  });

  /* ---------- Header shadow ---------- */
  function onScroll() { header.classList.toggle('is-scrolled', window.scrollY > 8); }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- "On this page" sub-navigation from section headings ---------- */
  var subnav = document.querySelector('.rds-subnav');
  if (subnav) {
    var headings = document.querySelectorAll('.rds-entry--sections h2[id]');
    if (headings.length >= 2) {
      var list = subnav.querySelector('ul');
      var links = [];
      headings.forEach(function (h) {
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = '#' + h.id;
        // Prefer the short eyebrow label above the heading, if there is one.
        var prev = h.previousElementSibling;
        var label = prev && prev.classList.contains('rds-eyebrow') ? prev.textContent : h.textContent;
        a.textContent = label.trim();
        li.appendChild(a);
        list.appendChild(li);
        links.push(a);
      });
      subnav.hidden = false;

      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            links.forEach(function (l) { l.classList.toggle('is-active', l.getAttribute('href') === '#' + en.target.id); });
          });
        }, { rootMargin: '-35% 0px -60% 0px' });
        headings.forEach(function (h) { io.observe(h); });
      }
    }
  }
})();
