<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferVehicleType extends Model
{
    use HasFactory;

    // Desteklenen donanim kodlari ve etiketleri
    public const AMENITY_LABELS = [
        'wifi'            => 'WiFi',
        'ac'              => 'Klima',
        'refreshments'    => 'Su / İkram',
        'child_seat'      => 'Çocuk Koltuğu',
        'usb'             => 'USB Şarj',
        'leather'         => 'Deri Koltuk',
        'panoramic'       => 'Panoramik Cam',
        'disabled_access' => 'Engelli Erişim',
        'luggage_assist'  => 'Bagaj Yardımı',
        'tv'              => 'TV / Ekran',
    ];

    public const AMENITY_ICONS = [
        'wifi'            => 'fas fa-wifi',
        'ac'              => 'fas fa-snowflake',
        'refreshments'    => 'fas fa-bottle-water',
        'child_seat'      => 'fas fa-baby',
        'usb'             => 'fas fa-plug',
        'leather'         => 'fas fa-couch',
        'panoramic'       => 'fas fa-panorama',
        'disabled_access' => 'fas fa-wheelchair',
        'luggage_assist'  => 'fas fa-suitcase-rolling',
        'tv'              => 'fas fa-tv',
    ];

    protected $fillable = [
        'code',
        'name',
        'max_passengers',
        'is_active',
        'sort_order',
        'description',
        'amenities_json',
        'suggested_retail_price',
        'luggage_capacity',
    ];

    protected $casts = [
        'max_passengers'         => 'integer',
        'is_active'              => 'boolean',
        'amenities_json'         => 'array',
        'suggested_retail_price' => 'decimal:2',
        'luggage_capacity'       => 'integer',
    ];

    public function pricingRules(): HasMany
    {
        return $this->hasMany(TransferPricingRule::class, 'vehicle_type_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(TransferVehicleMedia::class, 'vehicle_type_id')->orderBy('sort_order');
    }

    /** @return array<int, array{code:string, label:string, icon:string}> */
    public function activeAmenities(): array
    {
        $codes = is_array($this->amenities_json) ? $this->amenities_json : [];
        $result = [];
        foreach ($codes as $code) {
            $code = (string) $code;
            if (isset(self::AMENITY_LABELS[$code])) {
                $result[] = [
                    'code'  => $code,
                    'label' => self::AMENITY_LABELS[$code],
                    'icon'  => self::AMENITY_ICONS[$code] ?? 'fas fa-check',
                ];
            }
        }
        return $result;
    }

    public function firstPhotoUrl(): ?string
    {
        return $this->media
            ->where('media_type', 'photo')
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->first()
            ?->resolvedUrl();
    }
}

