<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'tours';
$action     = $_GET['action'] ?? 'list';
$msg        = $_GET['msg']    ?? '';
$editId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tour       = null;
$formError  = '';

/* ── CATEGORIES ── */
$categories = [];
$cr = $conn->query("SELECT * FROM tour_categories ORDER BY sort_order, name");
if ($cr) while ($c = $cr->fetch_assoc()) $categories[] = $c;

/* ── DELETE ── */
if ($action === 'delete' && $editId) {
    $r = $conn->query("SELECT image FROM tours WHERE id=$editId");
    if ($r && $row = $r->fetch_assoc()) {
        if ($row['image'] && file_exists(__DIR__ . '/../' . $row['image']))
            @unlink(__DIR__ . '/../' . $row['image']);
    }
    $conn->query("DELETE FROM tours WHERE id=$editId");
    header('Location: tours.php?msg=deleted'); exit;
}

/* ── TOGGLE FEATURED ── */
if ($action === 'toggle_featured' && $editId) {
    $conn->query("UPDATE tours SET is_featured = 1 - is_featured WHERE id=$editId");
    header('Location: tours.php?msg=updated'); exit;
}

/* ── SAVE (add / edit) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $short_desc  = trim($_POST['short_desc']  ?? '');
    $cat_id      = (int)($_POST['category_id'] ?? 0);
    $price_note  = trim($_POST['price_note']  ?? '');
    $duration    = trim($_POST['duration']    ?? '');
    $group_size  = trim($_POST['group_size']  ?? '');
    $tour_type   = trim($_POST['tour_type']   ?? '');
    $description = trim($_POST['description'] ?? '');
    $highlights  = trim($_POST['highlights']  ?? '');
    $tips        = trim($_POST['tips']        ?? '');
    $itinerary   = trim($_POST['itinerary']   ?? '');
    $map_embed   = trim($_POST['map_embed']   ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active   = isset($_POST['is_active'])   ? 1 : 0;
    $postId      = (int)($_POST['tour_id']    ?? 0);
    $seo_json    = json_encode([
        'title' => trim($_POST['seo_title'] ?? ''),
        'desc'  => trim($_POST['seo_desc']  ?? ''),
        'kw'    => trim($_POST['seo_kw']    ?? ''),
    ]);

    if (!$title) {
        $formError = 'Tour name is required.';
        $action    = $postId ? 'edit' : 'add';
        if ($postId) {
            $r = $conn->query("SELECT * FROM tours WHERE id=$postId");
            $tour = $r ? $r->fetch_assoc() : null;
            $editId = $postId;
        }
    } else {
        // Generate unique slug
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $sc   = $conn->prepare("SELECT id FROM tours WHERE slug=? AND id!=?");
        $sc->bind_param('si', $slug, $postId);
        $sc->execute();
        if ($sc->get_result()->num_rows) $slug .= '-' . time();
        $sc->close();

        // Image upload
        $imagePath = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $up = uploadImage($_FILES['image'], 'uploads/tours');
            if ($up['ok'])  $imagePath = $up['path'];
            else            $formError = $up['msg'];
        }

        if (!$formError) {
            $catVal  = $cat_id ?: 'NULL';
            $titleE  = $conn->real_escape_string($title);
            $slugE   = $conn->real_escape_string($slug);
            $sdE     = $conn->real_escape_string($short_desc);
            $pnE     = $conn->real_escape_string($price_note);
            $durE    = $conn->real_escape_string($duration);
            $gsE     = $conn->real_escape_string($group_size);
            $ttE     = $conn->real_escape_string($tour_type);
            $descE   = $conn->real_escape_string($description);
            $hiE     = $conn->real_escape_string($highlights);
            $tipsE   = $conn->real_escape_string($tips);
            $itinE   = $conn->real_escape_string($itinerary);
            $mapE    = $conn->real_escape_string($map_embed);
            $seoE    = $conn->real_escape_string($seo_json);
            $imgE    = $conn->real_escape_string($imagePath);

            if ($postId) {
                $conn->query("UPDATE tours SET
                    title='$titleE', slug='$slugE', short_desc='$sdE',
                    category_id=$catVal, price_note='$pnE', duration='$durE',
                    group_size='$gsE', description='$descE', highlights='$hiE',
                    tips='$tipsE', itinerary='$itinE', map_embed='$mapE',
                    includes='$seoE', is_featured=$is_featured, is_active=$is_active,
                    image='$imgE'
                    WHERE id=$postId");
                header('Location: tours.php?msg=updated'); exit;
            } else {
                $conn->query("INSERT INTO tours
                    (title,slug,short_desc,category_id,price_note,duration,group_size,
                     description,highlights,tips,itinerary,map_embed,includes,is_featured,is_active,image)
                    VALUES
                    ('$titleE','$slugE','$sdE',$catVal,'$pnE','$durE','$gsE',
                     '$descE','$hiE','$tipsE','$itinE','$mapE','$seoE',$is_featured,$is_active,'$imgE')");
                header('Location: tours.php?msg=added'); exit;
            }
        }
    }
}

/* ── FETCH FOR EDIT ── */
if ($action === 'edit' && $editId && !$tour) {
    $r    = $conn->query("SELECT * FROM tours WHERE id=$editId");
    $tour = $r ? $r->fetch_assoc() : null;
    if (!$tour) { header('Location: tours.php'); exit; }
}

