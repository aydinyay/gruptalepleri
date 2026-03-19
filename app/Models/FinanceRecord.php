<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_user_id',
        'agency_id',
        'scope_type',
        'service_type',
        'service_id',
        'document_type',
        'document_ref',
        'title',
        'currency',
        'gross_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
        'meta',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'meta' => 'array',
    ];

    public function agencyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_user_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'finance_record_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FinanceAllocation::class, 'finance_record_id');
    }
}
