<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharterSalesQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'supplier_quote_id',
        'base_supplier_price',
        'markup_percent',
        'min_profit',
        'sale_price',
        'currency',
        'is_override',
        'override_reason',
        'status',
    ];

    protected $casts = [
        'base_supplier_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'min_profit' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_override' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(CharterSupplierQuote::class, 'supplier_quote_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(CharterBooking::class, 'sales_quote_id');
    }
}

