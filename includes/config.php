<?php
if (!defined('GPS_CONFIG_LOADED')) {
    define('GPS_CONFIG_LOADED', true);

    date_default_timezone_set('Asia/Colombo');

    define('DB_HOST',  '127.0.0.1');
    define('DB_PORT',  '3307');
    define('DB_USER',  'root');
    define('DB_PASS',  '');
    define('DB_NAME',  'gps_lanka_db');
    define('SITE_URL', 'http://localhost:8080/gps-lanka');

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    if ($conn->connect_error) { $conn = null; }
    else $conn->set_charset('utf8mb4');

    /* Load settings */
    $s = [];
    if ($conn) {
        $r = @$conn->query("SELECT skey, sval FROM settings");
        if ($r) while ($row = $r->fetch_assoc()) $s[$row['skey']] = $row['sval'];
    }

    /* Sanitize WhatsApp number — strip everything except digits */
    if (!empty($s['site_whatsapp'])) {
        $s['site_whatsapp'] = preg_replace('/\D/', '', $s['site_whatsapp']);
    }

    function setting($key, $default = '') { global $s; return $s[$key] ?? $default; }

    /* ── MAINTENANCE MODE ── */
    if (!empty($s['maintenance_mode']) && $s['maintenance_mode'] === '1') {
        // Allow admin panel and the maintenance page itself through
        $reqPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $isAdmin = strpos($reqPath, DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR) !== false;
        $isMaint = basename($reqPath) === 'maintenance.php';
        if (!$isAdmin && !$isMaint) {
            $msg = $s['maintenance_message'] ?? "We're currently upgrading our website. We'll be back shortly!";
            include __DIR__ . '/maintenance.php';
            exit;
        }
    }
    function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
    function imgUrl($path) { return $path ? SITE_URL . '/' . ltrim($path, '/') : ''; }
    function tourUrl($t)   { return 'tour-detail.php?slug=' . urlencode($t['slug'] ?? $t['id']); }
    function blogUrl($p)   { return 'blog-detail.php?slug=' . urlencode($p['slug'] ?? $p['id']); }
    function starRating($n) {
        $out = '';
        for ($i = 1; $i <= 5; $i++)
            $out .= '<i class="fa' . ($i <= $n ? 's' : 'r') . ' fa-star"></i>';
        return $out;
    }

    /* Parse highlights: handles both HTML (TinyMCE) and plain comma-separated */
    function parseHighlights($raw, $max = 4) {
        if (!$raw) return [];
        $trimmed = trim($raw);
        if (($trimmed[0] === '{' || $trimmed[0] === '[') && json_decode($trimmed) !== null) return [];
        if (strpos($raw, '<') !== false) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/si', $raw, $m);
            $items = array_map(fn($v) => trim(strip_tags($v)), $m[1]);
        } else {
            $items = array_map('trim', explode(',', $raw));
        }
        return array_slice(array_filter($items), 0, $max);
    }

    /* Parse a list field (includes/excludes): handles HTML <li> or newline/comma plain text */
    function parseListItems($raw) {
        if (!$raw) return [];
        $trimmed = trim($raw);
        // Skip if content is JSON (someone pasted SEO/schema data by mistake)
        if (($trimmed[0] === '{' || $trimmed[0] === '[') && json_decode($trimmed) !== null) return [];
        if (strpos($raw, '<') !== false) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/si', $raw, $m);
            $items = array_map(fn($v) => trim(strip_tags($v)), $m[1]);
        } else {
            $items = array_map('trim', preg_split('/[\n,]+/', $raw));
        }
        return array_filter($items);
    }

    /* Check if a field contains HTML (from TinyMCE) */
    function isHtml($str) { return $str && strpos($str, '<') !== false; }

    /* Parse itinerary (JSON from day builder, HTML from TinyMCE, or plain text) into [{title, body}] */
    function parseItineraryHtml($html) {
        if (!$html) return [];
        $trimmed = trim($html);
        // JSON from admin day builder
        if ($trimmed && $trimmed[0] === '[') {
            $arr = json_decode($trimmed, true);
            if (is_array($arr)) {
                return array_values(array_filter($arr, fn($item) => !empty($item['title'])));
            }
        }
        if (strpos($html, '<') === false) return [];
        $dec = fn($s) => html_entity_decode(trim(strip_tags($s)), ENT_QUOTES, 'UTF-8');
        $items = [];
        // Strategy 1: split by <h2>/<h3>/<h4> headings
        if (preg_match('/<h[2-4]/i', $html)) {
            preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>(.*?)(?=<h[2-4]|$)/si', $html, $m);
            foreach ($m[1] as $i => $ttl) {
                $ttl = $dec($ttl);
                if (!$ttl) continue;
                $items[] = ['title' => $ttl, 'body' => $dec($m[2][$i])];
            }
        }
        // Strategy 2: <p><strong>Day N...</strong> inline text</p>
        if (empty($items) && preg_match('/Day\s+\d+/i', $html)) {
            preg_match_all('/<p[^>]*>\s*<strong[^>]*>(Day\s+\d+[^<]*)<\/strong>(.*?)<\/p>(.*?)(?=<p[^>]*>\s*<strong[^>]*>Day\s+\d+|$)/si', $html, $m);
            foreach ($m[1] as $i => $ttl) {
                $ttl = $dec($ttl);
                if ($ttl) $items[] = ['title' => $ttl, 'body' => $dec($m[2][$i] . ' ' . $m[3][$i])];
            }
        }
        // Strategy 3: every <p><strong>...</strong> starts a new item
        if (empty($items) && preg_match('/<strong>/i', $html)) {
            preg_match_all('/<p[^>]*>\s*<strong[^>]*>(.*?)<\/strong>(.*?)<\/p>(.*?)(?=<p[^>]*>\s*<strong|$)/si', $html, $m);
            foreach ($m[1] as $i => $ttl) {
                $ttl = $dec($ttl);
                if ($ttl) $items[] = ['title' => $ttl, 'body' => $dec($m[2][$i] . ' ' . $m[3][$i])];
            }
        }
        return $items;
    }

    /* Format tour price display */
    function tourPrice($price_usd, $price_note) {
        $p = (float)$price_usd;
        if ($p > 0) {
            return '$' . number_format($p, 0) . ($price_note ? ' <span>/ ' . htmlspecialchars($price_note, ENT_QUOTES) . '</span>' : '');
        }
        return $price_note ? htmlspecialchars($price_note, ENT_QUOTES) : 'Contact Us';
    }
}
