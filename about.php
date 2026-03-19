<?php
require_once 'includes/config.php';
$currentPage = 'about';

$siteTitle = setting('site_name',      'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp',  '94770489956');
$phone     = setting('site_phone',     '+94 77 048 9956');
$email     = setting('site_email',     'info@gpslanka.com');

/* ── WHY CHOOSE US (dynamic) ── */
$whyUs = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM why_us WHERE is_active=1 ORDER BY sort_order");
    if ($r) $whyUs = $r->fetch_all(MYSQLI_ASSOC);
}

/* ── TEAM ── */
$teamMembers = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM team WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    if ($r) $teamMembers = $r->fetch_all(MYSQLI_ASSOC);
}

/* ── TESTIMONIALS ── */
$testimonials = [];
if ($conn) {
    $r = $conn->query("SELECT id, name, country, rating, review, photo
                        FROM testimonials WHERE is_active=1 ORDER BY id LIMIT 6");
    if ($r) $testimonials = $r->fetch_all(MYSQLI_ASSOC);
}

/* ── ABOUT SETTINGS ── */
$aImg1  = setting('about_img1',  '');
$aImg2  = setting('about_img2',  '');
$aImg3  = setting('about_img3',  '');
$storyTag   = setting('about_story_tag', 'Our Story');
$storyH1    = setting('about_story_heading_line1', 'A Passion for');
$storyH2    = setting('about_story_heading_line2', 'Sharing Sri Lanka');
$storyP1    = setting('about_story_para1', 'GPS Lanka Travels was born from a deep love for this beautiful island and a desire to share its wonders with the world. Founded in 2014, we began as a small, passionate team of local travel enthusiasts committed to one simple goal — giving every traveler a genuine, unforgettable Sri Lanka experience.');
$storyP2    = setting('about_story_para2', 'Over the years we have grown into one of Sri Lanka\'s most trusted inbound tour operators, serving hundreds of satisfied guests from over 30 countries. From luxury private tours to budget-friendly group adventures, every journey we craft is built on personal care, deep local knowledge and unwavering reliability.');
$storyP3    = setting('about_story_para3', 'We believe travel is not just about visiting places — it\'s about connecting with people, cultures and stories. That\'s why everything we do, from your first inquiry to your farewell at the airport, is handled with warmth, honesty and attention to detail.');
$foundedYr  = setting('about_founded_year', '2014');
$foundedLbl = setting('about_founded_label', 'Est. in Sri Lanka');
$stats = [
  ['count'=>setting('about_stat1_count','500'), 'suffix'=>setting('about_stat1_suffix','+'), 'label'=>setting('about_stat1_label','Happy Guests')],
  ['count'=>setting('about_stat2_count','30'),  'suffix'=>setting('about_stat2_suffix','+'), 'label'=>setting('about_stat2_label','Countries Served')],
  ['count'=>setting('about_stat3_count','10'),  'suffix'=>setting('about_stat3_suffix','+'), 'label'=>setting('about_stat3_label','Years Experience')],
  ['count'=>setting('about_stat4_count','50'),  'suffix'=>setting('about_stat4_suffix','+'), 'label'=>setting('about_stat4_label','Tour Packages')],
];
$visionText  = setting('about_vision_text',  'To be the most trusted and preferred travel partner for luxury and experiential travel, setting the gold standard for high-end trips in Sri Lanka.');
$missionText = setting('about_mission_text', 'To provide exceptional, personalized travel services that create unforgettable memories for our guests while actively promoting sustainable growth.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="About <?= e($siteTitle) ?> | Sri Lanka's most trusted inbound tour operator. Learn our story, meet our team, and discover why thousands of travelers choose us."/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="About Us | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Sri Lanka's most trusted inbound tour operator. Learn our story, meet our team, and discover why thousands of travelers choose us."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/about-hero.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/about.php"/>
  <title>About Us | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/about.css"/>
</head>
<body data-page="about">

<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      About Us
    </div>
    <h1 class="page-hero-title">About <em><?= e($siteTitle) ?></em></h1>
    <p class="page-hero-sub">Your trusted partner for extraordinary Sri Lanka journeys</p>
  </div>
</section>


<!-- OUR STORY -->
<section class="about-intro section-pad">
  <div class="container">
    <div class="about-intro-grid">

      <div class="about-images reveal-left">
        <div class="about-img about-img-1"><img src="<?= $aImg1 ? e(imgUrl($aImg1)) : 'images/about-story-1.jpg' ?>" alt="<?= e($siteTitle) ?> Story"/></div>
        <div class="about-img about-img-2"><img src="<?= $aImg2 ? e(imgUrl($aImg2)) : 'images/about-story-2.jpg' ?>" alt="Sri Lanka Tour Experience"/></div>
        <div class="about-img about-img-3"><img src="<?= $aImg3 ? e(imgUrl($aImg3)) : 'images/about-story-3.jpg' ?>" alt="Sri Lanka Culture"/></div>
        <div class="about-founded-badge">
          <div class="year"><?= e($foundedYr) ?></div>
          <div class="label"><?= e($foundedLbl) ?></div>
        </div>
      </div>

      <div class="about-story-content reveal-right">
        <div class="section-tag"><?= e($storyTag) ?></div>
        <h2 class="section-title"><?= e($storyH1) ?><br><em><?= e($storyH2) ?></em></h2>
        <?php if ($storyP1): ?><p><?= e($storyP1) ?></p><?php endif; ?>
        <?php if ($storyP2): ?><p><?= e($storyP2) ?></p><?php endif; ?>
        <?php if ($storyP3): ?><p><?= e($storyP3) ?></p><?php endif; ?>

        <div class="about-highlights">
          <?php foreach ($stats as $i => $stat): ?>
            <?php if ($i > 0): ?><div class="about-highlight-div"></div><?php endif; ?>
            <div class="about-highlight">
              <div class="about-highlight-num" data-count="<?= e($stat['count']) ?>" data-suffix="<?= e($stat['suffix']) ?>">0</div>
              <div class="about-highlight-label"><?= e($stat['label']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <a href="contact.php#contact-form" class="btn-primary"><i class="fas fa-paper-plane"></i> Get in Touch</a>
      </div>

    </div>
  </div>
</section>


<!-- VISION & MISSION -->
<section class="about-vm section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Our Purpose</div>
      <h2 class="section-title reveal">Vision &amp; <em>Mission</em></h2>
      <p class="section-subtitle reveal">The principles that guide every journey we craft and every traveler we serve.</p>
    </div>
    <div class="about-vm-grid">
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


<!-- CORE VALUES -->
<section class="about-values section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">What We Stand For</div>
      <h2 class="section-title reveal">Our Core <em>Values</em></h2>
      <p class="section-subtitle reveal">The foundations that shape every decision we make and every experience we deliver.</p>
    </div>
    <div class="values-grid stagger-children">
      <div class="value-card reveal">
        <div class="value-icon"><i class="fas fa-handshake"></i></div>
        <h4>Integrity</h4>
        <p>We operate with complete transparency and honesty. What we promise is exactly what you receive — no hidden costs, no surprises.</p>
      </div>
      <div class="value-card reveal">
        <div class="value-icon"><i class="fas fa-star"></i></div>
        <h4>Excellence</h4>
        <p>From the vehicles we use to the guides we employ, every element of your journey is held to the highest possible standard.</p>
      </div>
      <div class="value-card reveal">
        <div class="value-icon"><i class="fas fa-leaf"></i></div>
        <h4>Sustainability</h4>
        <p>We actively support eco-friendly tourism that protects Sri Lanka's natural beauty and enriches local communities for future generations.</p>
      </div>
      <div class="value-card reveal">
        <div class="value-icon"><i class="fas fa-heart"></i></div>
        <h4>Passion</h4>
        <p>We genuinely love what we do. Our enthusiasm for Sri Lanka and travel shines through in every tour, every conversation and every detail.</p>
      </div>
    </div>
  </div>
</section>


<!-- WHY CHOOSE US (dynamic from DB) -->
<section class="about-why section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Our Promise</div>
      <h2 class="section-title reveal">Why Choose <em>Us?</em></h2>
      <p class="section-subtitle reveal">Six reasons why thousands of travelers from around the world trust <?= e($siteTitle) ?>.</p>
    </div>
    <div class="about-why-grid stagger-children">
      <?php if ($whyUs): foreach ($whyUs as $w): ?>
      <div class="why-card reveal">
        <div class="why-icon-wrap"><i class="<?= e($w['icon']) ?>"></i></div>
        <h3><?= e($w['title']) ?></h3>
        <p><?= e($w['description']) ?></p>
      </div>
      <?php endforeach; else: ?>
      <!-- Fallback if DB empty -->
      <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-shield-alt"></i></div><h3>SLTDA Licensed</h3><p>Fully registered and licensed by the Sri Lanka Tourism Development Authority.</p></div>
      <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-route"></i></div><h3>Personalized Itineraries</h3><p>Every tour is custom-built around your preferences, travel pace and budget.</p></div>
      <div class="why-card reveal"><div class="why-icon-wrap"><i class="fas fa-headset"></i></div><h3>24/7 Guest Support</h3><p>Our team is always reachable — day or night — throughout your entire journey.</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- TESTIMONIALS (dynamic from DB) -->
<?php if ($testimonials): ?>
<section class="about-testimonials section-pad" style="background:var(--off-white)">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Real Experiences</div>
      <h2 class="section-title reveal">What Our <em>Guests Say</em></h2>
      <p class="section-subtitle reveal">Trusted by travelers from over 30 countries — here's what they have to say about journeying with us.</p>
    </div>
    <div class="about-testi-slider">
      <div class="about-testi-track" id="aboutTestiTrack">
        <?php foreach ($testimonials as $t):
          $photo = $t['photo'] ? imgUrl($t['photo']) : '';
        ?>
        <div class="testi-card">
          <div class="testi-stars"><?= starRating($t['rating']) ?></div>
          <p class="testi-text">"<?= e($t['review']) ?>"</p>
          <div class="testi-author">
            <div class="testi-avatar">
              <?php if ($photo): ?>
                <img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>"/>
              <?php else: ?>
                <i class="fas fa-user"></i>
              <?php endif; ?>
            </div>
            <div>
              <div class="testi-name"><?= e($t['name']) ?></div>
              <div class="testi-loc"><i class="fas fa-map-marker-alt"></i> <?= e($t['country']) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="about-testi-nav">
        <button class="about-testi-btn" id="aboutTestiPrev"><i class="fas fa-chevron-left"></i></button>
        <div class="about-testi-dots" id="aboutTestiDots"></div>
        <button class="about-testi-btn" id="aboutTestiNext"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- OUR TEAM -->
<section class="about-team section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">The People Behind <?= e($siteTitle) ?></div>
      <h2 class="section-title reveal">Meet Our <em>Team</em></h2>
      <p class="section-subtitle reveal">A dedicated team of local travel experts who live and breathe Sri Lanka — ready to make your journey truly extraordinary.</p>
    </div>
    <div class="team-grid stagger-children">
      <?php if ($teamMembers): foreach ($teamMembers as $member): ?>
      <div class="team-card reveal">
        <div class="team-img-wrap">
          <?php if ($member['photo']): ?>
            <img src="<?= SITE_URL . '/' . e($member['photo']) ?>" alt="<?= e($member['name']) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"/>
          <?php else: ?>
            <div style="display:none;"></div>
          <?php endif; ?>
          <div class="team-img-placeholder" <?= $member['photo'] ? 'style="display:none;"' : '' ?>><i class="fas fa-user-tie"></i></div>
          <div class="team-social-overlay">
            <?php if ($member['facebook_url']): ?>
              <a href="<?= e($member['facebook_url']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <?php else: ?>
              <a href="#"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if ($member['instagram_url']): ?>
              <a href="<?= e($member['instagram_url']) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php else: ?>
              <a href="#"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
            <?php $wa = $member['whatsapp'] ?: $whatsapp; ?>
            <a href="https://wa.me/<?= e($wa) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
          </div>
        </div>
        <div class="team-body">
          <div class="team-name"><?= e($member['name']) ?></div>
          <div class="team-role"><?= e($member['role']) ?></div>
          <div class="team-bio"><?= e($member['bio']) ?></div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <!-- Fallback if no team members in DB yet -->
      <?php
      $fallbackTeam = [
        ['img'=>'team-1.jpg','name'=>'Founder / CEO',           'role'=>$siteTitle,'bio'=>'Passionate about Sri Lanka travel with over 10 years of experience creating unforgettable journeys for guests from around the world.'],
        ['img'=>'team-2.jpg','name'=>'Tour Operations Manager', 'role'=>$siteTitle,'bio'=>"Expert in designing customized Sri Lanka itineraries that perfectly match every traveler's dream, pace and budget."],
        ['img'=>'team-3.jpg','name'=>'Senior Driver Guide',     'role'=>$siteTitle,'bio'=>"Licensed and experienced chauffeur guide with deep knowledge of Sri Lanka's history, culture, wildlife and hidden gems."],
      ];
      foreach ($fallbackTeam as $member):
      ?>
      <div class="team-card reveal">
        <div class="team-img-wrap">
          <img src="images/<?= e($member['img']) ?>" alt="<?= e($member['name']) ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"/>
          <div class="team-img-placeholder" style="display:none;"><i class="fas fa-user-tie"></i></div>
          <div class="team-social-overlay">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
          </div>
        </div>
        <div class="team-body">
          <div class="team-name"><?= e($member['name']) ?></div>
          <div class="team-role"><?= e($member['role']) ?></div>
          <div class="team-bio"><?= e($member['bio']) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>


<!-- CERTIFICATIONS -->
<section class="about-certs section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Accreditations</div>
      <h2 class="section-title reveal">Licensed &amp; <em>Accredited</em></h2>
      <p class="section-subtitle reveal">Our certifications are your assurance of quality, safety and professionalism on every journey.</p>
    </div>
    <div class="certs-grid stagger-children">
      <div class="cert-card reveal"><div class="cert-icon"><i class="fas fa-certificate"></i></div><h4>SLTDA Registered</h4><p>Officially registered and licensed by the Sri Lanka Tourism Development Authority.</p></div>
      <div class="cert-card reveal"><div class="cert-icon"><i class="fab fa-tripadvisor"></i></div><h4>TripAdvisor Rated</h4><p>Highly rated on TripAdvisor with consistently outstanding reviews from international travelers.</p></div>
      <div class="cert-card reveal"><div class="cert-icon"><i class="fas fa-leaf"></i></div><h4>Eco-Certified</h4><p>Committed to sustainable and responsible tourism practices that protect Sri Lanka's natural heritage.</p></div>
      <div class="cert-card reveal"><div class="cert-icon"><i class="fas fa-star"></i></div><h4>5-Star Reviewed</h4><p>Proud recipients of five-star ratings across Google, Facebook and Booking.com platforms.</p></div>
    </div>
  </div>
</section>


<!-- CTA BANNER -->
<section class="about-cta section-pad">
  <div class="container">
    <div class="about-cta-inner reveal">
      <h2>Ready to Explore<br><em>Beautiful Sri Lanka?</em></h2>
      <p>Let us craft your perfect Sri Lanka journey. Whether you have a detailed plan or just a dream destination — our team is ready to make it happen.</p>
      <div class="about-cta-btns">
        <a href="tours.php" class="btn-primary"><i class="fas fa-map-marked-alt"></i> Browse All Tours</a>
        <a href="https://wa.me/<?= e($whatsapp) ?>" class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      </div>
    </div>
  </div>
</section>


<?php include 'includes/footer.php'; ?>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script>
/* ── TESTIMONIALS SLIDER ── */
(function() {
  const track   = document.getElementById('aboutTestiTrack');
  const dotsWrap= document.getElementById('aboutTestiDots');
  const prevBtn = document.getElementById('aboutTestiPrev');
  const nextBtn = document.getElementById('aboutTestiNext');
  if (!track) return;

  const cards = Array.from(track.children);
  if (!cards.length) return;

  let perPage  = window.innerWidth < 640 ? 1 : window.innerWidth < 1024 ? 2 : 3;
  let current  = 0;
  let total    = Math.ceil(cards.length / perPage);
  let autoTimer;

  function buildDots() {
    dotsWrap.innerHTML = '';
    for (let i = 0; i < total; i++) {
      const d = document.createElement('button');
      d.className = 'about-testi-dot' + (i === current ? ' active' : '');
      d.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(d);
    }
  }

  function goTo(idx) {
    current = (idx + total) % total;
    const offset = current * (100 / perPage) * perPage;
    track.style.transform = 'translateX(-' + (current * 100) + '%)';
    Array.from(dotsWrap.children).forEach((d, i) => d.classList.toggle('active', i === current));
  }

  function setupSlider() {
    perPage = window.innerWidth < 640 ? 1 : window.innerWidth < 1024 ? 2 : 3;
    total   = Math.ceil(cards.length / perPage);
    current = 0;
    cards.forEach(c => {
      c.style.flexShrink = '0';
      c.style.width      = 'calc(' + (100 / perPage) + '% - 16px)';
      c.style.margin     = '0 8px';
    });
    track.style.transform = 'translateX(0)';
    buildDots();
  }

  prevBtn.addEventListener('click', () => { goTo(current - 1); resetAuto(); });
  nextBtn.addEventListener('click', () => { goTo(current + 1); resetAuto(); });

  function resetAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => goTo(current + 1), 5000);
  }

  setupSlider();
  resetAuto();
  window.addEventListener('resize', () => { setupSlider(); resetAuto(); });
})();

/* Animated counters */
const counters = document.querySelectorAll('.about-highlight-num');
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el     = entry.target;
    const target = parseInt(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    let current  = 0;
    const step   = Math.ceil(target / 60);
    const timer  = setInterval(() => {
      current += step;
      if (current >= target) { current = target; clearInterval(timer); }
      el.textContent = current + suffix;
    }, 25);
    observer.unobserve(el);
  });
}, { threshold: 0.5 });
counters.forEach(c => observer.observe(c));
</script>
</body>
</html>
