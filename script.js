// Mr Bee's Brewery — landing page interactions

(() => {
  // Mobile nav toggle
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = toggle.classList.toggle('open');
      links.classList.toggle('show', open);
      toggle.setAttribute('aria-expanded', String(open));
    });
    links.querySelectorAll('a').forEach(a =>
      a.addEventListener('click', () => {
        toggle.classList.remove('open');
        links.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
      })
    );
  }

  // Scroll reveal — add .reveal to all major sections, animate when in view
  const revealTargets = document.querySelectorAll(
    '.section-head, .brew-card, .story-copy, .story-art, .stat, .gal, .t-card, .cta-card, .hero-copy, .hero-visual'
  );
  revealTargets.forEach(el => el.classList.add('reveal'));

  const io = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );
  revealTargets.forEach(el => io.observe(el));

  // Stat counter animation
  const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
  const formatNumber = n => Math.floor(n).toLocaleString('en-US');

  const animateCount = el => {
    const target = +el.dataset.count;
    const duration = 1800;
    const start = performance.now();
    const tick = now => {
      const p = Math.min((now - start) / duration, 1);
      el.textContent = formatNumber(target * easeOutCubic(p));
      if (p < 1) requestAnimationFrame(tick);
      else el.textContent = formatNumber(target);
    };
    requestAnimationFrame(tick);
  };

  const counterIO = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          counterIO.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.4 }
  );
  document.querySelectorAll('.stat-num[data-count]').forEach(el => counterIO.observe(el));

  // Subtle parallax on hero mascot — track mouse position
  const mascot = document.querySelector('.bee-mascot');
  const hero = document.querySelector('.hero');
  if (mascot && hero && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    hero.addEventListener('mousemove', e => {
      const r = hero.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5;
      const y = (e.clientY - r.top) / r.height - 0.5;
      mascot.style.transform = `translate(${x * 18}px, ${y * 18}px)`;
    });
    hero.addEventListener('mouseleave', () => {
      mascot.style.transform = '';
    });
  }
})();
