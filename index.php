<?php
require_once 'includes/config.php';
$currentPage = 'home';

/* ── FETCH DATA ── */
$slides = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM slider WHERE is_active=1 ORDER BY sort_order, id LIMIT 6");
    if ($r) $slides = $r->fetch_all(MYSQLI_ASSOC);
}
if (!$slides) $slides = array_fill(0, 4, ['image' => '', 'title' => '', 'subtitle' => '']);

$whyItems = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM why_us WHERE is_active=1 ORDER BY sort_order, id LIMIT 6");
    if ($r) $whyItems = $r->fetch_all(MYSQLI_ASSOC);
}

$featuredTours = [];
if ($conn) {
    $r = $conn->query("SELECT t.*, tc.name as cat_name, tc.slug as cat_slug, tc.icon as cat_icon
                        FROM tours t LEFT JOIN tour_categories tc ON t.category_id=tc.id
                        WHERE t.is_active=1 AND t.is_featured=1 ORDER BY t.sort_order, t.id LIMIT 3");
    if ($r) $featuredTours = $r->fetch_all(MYSQLI_ASSOC);
    if (!$featuredTours) {
        $r = $conn->query("SELECT t.*, tc.name as cat_name, tc.slug as cat_slug, tc.icon as cat_icon
                            FROM tours t LEFT JOIN tour_categories tc ON t.category_id=tc.id
                            WHERE t.is_active=1 ORDER BY t.sort_order, t.id LIMIT 3");
        if ($r) $featuredTours = $r->fetch_all(MYSQLI_ASSOC);
    }
}

$testimonials = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order, id LIMIT 8");
    if ($r) $testimonials = $r->fetch_all(MYSQLI_ASSOC);
}

$galleryItems = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order, id LIMIT 6");
    if ($r) $galleryItems = $r->fetch_all(MYSQLI_ASSOC);
}

$partners = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM partners WHERE is_active=1 ORDER BY sort_order, id");
    if ($r) $partners = $r->fetch_all(MYSQLI_ASSOC);
}

$offerItems = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    if ($r) $offerItems = $r->fetch_all(MYSQLI_ASSOC);
}

