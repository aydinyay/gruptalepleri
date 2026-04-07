<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use App\Models\BlogYazisi;

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

        // Öne çıkan ürünler
        $featuredItems = CatalogItem::published()
            ->featured()
            ->ordered()
            ->with('category')
            ->limit(6)
            ->get();

        // Son eklenen / popüler ürünler (featured olmayan)
        $latestItems = CatalogItem::published()
            ->where('is_featured', false)
            ->ordered()
            ->with('category')
            ->limit(8)
            ->get();

        // Hero kartları — öne çıkan ilk 2
        $heroItems = $featuredItems->take(2);

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

        return view('b2c.home.index', compact(
            'categories',
            'featuredItems',
            'heroItems',
            'latestItems',
            'destinationCities',
            'blogPosts',
        ));
    }
}
