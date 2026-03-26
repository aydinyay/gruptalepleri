<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferPaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_booking_id',
        'reference',
        'provider',
        'provider_transaction_id',
        'status',
        'amount',
        'currency',
        'request_payload_json',
        'response_payload_json',
        'callback_payload_json',
        'processed_at',
        'paid_at',
        'failed_at',
        'refunded_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload_json' => 'array',
        'response_payload_json' => 'array',
        'callback_payload_json' => 'array',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TransferBooking::class, 'transfer_booking_id');
    }
}

