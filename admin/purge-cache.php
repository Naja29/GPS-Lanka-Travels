<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'purge-cache';
$results    = [];

/* ── PURGE ACTION ── */
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'opcache' || $action === 'all') {
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $results[] = ['ok'=>true,  'label'=>'PHP OPcache', 'msg'=>'OPcache cleared successfully.'];
        } else {
            $results[] = ['ok'=>false, 'label'=>'PHP OPcache', 'msg'=>'OPcache is not enabled on this server.'];
        }
    }

    if ($action === 'thumbs' || $action === 'all') {
        $thumbDir = __DIR__ . '/../uploads/thumbs/';
        $count    = 0;
        if (is_dir($thumbDir)) {
            foreach (glob($thumbDir . '*') as $file) {
                if (is_file($file)) { unlink($file); $count++; }
            }
        }
        $results[] = ['ok'=>true, 'label'=>'Thumbnail Cache', 'msg'=>"$count cached thumbnail(s) removed."];
    }

    if ($action === 'sessions' || $action === 'all') {
        // Clear expired PHP session files (not the current one)
        $sessionPath = session_save_path() ?: sys_get_temp_dir();
        $count       = 0;
        $maxAge      = 7200; // 2 hours
        if (is_dir($sessionPath)) {
            foreach (glob($sessionPath . '/sess_*') as $file) {
                if (is_file($file) && (time() - filemtime($file)) > $maxAge && basename($file) !== 'sess_' . session_id()) {
                    unlink($file);
                    $count++;
                }
            }
        }
        $results[] = ['ok'=>true, 'label'=>'Expired Sessions', 'msg'=>"$count expired session file(s) removed."];
    }

    if ($action === 'logs' || $action === 'all') {
        $logDir = __DIR__ . '/../logs/';
        $count  = 0;
        if (is_dir($logDir)) {
            foreach (glob($logDir . '*.log') as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 86400) {
                    unlink($file);
                    $count++;
                }
            }
        }
        $results[] = ['ok'=>true, 'label'=>'Old Log Files', 'msg'=>"$count old log file(s) removed."];
    }
}

