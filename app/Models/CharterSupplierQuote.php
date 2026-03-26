<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharterSupplierQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'supplier_name',
        'supplier_channel',
        'model_name',
        'aircraft_image_url',
        'supplier_price',
        'currency',
        'supplier_note',
        'whatsapp_text',
        'ai_analysis',
        'ai_score',
        'status',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'supplier_price' => 'decimal:2',
        'ai_score' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }

    public function salesQuote(): HasOne
    {
        return $this->hasOne(CharterSalesQuote::class, 'supplier_quote_id');
    }
}

