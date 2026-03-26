<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>{{ $productType === 'dinner_cruise' ? 'Dinner Cruise Talepleri' : 'Yacht Charter Talepleri' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .leisure-admin-index .shell-card { border-radius: 18px; border: 1px solid rgba(148, 163, 184, .2); }
        .leisure-admin-index .filter-box { border: 1px dashed rgba(148, 163, 184, .35); border-radius: 14px; padding: 1rem; }
        .leisure-admin-index .badge-soft { display: inline-flex; padding: .35rem .65rem; border-radius: 999px; font-size: .76rem; font-weight: 700; }
        html[data-theme="dark"] .leisure-admin-index .shell-card { border-color: #2d4371; }
        html[data-theme="dark"] .leisure-admin-index .filter-box { border-color: #2d4371; }
    </style>
</head>
<body class="theme-scope leisure-admin-index">
@if($panelRole === 'superadmin')
    <x-navbar-superadmin :active="$navActive" />
@else
    <x-navbar-admin :active="$navActive" />
@endif

@php
    $isDinner = $productType === 'dinner_cruise';
    $statusMap = [
        'new' => ['label' => 'Yeni', 'bg' => 'rgba(148,163,184,.18)', 'color' => '#475569'],
        'offer_sent' => ['label' => 'Teklif Verildi', 'bg' => 'rgba(37,99,235,.14)', 'color' => '#1d4ed8'],
        'revised' => ['label' => 'Revize', 'bg' => 'rgba(249,115,22,.15)', 'color' => '#c2410c'],
        'approved' => ['label' => 'Onaylandi', 'bg' => 'rgba(16,185,129,.16)', 'color' => '#047857'],
        'in_operation' => ['label' => 'Operasyonda', 'bg' => 'rgba(168,85,247,.18)', 'color' => '#7c3aed'],
        'completed' => ['label' => 'Tamamlandi', 'bg' => 'rgba(34,197,94,.16)', 'color' => '#15803d'],
        'cancelled' => ['label' => 'Iptal', 'bg' => 'rgba(239,68,68,.16)', 'color' => '#b91c1c'],
    ];
@endphp

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">{{ $isDinner ? 'Dinner Cruise Talepleri' : 'Yacht Charter Talepleri' }}</h3>
            <div class="text-muted small">Talep, teklif, booking ve finans surecini tek ekranda takip edin.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($panelRole === 'superadmin')
                <a href="{{ route('superadmin.leisure.settings.index') }}" class="btn btn-outline-primary btn-sm">Leisure Ayarlari</a>
                <a href="{{ route('superadmin.dinner-cruise.showcase') }}" class="btn btn-primary btn-sm">Dinner Vitrin</a>
            @endif
            <a href="{{ $isDinner ? route($panelRole . '.yacht-charter.index') : route($panelRole . '.dinner-cruise.index') }}" class="btn btn-outline-secondary btn-sm">
                {{ $isDinner ? 'Yacht Listesi' : 'Dinner Cruise Listesi' }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shell-card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="filter-box">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">Durum filtresi</label>
                        <select name="status" class="form-select">
                            <option value="">Tum durumlar</option>
                            @foreach($statusMap as $key => $config)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $config['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <button class="btn btn-primary">Filtrele</button>
                        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Sifirla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card shadow-sm">
        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
            <span>Kayitlar</span>
            <span class="badge bg-secondary">{{ $requests->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>GTPNR</th>
                        <th>Acente</th>
                        <th>Tarih</th>
                        <th>Misafir</th>
                        <th>Paket</th>
                        <th>Transfer</th>
                        <th>Durum</th>
                        <th>Teklif</th>
                        <th>Booking</th>
                        <th class="text-end">Islem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $requestItem)
                        @php
                            $statusConfig = $statusMap[$requestItem->status] ?? ['label' => $requestItem->status, 'bg' => 'rgba(148,163,184,.18)', 'color' => '#475569'];
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $requestItem->gtpnr }}</td>
                            <td>
                                <div>{{ $requestItem->user->agency_name ?? $requestItem->user->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $requestItem->user->email ?? '-' }}</div>
                            </td>
                            <td>{{ optional($requestItem->service_date)->format('d.m.Y') }}</td>
                            <td>{{ $requestItem->guest_count }}</td>
                            <td>{{ \Illuminate\Support\Str::headline($requestItem->package_level ?: 'standard') }}</td>
                            <td>{{ $requestItem->transfer_required ? 'Var' : 'Yok' }}</td>
                            <td><span class="badge-soft" style="background: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['color'] }};">{{ $statusConfig['label'] }}</span></td>
                            <td>{{ $requestItem->clientOffers->count() }}</td>
                            <td>{{ $requestItem->booking?->status ? \Illuminate\Support\Str::headline($requestItem->booking->status) : '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route($routePrefix . '.show', $requestItem) }}" class="btn btn-sm btn-outline-primary">Detay</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Kayit bulunamadi.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>

@include('admin.partials.theme-script')
</body>
</html>
