<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'gallery';
$msg        = $_GET['msg'] ?? '';
$filterCat  = $_GET['cat'] ?? '';
$view       = $_GET['view'] ?? 'gallery'; // gallery | cats

/* CATEGORY MANAGEMENT */
if ($view === 'cats') {
    $catEditId  = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
    $catEdit    = null;
    $catError   = '';

    /* delete category */
    if (isset($_GET['delete_cat'])) {
        $id   = (int)$_GET['delete_cat'];
        $used = $conn->query("SELECT COUNT(*) as cnt FROM gallery WHERE category=(SELECT slug FROM gallery_categories WHERE id=$id)")->fetch_assoc()['cnt'];
        if ($used > 0) {
            $msg = 'cat_inuse';
        } else {
            $conn->query("DELETE FROM gallery_categories WHERE id=$id");
            $msg = 'cat_deleted';
        }
        header('Location: gallery.php?view=cats&msg=' . $msg); exit;
    }

    /* save category */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cat'])) {
        $cname  = trim($_POST['cat_name'] ?? '');
        $csort  = (int)($_POST['cat_sort'] ?? 0);
        $cpostId = (int)($_POST['cat_id'] ?? 0);

        if (!$cname) {
            $catError = 'Category name is required.';
            $catEditId = $cpostId;
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $cname), '-'));
            $sc   = $conn->prepare("SELECT id FROM gallery_categories WHERE slug=? AND id!=?");
            $sc->bind_param('si', $slug, $cpostId);
            $sc->execute();
            if ($sc->get_result()->num_rows) $slug .= '-' . time();
            $sc->close();

            $nameE = $conn->real_escape_string($cname);
            $slugE = $conn->real_escape_string($slug);

            if ($cpostId) {
                /* update slug in gallery rows too */
                $oldSlug = $conn->query("SELECT slug FROM gallery_categories WHERE id=$cpostId")->fetch_assoc()['slug'] ?? '';
                $conn->query("UPDATE gallery_categories SET name='$nameE', slug='$slugE', sort_order=$csort WHERE id=$cpostId");
                if ($oldSlug && $oldSlug !== $slugE) {
                    $conn->query("UPDATE gallery SET category='$slugE' WHERE category='$oldSlug'");
                }
                header('Location: gallery.php?view=cats&msg=cat_updated'); exit;
            } else {
                $conn->query("INSERT INTO gallery_categories (name, slug, sort_order) VALUES ('$nameE','$slugE',$csort)");
                header('Location: gallery.php?view=cats&msg=cat_added'); exit;
            }
        }
    }

    if ($catEditId) {
        $r       = $conn->query("SELECT * FROM gallery_categories WHERE id=$catEditId");
        $catEdit = $r ? $r->fetch_assoc() : null;
    }
}

/* GALLERY ACTIONS*/

/* delete single */
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $conn->query("SELECT filename FROM gallery WHERE id=$id")->fetch_assoc();
    if ($row) {
        $p = __DIR__ . '/../../' . $row['filename'];
        if (file_exists($p)) unlink($p);
        $conn->query("DELETE FROM gallery WHERE id=$id");
    }
    header('Location: gallery.php?msg=deleted'); exit;
}

/* toggle active */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE gallery SET is_active = 1 - is_active WHERE id=$id");
    header('Location: gallery.php' . ($filterCat ? "?cat=$filterCat" : '')); exit;
}

