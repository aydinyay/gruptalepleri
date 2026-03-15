<?php

namespace App\Services;

use App\Models\RequestNotification;
use App\Models\SistemAyar;
use App\Models\SmsNotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $kno;
    private string $username;
    private string $password;
    private string $originator;

    public function __construct()
    {
        $this->kno        = config('services.sms.kno');
        $this->username   = config('services.sms.username');
        $this->password   = config('services.sms.password');
        $this->originator = config('services.sms.originator');
    }

    /**
     * SMS gönder ve request_notifications tablosuna kaydet.
     * $scheduledFor verilirse o zaman kadar bekletilir.
     */
    public function send(
        ?int $requestId,
        string $recipient,
        string $recipientName,
        string $phone,
        string $message,
        ?Carbon $scheduledFor = null
    ): bool {
        $notification = RequestNotification::create([
            'request_id'     => $requestId,
            'channel'        => 'sms',
            'recipient'      => $recipient,
            'recipient_name' => $recipientName,
            'phone'          => $phone,
            'message'        => $message,
            'status'         => $scheduledFor ? 'scheduled' : 'pending',
            'scheduled_for'  => $scheduledFor,
        ]);

        // Zamanlı ise şimdi gönderme — SendScheduledSms komutu üstlenir
        if ($scheduledFor) {
            return true;
        }

        return $this->gonder($notification);
    }

    /**
     * Zamanlanmış bekleyen SMS'leri gönder (scheduler tarafından çağrılır).
     */
    public function sendScheduled(): void
    {
        $bekleyenler = RequestNotification::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($bekleyenler as $notification) {
            $this->gonder($notification);
        }
    }

    /**
     * Belirli bir olay için SMS ayarlarındaki tüm numaralara gönder.
     * Zaman penceresi dışındaysa bir sonraki açılış zamanına zamanlar.
     * Superadmin her zaman CC alır.
     */
    public function sendByEvent(string $event, ?int $requestId, string $message): void
    {
        $scheduledFor = $this->zamanPenceresindeMi() ? null : $this->sonrakiPencereAcilis();

        if ($scheduledFor) {
            Log::info("SMS zaman penceresi dışı, zamanlandı: {$event} → {$scheduledFor->format('d.m.Y H:i')}");
        }

        $phones = SmsNotificationSetting::phonesForEvent($event);

        // Superadmin CC: her zaman superadmin telefona da gönder
        $superadmin = \App\Models\User::where('role', 'superadmin')->whereNotNull('phone')->first();
        if ($superadmin?->phone && !in_array($superadmin->phone, $phones)) {
            $phones[] = $superadmin->phone;
        }

        if (empty($phones)) {
            $fallback = config('services.sms.notify_phone');
            if ($fallback) {
                $this->send($requestId, 'admin', 'Admin', $fallback, $message, $scheduledFor);
            }
            return;
        }

        foreach ($phones as $phone) {
            $name = ($superadmin && $phone === $superadmin->phone) ? 'Superadmin' : 'Admin';
            $this->send($requestId, 'admin', $name, $phone, $message, $scheduledFor);
        }
    }

    /**
     * Admin'e SMS gönder — geriye uyumluluk.
     */
    public function sendToAdmin(?int $requestId = null, string $message): bool
    {
        $phone = config('services.sms.notify_phone');
        if (!$phone) return false;
        return $this->send($requestId, 'admin', 'Admin', $phone, $message);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function gonder(RequestNotification $notification): bool
    {
        try {
            $xmlString = 'data=<sms>
<kno>' . $this->kno . '</kno>
<kulad>' . $this->username . '</kulad>
<sifre>' . $this->password . '</sifre>
<gonderen>' . $this->originator . '</gonderen>
<mesaj>' . $notification->message . '</mesaj>
<numaralar>' . $notification->phone . '</numaralar>
<tur>Normal</tur>
</sms>';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://www.toplusmsyolla.com/smsgonder1Npost.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $body = curl_exec($ch);
            curl_close($ch);

            $body      = trim($body);
            $isSuccess = str_contains($body, 'Gonderildi')
                || (is_numeric(explode(':', $body)[0]) && (int) explode(':', $body)[0] > 0);

            $notification->update([
                'status'        => $isSuccess ? 'sent' : 'failed',
                'provider_code' => $body,
                'sent_at'       => $isSuccess ? now() : null,
            ]);

            return $isSuccess;
        } catch (\Exception $e) {
            Log::error('SMS gönderme hatası: ' . $e->getMessage());
            $notification->update([
                'status'        => 'failed',
                'provider_code' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function zamanPenceresindeMi(): bool
    {
        $baslangic = SistemAyar::get('sms_baslangic_saat', '08:00');
        $bitis     = SistemAyar::get('sms_bitis_saat', '21:00');
        $simdi     = Carbon::now()->format('H:i');

        return $simdi >= $baslangic && $simdi <= $bitis;
    }

    private function sonrakiPencereAcilis(): Carbon
    {
        $baslangic = SistemAyar::get('sms_baslangic_saat', '08:00');
        [$saat, $dakika] = explode(':', $baslangic);

        $bugun = Carbon::today()->setHour((int)$saat)->setMinute((int)$dakika)->setSecond(0);

        // Eğer bugünün açılış saati henüz gelmemişse bugün kullan, geçmişse yarın
        return $bugun->isFuture() ? $bugun : $bugun->addDay();
    }
}
