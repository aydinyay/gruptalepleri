<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\B2cWishlistItem;
use App\Models\BlogYazisi;
use App\Models\SistemAyar;
use App\Services\HeroTextService;

class HomeController extends Controller
{
    public function index()
    {
        // Aktif kök kategoriler (ikonlu, sıralı)
        $categories = CatalogCategory::active()
            ->rootCategories()
            ->ordered()
            ->withCount(['publishedItems'])
            ->limit(8)
            ->get();

        // Tüm yayındaki ürünler — öne çıkanlar önce
        $allItems = CatalogItem::published()
            ->orderByDesc('is_featured')
            ->ordered()
            ->with('category')
            ->limit(24)
            ->get();

        $featuredItems = $allItems->where('is_featured', true)->values();
        $latestItems   = $allItems->where('is_featured', false)->values();

        // Hero kartları — sadece Vizyon etiketli, max 3
        $heroItems = $allItems->where('badge_label', 'Vizyon')->take(3)->values();

        // Destinasyonlar — hangi şehirlerde ürün var
        $destinationCities = CatalogItem::published()
            ->whereNotNull('destination_city')
            ->selectRaw('destination_city, COUNT(*) as cnt')
            ->groupBy('destination_city')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        // Son blog yazıları
        try {
            $blogPosts = BlogYazisi::yayinda()
                ->latest('yayinlanma_tarihi')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $blogPosts = collect();
        }

        $heroBgColor = SistemAyar::get('b2c_hero_bg_color', 'linear-gradient(135deg, #0f2444 0%, #1a3c6b 50%, #1e4d8c 100%)');
        $heroBgImage = SistemAyar::get('b2c_hero_bg_image', '');

        $savedIds = B2cWishlistItem::where('session_id', session()->getId())
            ->pluck('catalog_item_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $heroCtx  = HeroTextService::buildContext();
        $heroText = (new HeroTextService())->getHeroText($heroCtx);

        return view('b2c.home.index', compact(
            'categories',
            'allItems',
            'featuredItems',
            'heroItems',
            'latestItems',
            'destinationCities',
            'blogPosts',
            'heroBgColor',
            'heroBgImage',
            'savedIds',
            'heroText',
        ));
    }
}
