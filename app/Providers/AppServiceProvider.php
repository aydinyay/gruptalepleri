<?php

namespace App\Providers;

use App\Services\AiCelebrationService;
use App\Services\Transfer\Contracts\TransferDistanceCalculator;
use App\Services\Transfer\GoogleTransferDistanceCalculator;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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
        $this->app->bind(TransferDistanceCalculator::class, GoogleTransferDistanceCalculator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        // guest:b2c middleware authenticated kullanıcıyı B2B route'una (acente/dashboard)
        // yönlendirmesin — B2C domain'de B2C anasayfasına git.
        RedirectIfAuthenticated::redirectUsing(function (\Illuminate\Http\Request $request) {
            if ($request->attributes->get('is_b2c', false)) {
                return route('b2c.home');
            }
            if (auth()->check()) {
                $role = auth()->user()->role ?? '';
                if ($role === 'superadmin') return route('superadmin.dashboard');
                if ($role === 'admin')      return route('admin.dashboard');
            }
            return route('acente.dashboard');
        });

        // B2C navbar için kategoriler ve şehirler
        View::composer('b2c.layouts.app', function ($view) {
            static $navData = null;
            if ($navData === null) {
                try {
                    $navCategories = \App\Models\B2C\CatalogCategory::active()
                        ->rootCategories()
                        ->ordered()
                        ->withCount(['publishedItems'])
                        ->get();

                    $navCities = \App\Models\B2C\CatalogItem::published()
                        ->whereNotNull('destination_city')
                        ->where('destination_city', '!=', '')
                        ->selectRaw('destination_city, COUNT(*) as cnt')
                        ->groupBy('destination_city')
                        ->orderByDesc('cnt')
                        ->limit(9)
                        ->get();
                } catch (\Throwable $e) {
                    $navCategories = collect();
                    $navCities     = collect();
                }
                $navData = compact('navCategories', 'navCities');
            }
            $view->with($navData);
        });

        // Admin ve superadmin telefon numaralarını tüm view'lara paylaş
        View::composer('*', function ($view) {
            static $adminTelefon     = null;
            static $superadminTelefon = null;
            static $activeAiCampaignByViewer = [];

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

            $activeAiCelebration = null;
            try {
                $request = request();
                $isAiSettingsPage = $request->is('superadmin/ai-kutlama/*')
                    || ($request->is('superadmin/site-ayarlari') && $request->query('sekme') === 'ai');

                if (! $isAiSettingsPage) {
                    $viewerKey = auth()->check()
                        ? ('user_' . auth()->id())
                        : ('guest_' . ($request->cookie('gtp_guest') ?: session()->getId() ?: $request->ip()));

                    if (! array_key_exists($viewerKey, $activeAiCampaignByViewer)) {
                        $activeAiCampaignByViewer[$viewerKey] = app(AiCelebrationService::class)
                            ->activeCampaignForRequest($request, auth()->user());
                    }

                    $activeAiCelebration = $activeAiCampaignByViewer[$viewerKey];
                }
            } catch (\Throwable $e) {
                $activeAiCelebration = null;
            }

            $view->with('_adminTelefon', $adminTelefon)
                 ->with('_superadminTelefon', $superadminTelefon)
                 ->with('_acentePreviewMode', $previewMode)
                 ->with('_acentePreviewUser', $previewUser)
                 ->with('_activeAiCelebration', $activeAiCelebration);
        });
    }
}
