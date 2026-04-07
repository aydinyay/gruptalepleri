@extends('b2c.layouts.app')

@section('title', $item->meta_title ?? $item->title)
@section('meta_description', $item->meta_description ?? $item->short_desc ?? $item->title)

@section('content')
<section style="background:var(--gr-light);padding:2rem 0 1rem;">
    <div class="container">
        <nav aria-label="breadcrumb" style="font-size:.85rem;">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('b2c.home') }}">Ana Sayfa</a></li>
                @if($item->category)
                <li class="breadcrumb-item">
                    <a href="{{ route('b2c.catalog.category', $item->category->slug) }}">{{ $item->category->name }}</a>
                </li>
                @endif
                <li class="breadcrumb-item active">{{ Str::limit($item->title, 40) }}</li>
            </ol>
        </nav>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-5">
            {{-- Sol: Görsel + Detaylar --}}
            <div class="col-lg-8">
                {{-- Kapak görseli --}}
                @if($item->cover_image)
                <img src="{{ asset('storage/' . $item->cover_image) }}" alt="{{ $item->title }}"
                     class="rounded-3 w-100 mb-4" style="max-height:420px;object-fit:cover;">
                @else
                <div class="rounded-3 mb-4 d-flex align-items-center justify-content-center"
                     style="height:300px;background:linear-gradient(135deg,var(--gr-primary),#2a5298);">
                    <i class="bi bi-image text-white" style="font-size:3rem;opacity:.3;"></i>
                </div>
                @endif

                <h1 class="fw-800 mb-2" style="font-size:1.8rem;color:var(--gr-primary);">{{ $item->title }}</h1>

                <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.87rem;color:var(--gr-muted);">
                    @if($item->destination_city)
                    <span><i class="bi bi-geo-alt me-1"></i>{{ $item->destination_city }}</span>
                    @endif
                    @if($item->duration_days || $item->duration_hours)
                    <span><i class="bi bi-clock me-1"></i>
                        @if($item->duration_days){{ $item->duration_days }} gün @endif
                        @if($item->duration_hours){{ $item->duration_hours }} saat @endif
                    </span>
                    @endif
                    @if($item->min_pax)
                    <span><i class="bi bi-people me-1"></i>Min {{ $item->min_pax }} kişi</span>
                    @endif
                </div>

                @if($item->short_desc)
                <p class="lead mb-4" style="font-size:1.05rem;color:var(--gr-text);">{{ $item->short_desc }}</p>
                @endif

                @if($item->full_desc)
                <div style="line-height:1.8;color:var(--gr-text);">
                    {!! nl2br(e($item->full_desc)) !!}
                </div>
                @endif

                {{-- Tedarikçi bilgisi --}}
                @if($item->supplier)
                <div class="rounded-3 p-3 mt-4 d-flex align-items-center gap-3"
                     style="background:var(--gr-light);border:1px solid var(--gr-border);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:var(--gr-primary);color:#fff;font-size:1.3rem;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <div class="fw-700" style="font-size:.9rem;">Bu hizmet şu sağlayıcı tarafından sunulmaktadır:</div>
                        <div style="color:var(--gr-primary);font-weight:600;">
                            {{ $item->supplier->agency->company_title ?? $item->supplier->name }}
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Sağ: Fiyat + CTA --}}
            <div class="col-lg-4">
                <div class="rounded-3 p-4 shadow-sm sticky-top" style="background:#fff;border:1px solid var(--gr-border);top:80px;">
                    @if($item->base_price && $item->pricing_type === 'fixed')
                        <div class="mb-1" style="font-size:.85rem;color:var(--gr-muted);">Kişi başı başlangıç fiyatı</div>
                        <div class="fw-800 mb-3" style="font-size:2rem;color:var(--gr-primary);">
                            {{ $item->formatted_price }}
                        </div>
                    @elseif($item->pricing_type === 'quote')
                        <div class="fw-700 mb-3" style="font-size:1.2rem;color:var(--gr-primary);">
                            <i class="bi bi-chat-dots me-2"></i>Fiyat Sorunuz
                        </div>
                    @else
                        <div class="fw-700 mb-3" style="font-size:1.2rem;color:var(--gr-primary);">
                            <i class="bi bi-envelope me-2"></i>Talep Oluşturun
                        </div>
                    @endif

                    @if($item->pricing_type === 'fixed')
                    <form action="{{ route('b2c.cart.add') }}" method="POST">
                        @csrf
                        <input type="hidden" name="catalog_item_id" value="{{ $item->id }}">
                        <div class="mb-3">
                            <label class="form-label fw-600">Kişi Sayısı</label>
                            <input type="number" name="pax_count" class="form-control"
                                   value="1" min="{{ $item->min_pax ?? 1 }}"
                                   max="{{ $item->max_pax ?? 500 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600">Hizmet Tarihi (isteğe bağlı)</label>
                            <input type="date" name="service_date" class="form-control"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <button type="submit" class="btn btn-gr-accent btn-lg w-100">
                            <i class="bi bi-cart-plus me-2"></i>Sepete Ekle
                        </button>
                    </form>
                    @else
                    <a href="#hizli-teklif" class="btn btn-gr-primary btn-lg w-100 mb-2">
                        <i class="bi bi-send me-2"></i>{{ $item->pricing_label }}
                    </a>
                    @endif

                    <div class="mt-3 pt-3" style="border-top:1px solid var(--gr-border);">
                        <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.83rem;color:var(--gr-muted);">
                            <i class="bi bi-shield-check" style="color:var(--gr-accent);"></i> Güvenli ödeme
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.83rem;color:var(--gr-muted);">
                            <i class="bi bi-headset" style="color:var(--gr-accent);"></i> 7/24 destek
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:.83rem;color:var(--gr-muted);">
                            <i class="bi bi-lightning" style="color:var(--gr-accent);"></i> Hızlı onay
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Benzer Ürünler --}}
        @if($relatedItems->isNotEmpty())
        <div class="mt-5">
            <h3 class="fw-700 mb-4" style="color:var(--gr-primary);">Benzer Hizmetler</h3>
            <div class="row g-4">
                @foreach($relatedItems as $related)
                <div class="col-md-4">
                    @include('b2c.home._product-card', ['item' => $related])
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
