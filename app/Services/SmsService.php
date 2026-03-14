<?php

namespace App\Services;

use App\Models\RequestNotification;
use Illuminate\Support\Facades\Http;
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
    public function send(int $requestId, string $recipient, string $recipientName, string $phone, string $message): bool
    {
        $notification = RequestNotification::create([
            'request_id'    => $requestId,
            'channel'       => 'sms',
            'recipient'     => $recipient,
            'recipient_name'=> $recipientName,
            'phone'         => $phone,
            'message'       => $message,
            'status'        => 'pending',
        ]);

        try {
            $response = Http::timeout(15)->get('https://www.toplusmsyolla.com/smsgonder1N.php', [
                'kno'       => $this->kno,
                'kullanici' => $this->username,
                'sifre'     => $this->password,
                'gonderen'  => $this->originator,
                'mesaj'     => $message,
                'numaralar' => $phone,
                'tur'       => 'Normal',
            ]);

            $body = trim($response->body());
            // API başarılı döndüğünde genellikle sayısal bir kod döner (>0 başarı, <0 hata)
            $isSuccess = is_numeric($body) && (int)$body > 0;

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
     * Admin'e SMS gönder (.env SMS_ADMIN_PHONE)
     */
    public function sendToAdmin(int $requestId, string $message): bool
    {
        $adminPhone = config('services.sms.admin_phone');
        if (!$adminPhone) {
            return false;
        }
        return $this->send($requestId, 'admin', 'Admin', $adminPhone, $message);
    }
}
