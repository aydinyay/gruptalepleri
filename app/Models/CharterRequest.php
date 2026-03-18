<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharterRequest extends Model
{
    use HasFactory;

    public const TYPE_JET = 'jet';
    public const TYPE_HELICOPTER = 'helicopter';
    public const TYPE_AIRLINER = 'airliner';

    public const STATUS_LEAD = 'lead';
    public const STATUS_AI_QUOTED = 'ai_quoted';
    public const STATUS_RFQ_SENT = 'rfq_sent';
    public const STATUS_QUOTED_TO_AGENCY = 'quoted_to_agency';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PARTIAL_PAID = 'partial_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OPERATION_STARTED = 'operation_started';

    protected $fillable = [
        'user_id',
        'requester_type',
        'transport_type',
        'status',
        'name',
        'phone',
        'email',
        'from_iata',
        'to_iata',
        'departure_date',
        'pax',
        'is_flexible',
        'group_type',
        'notes',
        'ai_suggested_model',
        'ai_price_min',
        'ai_price_max',
        'ai_currency',
        'ai_risk_flags',
        'ai_comment',
        'aircraft_image_url',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'is_flexible' => 'boolean',
        'ai_risk_flags' => 'array',
        'ai_price_min' => 'decimal:2',
        'ai_price_max' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jetDetail(): HasOne
    {
        return $this->hasOne(CharterJetRequest::class);
    }

    public function helicopterDetail(): HasOne
    {
        return $this->hasOne(CharterHelicopterRequest::class);
    }

    public function airlinerDetail(): HasOne
    {
        return $this->hasOne(CharterAirlinerRequest::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(CharterQuote::class);
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(CharterSupplierQuote::class);
    }

    public function salesQuotes(): HasMany
    {
        return $this->hasMany(CharterSalesQuote::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(CharterBooking::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(CharterExtra::class);
    }
}