/* bulk delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = $_POST['selected'] ?? [];
    foreach ($ids as $id) {
        $id  = (int)$id;
        $row = $conn->query("SELECT filename FROM gallery WHERE id=$id")->fetch_assoc();
        if ($row) {
            $p = __DIR__ . '/../../' . $row['filename'];
            if (file_exists($p)) unlink($p);
            $conn->query("DELETE FROM gallery WHERE id=$id");
        }
    }
    header('Location: gallery.php?msg=bulk_deleted'); exit;
}

/* upload */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $cat      = $conn->real_escape_string($_POST['category'] ?? '');
    $caption  = trim($_POST['caption']    ?? '');
    $alt_text = trim($_POST['alt_text']   ?? '');
    $sort     = (int)($_POST['sort_order'] ?? 0);
    $uploaded = 0;
    $errors   = [];

    $files = $_FILES['images'];
    $count = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name'     => is_array($files['name'])     ? $files['name'][$i]     : $files['name'],
            'type'     => is_array($files['type'])     ? $files['type'][$i]     : $files['type'],
            'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
            'error'    => is_array($files['error'])    ? $files['error'][$i]    : $files['error'],
            'size'     => is_array($files['size'])     ? $files['size'][$i]     : $files['size'],
        ];
        if ($file['error'] !== UPLOAD_ERR_OK) continue;

        $up = uploadImage($file, 'uploads/gallery');
        if (!$up['ok']) { $errors[] = $file['name'] . ': ' . $up['msg']; continue; }

        $fnE  = $conn->real_escape_string($up['path']);
        $capE = $conn->real_escape_string($caption);
        $altE = $conn->real_escape_string($alt_text ?: $caption);
        $conn->query("INSERT INTO gallery (filename, caption, category, alt_text, sort_order)
            VALUES ('$fnE','$capE','$cat','$altE',$sort)");
        $uploaded++;
    }

    if ($errors) { $_SESSION['upload_errors'] = $errors; }
    $redir = $uploaded ? "uploaded_$uploaded" : 'upload_err';
    header('Location: gallery.php?msg=' . urlencode($redir) . ($cat ? "&cat=$cat" : '')); exit;
}

/* update item */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $id  = (int)$_POST['item_id'];
    $cap = $conn->real_escape_string(trim($_POST['caption']    ?? ''));
    $alt = $conn->real_escape_string(trim($_POST['alt_text']   ?? ''));
    $cat = $conn->real_escape_string($_POST['category']        ?? '');
    $srt = (int)($_POST['sort_order'] ?? 0);
    $conn->query("UPDATE gallery SET caption='$cap', alt_text='$alt', category='$cat', sort_order=$srt WHERE id=$id");
    header('Location: gallery.php?msg=updated' . ($filterCat ? "&cat=$filterCat" : '')); exit;
}

/* FETCH categories  */
$galCats    = [];
$rc         = $conn->query("SELECT * FROM gallery_categories ORDER BY sort_order, name");
if ($rc) while ($c = $rc->fetch_assoc()) $galCats[] = $c;

/* category counts */
$catCounts = [];
$rc2 = $conn->query("SELECT category, COUNT(*) as cnt FROM gallery GROUP BY category");
if ($rc2) while ($row = $rc2->fetch_assoc()) $catCounts[$row['category']] = $row['cnt'];
$totalCount = (int)($conn->query("SELECT COUNT(*) as cnt FROM gallery")->fetch_assoc()['cnt']);

