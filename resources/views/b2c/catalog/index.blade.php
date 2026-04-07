@extends('b2c.layouts.app')

@section('title', 'Tüm Hizmetler')
@section('meta_description', 'Transfer, charter, dinner cruise, yat kiralama, tur paketleri ve daha fazlası. Grup Rezervasyonları tüm seyahat hizmetleri.')

@section('content')
<section style="background:var(--gr-light);padding:2rem 0 1.5rem;">
    <div class="container">
        <h1 class="gr-section-title">Tüm Hizmetler</h1>

        {{-- Filtreler --}}
        <form method="GET" class="row g-2 mt-2">
            <div class="col-6 col-md-3">
                <select name="tip" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tüm Tipler</option>
                    @foreach(['transfer'=>'Transfer','charter'=>'Charter','leisure'=>'Deniz & Eğlence','tour'=>'Turlar','hotel'=>'Konaklama','visa'=>'Vize','other'=>'Diğer'] as $v => $l)
                    <option value="{{ $v }}" {{ request('tip') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="sehir" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tüm Şehirler</option>
                    @foreach($cities as $city)
                    <option value="{{ $city }}" {{ request('sehir') === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="fiyat" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tüm Fiyat Tipleri</option>
                    <option value="fixed"   {{ request('fiyat') === 'fixed'   ? 'selected' : '' }}>Sabit Fiyatlı</option>
                    <option value="quote"   {{ request('fiyat') === 'quote'   ? 'selected' : '' }}>Fiyat Sorunuz</option>
                    <option value="request" {{ request('fiyat') === 'request' ? 'selected' : '' }}>Talep Oluşturun</option>
                </select>
            </div>
        </form>
    </div>
</section>

<section>
    <div class="container">
        @if($items->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-search fs-1" style="color:var(--gr-muted);"></i>
                <p class="mt-3" style="color:var(--gr-muted);">Bu kriterlere uygun hizmet bulunamadı.</p>
            </div>
        @else
            <div class="row g-4">
                @foreach($items as $item)
                <div class="col-md-6 col-lg-4">
                    @include('b2c.home._product-card', ['item' => $item])
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
