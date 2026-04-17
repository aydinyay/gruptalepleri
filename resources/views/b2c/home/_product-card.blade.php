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
$icon     = $typeIcons[$item->product_type] ?? 'bi-grid';
$bg       = $typeColors[$item->product_type] ?? 'linear-gradient(135deg,#1a3c6b,#2a5298)';
$catLabel = optional($item->category)->name ?? ucfirst($item->product_type);
@endphp

<a href="{{ route('b2c.product.show', $item->slug) }}" class="gyg-pcard">
    <div class="gyg-pcard-img">
        @if($item->cover_image)
            @php $imgSrc = str_starts_with($item->cover_image, 'http') ? $item->cover_image : rtrim(config('app.url'), '/') . '/uploads/' . $item->cover_image; @endphp
            <img src="{{ $imgSrc }}" alt="{{ $item->title }}" loading="lazy">
        @else
            <div class="img-placeholder" style="background:{{ $bg }};">
                <i class="bi {{ $icon }}"></i>
            </div>
        @endif

        {{-- FOMO / rozet --}}
        @if($item->badge_label)
            @php
            $badgeStyles = [
                'Vizyon'     => 'background:#b7791f;color:#fff;',
                'Popüler'    => 'background:#3182ce;color:#fff;',
                'Yeni'       => 'background:#38a169;color:#fff;',
                'Son Fırsat' => 'background:#e53e3e;color:#fff;',
                'İndirim'    => 'background:#dd6b20;color:#fff;',
                'Sınırlı'   => 'background:#805ad5;color:#fff;',
            ];
            $badgeStyle = $badgeStyles[$item->badge_label] ?? 'background:#718096;color:#fff;';
            @endphp
            <div class="gyg-pcard-tag" style="{{ $badgeStyle }}">{{ $item->badge_label }}</div>
        @elseif($item->is_featured)
            <div class="gyg-pcard-tag featured">Öne Çıkan</div>
        @endif

        <div class="gyg-pcard-heart" onclick="event.preventDefault();this.innerHTML=this.innerHTML.includes('fill')?'<i class=\'bi bi-heart\'></i>':'<i class=\'bi bi-heart-fill\' style=\'color:#e53e3e\'></i>'">
            <i class="bi bi-heart"></i>
        </div>

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

        <div class="d-flex align-items-center gap-1" style="margin-bottom:4px;">
            @if(($item->rating_avg ?? 0) > 0)
            <span class="gyg-pcard-stars">{!! str_repeat('★', (int)floor($item->rating_avg)) . ($item->rating_avg - floor($item->rating_avg) >= 0.5 ? '★' : '') . str_repeat('☆', 5 - (int)ceil($item->rating_avg)) !!}</span>
            <span class="gyg-pcard-rating">{{ number_format($item->rating_avg, 1) }}</span>
            @if(($item->review_count ?? 0) > 0)
            <span class="gyg-pcard-reviews">({{ number_format($item->review_count, 0, ',', '.') }})</span>
            @endif
            @else
            <span class="gyg-pcard-stars" style="color:#d1d5db;">☆☆☆☆☆</span>
            <span style="font-size:.72rem;color:#a0aec0;font-weight:600;">Yeni</span>
            @endif
        </div>

        @if($item->pricing_type === 'fixed' && $item->base_price)
            <div class="gyg-pcard-price-label">kişi başı itibaren</div>
            <div class="gyg-pcard-price">{{ number_format($item->base_price, 0, ',', '.') }} {{ $item->currency ?? 'TRY' }}</div>
            <span class="gyg-pcard-cta">Rezervasyon Yap</span>
        @elseif($item->pricing_type === 'quote')
            <div class="gyg-pcard-price-label">Kişiye özel fiyat</div>
            <div class="gyg-pcard-price" style="font-size:.95rem;color:#718096;font-weight:600;">Fiyat Al</div>
            <span class="gyg-pcard-cta outline">Ücretsiz Teklif Al</span>
        @else
            <div class="gyg-pcard-price-label">Talep üzerine</div>
            <span class="gyg-pcard-cta outline">Bilgi Al</span>
        @endif
    </div>
</a>
