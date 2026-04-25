<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GR locale tespiti: URL prefix → cookie → tr (default)
 *
 * Global middleware olarak çalışır (routing öncesi).
 * /en/transfer → locale=en, path rewrite to /transfer
 * Cookie gr_locale varsa ve URL prefix yoksa cookie locale'i kullan.
 */
class SetLocale
{
    const SUPPORTED = ['en', 'ar', 'ru', 'de', 'fr', 'fa'];

    public function handle(Request $request, Closure $next): Response
    {
        // Sadece B2C domain'de çalış
        $b2cDomain = config('b2c.domain', 'gruprezervasyonlari.com');
        $host = preg_replace('/^www\./', '', $request->getHost());

        if ($host !== $b2cDomain) {
            return $next($request);
        }

        $path     = $request->path(); // "en/transfer" veya "transfer"
        $segments = explode('/', $path, 2);
        $first    = $segments[0] ?? '';

        if (in_array($first, self::SUPPORTED)) {
            $locale  = $first;
            $newPath = isset($segments[1]) ? $segments[1] : '';

            // Path'i yeniden yaz — router locale prefix'siz route'u eşleştirir
            $query  = $request->server('QUERY_STRING', '');
            $newUri = '/' . $newPath . ($query ? '?' . $query : '');

            $server                = $request->server->all();
            $server['REQUEST_URI'] = $newUri;
            $server['PATH_INFO']   = '/' . $newPath;

            $request->initialize(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $server,
                $request->getContent()
            );

            app()->setLocale($locale);
            $request->attributes->set('gr_locale', $locale);

            // Cookie'ye yaz — prefix'siz iç linklerde de locale korunur
            $response = $next($request);
            $response->headers->setCookie(
                cookie('gr_locale', $locale, 60 * 24 * 365, '/', null, false, false)
            );
            return $response;
        }

        // Dil sıfırlama: ?reset_locale=1 — TR'ye dön, cookie'yi temizle
        if ($request->query('reset_locale') == '1') {
            app()->setLocale('tr');
            $request->attributes->set('gr_locale', 'tr');
            $response = $next($request);
            $response->headers->setCookie(
                cookie('gr_locale', 'tr', 60 * 24 * 365, '/', null, false, false)
            );
            return $response;
        }

        // URL'de prefix yok → cookie'den oku
        $cookieLocale = $request->cookie('gr_locale', 'tr');
        $locale = in_array($cookieLocale, array_merge(self::SUPPORTED, ['tr'])) ? $cookieLocale : 'tr';
        app()->setLocale($locale);
        $request->attributes->set('gr_locale', $locale);

        return $next($request);
    }
}
