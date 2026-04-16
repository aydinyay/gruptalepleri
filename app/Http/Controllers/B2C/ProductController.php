<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogItem;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $item = CatalogItem::published()
            ->where('slug', $slug)
            ->with(['category', 'supplier', 'transferAirport', 'transferZone'])
            ->firstOrFail();

        $leisureTemplate = null;
        $galleryPhotos   = collect();

        if ($item->reference_type === 'leisure_package' && $item->reference_id) {
            $leisureTemplate = LeisurePackageTemplate::find($item->reference_id);
            if ($leisureTemplate) {
                $galleryPhotos = LeisureMediaAsset::where('package_code', $leisureTemplate->code)
                    ->where('category', 'gallery')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            }
        }

        $relatedItems = CatalogItem::published()
            ->where('category_id', $item->category_id)
            ->where('id', '!=', $item->id)
            ->ordered()
            ->limit(3)
            ->get();

        return view('b2c.product.show', compact('item', 'relatedItems', 'leisureTemplate', 'galleryPhotos'));
    }
}
