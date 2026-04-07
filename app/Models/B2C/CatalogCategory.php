<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogCategory extends Model
{
    protected $table = 'catalog_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'cover_image',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Scope'lar ──────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── İlişkiler ──────────────────────────────────────────────────────────

    public function parent()
    {
        return $this->belongsTo(CatalogCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CatalogCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function items()
    {
        return $this->hasMany(CatalogItem::class, 'category_id');
    }

    public function publishedItems()
    {
        return $this->hasMany(CatalogItem::class, 'category_id')
            ->where('is_published', true)
            ->where('is_active', true);
    }

    // ── Erişimciler ────────────────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return route('b2c.catalog.category', $this->slug);
    }
}
