<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use ResolvesPreviewUser;

    public function edit()
    {
        $user   = $this->acenteActor();
        $acente = $user->agency;
        return view('acente.profil', compact('user', 'acente'));
    }

    public function update(Request $request)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            // Acente alanları
            'tourism_title'  => 'nullable|string|max:255',
            'company_title'  => 'nullable|string|max:255',
            'tax_number'     => 'nullable|string|max:20',
            'tax_office'     => 'nullable|string|max:100',
            'address'        => 'nullable|string|max:500',
            'contact_name'   => 'nullable|string|max:255',
            'tursab_no'      => 'nullable|string|max:50',
        ]);

        // User güncelle
        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        // Agency güncelle veya oluştur
        $user->agency()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone'         => $request->phone,
                'email'         => $request->email,
                'tourism_title' => $request->tourism_title,
                'company_title' => $request->company_title,
                'tax_number'    => $request->tax_number,
                'tax_office'    => $request->tax_office,
                'address'       => $request->address,
                'contact_name'  => $request->contact_name,
                'tursab_no'     => $request->tursab_no,
            ]
        );

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }

    public function updatePassword(Request $request)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifreniz hatalı.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success_sifre', 'Şifreniz başarıyla güncellendi.');
    }
}
