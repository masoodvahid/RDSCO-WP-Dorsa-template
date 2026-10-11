/*
 * RDSCo slab inspection animation — [rds_slab_inspection].
 * Hot slabs travel along a roller table in a dimetric view, pass an inspection gantry,
 * defects are revealed and a live quality chart grades each slab. Vanilla canvas, no libraries.
 * Pauses off-screen and in hidden tabs; shows a still frame for prefers-reduced-motion.
 */
(function () {
  'use strict';

  var DEFAULT_TEXT = {
    quality: 'SURFACE QUALITY', scanning: 'SCANNING', grade: 'GRADE', severity: 'Defect severity',
    reject: 'Reject limit', position: 'Position from slab head', scanned: 'SCANNED', defects: 'DEFECTS',
    qi: 'QUALITY INDEX', last: 'LAST SLABS', defect1: 'defect', defectN: 'defects', inspecting: 'INSPECTING',
    pause: 'Pause animation', play: 'Play animation',
    lcrack: 'Longitudinal crack', tcrack: 'Transverse crack', corner: 'Corner crack',
    scale: 'Scale patch', slag: 'Slag inclusion', pin: 'Pinholes'
  };

  function init(root) {
  var L = DEFAULT_TEXT;
  try { L = Object.assign({}, DEFAULT_TEXT, JSON.parse(root.getAttribute('data-i18n') || '{}')); } catch (e) { /* keep defaults */ }
  var stage = root.querySelector('.rds-slab__stage');
  var canvas = root.querySelector('canvas');
  var ctx = canvas.getContext('2d');
  var toggle = root.querySelector('.rds-slab__btn');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var C = {
    sky: '#9fc3ec', gold: '#d6a63a', ok: '#2fa36b', bad: '#e5484d', muted: '#a9b8cc',
    faint: 'rgba(255,255,255,.08)', font: getComputedStyle(root).fontFamily || 'sans-serif'
  };
  var TYPES = ['lcrack', 'tcrack', 'corner', 'scale', 'slag', 'pin'].map(function (k) { return { key: k, label: L[k] }; });
  var REJECT = 0.65;

  /*
   * Track space: u runs along the roller table (u = 0 under the scanner, growing towards the
   * upper right), v runs across it (0 = far edge, h = near edge). A dimetric projection maps
   * track space to the screen, so slabs, rollers and the scan line are drawn as flat shapes
   * and come out in perspective.
   */
  var G = {};
  var slabs = [], history = [], current = null, serial = 1184;
  var dpr = 1, running = !reduce, visible = true, last = 0, t = 0;

  function rand(a, b) { return a + Math.random() * (b - a); }
  function pick(a) { return a[Math.floor(Math.random() * a.length)]; }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

  /*
   * Stage mode (hero variant on desktop screens): the canvas fills the whole hero behind its text.
   * The text sits top left, the chart top right, and the track angle and position are solved so the
   * roller table runs between them; slab history and the controls go bottom right, under the track.
   */
  var hero = root.classList.contains('rds-slab--hero') ? root.closest('.rds-showcase') : null;
  var stageQuery = window.matchMedia('(min-width: 1000px) and (min-height: 700px)');
  function isStage() { return !!hero && stageQuery.matches && document.documentElement.dir !== 'rtl'; }
  function relRect(el, base) {
    var r = el.getBoundingClientRect();
    return { l: r.left - base.left, t: r.top - base.top, r: r.right - base.left, b: r.bottom - base.top };
  }
  function hit(a, b) { return a.l < b.r && a.r > b.l && a.t < b.b && a.b > b.t; }
  function avoided(r) { return (G.avoid || []).some(function (a) { return hit(r, a); }); }

  function layoutStage(W, H) {
    var base = stage.getBoundingClientRect();
    var top = hero.querySelector('.rds-showcase__top');
    var box = top ? relRect(top.parentElement, base) : { l: 24, r: W - 24 };
    var txt = { l: box.l, t: 120, r: box.l + 1, b: 120 };
    if (top) {
      txt.t = Infinity; txt.b = -Infinity; txt.r = -Infinity;
      top.querySelectorAll(':scope > :not(.rds-showcase__copy), :scope > .rds-showcase__copy > *').forEach(function (el) {
        var r = relRect(el, base);
        if (r.b - r.t < 1) return;
        txt.t = Math.min(txt.t, r.t); txt.b = Math.max(txt.b, r.b); txt.r = Math.max(txt.r, r.r);
      });
    }
    var ctrlEl = root.querySelector('.rds-slab__controls');
    var ctrl = ctrlEl ? relRect(ctrlEl, base) : { l: W, t: H, r: W, b: H };

    G.W = W; G.H = H; G.wide = true; G.mobile = false; G.stage = true;
    G.wx = 0.40; G.wy = 0.56; G.h = 112; G.T = 11; G.post = 74;
    G.L = clamp(W * 0.25, 300, 420); G.gap = G.L * 0.32; G.speed = 95;

    G.panelW = clamp((box.r - box.l) * 0.42, 400, 520);
    G.panelH = 262;
    G.panelX = box.r - G.panelW;
    G.panelTop = txt.t;
    var panelB = G.panelTop + G.panelH;
    G.cx = Math.round(clamp((txt.r + G.panelX) / 2 + 60, txt.r + 150, box.r - 220));

    // Largest angle (8°–20°) at which the bed clears the text, the chart and the controls.
    var pick = null, lo = 0, hi = 0, sl = 0;
    for (var deg = 20; deg >= 8; deg -= 0.5) {
      sl = Math.tan(deg * Math.PI / 180);
      var bTop = 46 + 29 * sl, bBot = 64 + 29 * sl;
      lo = Math.max(txt.b + 20 + bTop + txt.r * sl, panelB + 28 + bTop + box.r * sl, panelB + 135 + G.cx * sl);
      hi = Math.min(H - bBot + 0.25 * W * sl, ctrl.t - 16 - bBot + ctrl.l * sl);
      if (lo <= hi) { pick = deg; break; }
    }
    if (pick === null) pick = 8;
    var th = pick * Math.PI / 180, y0 = (lo + hi) / 2;
    sl = Math.tan(th);
    G.dx = Math.cos(th); G.dy = -Math.sin(th);
    G.cy = Math.round(y0 - G.cx * sl);
    G.uMin = -(G.cx + 120) / G.dx - G.h;
    G.uMax = (W - G.cx + 120) / G.dx + G.h;

    // Slab history in the free corner under the track, above the controls.
    G.histX = box.r - 230;
    G.histY = Math.round(y0 - G.histX * sl + 64 + 29 * sl + 24);
    G.histRows = clamp(Math.floor((ctrl.t - 20 - G.histY - 34) / 40), 0, 5);
    if (G.histRows < 2) G.histRows = 0;

    G.avoid = [txt, { l: G.panelX, t: G.panelTop, r: box.r, b: panelB }, ctrl];
    if (G.histRows) G.avoid.push({ l: G.histX, t: G.histY, r: box.r, b: G.histY + 34 + G.histRows * 40 });

    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(W * dpr);
    canvas.height = Math.round(H * dpr);
    canvas.style.height = '';
  }

  /* ---------------- geometry ---------------- */
  function layout() {
    var W = stage.clientWidth;
    root.classList.toggle('is-stage', isStage());
    if (isStage()) { layoutStage(W, stage.clientHeight); return; }
    G.stage = false; G.avoid = []; G.histRows = 5;
    var wide = W >= 900;
    var th = (wide ? 20 : 21) * Math.PI / 180;
    G.W = W; G.wide = wide; G.mobile = W < 560;
    G.dx = Math.cos(th); G.dy = -Math.sin(th);   // along the track (to upper right)
    G.wx = 0.40; G.wy = 0.56;                    // across the track (towards the viewer)
    G.h = wide ? 112 : clamp(W * 0.2, 64, 96);   // slab width in track space
    G.T = wide ? 11 : 7;                         // slab thickness on screen
    G.post = wide ? 74 : 50;                     // gantry height
    G.L = wide ? clamp(W * 0.3, 300, 420) : clamp(W * 0.55, 170, 300);
    G.gap = G.L * 0.32;
    G.speed = wide ? 95 : clamp(W * 0.17, 55, 85);
    G.cx = Math.round(W / 2);
    var rise = (W / 2) * Math.abs(G.dy) / G.dx;  // vertical travel from centre to an edge
    G.cy = Math.round(rise + G.post + (wide ? 34 : 26));
    G.uMin = -(G.cx + 120) / G.dx - G.h;
    G.uMax = (W - G.cx + 120) / G.dx + G.h;
    var trackBottom = G.cy + rise + G.h * G.wy / 2 + G.T + 30;

    if (wide) {
      G.panelW = Math.min(560, Math.round(W * 0.46));
      G.panelH = 262;
      G.panelX = W - G.panelW;
      G.H = Math.max(Math.round(trackBottom), G.cy + 110 + G.panelH);
      G.panelTop = G.H - G.panelH;
      G.histX = 0; G.histY = 0;
    } else {
      G.panelW = W; G.panelX = 0;
      G.panelH = G.mobile ? 282 : 262;
      G.panelTop = Math.round(trackBottom) + 8;
      G.H = G.panelTop + G.panelH + 4;
    }

    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(W * dpr);
    canvas.height = Math.round(G.H * dpr);
    canvas.style.height = G.H + 'px';
  }

  // Track space -> screen.
  function P(u, v) {
    var o = v - G.h / 2;
    return { x: G.cx + u * G.dx + o * G.wx, y: G.cy + u * G.dy + o * G.wy };
  }
  function trackSpace() { ctx.setTransform(dpr * G.dx, dpr * G.dy, dpr * G.wx, dpr * G.wy, dpr * (G.cx - G.h / 2 * G.wx), dpr * (G.cy - G.h / 2 * G.wy)); }
  function screenSpace() { ctx.setTransform(dpr, 0, 0, dpr, 0, 0); }
  function poly(pts) { ctx.beginPath(); pts.forEach(function (p, i) { i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y); }); ctx.closePath(); }
  function down(p, d) { return { x: p.x, y: p.y + d }; }

  /* ---------------- slab model ---------------- */
  function makeSlab(u) {
    var count = pick([0, 1, 1, 1, 2, 2, 2, 3, 3, 4]);
    var defects = [];
    for (var i = 0; i < count; i++) {
      var type = pick(TYPES);
      defects.push({
        type: type,
        u: rand(0.08, 0.92),
        v: type.key === 'corner' ? pick([0.08, 0.92]) : rand(0.2, 0.8),
        size: Math.round(rand(14, 120)),
        sev: Math.random() < 0.22 ? rand(0.66, 0.92) : rand(0.18, 0.6)
      });
    }
    defects.sort(function (a, b) { return a.u - b.u; });
    var signal = [], N = 240;
    for (var k = 0; k <= N; k++) {
      var x = k / N, s = 0.05 + 0.035 * Math.sin(x * 61 + serial) + 0.03 * Math.random();
      defects.forEach(function (d) { s += d.sev * Math.exp(-Math.pow((x - d.u) / 0.012, 2)); });
      signal.push(clamp(s, 0, 1));
    }
    var slab = { id: 'SLB-26-' + (serial++), lenM: Math.round(rand(8.6, 11.8) * 10) / 10, u: u, defects: defects, signal: signal, done: false };
    slab.qi = qualityIndex(slab, 1);
    slab.grade = slab.qi >= 95 ? 'A' : slab.qi >= 88 ? 'B' : 'C';
    slab.tex = texture(slab);
    return slab;
  }
  function qualityIndex(slab, p) {
    var q = 100;
    slab.defects.forEach(function (d) { if (d.u <= p) q -= d.sev * 6.2; });
    return clamp(q, 60, 100);
  }
  function gradeColor(g) { return g === 'A' ? C.ok : g === 'B' ? C.gold : C.bad; }

  function texture(slab) {
    var w = Math.round(G.L), h = Math.round(G.h);
    var c = document.createElement('canvas');
    c.width = Math.round(w * dpr); c.height = Math.round(h * dpr);
    var g = c.getContext('2d');
    g.scale(dpr, dpr);
    var base = g.createLinearGradient(0, 0, 0, h);
    base.addColorStop(0, '#8a2f19'); base.addColorStop(0.25, '#c9532d'); base.addColorStop(0.55, '#e06a33');
    base.addColorStop(0.85, '#c24d2b'); base.addColorStop(1, '#93341c');
    g.fillStyle = base; g.fillRect(0, 0, w, h);
    var ends = g.createLinearGradient(0, 0, w, 0);
    ends.addColorStop(0, 'rgba(40,10,6,.45)'); ends.addColorStop(0.06, 'rgba(40,10,6,0)');
    ends.addColorStop(0.94, 'rgba(40,10,6,0)'); ends.addColorStop(1, 'rgba(40,10,6,.45)');
    g.fillStyle = ends; g.fillRect(0, 0, w, h);
    for (var i = 0; i < w * h / 24; i++) {
      g.fillStyle = Math.random() < 0.5 ? 'rgba(60,14,6,.17)' : 'rgba(255,175,95,.10)';
      g.fillRect(Math.random() * w, Math.random() * h, rand(1, 3), rand(1, 2));
    }
    slab.defects.forEach(function (d) {
      var dx = w * (1 - d.u), dy = h * d.v, px = clamp(d.size / 1000 / slab.lenM * w * 6, 10, 70);
      g.strokeStyle = 'rgba(70,16,8,.42)'; g.fillStyle = 'rgba(70,16,8,.30)'; g.lineWidth = 1.3;
      g.beginPath();
      if (d.type.key === 'lcrack') { g.moveTo(dx - px / 2, dy); for (var a = 0; a <= 6; a++) g.lineTo(dx - px / 2 + a * px / 6, dy + rand(-1.4, 1.4)); g.stroke(); }
      else if (d.type.key === 'tcrack' || d.type.key === 'corner') { var len = Math.min(px, h * 0.3); g.moveTo(dx, dy - len / 2); for (var b = 0; b <= 5; b++) g.lineTo(dx + rand(-1.4, 1.4), dy - len / 2 + b * len / 5); g.stroke(); }
      else if (d.type.key === 'scale') { g.ellipse(dx, dy, Math.max(6, px / 2), Math.max(4, px / 4), 0.3, 0, Math.PI * 2); g.fill(); }
      else { for (var q = 0; q < 7; q++) { var ex = dx + rand(-7, 7), ey = dy + rand(-5, 5); g.moveTo(ex, ey); g.arc(ex, ey, rand(0.8, 1.8), 0, Math.PI * 2); } g.fill(); }
    });
    return c;
  }

  // Detection frame of a defect, in track space.
  function frameOf(s, d) {
    var px = clamp(d.size / 1000 / s.lenM * G.L * 6, 10, 70);
    var bw = d.type.key === 'lcrack' ? px + 12 : d.type.key === 'tcrack' || d.type.key === 'corner' ? 18 : 28;
    var bh = d.type.key === 'tcrack' || d.type.key === 'corner' ? Math.min(px, G.h * 0.3) + 12 : 20;
    var cu = s.u + G.L * (1 - d.u), cv = G.h * d.v;
    return { u: cu - bw / 2, v: cv - bh / 2, w: bw, h: bh, cu: cu, cv: cv };
  }

  function seed() {
    slabs = []; history = []; serial = 1184;
    for (var i = 0; i < 3; i++) { var h0 = makeSlab(0); h0.done = true; history.unshift(h0); }
    var mid = makeSlab(-G.L * 0.62);
    slabs.push(mid);
    var ahead = makeSlab(mid.u + G.L + G.gap); ahead.done = true;
    if (ahead.u < G.uMax) slabs.unshift(ahead);
    var u = mid.u - G.gap - G.L;
    while (u + G.L > G.uMin - G.L) { slabs.push(makeSlab(u)); u -= G.gap + G.L; }
    current = mid;
  }

  /* ---------------- update ---------------- */
  function progressOf(s) { return clamp((s.u + G.L) / G.L, 0, 1); }

  function update(dt) {
    t += dt;
    slabs.forEach(function (s) { s.u += G.speed * dt; });
    var tail = slabs[slabs.length - 1];
    while (tail.u > G.uMin - G.L - G.gap) { tail = makeSlab(tail.u - G.gap - G.L); slabs.push(tail); }
    slabs = slabs.filter(function (s) { return s.u < G.uMax; });
    slabs.forEach(function (s) {
      var p = progressOf(s);
      if (p > 0 && p < 1 && current !== s) current = s;
      if (p >= 1 && !s.done) { s.done = true; history.unshift(s); history = history.slice(0, 5); }
    });
  }

  /* ---------------- drawing helpers ---------------- */
  function rr(x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
  }
  function text(str, x, y, size, weight, color, align, spacing) {
    ctx.font = (weight || 500) + ' ' + size + 'px ' + C.font;
    ctx.fillStyle = color; ctx.textAlign = align || 'left'; ctx.textBaseline = 'alphabetic';
    if (spacing && 'letterSpacing' in ctx) ctx.letterSpacing = spacing + 'px';
    ctx.fillText(str, x, y);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
  }

  /* ---------------- scene ---------------- */
  function drawBed() {
    var m = 16, a = G.uMin, b = G.uMax;
    // Near side face of the bed, then the bed top.
    screenSpace();
    var n0 = P(a, G.h + m), n1 = P(b, G.h + m);
    poly([n0, n1, down(n1, 18), down(n0, 18)]);
    ctx.fillStyle = '#05142a'; ctx.fill();
    trackSpace();
    ctx.fillStyle = '#0a2140';
    ctx.fillRect(a, -m, b - a, G.h + 2 * m);
    // Rollers.
    var step = G.wide ? 30 : 24;
    for (var u = Math.floor(a / step) * step; u < b; u += step) {
      var g = ctx.createLinearGradient(u - 5, 0, u + 5, 0);
      g.addColorStop(0, '#132c4b'); g.addColorStop(0.5, '#4a6789'); g.addColorStop(1, '#132c4b');
      ctx.fillStyle = g;
      ctx.fillRect(u - 5, -m + 4, 10, G.h + 2 * m - 8);
    }
    ctx.fillStyle = 'rgba(255,255,255,.07)';
    ctx.fillRect(a, -m, b - a, 1); ctx.fillRect(a, G.h + m - 1, b - a, 1);
  }

  function drawSlab(s) {
    var u0 = s.u, u1 = s.u + G.L;
    if (u0 > G.uMax || u1 < G.uMin) return;

    // Visible sides: the near long side and the tail end (both extruded downwards).
    screenSpace();
    var a = P(u0, G.h), b = P(u1, G.h), c = P(u0, 0);
    poly([a, b, down(b, G.T), down(a, G.T)]);
    ctx.fillStyle = '#7d2a16'; ctx.fill();
    poly([c, a, down(a, G.T), down(c, G.T)]);
    ctx.fillStyle = '#5e1f10'; ctx.fill();

    // Top face with heat glow.
    trackSpace();
    ctx.save();
    ctx.shadowColor = 'rgba(255,110,40,.42)';
    ctx.shadowBlur = 28;
    ctx.drawImage(s.tex, u0, 0, G.L, G.h);
    ctx.restore();

    // Inspected part of the surface.
    if (u1 > 0) {
      var su = Math.max(0, u0);
      ctx.save();
      ctx.beginPath(); ctx.rect(su, 0, u1 - su, G.h); ctx.clip();
      ctx.fillStyle = 'rgba(40,120,220,.17)';
      ctx.fillRect(su, 0, u1 - su, G.h);
      ctx.strokeStyle = 'rgba(160,210,255,.16)'; ctx.lineWidth = 1;
      ctx.beginPath();
      var gs = G.wide ? 14 : 11;
      for (var gu = u0; gu < u1; gu += gs) { ctx.moveTo(gu, 0); ctx.lineTo(gu, G.h); }
      for (var gv = 0; gv < G.h; gv += gs) { ctx.moveTo(su, gv); ctx.lineTo(u1, gv); }
      ctx.stroke();
      ctx.restore();
      s.defects.forEach(function (d) {
        var f = frameOf(s, d);
        if (f.cu < 0) return;
        ctx.save();
        ctx.globalAlpha = clamp(f.cu / 40, 0, 1);
        corners(f.u, f.v, f.w, f.h, d.sev >= REJECT ? C.bad : C.gold);
        ctx.restore();
      });
    }
  }

  function corners(x, y, w, h, col) {
    var l = Math.min(8, w / 2, h / 2);
    ctx.strokeStyle = col; ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(x, y + l); ctx.lineTo(x, y); ctx.lineTo(x + l, y);
    ctx.moveTo(x + w - l, y); ctx.lineTo(x + w, y); ctx.lineTo(x + w, y + l);
    ctx.moveTo(x + w, y + h - l); ctx.lineTo(x + w, y + h); ctx.lineTo(x + w - l, y + h);
    ctx.moveTo(x + l, y + h); ctx.lineTo(x, y + h); ctx.lineTo(x, y + h - l);
    ctx.stroke();
  }

  // Labels stay upright, in screen space.
  function drawLabels() {
    screenSpace();
    // Only the slab on the chart gets labels; finished slabs keep their frames.
    var placed = [];
    [current].forEach(function (s) {
      if (!s || slabs.indexOf(s) < 0) return;
      s.defects.forEach(function (d) {
        var f = frameOf(s, d);
        if (f.cu < 0 || (G.mobile && d.sev < REJECT)) return;
        var col = d.sev >= REJECT ? C.bad : C.gold;
        var anchor = P(f.cu, f.v);
        var str = G.mobile ? d.type.label : d.type.label + ' · ' + d.size + ' mm';
        ctx.font = '600 11px ' + C.font;
        var tw = ctx.measureText(str).width + 12;
        var lx = clamp(anchor.x - tw / 2, 4, G.W - tw - 4), ly = anchor.y - 30;
        // Stack labels that would overlap an earlier one.
        for (var k = 0; k < 3 && placed.some(function (q) { return hit(q, { l: lx, t: ly, r: lx + tw, b: ly + 18 }); }); k++) ly -= 22;
        var box = { l: lx, t: ly, r: lx + tw, b: ly + 18 };
        if (avoided(box) || placed.some(function (q) { return hit(q, box); })) return;
        placed.push({ l: box.l - 4, t: box.t - 2, r: box.r + 4, b: box.b + 2 });
        // Labels appear once the defect has cleared the gantry.
        var alpha = clamp((f.cu - 55) / 35, 0, 1);
        if (!alpha) return;
        ctx.globalAlpha = alpha;
        ctx.strokeStyle = col; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(anchor.x, anchor.y - 3); ctx.lineTo(anchor.x, ly + 18); ctx.stroke();
        ctx.fillStyle = 'rgba(7,26,51,.9)'; rr(lx, ly, tw, 18, 4); ctx.fill(); ctx.stroke();
        text(str, lx + 6, ly + 13, 11, 600, '#fff');
        ctx.globalAlpha = 1;
      });
    });
  }

  function post(v) {
    var base = P(0, v), top = down(base, -G.post), w = 7;
    var g = ctx.createLinearGradient(base.x - w, 0, base.x + w, 0);
    g.addColorStop(0, '#1b4f8c'); g.addColorStop(0.5, '#3f7fc4'); g.addColorStop(1, '#16457c');
    ctx.fillStyle = g;
    ctx.fillRect(base.x - w / 2, top.y, w, G.post);
    ctx.fillStyle = '#0d2c52';
    ctx.fillRect(base.x - w, base.y - 3, w * 2, 5);
    return top;
  }

  function drawGantryBack() {
    screenSpace();
    post(-26); // far post, behind the slabs
  }

  function drawGantryFront() {
    screenSpace();
    var farTop = down(P(0, -26), -G.post);
    var pulse = 0.75 + 0.25 * Math.sin(t * 22);

    // Light curtain from the beam down onto the slab surface.
    var s0 = P(0, 0), s1 = P(0, G.h), b0 = down(P(0, 0), -G.post + 8), b1 = down(P(0, G.h), -G.post + 8);
    var lg = ctx.createLinearGradient(0, b0.y, 0, s1.y);
    lg.addColorStop(0, 'rgba(150,215,255,' + (0.10 * pulse).toFixed(3) + ')');
    lg.addColorStop(1, 'rgba(150,215,255,' + (0.42 * pulse).toFixed(3) + ')');
    poly([b0, b1, s1, s0]); ctx.fillStyle = lg; ctx.fill();

    // Scan line on the surface.
    trackSpace();
    var glow = ctx.createLinearGradient(-22, 0, 22, 0);
    glow.addColorStop(0, 'rgba(120,200,255,0)');
    glow.addColorStop(0.5, 'rgba(150,215,255,' + (0.35 * pulse).toFixed(3) + ')');
    glow.addColorStop(1, 'rgba(120,200,255,0)');
    ctx.fillStyle = glow; ctx.fillRect(-22, -6, 44, G.h + 12);
    ctx.fillStyle = 'rgba(230,246,255,' + (0.75 + 0.25 * pulse).toFixed(3) + ')';
    ctx.fillRect(-1.2, -6, 2.4, G.h + 12);

    // Near post and the beam.
    screenSpace();
    var nearTop = post(G.h + 26);
    ctx.strokeStyle = '#2f6eb3'; ctx.lineWidth = G.wide ? 9 : 7; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(farTop.x, farTop.y); ctx.lineTo(nearTop.x, nearTop.y); ctx.stroke();
    ctx.strokeStyle = 'rgba(255,255,255,.25)'; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(farTop.x, farTop.y - 3); ctx.lineTo(nearTop.x, nearTop.y - 3); ctx.stroke();
    ctx.lineCap = 'butt';

    // Camera head in the middle of the beam.
    var mid = { x: (farTop.x + nearTop.x) / 2, y: (farTop.y + nearTop.y) / 2 };
    var hw = G.wide ? 64 : 50, hh = G.wide ? 22 : 18;
    var hg = ctx.createLinearGradient(0, mid.y - hh / 2, 0, mid.y + hh / 2);
    hg.addColorStop(0, '#2a6db6'); hg.addColorStop(1, '#174a86');
    ctx.fillStyle = hg; rr(mid.x - hw / 2, mid.y - hh / 2, hw, hh, 4); ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.3)'; ctx.stroke();
    text('DORSA', mid.x, mid.y + 3.5, G.wide ? 9 : 8, 700, 'rgba(255,255,255,.92)', 'center', 1.5);
    for (var i = -2; i <= 2; i++) { ctx.fillStyle = 'rgba(230,245,255,' + (0.6 + 0.4 * pulse).toFixed(2) + ')'; ctx.fillRect(mid.x + i * (hw / 6) - 3, mid.y + hh / 2 - 3, 6, 2); }

    // Status tag.
    var tag = L.inspecting;
    ctx.font = '600 10px ' + C.font;
    var tw = ctx.measureText(tag).width + 26, tx = mid.x - hw / 2 - 12 - tw, ty = mid.y - 9;
    if (tx < 4 || avoided({ l: tx, t: ty, r: tx + tw, b: ty + 18 })) tx = mid.x + hw / 2 + 10;
    G.headTop = { x: mid.x, y: mid.y - hh / 2 - 2 };
    ctx.fillStyle = 'rgba(7,26,51,.9)'; rr(tx, ty, tw, 18, 9); ctx.fill();
    ctx.fillStyle = C.ok; ctx.beginPath(); ctx.arc(tx + 10, ty + 9, 3.2, 0, Math.PI * 2); ctx.fill();
    ctx.globalAlpha = 0.35 + 0.35 * Math.sin(t * 4); ctx.beginPath(); ctx.arc(tx + 10, ty + 9, 6.5, 0, Math.PI * 2); ctx.fill(); ctx.globalAlpha = 1;
    text(tag, tx + 18, ty + 13, 10, 600, '#9fe0bf', 'left', 1.2);
    G.nearBase = P(0, G.h + 26);
  }

  function fadeEdges() {
    screenSpace();
    var f = Math.min(90, G.W * 0.08);
    ctx.save();
    ctx.globalCompositeOperation = 'destination-out';
    var l = ctx.createLinearGradient(0, 0, f, 0);
    l.addColorStop(0, 'rgba(0,0,0,1)'); l.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = l; ctx.fillRect(0, 0, f, G.H);
    var r = ctx.createLinearGradient(G.W - f, 0, G.W, 0);
    r.addColorStop(0, 'rgba(0,0,0,0)'); r.addColorStop(1, 'rgba(0,0,0,1)');
    ctx.fillStyle = r; ctx.fillRect(G.W - f, 0, f, G.H);
    ctx.restore();
  }

  function drawLink() {
    screenSpace();
    var a, b, start, dir;
    if (G.stage) {
      // Chart above the track: from the camera head up to the bottom of the chart.
      start = G.headTop; dir = -1;
      b = { x: clamp(start.x, G.panelX + 28, G.panelX + G.panelW - 28), y: G.panelTop + G.panelH };
    } else {
      a = G.nearBase; dir = 1;
      b = G.wide ? { x: G.panelX + 28, y: G.panelTop } : { x: G.cx, y: G.panelTop };
      start = { x: a.x, y: a.y + 6 };
    }
    var turn = b.y - 14 * dir;
    ctx.strokeStyle = 'rgba(159,195,236,.3)'; ctx.setLineDash([3, 5]); ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(start.x, start.y); ctx.lineTo(start.x, turn); ctx.lineTo(b.x, turn); ctx.lineTo(b.x, b.y); ctx.stroke();
    ctx.setLineDash([]);
    if (!current || current.done) return;
    var seg1 = Math.abs(turn - start.y), seg2 = Math.abs(b.x - start.x), seg3 = 14, total = seg1 + seg2 + seg3;
    for (var i = 0; i < 3; i++) {
      var d = ((t * 0.7 + i / 3) % 1) * total, x, y;
      if (d < seg1) { x = start.x; y = start.y + dir * d; }
      else if (d < seg1 + seg2) { x = start.x + Math.sign(b.x - start.x) * (d - seg1); y = turn; }
      else { x = b.x; y = turn + dir * (d - seg1 - seg2); }
      ctx.fillStyle = 'rgba(159,215,255,.9)'; ctx.beginPath(); ctx.arc(x, y, 2.2, 0, Math.PI * 2); ctx.fill();
    }
  }

  /* ---------------- chart & history ---------------- */
  function drawPanel() {
    screenSpace();
    var X = G.panelX, Y = G.panelTop, W = G.panelW, H = G.panelH;
    ctx.fillStyle = 'rgba(11,37,69,.94)'; rr(X, Y, W, H, 8); ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.12)'; ctx.lineWidth = 1; ctx.stroke();

    var s = current, p = s ? (s.done ? 1 : progressOf(s)) : 0;
    var plotX = X + 20, plotW = W - 40;

    text(L.quality, plotX, Y + 26, 11, 600, C.sky, 'left', 1.3);
    if (s) text(s.id + ' · ' + s.lenM.toFixed(1) + ' m', plotX, Y + 46, G.mobile ? 15 : 17, 600, '#fff');
    var bx = plotX + plotW, by = Y + 18;
    if (s && s.done) {
      ctx.fillStyle = gradeColor(s.grade); rr(bx - 92, by, 92, 30, 6); ctx.fill();
      text(L.grade + ' ' + s.grade, bx - 46, by + 20, 13, 700, '#fff', 'center', 1);
    } else {
      ctx.strokeStyle = 'rgba(255,255,255,.25)'; rr(bx - 92, by, 92, 30, 6); ctx.stroke();
      text(L.scanning, bx - 46, by + 20, 11, 600, C.muted, 'center', 1.2);
    }

    var top = Y + 74, h = G.mobile ? 118 : 104, bottom = top + h;
    text(L.severity, plotX, top - 8, 10, 500, C.muted);
    ctx.strokeStyle = C.faint; ctx.lineWidth = 1; ctx.beginPath();
    for (var gy = 0; gy <= 4; gy++) { var yy = Math.round(top + gy * h / 4) + 0.5; ctx.moveTo(plotX, yy); ctx.lineTo(plotX + plotW, yy); }
    ctx.stroke();
    var ry = bottom - REJECT * h;
    ctx.strokeStyle = 'rgba(229,72,77,.6)'; ctx.setLineDash([4, 4]);
    ctx.beginPath(); ctx.moveTo(plotX, ry + 0.5); ctx.lineTo(plotX + plotW, ry + 0.5); ctx.stroke(); ctx.setLineDash([]);
    text(L.reject, plotX + plotW, ry - 5, 10, 500, 'rgba(229,72,77,.9)', 'right');

    if (s) {
      var n = s.signal.length - 1, upto = Math.floor(n * p);
      if (upto > 0) {
        ctx.beginPath(); ctx.moveTo(plotX, bottom);
        for (var i = 0; i <= upto; i++) ctx.lineTo(plotX + plotW * i / n, bottom - s.signal[i] * h);
        ctx.lineTo(plotX + plotW * upto / n, bottom); ctx.closePath();
        var fill = ctx.createLinearGradient(0, top, 0, bottom);
        fill.addColorStop(0, 'rgba(29,95,166,.55)'); fill.addColorStop(1, 'rgba(29,95,166,.05)');
        ctx.fillStyle = fill; ctx.fill();
        ctx.beginPath();
        for (var j = 0; j <= upto; j++) { var px = plotX + plotW * j / n, py = bottom - s.signal[j] * h; j ? ctx.lineTo(px, py) : ctx.moveTo(px, py); }
        ctx.strokeStyle = '#7fb6ef'; ctx.lineWidth = 1.5; ctx.stroke();
        s.defects.forEach(function (d, k) {
          if (d.u > p) return;
          var mx = plotX + plotW * d.u, my = bottom - d.sev * h, col = d.sev >= REJECT ? C.bad : C.gold;
          ctx.fillStyle = col; ctx.beginPath(); ctx.arc(mx, my, 3.5, 0, Math.PI * 2); ctx.fill();
          text(String(k + 1), mx, my - 8, 10, 700, col, 'center');
        });
        if (!s.done) { ctx.fillStyle = 'rgba(225,245,255,.8)'; ctx.fillRect(plotX + plotW * p, top - 4, 1, h + 8); }
      }
    }
    text('0 m', plotX, bottom + 16, 10, 500, C.muted);
    if (s) text(s.lenM.toFixed(1) + ' m', plotX + plotW, bottom + 16, 10, 500, C.muted, 'right');
    text(L.position, plotX + plotW / 2, bottom + 16, 10, 500, C.muted, 'center');

    var ky = bottom + (G.mobile ? 44 : 46);
    var found = s ? s.defects.filter(function (d) { return d.u <= p; }).length : 0;
    var kpis = [[L.scanned, s ? (s.lenM * p).toFixed(1) + ' / ' + s.lenM.toFixed(1) + ' m' : '—'], [L.defects, String(found)], [L.qi, (s ? qualityIndex(s, p) : 100).toFixed(1)]];
    var cw = plotW / 3;
    kpis.forEach(function (k, i) {
      text(k[0], plotX + i * cw, ky, 10, 600, C.muted, 'left', 1.1);
      text(k[1], plotX + i * cw, ky + 22, G.mobile ? 15 : 18, 600, '#fff');
    });
  }

  function drawHistory() {
    if (!G.wide || !G.histRows) return;
    screenSpace();
    var X = G.histX, Y = G.histY, W = 230;
    text(L.last, X, Y + 14, 11, 600, C.sky, 'left', 1.3);
    history.slice(0, G.histRows).forEach(function (s, i) {
      var y = Y + 34 + i * 40;
      ctx.globalAlpha = 1 - i * 0.14;
      text(s.id, X, y + 12, 12, 600, '#fff');
      text(s.defects.length + ' ' + (s.defects.length === 1 ? L.defect1 : L.defectN) + ' · QI ' + s.qi.toFixed(1), X, y + 28, 11, 500, C.muted);
      ctx.fillStyle = gradeColor(s.grade); rr(X + W - 28, y + 4, 28, 22, 5); ctx.fill();
      text(s.grade, X + W - 14, y + 20, 12, 700, '#fff', 'center');
      ctx.globalAlpha = 1;
    });
  }

  function draw() {
    screenSpace();
    ctx.clearRect(0, 0, G.W, G.H);
    drawBed();
    drawGantryBack();
    // Far slabs (upper right) first so nearer ones overlap them correctly.
    slabs.slice().sort(function (a, b) { return b.u - a.u; }).forEach(drawSlab);
    fadeEdges();
    drawGantryFront();
    drawLabels();
    drawLink();
    drawPanel();
    drawHistory();
  }

  /* ---------------- loop & lifecycle ---------------- */
  function frame(now) {
    var dt = last ? Math.min((now - last) / 1000, 0.05) : 0;
    last = now;
    update(dt);
    draw();
    if (running && visible) requestAnimationFrame(frame); else last = 0;
  }
  function start() { if (running && visible) { last = 0; requestAnimationFrame(frame); } }
  function reset() { layout(); seed(); draw(); }

  toggle.addEventListener('click', function () {
    running = !running;
    toggle.setAttribute('aria-pressed', String(!running));
    toggle.textContent = running ? L.pause : L.play;
    start();
  });
  if (reduce) { toggle.textContent = L.play; toggle.setAttribute('aria-pressed', 'true'); }

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (e) {
      var was = visible; visible = e[0].isIntersecting;
      if (visible && !was) start();
    }).observe(canvas);
  }
  document.addEventListener('visibilitychange', function () { visible = !document.hidden; start(); });

  var rw = 0, rh = 0;
  window.addEventListener('resize', function () {
    var stageNow = isStage();
    if (stage.clientWidth === rw && (!stageNow || stage.clientHeight === rh) && stageNow === !!G.stage) return;
    rw = stage.clientWidth; rh = stage.clientHeight;
    reset();
  });

  var ready = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
  function boot() { rw = stage.clientWidth; rh = stage.clientHeight; reset(); start(); }
  ready.then(boot, boot);
  }

  document.querySelectorAll('[data-rds-slab]').forEach(init);
})();
