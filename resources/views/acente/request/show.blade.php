<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('acente.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $talep->gtpnr }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        html[data-theme="dark"] body { background: #1a1a2e !important; }

        /* Header */
        .talep-header { border-left: 5px solid #e94560; }
        .ozet-kutu { background: #f8f9fa; border-radius: 8px; padding: 10px 14px; text-align: center; }
        .ozet-kutu .etiket { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.8px; color: #6c757d; }
        .ozet-kutu .deger { font-size: 0.95rem; font-weight: 700; margin-top: 2px; }

        /* Talep bilgileri tablosu */
        .bilgi-tablo th { font-size: 0.78rem; color: #6c757d; font-weight: 600; width: 38%; padding: 7px 12px; border-color: #f0f2f5; }
        .bilgi-tablo td { font-size: 0.88rem; padding: 7px 12px; border-color: #f0f2f5; }
        .bilgi-tablo tr:last-child th,
        .bilgi-tablo tr:last-child td { border-bottom: 0; }

        /* Segment kartı */
        .seg-kart { background: linear-gradient(135deg, #1a1a2e, #0d3b7a); color: #fff; border-radius: 10px; padding: 14px 18px; }
        .seg-iata { font-size: 2rem; font-weight: 800; letter-spacing: 3px; }
        .seg-arrow { font-size: 1.4rem; opacity: 0.6; }

        /* Teklif kartları */
        .teklif-card { border: none; border-left: 4px solid #e94560; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 16px; }
        .teklif-card.kabul-edildi { border-left-color: #198754; }
        .teklif-card .fiyat-kutu { background: #f8fff8; border: 1px solid #d1e7dd; border-radius: 8px; padding: 12px; text-align: center; }
        .teklif-card .fiyat-kutu .fiyat { font-size: 1.6rem; font-weight: 800; color: #198754; }
        .teklif-card .fiyat-kutu .toplam { font-size: 0.85rem; color: #6c757d; }

        /* Kollaps ok */
        .collapse-toggle { cursor: pointer; user-select: none; }
        .collapse-toggle .chevron { transition: transform 0.2s; }
        .collapsed .chevron { transform: rotate(-90deg); }

        /* Map */
        #map { height: 220px; border-radius: 0 0 8px 8px; }

        /* Beklemede animasyonu */
        .beklemede-pulse { animation: pulse 2s infinite; }
        @@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }

        /* Vade alarm */
        @@keyframes blink-alarm { 0%,100%{opacity:1} 50%{opacity:0.3} }
        .alert-danger [id^="sayac-"] { animation: blink-alarm 1s infinite; }

        /* Opsiyon deadline bar */
        .opsiyon-bar { display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:6px;
                       border-left:3px solid;margin:8px 0;font-size:.85rem;flex-wrap:wrap; }
        .opsiyon-gecti    { background:rgba(220,53,69,.08);  border-color:#dc3545; color:#dc3545; }
        .opsiyon-kritik   { background:rgba(220,53,69,.1);   border-color:#dc3545; }
        .opsiyon-acil     { background:rgba(253,126,20,.1);  border-color:#fd7e14; }
        .opsiyon-dikkat   { background:rgba(255,193,7,.12);  border-color:#ffc107; }
        .opsiyon-normal   { background:rgba(13,110,253,.05); border-color:#0d6efd; }
        .opsiyon-belirsiz { background:rgba(108,117,125,.07);border-color:#6c757d; color:#6c757d; }
        .opsiyon-garanti-uyari { display:none; font-size:.78rem; color:#fd7e14; margin:-4px 0 6px 4px; }
        .opsiyon-garanti-uyari.show { display:block; }
        .opsiyon-kalan { font-size:.78rem; color:#6c757d; }
        @@keyframes pulse-border {
          0%,100% { box-shadow:0 0 0 0 rgba(220,53,69,.4); }
          50%     { box-shadow:0 0 0 6px rgba(220,53,69,0); }
        }
        .deadline-pulse { animation:pulse-border 2s infinite; }
    </style>
</head>
<body>

<x-navbar-acente active="show" />

@php
    // ── Temel hesaplamalar ──
    $statusMeta = \App\Models\Request::statusMeta($talep->status);
    $statusEtiket = $statusMeta['label'];
    $statusStyle = 'background:' . $statusMeta['bg'] . ';color:' . $statusMeta['text'] . ';';

    // Kabul edilen teklif varsa sadece onu göster, yoksa tüm beklemedeki ve fiyatlı teklifleri göster
    $kabulEdilenTeklif = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
    $gosterilecekTeklifler = $kabulEdilenTeklif
        ? $talep->offers->where('id', $kabulEdilenTeklif->id)
        : $talep->offers->whereIn('durum', [\App\Models\Offer::DURUM_BEKLEMEDE, \App\Models\Offer::DURUM_KABUL])->where('price_per_pax', '>', 0);
    $ilkTeklif = $kabulEdilenTeklif ?? $gosterilecekTeklifler->first();

    // Acentenin ilk talep notu (admin teklif metniyle aynıysa tekrar göstermeyelim)
    $yoneticiMesajlari = $talep->offers->pluck('offer_text')->filter(fn ($mesaj) => filled(trim((string) $mesaj)));
    $acenteNotu = filled(trim((string) $talep->notes)) ? trim((string) $talep->notes) : null;
    if ($acenteNotu && $yoneticiMesajlari->contains(fn ($mesaj) => trim((string) $mesaj) === $acenteNotu)) {
        $acenteNotu = null;
    }

    // Aktif adım ve ödeme durumu — tek kaynak, inference yok
    $aktifAdim    = $talep->aktif_adim ?? 'teklif_bekleniyor';
    $odemeDurumu  = $talep->odeme_durumu ?? 'yok';
    $aktifPayment = $talep->payments->firstWhere('is_active', true);

    // Header deadline kutusu — aktif_adim'a göre
    [$deadlineEtiket, $deadlineRenk, $deadlineIcerik] = match($aktifAdim) {
        'teklif_bekleniyor'       => ['Durum', 'secondary', 'Teklif bekleniyor'],
        'karar_bekleniyor'        => (function() use ($talep) {
            $opsiyonTeklif = $talep->offers
                ->where('durum', \App\Models\Offer::DURUM_BEKLEMEDE)
                ->filter(fn($o) => $o->option_date)
                ->sortBy('option_date')->first();
            if (!$opsiyonTeklif) {
                return ['Opsiyonda', 'secondary', 'Opsiyon süresi henüz belirlenmedi'];
            }
            $dl   = \Carbon\Carbon::parse($opsiyonTeklif->option_date
                    . ($opsiyonTeklif->option_time ? ' '.$opsiyonTeklif->option_time : ' 23:59:59'));
            $diff = now()->diffInMinutes($dl, false);
            if ($diff <= 0)    return ['⚠️ Opsiyon Süresi Doldu', 'danger',   $dl->format('d.m.Y H:i').' — Yeni fiyat talep edin'];
            if ($diff <= 60)   return ['🚨 '.ceil($diff).' Dakika Kaldı', 'danger',  'Bu süre geçerse fiyat ve koltuk garantisi kaybolur.'];
            if ($diff <= 360)  return ['⏰ '.floor($diff/60).' Saat Kaldı',  'warning', 'Opsiyon bitiş tarihi: '.$dl->format('d.m.Y H:i')];
            if ($diff <= 1440) return ['⚠️ '.floor($diff/60).' Saat Kaldı', 'warning', 'Bu süre geçerse fiyat ve koltuk garantisi kaybolur.'];
            return              ['📅 Son Ödeme / Opsiyon Tarihi', 'info', $dl->format('d.m.Y H:i').' tarihine kadar'];
        })(),
        'odeme_plani_bekleniyor'  => ['Durum', 'warning', 'Ödeme planı bekleniyor'],
        'odeme_bekleniyor'        => (function() use ($aktifPayment) {
            if (!$aktifPayment?->due_date) return ['Son Ödeme / Opsiyon Tarihi', 'warning', '—'];
            $dl   = $aktifPayment->due_date;
            $diff = now()->diffInMinutes($dl, false);
            if ($diff <= 0)    return ['⚠️ Ödeme Süresi Doldu', 'danger',   $dl->format('d.m.Y H:i').' — Lütfen operasyon ekibimizle iletişime geçin'];
            if ($diff <= 60)   return ['🚨 '.ceil($diff).' Dakika Kaldı', 'danger',  'Bu süre geçerse işlem geçersiz olur.'];
            if ($diff <= 360)  return ['⏰ '.floor($diff/60).' Saat Kaldı',  'warning', 'Son ödeme / opsiyon tarihi: '.$dl->format('d.m.Y H:i')];
            if ($diff <= 1440) return ['⚠️ '.floor($diff/60).' Saat Kaldı', 'warning', 'Bu süre geçerse işlem geçersiz olur.'];
            return              ['💳 Son Ödeme / Opsiyon Tarihi', 'warning', $dl->format('d.m.Y H:i').' tarihine kadar'];
        })(),
        'odeme_gecikti'           => (function() use ($aktifPayment) {
            $dl = $aktifPayment?->due_date;
            return ['⚠️ Ödeme GECİKTİ', 'danger',
                ($dl ? $dl->format('d.m.Y H:i').' tarihinde geçti — ' : '').'Lütfen operasyon ekibimizle iletişime geçin.'];
        })(),
        'odeme_alindi_devam'      => ['Sonraki Ödeme', 'info', 'Plan bekleniyor'],
        'biletleme_bekleniyor'    => ['Durum', 'success', 'Biletleme bekleniyor'],
        'tamamlandi'              => ['Durum', 'success', 'Tamamlandı'],
        default                   => ['Durum', 'secondary', '—'],
    };

    // Aktif ödeme (ilkBekleniyor yerine is_active pointer kullan)
    $ilkBekleniyor = $aktifPayment;

    // Header fiyat hesaplamaları
    if ($kabulEdilenTeklif) {
        $headerKisiEtiketi  = 'Kişi Başı';
        $headerKisiFiyat    = $kabulEdilenTeklif->price_per_pax > 0 ? $kabulEdilenTeklif->price_per_pax : null;
        $headerKisiCurrency = $kabulEdilenTeklif->currency;
        $headerToplamEtiket = 'Toplam';
        $headerToplam       = $kabulEdilenTeklif->total_price;
        $headerToplamCur    = $kabulEdilenTeklif->currency;
    } else {
        $fiyatliTeklifler   = $talep->offers->whereIn('durum', [\App\Models\Offer::DURUM_BEKLEMEDE, \App\Models\Offer::DURUM_KABUL])->where('price_per_pax', '>', 0);
        $minFiyatTeklif     = $fiyatliTeklifler->sortBy('price_per_pax')->first();
        $headerKisiEtiketi  = 'En düşük fiyat';
        $headerKisiFiyat    = $minFiyatTeklif?->price_per_pax;
        $headerKisiCurrency = $minFiyatTeklif?->currency;
        $headerToplamEtiket = 'Toplam (min)';
        $headerToplam       = $minFiyatTeklif?->total_price;
        $headerToplamCur    = $minFiyatTeklif?->currency;
    }

    // Havayolu logo map
    $airlineIata = [
        'turkish airlines'=>'TK','thy'=>'TK','tk'=>'TK',
        'pegasus'=>'PC','pc'=>'PC',
        'sunexpress'=>'XQ','sun express'=>'XQ','xq'=>'XQ',
        'ajet'=>'VF','vf'=>'VF',
        'freebird'=>'FH','fh'=>'FH',
        'corendon'=>'CAI','corendon airlines'=>'CAI',
        'wizz'=>'W6','wizz air'=>'W6','w6'=>'W6',
        'ryanair'=>'FR','fr'=>'FR',
        'easyjet'=>'U2','u2'=>'U2',
        'lufthansa'=>'LH','lh'=>'LH',
        'emirates'=>'EK','ek'=>'EK',
        'qatar'=>'QR','qatar airways'=>'QR','qr'=>'QR',
        'flydubai'=>'FZ','fz'=>'FZ',
        'atlas'=>'KK','atlasjet'=>'KK','atlasglobal'=>'KK',
    ];

    // Gidiş segmenti
    $segs     = $talep->segments->sortBy('order');
    $ilkSeg   = $segs->first();
    $sonSeg   = $segs->last();
    $tripType = \App\Models\Request::tripTypeLabel($talep->trip_type);
    $airlineLogoService = app(\App\Services\AirlineLogoService::class);
@endphp

<div class="container-fluid px-3 py-3" style="max-width:1200px;">

    @if(in_array(auth()->user()->role, ['admin','superadmin']))
    <div class="alert alert-warning py-2 px-3 mb-3 d-flex flex-wrap align-items-center gap-2" style="border-left:4px solid #ffc107;">
        <i class="fas fa-eye me-1"></i>
        <div class="small">
            <strong>Admin Önizleme Modu</strong>
            <span class="text-muted">— Acentenin gördüğünü görüyorsunuz. Butonlar işlev yapmaz.</span>
        </div>
        <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-dark ms-md-auto w-100 w-md-auto mt-1 mt-md-0">
            <i class="fas fa-arrow-left me-1"></i>Admin Sayfasına Dön
        </a>
    </div>
    @endif

    {{-- ═══════════════════════════════════════
         1. HEADER — GTPNR + DURUM + ÖZET BAR
         ═══════════════════════════════════════ --}}
    <div class="card shadow-sm talep-header mb-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-3">
                    @if(in_array(auth()->user()->role, ['admin','superadmin']))
                        <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @else
                        <a href="{{ route('acente.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    @endif
                    <div>
                        <h4 class="fw-bold mb-0 font-monospace" style="color:#1a1a2e;">{{ $talep->gtpnr }}</h4>
                        <small class="text-muted">
                            {{ $talep->agency_name }}
                            &nbsp;·&nbsp; {{ $talep->created_at->format('d.m.Y H:i') }}
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge fs-6 px-3 py-2" style="{{ $statusStyle }}">{{ $statusEtiket }}</span>
                    <x-iade-badge :talep="$talep" :showForAcente="true" />
                </div>
            </div>

            {{-- ÖZET BAR --}}
            <div class="row g-2">
                <div class="col-6 col-md">
                    <div class="ozet-kutu">
                        <div class="etiket">Rota</div>
                        <div class="deger">
                            {{ $ilkSeg?->from_iata }}
                            <i class="fas fa-arrow-right text-danger" style="font-size:0.6rem;"></i>
                            {{ $sonSeg?->to_iata }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="ozet-kutu">
                        <div class="etiket">Gidiş</div>
                        <div class="deger">
                            {{ $ilkSeg?->departure_date ? \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d M Y') : '—' }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="ozet-kutu">
                        <div class="etiket">PAX</div>
                        <div class="deger text-primary">{{ $talep->pax_total }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="ozet-kutu">
                        <div class="etiket">{{ $headerKisiEtiketi }}</div>
                        <div class="deger text-success">
                            @if($headerKisiFiyat > 0)
                                {{ number_format($headerKisiFiyat, 0) }} {{ $headerKisiCurrency }}
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="ozet-kutu">
                        <div class="etiket">{{ $headerToplamEtiket }}</div>
                        <div class="deger text-primary">
                            @if($headerToplam > 0)
                                {{ number_format($headerToplam, 0) }} {{ $headerToplamCur }}
                            @else <span class="text-muted">—</span> @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md">
                    <div class="ozet-kutu bg-{{ $deadlineRenk }} bg-opacity-10 {{ $deadlineRenk === 'danger' ? 'deadline-pulse' : '' }}" style="border:1px solid;">
                        <div class="etiket">{{ $deadlineEtiket }}</div>
                        <div class="deger text-{{ $deadlineRenk }}">{{ $deadlineIcerik }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ═══════════════════════════════════
             SOL KOLON
             ═══════════════════════════════════ --}}
        <div class="col-12 col-lg-7">

            {{-- ── TALEPBİLGİLERİ ── --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold py-2" style="background:#1a1a2e; color:#fff;">
                    <i class="fas fa-clipboard-list me-2" style="color:#e94560;"></i>Talep Bilgileri
                </div>
                <div class="card-body p-0">
                    <table class="table bilgi-tablo mb-0">
                        <tbody>
                        {{-- Acente & İletişim --}}
                        <tr>
                            <th>Acente</th>
                            <td class="fw-bold">{{ $talep->agency_name }}</td>
                        </tr>
                        <tr>
                            <th>Telefon</th>
                            <td>
                                <a href="tel:{{ $talep->phone }}" class="text-decoration-none">
                                    {{ $talep->phone }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>E-posta</th>
                            <td>{{ $talep->email }}</td>
                        </tr>
                        @if($talep->group_company_name)
                        <tr>
                            <th>Grup / Firma</th>
                            <td class="fw-bold">{{ $talep->group_company_name }}</td>
                        </tr>
                        @endif
                        @if($talep->flight_purpose)
                        <tr>
                            <th>Uçuş Amacı</th>
                            <td>{{ $talep->flight_purpose }}</td>
                        </tr>
                        @endif
                        {{-- PAX --}}
                        <tr>
                            <th>Yolcu Sayısı</th>
                            <td>
                                <span class="fw-bold fs-5 text-primary">{{ $talep->pax_total }}</span> kişi
                                @php
                                    $paxDetay = [];
                                    if ($talep->pax_adult > 0) $paxDetay[] = $talep->pax_adult . ' Yetişkin';
                                    if ($talep->pax_child > 0) $paxDetay[] = $talep->pax_child . ' Çocuk';
                                    if ($talep->pax_infant > 0) $paxDetay[] = $talep->pax_infant . ' Bebek';
                                @endphp
                                @if(count($paxDetay))
                                    <span class="text-muted ms-1" style="font-size:0.82rem;">({{ implode(', ', $paxDetay) }})</span>
                                @endif
                            </td>
                        </tr>
                        {{-- Uçuş türü --}}
                        <tr>
                            <th>Uçuş Türü</th>
                            <td>{{ $tripType }}</td>
                        </tr>
                        {{-- Segmentler --}}
                        @foreach($segs as $i => $seg)
                        <tr style="background:#f8faff;">
                            <th>
                                @if($segs->count() === 1)
                                    <i class="fas fa-plane-departure text-primary me-1"></i>Parkur
                                @elseif($i === 0)
                                    <i class="fas fa-plane-departure text-primary me-1"></i>Gidiş Parkuru
                                @elseif($i === $segs->count()-1)
                                    <i class="fas fa-plane-arrival text-success me-1"></i>Dönüş Parkuru
                                @else
                                    <i class="fas fa-plane text-warning me-1"></i>{{ $i+1 }}. Segment
                                @endif
                            </th>
                            <td>
                                <span class="fw-bold font-monospace fs-6">{{ $seg->from_iata }}</span>
                                <i class="fas fa-long-arrow-alt-right text-danger mx-2"></i>
                                <span class="fw-bold font-monospace fs-6">{{ $seg->to_iata }}</span>
                                @if($seg->from_city || $seg->to_city)
                                    <div class="text-muted" style="font-size:0.78rem;">
                                        {{ $seg->from_city }} → {{ $seg->to_city }}
                                    </div>
                                @endif
                                <div class="mt-1">
                                    <i class="fas fa-calendar-alt text-muted me-1" style="font-size:0.75rem;"></i>
                                    <span style="font-size:0.88rem;">
                                        {{ $seg->departure_date ? \Carbon\Carbon::parse($seg->departure_date)->format('d.m.Y (D)') : '—' }}
                                    </span>
                                    @if($seg->departure_time_slot)
                                        @php $slotLbl = ['sabah'=>'🌅 Sabah','ogle'=>'☀️ Öğle','aksam'=>'🌆 Akşam','esnek'=>'🔄 Esnek'][$seg->departure_time_slot] ?? $seg->departure_time_slot; @endphp
                                        <span class="badge bg-info text-dark ms-2" style="font-size:0.72rem;">{{ $slotLbl }}</span>
                                    @elseif($seg->departure_time)
                                        <span class="text-muted ms-2" style="font-size:0.82rem;">
                                            <i class="fas fa-clock me-1"></i>{{ substr($seg->departure_time, 0, 5) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        {{-- Tercihler --}}
                        @if($talep->preferred_airline)
                        <tr>
                            <th>Tercih Havayolu</th>
                            <td>{{ $talep->preferred_airline }}</td>
                        </tr>
                        @endif
                        @if($talep->hotel_needed || $talep->visa_needed)
                        <tr>
                            <th>Ek Hizmetler</th>
                            <td>
                                @if($talep->hotel_needed)
                                    <span class="badge bg-info text-dark me-1"><i class="fas fa-hotel me-1"></i>Otel</span>
                                @endif
                                @if($talep->visa_needed)
                                    <span class="badge bg-warning text-dark"><i class="fas fa-passport me-1"></i>Vize</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($acenteNotu)
                        <tr>
                            <th><i class="fas fa-sticky-note text-warning me-1"></i>Acente Notu</th>
                            <td style="white-space:pre-line;">{{ $acenteNotu }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Talep Tarihi</th>
                            <td class="text-muted">
                                {{ $talep->created_at->format('d.m.Y H:i') }}
                                <span class="ms-2 text-muted" style="font-size:0.78rem;">
                                    ({{ $talep->created_at->diffForHumans() }})
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── TEKLİFLER ── --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold py-2 d-flex justify-content-between align-items-center"
                     style="background:#1a1a2e; color:#fff;">
                    <div>
                        <i class="fas fa-tag me-2" style="color:#ffc107;"></i>Teklifler
                        @if($gosterilecekTeklifler->count() > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $gosterilecekTeklifler->count() }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body {{ $gosterilecekTeklifler->count() === 0 ? 'py-5 text-center' : 'pt-3 pb-2' }}">

                @forelse($gosterilecekTeklifler as $teklif)
                @php
                    $tklLogo = $airlineLogoService->resolve($teklif->airline);
                @endphp
                <div class="teklif-card card p-0 {{ $teklif->durum === \App\Models\Offer::DURUM_KABUL ? 'kabul-edildi' : '' }}">
                    <div class="card-body p-3">

                        {{-- Havayolu + kabul rozeti --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($tklLogo['has_logo'])
                                    <img src="{{ $tklLogo['path'] }}" alt="{{ $tklLogo['display_name'] }}"
                                         style="width:48px;height:48px;object-fit:contain;flex-shrink:0;"
                                         onerror="this.style.display='none';">
                                @endif
                                <span class="fw-bold fs-5 lh-sm">{{ $teklif->airline ?? '-' }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary">{{ $teklif->currency }}</span>
                                @if($teklif->durum === \App\Models\Offer::DURUM_KABUL)
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Kabul Edildi</span>
                                @endif
                            </div>
                        </div>

                        {{-- Opsiyon deadline bar (beklemede ve kabul_edildi teklifler için) --}}
                        @if(in_array($teklif->durum, [\App\Models\Offer::DURUM_BEKLEMEDE, \App\Models\Offer::DURUM_KABUL]) && $teklif->option_date)
                        @php
                            $hasDl  = (bool) $teklif->option_date;
                            $dlTs   = $hasDl ? \Carbon\Carbon::parse($teklif->option_date.($teklif->option_time ? ' '.$teklif->option_time : ' 23:59:59')) : null;
                            $dlDiff = $dlTs ? now()->diffInMinutes($dlTs, false) : null;
                            $dlClass = !$hasDl ? 'belirsiz' : ($dlDiff <= 0 ? 'gecti' : ($dlDiff <= 60 ? 'kritik' : ($dlDiff <= 360 ? 'acil' : ($dlDiff <= 1440 ? 'dikkat' : 'normal'))));
                        @endphp
                        <div class="opsiyon-bar opsiyon-{{ $dlClass }}"
                             @if($dlTs) data-deadline="{{ $dlTs->toISOString() }}" data-teklif-id="{{ $teklif->id }}" @endif>
                            @if(!$hasDl)
                                <span>📋 Opsiyon süresi henüz belirlenmedi</span>
                            @elseif($dlDiff <= 0)
                                <span>⚠️ Opsiyon süresi doldu — {{ $dlTs->format('d.m.Y H:i') }}</span>
                            @else
                                <span>{{ $dlClass === 'kritik' ? '🚨' : ($dlClass === 'acil' ? '⏰' : ($dlClass === 'dikkat' ? '⚠️' : '📅')) }}
                                Opsiyon bitiş tarihi: <strong>{{ $dlTs->format('d.m.Y H:i') }}</strong></span>
                                <span class="opsiyon-kalan ms-auto" id="opsiyon-kalan-{{ $teklif->id }}"></span>
                            @endif
                        </div>
                        <div class="opsiyon-garanti-uyari @if(in_array($dlClass, ['kritik','acil','dikkat'])) show @endif">
                            Bu süre geçerse fiyat ve koltuk garantisi kaybolur.
                        </div>
                        @endif

                        {{-- Uçuş detayları (PNR, sefer no, saat, bagaj) --}}
                        @if($teklif->airline_pnr || $teklif->flight_number || $teklif->flight_departure_time || $teklif->baggage_kg || $teklif->pax_confirmed)
                        <div class="rounded p-2 mb-3" style="background:#f0f4ff;border:1px solid #c5d3f0;">
                            <div class="row g-2 text-center">
                                @if($teklif->airline_pnr)
                                <div class="col-6 col-md-3">
                                    <div class="small text-muted">PNR</div>
                                    <div class="fw-bold font-monospace text-primary">{{ $teklif->airline_pnr }}</div>
                                </div>
                                @endif
                                @if($teklif->flight_number)
                                <div class="col-6 col-md-3">
                                    <div class="small text-muted">Sefer</div>
                                    <div class="fw-bold">{{ $teklif->flight_number }}</div>
                                </div>
                                @endif
                                @if($teklif->flight_departure_time || $teklif->flight_arrival_time)
                                <div class="col-6 col-md-3">
                                    <div class="small text-muted">Saat</div>
                                    <div class="fw-bold">{{ $teklif->flight_departure_time ? substr($teklif->flight_departure_time,0,5) : '--' }} → {{ $teklif->flight_arrival_time ? substr($teklif->flight_arrival_time,0,5) : '--' }}</div>
                                </div>
                                @endif
                                @if($teklif->baggage_kg)
                                <div class="col-6 col-md-3">
                                    <div class="small text-muted">Bagaj</div>
                                    <div class="fw-bold">🧳 {{ $teklif->baggage_kg }} KG</div>
                                </div>
                                @endif
                                @if($teklif->pax_confirmed)
                                <div class="col-6 col-md-3">
                                    <div class="small text-muted">PAX</div>
                                    <div class="fw-bold">👥 {{ $teklif->pax_confirmed }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Fiyat + toplam --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="fiyat-kutu">
                                    <div class="small text-muted">Kişi Başı</div>
                                    <div class="fiyat">{{ number_format($teklif->price_per_pax, 0) }}</div>
                                    <div class="toplam">{{ $teklif->currency }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light border rounded p-2 text-center">
                                    <div class="small text-muted">Toplam ({{ $talep->pax_total }} kişi)</div>
                                    <div class="fw-bold fs-5">{{ number_format($teklif->total_price, 0) }}</div>
                                    <div class="small text-muted">{{ $teklif->currency }}</div>
                                </div>
                            </div>
                            @if($teklif->deposit_amount)
                            <div class="col-12">
                                <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded p-2 text-center">
                                    <div class="small text-muted">Depozito (%{{ $teklif->deposit_rate }})</div>
                                    <div class="fw-bold text-warning">{{ number_format($teklif->deposit_amount, 0) }} {{ $teklif->currency }}</div>
                                </div>
                            </div>
                            @endif
                        </div>


                        {{-- Adminin acenteye notu --}}
                        @if($teklif->offer_text)
                        <div class="mb-3 p-2 rounded border-start border-3 border-info" style="background:rgba(13,202,240,0.07);white-space:pre-line;font-size:0.82rem;">
                            <div class="fw-semibold mb-1" style="font-size:0.72rem;color:#0dcaf0;">📨 YÖNETİCİ MESAJI</div>
                            {{ $teklif->offer_text }}
                        </div>
                        @endif

                        @if($teklif->created_by)
                        <div class="text-muted mb-3" style="font-size:0.78rem;">
                            <i class="fas fa-user-tie me-1"></i>Hazırlayan: <strong>{{ $teklif->created_by }}</strong>
                        </div>
                        @endif

                        {{-- Butonlar --}}
                        <div class="d-grid d-md-flex gap-2">
                            @if($teklif->durum === \App\Models\Offer::DURUM_BEKLEMEDE && $aktifAdim === 'karar_bekleniyor')
                            @php
                                $btnMetin    = 'Kabul Et';
                                $btnDisabled = false;
                                $btnDlStr    = '—';
                                if ($teklif->option_date) {
                                    $btnDl    = \Carbon\Carbon::parse($teklif->option_date.($teklif->option_time ? ' '.$teklif->option_time : ' 23:59:59'));
                                    $btnDiff  = now()->diffInMinutes($btnDl, false);
                                    $btnDlStr = $btnDl->format('d.m.Y H:i');
                                    if ($btnDiff <= 0)   { $btnDisabled = true; $btnMetin = 'Opsiyon Süresi Doldu'; }
                                    elseif ($btnDiff <= 60)  { $btnMetin = '🚨 Hemen Kabul Et'; }
                                    elseif ($btnDiff <= 360) { $btnMetin = '⚡ Kabul Et — Süre Kısıtlı'; }
                                }
                            @endphp
                            <button type="button" class="btn btn-success btn-sm w-100 w-md-auto flex-md-fill"
                                @if($btnDisabled) disabled title="Opsiyon süresi doldu, yeni fiyat talep edin" @endif
                                onclick="kabulOnayGoster(
                                    {{ $teklif->id }},
                                    '{{ addslashes($teklif->airline ?? '—') }}',
                                    '{{ number_format($teklif->price_per_pax,0) }} {{ $teklif->currency }}',
                                    '{{ number_format($teklif->total_price,0) }} {{ $teklif->currency }}',
                                    '{{ $btnDlStr }}'
                                )">
                                <i class="fas fa-check me-1"></i>{{ $btnMetin }}
                            </button>
                            @if($btnDisabled)
                            <div class="text-danger small mt-1 w-100">Opsiyon süresi doldu, yeni fiyat talep edin.</div>
                            @endif
                            @elseif($teklif->durum === \App\Models\Offer::DURUM_KABUL)
                            <span class="btn btn-success btn-sm w-100 w-md-auto flex-md-fill disabled">
                                <i class="fas fa-check-circle me-1"></i>Kabul Edildi
                            </span>
                            @else
                            <span class="btn btn-outline-secondary btn-sm w-100 w-md-auto flex-md-fill disabled">
                                <i class="fas fa-clock me-1"></i>İşlemde
                            </span>
                            @endif
                            <a href="https://wa.me/905354154799?text={{ urlencode($talep->gtpnr . ' - ' . ($teklif->airline ?? '') . ' teklifi hakkında sorum var') }}"
                               target="_blank" class="btn btn-outline-secondary btn-sm w-100 w-md-auto flex-md-fill">
                                <i class="fab fa-whatsapp me-1"></i>Sor
                            </a>
                        </div>

                    </div>
                </div>
                @empty
                {{-- Teklif yok --}}
                <div class="text-muted">
                    <div class="beklemede-pulse mb-3">
                        <i class="fas fa-hourglass-half fa-3x opacity-25"></i>
                    </div>
                    <p class="fw-bold mb-1">Teklif hazırlanıyor...</p>
                    <small>Operasyon ekibimiz en kısa sürede dönecektir.</small>
                    <div class="mt-3">
                        <a href="https://wa.me/905354154799?text={{ urlencode($talep->gtpnr . ' numaralı talebim için bilgi almak istiyorum') }}"
                           target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i>Durumu Sor
                        </a>
                    </div>
                </div>
                @endforelse
                </div>
            </div>

        </div>{{-- /SOL KOLON --}}

        {{-- ═══════════════════════════════════
             SAĞ KOLON
             ═══════════════════════════════════ --}}
        <div class="col-12 col-lg-5">

            {{-- ── MUHASEBE ── --}}
            @if($talep->offers->count() > 0)
            @php
                $muhTeklif = $ilkTeklif;
                $toplamTutar  = $muhTeklif->total_price ?? 0;
                $muhCurrency  = $muhTeklif->currency ?? '';
                $toplamOdenen = $talep->payments->where('status','alindi')->sum('amount');
                $kalanTutar   = max(0, $toplamTutar - $toplamOdenen);
                $yuzde        = $toplamTutar > 0 ? min(100, round(($toplamOdenen/$toplamTutar)*100)) : 0;
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold py-2">
                    <i class="fas fa-wallet me-2 text-success"></i>Ödeme Durumu
                </div>
                <div class="card-body">
                    @if($talep->status === 'depozitoda' && ($kabulEdilenTeklif || $aktifPayment))
                    <div class="alert alert-info alert-dismissible fade show py-2 small mb-3" role="alert">
                        <i class="fas fa-info-circle me-1"></i>
                        @if($aktifAdim === 'odeme_bekleniyor' && $aktifPayment?->due_date)
                        @php $alertDiff = now()->diffInMinutes($aktifPayment->due_date, false); @endphp
                            <strong>{{ number_format($aktifPayment->amount, 0) }} {{ $aktifPayment->currency }}</strong>
                            ödemesinin son tarihi: <strong>{{ $aktifPayment->due_date->format('d.m.Y H:i') }}</strong>.
                            @if($alertDiff > 0)
                                —
                                @if($alertDiff <= 1440)
                                    <strong class="text-danger">{{ floor($alertDiff/60) > 0 ? floor($alertDiff/60).' saat ' : '' }}{{ $alertDiff % 60 }} dakika kaldı.</strong>
                                @else
                                    <strong>{{ floor($alertDiff/1440) }} gün kaldı.</strong>
                                @endif
                            @else
                                — <strong class="text-danger">Süre doldu!</strong>
                            @endif
                            Bu süre geçerse işlem geçersiz olur.
                        @elseif($aktifAdim === 'odeme_gecikti')
                            <strong>Ödeme gecikti!</strong> Lütfen operasyon ekibimizle iletişime geçin.
                        @elseif($aktifAdim === 'odeme_plani_bekleniyor')
                            <strong>Teklifiniz onaylandı.</strong> Ödeme planınız hazırlanıyor — yakında bildirim alacaksınız.
                        @elseif($aktifAdim === 'odeme_alindi_devam')
                            Depozitonuz alındı, teşekkürler. Kalan tutar için ödeme planı hazırlanıyor.
                        @else
                            <strong>İşleminiz devam ediyor.</strong>
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if($kabulEdilenTeklif || $talep->payments->count() > 0)
                    {{-- Özet satırları --}}
                    <div class="d-flex justify-content-between flex-wrap gap-1 small mb-1">
                        <span class="text-muted">Tahsilat</span>
                        <span class="fw-bold">%{{ $yuzde }}</span>
                    </div>
                    <div class="progress mb-3" style="height:10px;border-radius:6px;">
                        <div class="progress-bar {{ $yuzde >= 100 ? 'bg-success' : ($yuzde >= 50 ? 'bg-primary' : 'bg-warning') }}"
                             style="width:{{ $yuzde }}%;border-radius:6px;"></div>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap gap-1 py-2 border-bottom">
                        <span class="small">Toplam Tutar</span>
                        <strong>{{ number_format($toplamTutar,0) }} {{ $muhCurrency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap gap-1 py-2 border-bottom text-success">
                        <span class="small">Ödenen</span>
                        <strong>{{ number_format($toplamOdenen,0) }} {{ $muhCurrency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap gap-1 py-2 {{ $kalanTutar > 0 ? 'text-danger' : 'text-success' }}">
                        <span class="small">Kalan</span>
                        <strong>{{ number_format($kalanTutar,0) }} {{ $muhCurrency }}</strong>
                    </div>

                    {{-- KK ile ödeme — sadece admin açtıysa görünür --}}
                    @if($kalanTutar > 0 && $kabulEdilenTeklif?->kk_enabled)
                        <form method="POST" action="{{ route('acente.requests.gateway-payment.start', $talep->gtpnr) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-credit-card me-1"></i>Kredi Kartı ile Öde
                            </button>
                            <div class="small text-muted mt-1 text-center">
                                Tutar: {{ number_format($kalanTutar, 2, ',', '.') }} {{ $muhCurrency }}
                            </div>
                        </form>
                    @elseif($kalanTutar > 0)
                        <div class="alert alert-light border mt-2 py-2 small">
                            <i class="fas fa-university me-1 text-primary"></i>
                            Ödemenizi <strong>EFT / Havale</strong> ile yapabilirsiniz. Operasyon ekibimiz ödemenizi aldığında sisteme işleyecektir.
                        </div>
                    @endif

                    {{-- Ödeme planı tablosu --}}
                    @if($talep->payments->count() > 0)
                    <hr class="my-2">
                    <div class="small fw-semibold mb-1 text-muted">Ödeme Planı</div>
                    @php
                        $siraliOdemeler_a    = $talep->payments->sortBy(fn($p) => [$p->sequence, $p->id])->values();
                        $toplamOdemeSayisi_a = $siraliOdemeler_a->count();
                        $kumuBeklenen_a      = 0;
                    @endphp
                    @foreach($siraliOdemeler_a as $siraNo_a => $odeme)
                    @php
                        $pos_a = $siraNo_a + 1;
                        $odemeLabel = $pos_a === 3 ? '3. Bakiye Ödemesi' : $pos_a . '. Depozito';
                        $kumuBeklenen_a += $odeme->amount;
                        $satırKalan_a = $toplamTutar > 0 ? max(0, $toplamTutar - $kumuBeklenen_a) : null;
                        $buOdemeBekliyor = $odeme->is_active;
                        $gosterecedekTarih = null;
                        $tarihEtiket = '';
                        if ($odeme->status === 'alindi' && $odeme->payment_date) {
                            $gosterecedekTarih = $odeme->payment_date->format('d.m.Y');
                            $tarihEtiket = 'Ödendi';
                        } elseif ($odeme->due_date) {
                            $gosterecedekTarih = $odeme->due_date->format('d.m.Y');
                            $tarihEtiket = 'Son tarih';
                        }
                    @endphp
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small
                        {{ $odeme->status==='alindi' ? 'bg-success bg-opacity-10' : ($odeme->status==='iade' ? 'bg-danger bg-opacity-10' : ($odeme->status==='gecikti' ? 'bg-danger bg-opacity-10' : ($buOdemeBekliyor ? 'bg-warning bg-opacity-10 border-warning' : ''))) }}"
                        style="border-radius:4px;padding:4px 6px;{{ $buOdemeBekliyor ? 'border-left:3px solid #ffc107;' : '' }}">
                        <div>
                            <span class="fw-bold">{{ $odemeLabel }}</span>
                            @if($buOdemeBekliyor)
                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">← Bu ödeme sizi bekliyor</span>
                            @endif
                            @if($gosterecedekTarih)
                            <div class="text-muted" style="font-size:0.72rem;">
                                {{ $tarihEtiket }}: {{ $gosterecedekTarih }}
                                @if($odeme->status === 'gecikti')
                                    <span class="text-danger fw-bold ms-1">⚠ Geçti!</span>
                                @endif
                            </div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="{{ $odeme->status==='alindi' ? 'text-success fw-bold' : ($odeme->status==='iade' ? 'text-danger fw-bold' : ($odeme->status==='gecikti' ? 'text-danger fw-bold' : 'text-warning fw-bold')) }}">
                                {{ number_format($odeme->amount,0) }} {{ $odeme->currency }}
                            </div>
                            @if(in_array($odeme->status,['aktif','taslak']))
                                <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Bekliyor</span>
                            @elseif($odeme->status==='gecikti')
                                <span class="badge bg-danger" style="font-size:0.6rem;">⚠ Gecikti</span>
                            @elseif($odeme->status==='iade')
                                <span class="badge bg-danger" style="font-size:0.6rem;">İade</span>
                            @else
                                <span class="badge bg-success" style="font-size:0.6rem;">✓ Alındı</span>
                            @endif
                            @if($satırKalan_a !== null)
                            <div style="font-size:0.65rem;color:#888;margin-top:2px;">
                                Kalan: <strong class="{{ $satırKalan_a == 0 ? 'text-success' : '' }}">{{ number_format($satırKalan_a,0) }} {{ $odeme->currency }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                    @if($buOdemeBekliyor && $odeme->due_date)
                    @php
                        $payDiff  = now()->diffInMinutes($odeme->due_date, false);
                        $payClass = $payDiff <= 0 ? 'gecti' : ($payDiff <= 60 ? 'kritik' : ($payDiff <= 360 ? 'acil' : ($payDiff <= 1440 ? 'dikkat' : 'normal')));
                    @endphp
                    <div class="opsiyon-bar opsiyon-{{ $payClass }} mt-1"
                         data-payment-deadline="{{ $odeme->due_date->toISOString() }}"
                         data-payment-id="{{ $odeme->id }}">
                        @if($payDiff <= 0)
                            <span>⚠️ Ödeme süresi doldu — {{ $odeme->due_date->format('d.m.Y H:i') }}</span>
                        @else
                            <span>{{ $payClass === 'kritik' ? '🚨' : ($payClass === 'acil' ? '⏰' : ($payClass === 'dikkat' ? '⚠️' : '💳')) }}
                            Son ödeme / opsiyon tarihi: <strong>{{ $odeme->due_date->format('d.m.Y H:i') }}</strong></span>
                            <span class="opsiyon-kalan ms-auto" id="payment-kalan-{{ $odeme->id }}"></span>
                        @endif
                    </div>
                    <div class="opsiyon-garanti-uyari @if(in_array($payClass, ['kritik','acil','dikkat'])) show @endif">
                        Bu süre geçerse işlem geçersiz olur.
                    </div>
                    @endif
                    @endforeach
                    @endif

                    @else
                    <hr class="my-2">
                    <div class="text-center text-muted small py-2">
                        @if(in_array($aktifAdim, ['odeme_plani_bekleniyor', 'biletleme_bekleniyor', 'tamamlandi']))
                            <i class="fas fa-hourglass-half me-1 text-warning"></i>
                            Ödeme planınız hazırlanıyor, yakında bildirim alacaksınız.
                        @else
                            <i class="fas fa-lock me-1"></i>
                            Teklif kabul ettiğinizde ödeme planı aktif olacak.
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── İLETİŞİM ── --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body d-grid gap-2">
                    <a href="https://wa.me/{{ $_adminTelefon }}?text={{ urlencode($talep->gtpnr . ' numaralı talep hakkında bilgi almak istiyorum') }}"
                       target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp ile Yaz
                    </a>
                    <a href="tel:+{{ $_adminTelefon }}" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-2"></i>Ara — {{ preg_replace('/^90/', '0', $_adminTelefon) }}
                    </a>
                </div>
            </div>

            {{-- ── ROTA HARİTASI (collapsible, açık) ── --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center collapse-toggle"
                     data-bs-toggle="collapse" data-bs-target="#harita-collapse" aria-expanded="true">
                    <span class="fw-semibold"><i class="fas fa-map-marked-alt me-2 text-success"></i>Rota Haritası</span>
                    <i class="fas fa-chevron-down chevron text-muted" style="font-size:0.75rem;" id="harita-chevron"></i>
                </div>
                <div class="collapse show" id="harita-collapse">
                    <div id="map"></div>
                </div>
            </div>

            {{-- ── OPERASYON ZAMAN ÇİZELGESİ (collapsible, kapalı) ── --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center collapse-toggle collapsed"
                     data-bs-toggle="collapse" data-bs-target="#timeline-collapse" aria-expanded="false">
                    <span class="fw-semibold"><i class="fas fa-history me-2 text-secondary"></i>Geçmiş</span>
                    <i class="fas fa-chevron-down chevron text-muted" style="font-size:0.75rem;"></i>
                </div>
                <div class="collapse" id="timeline-collapse">
                    <div class="card-body py-2">
                        <div class="timeline" style="padding-left:24px;position:relative;">
                            <div style="position:absolute;left:8px;top:0;bottom:0;width:2px;background:#dee2e6;"></div>
                            <div class="mb-3" style="position:relative;">
                                <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:#e94560;border:2px solid #fff;box-shadow:0 0 0 2px #e94560;"></div>
                                <div class="fw-bold small">Talep Oluşturuldu</div>
                                <small class="text-muted">{{ $talep->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                            @foreach($talep->logs as $log)
                            <div class="mb-3" style="position:relative;">
                                <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:#e94560;border:2px solid #fff;box-shadow:0 0 0 2px #e94560;"></div>
                                <div class="fw-bold small">{{ $log->description }}</div>
                                <small class="text-muted">
                                    {{ $log->created_at->format('d.m.Y H:i') }}
                                    @if($log->user) · {{ $log->user->name }} @endif
                                </small>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /SAĞ KOLON --}}
    </div>
</div>

{{-- KABUL ONAY MODALI --}}
<div class="modal fade" id="kabulOnayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fas fa-check-circle me-2"></i>Teklifi Kabul Et
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Aşağıdaki teklifi kabul etmek üzeresiniz:</p>
                <div class="bg-light rounded p-3 mb-3">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="small text-muted">Havayolu</div>
                            <div class="fw-bold" id="k-airline"></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Kişi Başı</div>
                            <div class="fw-bold text-success" id="k-price"></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Toplam</div>
                            <div class="fw-bold" id="k-total"></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-danger fw-semibold">Opsiyon Tarihi</div>
                            <div class="fw-bold" id="k-option"></div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Teklif kabul edildikten sonra bu sayfadan ödeme yapabilirsiniz.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Vazgeç
                </button>
                <form id="kabul-form" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check me-1"></i>Evet, Kabul Ediyorum
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Kabul Modal ──
const kabulModal = new bootstrap.Modal(document.getElementById('kabulOnayModal'));
const KABUL_BASE = '{{ url("acente/talep/" . $talep->gtpnr . "/teklif") }}';
function kabulOnayGoster(id, airline, price, total, option) {
    document.getElementById('k-airline').textContent = airline;
    document.getElementById('k-price').textContent   = price;
    document.getElementById('k-total').textContent   = total;
    document.getElementById('k-option').textContent  = option;
    document.getElementById('kabul-form').action     = KABUL_BASE + '/' + id + '/kabul';
    kabulModal.show();
}

// ── Harita (Google Maps) ──
@php
$segmentData = $talep->segments->map(fn($s) => [
    'from'     => $s->from_iata,
    'to'       => $s->to_iata,
    'fromCity' => $s->from_city ?: $s->from_iata,
    'toCity'   => $s->to_city   ?: $s->to_iata,
])->values()->toArray();
@endphp
const segmentler = @json($segmentData);

const havalimanları = {
    'IST':{lat:41.2753,lng:28.7519},'SAW':{lat:40.8985,lng:29.3092},
    'ESB':{lat:40.1281,lng:32.9951},'AYT':{lat:36.8987,lng:30.7992},
    'ADB':{lat:38.2924,lng:27.1570},'GZT':{lat:36.9473,lng:37.4787},
    'TZX':{lat:40.9950,lng:39.7897},'CDG':{lat:49.0097,lng:2.5479},
    'LHR':{lat:51.4700,lng:-0.4543},'DXB':{lat:25.2532,lng:55.3657},
    'JFK':{lat:40.6413,lng:-73.7781},'FRA':{lat:50.0379,lng:8.5622},
    'AMS':{lat:52.3105,lng:4.7683},'BCN':{lat:41.2974,lng:2.0833},
    'FCO':{lat:41.8003,lng:12.2389},'MUC':{lat:48.3538,lng:11.7861},
    'DOH':{lat:25.2732,lng:51.6080},'AUH':{lat:24.4330,lng:54.6511},
    'BKK':{lat:13.6811,lng:100.7472},'SIN':{lat:1.3644,lng:103.9915},
};

function initMap() {
    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 4, center: {lat:39.0,lng:30.0}, mapTypeId: 'roadmap',
        styles: [{featureType:'poi',stylers:[{visibility:'off'}]}]
    });
    const bounds  = new google.maps.LatLngBounds();
    const geocoder = new google.maps.Geocoder();
    let pending = 0;

    function getCoords(iata, city, cb) {
        if (havalimanları[iata]) { cb(havalimanları[iata]); return; }
        if (city) geocoder.geocode({address: city + ' airport'}, (r,s) => { if (s==='OK') cb(r[0].geometry.location); });
    }
    function addMarker(pos, code) {
        new google.maps.Marker({ position:pos, map, title:code,
            icon:{ path:google.maps.SymbolPath.CIRCLE, scale:10, fillColor:'#e94560', fillOpacity:1, strokeColor:'white', strokeWeight:2 },
            label:{ text:code, color:'white', fontSize:'9px', fontWeight:'bold' }
        });
    }
    function afterSegment() {
        pending--;
        if (pending === 0) {
            map.fitBounds(bounds);
            // Tek nokta ise yakınlaştırma çok fazla olmasın
            const listener = google.maps.event.addListener(map, 'idle', () => {
                if (map.getZoom() > 7) map.setZoom(7);
                google.maps.event.removeListener(listener);
            });
        }
    }

    pending = segmentler.length;
    if (pending === 0) return;

    segmentler.forEach(seg => {
        getCoords(seg.from, seg.fromCity, from => {
            getCoords(seg.to, seg.toCity, to => {
                if (from && to) {
                    new google.maps.Polyline({ path:[from,to], geodesic:true, strokeColor:'#e94560', strokeOpacity:0.9, strokeWeight:3, map });
                    addMarker(from, seg.from);
                    addMarker(to, seg.to);
                    bounds.extend(from);
                    bounds.extend(to);
                }
                afterSegment();
            });
        });
    });
}

// Harita collapse chevron
document.getElementById('harita-collapse')?.addEventListener('hide.bs.collapse', () => {
    document.getElementById('harita-chevron').style.transform = 'rotate(-90deg)';
});
document.getElementById('harita-collapse')?.addEventListener('show.bs.collapse', () => {
    document.getElementById('harita-chevron').style.transform = 'rotate(0deg)';
});

// ── Opsiyon + Ödeme Countdown ──
function formatKalan(diff) {
    var g = Math.floor(diff / 86400000);
    var s = Math.floor((diff % 86400000) / 3600000);
    var d = Math.floor((diff % 3600000) / 60000);
    return (g > 0 ? g + 'g ' : '') + (s > 0 ? s + 's ' : '') + d + 'dk kaldı';
}
function opsiyonCountdown() {
    document.querySelectorAll('[data-deadline][data-teklif-id]').forEach(function(el) {
        var dl   = new Date(el.dataset.deadline);
        var diff = dl - new Date();
        var kalanEl = document.getElementById('opsiyon-kalan-' + el.dataset.teklifId);
        if (!kalanEl) return;
        if (diff <= 0) { kalanEl.textContent = '— Süresi doldu'; return; }
        kalanEl.textContent = formatKalan(diff);
    });
    document.querySelectorAll('[data-payment-deadline][data-payment-id]').forEach(function(el) {
        var dl   = new Date(el.dataset.paymentDeadline);
        var diff = dl - new Date();
        var kalanEl = document.getElementById('payment-kalan-' + el.dataset.paymentId);
        if (!kalanEl) return;
        if (diff <= 0) { kalanEl.textContent = '— Süresi doldu'; return; }
        kalanEl.textContent = formatKalan(diff);
    });
}
opsiyonCountdown();
setInterval(opsiyonCountdown, 60000);
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA4CoEHudF9V3Zn4h6udx6Ftr3u6h51EXo&libraries=geometry&callback=initMap" async defer></script>
@include('acente.partials.theme-script')

@include('acente.partials.turai-widget', [
    'turaiGtpnr'          => $talep->gtpnr,
    'turaiAcilEndpoint'   => route('acente.requests.acil-sms', $talep->gtpnr),
    'turaiSelfSmsEndpoint'=> route('acente.requests.self-sms', $talep->gtpnr),
    'turaiAdminPhones'    => $adminTelefonlar ?? [],
    'turaiWaLink'         => 'https://wa.me/' . preg_replace('/[^0-9]/', '', \App\Models\SistemAyar::get('sirket_whatsapp', '905354154799')) . '?text=' . rawurlencode($talep->gtpnr . ' numaralı talebim hakkında görüşmek istiyorum.'),
    'turaiAcenteAdi'      => $talep->agency_name ?? '',
    'turaiUserId'         => auth()->id(),
    'turaiSubtitle'       => $talep->gtpnr . ' · ' . $talep->segments->map(fn($s) => $s->from_iata . '→' . $s->to_iata)->implode(' / '),
    'turaiOzetler'        => [],
])
</body>
</html>