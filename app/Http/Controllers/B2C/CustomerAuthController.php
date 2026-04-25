<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class CustomerAuthController extends Controller
{
    // ── Giriş ─────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('b2c.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('b2c')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $intended = session('intended', route('b2c.account.index'));
            return redirect($intended);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'E-posta adresi veya şifre hatalı.']);
    }

    // ── Kayıt ─────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('b2c.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:180|unique:b2c_users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = B2cUser::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);

        Auth::guard('b2c')->login($user);
        $request->session()->regenerate();

        return redirect()->route('b2c.account.index')
            ->with('success', 'Hoş geldiniz, ' . $user->name . '!');
    }

    // ── Çıkış ─────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::guard('b2c')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('b2c.home');
    }

    // ── Şifre Sıfırlama ───────────────────────────────────────────────────

    public function showForgotPassword()
    {
        return view('b2c.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('b2c_users')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.');
        }

        // E-posta kayıtlı değilse de güvenlik gereği aynı mesajı göster (enumeration önlemi)
        \Illuminate\Support\Facades\Log::warning('B2C reset link hatası', ['email' => $request->email, 'status' => $status]);
        return back()->with('status', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.');
    }

    public function showResetPassword(string $token)
    {
        return view('b2c.auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('b2c_users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (B2cUser $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('b2c.auth.login')->with('success', 'Şifreniz başarıyla sıfırlandı.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
