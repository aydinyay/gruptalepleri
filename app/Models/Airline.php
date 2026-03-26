<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Airline extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'iata', 'icao', 'name', 'alias', 'callsign', 'country', 'country_tr', 'active',
    ];

    public static function search(string $query, int $limit = 10): \Illuminate\Support\Collection
    {
        $q     = trim($query);
        if (strlen($q) < 2) return collect();

        $upper = strtoupper($q);
        $like  = '%' . $q . '%';

        return static::query()
            ->where('active', true)
            ->where(function (Builder $b) use ($upper, $like) {
                $b->where('iata', $upper)
                  ->orWhere('icao', $upper)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('alias', 'like', $like)
                  ->orWhere('callsign', 'like', $like);
            })
            ->orderByRaw("
                CASE
                    WHEN iata = ?    THEN 1
                    WHEN icao = ?    THEN 2
                    WHEN name LIKE ? THEN 3
                    ELSE 4
                END, name
            ", [$upper, $upper, $q . '%'])
            ->limit($limit)
            ->get(['iata', 'icao', 'name', 'alias', 'country', 'country_tr']);
    }

    public function displayLabel(): string
    {
        $parts = [];
        if ($this->iata) $parts[] = $this->iata;
        if ($this->icao) $parts[] = $this->icao;
        $prefix  = $parts ? implode('/', $parts) . ' — ' : '';
        $country = $this->country_tr ?: $this->country;
        $suffix  = $country ? " ({$country})" : '';
        return $prefix . $this->name . $suffix;
    }
}
