<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharterPresetPackage extends Model
{
    protected $fillable = [
        'code',
        'title',
        'summary',
        'transport_type',
        'from_iata',
        'to_iata',
        'from_label',
        'to_label',
        'aircraft_label',
        'suggested_pax',
        'trip_type',
        'group_type',
        'cabin_preference',
        'price',
        'currency',
        'hero_image_url',
        'highlights_json',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'highlights_json' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];
}
