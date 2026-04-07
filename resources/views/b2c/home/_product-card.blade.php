@php
$typeIcons = [
    'transfer'  => 'bi-car-front-fill',
    'charter'   => 'bi-airplane-fill',
    'leisure'   => 'bi-water',
    'tour'      => 'bi-map-fill',
    'hotel'     => 'bi-building',
    'visa'      => 'bi-passport',
    'other'     => 'bi-grid',
];
$typeLabels = [
    'transfer'  => 'Transfer',
    'charter'   => 'Charter & Uçuş',
    'leisure'   => 'Deniz & Eğlence',
    'tour'      => 'Tur',
    'hotel'     => 'Otel',
    'visa'      => 'Vize',
    'other'     => 'Diğer',
];
$typeColors = [
    'transfer'  => 'linear-gradient(135deg,#1a3c6b,#2a5298)',
    'charter'   => 'linear-gradient(135deg,#0c3547,#1a6b8a)',
    'leisure'   => 'linear-gradient(135deg,#0e4d6b,#1a7a8a)',
    'tour'      => 'linear-gradient(135deg,#1e4d1e,#2d7a2d)',
    'hotel'     => 'linear-gradient(135deg,#4d1e1e,#8a2d2d)',
    'visa'      => 'linear-gradient(135deg,#3d1a6b,#6b2a8a)',
    'other'     => 'linear-gradient(135deg,#1a3c6b,#2a5298)',
];
$icon  = $typeIcons[$item->product_type]  ?? 'bi-grid';
$label = $typeLabels[$item->product_type] ?? 'Hizmet';
$bg    = $typeColors[$item->product_type] ?? 'linear-gradient(135deg,#1a3c6b,#2a5298)';
@endphp

<a href="{{ route('b2c.product.show', $item->slug) }}" class="grt-product-card">
    <div class="position-relative">
        @if($item->cover_image)
            <img src="{{ asset('storage/' . $item->cover_image) }}"
                 alt="{{ $item->title }}" class="card-img">
        @else
            <div class="card-img-placeholder" style="background: {{ $bg }};">
                <i class="bi {{ $icon }}"></i>
            </div>
        @endif

        <div class="img-overlay">
            @if($item->destination_city)
            <span style="color:rgba(255,255,255,.85);font-size:.78rem;">
                <i class="bi bi-geo-alt-fill me-1"></i>{{ $item->destination_city }}
            </span>
            @else
            <span></span>
            @endif
            @if($item->is_featured)
            <span class="badge" style="background:var(--gr-accent);font-size:.72rem;">Öne Çıkan</span>
            @endif
        </div>
    </div>

    <div class="card-body-grt">
        <div class="card-cat-badge">
            <i class="bi {{ $icon }}"></i>{{ $label }}
            @if($item->duration_hours || $item->duration_days)
            &nbsp;·&nbsp;
            @if($item->duration_days) {{ $item->duration_days }} gün
            @elseif($item->duration_hours) {{ $item->duration_hours }} saat
            @endif
            @endif
        </div>

        <div class="card-title-grt">{{ $item->title }}</div>

        <div class="d-flex justify-content-between align-items-end mt-2">
            @if($item->pricing_type === 'fixed' && $item->base_price)
                <div>
                    <div class="card-price-label">kişi başı itibaren</div>
                    <div class="card-price">
                        {{ number_format($item->base_price, 0, ',', '.') }}
                        <span style="font-size:.8rem;font-weight:500;">{{ $item->currency ?? 'TL' }}</span>
                    </div>
                </div>
            @elseif($item->pricing_type === 'quote')
                <div>
                    <div class="card-price-label">Fiyat için</div>
                    <div class="card-cta">Teklif Alın <i class="bi bi-arrow-right"></i></div>
                </div>
            @else
                <div>
                    <div class="card-price-label">Talep üzerine</div>
                    <div class="card-cta">Bilgi Alın <i class="bi bi-arrow-right"></i></div>
                </div>
            @endif

            <i class="bi bi-heart" style="color:var(--gr-muted);font-size:1.1rem;"></i>
        </div>
    </div>
</a>
