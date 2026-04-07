@extends('b2c.layouts.app')

@section('title', 'Grup Seyahat Platformu')
@section('meta_description', 'Transfer, charter uçuş, dinner cruise, yat kiralama, yurt içi ve yurt dışı tur paketleri. Güvenli ödeme, uzman operasyon.')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════════════════ --}}
<section class="py-0" style="background: linear-gradient(135deg, var(--gr-dark) 0%, var(--gr-primary) 100%); min-height: 580px; display:flex; align-items:center;">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge mb-3 px-3 py-2" style="background:rgba(255,255,255,.15);color:#fff;font-size:.85rem;">
                    <i class="bi bi-star-fill me-1" style="color:var(--gr-accent);"></i>
                    Türkiye'nin Lider Grup Seyahat Platformu
                </span>
                <h1 class="fw-800 mb-3 text-white" style="font-size:clamp(1.8rem,4vw,2.8rem);line-height:1.2;">
                    Her Gruba Özel<br>
                    <span style="color:var(--gr-accent);">Seyahat Çözümleri</span>
                </h1>
                <p class="text-white mb-4" style="opacity:.85;font-size:1.05rem;max-width:480px;">
                    Transfer'den charter'a, dinner cruise'dan tur paketlerine kadar tüm grup seyahat hizmetleri güvenli ödeme ve uzman operasyonla tek platformda.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-accent btn-lg px-4">
                        <i class="bi bi-compass me-2"></i>Hizmetleri Keşfet
                    </a>
                    <a href="#hizli-teklif" class="btn btn-lg px-4" style="border:2px solid rgba(255,255,255,.5);color:#fff;">
                        <i class="bi bi-lightning me-2"></i>Hızlı Teklif Al
                    </a>
                </div>

                {{-- Güven rozetleri --}}
                <div class="d-flex flex-wrap gap-4 mt-2">
                    <div class="trust-badge" style="color:rgba(255,255,255,.75);">
                        <i class="bi bi-shield-check" style="color:var(--gr-accent);"></i>
                        Güvenli Ödeme
                    </div>
                    <div class="trust-badge" style="color:rgba(255,255,255,.75);">
                        <i class="bi bi-headset" style="color:var(--gr-accent);"></i>
                        7/24 Destek
                    </div>
                    <div class="trust-badge" style="color:rgba(255,255,255,.75);">
                        <i class="bi bi-award" style="color:var(--gr-accent);"></i>
                        Uzman Operasyon
                    </div>
                    <div class="trust-badge" style="color:rgba(255,255,255,.75);">
                        <i class="bi bi-lightning-fill" style="color:var(--gr-accent);"></i>
                        Hızlı Dönüş
                    </div>
                </div>
            </div>

            <div class="col-lg-6 d-none d-lg-block text-center">
                {{-- Dekoratif istatistik kutuları --}}
                <div class="row g-3 justify-content-center">
                    @foreach([
                        ['bi-airplane','Charter Uçuş','Özel jet, helikopter, uçak kiralama'],
                        ['bi-water','Deniz Hizmetleri','Dinner cruise & yat kiralama'],
                        ['bi-car-front','Transfer','Havalimanı & şehir içi'],
                        ['bi-map','Tur Paketleri','Yurt içi & yurt dışı turlar'],
                    ] as $card)
                    <div class="col-6">
                        <div class="rounded-3 p-3 text-start" style="background:rgba(255,255,255,.1);backdrop-filter:blur(6px);">
                            <i class="bi {{ $card[0] }} fs-4" style="color:var(--gr-accent);"></i>
                            <div class="fw-700 text-white mt-2" style="font-size:.95rem;">{{ $card[1] }}</div>
                            <div style="font-size:.8rem;color:rgba(255,255,255,.65);">{{ $card[2] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     KATEGORİ GRİD
═══════════════════════════════════════════════════════════════════════ --}}
<section style="background:var(--gr-light);">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="gr-section-title">Tüm Seyahat Hizmetleri</h2>
            <p class="gr-section-subtitle">İhtiyacınıza göre hizmet kategorisini seçin</p>
        </div>

        @if($categories->isEmpty())
        {{-- Kategori henüz girilmemiş — varsayılan statik kategoriler --}}
        <div class="row g-3 justify-content-center">
            @foreach([
                ['bi-car-front-fill','transfer','Havalimanı Transferi','Güvenli & konforlu'],
                ['bi-airplane-fill','ozel-jet','Özel Jet & Charter','Uçuş kiralama'],
                ['bi-helicopter','helikopter','Helikopter','Şehir içi & turistik'],
                ['bi-water','dinner-cruise','Dinner Cruise','Tekne turu & akşam yemeği'],
                ['bi-tsunami','yat-kiralama','Yat Kiralama','Özel & kurumsal'],
                ['bi-map-fill','yurt-ici-turlar','Yurt İçi Turlar','Kapadokya, Ege, Karadeniz'],
                ['bi-globe-americas','yurt-disi-turlar','Yurt Dışı Turlar','Avrupa, Dubai, Asya'],
                ['bi-passport','vize','Vize Hizmetleri','Danışmanlık & başvuru'],
            ] as $cat)
            <div class="col-6 col-md-3 col-lg-3">
                <a href="{{ route('b2c.catalog.category', $cat[1]) }}" class="gr-category-card">
                    <div class="cat-icon"><i class="bi {{ $cat[0] }}"></i></div>
                    <div class="cat-name">{{ $cat[2] }}</div>
                    <div style="font-size:.78rem;color:var(--gr-muted);margin-top:.25rem;">{{ $cat[3] }}</div>
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="row g-3 justify-content-center">
            @foreach($categories as $cat)
            <div class="col-6 col-md-3 col-lg-3">
                <a href="{{ route('b2c.catalog.category', $cat->slug) }}" class="gr-category-card">
                    <div class="cat-icon"><i class="bi {{ $cat->icon ?? 'bi-grid' }}"></i></div>
                    <div class="cat-name">{{ $cat->name }}</div>
                    @if($cat->published_items_count > 0)
                    <div style="font-size:.78rem;color:var(--gr-muted);margin-top:.25rem;">{{ $cat->published_items_count }} hizmet</div>
                    @endif
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     ÖNE ÇIKAN ÜRÜNLER
═══════════════════════════════════════════════════════════════════════ --}}
@if($featuredItems->isNotEmpty())
<section>
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="gr-section-title">Öne Çıkan Hizmetler</h2>
                <p class="gr-section-subtitle mb-0">En çok tercih edilen ve seçkin ürünlerimiz</p>
            </div>
            <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-outline btn-sm d-none d-md-inline-flex">
                Tümünü Gör <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach($featuredItems as $item)
            <div class="col-md-6 col-lg-4">
                @include('b2c.home._product-card', ['item' => $item])
            </div>
            @endforeach
        </div>

        <div class="text-center mt-4 d-md-none">
            <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-outline">Tüm Hizmetleri Gör</a>
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     DESTİNASYONLAR
═══════════════════════════════════════════════════════════════════════ --}}
@if($destinations->isNotEmpty())
<section style="background:var(--gr-light);">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="gr-section-title">Popüler Destinasyonlar</h2>
            <p class="gr-section-subtitle">Şehir bazlı hizmet ve tur seçeneklerimizi keşfedin</p>
        </div>
        <div class="row g-3">
            @foreach($destinations as $dest)
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('b2c.destination', \Illuminate\Support\Str::slug($dest->destination_city)) }}"
                   class="d-block rounded-3 p-3 text-center text-decoration-none"
                   style="background:#fff;border:1px solid var(--gr-border);transition:all .2s;"
                   onmouseover="this.style.borderColor='var(--gr-primary)';this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.borderColor='var(--gr-border)';this.style.transform='none'">
                    <i class="bi bi-geo-alt-fill fs-4" style="color:var(--gr-primary);"></i>
                    <div class="fw-600 mt-1" style="font-size:.92rem;color:var(--gr-text);">{{ $dest->destination_city }}</div>
                    <div style="font-size:.78rem;color:var(--gr-muted);">{{ $dest->item_count }} hizmet</div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     HIZLI TEKLİF FORMU
