<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceReceiptSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_record_id',
        'finance_transaction_id',
        'agency_user_id',
        'amount',
        'currency',
        'payment_date',
        'bank_name',
        'sender_name',
        'sender_reference',
        'receipt_path',
        'status',
        'review_note',
        'reviewed_by_user_id',
        'reviewed_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function agencyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
