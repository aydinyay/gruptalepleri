<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\B2C\B2cUser;

class TransferBooking extends Model
{
    use HasFactory;

    public const STATUS_PAYMENT_PENDING = 'payment_pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const SOURCE_B2B = 'b2b';
    public const SOURCE_B2C = 'b2c';

    protected $fillable = [
        'booking_ref',
        'source',
        'locale',
        'b2c_user_id',
        'b2c_contact_name',
        'b2c_contact_phone',
        'b2c_contact_email',
        'quote_lock_id',
        'supplier_id',
        'agency_user_id',
        'created_by_user_id',
        'airport_id',
        'zone_id',
        'vehicle_type_id',
        'direction',
        'pax',
        'pickup_at',
        'return_at',
        'status',
        'currency',
        'subtotal_amount',
        'commission_amount',
        'total_amount',
        'refundable_amount',
        'price_snapshot_json',
        'supplier_policy_snapshot_json',
        'notes',
        'confirmed_at',
        'failed_at',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
    ];

    protected $casts = [
        'pickup_at' => 'datetime',
        'return_at' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refundable_amount' => 'decimal:2',
        'price_snapshot_json' => 'array',
        'supplier_policy_snapshot_json' => 'array',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function quoteLock(): BelongsTo
    {
        return $this->belongsTo(TransferQuoteLock::class, 'quote_lock_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'supplier_id');
    }

    public function agencyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(TransferPaymentTransaction::class, 'transfer_booking_id');
    }

    public function b2cCustomer(): BelongsTo
    {
        return $this->belongsTo(B2cUser::class, 'b2c_user_id');
    }

    public function isB2C(): bool
    {
        return $this->source === self::SOURCE_B2C;
    }
}

