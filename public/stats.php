<?php
require_once __DIR__ . '/gt_config.php';
session_start();

/* -------------------------------------------------
   Login
------------------------------------------------- */

if (isset($_POST['pass'])) {
    if ($_POST['pass'] === GT_STATS_PASS) {
        $_SESSION['gt_auth'] = true;
        header('Location: stats.php');
        exit;
    } else {
        $login_error = 'Sifre hatali.';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: stats.php');
    exit;
}

$authed = $_SESSION['gt_auth'] ?? false;

/* -------------------------------------------------
   DB
------------------------------------------------- */

$pdo = null;

if ($authed) {
    try {
        $pdo = new PDO(
            "mysql:host=" . GT_DB_HOST . ";dbname=" . GT_DB_NAME . ";charset=utf8mb4",
            GT_DB_USER,
            GT_DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (Throwable $e) {
        die('<div style="padding:40px;font-family:Arial;color:#fff;background:#0b1220">Veritabani baglanti hatasi: ' . htmlspecialchars($e->getMessage()) . '</div>');
    }
}

/* -------------------------------------------------
   Helpers
------------------------------------------------- */

function gt_num($n)
{
    return number_format((float)$n, 0, ',', '.');
}

function gt_sec($sec)
{
    $sec = (int)$sec;
    if ($sec < 60) return $sec . ' sn';
    return floor($sec / 60) . ' dk ' . ($sec % 60) . ' sn';
}

function gt_risk_class($score)
{
    $score = (int)$score;
    if ($score >= 80) return 'risk-high';
    if ($score >= 50) return 'risk-mid';
    if ($score > 0) return 'risk-low';
    return 'risk-zero';
}

function gt_h($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/* -------------------------------------------------
   Data
------------------------------------------------- */

$tab = $_GET['tab'] ?? 'dashboard';

if ($authed) {
    $today_total = (int)$pdo->query("SELECT COUNT(*) FROM gt_visits WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $today_404   = (int)$pdo->query("SELECT COUNT(*) FROM gt_visits WHERE is_404=1 AND DATE(created_at)=CURDATE()")->fetchColumn();
    $today_403   = (int)$pdo->query("SELECT COUNT(*) FROM gt_visits WHERE is_403=1 AND DATE(created_at)=CURDATE()")->fetchColumn();
    $today_500   = (int)$pdo->query("SELECT COUNT(*) FROM gt_visits WHERE is_500=1 AND DATE(created_at)=CURDATE()")->fetchColumn();
    $risky_ips   = (int)$pdo->query("SELECT COUNT(DISTINCT ip) FROM gt_visits WHERE risk_score>=50 AND DATE(created_at)=CURDATE()")->fetchColumn();

    try {
        $login_fail = (int)$pdo->query("SELECT COUNT(*) FROM gt_events WHERE event_type='login_failed' AND DATE(created_at)=CURDATE()")->fetchColumn();
    } catch (Throwable $e) {
        $login_fail = 0;
    }

    $online_count = 0;
    try {
        $online_count = (int)$pdo->query("SELECT COUNT(*) FROM gt_online WHERE last_seen > NOW() - INTERVAL " . (int)GT_ONLINE_TIMEOUT . " MINUTE")->fetchColumn();
    } catch (Throwable $e) {
        $online_count = 0;
    }

    $top_pages = $pdo->query("
        SELECT page_url, COUNT(*) c, COUNT(DISTINCT ip) u
        FROM gt_visits
        WHERE created_at >= NOW() - INTERVAL 7 DAY
        GROUP BY page_url
        ORDER BY c DESC
        LIMIT 10
    ")->fetchAll();

    $top_ips = $pdo->query("
        SELECT ip, MAX(risk_score) max_risk, COUNT(*) hits, MAX(created_at) last_seen
        FROM gt_visits
        GROUP BY ip
        ORDER BY max_risk DESC, hits DESC
        LIMIT 10
    ")->fetchAll();

    $top_countries = $pdo->query("
        SELECT flag, country, COUNT(DISTINCT ip) u
        FROM gt_visits
        WHERE country <> ''
        GROUP BY country, flag
        ORDER BY u DESC
        LIMIT 8
    ")->fetchAll();

    $hourly_rows = $pdo->query("
        SELECT HOUR(created_at) h, COUNT(*) c
        FROM gt_visits
        WHERE created_at >= NOW() - INTERVAL 24 HOUR
        GROUP BY HOUR(created_at)
        ORDER BY HOUR(created_at)
    ")->fetchAll();

    $hourly = array_fill(0, 24, 0);
    foreach ($hourly_rows as $r) {
        $hourly[(int)$r['h']] = (int)$r['c'];
    }

    $timeline_ip = trim($_GET['ip'] ?? '');
    $timeline_rows = [];
    $timeline_info = null;
    $timeline_stats = null;

    if ($tab === 'timeline' && $timeline_ip !== '') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM gt_visits
            WHERE ip = ?
            ORDER BY created_at DESC
            LIMIT 200
        ");
        $stmt->execute([$timeline_ip]);
        $timeline_rows = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT
                ip,
                MAX(flag) flag,
                MAX(country) country,
                MAX(city) city,
                MAX(isp) isp,
                MAX(device) device,
                MAX(browser) browser,
                MAX(os) os
            FROM gt_visits
            WHERE ip = ?
            GROUP BY ip
            LIMIT 1
        ");
        $stmt->execute([$timeline_ip]);
        $timeline_info = $stmt->fetch();

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) total,
                SUM(is_404) c404,
                SUM(is_403) c403,
                SUM(is_500) c500,
                MAX(risk_score) max_risk,
                AVG(risk_score) avg_risk,
                MAX(created_at) last_seen
            FROM gt_visits
            WHERE ip = ?
        ");
        $stmt->execute([$timeline_ip]);
        $timeline_stats = $stmt->fetch();
    }

    $not_found_rows = [];
    if ($tab === '404') {
        $not_found_rows = $pdo->query("
            SELECT page_url, COUNT(*) c, COUNT(DISTINCT ip) u, MAX(created_at) last_seen
            FROM gt_visits
            WHERE is_404 = 1
            GROUP BY page_url
            ORDER BY c DESC
            LIMIT 50
        ")->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GT Stats</title>
<style>
:root{
    --bg:#0b1220;
    --panel:#111a2e;
    --panel2:#17233d;
    --line:#243250;
    --text:#f3f6ff;
    --muted:#94a3b8;
    --red:#ef4444;
    --orange:#f59e0b;
    --green:#22c55e;
    --blue:#3b82f6;
    --purple:#8b5cf6;
}
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font-family:Arial,sans-serif}
a{text-decoration:none;color:inherit}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.login-box{width:100%;max-width:380px;background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:30px}
.login-box h1{margin:0 0 8px;font-size:26px}
.login-box p{margin:0 0 20px;color:var(--muted)}
.login-box input{width:100%;padding:14px 16px;border-radius:12px;border:1px solid var(--line);background:#0e1628;color:#fff}
.login-box button{width:100%;margin-top:12px;padding:14px;border:0;border-radius:12px;background:var(--red);color:#fff;font-weight:700;cursor:pointer}
.err{margin-top:12px;color:#fca5a5;font-size:14px}

.app{display:flex;min-height:100vh}
.sidebar{width:240px;background:var(--panel);border-right:1px solid var(--line);padding:22px 16px;position:fixed;left:0;top:0;bottom:0}
.brand{font-size:12px;color:var(--muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:18px}
.brand strong{display:block;font-size:18px;color:#fff;letter-spacing:0;margin-bottom:4px}
.nav a{display:block;padding:12px 14px;border-radius:12px;color:var(--muted);margin-bottom:6px}
.nav a.active,.nav a:hover{background:var(--panel2);color:#fff}
.sidebar-foot{position:absolute;left:16px;right:16px;bottom:20px;color:var(--muted);font-size:13px}
.main{margin-left:240px;flex:1;padding:24px}

.hero{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}
.hero-left h2{margin:0;font-size:28px}
.hero-left p{margin:6px 0 0;color:var(--muted)}
.hero-right{display:flex;gap:12px;flex-wrap:wrap}
.pill{padding:10px 14px;border:1px solid var(--line);border-radius:999px;background:var(--panel);color:#fff;font-size:13px}

.cards{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:18px}
.metric{font-size:30px;font-weight:700;margin-bottom:6px}
.label{font-size:13px;color:var(--muted)}
.metric.red{color:var(--red)}
.metric.orange{color:var(--orange)}
.metric.green{color:var(--green)}
.metric.blue{color:var(--blue)}
.metric.purple{color:var(--purple)}

.grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}
.panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:18px}
.panel h3{margin:0 0 14px;font-size:16px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px 8px;border-bottom:1px solid var(--line);font-size:14px;text-align:left;vertical-align:top}
.table th{color:var(--muted);font-weight:600}
.url{font-family:Consolas,monospace;font-size:13px;word-break:break-all}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700}
.risk-zero{background:#243042;color:#cbd5e1}
.risk-low{background:rgba(34,197,94,.15);color:#86efac}
.risk-mid{background:rgba(245,158,11,.15);color:#fcd34d}
.risk-high{background:rgba(239,68,68,.15);color:#fca5a5}
.status-404{color:#fca5a5}
.timeline-form{display:flex;gap:10px;margin-bottom:18px}
.timeline-form input{flex:1;padding:13px 14px;border-radius:12px;border:1px solid var(--line);background:#0e1628;color:#fff}
.timeline-form button{padding:13px 18px;border:0;border-radius:12px;background:var(--blue);color:#fff;font-weight:700;cursor:pointer}
.timeline-head{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:18px}
.timeline-item{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:12px}
.timeline-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}
.timeline-date{font-size:13px;color:var(--muted)}
.timeline-url{font-family:Consolas,monospace;font-size:13px;word-break:break-all;margin-bottom:8px}
.meta{display:flex;gap:8px;flex-wrap:wrap}
.meta span{font-size:12px;padding:5px 8px;border-radius:999px;background:var(--panel2);color:#cbd5e1}
.empty{padding:24px;border:1px dashed var(--line);border-radius:16px;color:var(--muted);text-align:center}

.chart{display:flex;align-items:flex-end;gap:5px;height:180px}
.bar{flex:1;background:linear-gradient(180deg,#8b5cf6,#3b82f6);border-radius:8px 8px 0 0;min-width:8px;position:relative}
.bar span{position:absolute;bottom:-22px;left:50%;transform:translateX(-50%);font-size:10px;color:var(--muted)}

@media (max-width:1200px){
    .cards{grid-template-columns:repeat(3,1fr)}
}
@media (max-width:900px){
    .sidebar{position:static;width:100%;height:auto}
    .app{display:block}
    .main{margin-left:0}
    .grid{grid-template-columns:1fr}
    .cards{grid-template-columns:repeat(2,1fr)}
    .timeline-head{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:600px){
    .cards{grid-template-columns:1fr}
    .timeline-head{grid-template-columns:1fr}
    .hero{display:block}
    .hero-right{margin-top:12px}
}
</style>
</head>
<body>

<?php if (!$authed): ?>
<div class="login-wrap">
    <div class="login-box">
        <h1>GT Stats</h1>
        <p>Yonetim paneline giris yapin.</p>
        <form method="POST">
            <input type="password" name="pass" placeholder="Sifre" autocomplete="current-password">
            <button type="submit">Giris Yap</button>
            <?php if (!empty($login_error)): ?>
                <div class="err"><?= gt_h($login_error) ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php else: ?>

<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <strong>GT Analytics</strong>
            GrupTalepleri
        </div>

        <div class="nav">
            <a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="?tab=timeline" class="<?= $tab === 'timeline' ? 'active' : '' ?>">IP Timeline</a>
            <a href="?tab=404" class="<?= $tab === '404' ? 'active' : '' ?>">404 Raporu</a>
        </div>

        <div class="sidebar-foot">
            <a href="?logout=1">Cikis Yap</a>
        </div>
    </aside>

    <main class="main">

        <div class="hero">
            <div class="hero-left">
                <h2><?= $tab === 'dashboard' ? 'Dashboard' : ($tab === 'timeline' ? 'IP Timeline' : '404 Raporu') ?></h2>
                <p>Stabil, sade, sonuc odakli istatistik paneli.</p>
            </div>
            <div class="hero-right">
                <div class="pill">Online: <?= gt_num($online_count) ?></div>
                <div class="pill">Riskli IP: <?= gt_num($risky_ips) ?></div>
            </div>
        </div>

        <?php if ($tab === 'dashboard'): ?>

            <div class="cards">
                <div class="card">
                    <div class="metric"><?= gt_num($today_total) ?></div>
                    <div class="label">Bugun Toplam</div>
                </div>
                <div class="card">
                    <div class="metric red"><?= gt_num($today_404) ?></div>
                    <div class="label">404</div>
                </div>
                <div class="card">
                    <div class="metric orange"><?= gt_num($today_403) ?></div>
                    <div class="label">403</div>
                </div>
                <div class="card">
                    <div class="metric red"><?= gt_num($today_500) ?></div>
                    <div class="label">500</div>
                </div>
                <div class="card">
                    <div class="metric purple"><?= gt_num($risky_ips) ?></div>
                    <div class="label">Riskli IP</div>
                </div>
                <div class="card">
                    <div class="metric blue"><?= gt_num($login_fail) ?></div>
                    <div class="label">Login Fail</div>
                </div>
            </div>

            <div class="grid">
                <div class="panel">
                    <h3>Son 24 Saat Trafik</h3>
                    <div class="chart">
                        <?php
                        $maxHour = max($hourly ?: [1]);
                        foreach ($hourly as $i => $c):
                            $height = $maxHour > 0 ? max(8, round(($c / $maxHour) * 160)) : 8;
                        ?>
                            <div class="bar" style="height:<?= $height ?>px" title="<?= $i ?>:00 - <?= $c ?> hit">
                                <span><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel">
                    <h3>En Riskli IP'ler</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Risk</th>
                                <th>Hit</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_ips as $row): ?>
                            <tr>
                                <td><a href="?tab=timeline&ip=<?= urlencode($row['ip']) ?>"><?= gt_h($row['ip']) ?></a></td>
                                <td><span class="badge <?= gt_risk_class($row['max_risk']) ?>"><?= (int)$row['max_risk'] ?></span></td>
                                <td><?= gt_num($row['hits']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid" style="margin-top:18px">
                <div class="panel">
                    <h3>En Cok Gezilen Sayfalar</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sayfa</th>
                                <th>Hit</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_pages as $p): ?>
                            <tr>
                                <td class="url"><?= gt_h($p['page_url']) ?></td>
                                <td><?= gt_num($p['c']) ?></td>
                                <td><?= gt_num($p['u']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel">
                    <h3>Ulkeler</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ulke</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_countries as $c): ?>
                            <tr>
                                <td><?= gt_h(($c['flag'] ? $c['flag'] . ' ' : '') . $c['country']) ?></td>
                                <td><?= gt_num($c['u']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($tab === 'timeline'): ?>

            <form method="GET" class="timeline-form">
                <input type="hidden" name="tab" value="timeline">
                <input type="text" name="ip" value="<?= gt_h($timeline_ip) ?>" placeholder="IP adresi girin">
                <button type="submit">Sorgula</button>
            </form>

            <?php if ($timeline_ip !== '' && $timeline_info): ?>
                <div class="timeline-head">
                    <div class="card">
                        <div class="metric"><?= gt_num($timeline_stats['total'] ?? 0) ?></div>
                        <div class="label">Toplam Kayit</div>
                    </div>
                    <div class="card">
                        <div class="metric red"><?= gt_num($timeline_stats['c404'] ?? 0) ?></div>
                        <div class="label">404</div>
                    </div>
                    <div class="card">
                        <div class="metric orange"><?= gt_num($timeline_stats['c403'] ?? 0) ?></div>
                        <div class="label">403</div>
                    </div>
                    <div class="card">
                        <div class="metric red"><?= gt_num($timeline_stats['c500'] ?? 0) ?></div>
                        <div class="label">500</div>
                    </div>
                    <div class="card">
                        <div class="metric purple"><?= (int)($timeline_stats['max_risk'] ?? 0) ?></div>
                        <div class="label">Max Risk</div>
                    </div>
                    <div class="card">
                        <div class="metric blue"><?= round((float)($timeline_stats['avg_risk'] ?? 0), 1) ?></div>
                        <div class="label">Ort. Risk</div>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:18px">
                    <h3>IP Ozeti</h3>
                    <div class="meta">
                        <span>IP: <?= gt_h($timeline_info['ip']) ?></span>
                        <span><?= gt_h(($timeline_info['flag'] ? $timeline_info['flag'] . ' ' : '') . ($timeline_info['country'] ?? '')) ?></span>
                        <span>Sehir: <?= gt_h($timeline_info['city'] ?? '') ?></span>
                        <span>ISP: <?= gt_h($timeline_info['isp'] ?? '') ?></span>
                        <span>Cihaz: <?= gt_h($timeline_info['device'] ?? '') ?></span>
                        <span>Tarayici: <?= gt_h($timeline_info['browser'] ?? '') ?></span>
                        <span>OS: <?= gt_h($timeline_info['os'] ?? '') ?></span>
                        <span>Son Gorulme: <?= gt_h($timeline_stats['last_seen'] ?? '') ?></span>
                    </div>
                </div>

                <?php if ($timeline_rows): ?>
                    <?php foreach ($timeline_rows as $r): ?>
                        <div class="timeline-item">
                            <div class="timeline-top">
                                <div class="timeline-date"><?= gt_h($r['created_at']) ?></div>
                                <div>
                                    <span class="badge <?= gt_risk_class($r['risk_score']) ?>">Risk: <?= (int)$r['risk_score'] ?></span>
                                </div>
                            </div>

                            <div class="timeline-url <?= (int)$r['is_404'] === 1 ? 'status-404' : '' ?>">
                                <?= gt_h($r['page_url']) ?>
                            </div>

                            <div class="meta">
                                <span>Status: <?= (int)$r['http_status'] ?></span>
                                <span>Method: <?= gt_h($r['request_method']) ?></span>
                                <span><?= gt_h($r['device']) ?></span>
                                <span><?= gt_h($r['browser']) ?> / <?= gt_h($r['os']) ?></span>
                                <?php if (!empty($r['risk_flags'])): ?>
                                    <span>Flags: <?= gt_h($r['risk_flags']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($r['referrer_domain'])): ?>
                                    <span>Referrer: <?= gt_h($r['referrer_domain']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($r['search_keyword'])): ?>
                                    <span>Arama: <?= gt_h($r['search_keyword']) ?></span>
                                <?php endif; ?>
                                <?php if ((int)$r['time_on_page'] > 0): ?>
                                    <span>Sure: <?= gt_sec($r['time_on_page']) ?></span>
                                <?php endif; ?>
                                <?php if ((float)$r['load_time'] > 0): ?>
                                    <span>Yukleme: <?= gt_h($r['load_time']) ?> sn</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Kayit bulunamadi.</div>
                <?php endif; ?>

            <?php elseif ($timeline_ip !== ''): ?>
                <div class="empty">Bu IP icin kayit bulunamadi.</div>
            <?php else: ?>
                <div class="empty">Bir IP yazip sorgu yapin.</div>
            <?php endif; ?>

        <?php elseif ($tab === '404'): ?>

            <div class="panel">
                <h3>404 Sayfalari</h3>
                <?php if ($not_found_rows): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sayfa</th>
                                <th>Hit</th>
                                <th>IP</th>
                                <th>Son</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($not_found_rows as $r): ?>
                            <tr>
                                <td class="url status-404"><?= gt_h($r['page_url']) ?></td>
                                <td><?= gt_num($r['c']) ?></td>
                                <td><?= gt_num($r['u']) ?></td>
                                <td><?= gt_h($r['last_seen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty">404 kaydi yok.</div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </main>
</div>

<?php endif; ?>
</body>
</html>