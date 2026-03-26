<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferQuoteLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'supplier_id',
        'airport_id',
        'zone_id',
        'vehicle_type_id',
        'direction',
        'currency',
        'pax',
        'pickup_at',
        'return_at',
        'distance_km',
        'duration_minutes',
        'subtotal_amount',
        'commission_amount',
        'total_amount',
        'price_breakdown_json',
        'snapshot_json',
        'expires_at',
        'consumed_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'pickup_at' => 'datetime',
        'return_at' => 'datetime',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'integer',
        'subtotal_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'price_breakdown_json' => 'array',
        'snapshot_json' => 'array',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }
}

