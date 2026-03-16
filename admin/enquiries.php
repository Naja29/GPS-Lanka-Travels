<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'enquiries';
$msg        = $_GET['msg'] ?? '';
$viewId     = isset($_GET['view'])   ? (int)$_GET['view']   : 0;
$deleteId   = isset($_GET['delete']) ? (int)$_GET['delete'] : 0;
$filter     = $_GET['filter'] ?? 'all';
$search     = trim($_GET['q'] ?? '');

/* DELETE  */
if ($deleteId) {
    $conn->query("DELETE FROM enquiries WHERE id=$deleteId");
    header('Location: enquiries.php?msg=deleted'); exit;
}

/* UPDATE STATUS / NOTES (POST)  */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enq_id'])) {
    $id     = (int)$_POST['enq_id'];
    $status = in_array($_POST['status'] ?? '', ['new','read','replied','closed']) ? $_POST['status'] : 'read';
    $notes  = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $conn->query("UPDATE enquiries SET status='$status', notes='$notes', is_read=1 WHERE id=$id");
    header("Location: enquiries.php?view=$id&msg=updated"); exit;
}

/* MARK READ when viewing  */
if ($viewId) {
    $conn->query("UPDATE enquiries SET is_read=1, status=IF(status='new','read',status) WHERE id=$viewId");
}

/* EXPORT CSV  */
if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT * FROM enquiries ORDER BY created_at DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Email','Phone','Tour Type','Travel Date','Persons','Budget','Message','Status','Received']);
    while ($r = $rows->fetch_assoc()) {
        fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['phone'],$r['tour_type'],$r['travel_date'],$r['persons'],$r['budget'],$r['message'],$r['status'],$r['created_at']]);
    }
    fclose($out); exit;
}

/* FETCH SINGLE ENQUIRY  */
$enquiry = null;
if ($viewId) {
    $r       = $conn->query("SELECT * FROM enquiries WHERE id=$viewId");
    $enquiry = $r ? $r->fetch_assoc() : null;
    if (!$enquiry) { header('Location: enquiries.php'); exit; }
}

/* FETCH LIST  */
$where = [];
if ($filter !== 'all') $where[] = "status='" . $conn->real_escape_string($filter) . "'";
if ($search)           $where[] = "(name LIKE '%" . $conn->real_escape_string($search) . "%' OR email LIKE '%" . $conn->real_escape_string($search) . "%' OR tour_type LIKE '%" . $conn->real_escape_string($search) . "%')";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$enquiries = [];
$r = $conn->query("SELECT * FROM enquiries $whereSQL ORDER BY created_at DESC");
if ($r) while ($row = $r->fetch_assoc()) $enquiries[] = $row;

