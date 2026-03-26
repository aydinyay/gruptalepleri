<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $leisureRequest->gtpnr }} - {{ $leisureRequest->productLabel() }}</title>
    @include('acente.partials.theme-styles')
    <style>
        .leisure-show-page {
            --bg: linear-gradient(180deg, #f5f7fb 0%, #eef3f8 42%, #f8fafc 100%);
            --shell: rgba(255, 255, 255, .88);
            --border: rgba(148, 163, 184, .22);
            --muted: #64748b;
            --heading: #0f172a;
            --surface-shadow: 0 26px 60px rgba(15, 23, 42, .08);
            background: var(--bg);
            min-height: 100vh;
        }
        .leisure-show-page .page-shell { padding: 1.5rem 0 3rem; }
        .leisure-show-page .hero-card,
        .leisure-show-page .shell-card,
        .leisure-show-page .offer-card,
        .leisure-show-page .metric-card,
        .leisure-show-page .timeline-item,
        .leisure-show-page .media-card {
            background: var(--shell);
            border: 1px solid var(--border);
            box-shadow: var(--surface-shadow);
            backdrop-filter: blur(18px);
        }
        .leisure-show-page .hero-card {
            border-radius: 30px;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(250, 204, 21, .18), transparent 24%),
                radial-gradient(circle at left center, rgba(14, 165, 233, .18), transparent 28%),
                linear-gradient(135deg, #081225 0%, #10264d 46%, #142d56 100%);
            color: #f8fafc;
        }
        .leisure-show-page .hero-title { font-family: Georgia, "Times New Roman", serif; font-size: clamp(2rem, 4.6vw, 3.6rem); margin: .9rem 0 .7rem; }
        .leisure-show-page .eyebrow,
        .leisure-show-page .hero-chip,
        .leisure-show-page .status-pill,
        .leisure-show-page .fact-pill,
        .leisure-show-page .offer-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
        }
        .leisure-show-page .eyebrow { padding: .45rem .8rem; background: rgba(255, 255, 255, .12); color: rgba(255, 255, 255, .92); text-transform: uppercase; letter-spacing: .04em; }
        .leisure-show-page .hero-copy { color: rgba(226, 232, 240, .88); line-height: 1.8; max-width: 58ch; }
        .leisure-show-page .hero-chip-row,
        .leisure-show-page .metric-grid,
        .leisure-show-page .fact-grid,
        .leisure-show-page .timeline-grid,
        .leisure-show-page .media-grid,
        .leisure-show-page .offer-grid { display: grid; gap: 1rem; }
        .leisure-show-page .hero-chip-row { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 1rem; }
        .leisure-show-page .hero-chip { padding: .8rem .95rem; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .1); color: #fff; }
        .leisure-show-page .hero-actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 1.3rem; }
        .leisure-show-page .hero-btn,
        .leisure-show-page .ghost-btn,
        .leisure-show-page .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: 999px;
            padding: .9rem 1.2rem;
            text-decoration: none;
            font-weight: 700;
            border: 0;
        }
        .leisure-show-page .hero-btn,
        .leisure-show-page .action-btn.primary { color: #fff; background: linear-gradient(135deg, #f97316, #ea580c); box-shadow: 0 16px 32px rgba(234, 88, 12, .24); }
        .leisure-show-page .ghost-btn,
        .leisure-show-page .action-btn.secondary { color: #f8fafc; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .14); }
        .leisure-show-page .shell-card,
        .leisure-show-page .offer-card { border-radius: 28px; }
        .leisure-show-page .section-kicker { margin-bottom: .55rem; font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #0f766e; }
        .leisure-show-page .section-title { margin: 0; color: var(--heading); font-size: 1.35rem; font-weight: 800; }
        .leisure-show-page .section-copy,
        .leisure-show-page .small-copy,
        .leisure-show-page .offer-copy { color: var(--muted); line-height: 1.75; }
        .leisure-show-page .metric-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .leisure-show-page .metric-card { border-radius: 22px; padding: 1rem; }
        .leisure-show-page .metric-label { font-size: .76rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 800; color: var(--muted); }
        .leisure-show-page .metric-value { margin-top: .4rem; color: #0f172a; font-size: 1.15rem; font-weight: 800; }
        .leisure-show-page .fact-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 1rem; }
        .leisure-show-page .fact-pill { padding: .55rem .8rem; background: rgba(241, 245, 249, .94); color: #0f172a; border: 1px solid rgba(148, 163, 184, .2); }
        .leisure-show-page .timeline-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .leisure-show-page .timeline-item { border-radius: 18px; padding: .9rem 1rem; }
        .leisure-show-page .aside-stack { display: grid; gap: 1rem; position: sticky; top: 1rem; }
        .leisure-show-page .offer-card { overflow: hidden; }
        .leisure-show-page .offer-head { background: linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(30, 41, 59, .92)); color: #f8fafc; padding: 1rem 1.15rem; }
        .leisure-show-page .offer-pill { padding: .4rem .7rem; background: rgba(255, 255, 255, .12); color: #fff; }
        .leisure-show-page .offer-price { font-size: 1.65rem; font-weight: 800; }
        .leisure-show-page .offer-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .leisure-show-page .offer-block { background: rgba(248, 250, 252, .92); border: 1px solid rgba(148, 163, 184, .18); border-radius: 20px; padding: 1rem; }
        .leisure-show-page .offer-block h6 { margin: 0 0 .65rem; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; color: #0f172a; }
        .leisure-show-page .offer-block ul { margin: 0; padding-left: 1rem; color: var(--muted); line-height: 1.75; }
        .leisure-show-page .media-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .leisure-show-page .media-card { border-radius: 18px; overflow: hidden; }
        .leisure-show-page .media-card img { width: 100%; height: 170px; object-fit: cover; display: block; }
        .leisure-show-page .media-card .caption { padding: .8rem; color: var(--muted); font-size: .9rem; }
        @media (max-width: 991.98px) {
            .leisure-show-page .hero-chip-row,
            .leisure-show-page .metric-grid,
            .leisure-show-page .fact-grid,
            .leisure-show-page .timeline-grid,
            .leisure-show-page .offer-grid,
            .leisure-show-page .media-grid { grid-template-columns: 1fr; }
            .leisure-show-page .aside-stack { position: static; }
        }
        html[data-theme="dark"] .leisure-show-page {
            --bg: linear-gradient(180deg, #08111f 0%, #0b1629 45%, #091120 100%);
            --shell: rgba(10, 20, 37, .9);
            --border: rgba(59, 130, 246, .18);
            --muted: #9fb2d9;
            --heading: #eff6ff;
        }
        html[data-theme="dark"] .leisure-show-page .metric-value,
        html[data-theme="dark"] .leisure-show-page .offer-block h6 { color: #f8fafc; }
        html[data-theme="dark"] .leisure-show-page .fact-pill,
        html[data-theme="dark"] .leisure-show-page .offer-block { background: rgba(15, 23, 42, .86); color: #e2e8f0; }
    </style>
</head>
<body class="theme-scope leisure-show-page">
<x-navbar-acente :active="$productType === 'dinner_cruise' ? 'dinner-cruise' : 'yacht-charter'" />

@php
    $statusMap = [
        'new' => ['label' => 'Yeni', 'bg' => 'rgba(148,163,184,.18)', 'color' => '#475569'],
        'offer_sent' => ['label' => 'Teklif Verildi', 'bg' => 'rgba(37,99,235,.14)', 'color' => '#1d4ed8'],
        'revised' => ['label' => 'Revize', 'bg' => 'rgba(249,115,22,.15)', 'color' => '#c2410c'],
        'approved' => ['label' => 'Onaylandi', 'bg' => 'rgba(16,185,129,.16)', 'color' => '#047857'],
        'in_operation' => ['label' => 'Operasyonda', 'bg' => 'rgba(168,85,247,.18)', 'color' => '#7c3aed'],
        'completed' => ['label' => 'Tamamlandi', 'bg' => 'rgba(34,197,94,.16)', 'color' => '#15803d'],
        'cancelled' => ['label' => 'Iptal', 'bg' => 'rgba(239,68,68,.16)', 'color' => '#b91c1c'],
    ];
    $status = $statusMap[$leisureRequest->status] ?? ['label' => $leisureRequest->status, 'bg' => 'rgba(148,163,184,.18)', 'color' => '#475569'];
    $detail = $productType === 'dinner_cruise' ? $leisureRequest->dinnerCruiseDetail : $leisureRequest->yachtDetail;
    $booking = $leisureRequest->booking;
    $timelineField = $leisureRequest->language_preference === 'en' ? 'timeline_en' : 'timeline_tr';
    $offerNoteField = $leisureRequest->language_preference === 'en' ? 'offer_note_en' : 'offer_note_tr';
    $acceptedOfferId = optional($leisureRequest->clientOffers->firstWhere('status', 'accepted'))->id;
@endphp

<div class="container page-shell">
    <div class="hero-card card border-0 mb-4">
        <div class="card-body p-4 p-xl-5">
            <span class="eyebrow"><i class="fas {{ $productType === 'dinner_cruise' ? 'fa-utensils' : 'fa-ship' }}" aria-hidden="true"></i>{{ $leisureRequest->productLabel() }}</span>
            <h1 class="hero-title">{{ $leisureRequest->gtpnr }}</h1>
            <p class="hero-copy">{{ optional($leisureRequest->service_date)->format('d.m.Y') }} tarihinde {{ $leisureRequest->guest_count }} kisilik {{ $productType === 'dinner_cruise' ? 'dinner cruise' : 'yacht charter' }} akisi. Bu ekranda teklif, operasyon ve finans ozetini birlikte goruyorsunuz.</p>
            <div class="hero-chip-row">
                <div class="hero-chip"><i class="fas fa-layer-group" aria-hidden="true"></i>{{ \Illuminate\Support\Str::headline($leisureRequest->package_level ?: 'standard') }}</div>
                <div class="hero-chip"><i class="fas fa-ticket" aria-hidden="true"></i>{{ $leisureRequest->clientOffers->count() }} teklif</div>
                <div class="hero-chip"><i class="fas fa-circle-dot" aria-hidden="true"></i>{{ $status['label'] }}</div>
            </div>
            <div class="hero-actions">
                <a href="{{ route($routePrefix . '.index') }}" class="ghost-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i>Koleksiyona don</a>
                <a href="{{ route($routePrefix . '.create', ['package_level' => $leisureRequest->package_level ?: 'standard']) }}" class="hero-btn"><i class="fas fa-copy" aria-hidden="true"></i>Benzer talep ac</a>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="shell-card card border-0 mb-4">
                <div class="card-body p-4 p-xl-5">
                    <div class="section-kicker">Request overview</div>
                    <h2 class="section-title">Operasyon ozetiniz</h2>
                    <div class="metric-grid mt-3">
                        <div class="metric-card"><div class="metric-label">Misafir</div><div class="metric-value">{{ $leisureRequest->guest_count }} kisi</div></div>
                        <div class="metric-card"><div class="metric-label">Dil</div><div class="metric-value">{{ strtoupper($leisureRequest->language_preference) }}</div></div>
                        <div class="metric-card"><div class="metric-label">Transfer</div><div class="metric-value">{{ $leisureRequest->transfer_required ? 'Var' : 'Yok' }}</div></div>
                        <div class="metric-card"><div class="metric-label">Durum</div><div class="metric-value">{{ $status['label'] }}</div></div>
                    </div>
                    <div class="fact-grid">
                        @if($productType === 'dinner_cruise')
                            <span class="fact-pill"><i class="fas fa-clock" aria-hidden="true"></i>{{ $detail?->session_time ?: 'Seans bekleniyor' }}</span>
                            <span class="fact-pill"><i class="fas fa-location-dot" aria-hidden="true"></i>{{ $detail?->pier_name ?: 'Iskele bekleniyor' }}</span>
                            <span class="fact-pill"><i class="fas fa-champagne-glasses" aria-hidden="true"></i>{{ $detail?->celebration_type ?: 'Kutlama tipi yok' }}</span>
                            <span class="fact-pill"><i class="fas fa-table" aria-hidden="true"></i>{{ $detail?->shared_cruise ? 'Shared duzen' : 'Private masa' }}</span>
                        @else
                            <span class="fact-pill"><i class="fas fa-clock" aria-hidden="true"></i>{{ $detail?->start_time ?: 'Saat bekleniyor' }}</span>
                            <span class="fact-pill"><i class="fas fa-hourglass-half" aria-hidden="true"></i>{{ $detail?->duration_hours ?: '-' }} saat</span>
                            <span class="fact-pill"><i class="fas fa-location-dot" aria-hidden="true"></i>{{ $detail?->marina_name ?: 'Marina bekleniyor' }}</span>
                            <span class="fact-pill"><i class="fas fa-route" aria-hidden="true"></i>{{ $detail?->route_plan ?: 'Rota bekleniyor' }}</span>
                        @endif
                    </div>
                    @if($leisureRequest->notes || $leisureRequest->extra_requests)
                        <div class="mt-4">
                            <div class="section-kicker">Agency notes</div>
                            @if($leisureRequest->notes)<p class="section-copy mb-2"><strong>Not:</strong> {{ $leisureRequest->notes }}</p>@endif
                            @if($leisureRequest->extra_requests)<p class="section-copy mb-0"><strong>Ekstra talepler:</strong> {{ $leisureRequest->extra_requests }}</p>@endif
                        </div>
                    @endif
                </div>
            </div>

            @if($leisureRequest->extras->isNotEmpty())
                <div class="shell-card card border-0 mb-4"><div class="card-body p-4 p-xl-5"><div class="section-kicker">Extras</div><h2 class="section-title">Secili hizmetler</h2><div class="fact-grid">@foreach($leisureRequest->extras as $extra)<span class="fact-pill"><i class="fas fa-check" aria-hidden="true"></i>{{ $extra->title }} - {{ \Illuminate\Support\Str::headline($extra->status ?: '-') }}</span>@endforeach</div></div></div>
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <div class="aside-stack">
                                <div class="shell-card card border-0">
                    <div class="card-body p-4">
                        <div class="section-kicker">Finance</div>
                        <h3 class="section-title">Finans ozetiniz</h3>

                        @if($financeRecord)
                            <div class="metric-card mt-3">
                                <div class="metric-label">Toplam</div>
                                <div class="metric-value">{{ number_format((float) $financeRecord->gross_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div>
                            </div>
                            <div class="metric-card mt-3">
                                <div class="metric-label">Tahsil edilen</div>
                                <div class="metric-value">{{ number_format((float) $financeRecord->paid_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div>
                            </div>
                            <div class="metric-card mt-3">
                                <div class="metric-label">Kalan</div>
                                <div class="metric-value">{{ number_format((float) $financeRecord->remaining_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div>
                            </div>
                        @elseif($booking)
                            <div class="metric-card mt-3">
                                <div class="metric-label">Booking toplami</div>
                                <div class="metric-value">{{ number_format((float) $booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</div>
                            </div>
                            <div class="metric-card mt-3">
                                <div class="metric-label">Kalan</div>
                                <div class="metric-value">{{ number_format((float) $booking->remaining_amount, 2, ',', '.') }} {{ $booking->currency }}</div>
                            </div>
                        @else
                            <p class="small-copy mt-3 mb-0">Teklif kabul edildiginde finans kaydi otomatik olusur.</p>
                        @endif

                        @if($booking && (float) $booking->remaining_amount > 0.0001)
                            <form method="POST" action="{{ route('acente.leisure.payments.gateway-start', $booking) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="action-btn primary w-100">
                                    <i class="fas fa-credit-card" aria-hidden="true"></i>Odemeye Gec (Paynkolay)
                                </button>
                                <div class="small-copy mt-2">Sadece tam odeme: {{ number_format((float) $booking->remaining_amount, 2, ',', '.') }} {{ $booking->currency }}</div>
                            </form>
                        @endif

                        @if($booking && $booking->payments->isNotEmpty())
                            <hr>
                            <div class="small-copy mb-2">Odeme hareketleri</div>
                            @foreach($booking->payments->sortByDesc('id') as $payment)
                                <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-2 small-copy">
                                    <span>#{{ $payment->id }} ? {{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $payment->currency }}</span>
                                    <span class="offer-pill" style="background: rgba(15, 23, 42, .08); color: #0f172a;">{{ \Illuminate\Support\Str::upper($payment->status) }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                @if($leisureRequest->transfer_required)
                    <div class="shell-card card border-0"><div class="card-body p-4"><div class="section-kicker">Transfer</div><h3 class="section-title">Pickup detaylari</h3><div class="fact-grid mt-3"><span class="fact-pill"><i class="fas fa-hotel" aria-hidden="true"></i>{{ $leisureRequest->hotel_name ?: 'Otel bekleniyor' }}</span><span class="fact-pill"><i class="fas fa-map-pin" aria-hidden="true"></i>{{ $leisureRequest->transfer_region ?: 'Bolge bekleniyor' }}</span><span class="fact-pill"><i class="fas fa-user" aria-hidden="true"></i>{{ $leisureRequest->guest_name ?: 'Yolcu adi bekleniyor' }}</span><span class="fact-pill"><i class="fas fa-phone" aria-hidden="true"></i>{{ $leisureRequest->guest_phone ?: 'Telefon bekleniyor' }}</span></div></div></div>
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="section-kicker mb-1">Offers</div><h2 class="section-title">Acenteye sunulan teklifler</h2></div><span class="small-copy">{{ $leisureRequest->clientOffers->count() }} kayit</span></div>

    <div class="d-grid gap-4">
        @forelse($leisureRequest->clientOffers as $offer)
            @php
                $timelineLines = preg_split('/\r\n|\r|\n/', (string) $offer->{$timelineField});
                $includes = array_values(array_filter(array_merge($offer->includes_snapshot[$leisureRequest->language_preference] ?? [], $offer->includes_snapshot['supplier'] ?? [])));
                $excludes = array_values(array_filter(array_merge($offer->excludes_snapshot[$leisureRequest->language_preference] ?? [], $offer->excludes_snapshot['supplier'] ?? [])));
                $isAccepted = $offer->status === 'accepted' || $acceptedOfferId === $offer->id;
            @endphp
            <article class="offer-card">
                <div class="offer-head d-flex flex-column flex-xl-row justify-content-between gap-3">
                    <div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2"><span class="offer-pill"><i class="fas fa-layer-group" aria-hidden="true"></i>{{ $offer->package_label }}</span><span class="offer-pill">{{ $isAccepted ? 'Kabul edildi' : ($offer->status === 'sent' ? 'Aktif teklif' : \Illuminate\Support\Str::headline($offer->status)) }}</span></div>
                        <div>{{ $offer->supplierQuote?->supplier_name ?: 'Ic teklif' }}</div>
                    </div>
                    <div class="text-xl-end"><div class="offer-price">{{ number_format((float) $offer->total_price, 2, ',', '.') }} {{ $offer->currency }}</div><div class="small-copy text-white-50">Kisi basi {{ number_format((float) $offer->per_person_price, 2, ',', '.') }} {{ $offer->currency }}</div></div>
                </div>
                <div class="p-4 p-xl-5">
                    @if($offer->{$offerNoteField})<p class="offer-copy mb-4">{{ $offer->{$offerNoteField} }}</p>@endif
                    <div class="offer-grid mb-4">
                        <div class="offer-block"><h6>Dahil olanlar</h6><ul>@forelse($includes as $item)<li>{{ $item }}</li>@empty<li>Bilgi eklenmedi.</li>@endforelse</ul></div>
                        <div class="offer-block"><h6>Haric olanlar</h6><ul>@forelse($excludes as $item)<li>{{ $item }}</li>@empty<li>Haric kalem belirtilmedi.</li>@endforelse</ul></div>
                        <div class="offer-block"><h6>Ekstralar</h6><ul>@forelse($offer->extras_snapshot ?? [] as $extra)<li>{{ $extra['title'] ?? '-' }} @if(!empty($extra['agency_note']))- {{ $extra['agency_note'] }}@endif</li>@empty<li>Ekstra secim yok.</li>@endforelse</ul></div>
                    </div>
                    <div class="section-kicker">Timeline</div>
                    <div class="timeline-grid mb-4">@foreach($timelineLines as $line) @if(trim((string) $line) !== '')<div class="timeline-item">{{ $line }}</div>@endif @endforeach</div>
                    @if(!empty($offer->media_snapshot))
                        <div class="section-kicker">Sunum gorselleri</div>
                        <div class="media-grid mb-4">
                            @foreach($offer->media_snapshot as $media)
                                @if(($media['media_type'] ?? 'photo') === 'photo')
                                    <div class="media-card"><img src="{{ $media['url'] ?? '' }}" alt="{{ $media['title_tr'] ?? 'Gorsel' }}"><div class="caption">{{ $leisureRequest->language_preference === 'en' ? ($media['title_en'] ?? $media['title_tr'] ?? 'Media') : ($media['title_tr'] ?? 'Gorsel') }}</div></div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="{{ route($routePrefix . '.offers.print', $offer) }}" class="action-btn secondary"><i class="fas fa-print" aria-hidden="true"></i>Yazdir / PDF</a>
                        <a href="{{ $offer->shareUrl($leisureRequest->language_preference ?: 'tr') }}" class="action-btn secondary" target="_blank"><i class="fas fa-link" aria-hidden="true"></i>Guvenli link</a>
                        @if(! $isAccepted && in_array($offer->status, ['sent', 'accepted'], true))
                            <form method="POST" action="{{ route($routePrefix . '.accept', [$leisureRequest, $offer]) }}">@csrf<button type="submit" class="action-btn primary"><i class="fas fa-check" aria-hidden="true"></i>Teklifi kabul et</button></form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="shell-card card border-0"><div class="card-body p-4 p-xl-5"><p class="small-copy mb-0">Henuz acenteye sunulmus teklif yok. Teklif geldikce bu ekranda premium kartlar halinde gorunecek.</p></div></div>
        @endforelse
    </div>
</div>

@include('acente.partials.theme-script')
</body>
</html>
