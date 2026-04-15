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

        /* ── Voucher (print) ── */
        .gt-voucher {
            border: 2px solid #0f1f48;
            border-radius: 12px;
            padding: 1.5rem;
            background: #fff;
        }
        .gt-voucher-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f1f48;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .gt-voucher-brand { font-size: 1.2rem; font-weight: 800; color: #0f1f48; }
        .gt-voucher-ref   { font-size: 1.4rem; font-weight: 800; font-family: monospace; color: #0f1f48; }
        .gt-voucher-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem 1.5rem; }
        .gt-voucher-item  { font-size: .88rem; padding: .25rem 0; border-bottom: 1px solid #e2e8f0; }
        .gt-voucher-item .label { color: #64748b; font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .gt-voucher-item .value { font-weight: 600; color: #0f172a; }
        .gt-voucher-footer { margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #e2e8f0; font-size: .78rem; color: #64748b; text-align: center; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .gt-voucher { box-shadow: none; border-color: #000; }
            .container { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="theme-scope">
<x-dynamic-component :component="$navbarComponent" active="transfer" />

<div class="container py-4 no-print">
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

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <div>
            <div class="small text-muted">Transfer rezervasyon</div>
            <h1 class="h3 fw-bold mb-0">{{ $booking->booking_ref }}</h1>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge text-bg-primary">{{ strtoupper($booking->status) }}</span>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print me-1"></i>Yazdır / PDF
            </button>
            @if(isset($rezervasyonlarimRoute))
                <a href="{{ $rezervasyonlarimRoute }}" class="btn btn-outline-secondary btn-sm">Rezervasyonlarım</a>
            @endif
            <a href="{{ $searchRoute }}" class="btn btn-outline-secondary btn-sm">Yeni arama</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card gt-transfer-booking-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Rezervasyon Detayları</h2>
                    @php($contact = data_get($booking->price_snapshot_json, 'contact', []))
                    @php($operation = data_get($booking->price_snapshot_json, 'operation_details', []))
                    <div class="gt-transfer-kv"><span>Supplier</span><strong>{{ $booking->supplier?->company_name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Araç Tipi</span><strong>{{ $booking->vehicleType?->name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Rota</span><strong>{{ $booking->airport?->code }} → {{ $booking->zone?->name }}</strong></div>
                    <div class="gt-transfer-kv"><span>Yön</span><strong>{{ $booking->direction }}</strong></div>
                    <div class="gt-transfer-kv"><span>Alış Zamanı</span><strong>{{ optional($booking->pickup_at)->format('d.m.Y H:i') }}</strong></div>
                    @if($booking->return_at)
                        <div class="gt-transfer-kv"><span>Dönüş Zamanı</span><strong>{{ optional($booking->return_at)->format('d.m.Y H:i') }}</strong></div>
                    @endif
                    <div class="gt-transfer-kv"><span>PAX</span><strong>{{ $booking->pax }}</strong></div>
                    <div class="gt-transfer-kv"><span>Tutar</span><strong>{{ number_format((float) $booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</strong></div>
                    @if($booking->refundable_amount !== null)
                        <div class="gt-transfer-kv"><span>İade Tutarı</span><strong>{{ number_format((float) $booking->refundable_amount, 2, ',', '.') }} {{ $booking->currency }}</strong></div>
                    @endif
                    <hr class="my-3">
                    <h3 class="h6 fw-bold mb-2">Yolcu / Operasyon Bilgileri</h3>
                    <div class="gt-transfer-kv"><span>İletişim Adı</span><strong>{{ data_get($contact, 'name') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>İletişim Telefonu</span><strong>{{ data_get($contact, 'phone') ?: '-' }}</strong></div>
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
                    <div class="gt-transfer-kv"><span>Uçuş Numarası</span><strong>{{ data_get($operation, 'flight_number') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Terminal</span><strong>{{ data_get($operation, 'terminal') ?: '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Tabela Adı</span><strong>{{ data_get($operation, 'pickup_sign_name') ?: '-' }}</strong></div>
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
                    <div class="gt-transfer-kv"><span>Valiz Adedi</span><strong>{{ data_get($operation, 'luggage_count') ?? '-' }}</strong></div>
                    <div class="gt-transfer-kv"><span>Çocuk Koltuğu</span><strong>{{ data_get($operation, 'child_seat_count') ?? '-' }}</strong></div>
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
                    <h2 class="h5 fw-bold mb-3">Ödeme Hareketleri</h2>
                    @forelse($booking->paymentTransactions as $transaction)
                        <div class="border rounded p-2 mb-2">
                            <div class="small text-muted">{{ $transaction->reference }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ number_format((float) $transaction->amount, 2, ',', '.') }} {{ $transaction->currency }}</strong>
                                <span class="badge text-bg-secondary">{{ strtoupper($transaction->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-light border">Ödeme kaydı bulunamadı.</div>
                    @endforelse

                    @if($canCancel)
                        <form method="POST" action="{{ $cancelEndpoint }}" class="mt-3">
                            @csrf
                            <label class="form-label">İptal Nedeni (opsiyonel)</label>
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="İptal nedeni"></textarea>
                            <button type="submit" class="btn btn-outline-danger w-100">Rezervasyonu İptal Et</button>
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
    <div class="no-print">
        @include('acente.partials.leisure-footer')
    </div>
@endif

{{-- ── VOUCHER (sadece print görünümünde / yazdır basıldığında) ── --}}
<div class="container mt-4" id="gtVoucher" style="display:none;">
    @php($contact   = data_get($booking->price_snapshot_json, 'contact', []))
    @php($operation = data_get($booking->price_snapshot_json, 'operation_details', []))
    <div class="gt-voucher">
        <div class="gt-voucher-header">
            <div>
                <div class="gt-voucher-brand">GrupTalepleri.com</div>
                <div style="font-size:.78rem;color:#64748b;">Transfer Yolcu Belgesi</div>
            </div>
            <div class="text-end">
                <div class="gt-voucher-ref">{{ $booking->booking_ref }}</div>
                @php
                    $statusLabel = match($booking->status) {
                        'confirmed'       => 'ONAYLANDI',
                        'payment_pending' => 'ÖDEME BEKLİYOR',
                        'cancelled'       => 'İPTAL',
                        'refunded'        => 'İADE',
                        default           => strtoupper($booking->status),
                    };
                @endphp
                <div style="font-size:.78rem;font-weight:700;color:#1a3b7a;">{{ $statusLabel }}</div>
            </div>
        </div>

        <div class="gt-voucher-grid">
            <div class="gt-voucher-item">
                <div class="label">Araç Tipi</div>
                <div class="value">{{ $booking->vehicleType?->name ?? '-' }}</div>
            </div>
            <div class="gt-voucher-item">
                <div class="label">Rota</div>
                <div class="value">{{ $booking->airport?->code }} → {{ $booking->zone?->name }}</div>
            </div>
            <div class="gt-voucher-item">
                <div class="label">Yön</div>
                <div class="value">
                    @php
                        $dirLabel = match($booking->direction) {
                            'FROM_AIRPORT' => 'Havalimanından Şehre',
                            'TO_AIRPORT'   => 'Şehirden Havalimanına',
                            'BOTH'         => 'Gidiş Dönüş',
                            default        => $booking->direction,
                        };
                    @endphp
                    {{ $dirLabel }}
                </div>
            </div>
            <div class="gt-voucher-item">
                <div class="label">Alış Zamanı</div>
                <div class="value">{{ optional($booking->pickup_at)->format('d.m.Y H:i') }}</div>
            </div>
            @if($booking->return_at)
            <div class="gt-voucher-item">
                <div class="label">Dönüş Zamanı</div>
                <div class="value">{{ optional($booking->return_at)->format('d.m.Y H:i') }}</div>
            </div>
            @endif
            <div class="gt-voucher-item">
                <div class="label">PAX</div>
                <div class="value">{{ $booking->pax }} kişi</div>
            </div>
            <div class="gt-voucher-item">
                <div class="label">İletişim Adı</div>
                <div class="value">{{ data_get($contact, 'name') ?: '-' }}</div>
            </div>
            <div class="gt-voucher-item">
                <div class="label">Telefon</div>
                <div class="value">{{ data_get($contact, 'phone') ?: '-' }}</div>
            </div>
            @if(data_get($operation, 'flight_number'))
            <div class="gt-voucher-item">
                <div class="label">Uçuş No</div>
                <div class="value">{{ data_get($operation, 'flight_number') }}</div>
            </div>
            @endif
            @if(data_get($operation, 'terminal'))
            <div class="gt-voucher-item">
                <div class="label">Terminal</div>
                <div class="value">{{ data_get($operation, 'terminal') }}</div>
            </div>
            @endif
            @if(data_get($operation, 'pickup_sign_name'))
            <div class="gt-voucher-item">
                <div class="label">Tabela Adı</div>
                <div class="value">{{ data_get($operation, 'pickup_sign_name') }}</div>
            </div>
            @endif
            @if(data_get($operation, 'exact_pickup_address'))
            <div class="gt-voucher-item" style="grid-column: 1 / -1;">
                <div class="label">Tam Adres</div>
                <div class="value">{{ data_get($operation, 'exact_pickup_address') }}</div>
            </div>
            @endif
            @if(data_get($operation, 'passenger_names'))
            <div class="gt-voucher-item" style="grid-column: 1 / -1;">
                <div class="label">Yolcu İsimleri</div>
                <div class="value" style="white-space:pre-line;">{{ data_get($operation, 'passenger_names') }}</div>
            </div>
            @endif
            <div class="gt-voucher-item">
                <div class="label">Supplier</div>
                <div class="value">{{ $booking->supplier?->company_name ?? '-' }}</div>
            </div>
            <div class="gt-voucher-item">
                <div class="label">Ödenen Tutar</div>
                <div class="value">{{ number_format((float)$booking->total_amount, 2, ',', '.') }} {{ $booking->currency }}</div>
            </div>
        </div>

        @if($booking->notes)
        <div class="mt-3 p-2" style="background:#f8fafc;border-radius:6px;font-size:.82rem;">
            <strong>Not:</strong> {{ $booking->notes }}
        </div>
        @endif

        <div class="gt-voucher-footer">
            Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur.
            Lütfen sürücüye gösteriniz. &bull; gruptalepleri.com
        </div>
    </div>
</div>

<script>
// Print tuşuna basıldığında voucher göster, gerisi gizle
window.addEventListener('beforeprint', function() {
    document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');
    const voucher = document.getElementById('gtVoucher');
    if (voucher) voucher.style.display = 'block';
});
window.addEventListener('afterprint', function() {
    document.querySelectorAll('.no-print').forEach(el => el.style.display = '');
    const voucher = document.getElementById('gtVoucher');
    if (voucher) voucher.style.display = 'none';
});
</script>
</body>
</html>
