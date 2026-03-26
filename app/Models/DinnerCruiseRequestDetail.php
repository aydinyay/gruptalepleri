<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DinnerCruiseRequestDetail extends Model
{
    protected $fillable = [
        'leisure_request_id',
        'session_time',
        'pier_name',
        'adult_count',
        'child_count',
        'infant_count',
        'celebration_type',
        'shared_cruise',
        'detail_json',
    ];

    protected $casts = [
        'shared_cruise' => 'boolean',
        'detail_json' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeisureRequest::class, 'leisure_request_id');
    }
}
