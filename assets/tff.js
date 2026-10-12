/* The Fallow Few theme interactions. All content is visible without JavaScript; motion respects reduced-motion. */
(() => {
  const d = document;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const wide = window.matchMedia('(min-width: 768px)');
  const header = d.querySelector('[data-header]');
  const bar = d.querySelector('[data-progress]');
  const adminBar = d.getElementById('wpadminbar');
  const heroBg = d.querySelector('[data-parallax]');

  // Header turns solid once scrolled, sits under the admin bar, scroll progress, hero parallax (large screens only).
  let ticking = false;
  const frame = () => {
    ticking = false;
    const y = window.scrollY;
    const vh = window.innerHeight;
    if (header) {
      header.classList.toggle('scrolled', y > 24);
      header.style.top = adminBar ? Math.max(0, adminBar.getBoundingClientRect().bottom) + 'px' : '';
    }
    if (bar) {
      const max = d.documentElement.scrollHeight - vh;
      bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
    }
    if (heroBg && !reduce && wide.matches && y < vh * 1.2) heroBg.style.translate = '0 ' + (y * 0.25).toFixed(1) + 'px';
  };
  const onScroll = () => { if (!ticking) { ticking = true; window.requestAnimationFrame(frame); } };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });
  frame();

  // Phone menu.
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const closeMenu = () => { d.body.classList.remove('menu-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); };
  if (nav) [...nav.querySelectorAll('.menu > li')].forEach((li, i) => li.style.setProperty('--i', String(i)));
  if (toggle) toggle.addEventListener('click', () => {
    if (nav && header) nav.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
    const open = d.body.classList.toggle('menu-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  if (nav) nav.addEventListener('click', (e) => { if (e.target.closest('a')) closeMenu(); });
  d.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });

  // Scrollspy for the menu.
  const spyLinks = [...d.querySelectorAll('[data-spy]')];
  const spySections = spyLinks.map((a) => d.getElementById(a.dataset.spy)).filter(Boolean);
  if (spySections.length && 'IntersectionObserver' in window) {
    const spy = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) spyLinks.forEach((a) => a.classList.toggle('active', a.dataset.spy === entry.target.id));
    }), { rootMargin: '-45% 0px -50% 0px' });
    spySections.forEach((s) => spy.observe(s));
  }

  // Reveal on scroll with small staggers; a sweep shows anything already scrolled past (fast anchor jumps).
  const items = [...d.querySelectorAll('[data-reveal]')];
  const show = (el) => el.classList.add('in');
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(show);
  } else {
    const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) { show(entry.target); io.unobserve(entry.target); }
    }), { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    items.forEach((el) => {
      const siblings = [...el.parentElement.children].filter((c) => c.hasAttribute('data-reveal'));
      const index = siblings.indexOf(el);
      if (index > 0) el.style.setProperty('--d', String(Math.min(index, 5)));
      io.observe(el);
    });
    let pending = items.slice();
    let sweeping = false;
    const sweep = () => {
      sweeping = false;
      const limit = window.innerHeight * 0.92;
      pending = pending.filter((el) => {
        if (el.getBoundingClientRect().top < limit) { show(el); io.unobserve(el); return false; }
        return true;
      });
      if (!pending.length) window.removeEventListener('scroll', onSweep);
    };
    const onSweep = () => { if (!sweeping) { sweeping = true; window.setTimeout(sweep, 160); } };
    window.addEventListener('scroll', onSweep, { passive: true });
    window.addEventListener('load', sweep);
  }
  window.addEventListener('beforeprint', () => items.forEach(show));

  // Trailer: the YouTube player loads only when the poster is clicked.
  d.querySelectorAll('[data-video]').forEach((box) => {
    const button = box.querySelector('.video-play');
    if (!button) return;
    button.addEventListener('click', () => {
      const frameEl = d.createElement('iframe');
      frameEl.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(box.dataset.video) + '?autoplay=1&rel=0&playsinline=1';
      frameEl.title = 'The Fallow Few trailer';
      frameEl.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen';
      frameEl.allowFullscreen = true;
      box.replaceChildren(frameEl);
    });
  });

  // Gallery lightbox with keyboard and swipe support.
  const links = [...d.querySelectorAll('[data-lightbox]')];
  if (links.length && typeof HTMLDialogElement === 'function') {
    d.querySelectorAll('.lightbox').forEach((n) => n.remove());
    const box = d.createElement('dialog');
    box.className = 'lightbox';
    box.setAttribute('aria-label', 'Gallery image');
    box.innerHTML = '<button class="lb-close" type="button" aria-label="Close">×</button><button class="lb-prev" type="button" aria-label="Previous image">‹</button><img alt=""><button class="lb-next" type="button" aria-label="Next image">›</button>';
    d.body.appendChild(box);
    const img = box.querySelector('img');
    let current = 0;
    const go = (i) => { current = (i + links.length) % links.length; img.src = links[current].href; img.alt = links[current].querySelector('img').alt; };
    links.forEach((a, i) => a.addEventListener('click', (e) => { e.preventDefault(); go(i); box.showModal(); }));
    box.querySelector('.lb-close').addEventListener('click', () => box.close());
    box.querySelector('.lb-prev').addEventListener('click', () => go(current - 1));
    box.querySelector('.lb-next').addEventListener('click', () => go(current + 1));
    box.addEventListener('click', (e) => { if (e.target === box) box.close(); });
    box.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') go(current - 1); if (e.key === 'ArrowRight') go(current + 1); });
    let startX = null;
    box.addEventListener('touchstart', (e) => { startX = e.changedTouches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', (e) => {
      if (startX === null) return;
      const dx = e.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 50) go(current + (dx < 0 ? 1 : -1));
    }, { passive: true });
  }
})();
