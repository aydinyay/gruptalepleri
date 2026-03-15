<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'iata', 'icao', 'name', 'city', 'country', 'country_tr', 'country_code', 'type', 'latitude', 'longitude',
    ];

    /**
     * Havalimanı arama — IATA, ICAO, şehir, ülke, isim üzerinden.
     * Önce IATA eşleşmeleri, sonra şehir, sonra isim gelir.
     */
    public static function search(string $query, int $limit = 12): \Illuminate\Support\Collection
    {
        $q = trim($query);
        if (strlen($q) < 2) return collect();

        $upper = strtoupper($q);
        $like  = '%' . $q . '%';

        return static::query()
            ->where(function (Builder $b) use ($upper, $like) {
                $b->where('iata', 'like', $upper . '%')
                  ->orWhere('icao', 'like', $upper . '%')
                  ->orWhere('city', 'like', $like)
                  ->orWhere('country', 'like', $like)
                  ->orWhere('country_tr', 'like', $like)
                  ->orWhere('name', 'like', $like);
            })
            ->orderByRaw("
                CASE
                    WHEN iata = ?             THEN 1
                    WHEN iata LIKE ?          THEN 2
                    WHEN city LIKE ?          THEN 3
                    WHEN name LIKE ?          THEN 4
                    ELSE 5
                END, name
            ", [$upper, $upper . '%', $q . '%', $q . '%'])
            ->limit($limit)
            ->get(['iata', 'icao', 'name', 'city', 'country', 'country_tr', 'country_code']);
    }

    /**
     * Görüntüleme metni: "IST — İstanbul, Türkiye — İstanbul Havalimanı"
     */
    public function displayLabel(): string
    {
        $country = $this->country_tr ?: $this->country;
        $city    = $this->city ? "{$this->city}, {$country}" : $country;
        return "{$this->iata} — {$city} — {$this->name}";
    }
}
