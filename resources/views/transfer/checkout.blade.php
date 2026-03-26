<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Checkout - GrupTalepleri</title>
    @if(in_array($roleContext, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .gt-transfer-checkout-hero {
            border-radius: 18px;
            background: linear-gradient(130deg, #0f1f48 0%, #1a3b7a 100%);
            color: #fff;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 18px 32px rgba(15, 23, 42, .16);
        }
        .gt-transfer-checkout-card {
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, .08);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }
        .gt-transfer-kv {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .45rem 0;
            border-bottom: 1px dashed rgba(100, 116, 139, .24);
            font-size: .92rem;
        }
        .gt-transfer-kv:last-child { border-bottom: 0; }
    </style>
</head>
<body class="theme-scope">
<x-dynamic-component :component="$navbarComponent" active="transfer" />

<div class="container py-4">
    <div class="gt-transfer-checkout-hero mb-3">
        <div class="text-uppercase small fw-semibold opacity-75">Transfer Checkout</div>
        <h1 class="h3 fw-bold mb-1">Rezervasyon onayi</h1>
        <p class="mb-0 opacity-75">Teklifi onaylayip odeme adimina gecin. Teklif suresi bitmeden islemi tamamlayin.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card gt-transfer-checkout-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Transfer ozeti</h2>
                    <div class="gt-transfer-kv"><span>Supplier</span><strong>{{ $quote->supplier?->company_name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Arac tipi</span><strong>{{ $quote->vehicleType?->name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Rota</span><strong>{{ $quote->airport?->code }} -> {{ $quote->zone?->name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Yon</span><strong>{{ $quote->direction }}</strong></div>
                    <div class="gt-transfer-kv"><span>Alis</span><strong>{{ optional($quote->pickup_at)->format('d.m.Y H:i') }}</strong></div>
                    @if($quote->return_at)
                        <div class="gt-transfer-kv"><span>Donus</span><strong>{{ optional($quote->return_at)->format('d.m.Y H:i') }}</strong></div>
                    @endif
                    <div class="gt-transfer-kv"><span>PAX</span><strong>{{ $quote->pax }}</strong></div>
                    <div class="gt-transfer-kv"><span>Tahmini sure</span><strong>{{ $quote->duration_minutes }} dk</strong></div>
                    <div class="gt-transfer-kv"><span>Mesafe</span><strong>{{ number_format((float) $quote->distance_km, 1) }} km</strong></div>
                    <div class="gt-transfer-kv"><span>Toplam</span><strong>{{ number_format((float) $quote->total_amount, 2, ',', '.') }} {{ $quote->currency }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card gt-transfer-checkout-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Yolcu bilgileri</h2>
                        <span class="badge text-bg-warning" id="ttlBadge">{{ $ttlSeconds }} sn</span>
                    </div>

                    <form method="POST" action="{{ $bookEndpoint }}" class="d-grid gap-3">
                        @csrf
                        <div>
                            <label class="form-label">Iletisim adi</label>
                            <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', auth()->user()->name) }}" required>
                        </div>
                        <div>
                            <label class="form-label">Telefon</label>
                            <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', auth()->user()->phone) }}" required>
                        </div>
                        <div>
                            <label class="form-label">Yolcu isimleri</label>
                            <textarea name="passenger_names" class="form-control" rows="2" placeholder="Orn: Ali Yilmaz, Ayse Kaya">{{ old('passenger_names') }}</textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Ucus numarasi</label>
                                <input type="text" name="flight_number" class="form-control" value="{{ old('flight_number') }}" placeholder="Orn: TK1983">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Terminal</label>
                                <input type="text" name="terminal" class="form-control" value="{{ old('terminal') }}" placeholder="Orn: Dis Hatlar">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Karsilama tabela adi</label>
                            <input type="text" name="pickup_sign_name" class="form-control" value="{{ old('pickup_sign_name', auth()->user()->name) }}" placeholder="Orn: Aydin Yaylaciklilar">
                        </div>
                        <div>
                            <label class="form-label">Tam alis/birakis adresi</label>
                            <textarea name="exact_pickup_address" class="form-control" rows="2" placeholder="Orn: Le Meridien Etiler Hotel, Nispetiye Cd. No:34, Besiktas/Istanbul">{{ old('exact_pickup_address') }}</textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Valiz adedi</label>
                                <input type="number" min="0" max="50" name="luggage_count" class="form-control" value="{{ old('luggage_count', 0) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Cocuk koltugu adedi</label>
                                <input type="number" min="0" max="10" name="child_seat_count" class="form-control" value="{{ old('child_seat_count', 0) }}">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Not</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Opsiyonel not">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Odemeye gec
                        </button>
                        <a href="{{ $searchRoute }}" class="btn btn-outline-secondary w-100">Aramaya geri don</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if(in_array($roleContext, ['admin', 'superadmin'], true))
    @include('admin.partials.theme-script')
@else
    @include('acente.partials.theme-script')
@endif
<script>
(() => {
    const badge = document.getElementById('ttlBadge');
    if (!badge) return;

    let remaining = Number(@json($ttlSeconds));
    const tick = () => {
        remaining = Math.max(0, remaining - 1);
        badge.textContent = `${remaining} sn`;
        if (remaining <= 0) {
            badge.classList.remove('text-bg-warning');
            badge.classList.add('text-bg-danger');
        }
    };

    setInterval(tick, 1000);
})();
</script>
</body>
</html>
