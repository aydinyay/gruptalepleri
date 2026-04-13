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
    // Önce mevcut dizin ve üst dizinleri .git için tara (4 seviye yukarı)
    $check = $candidate;
    for ($i = 0; $i < 5; $i++) {
        if (is_dir($check . '/.git')) {
            return $check;
        }
        $parent = dirname($check);
        if ($parent === $check) break; // root'a ulaştık
        $check = $parent;
    }

    // .git bulunamadıysa git komutuyla dene
    $temp = '';
    $exitCode = 1;
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
    if ($action === 'patch-navbar') {
        // GitHub private repo'dan dosya çek (GitHub API + token gerekli)
        $branch   = $deployBranch ?: 'main';
        $ghToken  = (string) ($_GET['token'] ?? '');
        $apiBase  = "https://api.github.com/repos/aydinyay/gruptalepleri/contents";
        $files = [
            'resources/views/components/navbar-superadmin.blade.php',
            'resources/views/superadmin/tursab-kampanya.blade.php',
            'resources/views/superadmin/acente-listesi.blade.php',
            'resources/views/superadmin/kampanya-email.blade.php',
            'resources/views/superadmin/kampanya-sms.blade.php',
            'resources/views/superadmin/kampanya-csv-import.blade.php',
            'resources/views/superadmin/kampanya-zamanlama.blade.php',
            'routes/web.php',
            'routes/console.php',
            'app/Http/Controllers/TursabController.php',
            'app/Models/TursabDavet.php',
            'app/Http/Controllers/AcenetelIstatistikController.php',
            'app/Console/Commands/KampanyaEmailOtomatik.php',
            'app/Console/Commands/KampanyaSmsOtomatik.php',
            'app/Console/Commands/BakanlikCsvImport.php',
            'database/migrations/2026_04_03_012751_add_faks_harita_to_acenteler.php',
            'database/migrations/2026_04_03_014444_add_tip_to_tursab_davetler.php',
            // Dinner cruise B2B katalog
            'database/migrations/2026_04_13_100000_update_standard_departure_to_single.php',
            'app/Http/Controllers/Superadmin/LeisureSettingsController.php',
            'app/Http/Controllers/Payments/ModulePaymentController.php',
            'resources/views/superadmin/leisure/settings.blade.php',
            'resources/views/acente/dinner-cruise/catalog.blade.php',
            'resources/views/acente/dinner-cruise/show.blade.php',
            'public/deploy-run.php',
        ];

        // GitHub API ile dosya çek (private repo desteği)
        $fetchFile = function(string $rel) use ($apiBase, $branch, $ghToken): string|false {
            $url = $apiBase . '/' . $rel . '?ref=' . urlencode($branch);
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", array_filter([
                        'User-Agent: gruptalepleri-deploy/1.0',
                        'Accept: application/vnd.github.v3.raw',
                        $ghToken ? "Authorization: Bearer {$ghToken}" : '',
                    ])),
                    'timeout' => 15,
                ],
            ];
            $ctx = stream_context_create($opts);
            return @file_get_contents($url, false, $ctx);
        };

        if (!$ghToken) {
            $output .= "HATA: GitHub token gerekli. URL'e &token=GHP_... ekleyin.\n";
            $output .= "Token: https://github.com/settings/tokens/new (repo scope)\n";
        } else {
            foreach ($files as $rel) {
                $content = $fetchFile($rel);
                if ($content === false) {
                    $output .= "ATLANAMADI (fetch hatası): {$rel}\n";
                    continue;
                }
                $dest = $basePath . '/' . $rel;
                $dir  = dirname($dest);
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                file_put_contents($dest, $content);
                $output .= "OK: {$rel}\n";
            }
        }
        // Cache temizle
        $run($kernel, 'config:clear');
        $run($kernel, 'view:clear');
        $run($kernel, 'route:clear');
    } elseif ($action === 'patch-csv-command') {
        $dest = $basePath . '/app/Console/Commands/BakanlikCsvImport.php';
        $code = '<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BakanlikCsvImport extends Command
{
    protected $signature   = \'bakanlik:csv-import
                                {--file= : CSV dosya yolu}
                                {--truncate : Once tabloyu sifirla}
                                {--no-truncate : Truncate yapmadan sadece updateOrCreate}\';

    protected $description = \'Bakanlik CSV dosyasini acenteler tablosuna aktarir.\';

    public function handle(): int
    {
        $file = $this->option(\'file\') ?: storage_path(\'app/import/acenteler.csv\');

        if (!file_exists($file)) {
            $this->error("Dosya bulunamadi: {$file}");
            return self::FAILURE;
        }

        $noTruncate = $this->option(\'no-truncate\');

        if (!$noTruncate) {
            $this->warn(\'Acenteler tablosu temizleniyor (TRUNCATE)...\');
            DB::statement(\'SET FOREIGN_KEY_CHECKS=0\');
            DB::table(\'acenteler\')->truncate();
            DB::statement(\'SET FOREIGN_KEY_CHECKS=1\');
            $this->info(\'Tablo temizlendi.\');
        }

        $handle = fopen($file, \'r\');
        if (!$handle) {
            $this->error("CSV dosyasi acilamadi: {$file}");
            return self::FAILURE;
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") { rewind($handle); }

        // Delimiter otomatik tespit: ilk satırı oku, ; mi , mi?
        $firstLine = fgets($handle);
        rewind($handle);
        $bom2 = fread($handle, 3);
        if ($bom2 !== "\xEF\xBB\xBF") { rewind($handle); }
        $delim = (substr_count($firstLine, \';\') > substr_count($firstLine, \',\')) ? \';\' : \',\';

        $headers = fgetcsv($handle, 0, $delim);
        if (!$headers) {
            $this->error(\'CSV baslik satiri okunamadi.\');
            fclose($handle);
            return self::FAILURE;
        }

        // BOM kalintisi ilk headeri temizle
        $headers = array_map(fn($h) => trim(ltrim($h, "\xEF\xBB\xBF\xE2\x80\x8B")), $headers);
        $this->line(\'Delimiter: \' . ($delim === \';\' ? \'noktalivigul\' : \'virgul\'));
        $this->line(\'Kolonlar: \' . implode(\', \', $headers));

        $toplam = $yeni = $guncellenen = $hatali = 0;

        $isCli = php_sapi_name() === \'cli\';
        $bar = null;
        if ($isCli) {
            $bar = $this->output->createProgressBar();
            $bar->start();
        }

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (count($row) < 2) continue;
            $normalized = array_slice(array_pad($row, count($headers), \'\'), 0, count($headers));
            $data = array_combine($headers, $normalized);
            if ($data === false) { $hatali++; continue; }

            $belgeNo = trim($data[\'belgeNo\'] ?? $data[\'Detay_BelgeNo\'] ?? \'\');
            if (!$belgeNo) { $hatali++; continue; }

            $unvan       = trim($data[\'Detay_Unvan\'] ?? \'\') ?: trim($data[\'unvan\'] ?? \'\');
            $ticariUnvan = trim($data[\'Detay_TicariUnvan\'] ?? \'\') ?: trim($data[\'ticariUnvan\'] ?? \'\');
            $il          = trim($data[\'_Il\'] ?? \'\') ?: trim($data[\'ilAd\'] ?? \'\');
            $ilIlce      = trim($data[\'Il_Ilce\'] ?? \'\');
            $telefon     = trim($data[\'Detay_Telefon\'] ?? \'\') ?: trim($data[\'telefon\'] ?? \'\');
            $eposta      = trim($data[\'E-posta\'] ?? \'\');
            $faks        = trim($data[\'Faks\'] ?? \'\');
            $adres       = trim($data[\'Adres\'] ?? $data[\'adres\'] ?? \'\');
            $harita      = trim($data[\'Harita\'] ?? \'\');
            $grup        = trim($data[\'grup\'] ?? \'\');
            $durum       = trim($data[\'_Durum\'] ?? \'\');
            $internalId  = trim($data[\'internalId\'] ?? \'\');

            $payload = [
                \'acente_unvani\' => $unvan,
                \'ticari_unvan\'  => $ticariUnvan,
                \'grup\'          => $grup,
                \'il\'            => $il,
                \'il_ilce\'       => $ilIlce,
                \'telefon\'       => $telefon,
                \'eposta\'        => $eposta,
                \'faks\'          => $faks ?: null,
                \'adres\'         => $adres ?: null,
                \'harita\'        => $harita ?: null,
                \'internal_id\'   => $internalId ?: null,
                \'durum\'         => $durum ?: null,
                \'kaynak\'        => \'bakanlik\',
                \'synced_at\'     => now(),
            ];

            try {
                $existing = DB::table(\'acenteler\')->where(\'belge_no\', $belgeNo)->first();
                if ($existing) {
                    DB::table(\'acenteler\')->where(\'belge_no\', $belgeNo)->update($payload);
                    $guncellenen++;
                } else {
                    DB::table(\'acenteler\')->insert(array_merge($payload, [
                        \'belge_no\'   => $belgeNo,
                        \'created_at\' => now(),
                        \'updated_at\' => now(),
                    ]));
                    $yeni++;
                }
                $toplam++;
            } catch (\Throwable $e) {
                $hatali++;
                $this->warn("Hata ({$belgeNo}): " . $e->getMessage());
            }

            if ($bar) $bar->advance();
        }

        fclose($handle);
        if ($bar) $bar->finish();
        if ($isCli) $this->newLine(2);

        $this->info("Tamamlandi!");
        $this->table(
            [\'Toplam\', \'Yeni\', \'Guncellenen\', \'Hatali\'],
            [[$toplam, $yeni, $guncellenen, $hatali]]
        );

        return self::SUCCESS;
    }
}
';
        file_put_contents($dest, $code);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($dest, true);
            $output .= "Opcache temizlendi.\n";
        }
        $output .= "OK: BakanlikCsvImport.php yazildi (null guard + array_slice fix).\n";
        // Verify the fix is in the written file
        $verify = file_get_contents($dest);
        $output .= str_contains($verify, 'array_slice') ? "Verify OK: array_slice mevcut.\n" : "UYARI: array_slice bulunamadi!\n";
        $run($kernel, 'config:clear');
    } elseif ($action === 'patch-controller') {
        $ctrlFile = $basePath . '/app/Http/Controllers/TursabController.php';
        $content  = file_get_contents($ctrlFile);
        if ($content === false) {
            $output .= "HATA: TursabController.php okunamadi.\n";
        } elseif (str_contains($content, 'function csvImportForm')) {
            $output .= "Zaten mevcut: csvImportForm metodu var.\n";
        } else {
            $newMethods = <<<'PHPCODE'

    // ── CSV Import (Web) ─────────────────────────────────────────────────────

    public function csvImportForm()
    {
        $this->assertSuperadmin();
        $toplam = \App\Models\Acenteler::count();
        return view('superadmin.kampanya-csv-import', compact('toplam'));
    }

    public function csvImportYukle(\Illuminate\Http\Request $request)
    {
        $this->assertSuperadmin();
        $request->validate(['csv_dosya' => 'required|file|max:102400']);
        set_time_limit(600);
        ini_set('memory_limit', '512M');
        $file = $request->file('csv_dosya');
        $path = storage_path('app/import/acenteler.csv');
        if (!is_dir(dirname($path))) { mkdir(dirname($path), 0755, true); }
        $file->move(dirname($path), 'acenteler.csv');
        $noTruncate = $request->boolean('no_truncate', false);
        $exitCode = \Artisan::call('bakanlik:csv-import', $noTruncate ? ['--no-truncate' => true] : []);
        $out = trim(\Artisan::output());
        if ($exitCode !== 0) { return back()->with('error', "Import basarisiz (kod: {$exitCode}).\n\n" . $out); }
        return back()->with('success', "Import tamamlandi.\n\n" . $out);
    }

    // ── Email Kampanya ────────────────────────────────────────────────────────

    public function emailKampanya(\Illuminate\Http\Request $request)
    {
        $this->assertSuperadmin();
        $il   = trim($request->input('il', ''));
        $ilce = trim($request->input('ilce', ''));
        $grup = trim($request->input('grup', ''));
        $q    = trim($request->input('q', ''));
        $sadeceDavetEdilmemis = $request->boolean('sadece_yeni', true);
        $perPage = in_array((int)$request->input('per_page', 50), [25,50,100,200]) ? (int)$request->input('per_page',50) : 50;
        $tableExists = \Illuminate\Support\Facades\Schema::hasTable('tursab_davetler');
        $bugunGonderilen = $tableExists ? \App\Models\TursabDavet::whereDate('created_at', today())->count() : 0;
        $kalanHak = max(0, 50 - $bugunGonderilen);
        $davetEdilenler = $tableExists ? \App\Models\TursabDavet::pluck('eposta')->map(fn($e) => strtolower($e))->toArray() : [];
        $iller = \App\Models\Acenteler::whereNotNull('il')->where('il','!=','')->distinct()->orderBy('il')->pluck('il');
        $query = \App\Models\Acenteler::whereNotNull('eposta')->where('eposta','!=','');
        if ($q)    $query->where(fn($w) => $w->where('acente_unvani','like',"%{$q}%")->orWhere('belge_no','like',"%{$q}%"));
        if ($il)   $query->where('il', $il);
        if ($ilce) $query->where('il_ilce', $ilce);
        if ($grup) $query->where('grup', $grup);
        if ($sadeceDavetEdilmemis && count($davetEdilenler)) {
            $placeholders = implode(',', array_fill(0, count($davetEdilenler), '?'));
            $query->whereRaw("LOWER(eposta) NOT IN ({$placeholders})", $davetEdilenler);
        }
        $acenteler = $query->select('id','belge_no','acente_unvani','ticari_unvan','grup','il','il_ilce','eposta','telefon')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')->paginate($perPage)->withQueryString();
        $gecmis = $tableExists ? \App\Models\TursabDavet::orderByDesc('created_at')->limit(100)->get() : collect();
        return view('superadmin.kampanya-email', compact('acenteler','iller','bugunGonderilen','kalanHak','gecmis','il','ilce','grup','q','sadeceDavetEdilmemis','perPage'));
    }

    // ── SMS Kampanya ──────────────────────────────────────────────────────────

    public function smsKampanya(\Illuminate\Http\Request $request)
    {
        $this->assertSuperadmin();
        $il   = trim($request->input('il', ''));
        $ilce = trim($request->input('ilce', ''));
        $grup = trim($request->input('grup', ''));
        $q    = trim($request->input('q', ''));
        $sadeceCep = $request->boolean('sadece_cep', true);
        $perPage = in_array((int)$request->input('per_page', 50), [25,50,100,200]) ? (int)$request->input('per_page',50) : 50;
        $iller = \App\Models\Acenteler::whereNotNull('il')->where('il','!=','')->distinct()->orderBy('il')->pluck('il');
        $query = \App\Models\Acenteler::query();
        if ($q)    $query->where(fn($w) => $w->where('acente_unvani','like',"%{$q}%")->orWhere('belge_no','like',"%{$q}%"));
        if ($il)   $query->where('il', $il);
        if ($ilce) $query->where('il_ilce', $ilce);
        if ($grup) $query->where('grup', $grup);
        if ($sadeceCep) {
            $query->whereNotNull('telefon')->where('telefon','!=','')->whereRaw("telefon REGEXP '^[[:space:]]*(\\\\+?90)?0?5[0-9]'");
        }
        $acenteler = $query->select('id','belge_no','acente_unvani','ticari_unvan','grup','il','il_ilce','eposta','telefon')
                           ->orderByRaw('CAST(belge_no AS UNSIGNED) DESC')->paginate($perPage)->withQueryString();
        return view('superadmin.kampanya-sms', compact('acenteler','iller','il','ilce','grup','q','sadeceCep','perPage'));
    }

    // ── Otomatik Zamanlama ────────────────────────────────────────────────────

    public function zamanlamaForm()
    {
        $this->assertSuperadmin();
        $emailAyar = $this->zamanlamaAyar('email');
        $smsAyar   = $this->zamanlamaAyar('sms');
        $emailLog  = $this->zamanlamaLog('email');
        $smsLog    = $this->zamanlamaLog('sms');
        $iller = \App\Models\Acenteler::whereNotNull('il')->where('il','!=','')->distinct()->orderBy('il')->pluck('il');
        return view('superadmin.kampanya-zamanlama', compact('emailAyar','smsAyar','emailLog','smsLog','iller'));
    }

    public function zamanlamaKaydet(\Illuminate\Http\Request $request)
    {
        $this->assertSuperadmin();
        $tip = $request->input('tip');
        abort_unless(in_array($tip, ['email','sms']), 422);
        $slotSaatleri = $request->input('slot_saat', []);
        $slotAdetler  = $request->input('slot_adet', []);
        $slotAktifler = $request->input('slot_aktif', []);
        $slotlar = [];
        foreach ($slotSaatleri as $i => $saat) {
            if (!preg_match('/^\d{2}:\d{2}$/', $saat)) continue;
            $slotlar[] = ['saat' => $saat, 'adet' => max(1, min(500, (int)($slotAdetler[$i] ?? 50))), 'aktif' => in_array((string)$i, array_keys($slotAktifler))];
        }
        $ayar = ['aktif' => $request->boolean('aktif'), 'slotlar' => $slotlar, 'filtre' => ['il' => trim($request->input('filtre_il','')), 'ilce' => trim($request->input('filtre_ilce','')), 'grup' => trim($request->input('filtre_grup',''))]];
        if ($tip === 'email') { $ayar['filtre']['sablon'] = in_array($request->input('filtre_sablon'), ['emails.tursab_davet','emails.tursab_davet_yeni_acente']) ? $request->input('filtre_sablon') : 'emails.tursab_davet'; }
        if ($tip === 'sms') { $mesaj = trim($request->input('sms_mesaj','')); if (mb_strlen($mesaj) > 160) { return back()->with('error','SMS metni 160 karakteri gecemez.'); } $ayar['mesaj'] = $mesaj; }
        $key = $tip === 'email' ? 'kampanya_email_zamanlama' : 'kampanya_sms_zamanlama';
        \App\Models\SistemAyar::set($key, json_encode($ayar, JSON_UNESCAPED_UNICODE));
        return back()->with('success', ($tip === 'email' ? 'Email' : 'SMS') . ' kampanya zamanlama kaydedildi.');
    }

    public function zamanlamaTestGonder(\Illuminate\Http\Request $request)
    {
        $this->assertSuperadmin();
        $tip = $request->input('tip', 'email');
        abort_unless(in_array($tip, ['email','sms']), 422);
        $command = $tip === 'email' ? 'kampanya:email-otomatik' : 'kampanya:sms-otomatik';
        $args = ['--force' => true];
        if ($request->boolean('dry_run', true)) $args['--dry-run'] = true;
        \Artisan::call($command, $args);
        $out = trim(\Artisan::output());
        return response()->json(['success' => true, 'output' => $out ?: 'Komut tamamlandi.']);
    }

    private function zamanlamaAyar(string $tip): array
    {
        $key  = $tip === 'email' ? 'kampanya_email_zamanlama' : 'kampanya_sms_zamanlama';
        $json = \App\Models\SistemAyar::get($key, '');
        if (!$json) { return ['aktif' => false, 'slotlar' => [['saat' => '09:00', 'adet' => 50, 'aktif' => false]], 'filtre' => ['il' => '', 'ilce' => '', 'grup' => '', 'sablon' => 'emails.tursab_davet'], 'mesaj' => '']; }
        $a = json_decode($json, true) ?? [];
        if (!isset($a['slotlar']) || empty($a['slotlar'])) { $a['slotlar'] = [['saat' => '09:00', 'adet' => 50, 'aktif' => false]]; }
        return $a;
    }

    private function zamanlamaLog(string $tip): array
    {
        $key  = $tip === 'email' ? 'kampanya_email_calisma_log' : 'kampanya_sms_calisma_log';
        $json = \App\Models\SistemAyar::get($key, '{}');
        return json_decode($json, true) ?? [];
    }

    // ── AJAX: İlçe Listesi ───────────────────────────────────────────────────

    public function ilceler(\Illuminate\Http\Request $request)
    {
        $this->assertSuperadmin();
        $il = trim($request->input('il', ''));
        if (!$il) { return response()->json([]); }
        $ilceler = \App\Models\Acenteler::where('il', $il)->whereNotNull('il_ilce')->where('il_ilce','!=','')->distinct()->orderBy('il_ilce')->pluck('il_ilce');
        return response()->json($ilceler);
    }

PHPCODE;
            // Son kapanış } öncesine inject et
            $lastBrace = strrpos($content, '}');
            $content   = substr($content, 0, $lastBrace) . $newMethods . "\n}\n";
            file_put_contents($ctrlFile, $content);
            $output .= "OK: TursabController'a yeni metodlar eklendi.\n";
        }
        $run($kernel, 'route:clear');
        $run($kernel, 'config:clear');
        $run($kernel, 'view:clear');
    } elseif ($action === 'diagnose') {
        // Route ve controller teşhis
        $routesFile = $basePath . '/routes/web.php';
        $content = file_get_contents($routesFile);
        // kampanya/csv-import etrafındaki satırları göster
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (str_contains($line, 'kampanya')) {
                $output .= ($i+1) . ": " . $line . "\n";
            }
        }
        $output .= "\n--- TursabController metodları ---\n";
        $ctrl = $basePath . '/app/Http/Controllers/TursabController.php';
        $ctrlContent = file_get_contents($ctrl);
        preg_match_all('/public function (\w+)\(/', $ctrlContent, $matches);
        $output .= implode(', ', $matches[1]) . "\n";
    } elseif ($action === 'patch-routes') {
        // Eksik kampanya route'larını web.php'ye enjekte et
        $routesFile = $basePath . '/routes/web.php';
        $content = file_get_contents($routesFile);
        if ($content === false) {
            $output .= "HATA: routes/web.php okunamadı.\n";
        } elseif (str_contains($content, "kampanya/csv-import")) {
            $output .= "Zaten mevcut: kampanya/csv-import route'u var.\n";
        } else {
            // tursab-ilceler satırını bul ve önüne ekle
            $anchor = "Route::get( '/tursab-ilceler'";
            $newRoutes = "    Route::get( '/kampanya/email',      [\\App\\Http\\Controllers\\TursabController::class, 'emailKampanya'])->name('kampanya.email');\n"
                       . "    Route::get( '/kampanya/sms',        [\\App\\Http\\Controllers\\TursabController::class, 'smsKampanya'])->name('kampanya.sms');\n"
                       . "    Route::get( '/kampanya/csv-import', [\\App\\Http\\Controllers\\TursabController::class, 'csvImportForm'])->name('kampanya.csv-import');\n"
                       . "    Route::post('/kampanya/csv-import', [\\App\\Http\\Controllers\\TursabController::class, 'csvImportYukle'])->name('kampanya.csv-import.yukle');\n"
                       . "    Route::get( '/kampanya/zamanlama',  [\\App\\Http\\Controllers\\TursabController::class, 'zamanlamaForm'])->name('kampanya.zamanlama');\n"
                       . "    Route::post('/kampanya/zamanlama',  [\\App\\Http\\Controllers\\TursabController::class, 'zamanlamaKaydet'])->name('kampanya.zamanlama.kaydet');\n"
                       . "    Route::post('/kampanya/zamanlama/test', [\\App\\Http\\Controllers\\TursabController::class, 'zamanlamaTestGonder'])->name('kampanya.zamanlama.test');\n";
            if (str_contains($content, $anchor)) {
                $content = str_replace($anchor, $newRoutes . "    " . ltrim($anchor), $content);
                file_put_contents($routesFile, $content);
                $output .= "OK: 7 kampanya route'u eklendi.\n";
            } else {
                // Anchor bulunamadı, tursab.toplu-sms satırından sonra ekle
                $anchor2 = "Route::post('/tursab-toplu-sms'";
                if (str_contains($content, $anchor2)) {
                    $pos = strpos($content, "\n", strpos($content, $anchor2));
                    $content = substr($content, 0, $pos + 1) . $newRoutes . substr($content, $pos + 1);
                    file_put_contents($routesFile, $content);
                    $output .= "OK: 7 kampanya route'u (fallback anchor) eklendi.\n";
                } else {
                    $output .= "HATA: Ekleme noktası bulunamadı. Anchor yok.\n";
                }
            }
        }
        $run($kernel, 'route:clear');
        $run($kernel, 'config:clear');
    } elseif ($action === 'patch-console') {
        // routes/console.php — runInBackground kaldir (proc_open devre disi sunucularda sorun cikarir)
        $consoleFile = $basePath . '/routes/console.php';
        $content = file_get_contents($consoleFile);
        $fixed = $content;
        $fixed = str_replace("    ->runInBackground()\n    ->environments(['production']);\n\n// Zamanlanmış SMS kampanyası", "    ->environments(['production']);\n\n// Zamanlanmış SMS kampanyası", $fixed);
        $fixed = str_replace("    ->runInBackground()\n    ->environments(['production']);\n\n// Zamanlanmış SMS'leri", "    ->environments(['production']);\n\n// Zamanlanmış SMS'leri", $fixed);
        if ($fixed === $content) {
            $output .= "Değişiklik gerekmedi veya pattern bulunamadı (belki zaten düzeltilmiş).\n";
        } else {
            file_put_contents($consoleFile, $fixed);
            $output .= "routes/console.php güncellendi — runInBackground kaldırıldı.\n";
        }
        $run($kernel, 'config:clear');
        $run($kernel, 'cache:clear');
    } elseif ($action === 'kampanya-log') {
        $logFile = $basePath . '/storage/app/kampanya-email-out.txt';
        if (!file_exists($logFile)) {
            $output .= "Henüz log yok: {$logFile}\n";
        } else {
            $output .= file_get_contents($logFile);
        }
    } elseif ($action === 'schedule-run') {
        $run($kernel, 'schedule:run');
    } elseif ($action === 'email-force') {
        $run($kernel, 'kampanya:email-otomatik', ['--force' => true]);
    } elseif ($action === 'migrate') {
        $run($kernel, 'migrate', ['--force' => true]);
    } elseif ($action === 'fix-departure-times') {
        // Tek seferlik: standard paketi tek kalkis saatine guncelle
        $dbCfg = config('database.connections.mysql');
        $dsn = "mysql:host={$dbCfg['host']};port={$dbCfg['port']};dbname={$dbCfg['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbCfg['username'], $dbCfg['password']);
        $stmt = $pdo->prepare("UPDATE leisure_package_templates SET departure_times = ?, updated_at = NOW() WHERE product_type = 'dinner_cruise' AND code = 'standard'");
        $stmt->execute([json_encode(['20:30 Biniş / 21:00 Kalkış'])]);
        $output .= "Etkilenen satir: " . $stmt->rowCount() . "\n";
        $output .= "standard paketi departure_times → [\"20:30 Biniş / 21:00 Kalkış\"] olarak güncellendi.\n";
    } elseif ($action === 'setup-dc-packages') {
        // Alkolsüz (standard → alcoholfree) ve Alkollü paketleri kur
        $dbCfg = config('database.connections.mysql');
        $dsn = "mysql:host={$dbCfg['host']};port={$dbCfg['port']};dbname={$dbCfg['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // 1) standard → alcoholfree güncelle
        $stmt = $pdo->prepare("
            UPDATE leisure_package_templates
            SET code = 'alcoholfree',
                name_tr = 'Alkolsüz Dinner Cruise',
                name_en = 'Non-Alcoholic Dinner Cruise',
                summary_tr = 'Boğaz hattında alkolsüz menü, canlı show ve transfer dahil akşam deneyimi.',
                summary_en = 'Evening Bosphorus cruise with non-alcoholic menu, live show and transfer.',
                base_price_per_person = 35.00,
                original_price_per_person = 60.00,
                currency = 'EUR',
                sort_order = 10,
                includes_tr = ?,
                includes_en = ?,
                excludes_tr = ?,
                excludes_en = ?,
                is_active = 1,
                updated_at = NOW()
            WHERE product_type = 'dinner_cruise' AND code = 'standard'
        ");
        $stmt->execute([
            json_encode(['Gidiş / Dönüş transfer', 'Alkolsüz içecek paketi', 'Akşam yemeği', 'Canlı show programı', 'Boğaz turu'], JSON_UNESCAPED_UNICODE),
            json_encode(['Round-trip transfer', 'Non-alcoholic beverage package', 'Dinner', 'Live show program', 'Bosphorus cruise'], JSON_UNESCAPED_UNICODE),
            json_encode(['Alkollü içecekler', 'Özel fotoğraf/video çekimi'], JSON_UNESCAPED_UNICODE),
            json_encode(['Alcoholic beverages', 'Private photo/video production'], JSON_UNESCAPED_UNICODE),
        ]);
        $output .= "alcoholfree paketi guncellendi: " . $stmt->rowCount() . " satir.\n";

        // 2) alcohol paketi yoksa ekle
        $check = $pdo->prepare("SELECT id FROM leisure_package_templates WHERE product_type = 'dinner_cruise' AND code = 'alcohol' LIMIT 1");
        $check->execute();
        if ($check->fetch()) {
            $output .= "alcohol paketi zaten mevcut, atlanıyor.\n";
        } else {
            $ins = $pdo->prepare("
                INSERT INTO leisure_package_templates
                    (product_type, code, level, name_tr, name_en, summary_tr, summary_en,
                     base_price_per_person, original_price_per_person, currency,
                     sort_order, includes_tr, includes_en, excludes_tr, excludes_en,
                     departure_times, pier_name, duration_hours, is_active, created_at, updated_at)
                VALUES
                    ('dinner_cruise', 'alcohol', 'standard',
                     'Alkollü Dinner Cruise', 'Alcoholic Dinner Cruise',
                     'Boğaz hattında alkollü menü, canlı show ve transfer dahil akşam deneyimi.',
                     'Evening Bosphorus cruise with alcoholic menu (2 doubles), live show and transfer.',
                     45.00, 80.00, 'EUR',
                     20, ?, ?, ?, ?,
                     ?, 'Kabataş İskelesi', 3, 1, NOW(), NOW())
            ");
            $ins->execute([
                json_encode(['Gidiş / Dönüş transfer', 'Alkollü içecek paketi (2 duble)', 'Akşam yemeği', 'Canlı show programı', 'Boğaz turu'], JSON_UNESCAPED_UNICODE),
                json_encode(['Round-trip transfer', 'Alcoholic beverage package (2 doubles)', 'Dinner', 'Live show program', 'Bosphorus cruise'], JSON_UNESCAPED_UNICODE),
                json_encode(['Limitsiz alkol', 'Özel fotoğraf/video çekimi'], JSON_UNESCAPED_UNICODE),
                json_encode(['Unlimited alcohol', 'Private photo/video production'], JSON_UNESCAPED_UNICODE),
                json_encode(['20:30 Biniş / 21:00 Kalkış'], JSON_UNESCAPED_UNICODE),
            ]);
            $output .= "alcohol paketi eklendi (ID: " . $pdo->lastInsertId() . ").\n";
        }
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
    } elseif ($action === 'check-blade') {
        $file = $basePath . '/resources/views/superadmin/kampanya-email.blade.php';
        if (!file_exists($file)) { $output .= "DOSYA YOK: {$file}\n"; }
        else {
            $lines = file($file);
            $output .= "Toplam satir: " . count($lines) . "\n";
            foreach ($lines as $i => $line) {
                if (str_contains($line, 'tursab.kampanya') || str_contains($line, 'Kampanya Hub')) {
                    $output .= "Satir " . ($i+1) . ": " . trim($line) . "\n";
                }
            }
            // views dir
            $viewsDir = $basePath . '/storage/framework/views';
            $files = glob($viewsDir . '/*.php') ?: [];
            $output .= "\nViews dir ({$viewsDir}): " . count($files) . " dosya\n";
            // shell ile de dene
            if (function_exists('shell_exec')) {
                $cnt = @shell_exec("ls " . escapeshellarg($viewsDir) . " 2>&1 | wc -l");
                $output .= "Shell ls | wc -l: " . trim((string)$cnt) . "\n";
            }
        }
    } elseif ($action === 'fix-route-names') {
        // kampanya view'larindaki yanlis route adlarini duzelt
        $fixes = [
            $basePath . '/resources/views/superadmin/kampanya-email.blade.php',
            $basePath . '/resources/views/superadmin/kampanya-sms.blade.php',
            $basePath . '/resources/views/superadmin/kampanya-zamanlama.blade.php',
        ];
        foreach ($fixes as $file) {
            if (!file_exists($file)) { $output .= "YOK: {$file}\n"; continue; }
            $content = file_get_contents($file);
            $new = str_replace(
                ["route('tursab.kampanya')", "route('tursab.toplu-davet')", "route('tursab.toplu-sms')"],
                ["route('superadmin.tursab.kampanya')", "route('superadmin.tursab.toplu-davet')", "route('superadmin.tursab.toplu-sms')"],
                $content
            );
            if ($content === $new) { $output .= "DEGISIKLIK YOK: " . basename($file) . "\n"; }
            else { file_put_contents($file, $new); $output .= "DUZELTILDI: " . basename($file) . "\n"; }
        }
        // compiled view cache temizle
        $viewsDir = $basePath . '/storage/framework/views';
        $deleted = 0;
        foreach (glob($viewsDir . '/*.php') as $f) { @unlink($f); $deleted++; }
        $output .= "Compiled views silindi: {$deleted}\n";
    } elseif ($action === 'clear-views') {
        $viewsDir = $basePath . '/storage/framework/views';
        $deleted = 0;
        foreach (glob($viewsDir . '/*.php') as $f) {
            @unlink($f);
            $deleted++;
        }
        $output .= "Silinen compiled view: {$deleted} dosya\n";
        $output .= "Kalan: " . count(glob($viewsDir . '/*.php')) . " dosya\n";
    } elseif ($action === 'cron-log') {
        $logFile = $basePath . '/storage/logs/cron.log';
        if (!file_exists($logFile)) {
            $output .= "cron.log bulunamadı: {$logFile}\n";
            $output .= "Cron henüz çalışmamış veya /dev/null'a yönlendiriliyor.\n";
        } else {
            $lines = file($logFile);
            $last = array_slice($lines, -50);
            $output .= "--- cron.log Son 50 satır ---\n";
            $output .= implode('', $last);
        }
    } elseif ($action === 'log') {
        $logFile = $basePath . '/storage/logs/laravel.log';
        if (!file_exists($logFile)) {
            $output .= "Log dosyası bulunamadı: {$logFile}\n";
        } else {
            $lines = file($logFile);
            $last = array_slice($lines, -100);
            $output .= "--- Son 100 satır ({$logFile}) ---\n";
            $output .= implode('', $last);
        }
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
    } elseif ($action === 'csv-import-pdo') {
        echo '<div style="background:#000;color:#0f0;padding:1rem;font-family:monospace;white-space:pre-wrap;">'; flush();
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        echo "PDO ile CSV import basladi...\n"; flush();
        $dbCfg = config('database.connections.mysql');
        $dsn = "mysql:host={$dbCfg['host']};port={$dbCfg['port']};dbname={$dbCfg['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbCfg['username'], $dbCfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
        echo "DB baglantisi OK: {$dbCfg['database']}\n"; flush();
        $noTruncate = isset($_GET['no_truncate']);
        $csvFile = $basePath . '/storage/app/import/acenteler.csv';
        if (!file_exists($csvFile)) {
            echo "HATA: CSV bulunamadi: {$csvFile}\n"; flush();
        } else {
            if (!$noTruncate) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                $pdo->exec('TRUNCATE TABLE acenteler');
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                echo "TRUNCATE OK.\n"; flush();
            }
            $handle = fopen($csvFile, 'r');
            $firstLine = fgets($handle); rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            $headers = fgetcsv($handle, 0, $delim);
            $headers = array_map(fn($h) => trim(ltrim($h, "\xEF\xBB\xBF\xE2\x80\x8B")), $headers);
            echo "Delimiter:{$delim} Kolonlar:" . count($headers) . "\n"; flush();
            $toUtf = function($s) {
                $s = trim($s);
                if ($s === '' || mb_check_encoding($s, 'UTF-8')) return $s;
                $r = @iconv('windows-1254', 'UTF-8//IGNORE', $s);
                return ($r !== false && $r !== '') ? $r : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-9');
            };
            $sql = "INSERT INTO acenteler (belge_no,acente_unvani,ticari_unvan,grup,il,il_ilce,telefon,eposta,faks,adres,harita,internal_id,durum,kaynak,synced_at,created_at,updated_at)
                    VALUES (:belge_no,:acente_unvani,:ticari_unvan,:grup,:il,:il_ilce,:telefon,:eposta,:faks,:adres,:harita,:internal_id,:durum,:kaynak,:synced_at,:created_at,:updated_at)";
            $stmt = $pdo->prepare($sql);
            $toplam = $hatali = 0;
            $now = date('Y-m-d H:i:s');
            while (($row = fgetcsv($handle, 0, $delim)) !== false) {
                if (count($row) < 2) continue;
                $norm = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
                $data = @array_combine($headers, $norm);
                if (!$data) { $hatali++; continue; }
                $belgeNo = trim($data['belgeNo'] ?? $data['Detay_BelgeNo'] ?? '');
                if (!$belgeNo) { $hatali++; continue; }
                try {
                    $stmt->execute([
                        ':belge_no'      => $belgeNo,
                        ':acente_unvani' => $toUtf($data['Detay_Unvan'] ?? '') ?: $toUtf($data['unvan'] ?? ''),
                        ':ticari_unvan'  => $toUtf($data['Detay_TicariUnvan'] ?? '') ?: $toUtf($data['ticariUnvan'] ?? ''),
                        ':grup'          => $toUtf($data['grup'] ?? ''),
                        ':il'            => $toUtf($data['_Il'] ?? '') ?: $toUtf($data['ilAd'] ?? ''),
                        ':il_ilce'       => $toUtf($data['Il_Ilce'] ?? ''),
                        ':telefon'       => $toUtf($data['Detay_Telefon'] ?? '') ?: $toUtf($data['telefon'] ?? ''),
                        ':eposta'        => trim($data['E-posta'] ?? ''),
                        ':faks'          => $toUtf($data['Faks'] ?? '') ?: null,
                        ':adres'         => $toUtf($data['Adres'] ?? $data['adres'] ?? '') ?: null,
                        ':harita'        => $toUtf($data['Harita'] ?? '') ?: null,
                        ':internal_id'   => trim($data['internalId'] ?? '') ?: null,
                        ':durum'         => $toUtf($data['_Durum'] ?? '') ?: null,
                        ':kaynak'        => 'bakanlik',
                        ':synced_at'     => $now,
                        ':created_at'    => $now,
                        ':updated_at'    => $now,
                    ]);
                    $toplam++;
                    if ($toplam % 1000 === 0) { echo "{$toplam} satir eklendi...\n"; flush(); }
                } catch (\Throwable $e) {
                    $hatali++;
                    if ($hatali <= 2) { echo "HATA ({$belgeNo}): " . $e->getMessage() . "\n"; flush(); }
                }
            }
            fclose($handle);
            echo "TAMAMLANDI! Toplam:{$toplam} Hatali:{$hatali}\n"; flush();
        }
        echo '</div>'; flush();
    } elseif ($action === 'csv-import') {
        echo '<div style="background:#000;color:#0f0;padding:1rem;font-family:monospace;white-space:pre-wrap;">'; flush();
        echo "CSV Import basladi...\n"; flush();
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $noTruncate = isset($_GET['no_truncate']);
        $csvFile = $basePath . '/storage/app/import/acenteler.csv';
        echo "Dosya: " . (file_exists($csvFile) ? 'BULUNDU' : 'YOK - ' . $csvFile) . "\n"; flush();
        if (!file_exists($csvFile)) {
            $output .= "HATA: CSV dosyasi bulunamadi: {$csvFile}\n";
            echo "HATA: Dosya yok.\n"; flush();
        } else {
            $DB = \Illuminate\Support\Facades\DB::getFacadeRoot();
            if (!$noTruncate) {
                $DB->statement('SET FOREIGN_KEY_CHECKS=0');
                $DB->table('acenteler')->truncate();
                $DB->statement('SET FOREIGN_KEY_CHECKS=1');
                echo "TRUNCATE OK.\n"; flush();
            }
            $handle = fopen($csvFile, 'r');
            $firstLine = fgets($handle); rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            $headers = fgetcsv($handle, 0, $delim);
            $headers = array_map(fn($h) => trim(ltrim($h, "\xEF\xBB\xBF\xE2\x80\x8B")), $headers);
            echo "Delimiter:{$delim} Kolonlar:" . count($headers) . "\n"; flush();
            // Baglanti charset'ini zorla
            $DB->statement('SET NAMES utf8mb4');
            // CSV Windows-1254 -> UTF-8 donusum (iconv ile)
            $toUtf = function($s) {
                $s = trim($s);
                if ($s === '') return '';
                if (mb_check_encoding($s, 'UTF-8')) return $s;
                $r = @iconv('windows-1254', 'UTF-8//IGNORE', $s);
                return ($r !== false && $r !== '') ? $r : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-9');
            };
            $toplam = $hatali = 0;
            $batch = [];
            $now = now()->toDateTimeString();
            while (($row = fgetcsv($handle, 0, $delim)) !== false) {
                if (count($row) < 2) continue;
                $norm = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
                $data = @array_combine($headers, $norm);
                if (!$data) { $hatali++; continue; }
                $belgeNo = trim($data['belgeNo'] ?? $data['Detay_BelgeNo'] ?? '');
                if (!$belgeNo) { $hatali++; continue; }
                $batch[] = [
                    'belge_no'      => $belgeNo,
                    'acente_unvani' => $toUtf($data['Detay_Unvan'] ?? '') ?: $toUtf($data['unvan'] ?? ''),
                    'ticari_unvan'  => $toUtf($data['Detay_TicariUnvan'] ?? '') ?: $toUtf($data['ticariUnvan'] ?? ''),
                    'grup'          => $toUtf($data['grup'] ?? ''),
                    'il'            => $toUtf($data['_Il'] ?? '') ?: $toUtf($data['ilAd'] ?? ''),
                    'il_ilce'       => $toUtf($data['Il_Ilce'] ?? ''),
                    'telefon'       => $toUtf($data['Detay_Telefon'] ?? '') ?: $toUtf($data['telefon'] ?? ''),
                    'eposta'        => trim($data['E-posta'] ?? ''),
                    'faks'          => $toUtf($data['Faks'] ?? '') ?: null,
                    'adres'         => $toUtf($data['Adres'] ?? $data['adres'] ?? '') ?: null,
                    'harita'        => $toUtf($data['Harita'] ?? '') ?: null,
                    'internal_id'   => trim($data['internalId'] ?? '') ?: null,
                    'durum'         => $toUtf($data['_Durum'] ?? '') ?: null,
                    'kaynak'        => 'bakanlik',
                    'synced_at'     => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
                $toplam++;
                if (count($batch) >= 500) {
                    try {
                        $DB->table('acenteler')->insert($batch);
                        echo $toplam . " satir islendi...\n"; flush();
                    } catch (\Throwable $e) {
                        $hatali += count($batch);
                        echo "BATCH HATA: " . $e->getMessage() . "\n"; flush();
                    }
                    $batch = [];
                }
            }
            if ($batch) {
                try {
                    $DB->table('acenteler')->insert($batch);
                } catch (\Throwable $e) {
                    $hatali += count($batch);
                    echo "SON BATCH HATA: " . $e->getMessage() . "\n"; flush();
                }
            }
            fclose($handle);
            $msg = "TAMAMLANDI! Toplam:{$toplam} Hatali:{$hatali}";
            $output .= $msg . "\n";
            echo $msg . "\n"; flush();
        }
        echo '</div>'; flush();
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

<a href="?key=<?= urlencode($providedKey) ?>&action=schedule-run" class="btn green">⚙️ Schedule Run (Test)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=email-force" class="btn red" onclick="return confirm('Email kampanyası FORCE çalıştırılacak. Onaylıyor musun?')">📧 Email Force Çalıştır</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=check-blade" class="btn blue">🔍 Check Blade (Kontrol)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=fix-route-names" class="btn red" onclick="return confirm('Route adlari duzeltilsin ve view cache temizlensin mi?')">🔧 Fix Route Names + Clear Views</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=clear-views" class="btn red" onclick="return confirm('Compiled view cache temizlensin mi?')">🗑️ Clear Views (Direkt)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=cron-log" class="btn blue">📋 Cron Log</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=log" class="btn blue">📋 Laravel Log (Son 100)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=status" class="btn blue">Migration Durumu</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=migrate" class="btn green" onclick="return confirm('Migration calistirilsin mi?')">Migrate</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=setup-dc-packages" class="btn green" onclick="return confirm('Dinner Cruise paketleri kurulsun mu? (alcoholfree guncelle + alcohol ekle)')">🚢 DC Paketleri Kur</a>
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
<a href="?key=<?= urlencode($providedKey) ?>&action=patch-csv-command" class="btn red" onclick="return confirm('BakanlikCsvImport.php guncellencek. Devam?')">🔧 Patch CSV Command</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=patch-controller" class="btn red" onclick="return confirm('TursabController a yeni metodlar eklenecek. Devam?')">🔧 Patch Controller</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=diagnose" class="btn blue">🔍 Diagnose</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=patch-routes" class="btn red" onclick="return confirm('Eksik kampanya routelari web.php e eklenecek. Devam?')">🔧 Patch Routes (Inline)</a>
<span style="color:#fff;font-size:.85rem;margin-left:4px;">GitHub Token:</span>
<input type="text" id="ghToken" placeholder="ghp_xxxx" style="padding:6px 10px;border-radius:6px;border:none;font-size:.85rem;width:220px;">
<a href="#" class="btn red" onclick="var t=document.getElementById('ghToken').value;if(!t){alert('Token giriniz!');return false;}if(!confirm('GitHub\'dan dosyalar indirilecek. Onaylıyor musunuz?'))return false;window.location='?key=<?= urlencode($providedKey) ?>&action=patch-navbar&branch=main&token='+encodeURIComponent(t);">🚨 Patch (GitHub'dan Çek)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=csv-import-pdo" class="btn red" onclick="return confirm('TRUNCATE + CSV import (PDO). Emin misin?')">🚀 CSV Import PDO (TRUNCATE)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=csv-import-pdo&no_truncate=1" class="btn green" onclick="return confirm('UpdateOrCreate (PDO). Devam?')">🚀 CSV Import PDO (UpdateOrCreate)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=csv-import" class="btn red" onclick="return confirm('Acenteler tablosu TRUNCATE edilecek ve CSV import calisacak. Emin misin?')">CSV Import (TRUNCATE + Import)</a>
<a href="?key=<?= urlencode($providedKey) ?>&action=csv-import&no_truncate=1" class="btn green" onclick="return confirm('Mevcut kayitlar korunarak CSV updateOrCreate yapilacak. Devam?')">CSV Import (UpdateOrCreate)</a>

<?php if ($output !== ''): ?>
<h3>Cikti:</h3>
<pre><?= htmlspecialchars(mb_convert_encoding($output, 'UTF-8', 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
<?php endif; ?>

<p style="color:#666;font-size:0.8rem;">
    PHP: <?= PHP_VERSION ?> |
    Laravel: <?= app()->version() ?> |
    <?= now()->format('d.m.Y H:i:s') ?>
</p>
</body>
</html>
