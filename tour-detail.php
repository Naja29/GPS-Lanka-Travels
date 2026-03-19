<?php
require_once 'includes/config.php';
$currentPage = 'tours';

/* ── BOOKINGS TABLE (safe migration) ── */
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS bookings (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        tour_id     INT DEFAULT NULL,
        tour_title  VARCHAR(300) NOT NULL,
        name        VARCHAR(120) NOT NULL,
        email       VARCHAR(255) NOT NULL,
        phone       VARCHAR(50)  DEFAULT NULL,
        tour_date   DATE         DEFAULT NULL,
        persons     VARCHAR(50)  DEFAULT NULL,
        message     TEXT         DEFAULT NULL,
        status      VARCHAR(20)  DEFAULT 'new',
        is_read     TINYINT(1)   DEFAULT 0,
        ip_address  VARCHAR(45)  DEFAULT NULL,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

$tour = null;
if ($conn) {
    if ($slug) {
        $stmt = $conn->prepare("SELECT t.*, tc.name as cat_name, tc.slug as cat_slug
                                 FROM tours t LEFT JOIN tour_categories tc ON t.category_id=tc.id
                                 WHERE t.slug=? AND t.is_active=1 LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $r = $stmt->get_result();
    } elseif ($id) {
        $stmt = $conn->prepare("SELECT t.*, tc.name as cat_name, tc.slug as cat_slug
                                 FROM tours t LEFT JOIN tour_categories tc ON t.category_id=tc.id
                                 WHERE t.id=? AND t.is_active=1 LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result();
    }
    if (!empty($r)) $tour = $r->fetch_assoc();
}

/* Related tours */
$related = [];
if ($conn && $tour) {
    $catId   = (int)($tour['category_id'] ?? 0);
    $tourId  = (int)$tour['id'];
    $catCond = $catId ? "t.category_id=$catId AND" : '';
    $r = $conn->query("SELECT t.*, tc.name as cat_name FROM tours t
                        LEFT JOIN tour_categories tc ON t.category_id=tc.id
                        WHERE $catCond t.id != $tourId AND t.is_active=1
                        ORDER BY t.sort_order, t.id LIMIT 3");
    if ($r) $related = $r->fetch_all(MYSQLI_ASSOC);
    if (count($related) < 3) {
        $ids = array_map(fn($x) => (int)$x['id'], $related);
        $ids[] = $tourId;
        $excl = implode(',', $ids);
        $need = 3 - count($related);
        $r2 = $conn->query("SELECT t.*, tc.name as cat_name FROM tours t
                             LEFT JOIN tour_categories tc ON t.category_id=tc.id
                             WHERE t.id NOT IN($excl) AND t.is_active=1
                             ORDER BY t.sort_order, t.id LIMIT $need");
        if ($r2) $related = array_merge($related, $r2->fetch_all(MYSQLI_ASSOC));
    }
}

/* ── BOOKING FORM HANDLER ── */
$bookingRef    = $_GET['booked'] ?? '';
$bookingErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_submit'])) {
    $bName    = trim($_POST['b_name']       ?? '');
    $bEmail   = trim($_POST['b_email']      ?? '');
    $bPhone   = trim($_POST['b_phone']      ?? '');
    $bDate    = trim($_POST['b_date']       ?? '');
    $bPersons = trim($_POST['b_persons']    ?? '');
    $bMsg     = trim($_POST['b_message']    ?? '');
    $bTourId  = (int)($_POST['b_tour_id']   ?? 0);
    $bTitle   = trim($_POST['b_tour_title'] ?? '');

    if (!$bName)  $bookingErrors[] = 'Full name is required.';
    if (!$bEmail || !filter_var($bEmail, FILTER_VALIDATE_EMAIL)) $bookingErrors[] = 'Valid email is required.';
    if (!$bDate)  $bookingErrors[] = 'Tour date is required.';

    if (!$bookingErrors && $conn) {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $conn->prepare("INSERT INTO bookings (tour_id,tour_title,name,email,phone,tour_date,persons,message,ip_address)
                                VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('issssssss', $bTourId, $bTitle, $bName, $bEmail, $bPhone, $bDate, $bPersons, $bMsg, $ip);
        if ($stmt->execute()) {
            $ref = 'BK-' . str_pad($conn->insert_id, 5, '0', STR_PAD_LEFT);
            header('Location: ' . $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'],'?') !== false ? '&' : '?') . 'booked=' . urlencode($ref) . '#booking');
            exit;
        }
    }
}

$siteTitle = setting('site_name', 'GPS Lanka Travels');
$whatsapp  = setting('site_whatsapp', '94770489956');

/* Use static fallback if no tour in DB */
$useStatic = !$tour;
$t = $tour ?: [
    'title'       => 'Kandy Full Day Tour',
    'slug'        => 'kandy-full-day-tour',
    'short_desc'  => "Explore Sri Lanka's cultural capital — the Temple of the Tooth Relic, Pinnawala Elephant Orphanage, and Royal Botanical Gardens.",
    'description' => '<p>Kandy is the cultural capital and last royal capital of the Sri Lankan Kings. This full-day tour takes you through the best of Kandy.</p>',
    'itinerary'   => '',
    'includes'    => "AC vehicle & driver\nEnglish-speaking guide\nHotel pickup & drop-off\nLunch\nEntrance fees (select)\nBottled water",
    'excludes'    => "Temple of Tooth entrance\nPersonal expenses\nTips & gratuities\nTravel insurance",
    'highlights'  => 'Pinnawala Elephant Orphanage,Royal Botanical Gardens,Kandy Spice Garden,Kandyan Cultural Dance,Temple of Tooth Relic',
    'tips'        => '',
    'duration'    => '1 Day',
    'group_size'  => '1-10 people',
    'price_usd'   => 90,
    'price_note'  => 'person',
    'image'       => '',
    'map_embed'   => '',
    'cat_name'    => 'Day Tour',
    'id'          => 0,
];

$tourImg    = $t['image'] ? imgUrl($t['image']) : 'images/tour-kandy.jpg';
$highlights = parseHighlights($t['highlights'] ?? '', 99);
$includes   = parseListItems($t['includes']   ?? '');
$excludes   = parseListItems($t['excludes']   ?? '');
$faqs       = !empty($t['faqs']) ? (json_decode($t['faqs'], true) ?: []) : [];
$waMsg      = urlencode('Hi, I want to book the ' . ($t['title'] ?? 'tour'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?= e($t['short_desc'] ?? $t['title']) ?> | <?= e($siteTitle) ?>"/>
  <?php
  $ogImg = $t['image'] ? imgUrl($t['image']) : SITE_URL . '/images/tour-kandy.jpg';
  $ogUrl = SITE_URL . '/tour-detail.php?slug=' . urlencode($t['slug'] ?? '');
  $ogDesc = mb_substr(strip_tags($t['short_desc'] ?? $t['description'] ?? $t['title']), 0, 160);
  ?>
  <meta property="og:type"        content="website"/>
  <meta property="og:site_name"   content="<?= e($siteTitle) ?>"/>
  <meta property="og:title"       content="<?= e($t['title']) ?> | <?= e($siteTitle) ?>"/>
  <meta property="og:description" content="<?= e($ogDesc) ?>"/>
  <meta property="og:image"       content="<?= e($ogImg) ?>"/>
  <meta property="og:url"         content="<?= e($ogUrl) ?>"/>
  <title><?= e($t['title']) ?> | <?= e($siteTitle) ?></title>
  <link rel="icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <link rel="stylesheet" href="css/tours.css"/>
  <link rel="stylesheet" href="css/tour-detail.css"/>
</head>
<body data-page="tours">

<?php include 'includes/header.php'; ?>

<!-- DETAIL HERO -->
<section class="detail-hero"<?= $tourImg ? ' style="--detail-bg:url(\''.e($tourImg).'\')"' : '' ?>>
  <div class="detail-hero-bg"></div>
  <div class="detail-hero-content">
    <div class="detail-hero-inner">
      <div class="detail-breadcrumb">
        <a href="index.php">Home</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <a href="tours.php">All Tours</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <?= e($t['title']) ?>
      </div>
      <h1 class="detail-hero-title"><?= e($t['title']) ?></h1>
      <div class="detail-hero-badges">
        <?php if ($t['duration']): ?><div class="detail-badge"><i class="fas fa-clock"></i> <?= e($t['duration']) ?></div><?php endif; ?>
        <?php if ($t['group_size']): ?><div class="detail-badge"><i class="fas fa-users"></i> <?= e($t['group_size']) ?></div><?php endif; ?>
        <?php if ($t['cat_name']): ?><div class="detail-badge"><i class="fas fa-tag"></i> <?= e($t['cat_name']) ?></div><?php endif; ?>
        <div class="detail-badge"><i class="fas fa-map-marker-alt"></i> Sri Lanka</div>
      </div>
    </div>
  </div>
  <div class="detail-hero-ctas">
    <a href="#booking" class="btn-primary"><i class="fas fa-calendar-check"></i> Book Now</a>
    <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= $waMsg ?>" class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Enquire</a>
  </div>
</section>


<!-- MAIN BODY -->
<div class="detail-body">
  <div class="container">
    <div class="detail-grid">

      <!-- MAIN CONTENT -->
      <div class="detail-main">

        <!-- OVERVIEW -->
        <div class="detail-section detail-overview reveal">
          <h2 class="detail-section-title"><i class="fas fa-info-circle"></i> Tour Overview</h2>
          <?php if (!empty($t['description']) && strip_tags($t['description']) !== ''): ?>
            <?= $t['description'] ?>
          <?php elseif ($t['short_desc']): ?>
            <p><?= e($t['short_desc']) ?></p>
          <?php else: ?>
            <p>Kandy is a large city in central Sri Lanka, set on a plateau surrounded by mountains home to tea and rubber plantations. Kandy is the cultural capital and last royal capital of the Sri Lankan Kings. This full-day tour takes you through the best of Kandy — from the iconic Pinnawala Elephant Orphanage to the Temple of the Tooth Relic at sunset.</p>
          <?php endif; ?>
        </div>

        <!-- ITINERARY -->
        <?php if (!empty($t['itinerary'])): ?>
          <div class="detail-section reveal">
            <h2 class="detail-section-title"><i class="fas fa-route"></i> Tour Itinerary</h2>
            <?php
            $itinItems = parseItineraryHtml($t['itinerary']);
            if ($itinItems): ?>
            <div class="itinerary-list">
              <?php foreach ($itinItems as $di => $day): ?>
                <div class="itinerary-item">
                  <div class="itin-line"><div class="itin-num"><?= $di + 1 ?></div></div>
                  <div class="itin-content">
                    <h4><?= e(preg_replace('/^(Day\s+\d+[-:–\s]*|Stop\s+\d+[-:–\s]*)/i', '', $day['title'])) ?></h4>
                    <?php if ($day['body']): ?><p><?= e($day['body']) ?></p><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php elseif (!isHtml($t['itinerary'])): ?>
            <div class="itinerary-list">
              <?php
              $days = preg_split('/\n(?=Day\s+\d|Stop\s+\d|\d+[\.\)])/i', $t['itinerary']);
              if (count($days) <= 1) $days = array_filter(explode("\n\n", $t['itinerary']));
              foreach ($days as $di => $day):
                $lines = array_filter(explode("\n", trim($day)));
                if (!$lines) continue;
                $title = array_shift($lines);
                $body  = implode(' ', $lines);
              ?>
                <div class="itinerary-item">
                  <div class="itin-line"><div class="itin-num"><?= $di + 1 ?></div></div>
                  <div class="itin-content">
                    <h4><?= e(preg_replace('/^(Day\s+\d+:?\s*|Stop\s+\d+:?\s*|\d+[\.\)]\s*)/i', '', $title)) ?></h4>
                    <?php if ($body): ?><p><?= e($body) ?></p><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
              <div class="rich-content"><?= $t['itinerary'] ?></div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <!-- Static itinerary fallback for Kandy tour -->
          <div class="detail-section reveal">
            <h2 class="detail-section-title"><i class="fas fa-route"></i> Tour Itinerary</h2>
            <div class="itinerary-list">
              <div class="itinerary-item"><div class="itin-line"><div class="itin-num">1</div></div><div class="itin-content"><h4>Pinnawala Elephant Orphanage</h4><p>Begin your journey at Pinnawala Elephant Orphanage — home to the world's largest herd of captive elephants. Watch baby elephants being bottle-fed and enjoy the famous elephant bathing ritual in the Maha Oya River.</p></div></div>
              <div class="itinerary-item"><div class="itin-line"><div class="itin-num">2</div></div><div class="itin-content"><h4>Kandy Spice &amp; Herbal Garden</h4><p>Visit a traditional spice garden and discover the incredible variety of Sri Lankan spices and medicinal herbs — cinnamon, cardamom, pepper, cloves and more.</p></div></div>
              <div class="itinerary-item"><div class="itin-line"><div class="itin-num">3</div></div><div class="itin-content"><h4>Royal Botanical Gardens - Peradeniya</h4><p>Stroll through one of Asia's finest botanical gardens, home to over 4,000 plant species across 147 acres. Highlights include the Giant Javan Fig tree, the Orchid House, and the avenue of Royal Palms.</p></div></div>
              <div class="itinerary-item"><div class="itin-line"><div class="itin-num">4</div></div><div class="itin-content"><h4>Kandy City &amp; Local Market</h4><p>Explore Kandy's vibrant city center and the bustling local market. Pick up unique souvenirs — handloom textiles, silver jewellery, carved wooden crafts, and Ceylon tea.</p></div></div>
              <div class="itinerary-item"><div class="itin-line"><div class="itin-num">5</div></div><div class="itin-content"><h4>Traditional Kandyan Cultural Dance Show</h4><p>Witness a spectacular performance of traditional Kandyan dance — one of Sri Lanka's most prized art forms including fire dance, plate dance, and iconic Kandyan drumming.</p></div></div>
              <div class="itinerary-item"><div class="itin-line"><div class="itin-num">6</div></div><div class="itin-content"><h4>Temple of the Sacred Tooth Relic</h4><p>End your day with a visit to Sri Dalada Maligawa — one of the most sacred Buddhist sites in the world — at sunset. An unforgettable cultural experience.</p></div></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- MAP -->
        <?php if (!empty($t['map_embed'])): ?>
          <div class="detail-section reveal">
            <h2 class="detail-section-title"><i class="fas fa-map-marked-alt"></i> Tour Map</h2>
            <?= $t['map_embed'] ?>
          </div>
        <?php endif; ?>

        <!-- INCLUDES / EXCLUDES -->
        <?php if ($includes || $excludes): ?>
          <div class="detail-section inc-exc-section reveal">
            <h2 class="detail-section-title"><i class="fas fa-clipboard-list"></i> What's Included</h2>
            <div class="inc-exc-grid">
              <?php if ($includes): ?>
              <div class="inc-col">
                <div class="inc-col-head"><i class="fas fa-check-circle"></i> Included</div>
                <?php foreach ($includes as $item): ?>
                  <div class="inc-item"><i class="fas fa-check"></i> <?= e($item) ?></div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <?php if ($excludes): ?>
              <div class="exc-col">
                <div class="exc-col-head"><i class="fas fa-times-circle"></i> Not Included</div>
                <?php foreach ($excludes as $item): ?>
                  <div class="exc-item"><i class="fas fa-times"></i> <?= e($item) ?></div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- TRAVELER TIPS -->
        <?php if (!empty($t['tips'])): ?>
          <div class="detail-section reveal">
            <h2 class="detail-section-title"><i class="fas fa-lightbulb"></i> Traveler Tips</h2>
            <?php if (isHtml($t['tips'])): ?>
              <div class="rich-content"><?= $t['tips'] ?></div>
            <?php else: ?>
            <div class="detail-tips-grid">
              <?php foreach (array_filter(explode("\n\n", $t['tips'])) as $tip):
                $lines = explode("\n", trim($tip)); $th = array_shift($lines); ?>
                <div class="tip-card"><h5><?= e($th) ?></h5><p><?= e(implode(' ', $lines)) ?></p></div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="detail-section reveal">
            <h2 class="detail-section-title"><i class="fas fa-lightbulb"></i> Traveler Tips</h2>
            <div class="detail-tips-grid">
              <div class="tip-card"><h5><i class="fas fa-tshirt"></i> What to Wear</h5><p>Dress modestly when visiting temples — cover shoulders and knees. Bring comfortable walking shoes for the botanical gardens.</p></div>
              <div class="tip-card"><h5><i class="fas fa-sun"></i> Best Time to Visit</h5><p>Early morning departures are ideal to avoid heat and crowds. The evening Puja ceremony at the temple is magical.</p></div>
              <div class="tip-card"><h5><i class="fas fa-camera"></i> Photography</h5><p>Photography is allowed at most sites. Bring extra memory cards — the elephant bathing and temple ceremonies are incredibly photogenic.</p></div>
              <div class="tip-card"><h5><i class="fas fa-utensils"></i> Food &amp; Drinks</h5><p>Lunch at a local Sri Lankan restaurant is included. Try the traditional rice and curry — a must-have authentic experience.</p></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- FAQ -->
        <?php
        $faqDisplay = $faqs ?: [
            ['q' => 'What time does the tour start and end?',   'a' => 'The tour typically departs from your hotel between 6:30 AM and 8:00 AM depending on your location and returns by 8:00 PM. Exact timings are confirmed at the time of booking.'],
            ['q' => 'Is this tour private or a group tour?',    'a' => 'This is a fully private tour. You will have your own vehicle, driver and guide exclusively for your group. You will not share with other travelers.'],
            ['q' => 'Is pickup from my hotel included?',        'a' => 'Yes! We offer complimentary hotel pickup and drop-off from anywhere in Colombo, Negombo, or the Western Province. For other areas, a small additional charge may apply.'],
            ['q' => 'Can I customise this itinerary?',          'a' => 'Absolutely! All our tours are fully flexible. You can add, remove, or swap any stops based on your interests. Simply let us know when booking and we will tailor the itinerary to suit you perfectly.'],
        ];
        ?>
        <div class="detail-section reveal">
          <h2 class="detail-section-title"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
          <div class="faq-list">
            <?php foreach ($faqDisplay as $faq): ?>
            <div class="faq-item">
              <button class="faq-question"><?= e($faq['q']) ?><i class="fas fa-chevron-down"></i></button>
              <div class="faq-answer"><?= e($faq['a']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div><!-- /.detail-main -->


      <!-- SIDEBAR -->
      <div class="detail-sidebar">

        <!-- Booking Card -->
        <div class="booking-card" id="booking">
          <div class="booking-card-header">
            <h3>Book This Tour</h3>
            <div class="booking-price"><?= tourPrice($t['price_usd'], $t['price_note']) ?></div>
          </div>
          <div class="booking-card-body">
            <div class="booking-meta">
              <?php if ($t['duration']): ?><div class="booking-meta-item"><span class="booking-meta-label">Duration</span><span class="booking-meta-val"><i class="fas fa-clock"></i> <?= e($t['duration']) ?></span></div><?php endif; ?>
              <?php if ($t['group_size']): ?><div class="booking-meta-item"><span class="booking-meta-label">Group Size</span><span class="booking-meta-val"><i class="fas fa-users"></i> <?= e($t['group_size']) ?></span></div><?php endif; ?>
              <div class="booking-meta-item"><span class="booking-meta-label">Tour Type</span><span class="booking-meta-val"><i class="fas fa-tag"></i> Private</span></div>
              <div class="booking-meta-item"><span class="booking-meta-label">Language</span><span class="booking-meta-val"><i class="fas fa-language"></i> English</span></div>
            </div>
            <div class="booking-divider"></div>

            <?php if ($bookingRef): ?>
            <!-- SUCCESS STATE -->
            <div style="text-align:center;padding:16px 0">
              <div style="width:60px;height:60px;background:linear-gradient(135deg,#27ae60,#2ecc71);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:26px;color:#fff">
                <i class="fas fa-check"></i>
              </div>
              <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:#0a3232;margin-bottom:6px">Booking Received!</div>
              <div style="font-size:13px;color:#6b7280;margin-bottom:14px;line-height:1.6">
                Thank you! Your booking request has been sent.<br>Our team will confirm within 24 hours.
              </div>
              <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:12px 16px;margin-bottom:16px">
                <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#16a34a;margin-bottom:4px">Booking Reference</div>
                <div style="font-size:20px;font-weight:700;color:#0a3232;letter-spacing:2px"><?= e($bookingRef) ?></div>
              </div>
              <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hi, I just made a booking request (' . $bookingRef . ') for ' . $t['title'] . '. Can you confirm?') ?>"
                 class="booking-wa-btn" target="_blank" style="display:flex">
                <i class="fab fa-whatsapp"></i> Confirm on WhatsApp
              </a>
            </div>

            <?php elseif ($bookingErrors): ?>
            <!-- ERROR STATE -->
            <div style="background:#fff0f0;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:#c0392b">
              <i class="fas fa-exclamation-circle" style="margin-right:6px"></i>
              <?= implode('<br>', array_map('e', $bookingErrors)) ?>
            </div>
            <?php endif; ?>

            <?php if (!$bookingRef): ?>
            <!-- BOOKING FORM -->
            <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>#booking">
              <input type="hidden" name="booking_submit" value="1"/>
              <input type="hidden" name="b_tour_id"    value="<?= (int)($t['id'] ?? 0) ?>"/>
              <input type="hidden" name="b_tour_title" value="<?= e($t['title']) ?>"/>
              <div class="booking-form-group">
                <label>Full Name <span style="color:#e74c3c">*</span></label>
                <input type="text" name="b_name" placeholder="Your full name" required
                       value="<?= $bookingErrors ? e($_POST['b_name'] ?? '') : '' ?>"/>
              </div>
              <div class="booking-form-group">
                <label>Email Address <span style="color:#e74c3c">*</span></label>
                <input type="email" name="b_email" placeholder="your@email.com" required
                       value="<?= $bookingErrors ? e($_POST['b_email'] ?? '') : '' ?>"/>
              </div>
              <div class="booking-form-group">
                <label>Phone / WhatsApp</label>
                <input type="tel" name="b_phone" placeholder="+94 77 000 0000"
                       value="<?= $bookingErrors ? e($_POST['b_phone'] ?? '') : '' ?>"/>
              </div>
              <div class="booking-form-group">
                <label>Tour Date <span style="color:#e74c3c">*</span></label>
                <input type="date" name="b_date" required
                       min="<?= date('Y-m-d') ?>"
                       value="<?= $bookingErrors ? e($_POST['b_date'] ?? '') : '' ?>"/>
              </div>
              <div class="booking-form-group">
                <label>Number of Persons</label>
                <select name="b_persons">
                  <?php foreach (['1 Person','2 Persons','3 Persons','4 Persons','5-8 Persons','9+ Persons'] as $opt): ?>
                    <option <?= ($bookingErrors && ($_POST['b_persons'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="booking-form-group">
                <label>Special Requests</label>
                <textarea name="b_message" placeholder="Any special requirements or notes…" rows="2"
                          style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;resize:none;font-family:inherit"><?= $bookingErrors ? e($_POST['b_message'] ?? '') : '' ?></textarea>
              </div>
              <button type="submit" class="booking-btn">
                <i class="fas fa-calendar-check"></i> Confirm Booking
              </button>
            </form>
            <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= $waMsg ?>" class="booking-wa-btn" target="_blank">
              <i class="fab fa-whatsapp"></i> Book via WhatsApp
            </a>
            <?php endif; ?>

          </div>
        </div>

        <!-- Journey Highlights -->
        <?php if ($highlights): ?>
          <div class="highlights-card reveal">
            <h4>Journey Highlights</h4>
            <div class="highlight-list">
              <?php foreach ($highlights as $h): ?>
                <div class="highlight-item"><i class="fas fa-check-circle"></i> <?= e($h) ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      </div><!-- /.detail-sidebar -->

    </div><!-- /.detail-grid -->
  </div>
</div><!-- /.detail-body -->


<!-- RELATED TOURS -->
<?php if ($related): ?>
<section class="related-tours section-pad">
  <div class="container">
    <div class="section-header">
      <div class="section-tag reveal">You May Also Like</div>
      <h2 class="section-title reveal">Related <em>Tour Packages</em></h2>
    </div>
    <div class="related-grid stagger-children">
      <?php foreach ($related as $rt):
        $rimg  = $rt['image'] ? imgUrl($rt['image']) : 'images/tour-cultural.jpg';
        $badgeMap = ['Popular'=>'badge-popular','Best Seller'=>'badge-bestseller','Bestseller'=>'badge-bestseller','New'=>'badge-new','Honeymoon'=>'badge-honeymoon','Family'=>'badge-family','Adventure'=>'badge-adventure'];
        $rbadge = $rt['badge'] ?? '';
        $rbadgeClass = $badgeMap[$rbadge] ?? 'badge-popular';
      ?>
        <div class="tour-card reveal">
          <div class="tour-img">
            <img src="<?= e($rimg) ?>" alt="<?= e($rt['title']) ?>" loading="lazy"/>
            <?php if ($rbadge): ?><div class="tour-badge <?= $rbadgeClass ?>"><?= e($rbadge) ?></div><?php endif; ?>
            <?php if ($rt['duration']): ?><div class="tour-duration"><i class="fas fa-clock"></i> <?= e($rt['duration']) ?></div><?php endif; ?>
          </div>
          <div class="tour-body">
            <?php if ($rt['cat_name']): ?><div class="tour-location"><i class="fas fa-map-marker-alt"></i> <?= e($rt['cat_name']) ?></div><?php endif; ?>
            <div class="tour-name"><?= e($rt['title']) ?></div>
            <div class="tour-desc"><?= e($rt['short_desc'] ?? '') ?></div>
            <div class="tour-footer">
              <div><div class="tour-price-label">From</div><div class="tour-price"><?= tourPrice($rt['price_usd'], $rt['price_note'] ?: 'person') ?></div></div>
              <a href="<?= tourUrl($rt) ?>" class="btn-dark">View Details <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- CUSTOM TOUR CTA -->
<section class="detail-custom-cta section-pad">
  <div class="container">
    <div class="detail-custom-inner reveal">
      <div class="section-tag">Need Something Different?</div>
      <h2 class="section-title">Looking for an <em>Exclusive Customized Tour?</em></h2>
      <p>No problem! Tell us your dream itinerary and we'll build the perfect Sri Lanka experience around you.</p>
      <div class="detail-custom-btns">
        <a href="contact.php#contact-form"                           class="btn-primary"><i class="fas fa-pencil-alt"></i> Plan My Custom Tour</a>
        <a href="https://wa.me/<?= e($whatsapp) ?>"  class="btn-outline" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script>
<?php if ($bookingRef || $bookingErrors): ?>
document.addEventListener('DOMContentLoaded', function() {
  var el = document.getElementById('booking');
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
document.querySelectorAll('.faq-question').forEach(btn => {
  btn.addEventListener('click', () => {
    const item   = btn.closest('.faq-item');
    const answer = item.querySelector('.faq-answer');
    const isOpen = btn.classList.contains('open');
    document.querySelectorAll('.faq-question').forEach(b => {
      b.classList.remove('open');
      b.closest('.faq-item').querySelector('.faq-answer').classList.remove('open');
    });
    if (!isOpen) { btn.classList.add('open'); answer.classList.add('open'); }
  });
});
</script>

</body>
</html>
