@extends('b2c.layouts.app')
@section('title', 'Rezervasyon — ' . $order->order_ref)

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
.bk-row-val{font-weight:600;text-align:right;max-width:60%;}
.bk-total-row{display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-top:2px solid var(--brd);margin-top:.3rem;}
.bk-total-label{font-weight:700;font-size:1rem;}
.bk-total-val{font-size:1.4rem;font-weight:800;color:var(--gy);}
.bk-btn-pay{width:100%;padding:.85rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;}
.bk-btn-pay:hover{background:var(--gy-d);}
.bk-status{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.3rem .7rem;font-size:.78rem;font-weight:700;}
.bk-status.pending{background:rgba(245,166,35,.15);color:#c07a00;}
.bk-status.paid{background:rgba(18,163,84,.15);color:#0a7a3f;}
.bk-status.inquiry{background:rgba(66,153,225,.15);color:#2b6cb0;}
</style>

@php
$isFixed   = $order->status === 'pending' && $order->total_price > 0;
$isInquiry = in_array($order->status, ['pending_quote', 'quote_sent']);
$isPaid    = $order->payment_status === 'paid';
$paidPayment = $order->payments->firstWhere('status', 'paid');
@endphp

{{-- Breadcrumb --}}
<div style="background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);">
    <div class="container">
        <a href="{{ lroute('b2c.home') }}" style="color:var(--muted);text-decoration:none;">Ana Sayfa</a>
        @if($order->item?->category)
            <span class="mx-1">/</span>
            <a href="{{ lroute('b2c.catalog.category', $order->item->category->slug) }}" style="color:var(--muted);text-decoration:none;">{{ $order->item->category->name }}</a>
        @endif
        <span class="mx-1">/</span><strong style="color:var(--txt);">{{ $order->order_ref }}</strong>
    </div>
</div>

<div class="container">
    @if(session('payment_success'))
        <div class="alert alert-success mt-3"><i class="bi bi-check-circle-fill me-2"></i>Ödemeniz başarıyla alındı! Rezervasyonunuz kesinleşti.</div>
    @endif
    @if(session('payment_failed'))
        <div class="alert alert-danger mt-3"><i class="bi bi-x-circle-fill me-2"></i>Ödeme tamamlanamadı. Lütfen tekrar deneyin.</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif

    <div class="d-flex align-items-center gap-2 mt-3 mb-1" style="font-size:.82rem;color:var(--muted);">
        <span>Rezervasyon</span>
        <span>/</span>
        <strong style="color:var(--txt);">{{ $order->order_ref }}</strong>
    </div>

    <div class="bk-layout">
        {{-- Sol: Özet --}}
        <div>
            {{-- Başarı banner --}}
            <div class="d-flex gap-3 align-items-center p-3 rounded-3 mb-3"
                 style="background:rgba(18,163,84,.1);border:1px solid rgba(18,163,84,.3);">
                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                <div>
                    <div class="fw-bold">
                        @if($isPaid)Rezervasyonunuz kesinleşti!
                        @elseif($isInquiry)Talebiniz alındı!
                        @else Rezervasyonunuz oluşturuldu!
                        @endif
                    </div>
                    <div style="font-size:.85rem;color:var(--muted);">
                        Referans: <strong>{{ $order->order_ref }}</strong>
                        @if($isPaid) — Ödemeniz tamamlandı.
                        @elseif($isInquiry) — En kısa sürede sizi arayacağız.
                        @else — Ödemeyi tamamlayın, yeriniz kesinleşsin.
                        @endif
                    </div>
                </div>
            </div>

            {{-- Rezervasyon detayları --}}
            <div class="bk-card">
                <div class="bk-card-title"><i class="bi bi-clipboard-check text-primary"></i> Rezervasyon Detayları</div>
                <div class="bk-row"><span class="bk-row-label">Hizmet</span><span class="bk-row-val">{{ $order->item_title ?? $order->item?->title }}</span></div>
                @if($order->service_date)
                    <div class="bk-row"><span class="bk-row-label">Tarih</span><span class="bk-row-val">{{ $order->service_date->format('d.m.Y') }}</span></div>
                @endif
                <div class="bk-row"><span class="bk-row-label">Kişi sayısı</span><span class="bk-row-val">{{ $order->pax_count }} kişi</span></div>
                @if($order->event_type)
                    <div class="bk-row"><span class="bk-row-label">Etkinlik</span><span class="bk-row-val">{{ $order->event_type }}</span></div>
                @endif
                <div class="bk-row"><span class="bk-row-label">Ad Soyad</span><span class="bk-row-val">{{ $order->guest_name }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Telefon</span><span class="bk-row-val">{{ $order->guest_phone }}</span></div>
                @if($order->guest_email)
                    <div class="bk-row"><span class="bk-row-label">E-posta</span><span class="bk-row-val">{{ $order->guest_email }}</span></div>
                @endif
                @if($order->notes)
                    <div class="bk-row"><span class="bk-row-label">Notlar</span><span class="bk-row-val">{{ $order->notes }}</span></div>
                @endif
            </div>
        </div>

        {{-- Sağ: Ödeme paneli --}}
        <div>
            <div class="bk-card" style="position:sticky;top:80px;">
                @if($isInquiry)
                    {{-- Quote/inquiry: no payment --}}
                    <div class="bk-card-title"><i class="bi bi-telephone-fill text-warning"></i> Talep Durumu</div>
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 mb-3"
                         style="background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.3);">
                        <i class="bi bi-telephone text-warning"></i>
                        <div style="font-size:.88rem;">
                            <div class="fw-bold">En kısa sürede sizi arayacağız</div>
                            <div style="color:var(--muted);">Genellikle 4 saat içinde dönüş yapılır.</div>
                        </div>
                    </div>
                    <div style="font-size:.85rem;margin-bottom:.6rem;">
                        <i class="bi bi-receipt me-1 text-muted"></i>
                        <strong>Referans:</strong> {{ $order->order_ref }}
                    </div>
                @else
                    {{-- Fixed / paid --}}
                    <div class="bk-card-title"><i class="bi bi-receipt text-warning"></i> Ödeme Özeti</div>
                    <div class="bk-row">
                        <span class="bk-row-label">Durum</span>
                        <span class="bk-status {{ $isPaid ? 'paid' : 'pending' }}">
                            {{ $isPaid ? 'Ödendi' : 'Ödeme Bekliyor' }}
                        </span>
                    </div>
                    @if($order->product_type === 'charter')
                        <div class="bk-row">
                            <span class="bk-row-label">Fiyatlandırma</span>
                            <span class="bk-row-val">Uçuş başına sabit fiyat</span>
                        </div>
                    @else
                        @if($order->unit_price)
                            <div class="bk-row">
                                <span class="bk-row-label">Birim fiyat</span>
                                <span class="bk-row-val">{{ number_format((float)$order->unit_price,0,',','.') }} {{ $order->currency }}</span>
                            </div>
                        @endif
                        <div class="bk-row">
                            <span class="bk-row-label">Kişi sayısı</span>
                            <span class="bk-row-val">× {{ $order->pax_count }}</span>
                        </div>
                    @endif
                    <div class="bk-total-row">
                        <span class="bk-total-label">{{ $isPaid ? 'Ödenen' : 'Toplam' }}</span>
                        <span class="bk-total-val">{{ number_format((float)$order->total_price,0,',','.') }} {{ $order->currency }}</span>
                    </div>

                    @if($isPaid)
                        <div class="d-flex align-items-center gap-2 p-3 rounded-3 mt-2"
                             style="background:rgba(18,163,84,.1);border:1px solid rgba(18,163,84,.3);">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span style="font-size:.88rem;font-weight:600;">Ödeme tamamlandı</span>
                        </div>
                    @else
                        <form method="POST" action="{{ lroute('b2c.guest.payment.start', $order) }}">
                            @csrf
                            <button type="submit" class="bk-btn-pay mt-2">
                                <i class="bi bi-lock-fill me-2"></i>Ödemeyi Tamamla
                            </button>
                        </form>
                        <div class="text-center mt-2" style="font-size:.74rem;color:var(--muted);">
                            <i class="bi bi-shield-check me-1 text-success"></i>256-bit SSL şifreleme ile güvenli ödeme
                        </div>
                    @endif
                @endif

                <hr style="margin:1rem 0;">
                <div style="font-size:.78rem;color:var(--muted);line-height:1.7;">
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Ücretsiz iptal (24 saat öncesine kadar)</div>
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Onaylı tedarikçi</div>
                </div>
                <hr style="margin:.8rem 0;">
                @if($order->item)
                    <a href="{{ lroute('b2c.product.show', $order->item->slug) }}"
                       class="d-flex align-items-center gap-2 text-decoration-none"
                       style="font-size:.82rem;color:var(--muted);">
                        <i class="bi bi-arrow-left"></i> Ürüne dön
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
