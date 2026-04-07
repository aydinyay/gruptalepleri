@extends('b2c.layouts.app')

@section('title', $category->meta_title ?? $category->name)
@section('meta_description', $category->meta_description ?? $category->description ?? $category->name . ' hizmetleri — Grup Rezervasyonları')

@section('content')
<section style="background:var(--gr-light);padding:2.5rem 0 1.5rem;">
    <div class="container">
        <nav aria-label="breadcrumb" style="font-size:.85rem;">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('b2c.home') }}">Ana Sayfa</a></li>
                <li class="breadcrumb-item"><a href="{{ route('b2c.catalog.index') }}">Hizmetler</a></li>
                <li class="breadcrumb-item active">{{ $category->name }}</li>
            </ol>
        </nav>
        <h1 class="gr-section-title mb-1">{{ $category->name }}</h1>
        @if($category->description)
        <p style="color:var(--gr-muted);max-width:600px;">{{ $category->description }}</p>
        @endif

        {{-- Alt kategoriler --}}
        @if($subcategories->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mt-3">
            @foreach($subcategories as $sub)
            <a href="{{ route('b2c.catalog.category', $sub->slug) }}"
               class="btn btn-sm btn-gr-outline">
                {{ $sub->name }} ({{ $sub->published_items_count }})
            </a>
            @endforeach
        </div>
        @endif
    </div>
</section>

<section>
    <div class="container">
        @if($items->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-grid fs-1" style="color:var(--gr-muted);"></i>
                <p class="mt-3" style="color:var(--gr-muted);">Bu kategoride henüz ürün bulunmuyor.</p>
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
