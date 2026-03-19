<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'blog';
$editCat    = null;
$formError  = '';
$msg        = $_GET['msg'] ?? '';
$editId     = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

/* DELETE */
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $used = $conn->query("SELECT COUNT(*) as cnt FROM blog_posts WHERE category_id=$id")->fetch_assoc()['cnt'];
    if ($used > 0) {
        $msg = 'inuse';
    } else {
        $conn->query("DELETE FROM blog_categories WHERE id=$id");
        $msg = 'deleted';
    }
}

/* SAVE (add / edit) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']       ?? '');
    $color      = trim($_POST['color']      ?? '#0f5252');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $postId     = (int)($_POST['cat_id']    ?? 0);

    if (!$name) {
        $formError = 'Category name is required.';
        if ($postId) $editId = $postId;
    } else {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $sc   = $conn->prepare("SELECT id FROM blog_categories WHERE slug=? AND id!=?");
        $sc->bind_param('si', $slug, $postId);
        $sc->execute();
        if ($sc->get_result()->num_rows) $slug .= '-' . time();
        $sc->close();

        $nameE  = $conn->real_escape_string($name);
        $slugE  = $conn->real_escape_string($slug);
        $colorE = $conn->real_escape_string($color);

        if ($postId) {
            $conn->query("UPDATE blog_categories SET name='$nameE', slug='$slugE', color='$colorE', sort_order=$sort_order WHERE id=$postId");
            header('Location: blog-cats.php?msg=updated'); exit;
        } else {
            $conn->query("INSERT INTO blog_categories (name, slug, color, sort_order) VALUES ('$nameE','$slugE','$colorE',$sort_order)");
            header('Location: blog-cats.php?msg=added'); exit;
        }
    }
}

/* FETCH FOR EDIT */
if ($editId) {
    $r       = $conn->query("SELECT * FROM blog_categories WHERE id=$editId");
    $editCat = $r ? $r->fetch_assoc() : null;
}

