<?php
/**
 * sitemap.xml.php — Dynamic XML Sitemap for Google Search Console
 * Access via: yourdomain.com/sitemap.xml (via .htaccess rewrite)
 * Or directly: yourdomain.com/sitemap.xml.php
 */
require_once 'includes/config.php';

$base = rtrim(SITE_URL, '/');

/* Fetch active tours */
$tours = [];
if ($conn) {
    $r = $conn->query("SELECT slug, id, updated_at FROM tours WHERE is_active=1 ORDER BY id ASC");
    if ($r) $tours = $r->fetch_all(MYSQLI_ASSOC);
}

/* Fetch active destinations */
$destinations = [];
if ($conn) {
    $r = $conn->query("SELECT slug, id, updated_at FROM destinations WHERE is_active=1 ORDER BY id ASC");
    if ($r) $destinations = $r->fetch_all(MYSQLI_ASSOC);
}

/* Fetch published blog posts */
$posts = [];
if ($conn) {
    $r = $conn->query("SELECT slug, id, updated_at FROM blog_posts WHERE is_published=1 ORDER BY created_at DESC");
    if ($r) $posts = $r->fetch_all(MYSQLI_ASSOC);
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <!-- Static Pages -->
  <url>
    <loc><?= $base ?>/</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?= $base ?>/tours</loc>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $base ?>/destinations</loc>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $base ?>/gallery</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $base ?>/blog</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $base ?>/about</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $base ?>/contact</loc>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $base ?>/privacy</loc>
    <changefreq>yearly</changefreq>
    <priority>0.3</priority>
  </url>
  <url>
    <loc><?= $base ?>/terms</loc>
    <changefreq>yearly</changefreq>
    <priority>0.3</priority>
  </url>

  <!-- Tour Pages -->
  <?php foreach ($tours as $t): ?>
  <url>
    <loc><?= $base ?>/tour-detail?slug=<?= urlencode($t['slug'] ?? $t['id']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($t['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.85</priority>
  </url>
  <?php endforeach; ?>

  <!-- Destination Pages -->
  <?php foreach ($destinations as $d): ?>
  <url>
    <loc><?= $base ?>/destination-detail?slug=<?= urlencode($d['slug'] ?? $d['id']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($d['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>

  <!-- Blog Posts -->
  <?php foreach ($posts as $p): ?>
  <url>
    <loc><?= $base ?>/blog-detail?slug=<?= urlencode($p['slug'] ?? $p['id']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>

</urlset>
