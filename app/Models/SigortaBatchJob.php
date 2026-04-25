<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SigortaBatchJob extends Model
{
    protected $table = 'sigorta_batch_jobs';

    protected $fillable = [
        'islem_adi',
        'kanal',
        'acente_id',
        'b2c_user_id',
        'toplam',
        'tamamlanan',
        'basarisiz',
        'durum',
        'total_amount_try',
        'bekleyen_satirlar',
        'fiyatlanmis_satirlar',
        'hatali_satirlar',
    ];

    protected $casts = [
        'bekleyen_satirlar'    => 'array',
        'fiyatlanmis_satirlar' => 'array',
        'hatali_satirlar'      => 'array',
        'total_amount_try'     => 'decimal:2',
    ];

    public function policeler(): HasMany
    {
        return $this->hasMany(SigortaPolice::class, 'batch_job_id');
    }

    public function acente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acente_id');
    }

    public function odeme(): HasOne
    {
        return $this->hasOne(SigortaOdeme::class, 'sigorta_batch_job_id');
    }

    public function ilerlemeYuzdesi(): int
    {
        if ($this->toplam === 0) return 0;
        return (int) round(($this->tamamlanan / $this->toplam) * 100);
    }

    public function tamamlandiMi(): bool
    {
        return in_array($this->durum, ['tamamlandi', 'hata']);
    }
}
