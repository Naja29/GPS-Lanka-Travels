<?php

$activePage = $activePage ?? '';

$menu = [
    ['page'=>'dashboard',    'icon'=>'fas fa-th-large',      'label'=>'Dashboard'],
    ['page'=>'tours',        'icon'=>'fas fa-map-marked-alt', 'label'=>'Tours'],
    ['page'=>'tour-cats',    'icon'=>'fas fa-tags',           'label'=>'Tour Categories'],
    ['page'=>'enquiries',    'icon'=>'fas fa-envelope',       'label'=>'Enquiries',   'badge'=>true],
    ['page'=>'blog',         'icon'=>'fas fa-pen-nib',        'label'=>'Blog Posts'],
    ['page'=>'blog-cats',    'icon'=>'fas fa-folder',         'label'=>'Blog Categories'],
    ['page'=>'gallery',      'icon'=>'fas fa-images',         'label'=>'Gallery'],
    ['page'=>'testimonials', 'icon'=>'fas fa-star',           'label'=>'Testimonials'],
    ['page'=>'partners',     'icon'=>'fas fa-handshake',      'label'=>'Partners'],
    ['page'=>'slider',       'icon'=>'fas fa-film',           'label'=>'Homepage Slider'],
    ['page'=>'why-us',       'icon'=>'fas fa-award',          'label'=>'Why Choose Us'],
    ['page'=>'settings',     'icon'=>'fas fa-cog',            'label'=>'Settings'],
    ['page'=>'purge-cache',  'icon'=>'fas fa-fire-alt',       'label'=>'Purge Cache'],
];

// Count unread enquiries for badge
$enquiryBadge = 0;
if (isset($conn)) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM enquiries WHERE is_read = 0");
    if ($res) $enquiryBadge = $res->fetch_assoc()['cnt'];
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
                    ? '<span class="nav-badge">' . $enquiryBadge . '</span>' : '';
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
