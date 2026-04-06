<?php

namespace App\Services;

use App\Models\RequestNotification;
use App\Models\SistemAyar;
use App\Models\SistemOlaySablon;
use App\Models\SmsNotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $kno;
    private string $username;
    private string $password;
    private string $originator;

    public function __construct()
    {
        $this->kno = (string) config('services.sms.kno');
        $this->username = (string) config('services.sms.username');
        $this->password = (string) config('services.sms.password');
        $this->originator = (string) config('services.sms.originator');
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

        // Acente'ye gönderilen SMS'lerde bildirim_sms tercihini kontrol et
        if ($recipient === 'acente') {
            $acenteUser = \App\Models\User::where('phone', $phone)->first();
            if ($acenteUser && ($acenteUser->bildirim_sms ?? true) === false) {
                return false;
            }
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

        $result = $this->gonder($notification);

        // Admin SMS kopyası: acente'ye giden SMS'lerin kopyasını admin'e gönder.
        if ($recipient === 'acente' && SistemAyar::adminSmsCopyEnabled()) {
            $adminPhone = (string) config('services.sms.admin_phone');
            if ($adminPhone && $adminPhone !== $phone) {
                $kopya = RequestNotification::create([
                    'request_id'   => $requestId,
                    'channel'      => 'sms',
                    'recipient'    => 'admin',
                    'recipient_name' => 'Admin (Kopya)',
                    'phone'        => $adminPhone,
                    'message'      => $message,
                    'status'       => 'pending',
                    'scheduled_for' => null,
                ]);
                $this->gonder($kopya);
            }
        }

        return $result;
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
    public function sendByEvent(string $event, ?int $requestId, string $message, array $data = []): void
    {
        if (! SistemAyar::smsEnabled()) {
            return;
        }

        // DB'de özel SMS şablonu varsa kullan
        $ozelSms = SistemOlaySablon::resolveSms($event, $data);
        if ($ozelSms !== null) {
            $message = $ozelSms;
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
                $this->send($requestId, 'admin', 'Admin', (string) $fallback, $message, $scheduledFor);
            }
            return;
        }

        foreach ($phones as $phone) {
            $name = ($superadmin && $phone === $superadmin->phone) ? 'Superadmin' : 'Admin';
            $this->send($requestId, 'admin', $name, (string) $phone, $message, $scheduledFor);
        }
    }

    /**
     * Admin'e SMS gonder - geriye uyumluluk.
     */
    public function sendToAdmin(?int $requestId = null, string $message = ''): bool
    {
        $phone = config('services.sms.notify_phone');
        if (! $phone || $message === '') {
            return false;
        }

        return $this->send($requestId, 'admin', 'Admin', (string) $phone, $message);
    }

    /**
     * Kalan bakiye bilgisini kisa formatta doner.
     */
    public function getBalance(): array
    {
        $info = $this->getAccountInfo();
        return [
            'available' => $info['available'],
            'balance' => $info['balance'],
            'raw' => $info['raw'],
            'message' => $info['message'],
        ];
    }

    /**
     * Kullanici bilgi endpointinden bakiye ve birim fiyat gibi alanlari parse eder.
     */
    public function getAccountInfo(): array
    {
        $url = (string) config('services.sms.balance_url');
        if ($url === '') {
            return [
                'available' => false,
                'balance' => null,
                'unit_price' => null,
                'numbered_unit_price' => null,
                'header_unit_price' => null,
                'remaining_numbered_sms' => null,
                'remaining_header_sms' => null,
                'raw' => null,
                'message' => 'SMS bakiye endpointi tanimli degil.',
            ];
        }

        $response = $this->requestWithFallback($url, [
            'kno' => $this->kno,
            'kul_ad' => $this->username,
            'kulad' => $this->username,
            'sifre' => $this->password,
        ]);

        if (! $response['ok']) {
            return [
                'available' => false,
                'balance' => null,
                'unit_price' => null,
                'numbered_unit_price' => null,
                'header_unit_price' => null,
                'remaining_numbered_sms' => null,
                'remaining_header_sms' => null,
                'raw' => $response['raw'],
                'message' => $response['message'],
            ];
        }

        $raw = $response['raw'];
        $normalized = $this->normalizeText($raw);

        $balance = $this->extractNumberByPatterns($normalized, [
            '/kalan\s*bakiye\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
        ]);
        $numberedUnitPrice = $this->extractNumberByPatterns($normalized, [
            '/numarali\s*birim\s*fiyati\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
            '/numara\S*\s*birim\s*fiyat\S*\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
            '/sms\s*birim\s*fiyati\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
        ]);
        $headerUnitPrice = $this->extractNumberByPatterns($normalized, [
            '/baslikli\s*birim\s*fiyati\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
            '/ba\S*lik\S*\s*birim\s*fiyat\S*\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
        ]);
        $remainingNumberedSms = $this->extractNumberByPatterns($normalized, [
            '/numarali\s*kalan\s*sms\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
            '/numara\S*\s*kalan\s*sms\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
        ]);
        $remainingHeaderSms = $this->extractNumberByPatterns($normalized, [
            '/baslikli\s*kalan\s*sms\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
            '/ba\S*lik\S*\s*kalan\s*sms\s*=\s*(-?\d+(?:[.,]\d+)?)/i',
        ]);

        $allUnitPrices = $this->extractAllNumbersByPattern(
            $normalized,
            '/birim\s*fiyat\S*\s*=\s*(-?\d+(?:[.,]\d+)?)/i'
        );
        if ($numberedUnitPrice === null && isset($allUnitPrices[0])) {
            $numberedUnitPrice = $allUnitPrices[0];
        }
        if ($headerUnitPrice === null && isset($allUnitPrices[1])) {
            $headerUnitPrice = $allUnitPrices[1];
        }

        $allRemainingSms = $this->extractAllNumbersByPattern(
            $normalized,
            '/kalan\s*sms\s*=\s*(-?\d+(?:[.,]\d+)?)/i'
        );
        if ($remainingNumberedSms === null && isset($allRemainingSms[0])) {
            $remainingNumberedSms = $allRemainingSms[0];
        }
        if ($remainingHeaderSms === null && isset($allRemainingSms[1])) {
            $remainingHeaderSms = $allRemainingSms[1];
        }

        if ($balance === null) {
            return [
                'available' => false,
                'balance' => null,
                'unit_price' => $numberedUnitPrice ?? $headerUnitPrice,
                'numbered_unit_price' => $numberedUnitPrice,
                'header_unit_price' => $headerUnitPrice,
                'remaining_numbered_sms' => $remainingNumberedSms,
                'remaining_header_sms' => $remainingHeaderSms,
                'raw' => $raw,
                'message' => 'SMS bakiye cevabi parse edilemedi.',
            ];
        }

        return [
            'available' => true,
            'balance' => $balance,
            'unit_price' => $numberedUnitPrice ?? $headerUnitPrice,
            'numbered_unit_price' => $numberedUnitPrice,
            'header_unit_price' => $headerUnitPrice,
            'remaining_numbered_sms' => $remainingNumberedSms,
            'remaining_header_sms' => $remainingHeaderSms,
            'raw' => $raw,
            'message' => null,
        ];
    }

    /**
     * Gonderilmis SMS loglari icin teslim durumunu gunceller.
     */
    public function refreshDeliveryStatuses(int $limit = 100): array
    {
        $logs = RequestNotification::query()
            ->where('channel', 'sms')
            ->where('status', 'sent')
            ->where(function ($query): void {
                $query->whereNull('delivery_status')
                    ->orWhereIn('delivery_status', ['pending', 'unknown']);
            })
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();

        $result = [
            'checked' => 0,
            'updated' => 0,
            'delivered' => 0,
            'undelivered' => 0,
            'pending' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($logs as $log) {
            $result['checked']++;

            $queryResult = $this->queryDeliveryByNotification($log);
            if (! $queryResult['available']) {
                $result['errors']++;
                continue;
            }
            if ($queryResult['delivery_status'] === null) {
                $result['skipped']++;
                continue;
            }

            $newStatus = $queryResult['delivery_status'];
            $updates = ['delivery_status' => $newStatus];
            if ($newStatus === 'delivered' && $log->delivered_at === null) {
                $updates['delivered_at'] = now();
            }

            $changed = $log->delivery_status !== $newStatus || isset($updates['delivered_at']);
            if ($changed) {
                $log->update($updates);
                $result['updated']++;
            }

            if ($newStatus === 'delivered') {
                $result['delivered']++;
            } elseif ($newStatus === 'undelivered') {
                $result['undelivered']++;
            } else {
                $result['pending']++;
            }
        }

        return $result;
    }

    /**
     * Tek bir log kaydi icin provider'dan teslim bilgisini ceker.
     */
    public function queryDeliveryByNotification(RequestNotification $notification): array
    {
        $ozelkod = $this->extractOzelkod($notification->provider_code);
        if ($ozelkod === null) {
            return [
                'available' => false,
                'delivery_status' => null,
                'raw' => null,
                'message' => 'Ozel kod bulunamadi.',
            ];
        }

        return $this->queryDeliveryByOzelkod($ozelkod);
    }

    /**
     * Ozel kod ile smsrapor endpointinden teslim sonucunu getirir.
     */
    public function queryDeliveryByOzelkod(string $ozelkod): array
    {
        $url = (string) config('services.sms.delivery_report_url', 'http://www.toplusmsyolla.com/smsrapor.php');
        if ($url === '') {
            return [
                'available' => false,
                'delivery_status' => null,
                'raw' => null,
                'message' => 'SMS durum endpointi tanimli degil.',
            ];
        }

        $response = $this->httpGet($url, [
            'kno' => $this->kno,
            'kul_ad' => $this->username,
            'kulad' => $this->username,
            'sifre' => $this->password,
            'ozelkod' => $ozelkod,
        ]);

        if (! $response['ok']) {
            return [
                'available' => false,
                'delivery_status' => null,
                'raw' => $response['raw'],
                'message' => $response['message'],
            ];
        }

        $raw = $response['raw'];
        $normalized = $this->normalizeText($raw);

        if (str_contains($normalized, 'ozel kod bulunamadi')) {
            return [
                'available' => false,
                'delivery_status' => null,
                'raw' => $raw,
                'message' => 'Ozel kod bulunamadi.',
            ];
        }

        $status = $this->detectDeliveryStatus($normalized);
        return [
            'available' => true,
            'delivery_status' => $status,
            'raw' => $raw,
            'message' => null,
        ];
    }

    private function gonder(RequestNotification $notification): bool
    {
        try {
            $originatorCheck = $this->ensureOriginatorApproved();
            if (! $originatorCheck['ok']) {
                $notification->update([
                    'status' => 'failed',
                    'provider_code' => $originatorCheck['message'] ?? 'Originator onaysiz.',
                ]);
                return false;
            }

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
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                $notification->update([
                    'status' => 'failed',
                    'provider_code' => $curlError ?: 'SMS gonderim hatasi',
                ]);
                return false;
            }

            $body = trim((string) $body);
            $isSuccess = str_contains($this->normalizeText($body), 'gonderildi')
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

    private function ensureOriginatorApproved(): array
    {
        if ($this->originator === '') {
            return ['ok' => false, 'message' => 'Originator bos olamaz.'];
        }

        $strict = (bool) config('services.sms.strict_originator_check', false);

        $cacheKey = 'sms:originator:' . md5($this->kno . '|' . $this->username . '|' . $this->originator . '|' . (int) $strict);
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($strict): array {
            $url = (string) config('services.sms.originator_list_url', 'http://www.toplusmsyolla.com/orjinatorliste.php');
            if ($url === '') {
                return ['ok' => true, 'message' => null];
            }

            $response = $this->httpGet($url, [
                'kno' => $this->kno,
                'kulad' => $this->username,
                'sifre' => $this->password,
            ]);

            // Originator liste servisi ulasilamazsa akisi durdurma.
            if (! $response['ok']) {
                return ['ok' => true, 'message' => null];
            }

            $normalizedList = $this->normalizeText($response['raw']);
            $normalizedOriginator = $this->normalizeText($this->originator);

            if ($normalizedOriginator !== '' && str_contains($normalizedList, $normalizedOriginator)) {
                return ['ok' => true, 'message' => null];
            }

            if (! $strict) {
                Log::warning('SMS originator listesinde tam eslesme bulunamadi, strict kapali oldugu icin gonderime izin verildi.', [
                    'originator' => $this->originator,
                ]);
                return ['ok' => true, 'message' => null];
            }

            return [
                'ok' => false,
                'message' => "Originator onaysiz veya listede yok: {$this->originator}",
            ];
        });
    }

    private function requestWithFallback(string $url, array $params): array
    {
        $getResult = $this->httpGet($url, $params);
        if ($getResult['ok']) {
            return $getResult;
        }

        return $this->httpPost($url, $params);
    }

    private function httpGet(string $url, array $params): array
    {
        $timeout = (int) config('services.sms.balance_timeout', 10);
        $query = http_build_query($params);
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

        if ($body === false) {
            return ['ok' => false, 'raw' => null, 'message' => $curlError ?: 'HTTP GET hatasi'];
        }

        if ($httpCode >= 400) {
            return ['ok' => false, 'raw' => trim((string) $body), 'message' => "HTTP {$httpCode} hatasi"];
        }

        return ['ok' => true, 'raw' => trim((string) $body), 'message' => null];
    }

    private function httpPost(string $url, array $params): array
    {
        $timeout = (int) config('services.sms.balance_timeout', 10);
        $payload = http_build_query($params);

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

        if ($body === false) {
            return ['ok' => false, 'raw' => null, 'message' => $curlError ?: 'HTTP POST hatasi'];
        }

        if ($httpCode >= 400) {
            return ['ok' => false, 'raw' => trim((string) $body), 'message' => "HTTP {$httpCode} hatasi"];
        }

        return ['ok' => true, 'raw' => trim((string) $body), 'message' => null];
    }

    private function extractOzelkod(?string $providerCode): ?string
    {
        if ($providerCode === null || trim($providerCode) === '') {
            return null;
        }

        // Beklenen formatlar: "1:123456:Gonderildi:..." veya "1:ABC123:..."
        if (preg_match('/^\s*\d+\s*:\s*([A-Za-z0-9_-]+)\s*:/', $providerCode, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/ozelkod\s*[=:]\s*([A-Za-z0-9_-]+)/i', $providerCode, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function detectDeliveryStatus(string $normalized): ?string
    {
        // 0=Bekliyor, 1=Ulasti, 2=Ulasamadi gibi map.
        if (preg_match_all('/[:=]\s*([012])(?=(?:\D|$))/i', $normalized, $matches) > 0) {
            $codes = array_unique($matches[1]);
            sort($codes);
            if ($codes === ['1']) {
                return 'delivered';
            }
            if ($codes === ['2']) {
                return 'undelivered';
            }
            return 'pending';
        }

        if (str_contains($normalized, 'ulasti') || str_contains($normalized, 'teslim')) {
            return 'delivered';
        }
        if (str_contains($normalized, 'ulasamadi') || str_contains($normalized, 'iletilemedi')) {
            return 'undelivered';
        }
        if (str_contains($normalized, 'bekliyor')) {
            return 'pending';
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower(strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        return strtr($text, [
            'ı' => 'i',
            'İ' => 'i',
            'ş' => 's',
            'Ş' => 's',
            'ğ' => 'g',
            'Ğ' => 'g',
            'ü' => 'u',
            'Ü' => 'u',
            'ö' => 'o',
            'Ö' => 'o',
            'ç' => 'c',
            'Ç' => 'c',
            'ý' => 'i',
            'þ' => 's',
            'ð' => 'g',
        ]);
    }

    private function extractNumberByPatterns(string $text, array $patterns): ?float
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return (float) str_replace(',', '.', $matches[1]);
            }
        }

        return null;
    }

    private function extractAllNumbersByPattern(string $text, string $pattern): array
    {
        if (preg_match_all($pattern, $text, $matches) < 1 || empty($matches[1])) {
            return [];
        }

        return array_map(
            static fn (string $value): float => (float) str_replace(',', '.', $value),
            $matches[1]
        );
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
}