/* ── LIST ── */
$tours  = [];
$search = trim($_GET['q'] ?? '');
if ($action === 'list') {
    $where = $search ? "WHERE t.title LIKE '%" . $conn->real_escape_string($search) . "%'" : '';
    $r = $conn->query("SELECT t.*, tc.name AS cat_name FROM tours t
        LEFT JOIN tour_categories tc ON t.category_id = tc.id
        $where ORDER BY t.sort_order, t.created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $tours[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title><?= $action==='list' ? 'Manage Tours' : ($action==='edit' ? 'Edit Tour' : 'Add New Tour') ?> | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.tour-thumb{width:64px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--border)}
.tour-thumb-ph{width:64px;height:44px;border-radius:8px;background:var(--off-white);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:18px}
.cat-tag{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:var(--teal-pale);color:var(--teal);margin:2px 2px 2px 0}
.featured-yes{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:var(--green-pale);color:var(--green);font-size:12px;font-weight:700}
.featured-no{color:var(--text-light);font-size:13px}
.act-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.btn-feat{background:var(--gold-pale);color:var(--gold)}.btn-feat:hover{background:var(--gold);color:#fff}
.search-bar{display:flex;gap:10px;margin-bottom:20px;align-items:center}
.search-bar .form-control{max-width:320px}

/* FORM */
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;overflow:hidden}
.form-sec-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;background:#fff}
.form-sec-head h3{font-size:15px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:15px;width:18px;text-align:center}
.form-sec-body{padding:22px}
.frow{display:grid;gap:18px;margin-bottom:18px}
.frow.c2{grid-template-columns:1fr 1fr}
.frow.c3{grid-template-columns:1fr 1fr 1fr}
.frow.c5{grid-template-columns:repeat(5,1fr)}
.frow:last-child{margin-bottom:0}
.fgrp{display:flex;flex-direction:column;gap:6px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.8px;text-transform:uppercase}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
textarea.form-control{resize:vertical;min-height:80px}
.fcheck{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;color:var(--text-dark);padding-top:6px}
.fcheck input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal);cursor:pointer}
.img-preview{width:140px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border);margin-top:8px}
.rich-area{width:100%;min-height:180px;padding:12px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);resize:vertical;outline:none;transition:border-color .2s;line-height:1.6}
.rich-area:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.seo-head{background:var(--teal-pale) !important}
.seo-head h3,.seo-head i{color:var(--teal-dark) !important}
.form-sticky-bar{position:sticky;bottom:0;background:rgba(245,247,250,.96);backdrop-filter:blur(8px);padding:14px 0;border-top:1px solid var(--border);display:flex;gap:12px;align-items:center;z-index:50;margin-top:8px}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-light)}
.empty-state i{font-size:52px;margin-bottom:12px;display:block;opacity:.3}
@media(max-width:768px){.frow.c2,.frow.c3,.frow.c5{grid-template-columns:1fr}}
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
        <div class="topbar-title"><?= $action==='list' ? 'Manage Tours' : ($action==='edit' ? 'Edit Tour' : 'Add New Tour') ?></div>
        <div class="topbar-breadcrumb">Tours<?= $action!=='list' ? ' / '.($action==='edit'?'Edit':'Add New') : '' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($action === 'list'): ?>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <div style="position:relative">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:13px;pointer-events:none"></i>
            <input type="text" name="q" placeholder="Search tours…"
                   value="<?= htmlspecialchars($search) ?>"
                   style="padding:7px 12px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;width:200px;font-family:inherit;color:var(--text-dark)"
                   onfocus="this.style.borderColor='var(--teal)'" onblur="this.style.borderColor='var(--border)'"/>
          </div>
          <?php if ($search): ?>
            <a href="tours.php" class="btn btn-outline btn-sm" title="Clear"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="tour-cats.php" class="btn btn-outline btn-sm"><i class="fas fa-tags"></i> Categories</a>
        <a href="tours.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Tour</a>
      <?php else: ?>
        <a href="tours.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Tour added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Tour updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Tour deleted.</div><?php endif; ?>
      <?php if ($formError):         ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div><?php endif; ?>

      <?php if ($action === 'list'): ?>
      <!-- ============ LIST VIEW ============ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>All Tours <span style="font-size:14px;font-weight:400;color:var(--text-light)">(<?= count($tours) ?>)</span></h1>
          <p>Manage your tour packages</p>
        </div>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:80px">Image</th>
                <th>Name</th>
                <th>Category</th>
                <th style="width:130px">Price</th>
                <th style="width:100px;text-align:center">Featured</th>
                <th style="width:120px;text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($tours): foreach ($tours as $t): ?>
            <tr>
              <td>
                <?php if ($t['image']): ?>
                  <img src="<?= SITE_URL ?>/<?= htmlspecialchars($t['image']) ?>" class="tour-thumb" alt="">
                <?php else: ?>
                  <div class="tour-thumb-ph"><i class="fas fa-image"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="col-title"><?= htmlspecialchars($t['title']) ?></div>
                <?php if ($t['duration']): ?>
                  <div class="text-muted text-sm"><i class="fas fa-clock" style="font-size:11px"></i> <?= htmlspecialchars($t['duration']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($t['cat_name']): ?>
                  <span class="cat-tag"><?= htmlspecialchars($t['cat_name']) ?></span>
                <?php else: ?>
                  <span class="text-muted text-sm">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($t['price_note']): ?>
                  <span style="font-weight:600;color:var(--teal)"><?= htmlspecialchars($t['price_note']) ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <?php if ($t['is_featured']): ?>
                  <span class="featured-yes"><i class="fas fa-star"></i> Yes</span>
                <?php else: ?>
                  <span class="featured-no">No</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <div style="display:flex;gap:6px;justify-content:center">
                  <a href="tours.php?action=toggle_featured&id=<?= $t['id'] ?>" class="act-btn btn-feat" title="Toggle Featured"><i class="fas fa-star"></i></a>
                  <a href="tours.php?action=edit&id=<?= $t['id'] ?>" class="act-btn btn-edit" title="Edit"><i class="fas fa-pen"></i></a>
                  <button onclick="confirmDelete(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['title'])) ?>')" class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6">
              <div class="empty-state">
                <i class="fas fa-map-marked-alt"></i>
                <p>No tours found. <a href="tours.php?action=add" style="color:var(--teal);font-weight:600">Add your first tour →</a></p>
              </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php else: ?>
      <!-- ============ ADD / EDIT FORM ============ -->
      <?php
        $v   = $tour ?? [];
        $seo = isset($v['includes']) ? (json_decode($v['includes'], true) ?? []) : [];
      ?>
      <div class="page-header">
        <div class="page-header-left">
          <h1><?= $action==='edit' ? 'Edit Tour' : 'Add New Tour' ?></h1>
          <p><?= $action==='edit' ? htmlspecialchars($v['title'] ?? '') : 'Fill in the details below to create a new tour' ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="tourForm">
        <input type="hidden" name="tour_id"        value="<?= $editId ?>"/>
        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($v['image'] ?? '') ?>"/>

        <!-- BASIC INFORMATION -->
        <div class="form-section">
          <div class="form-sec-head">
            <i class="fas fa-info-circle"></i>
            <h3>Basic Information</h3>
          </div>
          <div class="form-sec-body">
            <div class="frow">
              <div class="fgrp">
                <label>Tour Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="title" class="form-control" required
                       placeholder="e.g. Sigiriya Full Day Tour"
                       value="<?= htmlspecialchars($v['title'] ?? '') ?>"/>
              </div>
            </div>
            <div class="frow">
              <div class="fgrp">
                <label>Sub Heading (Top Banner Text)</label>
                <textarea name="short_desc" class="form-control" rows="2"
                          placeholder="Short description shown on the tour banner…"><?= htmlspecialchars($v['short_desc'] ?? '') ?></textarea>
              </div>
            </div>
            <div class="frow">
              <div class="fgrp">
                <label>Category</label>
                <select name="category_id" class="form-control">
                  <option value="">— Select Category —</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (($v['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- KEY DETAILS -->
        <div class="form-section">
          <div class="form-sec-head">
            <i class="fas fa-list-ul"></i>
            <h3>Key Details</h3>
          </div>
          <div class="form-sec-body">
            <div class="frow c5">
              <div class="fgrp">
                <label>Price / Range</label>
                <input type="text" name="price_note" class="form-control"
                       placeholder="e.g. $120 per person"
                       value="<?= htmlspecialchars($v['price_note'] ?? '') ?>"/>
              </div>
              <div class="fgrp">
                <label>Duration</label>
                <input type="text" name="duration" class="form-control"
                       placeholder="e.g. 14 Days"
                       value="<?= htmlspecialchars($v['duration'] ?? '') ?>"/>
              </div>
              <div class="fgrp">
                <label>Tour Type</label>
                <input type="text" name="tour_type" class="form-control"
                       placeholder="e.g. Adventure"
                       value="<?= htmlspecialchars($v['tour_type'] ?? '') ?>"/>
              </div>
              <div class="fgrp">
                <label>Min People / Group Size</label>
                <input type="text" name="group_size" class="form-control"
                       placeholder="e.g. 2 People"
                       value="<?= htmlspecialchars($v['group_size'] ?? '') ?>"/>
              </div>
              <div class="fgrp">
                <label>Status</label>
                <label class="fcheck">
                  <input type="checkbox" name="is_active" value="1"
                         <?= (!isset($v['is_active']) || $v['is_active']) ? 'checked' : '' ?>/>
                  Published
                </label>
              </div>
            </div>
            <div>
              <label class="fcheck">
                <input type="checkbox" name="is_featured" value="1"
                       <?= !empty($v['is_featured']) ? 'checked' : '' ?>/>
                <span style="color:var(--gold);font-weight:600"><i class="fas fa-star"></i> Mark as Featured</span>
              </label>
            </div>
          </div>
        </div>

        <!-- MEDIA & MAP -->
        <div class="form-section">
          <div class="form-sec-head">
            <i class="fas fa-photo-video"></i>
            <h3>Media &amp; Map</h3>
          </div>
          <div class="form-sec-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>Main Background Image</label>
                <input type="file" name="image" class="form-control" accept="image/*"
                       onchange="previewImg(this)"/>
                <small class="text-muted" style="font-size:12px">Recommended: 1920×1080px · Max 25 MB · JPG / PNG / WebP</small>
                <div id="previewWrap" style="<?= empty($v['image']) ? 'display:none' : '' ?>">
                  <img id="imgPreview"
                       src="<?= !empty($v['image']) ? SITE_URL.'/'.$v['image'] : '' ?>"
                       class="img-preview" alt=""/>
                </div>
              </div>
              <div class="fgrp">
                <label>Map Embed Code (iframe)</label>
                <textarea name="map_embed" class="form-control" rows="6"
                          placeholder='&lt;iframe src="https://maps.google.com/…"&gt;&lt;/iframe&gt;'><?= htmlspecialchars($v['map_embed'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- DETAILED CONTENT -->
        <div class="form-section">
          <div class="form-sec-head">
            <i class="fas fa-align-left"></i>
            <h3>Detailed Content</h3>
          </div>
          <div class="form-sec-body">
            <div class="fgrp" style="margin-bottom:20px">
              <label>Tour Description (Before Itinerary)</label>
              <textarea name="description" class="rich-area"><?= htmlspecialchars($v['description'] ?? '') ?></textarea>
            </div>
            <div class="fgrp" style="margin-bottom:20px">
              <label>Journey Highlights</label>
              <textarea name="highlights" class="rich-area"><?= htmlspecialchars($v['highlights'] ?? '') ?></textarea>
            </div>
            <div class="fgrp" style="margin-bottom:20px">
              <label style="color:#c0392b"><i class="fas fa-lightbulb"></i> Insightful Tips (Pink Box)</label>
              <textarea name="tips" class="rich-area"><?= htmlspecialchars($v['tips'] ?? '') ?></textarea>
            </div>
            <div class="fgrp">
              <label>Itinerary / FAQ Content</label>
              <textarea name="itinerary" class="rich-area"><?= htmlspecialchars($v['itinerary'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- SEO -->
        <div class="form-section">
          <div class="form-sec-head seo-head">
            <i class="fas fa-search" style="color:var(--teal-dark)"></i>
            <h3 style="color:var(--teal-dark)">SEO Optimizations</h3>
          </div>
          <div class="form-sec-body">
            <div class="frow">
              <div class="fgrp">
                <label>SEO Meta Title</label>
                <input type="text" name="seo_title" class="form-control"
                       placeholder="Custom title for search engines (leave empty to use tour name)"
                       value="<?= htmlspecialchars($seo['title'] ?? '') ?>"/>
              </div>
            </div>
            <div class="frow">
              <div class="fgrp">
                <label>SEO Meta Description</label>
                <textarea name="seo_desc" class="form-control" rows="2"
                          placeholder="Brief summary for search results…"><?= htmlspecialchars($seo['desc'] ?? '') ?></textarea>
              </div>
            </div>
            <div class="frow">
              <div class="fgrp">
                <label>SEO Keywords</label>
                <input type="text" name="seo_kw" class="form-control"
                       placeholder="hiking, wellness, sri lanka, etc."
                       value="<?= htmlspecialchars($seo['kw'] ?? '') ?>"/>
              </div>
            </div>
          </div>
        </div>

        <!-- STICKY SAVE BAR -->
        <div class="form-sticky-bar">
          <button type="submit" class="btn btn-primary" style="min-width:200px;padding:13px 28px;font-size:15px">
            <i class="fas fa-save"></i> <?= $action==='edit' ? 'Update Tour' : 'Save Tour Details' ?>
          </button>
          <a href="tours.php" class="btn btn-outline">Cancel</a>
        </div>

      </form>
      <?php endif; ?>

    </div>
  </div>
</div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)">
      <i class="fas fa-trash"></i>
    </div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Tour?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13.5px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeDelModal()" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<!-- TinyMCE rich text editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="js/admin.js"></script>
<script>
<?php if ($action !== 'list'): ?>
tinymce.init({
  selector: '.rich-area',
  height: 300,
  menubar: false,
  plugins: 'lists link code',
  toolbar: 'undo redo | blocks | bold italic | bullist numlist | link | code',
  content_style: "body { font-family: 'DM Sans', sans-serif; font-size: 14px; line-height: 1.7; color: #2c3e50; padding: 10px; }",
  branding: false,
  promotion: false,
  setup: function(editor) {
    editor.on('change', function() { editor.save(); });
  }
});
<?php endif; ?>

function confirmDelete(id, name) {
  document.getElementById('delModalText').textContent = 'Are you sure you want to delete "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'tours.php?action=delete&id=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
function closeDelModal() {
  document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeDelModal();
});

function previewImg(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('imgPreview').src = e.target.result;
      document.getElementById('previewWrap').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
