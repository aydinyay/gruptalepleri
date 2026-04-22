<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ isset($kategori) ? $kategori->ad.' — ' : '' }}Blog — GrupTalepleri</title>
<meta name="description" content="Grup uçuşu, charter, yat kiralama ve seyahat operasyonu hakkında rehber yazılar ve ipuçları.">
<link rel="canonical" href="{{ isset($kategori) ? url('/blog/kategori/'.$kategori->slug) : url('/blog') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Barlow', sans-serif; background: #f8f9fa; }
.blog-hero { background: linear-gradient(135deg, #0f2544 0%, #1a3c6e 100%); color: #fff; padding: 3rem 0 2.5rem; }
.blog-hero h1 { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 800; }
.blog-card { background: #fff; border-radius: 14px; border: 1px solid #e9ecef; overflow: hidden; transition: transform .2s, box-shadow .2s; height: 100%; display: flex; flex-direction: column; }
.blog-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.1); }
.blog-card-img { height: 200px; object-fit: cover; width: 100%; background: linear-gradient(135deg,#0f2544,#1a3c6e); }
.blog-card-img-placeholder { height: 200px; background: linear-gradient(135deg,#0f2544,#1a3c6e); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,.3); font-size:2.5rem; }
.blog-card-body { padding: 1.2rem; flex: 1; display: flex; flex-direction: column; }
.blog-card-title { font-family: 'Barlow Condensed', sans-serif; font-size: 1.15rem; font-weight: 700; color: #0f2544; line-height: 1.3; margin-bottom: .5rem; }
.blog-card-ozet { font-size: .85rem; color: #6c757d; flex: 1; line-height: 1.6; }
.blog-card-footer { padding: .75rem 1.2rem; border-top: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; font-size: .78rem; color: #6c757d; }
.kat-badge { background: #e8a020; color: #fff; font-size: .7rem; font-weight: 700; padding: 2px 10px; border-radius: 50px; text-decoration: none; }
</style>
</head>
<body>

<div class="blog-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.8rem;opacity:.6;">
            <a href="/" style="color:inherit;">Ana Sayfa</a> › Blog
            @isset($kategori) › {{ $kategori->ad }} @endisset
        </div>
        <h1>{{ isset($kategori) ? $kategori->ad : 'Seyahat Rehberi & Blog' }}</h1>
        <p style="opacity:.75;max-width:600px;margin:.5rem 0 1.5rem;">Grup uçuşu, charter, yat kiralama ve seyahat operasyonu hakkında profesyonel rehberler.</p>

        @if($kategoriler->count())
        <div class="d-flex flex-wrap gap-2">
            <a href="/blog" class="btn btn-sm {{ !isset($kategori) ? 'btn-light' : 'btn-outline-light' }}">Tümü</a>
            @foreach($kategoriler as $k)
            <a href="/blog/kategori/{{ $k->slug }}"
               class="btn btn-sm {{ isset($kategori) && $kategori->id === $k->id ? 'btn-light' : 'btn-outline-light' }}">
                {{ $k->ad }}
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="container py-5">
    @if($yaziler->count())
    <div class="row g-4">
        @foreach($yaziler as $yazi)
        <div class="col-md-6 col-lg-4">
            <a href="/blog/{{ $yazi->slug }}" style="text-decoration:none;">
            <div class="blog-card">
                @if($yazi->kapak_gorseli)
                    <img src="{{ $yazi->kapak_gorseli_url }}" alt="{{ $yazi->baslik }}" class="blog-card-img">
                @else
                    <div class="blog-card-img-placeholder"><i class="fas fa-newspaper"></i></div>
                @endif
                <div class="blog-card-body">
                    @if($yazi->kategori)
                    <span class="kat-badge mb-2 d-inline-block">{{ $yazi->kategori->ad }}</span>
                    @endif
                    <div class="blog-card-title">{{ $yazi->baslik }}</div>
                    <div class="blog-card-ozet">{{ Str::limit($yazi->ozet, 120) }}</div>
                </div>
                <div class="blog-card-footer">
                    <span>{{ $yazi->yayinlanma_tarihi?->format('d M Y') }}</span>
                    <span>{{ number_format($yazi->goruntuleme) }} görüntüleme</span>
                </div>
            </div>
            </a>
        </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $yaziler->links() }}
    </div>
    @else
    <div class="text-center py-5 text-muted">
        <i class="fas fa-newspaper fa-3x mb-3 opacity-25"></i>
        <p>Henüz yazı yok.</p>
    </div>
    @endif
</div>

<footer style="background:#0f2544;color:rgba(255,255,255,.5);text-align:center;padding:1.5rem;font-size:.82rem;">
    <a href="/" style="color:#e8a020;text-decoration:none;font-weight:700;">✈ GrupTalepleri</a>
    &nbsp;·&nbsp; Tüm hakları saklıdır © {{ date('Y') }}
</footer>

</body>
</html>