/* COUNTS  */
$counts = ['all' => 0, 'new' => 0, 'read' => 0, 'replied' => 0, 'closed' => 0];
$rc = $conn->query("SELECT status, COUNT(*) as cnt FROM enquiries GROUP BY status");
if ($rc) while ($row = $rc->fetch_assoc()) {
    $counts[$row['status']] = (int)$row['cnt'];
    $counts['all'] += (int)$row['cnt'];
}
$unread = (int)($conn->query("SELECT COUNT(*) as c FROM enquiries WHERE is_read=0")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Enquiries | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
/* FILTER TABS */
.filter-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.ftab{padding:7px 16px;border-radius:20px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:#fff;color:var(--text-mid);cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.ftab:hover{border-color:var(--teal);color:var(--teal)}
.ftab.active{background:var(--teal-dark);color:#fff;border-color:var(--teal-dark)}
.ftab.active-new{background:var(--blue);color:#fff;border-color:var(--blue)}
.ftab.active-replied{background:var(--green);color:#fff;border-color:var(--green)}
.ftab.active-closed{background:var(--text-light);color:#fff;border-color:var(--text-light)}
.ftab-count{background:rgba(255,255,255,.25);padding:1px 7px;border-radius:10px;font-size:11px}
.ftab:not(.active) .ftab-count{background:var(--off-white);color:var(--text-light)}

/* STATUS BADGES */
.status-new{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--blue-pale);color:var(--blue);font-size:11.5px;font-weight:700}
.status-read{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--off-white);color:var(--text-light);font-size:11.5px;font-weight:600}
.status-replied{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--green-pale);color:var(--green);font-size:11.5px;font-weight:700}
.status-closed{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--orange-pale);color:var(--orange);font-size:11.5px;font-weight:700}

/* TABLE */
.enq-row-unread td{background:rgba(41,128,185,.03);font-weight:500}
.enq-row-unread .col-title{color:var(--teal-dark) !important}
.unread-dot{width:8px;height:8px;border-radius:50%;background:var(--blue);flex-shrink:0;display:inline-block}
.act-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-view{background:var(--teal-pale);color:var(--teal)}.btn-view:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}

/* DETAIL VIEW */
.detail-layout{display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start}
.detail-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.detail-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.detail-meta{display:flex;flex-direction:column;gap:3px}
.detail-name{font-size:18px;font-weight:700;color:var(--text-dark)}
.detail-email{font-size:13px;color:var(--teal)}
.detail-body{padding:24px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px}
.info-item{background:var(--off-white);border-radius:10px;padding:14px 16px}
.info-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-light);margin-bottom:4px}
.info-value{font-size:14px;font-weight:600;color:var(--text-dark)}
.message-box{background:var(--off-white);border-radius:10px;padding:16px 18px;line-height:1.7;font-size:13.5px;color:var(--text-mid);white-space:pre-line}
.nav-enqs{display:flex;gap:8px}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
select.form-control{cursor:pointer}
textarea.form-control{resize:vertical;min-height:100px}
.fgrp{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.8px;text-transform:uppercase}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-light)}
.empty-state i{font-size:52px;opacity:.2;display:block;margin-bottom:12px}
@media(max-width:900px){.detail-layout{grid-template-columns:1fr}}
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
          Enquiries &amp; Bookings
          <?php if ($unread > 0): ?>
            <span style="display:inline-flex;align-items:center;justify-content:center;background:var(--blue);color:#fff;font-size:11px;font-weight:700;width:20px;height:20px;border-radius:50%;margin-left:6px;vertical-align:middle"><?= $unread ?></span>
          <?php endif; ?>
        </div>
        <div class="topbar-breadcrumb"><?= $viewId ? 'Enquiries / View' : 'Enquiries' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($viewId): ?>
        <a href="enquiries.php?filter=<?= $filter ?>" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
      <?php else: ?>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"/>
          <div style="position:relative">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:13px;pointer-events:none"></i>
            <input type="text" name="q" placeholder="Search name, email…"
                   value="<?= htmlspecialchars($search) ?>"
                   style="padding:7px 12px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;width:200px;font-family:inherit;color:var(--text-dark)"
                   onfocus="this.style.borderColor='var(--teal)'" onblur="this.style.borderColor='var(--border)'"/>
          </div>
          <?php if ($search): ?>
            <a href="enquiries.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="enquiries.php?export=1" class="btn btn-outline btn-sm" style="border-color:#27ae60;color:#27ae60" title="Export to CSV">
          <i class="fas fa-file-csv"></i> Export CSV
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Enquiry updated.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Enquiry deleted.</div><?php endif; ?>

      <?php if ($viewId && $enquiry): ?>
      <!-- ============ DETAIL VIEW ============ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Enquiry #<?= $enquiry['id'] ?></h1>
          <p>Received <?= timeAgo($enquiry['created_at']) ?> &mdash; <?= date('d M Y, H:i', strtotime($enquiry['created_at'])) ?></p>
        </div>
        <div class="page-header-actions">
          <?php
            $ids  = array_column($enquiries ?: [], 'id');
            // Fetch prev/next from full unfiltered list for navigation
            $allIds = [];
            $rNav = $conn->query("SELECT id FROM enquiries ORDER BY created_at DESC");
            while ($n = $rNav->fetch_assoc()) $allIds[] = $n['id'];
            $pos  = array_search($viewId, $allIds);
            $prev = $pos > 0 ? $allIds[$pos - 1] : null;
            $next = ($pos !== false && $pos < count($allIds) - 1) ? $allIds[$pos + 1] : null;
          ?>
          <div class="nav-enqs">
            <?php if ($prev): ?><a href="enquiries.php?view=<?= $prev ?>" class="btn btn-outline btn-sm"><i class="fas fa-chevron-left"></i> Prev</a><?php endif; ?>
            <?php if ($next): ?><a href="enquiries.php?view=<?= $next ?>" class="btn btn-outline btn-sm">Next <i class="fas fa-chevron-right"></i></a><?php endif; ?>
          </div>
          <button onclick="confirmDelete(<?= $enquiry['id'] ?>, '<?= htmlspecialchars(addslashes($enquiry['name'])) ?>')" class="btn btn-sm" style="background:var(--red-pale);color:var(--red)"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>

      <div class="detail-layout">

        <!-- LEFT: enquiry details -->
        <div>
          <div class="detail-card">
            <div class="detail-header">
              <div class="detail-meta">
                <div class="detail-name"><?= htmlspecialchars($enquiry['name']) ?></div>
                <a href="mailto:<?= htmlspecialchars($enquiry['email']) ?>" class="detail-email"><?= htmlspecialchars($enquiry['email']) ?></a>
                <?php if ($enquiry['phone']): ?>
                  <a href="tel:<?= htmlspecialchars($enquiry['phone']) ?>" style="font-size:13px;color:var(--text-mid);margin-top:2px"><i class="fas fa-phone" style="font-size:11px"></i> <?= htmlspecialchars($enquiry['phone']) ?></a>
                <?php endif; ?>
              </div>
              <div>
                <?php $st = $enquiry['status']; ?>
                <span class="status-<?= $st ?>">
                  <i class="fas fa-circle" style="font-size:7px"></i>
                  <?= ucfirst($st) ?>
                </span>
              </div>
            </div>
            <div class="detail-body">
              <div class="info-grid">
                <?php if ($enquiry['tour_type']): ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-map-marked-alt"></i> Tour Type</div>
                  <div class="info-value"><?= htmlspecialchars($enquiry['tour_type']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($enquiry['travel_date']): ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-calendar"></i> Travel Date</div>
                  <div class="info-value"><?= date('d M Y', strtotime($enquiry['travel_date'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($enquiry['persons']): ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-users"></i> No. of Persons</div>
                  <div class="info-value"><?= (int)$enquiry['persons'] ?></div>
                </div>
                <?php endif; ?>
                <?php if ($enquiry['budget']): ?>
                <div class="info-item">
                  <div class="info-label"><i class="fas fa-dollar-sign"></i> Budget</div>
                  <div class="info-value"><?= htmlspecialchars($enquiry['budget']) ?></div>
                </div>
                <?php endif; ?>
              </div>

              <?php if ($enquiry['message']): ?>
              <div style="margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-light)">Message</div>
              <div class="message-box"><?= htmlspecialchars($enquiry['message']) ?></div>
              <?php endif; ?>

              <?php if ($enquiry['ip_address']): ?>
              <div style="margin-top:14px;font-size:12px;color:var(--text-light)"><i class="fas fa-network-wired" style="margin-right:4px"></i> IP: <?= htmlspecialchars($enquiry['ip_address']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Reply hint -->
          <div style="margin-top:16px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px;display:flex;align-items:center;gap:14px">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fas fa-envelope" style="color:var(--teal);font-size:16px"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:13.5px;margin-bottom:2px">Reply via Email</div>
              <a href="mailto:<?= htmlspecialchars($enquiry['email']) ?>?subject=Re: <?= urlencode('Your enquiry - ' . ($enquiry['tour_type'] ?: 'GPS Lanka Travels')) ?>"
                 class="btn btn-primary btn-sm" style="margin-top:4px">
                <i class="fas fa-reply"></i> Reply to <?= htmlspecialchars($enquiry['name']) ?>
              </a>
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
                <input type="hidden" name="enq_id" value="<?= $enquiry['id'] ?>"/>
                <div class="fgrp">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <?php foreach (['new','read','replied','closed'] as $s): ?>
                      <option value="<?= $s ?>" <?= $enquiry['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fgrp">
                  <label>Internal Notes</label>
                  <textarea name="notes" class="form-control" rows="4" placeholder="Add private notes about this enquiry…"><?= htmlspecialchars($enquiry['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Save</button>
              </form>
            </div>
          </div>

          <div class="detail-card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--text-dark)">
              <i class="fas fa-info-circle" style="color:var(--teal);margin-right:6px"></i> Enquiry Info
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;font-size:13px">
              <div style="display:flex;justify-content:space-between"><span style="color:var(--text-light)">ID</span><span style="font-weight:600">#<?= $enquiry['id'] ?></span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--text-light)">Received</span><span style="font-weight:600"><?= timeAgo($enquiry['created_at']) ?></span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--text-light)">Date</span><span style="font-weight:600"><?= date('d M Y', strtotime($enquiry['created_at'])) ?></span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--text-light)">Read</span><span><?= $enquiry['is_read'] ? '<span style="color:var(--green)"><i class="fas fa-check"></i> Yes</span>' : '<span style="color:var(--text-light)">No</span>' ?></span></div>
            </div>
          </div>
        </div>

      </div>

      <?php else: ?>
      <!-- ============ LIST VIEW ============ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Enquiries &amp; Bookings</h1>
          <p><?= $counts['all'] ?> total &mdash; <?= $unread ?> unread</p>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="filter-tabs">
        <?php
          $tabs = ['all'=>'All','new'=>'New','read'=>'Read','replied'=>'Replied','closed'=>'Closed'];
          $activeClass = ['all'=>'active','new'=>'active-new','read'=>'active','replied'=>'active-replied','closed'=>'active-closed'];
        ?>
        <?php foreach ($tabs as $key => $label): ?>
          <a href="enquiries.php?filter=<?= $key ?><?= $search ? '&q='.urlencode($search) : '' ?>"
             class="ftab <?= $filter === $key ? $activeClass[$key] : '' ?>">
            <?= $label ?>
            <span class="ftab-count"><?= $counts[$key] ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:36px"></th>
                <th>Name</th>
                <th>Tour Interest</th>
                <th>Travel Date</th>
                <th style="width:70px;text-align:center">Persons</th>
                <th>Received</th>
                <th style="width:100px">Status</th>
                <th style="width:90px;text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($enquiries): foreach ($enquiries as $e): ?>
            <tr class="<?= !$e['is_read'] ? 'enq-row-unread' : '' ?>">
              <td style="text-align:center">
                <?php if (!$e['is_read']): ?>
                  <span class="unread-dot" title="Unread"></span>
                <?php endif; ?>
              </td>
              <td>
                <div class="col-title"><?= htmlspecialchars($e['name']) ?></div>
                <div class="text-muted text-sm"><?= htmlspecialchars($e['email']) ?></div>
              </td>
              <td><?= htmlspecialchars($e['tour_type'] ?: '—') ?></td>
              <td><?= $e['travel_date'] ? date('d M Y', strtotime($e['travel_date'])) : '—' ?></td>
              <td style="text-align:center"><?= (int)$e['persons'] ?: '—' ?></td>
              <td>
                <div style="font-size:13px"><?= timeAgo($e['created_at']) ?></div>
                <div class="text-muted text-sm"><?= date('d M Y', strtotime($e['created_at'])) ?></div>
              </td>
              <td><span class="status-<?= $e['status'] ?>"><i class="fas fa-circle" style="font-size:7px"></i> <?= ucfirst($e['status']) ?></span></td>
              <td style="text-align:center">
                <div style="display:flex;gap:6px;justify-content:center">
                  <a href="enquiries.php?view=<?= $e['id'] ?>" class="act-btn btn-view" title="View"><i class="fas fa-eye"></i></a>
                  <button onclick="confirmDelete(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['name'])) ?>')" class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8">
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No enquiries found<?= $filter !== 'all' ? ' for filter "'.htmlspecialchars($filter).'"' : '' ?>.</p>
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

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Enquiry?</h3>
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
  document.getElementById('delModalText').textContent = 'Delete enquiry from "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'enquiries.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
