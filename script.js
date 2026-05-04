// Mr Bee's Brewery — interactions

(() => {
  // Scroll progress bar
  const bar = document.getElementById('progressBar');
  const navbar = document.getElementById('navbar');

  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY;
    const total = document.documentElement.scrollHeight - window.innerHeight;
    if (bar) bar.style.width = (scrolled / total * 100) + '%';
    if (navbar) navbar.classList.toggle('scrolled', scrolled > 60);
  }, { passive: true });

  // Mobile nav
  const toggle = document.getElementById('navToggle');
  const links  = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = toggle.classList.toggle('open');
      links.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
    });
    links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      toggle.classList.remove('open');
      links.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    }));
  }

  // Scroll reveal
  const reveals = document.querySelectorAll(
    '.section-head, .brew-card, .story-copy, .story-media, .stat-item, .gal-item, .t-card, .cta-info, .cta-form-wrap, .hero-copy, .hero-visual, .story-values li'
  );
  reveals.forEach((el, i) => {
    el.classList.add('reveal');
    if (i % 4 === 1) el.classList.add('reveal-delay-1');
    if (i % 4 === 2) el.classList.add('reveal-delay-2');
    if (i % 4 === 3) el.classList.add('reveal-delay-3');
  });
  const revealIO = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); revealIO.unobserve(e.target); } });
  }, { threshold: 0.12 });
  reveals.forEach(el => revealIO.observe(el));

  // Stat counter
  const ease = t => 1 - Math.pow(1 - t, 3);
  const fmt  = n => Math.floor(n).toLocaleString('en-GB');
  const animateStat = el => {
    const target = +el.dataset.count;
    const start  = performance.now();
    const dur    = 2000;
    const tick   = now => {
      const p = Math.min((now - start) / dur, 1);
      el.textContent = fmt(target * ease(p));
      if (p < 1) requestAnimationFrame(tick);
      else el.textContent = fmt(target);
    };
    requestAnimationFrame(tick);
  };
  const statIO = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { animateStat(e.target); statIO.unobserve(e.target); } });
  }, { threshold: 0.5 });
  document.querySelectorAll('.stat-num[data-count]').forEach(el => statIO.observe(el));

  // Hero mascot parallax
  const mascot = document.querySelector('.mascot-art');
  const hero   = document.querySelector('.hero');
  if (mascot && hero && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    hero.addEventListener('mousemove', e => {
      const r = hero.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width  - 0.5;
      const y = (e.clientY - r.top)  / r.height - 0.5;
      mascot.style.transform = `translate(${x * 14}px, ${y * 14}px)`;
    });
    hero.addEventListener('mouseleave', () => { mascot.style.transform = ''; });
  }

  // Newsletter form
  window.handleSubscribe = e => {
    e.preventDefault();
    const btn = document.getElementById('subBtn');
    if (btn) {
      btn.textContent = '✓ You\'re on the list';
      btn.style.background = '#2a7a2a';
      btn.disabled = true;
    }
  };
})();
