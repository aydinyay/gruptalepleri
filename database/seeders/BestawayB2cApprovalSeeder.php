<?php

namespace Database\Seeders;

use App\Models\B2C\B2cAgencySubscription;
use App\Models\TransferSupplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * BESTAWAY Tour'u B2C transfer tedarikçisi olarak onaylar.
 * Transfer aramasının sonuç döndürmesi için en az bir onaylı tedarikçi şarttır.
 */
class BestawayB2cApprovalSeeder extends Seeder
{
    public function run(): void
    {
        // BESTAWAY Tour kullanıcısını bul — ad veya şirket adıyla
        $user = User::where('name', 'like', '%BESTAWAY%')
            ->orWhere('name', 'like', '%Bestaway%')
            ->orWhere('company_name', 'like', '%BESTAWAY%')
            ->orWhere('company_name', 'like', '%Bestaway%')
            ->first();

        if (! $user) {
            $this->command->warn('BESTAWAY Tour kullanıcısı bulunamadı. Tüm kullanıcılar:');
            User::orderBy('id')->limit(20)->each(function (User $u): void {
                $this->command->line("  [{$u->id}] {$u->name} / " . ($u->company_name ?? '—'));
            });
            return;
        }

        $this->command->info("BESTAWAY Tour bulundu: [{$user->id}] {$user->name}");

        // Transfer tedarikçisi kaydını bul
        $supplier = TransferSupplier::where('user_id', $user->id)->first();

        if (! $supplier) {
            $this->command->warn("Kullanıcının transfer tedarikçi kaydı yok (user_id={$user->id}). Transfer onayı atlanıyor.");
        }

        // B2C aboneliği oluştur veya güncelle
        $subscription = B2cAgencySubscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'transfer_supplier_id' => $supplier?->id,
                'status'               => B2cAgencySubscription::STATUS_APPROVED,
                'service_types_json'   => ['transfer'],
                'approved_at'          => now(),
                'admin_note'           => 'Test acentesi — BestawayB2cApprovalSeeder tarafından otomatik onaylandı.',
            ]
        );

        $this->command->info(
            "B2C aboneliği " . ($subscription->wasRecentlyCreated ? 'oluşturuldu' : 'güncellendi') .
            " (id={$subscription->id}, status={$subscription->status})"
        );
    }
}
