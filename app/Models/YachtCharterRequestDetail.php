<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YachtCharterRequestDetail extends Model
{
    protected $fillable = [
        'leisure_request_id',
        'start_time',
        'duration_hours',
        'marina_name',
        'route_plan',
        'event_type',
        'vessel_style',
        'detail_json',
    ];

    protected $casts = [
        'detail_json' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeisureRequest::class, 'leisure_request_id');
    }
}
