<?php
/**
 * setup.php — Tabloları Oluştur
 *
 * KULLANIM: Sadece 1 kez çalıştırın, sonra sunucudan silin!
 * Tarayıcıdan: https://gruptalepleri.com/setup.php
 */

require_once __DIR__ . '/gt_config.php';

try {
    $pdo = new PDO(
        "mysql:host=".GT_DB_HOST.";dbname=".GT_DB_NAME.";charset=utf8mb4",
        GT_DB_USER, GT_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Ana ziyaret tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS gt_visits (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id       VARCHAR(64)  NOT NULL,
        ip               VARCHAR(45)  DEFAULT '',
        country          VARCHAR(100) DEFAULT '',
        city             VARCHAR(100) DEFAULT '',
        flag             VARCHAR(10)  DEFAULT '',
        isp              VARCHAR(200) DEFAULT '',
        device           VARCHAR(20)  DEFAULT 'Desktop',
        browser          VARCHAR(50)  DEFAULT '',
        os               VARCHAR(50)  DEFAULT '',
        page_url         VARCHAR(500) DEFAULT '',
        referrer         VARCHAR(500) DEFAULT '',
        referrer_domain  VARCHAR(200) DEFAULT '',
        search_keyword   VARCHAR(300) DEFAULT '',
        exit_url         VARCHAR(500) DEFAULT '',
        time_on_page     INT UNSIGNED DEFAULT 0,
        load_time        FLOAT        DEFAULT 0,
        is_returning     TINYINT(1)   DEFAULT 0,
        is_404           TINYINT(1)   DEFAULT 0,
        member_id        VARCHAR(100) DEFAULT '',
        member_name      VARCHAR(200) DEFAULT '',
        created_at       DATETIME     NOT NULL,
        INDEX idx_session  (session_id),
        INDEX idx_ip       (ip),
        INDEX idx_created  (created_at),
        INDEX idx_page     (page_url(100)),
        INDEX idx_404      (is_404)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Şu an online tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS gt_online (
        session_id VARCHAR(64) PRIMARY KEY,
        ip         VARCHAR(45)  DEFAULT '',
        country    VARCHAR(100) DEFAULT '',
        flag       VARCHAR(10)  DEFAULT '',
        page_url   VARCHAR(500) DEFAULT '',
        last_seen  DATETIME     NOT NULL,
        INDEX idx_last_seen (last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Ayarlar tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS gt_settings (
        setting_key   VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Varsayılan ayarlar
    $defaults = [
        'member_session_id'   => '',
        'member_session_name' => '',
        'kvkk_enabled'        => '1',
        'mail_enabled'        => '1',
        'mail_hour'           => '8',
    ];
    $ins = $pdo->prepare("INSERT IGNORE INTO gt_settings (setting_key, setting_value) VALUES (?,?)");
    foreach ($defaults as $k => $v) $ins->execute([$k, $v]);

    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">
    <title>GT Kurulum</title>
    <style>
      body{font-family:sans-serif;background:#0d1421;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
      .box{background:#1a2540;border-radius:12px;padding:40px;text-align:center;max-width:500px;}
      h2{color:#e8334a;margin:0 0 16px;}
      p{color:#8899aa;line-height:1.6;}
      .ok{font-size:3rem;margin-bottom:16px;}
      .warn{background:#e8334a22;border:1px solid #e8334a66;border-radius:8px;padding:16px;margin-top:20px;color:#ff6b7a;font-size:14px;}
    </style></head><body>
    <div class="box">
      <div class="ok">✅</div>
      <h2>Kurulum Tamamlandı!</h2>
      <p>Veritabanı tabloları başarıyla oluşturuldu.</p>
      <div class="warn">
        ⚠️ <strong>ÖNEMLİ:</strong> Bu dosyayı (setup.php) şimdi sunucudan silin!<br>
        Güvenlik riski oluşturabilir.
      </div>
    </div>
    </body></html>';

} catch (Exception $e) {
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">
    <title>Hata</title>
    <style>body{font-family:sans-serif;background:#0d1421;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}.box{background:#1a2540;border-radius:12px;padding:40px;text-align:center;}</style>
    </head><body><div class="box">
    <div style="font-size:3rem">❌</div>
    <h2 style="color:#e8334a">Bağlantı Hatası</h2>
    <p style="color:#8899aa">gt_config.php dosyasındaki veritabanı bilgilerini kontrol edin.</p>
    <p style="color:#ff6b7a;font-size:13px">Hata: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div></body></html>';
}
