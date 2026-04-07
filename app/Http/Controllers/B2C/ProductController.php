<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogCategory;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $item = CatalogItem::published()
            ->where('slug', $slug)
            ->with(['category', 'supplier'])
            ->firstOrFail();

        // Aynı kategoriden benzer ürünler
        $relatedItems = CatalogItem::published()
            ->where('category_id', $item->category_id)
            ->where('id', '!=', $item->id)
            ->ordered()
            ->limit(3)
            ->get();

        return view('b2c.product.show', compact('item', 'relatedItems'));
    }
}
