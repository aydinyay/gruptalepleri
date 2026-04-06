<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MesajSablon extends Model
{
    protected $table = 'mesaj_sablonlari';

    protected $fillable = [
        'sablon_adi',
        'email_konu',
        'email_govde',
        'sms_govde',
        'kanallar',
    ];

    protected $casts = [
        'kanallar' => 'array',
    ];
}
