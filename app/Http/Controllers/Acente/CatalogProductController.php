<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogCategory;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Services\GtpnrService;
use Illuminate\Http\Request;

class CatalogProductController extends Controller
{
    public function index()
    {
        $items = CatalogItem::where('is_active', true)
            ->with('category')
            ->ordered()
            ->get();

        $categories = CatalogCategory::withCount([
            'items as active_count' => fn($q) => $q->where('is_active', true),
        ])->orderBy('sort_order')->get();

        return view('acente.catalog.index', compact('items', 'categories'));
    }

    public function show(string $slug)
    {
        $item = CatalogItem::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'supplier'])
            ->firstOrFail();

        $relatedItems = CatalogItem::where('is_active', true)
            ->where('category_id', $item->category_id)
            ->where('id', '!=', $item->id)
            ->ordered()
            ->limit(3)
            ->get();

        $extraGallery = collect();
        $bookingUrl   = null;
        $leisureRefTypes = ['leisure_package', 'leisure_package_template'];

        if (in_array($item->reference_type, $leisureRefTypes, true) && $item->reference_id) {
            $package = LeisurePackageTemplate::find($item->reference_id);
            if ($package) {
                if (! $item->base_price) {
                    $item->base_price = $package->base_price_per_person ?? $package->original_price_per_person;
                    $item->currency   = $item->currency ?: ($package->currency ?? 'EUR');
                }
                $extraGallery = LeisureMediaAsset::where('package_code', $package->code)
                    ->where('category', 'gallery')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                $bookingUrl = match($package->product_type) {
                    'dinner_cruise'  => route('acente.dinner-cruise.show-product', $package->code),
                    'yacht_charter'  => route('acente.yacht-charter.show-product', $package->code),
                    'day_tour', 'multi_day_tour', 'activity_tour' => route('acente.tour.show-product', $package->code),
                    default          => null,
                };
            }
        }

        return view('acente.catalog.show', compact('item', 'relatedItems', 'extraGallery', 'bookingUrl'));
    }

    public function book(Request $request, string $slug, GtpnrService $gtpnrService)
    {
        $item = CatalogItem::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'service_date' => 'required|date|after_or_equal:today',
            'pax_count'    => 'required|integer|min:1|max:500',
            'guest_name'   => 'required|string|max:120',
            'guest_phone'  => 'required|string|max:30',
        ]);

        LeisureRequest::create([
            'user_id'      => $this->acenteActor()->id,
            'gtpnr'        => $gtpnrService->generate('catalog'),
            'product_type' => $item->product_subtype ?? $item->product_type ?? 'other',
            'status'       => LeisureRequest::STATUS_NEW,
            'service_date' => $validated['service_date'],
            'guest_count'  => $validated['pax_count'],
            'guest_name'   => $validated['guest_name'],
            'guest_phone'  => $validated['guest_phone'],
            'notes'        => "[Katalog: {$item->title} | ID:{$item->id}]",
        ]);

        return redirect()->route('acente.product.show', $slug)
            ->with('booking_success', 'Rezervasyon talebiniz alındı! Ekibimiz en kısa sürede sizinle iletişime geçecek.');
    }
}
