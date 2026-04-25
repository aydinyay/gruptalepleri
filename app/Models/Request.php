<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    public const STATUS_BEKLEMEDE = 'beklemede';
    public const STATUS_ISLEMDE = 'islemde';
    public const STATUS_FIYATLANDIRILDI = 'fiyatlandirildi';
    public const STATUS_DEPOZITODA = 'depozitoda';
    public const STATUS_BILETLENDI = 'biletlendi';
    public const STATUS_IADE = 'iade';
    public const STATUS_OLUMSUZ = 'olumsuz';
    public const STATUS_IPTAL = 'iptal';

    // Aktif adım sabitleri
    public const ADIM_TEKLIF_BEKLENIYOR      = 'teklif_bekleniyor';
    public const ADIM_KARAR_BEKLENIYOR       = 'karar_bekleniyor';
    public const ADIM_ODEME_PLANI_BEKLENIYOR = 'odeme_plani_bekleniyor';
    public const ADIM_ODEME_BEKLENIYOR       = 'odeme_bekleniyor';
    public const ADIM_ODEME_GECIKTI          = 'odeme_gecikti';
    public const ADIM_ODEME_ALINDI_DEVAM     = 'odeme_alindi_devam';
    public const ADIM_BILETLEME_BEKLENIYOR   = 'biletleme_bekleniyor';
    public const ADIM_TAMAMLANDI             = 'tamamlandi';

    // Ödeme durumu sabitleri
    public const ODEME_YOK          = 'yok';
    public const ODEME_PLANLI       = 'planli';
    public const ODEME_KISMI        = 'kismi_odendi';
    public const ODEME_GECIKTI      = 'gecikti';
    public const ODEME_TAMAMLANDI   = 'tamamlandi';

    public const TRIP_TYPE_ONE_WAY = 'one_way';
    public const TRIP_TYPE_ROUND_TRIP = 'round_trip';
    public const TRIP_TYPE_MULTI = 'multi';

    public const STATUS_ALIASES = [
        'fiyatlandirildi' => self::STATUS_FIYATLANDIRILDI,
        'depozito' => self::STATUS_DEPOZITODA,
        'depozitoda' => self::STATUS_DEPOZITODA,
    ];

    public const STATUS_META = [
        self::STATUS_BEKLEMEDE => ['label' => 'Beklemede', 'bg' => '#6c757d', 'text' => '#ffffff'],
        self::STATUS_ISLEMDE => ['label' => 'İşlemde', 'bg' => '#0d6efd', 'text' => '#ffffff'],
        self::STATUS_FIYATLANDIRILDI => ['label' => 'Fiyatlandırıldı', 'bg' => '#ffc107', 'text' => '#000000'],
        self::STATUS_DEPOZITODA => ['label' => 'Depozitoda', 'bg' => '#6f42c1', 'text' => '#ffffff'],
        self::STATUS_BILETLENDI => ['label' => 'Biletlendi', 'bg' => '#198754', 'text' => '#ffffff'],
        self::STATUS_IADE => ['label' => 'İade', 'bg' => '#dc3545', 'text' => '#ffffff'],
        self::STATUS_OLUMSUZ => ['label' => 'Olumsuz', 'bg' => '#343a40', 'text' => '#ffffff'],
        self::STATUS_IPTAL => ['label' => 'İptal', 'bg' => '#dc3545', 'text' => '#ffffff'],
    ];

    public const TRIP_TYPE_ALIASES = [
        'multi_city' => self::TRIP_TYPE_MULTI,
        'multicity' => self::TRIP_TYPE_MULTI,
    ];

    public const TRIP_TYPE_LABELS = [
        self::TRIP_TYPE_ONE_WAY => 'Tek Yön',
        self::TRIP_TYPE_ROUND_TRIP => 'Gidiş - Dönüş',
        self::TRIP_TYPE_MULTI => 'Çok Ayaklı',
    ];

    protected $fillable = [
        'gtpnr',
        'user_id',
        'source_channel',
        'locale',
        'type',
        'status',
        'aktif_adim',
        'odeme_durumu',
        'agency_name',
        'phone',
        'email',
        'group_company_name',
        'flight_purpose',
        'trip_type',
        'pax_total',
        'pax_adult',
        'pax_child',
        'pax_infant',
        'preferred_airline',
        'hotel_needed',
        'visa_needed',
        'passenger_nationality',
        'notes',
        'ai_analysis',
        'ai_analysis_hash',
        'ai_analysis_updated_at',
    ];

    protected $casts = [
        'ai_analysis_updated_at' => 'datetime',
    ];

    public function getStatusAttribute($value): ?string
    {
        return static::normalizeStatus($value);
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = static::normalizeStatus($value);
    }

    public function getTripTypeAttribute($value): ?string
    {
        return static::normalizeTripType($value);
    }

    public function setTripTypeAttribute($value): void
    {
        $this->attributes['trip_type'] = static::normalizeTripType($value);
    }

    public static function normalizeStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value), 'UTF-8');

        if (str_starts_with($normalized, 'fiyatlandir') && str_ends_with($normalized, 'ldi')) {
            return self::STATUS_FIYATLANDIRILDI;
        }

        return self::STATUS_ALIASES[$normalized] ?? $normalized;
    }

    public static function statusMeta(?string $value): array
    {
        $normalized = static::normalizeStatus($value);
        if (! $normalized) {
            return ['label' => 'Bilinmiyor', 'bg' => '#6c757d', 'text' => '#ffffff'];
        }

        return self::STATUS_META[$normalized] ?? [
            'label' => ucfirst(str_replace('_', ' ', $normalized)),
            'bg' => '#6c757d',
            'text' => '#ffffff',
        ];
    }

    public static function statusMetaMap(): array
    {
        return self::STATUS_META;
    }

    public static function normalizeTripType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value), 'UTF-8');

        return self::TRIP_TYPE_ALIASES[$normalized] ?? $normalized;
    }

    public static function tripTypeLabel(?string $value): string
    {
        $normalized = static::normalizeTripType($value);
        if (! $normalized) {
            return 'Belirtilmedi';
        }

        return self::TRIP_TYPE_LABELS[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function segments()
    {
        return $this->hasMany(FlightSegment::class);
    }

    public function yolcular()
    {
        return $this->hasMany(TalepYolcusu::class, 'request_id')->orderBy('sira');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
    public function logs()
    {
        return $this->hasMany(RequestLog::class)->orderBy('created_at', 'asc');
    }

    public function payments()
    {
        return $this->hasMany(RequestPayment::class)->orderBy('sequence');
    }

    /**
     * Şu an aktif (beklenen) ödeme adımı.
     */
    public function aktifPayment(): ?RequestPayment
    {
        return $this->payments->firstWhere('is_active', true);
    }

    /**
     * aktif_adim ve odeme_durumu alanlarını mevcut state'e göre hesaplayıp DB'ye yazar.
     * Her state geçişinden sonra çağrılmalı.
     */
    public function refreshAktifAdim(): void
    {
        // Güncel veriyi yükle
        $this->load(['offers', 'payments']);

        // — odeme_durumu —
        $payments = $this->payments;
        $bekleyenCount = $payments->whereIn('status', [
            RequestPayment::STATUS_TASLAK,
            RequestPayment::STATUS_AKTIF,
            RequestPayment::STATUS_GECIKTI,
            'bekleniyor', // eski enum değeri — geriye uyumluluk
        ])->count();
        $alinanCount = $payments->where('status', RequestPayment::STATUS_ALINDI)->count();

        $aktifPmt = $payments->firstWhere('is_active', true);
        $odemeGecikti = false;
        if ($aktifPmt && $aktifPmt->due_date && in_array($aktifPmt->status, [RequestPayment::STATUS_AKTIF, RequestPayment::STATUS_GECIKTI])) {
            $dueCutoff = $aktifPmt->due_date->copy()->startOfDay();
            if ($aktifPmt->due_time) {
                [$dh, $dm, $ds] = array_pad(explode(':', $aktifPmt->due_time), 3, '0');
                $dueCutoff->setTime((int) $dh, (int) $dm, (int) $ds);
            } else {
                $dueCutoff->endOfDay();
            }
            $odemeGecikti = $dueCutoff->isPast();
        }

        if ($odemeGecikti) {
            $odemeDurumu = self::ODEME_GECIKTI;
        } elseif ($bekleyenCount === 0 && $alinanCount > 0) {
            $odemeDurumu = self::ODEME_TAMAMLANDI;
        } elseif ($bekleyenCount > 0 && $alinanCount > 0) {
            $odemeDurumu = self::ODEME_KISMI;
        } elseif ($bekleyenCount > 0) {
            $odemeDurumu = self::ODEME_PLANLI;
        } else {
            $odemeDurumu = self::ODEME_YOK;
        }

        // — aktif_adim —
        $status = $this->status;

        if (in_array($status, ['biletlendi', 'iptal', 'olumsuz', 'iade'])) {
            $aktifAdim = self::ADIM_TAMAMLANDI;
        } elseif ($odemeDurumu === self::ODEME_TAMAMLANDI) {
            $aktifAdim = self::ADIM_BILETLEME_BEKLENIYOR;
        } elseif ($odemeDurumu === self::ODEME_GECIKTI) {
            $aktifAdim = self::ADIM_ODEME_GECIKTI;
        } elseif ($odemeDurumu === self::ODEME_KISMI) {
            // Aktif payment var mı? Varsa ödeme bekleniyor, yoksa plan bekleniyor
            $sonrakiAktif = $payments->firstWhere('is_active', true);
            $aktifAdim = $sonrakiAktif
                ? self::ADIM_ODEME_BEKLENIYOR
                : self::ADIM_ODEME_ALINDI_DEVAM;
        } elseif ($odemeDurumu === self::ODEME_PLANLI) {
            // is_active payment var mı?
            $aktifAdim = $aktifPmt ? self::ADIM_ODEME_BEKLENIYOR : self::ADIM_ODEME_PLANI_BEKLENIYOR;
        } elseif ($odemeDurumu === self::ODEME_YOK) {
            $kabulVar = $this->offers->firstWhere('durum', Offer::DURUM_KABUL);
            if ($kabulVar) {
                $aktifAdim = self::ADIM_ODEME_PLANI_BEKLENIYOR;
            } else {
                $offerVar = $this->offers->whereIn('durum', [Offer::DURUM_BEKLEMEDE, Offer::DURUM_KABUL])->count();
                $aktifAdim = $offerVar > 0
                    ? self::ADIM_KARAR_BEKLENIYOR
                    : self::ADIM_TEKLIF_BEKLENIYOR;
            }
        } else {
            $aktifAdim = self::ADIM_TEKLIF_BEKLENIYOR;
        }

        $this->update([
            'aktif_adim'    => $aktifAdim,
            'odeme_durumu'  => $odemeDurumu,
        ]);
    }

    public function notifications()
    {
        return $this->hasMany(RequestNotification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Sadece "depozitoda" statüsündeki talepler için "İadede" badge'i gösterilsin mi?
     * Şu koşullardan biri sağlanıyorsa true döner:
     *  1. Herhangi bir segment'in uçuş tarihi geçmişse
     *  2. Kabul edilmiş teklifin opsiyon tarihi 2026 öncesinde bitmişse
     *  3. Notes / group_company_name / flight_purpose / ai_analysis alanlarında "iade" geçiyorsa
     */
    public function isIadede(): bool
    {
        if ($this->status !== self::STATUS_DEPOZITODA) {
            return false;
        }

        // 1. Uçuş tarihi geçmiş mi?
        foreach ($this->segments as $segment) {
            if ($segment->departure_date && Carbon::parse($segment->departure_date)->isPast()) {
                return true;
            }
        }

        // 2. Opsiyon tarihi 2026 öncesinde bitmiş mi?
        $acceptedOffer = $this->offers->firstWhere('durum', Offer::DURUM_KABUL);
        if ($acceptedOffer && $acceptedOffer->option_date) {
            $optionDate = Carbon::parse($acceptedOffer->option_date);
            if ($optionDate->isPast() && $optionDate->year < 2026) {
                return true;
            }
        }

        // 3. Metin alanlarında "iade" geçiyor mu?
        $metin = implode(' ', array_filter([
            $this->notes,
            $this->group_company_name,
            $this->flight_purpose,
            $this->ai_analysis,
        ]));

        if (mb_stripos($metin, 'iade') !== false) {
            return true;
        }

        return false;
    }
}
