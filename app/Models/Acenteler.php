<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acenteler extends Model
{
    protected $table = 'acenteler';

    public $timestamps = false;

    protected $fillable = [
        'belge_no', 'sube_sira', 'is_sube',
        'acente_unvani', 'ticari_unvan', 'grup',
        'il', 'il_ilce', 'telefon', 'eposta',
        'adres', 'btk',
    ];
}
