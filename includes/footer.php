<?php
$_phone       = setting('site_phone',    '+94 77 048 9956');
$_email       = setting('site_email',    'info@gpslankatravels.com');
$_whatsapp    = setting('site_whatsapp', '94770489956');
$_address     = setting('site_address',  '289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka');
$_hours       = setting('business_hours','Mon – Sun: 8:00 AM – 8:00 PM');
$_fbUrl       = setting('facebook_url',    '');
$_igUrl       = setting('instagram_url',  '');
$_ytUrl       = setting('youtube_url',    '');
$_taUrl       = setting('tripadvisor_url','');
$_tkUrl       = setting('tiktok_url',     '');
$_twUrl       = setting('twitter_url',    '');
$_footerLogo  = setting('footer_logo');
$_footerAbout = setting('footer_about',  'Your trusted travel companion for unforgettable Sri Lanka experiences. Licensed, reliable and passionate about sharing the beauty of our island with the world.');
$_copyright   = setting('footer_copyright', '&copy; ' . date('Y') . ' GPS Lanka Travels. All rights reserved.');
?>
<footer class="footer">
  <div class="footer-top">
    <div class="container">
      <div class="footer-grid">

        <div class="footer-brand">
          <a href="index.php">
            <?php if ($_footerLogo): ?>
              <img src="<?= imgUrl($_footerLogo) ?>" alt="GPS Lanka Travels" class="footer-logo-img"/>
            <?php else: ?>
              <img src="images/footer-logo.png" alt="GPS Lanka Travels" class="footer-logo-img"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
              <div class="footer-logo-fallback" style="display:none">
                <div class="logo-icon"><i class="fas fa-compass"></i></div>
                <div>
                  <div class="logo-text" style="color:var(--white)">GPS Lanka Travels</div>
                  <div class="logo-sub"  style="color:rgba(255,255,255,0.4)">Sri Lanka's Premier Tour Operator</div>
                </div>
              </div>
            <?php endif; ?>
          </a>
          <p><?= e($_footerAbout) ?></p>
          <div class="footer-social">
            <?php if ($_fbUrl): ?><a href="<?= e($_fbUrl) ?>" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
            <?php if ($_igUrl): ?><a href="<?= e($_igUrl) ?>" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
            <?php if ($_twUrl): ?><a href="<?= e($_twUrl) ?>" target="_blank" title="X / Twitter"><i class="fab fa-x-twitter"></i></a><?php endif; ?>
            <?php if ($_ytUrl): ?><a href="<?= e($_ytUrl) ?>" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a><?php endif; ?>
            <?php if ($_tkUrl): ?><a href="<?= e($_tkUrl) ?>" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a><?php endif; ?>
            <?php if ($_taUrl): ?><a href="<?= e($_taUrl) ?>" target="_blank" title="TripAdvisor"><i class="fab fa-tripadvisor"></i></a><?php endif; ?>
            <a href="https://wa.me/<?= e($_whatsapp) ?>" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
          </div>
        </div>

        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul class="footer-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="tours.php">All Tours</a></li>
            <li><a href="destinations.php">Destinations</a></li>
            <li><a href="blog.php">Blog</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="contact.php">Contact Us</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Tour Types</h4>
          <ul class="footer-links">
            <li><a href="tours.php?cat=cultural">Cultural Tours</a></li>
            <li><a href="tours.php?cat=wildlife">Wildlife Safari</a></li>
            <li><a href="tours.php?cat=beach">Beach Holidays</a></li>
            <li><a href="tours.php?cat=adventure">Adventure Tours</a></li>
            <li><a href="tours.php?cat=honeymoon">Honeymoon Tours</a></li>
            <li><a href="tours.php?cat=family">Family Packages</a></li>
            <li><a href="tours.php?cat=day">Day Tours</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Contact Us</h4>
          <div class="footer-contact-item">
            <i class="fas fa-map-marker-alt footer-contact-icon"></i>
            <span><?= nl2br(e($_address)) ?></span>
          </div>
          <div class="footer-contact-item">
            <i class="fas fa-phone-alt footer-contact-icon"></i>
            <span><?= e($_phone) ?></span>
          </div>
          <div class="footer-contact-item">
            <i class="fas fa-envelope footer-contact-icon"></i>
            <span><?= e($_email) ?></span>
          </div>
          <div class="footer-contact-item">
            <i class="fab fa-whatsapp footer-contact-icon"></i>
            <span>+<?= e($_whatsapp) ?></span>
          </div>
          <div class="footer-contact-item">
            <i class="fas fa-clock footer-contact-icon"></i>
            <span><?= e($_hours) ?></span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="container">
    <div class="footer-bottom">
      <span><?= $_copyright ?> &nbsp;|&nbsp;
        <span class="designed-by">Designed by <a href="https://www.asseminate.com/" target="_blank">Asseminate</a></span>
      </span>
      <span>
        <a href="privacy.php">Privacy Policy</a>
        <span class="divider">·</span>
        <a href="terms.php">Terms of Service</a>
        <span class="divider">·</span>
        <a href="sitemap.php">Sitemap</a>
      </span>
    </div>
  </div>
</footer>

<a href="https://wa.me/<?= e($_whatsapp) ?>" class="float-wa" target="_blank" title="Chat on WhatsApp">
  <i class="fab fa-whatsapp"></i>
  <span class="float-wa-tooltip">Chat on WhatsApp</span>
</a>

<button class="back-top" id="backTop" title="Back to top">
  <i class="fas fa-chevron-up"></i>
</button>
