<?php
$currentPage = 'gallery';
require_once 'includes/config.php';

/* ── Testimonials ── */
$testimonials = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order ASC, id ASC LIMIT 12");
    if ($r) while ($row = $r->fetch_assoc()) $testimonials[] = $row;
}

/* ── Gallery ── */
$gallery = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    if ($r) while ($row = $r->fetch_assoc()) $gallery[] = $row;
}

/* ── Badge label map (all possible) ── */
$allBadgeLabels = [
    'guests'   => 'Happy Guests',
    'wildlife' => 'Wildlife',
    'nature'   => 'Nature & Scenery',
    'culture'  => 'Culture & Heritage',
    'beach'    => 'Beach & Coast',
    'misc'     => 'More',
];

/* ── Only keep categories that exist in active gallery items ── */
$badgeLabel = [];
if ($conn) {
    $r = $conn->query("SELECT DISTINCT category FROM gallery WHERE is_active = 1 AND category != '' AND category IS NOT NULL ORDER BY FIELD(category,'guests','wildlife','nature','culture','beach','misc')");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $key = $row['category'];
            if (isset($allBadgeLabels[$key])) {
                $badgeLabel[$key] = $allBadgeLabels[$key];
            }
        }
    }
}
if (!$badgeLabel) $badgeLabel = $allBadgeLabels; /* fallback if DB empty */

$_whatsapp = setting('site_whatsapp', '94770489956');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?= e(setting('meta_description','Gallery & Testimonials | See what our guests say and discover Sri Lanka through our travel photos with GPS Lanka Travels.')) ?>"/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e(setting('site_name','GPS Lanka Travels')) ?>"/>
  <meta property="og:title"       content="Gallery & Testimonials | <?= e(setting('site_name','GPS Lanka Travels')) ?>"/>
  <meta property="og:description" content="See what our guests say and discover Sri Lanka through our travel photos with GPS Lanka Travels."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/gallery-hero.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/gallery.php"/>
  <title>Gallery & Testimonials | <?= e(setting('site_name','GPS Lanka Travels')) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/gallery.css"/>
</head>
<body data-page="gallery">

