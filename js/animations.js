/* ============================================================
   GPS LANKA TRAVELS — animations.js
   Scroll reveal, number counters, parallax effects
   Include on every page after components.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ─── SCROLL REVEAL ─────────────────────────────────────
     Watches .reveal / .reveal-left / .reveal-right / .reveal-scale
     Adds .visible class when element enters viewport
  ──────────────────────────────────────────────────────── */
  const revealSelectors = '.reveal, .reveal-left, .reveal-right, .reveal-scale';
  const revealEls = document.querySelectorAll(revealSelectors);

  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  revealEls.forEach(el => revealObserver.observe(el));

  /* ─── STAGGERED CHILDREN REVEAL ────────────────────────
     Add class .stagger-children to a parent and all direct
     children get auto-staggered transition delays
  ──────────────────────────────────────────────────────── */
  document.querySelectorAll('.stagger-children').forEach(parent => {
    [...parent.children].forEach((child, i) => {
      child.style.transitionDelay = `${i * 0.08}s`;
    });
  });

  /* ─── NUMBER COUNTER ANIMATION ─────────────────────────
     Usage: <span data-count="500">0</span>
     Counts up to the target number when scrolled into view
  ──────────────────────────────────────────────────────── */
  function animateCounter(el, target, duration = 2000) {
    let start = 0;
    const step = target / (duration / 16);
    const suffix = el.dataset.suffix || '+';

    const timer = setInterval(() => {
      start += step;
      if (start >= target) {
        el.textContent = target.toLocaleString() + suffix;
        clearInterval(timer);
      } else {
        el.textContent = Math.floor(start).toLocaleString() + suffix;
      }
    }, 16);
  }

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const target = parseInt(entry.target.dataset.count);
        animateCounter(entry.target, target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));

  /* ─── PARALLAX EFFECT ───────────────────────────────────
     Add data-parallax="0.3" to any element to give it
     a vertical parallax offset on scroll (0.1 = subtle, 0.5 = strong)
  ──────────────────────────────────────────────────────── */
  const parallaxEls = document.querySelectorAll('[data-parallax]');

  if (parallaxEls.length) {
    window.addEventListener('scroll', () => {
      const scrollY = window.pageYOffset;
      parallaxEls.forEach(el => {
        const speed  = parseFloat(el.dataset.parallax) || 0.3;
        const rect   = el.getBoundingClientRect();
        const offset = (rect.top + scrollY) * speed;
        el.style.transform = `translateY(${offset * 0.1}px)`;
      });
    }, { passive: true });
  }

  /* ─── FADE-IN ON LOAD ───────────────────────────────────
     Elements with class .fade-on-load fade in immediately
     on page load (for hero content etc.)
  ──────────────────────────────────────────────────────── */
  document.querySelectorAll('.fade-on-load').forEach((el, i) => {
    el.style.animation = `fadeUp 0.8s ${i * 0.15}s both`;
  });

  /* ─── SMOOTH ANCHOR SCROLL ──────────────────────────────
     Overrides default anchor jump with smooth scroll
  ──────────────────────────────────────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ─── CURSOR GLOW (subtle luxury effect) ───────────────
     Adds a soft gold glow that follows the cursor.
     Remove this block if you don't want it.
  ──────────────────────────────────────────────────────── */
  const cursor = document.createElement('div');
  cursor.style.cssText = `
    position: fixed; pointer-events: none; z-index: 9999;
    width: 12px; height: 12px; border-radius: 50%;
    background: rgba(201,168,76,0.5);
    transform: translate(-50%, -50%);
    transition: transform 0.1s ease, opacity 0.3s;
    mix-blend-mode: screen;
  `;
  document.body.appendChild(cursor);

  document.addEventListener('mousemove', e => {
    cursor.style.left = e.clientX + 'px';
    cursor.style.top  = e.clientY + 'px';
  });
  document.addEventListener('mouseleave', () => { cursor.style.opacity = '0'; });
  document.addEventListener('mouseenter', () => { cursor.style.opacity = '1'; });

});
