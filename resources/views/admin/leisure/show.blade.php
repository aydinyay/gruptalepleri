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
    $isDinner = $productType === 'dinner_cruise';
    $detail = $isDinner ? $leisureRequest->dinnerCruiseDetail : $leisureRequest->yachtDetail;
@endphp

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">{{ $leisureRequest->gtpnr }} · {{ $leisureRequest->productLabel() }}</h3>
            <div class="text-muted small">{{ optional($leisureRequest->service_date)->format('d.m.Y') }} · {{ $leisureRequest->guest_count }} kisi · {{ \Illuminate\Support\Str::headline($leisureRequest->status) }}</div>
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
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold">Talep ve Transfer Ozeti</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><strong>Acente</strong><div>{{ $leisureRequest->user->agency_name ?? $leisureRequest->user->name ?? '-' }}</div></div>
                        <div class="col-6 col-md-3"><strong>Paket</strong><div>{{ \Illuminate\Support\Str::headline($leisureRequest->package_level ?: 'standard') }}</div></div>
                        <div class="col-6 col-md-3"><strong>Transfer</strong><div>{{ $leisureRequest->transfer_required ? 'Var' : 'Yok' }}</div></div>
                        <div class="col-6 col-md-3"><strong>Dil</strong><div>{{ strtoupper($leisureRequest->language_preference) }}</div></div>
                        @if($leisureRequest->transfer_required)
                            <div class="col-12 col-md-4"><strong>Bolge</strong><div>{{ $leisureRequest->transfer_region ?: '-' }}</div></div>
                            <div class="col-12 col-md-4"><strong>Otel</strong><div>{{ $leisureRequest->hotel_name ?: '-' }}</div></div>
                            <div class="col-12 col-md-4"><strong>Misafir / Telefon</strong><div>{{ $leisureRequest->guest_name ?: '-' }} @if($leisureRequest->guest_phone) · {{ $leisureRequest->guest_phone }} @endif</div></div>
                        @endif
                        @if($isDinner)
                            <div class="col-12 col-md-4"><strong>Seans</strong><div>{{ $detail?->session_time ?: '-' }}</div></div>
                            <div class="col-12 col-md-4"><strong>Iskele</strong><div>{{ $detail?->pier_name ?: '-' }}</div></div>
                            <div class="col-12 col-md-4"><strong>Cruise Tipi</strong><div>{{ $detail?->shared_cruise ? 'Shared' : 'Private masa' }}</div></div>
                        @else
                            <div class="col-12 col-md-3"><strong>Saat</strong><div>{{ $detail?->start_time ?: '-' }}</div></div>
                            <div class="col-12 col-md-3"><strong>Sure</strong><div>{{ $detail?->duration_hours ?: '-' }} saat</div></div>
                            <div class="col-12 col-md-3"><strong>Marina</strong><div>{{ $detail?->marina_name ?: '-' }}</div></div>
                            <div class="col-12 col-md-3"><strong>Event</strong><div>{{ $detail?->event_type ?: '-' }}</div></div>
                        @endif
                    </div>
                    @if($leisureRequest->notes || $leisureRequest->extra_requests)
                        <hr>
                        @if($leisureRequest->notes)<div class="mb-2"><strong>Not:</strong> {{ $leisureRequest->notes }}</div>@endif
                        @if($leisureRequest->extra_requests)<div><strong>Ekstra Talepler:</strong> {{ $leisureRequest->extra_requests }}</div>@endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold">Supplier Brief</div>
                <div class="card-body">
                    <div class="brief-box">{{ $supplierBrief }}</div>
                    @if($financeRecord)
                        <hr>
                        <div class="small text-muted mb-2">Finans Ozeti</div>
                        <div class="row g-2 small">
                            <div class="col-4"><strong>Toplam</strong><div>{{ number_format((float) $financeRecord->gross_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div></div>
                            <div class="col-4"><strong>Odenen</strong><div>{{ number_format((float) $financeRecord->paid_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div></div>
                            <div class="col-4"><strong>Kalan</strong><div>{{ number_format((float) $financeRecord->remaining_amount, 2, ',', '.') }} {{ $financeRecord->currency }}</div></div>
                        </div>
                    @endif
                    @if($leisureRequest->booking && (float) $leisureRequest->booking->remaining_amount > 0.0001)
                        <hr>
                        <form method="POST" action="{{ route($panelRole . '.leisure.payments.gateway-start', $leisureRequest->booking) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Odemeye Gec (Paynkolay)</button>
                        </form>
                        <div class="small text-muted mt-1">
                            Tam odeme: {{ number_format((float) $leisureRequest->booking->remaining_amount, 2, ',', '.') }} {{ $leisureRequest->booking->currency }}
                        </div>
                    @endif
                    @if($leisureRequest->booking && $leisureRequest->booking->payments->isNotEmpty())
                        <hr>
                        <div class="small text-muted mb-2">Odeme hareketleri</div>
                        @foreach($leisureRequest->booking->payments->sortByDesc('id') as $payment)
                            <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-2 small">
                                <span>#{{ $payment->id }} · {{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $payment->currency }}</span>
                                <span class="badge bg-light text-dark border">{{ \Illuminate\Support\Str::upper($payment->status) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

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
                        <div class="col-12 col-md-6"><label class="form-label">Dahil Olanlar</label><textarea name="includes_text" class="form-control" rows="4" placeholder="Her satira bir madde"></textarea></div>
                        <div class="col-12 col-md-6"><label class="form-label">Haric Olanlar</label><textarea name="excludes_text" class="form-control" rows="4" placeholder="Her satira bir madde"></textarea></div>
                        <div class="col-12"><label class="form-label">Supplier Notu</label><textarea name="supplier_note" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label">Operasyon Notu</label><textarea name="operation_note" class="form-control" rows="2"></textarea></div>
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

    <div class="row g-4">
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
                                <tr><td colspan="4" class="text-center text-muted py-4">Henuz supplier teklifi girilmedi.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card shell-card shadow-sm h-100">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span>Client Offer Kayitlari</span>
                    @if($leisureRequest->booking && (float) $leisureRequest->booking->remaining_amount <= 0.0001 && $leisureRequest->booking->status !== 'in_operation')
                        <form method="POST" action="{{ route($routePrefix . '.start-operation', $leisureRequest) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-success">Operasyonu Baslat</button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Paket</th><th>Fiyat</th><th>Durum</th><th>Link</th></tr></thead>
                            <tbody>
                            @forelse($leisureRequest->clientOffers as $offer)
                                <tr>
                                    <td>{{ $offer->package_label }}</td>
                                    <td>{{ number_format((float) $offer->total_price, 2, ',', '.') }} {{ $offer->currency }}</td>
                                    <td>{{ \Illuminate\Support\Str::headline($offer->status) }}</td>
                                    <td><a href="{{ $offer->shareUrl($leisureRequest->language_preference ?: 'tr') }}" target="_blank">Paylasim Linki</a></td>
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
    </div>
</div>

@include('admin.partials.theme-script')
</body>
</html>
