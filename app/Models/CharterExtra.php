<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'title',
        'agency_note',
        'admin_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'admin_price' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }
}

