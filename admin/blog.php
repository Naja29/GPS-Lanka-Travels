<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'blog';
$action     = $_GET['action'] ?? 'list';
$search     = trim($_GET['q'] ?? '');
$msg        = $_GET['msg'] ?? '';
$errors     = [];

/* ACTIONS  */
if ($action === 'delete' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $row = $conn->query("SELECT image FROM blog_posts WHERE id=$id")->fetch_assoc();
    if ($row && $row['image']) {
        $imgPath = __DIR__ . '/../' . $row['image'];
        if (file_exists($imgPath)) unlink($imgPath);
    }
    $conn->query("DELETE FROM blog_posts WHERE id=$id");
    header('Location: blog.php?msg=deleted'); exit;
}

if ($action === 'toggle_featured' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE blog_posts SET is_featured = 1 - is_featured WHERE id=$id");
    header('Location: blog.php'); exit;
}

if ($action === 'toggle_published' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $r  = $conn->query("SELECT is_published FROM blog_posts WHERE id=$id")->fetch_assoc();
    if ($r['is_published']) {
        $conn->query("UPDATE blog_posts SET is_published=0, published_at=NULL WHERE id=$id");
    } else {
        $conn->query("UPDATE blog_posts SET is_published=1, published_at=NOW() WHERE id=$id");
    }
    header('Location: blog.php'); exit;
}

