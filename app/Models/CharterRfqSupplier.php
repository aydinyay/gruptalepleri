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
        'is_active',
        'notes',
    ];

    protected $casts = [
        'service_types' => 'array',
        'is_active' => 'boolean',
    ];
}
