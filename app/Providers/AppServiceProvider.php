<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        // Admin ve superadmin telefon numaralarını tüm view'lara paylaş
        View::composer('*', function ($view) {
            static $adminTelefon     = null;
            static $superadminTelefon = null;

            if ($adminTelefon === null) {
                $admin = \App\Models\User::where('role', 'admin')
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->first();
                $adminTelefon = $admin?->phone ?? '905354154799';
            }

            if ($superadminTelefon === null) {
                $superadmin = \App\Models\User::where('role', 'superadmin')
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->first();
                $superadminTelefon = $superadmin?->phone ?? '905324262630';
            }

            $view->with('_adminTelefon', $adminTelefon)
                 ->with('_superadminTelefon', $superadminTelefon);
        });
    }
}
