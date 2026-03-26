<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_record_id',
        'finance_transaction_id',
        'refund_transaction_id',
        'amount',
        'currency',
        'method',
        'status',
        'reason',
        'initiated_by_user_id',
        'approved_by_user_id',
        'processed_by_user_id',
        'approved_at',
        'processed_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
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

    public function refundTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'refund_transaction_id');
    }
}
