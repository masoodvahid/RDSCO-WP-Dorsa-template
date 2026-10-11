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

  /* Any open menu, drawer or search turns the transparent (overlay) header solid. */
  function syncOpen() {
    var open = (drawer && !drawer.hidden) || (searchBar && !searchBar.hidden) || header.querySelector('.rds-nav__link[aria-expanded="true"]');
    header.classList.toggle('is-open', !!open);
  }

  /* ---------- Desktop menus ---------- */
  function closeMenus(except) {
    navButtons.forEach(function (b) { if (b !== except) b.setAttribute('aria-expanded', 'false'); });
    syncOpen();
  }
  navButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = btn.getAttribute('aria-expanded') === 'true';
      closeMenus(btn);
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      syncOpen();
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
    // Lock page scroll on <body>: it propagates to the viewport, so the sticky header stays put.
    // (overflow on <html> would turn <body> into its own scroller and unstick the header.)
    document.body.style.overflow = open ? 'hidden' : '';
    syncOpen();
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
    syncOpen();
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

/* Showcase home sections: how-it-works sticky visual and industry preview panel. */
(function () {
  'use strict';

  /* How it works: copy each step's image into one sticky figure and show the step in view. */
  document.querySelectorAll('.rds-how__grid').forEach(function (grid) {
    var steps = grid.querySelectorAll('.rds-how__step');
    if (steps.length < 2 || !('IntersectionObserver' in window)) return;

    var fig = document.createElement('figure');
    var cap = document.createElement('figcaption');
    var imgs = [];
    var caps = [];
    fig.className = 'rds-how__visual';
    fig.setAttribute('aria-hidden', 'true');
    steps.forEach(function (step) {
      var src = step.querySelector('.wp-block-image img');
      var fc = step.querySelector('.wp-block-image figcaption');
      var img = document.createElement('img');
      img.alt = '';
      img.decoding = 'async';
      if (src) {
        if (src.srcset) { img.srcset = src.srcset; img.sizes = '(max-width: 1320px) 50vw, 620px'; }
        img.src = src.getAttribute('src');
      }
      fig.appendChild(img);
      imgs.push(img);
      caps.push(fc ? fc.textContent.trim() : (src ? src.alt : ''));
    });
    fig.appendChild(cap);
    grid.insertBefore(fig, grid.firstChild);
    grid.classList.add('is-enhanced');

    function show(i) {
      steps.forEach(function (s, k) { s.classList.toggle('is-on', k === i); });
      imgs.forEach(function (im, k) { im.classList.toggle('is-on', k === i); });
      cap.textContent = caps[i];
      cap.hidden = !caps[i];
    }
    show(0);
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) show(Array.prototype.indexOf.call(steps, e.target));
      });
    }, { rootMargin: '-45% 0px -45% 0px' });
    steps.forEach(function (s) { io.observe(s); });
  });

  /* Industry preview: hovering or focusing a link shows its panel; the link itself still navigates. */
  document.querySelectorAll('[data-rds-industries]').forEach(function (box) {
    var items = box.querySelectorAll('.rds-industries__item');
    var cards = box.querySelectorAll('.rds-industries__card');
    var warmed = false;
    function warm() {
      if (warmed) return;
      warmed = true;
      box.querySelectorAll('.rds-industries__card img[loading="lazy"]').forEach(function (im) { im.loading = 'eager'; });
    }
    function show(i) {
      items.forEach(function (a) { a.classList.toggle('is-on', a.getAttribute('data-i') === i); });
      cards.forEach(function (c) { c.hidden = c.getAttribute('data-i') !== i; });
    }
    items.forEach(function (a) {
      a.addEventListener('mouseenter', function () { warm(); show(a.getAttribute('data-i')); });
      a.addEventListener('focus', function () { warm(); show(a.getAttribute('data-i')); });
    });
  });
})();
