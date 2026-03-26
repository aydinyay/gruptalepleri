<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Air Charter Taleplerim - GrupTalepleri</title>
    @include('acente.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .card-box { border-radius:12px; border:1px solid rgba(0,0,0,.08); }
        .status-pill { font-size:.74rem; border-radius:999px; padding:4px 10px; }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="charter" />

@php
    $statusMap = [
        'lead' => ['label' => 'Yeni Talep', 'class' => 'bg-secondary'],
        'ai_quoted' => ['label' => 'On Degerlendirildi', 'class' => 'bg-primary'],
        'rfq_sent' => ['label' => 'RFQ Gonderildi', 'class' => 'bg-info text-dark'],
        'quoted_to_agency' => ['label' => 'Acenteye Teklif Sunuldu', 'class' => 'bg-warning text-dark'],
        'pending_payment' => ['label' => 'Odeme Bekliyor', 'class' => 'bg-warning text-dark'],
        'partial_paid' => ['label' => 'Kismi Odeme', 'class' => 'bg-warning text-dark'],
        'paid' => ['label' => 'Odendi', 'class' => 'bg-success'],
        'operation_started' => ['label' => 'Operasyon Basladi', 'class' => 'bg-success'],
    ];
@endphp

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-helicopter me-2 text-danger"></i>Air Charter Taleplerim</h4>
            <div class="text-muted small">Actiginiz charter taleplerini tek ekranda takip edin.</div>
        </div>
        <a href="{{ route('acente.charter.create') }}" class="btn btn-danger btn-sm">
            <i class="fas fa-plus me-1"></i>Yeni Charter Talebi
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card card-box shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label">Ucus Turu</label>
                    <select name="transport_type" class="form-select">
                        <option value="">Tumu</option>
                        <option value="jet" @selected($transportType === 'jet')>Private Jet</option>
                        <option value="helicopter" @selected($transportType === 'helicopter')>Helikopter</option>
                        <option value="airliner" @selected($transportType === 'airliner')>Charter Ucak</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="">Tumu</option>
                        @foreach($statusMap as $code => $item)
                            <option value="{{ $code }}" @selected($status === $code)>{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary">Filtrele</button>
                    <a class="btn btn-outline-secondary" href="{{ route('acente.charter.index') }}">Sifirla</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-box shadow-sm">
        <div class="card-header fw-bold">Kayitlar</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Tarih</th>
                        <th>Tur</th>
                        <th>Rota</th>
                        <th>Hazir Paket</th>
                        <th>PAX</th>
                        <th>Durum</th>
                        <th>On Teklif Araligi</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $item)
                        @php
                            $statusBadge = $statusMap[$item->status] ?? ['label' => $item->status, 'class' => 'bg-secondary'];
                        @endphp
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                            <td><span class="badge bg-light text-dark border text-uppercase">{{ $item->transport_type }}</span></td>
                            <td>{{ strtoupper($item->from_iata) }} -> {{ strtoupper($item->to_iata) }}</td>
                            <td>
                                @if($item->preset_package_title)
                                    <div class="fw-semibold small">{{ $item->preset_package_title }}</div>
                                    <div class="text-muted small">
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
                            <td><span class="badge status-pill {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span></td>
                            <td>
                                @if($item->ai_price_min !== null && $item->ai_price_max !== null)
                                    {{ number_format((float) $item->ai_price_min, 0, ',', '.') }} - {{ number_format((float) $item->ai_price_max, 0, ',', '.') }} {{ $item->ai_currency ?: 'EUR' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('acente.charter.show', $item) }}" class="btn btn-outline-primary btn-sm">Detay</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted p-3">Henuz charter talebiniz yok.</td>
                        </tr>
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

@include('acente.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
