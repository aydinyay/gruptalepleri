<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class B2cPayment extends Model
{
    protected $table = 'b2c_payments';

    protected $fillable = [
        'b2c_order_id', 'reference', 'internal_reference', 'provider', 'provider_transaction_id',
        'status', 'amount', 'currency',
        'charged_try_amount', 'fx_rate', 'fx_timestamp', 'source_currency',
        'request_payload_json', 'response_payload_json', 'callback_payload_json',
        'paid_at', 'failed_at', 'refunded_at', 'processed_at', 'failure_reason',
    ];

    protected $hidden = [
        'request_payload_json',
        'response_payload_json',
        'callback_payload_json',
    ];

    protected function casts(): array
    {
        return [
            'amount'                 => 'decimal:2',
            'charged_try_amount'     => 'decimal:2',
            'fx_rate'                => 'decimal:6',
            'request_payload_json'  => 'array',
            'response_payload_json' => 'array',
            'callback_payload_json' => 'array',
            'paid_at'               => 'datetime',
            'failed_at'             => 'datetime',
            'refunded_at'           => 'datetime',
            'processed_at'          => 'datetime',
            'fx_timestamp'          => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(B2cOrder::class, 'b2c_order_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
