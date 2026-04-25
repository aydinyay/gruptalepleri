@extends('b2c.layouts.app')

@section('title', 'Talebim — ' . $talep->gtpnr)
@section('meta_description', 'Grup uçuş talebinizin durumunu takip edin.')

@push('head_styles')
<style>
.track-page {
    min-height: calc(100vh - var(--nav-height));
    background: var(--gr-light, #f8f9fc);
    padding: 32px 0 60px;
}

/* ── Üst başlık ─────────── */
.track-hero {
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
    color: #fff;
    padding: 28px 0 24px;
    margin-bottom: 28px;
}
.track-hero h1 { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px; }
.track-hero .gtpnr { font-family: monospace; font-size: 1.1rem; letter-spacing: 2px; color: rgba(255,255,255,.8); }

/* ── Durum badge ────────── */
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 20px;
    font-size: .8rem; font-weight: 700;
}
.status-beklemede       { background: rgba(108,117,125,.15); color: #6c757d; }
.status-islemde         { background: rgba(13,110,253,.12); color: #0d6efd; }
.status-fiyatlandirildi { background: rgba(255,193,7,.15); color: #d4a000; }
.status-depozitoda      { background: rgba(111,66,193,.12); color: #6f42c1; }
.status-biletlendi      { background: rgba(25,135,84,.12); color: #198754; }
.status-olumsuz, .status-iptal { background: rgba(220,53,69,.1); color: #dc3545; }

/* ── Kart ───────────────── */
.tr-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8edf5;
    margin-bottom: 16px;
    overflow: hidden;
}
.tr-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid #f0f4ff;
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #6b7a99;
    display: flex; align-items: center; gap: 8px;
}
.tr-card-body { padding: 18px 20px; }

/* ── Segment satırı ─────── */
.seg-row {
    display: flex; align-items: center;
    gap: 10px; padding: 10px 0;
    border-bottom: 1px solid #f0f4ff;
    font-size: .88rem;
}
.seg-row:last-child { border-bottom: none; padding-bottom: 0; }
.seg-iata { font-weight: 800; color: #1a3c6b; font-size: 1rem; }
.seg-arrow { color: #1a3c6b; opacity: .4; font-size: 1rem; }
.seg-date { color: #4a5568; font-size: .83rem; }
.seg-slot {
    background: #eef2fb; color: #1a3c6b;
    font-size: .68rem; font-weight: 700;
    padding: 2px 8px; border-radius: 12px;
    text-transform: uppercase; letter-spacing: .4px;
    margin-left: auto;
}

/* ── Bekleme kartı ──────── */
.waiting-card {
    background: linear-gradient(135deg, #fff7ed, #fff);
    border: 1.5px solid #fed7aa;
    border-radius: 14px;
    padding: 28px 24px;
    text-align: center;
    margin-bottom: 16px;
}
.waiting-icon { font-size: 2.5rem; margin-bottom: 10px; }
.waiting-card h3 { font-size: 1.05rem; font-weight: 700; color: #c2410c; margin: 0 0 6px; }
.waiting-card p  { font-size: .85rem; color: #7c2d12; margin: 0; line-height: 1.6; }

/* ── Teklif kartı ───────── */
.offer-card {
    background: linear-gradient(135deg, #f0fdf4, #fff);
    border: 2px solid #86efac;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 12px;
    position: relative;
}
.offer-card.accepted {
    background: linear-gradient(135deg, #eff6ff, #fff);
    border-color: #93c5fd;
}
.offer-badge {
    position: absolute; top: -10px; left: 20px;
    background: #22c55e; color: #fff;
    font-size: .65rem; font-weight: 700;
    padding: 3px 10px; border-radius: 12px;
    text-transform: uppercase; letter-spacing: .5px;
}
.offer-badge.accepted-badge { background: #3b82f6; }
.offer-airline { font-size: 1.1rem; font-weight: 800; color: #166534; margin-bottom: 10px; }
.offer-card.accepted .offer-airline { color: #1d4ed8; }
.offer-price {
    font-size: 1.8rem; font-weight: 800; color: #166534;
    margin-bottom: 4px;
}
.offer-card.accepted .offer-price { color: #1d4ed8; }
.offer-pax { font-size: .78rem; color: #718096; margin-bottom: 14px; }
.offer-detail { font-size: .82rem; color: #4a5568; line-height: 1.7; margin-bottom: 14px; }
.offer-detail li { list-style: none; padding: 0; display: flex; gap: 6px; }
.offer-detail li::before { content: '✓'; color: #22c55e; font-weight: 700; }

.btn-accept {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 10px;
    background: #16a34a;
    color: #fff;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .18s;
}
.btn-accept:hover { background: #15803d; transform: translateY(-1px); }

/* ── Kabul sonrası ──────── */
.accepted-notice {
    background: #eff6ff;
    border: 1.5px solid #93c5fd;
    border-radius: 12px;
    padding: 16px 18px;
    font-size: .85rem;
    color: #1e40af;
    display: flex; gap: 10px; align-items: flex-start;
    margin-bottom: 16px;
}
.accepted-notice .icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 1px; }

/* ── Adım timeline ──────── */
.steps-list { list-style: none; padding: 0; margin: 0; }
.steps-list li {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #f0f4ff;
    font-size: .83rem; color: #4a5568;
}
.steps-list li:last-child { border-bottom: none; }
.step-dot {
    width: 28px; height: 28px; flex-shrink: 0;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    font-size: .72rem; color: #718096;
}
.step-dot.done { background: #dcfce7; color: #16a34a; }
.step-dot.active { background: #dbeafe; color: #1d4ed8; }
.step-text .s-label { font-weight: 600; color: #1a202c; }
.step-text .s-time  { color: #a0aec0; font-size: .72rem; }

/* ── İletişim ───────────── */
.contact-strip {
    background: #0f2444;
    border-radius: 14px;
    padding: 18px 20px;
    color: #fff;
    display: flex; align-items: center;
    justify-content: space-between; gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.contact-strip .ct-text .label { font-size: .75rem; opacity: .65; }
.contact-strip .ct-text .val   { font-size: .9rem; font-weight: 600; }
.btn-whatsapp {
    background: #25d366; color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 18px; font-size: .83rem;
    font-weight: 700; cursor: pointer;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all .15s;
    flex-shrink: 0;
}
.btn-whatsapp:hover { background: #1ebe5b; }

@@media (max-width: 576px) {
    .track-hero { padding: 20px 0 18px; }
    .tr-card-body { padding: 14px 16px; }
    .tr-card-header { padding: 12px 16px; }
    .offer-card { padding: 16px; }
    .contact-strip { flex-direction: column; }
}
</style>
@endpush

@section('content')

@php
    $statusLabels = [
        'beklemede'      => ['label' => 'İnceleniyor',       'icon' => '⏳', 'css' => 'beklemede'],
        'islemde'        => ['label' => 'İşlemde',           'icon' => '⚙️', 'css' => 'islemde'],
        'fiyatlandirildi'=> ['label' => 'Teklif Hazır',      'icon' => '💸', 'css' => 'fiyatlandirildi'],
        'depozitoda'     => ['label' => 'Teklif Kabul Edildi','icon'=> '✅', 'css' => 'depozitoda'],
        'biletlendi'     => ['label' => 'Biletlendi',        'icon' => '🎫', 'css' => 'biletlendi'],
        'olumsuz'        => ['label' => 'Teklif Verilemedi', 'icon' => '❌', 'css' => 'olumsuz'],
        'iptal'          => ['label' => 'İptal',             'icon' => '❌', 'css' => 'iptal'],
    ];
    $statusInfo = $statusLabels[$talep->status] ?? ['label' => $talep->status, 'icon' => '•', 'css' => 'beklemede'];
    $slotLabels = ['sabah' => 'Sabah', 'ogle' => 'Öğlen', 'aksam' => 'Akşam', 'esnek' => 'Esnek'];
    $tripLabels = ['one_way' => 'Tek Yön', 'round_trip' => 'Gidiş-Dönüş', 'multi' => 'Çoklu Uçuş'];
    $activeOffers  = $talep->offers->where('durum', \App\Models\Offer::DURUM_BEKLEMEDE);
    $acceptedOffer = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
@endphp

{{-- Hero başlık --}}
<div class="track-hero">
    <div class="container px-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1>✈️ Grup Uçuş Talebim</h1>
                <div class="gtpnr">{{ $talep->gtpnr }}</div>
            </div>
            <div>
                <span class="status-badge status-{{ $statusInfo['css'] }}">
                    {{ $statusInfo['icon'] }} {{ $statusInfo['label'] }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="track-page" style="padding-top: 0;">
<div class="container px-3">
<div class="row g-3">

    {{-- Sol: Teklif / Durum ────────────────────────────── --}}
    <div class="col-lg-7">

        @if(session('offer_accepted'))
        <div class="accepted-notice">
            <span class="icon">🎉</span>
            <div>
                <strong>Teklifiniz kabul edildi!</strong><br>
                Ekibimiz ödeme planınızı hazırlayacak ve size en kısa sürede ulaşacak.
            </div>
        </div>
        @endif

        {{-- TEKLIF KARTI ---------------------------------------- --}}
        @if($acceptedOffer)
        <div class="offer-card accepted mb-3">
            <span class="offer-badge accepted-badge">✓ Kabul Edildi</span>
            <div class="mt-2 offer-airline">{{ $acceptedOffer->airline ?? 'Havayolu' }}</div>
            <div class="offer-price">
                {{ number_format($acceptedOffer->total_price, 0, ',', '.') }}
                <span style="font-size:1rem;font-weight:600;">{{ $acceptedOffer->currency }}</span>
            </div>
            <div class="offer-pax">{{ $acceptedOffer->pax_confirmed ?? $talep->pax_total }} kişi · kişi başı
                {{ $acceptedOffer->price_per_pax ? number_format($acceptedOffer->price_per_pax,0,',','.').' '.$acceptedOffer->currency : '—' }}
            </div>
            @if($acceptedOffer->offer_text)
            <ul class="offer-detail ps-0">
                @foreach(explode("\n", trim($acceptedOffer->offer_text)) as $line)
                    @if(trim($line))
                    <li>{{ trim($line) }}</li>
                    @endif
                @endforeach
            </ul>
            @endif
            <div class="alert py-2 mb-0" style="background:#dbeafe;border:1px solid #93c5fd;border-radius:8px;font-size:.82rem;color:#1e40af;">
                <i class="bi bi-info-circle me-1"></i>
                Ödeme planınız hazırlandığında size e-posta ve SMS ile bilgi verilecektir.
            </div>
        </div>

        @elseif($activeOffers->count() > 0)
            @foreach($activeOffers as $offer)
            <div class="offer-card mb-3">
                <span class="offer-badge">Teklif Hazır</span>
                <div class="mt-2 offer-airline">{{ $offer->airline ?? 'Havayolu' }}</div>
                <div class="offer-price">
                    {{ number_format($offer->total_price, 0, ',', '.') }}
                    <span style="font-size:1rem;font-weight:600;">{{ $offer->currency }}</span>
                </div>
                <div class="offer-pax">{{ $offer->pax_confirmed ?? $talep->pax_total }} kişi · kişi başı
                    {{ $offer->price_per_pax ? number_format($offer->price_per_pax,0,',','.').' '.$offer->currency : '—' }}
                </div>
                @if($offer->offer_text)
                <ul class="offer-detail ps-0">
                    @foreach(explode("\n", trim($offer->offer_text)) as $line)
                        @if(trim($line))
                        <li>{{ trim($line) }}</li>
                        @endif
                    @endforeach
                </ul>
                @endif
                @if($offer->option_date)
                <div style="font-size:.78rem;color:#dc3545;margin-bottom:12px;">
                    <i class="bi bi-clock me-1"></i>Son karar tarihi:
                    <strong>{{ \Carbon\Carbon::parse($offer->option_date)->format('d.m.Y') }}</strong>
                </div>
                @endif
                <form method="POST" action="{{ lroute('b2c.flight.offer.accept', [$talep->gtpnr, $offer->id]) }}" onsubmit="return confirm('Bu teklifi kabul etmek istediğinize emin misiniz?')">
                    @csrf
                    <button type="submit" class="btn-accept">
                        <i class="bi bi-check-circle-fill me-2"></i>Bu Teklifi Kabul Et
                    </button>
                </form>
            </div>
            @endforeach

        @elseif(in_array($talep->status, ['beklemede','islemde']))
        <div class="waiting-card">
            <div class="waiting-icon">⏳</div>
            <h3>Talebiniz İnceleniyor</h3>
            <p>Ekibimiz uygun uçuş seçeneklerini araştırıyor.<br>
            Fiyat teklifi hazır olduğunda <strong>{{ $talep->email }}</strong> adresinize e-posta göndereceğiz.</p>
        </div>

        @elseif($talep->status === 'olumsuz')
        <div class="waiting-card" style="background:linear-gradient(135deg,#fff5f5,#fff);border-color:#fed7d7;">
            <div class="waiting-icon">😔</div>
            <h3 style="color:#c53030;">Teklif Verilemedi</h3>
            <p style="color:#742a2a;">Belirttiğiniz tarih ve rota için şu an uygun seçenek bulunamadı. Farklı tarih veya rota ile yeni talep oluşturabilirsiniz.</p>
            <a href="{{ lroute('b2c.flight.create') }}" class="btn-accept mt-3 d-inline-block text-decoration-none text-center" style="background:#c53030;">
                Yeni Talep Oluştur
            </a>
        </div>

        @elseif($talep->status === 'biletlendi')
        <div class="waiting-card" style="background:linear-gradient(135deg,#f0fdf4,#fff);border-color:#86efac;">
            <div class="waiting-icon">🎫</div>
            <h3 style="color:#166534;">Biletlendi!</h3>
            <p style="color:#14532d;">Grubunuzun biletleri kesildi. Bilet bilgileri {{ $talep->email }} adresinize gönderilmiştir.</p>
        </div>
        @endif

        {{-- İletişim şeridi --}}
        @php
            $wa = \App\Models\SistemAyar::get('sirket_whatsapp', '');
            $tel = \App\Models\SistemAyar::get('sirket_telefon', '');
        @endphp
        @if($wa || $tel)
        <div class="contact-strip">
            <div class="ct-text">
                <div class="label">Sorunuz mu var?</div>
                <div class="val">{{ $wa ?: $tel }}</div>
            </div>
            @if($wa)
            <a href="https://wa.me/{{ preg_replace('/\D/','',$wa) }}?text={{ urlencode('Merhaba, '.$talep->gtpnr.' numaralı talep hakkında bilgi almak istiyorum.') }}"
               target="_blank" class="btn-whatsapp">
                <i class="bi bi-whatsapp"></i> WhatsApp
            </a>
            @endif
        </div>
        @endif

    </div>

    {{-- Sağ: Talep özeti ────────────────────────────────── --}}
    <div class="col-lg-5">

        {{-- Talep Detayı --}}
        <div class="tr-card">
            <div class="tr-card-header">
                <i class="bi bi-clipboard2-data"></i> Talep Detayı
            </div>
            <div class="tr-card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span style="font-size:.78rem;color:#718096;">Uçuş Tipi</span>
                    <span style="font-size:.83rem;font-weight:600;">{{ $tripLabels[$talep->trip_type] ?? $talep->trip_type }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span style="font-size:.78rem;color:#718096;">Yolcu</span>
                    <span style="font-size:.83rem;font-weight:600;">
                        {{ $talep->pax_total }} kişi
                        @if($talep->pax_adult || $talep->pax_child || $talep->pax_infant)
                        <span style="color:#718096;font-weight:400;">(Y:{{ $talep->pax_adult }} Ç:{{ $talep->pax_child }} B:{{ $talep->pax_infant }})</span>
                        @endif
                    </span>
                </div>
                @if($talep->flight_purpose)
                <div class="d-flex justify-content-between mb-2">
                    <span style="font-size:.78rem;color:#718096;">Amaç</span>
                    <span style="font-size:.83rem;font-weight:600;">{{ ucfirst(str_replace('_',' ',$talep->flight_purpose)) }}</span>
                </div>
                @endif
                @if($talep->hotel_needed)
                <div class="d-flex justify-content-between mb-2">
                    <span style="font-size:.78rem;color:#718096;">Otel</span>
                    <span style="font-size:.83rem;font-weight:600;">🏨 İsteniyor</span>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-2">
                    <span style="font-size:.78rem;color:#718096;">Talep Tarihi</span>
                    <span style="font-size:.83rem;font-weight:600;">{{ $talep->created_at->format('d.m.Y H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Rotalar --}}
        <div class="tr-card">
            <div class="tr-card-header">
                <i class="bi bi-map"></i> Rota
            </div>
            <div class="tr-card-body" style="padding-top:8px;padding-bottom:8px;">
                @foreach($talep->segments as $seg)
                <div class="seg-row">
                    <span class="seg-iata">{{ strtoupper($seg->from_iata) }}</span>
                    <span class="seg-arrow">→</span>
                    <span class="seg-iata">{{ strtoupper($seg->to_iata) }}</span>
                    <span class="seg-date">{{ \Carbon\Carbon::parse($seg->departure_date)->format('d.m.Y') }}</span>
                    <span class="seg-slot">{{ $slotLabels[$seg->departure_time_slot] ?? $seg->departure_time_slot }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Süreç Adımları --}}
        <div class="tr-card">
            <div class="tr-card-header">
                <i class="bi bi-list-check"></i> Süreç
            </div>
            <div class="tr-card-body" style="padding-top:8px;padding-bottom:8px;">
                <ul class="steps-list">
                    @php
                        $steps = [
                            ['label' => 'Talep Alındı',          'done' => true,  'active' => false],
                            ['label' => 'Fiyat Araştırılıyor',   'done' => !in_array($talep->status,['beklemede']), 'active' => $talep->status === 'beklemede'],
                            ['label' => 'Teklif Hazırlandı',     'done' => !in_array($talep->status,['beklemede','islemde']), 'active' => $talep->status === 'fiyatlandirildi'],
                            ['label' => 'Teklif Kabul Edildi',   'done' => in_array($talep->status,['depozitoda','biletlendi']), 'active' => $talep->status === 'depozitoda'],
                            ['label' => 'Biletlendi',            'done' => $talep->status === 'biletlendi', 'active' => false],
                        ];
                    @endphp
                    @foreach($steps as $step)
                    <li>
                        <div class="step-dot {{ $step['done'] ? 'done' : ($step['active'] ? 'active' : '') }}">
                            @if($step['done']) ✓
                            @elseif($step['active']) ●
                            @else ○
                            @endif
                        </div>
                        <div class="step-text">
                            <div class="s-label" style="{{ $step['done'] ? '' : ($step['active'] ? 'color:#1d4ed8;' : 'color:#a0aec0;') }}">
                                {{ $step['label'] }}
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="text-center mt-2 mb-2">
            <a href="{{ lroute('b2c.flight.create') }}" style="font-size:.8rem;color:#1a3c6b;text-decoration:none;">
                <i class="bi bi-plus-circle me-1"></i>Yeni talep oluştur
            </a>
        </div>

    </div>
</div>
</div>
</div>
@endsection
