<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'request_id',
        'airline',
        'airline_pnr',
        'flight_number',
        'flight_departure_time',
        'flight_arrival_time',
        'baggage_kg',
        'supplier_reference',
        'pax_confirmed',
        'currency',
        'price_per_pax',
        'total_price',
        'cost_price',
        'profit_amount',
        'profit_percent',
        'deposit_rate',
        'deposit_amount',
        'kk_enabled',
        'option_date',
        'option_time',
        'offer_text',
        'admin_raw_note',
        'ai_raw_output',
        'created_by',
        'is_visible',
        'is_accepted',
        'accepted_at',
    ];

    protected $casts = [
        'ai_raw_output' => 'array',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}