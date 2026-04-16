<?php

namespace Database\Seeders;

use App\Models\B2C\B2cAgencySubscription;
use App\Models\TransferSupplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * BESTAWAY Tour'u B2C transfer tedarikçisi olarak onaylar.
 * Transfer aramasının sonuç döndürmesi için en az bir onaylı tedarikçi şarttır.
 */
class BestawayB2cApprovalSeeder extends Seeder
{
    public function run(): void
    {
        // TransferSupplier tablosunda ara — User'da company_name yok
        $supplier = TransferSupplier::where('company_name', 'like', '%BESTAWAY%')
            ->orWhere('company_name', 'like', '%Bestaway%')
            ->orWhere('company_name', 'like', '%bestaway%')
            ->first();

        if (! $supplier) {
            // company_name bulunamazsa onaylı tedarikçileri listele
            $all = TransferSupplier::where('is_approved', true)->get(['id', 'company_name', 'user_id']);
            $msg = 'BESTAWAY Tour tedarikçisi bulunamadı. Onaylı tedarikçiler: '
                . $all->pluck('company_name')->implode(', ');
            Log::warning($msg);
            $this->command?->warn($msg);
            return;
        }

        $this->command?->info("Tedarikçi bulundu: [{$supplier->id}] {$supplier->company_name}");

        if (! $supplier->user_id) {
            $msg = "Tedarikçiye bağlı kullanıcı yok (supplier_id={$supplier->id}).";
            Log::warning($msg);
            $this->command?->warn($msg);
            return;
        }

        $subscription = B2cAgencySubscription::updateOrCreate(
            ['user_id' => $supplier->user_id],
            [
                'transfer_supplier_id' => $supplier->id,
                'status'               => B2cAgencySubscription::STATUS_APPROVED,
                'service_types_json'   => ['transfer'],
                'approved_at'          => now(),
                'admin_note'           => 'Test acentesi — BestawayB2cApprovalSeeder',
            ]
        );

        $action = $subscription->wasRecentlyCreated ? 'oluşturuldu' : 'güncellendi';
        $msg    = "B2C aboneliği {$action}: id={$subscription->id}, status={$subscription->status}";
        Log::info($msg);
        $this->command?->info($msg);
    }
}
