<?php
// Basit deployment testi - bu dosya canlıda görünüyorsa deploy çalışıyor
$appPath = dirname(__DIR__);

echo "<pre style='font:14px monospace;background:#111;color:#0f0;padding:20px;'>";
echo "=== DEPLOYMENT DURUMU ===\n\n";

// 1. Bu dosya ne zaman kopyalandı?
echo "Bu dosya: " . date('Y-m-d H:i:s', filemtime(__FILE__)) . "\n\n";

// 2. AceneAIController var mı?
$ctrl = $appPath . '/app/Http/Controllers/AceneAIController.php';
echo "AceneAIController: " . (file_exists($ctrl) ? "VAR (" . date('H:i:s', filemtime($ctrl)) . ")" : "YOK!") . "\n";

// 3. acente-ai view var mı?
$view = $appPath . '/resources/views/superadmin/acente-ai.blade.php';
echo "acente-ai view:    " . (file_exists($view) ? "VAR (" . date('H:i:s', filemtime($view)) . ")" : "YOK!") . "\n\n";

// 4. Route cache var mı?
$routeCache = $appPath . '/bootstrap/cache/routes-v7.php';
echo "routes-v7.php: " . (file_exists($routeCache) ? "VAR" : "YOK") . "\n";

// 5. acente-ai route var mı cache'de?
if (file_exists($routeCache)) {
    $content = file_get_contents($routeCache);
    echo "acente-ai rotası cache'de: " . (str_contains($content, 'acente-ai') ? "EVET" : "HAYIR!") . "\n";
}

// 6. Opcache durum
echo "\nOpcache: " . (function_exists('opcache_get_status') ? "AKTIF" : "YOK") . "\n";
if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    echo "validate_timestamps: " . (ini_get('opcache.validate_timestamps') ? 'AÇIK' : 'KAPALI') . "\n";
}

echo "</pre>";
