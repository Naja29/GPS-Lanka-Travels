<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'team';
$action     = $_GET['action'] ?? 'list';
$msg        = $_GET['msg']    ?? '';
$errors     = [];

/* ── DELETE ── */
if ($action === 'delete' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $row = $conn->query("SELECT photo FROM team WHERE id=$id")->fetch_assoc();
    if ($row && $row['photo']) {
        $p = __DIR__ . '/../' . $row['photo'];
        if (file_exists($p)) unlink($p);
    }
    $conn->query("DELETE FROM team WHERE id=$id");
    header('Location: team.php?msg=deleted'); exit;
}

/* ── TOGGLE ACTIVE ── */
if ($action === 'toggle_active' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE team SET is_active = 1 - is_active WHERE id=$id");
    header('Location: team.php'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId       = (int)($_POST['item_id']      ?? 0);
    $name         = trim($_POST['name']          ?? '');
    $role         = trim($_POST['role']          ?? '');
    $bio          = trim($_POST['bio']           ?? '');
    $facebook_url = trim($_POST['facebook_url']  ?? '');
    $instagram_url= trim($_POST['instagram_url'] ?? '');
    $whatsapp     = trim($_POST['whatsapp']      ?? '');
    $is_active    = isset($_POST['is_active'])    ? 1 : 0;
    $sort_order   = (int)($_POST['sort_order']   ?? 0);

    if (!$name) $errors[] = 'Member name is required.';

    $photoPath = '';
    if (!$errors && !empty($_FILES['photo']['name'])) {
        $up = uploadImage($_FILES['photo'], 'uploads/team');
        if (!$up['ok']) $errors[] = $up['msg'];
        else {
            $photoPath = $up['path'];
            if ($postId) {
                $old = $conn->query("SELECT photo FROM team WHERE id=$postId")->fetch_assoc();
                if ($old && $old['photo']) { $p = __DIR__.'/../'.$old['photo']; if (file_exists($p)) unlink($p); }
            }
        }
    }

    if (!$errors) {
        $nameE     = $conn->real_escape_string($name);
        $roleE     = $conn->real_escape_string($role);
        $bioE      = $conn->real_escape_string($bio);
        $fbE       = $conn->real_escape_string($facebook_url);
        $igE       = $conn->real_escape_string($instagram_url);
        $waE       = $conn->real_escape_string($whatsapp);

        if ($postId) {
            $imgClause = $photoPath ? ", photo='" . $conn->real_escape_string($photoPath) . "'" : '';
            $conn->query("UPDATE team SET
                name='$nameE', role='$roleE', bio='$bioE',
                facebook_url='$fbE', instagram_url='$igE', whatsapp='$waE',
                is_active=$is_active, sort_order=$sort_order$imgClause
                WHERE id=$postId");
            header('Location: team.php?msg=updated'); exit;
        } else {
            $imgVal = $photoPath ? "'" . $conn->real_escape_string($photoPath) . "'" : 'NULL';
            $conn->query("INSERT INTO team
                (name, role, bio, photo, facebook_url, instagram_url, whatsapp, is_active, sort_order)
                VALUES ('$nameE','$roleE','$bioE',$imgVal,'$fbE','$igE','$waE',$is_active,$sort_order)");
            header('Location: team.php?msg=added'); exit;
        }
    }

    $action = $postId ? 'edit' : 'add';
    if ($postId) $_GET['id'] = $postId;
}

/* ── FETCH FOR EDIT ── */
$editItem = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $id       = (int)$_GET['id'];
    $r        = $conn->query("SELECT * FROM team WHERE id=$id");
    $editItem = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH LIST ── */
$items = [];
if ($action === 'list') {
    $r = $conn->query("SELECT * FROM team ORDER BY sort_order ASC, id ASC");
    if ($r) while ($row = $r->fetch_assoc()) $items[] = $row;
}

$v = $editItem ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Team | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
/* list */
.team-list-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.team-list-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s}
.team-list-card:hover{box-shadow:0 4px 20px rgba(10,61,61,0.1)}
.team-list-card.hidden-dim{opacity:.5}
.tlc-img{width:100%;height:170px;object-fit:cover;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;color:var(--teal);font-size:48px}
.tlc-img img{width:100%;height:100%;object-fit:cover}
.tlc-body{padding:14px 16px;flex:1;display:flex;flex-direction:column;gap:6px}
.tlc-name{font-weight:700;font-size:15px;color:var(--text-dark)}
.tlc-role{font-size:12px;color:var(--teal);font-weight:500;text-transform:uppercase;letter-spacing:.5px}
.tlc-bio{font-size:12.5px;color:var(--text-mid);line-height:1.6;flex:1}
.tlc-social{display:flex;gap:6px;padding-top:6px;border-top:1px solid var(--border);margin-top:auto}
.tlc-soc-btn{width:28px;height:28px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;background:var(--off-white);color:var(--text-mid)}
.tlc-actions{padding:10px 16px;background:var(--off-white);border-top:1px solid var(--border);display:flex;gap:6px;justify-content:flex-end}
.act-btn{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit2{background:var(--teal-pale);color:var(--teal)}.btn-edit2:hover{background:var(--teal);color:#fff}
.btn-del2{background:var(--red-pale);color:var(--red)}.btn-del2:hover{background:var(--red);color:#fff}
.badge-order{font-size:11px;background:var(--teal-pale);color:var(--teal);padding:2px 8px;border-radius:5px;font-weight:600;margin-right:auto}
/* form */
.team-form-grid{display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.form-sec-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:14px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:14px}
.form-sec-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.frow{display:grid;gap:14px}
.frow.c2{grid-template-columns:1fr 1fr}
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
textarea.form-control{resize:vertical;min-height:90px}
.check-row{display:flex;align-items:center;gap:10px;padding:10px 13px;background:var(--off-white);border-radius:10px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal)}
.check-row span{font-size:13px;color:var(--text-dark);font-weight:500}
.photo-preview{width:100%;height:160px;object-fit:cover;border-radius:8px;display:block}
.photo-placeholder{width:100%;height:160px;border-radius:8px;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;color:var(--teal);font-size:48px}
@media(max-width:900px){.team-form-grid{grid-template-columns:1fr}.frow.c2{grid-template-columns:1fr}}
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
        <div class="topbar-title"><?= $action==='list' ? 'Team Members' : ($action==='edit' ? 'Edit Member' : 'Add Member') ?></div>
        <div class="topbar-breadcrumb">Team<?= $action!=='list' ? ' / '.($action==='edit'?'Edit':'Add New') : '' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($action === 'list'): ?>
        <a href="team.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Member</a>
      <?php else: ?>
        <a href="team.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Team member added.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Team member updated.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Team member deleted.</div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
      <?php endif; ?>

      <?php if ($action === 'list'): ?>
      <!-- ═══════════ LIST ═══════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Team Members <span style="font-size:14px;font-weight:400;color:var(--text-light)">(<?= count($items) ?>)</span></h1>
          <p>Manage team members shown on the About Us page</p>
        </div>
      </div>

      <?php if ($items): ?>
      <div class="team-list-grid">
        <?php foreach ($items as $item): ?>
        <div class="team-list-card <?= $item['is_active'] ? '' : 'hidden-dim' ?>">
          <div class="tlc-img">
            <?php if ($item['photo']): ?>
              <img src="<?= SITE_URL . '/' . htmlspecialchars($item['photo']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"/>
            <?php else: ?>
              <i class="fas fa-user-tie"></i>
            <?php endif; ?>
          </div>
          <div class="tlc-body">
            <div class="tlc-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="tlc-role"><?= htmlspecialchars($item['role']) ?></div>
            <?php if ($item['bio']): ?>
              <div class="tlc-bio"><?= htmlspecialchars(substr($item['bio'], 0, 100)) . (strlen($item['bio'])>100?'…':'') ?></div>
            <?php endif; ?>
            <?php if ($item['facebook_url'] || $item['instagram_url'] || $item['whatsapp']): ?>
            <div class="tlc-social">
              <?php if ($item['facebook_url']): ?>
                <a href="<?= htmlspecialchars($item['facebook_url']) ?>" target="_blank" class="tlc-soc-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
              <?php endif; ?>
              <?php if ($item['instagram_url']): ?>
                <a href="<?= htmlspecialchars($item['instagram_url']) ?>" target="_blank" class="tlc-soc-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
              <?php endif; ?>
              <?php if ($item['whatsapp']): ?>
                <a href="https://wa.me/<?= htmlspecialchars($item['whatsapp']) ?>" target="_blank" class="tlc-soc-btn" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="tlc-actions">
            <span class="badge-order">Order: <?= (int)$item['sort_order'] ?></span>
            <a href="team.php?action=toggle_active&id=<?= $item['id'] ?>"
               class="act-btn" style="background:var(--off-white);color:var(--text-light)" title="<?= $item['is_active']?'Hide':'Show' ?>">
              <i class="fas <?= $item['is_active']?'fa-eye-slash':'fa-eye' ?>"></i>
            </a>
            <a href="team.php?action=edit&id=<?= $item['id'] ?>" class="act-btn btn-edit2" title="Edit">
              <i class="fas fa-pen"></i>
            </a>
            <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')"
                    class="act-btn btn-del2" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:60px;color:var(--text-light)">
        <i class="fas fa-users" style="font-size:48px;opacity:.12;display:block;margin-bottom:14px"></i>
        <p>No team members yet. <a href="team.php?action=add" style="color:var(--teal)">Add your first member.</a></p>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ═══════════ ADD / EDIT FORM ═══════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1><?= $action==='edit' ? 'Edit' : 'Add' ?> Team Member</h1>
          <p><?= $action==='edit' ? 'Update the details for ' . htmlspecialchars($editItem['name'] ?? '') : 'Add a new team member to the About Us page' ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="teamForm">
        <input type="hidden" name="item_id" value="<?= $editItem['id'] ?? 0 ?>"/>
        <div class="team-form-grid">

          <!-- LEFT: main fields -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Basic Info -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-user"></i><h3>Basic Information</h3></div>
              <div class="form-sec-body">
                <div class="frow c2">
                  <div class="fgrp">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Kasun Perera" value="<?= htmlspecialchars($v['name'] ?? '') ?>" required/>
                  </div>
                  <div class="fgrp">
                    <label>Role / Position</label>
                    <input type="text" name="role" class="form-control" placeholder="e.g. Tour Operations Manager" value="<?= htmlspecialchars($v['role'] ?? '') ?>"/>
                  </div>
                </div>
                <div class="fgrp">
                  <label>Bio / Description</label>
                  <textarea name="bio" class="form-control" rows="4" placeholder="A short description about this team member..."><?= htmlspecialchars($v['bio'] ?? '') ?></textarea>
                </div>
              </div>
            </div>

            <!-- Social Links -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-share-alt"></i><h3>Social Media Links</h3></div>
              <div class="form-sec-body">
                <div class="fgrp">
                  <label><i class="fab fa-facebook-f" style="color:#3b5998;margin-right:4px"></i> Facebook URL</label>
                  <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($v['facebook_url'] ?? '') ?>"/>
                </div>
                <div class="fgrp">
                  <label><i class="fab fa-instagram" style="color:#e1306c;margin-right:4px"></i> Instagram URL</label>
                  <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($v['instagram_url'] ?? '') ?>"/>
                </div>
                <div class="fgrp">
                  <label><i class="fab fa-whatsapp" style="color:#25d366;margin-right:4px"></i> WhatsApp Number</label>
                  <input type="text" name="whatsapp" class="form-control" placeholder="94770489956 (no + or spaces)" value="<?= htmlspecialchars($v['whatsapp'] ?? '') ?>"/>
                  <small style="color:var(--text-light);font-size:11px">Country code + number, no spaces or +. e.g. 94770489956</small>
                </div>
              </div>
            </div>

          </div><!-- /left -->

          <!-- RIGHT: photo + settings -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Photo -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-image"></i><h3>Photo</h3></div>
              <div class="form-sec-body">
                <?php if (!empty($v['photo'])): ?>
                  <img src="<?= SITE_URL . '/' . htmlspecialchars($v['photo']) ?>" alt="" class="photo-preview" id="photoPreview"/>
                <?php else: ?>
                  <div class="photo-placeholder" id="photoPlaceholder"><i class="fas fa-user-tie"></i></div>
                  <img src="" alt="" class="photo-preview" id="photoPreview" style="display:none"/>
                <?php endif; ?>
                <input type="file" name="photo" id="photoInput" accept="image/*" class="form-control" style="margin-top:8px"/>
                <?php if (!empty($v['photo'])): ?>
                  <small style="color:var(--text-light);font-size:11px">Upload a new photo to replace the current one.</small>
                <?php endif; ?>
              </div>
            </div>

            <!-- Settings -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-cog"></i><h3>Settings</h3></div>
              <div class="form-sec-body">
                <div class="fgrp">
                  <label>Sort Order</label>
                  <input type="number" name="sort_order" class="form-control" value="<?= (int)($v['sort_order'] ?? 0) ?>" min="0"/>
                  <small style="color:var(--text-light);font-size:11px">Lower number = shown first</small>
                </div>
                <label class="check-row">
                  <input type="checkbox" name="is_active" value="1" <?= (!isset($v['is_active']) || $v['is_active']) ? 'checked' : '' ?>/>
                  <span>Active (visible on website)</span>
                </label>
              </div>
            </div>

            <!-- Save button -->
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px">
              <i class="fas fa-save"></i>
              <?= $action === 'edit' ? 'Update Member' : 'Add Member' ?>
            </button>

          </div><!-- /right -->
        </div><!-- /form-grid -->
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>
</div>

<!-- Delete confirm modal (reuse pattern) -->
<form id="deleteForm" method="GET" style="display:none">
  <input type="hidden" name="action" value="delete"/>
  <input type="hidden" name="id" id="deleteId"/>
</form>

<script>
function confirmDelete(id, name) {
    if (confirm('Delete team member "' + name + '"? This cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

/* Photo preview */
document.getElementById('photoInput')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('photoPreview');
        const ph   = document.getElementById('photoPlaceholder');
        prev.src = e.target.result;
        prev.style.display = 'block';
        if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(file);
});

/* Sidebar toggle */
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('adminSidebar')?.classList.toggle('open');
    document.getElementById('sidebarOverlay')?.classList.toggle('show');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
    document.getElementById('adminSidebar')?.classList.remove('open');
    document.getElementById('sidebarOverlay')?.classList.remove('show');
});
</script>
</body>
</html>
