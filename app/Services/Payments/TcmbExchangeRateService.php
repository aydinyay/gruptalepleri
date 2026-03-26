<?php

namespace App\Services\Payments;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class TcmbExchangeRateService
{
    /**
     * @return array{
     *   source_currency:string,
     *   source_amount:float,
     *   charged_try_amount:float,
     *   fx_rate:float,
     *   fx_timestamp:CarbonImmutable
     * }
     */
    public function convertToTry(float $sourceAmount, string $sourceCurrency): array
    {
        $normalizedCurrency = strtoupper(trim($sourceCurrency));
        $normalizedCurrency = $normalizedCurrency === '' ? 'TRY' : $normalizedCurrency;

        if ($normalizedCurrency === 'TRY' || $normalizedCurrency === 'TL') {
            return [
                'source_currency' => 'TRY',
                'source_amount' => round($sourceAmount, 2),
                'charged_try_amount' => round($sourceAmount, 2),
                'fx_rate' => 1.0,
                'fx_timestamp' => CarbonImmutable::now(),
            ];
        }

        $rates = $this->todayRates();
        $rate = (float) ($rates[$normalizedCurrency] ?? 0.0);
        if ($rate <= 0) {
            throw new RuntimeException('TCMB kur bilgisi bulunamadi: ' . $normalizedCurrency);
        }

        return [
            'source_currency' => $normalizedCurrency,
            'source_amount' => round($sourceAmount, 2),
            'charged_try_amount' => round($sourceAmount * $rate, 2),
            'fx_rate' => $rate,
            'fx_timestamp' => CarbonImmutable::now(),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function todayRates(): array
    {
        $cacheKey = 'payments:tcmb:rates:' . now()->format('Y-m-d');
        $ttlSeconds = max(60, (int) config('services.tcmb.cache_seconds', 600));

        return Cache::remember($cacheKey, $ttlSeconds, function (): array {
            $baseUrl = (string) config('services.tcmb.url', 'https://www.tcmb.gov.tr/kurlar/today.xml');
            $timeout = max(5, (int) config('services.tcmb.timeout', 15));

            $response = Http::timeout($timeout)->get($baseUrl);
            if (! $response->ok()) {
                throw new RuntimeException('TCMB kur servisine ulasilamadi (HTTP ' . $response->status() . ').');
            }

            $xml = @simplexml_load_string((string) $response->body());
            if (! $xml instanceof SimpleXMLElement) {
                throw new RuntimeException('TCMB kur XML verisi parse edilemedi.');
            }

            $rates = [];
            foreach ($xml->Currency as $currencyNode) {
                $currencyCode = strtoupper(trim((string) $currencyNode['CurrencyCode']));
                if ($currencyCode === '') {
                    continue;
                }

                $rawRate = $this->firstNonEmptyRate([
                    (string) $currencyNode->ForexSelling,
                    (string) $currencyNode->BanknoteSelling,
                    (string) $currencyNode->ForexBuying,
                    (string) $currencyNode->BanknoteBuying,
                ]);

                if ($rawRate === null) {
                    continue;
                }

                $rate = (float) str_replace(',', '.', $rawRate);
                if ($rate > 0) {
                    $rates[$currencyCode] = $rate;
                }
            }

            return $rates;
        });
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private function firstNonEmptyRate(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $normalized = trim($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }
}

