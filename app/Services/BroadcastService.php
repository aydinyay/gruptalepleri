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
            // Kullanıcıya özel değişken listesi
            $kisiselMesaj = $this->kisiselMesaj($broadcast->message, $user);
            $kisiselTitle = $this->kisiselMesaj($broadcast->title, $user);

            // Push bildirimi (uygulama içi bildirim zili)
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

            // SMS — acentenin agency.phone alanını kullan
            if (in_array('sms', $activeChannels, true)) {
                $phone = $user->agency?->phone ?? null;
                if ($phone) {
                    $smsMesaj = ($broadcast->emoji ? $broadcast->emoji . ' ' : '')
                        . $kisiselTitle . "\n" . $kisiselMesaj;
                    $sms->send(null, $user->role === 'acente' ? 'acente' : 'admin', $user->name, $phone, $smsMesaj);
                }
            }

            // E-posta (kişiselleştirilmiş broadcast klonu ile)
            if (in_array('email', $activeChannels, true)) {
                $kisiselBroadcast = clone $broadcast;
                $kisiselBroadcast->title   = $kisiselTitle;
                $kisiselBroadcast->message = $kisiselMesaj;
                $email->broadcastEmail($user, $kisiselBroadcast);
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
     * Mesaj metnindeki {değişken} yer tutucularını kullanıcıya özel değerlerle değiştir.
     * Desteklenen değişkenler:
     *   {acente_adi}        — Firma/acente adı
     *   {yetkili_adi}       — Yetkili kişi adı
     *   {ad}                — Kullanıcı adı (sisteme kayıtlı)
     *   {platform_linki}    — Dashboard URL
     *   {giris_linki}       — Giriş sayfası URL
     *   {talep_ac_linki}    — Yeni talep oluşturma URL
     *   {unsubscribe_linki} — Abonelik iptal URL (acente kullanıcıları için)
     */
    private function kisiselMesaj(string $metin, User $user): string
    {
        $unsubscribeUrl = ($user->id && $user->role === 'acente')
            ? \URL::signedRoute('abonelik.confirm', ['user' => $user->id])
            : url('/');

        $degiskenler = [
            '{acente_adi}'          => $user->agency?->company_title ?? $user->name,
            '{yetkili_adi}'         => $user->agency?->contact_name ?? $user->name,
            '{ad}'                  => $user->name,
            '{platform_linki}'      => url('/'),
            '{giris_linki}'         => route('login'),
            '{talep_ac_linki}'      => url('/talep/olustur'),
            '{sifre_yenile_linki}'  => route('password.request'),
            '{unsubscribe_linki}'   => $unsubscribeUrl,
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
