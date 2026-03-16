<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    public const STATUS_BEKLEMEDE = 'beklemede';
    public const STATUS_ISLEMDE = 'islemde';
    public const STATUS_FIYATLANDIRILDI = 'fiyatlandirildi';
    public const STATUS_DEPOZITODA = 'depozitoda';
    public const STATUS_BILETLENDI = 'biletlendi';
    public const STATUS_IADE = 'iade';
    public const STATUS_OLUMSUZ = 'olumsuz';
    public const STATUS_IPTAL = 'iptal';

    public const TRIP_TYPE_ONE_WAY = 'one_way';
    public const TRIP_TYPE_ROUND_TRIP = 'round_trip';
    public const TRIP_TYPE_MULTI = 'multi';

    public const STATUS_ALIASES = [
        'fiyatlandirildi' => self::STATUS_FIYATLANDIRILDI,
        'depozito' => self::STATUS_DEPOZITODA,
        'depozitoda' => self::STATUS_DEPOZITODA,
    ];

    public const TRIP_TYPE_ALIASES = [
        'multi_city' => self::TRIP_TYPE_MULTI,
        'multicity' => self::TRIP_TYPE_MULTI,
    ];

    public const TRIP_TYPE_LABELS = [
        self::TRIP_TYPE_ONE_WAY => 'Tek Yön',
        self::TRIP_TYPE_ROUND_TRIP => 'Gidiş - Dönüş',
        self::TRIP_TYPE_MULTI => 'Çok Ayaklı',
    ];

    protected $fillable = [
        'gtpnr',
        'user_id',
        'type',
        'status',
        'agency_name',
        'phone',
        'email',
        'group_company_name',
        'flight_purpose',
        'trip_type',
        'pax_total',
        'pax_adult',
        'pax_child',
        'pax_infant',
        'preferred_airline',
        'hotel_needed',
        'visa_needed',
        'passenger_nationality',
        'notes',
        'ai_analysis',
        'ai_analysis_hash',
        'ai_analysis_updated_at',
    ];

    protected $casts = [
        'ai_analysis_updated_at' => 'datetime',
    ];

    public function getStatusAttribute($value): ?string
    {
        return static::normalizeStatus($value);
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = static::normalizeStatus($value);
    }

    public function getTripTypeAttribute($value): ?string
    {
        return static::normalizeTripType($value);
    }

    public function setTripTypeAttribute($value): void
    {
        $this->attributes['trip_type'] = static::normalizeTripType($value);
    }

    public static function normalizeStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value), 'UTF-8');

        if (str_starts_with($normalized, 'fiyatlandir') && str_ends_with($normalized, 'ldi')) {
            return self::STATUS_FIYATLANDIRILDI;
        }

        return self::STATUS_ALIASES[$normalized] ?? $normalized;
    }

    public static function normalizeTripType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value), 'UTF-8');

        return self::TRIP_TYPE_ALIASES[$normalized] ?? $normalized;
    }

    public static function tripTypeLabel(?string $value): string
    {
        $normalized = static::normalizeTripType($value);
        if (! $normalized) {
            return 'Belirtilmedi';
        }

        return self::TRIP_TYPE_LABELS[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function segments()
    {
        return $this->hasMany(FlightSegment::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
    public function logs()
    {
        return $this->hasMany(RequestLog::class)->orderBy('created_at', 'asc');
    }

    public function payments()
    {
        return $this->hasMany(RequestPayment::class)->orderBy('sequence');
    }

    public function notifications()
    {
        return $this->hasMany(RequestNotification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Sadece "depozitoda" statüsündeki talepler için "İadede" badge'i gösterilsin mi?
     * Şu koşullardan biri sağlanıyorsa true döner:
     *  1. Herhangi bir segment'in uçuş tarihi geçmişse
     *  2. Kabul edilmiş teklifin opsiyon tarihi 2026 öncesinde bitmişse
     *  3. Notes / group_company_name / flight_purpose / ai_analysis alanlarında "iade" geçiyorsa
     */
    public function isIadede(): bool
    {
        if ($this->status !== self::STATUS_DEPOZITODA) {
            return false;
        }

        // 1. Uçuş tarihi geçmiş mi?
        foreach ($this->segments as $segment) {
            if ($segment->departure_date && Carbon::parse($segment->departure_date)->isPast()) {
                return true;
            }
        }

        // 2. Opsiyon tarihi 2026 öncesinde bitmiş mi?
        $acceptedOffer = $this->offers->firstWhere('is_accepted', true);
        if ($acceptedOffer && $acceptedOffer->option_date) {
            $optionDate = Carbon::parse($acceptedOffer->option_date);
            if ($optionDate->isPast() && $optionDate->year < 2026) {
                return true;
            }
        }

        // 3. Metin alanlarında "iade" geçiyor mu?
        $metin = implode(' ', array_filter([
            $this->notes,
            $this->group_company_name,
            $this->flight_purpose,
            $this->ai_analysis,
        ]));

        if (mb_stripos($metin, 'iade') !== false) {
            return true;
        }

        return false;
    }
}
