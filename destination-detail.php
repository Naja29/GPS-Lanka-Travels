<?php
require_once 'includes/config.php';
$currentPage = 'destinations';

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp', '94770489956');

/* ── LOAD DESTINATION ── */
$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

$d = null;
if ($conn) {
    if ($slug) {
        $stmt = $conn->prepare("SELECT d.*, dc.name AS cat_name, dc.slug AS cat_slug, dc.icon AS cat_icon
                                FROM destinations d
                                LEFT JOIN destination_categories dc ON dc.id = d.category_id
                                WHERE d.slug=? AND d.is_active=1 LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $r = $stmt->get_result();
    } elseif ($id) {
        $stmt = $conn->prepare("SELECT d.*, dc.name AS cat_name, dc.slug AS cat_slug, dc.icon AS cat_icon
                                FROM destinations d
                                LEFT JOIN destination_categories dc ON dc.id = d.category_id
                                WHERE d.id=? AND d.is_active=1 LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result();
    }
    if (!empty($r)) $d = $r->fetch_assoc();
}

if (!$d) {
    header('Location: destinations.php');
    exit;
}

/* ── PARSE JSON FIELDS ── */
$quickFacts = !empty($d['quick_facts']) ? (json_decode($d['quick_facts'], true) ?: []) : [];
$bestMonths = !empty($d['best_months']) ? (json_decode($d['best_months'], true) ?: array_fill(0, 12, 'avoid')) : array_fill(0, 12, 'avoid');

/* ── RELATED DESTINATIONS ── */
$related = [];
if ($conn) {
    $catIdE = (int)($d['category_id'] ?? 0);
    $idE    = (int)$d['id'];
    $r = $conn->query("SELECT d.id, d.title, d.slug, d.hero_image, d.region,
                              dc.name AS cat_name, dc.slug AS cat_slug
                        FROM destinations d
                        LEFT JOIN destination_categories dc ON dc.id = d.category_id
                        WHERE d.is_active=1 AND d.id != $idE
                        ORDER BY (d.category_id=$catIdE) DESC, d.sort_order ASC
                        LIMIT 4");
    if ($r) $related = $r->fetch_all(MYSQLI_ASSOC);
}

/* ── FETCH TOUR PACKAGES ── */
$tours = [];
if ($conn) {
    $r = $conn->query("SELECT t.id, t.title, t.slug, t.image, t.duration, t.price_usd,
                              t.price_note, t.short_desc, t.badge,
                              tc.name AS cat_name
                        FROM tours t
                        LEFT JOIN tour_categories tc ON tc.id = t.category_id
                        WHERE t.is_active=1
                        ORDER BY t.is_featured DESC, t.sort_order LIMIT 3");
    if ($r) $tours = $r->fetch_all(MYSQLI_ASSOC);
}

$catCssClass = [
    'nature'   => 'cat-nature',
    'culture'  => 'cat-culture',
    'beach'    => 'cat-beach',
    'wildlife' => 'cat-wildlife',
    'hill'     => 'cat-nature',
];

$months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$heroImg = $d['hero_image']
    ? (strpos($d['hero_image'],'uploads/')===0 ? SITE_URL.'/'.$d['hero_image'] : 'images/'.$d['hero_image'])
    : 'images/dest-arugam.jpg';
$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

function destImgD($img) {
    if (!$img) return 'images/dest-arugam.jpg';
    if (strpos($img, 'uploads/') === 0) return SITE_URL . '/' . $img;
    return 'images/' . $img;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?= e($d['excerpt'] ?: $d['title']) ?> | <?= e($siteTitle) ?>"/>
  <?php
  $ogImg  = $d['hero_image']
      ? (strpos($d['hero_image'], 'uploads/') === 0 ? SITE_URL . '/' . $d['hero_image'] : SITE_URL . '/images/' . $d['hero_image'])
      : SITE_URL . '/images/dest-arugam.jpg';
  $ogDesc = mb_substr(strip_tags($d['excerpt'] ?? $d['title']), 0, 160);
  ?>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="<?= e($d['title']) ?> | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="<?= e($ogDesc) ?>"/>
  <meta property="og:image"       content="<?= e($ogImg) ?>"/>
  <meta property="og:url"         content="<?= e($pageUrl) ?>"/>
  <title><?= e($d['title']) ?> | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/destinations.css"/>
  <link rel="stylesheet" href="css/tours.css"/>
  <link rel="stylesheet" href="css/tour-detail.css"/>
</head>
<body data-page="destinations"
      style="--dest-hero-bg: url('<?= e($heroImg) ?>')">

<?php include 'includes/header.php'; ?>

<!-- DETAIL HERO -->
<section class="dest-detail-hero">
  <div class="dest-detail-hero-bg"></div>
  <div class="dest-detail-hero-content">
    <div class="dest-detail-hero-inner">
      <div class="page-breadcrumb" style="margin-bottom:16px;">
        <a href="index.php">Home</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <a href="destinations.php">Destinations</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <?= e(explode(':', $d['title'])[0]) ?>
      </div>
      <div class="dest-detail-cat-tag">
        <i class="<?= e($d['cat_icon'] ?: 'fas fa-map-marker-alt') ?>"></i>
        <?= e($d['cat_name'] ?? '') ?>
      </div>
      <h1 class="dest-detail-title"><?= e($d['title']) ?></h1>
      <div class="dest-detail-meta">
        <?php if ($d['location']): ?>
          <span><i class="fas fa-map-marker-alt"></i> <?= e($d['location']) ?></span>
        <?php endif; ?>
        <?php if ($d['read_time']): ?>
          <span><i class="fas fa-clock"></i> <?= (int)$d['read_time'] ?> min read</span>
        <?php endif; ?>
        <span><i class="fas fa-pen-nib"></i> <?= e($siteTitle) ?></span>
      </div>
    </div>
  </div>
</section>


<!-- ARTICLE BODY -->
<div class="dest-detail-body">
  <div class="container">
    <div class="dest-detail-grid">

      <!-- MAIN ARTICLE -->
      <div class="dest-article">

        <?php if ($d['excerpt']): ?>
        <div class="article-section article-lead reveal">
          <p><?= e($d['excerpt']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Full content (rich HTML from TinyMCE) -->
        <?php if ($d['content']): ?>
        <div class="article-section rich-content reveal">
          <?= $d['content'] ?>
        </div>
        <?php endif; ?>

        <!-- Book Tour CTA -->
        <div class="article-book-btn reveal">
          <a href="tours.php" class="btn-primary" style="font-size:15px; padding:16px 44px;">
            <i class="fas fa-map-marked-alt"></i> Browse Tour Packages
          </a>
        </div>

        <!-- Share -->
        <div class="article-section reveal" style="border-top:1px solid var(--border);padding-top:28px;margin-top:28px;">
          <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;margin-bottom:16px;color:var(--teal-dark);">Share This Story</h4>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#1877f2;color:#fff;border-radius:6px;font-size:13px;text-decoration:none;">
              <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($d['title']) ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#1da1f2;color:#fff;border-radius:6px;font-size:13px;text-decoration:none;">
              <i class="fab fa-x-twitter"></i> Twitter
            </a>
            <a href="https://wa.me/?text=<?= urlencode($d['title'] . ' ' . $pageUrl) ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#25d366;color:#fff;border-radius:6px;font-size:13px;text-decoration:none;">
              <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <button onclick="navigator.clipboard.writeText(location.href).then(()=>{this.textContent='Copied!';setTimeout(()=>{this.innerHTML='<i class=\'fas fa-link\'></i> Copy Link';},2000);})"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--teal);color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;">
              <i class="fas fa-link"></i> Copy Link
            </button>
          </div>
        </div>

      </div><!-- /.dest-article -->


      <!-- SIDEBAR -->
      <div class="dest-sidebar">

        <!-- Quick Facts -->
        <?php if ($quickFacts): ?>
        <div class="dest-sidebar-card reveal">
          <h4>Quick Facts</h4>
          <div class="quick-facts">
            <?php foreach ($quickFacts as $qf): ?>
            <div class="qf-item">
              <i class="<?= e($qf['icon'] ?? 'fas fa-info-circle') ?> qf-icon"></i>
              <div>
                <span class="qf-label"><?= e($qf['label']) ?></span>
                <?= e($qf['value']) ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Best Time Widget -->
        <div class="dest-sidebar-card reveal">
          <h4>Best Time to Visit</h4>
          <div class="best-time-grid">
            <?php foreach ($bestMonths as $i => $cls): ?>
              <div class="month-tag <?= e($cls) ?>"><?= $months[$i] ?></div>
            <?php endforeach; ?>
          </div>
          <p style="font-size:12px;color:var(--text-light);margin-top:12px;line-height:1.6;">
            <span style="color:#27ae60;font-weight:600;">■</span> Best &nbsp;
            <span style="color:#e67e22;font-weight:600;">■</span> Good &nbsp;
            <span style="color:#c0392b;font-weight:600;">■</span> Avoid
          </p>
        </div>

        <!-- Related Destinations -->
        <?php if ($related): ?>
        <div class="dest-sidebar-card reveal">
          <h4>Explore More</h4>
          <div class="dest-mini-list">
            <?php foreach ($related as $rel): ?>
            <a href="destination-detail.php?slug=<?= urlencode($rel['slug']) ?>" class="dest-mini-item">
              <div class="dest-mini-img">
                <img src="<?= e(destImgD($rel['hero_image'])) ?>" alt="<?= e($rel['title']) ?>"/>
              </div>
              <div>
                <div class="dest-mini-name"><?= e($rel['title']) ?></div>
                <div class="dest-mini-cat"><?= e($rel['cat_name'] ?? '') ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Book CTA -->
        <div class="dest-sidebar-card reveal" style="background: linear-gradient(135deg, var(--teal-dark), var(--teal)); color:var(--white);">
          <h4 style="color:var(--white); border-bottom-color:rgba(201,168,76,0.3);">Plan a Trip Here?</h4>
          <p style="font-size:13.5px; color:rgba(255,255,255,0.75); line-height:1.7; margin-bottom:18px;">Let <?= e($siteTitle) ?> craft the perfect itinerary for you — private transfers, guided tours, and handpicked stays.</p>
          <a href="contact.php#contact-form" class="btn-primary" style="width:100%; justify-content:center;">
            <i class="fas fa-calendar-check"></i> Book This Destination
          </a>
          <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank"
             style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:10px;color:rgba(255,255,255,0.7);font-size:13px;text-decoration:none;">
            <i class="fab fa-whatsapp" style="color:#25d366;font-size:16px;"></i> Chat on WhatsApp
          </a>
        </div>

      </div><!-- /.dest-sidebar -->

    </div>
  </div>
</div>


<!-- RELATED DESTINATIONS -->
<?php if ($related): ?>
<section class="related-destinations section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Keep Exploring</div>
      <h2 class="section-title reveal">More <em>Destinations</em></h2>
      <p class="section-subtitle reveal">Discover more incredible places across Sri Lanka's diverse and beautiful landscape.</p>
    </div>
    <div class="related-dest-grid stagger-children">
      <?php foreach (array_slice($related, 0, 3) as $rel):
        $rCatCss = $catCssClass[$rel['cat_slug'] ?? ''] ?? 'cat-nature';
        $rCatLbl = $rel['cat_name'] ?? '';
      ?>
      <div class="dest-card reveal">
        <div class="dest-card-img">
          <img src="<?= e(destImgD($rel['hero_image'])) ?>" alt="<?= e($rel['title']) ?>"/>
          <span class="dest-card-cat <?= $rCatCss ?>"><?= e($rCatLbl) ?></span>
        </div>
        <div class="dest-card-body">
          <div class="dest-card-meta">
            <span><i class="fas fa-map-marker-alt"></i> Sri Lanka</span>
          </div>
          <div class="dest-card-title"><?= e($rel['title']) ?></div>
          <div class="dest-card-footer">
            <?php if ($rel['region']): ?>
              <span class="dest-card-region"><i class="fas fa-compass"></i> <?= e($rel['region']) ?></span>
            <?php endif; ?>
            <a href="destination-detail.php?slug=<?= urlencode($rel['slug']) ?>" class="read-story-btn">
              Read Story <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center reveal" style="margin-top:44px;">
      <a href="destinations.php" class="btn-primary"><i class="fas fa-globe-asia"></i> View All Destinations</a>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- TOUR PACKAGES -->
<?php if ($tours): ?>
<section class="related-tours section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Ready to Go?</div>
      <h2 class="section-title reveal">Related <em>Tour Packages</em></h2>
      <p class="section-subtitle reveal">Book one of our curated Sri Lanka tours and make this destination part of your journey.</p>
    </div>
    <div class="related-grid stagger-children">
      <?php
        $badgeMap = ['Popular'=>'badge-popular','Most Popular'=>'badge-popular','Best Seller'=>'badge-bestseller','Bestseller'=>'badge-bestseller','New'=>'badge-new','Honeymoon'=>'badge-honeymoon','Family'=>'badge-family','Adventure'=>'badge-adventure'];
        foreach ($tours as $t):
          $tImg       = $t['image'] ? (strpos($t['image'],'uploads/')===0 ? SITE_URL.'/'.$t['image'] : 'images/'.$t['image']) : 'images/tour-cultural.jpg';
          $tbadge     = $t['badge'] ?? '';
          $tbadgeCls  = $badgeMap[$tbadge] ?? 'badge-popular';
      ?>
      <div class="tour-card reveal">
        <div class="tour-img">
          <img src="<?= e($tImg) ?>" alt="<?= e($t['title']) ?>" loading="lazy"/>
          <?php if ($tbadge): ?><div class="tour-badge <?= $tbadgeCls ?>"><?= e($tbadge) ?></div><?php endif; ?>
          <?php if ($t['duration']): ?><div class="tour-duration"><i class="fas fa-clock"></i> <?= e($t['duration']) ?></div><?php endif; ?>
        </div>
        <div class="tour-body">
          <?php if ($t['cat_name']): ?><div class="tour-location"><i class="fas fa-map-marker-alt"></i> <?= e($t['cat_name']) ?></div><?php endif; ?>
          <div class="tour-name"><?= e($t['title']) ?></div>
          <div class="tour-desc"><?= e($t['short_desc'] ?? '') ?></div>
          <div class="tour-footer">
            <div><div class="tour-price-label">From</div><div class="tour-price"><?= tourPrice($t['price_usd'], $t['price_note'] ?: 'person') ?></div></div>
            <a href="<?= e(tourUrl($t)) ?>" class="btn-dark">View Details <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center reveal" style="margin-top:44px;">
      <a href="tours.php" class="btn-primary"><i class="fas fa-th-large"></i> View All Packages</a>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- CTA -->
<section class="dest-cta section-pad">
  <div class="container">
    <div class="dest-cta-inner reveal">
      <div class="section-tag">Looking for an</div>
      <h2 class="section-title">Exclusive <em>Customized Tour?</em></h2>
      <p>No problem! Tell us your dream and we'll build the perfect Sri Lanka experience just for you.</p>
      <div class="dest-cta-btns">
        <a href="contact.php#contact-form" class="btn-primary"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
        <a href="https://wa.me/<?= e($whatsapp) ?>" class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>
</body>
</html>
