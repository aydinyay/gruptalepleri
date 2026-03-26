<?php
/**
 * cron_mail.php — Günlük Rapor Maili
 *
 * cPanel → Cron Jobs'a şunu ekleyin (her gün saat 08:00'de):
 * 0 8 * * * php /home/CPANEL_KULLANICI/public_html/cron_mail.php
 */

require_once __DIR__ . '/gt_config.php';

try {
    $pdo = new PDO(
        "mysql:host=".GT_DB_HOST.";dbname=".GT_DB_NAME.";charset=utf8mb4",
        GT_DB_USER, GT_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    // Dün istatistikleri
    $yesterday = date('Y-m-d', strtotime('yesterday'));
    $unique_ip = $pdo->prepare("SELECT COUNT(DISTINCT ip) FROM gt_visits WHERE DATE(created_at)=? AND is_404=0");
    $unique_ip->execute([$yesterday]); $unique_ip = $unique_ip->fetchColumn();

    $total_vis = $pdo->prepare("SELECT COUNT(*) FROM gt_visits WHERE DATE(created_at)=? AND is_404=0");
    $total_vis->execute([$yesterday]); $total_vis = $total_vis->fetchColumn();

    $avg_time = $pdo->prepare("SELECT AVG(time_on_page) FROM gt_visits WHERE DATE(created_at)=? AND time_on_page>0");
    $avg_time->execute([$yesterday]); $avg_time = (int)$avg_time->fetchColumn();
    $avg_fmt = $avg_time < 60 ? $avg_time.'sn' : floor($avg_time/60).'dk '.($avg_time%60).'sn';

    $top_pages = $pdo->prepare("SELECT page_url, COUNT(*) as c FROM gt_visits WHERE DATE(created_at)=? AND is_404=0 GROUP BY page_url ORDER BY c DESC LIMIT 5");
    $top_pages->execute([$yesterday]); $top_pages = $top_pages->fetchAll();

    $top_countries = $pdo->prepare("SELECT flag, country, COUNT(DISTINCT ip) as u FROM gt_visits WHERE DATE(created_at)=? AND country!='' GROUP BY country ORDER BY u DESC LIMIT 5");
    $top_countries->execute([$yesterday]); $top_countries = $top_countries->fetchAll();

    $top_sources = $pdo->prepare("SELECT CASE WHEN referrer_domain='' THEN 'Direkt Giriş' ELSE referrer_domain END as src, COUNT(*) as c FROM gt_visits WHERE DATE(created_at)=? GROUP BY src ORDER BY c DESC LIMIT 5");
    $top_sources->execute([$yesterday]); $top_sources = $top_sources->fetchAll();

    $device_stats = $pdo->prepare("SELECT device, COUNT(*) as c FROM gt_visits WHERE DATE(created_at)=? GROUP BY device ORDER BY c DESC");
    $device_stats->execute([$yesterday]); $device_stats = $device_stats->fetchAll();

    $not_found_count = $pdo->prepare("SELECT COUNT(*) FROM gt_visits WHERE DATE(created_at)=? AND is_404=1");
    $not_found_count->execute([$yesterday]); $not_found_count = $not_found_count->fetchColumn();

    // HTML Mail
    $date_tr = date('d.m.Y', strtotime($yesterday));
    $rows_pages = '';
    foreach ($top_pages as $p) {
        $rows_pages .= "<tr><td style='padding:8px 12px;border-bottom:1px solid #1e2d48;font-family:monospace;font-size:12px;color:#aac'>" . htmlspecialchars($p['page_url']) . "</td><td style='padding:8px 12px;border-bottom:1px solid #1e2d48;text-align:right;font-weight:700;color:#e8334a'>" . number_format($p['c']) . "</td></tr>";
    }
    $rows_sources = '';
    foreach ($top_sources as $s) {
        $rows_sources .= "<tr><td style='padding:8px 12px;border-bottom:1px solid #1e2d48;color:#aac'>" . htmlspecialchars($s['src']) . "</td><td style='padding:8px 12px;border-bottom:1px solid #1e2d48;text-align:right;font-weight:700'>" . number_format($s['c']) . "</td></tr>";
    }
    $rows_countries = '';
    foreach ($top_countries as $c) {
        $rows_countries .= "<tr><td style='padding:8px 12px;border-bottom:1px solid #1e2d48;color:#aac'>{$c['flag']} " . htmlspecialchars($c['country']) . "</td><td style='padding:8px 12px;border-bottom:1px solid #1e2d48;text-align:right;font-weight:700'>" . number_format($c['u']) . "</td></tr>";
    }
    $devices_str = implode(' &nbsp;·&nbsp; ', array_map(fn($d) => ($d['device']==='Mobile'?'📱':'🖥️').' '.$d['device'].': '.$d['c'], $device_stats));

    $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0d1421;font-family:'Segoe UI',sans-serif">
<div style="max-width:600px;margin:0 auto;padding:32px 16px">

  <!-- HEADER -->
  <div style="text-align:center;margin-bottom:32px">
    <div style="font-size:13px;letter-spacing:3px;color:#6b7fa3;text-transform:uppercase;margin-bottom:8px">✈ GrupTalepleri</div>
    <h1 style="color:#fff;font-size:22px;margin:0">Günlük Ziyaretçi Raporu</h1>
    <p style="color:#6b7fa3;margin:8px 0 0;font-size:14px">{$date_tr}</p>
  </div>

  <!-- ANA RAKAMLAR -->
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
    <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;padding:20px;text-align:center">
      <div style="font-size:36px;font-weight:800;color:#e8334a;font-family:monospace">{$unique_ip}</div>
      <div style="font-size:12px;color:#6b7fa3;margin-top:4px">Benzersiz IP</div>
    </div>
    <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;padding:20px;text-align:center">
      <div style="font-size:36px;font-weight:800;color:#fff;font-family:monospace">{$total_vis}</div>
      <div style="font-size:12px;color:#6b7fa3;margin-top:4px">Toplam Ziyaret</div>
    </div>
    <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;padding:20px;text-align:center">
      <div style="font-size:36px;font-weight:800;color:#22c55e;font-family:monospace">{$avg_fmt}</div>
      <div style="font-size:12px;color:#6b7fa3;margin-top:4px">Ort. Süre</div>
    </div>
  </div>

  <!-- SAYFALAR -->
  <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;overflow:hidden;margin-bottom:16px">
    <div style="padding:14px 16px;border-bottom:1px solid #1e2d48;font-size:11px;letter-spacing:2px;color:#6b7fa3;text-transform:uppercase">En Çok Ziyaret Edilen Sayfalar</div>
    <table style="width:100%;border-collapse:collapse;color:#fff">
      {$rows_pages}
    </table>
  </div>

  <!-- KAYNAKLAR + ÜLKELER -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;overflow:hidden">
      <div style="padding:14px 16px;border-bottom:1px solid #1e2d48;font-size:11px;letter-spacing:2px;color:#6b7fa3;text-transform:uppercase">Kaynaklar</div>
      <table style="width:100%;border-collapse:collapse;color:#fff">{$rows_sources}</table>
    </div>
    <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;overflow:hidden">
      <div style="padding:14px 16px;border-bottom:1px solid #1e2d48;font-size:11px;letter-spacing:2px;color:#6b7fa3;text-transform:uppercase">Ülkeler</div>
      <table style="width:100%;border-collapse:collapse;color:#fff">{$rows_countries}</table>
    </div>
  </div>

  <!-- CİHAZ + 404 -->
  <div style="background:#141e30;border:1px solid #1e2d48;border-radius:12px;padding:16px;margin-bottom:24px;color:#aac;font-size:13px">
    <strong style="color:#fff">Cihazlar:</strong> {$devices_str}
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong style="color:#e8334a">404 Hataları:</strong> {$not_found_count}
  </div>

  <!-- FOOTER -->
  <div style="text-align:center;color:#6b7fa3;font-size:12px">
    <a href="https://gruptalepleri.com/stats.php" style="color:#e8334a;text-decoration:none;font-weight:700">📊 Tam Raporu Görüntüle →</a><br><br>
    Bu mail otomatik olarak gönderilmiştir · GrupTalepleri Analytics
  </div>

</div>
</body>
</html>
HTML;

    // Mail gönder
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GrupTalepleri Analytics <noreply@gruptalepleri.com>\r\n";

    $subject = "📊 Günlük Rapor — {$date_tr} | {$unique_ip} ziyaretçi";
    mail(GT_ADMIN_MAIL, $subject, $html, $headers);

    echo "Mail gönderildi: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
