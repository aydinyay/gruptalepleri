<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TursabDavet extends Model
{
    protected $table = 'tursab_davetler';

    protected $fillable = [
        'belge_no', 'eposta', 'acente_unvani', 'il',
        'tip', 'kampanya_etiket', 'status', 'hata', 'gonderen_user_id',
        'tiklanma_at', 'tiklanma_sayisi',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function gonderen()
    {
        return $this->belongsTo(User::class, 'gonderen_user_id');
    }
}
