<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharterBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_request_id',
        'sales_quote_id',
        'status',
        'total_amount',
        'total_paid',
        'remaining_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CharterRequest::class, 'charter_request_id');
    }

    public function salesQuote(): BelongsTo
    {
        return $this->belongsTo(CharterSalesQuote::class, 'sales_quote_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CharterPayment::class, 'charter_booking_id');
    }
}

