<?php

namespace App\Services;

use App\Models\KullaniciBildirimi;
use App\Models\SistemAyar;
use App\Models\User;

class NotificationService
{
    /**
     * Belirli bir kullanıcıya bildirim oluştur.
     */
    public function createForUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
        ?int $broadcastId = null
    ): ?KullaniciBildirimi
    {
        if (! SistemAyar::pushEnabled()) {
            return null;
        }

        return KullaniciBildirimi::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'url'     => $url,
            'broadcast_id' => $broadcastId,
        ]);
    }

    /**
     * Belirtilen role sahip tüm kullanıcılara bildirim oluştur.
     */
    public function createForRole(
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
        ?int $broadcastId = null
    ): void
    {
        $users = $role === 'admin_and_superadmin'
            ? User::whereIn('role', ['admin', 'superadmin'])->get()
            : User::where('role', $role)->get();

        foreach ($users as $user) {
            $this->createForUser($user->id, $type, $title, $message, $url, $broadcastId);
        }
    }

    // ── Hazır olaylar ────────────────────────────────────────────────────────

    public function yeniAcente(string $companyTitle, string $name, string $phone): void
    {
        $this->createForRole(
            'admin_and_superadmin',
            'new_agency',
            'Yeni Acente Kaydı',
            "{$companyTitle} firması kayıt oldu. Yetkili: {$name} / {$phone}",
            route('superadmin.acenteler')
        );
    }

    public function yeniTalep(int $userId, string $gtpnr, string $agencyName, int $paxTotal, string $url): void
    {
        $this->createForRole(
            'admin_and_superadmin',
            'new_request',
            'Yeni Talep: ' . $gtpnr,
            "{$agencyName} — {$paxTotal} PAX",
            $url
        );
    }

    public function yeniLeisureBooking(string $gtpnr, string $agencyName, string $productType, float $amount, string $currency, string $adminUrl): void
    {
        $label  = $productType === 'yacht' ? 'Yat Kiralama' : 'Dinner Cruise';
        $amtFmt = number_format($amount, 0, ',', '.') . ' ' . $currency;
        $this->createForRole(
            'admin_and_superadmin',
            'new_leisure_booking',
            "Yeni {$label}: {$gtpnr}",
            "{$agencyName} — {$amtFmt} — Ödeme Bekliyor",
            $adminUrl
        );
    }

    public function durumDegisti(int $agencyUserId, string $gtpnr, string $eskiDurum, string $yeniDurum, string $url): void
    {
        $this->createForUser(
            $agencyUserId,
            'durum_degisti',
            'Talep Durumu Güncellendi',
            "{$gtpnr} talebinizin durumu güncellendi: {$yeniDurum}.",
            $url
        );
    }

    public function teklifEklendi(int $agencyUserId, string $gtpnr, string $airline, string $url): void
    {
        $this->createForUser(
            $agencyUserId,
            'offer_added',
            'Yeni Teklif Hazırlandı',
            "{$gtpnr} talebiniz için {$airline} teklifi hazırlandı.",
            $url
        );
    }

    public function teklifKabulEdildi(string $gtpnr, string $agencyName, string $airline, string $url): void
    {
        $this->createForRole(
            'admin_and_superadmin',
            'offer_accepted',
            'Teklif Kabul: ' . $gtpnr,
            "{$agencyName} — {$airline} teklifini kabul etti.",
            $url
        );
    }

    public function opsiyonUyarisi(string $gtpnr, string $airline, int $saatKaldi, string $url): void
    {
        $this->createForRole(
            'admin_and_superadmin',
            'opsiyon_uyarisi',
            "⚠️ Ödeme Vadesi: {$gtpnr}",
            "{$airline} — {$saatKaldi} saat sonra ödeme süresi doluyor!",
            $url
        );
    }

    public function yeniB2cQuickLead(string $name, string $phone, string $serviceType, string $url): void
    {
        $label = $serviceType ?: 'Belirtilmedi';
        $this->createForRole(
            'admin_and_superadmin',
            'b2c_quick_lead',
            '🌐 B2C — Yeni Lead: ' . $name,
            "{$name} / {$phone} — Hizmet: {$label}",
            $url
        );
    }
}
