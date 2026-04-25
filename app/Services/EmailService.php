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
     * B2C grup uçuş talebi oluşturuldu — tüketiciye onay + takip linki emaili.
     */
    public function b2cTalepOnay(int $requestId, string $gtpnr, string $contactName, string $email, string $trackUrl): void
    {
        if (! SistemAyar::emailEnabled()) return;

        $subject = "✅ Talebiniz Alındı — {$gtpnr}";
        $html = <<<HTML
<!DOCTYPE html><html lang="tr"><body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
  <tr><td style="background:linear-gradient(135deg,#0f2444,#1a3c6b);padding:32px 36px 28px;text-align:center;">
    <div style="font-size:2.5rem;margin-bottom:8px;">✈️</div>
    <h1 style="color:#fff;font-size:1.4rem;margin:0 0 6px;">Talebiniz Alındı!</h1>
    <p style="color:rgba(255,255,255,.75);margin:0;font-size:.88rem;">En kısa sürede size geri dönüyoruz.</p>
  </td></tr>
  <tr><td style="padding:28px 36px;">
    <p style="font-size:.95rem;color:#1a202c;margin:0 0 20px;">Merhaba <strong>{$contactName}</strong>,</p>
    <p style="font-size:.88rem;color:#4a5568;line-height:1.6;margin:0 0 24px;">
      Grup uçuş talebiniz başarıyla alındı. Ekibimiz talebinizi inceleyerek <strong>2–4 saat içinde</strong> sizinle iletişime geçecektir.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;border-radius:10px;margin-bottom:24px;">
      <tr><td style="padding:16px 18px;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7a99;margin-bottom:4px;">Talep Referans No</div>
        <div style="font-size:1.3rem;font-weight:800;color:#1a3c6b;letter-spacing:2px;font-family:monospace;">{$gtpnr}</div>
      </td></tr>
    </table>
    <div style="text-align:center;margin-bottom:28px;">
      <a href="{$trackUrl}" style="display:inline-block;background:#FF5533;color:#fff;text-decoration:none;padding:13px 32px;border-radius:10px;font-weight:700;font-size:.95rem;">
        Talebimi Takip Et →
      </a>
    </div>
    <p style="font-size:.8rem;color:#718096;line-height:1.6;margin:0;">
      Bu linki kaybetmeyin — talebinizin durumunu görmek, teklifleri incelemek ve ödeme yapmak için kullanacaksınız.
    </p>
  </td></tr>
  <tr><td style="background:#f8faff;padding:16px 36px;text-align:center;font-size:.75rem;color:#a0aec0;border-top:1px solid #e2e8f0;">
    GrupRezervasyonlari.com · Her hakkı saklıdır.
  </td></tr>
</table></td></tr></table></body></html>
HTML;

        try {
            Mail::html($html, function ($m) use ($email, $contactName, $subject) {
                $m->to($email, $contactName)->subject($subject);
                $m->bcc('aydinyay@gmail.com', 'Aydın Yaylacıklılar');
            });
        } catch (\Throwable $e) {
            Log::error('EmailService b2cTalepOnay hatası: ' . $e->getMessage(), ['to' => $email]);
        }

        RequestNotification::create([
            'request_id'     => $requestId,
            'channel'        => 'email',
            'recipient'      => 'b2c_musteri',
            'recipient_name' => $contactName,
            'phone'          => null,
            'message'        => "B2C talep onay emaili gönderildi. Takip URL: {$trackUrl}",
            'subject'        => $subject,
            'status'         => 'sent',
            'sent_at'        => now(),
        ]);
    }

    /**
     * B2C talep için admin teklif ekledi — tüketiciye bildirim emaili.
     */
    public function b2cTeklifHazir(int $requestId, string $gtpnr, string $contactName, string $email, string $airline, string $trackUrl): void
    {
        if (! SistemAyar::emailEnabled()) return;

        $subject = "💸 Fiyat Teklifiniz Hazır — {$gtpnr}";
        $html = <<<HTML
<!DOCTYPE html><html lang="tr"><body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
  <tr><td style="background:linear-gradient(135deg,#166534,#22c55e);padding:32px 36px 28px;text-align:center;">
    <div style="font-size:2.5rem;margin-bottom:8px;">💸</div>
    <h1 style="color:#fff;font-size:1.4rem;margin:0 0 6px;">Fiyat Teklifiniz Hazır!</h1>
    <p style="color:rgba(255,255,255,.75);margin:0;font-size:.88rem;">{$airline} için fiyatlandırma tamamlandı.</p>
  </td></tr>
  <tr><td style="padding:28px 36px;">
    <p style="font-size:.95rem;color:#1a202c;margin:0 0 16px;">Merhaba <strong>{$contactName}</strong>,</p>
    <p style="font-size:.88rem;color:#4a5568;line-height:1.6;margin:0 0 24px;">
      <strong>{$gtpnr}</strong> numaralı grup uçuş talebiniz için fiyat teklifi hazırlandı. Teklifi inceleyip kabul etmek için aşağıdaki butona tıklayın.
    </p>
    <div style="text-align:center;margin-bottom:28px;">
      <a href="{$trackUrl}" style="display:inline-block;background:#FF5533;color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:1rem;">
        Teklifi İncele ve Kabul Et →
      </a>
    </div>
    <p style="font-size:.8rem;color:#718096;line-height:1.6;margin:0;">
      Teklifinizi kabul etmek için linke tıklayın ve telefon numaranız veya e-posta adresinizle doğrulama yapın.
    </p>
  </td></tr>
  <tr><td style="background:#f8faff;padding:16px 36px;text-align:center;font-size:.75rem;color:#a0aec0;border-top:1px solid #e2e8f0;">
    GrupRezervasyonlari.com · Her hakkı saklıdır.
  </td></tr>
</table></td></tr></table></body></html>
HTML;

        try {
            Mail::html($html, function ($m) use ($email, $contactName, $subject) {
                $m->to($email, $contactName)->subject($subject);
                $m->bcc('aydinyay@gmail.com', 'Aydın Yaylacıklılar');
            });
        } catch (\Throwable $e) {
            Log::error('EmailService b2cTeklifHazir hatası: ' . $e->getMessage(), ['to' => $email]);
        }

        RequestNotification::create([
            'request_id'     => $requestId,
            'channel'        => 'email',
            'recipient'      => 'b2c_musteri',
            'recipient_name' => $contactName,
            'phone'          => null,
            'message'        => "B2C teklif email gönderildi: {$airline}",
            'subject'        => $subject,
            'status'         => 'sent',
            'sent_at'        => now(),
        ]);
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
     * B2C fiyat alarmı bildirimi — raw e-posta adresine gönderir.
     */
    public function fiyatAlarmi(string $toEmail, string $toName, string $itemTitle, float $eskiFiyat, float $yeniFiyat, string $currency, string $slug): void
    {
        if (! SistemAyar::emailEnabled()) return;
        $url     = rtrim(config('app.b2c_url', 'https://gruprezervasyonlari.com'), '/') . '/urun/' . $slug;
        $subject = "🎉 Fiyat Düştü: {$itemTitle}";
        $html    = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px 20px;">'
            . '<h2 style="color:#1a3c6b;font-size:1.3rem;margin-bottom:8px;">Fiyat Alarmınız Tetiklendi!</h2>'
            . '<p style="color:#555;">Merhaba ' . htmlspecialchars($toName) . ',</p>'
            . '<p style="color:#555;">Takip ettiğiniz ürünün fiyatı düştü:</p>'
            . '<div style="background:#f6f8fc;border-left:4px solid #1a3c6b;padding:16px 20px;border-radius:6px;margin:16px 0;">'
            . '<strong style="font-size:1rem;color:#1a1a2e;">' . htmlspecialchars($itemTitle) . '</strong><br>'
            . '<span style="color:#718096;font-size:.9rem;">Eski fiyat: <s>' . number_format($eskiFiyat, 0, ',', '.') . ' ' . $currency . '</s></span><br>'
            . '<span style="color:#10b981;font-size:1.1rem;font-weight:700;">Yeni fiyat: ' . number_format($yeniFiyat, 0, ',', '.') . ' ' . $currency . '</span>'
            . '</div>'
            . '<a href="' . $url . '" style="display:inline-block;background:#e53e3e;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:.95rem;">Hemen İncele →</a>'
            . '<p style="color:#aaa;font-size:.75rem;margin-top:24px;">GrupRezervasyonları · Bu e-postayı aldınız çünkü GR Asistan üzerinden fiyat alarmı kurdunuz.</p>'
            . '</div>';
        try {
            Mail::html($html, function ($m) use ($toEmail, $toName, $subject) {
                $m->to($toEmail, $toName)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('EmailService::fiyatAlarmi hatası: ' . $e->getMessage(), ['to' => $toEmail]);
        }
    }

    /**
     * Sigorta poliçesi hazır bildirimi — acente veya B2C kullanıcısına.
     */
    public function policeHazir(\App\Models\SigortaPolice $police): void
    {
        if (! SistemAyar::emailEnabled()) return;

        $user = null;
        if ($police->kanal === 'b2b' && $police->acente_id) {
            $user = User::find($police->acente_id);
        } elseif ($police->kanal === 'b2c' && $police->b2c_user_id) {
            $user = User::find($police->b2c_user_id);
        }

        if (! $user || ! $user->email) return;

        $policeNo = $police->police_no ?: '—';
        $subject  = "🛡 Sigorta Poliçeniz Hazır — {$policeNo}";

        $belgeUrl = $police->kanal === 'b2b'
            ? route('acente.sigorta.belge', ['police' => $police->id, 'tip' => 'police'])
            : route('b2c.sigorta.belge',   ['police' => $police->id, 'tip' => 'police']);

        $sigortalı    = htmlspecialchars($police->sigortali_adi . ' ' . $police->sigortali_soyadi);
        $ülke         = htmlspecialchars($police->gidilecek_ulke ?? '');
        $baslangic    = $police->baslangic_tarihi?->format('d.m.Y') ?? '—';
        $bitis        = $police->bitis_tarihi?->format('d.m.Y') ?? '—';
        $adSoyad      = htmlspecialchars($user->name ?? '');

        $html = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:580px;margin:0 auto;padding:24px 20px;">
  <h2 style="color:#1a3c6b;font-size:1.25rem;margin-bottom:8px;">✅ Sigorta Poliçeniz Hazır!</h2>
  <p style="color:#555;">Merhaba {$adSoyad},</p>
  <p style="color:#555;">Aşağıdaki sigorta poliçeniz başarıyla düzenlendi:</p>
  <div style="background:#f0fdf4;border-left:4px solid #16a34a;padding:16px 20px;border-radius:6px;margin:16px 0;">
    <strong style="color:#15803d;font-size:1rem;">Poliçe No: {$policeNo}</strong><br>
    <span style="color:#555;">Sigortalı: {$sigortalı}</span><br>
    <span style="color:#555;">Gidilecek Ülke: {$ülke}</span><br>
    <span style="color:#555;">Seyahat: {$baslangic} – {$bitis}</span>
  </div>
  <a href="{$belgeUrl}" style="display:inline-block;background:#16a34a;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:.95rem;">
    Poliçeyi PDF İndir →
  </a>
  <p style="color:#aaa;font-size:.75rem;margin-top:24px;">GrupTalepleri Sigorta · PAO-Net / Nippon Sigorta</p>
</div>
HTML;

        try {
            Mail::html($html, function ($m) use ($user, $subject) {
                $m->to($user->email, $user->name)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('EmailService::policeHazir hatası: ' . $e->getMessage(), ['police_id' => $police->id]);
        }
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
