<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('acente.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GrupRezervasyonları.com — B2C Katılım</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: var(--bs-body-bg, #f8f9fa); }
        .grz-hero {
            background: linear-gradient(135deg, #0f1f48 0%, #1a3b7a 100%);
            color: #fff;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            margin-bottom: 1.5rem;
        }
        .status-badge-pending   { background:#fff3cd; color:#856404; }
        .status-badge-approved  { background:#d1e7dd; color:#0a3622; }
        .status-badge-rejected  { background:#f8d7da; color:#842029; }
        .status-badge-suspended { background:#e2e3e5; color:#41464b; }
        .fleet-row input[type="number"] { width: 80px; }
    </style>
</head>
<body class="theme-scope">

<x-navbar-acente active="b2c" />

<div class="container py-4">

    {{-- Başlık -------------------------------------------------------}}
    <div class="grz-hero d-flex align-items-center gap-3">
        <img src="https://gruprezervasyonlari.com/favicon.ico" alt="" width="48" height="48"
             onerror="this.style.display='none'" class="rounded">
        <div>
            <h1 class="h3 fw-bold mb-1">GrupRezervasyonları.com'a Katıl</h1>
            <p class="mb-0 opacity-75">
                Ürün ve hizmetlerinizi B2C müşterilere sunun, her satıştan komisyon kazanın.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul></div>
    @endif

    {{-- DURUM KARTI --------------------------------------------------}}
    @if($subscription)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h5 fw-bold mb-1">Başvuru Durumunuz</h2>
                    <small class="text-muted">{{ $subscription->created_at->format('d.m.Y H:i') }} tarihinde başvurdunuz</small>
                </div>
                <span class="badge fs-6 px-3 py-2 status-badge-{{ $subscription->status }}">
                    @php
                        $labels = [
                            'pending'   => 'İnceleniyor',
                            'approved'  => 'Onaylandı ✓',
                            'rejected'  => 'Reddedildi',
                            'suspended' => 'Askıya Alındı',
                        ];
                    @endphp
                    {{ $labels[$subscription->status] ?? $subscription->status }}
                </span>
            </div>

            @if($subscription->status === 'rejected' && $subscription->rejection_reason)
                <div class="alert alert-warning mt-3 mb-0">
                    <strong>Red nedeni:</strong> {{ $subscription->rejection_reason }}
                </div>
            @endif

            @if($subscription->status === 'approved')
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Tebrikler! Ürünleriniz <strong>gruprezervasyonlari.com</strong>'da yayınlanmaya hazır.
                    @if($subscription->approved_at)
                        Onay tarihi: {{ $subscription->approved_at->format('d.m.Y') }}
                    @endif
                </div>
            @endif

            @if($subscription->status === 'pending')
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Başvurunuz inceleniyor. Genellikle 1-2 iş günü içinde dönüş yapılır.
                </div>
            @endif
        </div>
    </div>

    @else
    {{-- BAŞVURU FORMU ------------------------------------------------}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-bold py-3">
            <i class="fas fa-file-alt me-2"></i>Katılım Başvurusu
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Başvurunuz incelendikten sonra onaylanırsa ürünleriniz
                <strong>gruprezervasyonlari.com</strong>'da B2C müşterilere sunulacak.
                Her satıştan belirlenen komisyon oranı kesilerek kalan tutar hesabınıza aktarılır.
            </p>

            <form method="POST" action="{{ route('acente.b2c.apply') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">Hangi hizmetlerinizi listelemek istiyorsunuz?</label>
                    <div class="row g-2">
                        @php
                            $serviceOptions = [
                                'transfer' => ['icon' => 'fa-car', 'label' => 'Transfer', 'desc' => 'Havalimanı-otel transferleri'],
                                'leisure'  => ['icon' => 'fa-umbrella-beach', 'label' => 'Leisure / Etkinlik', 'desc' => 'Tekne turları, yemekli etkinlikler'],
                                'charter'  => ['icon' => 'fa-plane', 'label' => 'Charter', 'desc' => 'Özel uçuş paketleri'],
                                'tour'     => ['icon' => 'fa-map-marked-alt', 'label' => 'Tur', 'desc' => 'Günübirlik & şehir turları'],
                            ];
                        @endphp
                        @foreach($serviceOptions as $key => $opt)
                        <div class="col-md-6 col-lg-3">
                            <label class="card p-3 h-100 cursor-pointer border @error('service_types') border-danger @enderror"
                                   style="cursor:pointer;">
                                <input type="checkbox" name="service_types[]" value="{{ $key }}"
                                       class="form-check-input me-2"
                                       {{ is_array(old('service_types')) && in_array($key, old('service_types')) ? 'checked' : '' }}>
                                <span class="fw-bold"><i class="fas {{ $opt['icon'] }} me-1"></i>{{ $opt['label'] }}</span>
                                <small class="text-muted d-block mt-1">{{ $opt['desc'] }}</small>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @error('service_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                @if(!$transferSupplier || !$transferSupplier->is_approved)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Transfer hizmeti listeleyebilmek için önce
                    <a href="{{ route('acente.transfer.supplier.index') }}" class="fw-bold">Transfer Tedarikçi</a>
                    kaydınızın onaylanmış olması gerekiyor.
                </div>
                @endif

                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-paper-plane me-2"></i>Başvuruyu Gönder
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ARAÇ FİLOSU (sadece transfer supplier onaylı ve subscription approved ise) --}}
    @if($transferSupplier && $subscription && $subscription->status === 'approved')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-car me-2"></i>Araç Filosu & Günlük Kapasite</span>
            <small class="text-muted fw-normal">B2C'de müsaitlik kontrolü için kullanılır</small>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Her araç tipi için sahip olduğunuz araç adedini ve günlük kabul edebileceğiniz
                maksimum rezervasyon sayısını girin.
            </p>
            <form method="POST" action="{{ route('acente.b2c.fleet.save') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Araç Tipi</th>
                                <th class="text-center">Maks. Yolcu</th>
                                <th class="text-center">Araç Adedi</th>
                                <th class="text-center">Günlük Max Rezervasyon</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($vehicleTypes as $i => $vt)
                            @php
                                $existing = $fleet->firstWhere('vehicle_type_id', $vt->id);
                            @endphp
                            <tr class="fleet-row">
                                <td>
                                    <input type="hidden" name="fleet[{{ $i }}][vehicle_type_id]" value="{{ $vt->id }}">
                                    <strong>{{ $vt->name }}</strong>
                                    <small class="text-muted d-block">{{ $vt->code }}</small>
                                </td>
                                <td class="text-center text-muted">{{ $vt->max_passengers }} kişi</td>
                                <td class="text-center">
                                    <input type="number" name="fleet[{{ $i }}][quantity]"
                                           class="form-control form-control-sm text-center mx-auto"
                                           style="width:80px"
                                           min="0" max="100"
                                           value="{{ $existing?->quantity ?? 0 }}">
                                </td>
                                <td class="text-center">
                                    <input type="number" name="fleet[{{ $i }}][max_daily_bookings]"
                                           class="form-control form-control-sm text-center mx-auto"
                                           style="width:80px"
                                           min="0" max="500"
                                           value="{{ $existing?->max_daily_bookings ?? 4 }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Filosu Kaydet
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- BİLGİ KUTUSU --}}
    <div class="card border-0 shadow-sm bg-light">
        <div class="card-body p-4">
            <h3 class="h6 fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Nasıl Çalışır?</h3>
            <div class="row g-3">
                <div class="col-md-4 text-center">
                    <div class="fs-2 mb-2">📋</div>
                    <strong>1. Başvur</strong>
                    <p class="small text-muted mb-0">Hangi hizmetleri listelemek istediğinizi seçin ve başvurun.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="fs-2 mb-2">✅</div>
                    <strong>2. Onay</strong>
                    <p class="small text-muted mb-0">Admin incelemesi sonrası hizmetleriniz yayına girer.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="fs-2 mb-2">💰</div>
                    <strong>3. Kazan</strong>
                    <p class="small text-muted mb-0">Her satıştan komisyon sonrası net tutarınız hesabınıza aktarılır.</p>
                </div>
            </div>
        </div>
    </div>

</div>

@include('acente.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
