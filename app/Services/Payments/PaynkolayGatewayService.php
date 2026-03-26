<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaynkolayGatewayService
{
    /**
     * @return array{redirect_url:string,provider_reference:?string,response:array<string,mixed>}
     */
    public function initializePayment(
        string $clientReference,
        float $amountTry,
        string $successUrl,
        string $failUrl,
        ?string $cardHolderIp = null
    ): array {
        $baseUrl = rtrim((string) config('transfer.paynkolay.base_url'), '/');
        $sx = trim((string) config('transfer.paynkolay.sx'));
        $merchantSecretKey = trim((string) config('transfer.paynkolay.merchant_secret_key'));
        $environment = trim((string) config('transfer.paynkolay.environment', 'API'));
        $currencyNumber = trim((string) config('transfer.paynkolay.currency_number', '949'));
        $use3d = (bool) config('transfer.paynkolay.use_3d', true);
        $installment = max(1, (int) config('transfer.paynkolay.installment', 1));
        $createPath = trim((string) config('transfer.paynkolay.by_link_create_path', '/Vpos/by-link-create'));
        $timeout = max(5, (int) config('transfer.paynkolay.timeout', 25));

        if ($sx === '' || $merchantSecretKey === '' || $baseUrl === '') {
            if (! $this->isSimulationAllowed()) {
                throw new RuntimeException('Paynkolay credentials eksik. Canli ortamda simulation kullanilamaz.');
            }

            return [
                'redirect_url' => route('payment.paynkolay.simulate', [
                    'reference' => $clientReference,
                    'status' => 'paid',
                ]),
                'provider_reference' => null,
                'response' => [
                    'mode' => 'simulation',
                    'message' => 'Paynkolay credentials eksik oldugu icin simulation aktif.',
                ],
            ];
        }

        $amount = $this->formatAmount($amountTry);
        $rnd = now()->format('d-m-Y H:i:s');
        $customerKey = '';

        $payload = [
            'sx' => $sx,
            'clientRefCode' => $clientReference,
            'amount' => $amount,
            'successUrl' => $successUrl,
            'failUrl' => $failUrl,
            'installmentNo' => (string) $installment,
            'use3D' => $use3d ? 'true' : 'false',
            'transactionType' => 'SALES',
            'rnd' => $rnd,
            'environment' => $environment !== '' ? $environment : 'API',
            'currencyNumber' => $currencyNumber !== '' ? $currencyNumber : '949',
            'cardHolderIP' => $cardHolderIp ?: '127.0.0.1',
        ];

        $payload['hashDatav2'] = $this->requestHashForPayment(
            sx: $payload['sx'],
            clientRefCode: $payload['clientRefCode'],
            amount: $payload['amount'],
            successUrl: $payload['successUrl'],
            failUrl: $payload['failUrl'],
            rnd: $payload['rnd'],
            customerKey: $customerKey,
            merchantSecretKey: $merchantSecretKey,
        );

        $response = Http::timeout($timeout)
            ->asForm()
            ->acceptJson()
            ->post($baseUrl . $createPath, $payload);

        if (! $response->ok()) {
            throw new RuntimeException('Paynkolay by-link-create basarisiz (HTTP ' . $response->status() . ').');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Paynkolay response gecersiz.');
        }

        $responseCode = trim((string) ($json['RESPONSE_CODE'] ?? $json['response_code'] ?? ''));
        if ($responseCode !== '' && $responseCode !== '2') {
            $providerMessage = trim((string) (
                $json['RESPONSE_DATA']
                ?? $json['response_data']
                ?? $json['message']
                ?? 'Bilinmeyen hata'
            ));

            throw new RuntimeException(
                sprintf(
                    'Paynkolay by-link-create reddetti (code: %s): %s',
                    $responseCode,
                    $providerMessage !== '' ? $providerMessage : 'Bilinmeyen hata'
                )
            );
        }

        $redirectUrl = $this->extractRedirectUrl($json, $baseUrl);
        if ($redirectUrl === '') {
            $providerMessage = trim((string) (
                $json['RESPONSE_DATA']
                ?? $json['response_data']
                ?? $json['message']
                ?? ''
            ));

            throw new RuntimeException(
                'Paynkolay odeme linki donmedi.'
                . ($providerMessage !== '' ? ' Mesaj: ' . $providerMessage : '')
            );
        }

        $providerReference = trim((string) (
            $json['referenceCode']
            ?? $json['REFERENCE_CODE']
            ?? $json['transaction_id']
            ?? $json['provider_transaction_id']
            ?? ''
        ));

        return [
            'redirect_url' => $redirectUrl,
            'provider_reference' => $providerReference !== '' ? $providerReference : null,
            'response' => $json,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapCallbackStatus(array $payload): string
    {
        $responseCode = trim((string) ($this->payloadValue($payload, ['response_code', 'RESPONSE_CODE']) ?? ''));
        $authCode = trim((string) ($this->payloadValue($payload, ['auth_code', 'AUTH_CODE']) ?? ''));
        if ($responseCode !== '') {
            if ($responseCode === '2' && $authCode !== '' && $authCode !== '0') {
                return 'paid';
            }

            if ($responseCode === '0') {
                return 'failed';
            }
        }

        $status = Str::lower(trim((string) ($this->payloadValue($payload, ['status', 'payment_status']) ?? '')));

        return match ($status) {
            'paid', 'success', 'approved', 'ok' => 'paid',
            'failed', 'error', 'declined' => 'failed',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function isValidResponseHash(array $payload): bool
    {
        $receivedHash = trim((string) ($this->payloadValue($payload, ['hashDataV2', 'hashDatav2', 'HASHDATAV2']) ?? ''));
        if ($receivedHash === '') {
            return false;
        }

        $merchantSecretKey = trim((string) config('transfer.paynkolay.merchant_secret_key'));
        if ($merchantSecretKey === '') {
            return false;
        }

        $expectedHash = $this->responseHashFromPayload($payload, $merchantSecretKey);

        return hash_equals($expectedHash, $receivedHash);
    }

    public function requestHashForPayment(
        string $sx,
        string $clientRefCode,
        string $amount,
        string $successUrl,
        string $failUrl,
        string $rnd,
        string $customerKey,
        string $merchantSecretKey
    ): string {
        $hashString = implode('|', [
            $sx,
            $clientRefCode,
            $amount,
            $successUrl,
            $failUrl,
            $rnd,
            $customerKey,
            $merchantSecretKey,
        ]);

        $hash = mb_convert_encoding($hashString, 'UTF-8');
        $hashedBytes = hash('sha512', $hash, true);

        return base64_encode($hashedBytes);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function responseHashFromPayload(array $payload, string $merchantSecretKey): string
    {
        $merchantNo = trim((string) (
            $this->payloadValue($payload, ['merchant_no', 'MERCHANT_NO'])
            ?? config('transfer.paynkolay.merchant_no')
            ?? ''
        ));
        $referenceCode = trim((string) ($this->payloadValue($payload, ['reference_code', 'REFERENCE_CODE']) ?? ''));
        $authCode = trim((string) ($this->payloadValue($payload, ['auth_code', 'AUTH_CODE']) ?? ''));
        $responseCode = trim((string) ($this->payloadValue($payload, ['response_code', 'RESPONSE_CODE']) ?? ''));
        $use3d = trim((string) (
            $this->payloadValue($payload, ['use_3d', 'USE_3D'])
            ?? ((bool) config('transfer.paynkolay.use_3d', true) ? 'true' : 'false')
        ));
        $rnd = trim((string) ($this->payloadValue($payload, ['rnd', 'RND']) ?? ''));
        $installment = trim((string) (
            $this->payloadValue($payload, ['installment', 'INSTALLMENT'])
            ?? (string) max(1, (int) config('transfer.paynkolay.installment', 1))
        ));
        $authorizationAmount = trim((string) ($this->payloadValue($payload, ['authorization_amount', 'AUTHORIZATION_AMOUNT']) ?? ''));
        $currencyCode = trim((string) (
            $this->payloadValue($payload, ['currency_code', 'CURRENCY_CODE'])
            ?? (string) config('transfer.paynkolay.currency_number', '949')
        ));

        $hashString = implode('|', [
            $merchantNo,
            $referenceCode,
            $authCode,
            $responseCode,
            $use3d,
            $rnd,
            $installment,
            $authorizationAmount,
            $currencyCode,
            $merchantSecretKey,
        ]);

        $hash = mb_convert_encoding($hashString, 'UTF-8');
        $hashedBytes = hash('sha512', $hash, true);

        return base64_encode($hashedBytes);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  string[]  $keys
     */
    public function payloadValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $payload[$key];
            }
        }

        return null;
    }

    private function isSimulationAllowed(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    private function formatAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractRedirectUrl(array $response, string $baseUrl): string
    {
        $rawUrl = trim((string) (
            $response['url']
            ?? $response['URL']
            ?? $response['payment_url']
            ?? $response['redirect_url']
            ?? $response['redirectUrl']
            ?? ''
        ));

        if ($rawUrl !== '') {
            return $rawUrl;
        }

        $q = trim((string) ($response['q'] ?? $response['Q'] ?? ''));
        if ($q !== '') {
            return rtrim($baseUrl, '/') . '/Vpos/by-link?q=' . urlencode($q);
        }

        $formHtml = (string) ($response['FORM'] ?? $response['form'] ?? '');
        if ($formHtml !== '' && preg_match('/action=[\'"]([^\'"]+)[\'"]/i', $formHtml, $matches) === 1) {
            $actionUrl = trim((string) ($matches[1] ?? ''));
            if ($actionUrl !== '') {
                if (str_starts_with($actionUrl, 'http://') || str_starts_with($actionUrl, 'https://')) {
                    return $actionUrl;
                }

                return rtrim($baseUrl, '/') . '/' . ltrim($actionUrl, '/');
            }
        }

        return '';
    }
}

