<?php

date_default_timezone_set('Asia/Colombo');

define('DB_HOST',   '127.0.0.1');
define('DB_PORT',   '3307');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_NAME',   'gps_lanka_db');
define('SITE_URL',  'http://localhost:8080/gps-lanka');
define('ADMIN_URL', 'http://localhost:8080/gps-lanka/admin');

/* MySQLi connection ($conn) */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;background:#fff0f0;border:2px solid #e74c3c;padding:28px;margin:40px auto;max-width:600px;border-radius:12px;">
        <h2 style="color:#e74c3c;">Database Connection Failed</h2>
        <p><strong>Error:</strong> ' . $conn->connect_error . '</p>
        <p>Make sure XAMPP is running, MySQL is on port <strong>3307</strong>, and database <strong>gps_lanka_db</strong> exists.</p>
        <p>Run <code>db_setup.sql</code> in phpMyAdmin to create all tables.</p>
    </div>');
}
$conn->set_charset('utf8mb4');

/* PDO connection ($pdo) — used by login.php */
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;background:#fff0f0;border:2px solid #e74c3c;padding:28px;margin:40px auto;max-width:600px;border-radius:12px;">
        <h2 style="color:#e74c3c;">PDO Connection Failed</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}

/* Helper functions */
function clean($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

function adminName() {
    return isset($_SESSION['admin_name']) ? $_SESSION['admin_name']
         : (isset($_SESSION['admin_username']) ? ucfirst($_SESSION['admin_username']) : 'Admin');
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'min ago';
    return 'Just now';
}

function sanitizeFilename($name) {
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9\-_\.]/', '-', $name);
    return preg_replace('/-+/', '-', $name);
}

function uploadImage($file, $folder = 'uploads') {
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) return ['ok'=>false,'msg'=>'Only JPG, PNG, WebP, GIF allowed.'];
    if ($file['size'] > 25 * 1024 * 1024)  return ['ok'=>false,'msg'=>'Max file size is 25 MB.'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_') . '.' . $ext;
    $dir      = __DIR__ . '/../../' . $folder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) return ['ok'=>false,'msg'=>'Upload failed.'];
    return ['ok'=>true,'path'=> $folder . '/' . $filename];
}
