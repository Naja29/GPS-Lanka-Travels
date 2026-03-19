<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'bookings';
$msg        = $_GET['msg'] ?? '';
$viewId     = isset($_GET['view'])   ? (int)$_GET['view']   : 0;
$deleteId   = isset($_GET['delete']) ? (int)$_GET['delete'] : 0;
$filter     = $_GET['filter'] ?? 'all';

/* ── SAFE MIGRATION ── */
$conn->query("CREATE TABLE IF NOT EXISTS bookings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tour_id     INT DEFAULT NULL,
    tour_title  VARCHAR(300) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    phone       VARCHAR(50)  DEFAULT NULL,
    tour_date   DATE         DEFAULT NULL,
    persons     VARCHAR(50)  DEFAULT NULL,
    message     TEXT         DEFAULT NULL,
    status      VARCHAR(20)  DEFAULT 'new',
    is_read     TINYINT(1)   DEFAULT 0,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── DELETE ── */
if ($deleteId) {
    $conn->query("DELETE FROM bookings WHERE id=$deleteId");
    header('Location: bookings.php?msg=deleted'); exit;
}

/* ── UPDATE STATUS (POST) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $id     = (int)$_POST['booking_id'];
    $status = in_array($_POST['status'] ?? '', ['new','confirmed','cancelled']) ? $_POST['status'] : 'new';
    $notes  = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $conn->query("UPDATE bookings SET status='$status', notes='$notes', is_read=1 WHERE id=$id");
    header("Location: bookings.php?view=$id&msg=updated"); exit;
}

/* ── MARK READ ── */
if ($viewId) {
    $conn->query("UPDATE bookings SET is_read=1 WHERE id=$viewId");
}

/* ── EXPORT CSV ── */
if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Ref','Tour','Name','Email','Phone','Date','Persons','Status','Received']);
    while ($r = $rows->fetch_assoc()) {
        fputcsv($out, [
            'BK-' . str_pad($r['id'], 5, '0', STR_PAD_LEFT),
            $r['tour_title'], $r['name'], $r['email'], $r['phone'],
            $r['tour_date'], $r['persons'], $r['status'], $r['created_at']
        ]);
    }
    fclose($out); exit;
}

/* ── FETCH SINGLE ── */
$booking = null;
if ($viewId) {
    $r = $conn->query("SELECT * FROM bookings WHERE id=$viewId");
    $booking = $r ? $r->fetch_assoc() : null;
    if (!$booking) { header('Location: bookings.php'); exit; }
}

