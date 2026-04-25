@extends('b2c.layouts.app')

@section('title', 'Rezervasyon — ' . $booking->booking_ref)

@push('head_styles')
<style>
.booking-header { background: #0f2444; color: #fff; padding: 24px 0; }
.status-confirmed { background: #d1fae5; color: #065f46; }
.status-payment_pending { background: #fef3c7; color: #92400e; }
.status-failed, .status-cancelled { background: #fee2e2; color: #991b1b; }
.booking-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; background: #fff; margin-bottom: 1.5rem; }
.detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
.detail-row:last-child { border-bottom: none; }
.detail-label { color: #64748b; }
.detail-value { font-weight: 600; }
.ref-badge { background: #f0f4ff; color: #3b5fc0; font-size: .8rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; }
</style>
@endpush

@section('content')

<div class="booking-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item">
                    <a href="{{ lroute('b2c.transfer.index') }}" style="color:rgba(255,255,255,.7)">Transfer</a>
                </li>
                <li class="breadcrumb-item active" style="color:#fff">Rezervasyon</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h2 class="mb-0 fw-bold"><i class="bi bi-ticket-detailed me-2"></i>{{ $booking->booking_ref }}</h2>
            @php
                $statusLabel = [
                    'confirmed'       => 'Onaylandı',
                    'payment_pending' => 'Ödeme Bekleniyor',
                    'failed'          => 'Başarısız',
                    'cancelled'       => 'İptal Edildi',
                    'refunded'        => 'İade Edildi',
                ][$booking->status] ?? $booking->status;
            @endphp
            <span class="badge fs-6 px-3 py-2 status-{{ $booking->status }}">{{ $statusLabel }}</span>
        </div>
    </div>
</div>

<div class="container py-4">

    @if(session('payment_success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill fs-4"></i>
            <div>
                <strong>Ödemeniz başarıyla alındı!</strong> Rezervasyonunuz onaylandı.
                Onay detayları e-posta adresinize gönderildi.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Transfer Detayları --}}
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-car-front me-2"></i>Transfer Detayları</h5>
                @php
                    $snap    = $booking->price_snapshot_json ?? [];
                    $snapData = $snap['snapshot'] ?? [];
                    $airport = $snapData['airport'] ?? [];
                    $zone    = $snapData['zone'] ?? [];
                    $dirLabel = ['ARR' => 'Varış', 'DEP' => 'Gidiş', 'BOTH' => 'Gidiş-Dönüş'][$booking->direction] ?? $booking->direction;
                @endphp
                <div class="detail-row">
                    <span class="detail-label">Havalimanı</span>
                    <span class="detail-value">{{ ($airport['code'] ?? '') }} — {{ ($airport['name'] ?? $booking->airport?->name ?? '') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bölge</span>
                    <span class="detail-value">{{ $zone['name'] ?? $booking->zone?->name ?? '' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Yön</span>
                    <span class="detail-value">{{ $dirLabel }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Kalkış</span>
                    <span class="detail-value">{{ $booking->pickup_at->format('d.m.Y H:i') }}</span>
                </div>
                @if($booking->return_at)
                <div class="detail-row">
                    <span class="detail-label">Dönüş</span>
                    <span class="detail-value">{{ $booking->return_at->format('d.m.Y H:i') }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Araç</span>
                    <span class="detail-value">{{ $booking->vehicleType?->name ?? 'Transfer Aracı' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Yolcu</span>
                    <span class="detail-value">{{ $booking->pax }} kişi</span>
                </div>
            </div>

            {{-- İletişim --}}
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>İletişim Bilgileri</h5>
                <div class="detail-row">
                    <span class="detail-label">Ad Soyad</span>
                    <span class="detail-value">{{ $booking->b2c_contact_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Telefon</span>
                    <span class="detail-value">{{ $booking->b2c_contact_phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">E-posta</span>
                    <span class="detail-value">{{ $booking->b2c_contact_email }}</span>
                </div>
            </div>

            @php
                $operation = $snap['operation_details'] ?? [];
            @endphp
            @if(array_filter($operation))
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-airplane me-2"></i>Operasyon Notları</h5>
                @if(!empty($operation['flight_number']))
                <div class="detail-row"><span class="detail-label">Uçuş No</span><span class="detail-value">{{ $operation['flight_number'] }}</span></div>
                @endif
                @if(!empty($operation['terminal']))
                <div class="detail-row"><span class="detail-label">Terminal</span><span class="detail-value">{{ $operation['terminal'] }}</span></div>
                @endif
                @if(!empty($operation['pickup_sign_name']))
                <div class="detail-row"><span class="detail-label">Tabela Adı</span><span class="detail-value">{{ $operation['pickup_sign_name'] }}</span></div>
                @endif
                @if(!empty($operation['exact_pickup_address']))
                <div class="detail-row"><span class="detail-label">Alış Adresi</span><span class="detail-value">{{ $operation['exact_pickup_address'] }}</span></div>
                @endif
                @if(isset($operation['luggage_count']) && $operation['luggage_count'] !== null)
                <div class="detail-row"><span class="detail-label">Bagaj</span><span class="detail-value">{{ $operation['luggage_count'] }} adet</span></div>
                @endif
            </div>
            @endif
        </div>

        <div class="col-lg-5">
            {{-- Ödeme Özeti --}}
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Ödeme Özeti</h5>
                <div class="detail-row">
                    <span class="detail-label">Tutar</span>
                    <span class="detail-value">{{ number_format($booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durum</span>
                    <span class="detail-value">{{ $statusLabel }}</span>
                </div>
                @if($booking->confirmed_at)
                <div class="detail-row">
                    <span class="detail-label">Onay Tarihi</span>
                    <span class="detail-value">{{ $booking->confirmed_at->format('d.m.Y H:i') }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Rezervasyon Kodu</span>
                    <span class="ref-badge">{{ $booking->booking_ref }}</span>
                </div>
            </div>

            {{-- İptal Politikası --}}
            @php $policySnap = $booking->supplier_policy_snapshot_json ?? []; @endphp
            @if(!empty($policySnap))
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>İptal Koşulları</h5>
                @if(!empty($policySnap['free_cancel_before_minutes']))
                <p class="text-success small mb-1">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    {{ $policySnap['free_cancel_before_minutes'] }} dakika öncesine kadar ücretsiz iptal
                </p>
                @endif
                @if(isset($policySnap['refund_percent_after_deadline']))
                <p class="small text-muted mb-0">
                    Sonrasında iade oranı: %{{ $policySnap['refund_percent_after_deadline'] }}
                </p>
                @endif
            </div>
            @endif

            {{-- Voucher --}}
            @if($booking->status === 'confirmed')
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-2"></i>Fiş / Voucher</h5>
                <a href="{{ lroute('b2c.transfer.voucher', $booking->booking_ref) }}"
                   target="_blank"
                   class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="bi bi-printer me-1"></i>Voucher Görüntüle / Yazdır
                </a>
            </div>
            @endif

            {{-- Destek --}}
            <div class="booking-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-headset me-2"></i>Destek</h5>
                <p class="small text-muted mb-2">Rezervasyonunuzla ilgili sorularınız için:</p>
                <a href="{{ lroute('b2c.iletisim') }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-envelope me-1"></i>İletişime Geç
                </a>
            </div>
        </div>
    </div>

    <div class="text-center mt-2">
        <a href="{{ lroute('b2c.transfer.index') }}" class="btn btn-link text-muted">
            <i class="bi bi-arrow-left me-1"></i>Yeni Transfer Ara
        </a>
    </div>
</div>
@endsection
