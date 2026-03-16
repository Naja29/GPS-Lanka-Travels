<?php
session_start();
if (!empty($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
require_once 'includes/db.php';

$error = ''; $timeout = isset($_GET['timeout']);

/* Fetch site logo from settings  */
$siteLogo = '';
try {
    $logoRow = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key='site_logo' LIMIT 1");
    if ($logoRow && $r = $logoRow->fetch_assoc()) { $siteLogo = $r['setting_value']; }
} catch (Exception $e) { /* table may not exist yet */ }
// Fallback to default images/logo.png
if (!$siteLogo && file_exists(__DIR__ . '/../images/logo.png')) { $siteLogo = 'images/logo.png'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $user['id'];
            $_SESSION['admin_username']  = $user['username'];
            $_SESSION['admin_name']      = $user['full_name'] ?: $user['username'];
            $_SESSION['admin_role']      = $user['role'];
            $_SESSION['last_activity']   = time();
            $conn->query("UPDATE admin_users SET last_login = NOW() WHERE id = " . (int)$user['id']);
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login | GPS Lanka Travels</title>
  <link rel="icon" type="image/png" href="../images/favicon.png"/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--teal-dark:#0a3d3d;--teal:#0f5252;--teal-light:#1a7575;--gold:#c9a84c;--gold-light:#e2c97e;--white:#fff;--off-white:#f8f6f0}
    html,body{height:100%;width:100%;overflow:hidden;font-family:'DM Sans',sans-serif;display:flex;margin:0;padding:0}
    .login-visual{flex:1;position:relative;overflow:hidden;background:url('../images/hero-slide-1.jpg') center/cover no-repeat;min-height:100vh;}
    .login-visual::before{content:'';position:absolute;inset:0;background:linear-gradient(145deg,rgba(10,61,61,.88) 0%,rgba(10,61,61,.6) 40%,rgba(0,0,0,.65) 100%);z-index:1}
    .login-visual::after{content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 20% 80%,rgba(201,168,76,.12) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(201,168,76,.08) 0%,transparent 50%);z-index:2}
    .visual-inner{position:relative;z-index:3;height:100%;display:flex;flex-direction:column;justify-content:space-between;padding:48px}
    .v-logo{display:flex;align-items:center;gap:14px;animation:fadeDown .8s .1s both}
    .v-logo-icon{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--gold),#a8782a);display:flex;align-items:center;justify-content:center;box-shadow:0 6px 24px rgba(201,168,76,.35)}
    .v-logo-icon i{color:#fff;font-size:22px}
    .v-brand{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:#fff;line-height:1}
    .v-tagline{font-size:11px;color:rgba(255,255,255,.5);letter-spacing:2px;text-transform:uppercase;margin-top:3px}
    .v-text{animation:fadeUp .9s .3s both}
    .v-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(201,168,76,.18);border:1px solid rgba(201,168,76,.35);color:var(--gold-light);font-size:11px;letter-spacing:2px;text-transform:uppercase;padding:6px 16px;border-radius:50px;margin-bottom:20px}
    .v-title{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,4vw,56px);font-weight:300;color:#fff;line-height:1.15;margin-bottom:16px}
    .v-title em{font-style:italic;color:var(--gold-light)}
    .v-title strong{font-weight:700;display:block}
    .v-sub{font-size:15px;color:rgba(255,255,255,.65);font-weight:300;line-height:1.7;max-width:420px}
    .v-stats{display:flex;gap:32px;animation:fadeUp .9s .5s both}
    .v-stat-num{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--gold-light);line-height:1}
    .v-stat-lbl{font-size:11px;color:rgba(255,255,255,.5);letter-spacing:1px;text-transform:uppercase;margin-top:4px}
    .v-stat-div{width:1px;background:rgba(255,255,255,.12);align-self:stretch;margin:0 4px}
    .dest-badges{position:absolute;top:50%;right:32px;transform:translateY(-50%);display:flex;flex-direction:column;gap:10px;z-index:4;animation:fadeLeft .8s .7s both}
    .dest-badge{background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.15);border-radius:50px;padding:8px 16px 8px 10px;display:flex;align-items:center;gap:10px;transition:background .25s,transform .25s}
    .dest-badge:hover{background:rgba(255,255,255,.18);transform:translateX(-4px)}
    .dbdot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .dest-badge span{font-size:12px;color:rgba(255,255,255,.85);font-weight:500;white-space:nowrap}
    .particles{position:absolute;inset:0;z-index:2;pointer-events:none;overflow:hidden}
    .particle{position:absolute;width:3px;height:3px;background:rgba(201,168,76,.4);border-radius:50%;animation:float linear infinite}
    @keyframes float{0%{transform:translateY(100vh) rotate(0deg);opacity:0}10%{opacity:1}90%{opacity:.6}100%{transform:translateY(-100px) rotate(720deg);opacity:0}}
    /* FORM SIDE */
    .login-form-side{width:480px;flex-shrink:0;background:#fff;display:flex;flex-direction:column;justify-content:center;padding:60px 52px;position:relative;overflow-y:auto;min-height:100vh;height:100vh;}
    .login-form-side::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--teal-dark),var(--gold),var(--teal-light))}
    .form-header{margin-bottom:32px;animation:fadeUp .7s .2s both}
    .form-tag{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:10px;display:flex;align-items:center;gap:8px}
    .form-tag::before{content:'';width:24px;height:2px;background:var(--gold);border-radius:2px}
    .form-header h1{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:600;color:var(--teal-dark);line-height:1.1;margin-bottom:10px}
    .form-header h1 em{font-style:italic;color:var(--gold)}
    .form-header p{font-size:14px;color:#999;font-weight:300;line-height:1.6}
    .login-alert{padding:13px 16px;border-radius:12px;font-size:13.5px;display:flex;align-items:center;gap:10px;margin-bottom:20px;animation:shake .4s}
    .login-alert.error{background:rgba(231,76,60,.08);border:1.5px solid rgba(231,76,60,.25);color:#c0392b}
    .login-alert.timeout{background:rgba(243,156,18,.08);border:1.5px solid rgba(243,156,18,.25);color:#935116}
    @keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
    .input-group{margin-bottom:20px;animation:fadeUp .7s both}
    .input-group:nth-child(1){animation-delay:.3s}.input-group:nth-child(2){animation-delay:.4s}
    .input-group label{display:block;font-size:11.5px;font-weight:600;color:#888;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px}
    .input-wrap{position:relative}
    .input-wrap .iico{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#ccc;font-size:15px;pointer-events:none;transition:color .25s}
    .input-wrap:focus-within .iico{color:var(--teal)}
    .input-wrap input{width:100%;padding:14px 16px 14px 46px;border:1.5px solid #e8e8e8;border-radius:14px;font-size:14.5px;font-family:'DM Sans',sans-serif;color:#333;background:var(--off-white);outline:none;transition:border-color .25s,background .25s,box-shadow .25s}
    .input-wrap input:focus{border-color:var(--teal);background:#fff;box-shadow:0 0 0 4px rgba(15,82,82,.07)}
    .input-wrap input::placeholder{color:#bbb}
    .toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#bbb;cursor:pointer;font-size:15px;transition:color .2s;padding:4px}
    .toggle-pw:hover{color:var(--teal)}
    .form-options{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;animation:fadeUp .7s .5s both}
    .remember-me{display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none}
    .remember-me input[type=checkbox]{display:none}
    .custom-check{width:18px;height:18px;border-radius:5px;border:1.5px solid #ddd;background:var(--off-white);display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0}
    .remember-me input:checked~.custom-check{background:var(--teal-dark);border-color:var(--teal-dark)}
    .custom-check i{color:#fff;font-size:10px;opacity:0;transition:opacity .2s}
    .remember-me input:checked~.custom-check i{opacity:1}
    .remember-label{font-size:13px;color:#888}
    .forgot-link{font-size:13px;color:var(--teal);text-decoration:none;font-weight:500;transition:color .2s}
    .forgot-link:hover{color:var(--gold)}
    .login-btn{width:100%;padding:16px;background:linear-gradient(135deg,var(--teal-dark),var(--teal));color:#fff;border:none;border-radius:14px;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;letter-spacing:.5px;box-shadow:0 8px 32px rgba(10,61,61,.28);transition:transform .25s,box-shadow .25s;display:flex;align-items:center;justify-content:center;gap:10px;position:relative;overflow:hidden;animation:fadeUp .7s .55s both}
    .login-btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);transition:left .5s}
    .login-btn:hover::before{left:100%}
    .login-btn:hover{transform:translateY(-2px);box-shadow:0 16px 44px rgba(10,61,61,.35)}
    .form-footer{margin-top:32px;padding-top:20px;border-top:1px solid #f0f0f0;text-align:center;animation:fadeUp .7s .65s both}
    .form-footer p{font-size:12px;color:#bbb}
    .form-footer a{color:var(--teal);text-decoration:none;font-weight:500}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    @keyframes fadeDown{from{opacity:0;transform:translateY(-16px)}to{opacity:1;transform:translateY(0)}}
    @keyframes fadeLeft{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
    @media(max-width:960px){.login-visual{display:none}body{overflow:auto}.login-form-side{width:100%;min-height:100vh}}
  </style>
</head>
<body>
  <div class="login-visual">
    <div class="particles" id="particles"></div>
    <div class="dest-badges">
      <div class="dest-badge"><div class="dbdot" style="background:#27ae60"></div><span>Sigiriya Rock Fortress</span></div>
      <div class="dest-badge"><div class="dbdot" style="background:#3498db"></div><span>Mirissa Beach</span></div>
      <div class="dest-badge"><div class="dbdot" style="background:var(--gold)"></div><span>Ella Nine Arch Bridge</span></div>
      <div class="dest-badge"><div class="dbdot" style="background:#e74c3c"></div><span>Yala National Park</span></div>
      <div class="dest-badge"><div class="dbdot" style="background:#9b59b6"></div><span>Temple of Tooth Relic</span></div>
    </div>
    <div class="visual-inner">
      <div class="v-logo">
        <?php if ($siteLogo): ?>
          <img src="../<?= htmlspecialchars($siteLogo) ?>" alt="GPS Lanka Travels" style="height:52px;max-width:180px;object-fit:contain;filter:drop-shadow(0 2px 8px rgba(0,0,0,.3))"/>
        <?php else: ?>
          <div class="v-logo-icon"><i class="fas fa-compass"></i></div>
        <?php endif; ?>
        <div><div class="v-brand">GPS Lanka Travels</div><div class="v-tagline">Admin Portal</div></div>
      </div>
      <div class="v-text">
        <div class="v-tag"><i class="fas fa-shield-alt"></i> Secure Admin Access</div>
        <h2 class="v-title">Manage Your<br><em>Sri Lanka</em><br><strong>Travel Empire</strong></h2>
        <p class="v-sub">Control tours, bookings, enquiries, gallery and all website content from one elegant dashboard.</p>
      </div>
      <div class="v-stats">
        <div><div class="v-stat-num">500+</div><div class="v-stat-lbl">Happy Guests</div></div>
        <div class="v-stat-div"></div>
        <div><div class="v-stat-num">50+</div><div class="v-stat-lbl">Tour Packages</div></div>
        <div class="v-stat-div"></div>
        <div><div class="v-stat-num">10+</div><div class="v-stat-lbl">Years Experience</div></div>
      </div>
    </div>
  </div>

  <div class="login-form-side">
    <div class="form-header">
      <div class="form-tag">Admin Portal</div>
      <h1>Welcome <em>Back</em></h1>
      <p>Sign in to manage your tours, bookings, gallery and website content.</p>
    </div>

    <?php if ($timeout): ?><div class="login-alert timeout"><i class="fas fa-clock"></i> Session expired. Please sign in again.</div><?php endif; ?>
    <?php if ($error):   ?><div class="login-alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <div class="input-group">
        <label>Username</label>
        <div class="input-wrap">
          <i class="fas fa-user iico"></i>
          <input type="text" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required/>
        </div>
      </div>
      <div class="input-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock iico"></i>
          <input type="password" name="password" id="pwInput" placeholder="Enter your password" autocomplete="current-password" required/>
          <button type="button" class="toggle-pw" tabindex="-1"><i class="fas fa-eye" id="pwEye"></i></button>
        </div>
      </div>
      <div class="form-options">
        <label class="remember-me">
          <input type="checkbox" name="remember"/>
          <div class="custom-check"><i class="fas fa-check"></i></div>
          <span class="remember-label">Remember me</span>
        </label>
        <a href="#" class="forgot-link">Forgot password?</a>
      </div>
      <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Sign In to Dashboard</button>
    </form>

    <div class="form-footer">
      <p>GPS Lanka Travels &copy; <?= date('Y') ?> &nbsp;·&nbsp; <a href="../index.html" target="_blank">View Website</a> &nbsp;·&nbsp; <a href="mailto:info@gpslankatravels.com">Support</a></p>
    </div>
  </div>

  <script>
    const pc=document.getElementById('particles');
    if(pc){for(let i=0;i<18;i++){const p=document.createElement('div');p.className='particle';p.style.cssText=`left:${Math.random()*100}%;width:${2+Math.random()*3}px;height:${2+Math.random()*3}px;animation-duration:${6+Math.random()*10}s;animation-delay:${Math.random()*8}s;`;pc.appendChild(p);}}
    document.querySelector('.toggle-pw')?.addEventListener('click',()=>{const i=document.getElementById('pwInput'),e=document.getElementById('pwEye');i.type=i.type==='text'?'password':'text';e.className=i.type==='text'?'fas fa-eye-slash':'fas fa-eye';});
  </script>
</body>
</html>
