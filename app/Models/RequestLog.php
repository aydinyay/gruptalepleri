<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $fillable = [
        'request_id',
        'action',
        'description',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}