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
     */
    public function send(?int $requestId, string $recipient, string $recipientName, string $phone, string $message): bool
    {
        $notification = RequestNotification::create([
            'request_id'     => $requestId,
            'channel'        => 'sms',
            'recipient'      => $recipient,
            'recipient_name' => $recipientName,
            'phone'          => $phone,
            'message'        => $message,
            'status'         => 'pending',
        ]);

        try {
            $xmlString = 'data=<sms>
<kno>' . $this->kno . '</kno>
<kulad>' . $this->username . '</kulad>
<sifre>' . $this->password . '</sifre>
<gonderen>' . $this->originator . '</gonderen>
<mesaj>' . $message . '</mesaj>
<numaralar>' . $phone . '</numaralar>
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
            $isSuccess = str_contains($body, 'Gonderildi') || (is_numeric(explode(':', $body)[0]) && (int) explode(':', $body)[0] > 0);

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

    /**
     * Belirli bir olay için SMS ayarlarındaki tüm numaralara gönder.
     * Zaman penceresi dışındaysa göndermez (admin bildirimleri için).
     */
    public function sendByEvent(string $event, ?int $requestId, string $message): void
    {
        if (!$this->zamanPenceresindeMi()) {
            Log::info("SMS zaman penceresi dışı, gönderilmedi: {$event}");
            return;
        }

        $phones = SmsNotificationSetting::phonesForEvent($event);

        if (empty($phones)) {
            $fallback = config('services.sms.notify_phone');
            if ($fallback) {
                $this->send($requestId, 'admin', 'Admin', $fallback, $message);
            }
            return;
        }

        foreach ($phones as $phone) {
            $this->send($requestId, 'admin', 'Admin', $phone, $message);
        }
    }

    /**
     * Şu anki saat SMS gönderme penceresinde mi?
     */
    private function zamanPenceresindeMi(): bool
    {
        $baslangic = SistemAyar::get('sms_baslangic_saat', '08:00');
        $bitis     = SistemAyar::get('sms_bitis_saat', '21:00');

        $simdi    = Carbon::now()->format('H:i');

        return $simdi >= $baslangic && $simdi <= $bitis;
    }

    /**
     * Admin'e SMS gönder — sendByEvent wrapper'ı (geriye uyumluluk).
     */
    public function sendToAdmin(?int $requestId = null, string $message): bool
    {
        $phone = config('services.sms.notify_phone');
        if (!$phone) return false;
        return $this->send($requestId, 'admin', 'Admin', $phone, $message);
    }
}
