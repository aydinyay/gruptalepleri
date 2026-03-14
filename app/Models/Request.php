<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'gtpnr',
        'user_id',
        'type',
        'status',
        'agency_name',
        'phone',
        'email',
        'group_company_name',
        'flight_purpose',
        'trip_type',
        'pax_total',
        'pax_adult',
        'pax_child',
        'pax_infant',
        'preferred_airline',
        'hotel_needed',
        'visa_needed',
        'passenger_nationality',
        'notes',
        'ai_analysis',
        'ai_analysis_hash',
        'ai_analysis_updated_at',
    ];

    protected $casts = [
        'ai_analysis_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function segments()
    {
        return $this->hasMany(FlightSegment::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
    public function logs()
    {
        return $this->hasMany(RequestLog::class)->orderBy('created_at', 'asc');
    }

    public function payments()
    {
        return $this->hasMany(RequestPayment::class)->orderBy('sequence');
    }

    public function notifications()
    {
        return $this->hasMany(RequestNotification::class)->orderBy('created_at', 'desc');
    }
}