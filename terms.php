<?php
require_once 'includes/config.php';
$currentPage = 'terms';
$siteTitle   = setting('site_name', 'GPS Lanka Travels');
$siteEmail   = setting('site_email', 'info@gpslankatravels.com');
$siteAddress = setting('site_address', '289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Terms of Service | <?= e($siteTitle) ?></title>
  <meta name="description" content="Terms of Service for <?= e($siteTitle) ?>. Please read these terms carefully before booking a tour with us."/>
  <meta property="og:title"       content="Terms of Service | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="Read the Terms of Service for GPS Lanka Travels before making a booking."/>
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
      Terms of Service
    </div>
    <h1 class="page-hero-title">Terms of <em>Service</em></h1>
    <p class="page-hero-sub">Please read these terms carefully before booking a tour with us</p>
  </div>
</section>

<!-- CONTENT -->
<section class="legal-wrap">
  <div class="legal-inner">

    <!-- Meta bar -->
    <div class="legal-meta">
      <span><i class="fas fa-calendar-alt"></i> Last updated: <strong>January 2025</strong></span>
      <span><i class="fas fa-file-contract"></i> Applies to all bookings &amp; services</span>
    </div>

    <!-- Lead -->
    <p class="legal-lead">
      By using the website or services of <?= e($siteTitle) ?> ("we", "us", "our"), you agree to be bound by the following Terms of Service. Please read them carefully before making a booking or enquiry. If you do not agree with these terms, please refrain from using our services.
    </p>

    <!-- 1 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">1</span> Booking &amp; Confirmation</h2>
      <p class="legal-text">All tour bookings are subject to availability. A booking is confirmed only after we have reviewed your request and sent you a written confirmation via email or WhatsApp. Submitting a booking form on our website does not constitute a confirmed reservation.</p>
      <p class="legal-text">We reserve the right to decline any booking at our discretion without obligation to provide a reason.</p>
    </div>

    <!-- 2 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">2</span> Pricing</h2>
      <p class="legal-text">All prices displayed on our website are indicative and may vary based on travel dates, group size, accommodation preferences and seasonal demand. Final pricing will be confirmed in writing before your tour begins.</p>
      <p class="legal-text">Prices are quoted in USD unless otherwise stated. Payment in other currencies may be arranged upon request.</p>
    </div>

    <!-- 3 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">3</span> Payment</h2>
      <p class="legal-text">Our standard payment terms are:</p>
      <ul class="legal-list">
        <li>A deposit (agreed upon confirmation) is required to secure your booking</li>
        <li>The remaining balance is due before or on the first day of the tour unless otherwise arranged</li>
        <li>Accepted payment methods will be communicated at the time of booking confirmation</li>
      </ul>
    </div>

    <!-- 4 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">4</span> Cancellation &amp; Refunds</h2>
      <p class="legal-sub-title">Cancellation by the Client</p>

      <table class="legal-table">
        <thead>
          <tr>
            <th>Notice Period</th>
            <th>Refund</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>More than 30 days before departure</td>
            <td><span class="badge-refund badge-full">Full refund (minus non-recoverable costs)</span></td>
          </tr>
          <tr>
            <td>15 – 30 days before departure</td>
            <td><span class="badge-refund badge-partial">50% of total tour cost</span></td>
          </tr>
          <tr>
            <td>Less than 15 days before departure</td>
            <td><span class="badge-refund badge-none">No refund</span></td>
          </tr>
          <tr>
            <td>No-show</td>
            <td><span class="badge-refund badge-none">No refund</span></td>
          </tr>
        </tbody>
      </table>

      <p class="legal-text">All cancellations must be made in writing via email to <a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a>.</p>

      <p class="legal-sub-title">Cancellation by <?= e($siteTitle) ?></p>
      <p class="legal-text">In the unlikely event that we must cancel a tour due to circumstances beyond our control (natural disasters, government restrictions, safety concerns), we will offer a full refund or an alternative tour of equal value.</p>
    </div>

    <!-- 5 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">5</span> Changes to Itinerary</h2>
      <p class="legal-text">We reserve the right to modify tour itineraries due to weather conditions, road closures, safety concerns or other unforeseen circumstances. We will always aim to provide an equivalent alternative experience. No refund will be issued for minor itinerary adjustments.</p>
    </div>

    <!-- 6 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">6</span> Travel Insurance</h2>
      <p class="legal-text">We strongly recommend that all clients obtain comprehensive travel insurance prior to departure. This should cover trip cancellation, medical emergencies, personal accident, baggage loss and other travel-related risks. <?= e($siteTitle) ?> is not liable for any costs arising from failure to obtain adequate insurance.</p>
    </div>

    <!-- 7 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">7</span> Client Responsibilities</h2>
      <p class="legal-text">As a client, you are responsible for:</p>
      <ul class="legal-list">
        <li>Ensuring your passport and any required visas are valid for the duration of your trip</li>
        <li>Arriving on time at agreed pick-up points</li>
        <li>Behaving respectfully towards local culture, customs and natural environments</li>
        <li>Disclosing any medical conditions or dietary requirements that may affect your tour</li>
        <li>Following the instructions of your guide for safety purposes at all times</li>
      </ul>
    </div>

    <!-- 8 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">8</span> Limitation of Liability</h2>
      <p class="legal-text"><?= e($siteTitle) ?> acts as an organiser and coordinator of tour services. While we take every precaution to ensure your safety and comfort, we are not liable for:</p>
      <ul class="legal-list">
        <li>Personal injury, illness, loss or damage caused by circumstances beyond our control</li>
        <li>Acts of third-party service providers (hotels, transport operators, activity providers)</li>
        <li>Losses arising from flight delays, cancellations or missed connections</li>
        <li>Any indirect, incidental or consequential damages</li>
      </ul>
    </div>

    <!-- 9 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">9</span> Photography &amp; Marketing</h2>
      <p class="legal-text">During tours, our guides may take photographs for promotional purposes. By joining a tour, you consent to the use of such photographs on our website and social media. If you prefer not to be photographed, please inform your guide at the start of the tour.</p>
    </div>

    <!-- 10 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">10</span> Governing Law</h2>
      <p class="legal-text">These Terms of Service are governed by the laws of Sri Lanka. Any disputes arising from these terms shall be subject to the jurisdiction of the courts of Sri Lanka.</p>
    </div>

    <!-- 11 -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">11</span> Changes to These Terms</h2>
      <p class="legal-text">We may update these Terms of Service at any time. The updated version will be posted on this page with a revised "Last updated" date. Continued use of our services after any changes constitutes acceptance of the new terms.</p>
    </div>

    <!-- 12 Contact -->
    <div class="legal-section">
      <h2 class="legal-section-title"><span class="section-num">12</span> Contact Us</h2>
      <p class="legal-text">If you have any questions about these Terms of Service, please reach out to us:</p>
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
  const els = document.querySelectorAll('.legal-section');
  const io  = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.style.opacity=1; e.target.style.transform='translateY(0)'; } });
  }, { threshold: 0.08 });
  els.forEach(el => { el.style.cssText='opacity:0;transform:translateY(24px);transition:opacity 0.6s ease,transform 0.6s ease'; io.observe(el); });
</script>
</body>
</html>
