@extends('b2c.layouts.app')

@section('title', 'İstek Listesi')

@section('content')
<div style="max-width:1280px;margin:0 auto;padding:40px 24px;">

    <div style="margin-bottom:32px;">
        <h1 style="font-size:1.6rem;font-weight:800;color:var(--gr-primary);margin:0 0 4px;">
            <i class="bi bi-heart-fill" style="color:#e53e3e;margin-right:8px;"></i>İstek Listesi
        </h1>
        <p style="color:var(--gr-muted);font-size:.9rem;margin:0;">
            @if($items->isEmpty())
                Henüz bir ürün eklemediniz.
            @else
                {{ $items->count() }} ürün kayıtlı.
            @endif
        </p>
    </div>

    @if($items->isEmpty())
        <div style="text-align:center;padding:80px 24px;">
            <i class="bi bi-heart" style="font-size:3rem;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
            <p style="color:var(--gr-muted);margin-bottom:24px;">İstek listesi boş. Beğendiğiniz ürünleri kalp ikonuna tıklayarak kaydedin.</p>
            <a href="{{ lroute('b2c.home') }}" class="btn btn-primary" style="background:var(--gr-primary);border:none;border-radius:8px;padding:10px 24px;">
                Ürünleri Keşfet
            </a>
        </div>
    @else
        <div class="gyg-products-grid">
            @foreach($items as $item)
                @include('b2c.home._product-card', ['item' => $item, 'savedIds' => $savedIds])
            @endforeach
        </div>
    @endif

</div>
@endsection
