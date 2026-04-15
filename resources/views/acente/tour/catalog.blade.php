<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Günübirlik Turlar — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root {
            --gt-green: #1a7a4a;
            --gt-green-dark: #155f3a;
            --gt-text: #1a1a1a;
            --gt-muted: #595959;
            --gt-border: #e8e8e8;
            --gt-bg: #f5f5f5;
            --gt-card: #ffffff;
            --gt-star: #f5a623;
            --gt-radius: 12px;
        }
        html[data-theme="dark"] {
            --gt-text: #f0f0f0; --gt-muted: #b0b0b0;
            --gt-border: #333; --gt-bg: #0f1520; --gt-card: #1a2235;
        }
        body { background: var(--gt-bg); color: var(--gt-text); min-height: 100vh; }

        .tr-hero {
            background: linear-gradient(135deg, #0d3320 0%, #1a6640 60%, #0f4a2a 100%);
            color: #fff; padding: 2.5rem 0 2rem;
        }
        .tr-hero h1 { font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 800; margin: 0 0 .6rem; line-height: 1.15; }
        .tr-hero p { color: rgba(255,255,255,.82); margin: 0 0 1rem; font-size: 1rem; }
        .tr-hero-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .tr-chip { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); padding: .3rem .7rem; font-size: .78rem; font-weight: 600; }

        .tr-filter-bar { background: var(--gt-card); border-bottom: 1px solid var(--gt-border); padding: .8rem 0; position: sticky; top: 64px; z-index: 99; }
        .tr-filter-bar .container { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .tr-filter-pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1px solid var(--gt-border); background: var(--gt-card); color: var(--gt-text); padding: .38rem .75rem; font-size: .82rem; font-weight: 600; cursor: pointer; transition: border-color .15s, background .15s; white-space: nowrap; }
        .tr-filter-pill:hover, .tr-filter-pill.active { border-color: var(--gt-green); color: var(--gt-green); background: rgba(26,122,74,.07); }
        .tr-result-count { margin-left: auto; font-size: .82rem; color: var(--gt-muted); }

        .tr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; padding: 1.5rem 0 2.5rem; }

        .tr-card { background: var(--gt-card); border-radius: var(--gt-radius); border: 1px solid var(--gt-border); overflow: hidden; transition: box-shadow .2s, transform .2s; cursor: pointer; text-decoration: none; color: var(--gt-text); display: flex; flex-direction: column; }
        .tr-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.13); transform: translateY(-2px); text-decoration: none; color: var(--gt-text); }
        .tr-card-img { position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; background: #ddd; }
        .tr-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s; }
        .tr-card:hover .tr-card-img img { transform: scale(1.04); }
        .tr-badge { position: absolute; top: .6rem; left: .6rem; border-radius: 6px; padding: .3rem .55rem; font-size: .72rem; font-weight: 700; z-index: 2; }
        .tr-badge.popular { background: #fff; color: var(--gt-text); box-shadow: 0 2px 6px rgba(0,0,0,.15); }
        .tr-badge.certified { background: var(--gt-green); color: #fff; }
        .tr-badge.new { background: #0a6fcf; color: #fff; }
        .tr-card-body { padding: .85rem; flex: 1; display: flex; flex-direction: column; gap: .35rem; }
        .tr-card-meta { font-size: .78rem; color: var(--gt-muted); display: flex; align-items: center; gap: .5rem; }
        .tr-card-title { font-size: 1rem; font-weight: 700; line-height: 1.3; color: var(--gt-text); }
        .tr-card-features { display: flex; flex-wrap: wrap; gap: .28rem; margin-top: .1rem; }
        .tr-card-feat { font-size: .74rem; color: var(--gt-muted); display: flex; align-items: center; gap: .22rem; }
        .tr-card-rating { display: flex; align-items: center; gap: .32rem; margin-top: auto; padding-top: .5rem; }
        .tr-card-rating .star { color: var(--gt-star); font-size: .85rem; }
        .tr-card-footer { border-top: 1px solid var(--gt-border); padding: .7rem .85rem; display: flex; align-items: center; justify-content: space-between; }
        .tr-price-label { font-size: .72rem; color: var(--gt-muted); }
        .tr-price-current { font-size: 1.15rem; font-weight: 800; color: var(--gt-green); }
        .tr-btn-detail { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1.5px solid var(--gt-green); background: transparent; color: var(--gt-green); padding: .36rem .75rem; font-size: .8rem; font-weight: 700; text-decoration: none; transition: background .15s, color .15s; }
        .tr-btn-detail:hover { background: var(--gt-green); color: #fff; text-decoration: none; }

        .tr-section-title { font-size: 1.2rem; font-weight: 800; margin: 0 0 .1rem; color: var(--gt-text); }
        .tr-section-sub { font-size: .88rem; color: var(--gt-muted); }

        @media (max-width: 575px) { .tr-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="tour" />

<div class="tr-hero">
    <div class="container">
        <div style="max-width:900px;">
            <h1>İstanbul ve Çevresi<br>Günübirlik Tur Paketleri</h1>
            <p>Bursa, Sapanca, Abant ve daha fazlası. Rehberli veya bireysel günübirlik turlarını hemen rezerve edin.</p>
            <div class="tr-hero-chips">
                <span class="tr-chip"><i class="fas fa-bus"></i> Konforlu Ulaşım</span>
                <span class="tr-chip"><i class="fas fa-map-marked-alt"></i> Rehber Dahil</span>
                <span class="tr-chip"><i class="fas fa-clock"></i> Günübirlik</span>
                <span class="tr-chip"><i class="fas fa-users"></i> Gruplu & Özel</span>
            </div>
        </div>
    </div>
</div>

<div class="tr-filter-bar">
    <div class="container">
        <button class="tr-filter-pill active" onclick="filterCards('all', this)"><i class="fas fa-border-all"></i> Tümü</button>
        <button class="tr-filter-pill" onclick="filterCards('popular', this)"><i class="fas fa-fire"></i> En Popüler</button>
        <button class="tr-filter-pill" onclick="filterCards('group', this)"><i class="fas fa-users"></i> Gruplu</button>
        <button class="tr-filter-pill" onclick="filterCards('private', this)"><i class="fas fa-user-shield"></i> Özel</button>
        <span class="tr-result-count">{{ $packages->count() }} tur</span>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mt-4 mb-1">
        <div>
            <div class="tr-section-title">Tüm Tur Paketleri</div>
            <div class="tr-section-sub">Paketi seçin, detayları inceleyin ve direkt rezervasyon yapın.</div>
        </div>
    </div>

    <div class="tr-grid" id="tr-grid">
        @forelse($packages as $pkg)
            @php
                $badgeClass = match(strtolower((string)($pkg->badge_text ?? ''))) {
                    'en çok tercih edilen' => 'popular',
                    'gt sertifikalı'        => 'certified',
                    'yeni etkinlik'         => 'new',
                    default                 => 'popular',
                };
                $filterTags = 'all';
                if (str_contains(strtolower((string)($pkg->name_tr ?? '')), 'gruplu')) $filterTags .= ' group';
                if (str_contains(strtolower((string)($pkg->name_tr ?? '')), 'özel')) $filterTags .= ' private';
                if ($pkg->badge_text === 'En Çok Tercih Edilen') $filterTags .= ' popular';
                $heroImg = $pkg->hero_image_url ?: 'https://images.pexels.com/photos/2325446/pexels-photo-2325446.jpeg?auto=compress&cs=tinysrgb&w=600';
            @endphp
            <a href="{{ route('acente.tour.show-product', $pkg->code) }}"
               class="tr-card"
               data-tags="{{ $filterTags }}">
                <div class="tr-card-img">
                    <img src="{{ $heroImg }}" alt="{{ $pkg->name_tr }}" loading="lazy">
                    @if($pkg->badge_text)
                        <span class="tr-badge {{ $badgeClass }}">{{ $pkg->badge_text }}</span>
                    @endif
                </div>
                <div class="tr-card-body">
                    <div class="tr-card-meta">
                        @if($pkg->duration_hours)
                            <span><i class="fas fa-clock fa-xs me-1"></i>{{ number_format((float)$pkg->duration_hours, 0) }} saat</span>
                        @endif
                        @if($pkg->pier_name)
                            <span>• <i class="fas fa-map-marker-alt fa-xs me-1"></i>{{ Str::before($pkg->pier_name, ',') }}</span>
                        @endif
                    </div>
                    <div class="tr-card-title">{{ $pkg->name_tr }}</div>
                    <div class="tr-card-features">
                        @foreach(collect($pkg->includes_tr ?? [])->take(3) as $inc)
                            <span class="tr-card-feat"><i class="fas fa-check-circle fa-xs" style="color:#1a7a4a;"></i> {{ $inc }}</span>
                        @endforeach
                    </div>
                    <div class="tr-card-rating">
                        <span class="star"><i class="fas fa-star fa-xs"></i></span>
                        <span style="font-weight:700;font-size:.88rem;">{{ $pkg->rating ?? '4.7' }}</span>
                        <span style="font-size:.78rem;color:var(--gt-muted);">({{ number_format((int)($pkg->review_count ?? 842)) }})</span>
                    </div>
                </div>
                <div class="tr-card-footer">
                    <div>
                        @if($pkg->original_price_per_person)
                            <div class="tr-price-label">Önerilen satış: {{ number_format((float)$pkg->original_price_per_person, 0, ',', '.') }} {{ $pkg->currency ?: 'EUR' }}/kişi</div>
                        @else
                            <div class="tr-price-label">Kişi başı B2B fiyat</div>
                        @endif
                        <div class="tr-price-current">{{ number_format((float)($pkg->base_price_per_person ?? 0), 0, ',', '.') }} {{ $pkg->currency ?: 'EUR' }}</div>
                    </div>
                    <span class="tr-btn-detail">İncele <i class="fas fa-arrow-right fa-xs"></i></span>
                </div>
            </a>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-map fa-3x mb-3 opacity-25"></i>
                    <p>Henüz aktif tur paketi tanımı bulunmuyor.</p>
                    <small>Superadmin → Leisure Ayarları'ndan tur paketi ekleyebilirsiniz.</small>
                </div>
            </div>
        @endforelse
    </div>

    <div class="row g-3 pb-4">
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background:var(--gt-card);border:1px solid var(--gt-border);">
                <div class="fw-bold mb-1"><i class="fas fa-shield-alt text-success me-2"></i>Ücretsiz İptal</div>
                <div class="small text-muted">Turdan 24 saat öncesine kadar ücretsiz iptal hakkı.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background:var(--gt-card);border:1px solid var(--gt-border);">
                <div class="fw-bold mb-1"><i class="fas fa-clock text-primary me-2"></i>Anında Onay</div>
                <div class="small text-muted">Ödeme sonrası rezervasyon anında onaylanır.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background:var(--gt-card);border:1px solid var(--gt-border);">
                <div class="fw-bold mb-1"><i class="fas fa-headset text-warning me-2"></i>7/24 Destek</div>
                <div class="small text-muted">Operasyon ekibimiz her zaman ulaşılabilir.</div>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.leisure-footer')
@include('acente.partials.theme-script')
<script>
function filterCards(tag, btn) {
    document.querySelectorAll('.tr-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#tr-grid .tr-card').forEach(card => {
        const tags = card.dataset.tags || 'all';
        card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
    });
}
</script>
</body>
</html>
