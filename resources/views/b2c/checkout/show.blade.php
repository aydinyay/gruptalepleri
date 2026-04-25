@extends('b2c.layouts.app')
@section('title', 'Ödeme — Grup Rezervasyonları')

@push('head_styles')
<style>
.checkout-wrap {
    max-width: 960px; margin: 0 auto; padding: 40px 20px 60px;
    display: grid; grid-template-columns: 1fr 360px; gap: 28px;
}
@@media (max-width: 768px) { .checkout-wrap { grid-template-columns: 1fr; } }
.ck-section {
    background: #fff; border: 1px solid #e8eef5; border-radius: 14px;
    padding: 24px; margin-bottom: 20px;
}
.ck-section-title { font-size: 1rem; font-weight: 800; color: #1a202c; margin-bottom: 16px; }
.ck-label { font-size: .82rem; font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
.ck-input {
    width: 100%; padding: 10px 13px;
    border: 1.5px solid #e2e8f0; border-radius: 9px;
    font-size: .93rem; color: #1a202c; background: #fafbfc;
    margin-bottom: 14px;
}
.ck-input:focus { border-color: #1a3c6b; outline: none; background: #fff; }
.ck-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@@media (max-width: 480px) { .ck-row { grid-template-columns: 1fr; } }
.ck-summary { background: #fff; border: 1px solid #e8eef5; border-radius: 14px; padding: 24px; align-self: start; }
.ck-item-title { font-weight: 700; color: #1a202c; font-size: .95rem; margin-bottom: 6px; }
.ck-item-meta { font-size: .82rem; color: #718096; margin-bottom: 3px; }
.ck-divider { border: none; border-top: 1px solid #f0f4f8; margin: 14px 0; }
.ck-total-row { display: flex; justify-content: space-between; align-items: center; }
.ck-total-label { font-size: .88rem; color: #718096; }
.ck-total-amount { font-size: 1.3rem; font-weight: 800; color: #1a202c; }
.ck-submit {
    width: 100%; padding: 13px;
    background: #1a3c6b; color: #fff;
    border: none; border-radius: 10px;
    font-size: 1rem; font-weight: 700;
    cursor: pointer; margin-top: 14px;
    transition: background .15s;
}
.ck-submit:hover { background: #152f56; }
.ck-trust { display: flex; align-items: center; gap: 8px; font-size: .78rem; color: #718096; margin-top: 8px; }
.ck-trust i { color: #48bb78; }
</style>
@endpush

@section('content')
<div style="background:#f8f9fc;border-bottom:1px solid #eee;padding:14px 0;margin-bottom:8px;">
    <div style="max-width:960px;margin:0 auto;padding:0 20px;font-size:.85rem;color:#718096;">
        <a href="{{ lroute('b2c.home') }}" style="color:#718096;text-decoration:none;">Ana Sayfa</a>
        <span style="margin:0 8px;">›</span> Ödeme
    </div>
</div>

<div class="checkout-wrap">

    {{-- Sol: İletişim + Notlar --}}
    <div>
        @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ lroute('b2c.checkout.create') }}">
            @csrf

            <div class="ck-section">
                <div class="ck-section-title"><i class="bi bi-person-fill me-2" style="color:#1a3c6b;"></i>İletişim Bilgileri</div>
                <div class="ck-row">
                    <div>
                        <label class="ck-label">Ad Soyad <span style="color:#e53e3e;">*</span></label>
                        <input type="text" name="contact_name" class="ck-input"
                               value="{{ old('contact_name', Auth::guard('b2c')->user()->name ?? '') }}"
                               placeholder="Tam adınız" required>
                    </div>
                    <div>
                        <label class="ck-label">Telefon <span style="color:#e53e3e;">*</span></label>
                        <input type="tel" name="contact_phone" class="ck-input"
                               value="{{ old('contact_phone', Auth::guard('b2c')->user()->phone ?? '') }}"
                               placeholder="+90 5xx xxx xx xx" required>
                    </div>
                </div>
                <label class="ck-label">E-posta <span style="color:#e53e3e;">*</span></label>
                <input type="email" name="contact_email" class="ck-input"
                       value="{{ old('contact_email', Auth::guard('b2c')->user()->email ?? '') }}"
                       placeholder="ornek@email.com" required>
            </div>

            <div class="ck-section">
                <div class="ck-section-title"><i class="bi bi-chat-left-text me-2" style="color:#1a3c6b;"></i>Ek Bilgi / Notlar</div>
                <label class="ck-label">Özel istek veya not (isteğe bağlı)</label>
                <textarea name="notes" class="ck-input" rows="3"
                          placeholder="Özel bir isteğiniz var mı? Bize bildirin.">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="ck-submit">
                <i class="bi bi-lock-fill me-2"></i>Siparişi Onayla
            </button>
            <div class="ck-trust"><i class="bi bi-check-circle-fill"></i> Güvenli ödeme · 256-bit SSL</div>
            <div class="ck-trust"><i class="bi bi-check-circle-fill"></i> Ücretsiz iptal (24 saat öncesine kadar)</div>
        </form>
    </div>

    {{-- Sağ: Sepet özeti --}}
    <div>
        <div class="ck-summary">
            <div style="font-size:1rem;font-weight:800;color:#1a202c;margin-bottom:16px;">Sipariş Özeti</div>

            @php $grandTotal = 0; $cartCurrency = 'TRY'; @endphp
            @foreach($cart as $rowId => $row)
            @php
                $durationHours = $row['duration_hours'] ?? null;
                $lineTotal = $durationHours
                    ? ($row['base_price'] ?? 0) * $durationHours
                    : ($row['base_price'] ?? 0) * ($row['pax_count'] ?? 1);
                $grandTotal += $lineTotal;
                if (!empty($row['currency'])) $cartCurrency = $row['currency'];
            @endphp
            <div style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <div style="flex:1;">
                    <div class="ck-item-title">{{ $row['title'] }}</div>
                    @if(!empty($row['service_date']))
                    <div class="ck-item-meta"><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($row['service_date'])->format('d M Y') }}</div>
                    @endif
                    @if($durationHours)
                    <div class="ck-item-meta"><i class="bi bi-clock me-1"></i>{{ $durationHours }} saat</div>
                    @else
                    <div class="ck-item-meta"><i class="bi bi-people me-1"></i>{{ $row['pax_count'] }} kişi</div>
                    @endif
                    @if(!empty($row['event_type']))
                    <div class="ck-item-meta"><i class="bi bi-star me-1"></i>{{ $row['event_type'] }}</div>
                    @endif
                    @if($row['pricing_type'] === 'fixed' && $row['base_price'])
                    <div class="ck-item-meta">
                        {{ number_format($row['base_price'],0,',','.') }} {{ $row['currency'] }}
                        @if($durationHours) × {{ $durationHours }} saat @else × {{ $row['pax_count'] }} kişi @endif
                        = <strong>{{ number_format($lineTotal,0,',','.') }} {{ $row['currency'] }}</strong>
                    </div>
                    @endif
                </div>
                <form method="POST" action="{{ lroute('b2c.cart.remove', $rowId) }}" style="flex-shrink:0;">
                    @csrf @method('DELETE')
                    <button type="submit" title="Kaldır"
                            style="border:none;background:none;color:#e53e3e;font-size:1rem;cursor:pointer;padding:0;">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </form>
            </div>
            <hr class="ck-divider">
            @endforeach

            <div class="ck-total-row">
                <span class="ck-total-label">Toplam</span>
                <span class="ck-total-amount">{{ number_format($grandTotal,0,',','.') }} {{ $cartCurrency }}</span>
            </div>

            <div style="margin-top:16px;padding:12px 14px;background:#f0f7ff;border-radius:9px;font-size:.82rem;color:#1a3c6b;">
                <i class="bi bi-info-circle me-1"></i>Siparişiniz onaylandıktan sonra ödeme bağlantısı e-postanıza iletilecektir.
            </div>
        </div>
    </div>

</div>
@endsection
