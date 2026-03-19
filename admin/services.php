<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'services';
$editItem   = null;
$formError  = '';
$msg        = $_GET['msg'] ?? '';
$editId     = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

/* ── SAFE MIGRATION ── */
$conn->query("CREATE TABLE IF NOT EXISTS services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    label       VARCHAR(80)  DEFAULT NULL,
    description TEXT         DEFAULT NULL,
    image       VARCHAR(300) DEFAULT NULL,
    icon        VARCHAR(100) DEFAULT 'fas fa-concierge-bell',
    link_url    VARCHAR(300) DEFAULT 'tours.php',
    link_text   VARCHAR(80)  DEFAULT 'Explore',
    sort_order  SMALLINT     DEFAULT 0,
    is_active   TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* Seed defaults if empty */
$seedCount = $conn->query("SELECT COUNT(*) AS cnt FROM services")->fetch_assoc()['cnt'];
if ($seedCount == 0) {
    $conn->query("INSERT INTO services (title, label, description, icon, link_url, link_text, sort_order) VALUES
        ('Budget Tours',      'Affordable', 'Experience the best of Sri Lanka without breaking the bank. Our budget tours include all essentials for a wonderful journey.',    'fas fa-wallet',       'tours.php', 'Explore', 0),
        ('Adventure Tours',   'Thrilling',  'From surfing in Arugam Bay to hiking in Knuckles Range — adrenaline-packed adventures await the bold traveler.',                'fas fa-hiking',       'tours.php', 'Explore', 1),
        ('Airport Transfers', 'Seamless',   'Comfortable, punctual and reliable airport transfers in luxury vehicles to start your Sri Lanka adventure perfectly.',          'fas fa-plane-arrival','tours.php', 'Explore', 2)");
}

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $conn->query("SELECT image FROM services WHERE id=$id")->fetch_assoc();
    if ($row && $row['image']) {
        $p = __DIR__ . '/../' . $row['image'];
        if (file_exists($p)) unlink($p);
    }
    $conn->query("DELETE FROM services WHERE id=$id");
    header('Location: services.php?msg=deleted'); exit;
}

/* ── TOGGLE ── */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE services SET is_active = 1 - is_active WHERE id=$id");
    header('Location: services.php'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId     = (int)($_POST['item_id']    ?? 0);
    $title      = trim($_POST['title']       ?? '');
    $label      = trim($_POST['label']       ?? '');
    $description= trim($_POST['description'] ?? '');
    $icon       = trim($_POST['icon']        ?? 'fas fa-concierge-bell');
    $link_url   = trim($_POST['link_url']    ?? 'tours.php');
    $link_text  = trim($_POST['link_text']   ?? 'Explore');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (!$title) {
        $formError = 'Service title is required.';
        $editId    = $postId;
    } else {
        $imagePath = '';
        if (!empty($_FILES['image']['name'])) {
            $up = uploadImage($_FILES['image'], 'uploads/services');
            if (!$up['ok']) {
                $formError = $up['msg'];
                $editId    = $postId;
            } else {
                $imagePath = $up['path'];
                if ($postId) {
                    $old = $conn->query("SELECT image FROM services WHERE id=$postId")->fetch_assoc();
                    if ($old && $old['image']) { $p = __DIR__.'/../'.$old['image']; if (file_exists($p)) unlink($p); }
                }
            }
        }

        if (!$formError) {
            $titleE = $conn->real_escape_string($title);
            $lblE   = $conn->real_escape_string($label);
            $descE  = $conn->real_escape_string($description);
            $iconE  = $conn->real_escape_string($icon);
            $lurlE  = $conn->real_escape_string($link_url);
            $ltxtE  = $conn->real_escape_string($link_text);

            if ($postId) {
                $imgClause = $imagePath ? ", image='" . $conn->real_escape_string($imagePath) . "'" : '';
                $conn->query("UPDATE services SET
                    title='$titleE', label='$lblE', description='$descE',
                    icon='$iconE', link_url='$lurlE', link_text='$ltxtE',
                    sort_order=$sort_order, is_active=$is_active$imgClause
                    WHERE id=$postId");
                header('Location: services.php?msg=updated'); exit;
            } else {
                $imgVal = $imagePath ? "'" . $conn->real_escape_string($imagePath) . "'" : 'NULL';
                $conn->query("INSERT INTO services (title, label, description, image, icon, link_url, link_text, sort_order, is_active)
                    VALUES ('$titleE','$lblE','$descE',$imgVal,'$iconE','$lurlE','$ltxtE',$sort_order,$is_active)");
                header('Location: services.php?msg=added'); exit;
            }
        }
    }
}

/* ── FETCH FOR EDIT ── */
if ($editId) {
    $r        = $conn->query("SELECT * FROM services WHERE id=$editId");
    $editItem = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH ALL ── */
$items = [];
$r     = $conn->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
if ($r) while ($row = $r->fetch_assoc()) $items[] = $row;

$v           = $editItem ?? [];
$currentIcon = $v['icon'] ?? 'fas fa-concierge-bell';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>What We Offer | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.split-layout{display:grid;grid-template-columns:400px 1fr;gap:24px;align-items:start}
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
.check-row{display:flex;align-items:center;gap:10px;padding:9px 13px;background:var(--off-white);border-radius:10px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal)}
.check-row span{font-size:13px;color:var(--text-dark);font-weight:500}
.icon-preview-row{display:flex;align-items:center;gap:12px;padding:10px 13px;background:var(--off-white);border-radius:10px}
.icon-preview-box{width:42px;height:42px;border-radius:10px;background:#fff;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--teal);flex-shrink:0}
.icon-sug-wrap{display:flex;flex-wrap:wrap;gap:5px;margin-top:5px}
.icon-sug-btn{width:30px;height:30px;border-radius:7px;border:1.5px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text-mid);transition:all .15s}
.icon-sug-btn:hover,.icon-sug-btn.selected{border-color:var(--teal);color:var(--teal);background:var(--teal-pale)}
.img-preview{width:100%;height:130px;object-fit:cover;border-radius:8px;margin-top:6px;display:block}
.img-ph{width:100%;height:130px;border-radius:8px;background:var(--off-white);border:1.5px dashed var(--border);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#ccc;gap:6px;margin-top:6px}
.img-ph i{font-size:28px}
/* service cards list */
.services-list{display:flex;flex-direction:column;gap:14px}
.svc-card{background:#fff;border:1.5px solid var(--border);border-radius:12px;overflow:hidden;display:grid;grid-template-columns:180px 1fr;transition:border-color .2s}
.svc-card:hover{border-color:var(--teal)}
.svc-card.inactive{opacity:.55}
.svc-thumb{width:180px;height:100%;min-height:120px;object-fit:cover;display:block}
.svc-thumb-ph{width:180px;min-height:120px;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;font-size:36px;color:var(--teal)}
.svc-body{padding:14px 18px;display:flex;flex-direction:column;gap:5px;justify-content:center}
.svc-label{font-size:10px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--teal)}
.svc-title{font-size:15px;font-weight:700;color:var(--text-dark)}
.svc-desc{font-size:12px;color:var(--text-mid);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.svc-meta{display:flex;align-items:center;gap:8px;margin-top:6px}
.svc-link-tag{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;font-size:11px;font-weight:600;background:var(--teal-pale);color:var(--teal);font-family:monospace}
.act-btn{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.btn-tog{background:var(--off-white);color:var(--text-light)}.btn-tog:hover{background:var(--text-light);color:#fff}
.edit-mode .form-sec-head{background:var(--gold-pale)}
.edit-mode .form-sec-head h3,.edit-mode .form-sec-head i{color:#a8782a}
@media(max-width:960px){.split-layout{grid-template-columns:1fr}.svc-card{grid-template-columns:1fr}.svc-thumb,.svc-thumb-ph{width:100%;height:160px}.frow.c2{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include 'includes/sidebar.php'; ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-main">

  <div class="admin-topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div>
        <div class="topbar-title">What We Offer</div>
        <div class="topbar-breadcrumb">Content / Services</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Service added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Service updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Service deleted.</div><?php endif; ?>
      <?php if ($formError):         ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div><?php endif; ?>

      <div class="page-header">
        <div class="page-header-left">
          <h1>What We Offer</h1>
          <p>Manage the service cards shown in the "What We Offer" section on the home page</p>
        </div>
      </div>

      <div class="split-layout">

        <!-- ── FORM ── -->
        <div class="form-section <?= $editItem ? 'edit-mode' : '' ?>">
          <div class="form-sec-head">
            <i class="fas <?= $editItem ? 'fa-pen' : 'fa-plus-circle' ?>"></i>
            <h3><?= $editItem ? 'Edit Service' : 'Add New Service' ?></h3>
          </div>
          <div class="form-sec-body">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="item_id" value="<?= $v['id'] ?? 0 ?>"/>

              <div class="fgrp">
                <label>Service Title <span style="color:var(--red)">*</span></label>
                <input type="text" name="title" class="form-control" required
                       placeholder="e.g. Adventure Tours"
                       value="<?= htmlspecialchars($v['title'] ?? '') ?>"/>
              </div>

              <div class="fgrp">
                <label>Label <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(small badge above title, e.g. "Thrilling")</span></label>
                <input type="text" name="label" class="form-control"
                       placeholder="e.g. Thrilling"
                       value="<?= htmlspecialchars($v['label'] ?? '') ?>"/>
              </div>

              <div class="fgrp">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Brief description shown on the card…"><?= htmlspecialchars($v['description'] ?? '') ?></textarea>
              </div>

              <!-- Icon -->
              <div class="fgrp">
                <label>Icon (Font Awesome)</label>
                <input type="text" name="icon" id="iconField" class="form-control"
                       placeholder="fas fa-concierge-bell"
                       value="<?= htmlspecialchars($currentIcon) ?>"
                       oninput="updateIconPrev(this.value)"/>
                <div style="margin-top:4px">
                  <div style="font-size:11px;color:var(--text-light);margin-bottom:5px">Quick pick:</div>
                  <div class="icon-sug-wrap">
                    <?php foreach ([
                      'fas fa-wallet','fas fa-hiking','fas fa-plane-arrival','fas fa-concierge-bell',
                      'fas fa-car','fas fa-ship','fas fa-camera','fas fa-umbrella-beach',
                      'fas fa-mountain','fas fa-paw','fas fa-mosque','fas fa-gem',
                      'fas fa-heart','fas fa-star','fas fa-map-marked-alt','fas fa-route',
                    ] as $ic): ?>
                      <button type="button" class="icon-sug-btn <?= $currentIcon === $ic ? 'selected' : '' ?>"
                              title="<?= $ic ?>" onclick="pickIcon('<?= $ic ?>')">
                        <i class="<?= $ic ?>"></i>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="icon-preview-row" style="margin-top:6px">
                  <div class="icon-preview-box"><i id="iconPrev" class="<?= htmlspecialchars($currentIcon) ?>"></i></div>
                  <div>
                    <div style="font-size:12px;font-family:monospace;color:var(--text-mid)" id="iconPrevLabel"><?= htmlspecialchars($currentIcon) ?></div>
                    <div style="font-size:11px;color:var(--text-light)">Live preview</div>
                  </div>
                </div>
              </div>

              <!-- Image -->
              <div class="fgrp">
                <label>Card Image <?= $editItem ? '<span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(leave empty to keep current)</span>' : '' ?></label>
                <input type="file" name="image" accept="image/*" class="form-control" style="font-size:13px" onchange="previewImg(this)"/>
                <span style="font-size:11px;color:var(--text-light)">Recommended: 800×600px JPG/WebP</span>
                <?php if (!empty($v['image'])): ?>
                  <img id="imgPreview" src="<?= SITE_URL.'/'.$v['image'] ?>" class="img-preview" alt=""/>
                <?php else: ?>
                  <div class="img-ph" id="imgPh"><i class="fas fa-image"></i><span style="font-size:12px">No image selected</span></div>
                  <img id="imgPreview" src="" class="img-preview" alt="" style="display:none"/>
                <?php endif; ?>
              </div>

              <!-- Link -->
              <div class="frow c2">
                <div class="fgrp">
                  <label>Button Text</label>
                  <input type="text" name="link_text" class="form-control"
                         placeholder="Explore"
                         value="<?= htmlspecialchars($v['link_text'] ?? 'Explore') ?>"/>
                </div>
                <div class="fgrp">
                  <label>Link URL</label>
                  <input type="text" name="link_url" class="form-control"
                         placeholder="tours.php"
                         value="<?= htmlspecialchars($v['link_url'] ?? 'tours.php') ?>"/>
                </div>
              </div>

              <!-- Sort & Active -->
              <div class="frow c2">
                <div class="fgrp">
                  <label>Sort Order</label>
                  <input type="number" name="sort_order" class="form-control" min="0"
                         value="<?= (int)($v['sort_order'] ?? 0) ?>"/>
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
                  <?= $editItem ? 'Update Service' : 'Add Service' ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="services.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- ── LIST ── -->
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h3 style="font-size:15px;font-weight:700;color:var(--text-dark);margin:0">
              <?= count($items) ?> Service<?= count($items) != 1 ? 's' : '' ?>
            </h3>
          </div>

          <?php if ($items): ?>
          <div class="services-list">
            <?php foreach ($items as $item): ?>
            <div class="svc-card <?= $item['is_active'] ? '' : 'inactive' ?>">
              <?php if ($item['image']): ?>
                <img src="<?= SITE_URL.'/'.$item['image'] ?>" class="svc-thumb" alt="<?= htmlspecialchars($item['title']) ?>"/>
              <?php else: ?>
                <div class="svc-thumb-ph"><i class="<?= htmlspecialchars($item['icon'] ?: 'fas fa-concierge-bell') ?>"></i></div>
              <?php endif; ?>
              <div class="svc-body">
                <?php if ($item['label']): ?>
                  <div class="svc-label"><?= htmlspecialchars($item['label']) ?></div>
                <?php endif; ?>
                <div class="svc-title">
                  <i class="<?= htmlspecialchars($item['icon'] ?: 'fas fa-concierge-bell') ?>" style="color:var(--teal);margin-right:5px;font-size:13px"></i>
                  <?= htmlspecialchars($item['title']) ?>
                </div>
                <?php if ($item['description']): ?>
                  <div class="svc-desc"><?= htmlspecialchars($item['description']) ?></div>
                <?php endif; ?>
                <div class="svc-meta">
                  <span class="svc-link-tag"><i class="fas fa-link" style="font-size:10px"></i> <?= htmlspecialchars($item['link_url']) ?></span>
                  <?php if (!$item['is_active']): ?>
                    <span style="font-size:10px;background:var(--off-white);color:var(--text-light);padding:2px 8px;border-radius:4px"><i class="fas fa-eye-slash"></i> Hidden</span>
                  <?php endif; ?>
                  <div style="margin-left:auto;display:flex;gap:5px">
                    <a href="services.php?toggle=<?= $item['id'] ?>" class="act-btn btn-tog" title="<?= $item['is_active'] ? 'Hide' : 'Show' ?>">
                      <i class="fas <?= $item['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                    </a>
                    <a href="services.php?edit=<?= $item['id'] ?>" class="act-btn btn-edit" title="Edit">
                      <i class="fas fa-pen"></i>
                    </a>
                    <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['title'])) ?>')"
                            class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div style="text-align:center;padding:60px;color:var(--text-light);background:#fff;border:1px solid var(--border);border-radius:var(--radius)">
            <i class="fas fa-concierge-bell" style="font-size:48px;opacity:.1;display:block;margin-bottom:14px"></i>
            <p style="font-size:14px">No services yet. Add your first one using the form.</p>
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
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Service?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function updateIconPrev(cls) {
  document.getElementById('iconPrev').className = cls || 'fas fa-concierge-bell';
  document.getElementById('iconPrevLabel').textContent = cls || 'fas fa-concierge-bell';
  document.querySelectorAll('.icon-sug-btn').forEach(b => b.classList.toggle('selected', b.title === cls));
}
function pickIcon(cls) {
  document.getElementById('iconField').value = cls;
  updateIconPrev(cls);
}
function previewImg(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById('imgPreview');
      const ph   = document.getElementById('imgPh');
      prev.src = e.target.result;
      prev.style.display = '';
      if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
function confirmDelete(id, name) {
  document.getElementById('delModalText').textContent = 'Delete "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'services.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
