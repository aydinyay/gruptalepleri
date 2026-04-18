<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class CatalogSession extends Model
{
    protected $fillable = [
        'catalog_item_id', 'session_date', 'session_time',
        'capacity', 'booked_count', 'price_override', 'label', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'session_date'   => 'date',
            'capacity'       => 'integer',
            'booked_count'   => 'integer',
            'price_override' => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function item()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) return null;
        return max(0, $this->capacity - $this->booked_count);
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) return false;
        return $this->booked_count >= $this->capacity;
    }

    public function scopeUpcoming($query)
    {
        return $query->where('session_date', '>=', today())
                     ->where('is_active', true)
                     ->orderBy('session_date')
                     ->orderBy('session_time');
    }
}
