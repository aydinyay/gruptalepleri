<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferVehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'max_passengers',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'max_passengers' => 'integer',
        'is_active' => 'boolean',
    ];

    public function pricingRules(): HasMany
    {
        return $this->hasMany(TransferPricingRule::class, 'vehicle_type_id');
    }
}

