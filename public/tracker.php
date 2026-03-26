<?php
/**
 * GT Tracker — Stabil Final
 */

if (defined('GT_TRACKER_LOADED')) {
    return;
}
define('GT_TRACKER_LOADED', true);

if (!defined('GT_REQUEST_START')) {
    define('GT_REQUEST_START', microtime(true));
}

require_once __DIR__ . '/gt_config.php';

function gt_substr_safe($value, $length)
{
    $value = (string)$value;
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $length, 'UTF-8');
    }
    return substr($value, 0, $length);
}

function gt_make_sid()
{
    if (function_exists('random_bytes')) {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable $e) {
        }
    }
    return md5(uniqid((string)mt_rand(), true));
}

function gt_flag_from_country_code($cc)
{
    $cc = strtoupper(trim((string)$cc));
    if (strlen($cc) !== 2 || !function_exists('mb_chr')) {
        return '';
    }

    $a = ord($cc[0]);
    $b = ord($cc[1]);

    if ($a < 65 || $a > 90 || $b < 65 || $b > 90) {
        return '';
    }

    return mb_chr($a - 65 + 0x1F1E6, 'UTF-8') . mb_chr($b - 65 + 0x1F1E6, 'UTF-8');
}

/* -------------------------------------------------
   User Agent / Bot filtre
------------------------------------------------- */

$ua_raw = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
$ua_lc  = strtolower($ua_raw);

if ($ua_raw === '') {
    return;
}

$bot_signatures = [
    'bot', 'crawl', 'spider', 'slurp', 'wget', 'curl',
    'python', 'scrapy', 'ahrefs', 'semrush', 'bingbot',
    'googlebot', 'yandexbot', 'baiduspider', 'duckduckbot'
];

foreach ($bot_signatures as $sig) {
    if (strpos($ua_lc, $sig) !== false) {
        return;
    }
}

/* -------------------------------------------------
   Veritabani
------------------------------------------------- */

try {
    $pdo = new PDO(
        'mysql:host=' . GT_DB_HOST . ';dbname=' . GT_DB_NAME . ';charset=utf8mb4',
        GT_DB_USER,
        GT_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_TIMEOUT => 2,
        ]
    );
} catch (Throwable $e) {
    return;
}

/* -------------------------------------------------
   Session ID
------------------------------------------------- */

$sid = isset($_COOKIE['gt_sid']) ? (string)$_COOKIE['gt_sid'] : '';
if ($sid === '' || !preg_match('/^[a-f0-9]{32,64}$/', $sid)) {
    $sid = gt_make_sid();
    @setcookie('gt_sid', $sid, time() + 86400 * 365, '/', '', false, true);
}

/* -------------------------------------------------
   IP
------------------------------------------------- */

$ip = '';
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $ip = $_SERVER['HTTP_X_REAL_IP'];
} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
}
$ip = trim((string)$ip);
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    $ip = '0.0.0.0';
}

/* -------------------------------------------------
   Returning visitor
------------------------------------------------- */

$is_returning = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gt_visits WHERE session_id = ? LIMIT 1");
    $stmt->execute([$sid]);
    $is_returning = ((int)$stmt->fetchColumn() > 0) ? 1 : 0;
} catch (Throwable $e) {
    $is_returning = 0;
}

/* -------------------------------------------------
   Device / Browser / OS
------------------------------------------------- */

$device = 'Desktop';
$device_type = 'desktop';

if (preg_match('/ipad|tablet/i', $ua_raw)) {
    $device = 'Tablet';
    $device_type = 'tablet';
} elseif (preg_match('/mobile|iphone|android/i', $ua_raw)) {
    $device = 'Mobile';
    $device_type = 'mobile';
}

$browser = 'Diger';
$ua_family = '';
$ua_version = '';

if (preg_match('/Edg\/([0-9\.]+)/i', $ua_raw, $m)) {
    $browser = 'Edge';
    $ua_family = 'Edge';
    $ua_version = $m[1] ?? '';
} elseif (preg_match('/OPR\/([0-9\.]+)/i', $ua_raw, $m) || preg_match('/Opera\/([0-9\.]+)/i', $ua_raw, $m)) {
    $browser = 'Opera';
    $ua_family = 'Opera';
    $ua_version = $m[1] ?? '';
} elseif (preg_match('/Chrome\/([0-9\.]+)/i', $ua_raw, $m)) {
    $browser = 'Chrome';
    $ua_family = 'Chrome';
    $ua_version = $m[1] ?? '';
} elseif (preg_match('/Firefox\/([0-9\.]+)/i', $ua_raw, $m)) {
    $browser = 'Firefox';
    $ua_family = 'Firefox';
    $ua_version = $m[1] ?? '';
} elseif (preg_match('/Version\/([0-9\.]+).*Safari/i', $ua_raw, $m)) {
    $browser = 'Safari';
    $ua_family = 'Safari';
    $ua_version = $m[1] ?? '';
}

