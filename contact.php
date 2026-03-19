<?php
require_once 'includes/config.php';
$currentPage = 'contact';

$phone    = setting('site_phone',    '+94 77 048 9956');
$email    = setting('site_email',    'info@gpslankatravels.com');
$whatsapp = setting('site_whatsapp', '94770489956');
$address  = setting('site_address',  '289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka');
$hours    = setting('business_hours','Mon – Sun: 8:00 AM – 8:00 PM');
$mapEmbed = setting('google_maps_embed');
$fbUrl    = setting('facebook_url',    '');
$igUrl    = setting('instagram_url',  '');
$ytUrl    = setting('youtube_url',    '');
$tkUrl    = setting('tiktok_url',     '');
$twUrl    = setting('twitter_url',    '');
$taUrl    = setting('tripadvisor_url','');
$siteTitle = setting('site_name', 'GPS Lanka Travels');

/* ── WHY CHOOSE US ── */
$whyUs = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM why_us WHERE is_active=1 ORDER BY sort_order");
    if ($r) $whyUs = $r->fetch_all(MYSQLI_ASSOC);
}

$success = false;
$errors  = [];

/* ── HANDLE FORM SUBMISSION ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name        = trim($_POST['name']        ?? '');
    $emailInput  = trim($_POST['email']       ?? '');
    $phoneInput  = trim($_POST['phone']       ?? '');
    $tourType    = trim($_POST['tour_type']   ?? '');
    $travelDate  = trim($_POST['travel_date'] ?? '');
    $persons     = trim($_POST['persons']     ?? '');
    $budget      = trim($_POST['budget']      ?? '');
    $message     = trim($_POST['message']     ?? '');

    /* Validate */
    if (!$name)                         $errors[] = 'Full name is required.';
    if (!$emailInput)                   $errors[] = 'Email address is required.';
    elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (!$message)                      $errors[] = 'Message is required.';

    /* Cloudflare Turnstile */
    $tsKey = setting('turnstile_secret');
    if ($tsKey && !empty($_POST['cf-turnstile-response'])) {
        $verify = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false,
            stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query(['secret' => $tsKey, 'response' => $_POST['cf-turnstile-response']])]]));
        $res = $verify ? json_decode($verify, true) : null;
        if (!$res || !$res['success']) $errors[] = 'Security check failed. Please try again.';
    }

    if (!$errors && $conn) {
        $n  = $conn->real_escape_string($name);
        $em = $conn->real_escape_string($emailInput);
        $ph = $conn->real_escape_string($phoneInput);
        $tt = $conn->real_escape_string($tourType);
        $td = $travelDate ?: 'NULL';
        $ps = $conn->real_escape_string($persons);
        $bg = $conn->real_escape_string($budget);
        $ms = $conn->real_escape_string($message);
        $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
        $dateVal = $td === 'NULL' ? 'NULL' : "'$td'";
        $conn->query("INSERT INTO enquiries (name,email,phone,tour_type,travel_date,persons,budget,message,ip_address)
                       VALUES ('$n','$em','$ph','$tt',$dateVal,'$ps','$bg','$ms','$ip')");
        header('Location: contact.php?sent=1#contact-form');
        exit;
    }
}

$success = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Contact GPS Lanka Travels | Get in touch to plan your perfect Sri Lanka tour."/>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="Contact Us | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Get in touch with GPS Lanka Travels to start planning your perfect Sri Lanka tour experience."/>
  <meta property="og:image"       content="<?= SITE_URL ?>/images/contact-hero.jpg"/>
  <meta property="og:url"         content="<?= SITE_URL ?>/contact.php"/>
  <title>Contact Us | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/contact.css"/>
  <?php if (setting('turnstile_site')): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  <?php endif; ?>
</head>
<body data-page="contact">

<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      Contact
    </div>
    <h1 class="page-hero-title">Let's <em>Connect</em></h1>
    <p class="page-hero-sub">We are here to help you plan your perfect Sri Lankan getaway</p>
  </div>
</section>


