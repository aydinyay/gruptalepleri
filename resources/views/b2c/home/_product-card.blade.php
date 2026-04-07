@php
$typeIcons = [
    'transfer' => 'bi-car-front-fill',
    'charter'  => 'bi-airplane-fill',
    'leisure'  => 'bi-water',
    'tour'     => 'bi-map-fill',
    'hotel'    => 'bi-building',
    'visa'     => 'bi-passport',
    'other'    => 'bi-grid',
];
$typeColors = [
    'transfer' => 'linear-gradient(135deg,#1a3c6b,#2a5298)',
    'charter'  => 'linear-gradient(135deg,#0c3547,#1a6b8a)',
    'leisure'  => 'linear-gradient(135deg,#0e4d6b,#1a7a8a)',
    'tour'     => 'linear-gradient(135deg,#1e4d1e,#2d7a2d)',
    'hotel'    => 'linear-gradient(135deg,#4d1e1e,#8a2d2d)',
    'visa'     => 'linear-gradient(135deg,#3d1a6b,#6b2a8a)',
    'other'    => 'linear-gradient(135deg,#1a3c6b,#2a5298)',
];
$icon  = $typeIcons[$item->product_type] ?? 'bi-grid';
$bg    = $typeColors[$item->product_type] ?? 'linear-gradient(135deg,#1a3c6b,#2a5298)';
$catLabel = optional($item->category)->name ?? ucfirst($item->product_type);
@endphp

<a href="{{ route('b2c.product.show', $item->slug) }}" class="gyg-pcard">
    <div class="gyg-pcard-img">
        @if($item->cover_image)
            <img src="{{ asset('storage/' . $item->cover_image) }}" alt="{{ $item->title }}">
        @else
            <div class="img-placeholder" style="background:{{ $bg }};">
                <i class="bi {{ $icon }}"></i>
            </div>
        @endif

        <div class="gyg-pcard-heart"><i class="bi bi-heart"></i></div>

        <div class="gyg-pcard-badge">
            @if($item->duration_days)
                {{ $item->duration_days }} gün
            @elseif($item->duration_hours)
                {{ $item->duration_hours }} saat
            @else
                Esnek
            @endif
        </div>
    </div>

    <div class="gyg-pcard-body">
        <div class="gyg-pcard-cat">
            {{ $catLabel }}
            @if($item->destination_city) · {{ $item->destination_city }} @endif
        </div>

        <div class="gyg-pcard-title">{{ $item->title }}</div>

        @if(($item->rating_avg ?? 0) > 0)
        <div class="d-flex align-items-center gap-1">
            <span class="gyg-pcard-stars">{!! str_repeat('★', (int)floor($item->rating_avg)) . ($item->rating_avg - floor($item->rating_avg) >= 0.5 ? '★' : '') . str_repeat('☆', 5 - (int)ceil($item->rating_avg)) !!}</span>
            <span class="gyg-pcard-rating">{{ number_format($item->rating_avg, 1) }}</span>
            @if(($item->review_count ?? 0) > 0)
            <span class="gyg-pcard-reviews">({{ number_format($item->review_count, 0, ',', '.') }})</span>
            @endif
        </div>
        @endif

        @if($item->pricing_type === 'fixed' && $item->base_price)
            <div class="gyg-pcard-price-label">kişi başı itibaren</div>
            <div class="gyg-pcard-price">{{ number_format($item->base_price, 0, ',', '.') }} {{ $item->currency ?? 'TRY' }}</div>
        @elseif($item->pricing_type === 'quote')
            <div class="gyg-pcard-price-label">Teklif alın</div>
            <div class="gyg-pcard-price" style="color:#718096;font-weight:500;font-size:.9rem;">Fiyat Al →</div>
        @else
            <div class="gyg-pcard-price-label">Talep üzerine</div>
            <div class="gyg-pcard-price" style="color:#718096;font-weight:500;font-size:.9rem;">Bilgi Al →</div>
        @endif
    </div>
</a>
