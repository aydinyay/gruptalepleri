<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsNotificationSetting extends Model
{
    protected $fillable = ['label', 'phone', 'event', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function phonesForEvent(string $event): array
    {
        return static::where('is_active', true)
            ->where(fn($q) => $q->where('event', $event)->orWhere('event', 'all'))
            ->pluck('phone')
            ->flatMap(fn($p) => array_map('trim', explode(',', $p)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function eventLabel(string $event): string
    {
        return match($event) {
            'new_agency'     => 'Yeni Acente Kaydı',
            'new_request'    => 'Yeni Talep',
            'offer_added'    => 'Teklif Eklendi',
            'offer_accepted' => 'Teklif Kabul Edildi',
            'all'            => 'Tüm Olaylar',
            default          => $event,
        };
    }
}