<!-- QUICK CONTACT STRIP -->
<div class="contact-strip">
  <div class="container" style="padding:0">
    <div class="contact-strip-grid">
      <a href="tel:<?= preg_replace('/[^0-9+]/', '', $phone) ?>" class="contact-strip-item">
        <div class="contact-strip-icon"><i class="fas fa-phone-alt"></i></div>
        <div>
          <div class="contact-strip-label">Call Us</div>
          <div class="contact-strip-value"><?= e($phone) ?><small><?= e($hours) ?></small></div>
        </div>
      </a>
      <a href="mailto:<?= e($email) ?>" class="contact-strip-item">
        <div class="contact-strip-icon"><i class="fas fa-envelope"></i></div>
        <div>
          <div class="contact-strip-label">Email Us</div>
          <div class="contact-strip-value"><?= e($email) ?><small>We reply within 24 hours</small></div>
        </div>
      </a>
      <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" class="contact-strip-item">
        <div class="contact-strip-icon"><i class="fab fa-whatsapp"></i></div>
        <div>
          <div class="contact-strip-label">WhatsApp</div>
          <div class="contact-strip-value">+<?= e($whatsapp) ?><small>Chat with us instantly</small></div>
        </div>
      </a>
    </div>
  </div>
</div>


<!-- MAIN CONTACT SECTION -->
<section class="contact-main section-pad">
  <div class="container">
    <div class="contact-grid">

      <!-- CONTACT FORM -->
      <div class="contact-form-wrap reveal-left" id="contact-form">
        <h2>Send Us a <em>Message</em></h2>
        <p class="contact-form-subtitle">Fill in the form below and our team will get back to you within 24 hours. For faster response, reach us on WhatsApp.</p>

        <?php if ($success): ?>
          <div class="form-success" style="display:flex">
            <i class="fas fa-check-circle"></i>
            <div><strong>Message sent successfully!</strong><br>Thank you for reaching out. Our team will contact you within 24 hours.</div>
          </div>
        <?php elseif ($errors): ?>
          <div class="form-success" style="display:flex;background:#fff0f0;border-color:#e74c3c;color:#c0392b">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= implode('<br>', array_map('e', $errors)) ?></div>
          </div>
        <?php endif; ?>

        <form id="contactForm" method="POST" action="contact.php">

          <div class="form-row">
            <div class="form-group">
              <label>Full Name <span>*</span></label>
              <input type="text" name="name" placeholder="Your full name" value="<?= e($_POST['name'] ?? '') ?>" required/>
            </div>
            <div class="form-group">
              <label>Phone / WhatsApp</label>
              <input type="tel" name="phone" placeholder="+1 234 567 8900" value="<?= e($_POST['phone'] ?? '') ?>"/>
            </div>
          </div>

          <div class="form-group">
            <label>Email Address <span>*</span></label>
            <input type="email" name="email" placeholder="your@email.com" value="<?= e($_POST['email'] ?? '') ?>" required/>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Tour Type</label>
              <select name="tour_type">
                <option value="">Select tour type…</option>
                <?php $tt = $_POST['tour_type'] ?? ''; foreach (['Day Tour','Multi-Day Package','Custom Private Tour','Honeymoon Package','Family Holiday','Wildlife Safari','Adventure Tour','Airport Transfer'] as $opt): ?>
                  <option<?= $tt === $opt ? ' selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Travel Date</label>
              <input type="date" name="travel_date" value="<?= e($_POST['travel_date'] ?? '') ?>"/>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Number of Persons</label>
              <select name="persons">
                <option value="">Select…</option>
                <?php foreach (['1 Person','2 Persons','3-4 Persons','5-8 Persons','9+ Persons'] as $opt): ?>
                  <option<?= ($_POST['persons'] ?? '') === $opt ? ' selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Budget (Per Person)</label>
              <select name="budget">
                <option value="">Select…</option>
                <?php foreach (['Under $100','$100 - $300','$300 - $600','$600 - $1000','$1000+'] as $opt): ?>
                  <option<?= ($_POST['budget'] ?? '') === $opt ? ' selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Your Message <span>*</span></label>
            <textarea name="message" placeholder="Tell us about your dream Sri Lanka trip…" required><?= e($_POST['message'] ?? '') ?></textarea>
          </div>

          <?php if (setting('turnstile_site')): ?>
            <div class="cf-turnstile" data-sitekey="<?= e(setting('turnstile_site')) ?>" style="margin-bottom:16px"></div>
          <?php endif; ?>

          <div class="form-submit-row">
            <button type="submit" name="contact_submit" class="contact-submit-btn">
              <i class="fas fa-paper-plane"></i>
              <span>Send Message</span>
            </button>
            <a href="https://wa.me/<?= e($whatsapp) ?>?text=Hi%2C%20I%27d%20like%20to%20enquire%20about%20a%20Sri%20Lanka%20tour" class="contact-wa-link" target="_blank">
              <i class="fab fa-whatsapp"></i> Or chat on WhatsApp
            </a>
          </div>

        </form>
      </div>


      <!-- INFO PANEL -->
      <div class="contact-info-panel">

        <div class="contact-info-card reveal-right">
          <h3>Get in Touch</h3>
          <div class="contact-info-list">
            <div class="contact-info-item">
              <div class="cii-icon"><i class="fas fa-map-marker-alt"></i></div>
              <div><div class="cii-label">Our Office</div><div class="cii-value"><?= nl2br(e($address)) ?></div></div>
            </div>
            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $phone) ?>" class="contact-info-item">
              <div class="cii-icon"><i class="fas fa-phone-alt"></i></div>
              <div><div class="cii-label">Phone</div><div class="cii-value"><?= e($phone) ?><small>Call or text anytime</small></div></div>
            </a>
            <a href="mailto:<?= e($email) ?>" class="contact-info-item">
              <div class="cii-icon"><i class="fas fa-envelope"></i></div>
              <div><div class="cii-label">Email</div><div class="cii-value"><?= e($email) ?><small>We reply within 24 hours</small></div></div>
            </a>
            <a href="https://wa.me/<?= e($whatsapp) ?>" class="contact-info-item" target="_blank">
              <div class="cii-icon"><i class="fab fa-whatsapp"></i></div>
              <div><div class="cii-label">WhatsApp</div><div class="cii-value">+<?= e($whatsapp) ?><small>Fastest way to reach us</small></div></div>
            </a>
          </div>
        </div>

        <?php
        $hoursJson = setting('business_hours_json');
        $hoursRows = $hoursJson ? (json_decode($hoursJson, true) ?: []) : [];
        if (!$hoursRows) $hoursRows = [
            ['days'=>'Monday - Friday',  'time'=>'8:00 AM - 8:00 PM', 'status'=>'open'],
            ['days'=>'Saturday',          'time'=>'8:00 AM - 8:00 PM', 'status'=>'open'],
            ['days'=>'Sunday',            'time'=>'8:00 AM - 8:00 PM', 'status'=>'open'],
            ['days'=>'Emergency Support', 'time'=>'24 / 7',            'status'=>'special'],
        ];
        ?>
        <div class="contact-info-card reveal-right">
          <h3>Business Hours</h3>
          <div class="hours-list">
            <?php foreach ($hoursRows as $hr):
              $st = $hr['status'] ?? 'open';
            ?>
            <div class="hours-item <?= $st === 'open' ? 'open' : ($st === 'closed' ? 'closed' : '') ?>"
                 <?= $st === 'special' ? 'style="background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.2)"' : '' ?>>
              <span class="hours-day"
                    <?= $st === 'special' ? 'style="color:var(--teal-dark);font-weight:600"' : '' ?>>
                <?php if ($st === 'special'): ?><i class="fas fa-headset" style="color:var(--gold);margin-right:6px"></i><?php endif; ?>
                <?= e($hr['days']) ?>
              </span>
              <span class="hours-time">
                <?= e($hr['time']) ?>
                <?php if ($st === 'open'): ?>
                  <span class="open-tag">Open</span>
                <?php elseif ($st === 'closed'): ?>
                  <span style="background:#fee2e2;color:#c0392b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px">Closed</span>
                <?php endif; ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="contact-info-card reveal-right">
          <h3>Follow Us</h3>
          <div class="social-links-grid">
            <a href="https://wa.me/<?= e($whatsapp) ?>" class="social-link-btn wa" target="_blank"><i class="fab fa-whatsapp"></i>WhatsApp</a>
            <?php if ($fbUrl): ?><a href="<?= e($fbUrl) ?>" class="social-link-btn fb" target="_blank"><i class="fab fa-facebook-f"></i>Facebook</a><?php endif; ?>
            <?php if ($igUrl): ?><a href="<?= e($igUrl) ?>" class="social-link-btn ig" target="_blank"><i class="fab fa-instagram"></i>Instagram</a><?php endif; ?>
            <?php if ($ytUrl): ?><a href="<?= e($ytUrl) ?>" class="social-link-btn yt" target="_blank"><i class="fab fa-youtube"></i>YouTube</a><?php endif; ?>
            <?php if ($tkUrl): ?><a href="<?= e($tkUrl) ?>" class="social-link-btn tk" target="_blank"><i class="fab fa-tiktok"></i>TikTok</a><?php endif; ?>
            <?php if ($twUrl): ?><a href="<?= e($twUrl) ?>" class="social-link-btn tw" target="_blank"><i class="fab fa-x-twitter"></i>Twitter / X</a><?php endif; ?>
            <?php if ($taUrl): ?><a href="<?= e($taUrl) ?>" class="social-link-btn ta" target="_blank"><i class="fab fa-tripadvisor"></i>TripAdvisor</a><?php endif; ?>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>