<?php include 'includes/header.php'; ?>

  <!-- PAGE HERO -->
  <section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-content">
      <div class="page-breadcrumb">
        <a href="index.php">Home</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        Gallery & Testimonials
      </div>
      <h1 class="page-hero-title">Gallery & <em>Testimonials</em></h1>
      <p class="page-hero-sub">See what our guests say and experience Sri Lanka through our lens</p>
    </div>
  </section>


  <!-- TESTIMONIALS -->
  <section class="gallery-testimonials section-pad">
    <div class="container">
      <div class="section-header">
        <div class="section-tag reveal">Guest Reviews</div>
        <h2 class="section-title reveal">Guest <em>Love</em></h2>
        <p class="section-subtitle reveal">Discover why our guests fall in love with Sri Lanka. Read authentic reviews and real heartfelt stories from travellers who have experienced our hospitality.</p>
      </div>

      <!-- Slider wrapper -->
      <div class="ts-slider-wrap">
        <button class="ts-arrow ts-prev" id="tsPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
        <button class="ts-arrow ts-next" id="tsNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>

        <div class="ts-viewport" id="tsViewport">
          <div class="ts-track" id="tsTrack">

            <?php if (!empty($testimonials)):
              foreach ($testimonials as $t): ?>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><?= starRating((int)($t['rating'] ?? 5)) ?></div>
                <p class="test-text">"<?= e($t['review'] ?? '') ?>"</p>
                <div class="test-author">
                  <?php if (!empty($t['photo'])): ?>
                    <div class="test-avatar"><img src="<?= e(imgUrl($t['photo'])) ?>" alt="<?= e($t['name']) ?>"/></div>
                  <?php else: ?>
                    <div class="test-avatar"><i class="fas fa-user"></i></div>
                  <?php endif; ?>
                  <div>
                    <div class="test-name"><?= e($t['name']) ?></div>
                    <div class="test-from"><i class="fas fa-map-marker-alt"></i> <?= e($t['country'] ?? '') ?></div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach;
            else: ?>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="test-text">"Our guide was absolutely incredible! He knew exactly where to take us and the driver was so dependable and professional. Cannot recommend enough!"</p>
                <div class="test-author"><div class="test-avatar"><i class="fas fa-user"></i></div><div><div class="test-name">Rajesh Kanwar</div><div class="test-from"><i class="fas fa-map-marker-alt"></i> India</div></div></div>
              </div>
            </div>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="test-text">"The blue whale safari in Mirissa was a dream come true. The crew followed all professional guidelines and we felt very safe. A truly breathtaking experience!"</p>
                <div class="test-author"><div class="test-avatar"><i class="fas fa-user"></i></div><div><div class="test-name">Noetha Nyoni</div><div class="test-from"><i class="fas fa-map-marker-alt"></i> France</div></div></div>
              </div>
            </div>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="test-text">"Exploring the cinnamon islands and ancient temples was the highlight of our trip. The most authentic Sri Lanka experience we have ever had!"</p>
                <div class="test-author"><div class="test-avatar"><i class="fas fa-user"></i></div><div><div class="test-name">Jody Lu Li</div><div class="test-from"><i class="fas fa-map-marker-alt"></i> USA</div></div></div>
              </div>
            </div>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="test-text">"Hotel pickup was on time, the guide was knowledgeable and funny, and the elephant orphanage was absolutely magical. A perfect Sri Lanka memory."</p>
                <div class="test-author"><div class="test-avatar"><i class="fas fa-user"></i></div><div><div class="test-name">Sophie M.</div><div class="test-from"><i class="fas fa-map-marker-alt"></i> Australia</div></div></div>
              </div>
            </div>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="test-text">"Booked a 10-day island-wide tour and it completely exceeded expectations. Every hotel was hand-picked and GPS Lanka Travels treated us like family."</p>
                <div class="test-author"><div class="test-avatar"><i class="fas fa-user"></i></div><div><div class="test-name">Marco Bianchi</div><div class="test-from"><i class="fas fa-map-marker-alt"></i> Italy</div></div></div>
              </div>
            </div>
            <div class="ts-slide">
              <div class="test-card">
                <div class="test-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="test-text">"The Yala safari was one of the most thrilling experiences of my life! Leopards, elephants, sloth bears and a crocodile — all in one day. Unforgettable!"</p>
                <div class="test-author"><div class="test-avatar"><i class="fas fa-user"></i></div><div><div class="test-name">Lena Fischer</div><div class="test-from"><i class="fas fa-map-marker-alt"></i> Germany</div></div></div>
              </div>
            </div>
            <?php endif; ?>

          </div><!-- /ts-track -->
        </div><!-- /ts-viewport -->

        <!-- Dots -->
        <div class="ts-dots" id="tsDots"></div>
      </div><!-- /ts-slider-wrap -->

      <!-- Trust Badges -->
      <div class="trust-badges-row reveal">
        <div class="trust-badge-item">
          <div class="badge-logo google">G</div>
          <div class="trust-badge-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <div class="trust-badge-label">Google Reviews</div>
        </div>
        <div class="trust-badge-item">
          <div class="badge-logo facebook"><i class="fab fa-facebook"></i></div>
          <div class="trust-badge-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <div class="trust-badge-label">Facebook Reviews</div>
        </div>
        <div class="trust-badge-item">
          <div class="badge-logo booking" style="font-size:15px;font-weight:800;">Booking.com</div>
          <div class="trust-badge-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <div class="trust-badge-label">Booking Reviews</div>
        </div>
        <div class="trust-badge-item">
          <div class="badge-logo tripadv"><i class="fab fa-tripadvisor"></i> TripAdvisor</div>
          <div class="trust-badge-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <div class="trust-badge-label">TripAdvisor Rated</div>
        </div>
      </div>

    </div>
  </section>


  <!-- GALLERY -->
  <section class="gallery-section section-pad">
    <div class="container">
      <div class="section-header">
        <div class="section-tag reveal">Photo Gallery</div>
        <h2 class="section-title reveal">Our <em>Gallery</em></h2>
        <p class="section-subtitle reveal">A glimpse into the incredible moments, places and people that make every GPS Lanka Travels journey truly special.</p>
      </div>

      <!-- Filter pills -->
      <div class="gallery-filter-bar reveal">
        <button class="gallery-pill active" data-filter="all">All Photos</button>
        <?php foreach ($badgeLabel as $key => $label): ?>
          <button class="gallery-pill" data-filter="<?= e($key) ?>"><?= e($label) ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Masonry Grid -->
      <div class="gallery-masonry" id="galleryGrid">

        <?php if (!empty($gallery)): ?>
          <?php foreach ($gallery as $g):
            $fn    = $g['filename'] ?? '';
            if (!$fn || !str_starts_with($fn, 'uploads/')) continue; // skip old/invalid entries
            $cat   = $g['category'] ?? 'misc';
            $label = $badgeLabel[$cat] ?? ucfirst($cat);
            $alt   = e($g['caption'] ?? $g['alt_text'] ?? $label);
            $src   = imgUrl($fn);
          ?>
          <div class="gallery-item" data-cat="<?= e($cat) ?>">
            <img src="<?= e($src) ?>" alt="<?= $alt ?>" loading="lazy"/>
            <div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div>
            <span class="gallery-item-badge gbadge-<?= e($cat) ?>"><?= e($label) ?></span>
          </div>
          <?php endforeach; ?>

        <?php else: ?>
          <!-- Static fallback -->
          <div class="gallery-item" data-cat="guests"><img src="images/gallery-1.jpg" alt="Happy Guests" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-guests">Guests</span></div>
          <div class="gallery-item" data-cat="guests"><img src="images/gallery-2.jpg" alt="Happy Guests" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-guests">Guests</span></div>
          <div class="gallery-item" data-cat="wildlife"><img src="images/gallery-3.jpg" alt="Wildlife" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-wildlife">Wildlife</span></div>
          <div class="gallery-item" data-cat="nature"><img src="images/gallery-4.jpg" alt="Sri Lanka Scenery" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-nature">Nature</span></div>
          <div class="gallery-item" data-cat="guests"><img src="images/gallery-5.jpg" alt="Happy Guests" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-guests">Guests</span></div>
          <div class="gallery-item" data-cat="wildlife"><img src="images/gallery-6.jpg" alt="Wildlife" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-wildlife">Wildlife</span></div>
          <div class="gallery-item" data-cat="guests"><img src="images/dest-ahangama.jpg" alt="Happy Guests" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-guests">Guests</span></div>
          <div class="gallery-item" data-cat="culture"><img src="images/dest-arugam.jpg" alt="Cultural Heritage" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-culture">Culture</span></div>
          <div class="gallery-item" data-cat="wildlife"><img src="images/dest-dambulla.jpg" alt="Wildlife" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-wildlife">Wildlife</span></div>
          <div class="gallery-item" data-cat="nature"><img src="images/dest-hatton.jpg" alt="Sri Lanka Scenery" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-nature">Nature</span></div>
          <div class="gallery-item" data-cat="wildlife"><img src="images/dest-ella.jpg" alt="Wildlife" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-wildlife">Wildlife</span></div>
          <div class="gallery-item" data-cat="beach"><img src="images/dest-horton.jpg" alt="Beach" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-beach">Beach</span></div>
          <div class="gallery-item" data-cat="nature"><img src="images/dest-jaffna.jpg" alt="Sri Lanka Scenery" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-nature">Nature</span></div>
          <div class="gallery-item" data-cat="wildlife"><img src="images/dest-minneriya.jpg" alt="Wildlife" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-wildlife">Wildlife</span></div>
          <div class="gallery-item" data-cat="beach"><img src="images/dest-mirissa.jpg" alt="Beach" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-beach">Beach</span></div>
          <div class="gallery-item" data-cat="culture"><img src="images/dest-sigiriya.jpg" alt="Cultural Heritage" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-culture">Culture</span></div>
          <div class="gallery-item" data-cat="guests"><img src="images/dest-unawatuna.jpg" alt="Happy Guests" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-guests">Guests</span></div>
          <div class="gallery-item" data-cat="nature"><img src="images/dest-waterfalls.jpg" alt="Sri Lanka Scenery" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-nature">Nature</span></div>
          <div class="gallery-item" data-cat="beach"><img src="images/about-2.jpg" alt="Beach" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-beach">Beach</span></div>
          <div class="gallery-item" data-cat="guests"><img src="images/about-circle.jpg" alt="Happy Guests" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-guests">Guests</span></div>
          <div class="gallery-item" data-cat="culture"><img src="images/about-hero.jpg" alt="Cultural Heritage" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-culture">Culture</span></div>
          <div class="gallery-item" data-cat="nature"><img src="images/about-main.jpg" alt="Sri Lanka Scenery" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-nature">Nature</span></div>
          <div class="gallery-item" data-cat="beach"><img src="images/about-story-1.jpg" alt="Beach" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-beach">Beach</span></div>
          <div class="gallery-item" data-cat="culture"><img src="images/about-story-2.jpg" alt="Cultural Heritage" loading="lazy"/><div class="gallery-overlay"><i class="fas fa-expand"></i><span>View</span></div><span class="gallery-item-badge gbadge-culture">Culture</span></div>
        <?php endif; ?>

      </div>
    </div>
  </section>


  <!-- LIGHTBOX -->
  <div id="lightbox" class="lightbox">
    <button id="lightboxClose" class="lightbox-close"><i class="fas fa-times"></i></button>
    <button id="lightboxPrev"  class="lightbox-prev"><i class="fas fa-chevron-left"></i></button>
    <div class="lightbox-img-wrap">
      <img id="lightboxImg" src="" alt="Gallery Image"/>
    </div>
    <button id="lightboxNext"  class="lightbox-next"><i class="fas fa-chevron-right"></i></button>
    <div id="lightboxCounter" class="lightbox-counter"></div>
  </div>


  <!-- CTA -->
  <section class="gallery-cta section-pad">
    <div class="container">
      <div class="gallery-cta-inner reveal">
        <div class="section-tag">Looking for an</div>
        <h2 class="section-title">Exclusive <em>Customized Tour?</em></h2>
        <p>No problem! Tell us your dream itinerary and we'll craft the perfect Sri Lanka experience — tailored entirely to you.</p>
        <div class="gallery-cta-btns">
          <a href="contact.php#contact-form" class="btn-primary"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
          <a href="https://wa.me/<?= e($_whatsapp) ?>" class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Scenic parallax banner -->
  <div class="scenic-banner">
    <div class="scenic-banner-text reveal">
      <h2>Every Photo Tells<br><strong>A Sri Lanka Story</strong></h2>
    </div>
  </div>

