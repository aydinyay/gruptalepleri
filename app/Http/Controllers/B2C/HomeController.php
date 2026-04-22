<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\B2cWishlistItem;
use App\Models\BlogYazisi;
use App\Models\SistemAyar;

class HomeController extends Controller
{
    public function index()
    {
        // Aktif kök kategoriler (ikonlu, sıralı)
        $categories = CatalogCategory::active()
            ->rootCategories()
            ->ordered()
            ->withCount(['publishedItems'])
            ->having('published_items_count', '>', 0)
            ->limit(12)
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

        // Hero kartları — homepage_hero işaretli, max 3
        $heroItems = CatalogItem::published()
            ->where('homepage_hero', true)
            ->with('category')
            ->ordered()
            ->limit(3)
            ->get();

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

        // Konuma yakın ürünler — session'da şehir varsa göster
        $nearbyCity  = session('b2c_user_city');
        $nearbyItems = $nearbyCity
            ? CatalogItem::published()->inCity($nearbyCity)->with('category')->limit(6)->get()
            : collect();
        // Tam eşleşme yoksa şehir adının ilk kelimesiyle tekrar dene (ör. "İzmir Province" → "İzmir")
        if ($nearbyCity && $nearbyItems->isEmpty()) {
            $firstWord = explode(' ', $nearbyCity)[0];
            if ($firstWord !== $nearbyCity) {
                $nearbyItems = CatalogItem::published()->inCity($firstWord)->with('category')->limit(6)->get();
                if ($nearbyItems->isNotEmpty()) $nearbyCity = $firstWord;
            }
        }

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
            'nearbyCity',
            'nearbyItems',
        ));
    }
}
