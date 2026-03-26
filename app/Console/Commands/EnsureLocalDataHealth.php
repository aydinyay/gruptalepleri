<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnsureLocalDataHealth extends Command
{
    protected $signature = 'db:ensure-local-health
                            {--import-on-empty : Requests tablosu boşsa legacy CSV import başlat}
                            {--min-requests=1 : Sağlıklı kabul için minimum request sayısı}';

    protected $description = 'Local ortamda veri sağlığını kontrol eder; boş DB durumunda otomatik toparlamaya yardımcı olur.';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->line('Ortam local değil, kontrol atlandı.');
            return self::SUCCESS;
        }

        $counts = [
            'users' => DB::table('users')->count(),
            'requests' => DB::table('requests')->count(),
            'offers' => DB::table('offers')->count(),
        ];

        $this->info(sprintf(
            'Local DB durumu: users=%d, requests=%d, offers=%d',
            $counts['users'],
            $counts['requests'],
            $counts['offers']
        ));

        $this->ensureLocalAdmin();

        $minRequests = max(0, (int) $this->option('min-requests'));
        if ($counts['requests'] >= $minRequests) {
            return self::SUCCESS;
        }

        $this->warn("Requests sayısı eşik altında ({$counts['requests']} < {$minRequests}).");

        if (! $this->option('import-on-empty')) {
            $this->line('Otomatik import kapalı. --import-on-empty ile çalıştırabilirsiniz.');
            return self::SUCCESS;
        }

        if ($counts['requests'] > 0) {
            $this->line('Requests tamamen boş değil; otomatik import tetiklenmedi.');
            return self::SUCCESS;
        }

        $legacyFile = storage_path('app/legacy_import.csv');
        if (! is_file($legacyFile)) {
            $this->error("Legacy CSV bulunamadı: {$legacyFile}");
            return self::FAILURE;
        }

        $this->warn('Requests boş bulundu. legacy:import otomatik başlatılıyor...');
        $exit = $this->call('legacy:import');
        if ($exit !== self::SUCCESS) {
            $this->error('legacy:import başarısız oldu.');
            return self::FAILURE;
        }

        $this->info('Otomatik toparlama tamamlandı.');

        return self::SUCCESS;
    }

    private function ensureLocalAdmin(): void
    {
        $email = env('LOCAL_ADMIN_EMAIL', 'admin@gruptalepleri.com');
        $password = env('LOCAL_ADMIN_PASSWORD', 'admin123');

        $user = User::where('email', $email)->first();
        if ($user) {
            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
            'can_send_broadcast' => true,
        ]);

        $this->warn("Local admin oluşturuldu: {$email}");
    }
}

