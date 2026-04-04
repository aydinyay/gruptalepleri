<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kampanya extends Model
{
    protected $table = 'kampanyalar';

    protected $fillable = [
        'ad', 'aciklama', 'tip', 'sablon_id',
        'hedef', 'zamanlama', 'durum', 'etiket', 'olusturan_id',
    ];

    protected $casts = [
        'hedef'     => 'array',
        'zamanlama' => 'array',
    ];

    public function sablon()
    {
        return $this->belongsTo(KampanyaSablon::class, 'sablon_id');
    }

    public function gonderilenler()
    {
        return $this->hasMany(TursabDavet::class, 'kampanya_etiket', 'etiket');
    }
}
