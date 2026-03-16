<?php

namespace App\Services;

use App\Models\BroadcastNotification;
use App\Models\SistemAyar;
use App\Models\User;

class BroadcastService
{
    /**
     * Broadcast'i seçili kanallara gönder.
     * channels: ['push', 'sms', 'email'] — en az biri olmalı.
     */
    public function send(BroadcastNotification $broadcast): void
    {
        if (! SistemAyar::broadcastEnabled()) {
            return;
        }

        $kullanicilar = $this->hedefKullanicilari($broadcast);
        $channels     = $broadcast->channels ?? ['push'];
        $activeChannels = array_values(array_filter($channels, function (string $channel): bool {
            return match ($channel) {
                'push' => SistemAyar::pushEnabled(),
                'sms' => SistemAyar::smsEnabled(),
                'email' => SistemAyar::emailEnabled(),
                default => false,
            };
        }));

        if (empty($activeChannels)) {
            return;
        }

        $ns    = new NotificationService();
        $sms   = new SmsService();
        $email = new EmailService();

        $pushTitle = ($broadcast->emoji ? $broadcast->emoji . ' ' : '') . $broadcast->title;

        foreach ($kullanicilar as $user) {
            // Push bildirimi (uygulama içi bildirim zili)
            if (in_array('push', $activeChannels, true)) {
                $ns->createForUser(
                    $user->id,
                    'broadcast',
                    $pushTitle,
                    $broadcast->message,
                    null,
                    $broadcast->id
                );
            }

            // SMS — acentenin agency.phone alanını kullan
            if (in_array('sms', $activeChannels, true)) {
                $phone = $user->agency?->phone ?? null;
                if ($phone) {
                    $mesaj = ($broadcast->emoji ? $broadcast->emoji . ' ' : '')
                        . $broadcast->title . "\n" . $broadcast->message;
                    $sms->send(null, $user->role === 'acente' ? 'acente' : 'admin', $user->name, $phone, $mesaj);
                }
            }

            // E-posta
            if (in_array('email', $activeChannels, true)) {
                $email->broadcastEmail($user, $broadcast);
            }
        }

        $sentCount = $kullanicilar->count();

        $broadcast->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'sent_count' => $sentCount,
        ]);

        $pushTitle = ($broadcast->emoji ? $broadcast->emoji . ' ' : '') . $broadcast->title;

        // Superadmin'ler: gönderen hariç, gerçek içeriği + özet birlikte al
        $superadminler = User::where('role', 'superadmin')->get();
        foreach ($superadminler as $sa) {
            // Bell: gerçek broadcast içeriği
            if (! in_array('push', $activeChannels, true) || $kullanicilar->contains('id', $sa->id)) {
                // Zaten listede var, sadece özet yeter
            } else {
                $ns->createForUser($sa->id, 'broadcast', $pushTitle, $broadcast->message, null, $broadcast->id);
            }
            // Her durumda gönderim özeti de ekle
            $ns->createForUser(
                $sa->id,
                'broadcast',
                '✅ Duyuru Gönderildi (' . $sentCount . ' kişi)',
                "\"{$broadcast->title}\" → Gönderen: " . ($broadcast->sender?->name ?? '-'),
                null,
                $broadcast->id
            );

            // SMS CC (superadmin'e broadcast SMS kopyası)
            if (in_array('sms', $activeChannels, true) && $sa->phone) {
                $mesaj = ($broadcast->emoji ? $broadcast->emoji . ' ' : '')
                    . $broadcast->title . "\n" . $broadcast->message
                    . "\n[CC Kopya — {$sentCount} kişiye gönderildi]";
                $sms->send(null, 'superadmin', $sa->name, $sa->phone, $mesaj);
            }

            // Email CC (superadmin'e broadcast email kopyası)
            if (in_array('email', $activeChannels, true) && !$kullanicilar->contains('id', $sa->id)) {
                $email->broadcastEmail($sa, $broadcast);
            }
        }

        // Gönderen admin ise kendisine özet bildir (superadmin zaten üstte)
        $sender = User::find($broadcast->sender_id);
        if ($sender && $sender->role === 'admin') {
            $ns->createForUser(
                $sender->id,
                'broadcast',
                '✅ Duyuru Gönderildi',
                "\"{$broadcast->title}\" — {$sentCount} kullanıcıya iletildi.",
                null,
                $broadcast->id
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
