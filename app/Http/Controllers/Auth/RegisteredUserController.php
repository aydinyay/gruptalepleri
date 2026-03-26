<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Agency;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'phone'         => ['required', 'string', 'max:20'],
            'company_title' => ['required', 'string', 'max:255'],
            'tourism_title' => ['nullable', 'string', 'max:255'],
            'tursab_no'     => ['nullable', 'string', 'max:50'],
            'tax_number'    => ['nullable', 'string', 'max:20'],
            'tax_office'    => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'acente',
        ]);

        Agency::create([
            'user_id'       => $user->id,
            'contact_name'  => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'company_title' => $request->company_title,
            'tourism_title' => $request->tourism_title,
            'tursab_no'     => $request->tursab_no,
            'tax_number'    => $request->tax_number,
            'tax_office'    => $request->tax_office,
            'is_active'     => true,
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Push + SMS bildirimi
        try {
            (new \App\Services\NotificationService())->yeniAcente($request->company_title, $request->name, $request->phone);
            $mesaj = 'GT YENI ACENTE! ' . $request->company_title . ' firmasi kayit oldu. Yetkili: ' . $request->name . ' / ' . $request->phone;
            (new \App\Services\SmsService())->sendByEvent('new_agency', null, $mesaj);
            (new \App\Services\EmailService())->yeniAcente($request->company_title, $request->name, $request->phone, $request->email, route('superadmin.acenteler'));
        } catch (\Exception $e) {
            // Bildirim hatası kaydı engellemesin
        }

        return redirect()->route('acente.dashboard');
    }
}
