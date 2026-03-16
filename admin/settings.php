<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$activePage = 'settings';
$activeTab  = $_GET['tab'] ?? 'general';
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
function handleLogoUpload($conn, $s, $fileKey, $settingKey, $folder = 'images') {
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
    $tab     = $_POST['tab'] ?? 'general';
    $saveMsg = 'saved';

    switch ($tab) {

        case 'general':
            saveSetting($conn, 'site_name',    trim($_POST['site_name']    ?? ''));
            saveSetting($conn, 'site_tagline', trim($_POST['site_tagline'] ?? ''));
            saveSetting($conn, 'footer_about', trim($_POST['footer_about'] ?? ''));
            saveSetting($conn, 'footer_text',  trim($_POST['footer_text']  ?? ''));
            $err = handleLogoUpload($conn, $s, 'site_logo',    'site_logo',    'images');
            if ($err) { $saveMsg = 'error:' . $err; break; }
            $err = handleLogoUpload($conn, $s, 'site_favicon', 'site_favicon', 'images');
            if ($err) { $saveMsg = 'error:' . $err; break; }
            $err = handleLogoUpload($conn, $s, 'footer_logo',  'footer_logo',  'images');
            if ($err) { $saveMsg = 'error:' . $err; break; }
            break;

        case 'password':
            $adminId     = (int)($_SESSION['admin_id'] ?? 0);
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password']     ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';
            $r           = $conn->query("SELECT password FROM admin_users WHERE id=$adminId");
            $adminRow    = $r ? $r->fetch_assoc() : null;
            if (!$adminRow || !password_verify($currentPass, $adminRow['password'])) {
                $saveMsg = 'error:Current password is incorrect.';
            } elseif (strlen($newPass) < 8) {
                $saveMsg = 'error:New password must be at least 8 characters.';
            } elseif ($newPass !== $confirmPass) {
                $saveMsg = 'error:New passwords do not match.';
            } else {
                $hash  = password_hash($newPass, PASSWORD_BCRYPT);
                $hashE = $conn->real_escape_string($hash);
                $conn->query("UPDATE admin_users SET password='$hashE' WHERE id=$adminId");
            }
            break;

        case 'seo':
            foreach (['meta_title','meta_description','meta_keywords'] as $k)
                saveSetting($conn, $k, trim($_POST[$k] ?? ''));
            break;

        case 'contact':
            foreach (['site_email','site_phone','site_whatsapp','site_address','business_hours','google_maps_embed'] as $k)
                saveSetting($conn, $k, trim($_POST[$k] ?? ''));
            break;

        case 'smtp':
            foreach (['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','smtp_encryption'] as $k)
                saveSetting($conn, $k, trim($_POST[$k] ?? ''));
            break;

        case 'social':
            foreach (['facebook_url','instagram_url','tiktok_url','tripadvisor_url','youtube_url','twitter_url'] as $k)
                saveSetting($conn, $k, trim($_POST[$k] ?? ''));
            break;

        case 'security':
            saveSetting($conn, 'login_attempts_max',       trim($_POST['login_attempts_max']       ?? '5'));
            saveSetting($conn, 'admin_ip_whitelist',       trim($_POST['admin_ip_whitelist']       ?? ''));
            saveSetting($conn, 'session_timeout_min',      trim($_POST['session_timeout_min']      ?? '120'));
            saveSetting($conn, 'turnstile_site_key',       trim($_POST['turnstile_site_key']       ?? ''));
            saveSetting($conn, 'turnstile_secret_key',     trim($_POST['turnstile_secret_key']     ?? ''));
            break;

        case 'maintenance':
            saveSetting($conn, 'maintenance_mode',    isset($_POST['maintenance_mode']) ? '1' : '0');
            saveSetting($conn, 'maintenance_message', trim($_POST['maintenance_message'] ?? ''));
            saveSetting($conn, 'coming_soon_date',    trim($_POST['coming_soon_date']    ?? ''));
            break;

        case 'about':
            foreach (['homepage_about_title','homepage_about_subtitle','homepage_about_content',
                      'homepage_about_highlight1','homepage_about_highlight2','homepage_about_highlight3'] as $k)
                saveSetting($conn, $k, trim($_POST[$k] ?? ''));
            $err = handleLogoUpload($conn, $s, 'homepage_about_image', 'homepage_about_image', 'uploads/about');
            if ($err) { $saveMsg = 'error:' . $err; break; }
            break;
    }

    header("Location: settings.php?tab=$tab&msg=" . urlencode($saveMsg));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Settings | GPS Lanka Admin</title>
<link rel="icon" type="image/png" href="../images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
/* ── SETTINGS LAYOUT ── */
.settings-layout{display:grid;grid-template-columns:210px 1fr;gap:24px;align-items:start}
/* ── TABS ── */
.stabs{display:flex;flex-direction:column;gap:2px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:8px;position:sticky;top:80px}
.stabs a{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;font-weight:500;color:var(--text-mid);text-decoration:none;border-radius:8px;transition:background .15s,color .15s;white-space:nowrap}
.stabs a:hover{background:var(--off-white);color:var(--text-dark)}
.stabs a.active{background:var(--teal-pale,#e6f0f0);color:var(--teal);font-weight:600}
.stabs a i{font-size:13px;width:16px;text-align:center;flex-shrink:0}
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
.fgrp{display:flex;flex-direction:column;gap:5px}
.fgrp label{font-size:11px;font-weight:600;color:#888;letter-spacing:.7px;text-transform:uppercase}
.fgrp .hint,.hint{font-size:11px;color:var(--text-light);margin-top:2px}
.form-control{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-sizing:border-box}
.form-control:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,82,82,.07)}
/* ── LOGO UPLOAD ── */
.logo-upload-row{display:flex;align-items:center;gap:16px;padding:14px;border:1.5px solid var(--border);border-radius:12px;background:var(--off-white)}
.logo-thumb{width:64px;height:64px;border-radius:10px;object-fit:contain;background:#fff;border:1px solid var(--border);padding:4px;flex-shrink:0}
.logo-thumb-placeholder{width:64px;height:64px;border-radius:10px;background:#fff;border:1.5px dashed var(--border);display:flex;align-items:center;justify-content:center;color:#ccc;font-size:22px;flex-shrink:0}
.logo-upload-info{flex:1;display:flex;flex-direction:column;gap:6px}
.logo-upload-info span{font-size:11px;color:var(--text-light)}
/* ── TOGGLE ── */
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--off-white);border-radius:12px;border:1.5px solid var(--border)}
.toggle-row-info{display:flex;flex-direction:column;gap:2px}
.toggle-row-info strong{font-size:13px;color:var(--text-dark)}
.toggle-row-info span{font-size:12px;color:var(--text-light)}
.toggle-switch{position:relative;width:46px;height:26px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#ddd;border-radius:50px;cursor:pointer;transition:.3s}
.toggle-slider:before{content:'';position:absolute;width:20px;height:20px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)}
.toggle-switch input:checked + .toggle-slider{background:var(--teal)}
.toggle-switch input:checked + .toggle-slider:before{transform:translateX(20px)}
/* ── SAVE BAR ── */
.save-bar{position:sticky;bottom:0;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-top:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;z-index:100;margin:0 -24px -24px}
.save-bar-hint{font-size:12px;color:var(--text-light)}
/* ── PASSWORD STRENGTH ── */
.pw-strength{height:4px;border-radius:4px;margin-top:6px;transition:width .3s,background .3s;width:0;background:#ddd}
/* ── MAINTENANCE WARNING ── */
.maintenance-warning{background:#fff3cd;border:1.5px solid #ffc107;border-radius:12px;padding:14px 16px;display:flex;gap:12px;align-items:flex-start}
.maintenance-warning i{color:#d97706;font-size:18px;margin-top:1px;flex-shrink:0}
.maintenance-warning p{font-size:13px;color:#92400e;margin:0;line-height:1.5}
@media(max-width:900px){.frow.c2,.frow.c3{grid-template-columns:1fr}.save-bar{margin:0 -16px -16px;padding:12px 16px}.settings-layout{grid-template-columns:1fr}.stabs{flex-direction:row;flex-wrap:wrap;position:static;gap:4px}.stabs a{padding:8px 12px;font-size:12px}}
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
        <div class="topbar-title">Settings</div>
        <div class="topbar-breadcrumb">Admin / Settings</div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-content-inner">

      <?php
      $msgType = 'success'; $msgText = '';
      if ($msg === 'saved') { $msgText = 'Settings saved successfully.'; }
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
          <h1>Site Settings</h1>
          <p>Manage all website configuration, branding and integrations</p>
        </div>
      </div>

      <!-- SETTINGS LAYOUT -->
      <div class="settings-layout">

      <!-- LEFT: vertical tabs -->
      <div class="stabs">
        <?php
        $tabs = [
          'general'     => ['icon'=>'fa-sliders-h',    'label'=>'General'],
          'password'    => ['icon'=>'fa-key',           'label'=>'Account / Password'],
          'seo'         => ['icon'=>'fa-search',        'label'=>'SEO'],
          'contact'     => ['icon'=>'fa-phone',         'label'=>'Contact'],
          'smtp'        => ['icon'=>'fa-envelope',      'label'=>'Email (SMTP)'],
          'social'      => ['icon'=>'fa-share-alt',     'label'=>'Social Media'],
          'security'    => ['icon'=>'fa-shield-alt',    'label'=>'Security'],
          'maintenance' => ['icon'=>'fa-tools',         'label'=>'Maintenance'],
          'about'       => ['icon'=>'fa-info-circle',   'label'=>'Homepage About'],
        ];
        foreach ($tabs as $key => $t): ?>
          <a href="?tab=<?= $key ?>" class="<?= $activeTab === $key ? 'active' : '' ?>">
            <i class="fas <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- RIGHT: tab content -->
      <div>

      <!-- ═══════════════════════════════════ GENERAL ═══ -->
      <?php if ($activeTab === 'general'): ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tab" value="general"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Site Identity</h2>
            <p>Core branding used in the browser tab, header and throughout the site</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>Site Title</label>
                <input type="text" name="site_name" class="form-control"
                       value="<?= sv($s,'site_name') ?>" placeholder="GPS Lanka Travels"/>
              </div>
              <div class="fgrp">
                <label>Tagline</label>
                <input type="text" name="site_tagline" class="form-control"
                       value="<?= sv($s,'site_tagline') ?>" placeholder="Sri Lanka's Premier Tour Operator"/>
              </div>
            </div>

            <div class="fgrp">
              <label>Site Main Logo <span class="hint">(.png, .jpg, .webp)</span></label>
              <div class="logo-upload-row">
                <?php if (!empty($s['site_logo'])): ?>
                  <img src="<?= SITE_URL.'/'.$s['site_logo'] ?>" class="logo-thumb" id="logoPreview" alt="Logo"/>
                <?php else: ?>
                  <div class="logo-thumb-placeholder" id="logoPreviewPh"><i class="fas fa-image"></i></div>
                  <img src="" class="logo-thumb" id="logoPreview" alt="" style="display:none"/>
                <?php endif; ?>
                <div class="logo-upload-info">
                  <input type="file" name="site_logo" class="form-control" accept="image/*"
                         onchange="previewImg(this,'logoPreview','logoPreviewPh')"/>
                  <span>Recommended format: Transparent PNG. Used in Header.</span>
                </div>
              </div>
            </div>

            <div class="fgrp">
              <label>Site Favicon <span class="hint">(.png, .ico, .jpg)</span></label>
              <div class="logo-upload-row">
                <?php if (!empty($s['site_favicon'])): ?>
                  <img src="<?= SITE_URL.'/'.$s['site_favicon'] ?>" class="logo-thumb" id="faviconPreview" alt="Favicon"/>
                <?php else: ?>
                  <div class="logo-thumb-placeholder" id="faviconPreviewPh"><i class="fas fa-star"></i></div>
                  <img src="" class="logo-thumb" id="faviconPreview" alt="" style="display:none"/>
                <?php endif; ?>
                <div class="logo-upload-info">
                  <input type="file" name="site_favicon" class="form-control" accept="image/*"
                         onchange="previewImg(this,'faviconPreview','faviconPreviewPh')"/>
                  <span>Recommended size: 32x32px or 16x16px.</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Footer Settings</h2>
            <p>Branding and text displayed in the website footer</p>
          </div>
          <div class="settings-section-body">
            <div class="fgrp">
              <label>Footer Logo <span class="hint">(.png, .jpg, .webp)</span></label>
              <div class="logo-upload-row">
                <?php if (!empty($s['footer_logo'])): ?>
                  <img src="<?= SITE_URL.'/'.$s['footer_logo'] ?>" class="logo-thumb" id="footerLogoPreview" alt="Footer Logo"/>
                <?php else: ?>
                  <div class="logo-thumb-placeholder" id="footerLogoPreviewPh"><i class="fas fa-image"></i></div>
                  <img src="" class="logo-thumb" id="footerLogoPreview" alt="" style="display:none"/>
                <?php endif; ?>
                <div class="logo-upload-info">
                  <input type="file" name="footer_logo" class="form-control" accept="image/*"
                         onchange="previewImg(this,'footerLogoPreview','footerLogoPreviewPh')"/>
                  <span>Optional. If not set, the Main Site Logo will be used in the footer.</span>
                </div>
              </div>
            </div>

            <div class="fgrp">
              <label>Footer About Text</label>
              <textarea name="footer_about" class="form-control" rows="3"
                        placeholder="A short description of your business for the footer…"><?= sv($s,'footer_about') ?></textarea>
            </div>

            <div class="fgrp">
              <label>Footer Copyright Text</label>
              <input type="text" name="footer_text" class="form-control"
                     value="<?= sv($s,'footer_text') ?>"
                     placeholder="&copy; All Rights Reserved | GPS Lanka Travels"/>
              <span class="hint">You can use HTML entities like &amp;copy; for standard symbols.</span>
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

      <!-- ═══════════════════════════════════ PASSWORD ═══ -->
      <?php elseif ($activeTab === 'password'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="password"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Change Password</h2>
            <p>Update your admin panel login password</p>
          </div>
          <div class="settings-section-body" style="max-width:480px">
            <div class="fgrp">
              <label>Current Password</label>
              <input type="password" name="current_password" class="form-control"
                     placeholder="Enter current password" autocomplete="current-password"/>
            </div>
            <div class="fgrp">
              <label>New Password</label>
              <input type="password" name="new_password" id="newPw" class="form-control"
                     placeholder="Min. 8 characters" autocomplete="new-password"
                     oninput="checkStrength(this.value)"/>
              <div id="pwStrength" class="pw-strength"></div>
              <span class="hint" id="pwStrengthLabel"></span>
            </div>
            <div class="fgrp">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control"
                     placeholder="Repeat new password" autocomplete="new-password"/>
            </div>
            <div style="background:var(--off-white);border-radius:10px;padding:14px;font-size:12px;color:var(--text-light);line-height:1.7">
              <strong style="color:var(--text-mid);display:block;margin-bottom:4px">
                <i class="fas fa-lock" style="color:var(--teal)"></i> Password Tips
              </strong>
              Use at least 8 characters. Mix uppercase, lowercase, numbers and symbols for a strong password.
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">You will remain logged in after changing your password.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-key"></i> Update Password
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ SEO ═══ -->
      <?php elseif ($activeTab === 'seo'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="seo"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>SEO &amp; Meta Tags</h2>
            <p>Defaults used when a page has no specific meta data configured</p>
          </div>
          <div class="settings-section-body">
            <div class="fgrp">
              <label>Default Meta Title</label>
              <input type="text" name="meta_title" id="metaTitle" class="form-control"
                     value="<?= sv($s,'meta_title') ?>"
                     placeholder="GPS Lanka Travels | Sri Lanka Tour Operator"
                     oninput="countChars(this,'metaTitleCount',60)"/>
              <div id="metaTitleCount" class="hint"></div>
            </div>
            <div class="fgrp">
              <label>Meta Description <span class="hint">150–160 characters recommended</span></label>
              <textarea name="meta_description" id="metaDesc" class="form-control" rows="3"
                        placeholder="GPS Lanka Travels — your trusted inbound tour operator…"
                        oninput="countChars(this,'metaDescCount',160)"><?= sv($s,'meta_description') ?></textarea>
              <div id="metaDescCount" class="hint"></div>
            </div>
            <div class="fgrp">
              <label>Meta Keywords <span class="hint">Comma-separated</span></label>
              <input type="text" name="meta_keywords" class="form-control"
                     value="<?= sv($s,'meta_keywords') ?>"
                     placeholder="sri lanka tours, cultural tours, wildlife safari…"/>
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">Meta tags improve search engine visibility.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ CONTACT ═══ -->
      <?php elseif ($activeTab === 'contact'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="contact"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Contact Information</h2>
            <p>Displayed on the website contact page, footer and enquiry forms</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>Email Address</label>
                <input type="email" name="site_email" class="form-control"
                       value="<?= sv($s,'site_email') ?>" placeholder="info@gpslankatravels.com"/>
              </div>
              <div class="fgrp">
                <label>Phone Number</label>
                <input type="text" name="site_phone" class="form-control"
                       value="<?= sv($s,'site_phone') ?>" placeholder="+94 77 048 9956"/>
              </div>
            </div>
            <div class="frow c2">
              <div class="fgrp">
                <label>WhatsApp Number <span class="hint">Numbers only with country code</span></label>
                <input type="text" name="site_whatsapp" class="form-control"
                       value="<?= sv($s,'site_whatsapp') ?>" placeholder="94770489956"/>
              </div>
              <div class="fgrp">
                <label>Business Hours</label>
                <input type="text" name="business_hours" class="form-control"
                       value="<?= sv($s,'business_hours') ?>" placeholder="Mon - Sun: 8:00 AM - 8:00 PM"/>
              </div>
            </div>
            <div class="fgrp">
              <label>Address</label>
              <textarea name="site_address" class="form-control" rows="2"
                        placeholder="289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka"><?= sv($s,'site_address') ?></textarea>
            </div>
            <div class="fgrp">
              <label>Google Maps Embed Code <span class="hint">Full &lt;iframe&gt; from Google Maps → Share → Embed</span></label>
              <textarea name="google_maps_embed" class="form-control" rows="3"
                        placeholder="&lt;iframe src=&quot;https://www.google.com/maps/embed?...&quot;&gt;&lt;/iframe&gt;"><?= sv($s,'google_maps_embed') ?></textarea>
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">Contact details appear on the Contact page and in the footer.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ SMTP ═══ -->
      <?php elseif ($activeTab === 'smtp'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="smtp"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Email (SMTP) Settings</h2>
            <p>Used to send enquiry confirmations and notifications via your mail server</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control"
                       value="<?= sv($s,'smtp_host') ?>" placeholder="smtp.gmail.com"/>
              </div>
              <div class="fgrp">
                <label>SMTP Port</label>
                <input type="number" name="smtp_port" class="form-control"
                       value="<?= sv($s,'smtp_port','587') ?>" placeholder="587"/>
              </div>
            </div>
            <div class="frow c2">
              <div class="fgrp">
                <label>SMTP Username</label>
                <input type="text" name="smtp_user" class="form-control"
                       value="<?= sv($s,'smtp_user') ?>" placeholder="your@email.com" autocomplete="off"/>
              </div>
              <div class="fgrp">
                <label>SMTP Password</label>
                <input type="password" name="smtp_pass" class="form-control"
                       value="<?= sv($s,'smtp_pass') ?>" placeholder="App password or SMTP password"
                       autocomplete="new-password"/>
              </div>
            </div>
            <div class="frow c3">
              <div class="fgrp">
                <label>From Name</label>
                <input type="text" name="smtp_from_name" class="form-control"
                       value="<?= sv($s,'smtp_from_name','GPS Lanka Travels') ?>"
                       placeholder="GPS Lanka Travels"/>
              </div>
              <div class="fgrp">
                <label>From Email</label>
                <input type="email" name="smtp_from_email" class="form-control"
                       value="<?= sv($s,'smtp_from_email') ?>"
                       placeholder="noreply@gpslankatravels.com"/>
              </div>
              <div class="fgrp">
                <label>Encryption</label>
                <select name="smtp_encryption" class="form-control">
                  <?php foreach (['tls'=>'TLS (Recommended)','ssl'=>'SSL','none'=>'None'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= ($s['smtp_encryption'] ?? 'tls') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div style="background:var(--off-white);border-radius:10px;padding:14px;font-size:12px;color:var(--text-light);line-height:1.7">
              <strong style="color:var(--text-mid);display:block;margin-bottom:4px">
                <i class="fas fa-lightbulb" style="color:var(--teal)"></i> Gmail Tip
              </strong>
              For Gmail use <strong>smtp.gmail.com</strong>, port <strong>587</strong>, TLS, and an App Password instead of your regular password.
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">SMTP is used for automated email notifications.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ SOCIAL ═══ -->
      <?php elseif ($activeTab === 'social'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="social"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Social Media Links</h2>
            <p>Displayed in the website header, footer and contact page</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label><i class="fab fa-facebook" style="color:#3b5998;width:16px"></i> Facebook URL</label>
                <input type="url" name="facebook_url" class="form-control"
                       value="<?= sv($s,'facebook_url') ?>" placeholder="https://facebook.com/…"/>
              </div>
              <div class="fgrp">
                <label><i class="fab fa-instagram" style="color:#e1306c;width:16px"></i> Instagram URL</label>
                <input type="url" name="instagram_url" class="form-control"
                       value="<?= sv($s,'instagram_url') ?>" placeholder="https://instagram.com/…"/>
              </div>
            </div>
            <div class="frow c2">
              <div class="fgrp">
                <label><i class="fab fa-tiktok" style="color:#010101;width:16px"></i> TikTok URL</label>
                <input type="url" name="tiktok_url" class="form-control"
                       value="<?= sv($s,'tiktok_url') ?>" placeholder="https://tiktok.com/@…"/>
              </div>
              <div class="fgrp">
                <label><i class="fab fa-tripadvisor" style="color:#34e0a1;width:16px"></i> TripAdvisor URL</label>
                <input type="url" name="tripadvisor_url" class="form-control"
                       value="<?= sv($s,'tripadvisor_url') ?>" placeholder="https://tripadvisor.com/…"/>
              </div>
            </div>
            <div class="frow c2">
              <div class="fgrp">
                <label><i class="fab fa-youtube" style="color:#ff0000;width:16px"></i> YouTube URL</label>
                <input type="url" name="youtube_url" class="form-control"
                       value="<?= sv($s,'youtube_url') ?>" placeholder="https://youtube.com/@…"/>
              </div>
              <div class="fgrp">
                <label><i class="fab fa-x-twitter" style="color:#000;width:16px"></i> X / Twitter URL</label>
                <input type="url" name="twitter_url" class="form-control"
                       value="<?= sv($s,'twitter_url') ?>" placeholder="https://x.com/…"/>
              </div>
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">Leave blank to hide a social media icon.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ SECURITY ═══ -->
      <?php elseif ($activeTab === 'security'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="security"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Security Settings</h2>
            <p>Protect your admin panel with additional security controls</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>Max Login Attempts <span class="hint">Per IP before lock</span></label>
                <input type="number" name="login_attempts_max" class="form-control"
                       min="3" max="20" value="<?= sv($s,'login_attempts_max','5') ?>"/>
              </div>
              <div class="fgrp">
                <label>Session Timeout (minutes)</label>
                <input type="number" name="session_timeout_min" class="form-control"
                       min="15" max="480" value="<?= sv($s,'session_timeout_min','120') ?>"/>
              </div>
            </div>
            <div class="fgrp">
              <label>IP Whitelist <span class="hint">Comma-separated IPs allowed to access admin (leave blank = allow all)</span></label>
              <input type="text" name="admin_ip_whitelist" class="form-control"
                     value="<?= sv($s,'admin_ip_whitelist') ?>"
                     placeholder="192.168.1.1, 203.0.113.0"/>
              <span class="hint">Your current IP: <strong><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?></strong></span>
            </div>
            <div style="background:#fff3cd;border:1.5px solid #ffc107;border-radius:12px;padding:14px 16px;font-size:12px;color:#92400e;line-height:1.6">
              <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong>
              If you add an IP whitelist, make sure your own IP is included or you will be locked out of the admin panel.
            </div>
          </div>
        </div>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Security &amp; Bot Protection</h2>
            <p>Cloudflare Turnstile — a smart, privacy-friendly alternative to CAPTCHA</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>Cloudflare Turnstile Site Key</label>
                <input type="text" name="turnstile_site_key" class="form-control"
                       value="<?= sv($s,'turnstile_site_key') ?>"
                       placeholder="0x4AAAAAAC…"/>
              </div>
              <div class="fgrp">
                <label>Cloudflare Turnstile Secret Key</label>
                <input type="text" name="turnstile_secret_key" class="form-control"
                       value="<?= sv($s,'turnstile_secret_key') ?>"
                       placeholder="0x4AAAAAAC…"/>
              </div>
            </div>
            <span class="hint">
              <i class="fas fa-info-circle"></i>
              Cloudflare Turnstile is a smart, privacy-friendly alternative to CAPTCHA.
              Get your keys from the <a href="https://dash.cloudflare.com/" target="_blank" style="color:var(--teal)">Cloudflare Dashboard</a>.
            </span>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">Security settings help protect your admin panel.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ MAINTENANCE ═══ -->
      <?php elseif ($activeTab === 'maintenance'): ?>
      <form method="POST">
        <input type="hidden" name="tab" value="maintenance"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Maintenance / Coming Soon</h2>
            <p>Put the website in maintenance mode to show a holding page to visitors</p>
          </div>
          <div class="settings-section-body">
            <?php if (!empty($s['maintenance_mode']) && $s['maintenance_mode'] === '1'): ?>
            <div class="maintenance-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <p><strong>Maintenance mode is currently ON.</strong> Visitors see a holding page. The admin panel remains accessible. Turn it off when your site is ready.</p>
            </div>
            <?php endif; ?>

            <div class="toggle-row">
              <div class="toggle-row-info">
                <strong>Maintenance Mode</strong>
                <span>Replaces the public website with a maintenance / coming soon page</span>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="maintenance_mode" value="1"
                       <?= (!empty($s['maintenance_mode']) && $s['maintenance_mode'] === '1') ? 'checked' : '' ?>/>
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="fgrp">
              <label>Maintenance Message</label>
              <textarea name="maintenance_message" class="form-control" rows="3"
                        placeholder="We're currently upgrading our website. We'll be back shortly!"><?= sv($s,'maintenance_message') ?></textarea>
            </div>

            <div class="fgrp">
              <label>Coming Soon Date <span class="hint">Optional countdown target date</span></label>
              <input type="datetime-local" name="coming_soon_date" class="form-control"
                     value="<?= sv($s,'coming_soon_date') ?>"/>
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">Admins can always access the panel regardless of maintenance mode.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>

      <!-- ═══════════════════════════════════ ABOUT ═══ -->
      <?php elseif ($activeTab === 'about'): ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tab" value="about"/>

        <div class="settings-section">
          <div class="settings-section-head">
            <h2>Homepage About Section</h2>
            <p>Content displayed in the "About Us" section on the homepage</p>
          </div>
          <div class="settings-section-body">
            <div class="frow c2">
              <div class="fgrp">
                <label>Section Title</label>
                <input type="text" name="homepage_about_title" class="form-control"
                       value="<?= sv($s,'homepage_about_title') ?>"
                       placeholder="About GPS Lanka Travels"/>
              </div>
              <div class="fgrp">
                <label>Subtitle / Tagline</label>
                <input type="text" name="homepage_about_subtitle" class="form-control"
                       value="<?= sv($s,'homepage_about_subtitle') ?>"
                       placeholder="Your trusted travel partner in Sri Lanka"/>
              </div>
            </div>

            <div class="fgrp">
              <label>About Content</label>
              <textarea name="homepage_about_content" class="form-control" rows="5"
                        placeholder="Write about your company, mission and values…"><?= sv($s,'homepage_about_content') ?></textarea>
            </div>

            <div class="fgrp">
              <label>Key Highlights <span class="hint">Short bullet points shown as badges or stats</span></label>
              <div style="display:flex;flex-direction:column;gap:8px">
                <input type="text" name="homepage_about_highlight1" class="form-control"
                       value="<?= sv($s,'homepage_about_highlight1') ?>"
                       placeholder="e.g. 500+ Happy Guests"/>
                <input type="text" name="homepage_about_highlight2" class="form-control"
                       value="<?= sv($s,'homepage_about_highlight2') ?>"
                       placeholder="e.g. 10+ Years Experience"/>
                <input type="text" name="homepage_about_highlight3" class="form-control"
                       value="<?= sv($s,'homepage_about_highlight3') ?>"
                       placeholder="e.g. 50+ Tour Packages"/>
              </div>
            </div>

            <div class="fgrp">
              <label>About Section Image</label>
              <div class="logo-upload-row" style="align-items:flex-start">
                <?php if (!empty($s['homepage_about_image'])): ?>
                  <img src="<?= SITE_URL.'/'.$s['homepage_about_image'] ?>"
                       style="width:100px;height:70px;object-fit:cover;border-radius:8px;border:1px solid var(--border);flex-shrink:0"
                       id="aboutImgPreview" alt="About"/>
                <?php else: ?>
                  <div class="logo-thumb-placeholder" id="aboutImgPreviewPh"
                       style="width:100px;height:70px;border-radius:8px"><i class="fas fa-image"></i></div>
                  <img src="" style="width:100px;height:70px;object-fit:cover;border-radius:8px;border:1px solid var(--border);flex-shrink:0;display:none"
                       id="aboutImgPreview" alt="About"/>
                <?php endif; ?>
                <div class="logo-upload-info">
                  <input type="file" name="homepage_about_image" class="form-control" accept="image/*"
                         onchange="previewImg(this,'aboutImgPreview','aboutImgPreviewPh')"/>
                  <span>Recommended: 800×600px JPG or WebP.</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="save-bar">
          <span class="save-bar-hint">About content is shown on the homepage and About page.</span>
          <button type="submit" class="btn btn-primary" style="padding:11px 32px">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
      <?php endif; ?>

      </div><!-- end right content -->
      </div><!-- end settings-layout -->

    </div>
  </div>
</div>
</div>

<script src="js/admin.js"></script>
<script>
/* Image preview */
function previewImg(input, previewId, phId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById(previewId);
      const ph   = phId ? document.getElementById(phId) : null;
      if (prev) { prev.src = e.target.result; prev.style.display = ''; }
      if (ph)   ph.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

/* Char counter */
function countChars(el, targetId, warn) {
  const n   = el.value.length;
  const div = document.getElementById(targetId);
  if (!div) return;
  div.textContent = n + ' characters';
  div.style.color = n > warn ? 'var(--red)' : n > warn * 0.85 ? '#c9a84c' : 'var(--text-light)';
}

/* Init counters */
const mt = document.getElementById('metaTitle');
const md = document.getElementById('metaDesc');
if (mt && mt.value) countChars(mt, 'metaTitleCount', 60);
if (md && md.value)  countChars(md, 'metaDescCount',  160);

/* Password strength */
function checkStrength(pw) {
  const bar   = document.getElementById('pwStrength');
  const lbl   = document.getElementById('pwStrengthLabel');
  if (!bar) return;
  let score = 0;
  if (pw.length >= 8)        score++;
  if (pw.length >= 12)       score++;
  if (/[A-Z]/.test(pw))     score++;
  if (/[0-9]/.test(pw))     score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const levels = [
    {w:'20%', c:'var(--red)',   t:'Very weak'},
    {w:'40%', c:'#e07b39',     t:'Weak'},
    {w:'60%', c:'#c9a84c',     t:'Fair'},
    {w:'80%', c:'#5aab6b',     t:'Strong'},
    {w:'100%',c:'var(--teal)', t:'Very strong'},
  ];
  const lv = levels[Math.min(score, 4)];
  bar.style.width      = pw.length ? lv.w : '0';
  bar.style.background = lv.c;
  lbl.textContent      = pw.length ? lv.t : '';
  lbl.style.color      = lv.c;
}
</script>
</body>
</html>
