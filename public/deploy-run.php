<?php
/**
 * Deploy utility endpoint for environments without SSH.
 * Delete this file after deployment.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('html_errors', '1');

$providedKey = (string) ($_GET['key'] ?? '');
$expectedKey = (string) (getenv('DEPLOY_RUN_KEY') ?: 'gtp2026deploy');

if ($providedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    exit('Yetkisiz erisim.');
}

define('LARAVEL_START', microtime(true));

$baseCandidates = array_filter([
    realpath(__DIR__ . '/..'),
    realpath(__DIR__),
    realpath(dirname(__DIR__, 2)),
]);

$basePath = null;
foreach ($baseCandidates as $candidate) {
    if (is_file($candidate . '/vendor/autoload.php') && is_file($candidate . '/bootstrap/app.php')) {
        $basePath = $candidate;
        break;
    }
}

if ($basePath === null) {
    http_response_code(500);
    echo '<pre>Bootstrap path bulunamadi.
Kontrol edilen yollar:
' . htmlspecialchars(implode("\n", $baseCandidates), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

try {
    require $basePath . '/vendor/autoload.php';
    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>Laravel bootstrap hatasi:
' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n" .
        htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

$action = (string) ($_GET['action'] ?? 'status');
$output = '';
$deployBranch = (string) ($_GET['branch'] ?? getenv('GIT_DEPLOY_BRANCH') ?: 'work');
$deployBranch = preg_replace('/[^A-Za-z0-9._\\/-]/', '', $deployBranch) ?: 'work';

$run = static function (Illuminate\Contracts\Console\Kernel $kernel, string $command, array $params = []) use (&$output): void {
    $kernel->call($command, $params);
    $output .= ">>> {$command}\n";
    $output .= trim($kernel->output()) . "\n\n";
};

$runShell = static function (string $command, string $cwd) use (&$output): int {
    $output .= ">>> shell: {$command}\n";

    if (function_exists('proc_open')) {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($command, $descriptors, $pipes, $cwd);
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]) ?: '';
            $stderr = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $output .= trim($stdout . "\n" . $stderr) . "\n\n";

            return (int) $exitCode;
        }
    }

    if (function_exists('shell_exec')) {
        $raw = @shell_exec('cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1');
        $output .= trim((string) $raw) . "\n\n";

        return 0;
    }

    $output .= "Shell komutlari host tarafinda devre disi.\n\n";

    return 127;
};

$detectGitRoot = static function (string $candidate) use ($runShell): ?string {
    if (is_dir($candidate . '/.git')) {
        return $candidate;
    }

    $temp = '';
    $exitCode = 0;
    if (function_exists('proc_open')) {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open('git rev-parse --show-toplevel', $descriptors, $pipes, $candidate);
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]) ?: '';
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $temp = trim($stdout);
        }
    }

    if ($exitCode === 0 && $temp !== '' && is_dir($temp . '/.git')) {
        return $temp;
    }

    return null;
};

try {
    if ($action === 'migrate') {
        $run($kernel, 'migrate', ['--force' => true]);
    } elseif ($action === 'cache-clear') {
        $run($kernel, 'config:clear');
        $run($kernel, 'cache:clear');
        $run($kernel, 'view:clear');
        $run($kernel, 'route:clear');
    } elseif ($action === 'full-clear') {
        $run($kernel, 'config:clear');
        $run($kernel, 'cache:clear');
        $run($kernel, 'view:clear');
        $run($kernel, 'route:clear');
        $run($kernel, 'optimize:clear');
    } elseif ($action === 'status') {
        $run($kernel, 'migrate:status');
    } elseif ($action === 'import-airports') {
        set_time_limit(300);
        $run($kernel, 'airports:import');
    } elseif ($action === 'import-airlines') {
        set_time_limit(300);
        $run($kernel, 'airlines:import');
    } elseif ($action === 'sync-legacy-offers') {
        set_time_limit(300);
        $run($kernel, 'legacy:sync-offers');
    } elseif ($action === 'repair-legacy-notes') {
        set_time_limit(300);
        $run($kernel, 'legacy:repair-notes');
    } elseif ($action === 'git-status') {
        $gitRoot = $detectGitRoot($basePath);
        if ($gitRoot === null) {
            $output .= "Git root bulunamadi: {$basePath}\n";
        } else {
            $output .= "Git root: {$gitRoot}\n\n";
            $runShell('git rev-parse --short HEAD', $gitRoot);
            $runShell('git branch --show-current', $gitRoot);
            $runShell('git status --short', $gitRoot);
            $runShell('git remote -v', $gitRoot);
        }
    } elseif ($action === 'git-update') {
        set_time_limit(300);
        $gitRoot = $detectGitRoot($basePath);
        if ($gitRoot === null) {
            $output .= "Git root bulunamadi: {$basePath}\n";
        } else {
            $output .= "Git root: {$gitRoot}\n";
            $output .= "Branch: {$deployBranch}\n\n";
            $code1 = $runShell('git fetch --all --prune', $gitRoot);
            $code2 = $runShell('git checkout ' . escapeshellarg($deployBranch), $gitRoot);
            $code3 = $runShell('git pull --ff-only origin ' . escapeshellarg($deployBranch), $gitRoot);
            if ($code1 !== 0 || $code2 !== 0 || $code3 !== 0) {
                $output .= "Git update adiminda hata olustu.\n";
            }
        }
    } elseif ($action === 'deploy-work') {
        set_time_limit(300);
        $gitRoot = $detectGitRoot($basePath);
        if ($gitRoot === null) {
            $output .= "Git root bulunamadi: {$basePath}\n";
        } else {
            $output .= "Git root: {$gitRoot}\n";
            $output .= "Branch: {$deployBranch}\n\n";
            $code1 = $runShell('git fetch --all --prune', $gitRoot);
            $code2 = $runShell('git checkout ' . escapeshellarg($deployBranch), $gitRoot);
            $code3 = $runShell('git pull --ff-only origin ' . escapeshellarg($deployBranch), $gitRoot);
            if ($code1 === 0 && $code2 === 0 && $code3 === 0) {
                $run($kernel, 'config:clear');
                $run($kernel, 'cache:clear');
                $run($kernel, 'view:clear');
                $run($kernel, 'route:clear');
                $run($kernel, 'optimize:clear');
            } else {
                $output .= "Git update basarisiz oldugu icin clear adimlari atlandi.\n";
            }
        }
    } elseif ($action === 'ai-refresh') {
        set_time_limit(300);
        $days = (int) ($_GET['days'] ?? 30);
        $days = max(1, min(30, $days));
        $actorId = (int) ($_GET['actor'] ?? 1);
        $actorId = max(1, $actorId);

        /** @var \App\Services\AiCelebrationService $aiService */
        $aiService = $app->make(\App\Services\AiCelebrationService::class);
        $stats = $aiService->scanUpcomingSuggestions($days, true, $actorId);

        $output .= "AI scan stats: " . json_encode($stats, JSON_UNESCAPED_UNICODE) . "\n";

        $refreshed = 0;
        $campaigns = \App\Models\AiCelebrationCampaign::query()
            ->whereNotNull('source_key')
            ->where('status', \App\Models\AiCelebrationCampaign::STATUS_DRAFT)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->limit(200)
            ->get();

        foreach ($campaigns as $campaign) {
            $aiService->generateContent($campaign, $campaign->topic_prompt, $actorId);
            $refreshed++;
        }

        $output .= "AI refreshed draft campaigns: {$refreshed}\n\n";
    } else {
        $output .= "Bilinmeyen action: {$action}\n";
    }
} catch (Throwable $e) {
    $output .= "HATA: " . $e->getMessage() . "\n";
    $output .= $e->getTraceAsString();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Deploy Runner</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #fff; padding: 2rem; }
        pre { background: #000; padding: 1rem; border-radius: 8px; color: #0f0; white-space: pre-wrap; }
        .btn { display: inline-block; padding: 10px 20px; margin: 8px 4px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .red { background: #e94560; color: #fff; }
        .green { background: #198754; color: #fff; }
        .blue { background: #0d6efd; color: #fff; }
        .warn { background: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<h2>Deploy Runner - GrupTalepleri</h2>
<div class="warn">Bu dosyayi kullandiktan sonra hemen silin.</div>

<a href="?key=<?= urlencode($providedKey) ?>&action=status" class="btn blue">Migration Durumu</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=migrate" class="btn green" onclick="return confirm('Migration calistirilsin mi?')">Migrate</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=cache-clear" class="btn red">Cache Clear</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=full-clear" class="btn red">Full Clear</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=git-status" class="btn blue">Git Status</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=git-update&branch=<?= urlencode($deployBranch) ?>" class="btn green" onclick="return confirm('Git update calissin mi?')">Git Update (<?= htmlspecialchars($deployBranch, ENT_QUOTES, 'UTF-8') ?>)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=deploy-work&branch=<?= urlencode($deployBranch) ?>" class="btn green" onclick="return confirm('Git update + full clear calissin mi?')">Deploy Work</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=ai-refresh&days=30&actor=1" class="btn blue" onclick="return confirm('AI kayitlari yenilensin mi?')">AI Refresh</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=import-airports" class="btn green" onclick="return confirm('Havalimanlari ice aktarilsin mi?')">Import Airports</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=import-airlines" class="btn green" onclick="return confirm('Havayollari ice aktarilsin mi?')">Import Airlines</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=sync-legacy-offers" class="btn green" onclick="return confirm('Eski sistem opsiyon sync calissin mi?')">Legacy Offer Sync</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=repair-legacy-notes" class="btn blue" onclick="return confirm('Eski sistem notlari duzeltilsin mi?')">Repair Legacy Notes</a>

<?php if ($output !== ''): ?>
<h3>Cikti:</h3>
<pre><?= htmlspecialchars($output, ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>

<p style="color:#666;font-size:0.8rem;">
    PHP: <?= PHP_VERSION ?> |
    Laravel: <?= app()->version() ?> |
    <?= now()->format('d.m.Y H:i:s') ?>
</p>
</body>
</html>
