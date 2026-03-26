<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeisureRequestExtra extends Model
{
    protected $fillable = [
        'leisure_request_id',
        'extra_option_id',
        'title',
        'agency_note',
        'unit_price',
        'quantity',
        'currency',
        'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeisureRequest::class, 'leisure_request_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(LeisureExtraOption::class, 'extra_option_id');
    }
}
