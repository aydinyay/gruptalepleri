<?php

namespace App\Services;

use App\Models\BroadcastNotification;
use App\Models\Request as TalepModel;
use App\Models\RequestNotification;
use App\Models\SistemAyar;
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
        $data    = compact('gtpnr', 'agencyName', 'paxTotal', 'adminUrl');

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            $this->send($requestId, $user, $subject, 'emails.yeni_talep', $data);
        }
    }

    /**
     * Teklif eklendiğinde acenteye email gönder + superadmin CC.
     */
    public function teklifEklendi(int $requestId, int $agencyUserId, string $gtpnr, string $airline, string $acenteUrl): void
    {
        $user = User::find($agencyUserId);
        if (! $user || ! $user->email) return;

        $subject = "✈️ Teklifiniz Hazır: {$gtpnr}";
        $data    = compact('gtpnr', 'airline', 'acenteUrl');
        $this->send($requestId, $user, $subject, 'emails.teklif_eklendi', $data);

        // Superadmin CC
        $this->ccSuperadmin($requestId, $subject . ' [CC: ' . $user->name . ']', 'emails.teklif_eklendi', $data);
    }

    /**
     * Teklif kabul edildiğinde admin + superadmin'e email gönder.
     */
    public function teklifKabul(int $requestId, string $gtpnr, string $agencyName, string $airline, string $adminUrl): void
    {
        $subject = "✅ Teklif Kabul Edildi: {$gtpnr}";
        $data    = compact('gtpnr', 'agencyName', 'airline', 'adminUrl');

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            $this->send($requestId, $user, $subject, 'emails.teklif_kabul', $data);
        }
    }

    /**
     * Ödeme vadesi yaklaşıyor — admin + superadmin'e email.
     */
    public function opsiyonUyarisi(int $requestId, string $gtpnr, string $airline, int $saatKaldi, string $opsiyonBitis, string $adminUrl): void
    {
        $subject = "⚠️ Ödeme Vadesi Uyarısı: {$gtpnr} — {$saatKaldi} saat kaldı";
        $data    = compact('gtpnr', 'airline', 'saatKaldi', 'opsiyonBitis', 'adminUrl');

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            $this->send($requestId, $user, $subject, 'emails.opsiyon_uyarisi', $data);
        }
    }

    /**
     * Hoş geldiniz emaili — yeni kaydolan acentenin kendisine.
     */
    public function hosgeldiniz(User $user, string $companyTitle, string $contactName, string $dashboardUrl): void
    {
        $subject = "GrupTalepleri'ne Hoş Geldiniz — {$companyTitle}";
        $data    = compact('companyTitle', 'contactName', 'dashboardUrl');
        $this->send(null, $user, $subject, 'emails.hosgeldiniz', $data);
    }

    /**
     * Yeni acente kaydı — admin + superadmin'e email.
     */
    public function yeniAcente(string $companyTitle, string $contactName, string $phone, string $email, string $adminUrl): void
    {
        $subject = "🏢 Yeni Acente Kaydı: {$companyTitle}";
        $data    = compact('companyTitle', 'contactName', 'phone', 'email', 'adminUrl');

        $alicilar = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($alicilar as $user) {
            $this->send(null, $user, $subject, 'emails.yeni_acente', $data);
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
        $subject = "📋 Talep Durumu Güncellendi: {$gtpnr} → {$yeniDurumEtiket}";
        $data    = compact('gtpnr', 'eskiDurum', 'yeniDurum', 'yeniDurumEtiket', 'acenteUrl');
        $this->send($requestId, $user, $subject, 'emails.durum_degisti', $data);

        // Superadmin CC
        $this->ccSuperadmin($requestId, $subject . ' [CC: ' . $user->name . ']', 'emails.durum_degisti', $data);
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
        if (! SistemAyar::emailEnabled()) {
            return;
        }

        if (! $user->email) return;

        $emoji   = $broadcast->emoji ?? '📢';
        $subject = "{$emoji} {$broadcast->title}";
        $data    = [
            'title'  => $broadcast->title,
            'body'   => $broadcast->message,
            'emoji'  => $emoji,
            'sender' => $broadcast->sender?->name ?? 'GrupTalepleri',
        ];

        $status = 'sent';
        try {
            Mail::send('emails.broadcast', $data, function ($m) use ($user, $subject) {
                $m->to($user->email, $user->name)->subject($subject);
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
     * Gerçek gönderim + log + push bildirimi "email gönderildi".
     * $requestId null olabilir (örn: yeni acente kaydı gibi talep bağımsız olaylar).
     */
    private function send(?int $requestId, User $user, string $subject, string $view, array $data): void
    {
        if (! SistemAyar::emailEnabled()) {
            return;
        }

        $adminEmailCopy = $user->role === 'acente' && SistemAyar::adminEmailCopyEnabled();
        $adminEmail = 'destek@gruptalepleri.com';

        $status = 'sent';
        try {
            Mail::send($view, $data, function ($m) use ($user, $subject, $adminEmailCopy, $adminEmail) {
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
            'message'        => strip_tags(view($view, $data)->render()),
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
