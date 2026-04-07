<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B2C route'larının yalnızca gruprezervasyonlari.com'dan erişilebilir olmasını sağlar.
 * gruptalepleri.com'dan B2C route'larına istek gelirse 404 döner.
 */
class EnsureB2CDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->attributes->get('is_b2c', false)) {
            abort(404);
        }

        return $next($request);
    }
}
