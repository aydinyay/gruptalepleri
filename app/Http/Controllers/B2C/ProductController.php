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

        $relatedItems = CatalogItem::published()
            ->where('category_id', $item->category_id)
            ->where('id', '!=', $item->id)
            ->ordered()
            ->limit(3)
            ->get();

        // Leisure ürünü → B2B tasarımıyla özel B2C sayfası
        $leisureTypes = ['leisure_package', 'leisure_package_template'];
        if (in_array($item->reference_type, $leisureTypes, true) && $item->reference_id) {
            $package = LeisurePackageTemplate::find($item->reference_id);

            if ($package) {
                $galleryPhotos = LeisureMediaAsset::where('package_code', $package->code)
                    ->where('category', 'gallery')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                $mediaAssets = LeisureMediaAsset::query()
                    ->where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', $package->product_type))
                    ->whereNull('package_code')
                    ->orderBy('sort_order')
                    ->limit(6)
                    ->get();

                $allPackages = LeisurePackageTemplate::query()
                    ->where('product_type', $package->product_type)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                return view('b2c.product.leisure-show', compact(
                    'item', 'package', 'relatedItems', 'galleryPhotos', 'mediaAssets', 'allPackages'
                ));
            }
        }

        return view('b2c.product.show', compact('item', 'relatedItems'));
    }
}