<!-- GOOGLE MAP -->
<div class="contact-map">
  <?php if ($mapEmbed): ?>
    <?= $mapEmbed ?>
  <?php else: ?>
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.4!2d80.0536!3d6.2368!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae173b0b348b44f%3A0x6c7fd8d8a3e9c7a9!2sAmbalangoda!5e0!3m2!1sen!2slk!4v1700000000000"
      allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="GPS Lanka Travels Location">
    </iframe>
  <?php endif; ?>
</div>


<!-- WHY CHOOSE US -->
<section class="contact-why section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">Our Promise</div>
      <h2 class="section-title reveal">Why Choose <em>Us?</em></h2>
      <p class="section-subtitle reveal">Six reasons why hundreds of travelers trust GPS Lanka Travels for their Sri Lanka adventures.</p>
    </div>
    <div class="contact-why-grid stagger-children">
      <?php if ($whyUs): foreach ($whyUs as $w): ?>
      <div class="contact-why-card reveal">
        <div class="cwc-icon"><i class="<?= e($w['icon']) ?>"></i></div>
        <div class="cwc-title"><?= e($w['title']) ?></div>
        <div class="cwc-desc"><?= e($w['description']) ?></div>
      </div>
      <?php endforeach; else: ?>
      <div class="contact-why-card reveal"><div class="cwc-icon"><i class="fas fa-shield-alt"></i></div><div class="cwc-title">SLTDA Licensed</div><div class="cwc-desc">Fully registered and licensed by the Sri Lanka Tourism Development Authority.</div></div>
      <div class="contact-why-card reveal"><div class="cwc-icon"><i class="fas fa-headset"></i></div><div class="cwc-title">24/7 Support</div><div class="cwc-desc">Our team is always reachable — day or night — throughout your entire journey.</div></div>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- SCENIC BANNER -->
<div class="scenic-banner">
  <div class="scenic-banner-text reveal">
    <h2>Your Dream Sri Lanka Trip<br><strong>Starts Here</strong></h2>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script>
<?php if ($success || $errors): ?>
document.addEventListener('DOMContentLoaded', function() {
  var el = document.getElementById('contact-form');
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>
</body>
</html>
