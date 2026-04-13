<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>{{ $leisureRequest->gtpnr }} - {{ $leisureRequest->productLabel() }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .leisure-admin-show .shell-card { border-radius: 18px; border: 1px solid rgba(148, 163, 184, .2); }
        .leisure-admin-show .brief-box { white-space: pre-wrap; font-family: Consolas, monospace; font-size: .84rem; border: 1px dashed rgba(148, 163, 184, .35); border-radius: 14px; padding: 1rem; background: rgba(248, 250, 252, .85); }
        .leisure-admin-show .thumb-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
        .leisure-admin-show .thumb-card { border: 1px solid #dbe3f0; border-radius: 14px; overflow: hidden; }
        .leisure-admin-show .thumb-card img { width: 100%; height: 140px; object-fit: cover; display: block; }
        .leisure-admin-show .thumb-card .caption { padding: .65rem; font-size: .8rem; }
        .leisure-admin-show .pay-row { display: flex; justify-content: space-between; align-items: center; border-radius: 8px; padding: .3rem .6rem; margin-bottom: .4rem; font-size: .85rem; }
        .leisure-admin-show .pay-row.approved { background: rgba(18,163,84,.1); border: 1px solid rgba(18,163,84,.25); }
        .leisure-admin-show .pay-row.pending  { background: rgba(245,166,35,.08); border: 1px solid rgba(245,166,35,.2); }
        .leisure-admin-show .pay-row.rejected { background: rgba(220,53,69,.05); border: 1px solid rgba(220,53,69,.12); color: #999; }
        html[data-theme="dark"] .leisure-admin-show .shell-card { border-color: #2d4371; }
        html[data-theme="dark"] .leisure-admin-show .brief-box,
        html[data-theme="dark"] .leisure-admin-show .thumb-card { border-color: #2d4371; background: #0f1d36; }
        @media (max-width: 991.98px) { .leisure-admin-show .thumb-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="theme-scope leisure-admin-show">
@if($panelRole === 'superadmin')
    <x-navbar-superadmin :active="$navActive" />
@else
    <x-navbar-admin :active="$navActive" />
@endif

@php
    $isDinner  = $productType === 'dinner_cruise';
    $detail    = $isDinner ? $leisureRequest->dinnerCruiseDetail : $leisureRequest->yachtDetail;
    $booking   = $leisureRequest->booking;
    $isDirectBooking = $booking && $leisureRequest->clientOffers->whereIn('status',['accepted','sent'])->isNotEmpty();
@endphp

<div class="container-fluid py-4 px-3 px-md-4">

    {{-- Başlık --}}
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">{{ $leisureRequest->gtpnr }} · {{ $leisureRequest->productLabel() }}</h3>
            <div class="text-muted small">
                {{ optional($leisureRequest->service_date)->format('d.m.Y') }} ·
                {{ $leisureRequest->guest_count }} kisi ·
                <span class="badge bg-{{ $leisureRequest->status === 'in_operation' ? 'success' : ($leisureRequest->status === 'approved' ? 'primary' : 'secondary') }} text-white">
                    {{ \Illuminate\Support\Str::headline($leisureRequest->status) }}
                </span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">Listeye Don</a>
            @if($panelRole === 'superadmin')
                <a href="{{ route('superadmin.leisure.settings.index') }}" class="btn btn-outline-primary btn-sm">Leisure Ayarlari</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ── Üst satır: Rezervasyon Özeti | Finans & Ödeme ── --}}
    <div class="row g-4 mb-4">

        {{-- Rezervasyon Detayları --}}
        <div class="col-12 col-xl-7">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold">Rezervasyon Detaylari</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Acente</div>
                            <div class="fw-semibold">{{ $leisureRequest->user->agency_name ?? $leisureRequest->user->name ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Paket</div>
                            <div class="fw-semibold">{{ \Illuminate\Support\Str::headline($leisureRequest->package_level ?: 'standard') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Transfer</div>
                            <div class="fw-semibold">{{ $leisureRequest->transfer_required ? 'Var' : 'Yok' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Dil</div>
                            <div class="fw-semibold">{{ strtoupper($leisureRequest->language_preference) }}</div>
                        </div>

                        @if($isDinner && $detail)
                            <div class="col-6 col-md-4">
                                <div class="text-muted small">Seans</div>
                                <div class="fw-semibold">{{ $detail->session_time ?: '-' }}</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-muted small">Iskele</div>
                                <div class="fw-semibold">{{ $detail->pier_name ?: '-' }}</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-muted small">Cruise Tipi</div>
                                <div class="fw-semibold">{{ $detail->shared_cruise ? 'Shared' : 'Private masa' }}</div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="text-muted small">Yetiskin</div>
                                <div class="fw-semibold">{{ $detail->adult_count ?? '-' }}</div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="text-muted small">Cocuk</div>
                                <div class="fw-semibold">{{ $detail->child_count ?? '0' }}</div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="text-muted small">Bebek</div>
                                <div class="fw-semibold">{{ $detail->infant_count ?? '0' }}</div>
                            </div>
                            @if($detail->celebration_type)
                            <div class="col-12 col-md-6">
                                <div class="text-muted small">Kutlama Tipi</div>
                                <div class="fw-semibold">{{ $detail->celebration_type }}</div>
                            </div>
                            @endif
                        @else
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Saat</div>
                                <div class="fw-semibold">{{ $detail?->start_time ?: '-' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Sure</div>
                                <div class="fw-semibold">{{ $detail?->duration_hours ?: '-' }} saat</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Marina</div>
                                <div class="fw-semibold">{{ $detail?->marina_name ?: '-' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Event</div>
                                <div class="fw-semibold">{{ $detail?->event_type ?: '-' }}</div>
                            </div>
                        @endif

                        {{-- Misafir bilgileri — HER ZAMAN göster --}}
                        @if($leisureRequest->guest_name || $leisureRequest->guest_phone)
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Misafir / Yetkili</div>
                            <div class="fw-semibold">{{ $leisureRequest->guest_name ?: '-' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Telefon</div>
                            <div class="fw-semibold">{{ $leisureRequest->guest_phone ?: '-' }}</div>
                        </div>
                        @endif

                        {{-- Transfer varsa otel/bölge --}}
                        @if($leisureRequest->transfer_required)
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Otel</div>
                            <div class="fw-semibold">{{ $leisureRequest->hotel_name ?: '-' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Bolge</div>
                            <div class="fw-semibold">{{ $leisureRequest->transfer_region ?: '-' }}</div>
                        </div>
                        @endif
                    </div>

                    @if($leisureRequest->notes || $leisureRequest->extra_requests)
                        <hr>
                        @if($leisureRequest->notes)<div class="mb-2 small"><strong>Not:</strong> {{ $leisureRequest->notes }}</div>@endif
                        @if($leisureRequest->extra_requests)<div class="small"><strong>Ekstra Talepler:</strong> {{ $leisureRequest->extra_requests }}</div>@endif
                    @endif

                    @if($leisureRequest->extras->isNotEmpty())
                        <hr>
                        <div class="text-muted small mb-1">Ekstra Secenekler</div>
                        @foreach($leisureRequest->extras as $extra)
                            <div class="d-flex gap-2 align-items-center small py-1 border-bottom">
                                <i class="fas fa-plus-circle text-primary fa-xs"></i>
                                <span>{{ $extra->title }}</span>
                                @if($extra->agency_note)<span class="text-muted">({{ $extra->agency_note }})</span>@endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Finans & Ödeme --}}
        <div class="col-12 col-xl-5">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold">Finans & Odeme</div>
                <div class="card-body">

                    {{-- Finans özeti --}}
                    @if($financeRecord)
                        <div class="row g-2 mb-3">
                            <div class="col-4 text-center p-2 rounded-3" style="background:rgba(148,163,184,.1)">
                                <div class="text-muted small">Toplam</div>
                                <div class="fw-bold">{{ number_format((float) $financeRecord->gross_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div>
                            </div>
                            <div class="col-4 text-center p-2 rounded-3" style="background:rgba(18,163,84,.08)">
                                <div class="text-muted small">Odenen</div>
                                <div class="fw-bold text-success">{{ number_format((float) $financeRecord->paid_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div>
                            </div>
                            <div class="col-4 text-center p-2 rounded-3" style="background:rgba(220,53,69,.06)">
                                <div class="text-muted small">Kalan</div>
                                <div class="fw-bold {{ (float)$financeRecord->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $financeRecord->remaining_amount, 2, ',', '.') }} {{ $financeRecord->currency }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Ödeme başlat butonu --}}
                    @if($booking && (float) $booking->remaining_amount > 0.0001)
                        <form method="POST" action="{{ route($panelRole . '.leisure.payments.gateway-start', $booking) }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-credit-card me-2"></i>Odemeye Gec (Paynkolay)
                                · {{ number_format((float) $booking->remaining_amount, 2, ',', '.') }} {{ $booking->currency }}
                            </button>
                        </form>
                    @endif

                    {{-- Ödeme hareketleri --}}
                    @if($booking && $booking->payments->isNotEmpty())
                        <div class="text-muted small mb-2">Odeme hareketleri</div>
                        @foreach($booking->payments->sortByDesc('id') as $payment)
                            @php $cls = match($payment->status) { 'approved','paid','completed' => 'approved', 'pending' => 'pending', default => 'rejected' }; @endphp
                            <div class="pay-row {{ $cls }}">
                                <span>#{{ $payment->id }} · {{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $payment->currency }}
                                    @if($payment->provider === 'simulation') <span class="text-muted">(sim)</span>@endif
                                </span>
                                <span class="fw-bold text-uppercase small">{{ $payment->status }}</span>
                            </div>
                        @endforeach
                    @endif

                    {{-- Supplier Brief (collapse) --}}
                    <hr>
                    <a class="small text-muted text-decoration-none d-flex justify-content-between align-items-center"
                       data-bs-toggle="collapse" href="#supplierBriefCollapse" role="button">
                        <span><i class="fas fa-clipboard-list me-1"></i>Supplier Brief</span>
                        <i class="fas fa-chevron-down fa-xs"></i>
                    </a>
                    <div class="collapse mt-2" id="supplierBriefCollapse">
                        <div class="brief-box">{{ $supplierBrief }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Orta satır: Operasyon & Client Offer ── --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span>Client Offer Kayitlari</span>
                    @if($booking && (float) $booking->remaining_amount <= 0.0001 && $booking->status !== 'in_operation')
                        <form method="POST" action="{{ route($routePrefix . '.start-operation', $leisureRequest) }}">
                            @csrf
                            <button class="btn btn-sm btn-success">
                                <i class="fas fa-play me-1"></i>Operasyonu Baslat
                            </button>
                        </form>
                    @elseif($booking?->status === 'in_operation')
                        <span class="badge bg-success">Operasyonda</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Paket</th><th>Fiyat</th><th>Durum</th><th></th></tr></thead>
                            <tbody>
                            @forelse($leisureRequest->clientOffers as $offer)
                                <tr>
                                    <td>{{ $offer->package_label }}</td>
                                    <td>{{ number_format((float) $offer->total_price, 2, ',', '.') }} {{ $offer->currency }}</td>
                                    <td>
                                        <span class="badge bg-{{ $offer->status === 'accepted' ? 'success' : ($offer->status === 'sent' ? 'primary' : 'secondary') }}">
                                            {{ \Illuminate\Support\Str::headline($offer->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($offer->status !== 'accepted')
                                            <a href="{{ $offer->shareUrl($leisureRequest->language_preference ?: 'tr') }}" target="_blank" class="small">Paylasim Linki</a>
                                        @else
                                            <span class="text-muted small">Odendi</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Henuz client offer olusturulmadi.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold">Kayitli Supplier Teklifleri</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Tedarikci</th><th>Paket</th><th>Maliyet</th><th>Durum</th></tr></thead>
                            <tbody>
                            @forelse($leisureRequest->supplierQuotes as $quote)
                                <tr>
                                    <td>{{ $quote->supplier_name }}</td>
                                    <td>{{ $quote->supplier_package_name ?: '-' }}</td>
                                    <td>{{ number_format((float) $quote->cost_total, 2, ',', '.') }} {{ $quote->currency }}</td>
                                    <td>{{ \Illuminate\Support\Str::headline($quote->status ?: '-') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Tedarikci teklifi yok.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Alt satır: Teklif/Offer formları (direkt booking'de collapse ile gizli) ── --}}
    <div class="mb-2">
        <a class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" href="#operasyonFormlar" role="button">
            <i class="fas fa-tools me-1"></i>
            {{ $isDirectBooking ? 'Tedarikci / Manuel Offer Formlari (gizli — direkt booking)' : 'Tedarikci Teklifi & Client Offer Formlari' }}
            <i class="fas fa-chevron-{{ $isDirectBooking ? 'right' : 'down' }} fa-xs ms-1"></i>
        </a>
    </div>

    <div class="collapse {{ $isDirectBooking ? '' : 'show' }}" id="operasyonFormlar">
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card shell-card shadow-sm h-100">
                    <div class="card-header fw-bold">Tedarikci Teklifi Gir</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route($routePrefix . '.supplier-quotes.store', $leisureRequest) }}" class="row g-3">
                            @csrf
                            <div class="col-12 col-md-6"><label class="form-label">Tedarikci</label><input type="text" name="supplier_name" class="form-control" required></div>
                            <div class="col-12 col-md-6"><label class="form-label">Iletisim Kisi</label><input type="text" name="supplier_contact_name" class="form-control"></div>
                            <div class="col-12 col-md-6"><label class="form-label">E-posta</label><input type="email" name="supplier_email" class="form-control"></div>
                            <div class="col-12 col-md-6"><label class="form-label">Telefon</label><input type="text" name="supplier_phone" class="form-control"></div>
                            <div class="col-12 col-md-6"><label class="form-label">Supplier Paket Adi</label><input type="text" name="supplier_package_name" class="form-control"></div>
                            <div class="col-12 col-md-3"><label class="form-label">Maliyet</label><input type="number" step="0.01" min="0" name="cost_total" class="form-control" required></div>
                            <div class="col-12 col-md-3"><label class="form-label">Para Birimi</label><input type="text" name="currency" class="form-control" value="EUR" required></div>
                            <div class="col-12 col-md-6"><label class="form-label">Dahil Olanlar</label><textarea name="includes_text" class="form-control" rows="3" placeholder="Her satira bir madde"></textarea></div>
                            <div class="col-12 col-md-6"><label class="form-label">Haric Olanlar</label><textarea name="excludes_text" class="form-control" rows="3" placeholder="Her satira bir madde"></textarea></div>
                            <div class="col-12"><label class="form-label">Supplier & Operasyon Notu</label><textarea name="supplier_note" class="form-control" rows="2"></textarea></div>
                            <div class="col-12"><button class="btn btn-primary">Tedarikci Teklifini Kaydet</button></div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card shell-card shadow-sm h-100">
                    <div class="card-header fw-bold">Client Offer Olustur</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route($routePrefix . '.client-offers.store', $leisureRequest) }}" class="row g-3">
                            @csrf
                            <div class="col-12 col-md-6">
                                <label class="form-label">Supplier Teklifi</label>
                                <select name="supplier_quote_id" class="form-select">
                                    <option value="">Ic teklif / supplier secme</option>
                                    @foreach($leisureRequest->supplierQuotes as $quote)
                                        <option value="{{ $quote->id }}">{{ $quote->supplier_name }} · {{ number_format((float) $quote->cost_total, 2, ',', '.') }} {{ $quote->currency }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Paket Sablonu</label>
                                <select name="package_template_id" class="form-select" required>
                                    @foreach($packageTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name_tr }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4"><label class="form-label">Satis Fiyati</label><input type="number" step="0.01" min="0" name="total_price" class="form-control" required></div>
                            <div class="col-12 col-md-2"><label class="form-label">Birim</label><input type="text" name="currency" value="EUR" class="form-control" required></div>
                            <div class="col-12 col-md-6"><label class="form-label">TR Teklif Notu</label><textarea name="offer_note_tr" class="form-control" rows="2"></textarea></div>
                            <div class="col-12"><label class="form-label">EN Teklif Notu</label><textarea name="offer_note_en" class="form-control" rows="2"></textarea></div>
                            <div class="col-12">
                                <label class="form-label d-block">Medya Secimi</label>
                                <div class="thumb-grid">
                                    @foreach($mediaAssets as $asset)
                                        <label class="thumb-card">
                                            <input type="checkbox" class="form-check-input m-2" name="media_asset_ids[]" value="{{ $asset->id }}">
                                            @if($asset->media_type === 'photo')
                                                <img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center" style="height: 140px; background: rgba(37, 99, 235, .08);">Video</div>
                                            @endif
                                            <div class="caption">{{ $asset->title_tr }}</div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12"><button class="btn btn-success">Acente Teklifini Olustur</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@include('admin.partials.theme-script')
</body>
</html>
