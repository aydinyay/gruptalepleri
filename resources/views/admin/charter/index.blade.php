<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Air Charter Talepler</title>
    @include('admin.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .card-box { border-radius:12px; border:1px solid rgba(0,0,0,.08); }
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
            <h4 class="mb-1 fw-bold"><i class="fas fa-helicopter me-2 text-danger"></i>Air Charter Talepler</h4>
            <div class="text-muted small">Jet, helikopter ve charter ucak taleplerini tek ekrandan yonet.</div>
        </div>
        @if(auth()->user()->role === 'superadmin')
            <a href="{{ route('superadmin.charter.packages.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-box-open me-1"></i>Hazir Paketleri Yonet
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card card-box shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-12 col-md-3">
                    <label class="form-label">Transport Type</label>
                    <select name="transport_type" class="form-select">
                        <option value="">Tumleri</option>
                        <option value="jet" @selected($transportType === 'jet')>Jet</option>
                        <option value="helicopter" @selected($transportType === 'helicopter')>Helikopter</option>
                        <option value="airliner" @selected($transportType === 'airliner')>Charter Ucak</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Durum</label>
                    <input name="status" class="form-control" value="{{ $status }}" placeholder="lead, ai_quoted, rfq_sent...">
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-primary">Filtrele</button>
                    <a class="btn btn-outline-secondary ms-1" href="{{ route($routePrefix . '.index') }}">Sifirla</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-box shadow-sm">
        <div class="card-header fw-bold">Kayitlar</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Musteri</th>
                        <th>Rota</th>
                        <th>Hazir Paket</th>
                        <th>PAX</th>
                        <th>Durum</th>
                        <th>AI Aralik</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper($item->transport_type) }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $item->name ?: ($item->user?->name ?: '-') }}</div>
                                <div class="small text-muted">{{ $item->email ?: ($item->user?->email ?: '-') }}</div>
                            </td>
                            <td>{{ strtoupper($item->from_iata) }} - {{ strtoupper($item->to_iata) }}</td>
                            <td>
                                @if($item->preset_package_title)
                                    <div class="fw-semibold small">{{ $item->preset_package_title }}</div>
                                    <div class="small text-muted">
                                        @if($item->preset_package_price !== null)
                                            {{ number_format((float) $item->preset_package_price, 0, ',', '.') }} {{ $item->preset_package_currency ?: 'EUR' }}
                                        @else
                                            Paket fiyat bilgisi yok
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>{{ $item->pax }}</td>
                            <td><span class="badge bg-secondary">{{ $item->status }}</span></td>
                            <td>
                                @if($item->ai_price_min !== null)
                                    {{ number_format((float) $item->ai_price_min, 0, ',', '.') }} - {{ number_format((float) $item->ai_price_max, 0, ',', '.') }} {{ $item->ai_currency }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route($routePrefix . '.show', $item) }}">Detay</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted p-3">Charter talebi bulunamadi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
</div>

@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
