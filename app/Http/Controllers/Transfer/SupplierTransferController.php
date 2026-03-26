<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\SistemAyar;
use App\Models\TransferAirport;
use App\Models\TransferBooking;
use App\Models\TransferCancellationPolicy;
use App\Models\TransferPricingRule;
use App\Models\TransferSupplier;
use App\Models\TransferSupplierCoverage;
use App\Models\TransferVehicleType;
use App\Models\TransferZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SupplierTransferController extends Controller
{
    public function showTerms(Request $request)
    {
        $supplier = $this->resolveApprovedSupplier($request);
        $currentVersion = SistemAyar::transferSupplierTermsVersion();

        if ($supplier->hasAcceptedVersion($currentVersion)) {
            return redirect()
                ->route('acente.transfer.supplier.index')
                ->with('success', 'Transfer tedarikci sozlesmesi zaten onayli.');
        }

        return view('transfer.supplier-terms', [
            'supplier' => $supplier,
            'termsText' => SistemAyar::transferSupplierTermsText(),
            'termsVersion' => $currentVersion,
        ]);
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);
        $request->validate([
            'accept_terms' => ['accepted'],
        ]);

        $supplier->update([
            'terms_accepted_at' => now(),
            'terms_version_accepted' => SistemAyar::transferSupplierTermsVersion(),
        ]);

        return redirect()
            ->route('acente.transfer.supplier.index')
            ->with('success', 'Sozlesme onayi tamamlandi. Tedarikci paneliniz acildi.');
    }

    public function index(Request $request)
    {
        $supplier = $this->resolveApprovedSupplier($request);
        $asSuperadmin = (bool) $request->attributes->get('transfer_supplier_as_superadmin', false);

        return view('transfer.supplier-dashboard', [
            'supplier' => $supplier->loadMissing('cancellationPolicy'),
            'asSuperadmin' => $asSuperadmin,
            'selectedSupplierId' => (int) $supplier->id,
            'airports' => TransferAirport::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'zones' => TransferZone::query()->with('airport')->where('is_active', true)->orderBy('sort_order')->get(),
            'vehicleTypes' => TransferVehicleType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'coverages' => $supplier->coverages()->with(['airport', 'zone'])->latest('id')->get(),
            'pricingRules' => $supplier->pricingRules()->with(['airport', 'zone', 'vehicleType'])->latest('id')->get(),
            'bookings' => TransferBooking::query()
                ->with(['airport', 'zone', 'vehicleType'])
                ->where('supplier_id', $supplier->id)
                ->latest('id')
                ->limit(30)
                ->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplier->update([
            'company_name' => $validated['company_name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'city' => $validated['city'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Profil bilgileri guncellendi.');
    }

    public function storeCoverage(Request $request): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);

        $validated = $request->validate([
            'airport_id' => ['required', 'integer', 'exists:transfer_airports,id'],
            'zone_id' => ['required', 'integer', 'exists:transfer_zones,id'],
            'direction' => ['required', 'in:FROM_AIRPORT,TO_AIRPORT,BOTH'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $zone = TransferZone::query()->find((int) $validated['zone_id']);
        if (! $zone || (int) $zone->airport_id !== (int) $validated['airport_id']) {
            return back()->withErrors(['zone_id' => 'Secilen bolge bu havalimani ile eslesmiyor.']);
        }

        TransferSupplierCoverage::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'airport_id' => (int) $validated['airport_id'],
                'zone_id' => (int) $validated['zone_id'],
                'direction' => $validated['direction'],
            ],
            [
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]
        );

        return back()->with('success', 'Coverage kaydi guncellendi.');
    }

    public function destroyCoverage(Request $request, TransferSupplierCoverage $coverage): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);

        abort_unless((int) $coverage->supplier_id === (int) $supplier->id, 403);

        $coverage->delete();

        return back()->with('success', 'Coverage kaydi silindi.');
    }

    public function storePricingRule(Request $request): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);

        $validated = $request->validate([
            'pricing_rule_id' => ['nullable', 'integer'],
            'airport_id' => ['required', 'integer', 'exists:transfer_airports,id'],
            'zone_id' => ['required', 'integer', 'exists:transfer_zones,id'],
            'vehicle_type_id' => ['required', 'integer', 'exists:transfer_vehicle_types,id'],
            'direction' => ['required', 'in:FROM_AIRPORT,TO_AIRPORT,BOTH'],
            'currency' => ['required', 'string', 'size:3'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'per_km' => ['required', 'numeric', 'min:0'],
            'per_minute' => ['required', 'numeric', 'min:0'],
            'minimum_fare' => ['required', 'numeric', 'min:0'],
            'night_multiplier' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'peak_multiplier' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'night_start' => ['nullable', 'date_format:H:i'],
            'night_end' => ['nullable', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $zone = TransferZone::query()->find((int) $validated['zone_id']);
        if (! $zone || (int) $zone->airport_id !== (int) $validated['airport_id']) {
            return back()->withErrors(['zone_id' => 'Secilen bolge bu havalimani ile eslesmiyor.']);
        }

        $rulePayload = [
            'airport_id' => (int) $validated['airport_id'],
            'zone_id' => (int) $validated['zone_id'],
            'vehicle_type_id' => (int) $validated['vehicle_type_id'],
            'direction' => $validated['direction'],
            'currency' => strtoupper($validated['currency']),
            'base_fare' => (float) $validated['base_fare'],
            'per_km' => (float) $validated['per_km'],
            'per_minute' => (float) $validated['per_minute'],
            'minimum_fare' => (float) $validated['minimum_fare'],
            'night_start' => ! empty($validated['night_start']) ? $validated['night_start'] . ':00' : null,
            'night_end' => ! empty($validated['night_end']) ? $validated['night_end'] . ':00' : null,
            'night_multiplier' => (float) ($validated['night_multiplier'] ?? 1),
            'peak_multiplier' => (float) ($validated['peak_multiplier'] ?? 1),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        $ruleId = (int) ($validated['pricing_rule_id'] ?? 0);
        if ($ruleId > 0) {
            $rule = TransferPricingRule::query()
                ->where('supplier_id', $supplier->id)
                ->findOrFail($ruleId);

            $rule->update($rulePayload);

            return back()->with('success', 'Fiyat kurali guncellendi.');
        }

        TransferPricingRule::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'airport_id' => $rulePayload['airport_id'],
                'zone_id' => $rulePayload['zone_id'],
                'vehicle_type_id' => $rulePayload['vehicle_type_id'],
                'direction' => $rulePayload['direction'],
                'currency' => $rulePayload['currency'],
            ],
            [
                'base_fare' => $rulePayload['base_fare'],
                'per_km' => $rulePayload['per_km'],
                'per_minute' => $rulePayload['per_minute'],
                'minimum_fare' => $rulePayload['minimum_fare'],
                'night_start' => $rulePayload['night_start'],
                'night_end' => $rulePayload['night_end'],
                'night_multiplier' => $rulePayload['night_multiplier'],
                'peak_multiplier' => $rulePayload['peak_multiplier'],
                'is_active' => $rulePayload['is_active'],
            ]
        );

        return back()->with('success', 'Fiyat kurali kaydedildi.');
    }

    public function destroyPricingRule(Request $request, TransferPricingRule $rule): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);

        abort_unless((int) $rule->supplier_id === (int) $supplier->id, 403);

        $rule->delete();

        return back()->with('success', 'Fiyat kurali silindi.');
    }

    public function updatePolicy(Request $request): RedirectResponse
    {
        $supplier = $this->resolveApprovedSupplier($request);

        $validated = $request->validate([
            'free_cancel_before_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'refund_percent_after_deadline' => ['required', 'numeric', 'min:0', 'max:100'],
            'no_show_refund_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        TransferCancellationPolicy::query()->updateOrCreate(
            ['supplier_id' => $supplier->id],
            [
                'free_cancel_before_minutes' => (int) $validated['free_cancel_before_minutes'],
                'refund_percent_after_deadline' => (float) $validated['refund_percent_after_deadline'],
                'no_show_refund_percent' => (float) $validated['no_show_refund_percent'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Iptal politikasi guncellendi.');
    }

    private function resolveApprovedSupplier(Request $request): TransferSupplier
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 403);

        $supplier = $request->attributes->get('transfer_supplier');
        if ($supplier instanceof TransferSupplier) {
            return $supplier->fresh();
        }

        $supplier = TransferSupplier::query()
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($supplier && $supplier->is_approved, 403);

        return $supplier;
    }
}
