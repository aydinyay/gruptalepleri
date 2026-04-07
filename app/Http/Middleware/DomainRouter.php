<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B2C Domain Tespiti Middleware
 *
 * gruprezervasyonlari.com'dan gelen istekleri tespit eder ve:
 * - Request'e 'is_b2c' attribute'u ekler (controller'larda kullanılabilir)
 * - B2C session cookie adını B2B'den farklı yapar (session karışımını önler)
 * - Config değerini runtime'da override eder
 *
 * Bu middleware web group'a eklenir ve tüm isteklerde çalışır.
 * B2B tarafı için herhangi bir değişiklik yapmaz.
 */
class DomainRouter
{
    public function handle(Request $request, Closure $next): Response
    {
        $b2cDomain = config('b2c.domain', 'gruprezervasyonlari.com');
        $host = $request->getHost();

        // www. prefix'i kaldır (www.gruprezervasyonlari.com = gruprezervasyonlari.com)
        $host = preg_replace('/^www\./', '', $host);

        $isB2C = ($host === $b2cDomain);

        // Request'e flag ekle — controller'lar $request->is_b2c ile okuyabilir
        $request->attributes->set('is_b2c', $isB2C);

        if ($isB2C) {
            // B2C session cookie adını override et — B2B cookie'siyle çakışma olmaz
            config(['session.cookie' => config('b2c.session_cookie', 'gruprezervasyonlari-session')]);
        }

        return $next($request);
    }
}
