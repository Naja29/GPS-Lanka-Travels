<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'destinations';
$action     = $_GET['action'] ?? 'list';
$search     = trim($_GET['q'] ?? '');
$msg        = $_GET['msg'] ?? '';
$errors     = [];

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

/* ── SAFE MIGRATION ── */
$conn->query("ALTER TABLE destinations ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL");

/* ── FETCH DESTINATION CATEGORIES ── */
$destCats = [];
$r = $conn->query("SELECT * FROM destination_categories ORDER BY sort_order ASC, name ASC");
if ($r) $destCats = $r->fetch_all(MYSQLI_ASSOC);

// Build lookup maps
$destCatById   = [];
foreach ($destCats as $dc) { $destCatById[(int)$dc['id']] = $dc; }

/* ── ACTIONS ── */
if ($action === 'delete' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $row = $conn->query("SELECT hero_image FROM destinations WHERE id=$id")->fetch_assoc();
    if ($row && $row['hero_image'] && strpos($row['hero_image'], 'uploads/') === 0) {
        $imgPath = __DIR__ . '/../' . $row['hero_image'];
        if (file_exists($imgPath)) unlink($imgPath);
    }
    $conn->query("DELETE FROM destinations WHERE id=$id");
    header('Location: destinations.php?msg=deleted'); exit;
}

if ($action === 'toggle_featured' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE destinations SET is_featured = 1 - is_featured WHERE id=$id");
    header('Location: destinations.php'); exit;
}

