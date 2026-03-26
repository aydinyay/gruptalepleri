<?php
/**
 * ╔══════════════════════════════════════════════════════╗
 * ║   GrupTalepleri — Ziyaretçi Takip Sistemi           ║
 * ║   gt_config.php — Ana Ayar Dosyası                  ║
 * ╚══════════════════════════════════════════════════════╝
 */

// ★ VERİTABANI BİLGİLERİ
define('GT_DB_HOST', 'localhost');
define('GT_DB_NAME', 'gruprez1_gruprez1_ziyaretci');
define('GT_DB_USER', 'gruprez1_takip');
define('GT_DB_PASS', 'GtTakip2024!');

// ★ STATS SAYFASI GİRİŞ ŞİFRESİ
define('GT_STATS_PASS', 'GtStats2024!');

// ★ GÜNLÜK RAPOR MAİLİ
define('GT_ADMIN_MAIL', 'destek@gruptalepleri.com');

// ─── DİĞER AYARLAR ───────────────────────────────────
define('GT_KEEP_DAYS', 60);
define('GT_ONLINE_TIMEOUT', 5);

// ─── ÜYE TAKİBİ ──────────────────────────────────────
define('GT_MEMBER_SESSION_ID',   '');
define('GT_MEMBER_SESSION_NAME', '');
