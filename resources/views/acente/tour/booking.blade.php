<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tur Rezervasyonu — {{ $leisureRequest->gtpnr }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root{--gy:#1a7a4a;--txt:#1a1a1a;--muted:#595959;--brd:#e8e8e8;--bg:#f5f5f5;--card:#fff;}
        html[data-theme="dark"]{--txt:#f0f0f0;--muted:#b0b0b0;--brd:#333;--bg:#0f1520;--card:#1a2235;}
        body{background:var(--bg);color:var(--txt);}
        .bk-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:1.5rem;padding:1.5rem 0 3rem;align-items:start;}
        @media(max-width:991px){.bk-layout{grid-template-columns:1fr;}}
        .bk-card{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;margin-bottom:1rem;}
        .bk-card-title{font-weight:800;font-size:1rem;margin-bottom:.9rem;display:flex;align-items:center;gap:.5rem;}
        .bk-row{display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--brd);font-size:.9rem;}
        .bk-row:last-child{border-bottom:none;}
        .bk-row-label{color:var(--muted);}
        .bk-row-val{font-weight:600;text-align:right;}
        .bk-total-row{display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-top:2px solid var(--brd);margin-top:.3rem;}
        .bk-total-label{font-weight:700;font-size:1rem;}
        .bk-total-val{font-size:1.4rem;font-weight:800;color:var(--gy);}
        .bk-btn-pay{width:100%;padding:.85rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;}
        .bk-btn-pay:hover{background:#155f3a;}
        .bk-status{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.3rem .7rem;font-size:.78rem;font-weight:700;}
        .bk-status.pending{background:rgba(245,166,35,.15);color:#c07a00;}
        .bk-status.paid{background:rgba(26,122,74,.15);color:#0a5c33;}
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="tour" />

@php
    $booking = $leisureRequest->booking;
    $offer   = $leisureRequest->clientOffers->first();
    $pkg     = $offer?->packageTemplate;
@endphp

<div class="container">
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif

    <div class="d-flex align-items-center gap-2 mt-3 mb-1" style="font-size:.82rem;color:var(--muted);">
        <a href="{{ route('acente.tour.catalog') }}" style="color:var(--muted);text-decoration:none;">Tur Paketleri</a>
        <span>/</span><span>Rezervasyon</span>
        <span>/</span><strong style="color:var(--txt);">{{ $leisureRequest->gtpnr }}</strong>
    </div>

    <div class="bk-layout">
        <div>
            <div class="d-flex gap-3 align-items-center p-3 rounded-3 mb-3" style="background:rgba(26,122,74,.1);border:1px solid rgba(26,122,74,.3);">
                <i class="fas fa-check-circle fa-2x text-success"></i>
                <div>
                    <div class="fw-bold">Rezervasyonunuz oluşturuldu!</div>
                    @if($booking && (float)$booking->remaining_amount <= 0)
                        <div style="font-size:.85rem;color:var(--muted);">Referans: <strong>{{ $leisureRequest->gtpnr }}</strong> — Ödemeniz tamamlandı, yeriniz kesinleşti.</div>
                    @else
                        <div style="font-size:.85rem;color:var(--muted);">Referans: <strong>{{ $leisureRequest->gtpnr }}</strong> — Ödemeyi tamamlayın, yeriniz kesinleşsin.</div>
                    @endif
                </div>
            </div>

            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-map-location-dot text-success"></i> Rezervasyon Detayları</div>
                <div class="bk-row"><span class="bk-row-label">Tur</span><span class="bk-row-val">{{ $pkg?->name_tr ?? 'Tur Paketi' }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Tarih</span><span class="bk-row-val">{{ optional($leisureRequest->service_date)->format('d.m.Y') }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Misafir Sayısı</span><span class="bk-row-val">{{ $leisureRequest->guest_count }} kişi</span></div>
                <div class="bk-row"><span class="bk-row-label">Alınacak Yer</span><span class="bk-row-val">{{ $leisureRequest->hotel_name ?: '—' }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Misafir</span><span class="bk-row-val">{{ $leisureRequest->guest_name }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Telefon</span><span class="bk-row-val">{{ $leisureRequest->guest_phone }}</span></div>
                @if($leisureRequest->nationality)
                    <div class="bk-row"><span class="bk-row-label">Uyruk</span><span class="bk-row-val">{{ $leisureRequest->nationality }}</span></div>
                @endif
                @if($leisureRequest->notes)
                    <div class="bk-row"><span class="bk-row-label">Notlar</span><span class="bk-row-val">{{ $leisureRequest->notes }}</span></div>
                @endif
            </div>

            @if($booking)
            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-receipt text-primary"></i> Ödeme Durumu</div>
                @php
                    $isPaid = (float)($booking->remaining_amount ?? $booking->total_amount) <= 0;
                    $statusClass = $isPaid ? 'paid' : 'pending';
                    $statusLabel = $isPaid ? 'Ödendi' : 'Ödeme Bekliyor';
                @endphp
                <div class="bk-row">
                    <span class="bk-row-label">Durum</span>
                    <span class="bk-status {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="bk-row"><span class="bk-row-label">Toplam</span><span class="bk-row-val">{{ number_format((float)$booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Ödenen</span><span class="bk-row-val">{{ number_format((float)$booking->total_paid, 2, ',', '.') }} {{ $booking->currency }}</span></div>
                <div class="bk-total-row">
                    <span class="bk-total-label">Kalan</span>
                    <span class="bk-total-val">{{ number_format((float)$booking->remaining_amount, 2, ',', '.') }} {{ $booking->currency }}</span>
                </div>
            </div>
            @endif
        </div>

        <div>
            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-credit-card text-success"></i> Ödeme</div>
                @if($booking && (float)$booking->remaining_amount > 0)
                    <div class="mb-3" style="font-size:.88rem;color:var(--muted);">
                        Rezervasyonu onaylamak için <strong>{{ number_format((float)$booking->remaining_amount, 2, ',', '.') }} {{ $booking->currency }}</strong> ödemeniz gerekmektedir.
                    </div>
                    @include('acente.partials.payment-button', [
                        'booking'         => $booking,
                        'leisureRequest'  => $leisureRequest,
                        'returnRoute'     => route('acente.tour.booking-show', $leisureRequest),
                        'productLabel'    => $pkg?->name_tr ?? 'Tur Paketi',
                    ])
                @elseif($booking && (float)$booking->remaining_amount <= 0)
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>Ödeme tamamlandı!
                    </div>
                @else
                    <div class="text-muted small">Ödeme bilgisi bulunamadı.</div>
                @endif
            </div>

            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-headset text-warning"></i> Yardım</div>
                <div style="font-size:.85rem;color:var(--muted);">
                    Rezervasyonunuzla ilgili sorularınız için destek ekibimizle iletişime geçin.
                </div>
                <a href="{{ route('acente.rezervasyonlarim.index') }}" class="btn btn-outline-secondary btn-sm mt-2 w-100">
                    <i class="fas fa-list me-1"></i> Tüm Rezervasyonlarım
                </a>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.leisure-footer')
@include('acente.partials.theme-script')
</body>
</html>
