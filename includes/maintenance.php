<?php
$_siteName    = $s['site_name']        ?? 'GPS Lanka Travels';
$_msg         = !empty($s['maintenance_message']) ? $s['maintenance_message'] : "We're currently upgrading our website. We'll be back shortly!";
$_countdown   = $s['coming_soon_date'] ?? '';
$_waRaw       = $s['site_whatsapp']    ?? '';
$_email       = $s['site_email']       ?? '';

/* Format WhatsApp number: strip non-digits, ensure country code */
$_waNum = preg_replace('/\D/', '', $_waRaw);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Under Maintenance | <?= htmlspecialchars($_siteName) ?></title>
<link rel="icon" type="image/png" href="<?= SITE_URL ?>/images/favicon.png"/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0a3232 0%,#0f5252 50%,#1a6b5a 100%);font-family:'DM Sans',sans-serif;padding:24px}
.card{background:#fff;border-radius:24px;padding:60px 50px;max-width:580px;width:100%;text-align:center;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.icon-wrap{width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#0f5252,#1a8a6a);display:flex;align-items:center;justify-content:center;margin:0 auto 26px;font-size:36px;color:#fff}
.tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#c9a84c;margin-bottom:12px}
h1{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700;color:#0a3232;line-height:1.2;margin-bottom:14px}
.divider{width:60px;height:3px;background:linear-gradient(90deg,#c9a84c,#e8c96d);border-radius:2px;margin:0 auto 22px}
.msg{font-size:15px;color:#6b7280;line-height:1.7;margin-bottom:28px}

/* Countdown */
.countdown{display:flex;gap:16px;justify-content:center;margin-bottom:32px}
.cd-block{display:flex;flex-direction:column;align-items:center;gap:4px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:14px;padding:14px 20px;min-width:72px}
.cd-num{font-size:32px;font-weight:700;color:#0f5252;line-height:1;font-family:'Cormorant Garamond',serif}
.cd-label{font-size:10px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:#9ca3af}

/* Buttons */
.contact-row{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:28px}
.contact-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:50px;font-size:14px;font-weight:600;text-decoration:none;transition:transform .2s,box-shadow .2s}
.contact-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.15)}
.btn-wa{background:#25D366;color:#fff}
.btn-email{background:#0f5252;color:#fff}
.admin-link{font-size:12px;color:#d1d5db}
.admin-link a{color:#9ca3af;text-decoration:none}
.admin-link a:hover{color:#0f5252}
@media(max-width:500px){
  .card{padding:36px 22px}
  h1{font-size:28px}
  .countdown{gap:8px}
  .cd-block{padding:10px 12px;min-width:58px}
  .cd-num{font-size:26px}
}
</style>
</head>
<body>
<div class="card">
  <div class="icon-wrap"><i class="fas fa-tools"></i></div>
  <div class="tag">Under Maintenance</div>
  <h1>We'll Be Back Soon</h1>
  <div class="divider"></div>
  <p class="msg"><?= nl2br(htmlspecialchars($_msg)) ?></p>

  <?php if ($_countdown): ?>
  <div class="countdown">
    <div class="cd-block"><span class="cd-num" id="cdDays">00</span><span class="cd-label">Days</span></div>
    <div class="cd-block"><span class="cd-num" id="cdHours">00</span><span class="cd-label">Hours</span></div>
    <div class="cd-block"><span class="cd-num" id="cdMins">00</span><span class="cd-label">Minutes</span></div>
    <div class="cd-block"><span class="cd-num" id="cdSecs">00</span><span class="cd-label">Seconds</span></div>
  </div>
  <?php endif; ?>

  <div class="contact-row">
    <?php if ($_waNum): ?>
    <a href="https://wa.me/<?= htmlspecialchars($_waNum) ?>" class="contact-btn btn-wa" target="_blank">
      <i class="fab fa-whatsapp"></i> WhatsApp Us
    </a>
    <?php endif; ?>
    <?php if ($_email): ?>
    <a href="mailto:<?= htmlspecialchars($_email) ?>" class="contact-btn btn-email">
      <i class="fas fa-envelope"></i> Email Us
    </a>
    <?php endif; ?>
  </div>

  <div class="admin-link"><a href="<?= SITE_URL ?>/admin/">Admin Login</a></div>
</div>

<?php if ($_countdown): ?>
<script>
const target = new Date("<?= htmlspecialchars($_countdown) ?>").getTime();
function tick() {
  const diff = target - Date.now();
  if (diff <= 0) {
    document.getElementById('cdDays').textContent  = '00';
    document.getElementById('cdHours').textContent = '00';
    document.getElementById('cdMins').textContent  = '00';
    document.getElementById('cdSecs').textContent  = '00';
    return;
  }
  const d = Math.floor(diff / 86400000);
  const h = Math.floor((diff % 86400000) / 3600000);
  const m = Math.floor((diff % 3600000)  / 60000);
  const s = Math.floor((diff % 60000)    / 1000);
  document.getElementById('cdDays').textContent  = String(d).padStart(2,'0');
  document.getElementById('cdHours').textContent = String(h).padStart(2,'0');
  document.getElementById('cdMins').textContent  = String(m).padStart(2,'0');
  document.getElementById('cdSecs').textContent  = String(s).padStart(2,'0');
}
tick();
setInterval(tick, 1000);
</script>
<?php endif; ?>
</body>
</html>
