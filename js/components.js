const headerHTML = `
<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-inner">
    <div class="topbar-left">
      <a href="tel:+94770489956">
        <i class="fas fa-phone-alt"></i>+94 77 048 9956
      </a>
      <a href="mailto:info@gpslankatravels.com">
        <i class="fas fa-envelope"></i>info@gpslankatravels.com
      </a>
    </div>
    <div class="topbar-right">
      <a href="#" title="Facebook"    target="_blank"><i class="fab fa-facebook-f"></i></a>
      <a href="#" title="Instagram"   target="_blank"><i class="fab fa-instagram"></i></a>
      <a href="#" title="WhatsApp"    target="_blank"><i class="fab fa-whatsapp"></i></a>
      <a href="#" title="TripAdvisor" target="_blank"><i class="fab fa-tripadvisor"></i></a>
      <a href="#" title="YouTube"     target="_blank"><i class="fab fa-youtube"></i></a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">

    <a href="index.html" class="logo">
      <img
        src="images/logo.png"
        alt="GPS Lanka Travels"
        class="logo-img"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
      />
      <div class="logo-icon" style="display:none;">
        <i class="fas fa-compass"></i>
      </div>
      <div>
        <div class="logo-text">GPS Lanka Travels</div>
        <div class="logo-sub">Sri Lanka's Premier Tour Operator</div>
      </div>
    </a>

    <!-- Desktop Nav -->
    <ul class="nav-links">
      <li><a href="index.html"        class="nav-link" data-nav="home">Home</a></li>
      <li><a href="about.html"        class="nav-link" data-nav="about">About Us</a></li>
      <li><a href="tours.html"        class="nav-link" data-nav="tours">All Sri Lanka Tours</a></li>
      <li><a href="destinations.html" class="nav-link" data-nav="destinations">Destinations</a></li>
      <li><a href="blog.html"         class="nav-link" data-nav="blog">Blog</a></li>
      <li><a href="gallery.html"      class="nav-link" data-nav="gallery">Gallery</a></li>
      <li><a href="contact.html"      class="nav-link" data-nav="contact">Contact</a></li>
      <li><a href="contact.html"      class="nav-link btn-book">Book Now</a></li>
    </ul>

    <!-- Hamburger -->
    <button class="hamburger" id="hamburger" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>

  </div>
</nav>

<!-- Mobile overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Mobile Drawer -->
<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-menu-close" id="mobileClose" aria-label="Close menu">
    <i class="fas fa-times"></i>
  </button>
  <ul>
    <li><a href="index.html">Home</a></li>
    <li><a href="about.html">About Us</a></li>
    <li><a href="tours.html">All Sri Lanka Tours</a></li>
    <li><a href="destinations.html">Destinations</a></li>
    <li><a href="blog.html">Blog</a></li>
    <li><a href="gallery.html">Gallery</a></li>
    <li><a href="contact.html">Contact</a></li>
    <li><a href="contact.html" class="book-mobile">Book Now</a></li>
  </ul>
</div>
`;

