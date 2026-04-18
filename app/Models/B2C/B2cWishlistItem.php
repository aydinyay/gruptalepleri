<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2cWishlistItem extends Model
{
    public $timestamps = false;
    protected $table   = 'b2c_wishlist_items';
    protected $fillable = ['session_id', 'catalog_item_id', 'created_at'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }
}
