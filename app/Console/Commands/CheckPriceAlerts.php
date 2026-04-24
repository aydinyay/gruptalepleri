<?php

namespace App\Console\Commands;

use App\Models\B2C\B2cPriceAlert;
use App\Services\EmailService;
use Illuminate\Console\Command;

class CheckPriceAlerts extends Command
{
    protected $signature   = 'gr:check-price-alerts';
    protected $description = 'Fiyat alarmı kayıtlı ürünlerin fiyatını kontrol eder, değişimde bildirim gönderir.';

    public function handle(EmailService $email): int
    {
        $alerts = B2cPriceAlert::with('item')
            ->whereNotNull('catalog_item_id')
            ->get();

        $notified = 0;

        foreach ($alerts as $alert) {
            $item = $alert->item;
            if (! $item) continue;

            $currentPrice = (float) ($item->base_price ?? 0);
            $savedPrice   = (float) ($alert->price_at_subscription ?? 0);

            if ($savedPrice <= 0 || $currentPrice <= 0) continue;
            if ($currentPrice >= $savedPrice) continue; // sadece fiyat düşünce bildir

            // Son bildirimden bu yana en az 24 saat geçmiş mi?
            if ($alert->last_notified_at && $alert->last_notified_at->diffInHours(now()) < 24) continue;

            $to = $alert->email;
            if (! $to) continue;

            $subject = "Fiyat Düştü: {$item->title}";
            $body    = "Merhaba,\n\n"
                . "Takip ettiğiniz ürünün fiyatı düştü:\n\n"
                . "📦 {$item->title}\n"
                . "💰 Eski fiyat: " . number_format($savedPrice, 0, ',', '.') . " {$item->currency}\n"
                . "🎉 Yeni fiyat: " . number_format($currentPrice, 0, ',', '.') . " {$item->currency}\n\n"
                . "Hemen incele: " . rtrim(config('app.url'), '/') . "/urun/{$item->slug}\n\n"
                . "GrupRezervasyonları";

            try {
                $name = $alert->b2c_user_id
                    ? (\App\Models\B2C\B2cUser::find($alert->b2c_user_id)?->name ?? 'Değerli Üye')
                    : 'Değerli Üye';
                $email->fiyatAlarmi($to, $name, $item->title, $savedPrice, $currentPrice, $item->currency ?? 'TRY', $item->slug);
                $alert->update([
                    'last_notified_at'      => now(),
                    'price_at_subscription' => $currentPrice,
                ]);
                $notified++;
            } catch (\Throwable $e) {
                $this->error("E-posta gönderilemedi: {$to} — " . $e->getMessage());
            }
        }

        $this->info("Kontrol edildi: {$alerts->count()} alarm, {$notified} bildirim gönderildi.");
        return self::SUCCESS;
    }
}
