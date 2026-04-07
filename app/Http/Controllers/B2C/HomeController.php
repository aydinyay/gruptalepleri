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

        // Destinasyonlar — hangi şehirlerde ürün var
        $destinations = CatalogItem::published()
            ->whereNotNull('destination_city')
            ->selectRaw('destination_city, destination_country, COUNT(*) as item_count')
            ->groupBy('destination_city', 'destination_country')
            ->orderByDesc('item_count')
            ->limit(6)
            ->get();

        // Son blog yazıları (mevcut tablo — scopeYayinda: durum='yayinda' ve yayinlanma_tarihi <= now())
        $blogPosts = BlogYazisi::yayinda()
            ->with('kategori')
            ->latest('yayinlanma_tarihi')
            ->limit(3)
            ->get();

        return view('b2c.home.index', compact(
            'categories',
            'featuredItems',
            'latestItems',
            'destinations',
            'blogPosts',
        ));
    }
}