$os = 'Diger';
$os_family = '';
$os_version = '';

if (preg_match('/Windows NT 10\.0/i', $ua_raw)) {
    $os = 'Windows';
    $os_family = 'Windows';
    $os_version = '10/11';
} elseif (preg_match('/Windows NT 6\.3/i', $ua_raw)) {
    $os = 'Windows';
    $os_family = 'Windows';
    $os_version = '8.1';
} elseif (preg_match('/Windows NT 6\.2/i', $ua_raw)) {
    $os = 'Windows';
    $os_family = 'Windows';
    $os_version = '8';
} elseif (preg_match('/Windows NT 6\.1/i', $ua_raw)) {
    $os = 'Windows';
    $os_family = 'Windows';
    $os_version = '7';
} elseif (preg_match('/iPhone OS ([0-9_]+)/i', $ua_raw, $m) || preg_match('/CPU OS ([0-9_]+)/i', $ua_raw, $m)) {
    $os = 'iOS';
    $os_family = 'iOS';
    $os_version = str_replace('_', '.', $m[1] ?? '');
} elseif (preg_match('/Android ([0-9\.]+)/i', $ua_raw, $m)) {
    $os = 'Android';
    $os_family = 'Android';
    $os_version = $m[1] ?? '';
} elseif (preg_match('/Mac OS X ([0-9_]+)/i', $ua_raw, $m)) {
    $os = 'macOS';
    $os_family = 'macOS';
    $os_version = str_replace('_', '.', $m[1] ?? '');
} elseif (stripos($ua_raw, 'Linux') !== false) {
    $os = 'Linux';
    $os_family = 'Linux';
    $os_version = '';
}

/* -------------------------------------------------
   Geo lookup
------------------------------------------------- */

$country = '';
$city = '';
$flag = '';
$isp = '';

$geo = null;
$geo_cache = sys_get_temp_dir() . '/gt_geo_' . md5($ip) . '.json';

if (is_file($geo_cache) && @filemtime($geo_cache) > (time() - 86400)) {
    $raw_cache = @file_get_contents($geo_cache);
    if ($raw_cache) {
        $geo = json_decode($raw_cache, true);
    }
} else {
    $raw_geo = false;

    if (function_exists('curl_init')) {
        $ch = curl_init('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=country,city,countryCode,isp&lang=tr');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $raw_geo = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $raw_geo = @file_get_contents('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=country,city,countryCode,isp&lang=tr');
    }

    if ($raw_geo) {
        $geo = json_decode($raw_geo, true);
        if (is_array($geo)) {
            @file_put_contents($geo_cache, json_encode($geo));
        }
    }
}

if (is_array($geo) && !empty($geo['country'])) {
    $country = gt_substr_safe($geo['country'], 100);
    $city    = gt_substr_safe($geo['city'] ?? '', 100);
    $isp     = gt_substr_safe($geo['isp'] ?? '', 200);
    $flag    = gt_flag_from_country_code($geo['countryCode'] ?? '');
}

/* -------------------------------------------------
   Referrer
------------------------------------------------- */

$referrer = gt_substr_safe($_SERVER['HTTP_REFERER'] ?? '', 500);
$referrer_domain = '';
$search_keyword = '';

if ($referrer !== '') {
    $parsed = @parse_url($referrer);
    $referrer_domain = gt_substr_safe($parsed['host'] ?? '', 200);
    $own_host = (string)($_SERVER['HTTP_HOST'] ?? '');

    if ($referrer_domain !== '' && $own_host !== '' && stripos($referrer_domain, $own_host) !== false) {
        $referrer_domain = '';
    } elseif (preg_match('/google|bing|yandex|yahoo|duckduck/i', $referrer_domain)) {
        $qp = [];
        parse_str($parsed['query'] ?? '', $qp);
        $kw = $qp['q'] ?? ($qp['text'] ?? ($qp['p'] ?? ''));
        $search_keyword = gt_substr_safe($kw, 300);
    }
}

/* -------------------------------------------------
   Sayfa / durum
------------------------------------------------- */

