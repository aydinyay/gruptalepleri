<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $yazi->translatedMetaBaslik() ?: $yazi->translatedBaslik() }} — GrupTalepleri Blog</title>
<meta name="description" content="{{ $yazi->translatedMetaAciklama() ?: Str::limit($yazi->translatedOzet() ?? $yazi->ozet, 160) }}">
<link rel="canonical" href="{{ url('/blog/'.$yazi->slug) }}">
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $yazi->translatedBaslik() }}">
<meta property="og:description" content="{{ Str::limit($yazi->translatedOzet() ?? $yazi->ozet, 200) }}">
@if($yazi->kapak_gorseli)
<meta property="og:image" content="{{ $yazi->kapak_gorseli }}">
@endif
<meta property="article:published_time" content="{{ $yazi->yayinlanma_tarihi?->toIso8601String() }}">

{{-- Article Schema --}}
@php
$_ctx = '@context'; $_type = '@type';
$_articleSchema = json_encode([
    $_ctx         => 'https://schema.org',
    $_type        => 'Article',
    'headline'    => $yazi->translatedBaslik(),
    'description' => Str::limit($yazi->translatedOzet() ?? $yazi->ozet, 200),
    'author'      => [$_type=>'Person','name'=>$yazi->yazar],
    'publisher'   => [$_type=>'Organization','name'=>'GrupTalepleri','logo'=>[$_type=>'ImageObject','url'=>'https://gruptalepleri.com/og-image.png']],
    'datePublished' => $yazi->yayinlanma_tarihi?->toIso8601String(),
    'dateModified'  => $yazi->updated_at->toIso8601String(),
    'image'         => $yazi->kapak_gorseli ?: 'https://gruptalepleri.com/og-image.png',
    'url'           => url('/blog/'.$yazi->slug),
    'mainEntityOfPage' => ['@type'=>'WebPage','@id'=>url('/blog/'.$yazi->slug)],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
@endphp
<script type="application/ld+json">{!! $_articleSchema !!}</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Barlow', sans-serif; background: #f8f9fa; }
.blog-hero { background: linear-gradient(135deg, #0f2544 0%, #1a3c6e 100%); color: #fff; padding: 3rem 0 2.5rem; }
.blog-hero h1 { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(1.6rem,4vw,2.5rem); font-weight: 800; line-height: 1.2; }
.blog-content { background: #fff; border-radius: 14px; padding: 2.5rem; box-shadow: 0 2px 16px rgba(0,0,0,.06); }
.blog-content h2 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.5rem; font-weight: 800; color: #0f2544; margin: 2rem 0 .75rem; }
.blog-content h3 { font-size: 1.15rem; font-weight: 700; color: #0f2544; margin: 1.5rem 0 .5rem; }
.blog-content p { color: #374151; line-height: 1.8; margin-bottom: 1rem; }
.blog-content ul, .blog-content ol { color: #374151; line-height: 1.8; margin-bottom: 1rem; padding-left: 1.5rem; }
.blog-content strong { color: #0f2544; }
.blog-content img { max-width: 100%; border-radius: 10px; margin: 1rem 0; }
.kat-badge { background: #e8a020; color: #fff; font-size: .75rem; font-weight: 700; padding: 3px 12px; border-radius: 50px; text-decoration: none; }
.ilgili-card { background: #fff; border-radius: 12px; border: 1px solid #e9ecef; padding: 1rem; text-decoration: none; display: block; transition: box-shadow .2s; }
.ilgili-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); }
.cta-box { background: linear-gradient(135deg,#0f2544,#1a3c6e); border-radius: 14px; padding: 2rem; color: #fff; text-align: center; margin-top: 2rem; }
</style>
</head>
<body>

<div class="blog-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-3" style="font-size:.8rem;opacity:.6;">
            <a href="/" style="color:inherit;">Ana Sayfa</a> ›
            <a href="/blog" style="color:inherit;">Blog</a>
            @if($yazi->kategori)
            › <a href="/blog/kategori/{{ $yazi->kategori->slug }}" style="color:inherit;">{{ $yazi->kategori->ad }}</a>
            @endif
        </div>
        @if($yazi->kategori)
        <span class="kat-badge mb-2 d-inline-block">{{ $yazi->kategori->ad }}</span>
        @endif
        <h1>{{ $yazi->translatedBaslik() }}</h1>
        <div class="mt-2" style="font-size:.85rem;opacity:.65;">
            <i class="fas fa-user me-1"></i>{{ $yazi->yazar }}
            &nbsp;·&nbsp;
            <i class="fas fa-calendar me-1"></i>{{ $yazi->yayinlanma_tarihi?->format('d M Y') }}
            &nbsp;·&nbsp;
            <i class="fas fa-eye me-1"></i>{{ number_format($yazi->goruntuleme) }} görüntüleme
        </div>
    </div>
</div>

@if($yazi->kapak_gorseli)
<div class="container" style="margin-top:-1.5rem;">
    <img src="{{ $yazi->kapak_gorseli_url }}" alt="{{ $yazi->baslik }}"
         style="width:100%;max-height:400px;object-fit:cover;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.15);">
</div>
@endif

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="blog-content">
                <p class="lead text-muted mb-4">{{ $yazi->translatedOzet() ?? $yazi->ozet }}</p>
                <hr class="mb-4">
                {!! $yazi->translatedIcerik() ?? $yazi->icerik !!}
            </div>

            <div class="cta-box">
                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:.5rem;">
                    Grup Seyahat Talebi Oluşturun
                </h3>
                <p style="opacity:.8;margin-bottom:1.2rem;">Ücretsiz kayıt olun, dakikalar içinde teklif alın.</p>
                <a href="/register" style="background:#e8a020;color:#fff;font-weight:700;padding:12px 32px;border-radius:8px;text-decoration:none;display:inline-block;">
                    Ücretsiz Başlayın →
                </a>
            </div>

            @if($ilgili->count())
            <div class="mt-5">
                <h4 class="fw-bold mb-3" style="font-family:'Barlow Condensed',sans-serif;">İlgili Yazılar</h4>
                <div class="row g-3">
                    @foreach($ilgili as $il)
                    <div class="col-md-4">
                        <a href="/blog/{{ $il->slug }}" class="ilgili-card">
                            <div style="font-size:.85rem;font-weight:700;color:#0f2544;line-height:1.35;margin-bottom:.4rem;">{{ $il->baslik }}</div>
                            <div style="font-size:.78rem;color:#6c757d;">{{ $il->yayinlanma_tarihi?->format('d M Y') }}</div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<footer style="background:#0f2544;color:rgba(255,255,255,.5);text-align:center;padding:1.5rem;font-size:.82rem;">
    <a href="/" style="color:#e8a020;text-decoration:none;font-weight:700;">✈ GrupTalepleri</a>
    &nbsp;·&nbsp; Tüm hakları saklıdır © {{ date('Y') }}
</footer>

</body>
</html>
