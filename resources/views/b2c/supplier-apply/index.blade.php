@extends('b2c.layouts.app')

@section('title', 'Tedarikçi / İş Ortağı Olun')
@section('meta_description', 'Grup Rezervasyonları platformuna tedarikçi veya iş ortağı olarak katılın. Transfer, tur, konaklama, yat ve daha fazla hizmet kategorisinde ürünlerinizi milyonlara ulaştırın.')

@section('content')

{{-- Hero --}}
<section style="background: linear-gradient(135deg, var(--gr-dark) 0%, var(--gr-primary) 100%); padding: 5rem 0 4rem;">
    <div class="container text-center text-white">
        <span class="badge mb-3 px-3 py-2" style="background:rgba(255,255,255,.15);color:#fff;font-size:.88rem;">
            <i class="bi bi-building me-1"></i>İş Ortaklığı Programı
        </span>
        <h1 class="fw-800 mb-3" style="font-size:clamp(1.8rem,4vw,2.6rem);">
            Ürününüzü <span style="color:var(--gr-accent);">Milyonlara</span> Ulaştırın
        </h1>
        <p class="mx-auto mb-4" style="opacity:.85;font-size:1.05rem;max-width:560px;">
            Transfer firması, tur operatörü, otel, yat işletmecisi veya vize danışmanıysanız platformumuza katılın. Ürün ve hizmetlerinizi yönetip müşteri kitlesiyle buluşturalım.
        </p>
        <a href="#basvuru-formu" class="btn btn-gr-accent btn-lg px-5">
            <i class="bi bi-arrow-down-circle me-2"></i>Hemen Başvurun
        </a>
    </div>
</section>

{{-- Kimler Başvurabilir --}}
<section>
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="gr-section-title">Kimler Başvurabilir?</h2>
            <p class="gr-section-subtitle">Seyahat ve turizm sektöründe hizmet sunan her firma platformumuza katılabilir.</p>
        </div>
        <div class="row g-3">
            @foreach([
                ['bi-car-front-fill',   '#e8f4ff', 'Transfer Firmaları',      'Havalimanı, şehir içi, kurumsal transfer hizmeti sunanlar'],
                ['bi-airplane-fill',    '#fff8e8', 'Tur Operatörleri',        'Yurt içi ve yurt dışı tur paketi düzenleyenler'],
                ['bi-building',         '#f0fff0', 'Otel & Konaklama',        'Butik otel, pansiyon ve tatil köyü işletmecileri'],
                ['bi-water',            '#e8f4ff', 'Tekne & Yat İşletmecisi', 'Dinner cruise, yat kiralama ve tekne turu sunanlar'],
                ['bi-passport',         '#fff8e8', 'Vize Danışmanları',       'Schengen, Türk vizesi ve diğer vize danışmanlık hizmetleri'],
                ['bi-map-fill',         '#f0fff0', 'Rehber & Aktivite',       'Şehir turu, doğa yürüyüşü ve aktivite organizatörleri'],
                ['bi-helicopter',       '#e8f4ff', 'Charter & Havacılık',     'Özel jet, helikopter ve charter uçuş hizmetleri'],
                ['bi-briefcase-fill',   '#fff8e8', 'Kurumsal Çözümler',       'MICE, kongre ve kurumsal seyahat organizatörleri'],
            ] as $item)
            <div class="col-6 col-md-3">
                <div class="rounded-3 p-3 h-100" style="background:{{ $item[1] }};border:1px solid var(--gr-border);">
                    <i class="bi {{ $item[0] }} fs-3 mb-2 d-block" style="color:var(--gr-primary);"></i>
                    <div class="fw-700 mb-1" style="font-size:.93rem;">{{ $item[2] }}</div>
                    <div style="font-size:.82rem;color:var(--gr-muted);">{{ $item[3] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Nasıl Çalışır --}}