/* ── FETCH LIST ── */
$whereSQL = $filter !== 'all' ? "WHERE status='" . $conn->real_escape_string($filter) . "'" : '';
$bookings = $conn->query("SELECT * FROM bookings $whereSQL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

/* ── COUNTS ── */
$counts = ['all'=>0,'new'=>0,'confirmed'=>0,'cancelled'=>0];
$rc = $conn->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status");
if ($rc) while ($row = $rc->fetch_assoc()) { $counts[$row['status']] = (int)$row['cnt']; $counts['all'] += (int)$row['cnt']; }
$unread = (int)($conn->query("SELECT COUNT(*) as c FROM bookings WHERE is_read=0")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Tour Bookings | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.filter-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.ftab{padding:7px 16px;border-radius:20px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:#fff;color:var(--text-mid);cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.ftab:hover{border-color:var(--teal);color:var(--teal)}
.ftab.active{background:var(--teal-dark);color:#fff;border-color:var(--teal-dark)}
.ftab.active-confirmed{background:var(--green);color:#fff;border-color:var(--green)}
.ftab.active-cancelled{background:var(--text-light);color:#fff;border-color:var(--text-light)}
.ftab-count{background:rgba(255,255,255,.25);padding:1px 7px;border-radius:10px;font-size:11px}
.ftab:not(.active) .ftab-count{background:var(--off-white);color:var(--text-light)}
.status-new{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--blue-pale);color:var(--blue);font-size:11.5px;font-weight:700}
.status-confirmed{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--green-pale);color:var(--green);font-size:11.5px;font-weight:700}
.status-cancelled{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--off-white);color:var(--text-light);font-size:11.5px;font-weight:600}
.bk-row-unread td{background:rgba(41,128,185,.03);font-weight:500}
.act-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-view{background:var(--teal-pale);color:var(--teal)}.btn-view:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.detail-layout{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start}
.detail-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.detail-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.detail-body{padding:24px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.info-item{background:var(--off-white);border-radius:10px;padding:14px 16px}
.info-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-light);margin-bottom:4px}
.info-value{font-size:14px;font-weight:600;color:var(--text-dark)}
.fgrp{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.8px;text-transform:uppercase}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-light)}
.empty-state i{font-size:52px;opacity:.2;display:block;margin-bottom:12px}
.bk-ref{font-size:11px;font-weight:700;letter-spacing:1px;color:var(--teal);background:var(--teal-pale);padding:2px 8px;border-radius:6px}
@media(max-width:900px){.detail-layout{grid-template-columns:1fr}.info-grid{grid-template-columns:1fr}}
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
        <div class="topbar-title">
          Tour Bookings
          <?php if ($unread > 0): ?>
            <span style="display:inline-flex;align-items:center;justify-content:center;background:var(--blue);color:#fff;font-size:11px;font-weight:700;width:20px;height:20px;border-radius:50%;margin-left:6px;vertical-align:middle"><?= $unread ?></span>
          <?php endif; ?>
        </div>
        <div class="topbar-breadcrumb"><?= $viewId ? 'Bookings / View' : 'Bookings' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($viewId): ?>
        <a href="bookings.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
      <?php else: ?>
        <a href="bookings.php?export=1" class="btn btn-outline btn-sm" style="border-color:#27ae60;color:#27ae60">
          <i class="fas fa-file-csv"></i> Export CSV
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Booking updated.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Booking deleted.</div><?php endif; ?>

      <?php if ($viewId && $booking): ?>
      <!-- ═══ DETAIL VIEW ═══ -->
      <?php $ref = 'BK-' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?>
      <div class="page-header">
        <div class="page-header-left">
          <h1>Booking <span class="bk-ref"><?= $ref ?></span></h1>
          <p>Received <?= timeAgo($booking['created_at']) ?> &mdash; <?= date('d M Y, H:i', strtotime($booking['created_at'])) ?></p>
        </div>
        <div class="page-header-actions">
          <button onclick="confirmDelete(<?= $booking['id'] ?>, '<?= htmlspecialchars(addslashes($booking['name'])) ?>')"
                  class="btn btn-sm" style="background:var(--red-pale);color:var(--red)">
            <i class="fas fa-trash"></i> Delete
          </button>
        </div>
      </div>

      <div class="detail-layout">
        <div>
          <div class="detail-card">
            <div class="detail-header">
              <div>
                <div style="font-size:18px;font-weight:700;color:var(--text-dark)"><?= htmlspecialchars($booking['name']) ?></div>
                <a href="mailto:<?= htmlspecialchars($booking['email']) ?>" style="font-size:13px;color:var(--teal)"><?= htmlspecialchars($booking['email']) ?></a>
                <?php if ($booking['phone']): ?>
                  <div style="font-size:13px;color:var(--text-mid);margin-top:2px">
                    <i class="fas fa-phone" style="font-size:11px"></i> <?= htmlspecialchars($booking['phone']) ?>
                  </div>
                <?php endif; ?>
              </div>
              <span class="status-<?= $booking['status'] ?>">
                <i class="fas fa-circle" style="font-size:7px"></i> <?= ucfirst($booking['status']) ?>
              </span>
            </div>
            <div class="detail-body">
              <div class="info-grid">
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-map-marked-alt"></i> Tour</div>
                  <div class="info-value"><?= htmlspecialchars($booking['tour_title']) ?></div>
                </div>
                <?php if ($booking['tour_date']): ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-calendar"></i> Tour Date</div>
                  <div class="info-value"><?= date('d M Y', strtotime($booking['tour_date'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($booking['persons']): ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-users"></i> Persons</div>
                  <div class="info-value"><?= htmlspecialchars($booking['persons']) ?></div>
                </div>
                <?php endif; ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-hashtag"></i> Reference</div>
                  <div class="info-value"><?= $ref ?></div>
                </div>
              </div>
              <?php if ($booking['message']): ?>
              <div style="margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-light)">Special Requests</div>
              <div style="background:var(--off-white);border-radius:10px;padding:14px 16px;font-size:13.5px;color:var(--text-mid);line-height:1.7;word-break:break-word"><?= htmlspecialchars($booking['message']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reply buttons -->
          <div style="margin-top:16px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px;">
            <div style="font-weight:600;font-size:13.5px;margin-bottom:14px;color:var(--text-dark)"><i class="fas fa-reply" style="color:var(--teal);margin-right:6px"></i>Reply to Customer</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <a href="mailto:<?= htmlspecialchars($booking['email']) ?>?subject=<?= urlencode('Your Booking Confirmation – ' . $ref . ' | ' . $booking['tour_title']) ?>"
                 class="btn btn-primary btn-sm"><i class="fas fa-envelope"></i> Reply via Email</a>
              <?php
                $waPhone = preg_replace('/\D/', '', $booking['phone'] ?? '');
                if ($waPhone && strlen($waPhone) >= 7):
                    if ($waPhone[0] === '0') $waPhone = '94' . substr($waPhone, 1);
                    $waConfirmText = urlencode('Hi ' . $booking['name'] . ', your booking for ' . $booking['tour_title'] . ' on ' . ($booking['tour_date'] ? date('d M Y', strtotime($booking['tour_date'])) : 'TBD') . ' (Ref: ' . $ref . ') is confirmed! We will be in touch shortly.');
              ?>
              <a href="https://wa.me/<?= $waPhone ?>?text=<?= $waConfirmText ?>"
                 target="_blank" class="btn btn-sm" style="background:#25D366;color:#fff">
                <i class="fab fa-whatsapp"></i> Confirm via WhatsApp
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT: status & notes -->
        <div style="display:flex;flex-direction:column;gap:18px">
          <div class="detail-card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--text-dark)">
              <i class="fas fa-sliders-h" style="color:var(--teal);margin-right:6px"></i> Update Status
            </div>
            <div style="padding:20px">
              <form method="POST">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>"/>
                <div class="fgrp">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <?php foreach (['new'=>'New','confirmed'=>'Confirmed','cancelled'=>'Cancelled'] as $v => $l): ?>
                      <option value="<?= $v ?>" <?= $booking['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fgrp">
                  <label>Internal Notes</label>
                  <textarea name="notes" class="form-control" rows="4"
                            placeholder="Private notes about this booking…"><?= htmlspecialchars($booking['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Save</button>
              </form>
            </div>
          </div>

          <div class="detail-card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--text-dark)">
              <i class="fas fa-info-circle" style="color:var(--teal);margin-right:6px"></i> Booking Info
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;font-size:13px">
              <div style="display:flex;justify-content:space-between"><span style="color:var(--text-light)">Reference</span><strong><?= $ref ?></strong></div>
              <div style="display:flex;justify-content:space-between;gap:12px"><span style="color:var(--text-light);flex-shrink:0">Received</span><span style="font-weight:600;text-align:right"><?= date('d M Y', strtotime($booking['created_at'])) ?><br><span style="font-size:11px;font-weight:400;color:var(--text-light)"><?= date('H:i', strtotime($booking['created_at'])) ?> (<?= timeAgo($booking['created_at']) ?>)</span></span></div>
            </div>
          </div>
        </div>

      </div>

      <?php else: ?>
      <!-- ═══ LIST VIEW ═══ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Tour Bookings</h1>
          <p><?= $counts['all'] ?> total &mdash; <?= $unread ?> unread</p>
        </div>
      </div>

      <div class="filter-tabs">
        <?php $tabMap = ['all'=>['All','active'],'new'=>['New','active'],'confirmed'=>['Confirmed','active-confirmed'],'cancelled'=>['Cancelled','active-cancelled']]; ?>
        <?php foreach ($tabMap as $key => [$label,$cls]): ?>
          <a href="bookings.php?filter=<?= $key ?>" class="ftab <?= $filter === $key ? $cls : '' ?>">
            <?= $label ?> <span class="ftab-count"><?= $counts[$key] ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:36px"></th>
                <th>Reference</th>
                <th>Customer</th>
                <th>Tour</th>
                <th>Tour Date</th>
                <th>Persons</th>
                <th>Received</th>
                <th style="width:110px">Status</th>
                <th style="width:90px;text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($bookings): foreach ($bookings as $bk): ?>
            <tr class="<?= !$bk['is_read'] ? 'bk-row-unread' : '' ?>">
              <td style="text-align:center">
                <?php if (!$bk['is_read']): ?><span style="width:8px;height:8px;border-radius:50%;background:var(--blue);display:inline-block" title="New"></span><?php endif; ?>
              </td>
              <td><span class="bk-ref">BK-<?= str_pad($bk['id'],5,'0',STR_PAD_LEFT) ?></span></td>
              <td>
                <div class="col-title"><?= htmlspecialchars($bk['name']) ?></div>
                <div class="text-muted text-sm"><?= htmlspecialchars($bk['email']) ?></div>
              </td>
              <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($bk['tour_title']) ?></td>
              <td><?= $bk['tour_date'] ? date('d M Y', strtotime($bk['tour_date'])) : '—' ?></td>
              <td><?= htmlspecialchars($bk['persons'] ?: '—') ?></td>
              <td>
                <div style="font-size:13px"><?= timeAgo($bk['created_at']) ?></div>
                <div class="text-muted text-sm"><?= date('d M Y', strtotime($bk['created_at'])) ?></div>
              </td>
              <td><span class="status-<?= $bk['status'] ?>"><i class="fas fa-circle" style="font-size:7px"></i> <?= ucfirst($bk['status']) ?></span></td>
              <td style="text-align:center">
                <div style="display:flex;gap:6px;justify-content:center">
                  <a href="bookings.php?view=<?= $bk['id'] ?>" class="act-btn btn-view" title="View"><i class="fas fa-eye"></i></a>
                  <button onclick="confirmDelete(<?= $bk['id'] ?>, '<?= htmlspecialchars(addslashes($bk['name'])) ?>')" class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="9">
              <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <h3>No bookings yet</h3>
                <p>Tour booking requests will appear here.</p>
              </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Booking?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13.5px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function confirmDelete(id, name) {
  document.getElementById('delModalText').textContent = 'Delete booking from "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'bookings.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