<?php include 'includes/footer.php'; ?>

<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script src="js/gallery.js"></script>
<script>
/* Auto-colour badges for custom (non-predefined) categories */
(function(){
  const known = ['guests','nature','wildlife','culture','beach','misc'];
  function hashColor(str) {
    let h = 0;
    for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0;
    const hue = h % 360;
    return `hsla(${hue},55%,38%,0.88)`;
  }
  document.querySelectorAll('.gallery-item-badge').forEach(el => {
    const cat = el.closest('[data-cat]')?.dataset.cat || '';
    if (cat && !known.includes(cat)) el.style.background = hashColor(cat);
  });
})();
</script>

<script>
/* ── TESTIMONIAL SLIDER ── */
(function () {
  const track    = document.getElementById('tsTrack');
  const viewport = document.getElementById('tsViewport');
  const dotsWrap = document.getElementById('tsDots');
  const btnPrev  = document.getElementById('tsPrev');
  const btnNext  = document.getElementById('tsNext');
  if (!track) return;

  const slides   = Array.from(track.querySelectorAll('.ts-slide'));
  const total    = slides.length;
  if (total === 0) return;

  let perView = window.innerWidth >= 1025 ? 3 : window.innerWidth >= 769 ? 2 : 1;
  let current = 0;
  let autoTimer;

  /* Build dots */
  const pageCount = () => Math.ceil(total / perView);
  function buildDots() {
    dotsWrap.innerHTML = '';
    for (let i = 0; i < pageCount(); i++) {
      const d = document.createElement('button');
      d.className = 'ts-dot' + (i === 0 ? ' active' : '');
      d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
      d.addEventListener('click', () => goTo(i * perView));
      dotsWrap.appendChild(d);
    }
  }

  function updateDots() {
    const page = Math.floor(current / perView);
    dotsWrap.querySelectorAll('.ts-dot').forEach((d, i) => d.classList.toggle('active', i === page));
  }

  function updateArrows() {
    btnPrev.disabled = current === 0;
    btnNext.disabled = current >= total - perView;
  }

  function getSlideWidth() {
    const gap  = 24;
    const vw   = viewport.offsetWidth;
    return (vw - gap * (perView - 1)) / perView + gap;
  }

  function goTo(idx) {
    const max = Math.max(0, total - perView);
    current = Math.max(0, Math.min(idx, max));
    track.style.transform = `translateX(-${current * getSlideWidth()}px)`;
    updateDots();
    updateArrows();
  }

  function next() { goTo(current + perView); if (current >= total - perView) goTo(0); }
  function prev() { goTo(current - perView); }

  btnNext.addEventListener('click', () => { next(); resetAuto(); });
  btnPrev.addEventListener('click', () => { prev(); resetAuto(); });

  /* Autoplay */
  function startAuto() { autoTimer = setInterval(next, 4500); }
  function resetAuto()  { clearInterval(autoTimer); startAuto(); }
  viewport.addEventListener('mouseenter', () => clearInterval(autoTimer));
  viewport.addEventListener('mouseleave', startAuto);

  /* Touch swipe */
  let touchX = 0;
  viewport.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
  viewport.addEventListener('touchend',   e => {
    const diff = touchX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 40) { diff > 0 ? next() : prev(); resetAuto(); }
  });

  /* Recalc on resize */
  window.addEventListener('resize', () => {
    const pv = window.innerWidth >= 1025 ? 3 : window.innerWidth >= 769 ? 2 : 1;
    if (pv !== perView) { perView = pv; current = 0; buildDots(); }
    goTo(current);
  });

  buildDots();
  goTo(0);
  startAuto();
})();
</script>

</body>
</html>
