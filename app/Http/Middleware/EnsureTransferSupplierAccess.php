<?php

namespace App\Http\Middleware;

use App\Models\SistemAyar;
use App\Models\TransferSupplier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureTransferSupplierAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'accepted'): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if (! Schema::hasTable('transfer_suppliers')) {
            abort(403);
        }

        $isSuperadmin = strtolower((string) ($user->role ?? '')) === 'superadmin';

        if ($isSuperadmin) {
            $supplierId = (int) ($request->query('supplier_id')
                ?? $request->input('supplier_id')
                ?? $request->session()->get('superadmin_transfer_supplier_id', 0));

            if ($supplierId <= 0) {
                abort(403, 'Tedarikci secimi gerekli.');
            }

            $supplier = TransferSupplier::query()->find($supplierId);
            if (! $supplier) {
                abort(404);
            }

            $request->session()->put('superadmin_transfer_supplier_id', $supplier->id);
            $request->attributes->set('transfer_supplier', $supplier);
            $request->attributes->set('transfer_supplier_as_superadmin', true);

            return $next($request);
        }

        $supplier = TransferSupplier::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $supplier || ! $supplier->is_approved) {
            abort(403);
        }

        $normalizedMode = strtolower(trim($mode));
        if ($normalizedMode === 'accepted') {
            $termsVersion = SistemAyar::transferSupplierTermsVersion();
            if (! $supplier->hasAcceptedVersion($termsVersion)) {
                return redirect()->route('acente.transfer.supplier.terms.show');
            }
        }

        $request->attributes->set('transfer_supplier', $supplier);

        return $next($request);
    }
}
