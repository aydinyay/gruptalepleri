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

        $sentCount = $kullanicilar->count();

        $broadcast->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'sent_count' => $sentCount,
        ]);

        // Superadmin'lere özet push bildirimi gönder (gönderen hariç)
        $superadminler = User::whereIn('role', ['superadmin'])
            ->where('id', '!=', $broadcast->sender_id)
            ->get();

        foreach ($superadminler as $sa) {
            $ns->createForUser(
                $sa->id,
                'broadcast',
                '✅ Duyuru Gönderildi',
                "\"{$broadcast->title}\" — {$sentCount} kullanıcıya iletildi.",
                null
            );
        }

        // Gönderen superadmin ise kendisine de özet bildir
        $sender = User::find($broadcast->sender_id);
        if ($sender && in_array($sender->role, ['superadmin', 'admin'])) {
            $ns->createForUser(
                $sender->id,
                'broadcast',
                '✅ Duyuru Gönderildi',
                "\"{$broadcast->title}\" — {$sentCount} kullanıcıya iletildi.",
                null
            );
        }
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
