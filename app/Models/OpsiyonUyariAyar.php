<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsiyonUyariAyar extends Model
{
    protected $table = 'opsiyon_uyari_ayarlari';

    protected $fillable = ['saat_oncesi', 'sms_aktif', 'push_aktif', 'is_active'];

    protected $casts = [
        'sms_aktif'  => 'boolean',
        'push_aktif' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public static function aktifler()
    {
        return static::where('is_active', true)->orderBy('saat_oncesi', 'desc')->get();
    }
}
