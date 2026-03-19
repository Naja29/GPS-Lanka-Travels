<?php
$_siteTitle = setting('site_name',    'GPS Lanka Travels');
$_tagline   = setting('site_tagline', "Sri Lanka's Premier Tour Operator");
$_phone     = setting('site_phone',   '+94 77 048 9956');
$_email     = setting('site_email',   'info@gpslankatravels.com');
$_whatsapp  = setting('site_whatsapp','94770489956');
$_siteLogo  = setting('site_logo');
$_fbUrl     = setting('facebook_url',    '');
$_igUrl     = setting('instagram_url',  '');
$_taUrl     = setting('tripadvisor_url','');
$_ytUrl     = setting('youtube_url',    '');
$_tkUrl     = setting('tiktok_url',     '');
$_twUrl     = setting('twitter_url',    '');
$_cp = $currentPage ?? 'home';
function navCls($p) { global $_cp; return $p === $_cp ? ' active' : ''; }
?>
<div class="site-header">
<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-inner">
    <div class="topbar-left">
      <a href="tel:<?= preg_replace('/[^0-9+]/', '', $_phone) ?>">
        <i class="fas fa-phone-alt"></i><?= e($_phone) ?>
      </a>
      <a href="mailto:<?= e($_email) ?>">
        <i class="fas fa-envelope"></i><?= e($_email) ?>
      </a>
    </div>
    <div class="topbar-right">
      <?php if ($_fbUrl): ?><a href="<?= e($_fbUrl) ?>" title="Facebook"    target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
      <?php if ($_igUrl): ?><a href="<?= e($_igUrl) ?>" title="Instagram"   target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
      <?php if ($_twUrl): ?><a href="<?= e($_twUrl) ?>" title="X / Twitter" target="_blank"><i class="fab fa-x-twitter"></i></a><?php endif; ?>
      <?php if ($_ytUrl): ?><a href="<?= e($_ytUrl) ?>" title="YouTube"     target="_blank"><i class="fab fa-youtube"></i></a><?php endif; ?>
      <?php if ($_tkUrl): ?><a href="<?= e($_tkUrl) ?>" title="TikTok"      target="_blank"><i class="fab fa-tiktok"></i></a><?php endif; ?>
      <?php if ($_taUrl): ?><a href="<?= e($_taUrl) ?>" title="TripAdvisor" target="_blank"><i class="fab fa-tripadvisor"></i></a><?php endif; ?>
      <a href="https://wa.me/<?= e($_whatsapp) ?>" title="WhatsApp" target="_blank"><i class="fab fa-whatsapp"></i></a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="index.php" class="logo">
      <?php if ($_siteLogo): ?>
        <img src="<?= imgUrl($_siteLogo) ?>" alt="<?= e($_siteTitle) ?>" class="logo-img"/>
      <?php else: ?>
        <img src="images/logo.png" alt="<?= e($_siteTitle) ?>" class="logo-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <div class="logo-icon" style="display:none"><i class="fas fa-compass"></i></div>
      <?php endif; ?>
      <div>
        <div class="logo-text"><?= e($_siteTitle) ?></div>
        <div class="logo-sub"><?= e($_tagline) ?></div>
      </div>
    </a>
    <ul class="nav-links">
      <li><a href="index.php"        class="nav-link<?= navCls('home') ?>">Home</a></li>
      <li><a href="about.php"        class="nav-link<?= navCls('about') ?>">About Us</a></li>
      <li><a href="tours.php"        class="nav-link<?= navCls('tours') ?>">All Sri Lanka Tours</a></li>
      <li><a href="destinations.php" class="nav-link<?= navCls('destinations') ?>">Destinations</a></li>
      <li><a href="blog.php"         class="nav-link<?= navCls('blog') ?>">Blog</a></li>
      <li><a href="gallery.php"      class="nav-link<?= navCls('gallery') ?>">Gallery</a></li>
      <li><a href="contact.php"      class="nav-link<?= navCls('contact') ?>">Contact</a></li>
      <li><a href="contact.php#contact-form" class="nav-link btn-book">Book Now</a></li>
    </ul>
    <button class="hamburger" id="hamburger" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

</div><!-- /.site-header -->

<div class="mobile-overlay" id="mobileOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-menu-close" id="mobileClose" aria-label="Close menu">
    <i class="fas fa-times"></i>
  </button>
  <ul>
    <li><a href="index.php">Home</a></li>
    <li><a href="about.php">About Us</a></li>
    <li><a href="tours.php">All Sri Lanka Tours</a></li>
    <li><a href="destinations.php">Destinations</a></li>
    <li><a href="blog.php">Blog</a></li>
    <li><a href="gallery.php">Gallery</a></li>
    <li><a href="contact.php">Contact</a></li>
    <li><a href="contact.php#contact-form" class="book-mobile">Book Now</a></li>
  </ul>
</div>
