<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'destinations';
$editItem   = null;
$formError  = '';
$msg        = $_GET['msg'] ?? '';
$editId     = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

/* ── SAFE MIGRATIONS ── */
$conn->query("CREATE TABLE IF NOT EXISTS destination_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  icon VARCHAR(100) DEFAULT 'fas fa-map-marker-alt',
  sort_order SMALLINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("ALTER TABLE destinations ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL");

/* Seed defaults if table is empty */
$seedCount = $conn->query("SELECT COUNT(*) AS cnt FROM destination_categories")->fetch_assoc()['cnt'];
if ($seedCount == 0) {
    $conn->query("INSERT IGNORE INTO destination_categories (name, slug, icon, sort_order) VALUES
        ('Nature & Adventure', 'nature', 'fas fa-tree', 0),
        ('Cultural Heritage', 'culture', 'fas fa-mosque', 1),
        ('Beach & Coast', 'beach', 'fas fa-umbrella-beach', 2),
        ('Safari & Wildlife', 'wildlife', 'fas fa-paw', 3),
        ('Hill Country', 'hill', 'fas fa-mountain', 4)");
}

/* Auto-migrate destinations that still use the old category slug column */
$conn->query("UPDATE destinations d
    INNER JOIN destination_categories dc ON dc.slug = d.category
    SET d.category_id = dc.id
    WHERE d.category_id IS NULL AND d.category IS NOT NULL AND d.category != ''");

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $used = $conn->query("SELECT COUNT(*) as cnt FROM destinations WHERE category_id=$id")->fetch_assoc()['cnt'];
    if ($used > 0) {
        header('Location: dest-cats.php?msg=inuse'); exit;
    }
    $conn->query("DELETE FROM destination_categories WHERE id=$id");
    header('Location: dest-cats.php?msg=deleted'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId     = (int)($_POST['cat_id']     ?? 0);
    $name       = trim($_POST['name']        ?? '');
    $icon       = trim($_POST['icon']        ?? 'fas fa-map-marker-alt');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (!$name) {
        $formError = 'Category name is required.';
        $editId    = $postId;
    } else {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $sc   = $conn->prepare("SELECT id FROM destination_categories WHERE slug=? AND id!=?");
        $sc->bind_param('si', $slug, $postId);
        $sc->execute();
        if ($sc->get_result()->num_rows) $slug .= '-' . time();
        $sc->close();

        if (!$formError) {
            $nameE = $conn->real_escape_string($name);
            $slugE = $conn->real_escape_string($slug);
            $iconE = $conn->real_escape_string($icon);

            if ($postId) {
                $conn->query("UPDATE destination_categories SET
                    name='$nameE', slug='$slugE', icon='$iconE', sort_order=$sort_order
                    WHERE id=$postId");
                header('Location: dest-cats.php?msg=updated'); exit;
            } else {
                $conn->query("INSERT INTO destination_categories (name, slug, icon, sort_order)
                    VALUES ('$nameE','$slugE','$iconE',$sort_order)");
                header('Location: dest-cats.php?msg=added'); exit;
            }
        }
    }
}

/* ── FETCH FOR EDIT ── */
if ($editId) {
    $r        = $conn->query("SELECT * FROM destination_categories WHERE id=$editId");
    $editItem = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH ALL ── */
$items = [];
$r = $conn->query("SELECT dc.*, (SELECT COUNT(*) FROM destinations d WHERE d.category_id=dc.id) AS dest_count
    FROM destination_categories dc ORDER BY dc.sort_order ASC, dc.name ASC");
if ($r) while ($row = $r->fetch_assoc()) $items[] = $row;

$v = $editItem ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Destination Categories | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.split-layout{display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.form-sec-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:14px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:14px}
.form-sec-body{padding:20px;display:flex;flex-direction:column;gap:13px}
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.icon-preview-row{display:flex;align-items:center;gap:12px;padding:10px 13px;background:var(--off-white);border-radius:10px}
.icon-preview-box{width:42px;height:42px;border-radius:10px;background:#fff;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--teal);flex-shrink:0}
.icon-sug-wrap{display:flex;flex-wrap:wrap;gap:5px;margin-top:5px}
.icon-sug-btn{width:30px;height:30px;border-radius:7px;border:1.5px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text-mid);transition:all .15s}
.icon-sug-btn:hover,.icon-sug-btn.selected{border-color:var(--teal);color:var(--teal);background:var(--teal-pale)}
/* category cards */
.cats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}
.cat-card{background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;transition:border-color .2s,box-shadow .2s}
.cat-card:hover{border-color:var(--teal);box-shadow:0 4px 20px rgba(15,82,82,.08)}
.cat-card-icon{width:100%;height:90px;display:flex;align-items:center;justify-content:center;font-size:36px;color:var(--teal);background:var(--teal-pale)}
.cat-card-body{padding:14px 14px 10px}
.cat-card-name{font-size:13px;font-weight:700;color:var(--text-dark);margin-bottom:3px}
.cat-card-meta{font-size:11px;color:#bbb;margin-top:6px;display:flex;align-items:center;gap:6px}
.cat-card-footer{padding:8px 14px;border-top:1px solid var(--border);display:flex;gap:6px;justify-content:flex-end}
.act-btn{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.edit-mode .form-sec-head{background:var(--gold-pale)}
.edit-mode .form-sec-head h3,.edit-mode .form-sec-head i{color:#a8782a}
.dest-count-badge{display:inline-flex;align-items:center;gap:4px;background:var(--teal-pale);color:var(--teal);font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px}
.page-tabs{display:flex;gap:2px;margin-bottom:22px;border-bottom:2px solid var(--border)}
.page-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text-mid);text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;border-bottom:none;margin-bottom:-2px;display:inline-flex;align-items:center;gap:7px;transition:color .15s,background .15s}
.page-tab:hover{color:var(--teal);background:var(--off-white)}
.page-tab.active{color:var(--teal);background:#fff;border-color:var(--border);border-bottom-color:#fff}
@media(max-width:960px){.split-layout{grid-template-columns:1fr}}
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
        <div class="topbar-title">Destination Categories</div>
        <div class="topbar-breadcrumb">Destinations / Categories</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <div class="page-tabs">
        <a href="destinations.php" class="page-tab"><i class="fas fa-map-marker-alt"></i> Destinations</a>
        <a href="dest-cats.php" class="page-tab active"><i class="fas fa-tags"></i> Categories</a>
      </div>

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Category added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Category updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Category deleted.</div><?php endif; ?>
      <?php if ($msg === 'inuse'):   ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Cannot delete — this category has destinations assigned to it.</div><?php endif; ?>
      <?php if ($formError):         ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div><?php endif; ?>

      <div class="page-header">
        <div class="page-header-left">
          <h1>Destination Categories</h1>
          <p>Manage the categories used to organise destination guides</p>
        </div>
      </div>

      <div class="split-layout">

        <!-- ── FORM ── -->
        <div class="form-section <?= $editItem ? 'edit-mode' : '' ?>">
          <div class="form-sec-head">
            <i class="fas <?= $editItem ? 'fa-pen' : 'fa-plus-circle' ?>"></i>
            <h3><?= $editItem ? 'Edit Category' : 'Add New Category' ?></h3>
          </div>
          <div class="form-sec-body">
            <form method="POST">
              <input type="hidden" name="cat_id" value="<?= $v['id'] ?? 0 ?>"/>

              <div class="fgrp">
                <label>Category Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" required
                       placeholder="e.g. Beach & Coast"
                       value="<?= htmlspecialchars($v['name'] ?? '') ?>"
                       oninput="autoSlug(this.value)"/>
              </div>

              <div class="fgrp">
                <label>Slug <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(auto-generated)</span></label>
                <input type="text" id="slugField" class="form-control"
                       style="background:var(--off-white);color:var(--text-light);font-family:monospace;font-size:12px"
                       value="<?= htmlspecialchars($v['slug'] ?? '') ?>" readonly/>
              </div>

              <!-- Icon -->
              <div class="fgrp">
                <label>Font Awesome Icon</label>
                <input type="text" name="icon" id="iconField" class="form-control"
                       placeholder="fas fa-map-marker-alt"
                       value="<?= htmlspecialchars($v['icon'] ?? 'fas fa-map-marker-alt') ?>"
                       oninput="updateIconPrev(this.value)"/>
                <div style="margin-top:4px">
                  <div style="font-size:11px;color:var(--text-light);margin-bottom:5px">Quick pick:</div>
                  <div class="icon-sug-wrap">
                    <?php
                    $suggestions = [
                      'fas fa-map-marker-alt','fas fa-tree','fas fa-umbrella-beach','fas fa-paw',
                      'fas fa-mountain','fas fa-mosque','fas fa-water','fas fa-camera',
                      'fas fa-hiking','fas fa-leaf','fas fa-fish','fas fa-binoculars',
                      'fas fa-sun','fas fa-globe-asia','fas fa-landmark','fas fa-ship',
                    ];
                    $currentIcon = $v['icon'] ?? 'fas fa-map-marker-alt';
                    foreach ($suggestions as $ic): ?>
                      <button type="button" class="icon-sug-btn <?= $currentIcon === $ic ? 'selected' : '' ?>"
                              title="<?= $ic ?>" onclick="pickIcon('<?= $ic ?>')">
                        <i class="<?= $ic ?>"></i>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="icon-preview-row" style="margin-top:6px">
                  <div class="icon-preview-box">
                    <i id="iconPrev" class="<?= htmlspecialchars($currentIcon) ?>"></i>
                  </div>
                  <div>
                    <div style="font-size:12px;font-family:monospace;color:var(--text-mid)" id="iconPrevLabel"><?= htmlspecialchars($currentIcon) ?></div>
                    <div style="font-size:11px;color:var(--text-light)">Live preview</div>
                  </div>
                </div>
              </div>

              <!-- Sort order -->
              <div class="fgrp">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control"
                       min="0" max="255" value="<?= (int)($v['sort_order'] ?? 0) ?>"/>
              </div>

              <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                  <i class="fas <?= $editItem ? 'fa-save' : 'fa-plus' ?>"></i>
                  <?= $editItem ? 'Update Category' : 'Add Category' ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="dest-cats.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- ── CARDS ── -->
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h3 style="font-size:15px;font-weight:700;color:var(--text-dark);margin:0">
              <?= count($items) ?> Categor<?= count($items) != 1 ? 'ies' : 'y' ?>
            </h3>
          </div>

          <?php if ($items): ?>
          <div class="cats-grid">
            <?php foreach ($items as $item): ?>
            <div class="cat-card">
              <div class="cat-card-icon">
                <i class="<?= htmlspecialchars($item['icon'] ?? 'fas fa-map-marker-alt') ?>"></i>
              </div>
              <div class="cat-card-body">
                <div class="cat-card-name"><?= htmlspecialchars($item['name']) ?></div>
                <div style="font-size:11px;color:var(--text-light);font-family:monospace"><?= htmlspecialchars($item['slug']) ?></div>
                <div class="cat-card-meta">
                  <span class="dest-count-badge">
                    <i class="fas fa-map-marker-alt" style="font-size:10px"></i>
                    <?= (int)$item['dest_count'] ?> dest<?= $item['dest_count'] != 1 ? 's' : '' ?>
                  </span>
                  <span>· Order: <?= (int)$item['sort_order'] ?></span>
                </div>
              </div>
              <div class="cat-card-footer">
                <a href="dest-cats.php?edit=<?= $item['id'] ?>" class="act-btn btn-edit" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= (int)$item['dest_count'] ?>)"
                        class="act-btn btn-del" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div style="text-align:center;padding:60px;color:var(--text-light);background:#fff;border:1px solid var(--border);border-radius:var(--radius)">
            <i class="fas fa-tags" style="font-size:48px;opacity:.1;display:block;margin-bottom:14px"></i>
            <p style="font-size:14px">No categories yet. Add your first one using the form.</p>
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
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Category?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function autoSlug(val) {
  document.getElementById('slugField').value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

function updateIconPrev(cls) {
  document.getElementById('iconPrev').className   = cls || 'fas fa-map-marker-alt';
  document.getElementById('iconPrevLabel').textContent = cls || 'fas fa-map-marker-alt';
  document.querySelectorAll('.icon-sug-btn').forEach(b => b.classList.toggle('selected', b.title === cls));
}

function pickIcon(cls) {
  document.getElementById('iconField').value = cls;
  updateIconPrev(cls);
}

function confirmDelete(id, name, destCount) {
  let text = 'Delete "' + name + '"?';
  if (destCount > 0) text += ' ' + destCount + ' destination(s) assigned to it will become uncategorised.';
  text += ' This cannot be undone.';
  document.getElementById('delModalText').textContent = text;
  document.getElementById('delConfirmBtn').href = 'dest-cats.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
