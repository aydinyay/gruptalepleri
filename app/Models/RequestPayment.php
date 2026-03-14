<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestPayment extends Model
{
    protected $fillable = [
        'request_id', 'sequence', 'payment_type', 'payment_method',
        'bank_name', 'sender_masked', 'account_masked',
        'amount', 'currency', 'payment_date', 'status', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
