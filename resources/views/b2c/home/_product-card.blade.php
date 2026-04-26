@php
$typeIcons = [
    'transfer' => 'bi-car-front-fill',
    'charter'  => 'bi-airplane-fill',
    'leisure'  => 'bi-water',
    'tour'     => 'bi-map-fill',
    'hotel'    => 'bi-building',
    'visa'     => 'bi-passport',
    'sigorta'  => 'bi-shield-fill-check',
    'other'    => 'bi-grid',
];
$typeColors = [
    'transfer' => 'linear-gradient(135deg,#1a3c6b,#2a5298)',
    'charter'  => 'linear-gradient(135deg,#0c3547,#1a6b8a)',
    'leisure'  => 'linear-gradient(135deg,#0e4d6b,#1a7a8a)',
    'tour'     => 'linear-gradient(135deg,#1e4d1e,#2d7a2d)',
    'hotel'    => 'linear-gradient(135deg,#4d1e1e,#8a2d2d)',
    'visa'     => 'linear-gradient(135deg,#3d1a6b,#6b2a8a)',
    'sigorta'  => 'linear-gradient(135deg,#065f46,#0d9488)',
    'other'    => 'linear-gradient(135deg,#1a3c6b,#2a5298)',
];
$icon     = $typeIcons[$item->product_type] ?? 'bi-grid';
$bg       = $typeColors[$item->product_type] ?? 'linear-gradient(135deg,#1a3c6b,#2a5298)';
$catLabel = optional($item->category)->name ?? ucfirst($item->product_type);

// Map DB-stored Turkish pricing_unit values to translation keys
$unitKeyMap = [
    'kişi başına'           => 'pricing_unit_per_person',
    'kişi başı'             => 'pricing_unit_per_person',
    'grup başına'           => 'pricing_unit_per_group',
    'saatlik'               => 'pricing_unit_hourly',
    'saatlik · grup başına' => 'pricing_unit_hourly_group',
    'sefer başına'          => 'pricing_unit_per_trip',
    'araç başına'           => 'pricing_unit_per_vehicle',
    'gecelik'               => 'pricing_unit_per_night',
    'başvuru başına'        => 'pricing_unit_per_application',
];
@endphp

