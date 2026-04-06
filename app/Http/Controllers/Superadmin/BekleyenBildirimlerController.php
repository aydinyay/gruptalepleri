<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OpsiyonUyariAyar;
use App\Models\RequestPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BekleyenBildirimlerController extends Controller
{
    public function index(Request $request)
    {
        $simdi  = now();
        $ayarlar = OpsiyonUyariAyar::aktifler(); // saat_oncesi, sms_aktif, push_aktif

        // Verilen bir vade için hangi saatlerde bildirim gideceğini hesapla
        $bildirimSaatleri = fn(Carbon $vade) => $ayarlar
            ->map(fn($a) => [
                'saat'  => $vade->copy()->subHours($a->saat_oncesi),
                'gecti' => $vade->copy()->subHours($a->saat_oncesi)->isPast(),
                'sms'   => (bool) $a->sms_aktif,
                'push'  => (bool) $a->push_aktif,
                'email' => true, // email her zaman gider
                'label' => $a->saat_oncesi . ' sa önce',
            ])
            ->values();

        // Güvenli Carbon parse — geçersiz tarih varsa null döner
        $safeParse = function (string $dateStr): ?Carbon {
            try {
                $c = Carbon::parse($dateStr);
                // '0000-00-00' veya anlamsız tarihleri ele
                if ($c->year < 2000) return null;
                return $c;
            } catch (\Throwable) {
                return null;
            }
        };

        // 1. Aktif opsiyonlar — karar bekleyen teklifler, option_date gelecekte
        $opsiyonlar = Offer::whereNotNull('option_date')
            ->where('option_date', '>', '2000-01-01')
            ->where('durum', 'beklemede')
            ->whereHas('request', fn($q) => $q->whereNotIn('status', ['biletlendi', 'olumsuz', 'iade', 'iptal']))
            ->with('request')
            ->get()
            ->map(fn($o) => [
                'tip'        => 'opsiyon',
                'etiket'     => 'Opsiyon',
                'gtpnr'      => $o->request?->gtpnr,
                'acente'     => $o->request?->agency_name,
                'tarih'      => $safeParse($o->option_date . ' ' . ($o->option_time ?: '15:59:59')),
                'detay'      => ($o->airline ?: '?') . ' · ' . number_format((float)$o->total_price, 0, ',', '.') . ' ' . $o->currency,
                'request_id' => $o->request_id,
            ])
            ->filter(fn($r) => $r['tarih'] && $r['tarih']->isFuture())
            ->map(fn($r) => array_merge($r, ['bildirimler' => $bildirimSaatleri($r['tarih'])]));

        // due_date cast Carbon → güvenli string çevirici
        $dueDateStr = fn($p): string =>
            $p->due_date instanceof Carbon
                ? $p->due_date->format('Y-m-d')
                : (string) $p->due_date;

        // 2. Aktif ödemeler — vadesi henüz geçmemiş
        $odemeler = RequestPayment::where('is_active', true)
            ->where('status', 'aktif')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>', '2000-01-01')
            ->with('request')
            ->get()
            ->map(fn($p) => [
                'tip'        => 'odeme',
                'etiket'     => 'Ödeme Vadesi',
                'gtpnr'      => $p->request?->gtpnr,
                'acente'     => $p->request?->agency_name,
                'tarih'      => $safeParse($dueDateStr($p) . ' ' . ($p->due_time ?: '15:59:59')),
                'detay'      => number_format((float)$p->amount, 0, ',', '.') . ' ' . $p->currency,
                'request_id' => $p->request_id,
            ])
            ->filter(fn($r) => $r['tarih'] && $r['tarih']->isFuture())
            ->map(fn($r) => array_merge($r, ['bildirimler' => $bildirimSaatleri($r['tarih'])]));

        // 3. Gecikmiş ödemeler — vadesi geçmiş, hâlâ aktif
        $gecikti = RequestPayment::where('is_active', true)
            ->whereIn('status', ['aktif', 'gecikti'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>', '2000-01-01')
            ->with('request')
            ->get()
            ->map(fn($p) => [
                'tip'        => 'gecikti',
                'etiket'     => 'Gecikmiş',
                'gtpnr'      => $p->request?->gtpnr,
                'acente'     => $p->request?->agency_name,
                'tarih'      => $safeParse($dueDateStr($p) . ' ' . ($p->due_time ?: '15:59:59')),
                'detay'      => number_format((float)$p->amount, 0, ',', '.') . ' ' . $p->currency,
                'request_id' => $p->request_id,
                'bildirimler' => collect(),
            ])
            ->filter(fn($r) => $r['tarih'] && $r['tarih']->isPast());

        $liste = $opsiyonlar
            ->concat($odemeler)
            ->concat($gecikti)
            ->sortBy('tarih')
            ->values();

        return view('admin.bekleyen-bildirimler', compact('liste', 'simdi', 'ayarlar'));
    }
}