/* ── STATS ── */
$opcacheStatus = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
$uploadsSize   = 0;
$uploadsCount  = 0;
$uploadsDir    = __DIR__ . '/../uploads/';
if (is_dir($uploadsDir)) {
    $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS));
    foreach ($rit as $file) {
        if ($file->isFile()) { $uploadsSize += $file->getSize(); $uploadsCount++; }
    }
}
function formatBytes($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1)    . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Purge Cache | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.cache-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:28px}
.cache-card{background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:22px;display:flex;flex-direction:column;gap:12px;transition:border-color .2s}
.cache-card:hover{border-color:var(--teal)}
.cache-card-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.cache-card-title{font-size:14px;font-weight:700;color:var(--text-dark)}
.cache-card-desc{font-size:12px;color:var(--text-light);line-height:1.5}
.cache-card-stat{font-size:12px;color:var(--text-mid);background:var(--off-white);border-radius:8px;padding:7px 12px;font-family:monospace}
.result-item{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:10px;margin-bottom:8px}
.result-item.ok{background:#f0fdf4;border:1px solid #bbf7d0}
.result-item.fail{background:#fff5f5;border:1px solid #fed7d7}
.result-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.result-icon.ok{background:#dcfce7;color:#16a34a}
.result-icon.fail{background:#fee2e2;color:#dc2626}
.purge-all-btn{width:100%;padding:14px;font-size:15px;font-weight:600;background:linear-gradient(135deg,var(--teal-dark),var(--teal));color:#fff;border:none;border-radius:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:transform .2s,box-shadow .2s}
.purge-all-btn:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(15,82,82,.25)}
.stat-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.stat-row:last-child{border-bottom:none}
.stat-label{color:var(--text-mid)}
.stat-value{font-weight:600;color:var(--text-dark);font-family:monospace}
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
        <div class="topbar-title">Purge Cache</div>
        <div class="topbar-breadcrumb">Admin / Purge Cache</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <div class="page-header">
        <div class="page-header-left">
          <h1>Purge Cache</h1>
          <p>Clear server-side caches to ensure visitors see the latest content</p>
        </div>
      </div>

      <!-- RESULTS -->
      <?php if ($results): ?>
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:24px">
        <div style="font-size:14px;font-weight:700;color:var(--text-dark);margin-bottom:12px"><i class="fas fa-check-circle" style="color:var(--teal)"></i> Purge Results</div>
        <?php foreach ($results as $r): ?>
          <div class="result-item <?= $r['ok'] ? 'ok' : 'fail' ?>">
            <div class="result-icon <?= $r['ok'] ? 'ok' : 'fail' ?>">
              <i class="fas fa-<?= $r['ok'] ? 'check' : 'times' ?>"></i>
            </div>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--text-dark)"><?= htmlspecialchars($r['label']) ?></div>
              <div style="font-size:12px;color:var(--text-light);margin-top:2px"><?= htmlspecialchars($r['msg']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">

        <!-- LEFT: actions -->
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--text-mid);text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px">Individual Actions</div>
          <div class="cache-grid">

            <!-- OPcache -->
            <div class="cache-card">
              <div style="display:flex;align-items:center;gap:12px">
                <div class="cache-card-icon" style="background:var(--teal-pale);color:var(--teal)"><i class="fas fa-microchip"></i></div>
                <div>
                  <div class="cache-card-title">PHP OPcache</div>
                  <?php if ($opcacheStatus): ?>
                    <div style="font-size:11px;color:#27ae60"><i class="fas fa-circle" style="font-size:8px"></i> Enabled</div>
                  <?php else: ?>
                    <div style="font-size:11px;color:#aaa"><i class="fas fa-circle" style="font-size:8px"></i> Not enabled</div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="cache-card-desc">Clears PHP's compiled script cache. Forces PHP to recompile all scripts on next request.</div>
              <?php if ($opcacheStatus && isset($opcacheStatus['memory_usage'])): ?>
                <div class="cache-card-stat">
                  Memory: <?= formatBytes($opcacheStatus['memory_usage']['used_memory']) ?> used
                </div>
              <?php endif; ?>
              <form method="POST">
                <input type="hidden" name="action" value="opcache"/>
                <button type="submit" class="btn btn-outline" style="width:100%"><i class="fas fa-bolt"></i> Clear OPcache</button>
              </form>
            </div>

            <!-- Thumbnails -->
            <div class="cache-card">
              <div style="display:flex;align-items:center;gap:12px">
                <div class="cache-card-icon" style="background:#fff3e0;color:#e07b39"><i class="fas fa-images"></i></div>
                <div>
                  <div class="cache-card-title">Thumbnail Cache</div>
                  <div style="font-size:11px;color:var(--text-light)">uploads/thumbs/</div>
                </div>
              </div>
              <div class="cache-card-desc">Removes auto-generated thumbnail images. They will be regenerated as needed.</div>
              <?php
              $thumbCount = 0;
              if (is_dir(__DIR__.'/../uploads/thumbs/')) $thumbCount = count(glob(__DIR__.'/../uploads/thumbs/*'));
              ?>
              <div class="cache-card-stat"><?= $thumbCount ?> cached thumbnail(s)</div>
              <form method="POST">
                <input type="hidden" name="action" value="thumbs"/>
                <button type="submit" class="btn btn-outline" style="width:100%"><i class="fas fa-trash-alt"></i> Clear Thumbnails</button>
              </form>
            </div>

            <!-- Sessions -->
            <div class="cache-card">
              <div style="display:flex;align-items:center;gap:12px">
                <div class="cache-card-icon" style="background:#f3e8ff;color:#9333ea"><i class="fas fa-user-clock"></i></div>
                <div>
                  <div class="cache-card-title">Expired Sessions</div>
                  <div style="font-size:11px;color:var(--text-light)">Older than 2 hours</div>
                </div>
              </div>
              <div class="cache-card-desc">Removes expired PHP session files. Your current session will not be affected.</div>
              <form method="POST">
                <input type="hidden" name="action" value="sessions"/>
                <button type="submit" class="btn btn-outline" style="width:100%"><i class="fas fa-clock"></i> Clear Sessions</button>
              </form>
            </div>

            <!-- Logs -->
            <div class="cache-card">
              <div style="display:flex;align-items:center;gap:12px">
                <div class="cache-card-icon" style="background:#fff0f0;color:var(--red)"><i class="fas fa-file-alt"></i></div>
                <div>
                  <div class="cache-card-title">Old Log Files</div>
                  <div style="font-size:11px;color:var(--text-light)">logs/ older than 24h</div>
                </div>
              </div>
              <div class="cache-card-desc">Removes log files older than 24 hours from the logs/ directory.</div>
              <form method="POST">
                <input type="hidden" name="action" value="logs"/>
                <button type="submit" class="btn btn-outline" style="width:100%"><i class="fas fa-file-times"></i> Clear Logs</button>
              </form>
            </div>

          </div>

          <!-- PURGE ALL -->
          <form method="POST">
            <input type="hidden" name="action" value="all"/>
            <button type="submit" class="purge-all-btn">
              <i class="fas fa-fire-alt"></i> Purge Everything
            </button>
          </form>
        </div>

        <!-- RIGHT: server info -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
          <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--text-dark)">
            <i class="fas fa-server" style="color:var(--teal);margin-right:6px"></i> Server Info
          </div>
          <div style="padding:16px 18px">
            <div class="stat-row">
              <span class="stat-label">PHP Version</span>
              <span class="stat-value"><?= PHP_VERSION ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-label">OPcache</span>
              <span class="stat-value" style="color:<?= $opcacheStatus ? '#27ae60' : '#aaa' ?>"><?= $opcacheStatus ? 'On' : 'Off' ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Uploads Folder</span>
              <span class="stat-value"><?= $uploadsCount ?> files</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Uploads Size</span>
              <span class="stat-value"><?= formatBytes($uploadsSize) ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Memory Limit</span>
              <span class="stat-value"><?= ini_get('memory_limit') ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Max Upload</span>
              <span class="stat-value"><?= ini_get('upload_max_filesize') ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Server Time</span>
              <span class="stat-value"><?= (new DateTime('now', new DateTimeZone('Asia/Colombo')))->format('H:i') ?> LK</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>

<script src="js/admin.js"></script>
</body>
</html>
