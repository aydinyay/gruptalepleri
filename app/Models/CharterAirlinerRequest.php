<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterAirlinerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'date_flexible',
        'group_type',
        'route_notes',
    ];

    protected $casts = [
        'date_flexible' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }
}

