<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestNotification extends Model
{
    protected $fillable = [
        'request_id',
        'channel',
        'recipient',
        'recipient_name',
        'phone',
        'message',
        'status',
        'provider_code',
        'delivery_status',
        'delivered_at',
        'sent_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
