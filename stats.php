<?php
require_once __DIR__ . '/gt_config.php';
session_start();

/* ================= LOGIN ================= */

if (isset($_POST['pass'])) {
    if ($_POST['pass'] === GT_STATS_PASS) {
        $_SESSION['gt_auth'] = true;
    } else {
        $login_error = 'Şifre hatalı.';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: stats.php");
    exit;
}

$authed = $_SESSION['gt_auth'] ?? false;

/* ================= DB ================= */

if ($authed) {
    $pdo = new PDO(
        "mysql:host=".GT_DB_HOST.";dbname=".GT_DB_NAME.";charset=utf8mb4",
        GT_DB_USER, GT_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

/* ================= HELPERS ================= */

function fmt($n){ return number_format($n,0,',','.'); }

/* ================= DATA ================= */

if ($authed) {

$tab = $_GET['tab'] ?? 'dashboard';

/* DASHBOARD */
$today_total = $pdo->query("SELECT COUNT(*) FROM gt_visits WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$today_404   = $pdo->query("SELECT COUNT(*) FROM gt_visits WHERE is_404=1 AND DATE(created_at)=CURDATE()")->fetchColumn();
$today_403   = $pdo->query("SELECT COUNT(*) FROM gt_visits WHERE is_403=1 AND DATE(created_at)=CURDATE()")->fetchColumn();
$today_500   = $pdo->query("SELECT COUNT(*) FROM gt_visits WHERE is_500=1 AND DATE(created_at)=CURDATE()")->fetchColumn();
$risky_ips   = $pdo->query("SELECT COUNT(DISTINCT ip) FROM gt_visits WHERE risk_score>=50 AND DATE(created_at)=CURDATE()")->fetchColumn();
$login_fail  = $pdo->query("SELECT COUNT(*) FROM gt_events WHERE event_type='login_failed' AND DATE(created_at)=CURDATE()")->fetchColumn();

/* TIMELINE */
if ($tab == 'timeline' && !empty($_GET['ip'])) {
    $ip = $_GET['ip'];

    $timeline = $pdo->prepare("SELECT * FROM gt_visits WHERE ip=? ORDER BY created_at DESC LIMIT 200");
    $timeline->execute([$ip]);

    $ip_stats = $pdo->prepare("
        SELECT 
        COUNT(*) total,
        SUM(is_404) c404,
        SUM(is_403) c403,
        SUM(is_500) c500,
        MAX(risk_score) max_risk
        FROM gt_visits WHERE ip=?
    ");
    $ip_stats->execute([$ip]);
    $ip_stats = $ip_stats->fetch(PDO::FETCH_ASSOC);
}

/* EVENTS */
if ($tab == 'events') {
    $events = $pdo->query("SELECT * FROM gt_events ORDER BY created_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
}

/* SECURITY */
if ($tab == 'security') {
    $risky = $pdo->query("
        SELECT ip, MAX(risk_score) risk, COUNT(*) hits
        FROM gt_visits
        WHERE created_at >= NOW() - INTERVAL 1 DAY
        GROUP BY ip
        ORDER BY risk DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/* IP LIST */
if ($tab == 'iplist') {
    $list = $pdo->query("SELECT * FROM gt_ip_lists ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GT Analytics</title>
<style>
body{background:#0d1421;color:#fff;font-family:sans-serif}
.card{background:#141e30;padding:15px;margin:10px;border-radius:10px}
.badge{padding:4px 8px;border-radius:5px;font-size:12px}
.red{background:#e8334a}
.green{background:#22c55e}
.yellow{background:#facc15}
</style>
</head>
<body>

<?php if(!$authed): ?>
<form method="POST">
<input type="password" name="pass">
<button>Giriş</button>
</form>
<?php exit; endif; ?>

<h2>GT Analytics</h2>

<a href="?tab=dashboard">Dashboard</a> |
<a href="?tab=timeline">Timeline</a> |
<a href="?tab=security">Security</a> |
<a href="?tab=events">Events</a> |
<a href="?tab=iplist">IP Lists</a> |
<a href="?logout=1">Çıkış</a>

<hr>

<?php if($tab=='dashboard'): ?>

<div class="card">Toplam: <?=fmt($today_total)?></div>
<div class="card">404: <?=fmt($today_404)?></div>
<div class="card">403: <?=fmt($today_403)?></div>
<div class="card">500: <?=fmt($today_500)?></div>
<div class="card">Riskli IP: <?=fmt($risky_ips)?></div>
<div class="card">Login Fail: <?=fmt($login_fail)?></div>

<?php endif; ?>


<?php if($tab=='timeline'): ?>

<form>
<input name="ip" placeholder="IP">
<button>Ara</button>
</form>

<?php if(!empty($timeline)): ?>

<div class="card">
Toplam: <?=$ip_stats['total']?> |
404: <?=$ip_stats['c404']?> |
403: <?=$ip_stats['c403']?> |
500: <?=$ip_stats['c500']?> |
Risk: <?=$ip_stats['max_risk']?>
</div>

<?php foreach($timeline as $t): ?>

<div class="card">
<?= $t['created_at'] ?> |
<?= $t['page_url'] ?> |
Status: <?= $t['http_status'] ?> |
Method: <?= $t['request_method'] ?>

<?php
$r = $t['risk_score'];
$class = $r>=80?'red':($r>=50?'yellow':'green');
?>

<span class="badge <?=$class?>">Risk: <?=$r?></span>
<br>
<?=$t['risk_flags']?>
</div>

<?php endforeach; ?>

<?php endif; ?>

<?php endif; ?>


<?php if($tab=='events'): ?>

<?php foreach($events as $e): ?>
<div class="card">
<?=$e['created_at']?> |
<?=$e['event_type']?> |
<?=$e['event_result']?> |
<?=$e['ip']?>
</div>
<?php endforeach; ?>

<?php endif; ?>


<?php if($tab=='security'): ?>

<?php foreach($risky as $r): ?>
<div class="card">
<?=$r['ip']?> |
Risk: <?=$r['risk']?> |
Hit: <?=$r['hits']?>
</div>
<?php endforeach; ?>

<?php endif; ?>


<?php if($tab=='iplist'): ?>

<?php foreach($list as $l): ?>
<div class="card">
<?=$l['ip']?> |
<?=$l['list_type']?> |
<?=$l['note']?>
</div>
<?php endforeach; ?>

<?php endif; ?>


</body>
</html>