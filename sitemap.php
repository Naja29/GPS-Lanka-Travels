<?php
require_once 'includes/config.php';
$currentPage = 'sitemap';
$siteTitle   = setting('site_name', 'GPS Lanka Travels');

/* Fetch tours */
$tours = [];
if ($conn) {
    $r = $conn->query("SELECT title, slug, id FROM tours WHERE is_active=1 ORDER BY title ASC");
    if ($r) $tours = $r->fetch_all(MYSQLI_ASSOC);
}

/* Fetch destinations */
$destinations = [];
if ($conn) {
    $r = $conn->query("SELECT title, slug, id FROM destinations WHERE is_active=1 ORDER BY title ASC");
    if ($r) $destinations = $r->fetch_all(MYSQLI_ASSOC);
}

/* Fetch blog posts */
$posts = [];
if ($conn) {
    $r = $conn->query("SELECT title, slug, id FROM blog_posts WHERE is_published=1 ORDER BY created_at DESC");
    if ($r) $posts = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sitemap | <?= e($siteTitle) ?></title>
  <meta name="description" content="Complete sitemap of <?= e($siteTitle) ?> — find all pages, tours, destinations and blog posts."/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="Sitemap | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Find all pages, tour packages, destinations and blog posts on the GPS Lanka Travels website."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/scenic-banner.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/sitemap.php"/>
  <link rel="icon"       type="image/png"  href="images/favicon.png"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/legal.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      Sitemap
    </div>
    <h1 class="page-hero-title">Site<em>map</em></h1>
    <p class="page-hero-sub">Find everything on the <?= e($siteTitle) ?> website</p>
  </div>
</section>

<!-- SITEMAP -->
<section class="sitemap-wrap">
  <div class="sitemap-inner">

    <div class="sitemap-intro">
      <div class="section-tag">All Pages</div>
      <h2 class="section-title">Everything in <em>One Place</em></h2>
      <p>Browse all our pages, tour packages, destinations and travel stories in one organised view.</p>
    </div>

    <div class="sitemap-grid">

      <!-- Main Pages -->
      <div class="sitemap-card reveal">
        <div class="sitemap-card-header">
          <div class="sitemap-card-icon"><i class="fas fa-home"></i></div>
          <div class="sitemap-card-title">Main Pages</div>
        </div>
        <div class="sitemap-card-body">
          <a href="index.php"        class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Home</span></a>
          <a href="tours.php"        class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Tours &amp; Packages</span></a>
          <a href="destinations.php" class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Destinations</span></a>
          <a href="gallery.php"      class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Gallery</span></a>
          <a href="blog.php"         class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Blog</span></a>
          <a href="about.php"        class="sitemap-link"><i class="fas fa-chevron-right"></i><span>About Us</span></a>
          <a href="contact.php"      class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Contact Us</span></a>
        </div>
      </div>

      <!-- Tours -->
      <div class="sitemap-card reveal">
        <div class="sitemap-card-header">
          <div class="sitemap-card-icon"><i class="fas fa-map-marked-alt"></i></div>
          <div class="sitemap-card-title">Tours &amp; Packages</div>
        </div>
        <div class="sitemap-card-body">
          <?php if ($tours): ?>
            <?php foreach ($tours as $t): ?>
              <a href="<?= tourUrl($t) ?>" class="sitemap-link">
                <i class="fas fa-chevron-right"></i>
                <span><?= e($t['title']) ?></span>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="sitemap-empty">No tours available yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Destinations -->
      <div class="sitemap-card reveal">
        <div class="sitemap-card-header">
          <div class="sitemap-card-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="sitemap-card-title">Destinations</div>
        </div>
        <div class="sitemap-card-body">
          <?php if ($destinations): ?>
            <?php foreach ($destinations as $d): ?>
              <a href="destination-detail.php?slug=<?= urlencode($d['slug'] ?? $d['id']) ?>" class="sitemap-link">
                <i class="fas fa-chevron-right"></i>
                <span><?= e($d['title']) ?></span>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="sitemap-empty">No destinations available yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Blog -->
      <div class="sitemap-card reveal">
        <div class="sitemap-card-header">
          <div class="sitemap-card-icon"><i class="fas fa-pen-nib"></i></div>
          <div class="sitemap-card-title">Blog &amp; Travel Stories</div>
        </div>
        <div class="sitemap-card-body">
          <?php if ($posts): ?>
            <?php foreach ($posts as $p): ?>
              <a href="<?= blogUrl($p) ?>" class="sitemap-link">
                <i class="fas fa-chevron-right"></i>
                <span><?= e($p['title']) ?></span>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="sitemap-empty">No blog posts published yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Legal -->
      <div class="sitemap-card reveal">
        <div class="sitemap-card-header">
          <div class="sitemap-card-icon"><i class="fas fa-shield-alt"></i></div>
          <div class="sitemap-card-title">Legal</div>
        </div>
        <div class="sitemap-card-body">
          <a href="privacy.php"  class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Privacy Policy</span></a>
          <a href="terms.php"    class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Terms of Service</span></a>
          <a href="sitemap.php"  class="sitemap-link"><i class="fas fa-chevron-right"></i><span>Sitemap</span></a>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
  const els = document.querySelectorAll('.reveal');
  const io  = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, { threshold: 0.1 });
  els.forEach(el => io.observe(el));
</script>
</body>
</html>
