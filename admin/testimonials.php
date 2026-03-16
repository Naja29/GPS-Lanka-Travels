<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'testimonials';
$action     = $_GET['action'] ?? 'list';
$msg        = $_GET['msg']    ?? '';
$errors     = [];

$sources = ['google'=>'Google','tripadvisor'=>'TripAdvisor','facebook'=>'Facebook','direct'=>'Direct'];

/* ── DELETE ── */
if ($action === 'delete' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $row = $conn->query("SELECT photo FROM testimonials WHERE id=$id")->fetch_assoc();
    if ($row && $row['photo']) {
        $p = __DIR__ . '/../' . $row['photo'];
        if (file_exists($p)) unlink($p);
    }
    $conn->query("DELETE FROM testimonials WHERE id=$id");
    header('Location: testimonials.php?msg=deleted'); exit;
}

/* ── TOGGLE FEATURED / ACTIVE ── */
if ($action === 'toggle_featured' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE testimonials SET is_featured = 1 - is_featured WHERE id=$id");
    header('Location: testimonials.php'); exit;
}
if ($action === 'toggle_active' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE testimonials SET is_active = 1 - is_active WHERE id=$id");
    header('Location: testimonials.php'); exit;
}

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId      = (int)($_POST['item_id']    ?? 0);
    $name        = trim($_POST['name']        ?? '');
    $country     = trim($_POST['country']     ?? '');
    $flag        = trim($_POST['country_flag'] ?? '');
    $tour        = trim($_POST['tour']        ?? '');
    $rating      = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $review      = trim($_POST['review']      ?? '');
    $source      = $_POST['source']           ?? 'google';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active   = isset($_POST['is_active'])   ? 1 : 0;
    $sort_order  = (int)($_POST['sort_order']  ?? 0);

    if (!$name)   $errors[] = 'Guest name is required.';
    if (!$review) $errors[] = 'Review text is required.';
    if (!in_array($source, array_keys($sources))) $source = 'google';

    $photoPath = '';
    if (!$errors && !empty($_FILES['photo']['name'])) {
        $up = uploadImage($_FILES['photo'], 'uploads/testimonials');
        if (!$up['ok']) $errors[] = $up['msg'];
        else {
            $photoPath = $up['path'];
            if ($postId) {
                $old = $conn->query("SELECT photo FROM testimonials WHERE id=$postId")->fetch_assoc();
                if ($old && $old['photo']) { $p = __DIR__.'/../'.$old['photo']; if (file_exists($p)) unlink($p); }
            }
        }
    }

    if (!$errors) {
        $nameE    = $conn->real_escape_string($name);
        $countryE = $conn->real_escape_string($country);
        $flagE    = $conn->real_escape_string($flag);
        $tourE    = $conn->real_escape_string($tour);
        $reviewE  = $conn->real_escape_string($review);
        $sourceE  = $conn->real_escape_string($source);

        if ($postId) {
            $imgClause = $photoPath ? ", photo='" . $conn->real_escape_string($photoPath) . "'" : '';
            $conn->query("UPDATE testimonials SET
                name='$nameE', country='$countryE', country_flag='$flagE', tour='$tourE',
                rating=$rating, review='$reviewE', source='$sourceE',
                is_featured=$is_featured, is_active=$is_active, sort_order=$sort_order$imgClause
                WHERE id=$postId");
            header('Location: testimonials.php?msg=updated'); exit;
        } else {
            $imgVal = $photoPath ? "'" . $conn->real_escape_string($photoPath) . "'" : 'NULL';
            $conn->query("INSERT INTO testimonials
                (name, country, country_flag, tour, rating, review, photo, source, is_featured, is_active, sort_order)
                VALUES ('$nameE','$countryE','$flagE','$tourE',$rating,'$reviewE',$imgVal,'$sourceE',$is_featured,$is_active,$sort_order)");
            header('Location: testimonials.php?msg=added'); exit;
        }
    }

    $action = $postId ? 'edit' : 'add';
    if ($postId) $_GET['id'] = $postId;
}

/* ── FETCH FOR EDIT ── */
$editItem = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $id       = (int)$_GET['id'];
    $r        = $conn->query("SELECT * FROM testimonials WHERE id=$id");
    $editItem = $r ? $r->fetch_assoc() : null;
}