═══════════════════════════════════════════════════════════════════════ --}}
<section id="hizli-teklif" style="background: linear-gradient(135deg, var(--gr-primary) 0%, #2a5298 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <h2 class="text-white fw-700 mb-2" style="font-size:1.8rem;">Hızlı Teklif Alın</h2>
                <p class="text-white mb-4" style="opacity:.85;">İhtiyacınızı bırakın, en kısa sürede uzmanlarımız sizi arasın.</p>

                <form action="{{ route('b2c.quick-lead.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control form-control-lg"
                                   placeholder="Adınız Soyadınız" required
                                   style="border-radius:8px;border:0;">
                        </div>
                        <div class="col-md-4">
                            <input type="tel" name="phone" class="form-control form-control-lg"
                                   placeholder="Telefon Numaranız" required
                                   style="border-radius:8px;border:0;">
                        </div>
                        <div class="col-md-4">
                            <select name="service_type" class="form-select form-select-lg"
                                    style="border-radius:8px;border:0;">
                                <option value="">Hizmet Türü Seçin</option>
                                <option value="transfer">Havalimanı Transferi</option>
                                <option value="charter">Charter / Özel Jet</option>
                                <option value="dinner_cruise">Dinner Cruise</option>
                                <option value="yat">Yat Kiralama</option>
                                <option value="tur">Tur Paketi</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Kısa not (isteğe bağlı)"
                                      style="border-radius:8px;border:0;resize:none;"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-gr-accent btn-lg w-100" style="border-radius:8px;">
                                <i class="bi bi-send me-2"></i>Teklif Talebimi Gönder
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     NEDEN BİZ
═══════════════════════════════════════════════════════════════════════ --}}
<section>
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="gr-section-title">Neden Grup Rezervasyonları?</h2>
        </div>
        <div class="row g-4 text-center">
            @foreach([
                ['bi-shield-check','Güvenli Ödeme','3D Secure ile korumalı, şeffaf ödeme altyapısı.'],
                ['bi-people-fill','Uzman Operasyon','Deneyimli seyahat uzmanları her adımda yanınızda.'],
                ['bi-lightning-fill','Hızlı Dönüş','Teklif taleplerinize 2 saat içinde cevap veriyoruz.'],
                ['bi-building','Seçkin Tedarikçi Ağı','Onaylı ve denetlenen tedarikçilerle güvenli hizmet.'],
                ['bi-headset','7/24 Destek','Seyahat öncesi ve sırası her an ulaşabilirsiniz.'],
                ['bi-check-circle','Şeffaf Fiyatlandırma','Gizli ücret yok, net ve net fiyatlar.'],
            ] as $feat)
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:60px;height:60px;background:var(--gr-light);">
                        <i class="bi {{ $feat[0] }} fs-4" style="color:var(--gr-primary);"></i>
                    </div>
                    <div class="fw-600 mb-1">{{ $feat[1] }}</div>
                    <div style="font-size:.84rem;color:var(--gr-muted);">{{ $feat[2] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     BLOG / REHBER
═══════════════════════════════════════════════════════════════════════ --}}
@if($blogPosts->isNotEmpty())
<section style="background:var(--gr-light);">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="gr-section-title">Seyahat Rehberi & Blog</h2>
                <p class="gr-section-subtitle mb-0">Seyahat ipuçları, destinasyon rehberleri ve haberler</p>
            </div>
            <a href="{{ route('b2c.blog.index') }}" class="btn btn-gr-outline btn-sm d-none d-md-inline-flex">
                Tüm Yazılar <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            @foreach($blogPosts as $post)
            <div class="col-md-4">
                <a href="{{ route('b2c.blog.show', $post->slug) }}" class="text-decoration-none">
                    <div class="gr-card h-100 bg-white">
                        <div class="card-body p-4">
                            @if($post->kategori)
                            <span class="badge mb-2" style="background:var(--gr-light);color:var(--gr-primary);font-weight:600;">
                                {{ $post->kategori->ad }}
                            </span>
                            @endif
                            <h5 class="fw-700 mb-2" style="color:var(--gr-text);font-size:1rem;">{{ $post->baslik }}</h5>
                            @if($post->ozet ?? null)
                            <p style="font-size:.87rem;color:var(--gr-muted);" class="mb-3">
                                {{ Str::limit($post->ozet, 100) }}
                            </p>
                            @endif
                            <span style="font-size:.82rem;color:var(--gr-accent);font-weight:600;">
                                Devamını Oku <i class="bi bi-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     TEDARİKÇİ OL ÇAĞRISI
═══════════════════════════════════════════════════════════════════════ --}}
<section class="py-small" style="background:var(--gr-primary);">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <h4 class="text-white fw-700 mb-1">Ürününüzü veya Hizmetinizi Platformda Listeleyin</h4>
                <p class="mb-0" style="color:rgba(255,255,255,.75);font-size:.94rem;">
                    Tur, transfer, konaklama veya özel hizmet sunuyorsanız platformumuza katılın.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('b2c.supplier-apply.show') }}" class="btn btn-gr-accent btn-lg">
                    <i class="bi bi-building me-2"></i>Tedarikçi Olun
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
// Hızlı teklif form validate
(function() {
    const form = document.querySelector('.needs-validation');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
})();
</script>
@endpush
