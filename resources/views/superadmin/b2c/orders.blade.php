<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2C Rezervasyonlar — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-clipboard-list me-2" style="color:#a78bfa;"></i>B2C Rezervasyonlar</h5>
        <p>gruprezervasyonlari.com'dan gelen tüm rezervasyon ve talepler</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filtreler --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending" @selected(request('status') === 'pending')>Ödeme Bekliyor</option>
                        <option value="confirmed" @selected(request('status') === 'confirmed')>Onaylandı</option>
                        <option value="pending_quote" @selected(request('status') === 'pending_quote')>Teklif Bekleniyor</option>
                        <option value="quote_sent" @selected(request('status') === 'quote_sent')>Teklif Gönderildi</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>İptal</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="payment" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tüm Ödemeler</option>
                        <option value="paid" @selected(request('payment') === 'paid')>Ödendi</option>
                        <option value="unpaid" @selected(request('payment') === 'unpaid')>Ödenmedi</option>
                    </select>
                </div>
                <div class="col-auto">
                    <a href="{{ route('superadmin.b2c.orders') }}" class="btn btn-sm btn-outline-secondary">Sıfırla</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-dark">
                    <tr>
                        <th>Ref No</th>
                        <th>Misafir</th>
                        <th>Hizmet</th>
                        <th>Kişi</th>
                        <th>Tarih</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Ödeme</th>
                        <th>Kayıt</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><code style="font-size:.78rem;">{{ $order->order_ref }}</code></td>
                    <td>
                        <div class="fw-semibold">{{ $order->guest_name }}</div>
                        <small class="text-muted">{{ $order->guest_phone }}</small>
                        @if($order->guest_email)
                            <div><small class="text-muted">{{ $order->guest_email }}</small></div>
                        @endif
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width:200px;">{{ $order->item_title ?? $order->item?->title ?? '—' }}</div>
                        @if($order->event_type)
                            <small class="text-muted">{{ $order->event_type }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $order->pax_count }}</td>
                    <td>
                        @if($order->service_date)
                            {{ $order->service_date->format('d.m.Y') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($order->total_price)
                            <div class="fw-semibold">{{ number_format($order->total_price, 0, ',', '.') }}</div>
                            <small class="text-muted">{{ $order->currency }}</small>
                        @else
                            <span class="text-muted">Teklif</span>
                        @endif
                    </td>
                    <td>
                        @php $statusMap = ['pending'=>['warning','Bekliyor'],'confirmed'=>['success','Onaylandı'],'pending_quote'=>['info','Teklif Bek.'],'quote_sent'=>['primary','Teklif Gönderildi'],'cancelled'=>['secondary','İptal']]; $s = $statusMap[$order->status] ?? ['secondary',$order->status]; @endphp
                        <span class="badge bg-{{ $s[0] }} {{ $s[0]==='warning'||$s[0]==='info' ? 'text-dark' : '' }}">{{ $s[1] }}</span>
                    </td>
                    <td>
                        @if($order->payment_status === 'paid')
                            <span class="badge bg-success">Ödendi</span>
                        @else
                            <span class="badge bg-warning text-dark">Ödenmedi</span>
                        @endif
                    </td>
                    <td><small class="text-muted">{{ $order->created_at->format('d.m H:i') }}</small></td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">Rezervasyon bulunamadı.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="card-footer bg-white">
            {{ $orders->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
