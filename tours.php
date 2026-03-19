<?php
require_once 'includes/config.php';
$currentPage = 'tours';

/* ── FETCH DATA ── */
$categories = [];
if ($conn) {
    $r = $conn->query("SELECT tc.* FROM tour_categories tc
                        WHERE EXISTS (SELECT 1 FROM tours t WHERE t.category_id = tc.id AND t.is_active = 1)
                        ORDER BY tc.sort_order, tc.name");
    if ($r) $categories = $r->fetch_all(MYSQLI_ASSOC);
}

$tours = [];
if ($conn) {
    $r = $conn->query("SELECT t.*, tc.name as cat_name, tc.slug as cat_slug, tc.icon as cat_icon
                        FROM tours t LEFT JOIN tour_categories tc ON t.category_id=tc.id
                        WHERE t.is_active=1 ORDER BY t.sort_order, t.id");
    if ($r) $tours = $r->fetch_all(MYSQLI_ASSOC);
}

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp', '94770489956');

/* Badge class map */
$badgeMap = [
    'bestseller' => 'badge-bestseller', 'popular' => 'badge-popular',
    'new'        => 'badge-new',        'honeymoon'=> 'badge-honeymoon',
    'adventure'  => 'badge-adventure',  'family'   => 'badge-family',
    'featured'   => 'badge-bestseller',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Browse all Sri Lanka tour packages by GPS Lanka Travels | cultural tours, beach holidays, wildlife safaris, hill country, honeymoon packages and more."/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="All Sri Lanka Tours | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Browse all Sri Lanka tour packages — cultural tours, beach holidays, wildlife safaris, hill country, honeymoon packages and more."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/tours-hero.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/tours.php"/>
  <title>All Sri Lanka Tours | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/tours.css"/>
</head>
<body data-page="tours">

<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      All Sri Lanka Tours
    </div>
    <h1 class="page-hero-title">All <em>Sri Lanka Tours</em></h1>
    <p class="page-hero-sub">Handcrafted journeys across the pearl of the Indian Ocean</p>
  </div>
</section>


<!-- FILTER BAR -->
<div class="tours-filter-section">
  <div class="container">
    <div class="filter-bar">
      <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="text" id="tourSearch" placeholder="Search tours…"/>
      </div>
      <div class="filter-pills">
        <button class="filter-pill active" data-filter="all">All Tours</button>
        <?php foreach ($categories as $cat): ?>
          <button class="filter-pill" data-filter="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <select class="filter-sort" id="tourSort">
        <option value="default">Sort by</option>
        <option value="price-low">Price: Low → High</option>
        <option value="price-high">Price: High → Low</option>
        <option value="days-low">Duration: Shortest</option>
        <option value="days-high">Duration: Longest</option>
      </select>
      <div class="filter-count" id="tourCount">Showing <span><?= count($tours) ?></span> tour<?= count($tours) !== 1 ? 's' : '' ?></div>
    </div>
  </div>
</div>


<!-- TOURS GRID -->
<section class="tours-main">
  <div class="container">
    <div class="tours-grid-wrap">
      <div class="tours-grid" id="toursGrid">

        <?php if ($tours): ?>
          <?php foreach ($tours as $t): ?>
            <?php
            $catSlug  = $t['cat_slug'] ?? 'other';
            $days     = (int)$t['duration'];
            $img      = $t['image'] ? imgUrl($t['image']) : 'images/tour-cultural.jpg';
            $tags     = parseHighlights($t['highlights'] ?? '');
            $badgeText = $t['is_featured'] ? 'Featured' : '';
            $badgeCls  = 'badge-bestseller';
            ?>
            <div class="tour-card" data-category="<?= e($catSlug) ?>" data-price="<?= (int)$t['price_usd'] ?>" data-days="<?= $days ?>">
              <div class="tour-img">
                <img src="<?= e($img) ?>" alt="<?= e($t['title']) ?>"/>
                <?php if ($badgeText): ?>
                  <div class="tour-badge <?= $badgeCls ?>"><?= e($badgeText) ?></div>
                <?php endif; ?>
                <?php if ($t['duration']): ?>
                  <div class="tour-duration"><i class="fas fa-clock"></i> <?= e($t['duration']) ?></div>
                <?php endif; ?>
                <button class="tour-wish" title="Save to wishlist"><i class="fas fa-heart"></i></button>
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
                  <a href="<?= tourUrl($t) ?>" class="btn-dark">View Details <i class="fas fa-arrow-right"></i></a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        <?php else: ?>
          <div class="tours-empty visible" style="grid-column:1/-1;display:block;padding:80px 20px;text-align:center;">
            <i class="fas fa-map-marked-alt" style="font-size:56px;color:var(--gold-pale);margin-bottom:18px;display:block;"></i>
            <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--teal-dark);margin-bottom:10px;">No tours added yet</h3>
            <p style="color:var(--text-light);font-size:15px;">Tours you add in the admin panel will appear here automatically.</p>
          </div>
        <?php endif; ?>

        <div id="toursEmpty" class="tours-empty" style="display:none">
          <i class="fas fa-map-marked-alt"></i>
          <h3>No tours found</h3>
          <p>Try a different search term or clear the filters to see all tours.</p>
        </div>

      </div>
    </div>
  </div>
</section>


<!-- FEATURES STRIP -->
<div class="tours-features">
  <div class="container">
    <div class="tours-feat-grid reveal">
      <div class="tours-feat-item"><i class="fas fa-route"></i><h4>Fully Customisable</h4><p>Every tour can be adjusted to match your exact pace, budget and preferences.</p></div>
      <div class="tours-feat-item"><i class="fas fa-car"></i><h4>Luxury AC Vehicles</h4><p>Travel in comfort with our modern, air-conditioned fleet and experienced drivers.</p></div>
      <div class="tours-feat-item"><i class="fas fa-headset"></i><h4>24/7 Guest Support</h4><p>Our team is always reachable — day or night — throughout your entire journey.</p></div>
    </div>
  </div>
</div>


<!-- CUSTOM TOUR CTA -->
<section class="tours-custom-cta section-pad">
  <div class="container">
    <div class="tours-custom-inner reveal">
      <div class="section-tag">Can't Find What You Need?</div>
      <h2 class="section-title">Build Your Own <em>Custom Tour</em></h2>
      <p>Tell us your dream itinerary — duration, destinations, style and budget — and we'll craft a completely personalised Sri Lanka journey just for you.</p>
      <div class="tours-custom-btns">
        <a href="contact.php#contact-form"               class="btn-primary"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
        <a href="https://wa.me/<?= e($whatsapp) ?>"  class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script src="js/tours.js"></script>

</body>
</html>
