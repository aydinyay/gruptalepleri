<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'airport_id',
        'zone_id',
        'vehicle_type_id',
        'direction',
        'currency',
        'base_fare',
        'per_km',
        'per_minute',
        'minimum_fare',
        'night_start',
        'night_end',
        'night_multiplier',
        'peak_multiplier',
        'valid_from',
        'valid_until',
        'is_active',
        'cost_price',
        'b2c_price',
    ];

    protected $casts = [
        'base_fare'        => 'decimal:2',
        'per_km'           => 'decimal:2',
        'per_minute'       => 'decimal:2',
        'minimum_fare'     => 'decimal:2',
        'night_multiplier' => 'decimal:2',
        'peak_multiplier'  => 'decimal:2',
        'cost_price'       => 'decimal:2',
        'b2c_price'        => 'decimal:2',
        'valid_from'       => 'datetime',
        'valid_until'      => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'supplier_id');
    }

    public function airport(): BelongsTo
    {
        return $this->belongsTo(TransferAirport::class, 'airport_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(TransferZone::class, 'zone_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(TransferVehicleType::class, 'vehicle_type_id');
    }
}

