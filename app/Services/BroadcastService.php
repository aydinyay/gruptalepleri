<?php

namespace App\Services;

use App\Models\BroadcastNotification;
use App\Models\User;

class BroadcastService
{
    /**
     * Broadcast'i seçili kanallara gönder.
     * channels: ['push', 'sms', 'email'] — en az biri olmalı.
     */
    public function send(BroadcastNotification $broadcast): void
    {
        $kullanicilar = $this->hedefKullanicilari($broadcast);
        $channels     = $broadcast->channels ?? ['push'];

        $ns    = new NotificationService();
        $sms   = new SmsService();
        $email = new EmailService();

        $pushTitle = ($broadcast->emoji ? $broadcast->emoji . ' ' : '') . $broadcast->title;

        foreach ($kullanicilar as $user) {
            // Push bildirimi (uygulama içi bildirim zili)
            if (in_array('push', $channels)) {
                $ns->createForUser(
                    $user->id,
                    'broadcast',
                    $pushTitle,
                    $broadcast->message,
                    null
                );
            }

            // SMS — acentenin agency.phone alanını kullan
            if (in_array('sms', $channels)) {
                $phone = $user->agency?->phone ?? null;
                if ($phone) {
                    $mesaj = ($broadcast->emoji ? $broadcast->emoji . ' ' : '')
                        . $broadcast->title . "\n" . $broadcast->message;
                    $sms->send(null, $user->role === 'acente' ? 'acente' : 'admin', $user->name, $phone, $mesaj);
                }
            }

            // E-posta
            if (in_array('email', $channels)) {
                $email->broadcastEmail($user, $broadcast);
            }
        }

        $broadcast->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'sent_count' => $kullanicilar->count(),
        ]);
    }

    /**
     * Hedef kitleyi döndür.
     */
    public function hedefKullanicilari(BroadcastNotification $broadcast): \Illuminate\Support\Collection
    {
        return match($broadcast->target) {
            'all'       => User::with('agency')->get(),
            'acenteler' => User::with('agency')->where('role', 'acente')->get(),
            'adminler'  => User::with('agency')->whereIn('role', ['admin', 'superadmin'])->get(),
            'secili'    => User::with('agency')->whereIn('id', $broadcast->target_user_ids ?? [])->get(),
            default     => collect(),
        };
    }
}
