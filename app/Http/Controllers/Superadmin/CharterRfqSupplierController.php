<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CharterRfqSupplier;
use App\Models\SistemAyar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CharterRfqSupplierController extends Controller
{
    private function authorizeSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }

    public function index(): View
    {
        $this->authorizeSuperadmin();

        $tableReady = $this->tableReady();

        $suppliers = $tableReady
            ? CharterRfqSupplier::query()->orderByDesc('is_active')->orderBy('name')->get()
            : collect();

        $maxSuppliers = SistemAyar::charterRfqMaxSuppliers((int) config('charter.rfq_max_suppliers', 10));

        return view('superadmin.charter-rfq-suppliers', [
            'suppliers' => $suppliers,
            'maxSuppliers' => $maxSuppliers,
            'tableReady' => $tableReady,
            'serviceTypeOptions' => [
                'jet' => 'Private Jet',
                'helicopter' => 'Helikopter',
                'airliner' => 'Charter Ucak',
            ],
            'supplierKindOptions' => [
                'operator' => 'Operator',
                'carrier' => 'Airline / Carrier',
                'broker' => 'Broker',
                'hybrid' => 'Hybrid',
                'cargo' => 'Cargo Operator',
            ],
            'charterModelOptions' => [
                'full_charter' => 'Full Charter',
                'acmi' => 'ACMI / Wet Lease',
                'block_seat' => 'Block Seat',
                'cargo' => 'Cargo',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin();
        if (! $this->tableReady()) {
            return back()->with('error', 'RFQ tedarikci tablosu hazir degil. Once migration calistirin.');
        }

        $data = $this->validated($request);
        CharterRfqSupplier::query()->create($data);

        return back()->with('success', 'RFQ alicisi eklendi.');
    }

    public function update(Request $request, CharterRfqSupplier $supplier): RedirectResponse
    {
        $this->authorizeSuperadmin();
        if (! $this->tableReady()) {
            return back()->with('error', 'RFQ tedarikci tablosu hazir degil. Once migration calistirin.');
        }

        $data = $this->validated($request);
        $supplier->update($data);

        return back()->with('success', 'RFQ alicisi guncellendi.');
    }

    public function destroy(CharterRfqSupplier $supplier): RedirectResponse
    {
        $this->authorizeSuperadmin();
        if (! $this->tableReady()) {
            return back()->with('error', 'RFQ tedarikci tablosu hazir degil. Once migration calistirin.');
        }
        $supplier->delete();

        return back()->with('success', 'RFQ alicisi silindi.');
    }

    public function updateMax(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin();

        $validated = $request->validate([
            'max_suppliers' => 'required|integer|min:1|max:100',
        ]);

        SistemAyar::set(SistemAyar::KEY_CHARTER_RFQ_MAX_SUPPLIERS, (string) $validated['max_suppliers']);

        return back()->with('success', 'RFQ dagitim limiti guncellendi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:40',
            'service_types' => 'required|array|min:1',
            'service_types.*' => 'required|string|in:jet,helicopter,airliner',
            'supplier_kind' => 'required|string|in:operator,carrier,broker,hybrid,cargo',
            'charter_models' => 'nullable|array',
            'charter_models.*' => 'required|string|in:full_charter,acmi,block_seat,cargo',
            'min_pax' => 'nullable|integer|min:1|max:999',
            'max_pax' => 'nullable|integer|min:1|max:999',
            'priority' => 'nullable|integer|min:1|max:999',
            'min_notice_hours' => 'nullable|integer|min:0|max:720',
            'notes' => 'nullable|string|max:500',
        ]);

        $data['service_types'] = array_values(array_unique($data['service_types']));
        $data['charter_models'] = array_values(array_unique((array) ($data['charter_models'] ?? [])));
        $data['priority'] = (int) ($data['priority'] ?? 100);
        $data['is_premium_only'] = $request->boolean('is_premium_only', false);
        $data['is_cargo_operator'] = $request->boolean('is_cargo_operator', false);
        $data['is_active'] = $request->boolean('is_active', false);

        if (
            isset($data['min_pax'], $data['max_pax'])
            && $data['min_pax'] !== null
            && $data['max_pax'] !== null
            && (int) $data['max_pax'] < (int) $data['min_pax']
        ) {
            throw ValidationException::withMessages([
                'max_pax' => 'Maksimum pax, minimum pax degerinden kucuk olamaz.',
            ]);
        }

        if ($data['supplier_kind'] === 'cargo') {
            $data['is_cargo_operator'] = true;
            if (! in_array('cargo', $data['charter_models'], true)) {
                $data['charter_models'][] = 'cargo';
            }
        }

        return $data;
    }

    private function tableReady(): bool
    {
        return Schema::hasTable('charter_rfq_suppliers');
    }
}
