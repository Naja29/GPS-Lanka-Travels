<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'about-settings';
$msg        = $_GET['msg'] ?? '';

/* ── FETCH ALL SETTINGS ── */
$s   = [];
$res = $conn->query("SELECT skey, sval FROM settings");
if ($res) while ($row = $res->fetch_assoc()) $s[$row['skey']] = $row['sval'];

function sv($s, $key, $default = '') {
    return htmlspecialchars($s[$key] ?? $default);
}
function saveSetting($conn, $key, $val) {
    $k = $conn->real_escape_string($key);
    $v = $conn->real_escape_string($val);
    $conn->query("INSERT INTO settings (skey,sval) VALUES ('$k','$v') ON DUPLICATE KEY UPDATE sval='$v'");
}
function handleAboutImgUpload($conn, $s, $fileKey, $settingKey, $folder = 'uploads/about') {
    if (!empty($_FILES[$fileKey]['name'])) {
        $up = uploadImage($_FILES[$fileKey], $folder);
        if ($up['ok']) {
            if (!empty($s[$settingKey])) {
                $old = __DIR__ . '/../' . $s[$settingKey];
                if (file_exists($old)) unlink($old);
            }
            saveSetting($conn, $settingKey, $up['path']);
            return '';
        }
        return $up['msg'];
    }
    return '';
}

/* ── HANDLE POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saveMsg = 'saved';

    /* Story text fields */
    $textFields = [
        'about_story_tag','about_story_heading_line1','about_story_heading_line2',
        'about_story_para1','about_story_para2','about_story_para3',
        'about_founded_year','about_founded_label',
        /* Stats */
        'about_stat1_count','about_stat1_suffix','about_stat1_label',
        'about_stat2_count','about_stat2_suffix','about_stat2_label',
        'about_stat3_count','about_stat3_suffix','about_stat3_label',
        'about_stat4_count','about_stat4_suffix','about_stat4_label',
        /* Vision & Mission */
        'about_vision_text','about_mission_text',
        /* Home About */
        'home_about_heading','home_about_desc',
    ];
    foreach ($textFields as $k) {
        saveSetting($conn, $k, trim($_POST[$k] ?? ''));
    }

    /* Image uploads */
    $err = handleAboutImgUpload($conn, $s, 'about_img1', 'about_img1', 'uploads/about');
    if ($err) { $saveMsg = 'error:' . $err; goto done; }
    $err = handleAboutImgUpload($conn, $s, 'about_img2', 'about_img2', 'uploads/about');
    if ($err) { $saveMsg = 'error:' . $err; goto done; }
    $err = handleAboutImgUpload($conn, $s, 'about_img3', 'about_img3', 'uploads/about');
    if ($err) { $saveMsg = 'error:' . $err; goto done; }
    $err = handleAboutImgUpload($conn, $s, 'home_img1', 'home_img1', 'uploads/about');
    if ($err) { $saveMsg = 'error:' . $err; goto done; }
    $err = handleAboutImgUpload($conn, $s, 'home_img2', 'home_img2', 'uploads/about');
    if ($err) { $saveMsg = 'error:' . $err; goto done; }
    $err = handleAboutImgUpload($conn, $s, 'home_img3', 'home_img3', 'uploads/about');
    if ($err) { $saveMsg = 'error:' . $err; goto done; }

    done:
    header("Location: about-settings.php?msg=" . urlencode($saveMsg));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>About Page Settings | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
