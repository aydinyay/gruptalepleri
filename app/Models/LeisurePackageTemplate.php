<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeisurePackageTemplate extends Model
{
    protected $fillable = [
        'product_type', 'code', 'level',
        'name_tr', 'name_en',
        'summary_tr', 'summary_en',
        'hero_image_url',
        'includes_tr', 'includes_en',
        'excludes_tr', 'excludes_en',
        'is_active', 'sort_order',
        'base_price_per_person', 'cost_price_per_person', 'supplier_name',
        'original_price_per_person', 'currency',
        'duration_hours', 'departure_times', 'pier_name', 'meeting_point',
        'max_pax', 'badge_text', 'rating', 'review_count',
        'long_description_tr', 'long_description_en',
        'timeline_tr', 'cancellation_policy_tr', 'important_notes_tr',
    ];

    protected $casts = [
        'includes_tr'               => 'array',
        'includes_en'               => 'array',
        'excludes_tr'               => 'array',
        'excludes_en'               => 'array',
        'departure_times'           => 'array',
        'timeline_tr'               => 'array',
        'important_notes_tr'        => 'array',
        'is_active'                 => 'boolean',
        'base_price_per_person'     => 'decimal:2',
        'cost_price_per_person'     => 'decimal:2',
        'original_price_per_person' => 'decimal:2',
        'duration_hours'            => 'decimal:1',
        'rating'                    => 'decimal:1',
    ];
}
