<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeisureExtraOption extends Model
{
    protected $fillable = [
        'product_type',
        'category',
        'code',
        'title_tr',
        'title_en',
        'description_tr',
        'description_en',
        'default_included',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_included' => 'boolean',
        'is_active' => 'boolean',
    ];
}
