<?php
require_once 'includes/config.php';
$currentPage = 'destinations';

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp', '94770489956');

/* ── FETCH DESTINATION CATEGORIES (only those with active destinations) ── */
$destCats = [];
if ($conn) {
    $r = $conn->query("SELECT dc.* FROM destination_categories dc
                        WHERE EXISTS (SELECT 1 FROM destinations d WHERE d.category_id = dc.id AND d.is_active = 1)
                        ORDER BY dc.sort_order ASC, dc.name ASC");
    if ($r) $destCats = $r->fetch_all(MYSQLI_ASSOC);
}

/* CSS class map by slug */
$catCssMap = [
    'nature'   => 'cat-nature',
    'culture'  => 'cat-culture',
    'beach'    => 'cat-beach',
    'wildlife' => 'cat-wildlife',
    'hill'     => 'cat-nature',
];

/* ── FETCH DESTINATIONS ── */
$featured = null;
$dests    = [];
if ($conn) {
    $r = $conn->query("SELECT d.id, d.title, d.slug, d.location, d.region, d.hero_image,
                              d.read_time, d.excerpt, d.is_featured,
                              dc.name AS cat_name, dc.slug AS cat_slug, dc.icon AS cat_icon
                        FROM destinations d
                        LEFT JOIN destination_categories dc ON dc.id = d.category_id
                        WHERE d.is_active = 1
                        ORDER BY d.is_featured DESC, d.sort_order ASC, d.created_at DESC");
    if ($r) {
        $all = $r->fetch_all(MYSQLI_ASSOC);
        foreach ($all as $i => $d) {
            if (!$featured && $d['is_featured']) {
                $featured = $d;
            } else {
                $dests[] = $d;
            }
        }
        // If no featured flag, use first
        if (!$featured && $all) {
            $featured = array_shift($all);
            $dests    = $all;
        }
    }
}

function destImg($img) {
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
  <meta name="description" content="Explore Sri Lanka's most stunning destinations | beaches, ancient cities, wildlife parks, hill country and more with <?= e($siteTitle) ?>."/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="Destinations | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Explore Sri Lanka's most stunning destinations — beaches, ancient cities, wildlife parks, hill country and more."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/destinations-hero.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/destinations.php"/>
  <title>Destinations | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/destinations.css"/>
</head>
<body data-page="destinations">

<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      Destinations
    </div>
    <h1 class="page-hero-title">Explore <em>Destinations</em></h1>
    <p class="page-hero-sub">Stories, tips and guides for your Sri Lanka adventure</p>
  </div>
</section>

<!-- FILTER TABS -->
<div class="dest-filter-section">
  <div class="container">
    <div class="dest-filter-bar">
      <button class="dest-filter-pill active" data-filter="all">All Stories</button>
      <?php foreach ($destCats as $dc): ?>
        <button class="dest-filter-pill" data-filter="<?= e($dc['slug']) ?>"><?= e($dc['name']) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="destinations-main">
  <div class="container">

    <?php if ($featured): ?>
    <!-- FEATURED -->
    <div class="dest-featured-wrap">
      <a href="destination-detail.php?slug=<?= urlencode($featured['slug']) ?>"
         class="dest-featured-card" data-filter-cat="<?= e($featured['cat_slug'] ?? '') ?>">
        <div class="dest-featured-img">
          <img src="<?= e(destImg($featured['hero_image'])) ?>" alt="<?= e($featured['title']) ?>"/>
          <div class="dest-featured-badge"><i class="fas fa-star"></i> Featured</div>
          <?php if ($featured['cat_name']): ?>
          <div class="dest-featured-cat">
            <?php if ($featured['cat_icon']): ?><i class="<?= e($featured['cat_icon']) ?>"></i> <?php endif; ?>
            <?= e($featured['cat_name']) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="dest-featured-body">
          <div class="section-tag">Editor's Pick</div>
          <h2><?= e($featured['title']) ?></h2>
          <div class="dest-featured-meta">
            <?php if ($featured['location']): ?>
              <span><i class="fas fa-map-marker-alt"></i> <?= e($featured['location']) ?></span>
            <?php endif; ?>
            <?php if ($featured['read_time']): ?>
              <span><i class="fas fa-clock"></i> <?= (int)$featured['read_time'] ?> min read</span>
            <?php endif; ?>
          </div>
          <?php if ($featured['excerpt']): ?>
            <p><?= e($featured['excerpt']) ?></p>
          <?php endif; ?>
          <span class="read-story-btn">Read Full Story <i class="fas fa-arrow-right"></i></span>
        </div>
      </a>
    </div>
    <?php endif; ?>

    <!-- DESTINATIONS GRID -->
    <?php if ($dests): ?>
    <div class="dest-grid-section">
      <div class="dest-grid" id="destGrid">
        <?php foreach ($dests as $d):
          $catCss = $catCssMap[$d['cat_slug'] ?? ''] ?? 'cat-nature';
          $catLbl = $d['cat_name'] ?? '';
        ?>
        <div class="dest-card" data-cat="<?= e($d['cat_slug'] ?? '') ?>">
          <div class="dest-card-img">
            <img src="<?= e(destImg($d['hero_image'])) ?>" alt="<?= e($d['title']) ?>"/>
            <span class="dest-card-cat <?= $catCss ?>">
              <?php if ($d['cat_icon']): ?><i class="<?= e($d['cat_icon']) ?>"></i> <?php endif; ?>
              <?= e($catLbl) ?>
            </span>
          </div>
          <div class="dest-card-body">
            <div class="dest-card-meta">
              <?php if ($d['location']): ?>
                <span><i class="fas fa-map-marker-alt"></i> <?= e($d['location']) ?></span>
              <?php endif; ?>
              <?php if ($d['read_time']): ?>
                <span><i class="fas fa-clock"></i> <?= (int)$d['read_time'] ?> min read</span>
              <?php endif; ?>
            </div>
            <div class="dest-card-title"><?= e($d['title']) ?></div>
            <?php if ($d['excerpt']): ?>
              <div class="dest-card-desc"><?= e($d['excerpt']) ?></div>
            <?php endif; ?>
            <div class="dest-card-footer">
              <?php if ($d['region']): ?>
                <span class="dest-card-region"><i class="fas fa-compass"></i> <?= e($d['region']) ?></span>
              <?php endif; ?>
              <a href="destination-detail.php?slug=<?= urlencode($d['slug']) ?>" class="read-story-btn">
                Read Story <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif (!$featured): ?>
    <div style="text-align:center;padding:80px 20px">
      <i class="fas fa-map-marker-alt" style="font-size:52px;color:var(--gold-pale);margin-bottom:18px;display:block;"></i>
      <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--teal-dark);margin-bottom:10px;">No destinations yet</h3>
      <p style="color:var(--text-light);">Check back soon — destination guides are coming!</p>
    </div>
    <?php endif; ?>

  </div>
</div>


<!-- TOURISM TRENDS -->
<section class="tourism-trends section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Travel Updates</div>
      <h2 class="section-title reveal">Sri Lanka <em>Tourism Trends</em></h2>
      <p class="section-subtitle reveal">Stay informed with the latest developments shaping travel in Sri Lanka today.</p>
    </div>

    <div class="trends-intro reveal">
      <div class="trends-intro-text">
        <p>Sri Lanka's tourism industry is experiencing a remarkable renaissance. With improved connectivity, world-class infrastructure investments, and a growing reputation for sustainable travel, the island is welcoming record numbers of international visitors.</p>
        <p>From eco-lodges in the highlands to luxury beach resorts in the south, the range of accommodation and experience on offer has never been greater — making Sri Lanka one of Asia's most compelling travel destinations.</p>
      </div>
      <div class="trends-quote">
        <p>"Discover Sri Lanka. Experience Paradise. Travel with <?= e($siteTitle) ?>."</p>
        <cite>— <?= e($siteTitle) ?>, Our Perspective</cite>
      </div>
    </div>

    <div class="trends-grid stagger-children">
      <div class="trend-card reveal">
        <div class="trend-icon green"><i class="fas fa-chart-line"></i></div>
        <h4>Sri Lanka Tourism Continues Strong Growth</h4>
        <p>International arrivals are growing significantly year-on-year, reflecting increasing global interest in Sri Lanka's unique blend of culture, nature and beaches.</p>
      </div>
      <div class="trend-card reveal">
        <div class="trend-icon blue"><i class="fas fa-plane"></i></div>
        <h4>Improved Air Connectivity &amp; New Markets</h4>
        <p>Bandaranaike International Airport now connects to major hubs including Dubai, London, Singapore and Doha — opening up traveler options from previously underserved markets.</p>
      </div>
      <div class="trend-card reveal">
        <div class="trend-icon purple"><i class="fas fa-mobile-alt"></i></div>
        <h4>Digital Convenience for Travelers</h4>
        <p>Sri Lanka Tourism is transforming rapidly — cashless payments, regional taxis, and digital guides are creating a smoother visitor experience across all touchpoints.</p>
      </div>
      <div class="trend-card reveal">
        <div class="trend-icon gold"><i class="fas fa-laptop"></i></div>
        <h4>Rise of Long-Stay &amp; Digital Nomad Travel</h4>
        <p>Sri Lanka's landscapes, affordable living costs and strong internet connectivity are attracting a growing wave of remote workers and digital nomads.</p>
      </div>
      <div class="trend-card reveal">
        <div class="trend-icon teal"><i class="fas fa-leaf"></i></div>
        <h4>Focus on Sustainable &amp; Experiential Tourism</h4>
        <p>More travelers are choosing eco-lodges, community-based experiences and wildlife-responsible operators that align with Sri Lanka's natural and cultural heritage.</p>
      </div>
      <div class="trend-card reveal">
        <div class="trend-icon red"><i class="fas fa-pray"></i></div>
        <h4>Cultural, Spiritual &amp; Special Interest Tourism</h4>
        <p>Buddhist pilgrimage circuits, Ayurvedic wellness retreats, and culinary tourism are rapidly growing niches attracting international visitors seeking deeper connections.</p>
      </div>
      <div class="trend-card reveal">
        <div class="trend-icon orange"><i class="fas fa-water"></i></div>
        <h4>Adventure &amp; Marine Tourism Expansion</h4>
        <p>Surfing, whale watching, diving, and white-water rafting are drawing adventure travellers globally — expanding Sri Lanka's appeal far beyond beaches and culture.</p>
      </div>
    </div>
  </div>
</section>


<!-- CTA -->
<section class="dest-cta section-pad">
  <div class="container">
    <div class="dest-cta-inner reveal">
      <div class="section-tag">Looking for an</div>
      <h2 class="section-title">Exclusive <em>Customized Tour?</em></h2>
      <p>Tell us your dream destination and we'll build a completely personalised Sri Lanka journey just for you — your pace, your budget, your adventure.</p>
      <div class="dest-cta-btns">
        <a href="contact.php#contact-form" class="btn-primary"><i class="fas fa-pencil-alt"></i> Plan My Tour</a>
        <a href="https://wa.me/<?= e($whatsapp) ?>" class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<div class="scenic-banner" style="background-image: url('images/scenic-banner.jpg');">
  <div class="scenic-banner-text reveal">
    <h2>Adventure Awaits in<br><strong>Every Corner</strong></h2>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script>
const pills = document.querySelectorAll('.dest-filter-pill');
const cards = document.querySelectorAll('.dest-card');
pills.forEach(pill => {
  pill.addEventListener('click', () => {
    pills.forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    const f = pill.dataset.filter;
    cards.forEach(card => {
      card.style.display = (f === 'all' || card.dataset.cat === f) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>