/* SAVE POST  */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId      = (int)($_POST['post_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $content     = $_POST['content'] ?? '';
    $excerpt     = trim($_POST['excerpt'] ?? '');
    $author      = trim($_POST['author'] ?? 'GPS Lanka Travels');
    $read_time   = max(1, (int)($_POST['read_time'] ?? 5));
    $cat_id      = (int)($_POST['category_id'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_pub      = (int)($_POST['is_published'] ?? 0);
    $meta_title  = trim($_POST['meta_title'] ?? '');
    $meta_desc   = trim($_POST['meta_desc'] ?? '');
    $meta_kw     = trim($_POST['meta_kw'] ?? '');

    if (!$title) $errors[] = 'Post title is required.';

    if (!$errors) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $sc   = $conn->prepare("SELECT id FROM blog_posts WHERE slug=? AND id!=?");
        $sc->bind_param('si', $slug, $postId);
        $sc->execute();
        if ($sc->get_result()->num_rows) $slug .= '-' . time();
        $sc->close();

        $imagePath = '';
        if (!empty($_FILES['image']['name'])) {
            $up = uploadImage($_FILES['image'], 'uploads/blog');
            if (!$up['ok']) $errors[] = $up['msg'];
            else $imagePath = $up['path'];
        }
    }

    if (!$errors) {
        $metaJson = json_encode(['title' => $meta_title, 'desc' => $meta_desc, 'kw' => $meta_kw]);
        $titleE   = $conn->real_escape_string($title);
        $slugE    = $conn->real_escape_string($slug);
        $contentE = $conn->real_escape_string($content);
        $excerptE = $conn->real_escape_string($excerpt);
        $authorE  = $conn->real_escape_string($author);
        $metaE    = $conn->real_escape_string($metaJson);
        $catSQL   = $cat_id ?: 'NULL';

        if ($postId) {
            $imgClause = $imagePath ? ", image='" . $conn->real_escape_string($imagePath) . "'" : '';
            $pubClause = $is_pub ? ", published_at=IFNULL(published_at,NOW())" : ', published_at=NULL';
            $conn->query("UPDATE blog_posts SET
                category_id=$catSQL, title='$titleE', slug='$slugE',
                excerpt='$excerptE', content='$contentE', author='$authorE',
                read_time=$read_time, is_featured=$is_featured, is_published=$is_pub,
                meta='$metaE'$imgClause$pubClause
                WHERE id=$postId");
            header('Location: blog.php?msg=updated'); exit;
        } else {
            $imgVal   = $imagePath ? "'" . $conn->real_escape_string($imagePath) . "'" : 'NULL';
            $pubAtSQL = $is_pub ? "NOW()" : 'NULL';
            $conn->query("INSERT INTO blog_posts
                (category_id, title, slug, excerpt, content, image, author, read_time, is_featured, is_published, meta, published_at)
                VALUES ($catSQL,'$titleE','$slugE','$excerptE','$contentE',$imgVal,'$authorE',$read_time,$is_featured,$is_pub,'$metaE',$pubAtSQL)");
            header('Location: blog.php?msg=added'); exit;
        }
    }

    /* stay on form with errors */
    $action = $postId ? 'edit' : 'add';
    if ($postId) $_GET['id'] = $postId;
}

/* FETCH FOR EDIT  */
$editPost = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $id       = (int)$_GET['id'];
    $r        = $conn->query("SELECT * FROM blog_posts WHERE id=$id");
    $editPost = $r ? $r->fetch_assoc() : null;
    if ($editPost && $editPost['meta']) {
        $editPost['_meta'] = json_decode($editPost['meta'], true) ?: [];
    }
}

/* FETCH CATEGORIES  */
$cats = [];
$cr   = $conn->query("SELECT * FROM blog_categories ORDER BY sort_order, name");
if ($cr) while ($c = $cr->fetch_assoc()) $cats[] = $c;

/* ── FETCH LIST ── */
$posts = [];
if ($action === 'list') {
    $where = '';
    if ($search) {
        $sq    = $conn->real_escape_string($search);
        $where = "WHERE bp.title LIKE '%$sq%' OR bp.author LIKE '%$sq%'";
    }
    $r = $conn->query("SELECT bp.*, bc.name as cat_name
        FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id=bp.category_id
        $where ORDER BY bp.created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $posts[] = $row;
}

$v = $editPost ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Blog Posts | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.post-thumb{width:60px;height:44px;object-fit:cover;border-radius:6px;display:block}
.post-thumb-ph{width:60px;height:44px;border-radius:6px;background:var(--off-white);display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:18px}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.4px}
.pill-pub{background:#e6f9f0;color:#1a8a5e}
.pill-draft{background:var(--off-white);color:var(--text-light)}
.act-btn{width:30px;height:30px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;border:none;cursor:pointer;transition:background .2s,color .2s;text-decoration:none}
.btn-edit2{background:var(--teal-pale);color:var(--teal)}.btn-edit2:hover{background:var(--teal);color:#fff}
.feat-on{background:#fff8e1;color:#c9a84c}
.feat-off{background:var(--off-white);color:#ccc}
.feat-off:hover{background:#fff8e1;color:#c9a84c}
.btn-del2{background:var(--red-pale);color:var(--red)}.btn-del2:hover{background:var(--red);color:#fff}
.blog-form-grid{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
.form-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
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
.seo-box{background:linear-gradient(135deg,#f0f9f9,#fff);border:1px solid rgba(15,82,82,.12);border-radius:10px;padding:16px;display:flex;flex-direction:column;gap:12px}
@media(max-width:960px){.blog-form-grid{grid-template-columns:1fr}}
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
        <div class="topbar-title"><?= $action==='list' ? 'Blog Posts' : ($action==='edit' ? 'Edit Post' : 'Add New Post') ?></div>
        <div class="topbar-breadcrumb">Blog<?= $action!=='list' ? ' / '.($action==='edit'?'Edit':'Add New') : '' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($action === 'list'): ?>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <div style="position:relative">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:13px;pointer-events:none"></i>
            <input type="text" name="q" placeholder="Search posts…" value="<?= htmlspecialchars($search) ?>"
                   style="padding:7px 12px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;width:200px;font-family:inherit;color:var(--text-dark)"
                   onfocus="this.style.borderColor='var(--teal)'" onblur="this.style.borderColor='var(--border)'"/>
          </div>
          <?php if ($search): ?>
            <a href="blog.php" class="btn btn-outline btn-sm" title="Clear"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="blog-cats.php" class="btn btn-outline btn-sm"><i class="fas fa-folder"></i> Categories</a>
        <a href="blog.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Post</a>
      <?php else: ?>
        <a href="blog.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Posts</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php if ($action === 'list'): ?>
      <div class="page-tabs">
        <a href="blog.php" class="page-tab active"><i class="fas fa-pen-nib"></i> Posts</a>
        <a href="blog-cats.php" class="page-tab"><i class="fas fa-folder"></i> Categories</a>
      </div>
      <?php endif; ?>

      <?php if ($msg === 'added'):   ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Post published successfully.</div><?php endif; ?>
      <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Post updated successfully.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-warning"><i class="fas fa-trash"></i> Post deleted.</div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
      <?php endif; ?>

      <?php if ($action === 'list'): ?>
      <!-- ═══════════ LIST ═══════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1>All Posts <span style="font-size:14px;font-weight:400;color:var(--text-light)">(<?= count($posts) ?>)</span></h1>
          <p>Manage your blog articles</p>
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
                <th style="width:80px">Author</th>
                <th style="width:110px;text-align:center">Status</th>
                <th style="width:100px">Date</th>
                <th style="width:100px;text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($posts): foreach ($posts as $post): ?>
            <tr>
              <td>
                <?php if ($post['image']): ?>
                  <img src="<?= SITE_URL . '/' . htmlspecialchars($post['image']) ?>" class="post-thumb" alt=""/>
                <?php else: ?>
                  <div class="post-thumb-ph"><i class="fas fa-image"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="col-title"><?= htmlspecialchars($post['title']) ?></div>
                <div style="font-size:11px;color:var(--text-light);margin-top:2px;font-family:monospace"><?= htmlspecialchars($post['slug']) ?></div>
              </td>
              <td>
                <?php if ($post['cat_name']): ?>
                  <span style="font-size:12px;background:var(--teal-pale);color:var(--teal);padding:3px 8px;border-radius:6px"><?= htmlspecialchars($post['cat_name']) ?></span>
                <?php else: ?>
                  <span style="font-size:12px;color:var(--text-light)">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:13px;color:var(--text-mid)"><?= htmlspecialchars($post['author']) ?></td>
              <td style="text-align:center">
                <a href="blog.php?action=toggle_published&id=<?= $post['id'] ?>" title="Click to toggle">
                  <span class="status-pill <?= $post['is_published'] ? 'pill-pub' : 'pill-draft' ?>">
                    <i class="fas <?= $post['is_published'] ? 'fa-check-circle' : 'fa-moon' ?>"></i>
                    <?= $post['is_published'] ? 'Published' : 'Draft' ?>
                  </span>
                </a>
              </td>
              <td style="font-size:12px;color:var(--text-light)">
                <?= $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : date('M j, Y', strtotime($post['created_at'])) ?>
              </td>
              <td style="text-align:center">
                <div style="display:flex;gap:5px;justify-content:center">
                  <a href="blog.php?action=edit&id=<?= $post['id'] ?>" class="act-btn btn-edit2" title="Edit"><i class="fas fa-pen"></i></a>
                  <a href="blog.php?action=toggle_featured&id=<?= $post['id'] ?>" class="act-btn <?= $post['is_featured'] ? 'feat-on' : 'feat-off' ?>" title="<?= $post['is_featured'] ? 'Remove featured' : 'Mark featured' ?>"><i class="fas fa-star"></i></a>
                  <button onclick="confirmDelete(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>')" class="act-btn btn-del2" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7">
              <div style="text-align:center;padding:50px;color:var(--text-light)">
                <i class="fas fa-pen-nib" style="font-size:40px;opacity:.15;display:block;margin-bottom:12px"></i>
                <p><?= $search ? 'No posts match your search.' : 'No blog posts yet. Create your first one.' ?></p>
              </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php else: ?>
      <!-- ═══════════ ADD / EDIT FORM ═══════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1><?= $editPost ? 'Edit Post' : 'Add New Post' ?></h1>
          <p><?= $editPost ? 'Update the blog post details' : 'Write and publish a new blog article' ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="blogForm">
        <input type="hidden" name="post_id" value="<?= $editPost['id'] ?? 0 ?>"/>
        <div class="blog-form-grid">

          <!-- ── LEFT: Main content ── -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Title + Slug -->
            <div class="form-section">
              <div class="form-sec-body" style="padding:20px">
                <div class="fgrp">
                  <label>Post Title <span style="color:var(--red)">*</span></label>
                  <input type="text" name="title" class="form-control" required
                         placeholder="Enter post title…"
                         value="<?= htmlspecialchars($v['title'] ?? '') ?>"
                         style="font-size:17px;padding:12px 14px"
                         oninput="autoSlug(this.value)"/>
                </div>
                <div class="fgrp">
                  <label>Slug <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-light)">(auto-generated)</span></label>
                  <input type="text" id="slugField" class="form-control"
                         style="background:var(--off-white);color:var(--text-light);cursor:default;font-family:monospace;font-size:13px"
                         value="<?= htmlspecialchars($v['slug'] ?? '') ?>" readonly/>
                </div>
              </div>
            </div>

            <!-- Content Editor -->
            <div class="form-section">
              <div class="form-sec-head">
                <i class="fas fa-align-left"></i><h3>Post Content</h3>
              </div>
              <div class="form-sec-body">
                <textarea name="content" id="postContent" rows="16"
                          style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:12px;font-size:14px;font-family:'DM Sans',sans-serif;resize:vertical;outline:none;box-sizing:border-box"><?= htmlspecialchars($v['content'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Excerpt -->
            <div class="form-section">
              <div class="form-sec-head">
                <i class="fas fa-file-alt"></i>
                <h3>Excerpt <span style="font-size:12px;font-weight:400;color:var(--text-light)">(Short Summary)</span></h3>
              </div>
              <div class="form-sec-body">
                <textarea name="excerpt" class="form-control" rows="3"
                          placeholder="Brief summary shown in blog listings…"><?= htmlspecialchars($v['excerpt'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Author & Read Time -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-user-edit"></i><h3>Author &amp; Read Time</h3></div>
              <div class="form-sec-body" style="flex-direction:row;gap:16px">
                <div class="fgrp" style="flex:1">
                  <label>Author</label>
                  <input type="text" name="author" class="form-control"
                         value="<?= htmlspecialchars($v['author'] ?? 'GPS Lanka Travels') ?>"/>
                </div>
                <div class="fgrp" style="width:130px">
                  <label>Read Time (min)</label>
                  <input type="number" name="read_time" class="form-control"
                         min="1" max="60" value="<?= (int)($v['read_time'] ?? 5) ?>"/>
                </div>
              </div>
            </div>

          </div>

          <!-- ── RIGHT: Sidebar ── -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Publish Settings -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-paper-plane"></i><h3>Publish Settings</h3></div>
              <div class="form-sec-body">
                <div class="fgrp">
                  <label>Status</label>
                  <select name="is_published" class="form-control">
                    <option value="1" <?= ($v['is_published'] ?? 0) ? 'selected' : '' ?>>Published</option>
                    <option value="0" <?= !($v['is_published'] ?? 1) ? 'selected' : '' ?>>Draft</option>
                  </select>
                </div>
                <div class="fgrp">
                  <label>Category</label>
                  <select name="category_id" class="form-control">
                    <option value="0">— Select Category —</option>
                    <?php foreach ($cats as $cat): ?>
                      <option value="<?= $cat['id'] ?>" <?= ($v['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <label class="check-row">
                  <input type="checkbox" name="is_featured" value="1" <?= ($v['is_featured'] ?? 0) ? 'checked' : '' ?>/>
                  <i class="fas fa-star" style="color:#c9a84c"></i>
                  <span>Mark as Featured</span>
                </label>
              </div>
            </div>

            <!-- Featured Image -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-image"></i><h3>Featured Image</h3></div>
              <div class="form-sec-body">
                <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImg(this)"/>
                <small style="font-size:11px;color:var(--text-light)">Recommended: 800×600px · Max 25 MB · JPG/PNG/WebP</small>
                <div id="previewWrap" style="<?= empty($v['image']) ? 'display:none' : '' ?>">
                  <img id="imgPreview"
                       src="<?= !empty($v['image']) ? SITE_URL.'/'.$v['image'] : '' ?>"
                       class="img-preview" alt=""/>
                </div>
              </div>
            </div>

            <!-- SEO -->
            <div class="form-section">
              <div class="form-sec-head"><i class="fas fa-search"></i><h3>SEO Optimizations</h3></div>
              <div class="form-sec-body">
                <div class="seo-box">
                  <div class="fgrp">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control"
                           placeholder="Leave empty to use post title"
                           value="<?= htmlspecialchars($v['_meta']['title'] ?? '') ?>"/>
                  </div>
                  <div class="fgrp">
                    <label>Meta Description</label>
                    <textarea name="meta_desc" class="form-control" rows="3"
                              placeholder="150–160 characters recommended"><?= htmlspecialchars($v['_meta']['desc'] ?? '') ?></textarea>
                  </div>
                  <div class="fgrp">
                    <label>Keywords</label>
                    <input type="text" name="meta_kw" class="form-control"
                           placeholder="keyword1, keyword2, …"
                           value="<?= htmlspecialchars($v['_meta']['kw'] ?? '') ?>"/>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:15px">
              <i class="fas <?= $editPost ? 'fa-save' : 'fa-paper-plane' ?>"></i>
              <?= $editPost ? 'Update Post' : 'Publish Post' ?>
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
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="width:56px;height:56px;background:var(--red-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--red)">
      <i class="fas fa-trash"></i>
    </div>
    <h3 style="margin-bottom:8px;color:var(--text-dark)">Delete Post?</h3>
    <p id="delModalText" style="color:var(--text-light);font-size:13.5px;margin-bottom:24px;line-height:1.6"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
      <a id="delConfirmBtn" href="#" class="btn" style="background:var(--red);color:#fff;min-width:100px">Delete</a>
    </div>
  </div>
</div>

<!-- TinyMCE rich text editor -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js"></script>
<script src="js/admin.js"></script>
<script>
<?php if ($action !== 'list'): ?>
tinymce.init({
  selector: '#postContent',
  height: 440,
  menubar: true,
  plugins: 'lists link image table code wordcount',
  toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link image table | alignleft aligncenter alignright | code',
  content_style: "body { font-family: 'DM Sans', sans-serif; font-size: 15px; line-height: 1.7; color: #2c3e50; padding: 12px; }",
  branding: false,
  promotion: false,
  setup: function(editor) {
    editor.on('change', function() { editor.save(); });
  }
});

function autoSlug(val) {
  const slug = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  document.getElementById('slugField').value = slug;
}

function previewImg(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('imgPreview').src = e.target.result;
      document.getElementById('previewWrap').style.display = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
<?php endif; ?>

function confirmDelete(id, title) {
  document.getElementById('delModalText').textContent =
    'Are you sure you want to delete "' + title + '"? This cannot be undone.';
  document.getElementById('delConfirmBtn').href = 'blog.php?action=delete&id=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
