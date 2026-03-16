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
        $this->kno = config('services.sms.kno');
        $this->username = config('services.sms.username');
        $this->password = config('services.sms.password');
        $this->originator = config('services.sms.originator');
    }

    /**
     * SMS gonder ve request_notifications tablosuna kaydet.
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
        if (! SistemAyar::smsEnabled()) {
            return false;
        }

        $notification = RequestNotification::create([
            'request_id' => $requestId,
            'channel' => 'sms',
            'recipient' => $recipient,
            'recipient_name' => $recipientName,
            'phone' => $phone,
            'message' => $message,
            'status' => $scheduledFor ? 'scheduled' : 'pending',
            'scheduled_for' => $scheduledFor,
        ]);

        // Zamanli ise simdi gonderme, scheduler komutu gonderecek.
        if ($scheduledFor) {
            return true;
        }

        return $this->gonder($notification);
    }

    /**
     * Zamanlanmis bekleyen SMS'leri gonderir.
     */
    public function sendScheduled(): void
    {
        if (! SistemAyar::smsEnabled()) {
            return;
        }

        $bekleyenler = RequestNotification::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($bekleyenler as $notification) {
            $this->gonder($notification);
        }
    }

    /**
     * Belirli bir olay icin SMS ayarlarindaki tum numaralara gonderir.
     * Zaman penceresi disindaysa bir sonraki acilis zamanina planlar.
     */
    public function sendByEvent(string $event, ?int $requestId, string $message): void
    {
        if (! SistemAyar::smsEnabled()) {
            return;
        }

        $scheduledFor = $this->zamanPenceresindeMi() ? null : $this->sonrakiPencereAcilis();
        if ($scheduledFor) {
            Log::info("SMS zaman penceresi disi, zamanlandi: {$event} -> {$scheduledFor->format('d.m.Y H:i')}");
        }

        $phones = SmsNotificationSetting::phonesForEvent($event);

        // Superadmin CC: her zaman superadmin telefonuna da gonder.
        $superadmin = \App\Models\User::where('role', 'superadmin')->whereNotNull('phone')->first();
        if ($superadmin?->phone && ! in_array($superadmin->phone, $phones, true)) {
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
     * Admin'e SMS gonder - geriye uyumluluk.
     */
    public function sendToAdmin(string $message, ?int $requestId = null): bool
    {
        $phone = config('services.sms.notify_phone');
        if (! $phone) {
            return false;
        }

        return $this->send($requestId, 'admin', 'Admin', $phone, $message);
    }

    /**
     * SMS servisinden kalan kredi bilgisini getirir.
     * Endpoint tanimli degilse guvenli fallback doner.
     */
    public function getBalance(): array
    {
        $url = config('services.sms.balance_url');
        if (! $url) {
            return [
                'available' => false,
                'balance' => null,
                'raw' => null,
                'message' => 'SMS bakiye endpointi tanimli degil.',
            ];
        }

        try {
            $timeout = (int) config('services.sms.balance_timeout', 10);
            $query = http_build_query([
                'kno' => $this->kno,
                'kul_ad' => $this->username,
                'kulad' => $this->username,
                'sifre' => $this->password,
            ]);

            $requestUrl = str_contains($url, '?') ? "{$url}&{$query}" : "{$url}?{$query}";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            $body = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $httpCode >= 400) {
                // Bazi hesaplarda POST beklenebildigi icin fallback.
                $payload = http_build_query([
                    'kno' => $this->kno,
                    'kul_ad' => $this->username,
                    'kulad' => $this->username,
                    'sifre' => $this->password,
                ]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

                $body = curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }

            if ($body === false) {
                return [
                    'available' => false,
                    'balance' => null,
                    'raw' => null,
                    'message' => $curlError ?: 'SMS bakiye sorgusu basarisiz.',
                ];
            }

            if ($httpCode >= 400) {
                return [
                    'available' => false,
                    'balance' => null,
                    'raw' => trim((string) $body),
                    'message' => "SMS bakiye sorgusu HTTP {$httpCode} hatasi verdi.",
                ];
            }

            $raw = trim((string) $body);
            $balance = $this->extractBalanceNumber($raw);

            if ($balance === null) {
                return [
                    'available' => false,
                    'balance' => null,
                    'raw' => $raw,
                    'message' => 'SMS bakiye cevabi parse edilemedi.',
                ];
            }

            return [
                'available' => true,
                'balance' => $balance,
                'raw' => $raw,
                'message' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('SMS bakiye sorgu hatasi: ' . $e->getMessage());
            return [
                'available' => false,
                'balance' => null,
                'raw' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

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

            $body = trim((string) $body);
            $isSuccess = str_contains($body, 'Gonderildi')
                || (is_numeric(explode(':', $body)[0] ?? null) && (int) (explode(':', $body)[0] ?? 0) > 0);

            $notification->update([
                'status' => $isSuccess ? 'sent' : 'failed',
                'provider_code' => $body,
                'sent_at' => $isSuccess ? now() : null,
            ]);

            return $isSuccess;
        } catch (\Throwable $e) {
            Log::error('SMS gonderme hatasi: ' . $e->getMessage());
            $notification->update([
                'status' => 'failed',
                'provider_code' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function zamanPenceresindeMi(): bool
    {
        $baslangic = SistemAyar::get('sms_baslangic_saat', '08:00');
        $bitis = SistemAyar::get('sms_bitis_saat', '21:00');
        $simdi = Carbon::now()->format('H:i');

        return $simdi >= $baslangic && $simdi <= $bitis;
    }

    private function sonrakiPencereAcilis(): Carbon
    {
        $baslangic = SistemAyar::get('sms_baslangic_saat', '08:00');
        [$saat, $dakika] = explode(':', $baslangic);

        $bugun = Carbon::today()->setHour((int) $saat)->setMinute((int) $dakika)->setSecond(0);

        // Eger bugunun acilis saati henuz gelmemisse bugun, gecmisse yarin.
        return $bugun->isFuture() ? $bugun : $bugun->addDay();
    }

    private function extractBalanceNumber(string $text): ?float
    {
        if (preg_match('/kalan\s*bakiye\s*=\s*(-?\d+(?:[.,]\d+)?)/i', $text, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        // Fallback: sadece saf sayi yaniti gelirse kabul et.
        $trimmed = trim($text);
        if (preg_match('/^-?\d+(?:[.,]\d+)?$/', $trimmed) !== 1) {
            return null;
        }

        return (float) str_replace(',', '.', $trimmed);
    }
}
