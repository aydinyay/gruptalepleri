<?php

namespace App\Http\Controllers;

use App\Models\EmailAbone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailAboneController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $email = strtolower(trim($request->email));

        $abone = EmailAbone::firstOrNew(['email' => $email]);

        if ($abone->exists && $abone->aktif) {
            return back()->with('abone_mesaj', 'Bu e-posta zaten kayıtlı.')->with('abone_durum', 'info');
        }

        $abone->token  = Str::random(64);
        $abone->ip     = $request->ip();
        $abone->aktif  = true;
        $abone->save();

        return back()->with('abone_mesaj', 'E-posta adresiniz kaydedildi.')->with('abone_durum', 'ok');
    }

    public function iptal(string $token)
    {
        $abone = EmailAbone::where('token', $token)->firstOrFail();

        return view('abonelik.misafir-confirm', compact('abone'));
    }

    public function iptalOnayla(string $token)
    {
        $abone = EmailAbone::where('token', $token)->firstOrFail();
        $abone->update(['aktif' => false]);

        return view('abonelik.misafir-sonlandi', compact('abone'));
    }

    public function baslatOnayla(string $token)
    {
        $abone = EmailAbone::where('token', $token)->firstOrFail();
        $abone->update(['aktif' => true]);

        return view('abonelik.misafir-sonlandi', ['abone' => $abone, 'yenidenUye' => true]);
    }
}
