<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerProfileController extends Controller
{
    public function index()
    {
        $user   = Auth::guard('b2c')->user();
        $orders = $user->orders()->with('item')->latest()->limit(5)->get();

        return view('b2c.account.index', compact('user', 'orders'));
    }

    public function edit()
    {
        $user = Auth::guard('b2c')->user();
        return view('b2c.account.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('b2c')->user();

        $validated = $request->validate([
            'name'                 => 'required|string|max:120',
            'phone'                => 'nullable|string|max:30',
            'current_password'     => 'nullable|string',
            'password'             => 'nullable|string|min:8|confirmed',
        ]);

        if ($validated['current_password'] ?? null) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Mevcut şifre hatalı.']);
            }
            $user->password = $validated['password'];
        }

        $user->name  = $validated['name'];
        $user->phone = $validated['phone'] ?? null;
        $user->save();

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }
}
