<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeisureRequest extends Model
{
    use HasFactory;

    public const PRODUCT_DINNER_CRUISE = 'dinner_cruise';
    public const PRODUCT_YACHT = 'yacht';
    public const PRODUCT_TOUR = 'tour';

    public const STATUS_NEW = 'new';
    public const STATUS_OFFER_SENT = 'offer_sent';
    public const STATUS_REVISED = 'revised';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IN_OPERATION = 'in_operation';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'gtpnr',
        'product_type',
        'status',
        'service_date',
        'guest_count',
        'transfer_required',
        'hotel_name',
        'transfer_region',
        'guest_name',
        'guest_phone',
        'package_level',
        'alcohol_preference',
        'menu_preference',
        'language_preference',
        'nationality',
        'notes',
        'extra_requests',
        'approved_at',
        'operated_at',
        'completed_at',
    ];

    protected $casts = [
        'service_date' => 'date',
        'transfer_required' => 'boolean',
        'approved_at' => 'datetime',
        'operated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'gtpnr';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dinnerCruiseDetail(): HasOne
    {
        return $this->hasOne(DinnerCruiseRequestDetail::class);
    }

    public function yachtDetail(): HasOne
    {
        return $this->hasOne(YachtCharterRequestDetail::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(LeisureRequestExtra::class);
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(LeisureSupplierQuote::class);
    }

    public function clientOffers(): HasMany
    {
        return $this->hasMany(LeisureClientOffer::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(LeisureBooking::class);
    }

    public function productLabel(): string
    {
        return match ($this->product_type) {
            self::PRODUCT_DINNER_CRUISE => 'Bosphorus Dinner Cruise',
            self::PRODUCT_YACHT => 'Yacht Charter',
            self::PRODUCT_TOUR => 'Tur Paketi',
            default => ucfirst((string) $this->product_type),
        };
    }
}
