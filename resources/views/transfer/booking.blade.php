<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Rezervasyon - {{ $booking->booking_ref }}</title>
    @if(in_array($roleContext, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .gt-transfer-booking-card {
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
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="small text-muted">Transfer rezervasyon</div>
            <h1 class="h3 fw-bold mb-0">{{ $booking->booking_ref }}</h1>
        </div>
        <div class="d-flex gap-2">
            <span class="badge text-bg-primary">{{ strtoupper($booking->status) }}</span>
            <a href="{{ $searchRoute }}" class="btn btn-outline-secondary btn-sm">Yeni arama</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card gt-transfer-booking-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Rezervasyon detaylari</h2>
                    @php($contact = data_get($booking->price_snapshot_json, 'contact', []))
                    @php($operation = data_get($booking->price_snapshot_json, 'operation_details', []))
                    <div class="gt-transfer-kv"><span>Supplier</span><strong>{{ $booking->supplier?->company_name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Arac tipi</span><strong>{{ $booking->vehicleType?->name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Rota</span><strong>{{ $booking->airport?->code }} -> {{ $booking->zone?->name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Yon</span><strong>{{ $booking->direction }}</strong></div>
                    <div class="gt-transfer-kv"><span>Alis zamani</span><strong>{{ optional($booking->pickup_at)->format('d.m.Y H:i') }}</strong></div>
                    @if($booking->return_at)
                        <div class="gt-transfer-kv"><span>Donus zamani</span><strong>{{ optional($booking->return_at)->format('d.m.Y H:i') }}</strong></div>
                    @endif
                    <div class="gt-transfer-kv"><span>PAX</span><strong>{{ $booking->pax }}</strong></div>
                    <div class="gt-transfer-kv"><span>Tutar</span><strong>{{ number_format((float) $booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</strong></div>
                    @if($booking->refundable_amount !== null)
                        <div class="gt-transfer-kv"><span>Iade tutari</span><strong>{{ number_format((float) $booking->refundable_amount, 2, ',', '.') }} {{ $booking->currency }}</strong></div>
                    @endif
                    <hr class="my-3">
                    <h3 class="h6 fw-bold mb-2">Yolcu / operasyon bilgileri</h3>
                    <div class="gt-transfer-kv"><span>Iletisim adi</span><strong>{{ data_get($contact, 'name') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Iletisim telefonu</span><strong>{{ data_get($contact, 'phone') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv">
                        <span>Yolcu isimleri</span>
                        <strong class="text-end">
                            @if(data_get($operation, 'passenger_names'))
                                {!! nl2br(e((string) data_get($operation, 'passenger_names'))) !!}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                    <div class="gt-transfer-kv"><span>Ucus numarasi</span><strong>{{ data_get($operation, 'flight_number') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Terminal</span><strong>{{ data_get($operation, 'terminal') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Tabela adi</span><strong>{{ data_get($operation, 'pickup_sign_name') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv">
                        <span>Tam adres</span>
                        <strong class="text-end">
                            @if(data_get($operation, 'exact_pickup_address'))
                                {!! nl2br(e((string) data_get($operation, 'exact_pickup_address'))) !!}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                    <div class="gt-transfer-kv"><span>Valiz adedi</span><strong>{{ data_get($operation, 'luggage_count') ?? '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Cocuk koltugu</span><strong>{{ data_get($operation, 'child_seat_count') ?? '-' }}</strong></div>
                    <div class="gt-transfer-kv">
                        <span>Not</span>
                        <strong class="text-end">
                            @if($booking->notes)
                                {!! nl2br(e((string) $booking->notes)) !!}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card gt-transfer-booking-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Odeme hareketleri</h2>
                    @forelse($booking->paymentTransactions as $transaction)
                        <div class="border rounded p-2 mb-2">
                            <div class="small text-muted">{{ $transaction->reference }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ number_format((float) $transaction->amount, 2, ',', '.') }} {{ $transaction->currency }}</strong>
                                <span class="badge text-bg-secondary">{{ strtoupper($transaction->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-light border">Odeme kaydi bulunamadi.</div>
                    @endforelse

                    @if($canCancel)
                        <form method="POST" action="{{ $cancelEndpoint }}" class="mt-3">
                            @csrf
                            <label class="form-label">Iptal nedeni (opsiyonel)</label>
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="Iptal nedeni"></textarea>
                            <button type="submit" class="btn btn-outline-danger w-100">Rezervasyonu iptal et</button>
                        </form>
                    @endif
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
</body>
</html>
