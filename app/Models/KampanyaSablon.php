<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KampanyaSablon extends Model
{
    protected $table = 'kampanya_sablonlar';

    protected $fillable = [
        'ad', 'tip', 'konu', 'html_icerik', 'sms_icerik', 'aktif', 'olusturan_id',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function kampanyalar()
    {
        return $this->hasMany(Kampanya::class, 'sablon_id');
    }
}
// deploy
