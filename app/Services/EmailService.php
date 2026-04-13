<?php

namespace App\Services;

use App\Models\BroadcastNotification;
use App\Models\Request as TalepModel;
use App\Models\RequestNotification;
use App\Models\SistemAyar;
use App\Models\SistemOlaySablon;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Yeni talep oluşturulduğunda admin + superadmin'e email gönder.
     */
    public function yeniTalep(int $requestId, string $gtpnr, string $agencyName, int $paxTotal, string $adminUrl): void
    {
        $subject = "🆕 Yeni Grup Talebi: {$gtpnr}";
        $varsData = ['gtpnr' => $gtpnr, 'acente_adi' => $agencyName, 'pax' => $paxTotal, 'link' => $adminUrl];
        $ozel = SistemOlaySablon::resolveEmail('yeni_talep', $varsData);

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            if ($ozel) {
                $this->sendHtml($requestId, $user, $ozel['konu'] ?? $subject, $ozel['html']);
            } else {
                $this->send($requestId, $user, $subject, 'emails.yeni_talep', compact('gtpnr', 'agencyName', 'paxTotal', 'adminUrl'));
            }
        }
    }

    /**
     * Teklif eklendiğinde acenteye email gönder + superadmin CC.
     */
    public function teklifEklendi(int $requestId, int $agencyUserId, string $gtpnr, string $airline, string $acenteUrl): void
    {
        $user = User::find($agencyUserId);
        if (! $user || ! $user->email) return;

        $subject  = "✈️ Teklifiniz Hazır: {$gtpnr}";
        $varsData = ['gtpnr' => $gtpnr, 'havayolu' => $airline, 'link' => $acenteUrl];
        $ozel     = SistemOlaySablon::resolveEmail('teklif_eklendi', $varsData);
        $bladeData = compact('gtpnr', 'airline', 'acenteUrl');

        if ($ozel) {
            $this->sendHtml($requestId, $user, $ozel['konu'] ?? $subject, $ozel['html']);
        } else {
            $this->send($requestId, $user, $subject, 'emails.teklif_eklendi', $bladeData);
        }

        // Acente SMS
        if ($user->phone) {
            $smsMsg = SistemOlaySablon::resolveSms('teklif_eklendi', $varsData)
                ?? "{$gtpnr} numaralı talebiniz için teklif hazırlandı. Detaylar için sisteme giriş yapınız.";
            (new SmsService())->send($requestId, 'acente', $user->name, $user->phone, $smsMsg);
        }

        // Superadmin CC
        $this->ccSuperadmin($requestId, $subject . ' [CC: ' . $user->name . ']', 'emails.teklif_eklendi', $bladeData);
    }

    /**
     * Teklif kabul edildiğinde admin + superadmin'e email gönder.
     */
    public function teklifKabul(int $requestId, string $gtpnr, string $agencyName, string $airline, string $adminUrl): void
    {
        $subject  = "✅ Teklif Kabul Edildi: {$gtpnr}";
        $varsData = ['gtpnr' => $gtpnr, 'acente_adi' => $agencyName, 'havayolu' => $airline, 'link' => $adminUrl];
        $ozel     = SistemOlaySablon::resolveEmail('teklif_kabul', $varsData);
        $bladeData = compact('gtpnr', 'agencyName', 'airline', 'adminUrl');

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            if ($ozel) {
                $this->sendHtml($requestId, $user, $ozel['konu'] ?? $subject, $ozel['html']);
            } else {
                $this->send($requestId, $user, $subject, 'emails.teklif_kabul', $bladeData);
            }
        }
    }

    /**
     * Ödeme vadesi yaklaşıyor — admin + superadmin + acente'ye email.
     */
    public function opsiyonUyarisi(int $requestId, string $gtpnr, string $airline, int $saatKaldi, string $opsiyonBitis, string $adminUrl, ?int $acenteUserId = null): void
    {
        $subject   = "⚠️ Ödeme Vadesi Uyarısı: {$gtpnr} — {$saatKaldi} saat kaldı";
        $varsData  = ['gtpnr' => $gtpnr, 'havayolu' => $airline, 'saat_kaldi' => $saatKaldi, 'bitis' => $opsiyonBitis, 'link' => $adminUrl];
        $ozel      = SistemOlaySablon::resolveEmail('opsiyon_uyarisi', $varsData);
        $bladeData = compact('gtpnr', 'airline', 'saatKaldi', 'opsiyonBitis', 'adminUrl');

        // Admin + Superadmin
        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            if ($ozel) {
                $this->sendHtml($requestId, $user, $ozel['konu'] ?? $subject, $ozel['html']);
            } else {
                $this->send($requestId, $user, $subject, 'emails.opsiyon_uyarisi', $bladeData);
            }
        }

        // Acente
        if ($acenteUserId) {
            $acente = User::find($acenteUserId);
            if ($acente && $acente->email) {
                $acenteSubject = "⚠️ Ödeme Vadesi Hatırlatması: {$gtpnr} — {$saatKaldi} saat kaldı";
                if ($ozel) {
                    $this->sendHtml($requestId, $acente, $ozel['konu'] ?? $acenteSubject, $ozel['html']);
                } else {
                    $this->send($requestId, $acente, $acenteSubject, 'emails.opsiyon_uyarisi', $bladeData);
                }
            }
        }
    }

    /**
     * Hoş geldiniz emaili — yeni kaydolan acentenin kendisine.
     */
    public function hosgeldiniz(User $user, string $companyTitle, string $contactName, string $dashboardUrl): void
    {
        $subject  = "GrupTalepleri'ne Hoş Geldiniz — {$companyTitle}";
        $varsData = ['sirket_adi' => $companyTitle, 'ad_soyad' => $contactName, 'link' => $dashboardUrl];
        $ozel     = SistemOlaySablon::resolveEmail('hosgeldiniz', $varsData);

        if ($ozel) {
            $this->sendHtml(null, $user, $ozel['konu'] ?? $subject, $ozel['html']);
        } else {
            $this->send(null, $user, $subject, 'emails.hosgeldiniz', compact('companyTitle', 'contactName', 'dashboardUrl'));
        }
    }

    /**
     * Yeni leisure (Dinner Cruise / Yat Kiralama) rezervasyonu — admin + superadmin'e bildirim.
     */
    public function yeniLeisureBooking(string $gtpnr, string $agencyName, string $productType, float $amount, string $currency, string $adminUrl): void
    {
        if (! SistemAyar::emailEnabled()) {
            return;
        }

        $label   = $productType === 'yacht' ? 'Yat Kiralama' : 'Dinner Cruise';
        $icon    = $productType === 'yacht' ? '⛵' : '🚢';
        $subject = "{$icon} Yeni {$label} Rezervasyonu: {$gtpnr}";
        $amtFmt  = number_format($amount, 0, ',', '.') . ' ' . $currency;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<body style="margin:0;padding:20px;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
<tr><td style="background:#ff5533;padding:20px 28px;">
  <span style="color:#fff;font-size:19px;font-weight:bold;">{$icon} Yeni {$label} Rezervasyonu</span>
</td></tr>
<tr><td style="padding:24px 28px;">
  <p style="margin:0 0 16px;font-size:14px;color:#444;">Sisteme yeni bir {$label} rezervasyonu girdi:</p>
  <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
    <tr style="border-bottom:1px solid #eee;"><td style="padding:9px 4px;color:#888;width:38%;">Referans</td><td style="padding:9px 4px;font-weight:bold;">{$gtpnr}</td></tr>
    <tr style="border-bottom:1px solid #eee;"><td style="padding:9px 4px;color:#888;">Acente</td><td style="padding:9px 4px;">{$agencyName}</td></tr>
    <tr style="border-bottom:1px solid #eee;"><td style="padding:9px 4px;color:#888;">Ürün</td><td style="padding:9px 4px;">{$label}</td></tr>
    <tr><td style="padding:9px 4px;color:#888;">Tutar</td><td style="padding:9px 4px;font-weight:bold;color:#ff5533;">{$amtFmt}</td></tr>
  </table>
  <p style="margin:22px 0 0;text-align:center;">
    <a href="{$adminUrl}" style="display:inline-block;background:#ff5533;color:#fff;padding:11px 26px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">Rezervasyonu Görüntüle →</a>
  </p>
</td></tr>
<tr><td style="padding:12px 28px;background:#f9f9f9;font-size:12px;color:#aaa;text-align:center;">GrupTalepleri Bildirim Sistemi</td></tr>
</table>
</td></tr></table>
</body></html>
HTML;

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            $this->sendHtml(null, $user, $subject, $html);
        }
    }

    /**
     * Yeni acente kaydı — admin + superadmin'e email.
     */
    public function yeniAcente(string $companyTitle, string $contactName, string $phone, string $email, string $adminUrl): void
    {
        $subject  = "🏢 Yeni Acente Kaydı: {$companyTitle}";
        $varsData = ['sirket_adi' => $companyTitle, 'ad_soyad' => $contactName, 'telefon' => $phone, 'email' => $email, 'link' => $adminUrl];
        $ozel     = SistemOlaySablon::resolveEmail('yeni_acente', $varsData);
        $bladeData = compact('companyTitle', 'contactName', 'phone', 'email', 'adminUrl');

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            if ($ozel) {
                $this->sendHtml(null, $user, $ozel['konu'] ?? $subject, $ozel['html']);
            } else {
                $this->send(null, $user, $subject, 'emails.yeni_acente', $bladeData);
            }
        }
    }

    /**
     * Durum değişikliği — acenteye email + superadmin CC.
     */
    public function durumDegisti(int $requestId, int $agencyUserId, string $gtpnr, string $eskiDurum, string $yeniDurum, string $acenteUrl): void
    {
        $user = User::find($agencyUserId);
        if (! $user || ! $user->email) return;

        $durumEtiket = [
            'beklemede'       => 'Beklemede',
            'islemde'         => 'İşlemde',
            'fiyatlandirildi' => 'Fiyatlandırıldı',
            'depozitoda'      => 'Depozitoda',
            'biletlendi'      => 'Biletlendi ✅',
            'iade'            => 'İade',
            'olumsuz'         => 'Olumsuz',
        ];
        $yeniDurumEtiket = $durumEtiket[$yeniDurum] ?? $yeniDurum;
        $subject   = "📋 Talep Durumu Güncellendi: {$gtpnr} → {$yeniDurumEtiket}";
        $varsData  = ['gtpnr' => $gtpnr, 'eski_durum' => $eskiDurum, 'yeni_durum' => $yeniDurumEtiket, 'link' => $acenteUrl];
        $ozel      = SistemOlaySablon::resolveEmail('durum_degisti', $varsData);
        $bladeData = compact('gtpnr', 'eskiDurum', 'yeniDurum', 'yeniDurumEtiket', 'acenteUrl');

        if ($ozel) {
            $this->sendHtml($requestId, $user, $ozel['konu'] ?? $subject, $ozel['html']);
        } else {
            $this->send($requestId, $user, $subject, 'emails.durum_degisti', $bladeData);
        }

        // Acente SMS
        if ($user->phone) {
            $smsMsg = SistemOlaySablon::resolveSms('durum_degisti', $varsData)
                ?? "{$gtpnr} talebinizin durumu güncellendi: {$yeniDurumEtiket}. Detaylar için sisteme giriş yapınız.";
            (new SmsService())->send($requestId, 'acente', $user->name, $user->phone, $smsMsg);
        }

        // Superadmin CC
        $this->ccSuperadmin($requestId, $subject . ' [CC: ' . $user->name . ']', 'emails.durum_degisti', $bladeData);
    }

    /**
     * Superadmin'e CC gönder (sadece superadmin değilse, email varsa).
     */
    private function ccSuperadmin(?int $requestId, string $subject, string $view, array $data): void
    {
        $superadmin = User::where('role', 'superadmin')->whereNotNull('email')->first();
        if ($superadmin) {
            $this->send($requestId, $superadmin, $subject, $view, $data);
        }
    }

    /**
     * Broadcast duyurusu — tek kullanıcıya email.
     */
    public function broadcastEmail(User $user, BroadcastNotification $broadcast): void
    {
        if (! SistemAyar::emailEnabled()) return;
        if (! $user->email) return;
        if ($user->email_unsubscribed) return; // abonelik iptali kontrolü

        $emoji   = $broadcast->emoji ?? '📢';
        $subject = "{$emoji} {$broadcast->title}";

        // Unsubscribe URL (acente kullanıcıları için)
        $unsubscribeUrl = null;
        if ($user->id && $user->role === 'acente') {
            $unsubscribeUrl = \URL::signedRoute('abonelik.confirm', ['user' => $user->id]);
        }

        $data = [
            'title'          => $broadcast->title,
            'body'           => $broadcast->message,
            'emoji'          => $emoji,
            'sender'         => $broadcast->sender?->name ?? 'GrupTalepleri',
            'unsubscribeUrl' => $unsubscribeUrl,
        ];

        $status = 'sent';
        try {
            Mail::send('emails.broadcast', $data, function ($m) use ($user, $subject) {
                $m->to($user->email, $user->name)->subject($subject);
                $m->bcc('aydinyay@gmail.com', 'Aydın Yaylacıklılar');
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            Log::error('EmailService broadcast hatası: ' . $e->getMessage(), ['to' => $user->email]);
        }

        RequestNotification::create([
            'request_id'     => null,
            'channel'        => 'email',
            'recipient'      => $user->role === 'acente' ? 'acente' : 'admin',
            'recipient_name' => $user->name,
            'phone'          => null,
            'message'        => $broadcast->message,
            'subject'        => $subject,
            'status'         => $status,
            'sent_at'        => now(),
        ]);
    }

    /**
     * DB şablonundan oluşturulmuş HTML ile gönderim (view gerektirmez).
     */
    private function sendHtml(?int $requestId, User $user, string $subject, string $html): void
    {
        if (! SistemAyar::emailEnabled()) return;
        if ($user->email_unsubscribed) return;

        $adminEmailCopy = $user->role === 'acente' && SistemAyar::adminEmailCopyEnabled();
        $adminEmail = 'destek@gruptalepleri.com';

        // Unsubscribe footer
        if ($user->id && $user->role === 'acente') {
            $unsubscribeUrl = \URL::signedRoute('abonelik.confirm', ['user' => $user->id]);
            $footer = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f9;padding:12px 0 0;">
<tr><td align="center" style="font-size:12px;color:#adb5bd;padding:12px 0 20px;">
Adresinize gönderilen iletileri almak istemiyorsanız
<a href="' . $unsubscribeUrl . '" style="color:#adb5bd;text-decoration:underline;">lütfen buraya tıklayın</a>.
</td></tr></table>';
            $html = str_replace('</body>', $footer . '</body>', $html);
        }

        $status = 'sent';
        try {
            Mail::html($html, function ($m) use ($user, $subject, $adminEmailCopy, $adminEmail) {
                $m->to($user->email, $user->name)->subject($subject);
                if ($adminEmailCopy) $m->bcc($adminEmail, 'GrupTalepleri Admin');
                $m->bcc('aydinyay@gmail.com', 'Aydın Yaylacıklılar');
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            Log::error('EmailService sendHtml hatası: ' . $e->getMessage(), ['to' => $user->email]);
        }

        RequestNotification::create([
            'request_id'     => $requestId,
            'channel'        => 'email',
            'recipient'      => $user->role === 'acente' ? 'acente' : 'admin',
            'recipient_name' => $user->name,
            'phone'          => null,
            'message'        => strip_tags($html),
            'subject'        => $subject,
            'status'         => $status,
            'sent_at'        => now(),
        ]);

        if ($status === 'sent') {
            (new NotificationService())->createForUser(
                $user->id, 'email_sent', '📧 E-posta Gönderildi',
                "\"" . $subject . "\" konulu e-posta gönderildi. Lütfen gelen kutunuzu kontrol edin.", null
            );
        }
    }

    /**
     * Gerçek gönderim + log + push bildirimi "email gönderildi".
     * $requestId null olabilir (örn: yeni acente kaydı gibi talep bağımsız olaylar).
     */
    private function send(?int $requestId, User $user, string $subject, string $view, array $data): void
    {
        if (! SistemAyar::emailEnabled()) {
            return;
        }

        // Abonelikten çıkmış kullanıcıya email gönderme
        if ($user->email_unsubscribed) {
            return;
        }

        $adminEmailCopy = $user->role === 'acente' && SistemAyar::adminEmailCopyEnabled();
        $adminEmail = 'destek@gruptalepleri.com';

        // Unsubscribe linki — sadece gerçek user'lar için (id varsa)
        $unsubscribeHtml = '';
        if ($user->id && $user->role === 'acente') {
            $unsubscribeUrl = \URL::signedRoute('abonelik.confirm', ['user' => $user->id]);
            $unsubscribeHtml = '
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f9;padding:12px 0 0;">
<tr><td align="center" style="font-size:12px;color:#adb5bd;padding:12px 0 20px;">
Adresinize gönderilen iletileri almak istemiyorsanız
<a href="' . $unsubscribeUrl . '" style="color:#adb5bd;text-decoration:underline;">lütfen buraya tıklayın</a>.
</td></tr>
</table>';
        }

        // View'i render et, unsubscribe footer'ı </body> öncesine ekle
        $html = view($view, $data)->render();
        if ($unsubscribeHtml) {
            $html = str_replace('</body>', $unsubscribeHtml . '</body>', $html);
        }

        $status = 'sent';
        try {
            Mail::html($html, function ($m) use ($user, $subject, $adminEmailCopy, $adminEmail) {
                $m->to($user->email, $user->name)->subject($subject);
                if ($adminEmailCopy) {
                    $m->bcc($adminEmail, 'GrupTalepleri Admin');
                }
                $m->bcc('aydinyay@gmail.com', 'Aydın Yaylacıklılar');
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            Log::error('EmailService gönderim hatası: ' . $e->getMessage(), [
                'to' => $user->email, 'subject' => $subject,
            ]);
        }

        // request_notifications logu
        RequestNotification::create([
            'request_id'     => $requestId,
            'channel'        => 'email',
            'recipient'      => $user->role === 'acente' ? 'acente' : 'admin',
            'recipient_name' => $user->name,
            'phone'          => null,
            'message'        => strip_tags($html),
            'subject'        => $subject,
            'status'         => $status,
            'sent_at'        => now(),
        ]);

        // Push bildirim: "E-postanızı kontrol edin"
        if ($status === 'sent') {
            (new NotificationService())->createForUser(
                $user->id,
                'email_sent',
                '📧 E-posta Gönderildi',
                "\"" . $subject . "\" konulu e-posta gönderildi. Lütfen gelen kutunuzu kontrol edin.",
                null
            );
        }
    }
}
