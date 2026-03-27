<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureTransferSupplierAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'transfer_supplier' => EnsureTransferSupplierAccess::class,
        ]);
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
