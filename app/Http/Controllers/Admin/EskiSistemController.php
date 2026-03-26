<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EskiSistemController extends Controller
{
    public function index(Request $request)
    {
        $opsis = $request->input('opsis', 'guncel');
        $q     = $request->input('q', '');

        try {
            $legacy = DB::connection('legacy');

            // Sayımlar
            $counts = [
                'toplam'         => $legacy->table('grupmesajlari')->count(),
                'guncel'         => $legacy->table('grupmesajlari')->whereNotIn('islemdurumu', ['3','4'])->count(),
                'biletlendi'     => $legacy->table('grupmesajlari')->where('islemdurumu','4')->count(),
                'olumsuz'        => $legacy->table('grupmesajlari')->where('islemdurumu','3')->count(),
                'beklemede'      => $legacy->table('grupmesajlari')->where('islemdurumu','0')->count(),
                'islemde'        => $legacy->table('grupmesajlari')->where('islemdurumu','1')->count(),
                'fiyatlandirıldi'=> $legacy->table('grupmesajlari')->where('islemdurumu','2')->where('opsiyontarihi','>=',now()->toDateString())->count(),
                'opsiyonbitmis'  => $legacy->table('grupmesajlari')->whereNotNull('opsiyontarihi')->whereRaw('LENGTH(opsiyontarihi) >= 10')->where('opsiyontarihi','<',now()->toDateString())->whereNotIn('islemdurumu',['3','4'])->count(),
                'depozito'       => $legacy->table('grupmesajlari')->where('islemdurumu','5')->count(),
            ];

            $query = $legacy->table('grupmesajlari');

            switch ($opsis) {
                case 'hepsi':           break;
                case 'guncel':          $query->whereNotIn('islemdurumu',['3','4']); break;
                case 'olumsuz':         $query->where('islemdurumu','3'); break;
                case 'beklemede':       $query->where('islemdurumu','1'); break;
                case 'bugunvesonrasi':  $query->where('islemdurumu','2')->where('opsiyontarihi','>=',now()->toDateString()); break;
                case 'opsiyonbitmis':   $query->whereNotNull('opsiyontarihi')->whereRaw('LENGTH(opsiyontarihi) >= 10')->where('opsiyontarihi','<',now()->toDateString())->whereNotIn('islemdurumu',['3','4']); break;
                case 'islemealinmamis': $query->where('islemdurumu','0'); break;
                case 'ok':              $query->where('islemdurumu','4'); break;
                case 'depozito':        $query->where('islemdurumu','5'); break;
                default:                $query->whereNotIn('islemdurumu',['3','4']); break;
            }

            if ($q) {
                $query->where(function($w) use ($q) {
                    $w->where('gtpnr','like',"%$q%")
                      ->orWhere('acentaadi','like',"%$q%")
                      ->orWhere('email','like',"%$q%")
                      ->orWhere('telefon','like',"%$q%");
                });
            }

            $talepler = $query->orderBy('id','desc')->paginate(50)->withQueryString();

        } catch (\Throwable $e) {
            return view('admin.eski-sistem.index', ['hata' => 'Bağlantı hatası: ' . $e->getMessage()]);
        }

        return view('admin.eski-sistem.index', compact('talepler','counts','opsis','q'));
    }

    public function show($gtpnr)
    {
        $gtpnr = strtoupper(trim($gtpnr));

        try {
            $talep = DB::connection('legacy')
                ->table('grupmesajlari')
                ->where('gtpnr', $gtpnr)
                ->first();
        } catch (\Throwable $e) {
            return view('admin.eski-sistem.index', [
                'hata'  => 'Eski sisteme bağlanılamadı: ' . $e->getMessage(),
                'gtpnr' => $gtpnr,
            ]);
        }

        if (!$talep) {
            return redirect()->route('admin.eski-sistem')->with('hata', '"' . $gtpnr . '" eski sistemde bulunamadı.');
        }

        // Yeni sistemdeki karşılığını bul
        $yeniTalep = DB::table('requests')
            ->where('gtpnr', $gtpnr)
            ->first();

        $yeniTeklifler = collect();
        $yeniOdemeler  = collect();
        if ($yeniTalep) {
            $yeniTeklifler = DB::table('offers')->where('request_id', $yeniTalep->id)->get();
            $yeniOdemeler  = DB::table('request_payments')->where('request_id', $yeniTalep->id)->get();
        }

        return view('admin.eski-sistem.show', compact('talep','yeniTalep','yeniTeklifler','yeniOdemeler'));
    }
}
