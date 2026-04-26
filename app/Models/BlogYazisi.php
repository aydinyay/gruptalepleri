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
        'baslik_translations', 'ozet_translations', 'icerik_translations',
        'meta_baslik_translations', 'meta_aciklama_translations',
    ];

    protected $casts = [
        'yayinlanma_tarihi'         => 'datetime',
        'baslik_translations'       => 'array',
        'ozet_translations'         => 'array',
        'icerik_translations'       => 'array',
        'meta_baslik_translations'  => 'array',
        'meta_aciklama_translations'=> 'array',
    ];

    public function translatedBaslik(): string
    {
        return $this->translatedField('baslik_translations', 'baslik') ?? $this->baslik;
    }

    public function translatedOzet(): ?string
    {
        return $this->translatedField('ozet_translations', 'ozet');
    }

    public function translatedIcerik(): ?string
    {
        return $this->translatedField('icerik_translations', 'icerik');
    }

    public function translatedMetaBaslik(): ?string
    {
        return $this->translatedField('meta_baslik_translations', 'meta_baslik');
    }

    public function translatedMetaAciklama(): ?string
    {
        return $this->translatedField('meta_aciklama_translations', 'meta_aciklama');
    }

    private function translatedField(string $jsonColumn, string $fallbackColumn): ?string
    {
        $locale = app()->getLocale();
        if ($locale !== 'tr') {
            $translations = $this->{$jsonColumn};
            if (is_array($translations) && ! empty($translations[$locale])) {
                return $translations[$locale];
            }
        }
        return $this->{$fallbackColumn};
    }

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
