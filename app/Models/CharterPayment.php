<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharterPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'charter_booking_id',
        'method',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_reference',
        'receipt_path',
        'approved_by_user_id',
        'approved_at',
        'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CharterBooking::class, 'charter_booking_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}

