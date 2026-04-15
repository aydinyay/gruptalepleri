<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Checkout - GrupTalepleri</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if(in_array($roleContext, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fff; color: #1e293b; }

        .page-title { font-weight: 800; font-size: 2.2rem; color: #0f172a; letter-spacing: -0.03em; margin-bottom: 0.5rem; line-height: 1.2; }
        .page-meta { font-size: 0.95rem; color: #475569; font-weight: 500; margin-bottom: 1.5rem; }
        .page-meta i { color: #f59e0b; margin-right: 4px; }

        /* GYG GALERİSİ */
        .gyg-gallery {
            display: grid; gap: 8px; height: 420px; border-radius: 16px;
            overflow: hidden; margin-bottom: 2.5rem; background: #f8fafc;
        }
        .gyg-media-item { position: relative; width: 100%; height: 100%; overflow: hidden; }
        .gyg-media-item img,
        .gyg-media-item video { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; background-color: #e2e8f0; }
        .gyg-media-item:hover img,
        .gyg-media-item:hover video { transform: scale(1.03); }
        .more-media-overlay {
            position: absolute; inset: 0; background: rgba(15,23,42,.6); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem;
        }

        /* Medya sayısına göre grid */
        .gyg-gallery[data-count="1"] { grid-template-columns: 1fr; }
        .gyg-gallery[data-count="2"] { grid-template-columns: 1fr 1fr; }
        .gyg-gallery[data-count="3"] { grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
        .gyg-gallery[data-count="3"] .m-0 { grid-row: span 2; }
        .gyg-gallery[data-count="4"] { grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
        .gyg-gallery[data-count="4"] .m-0 { grid-row: span 2; }
        .gyg-gallery[data-count="5"] { grid-template-columns: 2fr 1fr 1fr; grid-template-rows: 1fr 1fr; }
        .gyg-gallery[data-count="5"] .m-0 { grid-row: span 2; }

        @media(max-width: 767px) {
            .gyg-gallery { height: 260px; grid-template-columns: 1fr !important; grid-template-rows: 1fr !important; }
            .gyg-gallery .gyg-media-item:not(.m-0) { display: none; }
        }

        /* Bilgi listesi */
        .info-section-title { font-weight: 800; font-size: 1.4rem; margin-bottom: 1.5rem; color: #0f172a; }
        .gyg-info-list { display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 3rem; }
        .gyg-info-item { display: flex; gap: 1.2rem; }
        .gyg-info-icon { font-size: 1.6rem; color: #1e293b; width: 32px; text-align: center; }
        .gyg-info-text strong { font-size: 1.05rem; color: #0f172a; display: block; margin-bottom: 0.2rem; font-weight: 700; }
        .gyg-info-text span { font-size: 0.9rem; color: #475569; display: block; line-height: 1.5; }

        /* Form */
        .form-label { font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.4rem; }
        .form-control {
            font-family: 'Inter', sans-serif; font-weight: 600; padding: 0.85rem 1rem;
            border: 1px solid #cbd5e1; border-radius: 10px; color: #0f172a;
            font-size: 0.95rem; background: #f8fafc; transition: 0.2s;
        }
        .form-control:focus { background: #fff; border-color: #0071eb; box-shadow: 0 0 0 4px rgba(0,113,235,.1); outline: none; }

        /* Sticky sağ kolon */
        .sticky-sidebar { position: sticky; top: 20px; }
        .price-card { border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.8rem; background: #fff; box-shadow: 0 15px 35px rgba(0,0,0,.06); }
        .price-amount { font-weight: 800; font-size: 2rem; color: #0f172a; line-height: 1.1; margin-bottom: 0.2rem; }
        .price-sub { font-size: 0.85rem; color: #64748b; margin-bottom: 1.5rem; }

        .timer-box {
            background: #fffbeb; border: 1px solid #fde68a; color: #d97706;
            padding: 0.8rem; border-radius: 10px; font-weight: 700; text-align: center;
            margin-bottom: 1.5rem; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px;
        }

        .btn-submit { background-color: #0071eb; color: #fff; font-weight: 800; border-radius: 50px; padding: 1rem; width: 100%; border: none; font-size: 1.1rem; transition: 0.2s; }
        .btn-submit:hover { background-color: #005bbd; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,113,235,.2); }

        .trust-list { margin-top: 1.5rem; list-style: none; padding: 0; }
        .trust-list li { font-size: 0.85rem; color: #475569; margin-bottom: 0.8rem; display: flex; align-items: flex-start; gap: 8px; }
        .trust-list li i { color: #059669; font-size: 1rem; margin-top: 2px; }
    </style>
</head>
<body class="theme-scope">
<x-dynamic-component :component="$navbarComponent" active="transfer" />

<div class="container py-5">

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius:12px;">
            <ul class="mb-0 ps-3 fw-bold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        /* ── Araç görselleri — doğru yöntem ─────────────────────────────── */
        $mediaItems  = [];   // [['url'=>..., 'type'=>'photo'|'video'], ...]

        if ($quote->vehicleType) {
            $vt = $quote->vehicleType;
            $vt->loadMissing('media');

            // Önce video (varsa tek adet, galerinin başına)
            $videoMedia = $vt->media
                ->where('media_type', 'video')
                ->where('is_active', true)
                ->first();
            if ($videoMedia && $videoMedia->resolvedUrl()) {
                $mediaItems[] = ['url' => $videoMedia->resolvedUrl(), 'type' => 'video'];
            }

            // Sonra fotoğraflar (max 6)
            foreach ($vt->media->where('media_type', 'photo')->where('is_active', true)->sortBy('sort_order')->take(6) as $m) {
                $url = $m->resolvedUrl();
                if ($url) {
                    $mediaItems[] = ['url' => $url, 'type' => 'photo'];
                }
            }
        }

        $totalMedia  = count($mediaItems);
        $displayCount = min($totalMedia, 5);

        // Hiç medya yoksa placeholder
        if ($totalMedia === 0) {
            $placeholder = 'https://placehold.co/1200x600/f8fafc/475569?text=' . urlencode($quote->vehicleType?->name ?? 'Transfer Aracı');
            $mediaItems[] = ['url' => $placeholder, 'type' => 'photo'];
            $totalMedia   = 1;
            $displayCount = 1;
        }
    @endphp

    <form method="POST" action="{{ $bookEndpoint }}" id="checkoutForm">
        @csrf
        <div class="row g-5">

            {{-- Sol kolon --}}
            <div class="col-12 col-lg-8">

                <h1 class="page-title">
                    Özel Transfer: {{ $quote->airport?->code }} ➔ {{ $quote->zone?->name }}
                </h1>
                <div class="page-meta">
                    <i class="fas fa-star"></i> 5.0 (Memnuniyet garantisi) &bull;
                    Tedarikçi: <span class="fw-bold text-dark">{{ $quote->supplier?->company_name }}</span>
                </div>

                {{-- GYG Galeri --}}
                <div class="gyg-gallery" data-count="{{ $displayCount }}">
                    @foreach(array_slice($mediaItems, 0, 5) as $i => $item)
                        <div class="gyg-media-item m-{{ $i }}">
                            @if($item['type'] === 'video')
                                <video src="{{ $item['url'] }}" autoplay muted loop playsinline></video>
                            @else
                                <img src="{{ $item['url'] }}"
                                     alt="{{ $quote->vehicleType?->name }} {{ $i + 1 }}"
                                     onerror="this.src='https://placehold.co/400x300/e2e8f0/475569?text=Gorsel+Yok'">
                            @endif
                            @if($i === 4 && $totalMedia > 5)
                                <div class="more-media-overlay">+{{ $totalMedia - 5 }} Görsel</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Transfer detayları --}}
                <h2 class="info-section-title">Transfer Detayları</h2>
                <div class="gyg-info-list">
                    <div class="gyg-info-item">
                        <div class="gyg-info-icon"><i class="far fa-calendar-check"></i></div>
                        <div class="gyg-info-text">
                            <strong>Ücretsiz İptal</strong>
                            <span>Transferden 24 saat öncesine kadar iptal et ve paranın tamamını geri al.</span>
                        </div>
                    </div>
                    <div class="gyg-info-item">
                        <div class="gyg-info-icon"><i class="far fa-clock"></i></div>
                        <div class="gyg-info-text">
                            <strong>Süre: {{ $quote->duration_minutes ?? '--' }} Dakika</strong>
                            <span>Trafik durumuna göre tahmini varış süresidir. Mesafe: {{ number_format((float)($quote->distance_km ?? 0), 1) }} km.</span>
                        </div>
                    </div>
                    <div class="gyg-info-item">
                        <div class="gyg-info-icon"><i class="fas fa-car-side"></i></div>
                        <div class="gyg-info-text">
                            <strong>{{ $quote->vehicleType?->name ?? 'Özel Araç' }} ({{ $quote->pax }} Yolcu)</strong>
                            <span>Bu sadece size özel bir araçtır. Yabancı yolcularla seyahat etmezsiniz.</span>
                        </div>
                    </div>
                    <div class="gyg-info-item">
                        <div class="gyg-info-icon"><i class="fas fa-suitcase-rolling"></i></div>
                        <div class="gyg-info-text">
                            <strong>Bagaj Desteği ve Karşılama</strong>
                            <span>Şoförünüz sizi isminizin yazılı olduğu bir tabela ile karşılayacaktır.</span>
                        </div>
                    </div>
                </div>

                <hr class="mb-5" style="border-color:#e2e8f0;">

                {{-- Yolcu formu --}}
                <h2 class="info-section-title">Yolcu ve Uçuş Bilgileri</h2>
                <p class="text-muted mb-4">Şoförünüzün sizi sorunsuz bulabilmesi için lütfen bilgileri eksiksiz doldurun.</p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">İletişim Adı</label>
                        <input type="text" name="contact_name" class="form-control"
                               value="{{ old('contact_name', auth()->user()?->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon Numarası</label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="{{ old('contact_phone', auth()->user()?->phone ?? '') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tüm Yolcu İsimleri</label>
                        <textarea name="passenger_names" class="form-control" rows="2"
                                  placeholder="Örn: Ali Yılmaz, Ayşe Kaya vb.">{{ old('passenger_names') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Uçuş Numarası</label>
                        <input type="text" name="flight_number" class="form-control"
                               value="{{ old('flight_number') }}" placeholder="Örn: TK1983">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Terminal Bilgisi</label>
                        <input type="text" name="terminal" class="form-control"
                               value="{{ old('terminal') }}" placeholder="Örn: Dış Hatlar">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Karşılama Tabelasında Yazacak İsim</label>
                        <input type="text" name="pickup_sign_name" class="form-control"
                               value="{{ old('pickup_sign_name', auth()->user()?->name ?? '') }}"
                               placeholder="Örn: GrupTalepleri VIP">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tam Alış / Bırakış Adresi</label>
                        <textarea name="exact_pickup_address" class="form-control" rows="2"
                                  placeholder="Örn: Beşiktaş / İstanbul" required>{{ old('exact_pickup_address') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valiz Adedi</label>
                        <input type="number" min="0" max="50" name="luggage_count" class="form-control"
                               value="{{ old('luggage_count', 0) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bebek/Çocuk Koltuğu</label>
                        <input type="number" min="0" max="10" name="child_seat_count" class="form-control"
                               value="{{ old('child_seat_count', 0) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Şoföre Özel Not (Opsiyonel)</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Örn: Bebek arabamız var vb.">{{ old('notes') }}</textarea>
                    </div>
                </div>

            </div>

            {{-- Sağ kolon: fiyat kutusu --}}
            <div class="col-12 col-lg-4">
                <div class="sticky-sidebar">
                    <div class="price-card">

                        <div class="timer-box" id="ttlBadgeContainer">
                            <i class="fas fa-stopwatch"></i>
                            <span id="ttlBadge">Yerinizi tutuyoruz...</span>
                        </div>

                        <div class="text-muted fw-bold small mb-1">Toplam Fiyat</div>
                        <div class="price-amount">
                            {{ number_format((float)$quote->total_amount, 2, ',', '.') }} {{ $quote->currency }}
                        </div>
                        <div class="price-sub">Araç başı net fiyat (Vergiler dahildir)</div>

                        <div class="d-flex justify-content-between mb-2 text-dark fw-bold">
                            <span>Tarih:</span>
                            <span>{{ optional($quote->pickup_at)->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($quote->return_at)
                            <div class="d-flex justify-content-between mb-3 text-dark fw-bold pb-2 border-bottom">
                                <span>Dönüş:</span>
                                <span>{{ optional($quote->return_at)->format('d.m.Y H:i') }}</span>
                            </div>
                        @else
                            <div class="pb-2 border-bottom mb-3"></div>
                        @endif

                        <button type="submit" class="btn-submit mt-2">
                            Rezervasyonu Tamamla
                        </button>

                        <div class="text-center mt-3">
                            <a href="{{ $searchRoute }}" class="text-primary text-decoration-none fw-bold" style="font-size:.9rem;">
                                <i class="fas fa-arrow-left me-1"></i> Aramaya Geri Dön
                            </a>
                        </div>

                        <ul class="trust-list border-top pt-4 mt-4">
                            <li><i class="fas fa-check-circle"></i> 24 saat öncesine kadar iptal — paranın tamamı iade</li>
                            <li><i class="fas fa-check-circle"></i> Sadece size özel araç, paylaşımlı değil</li>
                            <li><i class="fas fa-check-circle"></i> Sürpriz ücret yok, her şey dahil</li>
                        </ul>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@if(in_array($roleContext, ['admin', 'superadmin'], true))
    @include('admin.partials.theme-script')
@else
    @include('acente.partials.theme-script')
    @include('acente.partials.leisure-footer')
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const badge          = document.getElementById('ttlBadge');
    const badgeContainer = document.getElementById('ttlBadgeContainer');
    const submitBtn      = document.querySelector('.btn-submit');
    const formInputs     = document.querySelectorAll('.form-control');

    if (!badge) return;

    let remaining = Math.max(0, Math.floor(Number(@json($ttlSeconds))));
    let timerInterval;

    const tick = () => {
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        badge.textContent = minutes > 0
            ? `Yerinizi tutuyoruz: ${minutes} dk ${seconds} sn`
            : `Yerinizi tutuyoruz: ${seconds} sn`;

        if (remaining <= 0) {
            clearInterval(timerInterval);
            badgeContainer.style.cssText = 'background:#fef2f2;border-color:#fca5a5;color:#ef4444;';
            badge.textContent = 'Süre doldu! Fiyat geçerliliğini yitirdi.';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.cssText = 'background:#94a3b8;cursor:not-allowed;box-shadow:none;transform:none;';
                submitBtn.textContent = 'Süre Doldu — Aramaya Dönün';
            }
            formInputs.forEach(i => { i.disabled = true; i.style.background = '#f1f5f9'; });
        }
        remaining = Math.max(0, remaining - 1);
    };

    tick();
    timerInterval = setInterval(tick, 1000);
})();
</script>
</body>
</html>
