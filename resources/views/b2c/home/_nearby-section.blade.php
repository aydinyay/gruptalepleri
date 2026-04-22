<section style="padding:2.5rem 0 0;">
    <div class="container" style="max-width:1280px;">
        <div class="gyg-section-head">
            <div>
                <h2>Yakınınızda keşfedilecekler <span style="font-size:.65em;font-weight:400;opacity:.65;">({{ $label ?? $city }})</span></h2>
                <p>{{ $city }} bölgesindeki deneyimler</p>
            </div>
            <a href="{{ route('b2c.catalog.index') }}?sehir={{ urlencode($city) }}" class="gyg-see-all">Tümünü Gör →</a>
        </div>
        <div class="gyg-products-grid">
            @foreach($items as $item)
                @include('b2c.home._product-card', ['item' => $item, 'savedIds' => []])
            @endforeach
        </div>
    </div>
</section>
