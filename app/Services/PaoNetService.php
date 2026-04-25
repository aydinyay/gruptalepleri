<?php

namespace App\Services;

use App\Models\SistemAyar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaoNetService
{
    private string $wsUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->wsUrl  = rtrim(config('services.paonet.ws_url', ''), '/');
        $this->apiKey = (string) config('services.paonet.api_key', '');
    }

    // ── Guard ─────────────────────────────────────────────────────────────────

    private function guardApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('PAO-Net henüz aktif değil. API key bekleniyor.');
        }
    }

    // ── Token Yönetimi ────────────────────────────────────────────────────────

    public function getToken(): string
    {
        $this->guardApiKey();

        $expiry = (int) SistemAyar::get('paonet_token_expiry', 0);
        $token  = (string) SistemAyar::get('paonet_token', '');

        if ($token && $expiry > time() + 60) {
            return $token;
        }

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->post($this->wsUrl . '/api/Auth/GetToken', [
                'ApiKey' => $this->apiKey,
            ]);

        $data = $this->parseResponse($response, 'GETTOKEN');

        $newToken  = $data['Token']  ?? ($data['token']  ?? '');
        $newExpiry = $data['Expiry'] ?? ($data['expiry'] ?? 0);

        if (is_string($newExpiry) && strlen($newExpiry) > 10) {
            // timestamp string → unix
            $newExpiry = strtotime($newExpiry) ?: (time() + 3600);
        }

        SistemAyar::set('paonet_token', $newToken);
        SistemAyar::set('paonet_token_expiry', (string) $newExpiry);

        return $newToken;
    }

    // ── Müşteri Kontrol (CHK2CST) ─────────────────────────────────────────────

    public function musteriKontrol(string $kimlik, string $dogumTarihi): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/CHK2CST', [
                'Kimlik'      => $kimlik,
                'DogumTarihi' => $dogumTarihi,
            ]);

        return $this->parseResponse($response, 'CHK2CST');
    }

    // ── Teklif Al (NPN302 / NPN220) ────────────────────────────────────────────

    public function teklifAl(string $urunKodu, string $strMsg): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/' . $urunKodu, [
                'StrMsg' => $strMsg,
            ]);

        return $this->parseResponse($response, $urunKodu);
    }

    // ── Poliçe Üret (NPN999) ───────────────────────────────────────────────────

    public function policeUret(string $teklifId, string $paymentType = '99'): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/NPN999', [
                'TeklifId'    => $teklifId,
                'PaymentType' => $paymentType,
            ]);

        return $this->parseResponse($response, 'NPN999');
    }

    // ── Teklif Durum (PAOCHK1) ─────────────────────────────────────────────────

    public function teklifDurumu(string $teklifId): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/PAOCHK1', [
                'TeklifId' => $teklifId,
            ]);

        return $this->parseResponse($response, 'PAOCHK1');
    }

    // ── Üretim Durum (CHKAUTOPOLICY) ──────────────────────────────────────────

    public function uretimDurumu(string $referans): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/CHKAUTOPOLICY', [
                'Referans' => $referans,
            ]);

        return $this->parseResponse($response, 'CHKAUTOPOLICY');
    }

    // ── PDF Getir (GETPDFURL) ──────────────────────────────────────────────────

    public function pdfGetir(string $policeNo): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/GETPDFURL', [
                'PoliceNo' => $policeNo,
            ]);

        return $this->parseResponse($response, 'GETPDFURL');
    }

    // ── MBF (GETTRAPINFO) ─────────────────────────────────────────────────────

    public function mbfOlustur(array $params): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/GETTRAPINFO', $params);

        return $this->parseResponse($response, 'GETTRAPINFO');
    }

    // ── İptal Ekle (ADDCANCELPOLICY) ──────────────────────────────────────────

    public function iptalEkle(string $policeNo, string $mukerrerPoliceNo, string $iptalNedeni): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/ADDCANCELPOLICY', [
                'PoliceNo'        => $policeNo,
                'MukerrerPolice'  => $mukerrerPoliceNo,
                'IptalNedeni'     => $iptalNedeni,
            ]);

        return $this->parseResponse($response, 'ADDCANCELPOLICY');
    }

    // ── İptal Kontrol (CHECKCANCELPOLICY) ────────────────────────────────────

    public function iptalKontrol(string $policeNo): array
    {
        $this->guardApiKey();
        $token = $this->getToken();

        $response = Http::timeout(config('services.paonet.timeout', 30))
            ->withToken($token)
            ->post($this->wsUrl . '/api/Insurance/CHECKCANCELPOLICY', [
                'PoliceNo' => $policeNo,
            ]);

        return $this->parseResponse($response, 'CHECKCANCELPOLICY');
    }

    // ── PDF Stream (proxy — disk'e yazmaz) ────────────────────────────────────

    public function pdfStream(string $rawUrl): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->guardApiKey();

        $url = str_replace('\\', '/', $rawUrl);

        return response()->stream(function () use ($url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) {
                echo $data;
                return strlen($data);
            });
            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="police.pdf"',
        ]);
    }

    // ── Hata Çözümleyici ──────────────────────────────────────────────────────

    private function parseResponse(\Illuminate\Http\Client\Response $response, string $islem): array
    {
        if ($response->failed()) {
            $msg = $this->hataMesaji($response->status(), $response->body());
            Log::error("PAO-Net [{$islem}] HTTP hatası", ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException($msg);
        }

        $data = $response->json() ?? [];

        $errorCode = $data['ErrorCode'] ?? ($data['errorCode'] ?? null);
        if ($errorCode && (int) $errorCode !== 0) {
            $msg = $this->hataMesaji((int) $errorCode, $data['ErrorMessage'] ?? ($data['errorMessage'] ?? ''));
            Log::error("PAO-Net [{$islem}] API hatası", ['code' => $errorCode, 'data' => $data]);
            throw new \RuntimeException($msg);
        }

        return $data;
    }

    private function hataMesaji(int $kod, string $detay = ''): string
    {
        $mesajlar = [
            1001 => 'API erişim yetkisi reddedildi.',
            1002 => 'IP adresi whitelist\'te değil. Sunucu IP\'si PAO-Net\'e bildirilmeli.',
            1003 => 'Geçersiz API anahtarı.',
            1004 => 'Token süresi dolmuş. Yenileniyor...',
            1005 => 'Yetersiz cari bakiye.',
            1006 => 'Servis geçici olarak kullanılamıyor.',
            96   => 'Teklif bilgileri geçersiz veya eksik.',
            97   => 'Poliçe oluşturma başarısız. PAO-Net sistem hatası.',
            98   => 'Mükerrer poliçe bulunamadı. İptal için aktif bir poliçe gerekli.',
            99   => 'Sistem hatası. Lütfen tekrar deneyin.',
        ];

        return $mesajlar[$kod] ?? "PAO-Net hatası ({$kod}): {$detay}";
    }
}
