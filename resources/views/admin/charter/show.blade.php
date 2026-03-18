<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Air Charter Detay #{{ $charterRequest->id }}</title>
    @include('admin.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .card-box { border-radius:12px; border:1px solid rgba(0,0,0,.08); }
        .kpi { font-size:1.1rem; font-weight:700; }
    </style>
</head>
<body class="theme-scope">
@if(auth()->user()->role === 'superadmin')
    <x-navbar-superadmin active="charter" />
@else
    <x-navbar-admin active="charter" />
@endif

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-helicopter me-2 text-danger"></i>Air Charter Detay #{{ $charterRequest->id }}
            </h4>
            <div class="text-muted small">
                {{ strtoupper($charterRequest->transport_type) }} · {{ strtoupper($charterRequest->from_iata) }} - {{ strtoupper($charterRequest->to_iata) }} ·
                {{ optional($charterRequest->departure_date)->format('d.m.Y') }} · PAX {{ $charterRequest->pax }}
            </div>
        </div>
        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">Listeye Don</a>
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

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">Talep ve AI Ozeti</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><div class="text-muted small">Durum</div><div class="kpi">{{ $charterRequest->status }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">Requester</div><div class="kpi">{{ $charterRequest->requester_type }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">PAX</div><div class="kpi">{{ $charterRequest->pax }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">Tarih</div><div class="kpi">{{ optional($charterRequest->departure_date)->format('d.m.Y') }}</div></div>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-12 col-md-4"><div class="text-muted small">AI Model</div><div class="fw-semibold">{{ $charterRequest->ai_suggested_model ?: '-' }}</div></div>
                        <div class="col-12 col-md-4"><div class="text-muted small">AI Fiyat Araligi</div><div class="fw-semibold">
                            @if($charterRequest->ai_price_min !== null)
                                {{ number_format((float) $charterRequest->ai_price_min, 0, ',', '.') }} - {{ number_format((float) $charterRequest->ai_price_max, 0, ',', '.') }} {{ $charterRequest->ai_currency }}
                            @else
                                -
                            @endif
                        </div></div>
                        <div class="col-12 col-md-4"><div class="text-muted small">Risk Flag</div><div class="fw-semibold">{{ collect($charterRequest->ai_risk_flags)->implode(' | ') ?: '-' }}</div></div>
                        <div class="col-12"><div class="text-muted small">AI Yorum</div><div>{{ $charterRequest->ai_comment ?: '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">RFQ / Operasyon</div>
                <div class="card-body d-grid gap-2">
                    <form method="POST" action="{{ route($routePrefix . '.send-rfq', $charterRequest) }}">
                        @csrf
                        <button class="btn btn-primary w-100">RFQ Dagitimini Baslat</button>
                    </form>
                    @if($charterRequest->booking)
                        <div class="p-2 border rounded">
                            <div class="small text-muted">Booking Durumu</div>
                            <div class="fw-bold">{{ $charterRequest->booking->status }}</div>
                            <div class="small">Toplam: {{ number_format((float) $charterRequest->booking->total_amount, 2, ',', '.') }}</div>
                            <div class="small">Kalan: {{ number_format((float) $charterRequest->booking->remaining_amount, 2, ',', '.') }}</div>
                        </div>
                        <form method="POST" action="{{ route($routePrefix . '.bookings.start-operation', $charterRequest->booking) }}">
                            @csrf
                            <button class="btn btn-success w-100">Operasyonu Baslat</button>
                        </form>
                    @else
                        <div class="text-muted small">Booking henuz yok. Acente teklif kabul ettiginde olusur.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-box shadow-sm mb-3">
        <div class="card-header fw-bold">Ekstra Fiyatlama</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Baslik</th><th>Not</th><th>Fiyat</th><th>Durum</th><th></th></tr></thead>
                    <tbody>
                    @forelse($charterRequest->extras as $extra)
                        <tr>
                            <td>{{ $extra->title }}</td>
                            <td>{{ $extra->agency_note ?: '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route($routePrefix . '.extras.price', [$charterRequest, $extra]) }}" class="d-flex flex-wrap gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" step="0.01" min="0" name="admin_price" class="form-control form-control-sm" style="max-width:130px;" value="{{ $extra->admin_price }}">
                                    <input type="text" name="currency" class="form-control form-control-sm" style="max-width:80px;" value="{{ $extra->currency }}">
                                    <select name="status" class="form-select form-select-sm" style="max-width:150px;">
                                        <option value="pending_pricing" @selected($extra->status === 'pending_pricing')>pending_pricing</option>
                                        <option value="priced" @selected($extra->status === 'priced')>priced</option>
                                        <option value="rejected" @selected($extra->status === 'rejected')>rejected</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Kaydet</button>
                                </form>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $extra->status }}</span></td>
                            <td></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted p-3">Ekstra kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">Supplier Teklifi Ekle</div>
                <div class="card-body">
                    <form method="POST" action="{{ route($routePrefix . '.supplier-quotes.store', $charterRequest) }}" class="row g-2">
                        @csrf
                        <div class="col-12 col-md-6"><input name="supplier_name" class="form-control" placeholder="Supplier adi" required></div>
                        <div class="col-12 col-md-6"><input name="model_name" class="form-control" placeholder="Model"></div>
                        <div class="col-12"><input name="aircraft_image_url" class="form-control" placeholder="Image URL"></div>
                        <div class="col-12 col-md-4"><input type="number" step="0.01" min="1" name="supplier_price" class="form-control" placeholder="Fiyat" required></div>
                        <div class="col-12 col-md-3"><input name="currency" class="form-control" value="EUR" required></div>
                        <div class="col-12"><textarea name="supplier_note" class="form-control" rows="2" placeholder="Supplier notu"></textarea></div>
                        <div class="col-12"><textarea name="whatsapp_text" class="form-control" rows="2" placeholder="WhatsApp metni"></textarea></div>
                        <div class="col-12"><button class="btn btn-primary">Supplier Teklif Kaydet</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">Acente Satis Teklifi Uret</div>
                <div class="card-body">
                    <form method="POST" action="{{ route($routePrefix . '.sales-quotes.store', $charterRequest) }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Supplier teklif sec</label>
                            <select name="supplier_quote_id" class="form-select" required>
                                <option value="">Seciniz</option>
                                @foreach($charterRequest->supplierQuotes as $sq)
                                    <option value="{{ $sq->id }}">#{{ $sq->id }} · {{ $sq->supplier_name }} · {{ number_format((float) $sq->supplier_price, 2, ',', '.') }} {{ $sq->currency }} · Skor {{ $sq->ai_score }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6"><input type="number" step="0.01" min="0" name="override_markup_percent" class="form-control" placeholder="Override markup % (opsiyonel)"></div>
                        <div class="col-12 col-md-6"><input type="number" step="0.01" min="0" name="override_min_profit" class="form-control" placeholder="Override min kar (opsiyonel)"></div>
                        <div class="col-12"><textarea name="override_reason" class="form-control" rows="2" placeholder="Override nedeni"></textarea></div>
                        <div class="col-12"><button class="btn btn-success">Satis Teklifi Uret</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-box shadow-sm mb-3">
        <div class="card-header fw-bold">Supplier Teklifler</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Supplier</th><th>Model</th><th>Fiyat</th><th>AI Skor</th><th>Durum</th></tr></thead>
                    <tbody>
                    @forelse($charterRequest->supplierQuotes as $sq)
                        <tr>
                            <td>{{ $sq->id }}</td>
                            <td>{{ $sq->supplier_name }}</td>
                            <td>{{ $sq->model_name ?: '-' }}</td>
                            <td>{{ number_format((float) $sq->supplier_price, 2, ',', '.') }} {{ $sq->currency }}</td>
                            <td>{{ $sq->ai_score }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $sq->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted p-3">Supplier teklifi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-box shadow-sm mb-3">
        <div class="card-header fw-bold">Acente Satis Teklifleri</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Supplier</th><th>Base</th><th>Markup %</th><th>Satis</th><th>Durum</th></tr></thead>
                    <tbody>
                    @forelse($charterRequest->salesQuotes as $sq)
                        <tr>
                            <td>{{ $sq->id }}</td>
                            <td>{{ $sq->supplierQuote?->supplier_name ?: '-' }}</td>
                            <td>{{ number_format((float) $sq->base_supplier_price, 2, ',', '.') }} {{ $sq->currency }}</td>
                            <td>{{ $sq->markup_percent }}</td>
                            <td>{{ number_format((float) $sq->sale_price, 2, ',', '.') }} {{ $sq->currency }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $sq->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted p-3">Acente satis teklifi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($charterRequest->booking)
        <div class="card card-box shadow-sm">
            <div class="card-header fw-bold">Odeme Kayitlari</div>
            <div class="card-body">
                <form method="POST" action="{{ route($routePrefix . '.payments.store', $charterRequest->booking) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12 col-md-2">
                        <select name="method" class="form-select" required>
                            <option value="card">card</option>
                            <option value="bank_transfer">bank_transfer</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2"><input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="Tutar" required></div>
                    <div class="col-12 col-md-2"><input name="currency" class="form-control" value="EUR" required></div>
                    <div class="col-12 col-md-2"><input name="provider" class="form-control" placeholder="Provider"></div>
                    <div class="col-12 col-md-2"><input name="provider_reference" class="form-control" placeholder="Ref"></div>
                    <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Odeme Ekle</button></div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>#</th><th>Method</th><th>Tutar</th><th>Durum</th><th>Onay</th></tr></thead>
                        <tbody>
                        @forelse($charterRequest->booking->payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->method }}</td>
                                <td>{{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $payment->currency }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $payment->status }}</span></td>
                                <td class="text-end">
                                    @if($payment->status === 'pending')
                                        <div class="d-inline-flex gap-1">
                                            <form method="POST" action="{{ route($routePrefix . '.payments.approve', $payment) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success">Onayla</button>
                                            </form>
                                            <form method="POST" action="{{ route($routePrefix . '.payments.reject', $payment) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger">Reddet</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted p-3">Odeme kaydi yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
