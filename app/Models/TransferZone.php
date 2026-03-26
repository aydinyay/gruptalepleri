<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'airport_id',
        'name',
        'slug',
        'city',
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

    public function airport(): BelongsTo
    {
        return $this->belongsTo(TransferAirport::class, 'airport_id');
    }
}

