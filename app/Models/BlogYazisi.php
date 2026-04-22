<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogYazisi extends Model
{
    protected $table = 'blog_yazilari';

    protected $fillable = [
        'kategori_id', 'baslik', 'slug', 'ozet', 'icerik',
        'kapak_gorseli', 'meta_baslik', 'meta_aciklama',
        'yazar', 'durum', 'yayinlanma_tarihi',
    ];

    protected $casts = [
        'yayinlanma_tarihi' => 'datetime',
    ];

    public function kategori()
    {
        return $this->belongsTo(BlogKategorisi::class, 'kategori_id');
    }

    public function scopeYayinda($query)
    {
        return $query->where('durum', 'yayinda')
                     ->where('yayinlanma_tarihi', '<=', now());
    }

    public function getKapakGorseliUrlAttribute(): ?string
    {
        if (!$this->kapak_gorseli) return null;
        if (str_starts_with($this->kapak_gorseli, 'http')) return $this->kapak_gorseli;
        return asset(ltrim($this->kapak_gorseli, '/'));
    }
}
