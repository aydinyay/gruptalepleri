<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightSegment extends Model
{
    protected $fillable = [
        'request_id',
        'order',
        'from_iata',
        'from_city',
        'to_iata',
        'to_city',
        'departure_date',
        'departure_time',
        'departure_time_slot',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}