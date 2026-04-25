@extends('b2c.layouts.app')

@section('title', ($category->meta_title ?? $category->name) . ' — Grup Rezervasyonları')
@section('meta_description', $category->meta_description ?? $category->description ?? $category->name . ' hizmetleri — Grup Rezervasyonları')

@push('head_styles')
<style>
.catalog-header {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 100%);
    padding: 2.5rem 0 2rem;
}
.catalog-filter-bar {
    background: #fff;
    border-bottom: 1px solid #e5e5e5;
    padding: 0;
    position: sticky;
    top: 64px;
    z-index: 90;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.filter-bar-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 24px;
    display: flex; align-items: center; gap: 0;
    overflow-x: auto; scrollbar-width: none;
}
.filter-bar-inner::-webkit-scrollbar { display: none; }
.filter-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 14px 16px;
    font-size: .88rem; font-weight: 500; color: #4a5568;
    text-decoration: none; white-space: nowrap;
    border-bottom: 2px solid transparent;
    transition: color .15s, border-color .15s;
    flex-shrink: 0; background: none; border-top: none;
    border-left: none; border-right: none; cursor: pointer;
}
.filter-btn:hover, .filter-btn.active {
    color: #1a3c6b; border-bottom-color: #1a3c6b;
}
.filter-btn.active { font-weight: 700; }
.filter-sep { width: 1px; height: 24px; background: #e5e5e5; flex-shrink: 0; margin: 0 4px; }

.catalog-content {
    max-width: 1280px; margin: 0 auto;
    padding: 28px 24px 48px;
}
.catalog-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
}
.catalog-result-count { font-size: .9rem; color: #718096; }
.catalog-result-count strong { color: #1a202c; }
.sort-select {
    font-size: .88rem; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 7px 12px; color: #1a202c; background: #fff; cursor: pointer;
    outline: none;
}
.sort-select:focus { border-color: #1a3c6b; }

.subcat-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.subcat-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 6px 14px; border-radius: 50px;
    font-size: .82rem; font-weight: 500; color: #1a3c6b;
    border: 1.5px solid #1a3c6b; text-decoration: none;
    transition: background .15s, color .15s;
}
.subcat-pill:hover { background: #1a3c6b; color: #fff; }
.subcat-pill .pill-count {
    font-size: .75rem; background: #e8eef8; color: #1a3c6b;
    border-radius: 50px; padding: 1px 6px; margin-left: 2px;
}
.subcat-pill:hover .pill-count { background: rgba(255,255,255,.25); color: #fff; }
</style>
@endpush

@section('content')

{{-- Sayfa başlığı --}}
<div class="catalog-header">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <div class="gyg-breadcrumb" style="background:transparent;border:none;padding:0 0 12px;">
            <a href="{{ lroute('b2c.home') }}" style="color:rgba(255,255,255,.6);">Ana Sayfa</a>
            <span class="sep" style="color:rgba(255,255,255,.4);">›</span>
            <a href="{{ lroute('b2c.catalog.index') }}" style="color:rgba(255,255,255,.6);">Tüm Hizmetler</a>
            <span class="sep" style="color:rgba(255,255,255,.4);">›</span>
            <span style="color:rgba(255,255,255,.9);">{{ $category->name }}</span>
        </div>
        <h1 style="color:#fff;font-size:1.8rem;font-weight:800;margin:0 0 6px;">
            {{ $category->name }}
        </h1>
        @if($category->description)
        <p style="color:rgba(255,255,255,.7);font-size:.9rem;margin:0 0 4px;max-width:600px;">
            {{ $category->description }}
        </p>
        @endif
        <p style="color:rgba(255,255,255,.6);font-size:.88rem;margin:6px 0 0;">
            {{ $items->total() }} hizmet listelendi
        </p>
    </div>
</div>

{{-- Filtre çubuğu --}}
<div class="catalog-filter-bar">
    <div class="filter-bar-inner">
        <a href="{{ lroute('b2c.catalog.index') }}" class="filter-btn">
            <i class="bi bi-grid-fill"></i> Tüm Hizmetler
        </a>
        <div class="filter-sep"></div>
        @foreach([
            'transfer' => ['bi-car-front-fill',   'Transfer'],
            'charter'  => ['bi-airplane-fill',     'Charter'],
            'leisure'  => ['bi-water',             'Deniz & Eğlence'],
            'tour'     => ['bi-map-fill',          'Turlar'],
            'hotel'    => ['bi-building',          'Konaklama'],
            'visa'     => ['bi-passport',          'Vize'],
        ] as $val => [$ico, $label])
        <a href="{{ lroute('b2c.catalog.index', ['tip' => $val]) }}"
           class="filter-btn {{ ($category->slug === $val || $category->product_type_hint === $val) ? 'active' : '' }}">
            <i class="bi {{ $ico }}"></i> {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- İçerik --}}
<div class="catalog-content">

    {{-- Alt kategoriler --}}
    @if($subcategories->isNotEmpty())
    <div class="subcat-pills">
        @foreach($subcategories as $sub)
        <a href="{{ lroute('b2c.catalog.category', $sub->slug) }}" class="subcat-pill">
            {{ $sub->name }}
            <span class="pill-count">{{ $sub->published_items_count }}</span>
        </a>
        @endforeach
    </div>
    @endif

    {{-- Üst bar: sonuç sayısı + sıralama --}}
    <div class="catalog-topbar">
        <div class="catalog-result-count">
            <strong>{{ $items->total() }}</strong> hizmet bulundu
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            {{-- Sıralama --}}
            <form method="GET" style="margin:0;">
                @foreach(request()->except('sirala','page') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="sirala" class="sort-select" onchange="this.form.submit()">
                    <option value="">Sırala: Önerilen</option>
                    <option value="puan"    {{ request('sirala') === 'puan'    ? 'selected' : '' }}>En Yüksek Puan</option>
                    <option value="fiyat_a" {{ request('sirala') === 'fiyat_a' ? 'selected' : '' }}>Fiyat: Düşükten Yükseğe</option>
                    <option value="fiyat_d" {{ request('sirala') === 'fiyat_d' ? 'selected' : '' }}>Fiyat: Yüksekten Düşüğe</option>
                    <option value="yeni"    {{ request('sirala') === 'yeni'    ? 'selected' : '' }}>En Yeni</option>
                </select>
            </form>
        </div>
    </div>

    {{-- Ürün grid --}}
    @if($items->isEmpty())
        <div style="text-align:center;padding:4rem 0;">
            <i class="bi bi-search" style="font-size:3rem;color:#a0aec0;"></i>
            <p style="color:#718096;margin-top:1rem;font-size:1rem;">Bu kategoride henüz hizmet bulunmuyor.</p>
            <a href="{{ lroute('b2c.catalog.index') }}" class="gyg-pcard-cta" style="display:inline-block;width:auto;padding:12px 32px;margin-top:12px;text-decoration:none;">
                Tüm Hizmetleri Gör
            </a>
        </div>
    @else
        <div class="gyg-products-grid">
            @foreach($items as $item)
                @include('b2c.home._product-card', ['item' => $item, 'savedIds' => $savedIds ?? []])
            @endforeach
        </div>
        <div style="margin-top:32px;">
            {{ $items->links() }}
        </div>
    @endif

</div>
@endsection
