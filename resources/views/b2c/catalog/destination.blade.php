@extends('b2c.layouts.app')
@section('title', $city . ' Hizmetleri')
@section('meta_description', $city . ' için transfer, tur ve seyahat hizmetleri — Grup Rezervasyonları')

@section('content')
<section style="background:var(--gr-light);padding:2.5rem 0 1.5rem;">
    <div class="container">
        <nav aria-label="breadcrumb" style="font-size:.85rem;">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('b2c.home') }}">Ana Sayfa</a></li>
                <li class="breadcrumb-item active">{{ ucfirst($city) }}</li>
            </ol>
        </nav>
        <h1 class="gr-section-title"><i class="bi bi-geo-alt me-2"></i>{{ ucfirst($city) }} Hizmetleri</h1>
    </div>
</section>
<section>
    <div class="container">
        @if($items->isEmpty())
            <div class="text-center py-5">
                <p style="color:var(--gr-muted);">Bu destinasyon için henüz hizmet bulunmuyor.</p>
                <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-primary mt-2">Tüm Hizmetlere Bak</a>
            </div>
        @else
            <div class="row g-4">
                @foreach($items as $item)
                <div class="col-md-6 col-lg-4">
                    @include('b2c.home._product-card', ['item' => $item])
                </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </div>
</section>
@endsection
