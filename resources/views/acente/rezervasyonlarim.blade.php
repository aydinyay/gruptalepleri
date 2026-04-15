<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyonlarım — GrupTalepleri</title>
    @include('acente.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }

        .gt-rez-hero {
            border-radius: 14px;
            background: linear-gradient(130deg, #0f1f48 0%, #1a3b7a 100%);
            color: #f8fafc;
            padding: 1.3rem 1.5rem;
        }

        .gt-rez-card {
            border: 1px solid rgba(15,23,42,.08);
            border-radius: 12px;
            background: #fff;
            transition: box-shadow .18s;
        }
        .gt-rez-card:hover { box-shadow: 0 6px 20px rgba(15,23,42,.1); }

        .gt-rez-status {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 6px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .gt-rez-status.confirmed  { background: #d1fae5; color: #065f46; }
        .gt-rez-status.pending    { background: #fef3c7; color: #92400e; }
        .gt-rez-status.cancelled  { background: #fee2e2; color: #991b1b; }
        .gt-rez-status.failed     { background: #f3f4f6; color: #374151; }
        .gt-rez-status.refunded   { background: #ede9fe; color: #5b21b6; }
        .gt-rez-status.booked     { background: #d1fae5; color: #065f46; }
        .gt-rez-status.accepted   { background: #d1fae5; color: #065f46; }
        .gt-rez-status.other      { background: #e0e7ff; color: #3730a3; }

        .gt-rez-meta { font-size: .82rem; color: #64748b; }
        .gt-rez-ref  { font-weight: 700; font-size: .92rem; color: #0f172a; font-family: monospace; }

        .gt-nav-tabs .nav-link { color: #64748b; font-weight: 600; }
        .gt-nav-tabs .nav-link.active { color: #1a3b7a; border-bottom: 2px solid #1a3b7a; background: transparent; }

        html[data-theme="dark"] .gt-rez-card { background: #0f1d36; border-color: #2d4371; color: #e5e7eb; }
        html[data-theme="dark"] .gt-rez-ref  { color: #f1f5f9; }
        html[data-theme="dark"] .gt-rez-meta { color: #9fb2d9; }
        html[data-theme="dark"] .gt-rez-hero { background: linear-gradient(130deg, #0a1428 0%, #102560 100%); }

        @media print { .no-print { display:none!important; } }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="rezervasyonlarim" />

<div class="container py-4">

    <div class="gt-rez-hero mb-4 no-print">
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:rgba(226,232,240,.75);font-weight:700;">
            <i class="fas fa-calendar-check me-1"></i>Rezervasyonlarım
        </div>
        <h1 style="font-size:clamp(1.3rem,2.2vw,1.8rem);font-weight:800;margin:.3rem 0 .3rem;">
            Tüm Rezervasyonlarım
        </h1>
        <p style="margin:0;color:rgba(241,245,249,.82);font-size:.9rem;">
            Transfer, dinner cruise ve yacht charter rezervasyonlarınız tek ekranda.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success border shadow-sm">{{ session('success') }}</div>
    @endif

    @php
        $totalCount = $transferBookings->count() + $leisureBookings->count();
    @endphp

    @if($totalCount === 0)
        <div class="text-center py-5">
            <i class="fas fa-calendar-xmark fa-3x text-muted mb-3"></i>
            <h3 class="h5 fw-bold text-muted">Henüz rezervasyonunuz yok</h3>
            <p class="text-muted">Transfer veya leisure hizmeti rezerve ettiğinizde burada görünür.</p>
            <a href="{{ route('acente.transfer.index') }}" class="btn btn-primary me-2">Transfer Ara</a>
            <a href="{{ route('acente.dinner-cruise.catalog') }}" class="btn btn-outline-primary">Dinner Cruise</a>
        </div>
    @else
        {{-- Sekme navigasyonu --}}
        <ul class="nav gt-nav-tabs border-bottom mb-4 no-print" id="rezTabs">
            <li class="nav-item">
                <button class="nav-link active" data-tab="all">
                    Tümü <span class="badge text-bg-secondary ms-1">{{ $totalCount }}</span>
                </button>
            </li>
            @if($transferBookings->count())
            <li class="nav-item">
                <button class="nav-link" data-tab="transfer">
                    <i class="fas fa-shuttle-van me-1"></i>Transfer
                    <span class="badge text-bg-secondary ms-1">{{ $transferBookings->count() }}</span>
                </button>
            </li>
            @endif
            @if($leisureBookings->count())
            <li class="nav-item">
                <button class="nav-link" data-tab="leisure">
                    <i class="fas fa-compass me-1"></i>Leisure
                    <span class="badge text-bg-secondary ms-1">{{ $leisureBookings->count() }}</span>
                </button>
            </li>
            @endif
        </ul>

        {{-- TRANSFER rezervasyonları --}}
        @if($transferBookings->count())
        <div class="rez-section mb-4" data-section="transfer">
            <h2 class="h6 fw-bold text-muted mb-2 text-uppercase" style="letter-spacing:.06em;">
                <i class="fas fa-shuttle-van me-1"></i>Transfer Rezervasyonları
            </h2>
            <div class="row g-3">
                @foreach($transferBookings as $booking)
                @php
                    $statusClass = match($booking->status) {
                        'confirmed'       => 'confirmed',
                        'payment_pending' => 'pending',
                        'cancelled'       => 'cancelled',
                        'refunded'        => 'refunded',
                        'failed'          => 'failed',
                        default           => 'other',
                    };
                    $statusLabel = match($booking->status) {
                        'confirmed'       => 'Onaylandı',
                        'payment_pending' => 'Ödeme Bekliyor',
                        'cancelled'       => 'İptal',
                        'refunded'        => 'İade',
                        'failed'          => 'Başarısız',
                        default           => strtoupper($booking->status),
                    };
                    $latestTx = $booking->paymentTransactions->first();
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="gt-rez-card p-3 h-100 d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="gt-rez-ref">{{ $booking->booking_ref }}</div>
                                <div class="gt-rez-meta">
                                    <i class="fas fa-shuttle-van me-1"></i>{{ $booking->vehicleType?->name ?? '-' }}
                                </div>
                            </div>
                            <span class="gt-rez-status {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="gt-rez-meta">
                            <i class="fas fa-route me-1"></i>
                            {{ $booking->airport?->code }} → {{ $booking->zone?->name }}
                        </div>
                        <div class="gt-rez-meta">
                            <i class="fas fa-calendar me-1"></i>
                            {{ optional($booking->pickup_at)->format('d.m.Y H:i') }}
                            @if($booking->return_at)
                                <span class="ms-2"><i class="fas fa-rotate me-1"></i>{{ optional($booking->return_at)->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        <div class="gt-rez-meta">
                            <i class="fas fa-users me-1"></i>{{ $booking->pax }} kişi
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <strong style="font-size:1rem;">
                                {{ number_format((float)$booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}
                            </strong>
                            <a href="{{ route('acente.transfer.booking.show', $booking) }}"
                               class="btn btn-outline-primary btn-sm">
                                Detay & Voucher <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- LEISURE rezervasyonları (dinner cruise + yacht) --}}
        @if($leisureBookings->count())
        <div class="rez-section mb-4" data-section="leisure">
            <h2 class="h6 fw-bold text-muted mb-2 text-uppercase" style="letter-spacing:.06em;">
                <i class="fas fa-compass me-1"></i>Leisure Rezervasyonları
            </h2>
            <div class="row g-3">
                @foreach($leisureBookings as $leisureRequest)
                @php
                    $lBooking = $leisureRequest->booking;
                    $statusClass = match(true) {
                        in_array($leisureRequest->status, ['booked', 'confirmed', 'in_operation', 'completed']) => 'confirmed',
                        in_array($leisureRequest->status, ['offer_sent', 'revised', 'pending']) => 'pending',
                        in_array($leisureRequest->status, ['cancelled']) => 'cancelled',
                        default => 'other',
                    };
                    $statusLabel = match($leisureRequest->status) {
                        'booked'       => 'Rezervasyon',
                        'confirmed'    => 'Onaylı',
                        'in_operation' => 'Operasyonda',
                        'completed'    => 'Tamamlandı',
                        'offer_sent'   => 'Teklif Verildi',
                        'revised'      => 'Revize',
                        'pending'      => 'Beklemede',
                        'cancelled'    => 'İptal',
                        default        => strtoupper($leisureRequest->status ?? '-'),
                    };
                    $productIcon = match($leisureRequest->product_type) {
                        'dinner_cruise' => 'fas fa-utensils',
                        'yacht'         => 'fas fa-ship',
                        'tour'          => 'fas fa-map-location-dot',
                        default         => 'fas fa-compass',
                    };
                    $productLabel = match($leisureRequest->product_type) {
                        'dinner_cruise' => 'Dinner Cruise',
                        'yacht'         => 'Yacht Charter',
                        'tour'          => 'Günübirlik Tur',
                        default         => ucfirst((string) $leisureRequest->product_type),
                    };
                    $bookingRoute = match($leisureRequest->product_type) {
                        'dinner_cruise' => route('acente.dinner-cruise.booking-show', $leisureRequest),
                        'yacht'         => route('acente.yacht-charter.booking-show', $leisureRequest),
                        'tour'          => route('acente.tour.booking-show', $leisureRequest),
                        default         => route('acente.rezervasyonlarim.index'),
                    };
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="gt-rez-card p-3 h-100 d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="gt-rez-ref">{{ $leisureRequest->gtpnr ?? ('LEIS-' . $leisureRequest->id) }}</div>
                                <div class="gt-rez-meta">
                                    <i class="{{ $productIcon }} me-1"></i>{{ $productLabel }}
                                </div>
                            </div>
                            <span class="gt-rez-status {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        @if($leisureRequest->service_date)
                        <div class="gt-rez-meta">
                            <i class="fas fa-calendar me-1"></i>
                            {{ \Carbon\Carbon::parse($leisureRequest->service_date)->format('d.m.Y') }}
                        </div>
                        @endif
                        @if($leisureRequest->guest_count)
                        <div class="gt-rez-meta">
                            <i class="fas fa-users me-1"></i>{{ $leisureRequest->guest_count }} kişi
                        </div>
                        @endif
                        @if($lBooking && $lBooking->total_amount)
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <strong style="font-size:1rem;">
                                {{ number_format((float)$lBooking->total_amount, 2, ',', '.') }} {{ $lBooking->currency ?? '' }}
                            </strong>
                            <a href="{{ $bookingRoute }}" class="btn btn-outline-primary btn-sm">
                                Detay <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        @else
                        <div class="mt-auto pt-2 border-top">
                            <a href="{{ $bookingRoute }}" class="btn btn-outline-primary btn-sm w-100">
                                Detay <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endif
</div>

@include('acente.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const tabs = document.querySelectorAll('#rezTabs [data-tab]');
    const sections = document.querySelectorAll('.rez-section');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const selected = tab.dataset.tab;
            sections.forEach(s => {
                if (selected === 'all' || s.dataset.section === selected) {
                    s.style.display = '';
                } else {
                    s.style.display = 'none';
                }
            });
        });
    });
})();
</script>
</body>
</html>
