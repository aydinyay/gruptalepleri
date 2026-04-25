@extends('b2c.layouts.app')

@section('title', 'Talebiniz Alındı — ' . $talep->gtpnr)
@section('meta_description', 'Grup uçuş talebiniz başarıyla alındı. En kısa sürede sizinle iletişime geçeceğiz.')

@push('head_styles')
<style>
.confirm-page {
    min-height: calc(100vh - var(--nav-height));
    background: linear-gradient(160deg, #0f2444 0%, #1a3c6b 60%, #1e4d8c 100%);
    padding: 48px 0 64px;
    display: flex; align-items: flex-start;
}

.confirm-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    max-width: 600px;
    margin: 0 auto;
    overflow: hidden;
}

/* Başarı header */
.confirm-header {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    padding: 36px 32px;
    text-align: center;
    color: #fff;
}
.confirm-check {
    width: 72px; height: 72px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto 16px;
}
.confirm-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0 0 6px; }
.confirm-header p  { margin: 0; opacity: 0.85; font-size: 0.88rem; }

/* GTPNR kutusu */
.gtpnr-box {
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    border-radius: 12px;
    padding: 14px 18px;
    margin: 20px 28px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.gtpnr-box .label { font-size: 0.75rem; color: #166534; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.gtpnr-box .code  { font-size: 1.25rem; font-weight: 800; color: #15803d; font-family: monospace; letter-spacing: 2px; }
.btn-copy {
    border: 1.5px solid #86efac;
    border-radius: 8px;
    padding: 6px 12px;
    background: #fff;
    color: #166534;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
    flex-shrink: 0;
}
.btn-copy:hover { background: #dcfce7; }

/* Talep özeti */
.summary-section { padding: 20px 28px; }
.summary-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6b7a99;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 12px;
}
.summary-rows { display: flex; flex-direction: column; gap: 10px; }
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}
.sr-label { font-size: 0.8rem; color: #718096; flex-shrink: 0; }
.sr-value { font-size: 0.88rem; font-weight: 600; color: #1a202c; text-align: right; }
.segment-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eef2fb;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1a3c6b;
    margin-bottom: 4px;
}
.segment-pill .arrow { opacity: 0.5; }

/* Beklenti kutusu */
.expect-box {
    margin: 0 28px;
    background: #fef9c3;
    border: 1px solid #fde047;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.expect-box .icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 1px; }
.expect-box p { margin: 0; font-size: 0.82rem; color: #854d0e; line-height: 1.5; }

/* CTA butonlar */
.confirm-actions {
    padding: 20px 28px 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.btn-new-request {
    padding: 12px;
    border: 1.5px solid #dde4f5;
    border-radius: 10px;
    background: #fff;
    color: #1a3c6b;
    font-size: 0.88rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: all 0.15s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-new-request:hover { background: #f0f4ff; border-color: #1a3c6b; }
.btn-home {
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: var(--gr-accent);
    color: #fff;
    font-size: 0.88rem;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    transition: all 0.18s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-home:hover { background: #e0401e; }

@@media (max-width: 576px) {
    .confirm-card { border-radius: 0; }
    .confirm-header { padding: 28px 20px; }
    .gtpnr-box { margin: 16px 18px 0; }
    .summary-section { padding: 16px 18px; }
    .expect-box { margin: 0 18px; }
    .confirm-actions { padding: 16px 18px 24px; grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="confirm-page">
    <div class="container px-3 w-100">

        <div class="confirm-card">

            {{-- Başarı header --}}
            <div class="confirm-header">
                <div class="confirm-check">✓</div>
                <h1>Talebiniz Alındı!</h1>
                <p>En kısa sürede size geri döneceğiz.</p>
            </div>

            {{-- GTPNR --}}
            <div class="gtpnr-box">
                <div>
                    <div class="label">Talep Referans No</div>
                    <div class="code" id="gtpnr-code">{{ $talep->gtpnr }}</div>
                </div>
                <button class="btn-copy" onclick="copyGtpnr()">
                    <i class="bi bi-copy me-1"></i> Kopyala
                </button>
            </div>

            {{-- Talep özeti --}}
            <div class="summary-section">
                <div class="summary-title">Talep Özeti</div>
                <div class="summary-rows">

                    {{-- Rotalar --}}
                    <div class="summary-row">
                        <span class="sr-label">Rota</span>
                        <div class="text-end">
                            @foreach($talep->segments->sortBy('order') as $seg)
                            <div class="segment-pill">
                                <strong>{{ strtoupper($seg->from_iata) }}</strong>
                                <span class="arrow">→</span>
                                <strong>{{ strtoupper($seg->to_iata) }}</strong>
                                @if($seg->from_city || $seg->to_city)
                                <span class="text-muted" style="font-weight:400;font-size:0.75rem;">
                                    {{ $seg->from_city }}→{{ $seg->to_city }}
                                </span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tarihler --}}
                    <div class="summary-row">
                        <span class="sr-label">
                            {{ $talep->segments->count() > 1 ? 'Tarihler' : 'Tarih' }}
                        </span>
                        <div class="sr-value">
                            @foreach($talep->segments->sortBy('order') as $seg)
                                <div>{{ \Carbon\Carbon::parse($seg->departure_date)->format('d.m.Y') }}
                                    @php
                                        $slotLabels = ['sabah'=>'Sabah','ogle'=>'Öğlen','aksam'=>'Akşam','esnek'=>'Esnek'];
                                    @endphp
                                    <span class="text-muted fw-normal" style="font-size:0.78rem;">
                                        ({{ $slotLabels[$seg->departure_time_slot] ?? $seg->departure_time_slot }})
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- PAX --}}
                    <div class="summary-row">
                        <span class="sr-label">Yolcu Sayısı</span>
                        <span class="sr-value">
                            {{ $talep->pax_total }} kişi
                            @if($talep->pax_adult || $talep->pax_child || $talep->pax_infant)
                            <span class="text-muted fw-normal" style="font-size:0.78rem;">
                                (Y:{{ $talep->pax_adult }} Ç:{{ $talep->pax_child }} B:{{ $talep->pax_infant }})
                            </span>
                            @endif
                        </span>
                    </div>

                    {{-- Uçuş tipi --}}
                    <div class="summary-row">
                        <span class="sr-label">Uçuş Tipi</span>
                        <span class="sr-value">
                            @php $tripLabels = ['one_way'=>'Tek Yön','round_trip'=>'Gidiş-Dönüş','multi'=>'Çoklu Uçuş']; @endphp
                            {{ $tripLabels[$talep->trip_type] ?? $talep->trip_type }}
                        </span>
                    </div>

                    {{-- İletişim --}}
                    <div class="summary-row">
                        <span class="sr-label">İletişim</span>
                        <div class="sr-value">
                            {{ $talep->agency_name }}<br>
                            <span class="text-muted fw-normal" style="font-size:0.78rem;">{{ $talep->phone }}</span>
                        </div>
                    </div>

                    @if($talep->hotel_needed)
                    <div class="summary-row">
                        <span class="sr-label">Ekstra</span>
                        <span class="sr-value">🏨 Otel de isteniyor</span>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Email gönderildi notu --}}
            <div class="expect-box" style="background:#eff6ff;border-color:#93c5fd;">
                <span class="icon">📧</span>
                <p style="color:#1e40af;"><strong>{{ $talep->email }}</strong> adresinize takip linki gönderildi. Bu link ile dilediğiniz zaman talebinizin durumunu görebilir, teklifleri inceleyebilir ve kabul edebilirsiniz.</p>
            </div>

            <div class="expect-box mt-3">
                <span class="icon">⏱️</span>
                <p>Ekibimiz talebinizi inceleyerek ortalama <strong>2–4 saat içinde</strong> size dönecektir. Referans: <strong>{{ $talep->gtpnr }}</strong></p>
            </div>

            {{-- Sigorta upsell --}}
            <div style="margin:0 24px 20px;background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1.5px solid #6ee7b7;border-radius:12px;padding:1.1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
                <div>
                    <div style="font-size:.78rem;font-weight:700;color:#059669;margin-bottom:.25rem;">🛡 Uçuşunuzu Güvence Altına Alın</div>
                    <div style="font-size:.9rem;font-weight:600;color:#064e3b;">Seyahat sigortası — Anında poliçe, SMS + e-posta ile teslim</div>
                </div>
                <a href="{{ route('b2c.sigorta.create') }}" style="background:#059669;color:#fff;font-weight:700;font-size:.85rem;text-decoration:none;padding:9px 18px;border-radius:8px;white-space:nowrap;">
                    Sigorta Yaptır →
                </a>
            </div>

            {{-- Eylem butonları --}}
            <div class="confirm-actions">
                <a href="{{ route('b2c.flight.create') }}" class="btn-new-request">
                    <i class="bi bi-plus-circle"></i> Yeni Talep
                </a>
                <a href="{{ $trackUrl }}" class="btn-home">
                    <i class="bi bi-search"></i> Talebimi Takip Et
                </a>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function copyGtpnr() {
    const code = document.getElementById('gtpnr-code').textContent.trim();
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Kopyalandı!';
        btn.style.background = '#dcfce7';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-copy me-1"></i> Kopyala'; btn.style.background = ''; }, 2000);
    });
}
</script>
@endpush
