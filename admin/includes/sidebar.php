<?php

$activePage = $activePage ?? '';

$menu = [
    ['page'=>'dashboard',      'icon'=>'fas fa-th-large',       'label'=>'Dashboard'],
    ['page'=>'bookings',       'icon'=>'fas fa-calendar-check', 'label'=>'Tour Bookings', 'badge'=>'bk'],
    ['page'=>'enquiries',      'icon'=>'fas fa-envelope',       'label'=>'Messages',      'badge'=>true],
    ['page'=>'subscribers',    'icon'=>'fas fa-paper-plane',    'label'=>'Newsletter',    'badge'=>'nl'],
    ['page'=>'tours',          'icon'=>'fas fa-map-marked-alt', 'label'=>'Tours'],
    ['page'=>'destinations',   'icon'=>'fas fa-map-marker-alt', 'label'=>'Destinations'],
    ['page'=>'blog',           'icon'=>'fas fa-pen-nib',        'label'=>'Blog Posts'],
    ['page'=>'gallery',        'icon'=>'fas fa-images',         'label'=>'Gallery'],
    ['page'=>'team',           'icon'=>'fas fa-users',          'label'=>'Team Members'],
    ['page'=>'testimonials',   'icon'=>'fas fa-star',           'label'=>'Testimonials'],
    ['page'=>'partners',       'icon'=>'fas fa-handshake',      'label'=>'Partners'],
    ['page'=>'slider',         'icon'=>'fas fa-film',           'label'=>'Homepage Slider'],
    ['page'=>'services',       'icon'=>'fas fa-concierge-bell', 'label'=>'What We Offer'],
    ['page'=>'why-us',         'icon'=>'fas fa-award',          'label'=>'Why Choose Us'],
    ['page'=>'settings',       'icon'=>'fas fa-cog',            'label'=>'Settings'],
    ['page'=>'about-settings', 'icon'=>'fas fa-info-circle',   'label'=>'About Page'],
    ['page'=>'purge-cache',    'icon'=>'fas fa-fire-alt',       'label'=>'Purge Cache'],
];

// Count unread enquiries for badge
$enquiryBadge = 0;
if (isset($conn)) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM enquiries WHERE is_read = 0");
    if ($res) $enquiryBadge = $res->fetch_assoc()['cnt'];
}
// Count unread bookings for badge (create table if first run)
$bkBadge = 0;
if (isset($conn)) {
    $conn->query("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY, tour_id INT DEFAULT NULL,
        tour_title VARCHAR(300) NOT NULL, name VARCHAR(120) NOT NULL,
        email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL,
        tour_date DATE DEFAULT NULL, persons VARCHAR(50) DEFAULT NULL,
        message TEXT DEFAULT NULL, status VARCHAR(20) DEFAULT 'new',
        is_read TINYINT(1) DEFAULT 0, ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $res = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE is_read = 0");
    if ($res) $bkBadge = (int)($res->fetch_assoc()['cnt'] ?? 0);
}
// Count newsletter subscribers (create table if first run)
$nlBadge = 0;
if (isset($conn)) {
    $conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        email         VARCHAR(255) NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active     TINYINT(1) DEFAULT 1,
        UNIQUE KEY uq_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $nlSince = (int)($_SESSION['nl_last_viewed'] ?? 0);
    $res = $conn->query("SELECT COUNT(*) as cnt FROM newsletter_subscribers WHERE is_active = 1 AND UNIX_TIMESTAMP(subscribed_at) > $nlSince");
    if ($res) $nlBadge = (int)($res->fetch_assoc()['cnt'] ?? 0);
}
?>
<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <?php if (file_exists(__DIR__ . '/../../images/logo.png')): ?>
            <img src="<?= defined('SITE_URL') ? SITE_URL : '..' ?>/images/logo.png"
                 alt="GPS Lanka Travels"
                 class="sidebar-logo-img"
                 onerror="this.style.display='none';document.getElementById('sidebarLogoFallback').style.display='flex';">
            <div class="sidebar-logo-icon" id="sidebarLogoFallback" style="display:none;">
                <i class="fas fa-compass"></i>
            </div>
        <?php else: ?>
            <div class="sidebar-logo-icon" id="sidebarLogoFallback">
                <i class="fas fa-compass"></i>
            </div>
        <?php endif; ?>
        <div class="sidebar-logo-text">
            <div class="sidebar-brand">GPS Lanka</div>
            <div class="sidebar-sub">Admin Panel</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">
        <?php foreach ($menu as $item): ?>
            <?php
                $isActive = ($activePage === $item['page']);
                $badge = ($item['page'] === 'enquiries' && $enquiryBadge > 0)
                    ? '<span class="nav-badge">' . $enquiryBadge . '</span>'
                    : (($item['page'] === 'bookings' && $bkBadge > 0)
                    ? '<span class="nav-badge">' . $bkBadge . '</span>'
                    : (($item['page'] === 'subscribers' && $nlBadge > 0)
                    ? '<span class="nav-badge">' . $nlBadge . '</span>' : ''));
            ?>
            <a href="<?= $item['page'] ?>.php"
               class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="<?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
                <?= $badge ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom links -->
    <div class="sidebar-bottom">
        <a href="<?= defined('SITE_URL') ? SITE_URL : '../' ?>" target="_blank" class="sidebar-link sidebar-link-bottom">
            <i class="fas fa-external-link-alt"></i>
            <span>Visit Website</span>
        </a>
        <a href="logout.php" class="sidebar-link sidebar-link-bottom sidebar-link-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

</aside>
