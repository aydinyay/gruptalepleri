<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DavetController extends Controller
{
    public function show(string $token)
    {
        $calisan = User::where('davet_token', $token)
            ->where('davet_expires_at', '>', now())
            ->firstOrFail();

        return view('davet', compact('calisan', 'token'));
    }

    public function kabul(Request $request, string $token)
    {
        $calisan = User::where('davet_token', $token)
            ->where('davet_expires_at', '>', now())
            ->firstOrFail();

        $request->validate([
            'name'     => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $calisan->update([
            'name'             => $request->name,
            'password'         => Hash::make($request->password),
            'davet_token'      => null,
            'davet_expires_at' => null,
            'email_verified_at' => now(),
        ]);

        Auth::login($calisan);

        return redirect()->route('acente.dashboard')->with('success', 'Hesabınız aktifleştirildi. Hoş geldiniz!');
    }
}
