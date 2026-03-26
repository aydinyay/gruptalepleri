<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeisurePackageTemplate extends Model
{
    protected $fillable = [
        'product_type',
        'code',
        'level',
        'name_tr',
        'name_en',
        'summary_tr',
        'summary_en',
        'hero_image_url',
        'includes_tr',
        'includes_en',
        'excludes_tr',
        'excludes_en',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'includes_tr' => 'array',
        'includes_en' => 'array',
        'excludes_tr' => 'array',
        'excludes_en' => 'array',
        'is_active' => 'boolean',
    ];
}
