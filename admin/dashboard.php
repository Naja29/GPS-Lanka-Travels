<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$stats = [];
$tableMap = [
    'bookings'     => ['label'=>'Tour Bookings', 'icon'=>'fas fa-calendar-check','color'=>'teal',   'link'=>'bookings.php'],
    'enquiries'    => ['label'=>'Messages',      'icon'=>'fas fa-envelope',       'color'=>'blue',   'link'=>'enquiries.php'],
    'tours'        => ['label'=>'Tours',         'icon'=>'fas fa-map-marked-alt', 'color'=>'gold',   'link'=>'tours.php'],
    'blog_posts'   => ['label'=>'Blog Posts',    'icon'=>'fas fa-pen-nib',        'color'=>'green',  'link'=>'blog.php'],
    'gallery'      => ['label'=>'Gallery',       'icon'=>'fas fa-images',         'color'=>'orange', 'link'=>'gallery.php'],
    'testimonials' => ['label'=>'Testimonials',  'icon'=>'fas fa-star',           'color'=>'red',    'link'=>'testimonials.php'],
];
foreach ($tableMap as $tbl => $info) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM `$tbl`");
    $stats[$tbl] = array_merge($info, ['count' => $r ? ($r->fetch_assoc()['cnt'] ?? 0) : 0]);
}
$unreadRes    = $conn->query("SELECT COUNT(*) as cnt FROM enquiries WHERE is_read = 0");
$unread       = $unreadRes ? $unreadRes->fetch_assoc()['cnt'] : 0;
$unreadBkRes  = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE is_read = 0");
$unreadBk     = $unreadBkRes ? $unreadBkRes->fetch_assoc()['cnt'] : 0;
$recentEnq    = $conn->query("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 6");
$recentBk     = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 6");
$recentBlog   = $conn->query("SELECT bp.*, bc.name as cat_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id ORDER BY bp.created_at DESC LIMIT 5");
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Dashboard | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-main">

  <div class="admin-topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div>
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-breadcrumb"><?= date('l, F j, Y') ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($unread > 0): ?>
      <a href="enquiries.php" class="topbar-badge-btn" title="<?= $unread ?> unread enquiries">
        <i class="fas fa-envelope"></i><span class="topbar-dot"></span>
      </a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>" target="_blank" class="topbar-badge-btn" title="Visit Website">
        <i class="fas fa-external-link-alt"></i>
      </a>
      <div class="topbar-avatar">
        <div class="avatar-circle"><?= strtoupper(substr(adminName(),0,1)) ?></div>
        <span class="topbar-name"><?= htmlspecialchars(adminName()) ?></span>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <div class="welcome-banner">
        <div class="welcome-text">
          <?php $lkHour = (int)(new DateTime('now', new DateTimeZone('Asia/Colombo')))->format('H'); ?>
          <h2>Good <?= ($lkHour < 12 ? 'Morning' : ($lkHour < 17 ? 'Afternoon' : 'Evening')) ?>, <?= htmlspecialchars(adminName()) ?>! 👋</h2>
          <p>Here's what's happening with GPS Lanka Travels today.</p>
        </div>
        <div class="welcome-icon"><i class="fas fa-compass"></i></div>
      </div>

      <div class="stats-grid">
        <?php foreach ($stats as $key => $s): ?>
        <a href="<?= $s['link'] ?>" class="stat-card">
          <div class="stat-icon <?= $s['color'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
          <div>
            <div class="stat-num"><?= number_format($s['count']) ?></div>
            <div class="stat-label"><?= $s['label'] ?>
              <?php if ($key==='enquiries' && $unread>0): ?>
                <span class="badge badge-unread" style="margin-left:6px;"><?= $unread ?> new</span>
              <?php elseif ($key==='bookings' && $unreadBk>0): ?>
                <span class="badge badge-unread" style="margin-left:6px;"><?= $unreadBk ?> new</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="quick-actions">
        <a href="tours.php?action=add"     class="quick-action-btn"><i class="fas fa-plus-circle"></i><span>Add Tour</span></a>
        <a href="bookings.php"             class="quick-action-btn"><i class="fas fa-calendar-check"></i><span>View Bookings</span></a>
        <a href="enquiries.php"            class="quick-action-btn"><i class="fas fa-envelope-open"></i><span>View Messages</span></a>
        <a href="gallery.php"              class="quick-action-btn"><i class="fas fa-cloud-upload-alt"></i><span>Upload Photos</span></a>
        <a href="blog.php?action=add"      class="quick-action-btn"><i class="fas fa-pen-nib"></i><span>Write Blog Post</span></a>
        <a href="slider.php"               class="quick-action-btn"><i class="fas fa-film"></i><span>Manage Slider</span></a>
        <a href="settings.php"             class="quick-action-btn"><i class="fas fa-cog"></i><span>Settings</span></a>
      </div>

      <div class="dash-grid">

        <div class="card">
          <div class="card-header" style="gap:12px;flex-wrap:wrap;">
            <div style="display:flex;gap:6px;">
              <button class="btn btn-sm btn-primary" id="tabBtnBk" onclick="switchTab('bk')">
                <i class="fas fa-calendar-check"></i> Bookings <?php if($unreadBk>0): ?><span class="badge badge-unread" style="margin-left:4px;"><?= $unreadBk ?></span><?php endif; ?>
              </button>
              <button class="btn btn-sm btn-outline" id="tabBtnEnq" onclick="switchTab('enq')">
                <i class="fas fa-envelope"></i> Messages <?php if($unread>0): ?><span class="badge badge-unread" style="margin-left:4px;"><?= $unread ?></span><?php endif; ?>
              </button>
            </div>
            <div style="display:flex;gap:6px;margin-left:auto;">
              <a href="bookings.php"  id="tabLinkBk"  class="btn btn-sm btn-outline">View All</a>
              <a href="enquiries.php" id="tabLinkEnq" class="btn btn-sm btn-outline" style="display:none;">View All</a>
            </div>
          </div>

          <!-- Bookings tab -->
          <div id="tabBk" class="table-wrap">
            <table class="admin-table">
              <thead><tr><th>Ref</th><th>Customer</th><th>Tour</th><th>Date</th><th>Status</th></tr></thead>
              <tbody>
              <?php if ($recentBk && $recentBk->num_rows > 0):
                while ($bk = $recentBk->fetch_assoc()): ?>
              <tr>
                <td><span style="font-weight:600;color:var(--teal);font-size:12px;">BK-<?= str_pad($bk['id'],5,'0',STR_PAD_LEFT) ?></span></td>
                <td><div class="col-title"><?= htmlspecialchars($bk['name']) ?></div>
                    <div class="text-muted text-sm"><?= htmlspecialchars($bk['email']) ?></div></td>
                <td><?= htmlspecialchars(mb_strimwidth($bk['tour_title'],0,30,'…')) ?></td>
                <td><?= $bk['tour_date'] ? date('d M Y', strtotime($bk['tour_date'])) : '—' ?></td>
                <td>
                  <?php if ($bk['status'] === 'new'): ?>
                    <span class="badge badge-unread"><i class="fas fa-circle" style="font-size:7px;"></i> New</span>
                  <?php elseif ($bk['status'] === 'confirmed'): ?>
                    <span class="badge badge-replied">Confirmed</span>
                  <?php elseif ($bk['status'] === 'cancelled'): ?>
                    <span class="badge badge-inactive">Cancelled</span>
                  <?php else: ?>
                    <span class="badge badge-pending"><?= htmlspecialchars($bk['status']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="5"><div class="empty-state" style="padding:30px;"><i class="fas fa-calendar-check"></i><p>No bookings yet</p></div></td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Messages tab -->
          <div id="tabEnq" class="table-wrap" style="display:none;">
            <table class="admin-table">
              <thead><tr><th>Name</th><th>Tour Type</th><th>Received</th><th>Status</th></tr></thead>
              <tbody>
              <?php if ($recentEnq && $recentEnq->num_rows > 0):
                while ($enq = $recentEnq->fetch_assoc()): ?>
              <tr>
                <td><div class="col-title"><?= htmlspecialchars($enq['name']) ?></div>
                    <div class="text-muted text-sm"><?= htmlspecialchars($enq['email']) ?></div></td>
                <td><?= htmlspecialchars($enq['tour_type'] ?: '—') ?></td>
                <td><?= timeAgo($enq['created_at']) ?></td>
                <td>
                  <?php if ($enq['status'] === 'new'): ?>
                    <span class="badge badge-unread"><i class="fas fa-circle" style="font-size:7px;"></i> New</span>
                  <?php elseif ($enq['status'] === 'replied'): ?>
                    <span class="badge badge-replied">Replied</span>
                  <?php elseif ($enq['status'] === 'closed'): ?>
                    <span class="badge badge-inactive">Closed</span>
                  <?php else: ?>
                    <span class="badge badge-pending">Read</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="4"><div class="empty-state" style="padding:30px;"><i class="fas fa-inbox"></i><p>No messages yet</p></div></td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:20px;">
          <div class="card">
            <div class="card-header">
              <span class="card-title"><i class="fas fa-pen-nib"></i> Recent Blog Posts</span>
              <a href="blog.php?action=add" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> New</a>
            </div>
            <div class="activity-list">
              <?php if ($recentBlog && $recentBlog->num_rows > 0):
                while ($post = $recentBlog->fetch_assoc()): ?>
              <div class="activity-item">
                <div class="activity-dot stat-icon teal"><i class="fas fa-pen-nib"></i></div>
                <div>
                  <div class="activity-text"><strong><?= htmlspecialchars(mb_strimwidth($post['title'],0,50,'…')) ?></strong></div>
                  <div class="activity-time"><?= htmlspecialchars($post['cat_name'] ?? 'Uncategorised') ?> · <?= timeAgo($post['created_at']) ?></div>
                </div>
              </div>
              <?php endwhile; else: ?>
              <div class="activity-item"><p class="text-muted text-sm" style="padding:8px 0;">No blog posts yet.</p></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-info-circle"></i> Site Info</span></div>
            <div class="card-body" style="font-size:13.5px;display:flex;flex-direction:column;gap:10px;">
              <div class="d-flex gap-2 align-center"><i class="fas fa-globe" style="color:var(--teal);width:16px;"></i>
                <a href="<?= SITE_URL ?>" target="_blank" style="color:var(--teal);"><?= SITE_URL ?></a></div>
              <div class="d-flex gap-2 align-center"><i class="fas fa-database" style="color:var(--teal);width:16px;"></i>
                <span><?= DB_NAME ?> @ port <?= DB_PORT ?></span></div>
              <div class="d-flex gap-2 align-center"><i class="fas fa-clock" style="color:var(--teal);width:16px;"></i>
                <span><?= date('d M Y, H:i') ?></span></div>
              <hr class="divider">
              <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-outline" style="width:100%;justify-content:center;">
                <i class="fas fa-external-link-alt"></i> Visit Website
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>
<script src="js/admin.js"></script>
<script>
function switchTab(tab) {
  const isBk = tab === 'bk';
  document.getElementById('tabBk').style.display    = isBk ? '' : 'none';
  document.getElementById('tabEnq').style.display   = isBk ? 'none' : '';
  document.getElementById('tabLinkBk').style.display  = isBk ? '' : 'none';
  document.getElementById('tabLinkEnq').style.display = isBk ? 'none' : '';
  document.getElementById('tabBtnBk').className  = 'btn btn-sm ' + (isBk ? 'btn-primary' : 'btn-outline');
  document.getElementById('tabBtnEnq').className = 'btn btn-sm ' + (isBk ? 'btn-outline' : 'btn-primary');
}
</script>
</body>
</html>
