<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cAgencySubscription;
use App\Models\TransferSupplier;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Superadmin — B2C Acente Başvuru Yönetimi
 *
 * Route prefix: /superadmin/b2c/acenteler
 * Middleware: auth + role:superadmin
 */
class B2cAgencyController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $subs = B2cAgencySubscription::with(['agency', 'transferSupplier'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $counts = [
            'pending'   => B2cAgencySubscription::where('status', 'pending')->count(),
            'approved'  => B2cAgencySubscription::where('status', 'approved')->count(),
            'rejected'  => B2cAgencySubscription::where('status', 'rejected')->count(),
            'suspended' => B2cAgencySubscription::where('status', 'suspended')->count(),
        ];

        // Direkt ekleme için: onaylı transfer tedarikçileri (henüz B2C kaydı yok olanlar önce)
        $transferSuppliers = TransferSupplier::where('is_approved', true)
            ->where('is_active', true)
            ->with(['user', 'b2cSubscription'])
            ->orderBy('company_name')
            ->get();

        return view('superadmin.b2c.agencies', compact('subs', 'counts', 'status', 'transferSuppliers'));
    }

    public function approve(Request $request, B2cAgencySubscription $sub)
    {
        $request->validate([
            'commission_pct' => 'nullable|numeric|min:0|max:50',
            'admin_note'     => 'nullable|string|max:500',
        ]);

        $sub->update([
            'status'               => B2cAgencySubscription::STATUS_APPROVED,
            'reviewed_by_user_id'  => auth()->id(),
            'approved_at'          => now(),
            'rejected_at'          => null,
            'rejection_reason'     => null,
            'commission_pct'       => $request->commission_pct,
            'admin_note'           => $request->admin_note,
        ]);

        return back()->with('success', "Başvuru onaylandı: {$sub->agency->name}");
    }

    public function reject(Request $request, B2cAgencySubscription $sub)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $sub->update([
            'status'              => B2cAgencySubscription::STATUS_REJECTED,
            'reviewed_by_user_id' => auth()->id(),
            'rejected_at'         => now(),
            'approved_at'         => null,
            'rejection_reason'    => $request->rejection_reason,
        ]);

        return back()->with('success', 'Başvuru reddedildi.');
    }

    public function suspend(Request $request, B2cAgencySubscription $sub)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $sub->update([
            'status'              => B2cAgencySubscription::STATUS_SUSPENDED,
            'reviewed_by_user_id' => auth()->id(),
            'suspended_at'        => now(),
            'admin_note'          => $request->admin_note,
        ]);

        return back()->with('success', 'Acente B2C erişimi askıya alındı.');
    }

    /**
     * Superadmin tarafından direkt B2C onayı — acente başvuru beklemeden eklenir.
     * Zaten onaylı transfer tedarikçileri için kısayol.
     */
    public function directApprove(Request $request)
    {
        $request->validate([
            'transfer_supplier_id' => 'required|integer|exists:transfer_suppliers,id',
            'service_types'        => 'required|array|min:1',
            'service_types.*'      => 'in:transfer,leisure,charter,tour',
            'commission_pct'       => 'nullable|numeric|min:0|max:50',
            'admin_note'           => 'nullable|string|max:500',
        ]);

        $supplier = TransferSupplier::with('user')->findOrFail($request->transfer_supplier_id);

        if (! $supplier->user_id) {
            return back()->with('error', 'Tedarikçiye bağlı bir kullanıcı bulunamadı.');
        }

        $sub = B2cAgencySubscription::updateOrCreate(
            ['user_id' => $supplier->user_id],
            [
                'transfer_supplier_id' => $supplier->id,
                'status'               => B2cAgencySubscription::STATUS_APPROVED,
                'service_types_json'   => $request->service_types,
                'commission_pct'       => $request->commission_pct ?: null,
                'reviewed_by_user_id'  => auth()->id(),
                'approved_at'          => now(),
                'rejected_at'          => null,
                'rejection_reason'     => null,
                'admin_note'           => $request->admin_note,
            ]
        );

        $action = $sub->wasRecentlyCreated ? 'oluşturuldu ve onaylandı' : 'güncellendi ve onaylandı';
        return back()->with('success', "{$supplier->company_name} B2C aboneliği {$action}.");
    }
}
