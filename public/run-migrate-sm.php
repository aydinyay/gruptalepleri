<?php
/**
 * Tek seferlik migration + seeder çalıştırıcı.
 * Başarıyla çalıştıktan sonra kendini siler.
 * Güvenlik: token parametresi zorunlu.
 */
if (($_GET['t'] ?? '') !== 'grtmig2026sm') {
    http_response_code(403);
    exit('no');
}

header('Content-Type: text/plain; charset=utf-8');

try {
    // .env oku
    $envPath = __DIR__ . '/../.env';
    $env = [];
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }

    $host  = $env['DB_HOST']     ?? '127.0.0.1';
    $port  = $env['DB_PORT']     ?? '3306';
    $db    = $env['DB_DATABASE'] ?? '';
    $user  = $env['DB_USERNAME'] ?? '';
    $pass  = $env['DB_PASSWORD'] ?? '';

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Bağlantı OK\n\n";

    // ── Migration 1: sosyal_medya_icerikleri ──────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sosyal_medya_icerikleri` (
            `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `platform`         VARCHAR(20)  NOT NULL,
            `format`           VARCHAR(30)  NOT NULL,
            `tema`             VARCHAR(80)  DEFAULT NULL,
            `konu`             VARCHAR(200) DEFAULT NULL,
            `icerik`           LONGTEXT     NOT NULL,
            `gorsel_base64`    LONGTEXT     DEFAULT NULL,
            `durum`            VARCHAR(20)  NOT NULL DEFAULT 'taslak',
            `planlanan_tarih`  TIMESTAMP    NULL DEFAULT NULL,
            `gonderim_tarihi`  TIMESTAMP    NULL DEFAULT NULL,
            `ozel_gun_ref`     VARCHAR(100) DEFAULT NULL,
            `ai_skor`          TINYINT      DEFAULT NULL,
            `buffer_id`        VARCHAR(80)  DEFAULT NULL,
            `user_id`          BIGINT UNSIGNED DEFAULT NULL,
            `created_at`       TIMESTAMP    NULL DEFAULT NULL,
            `updated_at`       TIMESTAMP    NULL DEFAULT NULL,
            INDEX `idx_platform_durum` (`platform`, `durum`),
            INDEX `idx_planlanan`      (`planlanan_tarih`),
            INDEX `idx_created`        (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ sosyal_medya_icerikleri tablosu oluşturuldu (veya zaten vardı)\n";

    // ── Migration 2: ozel_gunler ──────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ozel_gunler` (
            `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `ad`                 VARCHAR(150) NOT NULL,
            `kategori`           VARCHAR(40)  NOT NULL,
            `tarih`              DATE         NOT NULL,
            `tekrar`             VARCHAR(10)  NOT NULL DEFAULT 'yearly',
            `aciklama`           TEXT         DEFAULT NULL,
            `hizmet_baglantisi`  VARCHAR(50)  DEFAULT NULL,
            `hatirlatma_gun`     SMALLINT UNSIGNED NOT NULL DEFAULT 14,
            `aktif`              TINYINT(1)   NOT NULL DEFAULT 1,
            `created_at`         TIMESTAMP    NULL DEFAULT NULL,
            `updated_at`         TIMESTAMP    NULL DEFAULT NULL,
            INDEX `idx_tarih_aktif` (`tarih`, `aktif`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ ozel_gunler tablosu oluşturuldu (veya zaten vardı)\n";

    // ── migrations kaydı (Laravel migration tablosuna ekle) ───────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch`     INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $maxBatch = (int) $pdo->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn();
    $newBatch = $maxBatch + 1;
    $migs = [
        '2026_03_29_085116_create_sosyal_medya_icerikleri_table',
        '2026_03_29_085117_create_ozel_gunler_table',
    ];
    foreach ($migs as $mig) {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $exists->execute([$mig]);
        if (!(int) $exists->fetchColumn()) {
            $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)")->execute([$mig, $newBatch]);
            echo "✓ Migration kaydı: {$mig}\n";
        } else {
            echo "– Migration zaten kayıtlı: {$mig}\n";
        }
    }

    // ── Seeder: ozel_gunler ───────────────────────────────────────────────
    $count = (int) $pdo->query("SELECT COUNT(*) FROM ozel_gunler")->fetchColumn();
    if ($count > 0) {
        echo "\n– ozel_gunler zaten dolu ({$count} kayıt), seeder atlandı.\n";
    } else {
        $now = date('Y-m-d H:i:s');
        $gunler = [
            ['GrupTalepleri.com Yenilendi — Lansman', 'platform',  date('Y-m-d'), 'once',   "Türkiye'nin ilk ve tek grup operasyon platformu yeni arayüzü ve özellikleriyle yayında.", 'platform', 0],
            ['Ramazan Bayramı 2026',       'bayram',   '2026-03-20', 'yearly', 'Ramazan Bayramı grup turları ve özel tur paketleri için erken rezervasyon zamanı.', 'platform', 60],
            ['Kurban Bayramı 2026',        'bayram',   '2026-05-27', 'yearly', 'Kurban Bayramı tatili için hac ve umre turları, yurt içi/dışı grup paketleri.', 'air_charter', 60],
            ['Yılbaşı 2027',               'sezon',    '2027-01-01', 'yearly', 'Yılbaşı grup turları ve özel charter uçuşları için planlama zamanı.', 'air_charter', 45],
            ['Cumhuriyet Bayramı',         'resmi',    '2026-10-29', 'yearly', '29 Ekim Cumhuriyet Bayramı için yurt içi grup tur fırsatları.', 'platform', 7],
            ['23 Nisan Ulusal Egemenlik',  'resmi',    '2026-04-23', 'yearly', 'Çocuk ve aile grupları için özel tur fırsatları.', 'platform', 7],
            ['Alaçatı Ot Festivali',       'festival', '2026-03-14', 'yearly', "İzmir Alaçatı'da her yıl Mart ayında düzenlenen gastronomi festivali. Grup rezervasyonları için erken planlama.", 'leisure', 21],
            ['Cappadox Festivali',         'festival', '2026-06-05', 'yearly', "Kapadokya'da sanat, müzik ve doğa festivali. Grup charter uçuşları için ideal.", 'air_charter', 21],
            ['İstanbul Film Festivali',    'festival', '2026-04-10', 'yearly', 'Uluslararası İstanbul Film Festivali döneminde şehir grubu turları.', 'transfer', 14],
            ['EMITT Turizm Fuarı',         'turizm',   '2027-01-22', 'yearly', "Doğu Akdeniz Uluslararası Turizm ve Seyahat Fuarı — sektörün en büyük buluşması.", 'platform', 14],
            ['Dünya Turizm Günü',          'ulusal',   '2026-09-27', 'yearly', 'UNWTO Dünya Turizm Günü — sektör farkındalığı içerikleri için ideal.', 'platform', 7],
            ['Yaz Charter Sezonu Başlıyor','sezon',    '2026-05-01', 'yearly', 'Yaz sezonu charter uçuşları ve grup tur operasyonları başlıyor.', 'air_charter', 30],
            ['İstanbul Boğaz ve Yat Sezonu','sezon',   '2026-06-01', 'yearly', 'Boğaz sunset dinner cruise, yat kiralama ve leisure turları için sezon açılıyor.', 'leisure', 14],
            ['Kayak Sezonu Başlıyor',      'sezon',    '2026-12-01', 'yearly', 'Uludağ, Palandöken, Kartalkaya kayak grup turları için rezervasyon zamanı.', 'transfer', 30],
            ['Okul Çıkışı Grup Turları',   'sezon',    '2026-06-15', 'yearly', 'Okul tatili başlıyor — aile ve okul grubu turları için yoğun sezon.', 'platform', 21],
            ['Boğaz\'da Sunset Dinner Cruise','turizm','2026-08-01', 'once',   "Ağustos'ta İstanbul Boğazında gün batımı eşliğinde dinner cruise — grup rezervasyonları açık.", 'leisure', 14],
        ];

        $stmt = $pdo->prepare("INSERT INTO ozel_gunler (ad,kategori,tarih,tekrar,aciklama,hizmet_baglantisi,hatirlatma_gun,aktif,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,?,?)");
        foreach ($gunler as $g) {
            $stmt->execute([$g[0],$g[1],$g[2],$g[3],$g[4],$g[5],$g[6],$now,$now]);
        }
        echo "\n✓ ozel_gunler seeder: " . count($gunler) . " kayıt eklendi.\n";
    }

    echo "\n✅ Tüm işlemler tamamlandı.\n";

    // Kendini sil
    @unlink(__FILE__);
    echo "🗑️  Script silindi.\n";

} catch (Throwable $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    http_response_code(500);
}
