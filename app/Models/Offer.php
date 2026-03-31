<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    // Teklif durum sabitleri
    public const DURUM_BEKLEMEDE    = 'beklemede';
    public const DURUM_KABUL        = 'kabul_edildi';
    public const DURUM_REDDEDILDI   = 'reddedildi';
    public const DURUM_GIZLENDI     = 'gizlendi';

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
        'durum',
    ];

    protected $casts = [
        'ai_raw_output' => 'array',
    ];

    // Tam isim → IATA kodu normalizasyonu
    public const AIRLINE_NORMALIZE = [
        'PEGASUS'          => 'PC', 'PEGASUS AIRLINES' => 'PC', 'PGS'  => 'PC',
        'TURKISH AIRLINES' => 'TK', 'THY'              => 'TK', 'TURKISH' => 'TK',
        'SUNEXPRESS'       => 'XQ', 'SUN EXPRESS'      => 'XQ',
        'ANADOLUJET'       => 'AJ', 'ANADOLU JET'      => 'AJ', 'AJET' => 'AJ',
        'AIR EUROPA'       => 'UX',
        'LUFTHANSA'        => 'LH', 'EMIRATES'         => 'EK',
        'QATAR AIRWAYS'    => 'QR', 'QATAR'            => 'QR',
        'ETIHAD'           => 'EY', 'AEGEAN'           => 'A3',
        'WIZZ AIR'         => 'W6', 'WIZZAIR'          => 'W6',
        'RYANAIR'          => 'FR', 'EASYJET'          => 'U2',
        'BRITISH AIRWAYS'  => 'BA', 'AIR FRANCE'       => 'AF',
        'KLM'              => 'KL', 'AEROFLOT'         => 'SU',
    ];

    public static function normalizeAirline(?string $value): ?string
    {
        if (empty($value)) return $value;
        $upper = strtoupper(trim($value));
        return static::AIRLINE_NORMALIZE[$upper] ?? $upper;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($offer) {
            // Tam ismi IATA'ya çevir
            if (!empty($offer->airline)) {
                $offer->airline = static::normalizeAirline($offer->airline);
            }
            // flight_number'dan airline otomatik çıkar: "VF3002" → "VF"
            if (empty($offer->airline) && !empty($offer->flight_number)) {
                if (preg_match('/^([A-Z0-9]{2})\d+/i', strtoupper(trim($offer->flight_number)), $m)) {
                    $offer->airline = strtoupper($m[1]);
                }
            }
        });
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