/*  FETCH images  */
$images = [];
if ($view === 'gallery') {
    $where = $filterCat ? "WHERE g.category='" . $conn->real_escape_string($filterCat) . "'" : '';
    $r = $conn->query("SELECT g.*, gc.name as cat_name
        FROM gallery g LEFT JOIN gallery_categories gc ON gc.slug=g.category
        $where ORDER BY g.sort_order ASC, g.id DESC");
    if ($r) while ($row = $r->fetch_assoc()) $images[] = $row;
}

/* upload errors  */
$uploadErrors = $_SESSION['upload_errors'] ?? [];
unset($_SESSION['upload_errors']);

/* cat list for categories view */
$catList = [];
if ($view === 'cats') {
    $r = $conn->query("SELECT gc.*, (SELECT COUNT(*) FROM gallery g WHERE g.category=gc.slug) as img_count
        FROM gallery_categories gc ORDER BY gc.sort_order, gc.name");
    if ($r) while ($row = $r->fetch_assoc()) $catList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Gallery | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.cat-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.cat-tab{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;color:var(--text-mid);background:#fff;border:1.5px solid var(--border);cursor:pointer;text-decoration:none;transition:all .2s}
.cat-tab:hover,.cat-tab.active{background:var(--teal);color:#fff;border-color:var(--teal)}
.cat-tab .ctbadge{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9px;background:rgba(255,255,255,.25);font-size:10px;font-weight:700;padding:0 4px}
.cat-tab:not(.active) .ctbadge{background:var(--teal-pale);color:var(--teal)}
/* upload */
.upload-zone{border:2px dashed var(--border);border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:#fafafa}
.upload-zone:hover,.upload-zone.drag-over{border-color:var(--teal);background:var(--teal-pale)}
.upload-zone i{font-size:34px;color:var(--teal);opacity:.5;margin-bottom:8px}
.upload-zone p{font-size:13px;color:var(--text-light);margin:0}
/* grid */
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}
.gallery-item{position:relative;border-radius:10px;overflow:hidden;background:#000;border:1px solid var(--border)}
.gallery-item img{width:100%;height:150px;object-fit:cover;display:block;transition:opacity .2s}
.gallery-item.inactive img{opacity:.35}
.gallery-overlay{position:absolute;inset:0;background:rgba(0,0,0,.55);opacity:0;transition:opacity .2s;display:flex;flex-direction:column;justify-content:space-between;padding:8px}
.gallery-item:hover .gallery-overlay{opacity:1}
.gal-actions{display:flex;gap:5px;justify-content:flex-end}
.gal-btn{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;font-size:11px;transition:background .2s;text-decoration:none}
.gal-edit{background:rgba(255,255,255,.2);color:#fff}.gal-edit:hover{background:#fff;color:var(--teal)}
.gal-del{background:rgba(231,76,60,.35);color:#fff}.gal-del:hover{background:var(--red);color:#fff}
.gal-toggle{background:rgba(255,255,255,.2);color:#fff}.gal-toggle:hover{background:#fff;color:var(--text-dark)}
.gal-caption{font-size:11px;color:rgba(255,255,255,.85);line-height:1.3;margin-top:auto}
.sel-check{position:absolute;top:7px;left:7px;width:17px;height:17px;accent-color:var(--teal);cursor:pointer;display:none}
.select-mode .sel-check{display:block}
.hidden-badge{position:absolute;top:7px;right:7px;background:rgba(0,0,0,.55);color:#fff;font-size:10px;padding:2px 6px;border-radius:4px}
/* cat manager split */
.split-layout{display:grid;grid-template-columns:340px 1fr;gap:22px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.form-sec-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:14px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:14px}
.form-sec-body{padding:20px;display:flex;flex-direction:column;gap:13px}
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.cat-count{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--teal-pale);color:var(--teal);font-size:11px;font-weight:700}
.act-btn{width:30px;height:30px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.edit-mode .form-sec-head{background:var(--gold-pale)}
.edit-mode .form-sec-head h3,.edit-mode .form-sec-head i{color:#a8782a}
/* modals */
.modal-back{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;align-items:center;justify-content:center}
.modal-box{background:#fff;border-radius:16px;width:90%;max-width:500px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-hdr{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-hdr h3{font-size:14px;font-weight:700;margin:0;color:var(--text-dark)}
.modal-bdy{padding:20px;display:flex;flex-direction:column;gap:13px}
.modal-img-prev{width:100%;height:190px;object-fit:cover;border-radius:8px}
.modal-ftr{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
@media(max-width:860px){.split-layout{grid-template-columns:1fr}}
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
        <div class="topbar-title"><?= $view === 'cats' ? 'Gallery Categories' : 'Gallery' ?></div>
        <div class="topbar-breadcrumb">Media / <?= $view === 'cats' ? 'Categories' : 'Gallery' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($view === 'gallery'): ?>
        <a href="gallery.php?view=cats" class="btn btn-outline btn-sm"><i class="fas fa-folder"></i> Categories</a>
        <button id="selectModeBtn" class="btn btn-outline btn-sm" onclick="toggleSelectMode()">
          <i class="fas fa-check-square"></i> Select
        </button>
        <button id="selectAllBtn" class="btn btn-outline btn-sm" style="display:none" onclick="selectAll()">
          <i class="fas fa-check-double"></i> Select All
        </button>
        <button id="bulkDeleteBtn" class="btn btn-sm" style="background:var(--red);color:#fff;display:none" onclick="bulkDelete()">
          <i class="fas fa-trash"></i> Delete (<span id="selCount">0</span>)
        </button>
      <?php else: ?>
        <a href="gallery.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Gallery</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <!-- ALERTS -->
      <?php
      $alerts = [
        'deleted'      => ['warning', 'Image deleted.'],
        'bulk_deleted' => ['warning', 'Selected images deleted.'],
        'updated'      => ['success', 'Image updated.'],
        'cat_added'    => ['success', 'Category added successfully.'],
        'cat_updated'  => ['success', 'Category updated successfully.'],
        'cat_deleted'  => ['warning', 'Category deleted.'],
        'cat_inuse'    => ['danger',  'Cannot delete — this category has images assigned to it.'],
      ];
      if (str_starts_with($msg, 'uploaded_')) {
          echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . (int)substr($msg,9) . ' image(s) uploaded successfully.</div>';
      } elseif (isset($alerts[$msg])) {
          [$type, $text] = $alerts[$msg];
          echo "<div class=\"alert alert-$type\"><i class=\"fas fa-" . ($type==='success'?'check-circle':($type==='danger'?'exclamation-circle':'trash')) . "\"></i> $text</div>";
      }
      if (!empty($catError)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($catError) ?></div>
      <?php endif;
      if ($uploadErrors): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Some uploads failed:<br><?= implode('<br>', array_map('htmlspecialchars', $uploadErrors)) ?></div>
      <?php endif; ?>

      <?php if ($view === 'cats'): ?>
      <!-- ══════════════ CATEGORIES MANAGER ══════════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Gallery Categories</h1>
          <p>Manage categories used to organise gallery photos</p>
        </div>
      </div>

      <div class="split-layout">
        <!-- Form -->
        <div class="form-section <?= $catEdit ? 'edit-mode' : '' ?>">
          <div class="form-sec-head">
            <i class="fas <?= $catEdit ? 'fa-pen' : 'fa-plus-circle' ?>"></i>
            <h3><?= $catEdit ? 'Edit Category' : 'Add New Category' ?></h3>
          </div>
          <div class="form-sec-body">
            <form method="POST">
              <input type="hidden" name="save_cat" value="1"/>
              <input type="hidden" name="cat_id" value="<?= $catEdit['id'] ?? 0 ?>"/>
              <div class="fgrp">
                <label>Category Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="cat_name" class="form-control" required
                       placeholder="e.g. Sigiriya"
                       value="<?= htmlspecialchars($catEdit['name'] ?? '') ?>"
                       oninput="autoSlug(this.value)"/>
              </div>
              <div class="fgrp">
                <label>Slug <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(auto-generated)</span></label>
                <input type="text" id="slugField" class="form-control"
                       style="background:var(--off-white);color:var(--text-light);cursor:default;font-family:monospace;font-size:13px"
                       value="<?= htmlspecialchars($catEdit['slug'] ?? '') ?>" readonly/>
              </div>
              <div class="fgrp">
                <label>Sort Order</label>
                <input type="number" name="cat_sort" class="form-control" min="0" max="255"
                       value="<?= (int)($catEdit['sort_order'] ?? 0) ?>"/>
              </div>
              <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                  <i class="fas <?= $catEdit ? 'fa-save' : 'fa-plus' ?>"></i>
                  <?= $catEdit ? 'Update Category' : 'Add Category' ?>
                </button>
                <?php if ($catEdit): ?>
                  <a href="gallery.php?view=cats" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- Table -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-folder"></i> Existing Categories</span>
            <span style="font-size:13px;color:var(--text-light)"><?= count($catList) ?> total</span>
          </div>
          <div class="table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Slug</th>
                  <th style="width:70px;text-align:center">Photos</th>
                  <th style="width:70px;text-align:center">Order</th>
                  <th style="width:90px;text-align:center">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($catList): foreach ($catList as $cat): ?>
              <tr <?= ($catEditId === (int)$cat['id']) ? 'style="background:var(--gold-pale)"' : '' ?>>
                <td><div class="col-title"><?= htmlspecialchars($cat['name']) ?></div></td>
                <td><span style="font-size:12px;color:var(--text-light);font-family:monospace"><?= htmlspecialchars($cat['slug']) ?></span></td>
                <td style="text-align:center"><span class="cat-count"><?= $cat['img_count'] ?></span></td>
                <td style="text-align:center;color:var(--text-light);font-size:13px"><?= (int)$cat['sort_order'] ?></td>
                <td style="text-align:center">
                  <div style="display:flex;gap:6px;justify-content:center">
                    <a href="gallery.php?view=cats&edit=<?= $cat['id'] ?>" class="act-btn btn-edit" title="Edit"><i class="fas fa-pen"></i></a>
                    <button onclick="confirmCatDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', <?= $cat['img_count'] ?>)"
                            class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="5">
                <div style="text-align:center;padding:30px;color:var(--text-light)">
                  <i class="fas fa-folder" style="font-size:30px;opacity:.2;display:block;margin-bottom:8px"></i>
                  <p>No categories yet.</p>
                </div>
              </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- ══════════════ GALLERY ══════════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Photo Gallery <span style="font-size:14px;font-weight:400;color:var(--text-light)">(<?= $totalCount ?> total)</span></h1>
          <p>Upload and manage gallery photos</p>
        </div>
      </div>

      <!-- UPLOAD -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-upload"></i> Upload Photos</span>
        </div>
        <div style="padding:20px">
          <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="upload" value="1"/>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 80px auto;gap:12px;align-items:end">
              <div class="fgrp">
                <label>Category</label>
                <select name="category" class="form-control">
                  <?php foreach ($galCats as $gc): ?>
                    <option value="<?= htmlspecialchars($gc['slug']) ?>" <?= $filterCat===$gc['slug'] ? 'selected':'' ?>>
                      <?= htmlspecialchars($gc['name']) ?>
                    </option>
                  <?php endforeach; ?>
                  <?php if (!$galCats): ?>
                    <option value="misc">Misc</option>
                  <?php endif; ?>
                </select>
              </div>
              <div class="fgrp">
                <label>Caption</label>
                <input type="text" name="caption" class="form-control" placeholder="Optional…"/>
              </div>
              <div class="fgrp">
                <label>Alt Text</label>
                <input type="text" name="alt_text" class="form-control" placeholder="For accessibility…"/>
              </div>
              <div class="fgrp">
                <label>Sort</label>
                <input type="number" name="sort_order" class="form-control" value="0" min="0"/>
              </div>
              <div class="fgrp">
                <label>&nbsp;</label>
                <label class="btn btn-primary" style="cursor:pointer;white-space:nowrap">
                  <i class="fas fa-plus"></i> Choose &amp; Upload
                  <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display:none" onchange="this.form.submit()"/>
                </label>
              </div>
            </div>
            <div class="upload-zone" id="dropZone" style="margin-top:14px" onclick="document.getElementById('fileInput').click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Drag &amp; drop photos here, or <strong style="color:var(--teal)">click to browse</strong></p>
              <p style="font-size:11px;margin-top:4px">JPG, PNG, WebP · Max 25 MB each · Multiple files supported</p>
            </div>
          </form>
        </div>
      </div>

      <!-- FILTER TABS -->
      <div class="cat-tabs">
        <a href="gallery.php" class="cat-tab <?= !$filterCat ? 'active' : '' ?>">
          <i class="fas fa-images"></i> All <span class="ctbadge"><?= $totalCount ?></span>
        </a>
        <?php foreach ($galCats as $gc): ?>
          <a href="gallery.php?cat=<?= htmlspecialchars($gc['slug']) ?>" class="cat-tab <?= $filterCat===$gc['slug'] ? 'active':'' ?>">
            <?= htmlspecialchars($gc['name']) ?>
            <span class="ctbadge"><?= $catCounts[$gc['slug']] ?? 0 ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- GRID -->
      <?php if ($images): ?>
      <form method="POST" id="bulkForm">
        <input type="hidden" name="bulk_delete" value="1"/>
        <div class="gallery-grid" id="galleryGrid">
          <?php foreach ($images as $img): ?>
          <div class="gallery-item <?= $img['is_active'] ? '' : 'inactive' ?>">
            <input type="checkbox" name="selected[]" value="<?= $img['id'] ?>" class="sel-check" onchange="updateSelCount()"/>
            <img src="<?= SITE_URL . '/' . htmlspecialchars($img['filename']) ?>" alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"/>
            <?php if (!$img['is_active']): ?>
              <span class="hidden-badge"><i class="fas fa-eye-slash"></i> Hidden</span>
            <?php endif; ?>
            <div class="gallery-overlay">
              <div class="gal-actions">
                <button type="button" class="gal-btn gal-edit" title="Edit"
                        onclick="openEdit(<?= $img['id'] ?>,'<?= htmlspecialchars(addslashes($img['caption']??'')) ?>','<?= htmlspecialchars(addslashes($img['alt_text']??'')) ?>','<?= htmlspecialchars($img['category']??'') ?>',<?= $img['sort_order'] ?>,'<?= SITE_URL.'/'.htmlspecialchars($img['filename']) ?>')">
                  <i class="fas fa-pen"></i>
                </button>
                <a href="gallery.php?toggle=<?= $img['id'] ?>" class="gal-btn gal-toggle" title="<?= $img['is_active']?'Hide':'Show' ?>">
                  <i class="fas <?= $img['is_active']?'fa-eye-slash':'fa-eye' ?>"></i>
                </a>
                <button type="button" class="gal-btn gal-del" title="Delete" onclick="confirmDelete(<?= $img['id'] ?>)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
              <?php if ($img['caption']): ?>
                <div class="gal-caption"><?= htmlspecialchars($img['caption']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </form>
      <?php else: ?>
      <div style="text-align:center;padding:60px;color:var(--text-light)">
        <i class="fas fa-images" style="font-size:48px;opacity:.15;display:block;margin-bottom:14px"></i>
        <p><?= $filterCat ? 'No images in this category.' : 'No images yet. Upload your first photo above.' ?></p>
      </div>
      <?php endif; ?>

      <?php endif; /* end view=gallery */ ?>

    </div>
  </div>
</div>
</div>

<!-- EDIT IMAGE MODAL -->
<div class="modal-back" id="editModal">
  <div class="modal-box">
    <div class="modal-hdr">
      <h3><i class="fas fa-pen" style="color:var(--teal);margin-right:8px"></i> Edit Image</h3>
      <button onclick="closeEdit()" style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text-light)"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="update_item" value="1"/>
      <input type="hidden" name="item_id" id="editItemId"/>
      <div class="modal-bdy">
        <img id="editPreview" src="" alt="" class="modal-img-prev"/>
        <div class="fgrp">
          <label>Category</label>
          <select name="category" id="editCat" class="form-control">
            <?php foreach ($galCats as $gc): ?>
              <option value="<?= htmlspecialchars($gc['slug']) ?>"><?= htmlspecialchars($gc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fgrp">
          <label>Caption</label>
          <input type="text" name="caption" id="editCap" class="form-control" placeholder="Caption…"/>
        </div>
        <div class="fgrp">
          <label>Alt Text</label>
          <input type="text" name="alt_text" id="editAlt" class="form-control" placeholder="Descriptive alt text…"/>
        </div>
        <div class="fgrp">
          <label>Sort Order</label>
          <input type="number" name="sort_order" id="editSort" class="form-control" min="0"/>
        </div>
      </div>
      <div class="modal-ftr">
        <button type="button" onclick="closeEdit()" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE IMAGE MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Image?</h3>
    <p style="color:var(--text-light);font-size:13px;margin-bottom:24px">This will permanently delete the image file.</p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delImgBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<!-- DELETE CATEGORY MODAL -->
<div id="catDeleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-folder"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Category?</h3>
    <p id="catDelText" style="color:var(--text-light);font-size:13px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('catDeleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="catDelBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
/* drag & drop */
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
if (dropZone) {
  dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('drag-over'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    fileInput.files = e.dataTransfer.files;
    document.getElementById('uploadForm').submit();
  });
}

/* select mode */
let selMode = false;
function toggleSelectMode() {
  selMode = !selMode;
  document.getElementById('galleryGrid')?.classList.toggle('select-mode', selMode);
  document.getElementById('selectModeBtn').innerHTML = selMode
    ? '<i class="fas fa-times"></i> Cancel'
    : '<i class="fas fa-check-square"></i> Select';
  document.getElementById('selectAllBtn').style.display = selMode ? '' : 'none';
  if (!selMode) {
    document.querySelectorAll('.sel-check').forEach(c => c.checked = false);
    updateSelCount();
  }
}
function selectAll() {
  const checks = document.querySelectorAll('.sel-check');
  const allChecked = [...checks].every(c => c.checked);
  checks.forEach(c => c.checked = !allChecked);
  document.getElementById('selectAllBtn').innerHTML = allChecked
    ? '<i class="fas fa-check-double"></i> Select All'
    : '<i class="fas fa-times-circle"></i> Deselect All';
  updateSelCount();
}
function updateSelCount() {
  const n = document.querySelectorAll('.sel-check:checked').length;
  const b = document.getElementById('bulkDeleteBtn');
  if (b) { document.getElementById('selCount').textContent = n; b.style.display = n > 0 ? '' : 'none'; }
}
function bulkDelete() {
  const n = document.querySelectorAll('.sel-check:checked').length;
  if (n && confirm('Delete ' + n + ' image(s)? This cannot be undone.')) document.getElementById('bulkForm').submit();
}

/* edit modal */
function openEdit(id, cap, alt, cat, sort, src) {
  document.getElementById('editItemId').value = id;
  document.getElementById('editCap').value    = cap;
  document.getElementById('editAlt').value    = alt;
  document.getElementById('editSort').value   = sort;
  document.getElementById('editPreview').src  = src;
  document.getElementById('editCat').value    = cat;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
document.getElementById('editModal')?.addEventListener('click', e => { if (e.target === document.getElementById('editModal')) closeEdit(); });

/* delete image */
function confirmDelete(id) {
  document.getElementById('delImgBtn').href = 'gallery.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) { if (e.target===this) this.style.display='none'; });

/* delete category */
function confirmCatDelete(id, name, cnt) {
  let msg = 'Delete category "' + name + '"?';
  if (cnt > 0) msg += ' It has ' + cnt + ' photo(s) — they will become uncategorised.';
  msg += ' This cannot be undone.';
  document.getElementById('catDelText').textContent = msg;
  document.getElementById('catDelBtn').href = 'gallery.php?view=cats&delete_cat=' + id;
  document.getElementById('catDeleteModal').style.display = 'flex';
}
document.getElementById('catDeleteModal')?.addEventListener('click', function(e) { if (e.target===this) this.style.display='none'; });

/* slug auto */
function autoSlug(val) {
  const f = document.getElementById('slugField');
  if (f) f.value = val.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
</script>
</body>
</html>
