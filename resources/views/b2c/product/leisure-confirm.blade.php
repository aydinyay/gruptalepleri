@extends('b2c.layouts.app')
@section('title', 'Rezervasyon Talebiniz Alındı')
@push('head_styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
@endpush

@section('content')
<style>
:root{--gy:#FF5533;--gy-d:#e04420;--star:#f5a623;--txt:#1a202c;--muted:#718096;--brd:#e5e5e5;--bg:#f8f9fc;--card:#fff;}
body{background:var(--bg);color:var(--txt);}
.bk-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:1.5rem;padding:1.5rem 0 3rem;align-items:start;}
@media(max-width:991px){.bk-layout{grid-template-columns:1fr;}}
.bk-card{background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;margin-bottom:1rem;}
.bk-card-title{font-weight:800;font-size:1rem;margin-bottom:.9rem;display:flex;align-items:center;gap:.5rem;}
.bk-row{display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--brd);font-size:.9rem;}
.bk-row:last-child{border-bottom:none;}
.bk-row-label{color:var(--muted);}
.bk-row-val{font-weight:600;text-align:right;}
</style>

{{-- Breadcrumb --}}
<div style="background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);">
    <div class="container">
        <a href="{{ route('b2c.home') }}" style="color:var(--muted);text-decoration:none;">Ana Sayfa</a>
        <span class="mx-1">/</span>
        <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" style="color:var(--muted);text-decoration:none;">Yat Kiralama</a>
        <span class="mx-1">/</span>
        <strong>Talep Onayı</strong>
    </div>
</div>

<div class="container">
    <div class="d-flex align-items-center gap-2 mt-3 mb-1" style="font-size:.82rem;color:var(--muted);">
        <span>Rezervasyon Talebi</span>
        <span>/</span>
        <strong style="color:var(--txt);">{{ $inquiry['ref'] }}</strong>
    </div>

    <div class="bk-layout">
        {{-- Sol: Özet --}}
        <div>
            {{-- Başarı banner --}}
            <div class="d-flex gap-3 align-items-center p-3 rounded-3 mb-3" style="background:rgba(18,163,84,.1);border:1px solid rgba(18,163,84,.3);">
                <i class="fas fa-check-circle fa-2x text-success"></i>
                <div>
                    <div class="fw-bold">Rezervasyon talebiniz alındı!</div>
                    <div style="font-size:.85rem;color:var(--muted);">
                        Referans: <strong>{{ $inquiry['ref'] }}</strong> — En kısa sürede sizi arayacağız.
                    </div>
                </div>
            </div>

            {{-- Talep detayları --}}
            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-anchor text-primary"></i> Talep Detayları</div>
                <div class="bk-row"><span class="bk-row-label">Yat tipi</span><span class="bk-row-val">{{ $inquiry['package_name'] }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Tarih</span><span class="bk-row-val">{{ $inquiry['service_date'] }}</span></div>
                @if($inquiry['start_time'])
                    <div class="bk-row"><span class="bk-row-label">Kalkış saati</span><span class="bk-row-val">{{ $inquiry['start_time'] }}</span></div>
                @endif
                <div class="bk-row"><span class="bk-row-label">Süre</span><span class="bk-row-val">{{ $inquiry['duration_hours'] }} saat</span></div>
                <div class="bk-row"><span class="bk-row-label">Kişi sayısı</span><span class="bk-row-val">{{ $inquiry['guest_count'] }} kişi</span></div>
                @if($inquiry['event_type'])
                    <div class="bk-row"><span class="bk-row-label">Etkinlik</span><span class="bk-row-val">{{ $inquiry['event_type'] }}</span></div>
                @endif
                <div class="bk-row"><span class="bk-row-label">Yetkili</span><span class="bk-row-val">{{ $inquiry['guest_name'] }}</span></div>
                <div class="bk-row"><span class="bk-row-label">Telefon</span><span class="bk-row-val">{{ $inquiry['guest_phone'] }}</span></div>
                @if($inquiry['total'])
                    <div class="bk-row"><span class="bk-row-label">Tahmini tutar</span><span class="bk-row-val" style="color:var(--gy);">{{ $inquiry['total'] }}</span></div>
                @endif
                @if($inquiry['pier_name'])
                    <div class="bk-row"><span class="bk-row-label">Kalkış noktası</span><span class="bk-row-val">{{ $inquiry['pier_name'] }}</span></div>
                @endif
            </div>

            {{-- Dahil olanlar --}}
            @if(!empty($inquiry['includes']))
            <div class="bk-card">
                <div class="bk-card-title"><i class="fas fa-check-circle text-success"></i> Pakete Dahil</div>
                <ul style="list-style:none;padding:0;margin:0;">
                    @foreach($inquiry['includes'] as $inc)
                        <li class="d-flex gap-2 align-items-center py-1" style="font-size:.88rem;border-bottom:1px solid var(--brd);">
                            <i class="fas fa-check text-success fa-xs"></i>{{ $inc }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        {{-- Sağ: Durum paneli --}}
        <div>
            <div class="bk-card" style="position:sticky;top:80px;">
                <div class="bk-card-title"><i class="fas fa-hourglass-half text-warning"></i> Talep Durumu</div>

                <div class="d-flex align-items-center gap-2 p-3 rounded-3 mb-3"
                     style="background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.3);">
                    <i class="fas fa-phone text-warning"></i>
                    <div style="font-size:.88rem;">
                        <div class="fw-bold">En kısa sürede sizi arayacağız</div>
                        <div style="color:var(--muted);">Talebiniz ekibimize iletildi. Genellikle 4 saat içinde dönüş yapılır.</div>
                    </div>
                </div>

                <div style="font-size:.85rem;margin-bottom:.6rem;">
                    <i class="fas fa-receipt me-1 text-muted"></i>
                    <strong>Referans numaranız:</strong> {{ $inquiry['ref'] }}
                </div>

                @if($inquiry['total'])
                <div style="font-size:.85rem;margin-bottom:1rem;">
                    <i class="fas fa-tag me-1 text-muted"></i>
                    <strong>Tahmini tutar:</strong>
                    <span style="color:var(--gy);font-weight:700;">{{ $inquiry['total'] }}</span>
                    <span style="color:var(--muted);font-size:.78rem;">(kesin teklif telefonla verilir)</span>
                </div>
                @endif

                <hr style="margin:.8rem 0;">

                <div style="font-size:.78rem;color:var(--muted);line-height:1.6;">
                    <div><i class="fas fa-check-circle text-success me-1"></i> Ücretsiz iptal (24 saat öncesine kadar)</div>
                    <div><i class="fas fa-check-circle text-success me-1"></i> Özel kaptan hizmeti dahil</div>
                    <div><i class="fas fa-check-circle text-success me-1"></i> Yakıt ve marina ücreti dahil</div>
                </div>

                <hr style="margin:.8rem 0;">

                <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}"
                   class="d-flex align-items-center gap-2 text-decoration-none"
                   style="font-size:.82rem;color:var(--muted);">
                    <i class="fas fa-arrow-left"></i> Kataloga dön
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
