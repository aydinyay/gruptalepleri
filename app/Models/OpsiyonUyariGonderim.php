<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsiyonUyariGonderim extends Model
{
    protected $table = 'opsiyon_uyari_gonderimler';

    protected $fillable = ['offer_id', 'saat_oncesi', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public static function gonderildiMi(int $offerId, int $saatOncesi): bool
    {
        return static::where('offer_id', $offerId)
            ->where('saat_oncesi', $saatOncesi)
            ->exists();
    }
}