$page_url = gt_substr_safe($_SERVER['REQUEST_URI'] ?? '/', 500);
$request_method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$http_status = (int)http_response_code();
if ($http_status <= 0) {
    $http_status = 200;
}

$is_404 = ($http_status === 404) ? 1 : 0;
$is_403 = ($http_status === 403) ? 1 : 0;
$is_500 = ($http_status >= 500) ? 1 : 0;

$route_name = '';

/* -------------------------------------------------
   Uye bilgisi
------------------------------------------------- */

$member_id = '';
$member_name = '';

if (defined('GT_MEMBER_SESSION_ID') && GT_MEMBER_SESSION_ID && session_status() === PHP_SESSION_ACTIVE) {
    $member_id = gt_substr_safe($_SESSION[GT_MEMBER_SESSION_ID] ?? '', 100);
}
if (defined('GT_MEMBER_SESSION_NAME') && GT_MEMBER_SESSION_NAME && session_status() === PHP_SESSION_ACTIVE) {
    $member_name = gt_substr_safe($_SESSION[GT_MEMBER_SESSION_NAME] ?? '', 200);
}

/* -------------------------------------------------
   IP listesi
------------------------------------------------- */

$list_type = '';
$list_note = '';

try {
    $list_stmt = $pdo->prepare("
        SELECT list_type, note
        FROM gt_ip_lists
        WHERE ip = ? AND is_active = 1
        ORDER BY id DESC
        LIMIT 1
    ");
    $list_stmt->execute([$ip]);
    $list_row = $list_stmt->fetch(PDO::FETCH_ASSOC);

    if ($list_row) {
        $list_type = (string)($list_row['list_type'] ?? '');
        $list_note = (string)($list_row['note'] ?? '');
    }
} catch (Throwable $e) {
    $list_type = '';
    $list_note = '';
}

/* -------------------------------------------------
   Risk hesaplama
------------------------------------------------- */

$risk_score = 0;
$risk_flags = [];

if ($is_404) {
    $risk_score += 10;
    $risk_flags[] = '404';
}
if ($is_403) {
    $risk_score += 20;
    $risk_flags[] = '403';
}
if ($is_500) {
    $risk_score += 15;
    $risk_flags[] = '500';
}
if ($request_method !== 'GET') {
    $risk_score += 5;
    $risk_flags[] = 'post';
}
if ($browser === 'Diger') {
    $risk_score += 10;
    $risk_flags[] = 'unknown_browser';
}
if ($os === 'Diger') {
    $risk_score += 8;
    $risk_flags[] = 'unknown_os';
}

$hits_1m = 0;
$hits_10m = 0;
$err404_10m = 0;

try {
    $q = $pdo->prepare("SELECT COUNT(*) FROM gt_visits WHERE ip = ? AND created_at >= NOW() - INTERVAL 1 MINUTE");
    $q->execute([$ip]);
    $hits_1m = (int)$q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM gt_visits WHERE ip = ? AND created_at >= NOW() - INTERVAL 10 MINUTE");
    $q->execute([$ip]);
    $hits_10m = (int)$q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM gt_visits WHERE ip = ? AND is_404 = 1 AND created_at >= NOW() - INTERVAL 10 MINUTE");
    $q->execute([$ip]);
    $err404_10m = (int)$q->fetchColumn();
} catch (Throwable $e) {
    $hits_1m = 0;
    $hits_10m = 0;
    $err404_10m = 0;
}

if ($hits_1m >= 3) {
    $risk_score += 20;
    $risk_flags[] = 'burst';
}
if ($hits_1m >= 6) {
    $risk_score += 20;
    $risk_flags[] = 'burst_high';
}
if ($hits_10m >= 12) {
    $risk_score += 20;
    $risk_flags[] = 'volume';
}
if ($err404_10m >= 3) {
    $risk_score += 20;
    $risk_flags[] = 'scan_404';
}

if ($list_type === 'white') {
    $risk_score = 0;
    $risk_flags[] = 'whitelisted';
}
if ($list_type === 'black') {
    $risk_score = 100;
    $risk_flags[] = 'blacklisted';
}

$risk_score = (int)max(0, min(100, $risk_score));
$risk_flags = array_values(array_unique(array_filter($risk_flags)));
$risk_flags_str = gt_substr_safe(implode(',', $risk_flags), 500);

/* -------------------------------------------------
   Response time
------------------------------------------------- */

$response_time_ms = (int)round((microtime(true) - GT_REQUEST_START) * 1000);
if ($response_time_ms < 0) {
    $response_time_ms = 0;
}

/* -------------------------------------------------
   Kayıt
------------------------------------------------- */

$visit_id = 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO gt_visits (
            session_id,
            ip,
            country,
            city,
            flag,
            isp,
            device,
            browser,
            os,
            page_url,
            referrer,
            referrer_domain,
            search_keyword,
            exit_url,
            time_on_page,
            load_time,
            is_returning,
            is_404,
            http_status,
            is_403,
            is_500,
            request_method,
            user_agent,
            ua_family,
            ua_version,
            os_family,
            os_version,
            device_type,
            route_name,
            response_time_ms,
            risk_score,
            risk_flags,
            member_id,
            member_name,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $sid,
        $ip,
        $country,
        $city,
        $flag,
        $isp,
        $device,
        $browser,
        $os,
        $page_url,
        $referrer,
        $referrer_domain,
        $search_keyword,
        '',
        0,
        0,
        $is_returning,
        $is_404,
        $http_status,
        $is_403,
        $is_500,
        $request_method,
        gt_substr_safe($ua_raw, 65000),
        gt_substr_safe($ua_family, 100),
        gt_substr_safe($ua_version, 50),
        gt_substr_safe($os_family, 100),
        gt_substr_safe($os_version, 50),
        gt_substr_safe($device_type, 50),
        gt_substr_safe($route_name, 150),
        $response_time_ms,
        $risk_score,
        $risk_flags_str,
        $member_id,
        $member_name,
    ]);

    $visit_id = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    return;
}

