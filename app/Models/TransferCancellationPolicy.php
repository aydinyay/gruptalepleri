<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferCancellationPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'free_cancel_before_minutes',
        'refund_percent_after_deadline',
        'no_show_refund_percent',
        'is_active',
    ];

    protected $casts = [
        'free_cancel_before_minutes' => 'integer',
        'refund_percent_after_deadline' => 'decimal:2',
        'no_show_refund_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'supplier_id');
    }
}