/* FOOTER TEMPLATE */
const footerHTML = `
<footer class="footer">
  <div class="footer-top">
    <div class="container">
      <div class="footer-grid">

        <!-- Brand -->
        <div class="footer-brand">
          <!--
            FOOTER LOGO PATH → images/footer-logo.png
            Recommended: white/light version, ~200×60px transparent PNG
          -->
          <a href="index.html">
            <img
              src="images/footer-logo.png"
              alt="GPS Lanka Travels"
              class="footer-logo-img"
              onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
            />
            <div class="footer-logo-fallback" style="display:none;">
              <div class="logo-icon"><i class="fas fa-compass"></i></div>
              <div>
                <div class="logo-text" style="color:var(--white)">GPS Lanka Travels</div>
                <div class="logo-sub"  style="color:rgba(255,255,255,0.4)">Sri Lanka's Premier Tour Operator</div>
              </div>
            </div>
          </a>
          <p>Your trusted travel companion for unforgettable Sri Lanka experiences. Licensed, reliable and passionate about sharing the beauty of our island with the world.</p>
          <div class="footer-social">
            <a href="#" target="_blank" title="Facebook">    <i class="fab fa-facebook-f"></i></a>
            <a href="#" target="_blank" title="Instagram">   <i class="fab fa-instagram"></i></a>
            <a href="#" target="_blank" title="YouTube">     <i class="fab fa-youtube"></i></a>
            <a href="https://wa.me/94770489956" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <a href="#" target="_blank" title="TripAdvisor"> <i class="fab fa-tripadvisor"></i></a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul class="footer-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="about.html">About Us</a></li>
            <li><a href="tours.html">All Tours</a></li>
            <li><a href="destinations.html">Destinations</a></li>
            <li><a href="blog.html">Blog</a></li>
            <li><a href="gallery.html">Gallery</a></li>
            <li><a href="contact.html">Contact Us</a></li>
          </ul>
        </div>

        <!-- Tour Types -->
        <div class="footer-col">
          <h4>Tour Types</h4>
          <ul class="footer-links">
            <li><a href="tours.html#filter=cultural">Cultural Tours</a></li>
            <li><a href="tours.html#filter=wildlife">Wildlife Safari</a></li>
            <li><a href="tours.html#filter=beach">Beach Holidays</a></li>
            <li><a href="tours.html#filter=adventure">Adventure Tours</a></li>
            <li><a href="tours.html#filter=honeymoon">Honeymoon Tours</a></li>
            <li><a href="tours.html#filter=family">Family Packages</a></li>
            <li><a href="tours.html#filter=day">Day Tours</a></li>
          </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
          <h4>Contact Us</h4>
          <div class="footer-contact-item">
            <i class="fas fa-map-marker-alt footer-contact-icon"></i>
            <span>289/1 Madampagama, Kuleegoda,<br>Ambalangoda, Sri Lanka</span>
          </div>
          <div class="footer-contact-item">
            <i class="fas fa-phone-alt footer-contact-icon"></i>
            <span>+94 77 048 9956</span>
          </div>
          <div class="footer-contact-item">
            <i class="fas fa-envelope footer-contact-icon"></i>
            <span>info@gpslankatravels.com</span>
          </div>
          <div class="footer-contact-item">
            <i class="fab fa-whatsapp footer-contact-icon"></i>
            <span>+94 77 048 9956</span>
          </div>
          <div class="footer-contact-item">
            <i class="fas fa-clock footer-contact-icon"></i>
            <span>Mon – Sun: 8:00 AM – 8:00 PM</span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Bottom Bar -->
  <div class="container">
    <div class="footer-bottom">
      <span>
        © ${new Date().getFullYear()} GPS Lanka Travels. All rights reserved.
        &nbsp;|&nbsp;
        <span class="designed-by">Designed by <a href="https://www.asseminate.com/" target="_blank">Asseminate</a></span>
      </span>
      <span>
        <a href="privacy.html">Privacy Policy</a>
        <span class="divider">·</span>
        <a href="terms.html">Terms of Service</a>
        <span class="divider">·</span>
        <a href="sitemap.html">Sitemap</a>
      </span>
    </div>
  </div>
</footer>

<!-- WhatsApp Floating Button -->
<a href="https://wa.me/94770489956" class="float-wa" target="_blank" title="Chat on WhatsApp">
  <i class="fab fa-whatsapp"></i>
  <span class="float-wa-tooltip">Chat on WhatsApp</span>
</a>

<!-- Back to Top -->
<button class="back-top" id="backTop" title="Back to top">
  <i class="fas fa-chevron-up"></i>
</button>
`;

/* INJECT HEADER & FOOTER */
(function () {
  // Inject header
  const headerEl = document.getElementById('header-placeholder');
  if (headerEl) headerEl.innerHTML = headerHTML;

  // Inject footer
  const footerEl = document.getElementById('footer-placeholder');
  if (footerEl) footerEl.innerHTML = footerHTML;

  // Set active nav link based on data-page on <body> 
  const currentPage = document.body.dataset.page || '';
  document.querySelectorAll('.nav-link[data-nav]').forEach(link => {
    if (link.dataset.nav === currentPage) link.classList.add('active');
  });

  // Mobile menu toggle 
  const hamburger    = document.getElementById('hamburger');
  const mobileMenu   = document.getElementById('mobileMenu');
  const mobileClose  = document.getElementById('mobileClose');
  const mobileOverlay= document.getElementById('mobileOverlay');

  function openMenu() {
    mobileMenu?.classList.add('open');
    mobileOverlay?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    mobileMenu?.classList.remove('open');
    mobileOverlay?.classList.remove('open');
    document.body.style.overflow = '';
  }

  hamburger?.addEventListener('click', openMenu);
  mobileClose?.addEventListener('click', closeMenu);
  mobileOverlay?.addEventListener('click', closeMenu);

  // Navbar scroll effect 
  const navbar  = document.getElementById('navbar');
  const backTop = document.getElementById('backTop');

  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    navbar?.classList.toggle('scrolled', y > 80);
    backTop?.classList.toggle('visible', y > 400);
  });

  // Back to top 
  backTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // Hash-based tour filter (tours.html#filter=wildlife etc.) 
  if (window.location.hash.startsWith('#filter=')) {
    const filterVal = window.location.hash.replace('#filter=', '');
    // Wait for tours.js to initialise
    setTimeout(() => {
      const targetPill = document.querySelector(`.filter-pill[data-filter="${filterVal}"]`);
      if (targetPill) targetPill.click();
    }, 100);
  }

})();
