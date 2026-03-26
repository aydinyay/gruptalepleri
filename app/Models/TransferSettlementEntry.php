<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferSettlementEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_booking_id',
        'supplier_id',
        'status',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'currency',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TransferBooking::class, 'transfer_booking_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'supplier_id');
    }
}

