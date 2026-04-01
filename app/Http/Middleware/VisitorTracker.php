<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VisitorTracker
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Sadece GET isteklerini takip et
        if ($request->isMethod('GET')) {
            try {
                $trackerFile = public_path('tracker.php');
                if (file_exists($trackerFile)) {
                    // Oturum açmış kullanıcıyı tracker'a ilet
                    if (auth()->check()) {
                        $u = auth()->user();
                        $_SERVER['GT_MEMBER_ID']   = (string) $u->id;
                        $_SERVER['GT_MEMBER_NAME'] = $u->name . ' [' . $u->role . ']';
                    } else {
                        $_SERVER['GT_MEMBER_ID']   = '';
                        $_SERVER['GT_MEMBER_NAME'] = '';
                    }
                    // tracker.php kendi DB bağlantısını kullanıyor
                    // Laravel değişkenlerini kirletmemek için output buffering kullan
                    ob_start();
                    include $trackerFile;
                    ob_end_clean();
                }
            } catch (\Exception $e) {
                // Hata olursa sessizce atla, site etkilenmesin
            }
        }

        return $response;
    }
}
