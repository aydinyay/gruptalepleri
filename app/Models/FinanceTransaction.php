<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_record_id',
        'source_key',
        'source_type',
        'source_id',
        'payer_user_id',
        'method',
        'direction',
        'gross_amount',
        'fee_amount',
        'commission_amount',
        'net_amount',
        'currency',
        'status',
        'payment_date',
        'provider',
        'provider_reference',
        'bank_name',
        'sender_name',
        'sender_reference',
        'receipt_path',
        'notes',
        'meta',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function allocation(): HasOne
    {
        return $this->hasOne(FinanceAllocation::class, 'finance_transaction_id');
    }
}
