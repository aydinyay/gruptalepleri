@extends('b2c.layouts.app')
@section('title', 'Hesabım')

@section('content')
<section style="background:var(--gr-light);padding:2rem 0 1rem;">
    <div class="container">
        <h1 class="gr-section-title">Hesabım</h1>
        <p style="color:var(--gr-muted);">Hoş geldiniz, <strong>{{ $user->name }}</strong></p>
    </div>
</section>
<section>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="{{ route('b2c.account.index') }}" class="list-group-item list-group-item-action active">
                        <i class="bi bi-house me-2"></i>Genel Bakış
                    </a>
                    <a href="{{ route('b2c.account.orders.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-bag me-2"></i>Siparişlerim
                    </a>
                    <a href="{{ route('b2c.account.profile.edit') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person me-2"></i>Profil
                    </a>
                </div>
            </div>
            <div class="col-md-9">
                <h5 class="fw-700 mb-3">Son Siparişler</h5>
                @if($orders->isEmpty())
                <p style="color:var(--gr-muted);">Henüz siparişiniz yok.</p>
                <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-primary">Hizmetleri Keşfedin</a>
                @else
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Referans</th><th>Hizmet</th><th>Tarih</th><th>Durum</th><th></th></tr></thead>
                        <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td><code>{{ $order->order_ref }}</code></td>
                            <td>{{ Str::limit($order->item->title ?? '-', 30) }}</td>
                            <td>{{ $order->created_at->format('d.m.Y') }}</td>
                            <td><span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span></td>
                            <td><a href="{{ route('b2c.account.orders.show', $order->order_ref) }}" class="btn btn-sm btn-gr-outline">Detay</a></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
