@extends('b2c.layouts.app')
@section('title', 'Rezervasyon — ' . $leisureRequest->gtpnr)
@push('head_styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
@endpush

@section('content')
<style>
:root{--gy:#FF5533;--gy-d:#e04420;--txt:#1a202c;--muted:#718096;--brd:#e5e5e5;--bg:#f8f9fc;--card:#fff;}
body{background:var(--bg);color:var(--txt);}
.bk-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:1.5rem;padding:1.5rem 0 3rem;align-items:start;}
@@media(max-width:991px){.bk-layout{grid-template-columns:1fr;}}
.bk-card{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;margin-bottom:1rem;}
.bk-card-title{font-weight:800;font-size:1rem;margin-bottom:.9rem;display:flex;align-items:center;gap:.5rem;}
.bk-row{display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--brd);font-size:.9rem;}
.bk-row:last-child{border-bottom:none;}
.bk-row-label{color:var(--muted);}
.bk-row-val{font-weight:600;text-align:right;}
.bk-total-row{display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-top:2px solid var(--brd);margin-top:.3rem;}
.bk-total-label{font-weight:700;font-size:1rem;}
.bk-total-val{font-size:1.4rem;font-weight:800;color:var(--gy);}
.bk-btn-pay{width:100%;padding:.85rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;}
.bk-btn-pay:hover{background:var(--gy-d);}
.bk-status{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.3rem .7rem;font-size:.78rem;font-weight:700;}
.bk-status.pending{background:rgba(245,166,35,.15);color:#c07a00;}
.bk-status.paid{background:rgba(18,163,84,.15);color:#0a7a3f;}
</style>

{{-- Breadcrumb --}}
<div style="background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);">
    <div class="container">
        <a href="{{ route('b2c.home') }}" style="color:var(--muted);text-decoration:none;">Ana Sayfa</a>
        <span class="mx-1">/</span>
        <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" style="color:var(--muted);text-decoration:none;">Yat Kiralama</a>
        <span class="mx-1">/</span>
        <span>Rezervasyon</span>
        <span class="mx-1">/</span>
        <strong style="color:var(--txt);">{{ $leisureRequest->gtpnr }}</strong>
    </div>
</div>

<div class="container">
    @if(session('payment_success'))
        <div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i>Ödemeniz başarıyla alındı! Rezervasyonunuz kesinleşti.</div>
    @endif
    @if(session('payment_failed'))
        <div class="alert alert-danger mt-3"><i class="fas fa-times-circle me-2"></i>Ödeme tamamlanamadı. Lütfen tekrar deneyin.</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif

    <div class="d-flex align-items-center gap-2 mt-3 mb-1" style="font-size:.82rem;color:var(--muted);">
        <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" style="color:var(--muted);text-decoration:none;">Yat Kiralama</a>
        <span>/</span><span>Rezervasyon</span>
        <span>/</span><strong style="color:var(--txt);">{{ $leisureRequest->gtpnr }}</strong>
    </div>

    <div class="bk-layout">
        {{-- Sol: Özet --}}
        <div>
            {{-- Başarı banner --}}
            <div class="d-flex gap-3 align-items-center p-3 rounded-3 mb-3" style="background:rgba(18,163,84,.1);border:1px solid rgba(18,163,84,.3);">
                <i class="fas fa-check-circle fa-2x text-success"></i>
                <div>
                    <div class="fw-bold">Rezervasyonunuz oluşturuldu!</div>
                    @if($booking && (float)$booking->remaining_amount <= 0)
                        <div style="font-size:.85rem;color:var(--muted);">Referans: <strong>{{ $leisureRequest->gtpnr }}</strong> — Ödemeniz tamamlandı, yeriniz kesinleşti.</div>
                    @else
                        <div style="font-size:.85rem;color:var(--muted);">Referans: <strong>{{ $leisureRequest->gtpnr }}</strong> — Ödemeyi tamamlayın, yeriniz kesinleşsin.</div>
                    @endif
                </div>
            </div>

            {{-- Rezervasyon detayları --}}
            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-anchor text-primary"></i> Rezervasyon Detayları</div>
                <div class="bk-row"><span class="bk-row-label">Yat tipi</span><span class="bk-row-val">{{ $pkg?->name_tr ?? 'Yat Charter' }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Tarih</span><span class="bk-row-val">{{ optional($leisureRequest->service_date)->format('d.m.Y') }}</span></div>
                @if($detail?->start_time)
                    <div class="bk-row"><span class="bk-row-label">Kalkış saati</span><span class="bk-row-val">{{ $detail->start_time }}</span></div>
                @endif
                @if($detail?->duration_hours)
                    <div class="bk-row"><span class="bk-row-label">Süre</span><span class="bk-row-val">{{ $detail->duration_hours }} saat</span></div>
                @endif
                <div class="bk-row"><span class="bk-row-label">Kişi sayısı</span><span class="bk-row-val">{{ $leisureRequest->guest_count }}</span></div>
                @if($detail?->event_type)
                    <div class="bk-row"><span class="bk-row-label">Etkinlik</span><span class="bk-row-val">{{ $detail->event_type }}</span></div>
                @endif
                @if($leisureRequest->guest_name)
                    <div class="bk-row"><span class="bk-row-label">Yetkili</span><span class="bk-row-val">{{ $leisureRequest->guest_name }}</span></div>
                @endif
                @if($leisureRequest->guest_phone)
                    <div class="bk-row"><span class="bk-row-label">Telefon</span><span class="bk-row-val">{{ $leisureRequest->guest_phone }}</span></div>
                @endif
            </div>

            {{-- Dahil olanlar --}}
            @if($pkg && !empty($pkg->includes_tr))
            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-check-circle text-success"></i> Pakete Dahil</div>
                <ul style="list-style:none;padding:0;margin:0;">
                    @foreach($pkg->includes_tr as $inc)
                        <li class="d-flex gap-2 align-items-center py-1" style="font-size:.88rem;border-bottom:1px solid var(--brd);">
                            <i class="fas fa-check text-success fa-xs"></i>{{ $inc }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        {{-- Sağ: Ödeme paneli --}}
        <div>
            <div class="bk-card" style="position:sticky;top:80px;">
                <div class="bk-card-title"><i class="fas fa-receipt text-warning"></i> Ödeme Özeti</div>

                @if($booking)
                    <div class="bk-row">
                        <span class="bk-row-label">Durum</span>
                        <span class="bk-status {{ $booking->status === 'paid' ? 'paid' : 'pending' }}">
                            {{ $booking->status === 'paid' ? 'Ödendi' : 'Ödeme Bekliyor' }}
                        </span>
                    </div>
                    <div class="bk-row"><span class="bk-row-label">Toplam tutar</span><span class="bk-row-val">{{ number_format((float)$booking->total_amount,0,',','.') }} {{ $booking->currency }}</span></div>
                    <div class="bk-row"><span class="bk-row-label">Ödenen</span><span class="bk-row-val" style="color:#12a354;">{{ number_format((float)$booking->total_paid,0,',','.') }} {{ $booking->currency }}</span></div>
                    <div class="bk-total-row">
                        <span class="bk-total-label">Kalan</span>
                        <span class="bk-total-val">{{ number_format((float)$booking->remaining_amount,0,',','.') }} {{ $booking->currency }}</span>
                    </div>

                    @if((float)$booking->remaining_amount > 0)
                        <form method="POST" action="{{ route('b2c.leisure.payment.start', $booking) }}">
                            @csrf
                            <button type="submit" class="bk-btn-pay mt-2">
                                <i class="fas fa-lock me-2"></i>Ödemeyi Tamamla
                            </button>
                        </form>
                        <div class="text-center mt-2" style="font-size:.74rem;color:var(--muted);">
                            <i class="fas fa-shield-alt me-1 text-success"></i>256-bit SSL şifreleme ile güvenli ödeme
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2 p-3 rounded-3 mt-2" style="background:rgba(18,163,84,.1);border:1px solid rgba(18,163,84,.3);">
                            <i class="fas fa-check-circle text-success"></i>
                            <span style="font-size:.88rem;font-weight:600;">Ödeme tamamlandı</span>
                        </div>
                    @endif
                @endif

                <hr style="margin:1rem 0;">
                <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" class="d-flex align-items-center gap-2 text-decoration-none" style="font-size:.82rem;color:var(--muted);">
                    <i class="fas fa-arrow-left"></i> Ürün kataloğuna dön
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
