<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigortaPolice extends Model
{
    protected $table = 'sigorta_policeler';

    protected $fillable = [
        'batch_job_id',
        'acente_id',
        'b2c_user_id',
        'kanal',
        'paonet_referans',
        'paonet_teklif_id',
        'police_no',
        'paonet_urun_kodu',
        'sigortali_kimlik',
        'kimlik_tipi',
        'sigortali_adi',
        'sigortali_soyadi',
        'sigortali_dogum',
        'baslangic_tarihi',
        'bitis_tarihi',
        'gidilecek_ulke',
        'gidilecek_ulke_kodu',
        'api_doviz_turu',
        'api_doviz_tutar',
        'api_kur',
        'maliyet_tl',
        'b2b_fiyat_tl',
        'b2c_fiyat_tl',
        'satilan_fiyat_tl',
        'net_kar_tl',
        'markup_yuzde',
        'kur_tamponu_yuzde',
        'pdf_url_base',
        'pdf_link',
        'makbuz_link',
        'sertifika_link',
        'ing_sertifika_link',
        'durum',
        'hata_mesaji',
        'iptal_nedeni',
        'iptal_tarih',
        'mukerrer_police_no',
    ];

    protected $casts = [
        'sigortali_dogum'  => 'date',
        'baslangic_tarihi' => 'date',
        'bitis_tarihi'     => 'date',
        'iptal_tarih'      => 'datetime',
        'api_doviz_tutar'  => 'decimal:2',
        'api_kur'          => 'decimal:4',
        'maliyet_tl'       => 'decimal:2',
        'b2b_fiyat_tl'     => 'decimal:2',
        'b2c_fiyat_tl'     => 'decimal:2',
        'satilan_fiyat_tl' => 'decimal:2',
        'net_kar_tl'       => 'decimal:2',
        'markup_yuzde'     => 'decimal:2',
        'kur_tamponu_yuzde'=> 'decimal:2',
    ];

    public function batchJob(): BelongsTo
    {
        return $this->belongsTo(SigortaBatchJob::class, 'batch_job_id');
    }

    public function acente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acente_id');
    }

    // Sigortali tam adı
    public function sigortalıAdSoyad(): string
    {
        return trim($this->sigortali_adi . ' ' . $this->sigortali_soyadi);
    }

    // Poliçe aktif mi (iptal veya hata değil)
    public function aktifMi(): bool
    {
        return !in_array($this->durum, ['iptal', 'hata']);
    }

    // İptal edilebilir mi
    public function iptalEdilebilirMi(): bool
    {
        return $this->durum === 'tamamlandi';
    }

    // PDF proxy URL yardımcıları
    public function belgeUrl(string $tip): string
    {
        return route('acente.sigorta.belge', ['police' => $this->id, 'tip' => $tip]);
    }
}
