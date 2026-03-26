<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_booking_id',
        'method',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_reference',
        'internal_reference',
        'receipt_path',
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
        'approved_by_user_id',
        'approved_at',
        'admin_note',
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
        'approved_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CharterBooking::class, 'charter_booking_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
