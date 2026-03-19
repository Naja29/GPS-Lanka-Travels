<?php
require_once 'includes/config.php';
$currentPage = 'blog';

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp', '94770489956');

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
function fmtDate($dt) { return $dt ? date('F Y', strtotime($dt)) : ''; }
function fmtDateFull($dt) { return $dt ? date('d M Y', strtotime($dt)) : ''; }

/* ── LOAD POST ── */
$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);
$post = null;

if ($conn) {
    if ($slug) {
        $stmt = $conn->prepare("SELECT bp.*, bc.name AS cat_name, bc.slug AS cat_slug
                                 FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                                 WHERE bp.slug = ? AND bp.is_published = 1 LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $r = $stmt->get_result();
    } elseif ($id) {
        $stmt = $conn->prepare("SELECT bp.*, bc.name AS cat_name, bc.slug AS cat_slug
                                 FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                                 WHERE bp.id = ? AND bp.is_published = 1 LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result();
    }
    if (!empty($r)) $post = $r->fetch_assoc();

    /* Increment view count */
    if ($post) {
        $conn->query("UPDATE blog_posts SET views = views + 1 WHERE id = " . (int)$post['id']);
    }
}

/* 404 fallback */
if (!$post) {
    header('Location: blog.php');
    exit;
}

$p = $post;
$heroImg   = blogImg($p['image']);
$cSlug     = $p['cat_slug'] ?? '';
$cClass    = $catClasses[$cSlug] ?? 'bcat-tips';
$cIcon     = $catIcons[$cSlug]   ?? 'fa-newspaper';

/* ── RELATED POSTS (same category) ── */
$related = [];
if ($conn && $p['category_id']) {
    $r = $conn->query("SELECT bp.id, bp.title, bp.slug, bp.image, bp.excerpt,
                               bp.published_at, bp.read_time, bc.name AS cat_name, bc.slug AS cat_slug
                        FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                        WHERE bp.category_id = {$p['category_id']} AND bp.id != {$p['id']} AND bp.is_published = 1
                        ORDER BY bp.published_at DESC LIMIT 3");
    if ($r) $related = $r->fetch_all(MYSQLI_ASSOC);
}
/* Fill with any posts if not enough */
if (count($related) < 3 && $conn) {
    $excl = array_map(fn($x) => (int)$x['id'], $related);
    $excl[] = (int)$p['id'];
    $excl = implode(',', $excl);
    $need = 3 - count($related);
    $r2 = $conn->query("SELECT bp.id, bp.title, bp.slug, bp.image, bp.excerpt,
                                bp.published_at, bp.read_time, bc.name AS cat_name, bc.slug AS cat_slug
                         FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                         WHERE bp.id NOT IN ($excl) AND bp.is_published = 1
                         ORDER BY bp.published_at DESC LIMIT $need");
    if ($r2) $related = array_merge($related, $r2->fetch_all(MYSQLI_ASSOC));
}

/* ── RECENT POSTS (sidebar) ── */
$recentPosts = [];
if ($conn) {
    $r = $conn->query("SELECT id, title, slug, image, published_at FROM blog_posts
                        WHERE is_published = 1 AND id != {$p['id']}
                        ORDER BY published_at DESC LIMIT 4");
    if ($r) $recentPosts = $r->fetch_all(MYSQLI_ASSOC);
}

/* ── CATEGORIES with counts (sidebar) ── */
$categories = [];
if ($conn) {
    $r = $conn->query("SELECT bc.*, COUNT(bp.id) AS post_count
                        FROM blog_categories bc
                        LEFT JOIN blog_posts bp ON bp.category_id = bc.id AND bp.is_published = 1
                        GROUP BY bc.id ORDER BY bc.id");
    if ($r) $categories = $r->fetch_all(MYSQLI_ASSOC);
}

/* Share URLs */
$pageUrl   = SITE_URL . '/blog-detail.php?slug=' . urlencode($p['slug']);
$shareText = urlencode($p['title'] . ' | ' . $siteTitle);
$shareFb   = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($pageUrl);
$shareWa   = 'https://wa.me/?text=' . urlencode($p['title'] . ' ' . $pageUrl);
$shareTw   = 'https://twitter.com/intent/tweet?text=' . $shareText . '&url=' . urlencode($pageUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?= e($p['excerpt'] ?: $p['title']) ?> | <?= e($siteTitle) ?>"/>
  <?php
  $ogImg  = $p['image']
      ? (strpos($p['image'], 'uploads/') === 0 ? SITE_URL . '/' . $p['image'] : SITE_URL . '/images/' . $p['image'])
      : SITE_URL . '/images/dest-ella.jpg';
  $ogDesc = mb_substr(strip_tags($p['excerpt'] ?? $p['title']), 0, 160);
  ?>
  <meta property="og:type"              content="article"/>
  <meta property="og:site_name"         content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"             content="<?= e($p['title']) ?> | <?= e($siteTitle) ?>"/>
  <meta property="og:description"       content="<?= e($ogDesc) ?>"/>
  <meta property="og:image"             content="<?= e($ogImg) ?>"/>
  <meta property="og:url"               content="<?= e($pageUrl) ?>"/>
  <?php if ($p['published_at']): ?>
  <meta property="article:published_time" content="<?= date('c', strtotime($p['published_at'])) ?>"/>
  <?php endif; ?>
  <title><?= e($p['title']) ?> | <?= e($siteTitle) ?> Blog</title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/blog.css"/>
</head>
<body data-page="blog">

<?php include 'includes/header.php'; ?>

<!-- DETAIL HERO -->
<section class="blog-detail-hero" style="--blog-hero-bg:url('<?= e($heroImg) ?>')">
  <div class="blog-detail-hero-bg"></div>
  <div class="blog-detail-hero-content">
    <div class="blog-detail-hero-inner">
      <?php if ($p['cat_name']): ?>
      <div class="blog-detail-cat-tag">
        <i class="fas <?= $cIcon ?>"></i> <?= e($p['cat_name']) ?>
      </div>
      <?php endif; ?>
      <h1 class="blog-detail-title"><?= e($p['title']) ?></h1>
      <div class="blog-detail-meta">
        <span><i class="fas fa-user"></i> <?= e($p['author'] ?: 'GPS Lanka Travels') ?></span>
        <span><i class="fas fa-calendar-alt"></i> <?= fmtDateFull($p['published_at']) ?></span>
        <?php if ($p['read_time']): ?><span><i class="fas fa-clock"></i> <?= (int)$p['read_time'] ?> min read</span><?php endif; ?>
        <span><i class="fas fa-eye"></i> <?= number_format((int)$p['views'] + 1) ?> views</span>
      </div>
    </div>
  </div>
</section>


<!-- ARTICLE BODY -->
<div class="blog-detail-body">
  <div class="container">
    <div class="blog-detail-grid">

      <!-- ARTICLE -->
      <div class="blog-article">

        <!-- Excerpt / lead -->
        <?php if ($p['excerpt']): ?>
        <div class="blog-article-card reveal">
          <p class="blog-lead"><?= e($p['excerpt']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Hero image -->
        <div class="article-img-full reveal">
          <img src="<?= e($heroImg) ?>" alt="<?= e($p['title']) ?>"/>
        </div>

        <!-- Main content -->
        <?php if ($p['content']): ?>
        <div class="blog-article-card reveal">
          <div class="blog-content rich-content">
            <?= $p['content'] ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Share -->
        <div class="blog-article-card reveal">
          <div class="blog-share">
            <span class="blog-share-label">Share this article:</span>
            <a href="<?= $shareFb ?>" class="share-btn share-fb" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i> Facebook</a>
            <a href="<?= $shareTw ?>" class="share-btn share-tw" target="_blank" rel="noopener"><i class="fab fa-twitter"></i> Twitter</a>
            <a href="<?= $shareWa ?>" class="share-btn share-wa" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            <button class="share-btn share-cp" onclick="navigator.clipboard.writeText('<?= e($pageUrl) ?>');this.innerHTML='<i class=\'fas fa-check\'></i> Copied!'">
              <i class="fas fa-link"></i> Copy Link
            </button>
          </div>
        </div>

      </div><!-- /.blog-article -->


      <!-- SIDEBAR -->
      <div class="blog-sidebar">

        <!-- Author -->
        <div class="blog-sidebar-card reveal" style="text-align:center;">
          <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-light));display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:24px;color:var(--gold-light);">
            <i class="fas fa-compass"></i>
          </div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--teal-dark);margin-bottom:6px;"><?= e($p['author'] ?: 'GPS Lanka Travels') ?></div>
          <div style="font-size:12px;color:var(--text-light);margin-bottom:12px;">Sri Lanka's Premier Tour Operator</div>
          <p style="font-size:13px;color:var(--text-light);line-height:1.65;">Our team of experienced local travel experts share their insider knowledge to help you discover the very best of Sri Lanka.</p>
        </div>

        <!-- Categories -->
        <?php if ($categories): ?>
        <div class="blog-sidebar-card reveal">
          <h4>Categories</h4>
          <div class="blog-cats-list">
            <?php foreach ($categories as $cat):
              $icon = $catIcons[$cat['slug']] ?? 'fa-folder';
            ?>
            <a href="blog.php?cat=<?= e($cat['slug']) ?>" class="blog-cat-item <?= $cSlug === $cat['slug'] ? 'active' : '' ?>">
              <span><i class="fas <?= $icon ?>"></i> <?= e($cat['name']) ?></span>
              <span class="blog-cat-count"><?= (int)$cat['post_count'] ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

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
          <h4>Tags</h4>
          <div class="blog-tags-cloud">
            <?php
            $tags = ['Sri Lanka','Ella','Sigiriya','Safari','Beach','Kandy','Tea Country','Mirissa','Travel Tips','Food','Wildlife','Galle'];
            foreach ($tags as $tag):
            ?>
            <a href="blog.php?q=<?= urlencode($tag) ?>" class="blog-tag"><?= e($tag) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Plan Your Trip CTA -->
        <div class="blog-sidebar-card reveal" style="background:linear-gradient(135deg,var(--teal-dark),var(--teal));">
          <h4 style="color:var(--white);border-bottom-color:rgba(201,168,76,0.3);">Plan Your Trip</h4>
          <p style="font-size:13.5px;color:rgba(255,255,255,0.75);line-height:1.7;margin-bottom:18px;">Inspired by this article? Let us build your perfect Sri Lanka itinerary.</p>
          <a href="tours.php" class="btn-primary" style="width:100%;justify-content:center;">
            <i class="fas fa-map-marked-alt"></i> View Tour Packages
          </a>
          <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank"
             style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:10px;color:rgba(255,255,255,0.7);font-size:13px;text-decoration:none;">
            <i class="fab fa-whatsapp" style="color:#25d366;font-size:16px;"></i> Chat on WhatsApp
          </a>
        </div>

      </div><!-- /.blog-sidebar -->

    </div>
  </div>
</div>


<!-- RELATED POSTS -->
<?php if ($related): ?>
<section class="related-posts section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Keep Reading</div>
      <h2 class="section-title reveal">Related <em>Articles</em></h2>
    </div>
    <div class="related-posts-grid stagger-children">
      <?php foreach ($related as $rp):
        $rcSlug  = $rp['cat_slug'] ?? '';
        $rcClass = $catClasses[$rcSlug] ?? 'bcat-tips';
      ?>
      <a href="<?= e(blogUrl($rp)) ?>" class="blog-card reveal">
        <div class="blog-card-img">
          <img src="<?= e(blogImg($rp['image'])) ?>" alt="<?= e($rp['title']) ?>" loading="lazy"/>
          <?php if ($rp['cat_name']): ?>
            <span class="blog-card-cat <?= $rcClass ?>"><?= e($rp['cat_name']) ?></span>
          <?php endif; ?>
        </div>
        <div class="blog-card-body">
          <div class="blog-card-meta">
            <span><i class="fas fa-calendar-alt"></i> <?= fmtDate($rp['published_at']) ?></span>
            <?php if ($rp['read_time']): ?><span><i class="fas fa-clock"></i> <?= (int)$rp['read_time'] ?> min</span><?php endif; ?>
          </div>
          <div class="blog-card-title"><?= e($rp['title']) ?></div>
          <div class="blog-card-desc"><?= e($rp['excerpt'] ?: '') ?></div>
          <div class="blog-card-footer">
            <div class="blog-card-author">
              <div class="blog-card-author-dot"><i class="fas fa-user"></i></div>
              GPS Lanka Team
            </div>
            <span class="read-more-btn" style="font-size:12px;">Read <i class="fas fa-arrow-right"></i></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="text-center reveal" style="margin-top:44px;">
      <a href="blog.php" class="btn-primary"><i class="fas fa-th-large"></i> View All Blog Posts</a>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- CTA -->
<section class="blog-cta section-pad">
  <div class="container">
    <div class="blog-cta-inner reveal">
      <div class="section-tag">Ready to Visit?</div>
      <h2 class="section-title">Turn Inspiration into <em>Reality</em></h2>
      <p>Let GPS Lanka Travels build your perfect Sri Lanka itinerary — all the places in this article and more, crafted just for you.</p>
      <div class="blog-cta-btns">
        <a href="tours.php"   class="btn-primary"><i class="fas fa-map-marked-alt"></i> Browse Tour Packages</a>
        <a href="contact.php#contact-form" class="btn-outline"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
      </div>
    </div>
  </div>
</section>

<div class="scenic-banner">
  <div class="scenic-banner-text reveal">
    <h2>The Adventure of a Lifetime<br><strong>Awaits You in Sri Lanka</strong></h2>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>

</body>
</html>
