<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterJetRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'flight_hours_estimate',
        'round_trip',
        'pet_onboard',
        'vip_catering',
        'wifi_required',
        'special_luggage',
        'luggage_count',
        'cabin_preference',
        'airport_slot_note',
        'specs_json',
    ];

    protected $casts = [
        'round_trip' => 'boolean',
        'pet_onboard' => 'boolean',
        'vip_catering' => 'boolean',
        'wifi_required' => 'boolean',
        'special_luggage' => 'boolean',
        'specs_json' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }
}