/* FETCH ALL */
$cats = [];
$r    = $conn->query("SELECT bc.*, (SELECT COUNT(*) FROM blog_posts bp WHERE bp.category_id=bc.id) as post_count
    FROM blog_categories bc ORDER BY bc.sort_order, bc.name");
if ($r) while ($row = $r->fetch_assoc()) $cats[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Blog Categories | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.split-layout{display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.form-sec-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:15px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:15px}
.form-sec-body{padding:22px;display:flex;flex-direction:column;gap:16px}
.fgrp{display:flex;flex-direction:column;gap:6px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.8px;text-transform:uppercase}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.color-row{display:flex;align-items:center;gap:12px}
.color-row input[type=color]{width:44px;height:40px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;padding:2px;background:#fff}
.cat-count{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:var(--teal-pale);color:var(--teal);font-size:11px;font-weight:700}
.act-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.edit-mode .form-sec-head{background:var(--gold-pale)}
.edit-mode .form-sec-head h3,.edit-mode .form-sec-head i{color:#a8782a}
.color-swatch{width:18px;height:18px;border-radius:4px;display:inline-block;flex-shrink:0;border:1px solid rgba(0,0,0,.1)}
@media(max-width:900px){.split-layout{grid-template-columns:1fr}}
.page-tabs{display:flex;gap:2px;margin-bottom:22px;border-bottom:2px solid var(--border)}
.page-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text-mid);text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;border-bottom:none;margin-bottom:-2px;display:inline-flex;align-items:center;gap:7px;transition:color .15s,background .15s}
.page-tab:hover{color:var(--teal);background:var(--off-white)}
.page-tab.active{color:var(--teal);background:#fff;border-color:var(--border);border-bottom-color:#fff}
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
        <div class="topbar-title">Blog Categories</div>
        <div class="topbar-breadcrumb">Blog / Categories</div>
      </div>
    </div>
    <div class="topbar-right">
      <a href="blog.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Posts</a>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <div class="page-tabs">
        <a href="blog.php" class="page-tab"><i class="fas fa-pen-nib"></i> Posts</a>
        <a href="blog-cats.php" class="page-tab active"><i class="fas fa-folder"></i> Categories</a>
      </div>

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Category added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Category updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Category deleted.</div><?php endif; ?>
      <?php if ($msg === 'inuse'):   ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Cannot delete — this category is assigned to one or more posts.</div><?php endif; ?>
      <?php if ($formError):         ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div><?php endif; ?>

      <div class="page-header">
        <div class="page-header-left">
          <h1>Blog Categories</h1>
          <p>Manage categories used to organise blog posts</p>
        </div>
      </div>

      <div class="split-layout">

        <!-- ── ADD / EDIT FORM ── -->
        <div class="form-section <?= $editCat ? 'edit-mode' : '' ?>">
          <div class="form-sec-head">
            <i class="fas <?= $editCat ? 'fa-pen' : 'fa-plus-circle' ?>"></i>
            <h3><?= $editCat ? 'Edit Category' : 'Add New Category' ?></h3>
          </div>
          <div class="form-sec-body">
            <form method="POST" id="catForm">
              <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?? 0 ?>"/>

              <div class="fgrp">
                <label>Category Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" required
                       placeholder="e.g. Travel Tips"
                       value="<?= htmlspecialchars($editCat['name'] ?? '') ?>"
                       oninput="autoSlug(this.value)"/>
              </div>

              <div class="fgrp">
                <label>Slug <span style="color:var(--text-light);font-weight:400;text-transform:none;letter-spacing:0">(auto-generated)</span></label>
                <input type="text" id="slugField" class="form-control"
                       style="background:var(--off-white);color:var(--text-light);cursor:default;font-family:monospace;font-size:13px"
                       value="<?= htmlspecialchars($editCat['slug'] ?? '') ?>" readonly/>
              </div>

              <div class="fgrp">
                <label>Label Color</label>
                <div class="color-row">
                  <input type="color" name="color" id="colorPicker"
                         value="<?= htmlspecialchars($editCat['color'] ?? '#0f5252') ?>"/>
                  <input type="text" id="colorText" class="form-control"
                         value="<?= htmlspecialchars($editCat['color'] ?? '#0f5252') ?>"
                         placeholder="#0f5252" maxlength="7"
                         oninput="syncColorFromText(this.value)"/>
                </div>
              </div>

              <div class="fgrp">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control"
                       min="0" max="255" placeholder="0"
                       value="<?= (int)($editCat['sort_order'] ?? 0) ?>"/>
              </div>

              <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                  <i class="fas <?= $editCat ? 'fa-save' : 'fa-plus' ?>"></i>
                  <?= $editCat ? 'Update Category' : 'Add Category' ?>
                </button>
                <?php if ($editCat): ?>
                  <a href="blog-cats.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- ── EXISTING CATEGORIES TABLE ── -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-folder"></i> Existing Categories</span>
            <span style="font-size:13px;color:var(--text-light)"><?= count($cats) ?> total</span>
          </div>
          <div class="table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width:36px;text-align:center">Color</th>
                  <th>Name</th>
                  <th>Slug</th>
                  <th style="width:70px;text-align:center">Posts</th>
                  <th style="width:80px;text-align:center">Order</th>
                  <th style="width:90px;text-align:center">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($cats): foreach ($cats as $cat): ?>
              <tr <?= ($editId === (int)$cat['id']) ? 'style="background:var(--gold-pale)"' : '' ?>>
                <td style="text-align:center">
                  <span class="color-swatch" style="background:<?= htmlspecialchars($cat['color'] ?? '#0f5252') ?>"></span>
                </td>
                <td>
                  <div class="col-title"><?= htmlspecialchars($cat['name']) ?></div>
                </td>
                <td>
                  <span style="font-size:12px;color:var(--text-light);font-family:monospace"><?= htmlspecialchars($cat['slug']) ?></span>
                </td>
                <td style="text-align:center">
                  <span class="cat-count"><?= $cat['post_count'] ?></span>
                </td>
                <td style="text-align:center;color:var(--text-light);font-size:13px">
                  <?= (int)$cat['sort_order'] ?>
                </td>
                <td style="text-align:center">
                  <div style="display:flex;gap:6px;justify-content:center">
                    <a href="blog-cats.php?edit=<?= $cat['id'] ?>" class="act-btn btn-edit" title="Edit"><i class="fas fa-pen"></i></a>
                    <button onclick="confirmDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', <?= $cat['post_count'] ?>)"
                            class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="6">
                <div style="text-align:center;padding:40px;color:var(--text-light)">
                  <i class="fas fa-folder" style="font-size:36px;opacity:.2;display:block;margin-bottom:10px"></i>
                  <p>No categories yet. Add your first one.</p>
                </div>
              </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /split-layout -->
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
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Category?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13.5px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function autoSlug(val) {
  const slug = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  document.getElementById('slugField').value = slug;
}

const colorPicker = document.getElementById('colorPicker');
const colorText   = document.getElementById('colorText');

colorPicker.addEventListener('input', function() {
  colorText.value = this.value;
});

function syncColorFromText(val) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    colorPicker.value = val;
  }
}

function confirmDelete(id, name, postCount) {
  let msg = 'Are you sure you want to delete "' + name + '"?';
  if (postCount > 0) {
    msg += ' This category has ' + postCount + ' post(s) assigned to it — they will become uncategorised.';
  }
  msg += ' This cannot be undone.';
  document.getElementById('delModalText').textContent = msg;
  document.getElementById('delConfirmBtn').href = 'blog-cats.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
