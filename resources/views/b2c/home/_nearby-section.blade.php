<section style="padding:2rem 0 0;">
    <div class="container" style="max-width:1280px;">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                <span style="background:#10b981;color:#fff;border-radius:20px;padding:.28rem .75rem .28rem .55rem;font-size:.78rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;letter-spacing:.01em;">
                    <i class="bi bi-geo-alt-fill" style="font-size:.75rem;"></i> Size Yakın
                </span>
                <span style="color:#4a5568;font-size:.88rem;font-weight:500;">{{ $label ?? $city }}</span>
            </div>
            <a href="{{ lroute('b2c.catalog.index') }}?sehir={{ urlencode($city) }}" class="gyg-see-all">Tümünü Gör →</a>
        </div>
        <div class="gyg-products-grid">
            @foreach($items as $item)
                @include('b2c.home._product-card', ['item' => $item, 'savedIds' => [], 'nearbyBadge' => true])
            @endforeach
        </div>
    </div>
</section>
