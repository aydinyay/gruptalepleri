<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * B2C Kullanıcı Auth Middleware
 *
 * B2C korumalı route'larda (hesabım, sipariş, ödeme) kullanılır.
 * B2B guard (web) yerine 'b2c' guard'ını kontrol eder.
 * Giriş yapmamış B2C kullanıcıyı /hesabim/giris sayfasına yönlendirir.
 */
class EnsureB2CAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('b2c')->check()) {
            // AJAX isteği ise 401 döndür
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Giriş yapmanız gerekiyor.'], 401);
            }

            // Giriş sonrası gidilmek istenen URL'yi hatırlat
            return redirect()->route('b2c.auth.login')
                ->with('intended', $request->url());
        }

        return $next($request);
    }
}
