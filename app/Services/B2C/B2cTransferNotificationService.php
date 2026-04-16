<?php

namespace App\Services\B2C;

use App\Models\RequestNotification;
use App\Models\SistemAyar;
use App\Models\TransferBooking;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * B2C Transfer Bildirim Servisi
 *
 * Rezervasyon onaylandığında:
 *  - Müşteriye HTML e-posta (voucher linki dahil)
 *  - Superadmin'e B2C yeni rezervasyon bildirimi
 *  - Tedarikçi kullanıcıya bildirim (opsiyonel, varsa)
 */
class B2cTransferNotificationService
{
    /**
     * Ödeme tamamlandı, rezervasyon onaylandı.
     */
    public function bookingConfirmed(TransferBooking $booking): void
    {
        if (! SistemAyar::emailEnabled()) {
            return;
        }

        $booking->loadMissing(['supplier', 'airport', 'zone', 'vehicleType']);

        $this->sendCustomerConfirmation($booking);
        $this->notifyAdmins($booking);
        $this->notifySupplierUser($booking);
    }

    // ── Müşteri Onay E-postası ─────────────────────────────────────────────

    private function sendCustomerConfirmation(TransferBooking $booking): void
    {
        $email = $booking->b2c_contact_email;
        if (! $email) {
            return;
        }

        $snap         = $booking->price_snapshot_json ?? [];
        $snapData     = $snap['snapshot'] ?? [];
        $airportName  = ($snapData['airport']['code'] ?? '') . ' — ' . ($snapData['airport']['name'] ?? $booking->airport?->name ?? '');
        $zoneName     = $snapData['zone']['name'] ?? $booking->zone?->name ?? '';
        $vehicleName  = $booking->vehicleType?->name ?? 'Transfer Aracı';
        $pickupAt     = $booking->pickup_at->format('d.m.Y H:i');
        $dirLabel     = ['ARR' => 'Varış', 'DEP' => 'Gidiş', 'BOTH' => 'Gidiş-Dönüş'][$booking->direction] ?? $booking->direction;
        $amountFmt    = number_format((float) $booking->total_amount, 0, ',', '.') . ' ' . $booking->currency;
        $bookingUrl   = route('b2c.transfer.booking', ['bookingRef' => $booking->booking_ref]);

        $html = $this->buildCustomerEmailHtml(
            contactName:  $booking->b2c_contact_name ?? '',
            bookingRef:   $booking->booking_ref,
            airportName:  $airportName,
            zoneName:     $zoneName,
            direction:    $dirLabel,
            pickupAt:     $pickupAt,
            vehicleName:  $vehicleName,
            pax:          $booking->pax,
            amount:       $amountFmt,
            bookingUrl:   $bookingUrl,
        );

        $subject = '✅ Transfer Rezervasyonunuz Onaylandı — ' . $booking->booking_ref;

        $status = 'sent';
        try {
            Mail::html($html, function ($m) use ($email, $booking, $subject) {
                $m->to($email, $booking->b2c_contact_name ?? $email)
                  ->subject($subject);
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            Log::warning('B2cTransferNotification müşteri e-posta hatası', [
                'booking_ref' => $booking->booking_ref,
                'email'       => $email,
                'error'       => $e->getMessage(),
            ]);
        }

        RequestNotification::create([
            'request_id'     => null,
            'channel'        => 'email',
            'recipient'      => 'b2c_customer',
            'recipient_name' => $booking->b2c_contact_name ?? $email,
            'phone'          => null,
            'message'        => "Transfer rezervasyonu onaylandı: {$booking->booking_ref}",
            'subject'        => $subject,
            'status'         => $status,
            'sent_at'        => now(),
        ]);
    }

    // ── Admin Bildirimi ────────────────────────────────────────────────────

    private function notifyAdmins(TransferBooking $booking): void
    {
        $snap         = $booking->price_snapshot_json ?? [];
        $snapData     = $snap['snapshot'] ?? [];
        $airportName  = ($snapData['airport']['code'] ?? '') . ' — ' . ($snapData['airport']['name'] ?? '');
        $zoneName     = $snapData['zone']['name'] ?? '';
        $vehicleName  = $booking->vehicleType?->name ?? 'Transfer Aracı';
        $pickupAt     = $booking->pickup_at->format('d.m.Y H:i');
        $amountFmt    = number_format((float) $booking->total_amount, 0, ',', '.') . ' ' . $booking->currency;

        $html = $this->buildAdminEmailHtml(
            bookingRef:  $booking->booking_ref,
            contactName: $booking->b2c_contact_name ?? '',
            airportName: $airportName,
            zoneName:    $zoneName,
            pickupAt:    $pickupAt,
            vehicleName: $vehicleName,
            amount:      $amountFmt,
        );

        $subject = '🚗 Yeni B2C Transfer Rezervasyonu: ' . $booking->booking_ref;

        $admins = User::whereIn('role', ['admin', 'superadmin'])->whereNotNull('email')->get();
        foreach ($admins as $admin) {
            try {
                Mail::html($html, fn ($m) => $m->to($admin->email, $admin->name)->subject($subject));
            } catch (\Throwable $e) {
                Log::warning('B2cTransferNotification admin e-posta hatası', ['error' => $e->getMessage()]);
            }
        }
    }

    // ── Tedarikçi Bildirimi ────────────────────────────────────────────────

    private function notifySupplierUser(TransferBooking $booking): void
    {
        $supplierUser = $booking->supplier?->user;
        if (! $supplierUser || ! $supplierUser->email) {
            return;
        }

        $snap        = $booking->price_snapshot_json ?? [];
        $snapData    = $snap['snapshot'] ?? [];
        $airportName = ($snapData['airport']['code'] ?? '') . ' — ' . ($snapData['airport']['name'] ?? '');
        $zoneName    = $snapData['zone']['name'] ?? '';
        $pickupAt    = $booking->pickup_at->format('d.m.Y H:i');
        $vehicleName = $booking->vehicleType?->name ?? 'Transfer Aracı';

        $html = $this->buildSupplierEmailHtml(
            bookingRef:  $booking->booking_ref,
            contactName: $booking->b2c_contact_name ?? '',
            phone:       $booking->b2c_contact_phone ?? '',
            airportName: $airportName,
            zoneName:    $zoneName,
            pickupAt:    $pickupAt,
            vehicleName: $vehicleName,
            pax:         $booking->pax,
        );

        $subject = '🚗 Yeni Transfer Rezervasyonu: ' . $booking->booking_ref;

        try {
            Mail::html($html, fn ($m) => $m->to($supplierUser->email, $supplierUser->name)->subject($subject));
        } catch (\Throwable $e) {
            Log::warning('B2cTransferNotification tedarikçi e-posta hatası', ['error' => $e->getMessage()]);
        }
    }

    // ── HTML Şablonları ────────────────────────────────────────────────────

    private function buildCustomerEmailHtml(
        string $contactName,
        string $bookingRef,
        string $airportName,
        string $zoneName,
        string $direction,
        string $pickupAt,
        string $vehicleName,
        int    $pax,
        string $amount,
        string $bookingUrl,
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px;">
<table width="580" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.09);">
  <tr><td style="background:linear-gradient(135deg,#0f2444,#1a3c6b);padding:28px 36px;text-align:center;">
    <p style="margin:0;color:#fff;font-size:1.4rem;font-weight:800;letter-spacing:.5px;">GrupRezervasyonları</p>
    <p style="margin:6px 0 0;color:rgba(255,255,255,.7);font-size:.9rem;">Transfer Rezervasyonu</p>
  </td></tr>
  <tr><td style="padding:32px 36px;">
    <p style="margin:0 0 8px;font-size:1.1rem;font-weight:700;color:#1e293b;">Merhaba, {$contactName}!</p>
    <p style="margin:0 0 24px;color:#64748b;line-height:1.6;">Transfer rezervasyonunuz onaylandı. Aşağıda rezervasyon detaylarınızı bulabilirsiniz.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;font-size:.9rem;margin-bottom:24px;">
      <tr style="background:#f8faff;"><td colspan="2" style="padding:12px 16px;font-weight:700;color:#1a3c6b;font-size:.85rem;letter-spacing:.5px;text-transform:uppercase;">Rezervasyon Detayları</td></tr>
      <tr style="border-top:1px solid #e2e8f0;"><td style="padding:10px 16px;color:#64748b;width:40%;">Rezervasyon Kodu</td><td style="padding:10px 16px;font-weight:700;color:#1a3c6b;font-size:1rem;">{$bookingRef}</td></tr>
      <tr style="border-top:1px solid #e2e8f0;background:#fafafa;"><td style="padding:10px 16px;color:#64748b;">Havalimanı</td><td style="padding:10px 16px;font-weight:600;">{$airportName}</td></tr>
      <tr style="border-top:1px solid #e2e8f0;"><td style="padding:10px 16px;color:#64748b;">Bölge / Otel</td><td style="padding:10px 16px;font-weight:600;">{$zoneName}</td></tr>
      <tr style="border-top:1px solid #e2e8f0;background:#fafafa;"><td style="padding:10px 16px;color:#64748b;">Yön</td><td style="padding:10px 16px;">{$direction}</td></tr>
      <tr style="border-top:1px solid #e2e8f0;"><td style="padding:10px 16px;color:#64748b;">Tarih / Saat</td><td style="padding:10px 16px;font-weight:600;">{$pickupAt}</td></tr>
      <tr style="border-top:1px solid #e2e8f0;background:#fafafa;"><td style="padding:10px 16px;color:#64748b;">Araç</td><td style="padding:10px 16px;">{$vehicleName}</td></tr>
      <tr style="border-top:1px solid #e2e8f0;"><td style="padding:10px 16px;color:#64748b;">Yolcu</td><td style="padding:10px 16px;">{$pax} kişi</td></tr>
      <tr style="border-top:2px solid #1a3c6b;"><td style="padding:12px 16px;font-weight:700;color:#1a3c6b;">Toplam Tutar</td><td style="padding:12px 16px;font-weight:800;color:#FF5533;font-size:1.1rem;">{$amount}</td></tr>
    </table>
    <p style="text-align:center;margin:0 0 8px;">
      <a href="{$bookingUrl}" style="display:inline-block;background:#FF5533;color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:1rem;">Rezervasyonu Görüntüle</a>
    </p>
    <p style="text-align:center;font-size:.8rem;color:#94a3b8;margin:0;">Seyahatinizde başarılar dileriz.</p>
  </td></tr>
  <tr><td style="padding:16px 36px;background:#f8faff;text-align:center;font-size:.78rem;color:#94a3b8;border-top:1px solid #e2e8f0;">
    Bu e-posta GrupRezervasyonları tarafından otomatik olarak gönderilmiştir.
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
    }

    private function buildAdminEmailHtml(
        string $bookingRef,
        string $contactName,
        string $airportName,
        string $zoneName,
        string $pickupAt,
        string $vehicleName,
        string $amount,
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<body style="margin:0;padding:20px;background:#f4f6f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1a3c6b;padding:18px 28px;">
    <span style="color:#fff;font-size:18px;font-weight:bold;">🚗 Yeni B2C Transfer Rezervasyonu</span>
  </td></tr>
  <tr><td style="padding:24px 28px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;width:40%;">Rezervasyon Kodu</td><td style="padding:8px 4px;font-weight:bold;color:#1a3c6b;">{$bookingRef}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Müşteri</td><td style="padding:8px 4px;">{$contactName}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Havalimanı</td><td style="padding:8px 4px;">{$airportName}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Bölge</td><td style="padding:8px 4px;">{$zoneName}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Tarih</td><td style="padding:8px 4px;">{$pickupAt}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Araç</td><td style="padding:8px 4px;">{$vehicleName}</td></tr>
      <tr><td style="padding:8px 4px;color:#888;">Tutar</td><td style="padding:8px 4px;font-weight:bold;color:#FF5533;">{$amount}</td></tr>
    </table>
  </td></tr>
  <tr><td style="padding:12px 28px;background:#f9f9f9;font-size:12px;color:#aaa;text-align:center;">GrupRezervasyonları B2C Sistem Bildirimi</td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
    }

    private function buildSupplierEmailHtml(
        string $bookingRef,
        string $contactName,
        string $phone,
        string $airportName,
        string $zoneName,
        string $pickupAt,
        string $vehicleName,
        int    $pax,
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<body style="margin:0;padding:20px;background:#f4f6f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#0f2444;padding:18px 28px;">
    <span style="color:#fff;font-size:18px;font-weight:bold;">🚗 Yeni Transfer Görevi</span>
  </td></tr>
  <tr><td style="padding:24px 28px;">
    <p style="margin:0 0 16px;font-size:14px;color:#444;">Platformumuz üzerinden yeni bir transfer rezervasyonu oluşturuldu:</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;width:40%;">Rezervasyon No</td><td style="padding:8px 4px;font-weight:bold;">{$bookingRef}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Müşteri Adı</td><td style="padding:8px 4px;">{$contactName}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">İletişim</td><td style="padding:8px 4px;">{$phone}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Havalimanı</td><td style="padding:8px 4px;">{$airportName}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Bölge</td><td style="padding:8px 4px;">{$zoneName}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Tarih / Saat</td><td style="padding:8px 4px;font-weight:bold;">{$pickupAt}</td></tr>
      <tr style="border-bottom:1px solid #eee;"><td style="padding:8px 4px;color:#888;">Araç</td><td style="padding:8px 4px;">{$vehicleName}</td></tr>
      <tr><td style="padding:8px 4px;color:#888;">Yolcu Sayısı</td><td style="padding:8px 4px;">{$pax} kişi</td></tr>
    </table>
  </td></tr>
  <tr><td style="padding:12px 28px;background:#f9f9f9;font-size:12px;color:#aaa;text-align:center;">GrupRezervasyonları — Bu görevin detayları sisteme kaydedilmiştir.</td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
    }
}
