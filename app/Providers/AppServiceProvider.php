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
                try {
                    $admin = \App\Models\User::where('role', 'admin')
                        ->whereNotNull('phone')
                        ->where('phone', '!=', '')
                        ->first();
                    $adminTelefon = $admin?->phone ?? '905354154799';
                } catch (\Throwable $e) {
                    $adminTelefon = '905354154799';
                }
            }

            if ($superadminTelefon === null) {
                try {
                    $superadmin = \App\Models\User::where('role', 'superadmin')
                        ->whereNotNull('phone')
                        ->where('phone', '!=', '')
                        ->first();
                    $superadminTelefon = $superadmin?->phone ?? '905324262630';
                } catch (\Throwable $e) {
                    $superadminTelefon = '905324262630';
                }
            }

            $previewUser = null;
            $previewMode = false;

            if (auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                $previewUserId = session('acente_preview_user_id');
                if ($previewUserId) {
                    try {
                        $previewUser = \App\Models\User::with('agency')
                            ->where('role', 'acente')
                            ->find($previewUserId);
                        $previewMode = (bool) $previewUser;
                        if (! $previewMode) {
                            session()->forget('acente_preview_user_id');
                        }
                    } catch (\Throwable $e) {
                        $previewUser = null;
                        $previewMode = false;
                    }
                }
            }

            $view->with('_adminTelefon', $adminTelefon)
                 ->with('_superadminTelefon', $superadminTelefon)
                 ->with('_acentePreviewMode', $previewMode)
                 ->with('_acentePreviewUser', $previewUser);
        });
    }
}
