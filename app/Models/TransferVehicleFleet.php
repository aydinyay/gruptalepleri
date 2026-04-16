<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferVehicleFleet extends Model
{
    protected $table = 'transfer_vehicle_fleet';

    protected $fillable = [
        'supplier_id',
        'vehicle_type_id',
        'quantity',
        'max_daily_bookings',
        'is_active',
    ];

    protected $casts = [
        'quantity'           => 'integer',
        'max_daily_bookings' => 'integer',
        'is_active'          => 'boolean',
    ];

    // ── İlişkiler ────────────────────────────────────────────────
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'supplier_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(TransferVehicleType::class, 'vehicle_type_id');
    }

    // ── Yardımcı ─────────────────────────────────────────────────
    /**
     * Belirtilen tarihteki mevcut rezervasyon sayısını döner.
     * max_daily_bookings - mevcut = müsait slot
     */
    public function bookedCountForDate(\Carbon\Carbon $date): int
    {
        return TransferBooking::where('supplier_id', $this->supplier_id)
            ->where('vehicle_type_id', $this->vehicle_type_id)
            ->whereDate('pickup_at', $date->toDateString())
            ->whereNotIn('status', [
                TransferBooking::STATUS_CANCELLED,
                TransferBooking::STATUS_FAILED,
            ])
            ->count();
    }

    public function availableSlotsForDate(\Carbon\Carbon $date): int
    {
        return max(0, $this->max_daily_bookings - $this->bookedCountForDate($date));
    }

    public function isAvailableForDate(\Carbon\Carbon $date): bool
    {
        return $this->availableSlotsForDate($date) > 0;
    }
}
