<?php

namespace App\Services;

use App\Models\RequestNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.whatsapp.enabled', false);
    }

    public function sendPasswordResetLink(User $user, string $resetUrl): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $apiUrl = (string) config('services.whatsapp.api_url', '');
        if ($apiUrl === '') {
            Log::warning('WhatsApp reset mesaji atlandi: API URL tanimli degil.');
            return false;
        }

        $phone = $this->normalizePhone($user->phone ?? '');
        if (! $phone) {
            Log::warning('WhatsApp reset mesaji atlandi: kullanici telefonu gecersiz.', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        $message = "GrupTalepleri sifre sifirlama baglantiniz:\n{$resetUrl}\n\nBu baglanti kisa sure icinde gecersiz olur.";
        $token   = (string) config('services.whatsapp.api_token', '');
        $timeout = (int) config('services.whatsapp.timeout', 10);

        $payload = [
            'to'      => $phone,
            'channel' => 'whatsapp',
            'type'    => 'text',
            'message' => $message,
        ];

        try {
            $request = Http::timeout($timeout)->acceptJson();
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($apiUrl, $payload);
            $ok       = $response->successful();

            RequestNotification::create([
                'request_id'     => null,
                'channel'        => 'whatsapp',
                'recipient'      => 'acente',
                'recipient_name' => $user->name,
                'phone'          => $phone,
                'message'        => $message,
                'subject'        => 'Sifre Sifirlama',
                'status'         => $ok ? 'sent' : 'failed',
                'provider_code'  => $response->body(),
                'sent_at'        => $ok ? now() : null,
            ]);

            if (! $ok) {
                Log::warning('WhatsApp reset mesaji gonderilemedi.', [
                    'user_id' => $user->id,
                    'status'  => $response->status(),
                ]);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('WhatsApp reset mesaji hatasi: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);

            RequestNotification::create([
                'request_id'     => null,
                'channel'        => 'whatsapp',
                'recipient'      => 'acente',
                'recipient_name' => $user->name,
                'phone'          => $phone,
                'message'        => $message,
                'subject'        => 'Sifre Sifirlama',
                'status'         => 'failed',
                'provider_code'  => $e->getMessage(),
                'sent_at'        => null,
            ]);

            return false;
        }
    }

    private function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '90' . $digits;
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '9' . $digits;
        }

        if (! preg_match('/^90\d{10}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}

