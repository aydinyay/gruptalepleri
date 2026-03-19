<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharterRfqSupplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'service_types',
        'supplier_kind',
        'charter_models',
        'min_pax',
        'max_pax',
        'priority',
        'is_premium_only',
        'is_cargo_operator',
        'min_notice_hours',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'service_types' => 'array',
        'charter_models' => 'array',
        'min_pax' => 'integer',
        'max_pax' => 'integer',
        'priority' => 'integer',
        'min_notice_hours' => 'integer',
        'is_premium_only' => 'boolean',
        'is_cargo_operator' => 'boolean',
        'is_active' => 'boolean',
    ];
}
