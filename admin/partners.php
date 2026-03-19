<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'partners';
$editItem   = null;
$formError  = '';
$msg        = $_GET['msg'] ?? '';
$editId     = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

/* ── SAFE MIGRATION ── */
$conn->query("CREATE TABLE IF NOT EXISTS partners (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    logo        VARCHAR(300) DEFAULT NULL,
    label       VARCHAR(120) DEFAULT NULL,
    url         VARCHAR(300) DEFAULT NULL,
    icon_class  VARCHAR(100) DEFAULT NULL,
    icon_color  VARCHAR(20)  DEFAULT '#0f5252',
    sort_order  SMALLINT     DEFAULT 0,
    is_active   TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $conn->query("SELECT logo FROM partners WHERE id=$id")->fetch_assoc();
    if ($row && $row['logo']) {
        $p = __DIR__ . '/../' . $row['logo'];
        if (file_exists($p)) unlink($p);
    }
    $conn->query("DELETE FROM partners WHERE id=$id");
    header('Location: partners.php?msg=deleted'); exit;
}

/* ── TOGGLE ACTIVE ── */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE partners SET is_active = 1 - is_active WHERE id=$id");
    header('Location: partners.php'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId     = (int)($_POST['item_id']    ?? 0);
    $name       = trim($_POST['name']        ?? '');
    $label      = trim($_POST['label']       ?? '');
    $url        = trim($_POST['url']         ?? '');
    $icon_class = trim($_POST['icon_class']  ?? '');
    $icon_color = trim($_POST['icon_color']  ?? '#0f5252');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active'])  ? 1 : 0;

    if (!$name) {
        $formError = 'Partner name is required.';
        $editId    = $postId;
    } else {
        $logoPath = '';
        if (!empty($_FILES['logo']['name'])) {
            $up = uploadImage($_FILES['logo'], 'uploads/partners');
            if (!$up['ok']) {
                $formError = $up['msg'];
            } else {
                $logoPath = $up['path'];
                if ($postId) {
                    $old = $conn->query("SELECT logo FROM partners WHERE id=$postId")->fetch_assoc();
                    if ($old && $old['logo']) { $p = __DIR__.'/../'.$old['logo']; if (file_exists($p)) unlink($p); }
                }
            }
        }

        if (!$formError) {
            $nameE  = $conn->real_escape_string($name);
            $labelE = $conn->real_escape_string($label);
            $urlE   = $conn->real_escape_string($url);
            $iconE  = $conn->real_escape_string($icon_class);
            $colorE = $conn->real_escape_string($icon_color);

            if ($postId) {
                $imgClause = $logoPath ? ", logo='" . $conn->real_escape_string($logoPath) . "'" : '';
                $conn->query("UPDATE partners SET
                    name='$nameE', label='$labelE', url='$urlE',
                    icon_class='$iconE', icon_color='$colorE',
                    sort_order=$sort_order, is_active=$is_active$imgClause
                    WHERE id=$postId");
                header('Location: partners.php?msg=updated'); exit;
            } else {
                $imgVal = $logoPath ? "'" . $conn->real_escape_string($logoPath) . "'" : 'NULL';
                $conn->query("INSERT INTO partners (name, logo, label, url, icon_class, icon_color, sort_order, is_active)
                    VALUES ('$nameE',$imgVal,'$labelE','$urlE','$iconE','$colorE',$sort_order,$is_active)");
                header('Location: partners.php?msg=added'); exit;
            }
        }
    }
}

/* ── FETCH FOR EDIT ── */
if ($editId) {
    $r        = $conn->query("SELECT * FROM partners WHERE id=$editId");
    $editItem = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH ALL ── */
$items = [];
$r     = $conn->query("SELECT * FROM partners ORDER BY sort_order ASC, id ASC");
if ($r) while ($row = $r->fetch_assoc()) $items[] = $row;

$v = $editItem ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Partners | GPS Lanka Admin</title>
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
.icon-preview{display:flex;align-items:center;gap:10px;margin-top:4px;padding:9px 13px;background:var(--off-white);border-radius:8px;font-size:13px;color:var(--text-mid)}
.color-row{display:flex;align-items:center;gap:10px}
.color-row input[type=color]{width:42px;height:38px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;padding:2px;background:#fff}
.check-row{display:flex;align-items:center;gap:10px;padding:9px 13px;background:var(--off-white);border-radius:10px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal)}
.check-row span{font-size:13px;color:var(--text-dark);font-weight:500}
.logo-preview{width:100%;height:80px;object-fit:contain;border-radius:8px;margin-top:6px;background:var(--off-white);padding:8px;box-sizing:border-box}
/* partner grid */
.partners-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.partner-card{background:#fff;border:1.5px solid var(--border);border-radius:12px;padding:18px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;position:relative;transition:border-color .2s}
.partner-card:hover{border-color:var(--teal)}
.partner-card.inactive{opacity:.5}
.partner-logo-box{width:80px;height:60px;display:flex;align-items:center;justify-content:center}
.partner-logo-box img{max-width:100%;max-height:100%;object-fit:contain}
.partner-icon-box{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--off-white);font-size:26px}
.partner-name{font-size:13px;font-weight:600;color:var(--text-dark)}
.partner-label{font-size:11px;color:var(--text-light)}
.partner-url{font-size:11px;color:var(--teal);font-family:monospace;word-break:break-all}
.card-actions{display:flex;gap:6px;margin-top:auto}
.act-btn{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit{background:var(--teal-pale);color:var(--teal)}.btn-edit:hover{background:var(--teal);color:#fff}
.btn-del{background:var(--red-pale);color:var(--red)}.btn-del:hover{background:var(--red);color:#fff}
.btn-tog{background:var(--off-white);color:var(--text-light)}.btn-tog:hover{background:var(--text-light);color:#fff}
.edit-mode .form-sec-head{background:var(--gold-pale)}
.edit-mode .form-sec-head h3,.edit-mode .form-sec-head i{color:#a8782a}
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
        <div class="topbar-title">Partners</div>
        <div class="topbar-breadcrumb">Content / Partners</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Partner added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Partner updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Partner deleted.</div><?php endif; ?>
      <?php if ($formError):         ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div><?php endif; ?>

      <div class="page-header">
        <div class="page-header-left">
          <h1>Partners &amp; Associations</h1>
          <p>Manage partner logos and trust badges shown on the website</p>
        </div>
      </div>

      <div class="split-layout">

        <!-- ── FORM ── -->
        <div class="form-section <?= $editItem ? 'edit-mode' : '' ?>">
          <div class="form-sec-head">
            <i class="fas <?= $editItem ? 'fa-pen' : 'fa-plus-circle' ?>"></i>
            <h3><?= $editItem ? 'Edit Partner' : 'Add New Partner' ?></h3>
          </div>
          <div class="form-sec-body">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="item_id" value="<?= $v['id'] ?? 0 ?>"/>

              <div class="fgrp">
                <label>Partner / Organisation Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" required
                       placeholder="e.g. Sri Lanka Tourism"
                       value="<?= htmlspecialchars($v['name'] ?? '') ?>"/>
              </div>

              <div class="fgrp">
                <label>Display Label <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(shown under logo)</span></label>
                <input type="text" name="label" class="form-control"
                       placeholder="e.g. Official Partner"
                       value="<?= htmlspecialchars($v['label'] ?? '') ?>"/>
              </div>

              <div class="fgrp">
                <label>Website URL <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(optional)</span></label>
                <input type="url" name="url" class="form-control"
                       placeholder="https://example.com"
                       value="<?= htmlspecialchars($v['url'] ?? '') ?>"/>
              </div>

              <!-- Logo upload -->
              <div class="fgrp">
                <label>Logo Image <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(PNG with transparent bg recommended)</span></label>
                <input type="file" name="logo" class="form-control" accept="image/*" onchange="previewLogo(this)"/>
                <div id="logoWrap" style="<?= empty($v['logo']) ? 'display:none' : '' ?>">
                  <img id="logoPreview"
                       src="<?= !empty($v['logo']) ? SITE_URL.'/'.$v['logo'] : '' ?>"
                       class="logo-preview" alt=""/>
                </div>
              </div>

              <!-- Icon fallback -->
              <div style="background:var(--off-white);border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:10px">
                <div style="font-size:11px;font-weight:600;color:#888;letter-spacing:.6px;text-transform:uppercase">Icon Fallback <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(if no logo uploaded)</span></div>
                <div class="frow c2">
                  <div class="fgrp">
                    <label>Font Awesome Class</label>
                    <input type="text" name="icon_class" id="iconClassField" class="form-control"
                           placeholder="fas fa-handshake"
                           value="<?= htmlspecialchars($v['icon_class'] ?? '') ?>"
                           oninput="updateIconPrev(this.value)"/>
                  </div>
                  <div class="fgrp">
                    <label>Icon Color</label>
                    <div class="color-row">
                      <input type="color" id="iconColorPicker" value="<?= htmlspecialchars($v['icon_color'] ?? '#0f5252') ?>"/>
                      <input type="text" name="icon_color" id="iconColorText" class="form-control"
                             value="<?= htmlspecialchars($v['icon_color'] ?? '#0f5252') ?>" maxlength="7"
                             oninput="syncColor(this.value)"/>
                    </div>
                  </div>
                </div>
                <div class="icon-preview">
                  <i id="iconPrev" class="<?= htmlspecialchars($v['icon_class'] ?? 'fas fa-handshake') ?>"
                     style="font-size:22px;color:<?= htmlspecialchars($v['icon_color'] ?? '#0f5252') ?>"></i>
                  <span id="iconPrevLabel" style="font-size:12px"><?= htmlspecialchars($v['icon_class'] ?? 'fas fa-handshake') ?></span>
                </div>
              </div>

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
                  <?= $editItem ? 'Update Partner' : 'Add Partner' ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="partners.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- ── PARTNER GRID ── -->
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h3 style="font-size:15px;font-weight:700;color:var(--text-dark);margin:0"><?= count($items) ?> Partner<?= count($items)!=1?'s':'' ?></h3>
          </div>

          <?php if ($items): ?>
          <div class="partners-grid">
            <?php foreach ($items as $item): ?>
            <div class="partner-card <?= $item['is_active'] ? '' : 'inactive' ?>">

              <!-- Logo or icon -->
              <?php if ($item['logo']): ?>
                <div class="partner-logo-box">
                  <img src="<?= SITE_URL . '/' . htmlspecialchars($item['logo']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"/>
                </div>
              <?php elseif ($item['icon_class']): ?>
                <div class="partner-icon-box">
                  <i class="<?= htmlspecialchars($item['icon_class']) ?>" style="color:<?= htmlspecialchars($item['icon_color'] ?? '#0f5252') ?>"></i>
                </div>
              <?php else: ?>
                <div class="partner-icon-box">
                  <i class="fas fa-handshake" style="color:var(--teal)"></i>
                </div>
              <?php endif; ?>

              <div class="partner-name"><?= htmlspecialchars($item['name']) ?></div>
              <?php if ($item['label']): ?>
                <div class="partner-label"><?= htmlspecialchars($item['label']) ?></div>
              <?php endif; ?>
              <?php if ($item['url']): ?>
                <div class="partner-url"><?= htmlspecialchars(parse_url($item['url'], PHP_URL_HOST) ?: $item['url']) ?></div>
              <?php endif; ?>

              <?php if (!$item['is_active']): ?>
                <span style="font-size:10px;background:var(--off-white);color:var(--text-light);padding:2px 8px;border-radius:4px"><i class="fas fa-eye-slash"></i> Hidden</span>
              <?php endif; ?>

              <div class="card-actions">
                <a href="partners.php?toggle=<?= $item['id'] ?>" class="act-btn btn-tog" title="<?= $item['is_active']?'Hide':'Show' ?>">
                  <i class="fas <?= $item['is_active']?'fa-eye-slash':'fa-eye' ?>"></i>
                </a>
                <a href="partners.php?edit=<?= $item['id'] ?>" class="act-btn btn-edit" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')"
                        class="act-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div style="text-align:center;padding:60px;color:var(--text-light);background:#fff;border:1px solid var(--border);border-radius:var(--radius)">
            <i class="fas fa-handshake" style="font-size:42px;opacity:.12;display:block;margin-bottom:12px"></i>
            <p>No partners yet. Add your first one.</p>
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
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Partner?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function previewLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('logoPreview').src = e.target.result;
      document.getElementById('logoWrap').style.display = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function updateIconPrev(cls) {
  const el = document.getElementById('iconPrev');
  const lb = document.getElementById('iconPrevLabel');
  el.className   = cls || 'fas fa-handshake';
  lb.textContent = cls || 'fas fa-handshake';
}

const colorPicker = document.getElementById('iconColorPicker');
const colorText   = document.getElementById('iconColorText');
colorPicker.addEventListener('input', function() {
  colorText.value = this.value;
  document.getElementById('iconPrev').style.color = this.value;
});
function syncColor(val) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    colorPicker.value = val;
    document.getElementById('iconPrev').style.color = val;
  }
}

function confirmDelete(id, name) {
  document.getElementById('delModalText').textContent = 'Delete "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'partners.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
