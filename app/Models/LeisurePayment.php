<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeisurePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'leisure_booking_id',
        'reference',
        'method',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_reference',
        'internal_reference',
        'request_payload_json',
        'response_payload_json',
        'callback_payload_json',
        'failure_reason',
        'processed_at',
        'paid_at',
        'failed_at',
        'charged_try_amount',
        'fx_rate',
        'fx_timestamp',
        'source_currency',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charged_try_amount' => 'decimal:2',
        'fx_rate' => 'decimal:6',
        'request_payload_json' => 'array',
        'response_payload_json' => 'array',
        'callback_payload_json' => 'array',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'fx_timestamp' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(LeisureBooking::class, 'leisure_booking_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

