@extends('b2c.layouts.app')
@section('title', 'Sipariş #' . $order->order_ref . ' — Grup Rezervasyonları')

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
.detail-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 22px 24px; margin-bottom: 16px;
}
.detail-card-title {
    font-size: 1rem; font-weight: 700; color: #1a202c;
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    padding-bottom: 12px; border-bottom: 1px solid #f0f4f8;
}
.detail-card-title i { color: #1a3c6b; }
.dl-row { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f7f9fc; font-size: .9rem; }
.dl-row:last-child { border-bottom: none; }
.dl-label { color: #718096; min-width: 160px; flex-shrink: 0; }
.dl-value { color: #1a202c; font-weight: 500; }
.badge-warning  { background: #fef3c7; color: #92400e; padding: 3px 12px; border-radius: 50px; font-size: .8rem; font-weight: 700; }
.badge-primary  { background: #dbeafe; color: #1e40af; padding: 3px 12px; border-radius: 50px; font-size: .8rem; font-weight: 700; }
.badge-success  { background: #dcfce7; color: #166534; padding: 3px 12px; border-radius: 50px; font-size: .8rem; font-weight: 700; }
.badge-danger   { background: #fee2e2; color: #991b1b; padding: 3px 12px; border-radius: 50px; font-size: .8rem; font-weight: 700; }
.badge-secondary{ background: #f1f5f9; color: #475569; padding: 3px 12px; border-radius: 50px; font-size: .8rem; font-weight: 700; }
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
        {{-- Geri butonu --}}
        <a href="{{ route('b2c.account.orders.index') }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:.88rem;color:#718096;text-decoration:none;margin-bottom:16px;">
            <i class="bi bi-arrow-left"></i> Siparişlerime Dön
        </a>

        {{-- Sipariş özeti --}}
        <div class="detail-card">
            <div class="detail-card-title">
                <i class="bi bi-bag-check-fill"></i>
                Sipariş Detayı
                <span class="badge-{{ $order->status_color }}" style="margin-left:auto;">{{ $order->status_label }}</span>
            </div>
            <div class="dl-row">
                <span class="dl-label">Sipariş No</span>
                <span class="dl-value"><code style="background:#f0f4f8;padding:2px 8px;border-radius:4px;">{{ $order->order_ref }}</code></span>
            </div>
            <div class="dl-row">
                <span class="dl-label">Hizmet</span>
                <span class="dl-value">{{ $order->item->title ?? '—' }}</span>
            </div>
            @if($order->item && $order->item->category)
            <div class="dl-row">
                <span class="dl-label">Kategori</span>
                <span class="dl-value">{{ $order->item->category->name }}</span>
            </div>
            @endif
            <div class="dl-row">
                <span class="dl-label">Sipariş Tarihi</span>
                <span class="dl-value">{{ $order->created_at->format('d.m.Y H:i') }}</span>
            </div>
            @if($order->service_date)
            <div class="dl-row">
                <span class="dl-label">Hizmet Tarihi</span>
                <span class="dl-value">{{ $order->service_date->format('d.m.Y') }}</span>
            </div>
            @endif
            <div class="dl-row">
                <span class="dl-label">Kişi Sayısı</span>
                <span class="dl-value">{{ $order->pax_count }}</span>
            </div>
            @if($order->notes)
            <div class="dl-row">
                <span class="dl-label">Notunuz</span>
                <span class="dl-value">{{ $order->notes }}</span>
            </div>
            @endif
            @if($order->admin_note)
            <div class="dl-row">
                <span class="dl-label">Operasyon Notu</span>
                <span class="dl-value">{{ $order->admin_note }}</span>
            </div>
            @endif
        </div>

        {{-- Ödeme bilgisi --}}
        <div class="detail-card">
            <div class="detail-card-title">
                <i class="bi bi-credit-card-fill"></i> Ödeme
            </div>
            <div class="dl-row">
                <span class="dl-label">Birim Fiyat</span>
                <span class="dl-value">{{ number_format($order->unit_price, 2, ',', '.') }} {{ $order->currency }}</span>
            </div>
            <div class="dl-row">
                <span class="dl-label">Toplam Tutar</span>
                <span class="dl-value" style="font-weight:800;color:#1a3c6b;font-size:1.05rem;">{{ number_format($order->total_price, 2, ',', '.') }} {{ $order->currency }}</span>
            </div>
            <div class="dl-row">
                <span class="dl-label">Ödeme Durumu</span>
                <span class="dl-value">
                    @if($order->payment_status === 'paid')
                        <span style="color:#166534;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i>Ödendi</span>
                    @elseif($order->payment_status === 'refunded')
                        <span style="color:#991b1b;font-weight:600;"><i class="bi bi-arrow-counterclockwise me-1"></i>İade Edildi</span>
                    @else
                        <span style="color:#92400e;font-weight:600;"><i class="bi bi-clock me-1"></i>Bekleniyor</span>
                    @endif
                </span>
            </div>
            @if($order->paid_at)
            <div class="dl-row">
                <span class="dl-label">Ödeme Tarihi</span>
                <span class="dl-value">{{ $order->paid_at->format('d.m.Y H:i') }}</span>
            </div>
            @endif
        </div>

        {{-- Yolcular --}}
        @if($order->passengers->isNotEmpty())
        <div class="detail-card">
            <div class="detail-card-title">
                <i class="bi bi-people-fill"></i> Yolcular
            </div>
            @foreach($order->passengers as $pax)
            <div class="dl-row">
                <span class="dl-label">{{ $loop->iteration }}. Yolcu</span>
                <span class="dl-value">
                    {{ $pax->ad }} {{ $pax->soyad }}
                    @if($pax->dogum_tarihi)
                    <span style="color:#718096;font-size:.82rem;margin-left:8px;">{{ $pax->dogum_tarihi }}</span>
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Yardım --}}
        <div style="background:#f7faff;border:1px solid #e8eef5;border-radius:12px;padding:16px 20px;font-size:.88rem;color:#4a5568;">
            <i class="bi bi-headset" style="color:#1a3c6b;margin-right:6px;"></i>
            Siparişinizle ilgili sorularınız için
            <a href="{{ route('b2c.iletisim') }}" style="color:#1a3c6b;font-weight:600;">iletişime geçin</a>.
        </div>
    </main>
</div>
@endsection
