<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\Request as TalepModel;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $agency = $user->agency;

        $talepler = TalepModel::where('user_id', $user->id)
            ->with(['segments', 'offers' => fn($q) => $q->where('is_visible', true)->where('price_per_pax', '>', 0)])
            ->orderBy('created_at', 'desc')
            ->get();

        $istatistik = [
            'toplam'          => $talepler->count(),
            'beklemede'       => $talepler->where('status', 'beklemede')->count(),
            'islemde'         => $talepler->where('status', 'islemde')->count(),
            'fiyatlandirıldi' => $talepler->where('status', 'fiyatlandirıldi')->count(),
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

        return view('acente.dashboard', compact('talepler', 'haritaVerisi', 'istatistik', 'agency'));
    }
}