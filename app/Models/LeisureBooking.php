<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeisureBooking extends Model
{
    protected $fillable = [
        'leisure_request_id',
        'client_offer_id',
        'status',
        'total_amount',
        'total_paid',
        'remaining_amount',
        'currency',
        'operation_note',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeisureRequest::class, 'leisure_request_id');
    }

    public function clientOffer(): BelongsTo
    {
        return $this->belongsTo(LeisureClientOffer::class, 'client_offer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LeisurePayment::class, 'leisure_booking_id');
    }
}