<section style="background:var(--gr-light);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="gr-section-title">Nasıl Çalışır?</h2>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach([
                ['1', 'bi-send-fill',         'Başvurun',          'Aşağıdaki formu doldurun veya gruptalepleri.com üzerinden kayıt olun.'],
                ['2', 'bi-clipboard-check',   'Değerlendirme',     'Operasyon ekibimiz başvurunuzu 1-3 iş günü içinde inceler.'],
                ['3', 'bi-person-check-fill', 'Panel Açılır',      'Onaylananlar gruptalepleri.com üzerinde tedarikçi paneline erişir.'],
                ['4', 'bi-grid-fill',         'Ürün Girişi',       'Ürün ve hizmetlerinizi detaylıca tanımlarsınız.'],
                ['5', 'bi-globe',             'Yayına Girer',      'Admin onayıyla ürünleriniz gruprezervasyonlari.com\'da listelenir.'],
                ['6', 'bi-cash-coin',         'Satış & Komisyon',  'Her satıştan anlaşılan komisyon oranınca kazanırsınız.'],
            ] as $step)
            <div class="col-6 col-md-4 col-lg-2">
                <div class="text-center p-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:56px;height:56px;background:var(--gr-primary);color:#fff;font-size:1.3rem;font-weight:800;">
                        {{ $step[0] }}
                    </div>
                    <i class="bi {{ $step[1] }} fs-5 d-block mb-2" style="color:var(--gr-accent);"></i>
                    <div class="fw-700 mb-1" style="font-size:.93rem;">{{ $step[2] }}</div>
                    <div style="font-size:.82rem;color:var(--gr-muted);">{{ $step[3] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Neden Biz --}}
<section>
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <h2 class="gr-section-title">Neden Grup Rezervasyonları?</h2>
                <p style="color:var(--gr-muted);">Onlarca yıllık sektör deneyimi ve güçlü B2B ağıyla size müşteri getiriyoruz.</p>
                <ul class="list-unstyled mt-3">
                    @foreach([
                        'Geniş acente ve kurumsal müşteri ağı',
                        'Güvenli ödeme altyapısı (Paynkolay 3D Secure)',
                        'Profesyonel operasyon ve müşteri desteği',
                        'Şeffaf komisyon ve hızlı ödeme',
                        'Ürün görünürlüğü ve SEO desteği',
                        'Kalite kontrolü ile güvenilir platform',
                    ] as $benefit)
                    <li class="mb-2 d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill mt-1" style="color:var(--gr-accent);flex-shrink:0;"></i>
                        <span style="font-size:.94rem;">{{ $benefit }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-7" id="basvuru-formu">
                <div class="rounded-3 p-4 shadow-sm" style="background:#fff;border:1px solid var(--gr-border);">
                    <h4 class="fw-700 mb-1" style="color:var(--gr-primary);">Başvuru Formu</h4>
                    <p class="mb-4" style="font-size:.88rem;color:var(--gr-muted);">
                        Formu doldurun, ekibimiz en kısa sürede sizinle iletişime geçsin.
                    </p>

                    @if(session('success'))
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    </div>
                    @endif

                    <form action="{{ route('b2c.supplier-apply.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-600">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="applicant_name" class="form-control @error('applicant_name') is-invalid @enderror"
                                       value="{{ old('applicant_name') }}" required>
                                @error('applicant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600">Firma Adı <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                       value="{{ old('company_name') }}" required>
                                @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600">E-posta <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600">Telefon</label>
                                <input type="tel" name="phone" class="form-control"
                                       value="{{ old('phone') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-600">Sunduğunuz Hizmetler</label>
                                <div class="row g-2">
                                    @foreach([
                                        ['transfer',    'Transfer'],
                                        ['tur',         'Tur Paketi'],
                                        ['otel',        'Otel / Konaklama'],
                                        ['yat',         'Yat / Tekne'],
                                        ['charter',     'Charter / Havacılık'],
                                        ['vize',        'Vize Hizmeti'],
                                        ['rehber',      'Rehber / Aktivite'],
                                        ['diger',       'Diğer'],
                                    ] as $svc)
                                    <div class="col-6 col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="service_types[]" value="{{ $svc[0] }}"
                                                   id="svc_{{ $svc[0] }}"
                                                   {{ in_array($svc[0], old('service_types', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="svc_{{ $svc[0] }}" style="font-size:.9rem;">
                                                {{ $svc[1] }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-600">Kısa Not (isteğe bağlı)</label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="Firmanız ve sunduğunuz hizmetler hakkında kısa bilgi verin...">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-gr-primary btn-lg w-100">
                                    <i class="bi bi-send me-2"></i>Başvurumu Gönder
                                </button>
                                <p class="text-center mt-2 mb-0" style="font-size:.82rem;color:var(--gr-muted);">
                                    Veya doğrudan
                                    <a href="{{ config('b2c.supplier_apply_redirect', '#') }}" target="_blank" style="color:var(--gr-primary);">
                                        gruptalepleri.com'a kayıt olun
                                    </a>
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
