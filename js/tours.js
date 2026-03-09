/* ============================================================
   GPS LANKA TRAVELS — tours.js
   Filter by category, live search, sort, wishlist toggle
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  const cards      = document.querySelectorAll('.tour-card[data-category]');
  const pills      = document.querySelectorAll('.filter-pill');
  const searchInput= document.getElementById('tourSearch');
  const sortSelect = document.getElementById('tourSort');
  const countEl    = document.getElementById('tourCount');
  const grid       = document.getElementById('toursGrid');
  const emptyState = document.getElementById('toursEmpty');

  let activeCategory = 'all';
  let searchQuery    = '';

  /* ── Filter + Search ── */
  function applyFilters() {
    let visible = 0;
    const q = searchQuery.toLowerCase();

    cards.forEach(card => {
      const cat   = card.dataset.category || '';
      const text  = card.innerText.toLowerCase();
      const matchCat  = activeCategory === 'all' || cat === activeCategory;
      const matchSearch = !q || text.includes(q);

      if (matchCat && matchSearch) {
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });

    // Update count
    if (countEl) countEl.innerHTML = `Showing <span>${visible}</span> tours`;

    // Empty state
    if (emptyState) {
      emptyState.classList.toggle('visible', visible === 0);
    }
  }

  /* ── Sort ── */
  function applySort(val) {
    const cardArr = [...cards].filter(c => c.style.display !== 'none');
    const allCards = [...cards];

    allCards.sort((a, b) => {
      const priceA = parseFloat(a.dataset.price || 0);
      const priceB = parseFloat(b.dataset.price || 0);
      const daysA  = parseInt(a.dataset.days || 0);
      const daysB  = parseInt(b.dataset.days || 0);

      if (val === 'price-low')  return priceA - priceB;
      if (val === 'price-high') return priceB - priceA;
      if (val === 'days-low')   return daysA - daysB;
      if (val === 'days-high')  return daysB - daysA;
      return 0;
    });

    allCards.forEach(card => grid.appendChild(card));
  }

  /* ── Category pills ── */
  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      pills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeCategory = pill.dataset.filter;
      applyFilters();
    });
  });

  /* ── Search ── */
  searchInput?.addEventListener('input', e => {
    searchQuery = e.target.value;
    applyFilters();
  });

  /* ── Sort ── */
  sortSelect?.addEventListener('change', e => {
    applySort(e.target.value);
  });

  /* ── Wishlist heart toggle ── */
  document.querySelectorAll('.tour-wish').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      btn.classList.toggle('active');
    });
  });

  /* ── Init ── */
  applyFilters();

});
