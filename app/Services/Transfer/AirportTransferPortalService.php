<?php

namespace App\Services\Transfer;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AirportTransferPortalService
{
    private string $baseUrl;

    private ?string $apiKey;

    private ?string $partnerCode;

    private bool $enabled;

    private int $airportsCacheSeconds;

    private int $zonesCacheSeconds;

    public function __construct()
    {
        $config = (array) config('services.airport_transfer_portal', []);

        $this->baseUrl = rtrim((string) ($config['api_base_url'] ?? 'https://www.airporttransferportal.com'), '/');
        $this->apiKey = $this->normalizeString($config['api_key'] ?? null);
        $this->partnerCode = $this->normalizeString($config['partner_code'] ?? null);
        $this->enabled = (bool) ($config['enabled'] ?? true);
        $this->airportsCacheSeconds = max(60, (int) ($config['airports_cache_seconds'] ?? 21600));
        $this->zonesCacheSeconds = max(60, (int) ($config['zones_cache_seconds'] ?? 1800));
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function partnerCode(): ?string
    {
        return $this->partnerCode;
    }

    public function bookingBaseUrl(): string
    {
        return $this->baseUrl . '/booking';
    }

    /**
     * @return array<int, array{id:int,code:string,name:string,city:string,country:string,has_pricing:bool}>
     */
    public function airports(): array
    {
        return Cache::remember('atp:airports', $this->airportsCacheSeconds, function (): array {
            $response = $this->request()->get($this->baseUrl . '/api/public/airports');
            $this->ensureSuccess($response->status(), 'Havalimani listesi alinamadi.');

            $payload = $response->json();
            if (! is_array($payload)) {
                return [];
            }

            $mapped = [];
            foreach ($payload as $item) {
                if (! is_array($item) || empty($item['id']) || empty($item['code'])) {
                    continue;
                }

                $mapped[] = [
                    'id' => (int) $item['id'],
                    'code' => strtoupper(trim((string) ($item['code'] ?? ''))),
                    'name' => trim((string) ($item['name'] ?? '')),
                    'city' => trim((string) ($item['city'] ?? '')),
                    'country' => trim((string) ($item['country'] ?? '')),
                    'has_pricing' => (bool) ($item['hasPricing'] ?? true),
                ];
            }

            usort($mapped, static function (array $left, array $right): int {
                return strcmp($left['code'], $right['code']);
            });

            return $mapped;
        });
    }

    /**
     * @return array<int, array{id:int,name:string,city:string,country:string}>
     */
    public function zones(int $airportId): array
    {
        $cacheKey = 'atp:zones:' . $airportId;

        return Cache::remember($cacheKey, $this->zonesCacheSeconds, function () use ($airportId): array {
            $response = $this->request()->get($this->baseUrl . '/api/public/zones', [
                'airportId' => $airportId,
            ]);
            $this->ensureSuccess($response->status(), 'Bolge listesi alinamadi.');

            $payload = $response->json();
            if (! is_array($payload)) {
                return [];
            }

            $mapped = [];
            foreach ($payload as $item) {
                if (! is_array($item) || empty($item['id']) || empty($item['name'])) {
                    continue;
                }

                if (isset($item['isActive']) && ! $item['isActive']) {
                    continue;
                }

                $mapped[] = [
                    'id' => (int) $item['id'],
                    'name' => trim((string) ($item['name'] ?? '')),
                    'city' => trim((string) ($item['city'] ?? '')),
                    'country' => trim((string) ($item['country'] ?? '')),
                ];
            }

            usort($mapped, static function (array $left, array $right): int {
                return strcmp($left['name'], $right['name']);
            });

            return $mapped;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function search(array $payload): array
    {
        $token = $this->issueSearchToken();

        $response = $this->request()
            ->withHeaders(['x-search-token' => $token])
            ->post($this->baseUrl . '/api/public/search-transfers', $payload);

        $this->ensureSuccess($response->status(), 'Transfer aramasi basarisiz oldu.');

        $json = $response->json();
        return is_array($json) ? $json : [];
    }

    private function issueSearchToken(): string
    {
        $challengeResponse = $this->request()->get($this->baseUrl . '/api/public/search-token');
        $this->ensureSuccess($challengeResponse->status(), 'Transfer token challenge alinamadi.');

        $challengePayload = $challengeResponse->json();
        if (! is_array($challengePayload)) {
            throw new RuntimeException('Transfer token challenge verisi gecersiz.');
        }

        $challenge = trim((string) ($challengePayload['challenge'] ?? ''));
        $difficulty = max(1, (int) ($challengePayload['difficulty'] ?? 1));
        if ($challenge === '') {
            throw new RuntimeException('Transfer token challenge bos geldi.');
        }

        $nonce = $this->solveNonce($challenge, $difficulty);

        $verifyResponse = $this->request()->post($this->baseUrl . '/api/public/search-token', [
            'challenge' => $challenge,
            'nonce' => $nonce,
        ]);
        $this->ensureSuccess($verifyResponse->status(), 'Transfer token dogrulanamadi.');

        $verifyPayload = $verifyResponse->json();
        $token = is_array($verifyPayload) ? trim((string) ($verifyPayload['token'] ?? '')) : '';
        if ($token === '') {
            throw new RuntimeException('Transfer token bos dondu.');
        }

        return $token;
    }

    private function solveNonce(string $challenge, int $difficulty): string
    {
        $prefix = str_repeat('0', $difficulty);
        $maxIterations = 5_000_000;

        for ($nonce = 0; $nonce <= $maxIterations; $nonce++) {
            $hash = hash('sha256', $challenge . $nonce);
            if (str_starts_with($hash, $prefix)) {
                return (string) $nonce;
            }
        }

        throw new RuntimeException('Transfer token nonce cozulmedi.');
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()->timeout(30);

        if ($this->apiKey !== null) {
            $request = $request->withHeaders([
                'x-api-key' => $this->apiKey,
            ]);
        }

        return $request;
    }

    private function ensureSuccess(int $status, string $message): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new RuntimeException($message . ' (HTTP ' . $status . ')');
    }

    private function normalizeString(mixed $value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }
}
