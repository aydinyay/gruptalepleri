<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_record_id',
        'sequence',
        'title',
        'due_date',
        'amount',
        'paid_amount',
        'currency',
        'status',
        'paid_at',
        'note',
        'meta',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