<a href="{{ lroute('b2c.product.show', $item->slug) }}" class="gyg-pcard"
   @if(!empty($dataCity)) data-city="{{ $dataCity }}" @endif
   @if(!empty($item->venue_lat)) data-lat="{{ $item->venue_lat }}" data-lng="{{ $item->venue_lng }}" @endif>
    <div class="gyg-pcard-img">
        @if($item->cover_image)
            @php $imgSrc = str_starts_with($item->cover_image, 'http') ? $item->cover_image : rtrim(config('app.url'), '/') . '/uploads/' . $item->cover_image; @endphp
            <img src="{{ $imgSrc }}" alt="{{ $item->translatedTitle() }}" loading="lazy">
        @else
            <div class="img-placeholder" style="background:{{ $bg }};">
                <i class="bi {{ $icon }}"></i>
            </div>
        @endif

        {{-- FOMO / rozet --}}
        @if($item->badge_label)
            @php
            $badgeStyles = [
                'Vizyon'              => 'background:#b7791f;color:#fff;',
                'Popüler'             => 'background:#3182ce;color:#fff;',
                'Yeni'                => 'background:#38a169;color:#fff;',
                'Son Fırsat'          => 'background:#e53e3e;color:#fff;',
                'İndirim'             => 'background:#dd6b20;color:#fff;',
                'Sınırlı'             => 'background:#805ad5;color:#fff;',
                'Çok Satan'           => 'background:#c05621;color:#fff;',
                'Sıradışı'            => 'background:#0e7490;color:#fff;',
                'Hızlı Tükeniyor'     => 'background:#be123c;color:#fff;',
                'Klasik'              => 'background:#374151;color:#fff;',
                'Efsane'              => 'background:#1e3a5f;color:#fff;',
                'Özel Teklif'         => 'background:#065f46;color:#fff;',
                'Erken Rezervasyon'   => 'background:#5b21b6;color:#fff;',
                'Gastronomi'          => 'background:#92400e;color:#fff;',
                'Gurme'               => 'background:#7c2d12;color:#fff;',
                'Lezzetler'           => 'background:#a16207;color:#fff;',
            ];
            $badgeLabelKeys = [
                'Vizyon'=>'badge_vision','Popüler'=>'badge_popular','Yeni'=>'badge_new_item',
                'Son Fırsat'=>'badge_last_chance','İndirim'=>'badge_discount','Sınırlı'=>'badge_limited',
                'Çok Satan'=>'badge_bestseller','Sıradışı'=>'badge_unique','Hızlı Tükeniyor'=>'badge_selling_fast',
                'Klasik'=>'badge_classic','Efsane'=>'badge_legendary','Özel Teklif'=>'badge_special_offer',
                'Erken Rezervasyon'=>'badge_early_booking','Gastronomi'=>'badge_gastronomy',
                'Gurme'=>'badge_gourmet','Lezzetler'=>'badge_flavors',
            ];
            $badgeStyle  = $badgeStyles[$item->badge_label] ?? 'background:#718096;color:#fff;';
            $badgeText   = isset($badgeLabelKeys[$item->badge_label]) ? __($badgeLabelKeys[$item->badge_label]) : $item->badge_label;
            @endphp
            <div class="gyg-pcard-tag" style="{{ $badgeStyle }}">{{ $badgeText }}</div>
        @elseif($item->is_featured)
            <div class="gyg-pcard-tag featured">{{ __('badge_featured') }}</div>
        @endif

        @php $isSaved = in_array($item->id, $savedIds ?? []); @endphp
        <div class="gyg-pcard-heart {{ $isSaved ? 'saved' : '' }}"
             data-item-id="{{ $item->id }}"
             onclick="event.preventDefault();grtWishlistToggle(this)">
            <i class="bi {{ $isSaved ? 'bi-heart-fill' : 'bi-heart' }}" {{ $isSaved ? 'style=color:#e53e3e' : '' }}></i>
        </div>

        <div class="gyg-pcard-badge">
            @if($item->duration_days)
                {{ $item->duration_days }} {{ __('duration_days_unit') }}
            @elseif($item->duration_hours)
                {{ $item->duration_hours }} {{ __('duration_hours') }}
            @else
                {{ __('duration_flexible') }}
            @endif
        </div>

        {{-- JS tarafından inject edilecek: nearby-pin --}}
    </div>

    <div class="gyg-pcard-body">
        <div class="gyg-pcard-cat">
            {{ $catLabel }}
            @if($item->destination_city) · {{ implode(', ', array_filter([$item->destination_district, $item->destination_city])) }} @endif
        </div>

        <div class="gyg-pcard-title">{{ $item->translatedTitle() }}</div>

        <div class="d-flex align-items-center gap-1" style="margin-bottom:4px;">
            @if(($item->rating_avg ?? 0) > 0)
            <span class="gyg-pcard-stars">{!! str_repeat('★', (int)floor($item->rating_avg)) . ($item->rating_avg - floor($item->rating_avg) >= 0.5 ? '★' : '') . str_repeat('☆', 5 - (int)ceil($item->rating_avg)) !!}</span>
            <span class="gyg-pcard-rating">{{ number_format($item->rating_avg, 1) }}</span>
            @if(($item->review_count ?? 0) > 0)
            <span class="gyg-pcard-reviews">({{ number_format($item->review_count, 0, ',', '.') }})</span>
            @endif
            @else
            <span class="gyg-pcard-stars" style="color:#d1d5db;">☆☆☆☆☆</span>
            <span style="font-size:.72rem;color:#a0aec0;font-weight:600;">{{ __('card_no_rating') }}</span>
            @endif
        </div>

        @if($item->pricing_type === 'fixed' && $item->base_price)
            @php
            if ($item->pricing_unit && isset($unitKeyMap[$item->pricing_unit])) {
                $cardPriceLabel = __($unitKeyMap[$item->pricing_unit]);
            } elseif ($item->pricing_unit) {
                $cardPriceLabel = $item->pricing_unit;
            } else {
                $cardPriceLabel = match($item->product_subtype ?? '') {
                    'yacht_charter'                           => __('pricing_unit_hourly_group'),
                    'helicopter_tour', 'private_jet'          => __('pricing_unit_per_trip'),
                    'airport_transfer', 'intercity_transfer'  => __('pricing_unit_per_vehicle'),
                    'hotel_room', 'apart_rental'              => __('pricing_unit_per_night'),
                    'visa_service'                            => __('pricing_unit_per_application'),
                    default                                   => __('pricing_unit_per_person'),
                };
            }
            @endphp
            <div class="gyg-pcard-price-label">{{ $cardPriceLabel }}</div>
            <div class="gyg-pcard-price">{{ number_format($item->base_price, 0, ',', '.') }} {{ $item->currency ?? 'TRY' }}</div>
            <span class="gyg-pcard-cta">{{ __('card_view') }}</span>
        @elseif($item->pricing_type === 'quote')
            <div class="gyg-pcard-price-label">{{ __('card_custom_price') }}</div>
            <div class="gyg-pcard-price" style="font-size:.95rem;color:#718096;font-weight:600;">{{ __('price_get') }}</div>
            <span class="gyg-pcard-cta outline">{{ __('card_free_quote') }}</span>
        @else
            <div class="gyg-pcard-price-label">{{ __('card_on_request') }}</div>
            <span class="gyg-pcard-cta outline">{{ __('card_get_info') }}</span>
        @endif
    </div>
</a>
