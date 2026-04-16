<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transfer Voucher — {{ $booking->booking_ref }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f4f7fb;
    color: #1e293b;
    padding: 32px 16px;
}
.voucher {
    max-width: 680px;
    margin: 0 auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,.1);
    overflow: hidden;
}
.v-header {
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
    padding: 24px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.v-header .brand { color: #fff; font-size: 1.25rem; font-weight: 800; }
.v-header .brand small { color: rgba(255,255,255,.65); font-size: .75rem; display: block; font-weight: 400; }
.v-header .ref-box { text-align: right; }
.v-header .ref-box .ref { color: #fff; font-size: 1.1rem; font-weight: 800; letter-spacing: 1px; }
.v-header .ref-box small { color: rgba(255,255,255,.6); font-size: .72rem; display: block; }
.v-status {
    background: #d1fae5;
    color: #065f46;
    padding: 10px 32px;
    font-size: .88rem;
    font-weight: 700;
    text-align: center;
    letter-spacing: .3px;
}
.v-body { padding: 24px 32px; }
.v-section { margin-bottom: 20px; }
.v-section-title {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #1a3c6b;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 6px;
    margin-bottom: 12px;
}
.v-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; }
.v-row { display: flex; flex-direction: column; }
.v-label { font-size: .73rem; color: #94a3b8; font-weight: 500; margin-bottom: 2px; }
.v-val { font-size: .92rem; font-weight: 600; }
.v-amount { font-size: 1.5rem; font-weight: 800; color: #0f2444; margin-top: 4px; }
.v-footer {
    background: #f8faff;
    border-top: 1px solid #e2e8f0;
    padding: 14px 32px;
    text-align: center;
    font-size: .75rem;
    color: #94a3b8;
}
.print-btn {
    display: block;
    margin: 20px auto 0;
    background: #1a3c6b;
    color: #fff;
    border: none;
    padding: 12px 32px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    width: fit-content;
}

@@media print {
    body { background: #fff; padding: 0; }
    .voucher { box-shadow: none; border-radius: 0; max-width: 100%; }
    .print-btn { display: none; }
    .no-print { display: none; }
}
</style>
</head>
<body>

@php
    $snap     = $booking->price_snapshot_json ?? [];
    $snapData = $snap['snapshot'] ?? [];
    $airport  = $snapData['airport'] ?? [];
    $zone     = $snapData['zone'] ?? [];
    $policy   = $booking->supplier_policy_snapshot_json ?? [];
    $dirLabel = ['ARR' => 'Varış (Havalimanı → Otel)', 'DEP' => 'Gidiş (Otel → Havalimanı)', 'BOTH' => 'Gidiş-Dönüş'][$booking->direction] ?? $booking->direction;
    $operation = $snap['operation_details'] ?? [];
@endphp

<div class="voucher">
    <div class="v-header">
        <div class="brand">
            GrupRezervasyonları.com
            <small>Transfer Hizmet Fişi</small>
        </div>
        <div class="ref-box">
            <div class="ref">{{ $booking->booking_ref }}</div>
            <small>Rezervasyon Tarihi: {{ $booking->created_at->format('d.m.Y') }}</small>
        </div>
    </div>

    <div class="v-status">
        ✓ Rezervasyon Onaylandı
        @if($booking->confirmed_at)
            — {{ $booking->confirmed_at->format('d.m.Y H:i') }}
        @endif
    </div>

    <div class="v-body">

        {{-- Transfer Bilgileri --}}
        <div class="v-section">
            <div class="v-section-title">Transfer Bilgileri</div>
            <div class="v-grid">
                <div class="v-row">
                    <span class="v-label">Havalimanı</span>
                    <span class="v-val">{{ ($airport['code'] ?? '') }} — {{ ($airport['name'] ?? $booking->airport?->name ?? '') }}</span>
                </div>
                <div class="v-row">
                    <span class="v-label">Bölge / Otel</span>
                    <span class="v-val">{{ $zone['name'] ?? $booking->zone?->name ?? '' }}</span>
                </div>
                <div class="v-row">
                    <span class="v-label">Yön</span>
                    <span class="v-val">{{ $dirLabel }}</span>
                </div>
                <div class="v-row">
                    <span class="v-label">Araç</span>
                    <span class="v-val">{{ $booking->vehicleType?->name ?? 'Transfer Aracı' }}</span>
                </div>
                <div class="v-row">
                    <span class="v-label">Kalkış Tarihi / Saati</span>
                    <span class="v-val">{{ $booking->pickup_at->format('d.m.Y H:i') }}</span>
                </div>
                @if($booking->return_at)
                <div class="v-row">
                    <span class="v-label">Dönüş Tarihi / Saati</span>
                    <span class="v-val">{{ $booking->return_at->format('d.m.Y H:i') }}</span>
                </div>
                @endif
                <div class="v-row">
                    <span class="v-label">Yolcu Sayısı</span>
                    <span class="v-val">{{ $booking->pax }} kişi</span>
                </div>
            </div>
        </div>

        {{-- Müşteri --}}
        <div class="v-section">
            <div class="v-section-title">Müşteri Bilgileri</div>
            <div class="v-grid">
                <div class="v-row">
                    <span class="v-label">Ad Soyad</span>
                    <span class="v-val">{{ $booking->b2c_contact_name }}</span>
                </div>
                <div class="v-row">
                    <span class="v-label">Telefon</span>
                    <span class="v-val">{{ $booking->b2c_contact_phone }}</span>
                </div>
                <div class="v-row">
                    <span class="v-label">E-posta</span>
                    <span class="v-val">{{ $booking->b2c_contact_email }}</span>
                </div>
            </div>
        </div>

        {{-- Operasyon Notları --}}
        @if(!empty($operation['flight_number']) || !empty($operation['terminal']) || !empty($operation['pickup_sign_name']))
        <div class="v-section">
            <div class="v-section-title">Operasyon Notları</div>
            <div class="v-grid">
                @if(!empty($operation['flight_number']))
                <div class="v-row"><span class="v-label">Uçuş No</span><span class="v-val">{{ $operation['flight_number'] }}</span></div>
                @endif
                @if(!empty($operation['terminal']))
                <div class="v-row"><span class="v-label">Terminal</span><span class="v-val">{{ $operation['terminal'] }}</span></div>
                @endif
                @if(!empty($operation['pickup_sign_name']))
                <div class="v-row"><span class="v-label">Karşılama Tabelası</span><span class="v-val">{{ $operation['pickup_sign_name'] }}</span></div>
                @endif
                @if(!empty($operation['exact_pickup_address']))
                <div class="v-row"><span class="v-label">Alış Adresi</span><span class="v-val">{{ $operation['exact_pickup_address'] }}</span></div>
                @endif
            </div>
        </div>
        @endif

        {{-- Ödeme --}}
        <div class="v-section">
            <div class="v-section-title">Ödeme</div>
            <div class="v-amount">{{ number_format($booking->total_amount, 0, ',', '.') }} {{ $booking->currency }}</div>
            <div style="font-size:.8rem;color:#64748b;margin-top:4px;">Ödeme alındı ✓</div>
        </div>

        {{-- İptal Politikası --}}
        @if(!empty($policy['free_cancel_before_minutes']))
        <div class="v-section">
            <div class="v-section-title">İptal Koşulları</div>
            <p style="font-size:.85rem;color:#475569;">
                Kalkıştan {{ $policy['free_cancel_before_minutes'] }} dakika öncesine kadar ücretsiz iptal.
                @if(isset($policy['refund_percent_after_deadline']))
                    Sonrasında iade oranı: %{{ $policy['refund_percent_after_deadline'] }}.
                @endif
            </p>
        </div>
        @endif

    </div>

    <div class="v-footer">
        Bu fiş GrupRezervasyonları.com tarafından düzenlenmiştir.
        Sorunlarınız için: destek@gruprezervasyonlari.com
    </div>
</div>

<div class="no-print" style="text-align:center;">
    <button class="print-btn" onclick="window.print()">🖨 Yazdır / PDF Kaydet</button>
    <p style="margin-top:10px;font-size:.82rem;color:#94a3b8;">
        Tarayıcınızın yazdır iletişim kutusundan "PDF olarak kaydet" seçeneğini kullanabilirsiniz.
    </p>
</div>

</body>
</html>
