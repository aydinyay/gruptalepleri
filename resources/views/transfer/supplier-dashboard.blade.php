<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Tedarikci Paneli - GrupTalepleri</title>
    @include('acente.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="theme-scope">
<x-navbar-acente active="transfer" />

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
            <h1 class="h3 fw-bold mb-1">Transfer Tedarikci Paneli</h1>
            <p class="text-muted mb-0">Coverage, fiyat ve iptal kurallarini buradan yonetin.</p>
        </div>
        <span class="badge {{ $supplier->is_approved ? 'text-bg-success' : 'text-bg-warning' }}">{{ $supplier->is_approved ? 'Onayli' : 'Onay bekliyor' }}</span>
    </div>
    @if(!empty($asSuperadmin))
        <div class="alert alert-info py-2">
            Superadmin gorunumu: <strong>{{ $supplier->company_name }}</strong> tedarikcisi adina paneli yonetiyorsunuz.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 fw-bold">Profil</h2>
                    <form method="POST" action="{{ route('acente.transfer.supplier.profile.update') }}" class="row g-2">
                        @csrf
                        @method('PATCH')
                        @if(!empty($asSuperadmin))
                            <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                        @endif
                        <div class="col-12">
                            <label class="form-label">Firma adi</label>
                            <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yetkili</label>
                            <input type="text" class="form-control" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $supplier->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $supplier->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sehir</label>
                            <input type="text" class="form-control" name="city" value="{{ old('city', $supplier->city) }}">
                        </div>
                        <div class="col-12 form-check ms-1">
                            <input class="form-check-input" type="checkbox" value="1" id="supplierActive" name="is_active" @checked(old('is_active', $supplier->is_active))>
                            <label class="form-check-label" for="supplierActive">Aktif satis</label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Profili guncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 fw-bold">Iptal politikasi</h2>
                    <form method="POST" action="{{ route('acente.transfer.supplier.policy.update') }}" class="row g-2">
                        @csrf
                        @method('PATCH')
                        @if(!empty($asSuperadmin))
                            <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Ucretsiz iptal (dk)</label>
                            <input type="number" class="form-control" name="free_cancel_before_minutes" min="0" value="{{ old('free_cancel_before_minutes', $supplier->cancellationPolicy?->free_cancel_before_minutes ?? 180) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deadline sonrasi iade %</label>
                            <input type="number" class="form-control" name="refund_percent_after_deadline" min="0" max="100" step="0.01" value="{{ old('refund_percent_after_deadline', $supplier->cancellationPolicy?->refund_percent_after_deadline ?? 0) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No-show iade %</label>
                            <input type="number" class="form-control" name="no_show_refund_percent" min="0" max="100" step="0.01" value="{{ old('no_show_refund_percent', $supplier->cancellationPolicy?->no_show_refund_percent ?? 0) }}" required>
                        </div>
                        <div class="col-12 form-check ms-1">
                            <input class="form-check-input" type="checkbox" value="1" id="policyActive" name="is_active" @checked(old('is_active', $supplier->cancellationPolicy?->is_active ?? true))>
                            <label class="form-check-label" for="policyActive">Politika aktif</label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Politikayi kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 fw-bold">Coverage ekle</h2>
                    <form method="POST" action="{{ route('acente.transfer.supplier.coverage.store') }}" class="row g-2">
                        @csrf
                        @if(!empty($asSuperadmin))
                            <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Havalimani</label>
                            <select class="form-select" name="airport_id" required>
                                <option value="">Secin</option>
                                @foreach($airports as $airport)
                                    <option value="{{ $airport->id }}">{{ $airport->code }} - {{ $airport->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bolge</label>
                            <select class="form-select" name="zone_id" required>
                                <option value="">Secin</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }} ({{ $zone->airport?->code ?? $zone->airport_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yon</label>
                            <select class="form-select" name="direction" required>
                                <option value="FROM_AIRPORT">FROM_AIRPORT</option>
                                <option value="TO_AIRPORT">TO_AIRPORT</option>
                                <option value="BOTH">BOTH</option>
                            </select>
                        </div>
                        <div class="col-12 form-check ms-1">
                            <input class="form-check-input" type="checkbox" value="1" id="coverageActive" name="is_active" checked>
                            <label class="form-check-label" for="coverageActive">Aktif</label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Coverage kaydet</button>
                        </div>
                    </form>

                    <hr>
                    <h3 class="h6 fw-bold">Coverage listesi</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Havalimani</th>
                                <th>Bolge</th>
                                <th>Yon</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($coverages as $coverage)
                                <tr>
                                    <td>{{ $coverage->airport?->code }}</td>
                                    <td>{{ $coverage->zone?->name }}</td>
                                    <td>{{ $coverage->direction }}</td>
                                    <td>{{ $coverage->is_active ? 'Aktif' : 'Pasif' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('acente.transfer.supplier.coverage.destroy', $coverage) }}">
                                            @csrf
                                            @method('DELETE')
                                            @if(!empty($asSuperadmin))
                                                <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                                            @endif
                                            <button class="btn btn-outline-danger btn-sm">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">Coverage kaydi yok.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h2 id="pricingRuleSectionTitle" class="h5 fw-bold">Fiyat kurali ekle</h2>
                    <form id="pricingRuleForm" method="POST" action="{{ route('acente.transfer.supplier.pricing.store') }}" class="row g-2">
                        @csrf
                        <input type="hidden" name="pricing_rule_id" id="pricingRuleId" value="">
                        @if(!empty($asSuperadmin))
                            <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Havalimani</label>
                            <select class="form-select" name="airport_id" required>
                                <option value="">Secin</option>
                                @foreach($airports as $airport)
                                    <option value="{{ $airport->id }}">{{ $airport->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bolge</label>
                            <select class="form-select" name="zone_id" required>
                                <option value="">Secin</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }} ({{ $zone->airport?->code ?? $zone->airport_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arac</label>
                            <select class="form-select" name="vehicle_type_id" required>
                                <option value="">Secin</option>
                                @foreach($vehicleTypes as $vehicleType)
                                    <option value="{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yon</label>
                            <select class="form-select" name="direction" required>
                                <option value="FROM_AIRPORT">FROM_AIRPORT</option>
                                <option value="TO_AIRPORT">TO_AIRPORT</option>
                                <option value="BOTH">BOTH</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Para birimi</label>
                            <input class="form-control" name="currency" value="TRY" maxlength="3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Base fare</label>
                            <input type="number" step="0.01" class="form-control" name="base_fare" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Km basi</label>
                            <input type="number" step="0.01" class="form-control" name="per_km" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dakika basi</label>
                            <input type="number" step="0.01" class="form-control" name="per_minute" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Minimum fiyat</label>
                            <input type="number" step="0.01" class="form-control" name="minimum_fare" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gece baslangic (opsiyonel)</label>
                            <input type="time" class="form-control" name="night_start">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gece bitis (opsiyonel)</label>
                            <input type="time" class="form-control" name="night_end">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gece carpan</label>
                            <input type="number" step="0.01" min="1" max="5" class="form-control" name="night_multiplier" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yogun saat carpan</label>
                            <input type="number" step="0.01" min="1" max="5" class="form-control" name="peak_multiplier" value="1">
                        </div>
                        <div class="col-12 form-check ms-1">
                            <input class="form-check-input" type="checkbox" value="1" id="pricingActive" name="is_active" checked>
                            <label class="form-check-label" for="pricingActive">Aktif</label>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button id="pricingRuleSubmitBtn" class="btn btn-primary">Fiyat kurali kaydet</button>
                            <button id="pricingRuleCancelBtn" type="button" class="btn btn-outline-secondary d-none">Duzenlemeyi iptal et</button>
                        </div>
                    </form>

                    <hr>
                    <h3 class="h6 fw-bold">Fiyat kurallari</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Rota</th>
                                <th>Arac</th>
                                <th>Kurgu</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($pricingRules as $rule)
                                <tr>
                                    <td>{{ $rule->airport?->code }} -> {{ $rule->zone?->name }}<br><small>{{ $rule->direction }}</small></td>
                                    <td>{{ $rule->vehicleType?->name }}</td>
                                    <td>
                                        Base {{ number_format((float)$rule->base_fare, 2, ',', '.') }} +
                                        {{ number_format((float)$rule->per_km, 2, ',', '.') }}/km +
                                        {{ number_format((float)$rule->per_minute, 2, ',', '.') }}/dk
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm js-pricing-edit"
                                                data-rule-id="{{ $rule->id }}"
                                                data-airport-id="{{ $rule->airport_id }}"
                                                data-zone-id="{{ $rule->zone_id }}"
                                                data-vehicle-type-id="{{ $rule->vehicle_type_id }}"
                                                data-direction="{{ $rule->direction }}"
                                                data-currency="{{ strtoupper((string) $rule->currency) }}"
                                                data-base-fare="{{ (float) $rule->base_fare }}"
                                                data-per-km="{{ (float) $rule->per_km }}"
                                                data-per-minute="{{ (float) $rule->per_minute }}"
                                                data-minimum-fare="{{ (float) $rule->minimum_fare }}"
                                                data-night-start="{{ $rule->night_start ? substr((string) $rule->night_start, 0, 5) : '' }}"
                                                data-night-end="{{ $rule->night_end ? substr((string) $rule->night_end, 0, 5) : '' }}"
                                                data-night-multiplier="{{ (float) $rule->night_multiplier }}"
                                                data-peak-multiplier="{{ (float) $rule->peak_multiplier }}"
                                                data-is-active="{{ $rule->is_active ? '1' : '0' }}"
                                            >Duzenle</button>
                                            <form method="POST" action="{{ route('acente.transfer.supplier.pricing.destroy', $rule) }}">
                                                @csrf
                                                @method('DELETE')
                                                @if(!empty($asSuperadmin))
                                                    <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                                                @endif
                                                <button class="btn btn-outline-danger btn-sm">Sil</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Fiyat kurali yok.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h2 class="h5 fw-bold">Son rezervasyonlar</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Rota</th>
                        <th>Alis</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_ref }}</td>
                            <td>{{ $booking->airport?->code }} -> {{ $booking->zone?->name }}</td>
                            <td>{{ optional($booking->pickup_at)->format('d.m.Y H:i') }}</td>
                            <td>{{ number_format((float)$booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</td>
                            <td>{{ $booking->status }}</td>
                            <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('acente.transfer.supplier.bookings.show', $booking) }}{{ !empty($asSuperadmin) ? ('?supplier_id=' . $selectedSupplierId) : '' }}">Detay</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Kayit bulunamadi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('pricingRuleForm');
    if (!form) {
        return;
    }

    var sectionTitle = document.getElementById('pricingRuleSectionTitle');
    var submitButton = document.getElementById('pricingRuleSubmitBtn');
    var cancelButton = document.getElementById('pricingRuleCancelBtn');
    var idField = document.getElementById('pricingRuleId');
    var activeField = document.getElementById('pricingActive');

    var fields = {
        airport_id: form.querySelector('[name="airport_id"]'),
        zone_id: form.querySelector('[name="zone_id"]'),
        vehicle_type_id: form.querySelector('[name="vehicle_type_id"]'),
        direction: form.querySelector('[name="direction"]'),
        currency: form.querySelector('[name="currency"]'),
        base_fare: form.querySelector('[name="base_fare"]'),
        per_km: form.querySelector('[name="per_km"]'),
        per_minute: form.querySelector('[name="per_minute"]'),
        minimum_fare: form.querySelector('[name="minimum_fare"]'),
        night_start: form.querySelector('[name="night_start"]'),
        night_end: form.querySelector('[name="night_end"]'),
        night_multiplier: form.querySelector('[name="night_multiplier"]'),
        peak_multiplier: form.querySelector('[name="peak_multiplier"]')
    };

    var defaults = {
        airport_id: fields.airport_id ? fields.airport_id.value : '',
        zone_id: fields.zone_id ? fields.zone_id.value : '',
        vehicle_type_id: fields.vehicle_type_id ? fields.vehicle_type_id.value : '',
        direction: fields.direction ? fields.direction.value : 'FROM_AIRPORT',
        currency: fields.currency ? fields.currency.value : 'TRY',
        base_fare: fields.base_fare ? fields.base_fare.value : '0',
        per_km: fields.per_km ? fields.per_km.value : '0',
        per_minute: fields.per_minute ? fields.per_minute.value : '0',
        minimum_fare: fields.minimum_fare ? fields.minimum_fare.value : '0',
        night_start: fields.night_start ? fields.night_start.value : '',
        night_end: fields.night_end ? fields.night_end.value : '',
        night_multiplier: fields.night_multiplier ? fields.night_multiplier.value : '1',
        peak_multiplier: fields.peak_multiplier ? fields.peak_multiplier.value : '1',
        is_active: activeField ? activeField.checked : true
    };

    function setCreateMode() {
        if (idField) {
            idField.value = '';
        }

        Object.keys(fields).forEach(function (name) {
            if (fields[name]) {
                fields[name].value = defaults[name];
            }
        });

        if (activeField) {
            activeField.checked = !!defaults.is_active;
        }

        if (sectionTitle) {
            sectionTitle.textContent = 'Fiyat kurali ekle';
        }

        if (submitButton) {
            submitButton.textContent = 'Fiyat kurali kaydet';
        }

        if (cancelButton) {
            cancelButton.classList.add('d-none');
        }
    }

    function setUpdateMode(button) {
        if (idField) {
            idField.value = button.dataset.ruleId || '';
        }

        if (fields.airport_id) fields.airport_id.value = button.dataset.airportId || '';
        if (fields.zone_id) fields.zone_id.value = button.dataset.zoneId || '';
        if (fields.vehicle_type_id) fields.vehicle_type_id.value = button.dataset.vehicleTypeId || '';
        if (fields.direction) fields.direction.value = button.dataset.direction || 'FROM_AIRPORT';
        if (fields.currency) fields.currency.value = button.dataset.currency || 'TRY';
        if (fields.base_fare) fields.base_fare.value = button.dataset.baseFare || '0';
        if (fields.per_km) fields.per_km.value = button.dataset.perKm || '0';
        if (fields.per_minute) fields.per_minute.value = button.dataset.perMinute || '0';
        if (fields.minimum_fare) fields.minimum_fare.value = button.dataset.minimumFare || '0';
        if (fields.night_start) fields.night_start.value = button.dataset.nightStart || '';
        if (fields.night_end) fields.night_end.value = button.dataset.nightEnd || '';
        if (fields.night_multiplier) fields.night_multiplier.value = button.dataset.nightMultiplier || '1';
        if (fields.peak_multiplier) fields.peak_multiplier.value = button.dataset.peakMultiplier || '1';
        if (activeField) activeField.checked = button.dataset.isActive === '1';

        if (sectionTitle) {
            sectionTitle.textContent = 'Fiyat kuralini guncelle';
        }

        if (submitButton) {
            submitButton.textContent = 'Guncelle';
        }

        if (cancelButton) {
            cancelButton.classList.remove('d-none');
        }

        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.querySelectorAll('.js-pricing-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            setUpdateMode(button);
        });
    });

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            setCreateMode();
        });
    }

    setCreateMode();
});
</script>

@include('acente.partials.theme-script')
</body>
</html>
