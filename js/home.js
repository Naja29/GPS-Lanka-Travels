/* ============================================================
   GPS LANKA TRAVELS — home.js
   Homepage-only JavaScript:
   - Testimonial carousel
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ─── TESTIMONIAL SLIDER ────────────────────────────────── */
  const track    = document.getElementById('testiTrack');
  const prevBtn  = document.getElementById('testiPrev');
  const nextBtn  = document.getElementById('testiNext');

  if (!track) return;

  const cards    = track.querySelectorAll('.testimonial-card');
  let   index    = 0;
  let   autoPlay;

  /* How many cards are visible at current breakpoint */
  function visibleCount() {
    if (window.innerWidth < 768)  return 1;
    if (window.innerWidth < 1024) return 2;
    return 3;
  }

  function maxIndex() {
    return Math.max(0, cards.length - visibleCount());
  }

  function getCardWidth() {
    return cards[0].offsetWidth + 28; // 28 = gap in CSS
  }

  function update() {
    if (index < 0)          index = maxIndex();
    if (index > maxIndex()) index = 0;
    track.style.transform = `translateX(-${index * getCardWidth()}px)`;
  }

  function startAuto() {
    clearInterval(autoPlay);
    autoPlay = setInterval(() => { index++; update(); }, 5000);
  }

  /* Init */
  update();
  startAuto();

  nextBtn?.addEventListener('click', () => { index++; update(); startAuto(); });
  prevBtn?.addEventListener('click', () => { index--; update(); startAuto(); });

  window.addEventListener('resize', update);

});
