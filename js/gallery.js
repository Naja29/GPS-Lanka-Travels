document.addEventListener('DOMContentLoaded', () => {

  const items   = document.querySelectorAll('.gallery-item');
  const pills   = document.querySelectorAll('.gallery-pill');
  const lb      = document.getElementById('lightbox');
  const lbImg   = document.getElementById('lightboxImg');
  const lbClose = document.getElementById('lightboxClose');
  const lbPrev  = document.getElementById('lightboxPrev');
  const lbNext  = document.getElementById('lightboxNext');
  const lbCount = document.getElementById('lightboxCounter');

  let visibleItems = [...items];
  let currentIndex = 0;

  /* Filter pills */
  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      pills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      const f = pill.dataset.filter;

      items.forEach(item => {
        const show = f === 'all' || item.dataset.cat === f;
        item.style.display = show ? '' : 'none';
      });

      visibleItems = [...items].filter(i => i.style.display !== 'none');
    });
  });

  /* Open lightbox */
  items.forEach((item, idx) => {
    item.addEventListener('click', () => {
      visibleItems = [...items].filter(i => i.style.display !== 'none');
      currentIndex = visibleItems.indexOf(item);
      openLightbox(currentIndex);
    });
  });

  function openLightbox(idx) {
    const src = visibleItems[idx]?.querySelector('img')?.src;
    if (!src) return;
    lbImg.src = src;
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
    updateCounter();
  }

  function closeLightbox() {
    lb.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(() => { lbImg.src = ''; }, 350);
  }

  function navigate(dir) {
    currentIndex = (currentIndex + dir + visibleItems.length) % visibleItems.length;
    lbImg.style.opacity = '0';
    setTimeout(() => {
      lbImg.src = visibleItems[currentIndex]?.querySelector('img')?.src || '';
      lbImg.style.opacity = '1';
      updateCounter();
    }, 180);
  }

  function updateCounter() {
    if (lbCount) lbCount.textContent = `${currentIndex + 1} / ${visibleItems.length}`;
  }

  lbClose?.addEventListener('click', closeLightbox);
  lbPrev?.addEventListener('click',  () => navigate(-1));
  lbNext?.addEventListener('click',  () => navigate(1));

  /* Close on backdrop click */
  lb?.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });

  /* Keyboard nav */
  document.addEventListener('keydown', e => {
    if (!lb?.classList.contains('open')) return;
    if (e.key === 'Escape')     closeLightbox();
    if (e.key === 'ArrowLeft')  navigate(-1);
    if (e.key === 'ArrowRight') navigate(1);
  });

  /* Smooth image fade */
  lbImg && (lbImg.style.transition = 'opacity .18s');

});
