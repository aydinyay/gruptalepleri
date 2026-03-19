<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\FinanceReceiptSubmission;
use App\Models\FinanceRecord;
use App\Models\FinanceRefund;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);

        $coreReady = Schema::hasTable('finance_records') && Schema::hasTable('finance_transactions');
        if (!$coreReady) {
            return view('superadmin.finance.index', [
                'coreReady' => false,
                'records' => collect(),
                'summary' => [
                    'open_total' => 0,
                    'paid_total' => 0,
                    'remaining_total' => 0,
                    'pending_transactions' => 0,
                    'pending_receipts' => 0,
                    'requested_refunds' => 0,
                ],
                'agencyUsers' => collect(),
                'openRecords' => collect(),
            ]);
        }

        $records = FinanceRecord::query()
            ->with(['agencyUser', 'transactions' => fn ($q) => $q->latest()->limit(5)])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $summary = [
            'open_total' => (float) FinanceRecord::query()->whereIn('status', ['open', 'partial'])->sum('gross_amount'),
            'paid_total' => (float) FinanceRecord::query()->sum('paid_amount'),
            'remaining_total' => (float) FinanceRecord::query()->sum('remaining_amount'),
            'pending_transactions' => FinanceTransaction::query()->whereIn('status', ['pending', 'awaiting_validation'])->count(),
            'pending_receipts' => Schema::hasTable('finance_receipt_submissions')
                ? FinanceReceiptSubmission::query()->whereIn('status', ['pending', 'needs_review', 'insufficient_data'])->count()
                : 0,
            'requested_refunds' => Schema::hasTable('finance_refunds')
                ? FinanceRefund::query()->where('status', 'requested')->count()
                : 0,
        ];

        $agencyUsers = User::query()
            ->where('role', 'acente')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'email']);

        $openRecords = FinanceRecord::query()
            ->with('agencyUser')
            ->whereIn('status', ['open', 'partial', 'paid'])
            ->latest('id')
            ->limit(500)
            ->get(['id', 'document_ref', 'title', 'currency', 'status', 'agency_user_id', 'remaining_amount', 'paid_amount']);

        return view('superadmin.finance.index', compact('coreReady', 'records', 'summary', 'agencyUsers', 'openRecords'));
    }
}