/* ── FETCH LIST ── */
$items = [];
if ($action === 'list') {
    $r = $conn->query("SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC");
    if ($r) while ($row = $r->fetch_assoc()) $items[] = $row;
}

$v = $editItem ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Testimonials | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
/* list */
.review-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:10px;position:relative}
.reviewer-row{display:flex;align-items:center;gap:12px}
.reviewer-avatar{width:44px;height:44px;border-radius:50%;object-fit:cover;flex-shrink:0;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--teal);font-size:16px;overflow:hidden}
.reviewer-avatar img{width:100%;height:100%;object-fit:cover}
.reviewer-name{font-weight:600;font-size:14px;color:var(--text-dark)}
.reviewer-meta{font-size:12px;color:var(--text-light);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.stars{color:#f59e0b;font-size:13px;letter-spacing:1px}
.review-text{font-size:13px;color:var(--text-mid);line-height:1.65;font-style:italic}
.source-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600}
.src-google{background:#fce8e6;color:#d93025}
.src-tripadvisor{background:#e8f5e9;color:#1a8a4a}
.src-facebook{background:#e8eaf6;color:#3b5998}
.src-direct{background:var(--teal-pale);color:var(--teal)}
.card-actions{display:flex;gap:6px;margin-left:auto}
.act-btn{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit2{background:var(--teal-pale);color:var(--teal)}.btn-edit2:hover{background:var(--teal);color:#fff}
.btn-del2{background:var(--red-pale);color:var(--red)}.btn-del2:hover{background:var(--red);color:#fff}
.feat-on{background:#fff8e1;color:#c9a84c}
.feat-off{background:var(--off-white);color:#ccc}.feat-off:hover{background:#fff8e1;color:#c9a84c}
.hidden-dim{opacity:.5}
/* form */
.test-form-grid{display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.form-sec-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.form-sec-head h3{font-size:14px;font-weight:700;color:var(--text-dark);margin:0}
.form-sec-head i{color:var(--teal);font-size:14px}
.form-sec-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.frow{display:grid;gap:14px}
.frow.c2{grid-template-columns:1fr 1fr}
.frow.c3{grid-template-columns:1fr 1fr 1fr}
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
.star-pick{display:flex;gap:4px;flex-direction:row-reverse;justify-content:flex-end}
.star-pick input{display:none}
.star-pick label{font-size:24px;color:#d1d5db;cursor:pointer;transition:color .15s}
.star-pick label:hover,.star-pick label:hover~label,.star-pick input:checked~label{color:#f59e0b}
.check-row{display:flex;align-items:center;gap:10px;padding:10px 13px;background:var(--off-white);border-radius:10px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--teal)}
.check-row span{font-size:13px;color:var(--text-dark);font-weight:500}
.photo-preview{width:100%;height:140px;object-fit:cover;border-radius:8px;margin-top:6px;display:block}
.reviews-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
@media(max-width:900px){.test-form-grid{grid-template-columns:1fr}.frow.c2,.frow.c3{grid-template-columns:1fr}}
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
        <div class="topbar-title"><?= $action==='list' ? 'Testimonials' : ($action==='edit' ? 'Edit Review' : 'Add Review') ?></div>
        <div class="topbar-breadcrumb">Testimonials<?= $action!=='list' ? ' / '.($action==='edit'?'Edit':'Add New') : '' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($action === 'list'): ?>
        <a href="testimonials.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Review</a>
      <?php else: ?>
        <a href="testimonials.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Review added successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Review updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Review deleted.</div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
      <?php endif; ?>

      <?php if ($action === 'list'): ?>
      <!-- ═══════════ LIST ═══════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>Testimonials <span style="font-size:14px;font-weight:400;color:var(--text-light)">(<?= count($items) ?>)</span></h1>
          <p>Manage customer reviews and testimonials</p>
        </div>
      </div>

      <?php if ($items): ?>
      <div class="reviews-grid">
        <?php foreach ($items as $item): ?>
        <div class="review-card <?= $item['is_active'] ? '' : 'hidden-dim' ?>">

          <!-- header row -->
          <div class="reviewer-row">
            <div class="reviewer-avatar">
              <?php if ($item['photo']): ?>
                <img src="<?= SITE_URL . '/' . htmlspecialchars($item['photo']) ?>" alt=""/>
              <?php else: ?>
                <?= strtoupper(substr($item['name'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
              <div class="reviewer-name">
                <?= htmlspecialchars($item['name']) ?>
                <?php if ($item['country_flag']): ?>
                  <span style="margin-left:4px"><?= htmlspecialchars($item['country_flag']) ?></span>
                <?php endif; ?>
              </div>
              <div class="reviewer-meta">
                <?php if ($item['country']): ?>
                  <span><?= htmlspecialchars($item['country']) ?></span>
                  <span style="opacity:.4">·</span>
                <?php endif; ?>
                <span class="source-badge src-<?= $item['source'] ?>">
                  <i class="fab fa-<?= $item['source']==='tripadvisor'?'tripadvisor':($item['source']==='facebook'?'facebook':($item['source']==='google'?'google':'comment')) ?>"></i>
                  <?= $sources[$item['source']] ?? $item['source'] ?>
                </span>
              </div>
            </div>
            <div class="card-actions">
              <a href="testimonials.php?action=toggle_featured&id=<?= $item['id'] ?>"
                 class="act-btn <?= $item['is_featured'] ? 'feat-on' : 'feat-off' ?>" title="<?= $item['is_featured']?'Remove featured':'Mark featured' ?>">
                <i class="fas fa-star"></i>
              </a>
              <a href="testimonials.php?action=toggle_active&id=<?= $item['id'] ?>"
                 class="act-btn" style="background:var(--off-white);color:var(--text-light)" title="<?= $item['is_active']?'Hide':'Show' ?>">
                <i class="fas <?= $item['is_active']?'fa-eye-slash':'fa-eye' ?>"></i>
              </a>
              <a href="testimonials.php?action=edit&id=<?= $item['id'] ?>" class="act-btn btn-edit2" title="Edit">
                <i class="fas fa-pen"></i>
              </a>
              <button onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')"
                      class="act-btn btn-del2" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
          </div>

          <!-- stars -->
          <div class="stars">
            <?php for ($s=1;$s<=5;$s++) echo $s<=$item['rating'] ? '★' : '☆'; ?>
            <span style="font-size:11px;color:var(--text-light);margin-left:4px;font-family:'DM Sans',sans-serif"><?= $item['rating'] ?>/5</span>
          </div>

          <!-- review -->
          <div class="review-text">"<?= htmlspecialchars($item['review']) ?>"</div>

          <!-- tour + meta -->
          <?php if ($item['tour']): ?>
            <div style="font-size:12px;color:var(--teal);font-weight:500"><i class="fas fa-map-marked-alt" style="margin-right:4px"></i><?= htmlspecialchars($item['tour']) ?></div>
          <?php endif; ?>

          <?php if ($item['is_featured'] || !$item['is_active']): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if ($item['is_featured']): ?>
              <span style="font-size:11px;background:#fff8e1;color:#c9a84c;padding:2px 8px;border-radius:5px;font-weight:600"><i class="fas fa-star"></i> Featured</span>
            <?php endif; ?>
            <?php if (!$item['is_active']): ?>
              <span style="font-size:11px;background:var(--off-white);color:var(--text-light);padding:2px 8px;border-radius:5px;font-weight:600"><i class="fas fa-eye-slash"></i> Hidden</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:60px;color:var(--text-light)">
        <i class="fas fa-star" style="font-size:48px;opacity:.12;display:block;margin-bottom:14px"></i>
        <p>No testimonials yet. <a href="testimonials.php?action=add" style="color:var(--teal)">Add your first review.</a></p>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ═══════════ ADD / EDIT FORM ═══════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1><?= $editItem ? 'Edit Review' : 'Add New Review' ?></h1>
          <p><?= $editItem ? 'Update the testimonial details' : 'Add a customer review or testimonial' ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="item_id" value="<?= $v['id'] ?? 0 ?>"/>
        <div class="test-form-grid">

          <!-- LEFT -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Guest Info -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-user"></i><h3>Guest Information</h3></div>
              <div class="form-sec-body">
                <div class="frow c2">
                  <div class="fgrp">
                    <label>Guest Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="e.g. Sarah Johnson"
                           value="<?= htmlspecialchars($v['name'] ?? '') ?>"/>
                  </div>
                  <div class="fgrp">
                    <label>Country</label>
                    <input type="text" name="country" class="form-control"
                           placeholder="e.g. United Kingdom"
                           value="<?= htmlspecialchars($v['country'] ?? '') ?>"/>
                  </div>
                </div>
                <div class="frow c2">
                  <div class="fgrp">
                    <label>Country Flag <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(emoji)</span></label>
                    <input type="text" name="country_flag" class="form-control"
                           placeholder="🇬🇧" maxlength="10"
                           value="<?= htmlspecialchars($v['country_flag'] ?? '') ?>"
                           style="font-size:20px"/>
                  </div>
                  <div class="fgrp">
                    <label>Tour Taken</label>
                    <input type="text" name="tour" class="form-control"
                           placeholder="e.g. 14-Day Round Tour"
                           value="<?= htmlspecialchars($v['tour'] ?? '') ?>"/>
                  </div>
                </div>
              </div>
            </div>

            <!-- Review -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-quote-left"></i><h3>Review</h3></div>
              <div class="form-sec-body">
                <!-- Star rating picker -->
                <div class="fgrp">
                  <label>Rating</label>
                  <div class="star-pick" id="starPick">
                    <?php for ($s=5;$s>=1;$s--): ?>
                      <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>"
                             <?= ($v['rating'] ?? 5) == $s ? 'checked' : '' ?>>
                      <label for="star<?= $s ?>" title="<?= $s ?> star<?= $s>1?'s':'' ?>">★</label>
                    <?php endfor; ?>
                  </div>
                </div>
                <div class="fgrp">
                  <label>Review Text <span style="color:var(--red)">*</span></label>
                  <textarea name="review" class="form-control" rows="5" required
                            placeholder="The guest's review…"><?= htmlspecialchars($v['review'] ?? '') ?></textarea>
                </div>
                <div class="fgrp">
                  <label>Review Source</label>
                  <select name="source" class="form-control">
                    <?php foreach ($sources as $key => $label): ?>
                      <option value="<?= $key ?>" <?= ($v['source'] ?? 'google') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

          </div>

          <!-- RIGHT: sidebar -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Settings -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-cog"></i><h3>Settings</h3></div>
              <div class="form-sec-body">
                <label class="check-row">
                  <input type="checkbox" name="is_active" value="1" <?= ($v['is_active'] ?? 1) ? 'checked' : '' ?>/>
                  <i class="fas fa-eye" style="color:var(--teal)"></i>
                  <span>Visible on website</span>
                </label>
                <label class="check-row">
                  <input type="checkbox" name="is_featured" value="1" <?= ($v['is_featured'] ?? 0) ? 'checked' : '' ?>/>
                  <i class="fas fa-star" style="color:#c9a84c"></i>
                  <span>Mark as Featured</span>
                </label>
                <div class="fgrp">
                  <label>Sort Order</label>
                  <input type="number" name="sort_order" class="form-control"
                         min="0" value="<?= (int)($v['sort_order'] ?? 0) ?>"/>
                </div>
              </div>
            </div>

            <!-- Photo -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-user-circle"></i><h3>Guest Photo</h3></div>
              <div class="form-sec-body">
                <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewPhoto(this)"/>
                <small style="font-size:11px;color:var(--text-light)">Square photo recommended · Max 25 MB<br>Leave empty to show initials avatar.</small>
                <div id="photoWrap" style="<?= empty($v['photo']) ? 'display:none' : '' ?>">
                  <img id="photoPreview"
                       src="<?= !empty($v['photo']) ? SITE_URL.'/'.$v['photo'] : '' ?>"
                       class="photo-preview" style="border-radius:50%;width:100px;height:100px;object-fit:cover;margin:8px auto 0;display:block" alt=""/>
                </div>
              </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:15px">
              <i class="fas <?= $editItem ? 'fa-save' : 'fa-plus' ?>"></i>
              <?= $editItem ? 'Update Review' : 'Add Review' ?>
            </button>

          </div>
        </div>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)"><i class="fas fa-trash"></i></div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Review?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<script src="js/admin.js"></script>
<script>
function confirmDelete(id, name) {
  document.getElementById('delModalText').textContent = 'Delete review from "' + name + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'testimonials.php?action=delete&id=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});

function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('photoPreview').src = e.target.result;
      document.getElementById('photoWrap').style.display = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
