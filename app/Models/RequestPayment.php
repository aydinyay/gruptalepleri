<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestPayment extends Model
{
    // Payment durum sabitleri
    public const STATUS_TASLAK   = 'taslak';
    public const STATUS_AKTIF    = 'aktif';
    public const STATUS_GECIKTI  = 'gecikti';
    public const STATUS_ALINDI   = 'alindi';
    public const STATUS_IADE     = 'iade';

    protected $fillable = [
        'request_id', 'offer_id', 'sequence', 'payment_type', 'payment_method',
        'bank_name', 'sender_masked', 'account_masked',
        'amount', 'currency', 'payment_date', 'due_date', 'due_time', 'status', 'is_active', 'created_by',
        'gateway_provider', 'gateway_internal_reference', 'gateway_provider_reference', 'gateway_status',
        'request_payload_json', 'response_payload_json', 'callback_payload_json',
        'failure_reason', 'processed_at', 'paid_at', 'failed_at',
        'charged_try_amount', 'fx_rate', 'fx_timestamp', 'source_currency',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'request_payload_json' => 'array',
        'response_payload_json' => 'array',
        'callback_payload_json' => 'array',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'charged_try_amount' => 'decimal:2',
        'fx_rate' => 'decimal:6',
        'fx_timestamp' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
