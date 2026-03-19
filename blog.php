<?php
require_once 'includes/config.php';
$currentPage = 'blog';

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp', '94770489956');

/* ── NEWSLETTER TABLE (safe migration) ── */
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uq_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/* ── NEWSLETTER SUBSCRIBE HANDLER ── */
$nlMsg = '';
$nlError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nl_email'])) {
    $email = trim($_POST['nl_email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $nlError = 'Please enter a valid email address.';
    } elseif ($conn) {
        $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE subscribed_at=NOW(), is_active=1");
        $stmt->bind_param('s', $email);
        $nlMsg   = $stmt->execute() ? 'Thank you for subscribing!' : 'Something went wrong. Please try again.';
        $nlError = $nlMsg === 'Thank you for subscribing!' ? '' : $nlMsg;
        if ($nlError) $nlMsg = '';
    }
}

/* ── HELPERS ── */
$catIcons = [
    'travel-tips'  => 'fa-lightbulb',
    'culture'      => 'fa-landmark',
    'wildlife'     => 'fa-paw',
    'beach'        => 'fa-umbrella-beach',
    'hill-country' => 'fa-mountain',
    'food'         => 'fa-utensils',
];
$catClasses = [
    'travel-tips'  => 'bcat-tips',
    'culture'      => 'bcat-culture',
    'wildlife'     => 'bcat-wildlife',
    'beach'        => 'bcat-beach',
    'hill-country' => 'bcat-hill',
    'food'         => 'bcat-food',
];
function blogImg($img) {
    if (!$img) return 'images/dest-ella.jpg';
    if (strpos($img, 'uploads/') === 0) return SITE_URL . '/' . $img;
    return 'images/' . $img;
}
function fmtDate($dt) {
    return $dt ? date('F Y', strtotime($dt)) : '';
}

