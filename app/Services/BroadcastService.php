<?php

namespace App\Services;

use App\Models\BroadcastEmailTrack;
use App\Models\BroadcastNotification;
use App\Models\SistemAyar;
use App\Models\User;

class BroadcastService
{
    /**
     * Broadcast'i seçili kanallara gönder.
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
                'push'  => SistemAyar::pushEnabled(),
                'sms'   => SistemAyar::smsEnabled(),
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

        foreach ($kullanicilar as $user) {
            $kisiselMesaj = $this->kisiselMesaj($broadcast->message, $user, $broadcast->id);
            $kisiselTitle = $this->kisiselMesaj($broadcast->title, $user, $broadcast->id);

            // Push
            if (in_array('push', $activeChannels, true)) {
                $ns->createForUser(
                    $user->id,
                    'broadcast',
                    ($broadcast->emoji ? $broadcast->emoji . ' ' : '') . $kisiselTitle,
                    $kisiselMesaj,
                    null,
                    $broadcast->id
                );
            }

            // SMS
            if (in_array('sms', $activeChannels, true)) {
                $phone = $user->agency?->phone ?? null;
                if ($phone) {
                    $smsMesaj = ($broadcast->emoji ? $broadcast->emoji . ' ' : '')
                        . $kisiselTitle . "\n" . $kisiselMesaj;
                    $sms->send(null, $user->role === 'acente' ? 'acente' : 'admin', $user->name, $phone, $smsMesaj);
                }
            }

            // Email — tracking token'ları üret, pixel + tracked linkler ekle
            if (in_array('email', $activeChannels, true)) {
                $kisiselBroadcast          = clone $broadcast;
                $kisiselBroadcast->title   = $kisiselTitle;
                $kisiselBroadcast->message = $this->injectTracking($kisiselMesaj, $broadcast->id, $user->id);

                $email->broadcastEmail($user, $kisiselBroadcast, $broadcast->id);
            }
        }

        $sentCount = $kullanicilar->count();

        $broadcast->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'sent_count' => $sentCount,
        ]);

        // Superadmin özet bildirimleri
        $superadminler = User::where('role', 'superadmin')->get();
        foreach ($superadminler as $sa) {
            if (! in_array('push', $activeChannels, true) || $kullanicilar->contains('id', $sa->id)) {
                // zaten listede, ekstra push yok
            } else {
                $pushTitle = ($broadcast->emoji ? $broadcast->emoji . ' ' : '') . $broadcast->title;
                $ns->createForUser($sa->id, 'broadcast', $pushTitle, $broadcast->message, null, $broadcast->id);
            }
            $ns->createForUser(
                $sa->id,
                'broadcast',
                '✅ Duyuru Gönderildi (' . $sentCount . ' kişi)',
                "\"{$broadcast->title}\" → Gönderen: " . ($broadcast->sender?->name ?? '-'),
                null,
                $broadcast->id
            );
            if (in_array('sms', $activeChannels, true) && $sa->phone) {
                $mesaj = ($broadcast->emoji ? $broadcast->emoji . ' ' : '')
                    . $broadcast->title . "\n" . $broadcast->message
                    . "\n[CC — {$sentCount} kişiye gönderildi]";
                $sms->send(null, 'superadmin', $sa->name, $sa->phone, $mesaj);
            }
            if (in_array('email', $activeChannels, true) && ! $kullanicilar->contains('id', $sa->id)) {
                $email->broadcastEmail($sa, $broadcast);
            }
        }

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
     * Email gövdesine open pixel ekler, tüm http(s) linklerini tracked URL'e çevirir.
     */
    private function injectTracking(string $html, int $broadcastId, int $userId): string
    {
        // 1. Click tracking: mevcut href="http..." linklerini tracked URL'e çevir
        $html = preg_replace_callback(
            '/href=["\']((https?:\/\/[^"\']+))["\']/',
            function (array $m) use ($broadcastId, $userId): string {
                $original = $m[1];
                $token    = BroadcastEmailTrack::makeToken($broadcastId, $userId, 'click', $original);
                BroadcastEmailTrack::firstOrCreate(
                    ['token' => $token],
                    ['broadcast_id' => $broadcastId, 'user_id' => $userId, 'type' => 'click', 'destination_url' => $original]
                );
                return 'href="' . route('email.track.click', $token) . '"';
            },
            $html
        );

        // 2. Düz metin URL'leri otomatik linked <a> tag'a çevir (text node içindeki https?:// )
        $html = preg_replace_callback(
            '/>([^<]+)</U',
            function (array $m) use ($broadcastId, $userId): string {
                $text = $m[1];
                if (! preg_match('/https?:\/\//', $text)) {
                    return '>' . $text . '<';
                }
                $linked = preg_replace_callback(
                    '/(https?:\/\/[^\s<>"\']+)/',
                    function (array $inner) use ($broadcastId, $userId): string {
                        $url   = rtrim($inner[1], '.,;:!?)');
                        $token = BroadcastEmailTrack::makeToken($broadcastId, $userId, 'click', $url);
                        BroadcastEmailTrack::firstOrCreate(
                            ['token' => $token],
                            ['broadcast_id' => $broadcastId, 'user_id' => $userId, 'type' => 'click', 'destination_url' => $url]
                        );
                        $tracked = route('email.track.click', $token);
                        return '<a href="' . $tracked . '" style="color:#e94560;text-decoration:underline;">' . $url . '</a>';
                    },
                    $text
                );
                return '>' . $linked . '<';
            },
            $html
        );

        // 3. Open pixel — gövde sonuna 1×1 görünmez resim
        $openToken = BroadcastEmailTrack::makeToken($broadcastId, $userId, 'open');
        BroadcastEmailTrack::firstOrCreate(
            ['token' => $openToken],
            ['broadcast_id' => $broadcastId, 'user_id' => $userId, 'type' => 'open']
        );
        $pixelUrl = route('email.track.open', $openToken);
        $html    .= '<img src="' . $pixelUrl . '" width="1" height="1" style="display:block;border:0;" alt="">';

        return $html;
    }

    /**
     * Değişken yerine koyma — her kullanıcı için kişiselleştirilmiş URL'ler dahil.
     */
    private function kisiselMesaj(string $metin, User $user, int $broadcastId = 0): string
    {
        $unsubscribeUrl = ($user->id && $user->role === 'acente')
            ? \URL::signedRoute('abonelik.confirm', ['user' => $user->id])
            : url('/');

        $degiskenler = [
            '{acente_adi}'         => $user->agency?->company_title ?? $user->name,
            '{yetkili_adi}'        => $user->agency?->contact_name ?? $user->name,
            '{ad}'                 => $user->name,
            '{platform_linki}'     => url('/'),
            '{giris_linki}'        => route('login'),
            '{talep_ac_linki}'     => url('/talep/olustur'),
            '{sifre_yenile_linki}' => route('password.request'),
            '{unsubscribe_linki}'  => $unsubscribeUrl,
        ];

        return str_replace(array_keys($degiskenler), array_values($degiskenler), $metin);
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
