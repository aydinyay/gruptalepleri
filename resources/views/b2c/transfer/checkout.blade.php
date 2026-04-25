@extends('b2c.layouts.app')

@section('title', 'Transfer Rezervasyonu')
@section('meta_description', 'Transfer rezervasyonunuzu tamamlayın ve güvenli ödeme yapın.')

@push('head_styles')
<style>
.checkout-header { background: #0f2444; color: #fff; padding: 24px 0; }
.checkout-header h2 { font-size: 1.25rem; font-weight: 700; }

.summary-card { background: #f8faff; border: 1px solid #dbe4f5; border-radius: 14px; padding: 1.5rem; }
.summary-card .price-big { font-size: 2rem; font-weight: 800; color: #0f2444; }
.summary-card .price-big small { font-size: .8rem; font-weight: 400; color: #64748b; display: block; }
.summary-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: .88rem; }
.summary-row.total { font-weight: 700; font-size: 1rem; border-top: 2px solid #dbe4f5; padding-top: 10px; margin-top: 6px; }
.form-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; margin-bottom: 1.5rem; }
.form-section h5 { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem; }
.countdown { font-size: .88rem; color: #ef4444; font-weight: 600; }
.pay-btn {
    background: #FF5533;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 16px;
    font-weight: 700;
    font-size: 1.1rem;
    width: 100%;
    transition: background .15s;
}
.pay-btn:hover { background: #e04420; color: #fff; }
.pay-btn:disabled { background: #94a3b8; }
.security-note { font-size: .8rem; color: #64748b; text-align: center; margin-top: .5rem; }
</style>
@endpush

@section('content')

<div class="checkout-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item">
                    <a href="{{ lroute('b2c.transfer.index') }}" style="color:rgba(255,255,255,.7)">Transfer</a>
                </li>
                <li class="breadcrumb-item" style="color:rgba(255,255,255,.7)">Sonuçlar</li>
                <li class="breadcrumb-item active" style="color:#fff">Rezervasyon</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="mb-0"><i class="bi bi-calendar2-check me-2"></i>Rezervasyon Detayları</h2>
            <div class="countdown" id="countdownDisplay">
                <i class="bi bi-clock me-1"></i>Süre: <span id="countdownTimer">--:--</span>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
<div class="row g-4">

    {{-- Sol: Form --}}
    <div class="col-lg-8">

        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ lroute('b2c.transfer.book', $quote->token) }}" id="bookingForm">
            @csrf

            {{-- İletişim Bilgileri --}}
            <div class="form-section">
                <h5><i class="bi bi-person-fill me-2"></i>İletişim Bilgileri</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror"
                               value="{{ old('contact_name', $b2cUser?->name ?? '') }}"
                               placeholder="Tam adınız" required>
                        @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Telefon <span class="text-danger">*</span></label>
                        <input type="tel" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror"
                               value="{{ old('contact_phone', $b2cUser?->phone ?? '') }}"
                               placeholder="+90 5xx xxx xx xx" required>
                        @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">E-posta <span class="text-danger">*</span></label>
                        <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror"
                               value="{{ old('contact_email', $b2cUser?->email ?? '') }}"
                               placeholder="e-posta@ornek.com" required>
                        <small class="text-muted">Rezervasyon onayı bu adrese gönderilecektir.</small>
                        @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Uçuş / Operasyon Bilgileri --}}
            <div class="form-section">
                <h5><i class="bi bi-airplane me-2"></i>Uçuş / Operasyon Bilgileri</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Uçuş No</label>
                        <input type="text" name="flight_number" class="form-control"
                               value="{{ old('flight_number') }}" placeholder="TK 1234">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Terminal</label>
                        <input type="text" name="terminal" class="form-control"
                               value="{{ old('terminal') }}" placeholder="1, 2, Domestic...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Karşılama Tabelası Adı</label>
                        <input type="text" name="pickup_sign_name" class="form-control"
                               value="{{ old('pickup_sign_name') }}" placeholder="Adınız / Şirket adı">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Tam Alış Adresi</label>
                        <input type="text" name="exact_pickup_address" class="form-control"
                               value="{{ old('exact_pickup_address') }}" placeholder="Otel adı / Adres (opsiyonel)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Yolcu Adları</label>
                        <textarea name="passenger_names" class="form-control" rows="2"
                                  placeholder="Her satıra bir isim (opsiyonel)">{{ old('passenger_names') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bagaj Sayısı</label>
                        <input type="number" name="luggage_count" class="form-control"
                               value="{{ old('luggage_count', 0) }}" min="0" max="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Çocuk Koltuğu</label>
                        <input type="number" name="child_seat_count" class="form-control"
                               value="{{ old('child_seat_count', 0) }}" min="0" max="10">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Notlar</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Tedarikçiye iletilecek ek notlar...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Ödeme Butonu (mobil için) --}}
            <div class="d-lg-none">
                <button type="submit" class="pay-btn" id="payBtnMobile">
                    <i class="bi bi-credit-card me-2"></i>Ödemeye Geç
                </button>
                <div class="security-note"><i class="bi bi-shield-lock me-1"></i>SSL ile güvenli ödeme</div>
            </div>
        </form>
    </div>

    {{-- Sağ: Özet + Ödeme --}}
    <div class="col-lg-4">
        <div class="summary-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Rezervasyon Özeti</h5>

            @php
                $snap    = $quote->snapshot_json ?? [];
                $airport = $snap['airport'] ?? [];
                $zone    = $snap['zone']    ?? [];
                $dirLabel = ['ARR' => 'Varış', 'DEP' => 'Gidiş', 'BOTH' => 'Gidiş-Dönüş'][$quote->direction] ?? $quote->direction;
            @endphp

            <div class="summary-row">
                <span class="text-muted">Havalimanı</span>
                <span class="fw-semibold">{{ $airport['code'] ?? '' }} — {{ $airport['name'] ?? '' }}</span>
            </div>
            <div class="summary-row">
                <span class="text-muted">Bölge</span>
                <span class="fw-semibold">{{ $zone['name'] ?? '' }}</span>
            </div>
            <div class="summary-row">
                <span class="text-muted">Yön</span>
                <span class="fw-semibold">{{ $dirLabel }}</span>
            </div>
            <div class="summary-row">
                <span class="text-muted">Tarih/Saat</span>
                <span class="fw-semibold">{{ $quote->pickup_at->format('d.m.Y H:i') }}</span>
            </div>
            @if($quote->return_at)
            <div class="summary-row">
                <span class="text-muted">Dönüş</span>
                <span class="fw-semibold">{{ $quote->return_at->format('d.m.Y H:i') }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span class="text-muted">Araç</span>
                <span class="fw-semibold">{{ $quote->vehicleType?->name ?? 'Transfer Aracı' }}</span>
            </div>
            <div class="summary-row">
                <span class="text-muted">Yolcu</span>
                <span class="fw-semibold">{{ $quote->pax }} kişi</span>
            </div>
            <div class="summary-row total">
                <span>Toplam</span>
                <div>
                    <div class="price-big text-end">
                        {{ number_format($quote->total_amount, 0, ',', '.') }}
                        <small>{{ $quote->currency }}</small>
                    </div>
                </div>
            </div>

            {{-- İptal Politikası --}}
            @if(!empty($snap['policy']['free_cancel_before_minutes']))
            <div class="mt-3 p-2 bg-success bg-opacity-10 rounded small text-success">
                <i class="bi bi-check-circle-fill me-1"></i>
                {{ $snap['policy']['free_cancel_before_minutes'] }} dk. öncesine kadar ücretsiz iptal
            </div>
            @endif

            {{-- Desktop ödeme butonu --}}
            <div class="d-none d-lg-block mt-3">
                <button type="submit" form="bookingForm" class="pay-btn" id="payBtnDesktop">
                    <i class="bi bi-credit-card me-2"></i>Ödemeye Geç
                </button>
                <div class="security-note mt-2"><i class="bi bi-shield-lock me-1"></i>SSL ile güvenli ödeme</div>
            </div>

            {{-- Sigorta Upsell --}}
            <div style="margin-top:1.25rem;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:1rem;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:.5rem;">
                    <i class="bi bi-shield-check" style="color:#059669;font-size:1.2rem;"></i>
                    <span style="font-weight:700;font-size:.9rem;color:#064e3b;">Seyahat Sigortası Ekle</span>
                </div>
                <p style="font-size:.8rem;color:#047857;margin:0 0 .75rem;">Tıbbi acil, bagaj kaybı ve uçuş iptali için koruma. Anında poliçe.</p>
                <a href="{{ lroute('b2c.sigorta.create') }}" style="display:block;text-align:center;background:#059669;color:#fff;font-weight:600;font-size:.85rem;text-decoration:none;padding:9px;border-radius:7px;">
                    <i class="bi bi-shield-check me-1"></i> Sigorta Teklifi Al →
                </a>
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ttl = parseInt('{{ $ttlSeconds }}', 10);
    var display = document.getElementById('countdownTimer');

    function tick() {
        if (ttl <= 0) {
            display.textContent = 'Süresi Doldu';
            document.getElementById('payBtnDesktop')?.setAttribute('disabled', true);
            document.getElementById('payBtnMobile')?.setAttribute('disabled', true);
            return;
        }
        var m = Math.floor(ttl / 60);
        var s = ttl % 60;
        display.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        ttl--;
        setTimeout(tick, 1000);
    }
    tick();

    document.getElementById('bookingForm').addEventListener('submit', function () {
        var btns = document.querySelectorAll('#payBtnDesktop, #payBtnMobile');
        btns.forEach(function (b) {
            b.disabled = true;
            b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>İşleniyor...';
        });
    });
});
</script>
@endpush
