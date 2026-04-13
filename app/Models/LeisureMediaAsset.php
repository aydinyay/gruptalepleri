<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeisureMediaAsset extends Model
{
    protected $fillable = [
        'product_type',
        'package_code',
        'category',
        'media_type',
        'source_type',
        'title_tr',
        'title_en',
        'file_path',
        'external_url',
        'tags_json',
        'capacity_min',
        'capacity_max',
        'luxury_level',
        'usage_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function resolvedUrl(): ?string
    {
        if ($this->source_type === 'link') {
            return $this->external_url;
        }

        if (! $this->file_path) {
            return null;
        }

        if (Str::startsWith($this->file_path, ['http://', 'https://', '/'])) {
            return $this->file_path;
        }

        return asset($this->file_path);
    }
}
