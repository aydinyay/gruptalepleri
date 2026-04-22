<?php

namespace App\Models\B2C;

use App\Models\B2C\CatalogItemLocation;
use App\Models\TransferAirport;
use App\Models\TransferZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogItem extends Model
{
    protected $table = 'catalog_items';

    protected $fillable = [
        'category_id',
        'owner_type',
        'supplier_id',
        'supplier_name',
        'supplier_logo_url',
        'product_type',
        'product_subtype',
        'reference_type',
        'reference_id',
        'title',
        'slug',
        'short_desc',
        'full_desc',
        'cover_image',
        'gallery_json',
        'pricing_type',
        'base_price',
        'currency',
        'is_active',
        'is_featured',
        'badge_label',
        'is_published',
        'publish_status',
        'published_at',
        'destination_city',
        'destination_district',
        'destination_area',
        'destination_country',
        'venue_address',
        'venue_lat',
        'venue_lng',
        'duration_days',
        'duration_hours',
        'min_pax',
        'max_pax',
        'sort_order',
        'cost_price',
        'gt_price',
        'pricing_unit',
        'pricing_notes',
        'rating_avg',
        'review_count',
        'meta_title',
        'meta_description',
        'transfer_airport_id',
        'transfer_zone_id',
        'transfer_direction',
    ];

    protected function casts(): array
    {
        return [
            'gallery_json'  => 'array',
            'is_active'     => 'boolean',
            'is_featured'   => 'boolean',
            'is_published'  => 'boolean',
            'published_at'  => 'datetime',
            'base_price'    => 'decimal:2',
            'cost_price'    => 'decimal:2',
            'gt_price'      => 'decimal:2',
            'sort_order'    => 'integer',
            'venue_lat'     => 'decimal:7',
            'venue_lng'     => 'decimal:7',
        ];
    }

    // ── publish_status sabitleri ───────────────────────────────────────────
    const STATUS_DRAFT = 'draft';
    const STATUS_B2B   = 'b2b';
    const STATUS_B2C   = 'b2c';

    // ── Scope'lar ──────────────────────────────────────────────────────────

    /** GR B2C vitrin: sadece 'b2c' */
    public function scopeB2cVisible(Builder $query): Builder
    {
        return $query->where('publish_status', self::STATUS_B2C)->where('is_active', true);
    }

    /** GT acente katalog: 'b2b' veya 'b2c' */
    public function scopeB2bVisible(Builder $query): Builder
    {
        return $query->whereIn('publish_status', [self::STATUS_B2B, self::STATUS_B2C])->where('is_active', true);
    }

    /** Geriye dönük uyumluluk — eski scopePublished çağrıları b2cVisible'a yönlenir */
    public function scopePublished(Builder $query): Builder
    {
        return $this->scopeB2cVisible($query);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('product_type', $type);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('destination_city', 'LIKE', $city);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    // ── İlişkiler ──────────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(CatalogCategory::class, 'category_id');
    }

    public function locations()
    {
        return $this->hasMany(CatalogItemLocation::class, 'catalog_item_id');
    }

    /** Tedarikçi acente — gruptalepleri.com'daki B2B user */
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /** Tedarikçinin acente profili (company_title, phone vb.) */
    public function supplierAgency()
    {
        return $this->hasOneThrough(
            \App\Models\Agency::class,
            User::class,
            'id',           // users.id
            'user_id',      // agencies.user_id
            'supplier_id',  // catalog_items.supplier_id
            'id'            // users.id
        );
    }

    /** Ürün sayfasında gösterilecek tedarikçi adı */
    public function getSupplierDisplayNameAttribute(): string
    {
        if ($this->supplier_name) {
            return $this->supplier_name;
        }
        if ($this->supplierAgency?->company_title) {
            return $this->supplierAgency->company_title;
        }
        if ($this->supplier?->name) {
            return $this->supplier->name;
        }
        return 'Grup Rezervasyonları';
    }

    /** Transfer rotası: havalimanı */
    public function transferAirport()
    {
        return $this->belongsTo(TransferAirport::class, 'transfer_airport_id');
    }

    /** Transfer rotası: bölge/otel bölgesi */
    public function transferZone()
    {
        return $this->belongsTo(TransferZone::class, 'transfer_zone_id');
    }

    /** Bu ürün sayfasında canlı transfer fiyat sorgusu yapılabilir mi? */
    public function hasLiveTransferPricing(): bool
    {
        return $this->product_type === 'transfer'
            && $this->transfer_airport_id !== null
            && $this->transfer_zone_id !== null
            && $this->transfer_direction !== null;
    }

    // ── Erişimciler ────────────────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return route('b2c.product.show', $this->slug);
    }

    public function getFormattedPriceAttribute(): ?string
    {
        if ($this->base_price === null) {
            return null;
        }

        return number_format((float) $this->base_price, 0, ',', '.') . ' ' . $this->currency;
    }

    public function getPricingLabelAttribute(): string
    {
        return match ($this->pricing_type) {
            'fixed'   => 'Hemen Al',
            'quote'   => 'Fiyat Al',
            'request' => 'Talep Oluştur',
            default   => 'İncele',
        };
    }

    public function getIsOwnedByPlatformAttribute(): bool
    {
        return $this->owner_type === 'platform';
    }
}
