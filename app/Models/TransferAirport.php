<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferAirport extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(TransferZone::class, 'airport_id');
    }
}

