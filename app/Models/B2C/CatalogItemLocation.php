<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItemLocation extends Model
{
    protected $table = 'catalog_item_locations';

    protected $fillable = ['catalog_item_id', 'type', 'name', 'slug'];

    public static array $typeLabels = [
        'belde' => 'Belde',
        'ilce'  => 'İlçe',
        'il'    => 'İl / Şehir',
        'bolge' => 'Bölge',
        'ulke'  => 'Ülke',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }
}