/* -------------------------------------------------
   Online guncelle
------------------------------------------------- */

try {
    $online_stmt = $pdo->prepare("
        INSERT INTO gt_online (session_id, ip, country, flag, page_url, last_seen)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            ip = VALUES(ip),
            country = VALUES(country),
            flag = VALUES(flag),
            page_url = VALUES(page_url),
            last_seen = NOW()
    ");
    $online_stmt->execute([
        $sid,
        $ip,
        $country,
        $flag,
        $page_url
    ]);

    $pdo->exec('DELETE FROM gt_online WHERE last_seen < NOW() - INTERVAL ' . (int)GT_ONLINE_TIMEOUT . ' MINUTE');
} catch (Throwable $e) {
}

/* -------------------------------------------------
   Blacklist event
------------------------------------------------- */

if ($list_type === 'black') {
    try {
        $meta = json_encode([
            'note' => $list_note,
            'risk_score' => $risk_score,
            'risk_flags' => $risk_flags,
            'status' => $http_status,
            'method' => $request_method
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $event_stmt = $pdo->prepare("
            INSERT INTO gt_events (
                session_id, ip, event_type, event_result, page_url,
                member_id, member_name, meta_json, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $event_stmt->execute([
            $sid,
            $ip,
            'blacklist_hit',
            'blocked_log_only',
            $page_url,
            $member_id,
            $member_name,
            $meta ? $meta : '{}'
        ]);
    } catch (Throwable $e) {
    }
}

/* -------------------------------------------------
   JS beacon
------------------------------------------------- */

echo '<script>
(function(){
    var vid = ' . (int)$visit_id . ';
    var started = Date.now();
    if (!vid) return;

    document.addEventListener("click", function(e){
        var a = e.target.closest ? e.target.closest("a") : null;
        if (!a || !a.href) return;

        try {
            var url = new URL(a.href, location.href);
            if (url.hostname !== location.hostname && /^https?:$/i.test(url.protocol)) {
                if (navigator.sendBeacon) {
                    navigator.sendBeacon("/tracker_update.php", JSON.stringify({
                        vid: vid,
                        exit_url: url.href.substring(0, 500)
                    }));
                }
            }
        } catch(err){}
    }, true);

    function sendTime(){
        var sec = Math.round((Date.now() - started) / 1000);
        if (sec < 0) sec = 0;
        if (navigator.sendBeacon) {
            navigator.sendBeacon("/tracker_update.php", JSON.stringify({
                vid: vid,
                time_on_page: sec
            }));
        }
    }

    document.addEventListener("visibilitychange", function(){
        if (document.hidden) sendTime();
    });

    window.addEventListener("pagehide", function(){
        sendTime();
    });

    window.addEventListener("load", function(){
        try {
            var lt = 0;
            if (window.performance && performance.timing) {
                lt = (performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart) / 1000;
            }
            if (lt > 0 && lt < 60 && navigator.sendBeacon) {
                navigator.sendBeacon("/tracker_update.php", JSON.stringify({
                    vid: vid,
                    load_time: parseFloat(lt.toFixed(2))
                }));
            }
        } catch(err){}
    });
})();
</script>';