if ($action === 'toggle_active' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE destinations SET is_active = 1 - is_active WHERE id=$id");
    header('Location: destinations.php'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destId     = (int)($_POST['dest_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $location   = trim($_POST['location'] ?? '');
    $region     = trim($_POST['region'] ?? '');
    $read_time  = max(1, (int)($_POST['read_time'] ?? 5));
    $excerpt    = trim($_POST['excerpt'] ?? '');
    $content    = $_POST['content'] ?? '';
    $is_feat    = isset($_POST['is_featured']) ? 1 : 0;
    $is_active  = isset($_POST['is_active'])   ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Quick facts JSON from rows
    $qfIcons  = $_POST['qf_icon']  ?? [];
    $qfLabels = $_POST['qf_label'] ?? [];
    $qfValues = $_POST['qf_value'] ?? [];
    $qfRows   = [];
    foreach ($qfLabels as $i => $lbl) {
        $lbl = trim($lbl);
        $val = trim($qfValues[$i] ?? '');
        if ($lbl && $val) {
            $qfRows[] = ['icon' => trim($qfIcons[$i] ?? 'fas fa-info-circle'), 'label' => $lbl, 'value' => $val];
        }
    }
    $quickFactsJson = json_encode($qfRows, JSON_UNESCAPED_UNICODE);

    // Best months — 12 selects
    $bmArr = [];
    for ($i = 0; $i < 12; $i++) {
        $bmArr[] = $_POST['bm_' . $i] ?? 'avoid';
    }
    $bestMonthsJson = json_encode($bmArr);

    if (!$title) $errors[] = 'Title is required.';

    if (!$errors) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $sc   = $conn->prepare("SELECT id FROM destinations WHERE slug=? AND id!=?");
        $sc->bind_param('si', $slug, $destId);
        $sc->execute();
        if ($sc->get_result()->num_rows) $slug .= '-' . time();
        $sc->close();

        $imagePath = '';
        if (!empty($_FILES['hero_image']['name'])) {
            $up = uploadImage($_FILES['hero_image'], 'uploads/destinations');
            if (!$up['ok']) $errors[] = $up['msg'];
            else $imagePath = $up['path'];
        }
    }

    if (!$errors) {
        $titleE   = $conn->real_escape_string($title);
        $slugE    = $conn->real_escape_string($slug);
        $locE     = $conn->real_escape_string($location);
        $regE     = $conn->real_escape_string($region);
        $excE     = $conn->real_escape_string($excerpt);
        $contE    = $conn->real_escape_string($content);
        $qfE      = $conn->real_escape_string($quickFactsJson);
        $bmE      = $conn->real_escape_string($bestMonthsJson);
        $catIdVal = $categoryId ?: 'NULL';

        if ($destId) {
            $imgClause = $imagePath ? ", hero_image='" . $conn->real_escape_string($imagePath) . "'" : '';
            $conn->query("UPDATE destinations SET
                title='$titleE', slug='$slugE', category_id=$catIdVal, location='$locE',
                region='$regE', read_time=$read_time, excerpt='$excE', content='$contE',
                quick_facts='$qfE', best_months='$bmE',
                is_featured=$is_feat, is_active=$is_active, sort_order=$sort_order
                $imgClause
                WHERE id=$destId");
            header('Location: destinations.php?msg=updated'); exit;
        } else {
            $imgVal = $imagePath ? "'" . $conn->real_escape_string($imagePath) . "'" : "''";
            $conn->query("INSERT INTO destinations
                (title, slug, category_id, location, region, hero_image, read_time,
                 excerpt, content, quick_facts, best_months, is_featured, is_active, sort_order)
                VALUES
                ('$titleE','$slugE',$catIdVal,'$locE','$regE',$imgVal,$read_time,
                 '$excE','$contE','$qfE','$bmE',$is_feat,$is_active,$sort_order)");
            header('Location: destinations.php?msg=added'); exit;
        }
    }

    $action = $destId ? 'edit' : 'add';
    if ($destId) $_GET['id'] = $destId;
}

/* ── FETCH FOR EDIT ── */
$editDest = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $id       = (int)$_GET['id'];
    $r        = $conn->query("SELECT * FROM destinations WHERE id=$id");
    $editDest = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH LIST ── */
$dests = [];
if ($action === 'list') {
    $where = '';
    if ($search) {
        $sq    = $conn->real_escape_string($search);
        $where = "WHERE title LIKE '%$sq%' OR location LIKE '%$sq%'";
    }
    $r = $conn->query("SELECT d.*, dc.name AS cat_name, dc.icon AS cat_icon
                        FROM destinations d
                        LEFT JOIN destination_categories dc ON dc.id = d.category_id
                        $where ORDER BY d.sort_order, d.created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $dests[] = $row;
}

$v          = $editDest ?? [];
$vQF        = !empty($v['quick_facts']) ? (json_decode($v['quick_facts'], true) ?: []) : [];
$vBM        = !empty($v['best_months']) ? (json_decode($v['best_months'], true) ?: array_fill(0, 12, 'avoid')) : array_fill(0, 12, 'avoid');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Destinations | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js"></script>
<style>
.dest-thumb{width:60px;height:44px;object-fit:cover;border-radius:6px;display:block}
.dest-thumb-ph{width:60px;height:44px;border-radius:6px;background:var(--off-white);display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:18px}
.cat-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.cat-nature{background:#e8f5e9;color:#2e7d32}
.cat-culture{background:#fff3e0;color:#e65100}
.cat-beach{background:#e3f2fd;color:#1565c0}
.cat-wildlife{background:#fce4ec;color:#880e4f}
.cat-hill{background:#f3e5f5;color:#4a148c}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.4px}
.pill-active{background:#e6f9f0;color:#1a8a5e}
.pill-inactive{background:var(--off-white);color:var(--text-light)}
.act-btn{width:30px;height:30px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit2{background:var(--teal-pale);color:var(--teal)}.btn-edit2:hover{background:var(--teal);color:#fff}
.feat-on{background:#fff8e1;color:#c9a84c}
.feat-off{background:var(--off-white);color:#ccc}.feat-off:hover{background:#fff8e1;color:#c9a84c}
.btn-del2{background:var(--red-pale);color:var(--red)}.btn-del2:hover{background:var(--red);color:#fff}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px}
.form-sec-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:14px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:14px}
.form-sec-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.fgrp{display:flex;flex-direction:column;gap:6px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.8px;text-transform:uppercase}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.check-row{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--off-white);border-radius:10px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal)}
.check-row span{font-size:13px;color:var(--text-dark);font-weight:500}
.img-preview{width:100%;height:160px;object-fit:cover;border-radius:8px;margin-top:8px;display:block}
.dest-form-grid{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
/* Quick Facts builder */
.qf-row{display:grid;grid-template-columns:160px 130px 1fr 34px;gap:8px;align-items:center;background:var(--off-white);border-radius:8px;padding:8px 10px;margin-bottom:6px}
/* Best months */
.bm-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
.bm-cell{display:flex;flex-direction:column;gap:4px;align-items:center}
.bm-cell label{font-size:11px;font-weight:600;color:var(--text-light)}
.bm-cell select{padding:4px 6px;border:1.5px solid var(--border);border-radius:6px;font-size:11px;font-family:'DM Sans',sans-serif;outline:none;width:100%}
.bm-cell select.bm-good{background:#e8f5e9;color:#2e7d32;border-color:#a5d6a7}
.bm-cell select.bm-ok{background:#fff3e0;color:#e65100;border-color:#ffcc80}
.bm-cell select.bm-avoid{background:#fff;color:var(--text-light)}
@media(max-width:960px){.dest-form-grid{grid-template-columns:1fr}}
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
        <div class="topbar-title"><?= $action==='list' ? 'Destinations' : ($action==='edit' ? 'Edit Destination' : 'Add Destination') ?></div>
        <div class="topbar-breadcrumb">Destinations<?= $action!=='list' ? ' / '.($action==='edit'?'Edit':'Add New') : '' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($action === 'list'): ?>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <div style="position:relative">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:13px;pointer-events:none"></i>
            <input type="text" name="q" placeholder="Search destinations…" value="<?= htmlspecialchars($search) ?>"
                   style="padding:7px 12px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;width:200px;font-family:inherit;color:var(--text-dark)"
                   onfocus="this.style.borderColor='var(--teal)'" onblur="this.style.borderColor='var(--border)'"/>
          </div>
          <?php if ($search): ?>
            <a href="destinations.php" class="btn btn-outline btn-sm" title="Clear"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="destinations.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Destination</a>
      <?php else: ?>
        <a href="destinations.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Destination added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Destination updated.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Destination deleted.</div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
      <?php endif; ?>


      <?php if ($action === 'list'): ?>
      <!-- ═══════════════ LIST ═══════════════ -->
      <div class="page-tabs" style="display:flex;gap:2px;margin-bottom:22px;border-bottom:2px solid var(--border)">
        <a href="destinations.php" class="page-tab active" style="padding:10px 20px;font-size:13px;font-weight:600;color:var(--teal);text-decoration:none;border-radius:8px 8px 0 0;border:1px solid var(--border);border-bottom-color:#fff;margin-bottom:-2px;display:inline-flex;align-items:center;gap:7px;background:#fff"><i class="fas fa-map-marker-alt"></i> Destinations</a>
        <a href="dest-cats.php" class="page-tab" style="padding:10px 20px;font-size:13px;font-weight:600;color:var(--text-mid);text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;border-bottom:none;margin-bottom:-2px;display:inline-flex;align-items:center;gap:7px;transition:color .15s"><i class="fas fa-tags"></i> Categories</a>
      </div>
      <div class="page-header">
        <div class="page-header-left">
          <h1>All Destinations <span style="font-size:14px;font-weight:400;color:var(--text-light)">(<?= count($dests) ?>)</span></h1>
          <p>Manage destination guides shown on the website</p>
        </div>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:70px">Image</th>
                <th>Title</th>
                <th style="width:130px">Category</th>
                <th style="width:130px">Location</th>
                <th style="width:110px;text-align:center">Status</th>
                <th style="width:110px;text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($dests): foreach ($dests as $d): ?>
            <tr>
              <td>
                <?php
                  $thumbUrl = '';
                  if ($d['hero_image']) {
                      $thumbUrl = strpos($d['hero_image'],'uploads/')===0
                          ? SITE_URL.'/'.$d['hero_image']
                          : SITE_URL.'/images/'.$d['hero_image'];
                  }
                ?>
                <?php if ($thumbUrl): ?>
                  <img src="<?= htmlspecialchars($thumbUrl) ?>" class="dest-thumb" alt=""/>
                <?php else: ?>
                  <div class="dest-thumb-ph"><i class="fas fa-image"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="col-title"><?= htmlspecialchars($d['title']) ?></div>
                <div style="font-size:11px;color:var(--text-light);margin-top:2px;font-family:monospace"><?= htmlspecialchars($d['slug']) ?></div>
              </td>
              <td>
                <?php if ($d['cat_name']): ?>
                <span class="cat-badge" style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:var(--teal-pale);color:var(--teal)">
                  <i class="<?= htmlspecialchars($d['cat_icon'] ?: 'fas fa-map-marker-alt') ?>"></i>
                  <?= htmlspecialchars($d['cat_name']) ?>
                </span>
                <?php else: ?>
                <span style="font-size:12px;color:var(--text-light)">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:13px;color:var(--text-mid)"><?= htmlspecialchars($d['location']) ?></td>
              <td style="text-align:center">
                <a href="destinations.php?action=toggle_active&id=<?= $d['id'] ?>" title="Click to toggle">
                  <span class="status-pill <?= $d['is_active'] ? 'pill-active' : 'pill-inactive' ?>">
                    <i class="fas <?= $d['is_active'] ? 'fa-check-circle' : 'fa-moon' ?>"></i>
                    <?= $d['is_active'] ? 'Active' : 'Hidden' ?>
                  </span>
                </a>
              </td>
              <td style="text-align:center">
                <div style="display:flex;gap:5px;justify-content:center">
                  <a href="destinations.php?action=edit&id=<?= $d['id'] ?>" class="act-btn btn-edit2" title="Edit"><i class="fas fa-pen"></i></a>
                  <a href="destinations.php?action=toggle_featured&id=<?= $d['id'] ?>" class="act-btn <?= $d['is_featured'] ? 'feat-on' : 'feat-off' ?>" title="<?= $d['is_featured'] ? 'Remove featured' : 'Mark featured' ?>"><i class="fas fa-star"></i></a>
                  <a href="<?= SITE_URL ?>/destination-detail.php?slug=<?= urlencode($d['slug']) ?>" target="_blank" class="act-btn" style="background:var(--off-white);color:var(--text-mid)" title="Preview"><i class="fas fa-eye"></i></a>
                  <button onclick="confirmDelete(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>')" class="act-btn btn-del2" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6">
              <div style="text-align:center;padding:50px;color:var(--text-light)">
                <i class="fas fa-map-marker-alt" style="font-size:40px;opacity:.15;display:block;margin-bottom:12px"></i>
                <p><?= $search ? 'No destinations match your search.' : 'No destinations yet. Add your first one.' ?></p>
              </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>


      <?php else: ?>
      <!-- ═══════════════ ADD / EDIT FORM ═══════════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1><?= $editDest ? 'Edit Destination' : 'Add New Destination' ?></h1>
          <p><?= $editDest ? 'Update the destination guide' : 'Create a new destination guide page' ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="destForm"
            onsubmit="if(typeof tinymce!=='undefined') tinymce.triggerSave();">
        <input type="hidden" name="dest_id" value="<?= $editDest['id'] ?? 0 ?>"/>
        <div class="dest-form-grid">

          <!-- ── LEFT COLUMN ── -->
          <div>

            <!-- Title -->
            <div class="form-section">
              <div class="form-sec-body" style="padding:20px">
                <div class="fgrp">
                  <label>Destination Title <span style="color:var(--red)">*</span></label>
                  <input type="text" name="title" class="form-control" required
                         placeholder="e.g. Arugam Bay: The World-Class Surf Mecca"
                         value="<?= htmlspecialchars($v['title'] ?? '') ?>"
                         style="font-size:17px;padding:12px 14px"
                         oninput="autoSlug(this.value)"/>
                </div>
                <div class="fgrp">
                  <label>Slug <span style="font-size:11px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(auto-generated, used in URL)</span></label>
                  <input type="text" id="slugField" class="form-control"
                         style="background:var(--off-white);color:var(--text-light);cursor:default;font-family:monospace;font-size:13px"
                         value="<?= htmlspecialchars($v['slug'] ?? '') ?>" readonly/>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 100px;gap:14px">
                  <div class="fgrp">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                      <option value="0">— Select Category —</option>
                      <?php foreach ($destCats as $dc): ?>
                        <option value="<?= $dc['id'] ?>" <?= (int)($v['category_id'] ?? 0) === (int)$dc['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($dc['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="fgrp">
                    <label>Location <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0">(Province / District)</span></label>
                    <input type="text" name="location" class="form-control"
                           placeholder="e.g. Eastern Province, Sri Lanka"
                           value="<?= htmlspecialchars($v['location'] ?? '') ?>"/>
                  </div>
                  <div class="fgrp">
                    <label>Read Time (min)</label>
                    <input type="number" name="read_time" class="form-control" min="1" max="60"
                           value="<?= (int)($v['read_time'] ?? 5) ?>"/>
                  </div>
                </div>
                <div class="fgrp">
                  <label>Region Label <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0">(shown on card, e.g. "South Coast")</span></label>
                  <input type="text" name="region" class="form-control"
                         placeholder="e.g. Hill Country"
                         value="<?= htmlspecialchars($v['region'] ?? '') ?>"/>
                </div>
              </div>
            </div>

            <!-- Excerpt -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-file-alt"></i><h3>Short Description <span style="font-size:12px;font-weight:400;color:var(--text-light)">(shown on listing cards)</span></h3></div>
              <div class="form-sec-body">
                <textarea name="excerpt" class="form-control" rows="3"
                          placeholder="1-2 sentences describing this destination…"><?= htmlspecialchars($v['excerpt'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Content -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-align-left"></i><h3>Full Article Content</h3></div>
              <div class="form-sec-body">
                <p style="font-size:12px;color:var(--text-light);margin:0">Write the full destination guide. Use headings (H2/H3), paragraphs and images to tell the story.</p>
                <textarea name="content" id="destContent" rows="18"
                          style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:12px;font-size:14px;font-family:'DM Sans',sans-serif;resize:vertical;outline:none;box-sizing:border-box"><?= htmlspecialchars($v['content'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Quick Facts builder -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-info-circle"></i><h3>Quick Facts Sidebar</h3></div>
              <div class="form-sec-body">
                <p style="font-size:12px;color:var(--text-light);margin:0 0 8px">These appear in the sidebar Quick Facts card. Icon uses FontAwesome class (e.g. <code>fas fa-sun</code>).</p>
                <div id="qfContainer">
                  <?php if ($vQF): foreach ($vQF as $qf): ?>
                  <div class="qf-row">
                    <input type="text" name="qf_icon[]" class="form-control" placeholder="fas fa-sun"
                           value="<?= htmlspecialchars($qf['icon'] ?? 'fas fa-info-circle') ?>" style="font-size:12px"/>
                    <input type="text" name="qf_label[]" class="form-control" placeholder="Label"
                           value="<?= htmlspecialchars($qf['label'] ?? '') ?>" style="font-size:12px"/>
                    <input type="text" name="qf_value[]" class="form-control" placeholder="Value"
                           value="<?= htmlspecialchars($qf['value'] ?? '') ?>" style="font-size:12px"/>
                    <button type="button" onclick="this.closest('.qf-row').remove()"
                            style="width:30px;height:30px;border:none;background:var(--red-pale);color:var(--red);border-radius:6px;cursor:pointer;font-size:14px">×</button>
                  </div>
                  <?php endforeach; else: ?>
                  <!-- Default empty row -->
                  <div class="qf-row">
                    <input type="text" name="qf_icon[]" class="form-control" placeholder="fas fa-map-marker-alt" value="fas fa-map-marker-alt" style="font-size:12px"/>
                    <input type="text" name="qf_label[]" class="form-control" placeholder="Location" value="Location" style="font-size:12px"/>
                    <input type="text" name="qf_value[]" class="form-control" placeholder="e.g. Eastern Province" style="font-size:12px"/>
                    <button type="button" onclick="this.closest('.qf-row').remove()"
                            style="width:30px;height:30px;border:none;background:var(--red-pale);color:var(--red);border-radius:6px;cursor:pointer;font-size:14px">×</button>
                  </div>
                  <?php endif; ?>
                </div>
                <button type="button" onclick="addQFRow()"
                        style="margin-top:8px;padding:7px 16px;background:var(--teal-pale);color:var(--teal);border:none;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600">
                  <i class="fas fa-plus"></i> Add Fact
                </button>
              </div>
            </div>

            <!-- Best Months -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-calendar-alt"></i><h3>Best Time to Visit</h3></div>
              <div class="form-sec-body">
                <p style="font-size:12px;color:var(--text-light);margin:0 0 12px">Set each month as <strong style="color:#27ae60">Good</strong>, <strong style="color:#e67e22">OK</strong> or <strong style="color:#c0392b">Avoid</strong>.</p>
                <div class="bm-grid">
                  <?php foreach ($months as $i => $mon): ?>
                  <div class="bm-cell">
                    <label><?= $mon ?></label>
                    <select name="bm_<?= $i ?>" class="bm-cell-select"
                            onchange="this.className='bm-cell-select bm-'+this.value">
                      <option value="good"  <?= ($vBM[$i]??'avoid')==='good'  ? 'selected' : '' ?>>Good</option>
                      <option value="ok"    <?= ($vBM[$i]??'avoid')==='ok'    ? 'selected' : '' ?>>OK</option>
                      <option value="avoid" <?= ($vBM[$i]??'avoid')==='avoid' ? 'selected' : '' ?>>Avoid</option>
                    </select>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

          </div><!-- /.left -->


          <!-- ── RIGHT SIDEBAR ── -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Publish -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-cog"></i><h3>Settings</h3></div>
              <div class="form-sec-body">
                <label class="check-row">
                  <input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || $v['is_active'] ? 'checked' : '' ?>/>
                  <span>Active (visible on website)</span>
                </label>
                <label class="check-row">
                  <input type="checkbox" name="is_featured" value="1" <?= !empty($v['is_featured']) ? 'checked' : '' ?>/>
                  <span>Featured (shown prominently)</span>
                </label>
                <div class="fgrp">
                  <label>Sort Order</label>
                  <input type="number" name="sort_order" class="form-control" min="0"
                         value="<?= (int)($v['sort_order'] ?? 0) ?>"/>
                </div>
              </div>
            </div>

            <!-- Hero Image -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-image"></i><h3>Hero Image</h3></div>
              <div class="form-sec-body">
                <?php
                  $existImg = '';
                  if (!empty($v['hero_image'])) {
                      $existImg = strpos($v['hero_image'],'uploads/')===0
                          ? SITE_URL.'/'.$v['hero_image']
                          : SITE_URL.'/images/'.$v['hero_image'];
                  }
                ?>
                <?php if ($existImg): ?>
                  <img src="<?= htmlspecialchars($existImg) ?>" class="img-preview" id="imgPreview" alt=""/>
                <?php else: ?>
                  <img src="" class="img-preview" id="imgPreview" alt="" style="display:none"/>
                <?php endif; ?>
                <input type="file" name="hero_image" accept="image/*" class="form-control"
                       style="font-size:13px"
                       onchange="previewImg(this,'imgPreview')"/>
                <p style="font-size:11px;color:var(--text-light);margin:4px 0 0">Recommended: 1400×900px. Shown as the full-width hero background.</p>
                <?php if (!empty($v['hero_image'])): ?>
                  <p style="font-size:11px;color:var(--text-light);margin:4px 0 0">Current: <code><?= htmlspecialchars($v['hero_image']) ?></code></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:15px">
              <i class="fas fa-save"></i> <?= $editDest ? 'Update Destination' : 'Save Destination' ?>
            </button>
            <?php if ($editDest): ?>
              <a href="<?= SITE_URL ?>/destination-detail.php?slug=<?= urlencode($editDest['slug']) ?>"
                 target="_blank" class="btn btn-outline" style="width:100%;text-align:center">
                <i class="fas fa-eye"></i> Preview on Website
              </a>
            <?php endif; ?>

          </div><!-- /.right -->

        </div>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>
</div>

<!-- Delete confirmation -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <h3 style="margin:0 0 12px;font-size:18px;color:var(--text-dark)">Delete Destination?</h3>
    <p id="deleteModalMsg" style="color:var(--text-mid);font-size:14px;margin:0 0 24px"></p>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button onclick="closeDelete()" class="btn btn-outline">Cancel</button>
      <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
    </div>
  </div>
</div>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('adminSidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
  document.getElementById('adminSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// Auto slug
function autoSlug(val) {
  const sf = document.getElementById('slugField');
  if (sf && !sf.dataset.locked) {
    sf.value = val.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
  }
}

// Image preview
function previewImg(input, previewId) {
  const prev = document.getElementById(previewId);
  if (!prev) return;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { prev.src = e.target.result; prev.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}

// Quick Facts add row
function addQFRow() {
  const container = document.getElementById('qfContainer');
  const row = document.createElement('div');
  row.className = 'qf-row';
  row.innerHTML = `
    <input type="text" name="qf_icon[]" class="form-control" placeholder="fas fa-info-circle" value="fas fa-info-circle" style="font-size:12px"/>
    <input type="text" name="qf_label[]" class="form-control" placeholder="Label" style="font-size:12px"/>
    <input type="text" name="qf_value[]" class="form-control" placeholder="Value" style="font-size:12px"/>
    <button type="button" onclick="this.closest('.qf-row').remove()"
            style="width:30px;height:30px;border:none;background:var(--red-pale);color:var(--red);border-radius:6px;cursor:pointer;font-size:14px">×</button>
  `;
  container.appendChild(row);
}

// Month select colors
document.querySelectorAll('.bm-cell-select').forEach(sel => {
  sel.className = 'bm-cell-select bm-' + sel.value;
});

// Delete modal
function confirmDelete(id, title) {
  document.getElementById('deleteModal').style.display = 'flex';
  document.getElementById('deleteModalMsg').textContent = 'Are you sure you want to delete "' + title + '"? This cannot be undone.';
  document.getElementById('deleteConfirmBtn').href = 'destinations.php?action=delete&id=' + id;
}
function closeDelete() { document.getElementById('deleteModal').style.display = 'none'; }

// TinyMCE
tinymce.init({
  selector: '#destContent',
  plugins: 'lists link image table code',
  toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link image | table | code',
  height: 440,
  menubar: false,
  block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
  content_style: "body{font-family:'DM Sans',sans-serif;font-size:15px;color:#1a2b2b;line-height:1.7}",
  branding: false,
});
</script>
</body>
</html>
