@extends('b2c.layouts.app')
@section('title', 'Siparişlerim — Grup Rezervasyonları')

@section('content')
<style>
.account-wrap {
    max-width: 1100px; margin: 0 auto; padding: 32px 24px 60px;
    display: grid; grid-template-columns: 240px 1fr; gap: 28px;
}
@@media (max-width: 768px) {
    .account-wrap { grid-template-columns: 1fr; padding: 20px 16px 48px; }
}
.account-sidebar {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 0; overflow: hidden; align-self: start;
}
.account-user-header {
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
    padding: 20px; text-align: center;
}
.account-user-avatar {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.3);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 8px;
}
.account-user-name { font-size: .95rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
.account-user-email { font-size: .78rem; color: rgba(255,255,255,.6); }
.account-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 18px; font-size: .9rem; font-weight: 500;
    color: #4a5568; text-decoration: none;
    border-bottom: 1px solid #f0f4f8;
    transition: background .12s, color .12s;
}
.account-nav-item:last-child { border-bottom: none; }
.account-nav-item:hover { background: #f7faff; color: #1a3c6b; }
.account-nav-item.active { background: #ebf0fb; color: #1a3c6b; font-weight: 700; }
.account-nav-item i { font-size: 1rem; width: 18px; text-align: center; }
.account-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 20px 24px;
}
.account-card-title {
    font-size: 1rem; font-weight: 700; color: #1a202c;
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}
.account-card-title i { color: #1a3c6b; }
.order-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 0; border-bottom: 1px solid #f0f4f8;
}
.order-row:last-child { border-bottom: none; }
.order-icon {
    width: 44px; height: 44px; border-radius: 10px;
    background: #ebf0fb; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.order-icon i { font-size: 1.2rem; color: #1a3c6b; }
.order-info { flex: 1; min-width: 0; }
.order-title { font-size: .92rem; font-weight: 600; color: #1a202c; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.order-meta { font-size: .78rem; color: #718096; margin-top: 3px; }
.order-badge {
    font-size: .75rem; font-weight: 700; padding: 3px 10px;
    border-radius: 50px; flex-shrink: 0; white-space: nowrap;
}
.badge-warning  { background: #fef3c7; color: #92400e; }
.badge-primary  { background: #dbeafe; color: #1e40af; }
.badge-success  { background: #dcfce7; color: #166534; }
.badge-danger   { background: #fee2e2; color: #991b1b; }
.badge-secondary{ background: #f1f5f9; color: #475569; }
.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-state i { font-size: 3rem; color: #cbd5e0; display: block; margin-bottom: 12px; }
.empty-state p { color: #718096; font-size: .93rem; margin-bottom: 14px; }
</style>

<div class="account-wrap">
    {{-- Sidebar --}}
    <aside class="account-sidebar">
        <div class="account-user-header">
            <div class="account-user-avatar">
                {{ strtoupper(substr(Auth::guard('b2c')->user()->name, 0, 1)) }}
            </div>
            <div class="account-user-name">{{ Auth::guard('b2c')->user()->name }}</div>
            <div class="account-user-email">{{ Auth::guard('b2c')->user()->email }}</div>
        </div>
        <nav>
            <a href="{{ route('b2c.account.index') }}" class="account-nav-item">
                <i class="bi bi-house-fill"></i> Genel Bakış
            </a>
            <a href="{{ route('b2c.account.orders.index') }}" class="account-nav-item active">
                <i class="bi bi-bag-fill"></i> Siparişlerim
            </a>
            <a href="{{ route('b2c.account.profile.edit') }}" class="account-nav-item">
                <i class="bi bi-person-fill"></i> Profilim
            </a>
            <a href="{{ route('b2c.sigorta.policelerim') }}" class="account-nav-item">
                <i class="bi bi-shield-fill-check"></i> Poliçelerim
            </a>
            <a href="{{ route('b2c.catalog.index') }}" class="account-nav-item">
                <i class="bi bi-grid-fill"></i> Hizmetleri Keşfet
            </a>
            <form method="POST" action="{{ route('b2c.auth.logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="account-nav-item" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;border-radius:0;">
                    <i class="bi bi-box-arrow-right" style="color:#e53e3e;"></i>
                    <span style="color:#e53e3e;">Çıkış Yap</span>
                </button>
            </form>
        </nav>
    </aside>

    {{-- Main --}}
    <main>
        <div class="account-card">
            <div class="account-card-title">
                <i class="bi bi-bag-fill"></i> Siparişlerim
                <span style="font-size:.82rem;font-weight:500;color:#718096;margin-left:auto;">
                    {{ $orders->total() }} sipariş
                </span>
            </div>

            @if($orders->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bag"></i>
                <p>Henüz siparişiniz yok.</p>
                <a href="{{ route('b2c.catalog.index') }}" style="display:inline-block;padding:10px 24px;background:#1a3c6b;color:#fff;border-radius:8px;text-decoration:none;font-size:.9rem;font-weight:600;">
                    Hizmetleri Keşfedin
                </a>
            </div>
            @else
            @foreach($orders as $order)
            <div class="order-row">
                <div class="order-icon">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="order-info">
                    <div class="order-title">{{ Str::limit($order->item->title ?? 'Hizmet', 45) }}</div>
                    <div class="order-meta">
                        <code style="font-size:.75rem;background:#f0f4f8;padding:1px 6px;border-radius:4px;">{{ $order->order_ref }}</code>
                        &nbsp;·&nbsp; {{ $order->created_at->format('d.m.Y') }}
                        @if($order->service_date)
                        &nbsp;·&nbsp; Hizmet: {{ $order->service_date->format('d.m.Y') }}
                        @endif
                    </div>
                </div>
                <span class="order-badge badge-{{ $order->status_color }}">
                    {{ $order->status_label }}
                </span>
                <a href="{{ route('b2c.account.orders.show', $order->order_ref) }}"
                   style="font-size:.82rem;color:#1a3c6b;text-decoration:none;flex-shrink:0;font-weight:600;white-space:nowrap;">
                    Detay →
                </a>
            </div>
            @endforeach

            <div style="margin-top:20px;">
                {{ $orders->links() }}
            </div>
            @endif
        </div>
    </main>
</div>
@endsection