$homeDestinations = [];
if ($conn) {
    $r = $conn->query("SELECT d.id, d.title, d.slug, d.hero_image, d.location, d.region, d.excerpt,
                              dc.name AS cat_name, dc.icon AS cat_icon
                        FROM destinations d
                        LEFT JOIN destination_categories dc ON dc.id = d.category_id
                        WHERE d.is_active = 1
                        ORDER BY d.is_featured DESC, d.sort_order ASC, d.id ASC
                        LIMIT 3");
    if ($r) $homeDestinations = $r->fetch_all(MYSQLI_ASSOC);
}

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$pageDesc  = setting('meta_description', 'GPS Lanka Travels | Your trusted partner for luxury Sri Lanka tours, airport transfers, hotel bookings and custom travel experiences across the island.');
$whatsapp  = setting('site_whatsapp', '94770489956');

/* ── ABOUT SETTINGS ── */
$aImg1       = setting('about_img1', '');
$aImg2       = setting('about_img2', '');
$aImg3       = setting('about_img3', '');
$hImg1       = setting('home_img1', '');
$hImg2       = setting('home_img2', '');
$hImg3       = setting('home_img3', '');
$homeAboutH  = setting('home_about_heading', 'Welcome to GPS Lanka Travels');
$homeAboutD  = setting('home_about_desc', 'We are dedicated to crafting exceptional Sri Lanka travel experiences. From the misty highlands of Nuwara Eliya to the golden shores of Mirissa, every journey we create is a masterpiece tailored just for you. Our team of passionate local experts ensures every detail is perfect — from the moment you land to your fond farewell.');
$stat1Count  = setting('about_stat1_count',  '500');
$stat1Suffix = setting('about_stat1_suffix', '+');
$stat1Label  = setting('about_stat1_label',  'Happy Travelers');
$stat3Count  = setting('about_stat3_count',  '10');
$stat3Suffix = setting('about_stat3_suffix', '+');
$stat3Label  = setting('about_stat3_label',  'Years Experience');
$stat4Count  = setting('about_stat4_count',  '50');
$stat4Suffix = setting('about_stat4_suffix', '+');
$stat4Label  = setting('about_stat4_label',  'Tour Packages');
$yearsCount  = $stat3Count;
$visionText  = setting('about_vision_text',  'To be the most trusted and preferred travel partner for luxury and experiential travel, setting the gold standard for high-end trips in Sri Lanka.');
$missionText = setting('about_mission_text', 'To provide exceptional, personalized travel services that create unforgettable memories for our guests while actively promoting sustainable growth.');

/* Gallery span classes for masonry effect */
$galClasses = ['tall', 'wide', '', '', '', ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?= e($pageDesc) ?>"/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="<?= e($siteTitle) ?> | Create Unforgettable Travel Memories"/>
  <meta property="og:description" content="<?= e($pageDesc) ?>"/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/og-image.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/index.php"/>
  <title><?= e($siteTitle) ?> | Create Unforgettable Travel Memories</title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/home.css"/>
</head>
<body data-page="home">

<?php include 'includes/header.php'; ?>

<!-- HERO -->
<section class="hero" id="home">

  <div class="hero-slides">
    <?php foreach ($slides as $slide): ?>
      <div class="hero-slide"
        <?= $slide['image'] ? ' style="background-image:url(\''.imgUrl($slide['image']).'\')"' : '' ?>
        data-title="<?= e($slide['title'] ?? '') ?>"
        data-subtitle="<?= e($slide['subtitle'] ?? '') ?>"
        data-btn1-text="<?= e($slide['btn1_text'] ?? '') ?>"
        data-btn1-url="<?= e($slide['btn1_url'] ?? '') ?>"
        data-btn2-text="<?= e($slide['btn2_text'] ?? '') ?>"
        data-btn2-url="<?= e($slide['btn2_url'] ?? '') ?>"
      ></div>
    <?php endforeach; ?>
  </div>

  <button class="slider-arrow slider-prev" id="sliderPrev" aria-label="Previous">
    <i class="fas fa-chevron-left"></i>
  </button>
  <button class="slider-arrow slider-next" id="sliderNext" aria-label="Next">
    <i class="fas fa-chevron-right"></i>
  </button>

  <?php
    $s0 = $slides[0] ?? [];
    $heroTitle    = $s0['title']     ?? '';
    $heroSubtitle = $s0['subtitle']  ?? '';
    $heroBtn1Text = $s0['btn1_text'] ?? '';
    $heroBtn1Url  = $s0['btn1_url']  ?? '';
    $heroBtn2Text = $s0['btn2_text'] ?? '';
    $heroBtn2Url  = $s0['btn2_url']  ?? '';
  ?>
  <div class="hero-content">
    <div class="hero-eyebrow">
      <i class="fas fa-star"></i> Welcome to Sri Lanka <i class="fas fa-star"></i>
    </div>
    <h1 class="hero-title" id="heroTitle"><?php
      if ($heroTitle) {
        echo preg_replace('/\*(.+?)\*/', '<em>$1</em>', e($heroTitle));
      } else {
        echo 'Create <em>Unforgettable</em><br>Travel Memories';
      }
    ?></h1>
    <p class="hero-subtitle" id="heroSubtitle"><?= $heroSubtitle ? e($heroSubtitle) : 'Discover the pearl of the Indian Ocean with GPS Lanka Travels — your trusted partner for luxury tours, authentic experiences, and lifetime memories across beautiful Sri Lanka.' ?></p>
    <div class="hero-ctas" id="heroCtas">
      <?php if ($heroBtn1Text && $heroBtn1Url): ?>
        <a href="<?= e($heroBtn1Url) ?>" class="btn-primary"><i class="fas fa-map-marked-alt"></i> <?= e($heroBtn1Text) ?></a>
      <?php else: ?>
        <a href="tours.php" class="btn-primary"><i class="fas fa-map-marked-alt"></i> Explore Tours</a>
      <?php endif; ?>
      <?php if ($heroBtn2Text && $heroBtn2Url): ?>
        <a href="<?= e($heroBtn2Url) ?>" class="btn-outline"><i class="fas fa-phone-alt"></i> <?= e($heroBtn2Text) ?></a>
      <?php else: ?>
        <a href="contact.php#contact-form" class="btn-outline"><i class="fas fa-phone-alt"></i> Contact Us</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="hero-stats-wrap">
    <div class="hero-stats">
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= e($stat1Count) ?>" data-suffix="<?= e($stat1Suffix) ?>">0</div>
        <div class="hero-stat-label"><?= e($stat1Label) ?></div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= e($stat4Count) ?>" data-suffix="<?= e($stat4Suffix) ?>">0</div>
        <div class="hero-stat-label"><?= e($stat4Label) ?></div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= e($stat3Count) ?>" data-suffix="<?= e($stat3Suffix) ?>">0</div>
        <div class="hero-stat-label"><?= e($stat3Label) ?></div>
      </div>
    </div>
  </div>

  <div class="slider-dots">
    <?php foreach ($slides as $i => $slide): ?>
      <button class="slider-dot<?= $i === 0 ? ' active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
    <?php endforeach; ?>
  </div>

</section>


<!-- WELCOME / ABOUT -->
<section class="welcome section-pad" id="about">
  <div class="container">
    <div class="welcome-grid">
      <div class="welcome-images reveal-left">
        <div class="welcome-img welcome-img-main">
          <img src="<?= $hImg1 ? e(imgUrl($hImg1)) : 'images/about-main.jpg' ?>" alt="Explore Sri Lanka with GPS Lanka Travels"/>
        </div>
        <div class="welcome-img welcome-img-2">
          <img src="<?= $hImg2 ? e(imgUrl($hImg2)) : 'images/about-2.jpg' ?>" alt="Sri Lanka Beaches"/>
        </div>
        <div class="welcome-img welcome-img-3">
          <img src="<?= $hImg3 ? e(imgUrl($hImg3)) : 'images/about-circle.jpg' ?>" alt="Sri Lanka Culture"/>
        </div>
        <div class="welcome-badge">
          <div class="welcome-badge-num"><?= e($yearsCount) ?>+</div>
          <div class="welcome-badge-text">Years of Excellence</div>
        </div>
      </div>
      <div class="welcome-content reveal-right">
        <div class="section-tag">About GPS Lanka Travels</div>
        <h2 class="section-title"><?= e($homeAboutH) ?></h2>
        <p class="welcome-desc"><?= e($homeAboutD) ?></p>
        <div class="welcome-features">
          <div class="welcome-feat">
            <div class="welcome-feat-icon"><i class="fas fa-car"></i></div>
            <div><h4>Luxury Transfers</h4><p>Premium AC vehicles with experienced drivers</p></div>
          </div>
          <div class="welcome-feat">
            <div class="welcome-feat-icon"><i class="fas fa-hotel"></i></div>
            <div><h4>Hotel Bookings</h4><p>Handpicked stays for every budget</p></div>
          </div>
          <div class="welcome-feat">
            <div class="welcome-feat-icon"><i class="fas fa-user-tie"></i></div>
            <div><h4>Expert Guides</h4><p>Licensed &amp; passionate local guides</p></div>
          </div>
          <div class="welcome-feat">
            <div class="welcome-feat-icon"><i class="fas fa-headset"></i></div>
            <div><h4>24/7 Support</h4><p>Always here whenever you need us</p></div>
          </div>
        </div>
        <a href="about.php" class="btn-primary"><i class="fas fa-arrow-right"></i> Learn More About Us</a>
      </div>
    </div>
  </div>
</section>


<!-- WHAT WE OFFER -->
<section class="offer section-pad" id="services">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">What We Offer</div>
      <h2 class="section-title reveal">Discover Sri Lanka <em>Your Way</em></h2>
      <p class="section-subtitle reveal">Expert travel experiences through Sri Lanka's breathtaking landscapes, ancient cities, and pristine beaches.</p>
    </div>
    <div class="offer-grid stagger-children">
      <?php if ($offerItems): ?>
        <?php foreach ($offerItems as $svc): ?>
        <?php $svcImg = $svc['image'] ? imgUrl($svc['image']) : 'images/offer-budget.jpg'; ?>
        <div class="offer-card reveal">
          <img src="<?= e($svcImg) ?>" alt="<?= e($svc['title']) ?>"/>
          <div class="offer-overlay">
            <div class="offer-icon"><i class="<?= e($svc['icon'] ?: 'fas fa-concierge-bell') ?>"></i></div>
            <?php if ($svc['label']): ?><div class="offer-cat"><?= e($svc['label']) ?></div><?php endif; ?>
            <div class="offer-title"><?= e($svc['title']) ?></div>
            <?php if ($svc['description']): ?><div class="offer-desc"><?= e($svc['description']) ?></div><?php endif; ?>
            <a href="<?= e($svc['link_url'] ?: 'tours.php') ?>" class="offer-link">
              <?= e($svc['link_text'] ?: 'Explore') ?> <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="offer-card reveal">
          <img src="images/offer-budget.jpg" alt="Budget Tours"/>
          <div class="offer-overlay">
            <div class="offer-icon"><i class="fas fa-wallet"></i></div>
            <div class="offer-cat">Affordable</div>
            <div class="offer-title">Budget Tours</div>
            <div class="offer-desc">Experience the best of Sri Lanka without breaking the bank. Our budget tours include all essentials for a wonderful journey.</div>
            <a href="tours.php" class="offer-link">Explore <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
        <div class="offer-card reveal">
          <img src="images/offer-adventure.jpg" alt="Adventure Tours"/>
          <div class="offer-overlay">
            <div class="offer-icon"><i class="fas fa-hiking"></i></div>
            <div class="offer-cat">Thrilling</div>
            <div class="offer-title">Adventure Tours</div>
            <div class="offer-desc">From surfing in Arugam Bay to hiking in Knuckles Range — adrenaline-packed adventures await the bold traveler.</div>
            <a href="tours.php" class="offer-link">Explore <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
        <div class="offer-card reveal">
          <img src="images/offer-transfer.jpg" alt="Airport Transfers"/>
          <div class="offer-overlay">
            <div class="offer-icon"><i class="fas fa-plane-arrival"></i></div>
            <div class="offer-cat">Seamless</div>
            <div class="offer-title">Airport Transfers</div>
            <div class="offer-desc">Comfortable, punctual and reliable airport transfers in luxury vehicles to start your Sri Lanka adventure perfectly.</div>
            <a href="tours.php" class="offer-link">Explore <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- WHY CHOOSE US -->
<section class="why section-pad" id="why">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Our Promise</div>
      <h2 class="section-title reveal">Why Choose <em>Us?</em></h2>
      <p class="section-subtitle reveal" style="color:rgba(255,255,255,0.6)">Six reasons why thousands of travelers trust GPS Lanka Travels for extraordinary experiences.</p>
    </div>
    <div class="why-grid stagger-children">
      <?php if ($whyItems): ?>
        <?php foreach ($whyItems as $w): ?>
          <div class="why-card reveal">
            <div class="why-icon-wrap"><i class="<?= e($w['icon']) ?>"></i></div>
            <h3><?= e($w['title']) ?></h3>
            <p><?= e($w['description']) ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-shield-alt"></i></div><h3>Trusted &amp; Reliable</h3><p>SLTDA registered and fully licensed tour operator with a proven track record of excellence.</p></div>
        <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-route"></i></div><h3>Personalized Itineraries</h3><p>Every tour is custom-built around your preferences, pace, and travel style.</p></div>
        <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-map-marker-alt"></i></div><h3>Island-Wide Coverage</h3><p>From Jaffna in the north to Dondra in the south — we cover every corner of Sri Lanka.</p></div>
        <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-dollar-sign"></i></div><h3>Best Value Pricing</h3><p>Premium experiences at transparent, competitive prices with no hidden charges.</p></div>
        <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-thumbs-up"></i></div><h3>Eco-Responsible Tourism</h3><p>We practice and promote sustainable tourism that respects nature and local communities.</p></div>
        <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-credit-card"></i></div><h3>Flexible Payment</h3><p>Convenient payment options including online, bank transfer, and instalment plans.</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- VISION & MISSION -->
<section class="vision-mission section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Our Purpose</div>
      <h2 class="section-title reveal">Vision &amp; <em>Mission</em></h2>
      <p class="section-subtitle reveal">The principles that guide every journey we craft and every traveler we serve.</p>
    </div>
    <div class="vm-grid">
      <div class="vm-card reveal-left" data-letter="V">
        <div class="vm-icon-wrap"><i class="fas fa-eye"></i></div>
        <h3>Our <em>Vision</em></h3>
        <div class="vm-divider"></div>
        <p><?= e($visionText) ?></p>
      </div>
      <div class="vm-card reveal-right" data-letter="M">
        <div class="vm-icon-wrap"><i class="fas fa-bullseye"></i></div>
        <h3>Our <em>Mission</em></h3>
        <div class="vm-divider"></div>
        <p><?= e($missionText) ?></p>
      </div>
    </div>
  </div>
</section>


<!-- FEATURED TOURS -->
<section class="tours section-pad" id="tours">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Curated Journeys</div>
      <h2 class="section-title reveal">Our Tour <em>Packages</em></h2>
      <p class="section-subtitle reveal">Handcrafted itineraries designed to reveal the true soul of Sri Lanka — from ancient kingdoms to tropical shores.</p>
    </div>
    <div class="tours-grid stagger-children">
      <?php if ($featuredTours): ?>
        <?php foreach ($featuredTours as $t): ?>
          <?php
          $days    = (int)$t['duration'];
          $img     = $t['image'] ? imgUrl($t['image']) : 'images/tour-cultural.jpg';
          $tags    = parseHighlights($t['highlights'] ?? '');
          $badge   = $t['is_featured'] ? '<div class="tour-badge popular">Featured</div>' : '';
          ?>
          <div class="tour-card reveal">
            <div class="tour-img">
              <img src="<?= e($img) ?>" alt="<?= e($t['title']) ?>"/>
              <?= $badge ?>
              <?php if ($t['duration']): ?>
                <div class="tour-duration"><i class="fas fa-clock"></i> <?= e($t['duration']) ?></div>
              <?php endif; ?>
            </div>
            <div class="tour-body">
              <?php if ($t['cat_name']): ?>
                <div class="tour-location">
                  <i class="<?= e($t['cat_icon'] ?: 'fas fa-map-marked-alt') ?>"></i>
                  <?= e($t['cat_name']) ?>
                </div>
              <?php endif; ?>
              <div class="tour-name"><?= e($t['title']) ?></div>
              <div class="tour-desc"><?= e($t['short_desc'] ?? '') ?></div>
              <?php if ($tags): ?>
                <div class="tour-highlights">
                  <?php foreach ($tags as $tag): ?>
                    <span class="tour-tag" title="<?= e($tag) ?>"><?= e($tag) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <div class="tour-footer">
                <div>
                  <div class="tour-price-label">From</div>
                  <div class="tour-price"><?= tourPrice($t['price_usd'], $t['price_note']) ?></div>
                </div>
                <a href="<?= tourUrl($t) ?>" class="btn-dark">Book Now <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Static fallback tours -->
        <div class="tour-card reveal">
          <div class="tour-img"><img src="images/tour-cultural.jpg" alt="Ancient Kingdoms Tour"/><div class="tour-badge popular">Most Popular</div><div class="tour-duration"><i class="fas fa-clock"></i> 7 Days</div></div>
          <div class="tour-body">
            <div class="tour-location"><i class="fas fa-map-marker-alt"></i> Cultural Triangle</div>
            <div class="tour-name">Ancient Kingdoms Discovery Tour</div>
            <div class="tour-desc">Uncover Sri Lanka's UNESCO World Heritage Sites — Sigiriya Rock Fortress, Dambulla Cave Temple, Polonnaruwa &amp; Anuradhapura.</div>
            <div class="tour-highlights"><span class="tour-tag">Sigiriya</span><span class="tour-tag">Dambulla</span><span class="tour-tag">Kandy</span><span class="tour-tag">Wildlife</span></div>
            <div class="tour-footer"><div><div class="tour-price-label">From</div><div class="tour-price">$450 <span>/ person</span></div></div><a href="contact.php#contact-form" class="btn-dark">Book Now <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
        <div class="tour-card reveal">
          <div class="tour-img"><img src="images/tour-hill.jpg" alt="Tea Trails Tour"/><div class="tour-badge new-badge">New Tour</div><div class="tour-duration"><i class="fas fa-clock"></i> 5 Days</div></div>
          <div class="tour-body">
            <div class="tour-location"><i class="fas fa-map-marker-alt"></i> Hill Country</div>
            <div class="tour-name">Misty Mountains &amp; Tea Trails</div>
            <div class="tour-desc">Journey through Ella's emerald hills, Nuwara Eliya's tea estates, and the breathtaking Nine Arch Bridge on a scenic train ride.</div>
            <div class="tour-highlights"><span class="tour-tag">Ella</span><span class="tour-tag">Nuwara Eliya</span><span class="tour-tag">Train Ride</span><span class="tour-tag">Tea Tasting</span></div>
            <div class="tour-footer"><div><div class="tour-price-label">From</div><div class="tour-price">$320 <span>/ person</span></div></div><a href="contact.php#contact-form" class="btn-dark">Book Now <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
        <div class="tour-card reveal">
          <div class="tour-img"><img src="images/tour-beach.jpg" alt="Beach Escape Tour"/><div class="tour-badge beach">Beach Paradise</div><div class="tour-duration"><i class="fas fa-clock"></i> 6 Days</div></div>
          <div class="tour-body">
            <div class="tour-location"><i class="fas fa-map-marker-alt"></i> Southern Coast</div>
            <div class="tour-name">Coastal Wonders &amp; Beach Escape</div>
            <div class="tour-desc">Pristine beaches of Mirissa, whale watching, Galle Fort's colonial charm, and sunset surf at Weligama Bay.</div>
            <div class="tour-highlights"><span class="tour-tag">Mirissa</span><span class="tour-tag">Galle Fort</span><span class="tour-tag">Whale Watch</span><span class="tour-tag">Surfing</span></div>
            <div class="tour-footer"><div><div class="tour-price-label">From</div><div class="tour-price">$380 <span>/ person</span></div></div><a href="contact.php#contact-form" class="btn-dark">Book Now <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div class="tours-cta reveal">
      <a href="tours.php" class="btn-primary" style="font-size:15px;padding:16px 40px;">
        <i class="fas fa-th-large"></i> View All Packages
      </a>
    </div>
  </div>
</section>


<!-- CUSTOM TOUR BANNER -->
<section class="custom-tour section-pad">
  <div class="container">
    <div class="custom-tour-inner reveal">
      <div class="section-tag">Tailor-Made</div>
      <h2 class="section-title">Looking for an <em>Exclusive</em><br>Customized Tour?</h2>
      <p>Tell us your dream — we'll craft the perfect Sri Lanka itinerary just for you. No templates, no compromises. Just an extraordinary journey built entirely around you.</p>
      <div class="custom-tour-btns">
        <a href="contact.php#contact-form"                             class="btn-primary"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
        <a href="https://wa.me/<?= e($whatsapp) ?>"  class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      </div>
    </div>
  </div>
</section>


<!-- TRUST BADGES -->
<div class="trust reveal">
  <div class="container">
    <div class="trust-label">Trusted on these platforms</div>
    <div class="trust-logos">
      <?php if ($partners): ?>
        <?php foreach ($partners as $p): ?>
        <?php $wrap = $p['url'] ? '<a href="' . e($p['url']) . '" target="_blank" rel="noopener" class="trust-logo">' : '<div class="trust-logo">'; ?>
        <?php $wrapEnd = $p['url'] ? '</a>' : '</div>'; ?>
        <?= $wrap ?>
          <?php if ($p['logo']): ?>
            <img src="<?= e(imgUrl($p['logo'])) ?>" alt="<?= e($p['name']) ?>" style="height:36px;max-width:80px;object-fit:contain"/>
          <?php else: ?>
            <i class="<?= e($p['icon_class'] ?: 'fas fa-handshake') ?>" style="color:<?= e($p['icon_color'] ?: 'var(--teal)') ?>;font-size:28px"></i>
          <?php endif; ?>
          <div>
            <div class="trust-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
            <div class="trust-logo-name" style="color:<?= e($p['icon_color'] ?: 'var(--teal)') ?>"><?= e($p['name']) ?></div>
            <?php if ($p['label']): ?><div style="font-size:10px;color:var(--text-light);margin-top:1px"><?= e($p['label']) ?></div><?php endif; ?>
          </div>
        <?= $wrapEnd ?>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="trust-logo trust-logo-tripadvisor">
          <i class="fab fa-tripadvisor"></i>
          <div><div class="trust-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><div class="trust-logo-name" style="color:#34e0a1">Tripadvisor</div></div>
        </div>
        <div class="trust-logo trust-logo-google">
          <i class="fab fa-google"></i>
          <div><div class="trust-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div><div class="trust-logo-name" style="color:#4285f4">Google Reviews</div></div>
        </div>
        <div class="trust-logo trust-logo-facebook">
          <i class="fab fa-facebook"></i>
          <div><div class="trust-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><div class="trust-logo-name" style="color:#1877f2">Facebook</div></div>
        </div>
        <div class="trust-logo trust-logo-booking">
          <i class="fas fa-bed"></i>
          <div><div class="trust-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><div class="trust-logo-name" style="color:#003580">Booking.com</div></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- DESTINATIONS -->
<section class="destinations section-pad" id="destinations">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Must-Visit Places</div>
      <h2 class="section-title reveal">Explore Sri Lanka's <em>Destinations</em></h2>
      <p class="section-subtitle reveal">From ancient temples to tropical rainforests — every corner of this paradise island tells a story worth discovering.</p>
    </div>
    <div class="dest-grid stagger-children">
      <?php if ($homeDestinations): ?>
        <?php foreach ($homeDestinations as $d): ?>
        <?php
          $dImg  = $d['hero_image'] ? imgUrl($d['hero_image']) : 'images/dest-mirissa.jpg';
          $dInfo = $d['excerpt'] ?: $d['cat_name'];
        ?>
        <a href="destination-detail.php?slug=<?= urlencode($d['slug']) ?>" class="dest-card reveal">
          <img src="<?= e($dImg) ?>" alt="<?= e($d['title']) ?>"/>
          <div class="dest-tag-explore"><i class="fas fa-compass"></i> Explore</div>
          <div class="dest-overlay">
            <?php if ($d['location'] || $d['region']): ?>
            <div class="dest-region"><i class="fas fa-map-marker-alt"></i> <?= e($d['region'] ?: $d['location']) ?></div>
            <?php endif; ?>
            <div class="dest-name"><?= e($d['title']) ?></div>
            <?php if ($dInfo): ?>
            <div class="dest-info"><?= e($dInfo) ?></div>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="dest-card reveal">
          <img src="images/dest-mirissa.jpg" alt="Mirissa Beach"/>
          <div class="dest-tag-explore"><i class="fas fa-compass"></i> Explore</div>
          <div class="dest-overlay">
            <div class="dest-region"><i class="fas fa-map-marker-alt"></i> Southern Province</div>
            <div class="dest-name">Mirissa Beach</div>
            <div class="dest-info"><i class="fas fa-sun"></i> Beach · Whale Watching · Surfing</div>
          </div>
        </div>
        <div class="dest-card reveal">
          <img src="images/dest-sigiriya.jpg" alt="Sigiriya Rock"/>
          <div class="dest-tag-explore"><i class="fas fa-compass"></i> Explore</div>
          <div class="dest-overlay">
            <div class="dest-region"><i class="fas fa-map-marker-alt"></i> Central Province</div>
            <div class="dest-name">Sigiriya Rock</div>
            <div class="dest-info"><i class="fas fa-landmark"></i> UNESCO · History · Panoramic Views</div>
          </div>
        </div>
        <div class="dest-card reveal">
          <img src="images/dest-ella.jpg" alt="Ella Hills"/>
          <div class="dest-tag-explore"><i class="fas fa-compass"></i> Explore</div>
          <div class="dest-overlay">
            <div class="dest-region"><i class="fas fa-map-marker-alt"></i> Badulla District</div>
            <div class="dest-name">Ella Hills</div>
            <div class="dest-info"><i class="fas fa-mountain"></i> Hiking · Tea · Nine Arch Bridge</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div class="text-center reveal" style="margin-top:44px;">
      <a href="destinations.php" class="btn-primary"><i class="fas fa-globe-asia"></i> All Destinations</a>
    </div>
  </div>
</section>


<!-- TESTIMONIALS -->
<section class="testimonials section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Happy Travelers</div>
      <h2 class="section-title reveal">What Our <em>Guests Say</em></h2>
      <p class="section-subtitle reveal">Real stories from real travelers who trusted us to make their Sri Lanka dreams come true.</p>
    </div>
    <div class="testimonials-slider reveal">
      <div class="testimonials-track" id="testiTrack">
        <?php if ($testimonials): ?>
          <?php foreach ($testimonials as $t): ?>
            <div class="testimonial-card">
              <div class="testimonial-stars"><?= starRating($t['rating']) ?></div>
              <p class="testimonial-text">"<?= e($t['review']) ?>"</p>
              <div class="testimonial-author">
                <?php if ($t['photo']): ?>
                  <img src="<?= imgUrl($t['photo']) ?>" alt="<?= e($t['name']) ?>" class="testimonial-avatar" style="width:44px;height:44px;border-radius:50%;object-fit:cover"/>
                <?php else: ?>
                  <div class="testimonial-icon-avatar"><i class="fas fa-user"></i></div>
                <?php endif; ?>
                <div>
                  <div class="testimonial-name"><?= e($t['name']) ?></div>
                  <div class="testimonial-country"><i class="fas fa-flag"></i> <?= e($t['country']) ?><?= $t['country_flag'] ? ' ' . e($t['country_flag']) : '' ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Static fallback testimonials -->
          <div class="testimonial-card"><div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="testimonial-text">"Absolutely breathtaking trip! Our driver-guide was incredibly knowledgeable and made every moment special. GPS Lanka Travels exceeded all our expectations from start to finish."</p><div class="testimonial-author"><div class="testimonial-icon-avatar"><i class="fas fa-user"></i></div><div><div class="testimonial-name">Sarah Mitchell</div><div class="testimonial-country"><i class="fas fa-flag"></i> United Kingdom</div></div></div></div>
          <div class="testimonial-card"><div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="testimonial-text">"The 10-day island tour was perfectly planned. Every hotel, every attraction, every meal — flawless. I cannot recommend GPS Lanka Travels enough to anyone visiting Sri Lanka."</p><div class="testimonial-author"><div class="testimonial-icon-avatar"><i class="fas fa-user"></i></div><div><div class="testimonial-name">Thomas Berger</div><div class="testimonial-country"><i class="fas fa-flag"></i> Germany</div></div></div></div>
          <div class="testimonial-card"><div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="testimonial-text">"Our family of five had the most magical experience! The kids loved the elephant orphanage and whale watching. Everything was safe, professional and wonderful."</p><div class="testimonial-author"><div class="testimonial-icon-avatar"><i class="fas fa-user"></i></div><div><div class="testimonial-name">Priya Nakamura</div><div class="testimonial-country"><i class="fas fa-flag"></i> Japan</div></div></div></div>
          <div class="testimonial-card"><div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="testimonial-text">"Booking was easy and the team was always responsive. Our customized honeymoon tour was perfect — romantic, adventurous, and absolutely unforgettable."</p><div class="testimonial-author"><div class="testimonial-icon-avatar"><i class="fas fa-user"></i></div><div><div class="testimonial-name">Emma &amp; James Cole</div><div class="testimonial-country"><i class="fas fa-flag"></i> Australia</div></div></div></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="testi-nav">
      <button class="testi-btn" id="testiPrev"><i class="fas fa-chevron-left"></i></button>
      <button class="testi-btn" id="testiNext"><i class="fas fa-chevron-right"></i></button>
    </div>
  </div>
</section>


<!-- GALLERY PREVIEW -->
<section class="gallery section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Our Moments</div>
      <h2 class="section-title reveal">Through Our <em>Lens</em></h2>
      <p class="section-subtitle reveal">Snapshots of the magical moments and breathtaking places we've explored together with our travelers.</p>
    </div>
    <div class="gallery-grid reveal">
      <?php if ($galleryItems): ?>
        <?php foreach ($galleryItems as $i => $g): ?>
          <?php $cls = $galClasses[$i] ?? ''; ?>
          <div class="gallery-item<?= $cls ? ' ' . $cls : '' ?>">
            <img src="<?= imgUrl($g['filename']) ?>" alt="<?= e($g['alt_text'] ?: $g['caption'] ?: 'Sri Lanka') ?>"/>
            <div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="gallery-item tall"><img src="images/gallery-1.jpg" alt="Sri Lanka landscape"/><div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div></div>
        <div class="gallery-item wide"><img src="images/gallery-2.jpg" alt="Beach Sri Lanka"/><div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div></div>
        <div class="gallery-item"><img src="images/gallery-3.jpg" alt="Temple Sri Lanka"/><div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div></div>
        <div class="gallery-item"><img src="images/gallery-4.jpg" alt="Sigiriya"/><div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div></div>
        <div class="gallery-item"><img src="images/gallery-5.jpg" alt="Nature Sri Lanka"/><div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div></div>
        <div class="gallery-item"><img src="images/gallery-6.jpg" alt="Mountains Sri Lanka"/><div class="gallery-item-overlay"><i class="fas fa-search-plus"></i></div></div>
      <?php endif; ?>
    </div>
    <div class="gallery-cta reveal">
      <a href="gallery.php" class="btn-primary"><i class="fas fa-images"></i> View Full Gallery</a>
    </div>
  </div>
</section>


<!-- SCENIC PARALLAX BANNER -->
<div class="scenic-banner">
  <div class="scenic-banner-text reveal">
    <h2>The Pearl of the<br><strong>Indian Ocean</strong></h2>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script src="js/slider.js"></script>
<script src="js/home.js"></script>

</body>
</html>
