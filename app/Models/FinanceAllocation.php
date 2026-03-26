<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_record_id',
        'finance_transaction_id',
        'allocation_type',
        'amount',
        'currency',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }
}
