<?php
require_once 'includes/config.php';
$currentPage = 'privacy';
$siteTitle   = setting('site_name', 'GPS Lanka Travels');
$siteEmail   = setting('site_email', 'info@gpslankatravels.com');
$siteAddress = setting('site_address', '289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Privacy Policy | <?= e($siteTitle) ?></title>
  <meta name="description" content="Privacy Policy for <?= e($siteTitle) ?>. Learn how we collect, use and protect your personal information."/>
  <meta property="og:title"       content="Privacy Policy | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Learn how GPS Lanka Travels collects, uses and protects your personal information."/>
  <meta property="og:type"        content="website"/>
  <link rel="icon"       type="image/png"  href="images/favicon.png"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/legal.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-content">
    <div class="page-breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep"><i class="fas fa-chevron-right"></i></span>
      Privacy Policy
    </div>
    <h1 class="page-hero-title">Privacy <em>Policy</em></h1>
    <p class="page-hero-sub">How we collect, use and protect your personal information</p>
  </div>
</section>

<!-- CONTENT -->
<section class="legal-wrap">
  <div class="legal-inner">

    <!-- Meta bar -->
    <div class="legal-meta">
      <span><i class="fas fa-calendar-alt"></i> Last updated: <strong>January 2025</strong></span>
      <span><i class="fas fa-shield-alt"></i> Your privacy is important to us</span>
    </div>

    <!-- Lead -->
    <p class="legal-lead">
      Welcome to <?= e($siteTitle) ?>. We are committed to protecting your privacy and ensuring that your personal information is handled safely and responsibly. This Privacy Policy explains how we collect, use, disclose and safeguard your information when you visit our website or make a booking with us.
    </p>

    <!-- 1 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">1</span> Information We Collect</h2>
      <p class="legal-sub-title">Information You Provide</p>
      <p class="legal-text">We collect information you voluntarily provide when you:</p>
      <ul class="legal-list">
        <li>Fill out our contact or enquiry forms</li>
        <li>Submit a tour booking request</li>
        <li>Subscribe to our newsletter</li>
        <li>Communicate with us via email, phone or WhatsApp</li>
      </ul>
      <p class="legal-text">This may include your name, email address, phone number, travel dates, number of travellers, and any special requirements you share with us.</p>

      <p class="legal-sub-title">Information Collected Automatically</p>
      <p class="legal-text">When you visit our website, we may automatically collect certain technical information such as your IP address, browser type, pages visited, and time spent on the site. This is used solely for improving website performance and user experience.</p>
    </div>

    <!-- 2 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">2</span> How We Use Your Information</h2>
      <p class="legal-text">We use the information we collect to:</p>
      <ul class="legal-list">
        <li>Respond to your enquiries and booking requests</li>
        <li>Plan and organise your personalised tour itinerary</li>
        <li>Send booking confirmations and travel information</li>
        <li>Send our newsletter (only if you have subscribed)</li>
        <li>Improve our website and the quality of our services</li>
        <li>Comply with any applicable legal obligations</li>
      </ul>
      <p class="legal-text">We will never sell, rent or trade your personal information to third parties for marketing purposes.</p>
    </div>

    <!-- 3 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">3</span> How We Share Your Information</h2>
      <p class="legal-text">We may share your information only in the following limited circumstances:</p>
      <ul class="legal-list">
        <li><strong>Service providers:</strong> Trusted local partners such as hotels, transport providers and guides who need the information to fulfil your tour. They are bound to keep your data confidential.</li>
        <li><strong>Legal requirements:</strong> If required by law or to protect our rights and the safety of our clients.</li>
      </ul>
    </div>

    <!-- 4 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">4</span> Data Security</h2>
      <p class="legal-text">We implement appropriate technical and organisational measures to protect your personal information against unauthorised access, alteration, disclosure or destruction. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p>
    </div>

    <!-- 5 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">5</span> Cookies</h2>
      <p class="legal-text">Our website may use cookies to enhance your browsing experience. Cookies are small text files stored on your device. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. Note that some parts of the website may not function properly if cookies are disabled.</p>
    </div>

    <!-- 6 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">6</span> Newsletter &amp; Marketing Emails</h2>
      <p class="legal-text">If you subscribe to our newsletter, you can unsubscribe at any time by contacting us at <a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a>. We will promptly remove you from our mailing list.</p>
    </div>

    <!-- 7 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">7</span> Third-Party Links</h2>
      <p class="legal-text">Our website may contain links to third-party websites such as TripAdvisor, Google Maps and social media platforms. We are not responsible for the privacy practices of those sites and encourage you to review their privacy policies independently.</p>
    </div>

    <!-- 8 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">8</span> Children's Privacy</h2>
      <p class="legal-text">Our services are not directed to children under the age of 13. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us and we will delete it promptly.</p>
    </div>

    <!-- 9 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">9</span> Your Rights</h2>
      <p class="legal-text">You have the right to:</p>
      <ul class="legal-list">
        <li>Request access to the personal information we hold about you</li>
        <li>Request correction of any inaccurate information</li>
        <li>Request deletion of your personal information</li>
        <li>Withdraw consent for newsletter communications at any time</li>
      </ul>
      <p class="legal-text">To exercise any of these rights, please contact us using the details below.</p>
    </div>

    <!-- 10 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">10</span> Changes to This Policy</h2>
      <p class="legal-text">We may update this Privacy Policy from time to time. Any changes will be posted on this page with a revised "Last updated" date. We encourage you to review this page periodically to stay informed.</p>
    </div>

    <!-- 11 Contact -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">11</span> Contact Us</h2>
      <p class="legal-text">If you have any questions or concerns about this Privacy Policy, please reach out to us:</p>
      <div class="legal-contact-box">
        <div class="legal-contact-item">
          <i class="fas fa-envelope"></i>
          <span>Email: <a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a></span>
        </div>
        <div class="legal-contact-item">
          <i class="fas fa-map-marker-alt"></i>
          <span><?= e($siteAddress) ?></span>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
  // Scroll reveal
  const els = document.querySelectorAll('.legal-section');
  const io  = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.style.opacity=1; e.target.style.transform='translateY(0)'; } });
  }, { threshold: 0.08 });
  els.forEach(el => { el.style.cssText='opacity:0;transform:translateY(24px);transition:opacity 0.6s ease,transform 0.6s ease'; io.observe(el); });
</script>
</body>
</html>
