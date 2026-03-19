<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\FinanceReceiptSubmission;
use App\Models\FinanceRecord;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $coreReady = Schema::hasTable('finance_records') && Schema::hasTable('finance_transactions');

        if (!$coreReady) {
            return view('acente.finance.index', [
                'coreReady' => false,
                'records' => collect(),
                'submissions' => collect(),
                'summary' => [
                    'open_total' => 0,
                    'paid_total' => 0,
                    'remaining_total' => 0,
                    'pending_transactions' => 0,
                ],
            ]);
        }

        $records = FinanceRecord::query()
            ->where('agency_user_id', auth()->id())
            ->with(['transactions' => fn ($q) => $q->latest()->limit(5)])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $submissions = Schema::hasTable('finance_receipt_submissions')
            ? FinanceReceiptSubmission::query()
                ->where('agency_user_id', auth()->id())
                ->with('record')
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $summary = [
            'open_total' => (float) FinanceRecord::query()
                ->where('agency_user_id', auth()->id())
                ->whereIn('status', ['open', 'partial'])
                ->sum('gross_amount'),
            'paid_total' => (float) FinanceRecord::query()
                ->where('agency_user_id', auth()->id())
                ->sum('paid_amount'),
            'remaining_total' => (float) FinanceRecord::query()
                ->where('agency_user_id', auth()->id())
                ->sum('remaining_amount'),
            'pending_transactions' => FinanceTransaction::query()
                ->where('payer_user_id', auth()->id())
                ->whereIn('status', ['pending', 'awaiting_validation'])
                ->count(),
        ];

        return view('acente.finance.index', compact('coreReady', 'records', 'summary', 'submissions'));
    }
}
