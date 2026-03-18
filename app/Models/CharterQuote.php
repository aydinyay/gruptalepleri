<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'quote_type',
        'status',
        'title',
        'description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }
}

