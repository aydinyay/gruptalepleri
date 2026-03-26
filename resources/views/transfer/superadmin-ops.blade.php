<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Operasyon - GrupTalepleri</title>
    @include('admin.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="theme-scope">
<x-navbar-superadmin active="transfer" />

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Transfer Operasyon</h1>
            <p class="text-muted mb-0">Supplier onaylari, zone yonetimi ve settlement raporlamasi.</p>
        </div>
        <a href="{{ route('superadmin.transfer.index') }}" class="btn btn-outline-secondary">Transfer arama</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h2 class="h5 fw-bold mb-0">Transfer tedarikci sozlesmesi</h2>
                <span class="badge text-bg-primary">Guncel versiyon: {{ $termsVersion }}</span>
            </div>
            <p class="text-muted small mb-3">Metin her kaydedildiginde versiyon otomatik artar ve tum tedarikciler yeniden onay ekranina duser.</p>
            <form method="POST" action="{{ route('superadmin.transfer.ops.terms.update') }}">
                @csrf
                @method('PATCH')
                <div class="mb-2">
                    <textarea name="terms_text" class="form-control" rows="5" required>{{ old('terms_text', $termsText) }}</textarea>
                </div>
                <button class="btn btn-primary btn-sm">Sozlesmeyi guncelle ve versiyon arttir</button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5 fw-bold">Supplier listesi</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>Firma</th>
                        <th>Iletisim</th>
                        <th>Coverage</th>
                        <th>Rule</th>
                        <th>Komisyon</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->company_name }}</td>
                            <td>
                                <div>{{ $supplier->contact_name }}</div>
                                <small class="text-muted">{{ $supplier->email }}</small>
                            </td>
                            <td>{{ $supplier->coverages_count }}</td>
                            <td>{{ $supplier->pricing_rules_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('superadmin.transfer.ops.suppliers.update', $supplier) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" style="max-width: 90px;" name="commission_rate" value="{{ $supplier->commission_rate }}" required>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_approved" value="1" id="approved{{ $supplier->id }}" @checked($supplier->is_approved)>
                                        <label class="form-check-label small" for="approved{{ $supplier->id }}">Onay</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $supplier->id }}" @checked($supplier->is_active)>
                                        <label class="form-check-label small" for="active{{ $supplier->id }}">Aktif</label>
                                    </div>
                                    <button class="btn btn-primary btn-sm">Kaydet</button>
                                </form>
                            </td>
                            <td>
                                <span class="badge {{ $supplier->is_approved ? 'text-bg-success' : 'text-bg-warning' }}">{{ $supplier->is_approved ? 'Onayli' : 'Beklemede' }}</span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('acente.transfer.supplier.index', ['supplier_id' => $supplier->id]) }}">
                                    Paneli Ac
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Supplier kaydi bulunamadi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5 fw-bold">Bolge ekle</h2>
                    <form method="POST" action="{{ route('superadmin.transfer.ops.zones.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Havalimani</label>
                            <select class="form-select" name="airport_id" required>
                                @foreach($airports as $airport)
                                    <option value="{{ $airport->id }}">{{ $airport->code }} - {{ $airport->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bolge adi</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sehir</label>
                            <input class="form-control" name="city" value="Istanbul" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lat</label>
                            <input class="form-control" name="latitude">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lng</label>
                            <input class="form-control" name="longitude">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sort</label>
                            <input class="form-control" name="sort_order" value="100">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="zoneActive" name="is_active" checked>
                                <label class="form-check-label" for="zoneActive">Aktif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Bolge kaydet</button>
                        </div>
                    </form>

                    <hr>
                    <h3 class="h6 fw-bold">Mevcut zonelar</h3>
                    @foreach($airports as $airport)
                        <div class="mb-2">
                            <div class="fw-semibold">{{ $airport->code }} - {{ $airport->name }}</div>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @forelse($airport->zones as $zone)
                                    <form method="POST" action="{{ route('superadmin.transfer.ops.zones.update', $zone) }}" class="d-inline-flex gap-1 align-items-center border rounded p-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="name" value="{{ $zone->name }}" class="form-control form-control-sm" style="width:130px;">
                                        <input type="text" name="city" value="{{ $zone->city }}" class="form-control form-control-sm" style="width:100px;">
                                        <input type="text" name="latitude" value="{{ $zone->latitude }}" class="form-control form-control-sm" style="width:90px;">
                                        <input type="text" name="longitude" value="{{ $zone->longitude }}" class="form-control form-control-sm" style="width:90px;">
                                        <input type="number" name="sort_order" value="{{ $zone->sort_order }}" class="form-control form-control-sm" style="width:70px;">
                                        <div class="form-check ms-1">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($zone->is_active)>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm">Guncelle</button>
                                    </form>
                                @empty
                                    <span class="text-muted small">Bolge yok.</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5 fw-bold">Settlement raporu</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Rota</th>
                                <th>Alis</th>
                                <th>Acenta</th>
                                <th>Supplier</th>
                                <th>Brut</th>
                                <th>Komisyon</th>
                                <th>Net</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($settlements as $settlement)
                                @php($booking = $settlement->booking)
                                <tr>
                                    <td>
                                        @if($booking)
                                            <a href="{{ route('superadmin.transfer.booking.show', $booking) }}" class="fw-semibold text-decoration-none">
                                                {{ $booking->booking_ref }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($booking)
                                            <div>{{ $booking->airport?->code }} -> {{ $booking->zone?->name }}</div>
                                            <small class="text-muted">{{ $booking->vehicleType?->name }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($booking?->pickup_at)->format('d.m.Y H:i') ?? '-' }}</td>
                                    <td>{{ $booking?->agencyUser?->name ?? '-' }}</td>
                                    <td>{{ $settlement->supplier?->company_name }}</td>
                                    <td>{{ number_format((float)$settlement->gross_amount, 2, ',', '.') }} {{ $settlement->currency }}</td>
                                    <td>{{ number_format((float)$settlement->commission_amount, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float)$settlement->net_amount, 2, ',', '.') }}</td>
                                    <td>{{ $settlement->status }}</td>
                                    <td class="text-end">
                                        @if($booking)
                                            <a href="{{ route('superadmin.transfer.booking.show', $booking) }}" class="btn btn-outline-primary btn-sm">
                                                Detay
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-muted">Settlement kaydi yok.</td></tr>
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
