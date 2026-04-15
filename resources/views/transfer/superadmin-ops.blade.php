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
                            <td class="text-end" style="white-space:nowrap;">
                                @php $termsOk = $supplier->hasAcceptedVersion($termsVersion); @endphp
                                @if($termsOk)
                                    <span class="badge text-bg-success me-1" title="Sözleşme güncel (v{{ $termsVersion }})">
                                        <i class="fas fa-file-contract"></i> v{{ $termsVersion }} ✓
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('superadmin.transfer.ops.suppliers.force-accept-terms', $supplier) }}" class="d-inline"
                                          onsubmit="return confirm('{{ addslashes($supplier->company_name) }} adına mevcut sözleşmeyi (v{{ $termsVersion }}) onaylamak istiyor musunuz?')">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm me-1">
                                            <i class="fas fa-file-contract"></i> Sözleşme Onayla
                                        </button>
                                    </form>
                                @endif
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('acente.transfer.supplier.index', ['supplier_id' => $supplier->id]) }}">
                                    Paneli Aç
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

    {{-- ═══════════════════════════════════════════════════════════
         ARAÇ TİPİ YÖNETİMİ (medya, donanım, önerilen fiyat)
    ════════════════════════════════════════════════════════════ --}}
    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">Araç Tipleri</h2>

            {{-- Mevcut araç tipleri --}}
            @foreach($vehicleTypes as $vt)
            <div class="border rounded p-3 mb-3">
                <div class="row g-2 align-items-start">
                    {{-- Medya galeri önizleme --}}
                    <div class="col-12 col-md-3">
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($vt->media->where('media_type', 'photo')->take(3) as $m)
                                <img src="{{ $m->resolvedUrl() }}" alt="foto"
                                     style="width:70px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                            @endforeach
                            @foreach($vt->media->where('media_type', 'video')->take(1) as $m)
                                <div style="width:70px;height:52px;background:#0f172a;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-play text-white"></i>
                                </div>
                            @endforeach
                            @if($vt->media->isEmpty())
                                <div class="text-muted small fst-italic">Medya yok</div>
                            @endif
                        </div>
                        <div class="mt-1">
                            <span class="badge text-bg-secondary">{{ $vt->media->count() }}/7 medya</span>
                        </div>
                    </div>

                    {{-- Araç bilgileri güncelleme formu --}}
                    <div class="col-12 col-md-9">
                        <form method="POST" action="{{ route('superadmin.transfer.ops.vehicle-types.update', $vt) }}" class="row g-2">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">İsim</label>
                                <input class="form-control form-control-sm" name="name" value="{{ $vt->name }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Max PAX</label>
                                <input class="form-control form-control-sm" type="number" name="max_passengers" value="{{ $vt->max_passengers }}" min="1" max="100" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Bagaj</label>
                                <input class="form-control form-control-sm" type="number" name="luggage_capacity" value="{{ $vt->luggage_capacity }}" min="0" placeholder="adet">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Önerilen Satış (TRY)</label>
                                <input class="form-control form-control-sm" type="number" step="0.01" name="suggested_retail_price" value="{{ $vt->suggested_retail_price }}" min="0" placeholder="müşteriye önerilen">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="vtActive{{ $vt->id }}" @checked($vt->is_active)>
                                    <label class="form-check-label small" for="vtActive{{ $vt->id }}">Aktif</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Açıklama</label>
                                <input class="form-control form-control-sm" name="description" value="{{ $vt->description }}" placeholder="Araç hakkında kısa açıklama">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Donanım</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($amenityLabels as $code => $label)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="amenities[]"
                                                   value="{{ $code }}" id="amenity_{{ $vt->id }}_{{ $code }}"
                                                   @checked(in_array($code, $vt->amenities_json ?? []))>
                                            <label class="form-check-label small" for="amenity_{{ $vt->id }}_{{ $code }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary btn-sm">Güncelle</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Medya yükleme --}}
                @if($vt->media->count() < 7)
                <div class="border-top mt-3 pt-3">
                    <form method="POST" action="{{ route('superadmin.transfer.ops.vehicle-types.media.store', $vt) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
                        @csrf
                        <label class="form-label small fw-semibold mb-0">Fotoğraf/Video Ekle <span class="text-muted">(max 50MB — jpg/png/webp/mp4)</span></label>
                        <input type="file" name="vehicle_media[]" class="form-control form-control-sm" style="max-width:320px;" multiple accept=".jpg,.jpeg,.png,.webp,.avif,.gif,.mp4,.webm,.mov">
                        <button class="btn btn-outline-primary btn-sm">Yükle</button>
                    </form>
                </div>
                @endif

                {{-- Mevcut medya listesi + sil --}}
                @if($vt->media->isNotEmpty())
                <div class="border-top mt-2 pt-2">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($vt->media as $m)
                        <div class="position-relative" style="width:80px;">
                            @if($m->media_type === 'photo')
                                <img src="{{ $m->resolvedUrl() }}" style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #ddd;" alt="">
                            @else
                                <div style="width:80px;height:60px;background:#0f172a;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-film text-white"></i>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('superadmin.transfer.ops.vehicle-types.media.delete', $m) }}" class="position-absolute top-0 end-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm p-0" style="width:20px;height:20px;font-size:.65rem;" title="Sil" onclick="return confirm('Sil?')">×</button>
                            </form>
                            <div class="text-center" style="font-size:.65rem;color:#64748b;">{{ $m->sort_order }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach

            {{-- Yeni araç tipi ekle --}}
            <div class="border border-dashed rounded p-3 bg-light">
                <h3 class="h6 fw-bold mb-3">Yeni Araç Tipi Ekle</h3>
                <form method="POST" action="{{ route('superadmin.transfer.ops.vehicle-types.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small">Kod (unique)</label>
                        <input class="form-control form-control-sm" name="code" placeholder="sprinter_15" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">İsim</label>
                        <input class="form-control form-control-sm" name="name" placeholder="Mercedes Sprinter 15" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Max PAX</label>
                        <input class="form-control form-control-sm" type="number" name="max_passengers" value="3" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Bagaj</label>
                        <input class="form-control form-control-sm" type="number" name="luggage_capacity" placeholder="adet">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Önerilen Satış (TRY)</label>
                        <input class="form-control form-control-sm" type="number" step="0.01" name="suggested_retail_price" min="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Sort</label>
                        <input class="form-control form-control-sm" type="number" name="sort_order" value="100">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Donanım</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($amenityLabels as $code => $label)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="{{ $code }}" id="new_amenity_{{ $code }}">
                                    <label class="form-check-label small" for="new_amenity_{{ $code }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success btn-sm">Araç Tipi Ekle</button>
                    </div>
                </form>
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