/* ── SECTION ── */
.settings-section{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:22px}
.settings-section-head{padding:16px 22px;border-bottom:1px solid var(--border)}
.settings-section-head h2{font-size:15px;font-weight:700;color:var(--text-dark);margin:0 0 2px}
.settings-section-head p{font-size:12px;color:var(--text-light);margin:0}
.settings-section-body{padding:22px;display:flex;flex-direction:column;gap:18px}
/* ── FIELDS ── */
.frow{display:grid;gap:18px}
.frow.c2{grid-template-columns:1fr 1fr}
.frow.c3{grid-template-columns:1fr 1fr 1fr}
.frow.c4{grid-template-columns:1fr 1fr 1fr 1fr}
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.fgrp .hint,.hint{font-size:11px;color:var(--text-light);margin-top:2px}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
/* ── IMAGE UPLOAD ── */
.img-upload-row{display:flex;align-items:center;gap:16px;padding:14px;border:1.5px solid var(--border);border-radius:12px;background:var(--off-white)}
.img-thumb{width:60px;height:60px;border-radius:10px;object-fit:cover;background:#fff;border:1px solid var(--border);flex-shrink:0}
.img-thumb-placeholder{width:60px;height:60px;border-radius:10px;background:#fff;border:1.5px dashed var(--border);display:flex;align-items:center;justify-content:center;color:#ccc;font-size:22px;flex-shrink:0}
.img-upload-info{flex:1;display:flex;flex-direction:column;gap:6px}
.img-upload-info span{font-size:11px;color:var(--text-light)}
/* ── IMG PREVIEWS GRID ── */
.img-previews-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.img-preview-cell{display:flex;flex-direction:column;gap:8px}
.img-preview-cell label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
/* ── SAVE BAR ── */
.save-bar{position:sticky;bottom:0;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-top:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;z-index:100;margin:0 -24px -24px}
.save-bar-hint{font-size:12px;color:var(--text-light)}
@media(max-width:900px){.frow.c2,.frow.c3,.frow.c4{grid-template-columns:1fr}.img-previews-grid{grid-template-columns:1fr}.save-bar{margin:0 -16px -16px;padding:12px 16px}}
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
        <div class="topbar-title">About Page Settings</div>
        <div class="topbar-breadcrumb">Admin / About Page</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php
      $msgType = 'success'; $msgText = '';
      if ($msg === 'saved') { $msgText = 'About page settings saved successfully.'; }
      elseif (strpos($msg, 'error:') === 0) { $msgType = 'danger'; $msgText = substr($msg, 6); }
      ?>
      <?php if ($msgText): ?>
        <div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px">
          <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
          <?= htmlspecialchars($msgText) ?>
        </div>
      <?php endif; ?>

      <div class="page-header" style="margin-bottom:20px">
        <div class="page-header-left">
          <h1>About Page Settings</h1>
          <p>Manage all content displayed on the About Us page and the home page about section</p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">

        <!--  STORY IMAGES  -->
        <div class="settings-section">
          <div class="settings-section-head">
            <h2><i class="fas fa-images" style="color:var(--teal);margin-right:6px"></i>Story Section Images</h2>
            <p>Three images displayed in the photo collage on the About Us story section</p>
          </div>
          <div class="settings-section-body">

            <div class="img-previews-grid">

              <!-- Image 1 -->
              <div class="img-preview-cell">
                <label>Image 1 <span class="hint">(Main / Top-Right)</span></label>
                <div class="img-upload-row">
                  <?php if (!empty($s['about_img1'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($s['about_img1']) ?>"
                         class="img-thumb" id="aboutImg1Preview" alt="About Image 1"/>
                  <?php else: ?>
                    <div class="img-thumb-placeholder" id="aboutImg1PreviewPh"><i class="fas fa-image"></i></div>
                    <img src="" class="img-thumb" id="aboutImg1Preview" alt="" style="display:none"/>
                  <?php endif; ?>
                  <div class="img-upload-info">
                    <input type="file" name="about_img1" class="form-control" accept="image/*"
                           onchange="previewImg(this,'aboutImg1Preview','aboutImg1PreviewPh')"/>
                    <span>Shown top-right. ~65% wide, ~68% tall.</span>
                  </div>
                </div>
              </div>

              <!-- Image 2 -->
              <div class="img-preview-cell">
                <label>Image 2 <span class="hint">(Bottom-Left)</span></label>
                <div class="img-upload-row">
                  <?php if (!empty($s['about_img2'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($s['about_img2']) ?>"
                         class="img-thumb" id="aboutImg2Preview" alt="About Image 2"/>
                  <?php else: ?>
                    <div class="img-thumb-placeholder" id="aboutImg2PreviewPh"><i class="fas fa-image"></i></div>
                    <img src="" class="img-thumb" id="aboutImg2Preview" alt="" style="display:none"/>
                  <?php endif; ?>
                  <div class="img-upload-info">
                    <input type="file" name="about_img2" class="form-control" accept="image/*"
                           onchange="previewImg(this,'aboutImg2Preview','aboutImg2PreviewPh')"/>
                    <span>Shown bottom-left. ~55% wide, ~52% tall.</span>
                  </div>
                </div>
              </div>

              <!-- Image 3 -->
              <div class="img-preview-cell">
                <label>Image 3 <span class="hint">(Center Circle)</span></label>
                <div class="img-upload-row">
                  <?php if (!empty($s['about_img3'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($s['about_img3']) ?>"
                         class="img-thumb" id="aboutImg3Preview" alt="About Image 3"/>
                  <?php else: ?>
                    <div class="img-thumb-placeholder" id="aboutImg3PreviewPh"><i class="fas fa-image"></i></div>
                    <img src="" class="img-thumb" id="aboutImg3Preview" alt="" style="display:none"/>
                  <?php endif; ?>
                  <div class="img-upload-info">
                    <input type="file" name="about_img3" class="form-control" accept="image/*"
                           onchange="previewImg(this,'aboutImg3Preview','aboutImg3PreviewPh')"/>
                    <span>Shown center as a circle badge. Square crop recommended.</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- ══════════════════════════════ STORY CONTENT ══ -->
        <div class="settings-section">
          <div class="settings-section-head">
            <h2><i class="fas fa-pen-nib" style="color:var(--teal);margin-right:6px"></i>Our Story — Text Content</h2>
            <p>Heading, tag, paragraphs and the founded badge text for the story section</p>
          </div>
          <div class="settings-section-body">

            <div class="frow c3">
              <div class="fgrp">
                <label>Section Tag</label>
                <input type="text" name="about_story_tag" class="form-control"
                       value="<?= sv($s,'about_story_tag','Our Story') ?>"
                       placeholder="Our Story"/>
              </div>
              <div class="fgrp">
                <label>Founded Year</label>
                <input type="text" name="about_founded_year" class="form-control"
                       value="<?= sv($s,'about_founded_year','2014') ?>"
                       placeholder="2014"/>
              </div>
              <div class="fgrp">
                <label>Founded Label</label>
                <input type="text" name="about_founded_label" class="form-control"
                       value="<?= sv($s,'about_founded_label','Est. in Sri Lanka') ?>"
                       placeholder="Est. in Sri Lanka"/>
              </div>
            </div>

            <div class="frow c2">
              <div class="fgrp">
                <label>Heading Line 1</label>
                <input type="text" name="about_story_heading_line1" class="form-control"
                       value="<?= sv($s,'about_story_heading_line1','A Passion for') ?>"
                       placeholder="A Passion for"/>
              </div>
              <div class="fgrp">
                <label>Heading Line 2 <span class="hint">(renders in italic)</span></label>
                <input type="text" name="about_story_heading_line2" class="form-control"
                       value="<?= sv($s,'about_story_heading_line2','Sharing Sri Lanka') ?>"
                       placeholder="Sharing Sri Lanka"/>
              </div>
            </div>

            <div class="fgrp">
              <label>Paragraph 1</label>
              <textarea name="about_story_para1" class="form-control" rows="4"
                        placeholder="First paragraph…"><?= sv($s,'about_story_para1','GPS Lanka Travels was born from a deep love for this beautiful island and a desire to share its wonders with the world. Founded in 2014, we began as a small, passionate team of local travel enthusiasts committed to one simple goal — giving every traveler a genuine, unforgettable Sri Lanka experience.') ?></textarea>
            </div>

            <div class="fgrp">
              <label>Paragraph 2</label>
              <textarea name="about_story_para2" class="form-control" rows="4"
                        placeholder="Second paragraph…"><?= sv($s,'about_story_para2','Over the years we have grown into one of Sri Lanka\'s most trusted inbound tour operators, serving hundreds of satisfied guests from over 30 countries. From luxury private tours to budget-friendly group adventures, every journey we craft is built on personal care, deep local knowledge and unwavering reliability.') ?></textarea>
            </div>

            <div class="fgrp">
              <label>Paragraph 3</label>
              <textarea name="about_story_para3" class="form-control" rows="4"
                        placeholder="Third paragraph…"><?= sv($s,'about_story_para3','We believe travel is not just about visiting places — it\'s about connecting with people, cultures and stories. That\'s why everything we do, from your first inquiry to your farewell at the airport, is handled with warmth, honesty and attention to detail.') ?></textarea>
            </div>

          </div>
        </div>

        <!-- ══════════════════════════════ STATS ══ -->
        <div class="settings-section">
          <div class="settings-section-head">
            <h2><i class="fas fa-chart-bar" style="color:var(--teal);margin-right:6px"></i>Statistics / Highlights</h2>
            <p>Four stat counters shown below the story paragraphs (e.g. 500+ Happy Guests)</p>
          </div>
          <div class="settings-section-body">

            <div class="frow c4">
              <div class="fgrp">
                <label>Stat 1 Count</label>
                <input type="text" name="about_stat1_count" class="form-control"
                       value="<?= sv($s,'about_stat1_count','500') ?>" placeholder="500"/>
              </div>
              <div class="fgrp">
                <label>Stat 1 Suffix</label>
                <input type="text" name="about_stat1_suffix" class="form-control"
                       value="<?= sv($s,'about_stat1_suffix','+') ?>" placeholder="+"/>
              </div>
              <div class="fgrp" style="grid-column:span 2">
                <label>Stat 1 Label</label>
                <input type="text" name="about_stat1_label" class="form-control"
                       value="<?= sv($s,'about_stat1_label','Happy Guests') ?>" placeholder="Happy Guests"/>
              </div>
            </div>

            <div class="frow c4">
              <div class="fgrp">
                <label>Stat 2 Count</label>
                <input type="text" name="about_stat2_count" class="form-control"
                       value="<?= sv($s,'about_stat2_count','30') ?>" placeholder="30"/>
              </div>
              <div class="fgrp">
                <label>Stat 2 Suffix</label>
                <input type="text" name="about_stat2_suffix" class="form-control"
                       value="<?= sv($s,'about_stat2_suffix','+') ?>" placeholder="+"/>
              </div>
              <div class="fgrp" style="grid-column:span 2">
                <label>Stat 2 Label</label>
                <input type="text" name="about_stat2_label" class="form-control"
                       value="<?= sv($s,'about_stat2_label','Countries Served') ?>" placeholder="Countries Served"/>
              </div>
            </div>

            <div class="frow c4">
              <div class="fgrp">
                <label>Stat 3 Count</label>
                <input type="text" name="about_stat3_count" class="form-control"
                       value="<?= sv($s,'about_stat3_count','10') ?>" placeholder="10"/>
              </div>
              <div class="fgrp">
                <label>Stat 3 Suffix</label>
                <input type="text" name="about_stat3_suffix" class="form-control"
                       value="<?= sv($s,'about_stat3_suffix','+') ?>" placeholder="+"/>
              </div>
              <div class="fgrp" style="grid-column:span 2">
                <label>Stat 3 Label</label>
                <input type="text" name="about_stat3_label" class="form-control"
                       value="<?= sv($s,'about_stat3_label','Years Experience') ?>" placeholder="Years Experience"/>
              </div>
            </div>

            <div class="frow c4">
              <div class="fgrp">
                <label>Stat 4 Count</label>
                <input type="text" name="about_stat4_count" class="form-control"
                       value="<?= sv($s,'about_stat4_count','50') ?>" placeholder="50"/>
              </div>
              <div class="fgrp">
                <label>Stat 4 Suffix</label>
                <input type="text" name="about_stat4_suffix" class="form-control"
                       value="<?= sv($s,'about_stat4_suffix','+') ?>" placeholder="+"/>
              </div>
              <div class="fgrp" style="grid-column:span 2">
                <label>Stat 4 Label</label>
                <input type="text" name="about_stat4_label" class="form-control"
                       value="<?= sv($s,'about_stat4_label','Tour Packages') ?>" placeholder="Tour Packages"/>
              </div>
            </div>

          </div>
        </div>

        <!-- ══════════════════════════════ VISION & MISSION ══ -->
        <div class="settings-section">
          <div class="settings-section-head">
            <h2><i class="fas fa-eye" style="color:var(--teal);margin-right:6px"></i>Vision &amp; Mission</h2>
            <p>Text shown in the Vision &amp; Mission cards on both the About page and the Home page</p>
          </div>
          <div class="settings-section-body">

            <div class="fgrp">
              <label>Vision Text</label>
              <textarea name="about_vision_text" class="form-control" rows="4"
                        placeholder="Our vision statement…"><?= sv($s,'about_vision_text','To be the most trusted and preferred travel partner for luxury and experiential travel, setting the gold standard for high-end trips in Sri Lanka.') ?></textarea>
            </div>

            <div class="fgrp">
              <label>Mission Text</label>
              <textarea name="about_mission_text" class="form-control" rows="4"
                        placeholder="Our mission statement…"><?= sv($s,'about_mission_text','To provide exceptional, personalized travel services that create unforgettable memories for our guests while actively promoting sustainable growth.') ?></textarea>
            </div>

          </div>
        </div>

        <!-- ══════════════════════════════ HOME ABOUT SECTION ══ -->
        <div class="settings-section">
          <div class="settings-section-head">
            <h2><i class="fas fa-home" style="color:var(--teal);margin-right:6px"></i>Home Page — About / Welcome Section</h2>
            <p>Separate images, heading and description for the Welcome section on the home page</p>
          </div>
          <div class="settings-section-body">

            <!-- Home Images -->
            <div class="img-previews-grid">

              <!-- Home Image 1 -->
              <div class="img-preview-cell">
                <label>Image 1 <span class="hint">(Main / Large)</span></label>
                <div class="img-upload-row">
                  <?php if (!empty($s['home_img1'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($s['home_img1']) ?>"
                         class="img-thumb" id="homeImg1Preview" alt="Home Image 1"/>
                  <?php else: ?>
                    <div class="img-thumb-placeholder" id="homeImg1PreviewPh"><i class="fas fa-image"></i></div>
                    <img src="" class="img-thumb" id="homeImg1Preview" alt="" style="display:none"/>
                  <?php endif; ?>
                  <div class="img-upload-info">
                    <input type="file" name="home_img1" class="form-control" accept="image/*"
                           onchange="previewImg(this,'homeImg1Preview','homeImg1PreviewPh')"/>
                    <span>Main large image on the left side.</span>
                  </div>
                </div>
              </div>

              <!-- Home Image 2 -->
              <div class="img-preview-cell">
                <label>Image 2 <span class="hint">(Small / Secondary)</span></label>
                <div class="img-upload-row">
                  <?php if (!empty($s['home_img2'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($s['home_img2']) ?>"
                         class="img-thumb" id="homeImg2Preview" alt="Home Image 2"/>
                  <?php else: ?>
                    <div class="img-thumb-placeholder" id="homeImg2PreviewPh"><i class="fas fa-image"></i></div>
                    <img src="" class="img-thumb" id="homeImg2Preview" alt="" style="display:none"/>
                  <?php endif; ?>
                  <div class="img-upload-info">
                    <input type="file" name="home_img2" class="form-control" accept="image/*"
                           onchange="previewImg(this,'homeImg2Preview','homeImg2PreviewPh')"/>
                    <span>Small secondary image.</span>
                  </div>
                </div>
              </div>

              <!-- Home Image 3 -->
              <div class="img-preview-cell">
                <label>Image 3 <span class="hint">(Circle)</span></label>
                <div class="img-upload-row">
                  <?php if (!empty($s['home_img3'])): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($s['home_img3']) ?>"
                         class="img-thumb" id="homeImg3Preview" alt="Home Image 3"/>
                  <?php else: ?>
                    <div class="img-thumb-placeholder" id="homeImg3PreviewPh"><i class="fas fa-image"></i></div>
                    <img src="" class="img-thumb" id="homeImg3Preview" alt="" style="display:none"/>
                  <?php endif; ?>
                  <div class="img-upload-info">
                    <input type="file" name="home_img3" class="form-control" accept="image/*"
                           onchange="previewImg(this,'homeImg3Preview','homeImg3PreviewPh')"/>
                    <span>Circle badge image. Square crop recommended.</span>
                  </div>
                </div>
              </div>

            </div>

            <div class="fgrp">
              <label>Section Heading</label>
              <input type="text" name="home_about_heading" class="form-control"
                     value="<?= sv($s,'home_about_heading','Welcome to GPS Lanka Travels') ?>"
                     placeholder="Welcome to GPS Lanka Travels"/>
            </div>

            <div class="fgrp">
              <label>Description Paragraph</label>
              <textarea name="home_about_desc" class="form-control" rows="5"
                        placeholder="Short welcome description for the home page…"><?= sv($s,'home_about_desc','We are dedicated to crafting exceptional Sri Lanka travel experiences. From the misty highlands of Nuwara Eliya to the golden shores of Mirissa, every journey we create is a masterpiece tailored just for you. Our team of passionate local experts ensures every detail is perfect — from the moment you land to your fond farewell.') ?></textarea>
            </div>

          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint"><i class="fas fa-info-circle"></i> Changes apply to the live website immediately.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>

      </form>

    </div><!-- /.admin-content-inner -->
  </div><!-- /.admin-content -->
</div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<script>
/* Sidebar toggle */
const sidebarToggle = document.getElementById('sidebarToggle');
const adminSidebar  = document.getElementById('adminSidebar');
const sidebarOverlay= document.getElementById('sidebarOverlay');
if (sidebarToggle) {
  sidebarToggle.addEventListener('click', () => {
    adminSidebar.classList.toggle('open');
    sidebarOverlay.classList.toggle('visible');
  });
}
if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', () => {
    adminSidebar.classList.remove('open');
    sidebarOverlay.classList.remove('visible');
  });
}

/* Image preview helper */
function previewImg(input, previewId, placeholderId) {
  const preview = document.getElementById(previewId);
  const ph      = placeholderId ? document.getElementById(placeholderId) : null;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      if (preview) { preview.src = e.target.result; preview.style.display = ''; }
      if (ph)      { ph.style.display = 'none'; }
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
