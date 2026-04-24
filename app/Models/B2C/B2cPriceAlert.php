<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class B2cPriceAlert extends Model
{
    protected $table    = 'b2c_price_alerts';
    protected $fillable = [
        'session_id', 'b2c_user_id', 'catalog_item_id',
        'slug', 'price_at_subscription', 'email', 'last_notified_at',
    ];

    protected $casts = [
        'price_at_subscription' => 'decimal:2',
        'last_notified_at'      => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public static function register(string $sessionId, ?int $userId, int $itemId, string $slug, ?float $price, ?string $email): self
    {
        return static::firstOrCreate(
            ['session_id' => $sessionId, 'catalog_item_id' => $itemId],
            [
                'b2c_user_id'          => $userId,
                'slug'                 => $slug,
                'price_at_subscription'=> $price,
                'email'                => $email,
            ]
        );
    }
}
