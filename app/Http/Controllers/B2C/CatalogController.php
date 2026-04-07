<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categories = CatalogCategory::active()->rootCategories()->ordered()->get();

        $query = CatalogItem::published()->with('category')->ordered();

        if ($type = $request->get('tip')) {
            $query->where('product_type', $type);
        }
        if ($city = $request->get('sehir')) {
            $query->where('destination_city', $city);
        }
        if ($pricing = $request->get('fiyat')) {
            $query->where('pricing_type', $pricing);
        }

        $items = $query->paginate(12)->withQueryString();

        $cities = CatalogItem::published()
            ->whereNotNull('destination_city')
            ->distinct()
            ->pluck('destination_city')
            ->sort()
            ->values();

        return view('b2c.catalog.index', compact('categories', 'items', 'cities'));
    }

    public function category(string $slug)
    {
        $category = CatalogCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $items = CatalogItem::published()
            ->where('category_id', $category->id)
            ->with('category')
            ->ordered()
            ->paginate(12);

        $subcategories = $category->children()->active()->ordered()->withCount(['publishedItems'])->get();

        return view('b2c.catalog.category', compact('category', 'items', 'subcategories'));
    }

    public function destination(string $slug)
    {
        // slug'ı okunabilir şehir adına çevir
        $city = str_replace('-', ' ', $slug);

        $items = CatalogItem::published()
            ->whereRaw('LOWER(destination_city) LIKE ?', [strtolower($city) . '%'])
            ->with('category')
            ->ordered()
            ->paginate(12);

        return view('b2c.catalog.destination', compact('city', 'items', 'slug'));
    }
}
