<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\Request as TalepModel;
use App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;
use App\Models\Request as RequestModel;

class DashboardController extends Controller
{
    use ResolvesPreviewUser;

    public function index()
    {
        $user = $this->acenteActor();
        $agency = $user->agency;

        $talepler = TalepModel::where('user_id', $user->id)
            ->with([
                'segments',
                'offers'   => fn($q) => $q->whereIn('durum', ['beklemede', 'kabul_edildi'])->orderByRaw("FIELD(durum,'kabul_edildi','beklemede')"),
                'payments' => fn($q) => $q->where('is_active', true),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $istatistik = [
            'toplam'          => $talepler->count(),
            'beklemede'      => $talepler->where('status', 'beklemede')->count(),
            'islemde'         => $talepler->where('status', 'islemde')->count(),
            'fiyatlandirildi' => $talepler->where('status', 'fiyatlandirildi')->count(),
            'iptal'           => $talepler->where('status', 'iptal')->count(),
            'biletlendi'      => $talepler->where('status', 'biletlendi')->count(),
            'depozitoda'      => $talepler->where('status', 'depozitoda')->count(),
        ];

        $haritaVerisi = $talepler
            ->map(function($t) {
                return [
                    'gtpnr'    => $t->gtpnr,
                    'status'   => $t->status,
                    'pax'      => $t->pax_total,
                    'segments' => $t->segments->map(function($s) {
                        return [
                            'from' => $s->from_iata,
                            'to'   => $s->to_iata,
                            'date' => $s->departure_date,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

        // Turai widget için değişkenler
        $adminTelefonlar = \App\Models\User::whereIn('role', ['admin', 'superadmin'])
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->orderByRaw("role = 'superadmin' DESC")
            ->get(['name', 'phone', 'role'])
            ->toArray();

        // Turai greeting: acil özetler
        $turaiOzetler = [];
        foreach ($talepler as $t) {
            if ($t->status === 'iptal') continue;
            $aktifPayment = $t->payments->first(); // is_active=true filtreli yüklendi
            if ($t->aktif_adim === 'odeme_gecikti') {
                $turaiOzetler[] = '⚠️ <strong>' . $t->gtpnr . '</strong> gecikmiş ödeme';
            } elseif ($aktifPayment?->due_date) {
                $saatKaldi = \Carbon\Carbon::parse($aktifPayment->due_date)->diffInHours(now(), false);
                if ($saatKaldi < 0 && $saatKaldi > -48) {
                    $turaiOzetler[] = '⏰ <strong>' . $t->gtpnr . '</strong> 48 saat içinde ödeme vadesi';
                }
            }
        }

        return view('acente.dashboard', compact('talepler', 'haritaVerisi', 'istatistik', 'agency', 'adminTelefonlar', 'turaiOzetler'));
    }
}