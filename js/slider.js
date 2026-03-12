document.addEventListener('DOMContentLoaded', () => {

  const slides  = document.querySelectorAll('.hero-slide');
  const dots    = document.querySelectorAll('.slider-dot');
  const prevBtn = document.getElementById('sliderPrev');
  const nextBtn = document.getElementById('sliderNext');

  // No slider on this page — exit silently
  if (!slides.length) return;

  let current = 0;
  let timer;
  const INTERVAL = 3500; // 3.5s — professional feel, not too fast/slow

  /* Go to specific slide */
  function goTo(n) {
    slides[current].classList.remove('active');
    dots[current]?.classList.remove('active');

    current = (n + slides.length) % slides.length;

    slides[current].classList.add('active');
    dots[current]?.classList.add('active');
  }

  /* Auto-play */
  function startTimer() {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), INTERVAL);
  }

  /* Init */
  goTo(0);
  startTimer();

  /* Arrow buttons */
  nextBtn?.addEventListener('click', () => { goTo(current + 1); startTimer(); });
  prevBtn?.addEventListener('click', () => { goTo(current - 1); startTimer(); });

  /* Dot buttons */
  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => { goTo(i); startTimer(); });
  });

  /* Touch / swipe support */
  let touchStartX = 0;
  const heroEl = document.querySelector('.hero');

  heroEl?.addEventListener('touchstart', e => {
    touchStartX = e.changedTouches[0].clientX;
  }, { passive: true });

  heroEl?.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) {
      diff > 0 ? goTo(current + 1) : goTo(current - 1);
      startTimer();
    }
  });

  /* Keyboard support */
  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') { goTo(current + 1); startTimer(); }
    if (e.key === 'ArrowLeft')  { goTo(current - 1); startTimer(); }
  });

  /* Pause on hover */
  const heroSection = document.querySelector('.hero');
  heroSection?.addEventListener('mouseenter', () => clearInterval(timer));
  heroSection?.addEventListener('mouseleave', startTimer);

});
