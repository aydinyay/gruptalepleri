<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Boğazda Türk Gecesi — Dinner Cruise Paketleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root {
            --gyg-orange: #ff5533;
            --gyg-orange-dark: #e8411d;
            --gyg-text: #1a1a1a;
            --gyg-muted: #595959;
            --gyg-border: #e8e8e8;
            --gyg-bg: #f5f5f5;
            --gyg-card: #ffffff;
            --gyg-star: #f5a623;
            --gyg-radius: 12px;
        }
        html[data-theme="dark"] {
            --gyg-text: #f0f0f0;
            --gyg-muted: #b0b0b0;
            --gyg-border: #333;
            --gyg-bg: #0f1520;
            --gyg-card: #1a2235;
        }

        body { background: var(--gyg-bg); color: var(--gyg-text); min-height: 100vh; }

        /* ── Hero bant ── */
        .dc-hero {
            background: linear-gradient(135deg, #0b1f42 0%, #1a3a6e 60%, #0e2d5a 100%);
            color: #fff;
            padding: 2.5rem 0 2rem;
        }
        .dc-hero-inner { max-width: 900px; }
        .dc-hero h1 { font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 800; margin: 0 0 .6rem; line-height: 1.15; }
        .dc-hero p { color: rgba(255,255,255,.82); margin: 0 0 1rem; font-size: 1rem; }
        .dc-hero-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .dc-chip { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); padding: .3rem .7rem; font-size: .78rem; font-weight: 600; }
        .dc-rating-strip { display: flex; align-items: center; gap: .4rem; margin-top: .9rem; }
        .dc-rating-strip .stars { color: var(--gyg-star); }
        .dc-rating-strip span { font-size: .88rem; color: rgba(255,255,255,.75); }

        /* ── Filtre bar ── */
        .dc-filter-bar { background: var(--gyg-card); border-bottom: 1px solid var(--gyg-border); padding: .8rem 0; position: sticky; top: 64px; z-index: 99; }
        .dc-filter-bar .container { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .dc-filter-pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1px solid var(--gyg-border); background: var(--gyg-card); color: var(--gyg-text); padding: .38rem .75rem; font-size: .82rem; font-weight: 600; cursor: pointer; transition: border-color .15s, background .15s; white-space: nowrap; }
        .dc-filter-pill:hover, .dc-filter-pill.active { border-color: var(--gyg-orange); color: var(--gyg-orange); background: rgba(255,85,51,.07); }
        .dc-result-count { margin-left: auto; font-size: .82rem; color: var(--gyg-muted); }

        /* ── Kart grid ── */
        .dc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; padding: 1.5rem 0 2.5rem; }

        /* ── Ürün kartı ── */
        .dc-card { background: var(--gyg-card); border-radius: var(--gyg-radius); border: 1px solid var(--gyg-border); overflow: hidden; transition: box-shadow .2s, transform .2s; cursor: pointer; text-decoration: none; color: var(--gyg-text); display: flex; flex-direction: column; }
        .dc-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.13); transform: translateY(-2px); text-decoration: none; color: var(--gyg-text); }
        .dc-card-img { position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; background: #ddd; }
        .dc-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s; }
        .dc-card:hover .dc-card-img img { transform: scale(1.04); }
        .dc-badge { position: absolute; top: .6rem; left: .6rem; border-radius: 6px; padding: .3rem .55rem; font-size: .72rem; font-weight: 700; z-index: 2; }
        .dc-badge.popular { background: #fff; color: var(--gyg-text); box-shadow: 0 2px 6px rgba(0,0,0,.15); }
        .dc-badge.certified { background: #0a6fcf; color: #fff; }
        .dc-badge.new { background: #12a354; color: #fff; }
        .dc-wishlist { position: absolute; top: .6rem; right: .6rem; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.92); border: none; display: flex; align-items: center; justify-content: center; color: #888; font-size: .9rem; cursor: pointer; z-index: 2; transition: color .15s; }
        .dc-wishlist:hover { color: var(--gyg-orange); }
        .dc-card-body { padding: .85rem; flex: 1; display: flex; flex-direction: column; gap: .35rem; }
        .dc-card-meta { font-size: .78rem; color: var(--gyg-muted); display: flex; align-items: center; gap: .5rem; }
        .dc-card-title { font-size: 1rem; font-weight: 700; line-height: 1.3; color: var(--gyg-text); }
        .dc-card-features { display: flex; flex-wrap: wrap; gap: .28rem; margin-top: .1rem; }
        .dc-card-feat { font-size: .74rem; color: var(--gyg-muted); display: flex; align-items: center; gap: .22rem; }
        .dc-card-rating { display: flex; align-items: center; gap: .32rem; margin-top: auto; padding-top: .5rem; }
        .dc-card-rating .star { color: var(--gyg-star); font-size: .85rem; }
        .dc-card-rating .score { font-weight: 700; font-size: .88rem; }
        .dc-card-rating .count { font-size: .78rem; color: var(--gyg-muted); }
        .dc-card-footer { border-top: 1px solid var(--gyg-border); padding: .7rem .85rem; display: flex; align-items: center; justify-content: space-between; }
        .dc-price-label { font-size: .72rem; color: var(--gyg-muted); }
        .dc-price-original { font-size: .78rem; color: var(--gyg-muted); text-decoration: line-through; }
        .dc-price-current { font-size: 1.15rem; font-weight: 800; color: var(--gyg-orange); }
        .dc-btn-detail { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1.5px solid var(--gyg-orange); background: transparent; color: var(--gyg-orange); padding: .36rem .75rem; font-size: .8rem; font-weight: 700; text-decoration: none; transition: background .15s, color .15s; }
        .dc-btn-detail:hover { background: var(--gyg-orange); color: #fff; text-decoration: none; }

        /* ── Sayfa başlığı ── */
        .dc-section-title { font-size: 1.2rem; font-weight: 800; margin: 0 0 .1rem; color: var(--gyg-text); }
        .dc-section-sub { font-size: .88rem; color: var(--gyg-muted); }

        @media (max-width: 575px) {
            .dc-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="dinner-cruise" />

{{-- Hero --}}
<div class="dc-hero">
    <div class="container">
        <div class="dc-hero-inner">
            <h1>İstanbul: Türk Gecesi Gösterisi ile<br>Boğazda Akşam Yemeği Gezisi</h1>
            <p>Profesyonel oryantal dans, halk oyunları ve 3 kurslu akşam yemeği eşliğinde Boğaz'da unutulmaz bir gece.</p>
            <div class="dc-hero-chips">
                <span class="dc-chip"><i class="fas fa-clock"></i> 3 saat</span>
                <span class="dc-chip"><i class="fas fa-ship"></i> Kabataş İskelesi</span>
                <span class="dc-chip"><i class="fas fa-utensils"></i> Akşam yemeği dahil</span>
                <span class="dc-chip"><i class="fas fa-star"></i> Canlı Türk Gecesi Şovu</span>
                <span class="dc-chip"><i class="fas fa-car"></i> Transfer opsiyonel</span>
            </div>
            <div class="dc-rating-strip">
                <div class="stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <strong style="color:#fff;">4.6</strong>
                <span>• 2.053 yorum • GetYourGuide sertifikalı ürün</span>
            </div>
        </div>
    </div>
</div>

{{-- Filtre bar --}}
<div class="dc-filter-bar">
    <div class="container">
        <button class="dc-filter-pill active" onclick="filterCards('all', this)"><i class="fas fa-border-all"></i> Tümü</button>
        <button class="dc-filter-pill" onclick="filterCards('popular', this)"><i class="fas fa-fire"></i> En Popüler</button>
        <button class="dc-filter-pill" onclick="filterCards('alcohol', this)"><i class="fas fa-wine-glass-alt"></i> Alkollü</button>
        <button class="dc-filter-pill" onclick="filterCards('transfer', this)"><i class="fas fa-car"></i> Transfer Dahil</button>
        <span class="dc-result-count">{{ $packages->count() }} paket</span>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mt-4 mb-1">
        <div>
            <div class="dc-section-title">Tüm Dinner Cruise Paketleri</div>
            <div class="dc-section-sub">Paketi seçin, detayları inceleyin ve direkt rezervasyon yapın.</div>
        </div>
        <a href="{{ route('acente.dinner-cruise.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Talep Sistemi
        </a>
    </div>

    <div class="dc-grid" id="dc-grid">
        @forelse($packages as $pkg)
            @php
                $badgeClass = match(strtolower((string)($pkg->badge_text ?? ''))) {
                    'en çok tercih edilen' => 'popular',
                    'gyg sertifikalı'       => 'certified',
                    'yeni etkinlik'         => 'new',
                    default                 => 'popular',
                };
                $filterTags = 'all';
                if (str_contains(strtolower((string)($pkg->name_tr ?? '')), 'transfer')) $filterTags .= ' transfer';
                if (str_contains(strtolower(implode(' ', (array)($pkg->includes_tr ?? []))), 'alkollü')) $filterTags .= ' alcohol';
                if ($pkg->badge_text === 'En Çok Tercih Edilen') $filterTags .= ' popular';

                $heroImg = $pkg->hero_image_url ?: 'https://images.pexels.com/photos/3411083/pexels-photo-3411083.jpeg?auto=compress&cs=tinysrgb&w=600';
            @endphp
            <a href="{{ route('acente.dinner-cruise.show', $pkg->code) }}"
               class="dc-card"
               data-tags="{{ $filterTags }}">
                <div class="dc-card-img">
                    <img src="{{ $heroImg }}" alt="{{ $pkg->name_tr }}" loading="lazy">
                    @if($pkg->badge_text)
                        <span class="dc-badge {{ $badgeClass }}">{{ $pkg->badge_text }}</span>
                    @endif
                    <button class="dc-wishlist" onclick="event.preventDefault(); toggleWishlist(this)">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <div class="dc-card-body">
                    <div class="dc-card-meta">
                        @if($pkg->duration_hours)
                            <span><i class="fas fa-clock fa-xs me-1"></i>{{ number_format((float)$pkg->duration_hours, 0) }} saat</span>
                        @endif
                        @if($pkg->pier_name)
                            <span>• <i class="fas fa-ship fa-xs me-1"></i>{{ Str::before($pkg->pier_name, ',') }}</span>
                        @endif
                    </div>

                    <div class="dc-card-title">{{ $pkg->name_tr }}</div>

                    <div class="dc-card-features">
                        @foreach(collect($pkg->includes_tr ?? [])->take(3) as $inc)
                            <span class="dc-card-feat"><i class="fas fa-check-circle fa-xs" style="color:#12a354;"></i> {{ $inc }}</span>
                        @endforeach
                    </div>

                    <div class="dc-card-rating">
                        <span class="star"><i class="fas fa-star fa-xs"></i></span>
                        <span class="score">{{ $pkg->rating ?? '4.6' }}</span>
                        <span class="count">({{ number_format((int)($pkg->review_count ?? 2053)) }})</span>
                    </div>
                </div>

                <div class="dc-card-footer">
                    <div>
                        <div class="dc-price-label">Kişi başı başlangıç</div>
                        @if($pkg->original_price_per_person && $pkg->original_price_per_person > $pkg->base_price_per_person)
                            <div class="dc-price-original">{{ number_format((float)$pkg->original_price_per_person, 0, ',', '.') }} TRY</div>
                        @endif
                        <div class="dc-price-current">{{ number_format((float)($pkg->base_price_per_person ?? 0), 0, ',', '.') }} TRY</div>
                    </div>
                    <span class="dc-btn-detail">İncele <i class="fas fa-arrow-right fa-xs"></i></span>
                </div>
            </a>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-ship fa-3x mb-3 opacity-25"></i>
                    <p>Henüz aktif paket tanımı bulunmuyor.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Bilgi kutusu --}}
    <div class="row g-3 pb-4">
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: var(--gyg-card); border: 1px solid var(--gyg-border);">
                <div class="fw-bold mb-1"><i class="fas fa-shield-alt text-success me-2"></i>Ücretsiz İptal</div>
                <div class="small text-muted">Hizmetten 24 saat öncesine kadar ücretsiz iptal hakkı.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: var(--gyg-card); border: 1px solid var(--gyg-border);">
                <div class="fw-bold mb-1"><i class="fas fa-clock text-primary me-2"></i>Anında Onay</div>
                <div class="small text-muted">Rezervasyonunuz ödeme sonrası anında onaylanır, beklemenize gerek yok.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: var(--gyg-card); border: 1px solid var(--gyg-border);">
                <div class="fw-bold mb-1"><i class="fas fa-headset text-warning me-2"></i>7/24 Destek</div>
                <div class="small text-muted">Operasyon ekibimiz her zaman ulaşılabilir, sorularınızı yanıtlar.</div>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
<script>
function filterCards(tag, btn) {
    document.querySelectorAll('.dc-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#dc-grid .dc-card').forEach(card => {
        const tags = card.dataset.tags || 'all';
        card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
    });
}

function toggleWishlist(btn) {
    const icon = btn.querySelector('i');
    if (icon.classList.contains('far')) {
        icon.classList.replace('far', 'fas');
        btn.style.color = '#ff5533';
    } else {
        icon.classList.replace('fas', 'far');
        btn.style.color = '';
    }
}
</script>
</body>
</html>
