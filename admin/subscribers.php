<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'subscribers';
$msg        = $_GET['msg'] ?? '';

/* Mark subscribers as seen */
$_SESSION['nl_last_viewed'] = time();

/* ── SAFE MIGRATION ── */
$conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active     TINYINT(1) DEFAULT 1,
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── TOGGLE ACTIVE ── */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE newsletter_subscribers SET is_active = 1 - is_active WHERE id = $id");
    header('Location: subscribers.php?msg=updated'); exit;
}

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM newsletter_subscribers WHERE id = $id");
    header('Location: subscribers.php?msg=deleted'); exit;
}

/* ── EXPORT CSV ── */
if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT email, subscribed_at, is_active FROM newsletter_subscribers ORDER BY subscribed_at DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Subscribed At', 'Active']);
    while ($r = $rows->fetch_assoc()) {
        fputcsv($out, [$r['email'], $r['subscribed_at'], $r['is_active'] ? 'Yes' : 'No']);
    }
    fclose($out); exit;
}

/* ── FETCH ── */
$filter      = $_GET['filter'] ?? 'all';
$whereClause = $filter === 'active'   ? 'WHERE is_active = 1'
             : ($filter === 'inactive' ? 'WHERE is_active = 0' : '');
$subscribers  = $conn->query("SELECT * FROM newsletter_subscribers $whereClause ORDER BY subscribed_at DESC")->fetch_all(MYSQLI_ASSOC);
$totalActive  = (int)$conn->query("SELECT COUNT(*) AS c FROM newsletter_subscribers WHERE is_active=1")->fetch_assoc()['c'];
$totalAll     = (int)$conn->query("SELECT COUNT(*) AS c FROM newsletter_subscribers")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Newsletter Subscribers | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.filter-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.ftab{padding:7px 16px;border-radius:20px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:#fff;color:var(--text-mid);cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.ftab:hover{border-color:var(--teal);color:var(--teal)}
.ftab.active{background:var(--teal-dark);color:#fff;border-color:var(--teal-dark)}
.ftab-count{background:rgba(255,255,255,.25);padding:1px 7px;border-radius:10px;font-size:11px}
.ftab:not(.active) .ftab-count{background:var(--off-white);color:var(--text-light)}
.status-active{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--green-pale);color:var(--green);font-size:11.5px;font-weight:700}
.status-inactive{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--off-white);color:var(--text-light);font-size:11.5px;font-weight:600}
.act-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-tog-off{background:var(--orange-pale);color:var(--orange)}.btn-tog-off:hover{background:var(--orange);color:#fff}
.btn-tog-on{background:var(--green-pale);color:var(--green)}.btn-tog-on:hover{background:var(--green);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-light)}
.empty-state i{font-size:52px;opacity:.2;display:block;margin-bottom:12px}
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-main">

  <!-- TOPBAR -->
  <div class="admin-topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div>
        <div class="topbar-title">Newsletter Subscribers</div>
        <div class="topbar-breadcrumb">Blog / Newsletter</div>
      </div>
    </div>
    <div class="topbar-right">
      <a href="subscribers.php?export=1" class="btn btn-outline btn-sm" style="border-color:#27ae60;color:#27ae60" title="Export to CSV">
        <i class="fas fa-file-csv"></i> Export CSV
      </a>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'deleted'): ?>
        <div class="alert alert-warning"><i class="fas fa-trash"></i> Subscriber removed.</div>
      <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Status updated.</div>
      <?php endif; ?>

      <div class="page-header">
        <div class="page-header-left">
          <h1><i class="fas fa-paper-plane" style="color:var(--teal);margin-right:8px;font-size:20px"></i>Newsletter Subscribers</h1>
          <p><?= $totalActive ?> active &mdash; <?= $totalAll ?> total</p>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="filter-tabs">
        <a href="subscribers.php" class="ftab <?= $filter === 'all' ? 'active' : '' ?>">
          All <span class="ftab-count"><?= $totalAll ?></span>
        </a>
        <a href="subscribers.php?filter=active" class="ftab <?= $filter === 'active' ? 'active' : '' ?>">
          Active <span class="ftab-count"><?= $totalActive ?></span>
        </a>
        <a href="subscribers.php?filter=inactive" class="ftab <?= $filter === 'inactive' ? 'active' : '' ?>">
          Unsubscribed <span class="ftab-count"><?= $totalAll - $totalActive ?></span>
        </a>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:50px">#</th>
                <th>Email</th>
                <th>Subscribed</th>
                <th style="width:120px">Status</th>
                <th style="width:90px;text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($subscribers): foreach ($subscribers as $i => $sub): ?>
            <tr>
              <td style="color:var(--text-light);font-size:13px;"><?= $i + 1 ?></td>
              <td>
                <a href="mailto:<?= htmlspecialchars($sub['email']) ?>"
                   style="color:var(--teal);font-weight:500;text-decoration:none;">
                  <i class="fas fa-envelope" style="font-size:12px;margin-right:6px;opacity:.6"></i><?= htmlspecialchars($sub['email']) ?>
                </a>
              </td>
              <td>
                <div style="font-size:13px;font-weight:500"><?= date('d M Y', strtotime($sub['subscribed_at'])) ?></div>
                <div class="text-muted text-sm"><?= date('H:i', strtotime($sub['subscribed_at'])) ?></div>
              </td>
              <td>
                <?php if ($sub['is_active']): ?>
                  <span class="status-active"><i class="fas fa-circle" style="font-size:7px"></i> Active</span>
                <?php else: ?>
                  <span class="status-inactive"><i class="fas fa-circle" style="font-size:7px"></i> Unsubscribed</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <div style="display:flex;gap:6px;justify-content:center">
                  <a href="subscribers.php?toggle=<?= $sub['id'] ?>"
                     class="act-btn <?= $sub['is_active'] ? 'btn-tog-off' : 'btn-tog-on' ?>"
                     title="<?= $sub['is_active'] ? 'Deactivate' : 'Reactivate' ?>">
                    <i class="fas fa-<?= $sub['is_active'] ? 'ban' : 'check' ?>"></i>
                  </a>
                  <button onclick="confirmDelete(<?= $sub['id'] ?>, '<?= htmlspecialchars(addslashes($sub['email'])) ?>')"
                          class="act-btn btn-del" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5">
              <div class="empty-state">
                <i class="fas fa-paper-plane"></i>
                <h3>No subscribers yet</h3>
                <p>Subscribers from the blog newsletter form will appear here.</p>
              </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Remove Subscriber?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13.5px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Remove</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function confirmDelete(id, email) {
  document.getElementById('delModalText').textContent = 'Remove "' + email + '" from the newsletter list? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'subscribers.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
