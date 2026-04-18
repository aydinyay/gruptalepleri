<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cWishlistItem;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogSession;
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

        // Yacht charter: saatlik özel şablon (leisure-show) — diğer leisure/tour subtypelar show.blade.php kullanır
        $yachtSubtypes   = ['yacht_charter'];
        $leisureRefTypes = ['leisure_package', 'leisure_package_template'];

        if (in_array($item->product_subtype, $yachtSubtypes, true)
            && in_array($item->reference_type, $leisureRefTypes, true)
            && $item->reference_id) {

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

                $isSaved = B2cWishlistItem::where('session_id', session()->getId())->where('catalog_item_id', $item->id)->exists();

                return view('b2c.product.leisure-show', compact(
                    'item', 'package', 'relatedItems', 'galleryPhotos', 'mediaAssets', 'allPackages', 'isSaved'
                ));
            }
        }

        // Leisure/tour referanslı ürünler subtype'a göre show.blade.php ile görüntülenir:
        // dinner_cruise, evening_show, day_tour, activity_tour, multi_day_tour
        // Eğer reference_type var ama yacht_charter değilse → show.blade.php
        // Diğer tüm ürünler (transfer, charter, hotel, visa) → show.blade.php
        $extraGallery = collect();

        if (in_array($item->reference_type, $leisureRefTypes, true) && $item->reference_id) {
            $package = LeisurePackageTemplate::find($item->reference_id);
            if ($package) {
                if (! $item->base_price) {
                    $item->base_price = $package->base_price_per_person ?? $package->original_price_per_person;
                    $item->currency   = $item->currency ?: ($package->currency ?? 'EUR');
                }

                // Leisure şablonunun galeri fotoğraflarını da gönder
                $extraGallery = LeisureMediaAsset::where('package_code', $package->code)
                    ->where('category', 'gallery')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            }
        }

        $sessionSubtypes = ['timed_experience', 'event_ticket', 'admission_ticket', 'dinner_cruise', 'evening_show'];
        $sessions = in_array($item->product_subtype, $sessionSubtypes)
            ? CatalogSession::where('catalog_item_id', $item->id)->upcoming()->get()
            : collect();

        $isSaved = B2cWishlistItem::where('session_id', session()->getId())->where('catalog_item_id', $item->id)->exists();

        return view('b2c.product.show', compact('item', 'relatedItems', 'extraGallery', 'sessions', 'isSaved'));
    }
}