/* ── FETCH CATEGORIES with counts ── */
$categories = [];
if ($conn) {
    $r = $conn->query("SELECT bc.*, COUNT(bp.id) AS post_count
                        FROM blog_categories bc
                        INNER JOIN blog_posts bp ON bp.category_id = bc.id AND bp.is_published = 1
                        GROUP BY bc.id
                        HAVING post_count > 0
                        ORDER BY bc.sort_order, bc.name");
    if ($r) $categories = $r->fetch_all(MYSQLI_ASSOC);
}

/* ── ACTIVE CATEGORY FILTER ── */
$activeCat  = trim($_GET['cat'] ?? '');
$activeSearch = trim($_GET['q'] ?? '');

/* ── FETCH POSTS ── */
$posts   = [];
$featured = null;
if ($conn) {
    $where  = "WHERE bp.is_published = 1";
    $params = [];
    $types  = '';
    if ($activeCat) {
        $where   .= " AND bc.slug = ?";
        $params[] = $activeCat;
        $types   .= 's';
    }
    if ($activeSearch) {
        $like     = '%' . $activeSearch . '%';
        $where   .= " AND (bp.title LIKE ? OR bp.excerpt LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }
    $sql = "SELECT bp.*, bc.name AS cat_name, bc.slug AS cat_slug
            FROM blog_posts bp
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id
            $where ORDER BY bp.is_featured DESC, bp.published_at DESC";
    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result();
    } else {
        $r = $conn->query($sql);
    }
    if ($r) $posts = $r->fetch_all(MYSQLI_ASSOC);

    // Pull featured out (only on no-filter view)
    if (!$activeCat && !$activeSearch && $posts) {
        foreach ($posts as $i => $p) {
            if ($p['is_featured']) { $featured = $p; array_splice($posts, $i, 1); break; }
        }
        if (!$featured) { $featured = array_shift($posts); }
    }
}

/* ── RECENT POSTS (sidebar) ── */
$recentPosts = [];
if ($conn) {
    $r = $conn->query("SELECT bp.id, bp.title, bp.slug, bp.image, bp.published_at
                        FROM blog_posts bp WHERE bp.is_published=1
                        ORDER BY bp.published_at DESC LIMIT 4");
    if ($r) $recentPosts = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="GPS Lanka Travels Blog | Travel tips, destination guides, culture, wildlife and everything you need to know about Sri Lanka."/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="Travel Blog | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Travel tips, destination guides, culture, wildlife and everything you need to know about Sri Lanka."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/blog-hero.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/blog.php"/>
  <title>Travel Blog | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/blog.css"/>
</head>
<body data-page="blog">

<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      Blog
    </div>
    <h1 class="page-hero-title">Travel <em>Blog</em></h1>
    <p class="page-hero-sub">Stories, tips and guides to inspire your perfect Sri Lanka journey</p>
  </div>
</section>


<!-- FILTER BAR -->
<div class="blog-filter-section">
  <div class="container">
    <div class="blog-filter-bar">
      <a href="blog.php" class="blog-filter-pill <?= !$activeCat ? 'active' : '' ?>">All Posts</a>
      <?php foreach ($categories as $cat): ?>
        <a href="blog.php?cat=<?= e($cat['slug']) ?>"
           class="blog-filter-pill <?= $activeCat === $cat['slug'] ? 'active' : '' ?>">
          <?= e($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<!-- MAIN CONTENT -->
<div class="blog-main section-pad">
  <div class="container">
    <div class="blog-layout">

      <!-- POSTS COLUMN -->
      <div class="blog-posts-col">

        <?php if ($featured): ?>
        <!-- FEATURED POST -->
        <div class="blog-featured reveal">
          <a href="<?= e(blogUrl($featured)) ?>" class="blog-featured-card">
            <div class="blog-featured-img">
              <img src="<?= e(blogImg($featured['image'])) ?>" alt="<?= e($featured['title']) ?>"/>
              <div class="blog-featured-badge"><i class="fas fa-star"></i> Featured</div>
              <?php if ($featured['cat_name']): ?>
                <div class="blog-featured-cat"><?= e($featured['cat_name']) ?></div>
              <?php endif; ?>
            </div>
            <div class="blog-featured-body">
              <div class="section-tag">Editor's Pick</div>
              <h2><?= e($featured['title']) ?></h2>
              <div class="blog-meta">
                <span><i class="fas fa-user"></i> <?= e($featured['author'] ?: 'GPS Lanka Travels') ?></span>
                <span><i class="fas fa-calendar-alt"></i> <?= fmtDate($featured['published_at']) ?></span>
                <?php if ($featured['read_time']): ?><span><i class="fas fa-clock"></i> <?= (int)$featured['read_time'] ?> min read</span><?php endif; ?>
              </div>
              <p><?= e($featured['excerpt'] ?: '') ?></p>
              <span class="read-more-btn">Read Full Article <i class="fas fa-arrow-right"></i></span>
            </div>
          </a>
        </div>
        <?php endif; ?>

        <!-- POSTS GRID -->
        <?php if ($posts): ?>
        <div class="blog-grid stagger-children" id="blogGrid">
          <?php foreach ($posts as $p):
            $cSlug  = $p['cat_slug'] ?? '';
            $cClass = $catClasses[$cSlug] ?? 'bcat-tips';
          ?>
          <a href="<?= e(blogUrl($p)) ?>" class="blog-card reveal">
            <div class="blog-card-img">
              <img src="<?= e(blogImg($p['image'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy"/>
              <?php if ($p['cat_name']): ?>
                <span class="blog-card-cat <?= $cClass ?>"><?= e($p['cat_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="blog-card-body">
              <div class="blog-card-meta">
                <span><i class="fas fa-calendar-alt"></i> <?= fmtDate($p['published_at']) ?></span>
                <?php if ($p['read_time']): ?><span><i class="fas fa-clock"></i> <?= (int)$p['read_time'] ?> min</span><?php endif; ?>
              </div>
              <div class="blog-card-title"><?= e($p['title']) ?></div>
              <div class="blog-card-desc"><?= e($p['excerpt'] ?: '') ?></div>
              <div class="blog-card-footer">
                <div class="blog-card-author">
                  <div class="blog-card-author-dot"><i class="fas fa-user"></i></div>
                  <?= e($p['author'] ?: 'GPS Lanka Team') ?>
                </div>
                <span class="read-more-btn" style="font-size:12px;">Read <i class="fas fa-arrow-right"></i></span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <?php elseif (!$featured): ?>
        <!-- EMPTY STATE -->
        <div style="text-align:center;padding:80px 20px;">
          <i class="fas fa-newspaper" style="font-size:52px;color:var(--gold-pale);margin-bottom:18px;display:block;"></i>
          <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--teal-dark);margin-bottom:10px;">No posts found</h3>
          <p style="color:var(--text-light);">Try a different category or <a href="blog.php" style="color:var(--teal)">view all posts</a>.</p>
        </div>
        <?php endif; ?>

      </div><!-- /.blog-posts-col -->


      <!-- SIDEBAR -->
      <div class="blog-sidebar">

        <!-- Search -->
        <div class="blog-sidebar-card reveal">
          <h4>Search Blog</h4>
          <form method="GET" action="blog.php" class="blog-search-wrap">
            <input type="text" name="q" placeholder="Search articles…" value="<?= e($activeSearch) ?>"/>
            <button type="submit"><i class="fas fa-search"></i></button>
          </form>
        </div>

        <!-- Categories -->
        <div class="blog-sidebar-card reveal">
          <h4>Categories</h4>
          <div class="blog-cats-list">
            <?php foreach ($categories as $cat):
              $icon = $catIcons[$cat['slug']] ?? 'fa-folder';
            ?>
            <a href="blog.php?cat=<?= e($cat['slug']) ?>"
               class="blog-cat-item <?= $activeCat === $cat['slug'] ? 'active' : '' ?>">
              <span><i class="fas <?= $icon ?>"></i> <?= e($cat['name']) ?></span>
              <span class="blog-cat-count"><?= (int)$cat['post_count'] ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Recent Posts -->
        <?php if ($recentPosts): ?>
        <div class="blog-sidebar-card reveal">
          <h4>Recent Posts</h4>
          <div class="recent-posts-list">
            <?php foreach ($recentPosts as $rp): ?>
            <a href="<?= e(blogUrl($rp)) ?>" class="recent-post-item">
              <div class="recent-post-img">
                <img src="<?= e(blogImg($rp['image'])) ?>" alt="<?= e($rp['title']) ?>" loading="lazy"/>
              </div>
              <div>
                <div class="recent-post-title"><?= e($rp['title']) ?></div>
                <div class="recent-post-date"><i class="fas fa-calendar-alt"></i> <?= fmtDate($rp['published_at']) ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Tags -->
        <div class="blog-sidebar-card reveal">
          <h4>Popular Tags</h4>
          <div class="blog-tags-cloud">
            <?php
            $tags = ['Sri Lanka','Ella','Sigiriya','Safari','Beach','Kandy','Tea Country','Mirissa','Travel Tips','Food','Wildlife','Galle'];
            foreach ($tags as $tag):
            ?>
            <a href="blog.php?q=<?= urlencode($tag) ?>" class="blog-tag"><?= e($tag) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Newsletter -->
        <div class="newsletter-card reveal" id="newsletter">
          <h4 style="color:var(--white);border-bottom-color:rgba(201,168,76,0.3);margin-bottom:12px;padding-bottom:12px;font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;">
            Travel Newsletter
          </h4>
          <p>Get the latest Sri Lanka travel tips, destination guides and exclusive tour deals delivered to your inbox.</p>
          <?php if ($nlMsg): ?>
            <div style="background:rgba(39,174,96,.2);color:#a8f0c6;padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:13px;">
              <i class="fas fa-check-circle"></i> <?= e($nlMsg) ?>
            </div>
          <?php elseif ($nlError): ?>
            <div style="background:rgba(231,76,60,.2);color:#f5b7b1;padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:13px;">
              <i class="fas fa-exclamation-circle"></i> <?= e($nlError) ?>
            </div>
          <?php endif; ?>
          <form method="POST" action="blog.php#newsletter">
            <div class="newsletter-input-wrap">
              <input type="email" name="nl_email" placeholder="Your email address" required
                     value="<?= $nlMsg ? '' : e($_POST['nl_email'] ?? '') ?>"/>
              <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i> Subscribe</button>
            </div>
          </form>
        </div>

      </div><!-- /.blog-sidebar -->

    </div>
  </div>
</div>


<!-- CTA -->
<section class="blog-cta section-pad">
  <div class="container">
    <div class="blog-cta-inner reveal">
      <div class="section-tag">Ready to Visit?</div>
      <h2 class="section-title">Turn Inspiration into <em>Reality</em></h2>
      <p>Loved what you read? Let us build your perfect Sri Lanka itinerary — tailored to everything you've dreamed about.</p>
      <div class="blog-cta-btns">
        <a href="tours.php"   class="btn-primary"><i class="fas fa-map-marked-alt"></i> Browse Tour Packages</a>
        <a href="contact.php#contact-form" class="btn-outline"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
      </div>
    </div>
  </div>
</section>

<div class="scenic-banner">
  <div class="scenic-banner-text reveal">
    <h2>Every Journey Begins with<br><strong>A Single Story</strong></h2>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>

</body>
</html>
