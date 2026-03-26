<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $whatsAppService = app(WhatsAppService::class);

        $status = Password::sendResetLink(
            $request->only('email'),
            function (User $user, string $token) use ($whatsAppService): void {
                // Mevcut e-posta akisini aynen koru
                $user->sendPasswordResetNotification($token);

                // Opsiyonel WhatsApp kanali (feature flag aciksa)
                if (! $whatsAppService->isEnabled()) {
                    return;
                }

                $resetUrl = route('password.reset', [
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ]);

                $whatsAppService->sendPasswordResetLink($user, $resetUrl);
            }
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
