<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\SistemAyar;
use App\Models\TransferAirport;
use App\Models\TransferSettlementEntry;
use App\Models\TransferSupplier;
use App\Models\TransferZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SuperadminTransferOpsController extends Controller
{
    public function index()
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        return view('transfer.superadmin-ops', [
            'suppliers' => TransferSupplier::query()
                ->withCount(['pricingRules', 'coverages'])
                ->orderByDesc('is_approved')
                ->orderBy('company_name')
                ->get(),
            'airports' => TransferAirport::query()
                ->with(['zones' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->get(),
            'settlements' => TransferSettlementEntry::query()
                ->with(['supplier', 'booking.airport', 'booking.zone', 'booking.vehicleType', 'booking.agencyUser'])
                ->latest('id')
                ->limit(40)
                ->get(),
            'termsText' => SistemAyar::transferSupplierTermsText(),
            'termsVersion' => SistemAyar::transferSupplierTermsVersion(),
        ]);
    }

    public function updateSupplier(Request $request, TransferSupplier $supplier): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        $validated = $request->validate([
            'is_approved' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $supplier->update([
            'is_approved' => (bool) ($validated['is_approved'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'commission_rate' => (float) $validated['commission_rate'],
            'approved_at' => (bool) ($validated['is_approved'] ?? false) ? now() : null,
        ]);

        return back()->with('success', 'Supplier ayarlari guncellendi.');
    }

    public function updateTerms(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        $validated = $request->validate([
            'terms_text' => ['required', 'string', 'min:20', 'max:20000'],
        ]);

        $nextVersion = SistemAyar::transferSupplierTermsVersion() + 1;

        SistemAyar::set(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_TEXT, trim((string) $validated['terms_text']));
        SistemAyar::set(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_VERSION, (string) $nextVersion);

        return back()->with('success', 'Transfer tedarikci sozlesmesi guncellendi. Yeni versiyon: ' . $nextVersion);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'airport_id' => ['required', 'integer', 'exists:transfer_airports,id'],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        TransferZone::query()->updateOrCreate(
            [
                'airport_id' => (int) $validated['airport_id'],
                'slug' => Str::slug((string) $validated['name']),
            ],
            [
                'name' => $validated['name'],
                'city' => $validated['city'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'sort_order' => (int) ($validated['sort_order'] ?? 100),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Transfer bolgesi kaydedildi.');
    }

    public function updateZone(Request $request, TransferZone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $zone->update([
            'name' => $validated['name'],
            'slug' => Str::slug((string) $validated['name']),
            'city' => $validated['city'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? $zone->sort_order),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Bolge bilgisi guncellendi.');
    }
}
