<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class EskiSistemController extends Controller
{
    public function index()
    {
        return view('admin.eski-sistem.index');
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
            return view('admin.eski-sistem.index', [
                'hata'  => '"' . $gtpnr . '" eski sistemde bulunamadı.',
                'gtpnr' => $gtpnr,
            ]);
        }

        return view('admin.eski-sistem.show', compact('talep'));
    }
}
