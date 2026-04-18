<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cWishlistItem;
use App\Models\B2C\CatalogCategory;
use App\Models\B2C\CatalogItem;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categories = CatalogCategory::active()->rootCategories()->ordered()->get();

        $query = CatalogItem::published()->with('category');

        if ($type = $request->get('tip')) {
            $query->where('product_type', $type);
        }
        if ($city = $request->get('sehir')) {
            $cityLower = mb_strtolower($city);
            $query->where(function ($q) use ($cityLower) {
                $q->whereRaw('LOWER(destination_city) = ?', [$cityLower])
                  ->orWhereHas('locations', fn ($lq) => $lq->where('slug', $cityLower));
            });
        }
        if ($pricing = $request->get('fiyat')) {
            $query->where('pricing_type', $pricing);
        }
        if ($q = trim($request->get('q', ''))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('destination_city', 'like', "%{$q}%")
                    ->orWhere('short_desc', 'like', "%{$q}%");
            });
        }

        $this->applySorting($query, $request->get('sirala'));

        $items = $query->paginate(12)->withQueryString();

        $cities = CatalogItem::published()
            ->whereNotNull('destination_city')
            ->distinct()
            ->pluck('destination_city')
            ->sort()
            ->values();

        $savedIds = $this->savedIds();

        return view('b2c.catalog.index', compact('categories', 'items', 'cities', 'savedIds'));
    }

    public function category(Request $request, string $slug)
    {
        $category = CatalogCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $query = CatalogItem::published()
            ->where('category_id', $category->id)
            ->with('category');

        $this->applySorting($query, $request->get('sirala'));

        $items = $query->paginate(12)->withQueryString();

        $subcategories = $category->children()->active()->ordered()->withCount(['publishedItems'])->get();

        $savedIds = $this->savedIds();

        return view('b2c.catalog.category', compact('category', 'items', 'subcategories', 'savedIds'));
    }

    public function destination(Request $request, string $slug)
    {
        $city = str_replace('-', ' ', $slug);

        $query = CatalogItem::published()
            ->whereRaw('LOWER(destination_city) LIKE ?', [strtolower($city) . '%'])
            ->with('category');

        if ($type = $request->get('tip')) {
            $query->where('product_type', $type);
        }

        $this->applySorting($query, $request->get('sirala'));

        $items = $query->paginate(12)->withQueryString();

        $savedIds = $this->savedIds();

        return view('b2c.catalog.destination', compact('city', 'items', 'slug', 'savedIds'));
    }

    private function savedIds(): array
    {
        return B2cWishlistItem::where('session_id', session()->getId())
            ->pluck('catalog_item_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function applySorting($query, ?string $sort): void
    {
        match ($sort) {
            'puan'    => $query->orderByDesc('rating_avg')->orderByDesc('review_count'),
            'fiyat_a' => $query->orderBy('base_price'),
            'fiyat_d' => $query->orderByDesc('base_price'),
            'yeni'    => $query->latest(),
            default   => $query->ordered(),
        };
    }
}
