<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureTransferSupplierAccess;
use App\Http\Middleware\EnsureB2CAuth;
use App\Http\Middleware\EnsureB2CDomain;
use App\Http\Middleware\DomainRouter;
use App\Http\Middleware\VisitorTracker;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'             => EnsureUserHasRole::class,
            'transfer_supplier'=> EnsureTransferSupplierAccess::class,
            'b2c_auth'         => EnsureB2CAuth::class,
            'b2c_domain'       => EnsureB2CDomain::class,
        ]);
        // DomainRouter en başa eklenir: session cookie'yi route yüklenmeden önce ayarlar
        $middleware->prependToGroup('web', DomainRouter::class);
        $middleware->appendToGroup('web', VisitorTracker::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 Page Expired: logout butonuna tıklanınca session süresi dolmuşsa
        // 419 göstermek yerine sessizce login sayfasına yönlendir.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->is('logout') || str_ends_with($request->path(), '/logout')) {
                return redirect()->route('login')->with('status', 'Oturumunuz sona erdi. Lütfen tekrar giriş yapın.');
            }
        });
    })->create();
