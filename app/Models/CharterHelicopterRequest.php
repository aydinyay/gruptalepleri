<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterHelicopterRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'pickup',
        'dropoff',
        'landing_details',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }
}

