<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\RequestPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BekleyenBildirimlerController extends Controller
{
    public function index(Request $request)
    {
        $simdi = now();

        // 1. Aktif opsiyonlar — karar bekleyen teklifler, option_date gelecekte
        $opsiyonlar = Offer::whereNotNull('option_date')
            ->where('durum', 'beklemede')
            ->whereHas('request', fn($q) => $q->whereNotIn('status', ['biletlendi', 'olumsuz', 'iade', 'iptal']))
            ->with('request')
            ->get()
            ->map(fn($o) => [
                'tip'        => 'opsiyon',
                'etiket'     => 'Opsiyon',
                'gtpnr'      => $o->request?->gtpnr,
                'acente'     => $o->request?->agency_name,
                'tarih'      => Carbon::parse($o->option_date . ' ' . ($o->option_time ?: '15:59:59')),
                'detay'      => ($o->airline ?: '?') . ' · ' . number_format((float)$o->total_price, 0, ',', '.') . ' ' . $o->currency,
                'gidecek'    => 'Opsiyon uyarısı — SMS + Email + Push (vade öncesi)',
                'request_id' => $o->request_id,
            ])
            ->filter(fn($r) => $r['tarih']->isFuture());

        // 2. Aktif ödemeler — vadesi henüz geçmemiş
        $odemeler = RequestPayment::where('is_active', true)
            ->where('status', 'aktif')
            ->whereNotNull('due_date')
            ->with('request')
            ->get()
            ->map(fn($p) => [
                'tip'        => 'odeme',
                'etiket'     => 'Ödeme Vadesi',
                'gtpnr'      => $p->request?->gtpnr,
                'acente'     => $p->request?->agency_name,
                'tarih'      => Carbon::parse(Carbon::parse($p->due_date)->format('Y-m-d') . ' ' . ($p->due_time ?: '15:59:59')),
                'detay'      => number_format((float)$p->amount, 0, ',', '.') . ' ' . $p->currency,
                'gidecek'    => 'Ödeme vadesi — SMS + Email + Push (vade öncesi otomatik)',
                'request_id' => $p->request_id,
            ])
            ->filter(fn($r) => $r['tarih']->isFuture());

        // 3. Gecikmiş ödemeler — vadesi geçmiş, hâlâ aktif
        $gecikti = RequestPayment::where('is_active', true)
            ->whereIn('status', ['aktif', 'gecikti'])
            ->whereNotNull('due_date')
            ->with('request')
            ->get()
            ->map(fn($p) => [
                'tip'        => 'gecikti',
                'etiket'     => 'Gecikmiş',
                'gtpnr'      => $p->request?->gtpnr,
                'acente'     => $p->request?->agency_name,
                'tarih'      => Carbon::parse(Carbon::parse($p->due_date)->format('Y-m-d') . ' ' . ($p->due_time ?: '15:59:59')),
                'detay'      => number_format((float)$p->amount, 0, ',', '.') . ' ' . $p->currency,
                'gidecek'    => 'Otomatik bildirim gönderilmez — manuel müdahale gerekli',
                'request_id' => $p->request_id,
            ])
            ->filter(fn($r) => $r['tarih']->isPast());

        // Hepsini birleştir, tarihe göre sırala
        $liste = $opsiyonlar
            ->concat($odemeler)
            ->concat($gecikti)
            ->sortBy('tarih')
            ->values();

        return view('admin.bekleyen-bildirimler', compact('liste', 'simdi'));
    }
}
