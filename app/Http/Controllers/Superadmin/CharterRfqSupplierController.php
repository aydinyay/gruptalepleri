<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CharterRfqSupplier;
use App\Models\SistemAyar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $suppliers = CharterRfqSupplier::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $maxSuppliers = SistemAyar::charterRfqMaxSuppliers((int) config('charter.rfq_max_suppliers', 10));

        return view('superadmin.charter-rfq-suppliers', [
            'suppliers' => $suppliers,
            'maxSuppliers' => $maxSuppliers,
            'serviceTypeOptions' => [
                'jet' => 'Private Jet',
                'helicopter' => 'Helikopter',
                'airliner' => 'Charter Ucak',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin();

        $data = $this->validated($request);
        CharterRfqSupplier::query()->create($data);

        return back()->with('success', 'RFQ alicisi eklendi.');
    }

    public function update(Request $request, CharterRfqSupplier $supplier): RedirectResponse
    {
        $this->authorizeSuperadmin();

        $data = $this->validated($request);
        $supplier->update($data);

        return back()->with('success', 'RFQ alicisi guncellendi.');
    }

    public function destroy(CharterRfqSupplier $supplier): RedirectResponse
    {
        $this->authorizeSuperadmin();
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
            'notes' => 'nullable|string|max:500',
        ]);

        $data['service_types'] = array_values(array_unique($data['service_types']));
        $data['is_active'] = $request->boolean('is_active', false);

        return $data;
    }
}
