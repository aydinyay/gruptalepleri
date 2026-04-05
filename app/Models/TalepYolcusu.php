<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalepYolcusu extends Model
{
    protected $table = 'talep_yolculari';

    protected $fillable = [
        'request_id',
        'sira',
        'tur',
        'ad',
        'soyad',
        'kimlik_no',
        'kimlik_tipi',
        'dogum_tarihi',
        'uyruk',
        'cinsiyet',
        'olusturan_id',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
