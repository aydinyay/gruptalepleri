{{--
    B2C Ürün Kartı — Partial
    Kullanım: @include('b2c.home._product-card', ['item' => $item])
--}}
<div class="gr-card h-100 bg-white">
    {{-- Görsel --}}
    <div style="position:relative;overflow:hidden;height:200px;">
        @if($item->cover_image)
            <img src="{{ asset('storage/' . $item->cover_image) }}"
                 alt="{{ $item->title }}"
                 style="width:100%;height:100%;object-fit:cover;">
        @else
            <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--gr-primary),#2a5298);display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-image text-white" style="font-size:2.5rem;opacity:.4;"></i>
            </div>
        @endif

        {{-- Ürün tipi rozeti --}}
        <span class="badge-type" style="position:absolute;top:12px;left:12px;">
            {{ match($item->product_type) {
                'transfer' => 'Transfer',
                'charter'  => 'Charter',
                'leisure'  => 'Deniz & Eğlence',
                'tour'     => 'Tur Paketi',
                'hotel'    => 'Konaklama',
                'visa'     => 'Vize',
                default    => 'Hizmet',
            } }}
        </span>

        {{-- Öne çıkan rozeti --}}
        @if($item->is_featured)
        <span style="position:absolute;top:12px;right:12px;background:var(--gr-accent);color:#fff;font-size:.72rem;font-weight:600;padding:.25rem .6rem;border-radius:4px;">
            <i class="bi bi-star-fill me-1"></i>Öne Çıkan
        </span>
        @endif
    </div>

    {{-- İçerik --}}
    <div class="card-body p-3 d-flex flex-column">
        {{-- Destinasyon --}}
        @if($item->destination_city)
        <div style="font-size:.8rem;color:var(--gr-muted);" class="mb-1">
            <i class="bi bi-geo-alt me-1"></i>{{ $item->destination_city }}
            @if($item->destination_country) · {{ $item->destination_country }} @endif
        </div>
        @endif

        <h5 class="fw-700 mb-1" style="font-size:.97rem;color:var(--gr-text);">{{ $item->title }}</h5>

        @if($item->short_desc)
        <p style="font-size:.84rem;color:var(--gr-muted);" class="mb-2 flex-grow-1">
            {{ Str::limit($item->short_desc, 90) }}
        </p>
        @else
        <div class="flex-grow-1"></div>
        @endif

        {{-- Süre --}}
        @if($item->duration_days || $item->duration_hours)
        <div style="font-size:.8rem;color:var(--gr-muted);" class="mb-2">
            <i class="bi bi-clock me-1"></i>
            @if($item->duration_days) {{ $item->duration_days }} gün @endif
            @if($item->duration_hours) {{ $item->duration_hours }} saat @endif
        </div>
        @endif

        {{-- Fiyat ve CTA --}}
        <div class="d-flex justify-content-between align-items-center mt-auto pt-2"
             style="border-top:1px solid var(--gr-border);">
            <div>
                @if($item->base_price && $item->pricing_type === 'fixed')
                    <div class="pricing-label">Başlangıç fiyatı</div>
                    <div class="price-value">{{ $item->formatted_price }}</div>
                @elseif($item->pricing_type === 'quote')
                    <div class="price-value" style="font-size:1rem;">Fiyat sorunuz</div>
                @else
                    <div class="price-value" style="font-size:1rem;">Talep oluşturun</div>
                @endif
            </div>
            <a href="{{ route('b2c.product.show', $item->slug) }}"
               class="btn btn-sm btn-gr-primary px-3">
                {{ $item->pricing_label }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
