<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'why-us';
$editItem   = null;
$formError  = '';
$msg        = $_GET['msg'] ?? '';
$editId     = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM why_us WHERE id=$id");
    header('Location: why-us.php?msg=deleted'); exit;
}

/* ── TOGGLE ACTIVE ── */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE why_us SET is_active = 1 - is_active WHERE id=$id");
    header('Location: why-us.php'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId     = (int)($_POST['item_id']    ?? 0);
    $title      = trim($_POST['title']       ?? '');
    $description= trim($_POST['description'] ?? '');
    $icon       = trim($_POST['icon']        ?? 'fas fa-star');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active'])  ? 1 : 0;

    if (!$title) {
        $formError = 'Title is required.';
        $editId    = $postId;
    } else {
        $titleE = $conn->real_escape_string($title);
        $descE  = $conn->real_escape_string($description);
        $iconE  = $conn->real_escape_string($icon);

        if ($postId) {
            $conn->query("UPDATE why_us SET
                title='$titleE', description='$descE', icon='$iconE',
                sort_order=$sort_order, is_active=$is_active
                WHERE id=$postId");
            header('Location: why-us.php?msg=updated'); exit;
        } else {
            $conn->query("INSERT INTO why_us (title, description, icon, sort_order, is_active)
                VALUES ('$titleE','$descE','$iconE',$sort_order,$is_active)");
            header('Location: why-us.php?msg=added'); exit;
        }
    }
}

/* ── FETCH FOR EDIT ── */
if ($editId) {
    $r        = $conn->query("SELECT * FROM why_us WHERE id=$editId");
    $editItem = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH ALL ── */
$items = [];
$r     = $conn->query("SELECT * FROM why_us ORDER BY sort_order ASC, id ASC");
if ($r) while ($row = $r->fetch_assoc()) $items[] = $row;

$v = $editItem ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Why Choose Us | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.split-layout{display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.form-sec-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:14px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:14px}
.form-sec-body{padding:20px;display:flex;flex-direction:column;gap:13px}
.frow{display:grid;gap:13px}
.frow.c2{grid-template-columns:1fr 1fr}
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.icon-preview{display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--off-white);border-radius:10px}
.icon-preview-box{width:48px;height:48px;border-radius:12px;background:#fff;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--teal);flex-shrink:0}
.icon-preview-info{display:flex;flex-direction:column;gap:2px}
.icon-preview-class{font-size:12px;font-family:monospace;color:var(--text-mid)}
.icon-preview-hint{font-size:11px;color:var(--text-light)}
.check-row{display:flex;align-items:center;gap:10px;padding:9px 13px;background:var(--off-white);border-radius:10px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal)}
.check-row span{font-size:13px;color:var(--text-dark);font-weight:500}
/* why-us cards */
.whyus-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.whyus-card{background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:22px 18px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;position:relative;transition:border-color .2s,box-shadow .2s}
.whyus-card:hover{border-color:var(--teal);box-shadow:0 4px 20px rgba(15,82,82,.08)}
.whyus-card.inactive{opacity:.5}
.whyus-icon-box{width:64px;height:64px;border-radius:16px;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--teal)}
.whyus-title{font-size:14px;font-weight:700;color:var(--text-dark);line-height:1.3}
.whyus-desc{font-size:12px;color:var(--text-light);line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.whyus-meta{font-size:11px;color:#bbb;margin-top:auto}
.card-actions{display:flex;gap:6px;margin-top:6px}
.act-btn{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.btn-tog{background:var(--off-white);color:var(--text-light)}.btn-tog:hover{background:var(--text-light);color:#fff}
.edit-mode .form-sec-head{background:var(--gold-pale)}
.edit-mode .form-sec-head h3,.edit-mode .form-sec-head i{color:#a8782a}
.icon-suggestions{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.icon-sug-btn{width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--text-mid);transition:all .15s}
.icon-sug-btn:hover{border-color:var(--teal);color:var(--teal);background:var(--teal-pale)}
.icon-sug-btn.selected{border-color:var(--teal);color:var(--teal);background:var(--teal-pale)}
@media(max-width:960px){.split-layout{grid-template-columns:1fr}.frow.c2{grid-template-columns:1fr}}
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
        <div class="topbar-title">Why Choose Us</div>
        <div class="topbar-breadcrumb">Content / Why Choose Us</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Feature added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Feature updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Feature deleted.</div><?php endif; ?>
      <?php if ($formError):         ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div><?php endif; ?>

      <div class="page-header">
        <div class="page-header-left">
          <h1>Why Choose Us</h1>
          <p>Manage the feature cards shown in the "Why Choose Us" section on the homepage</p>
        </div>
      </div>

      <div class="split-layout">

        <!-- ── FORM ── -->
        <div class="form-section <?= $editItem ? 'edit-mode' : '' ?>">
          <div class="form-sec-head">
            <i class="fas <?= $editItem ? 'fa-pen' : 'fa-plus-circle' ?>"></i>
            <h3><?= $editItem ? 'Edit Feature' : 'Add New Feature' ?></h3>
          </div>
          <div class="form-sec-body">
            <form method="POST">
              <input type="hidden" name="item_id" value="<?= $v['id'] ?? 0 ?>"/>

              <!-- Title -->
              <div class="fgrp">
                <label>Title <span style="color:var(--red)">*</span></label>
                <input type="text" name="title" class="form-control" required
                       placeholder="e.g. Experienced Local Guides"
                       value="<?= htmlspecialchars($v['title'] ?? '') ?>"/>
              </div>

              <!-- Description -->
              <div class="fgrp">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="A short description of this benefit…"><?= htmlspecialchars($v['description'] ?? '') ?></textarea>
              </div>

              <!-- Icon -->
              <div class="fgrp">
                <label>Font Awesome Icon Class</label>
                <input type="text" name="icon" id="iconField" class="form-control"
                       placeholder="fas fa-star"
                       value="<?= htmlspecialchars($v['icon'] ?? 'fas fa-star') ?>"
                       oninput="updateIconPreview(this.value)"/>
                <div style="margin-top:4px">
                  <div style="font-size:11px;color:var(--text-light);margin-bottom:6px">Quick pick:</div>
                  <div class="icon-suggestions">
                    <?php
                    $suggestions = [
                      'fas fa-star','fas fa-award','fas fa-shield-alt','fas fa-map-marked-alt',
                      'fas fa-users','fas fa-leaf','fas fa-heart','fas fa-compass',
                      'fas fa-camera','fas fa-thumbs-up','fas fa-globe-asia','fas fa-clock',
                      'fas fa-headset','fas fa-dollar-sign','fas fa-check-circle','fas fa-route',
                    ];
                    $currentIcon = $v['icon'] ?? 'fas fa-star';
                    foreach ($suggestions as $ic): ?>
                      <button type="button" class="icon-sug-btn <?= $currentIcon === $ic ? 'selected' : '' ?>"
                              title="<?= $ic ?>" onclick="pickIcon('<?= $ic ?>')">
                        <i class="<?= $ic ?>"></i>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </div>
                <!-- Live preview -->
                <div class="icon-preview" style="margin-top:8px">
                  <div class="icon-preview-box">
                    <i id="iconPrev" class="<?= htmlspecialchars($currentIcon) ?>"></i>
                  </div>
                  <div class="icon-preview-info">
                    <div class="icon-preview-class" id="iconPrevClass"><?= htmlspecialchars($currentIcon) ?></div>
                    <div class="icon-preview-hint">Live icon preview</div>
                  </div>
                </div>
              </div>

              <!-- Sort + Active -->
              <div class="frow c2">
                <div class="fgrp">
                  <label>Sort Order</label>
                  <input type="number" name="sort_order" class="form-control"
                         min="0" value="<?= (int)($v['sort_order'] ?? 0) ?>"/>
                </div>
                <div class="fgrp" style="justify-content:flex-end;padding-top:18px">
                  <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" <?= ($v['is_active'] ?? 1) ? 'checked' : '' ?>/>
                    <i class="fas fa-eye" style="color:var(--teal)"></i>
                    <span>Active</span>
                  </label>
                </div>
              </div>

              <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                  <i class="fas <?= $editItem ? 'fa-save' : 'fa-plus' ?>"></i>
                  <?= $editItem ? 'Update Feature' : 'Add Feature' ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="why-us.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- ── CARDS ── -->
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h3 style="font-size:15px;font-weight:700;color:var(--text-dark);margin:0">
              <?= count($items) ?> Feature<?= count($items) != 1 ? 's' : '' ?>
            </h3>
          </div>

          <?php if ($items): ?>
          <div class="whyus-grid">
            <?php foreach ($items as $item): ?>
            <div class="whyus-card <?= $item['is_active'] ? '' : 'inactive' ?>">

              <div class="whyus-icon-box">
                <i class="<?= htmlspecialchars($item['icon'] ?? 'fas fa-star') ?>"></i>
              </div>

              <div class="whyus-title"><?= htmlspecialchars($item['title']) ?></div>

              <?php if ($item['description']): ?>
                <div class="whyus-desc"><?= htmlspecialchars($item['description']) ?></div>
              <?php endif; ?>

              <div class="whyus-meta">
                Order: <?= (int)$item['sort_order'] ?>
                <?php if (!$item['is_active']): ?>
                  &nbsp;·&nbsp;<span style="color:var(--red)"><i class="fas fa-eye-slash"></i> Hidden</span>
                <?php endif; ?>
              </div>

              <div class="card-actions">
                <a href="why-us.php?toggle=<?= $item['id'] ?>" class="act-btn btn-tog"
                   title="<?= $item['is_active'] ? 'Hide' : 'Show' ?>">
                  <i class="fas <?= $item['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                </a>
                <a href="why-us.php?edit=<?= $item['id'] ?>" class="act-btn btn-edit" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['title'])) ?>')"
                        class="act-btn btn-del" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div style="text-align:center;padding:60px;color:var(--text-light);background:#fff;border:1px solid var(--border);border-radius:var(--radius)">
            <i class="fas fa-award" style="font-size:48px;opacity:.1;display:block;margin-bottom:14px"></i>
            <p style="font-size:14px">No features yet. Add your first one using the form.</p>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Feature?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function updateIconPreview(cls) {
  const icon = document.getElementById('iconPrev');
  const label = document.getElementById('iconPrevClass');
  icon.className = cls || 'fas fa-star';
  label.textContent = cls || 'fas fa-star';
  // highlight matching suggestion button
  document.querySelectorAll('.icon-sug-btn').forEach(btn => {
    btn.classList.toggle('selected', btn.title === cls);
  });
}

function pickIcon(cls) {
  document.getElementById('iconField').value = cls;
  updateIconPreview(cls);
}

function confirmDelete(id, name) {
  document.getElementById('delModalText').textContent = 'Delete "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'why-us.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
