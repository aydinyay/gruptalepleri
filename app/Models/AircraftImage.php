<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AircraftImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'model_code',
        'model_name',
        'image_url',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

