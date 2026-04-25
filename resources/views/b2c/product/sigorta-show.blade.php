@extends('b2c.layouts.app')
@section('title', ($item->meta_title ?? $item->title) . ' — Grup Rezervasyonları')
@section('meta_description', $item->meta_description ?? $item->short_desc ?? ($item->title . ' — Grup Rezervasyonları'))
@if($item->cover_image)
@section('og_image', str_starts_with($item->cover_image,'http') ? $item->cover_image : rtrim(config('app.url'),'/').'/uploads/'.$item->cover_image)
@endif

@push('head_styles')
<style>
.sig-hero {
    background: linear-gradient(135deg, #065f46 0%, #0d9488 50%, #0891b2 100%);
    color: #fff;
    padding: 60px 0 80px;
    position: relative;
    overflow: hidden;
}
.sig-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.sig-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; left: -40px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
}
.sig-hero-inner {
    max-width: 1100px; margin: 0 auto; padding: 0 24px;
    display: grid; grid-template-columns: 1fr 380px; gap: 48px; align-items: center;
    position: relative; z-index: 1;
}
@@media (max-width: 900px) {
    .sig-hero-inner { grid-template-columns: 1fr; gap: 32px; }
}
.sig-hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.15); backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 50px; padding: 5px 14px;
    font-size: .8rem; font-weight: 600; margin-bottom: 16px;
}
.sig-hero h1 {
    font-size: 2.2rem; font-weight: 800; line-height: 1.2;
    margin-bottom: 16px;
}
@@media (max-width: 600px) { .sig-hero h1 { font-size: 1.7rem; } }
.sig-hero-desc {
    font-size: 1.05rem; opacity: .9; line-height: 1.6; margin-bottom: 24px;
}
.sig-trust-row {
    display: flex; gap: 20px; flex-wrap: wrap;
}
.sig-trust-item {
    display: flex; align-items: center; gap: 6px;
    font-size: .82rem; opacity: .85;
}
.sig-trust-item i { font-size: 1rem; }

/* Kart — sağ taraf */
.sig-cta-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    padding: 28px;
    color: #1a202c;
}
.sig-cta-card .cta-title {
    font-size: 1rem; font-weight: 700; margin-bottom: 4px;
}
.sig-cta-card .cta-sub {
    font-size: .85rem; color: #718096; margin-bottom: 20px;
}
.sig-cta-btn {
    display: block; width: 100%;
    background: linear-gradient(135deg, #065f46, #0d9488);
    color: #fff !important; font-weight: 800; font-size: 1rem;
    padding: 15px; border-radius: 10px;
    text-align: center; text-decoration: none !important;
    border: none; cursor: pointer;
    transition: opacity .15s;
    margin-bottom: 12px;
}
.sig-cta-btn:hover { opacity: .9; color: #fff !important; }
.sig-cta-btn:disabled, .sig-cta-btn.disabled {
    background: #94a3b8; cursor: not-allowed; opacity: 1;
}
.sig-cta-note {
    font-size: .78rem; color: #718096; text-align: center;
}
.sig-cta-features {
    margin-top: 18px; padding-top: 18px;
    border-top: 1px solid #f0f4f8;
}
.sig-cta-feature {
    display: flex; align-items: center; gap: 8px;
    font-size: .83rem; color: #4a5568; margin-bottom: 8px;
}
.sig-cta-feature i { color: #0d9488; font-size: .95rem; }

/* İçerik alanı */
.sig-body {
    max-width: 1100px; margin: 0 auto; padding: 48px 24px 64px;
    display: grid; grid-template-columns: 1fr 320px; gap: 40px;
}
@@media (max-width: 900px) { .sig-body { grid-template-columns: 1fr; } }
.sig-main {}
.sig-sidebar {}

.sig-section { margin-bottom: 36px; }
.sig-section-title {
    font-size: 1.15rem; font-weight: 700; color: #1a202c;
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}
.sig-section-title i { color: #0d9488; }

/* Kapsam grid */
.sig-coverage-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;
}
@@media (max-width: 540px) { .sig-coverage-grid { grid-template-columns: 1fr; } }
.sig-coverage-item {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 10px; padding: 14px 16px;
    display: flex; align-items: flex-start; gap: 10px;
}
.sig-coverage-item i { color: #16a34a; font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
.sig-coverage-item .cov-title { font-size: .88rem; font-weight: 600; color: #1a202c; }
.sig-coverage-item .cov-sub { font-size: .78rem; color: #718096; margin-top: 2px; }

/* Nasıl çalışır */
.sig-steps { display: flex; flex-direction: column; gap: 0; }
.sig-step {
    display: flex; gap: 16px; padding: 16px 0;
    border-bottom: 1px solid #f0f4f8;
}
.sig-step:last-child { border-bottom: none; }
.sig-step-num {
    width: 36px; height: 36px; border-radius: 50%;
    background: #0d9488; color: #fff;
    font-size: .9rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sig-step-text h4 { font-size: .9rem; font-weight: 700; margin-bottom: 3px; }
.sig-step-text p  { font-size: .82rem; color: #718096; margin: 0; }

/* Açıkça kapsam dışı */
.sig-exclusion {
    background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 10px; padding: 16px;
}
.sig-exclusion ul { margin: 0; padding-left: 18px; }
.sig-exclusion li { font-size: .85rem; color: #7f1d1d; margin-bottom: 4px; }

/* Full desc (superadmin HTML içeriği) */
.sig-full-desc {
    line-height: 1.8; color: #374151; font-size: .93rem;
}
.sig-full-desc h2,.sig-full-desc h3 { font-weight: 700; margin-top: 1.5rem; color: #1a202c; }
.sig-full-desc ul { padding-left: 20px; }
.sig-full-desc li { margin-bottom: 6px; }

/* Sidebar kartı */
.sig-info-card {
    background: #fff; border: 1px solid #e8eef5; border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    padding: 20px; margin-bottom: 16px;
}
.sig-info-card-title {
    font-size: .88rem; font-weight: 700; color: #1a202c;
    margin-bottom: 12px; display: flex; align-items: center; gap: 6px;
}
.sig-info-card-title i { color: #0d9488; }
.sig-info-row {
    display: flex; justify-content: space-between;
    font-size: .83rem; padding: 6px 0;
    border-bottom: 1px solid #f0f4f8;
}
.sig-info-row:last-child { border-bottom: none; }
.sig-info-row .label { color: #718096; }
.sig-info-row .value { font-weight: 600; color: #1a202c; }

/* Related */
.sig-related { max-width: 1100px; margin: 0 auto; padding: 0 24px 64px; }
.sig-related h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 20px; }
.sig-related-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
@@media (max-width: 700px) { .sig-related-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

{{-- ── Hero ── --}}
<div class="sig-hero">
    <div class="sig-hero-inner">
        <div>
            <div class="sig-hero-badge">
                <i class="bi bi-shield-fill-check"></i>
                Nippon Sigorta · PAO-Net Lisanslı
            </div>
            <h1>{{ $item->title }}</h1>
            @if($item->short_desc)
            <p class="sig-hero-desc">{{ $item->short_desc }}</p>
            @endif
            <div class="sig-trust-row">
                <div class="sig-trust-item"><i class="bi bi-patch-check-fill"></i> SEDDK Lisanslı</div>
                <div class="sig-trust-item"><i class="bi bi-clock-fill"></i> Anında Poliçe</div>
                <div class="sig-trust-item"><i class="bi bi-telephone-fill"></i> 7/24 Destek</div>
                <div class="sig-trust-item"><i class="bi bi-globe2"></i> Tüm Dünya Geçerli</div>
            </div>
        </div>

        {{-- CTA Kartı --}}
        <div class="sig-cta-card">
            <div class="cta-title">Sigorta Fiyatını Hesapla</div>
            <div class="cta-sub">TC kimlik no, tarih ve ülkeyi girerek anında teklif alın.</div>

            @if($sigortaAktif)
                <a href="{{ route('b2c.sigorta.create') }}" class="sig-cta-btn">
                    <i class="bi bi-shield-plus me-2"></i> Teklif Al &amp; Poliçe Yaptır
                </a>
                <p class="sig-cta-note"><i class="bi bi-lock-fill me-1"></i>Güvenli ödeme · SSL şifreli</p>
            @else
                <button class="sig-cta-btn disabled" disabled>Yakında Aktif Olacak</button>
                <p class="sig-cta-note">Sigorta hizmeti çok yakında bu sayfada aktif olacak.</p>
            @endif

            <div class="sig-cta-features">
                <div class="sig-cta-feature"><i class="bi bi-envelope-fill"></i> Poliçe anında e-posta ile iletilir</div>
                <div class="sig-cta-feature"><i class="bi bi-phone-fill"></i> PDF linki SMS ile de gönderilir</div>
                <div class="sig-cta-feature"><i class="bi bi-check-circle-fill"></i> İptal hakkı (poliçe başlangıcına kadar)</div>
                <div class="sig-cta-feature"><i class="bi bi-credit-card-fill"></i> Kredi kartı ile güvenli ödeme</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Gövde ── --}}
<div class="sig-body">
    <main class="sig-main">

        {{-- Kapsam --}}
        <div class="sig-section">
            <div class="sig-section-title"><i class="bi bi-shield-check"></i> Kapsam Özeti</div>
            <div class="sig-coverage-grid">
                <div class="sig-coverage-item">
                    <i class="bi bi-heart-pulse-fill"></i>
                    <div>
                        <div class="cov-title">Sağlık Giderleri</div>
                        <div class="cov-sub">Hastalık ve kaza tedavi masrafları</div>
                    </div>
                </div>
                <div class="sig-coverage-item">
                    <i class="bi bi-airplane-fill"></i>
                    <div>
                        <div class="cov-title">Uçuş Iptali / Gecikmesi</div>
                        <div class="cov-sub">Kaçırılan bağlantı ve iptal tazminatı</div>
                    </div>
                </div>
                <div class="sig-coverage-item">
                    <i class="bi bi-bag-fill"></i>
                    <div>
                        <div class="cov-title">Bagaj Kaybı / Hasarı</div>
                        <div class="cov-sub">Kayıp, çalıntı ve hasar güvencesi</div>
                    </div>
                </div>
                <div class="sig-coverage-item">
                    <i class="bi bi-hospital-fill"></i>
                    <div>
                        <div class="cov-title">Acil Tahliye</div>
                        <div class="cov-sub">Tıbbi nakil ve repatriyasyon</div>
                    </div>
                </div>
                <div class="sig-coverage-item">
                    <i class="bi bi-person-fill-exclamation"></i>
                    <div>
                        <div class="cov-title">Kişisel Sorumluluk</div>
                        <div class="cov-sub">3. şahıslara verilen hasarlar</div>
                    </div>
                </div>
                <div class="sig-coverage-item">
                    <i class="bi bi-telephone-fill"></i>
                    <div>
                        <div class="cov-title">7/24 Yardım Hattı</div>
                        <div class="cov-sub">Acil durumda anında destek</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nasıl Çalışır --}}
        <div class="sig-section">
            <div class="sig-section-title"><i class="bi bi-lightning-fill"></i> Nasıl Çalışır?</div>
            <div class="sig-steps">
                <div class="sig-step">
                    <div class="sig-step-num">1</div>
                    <div class="sig-step-text">
                        <h4>Bilgileri Girin</h4>
                        <p>TC kimlik no (veya pasaport), seyahat tarihleri ve gidilecek ülkeyi girin.</p>
                    </div>
                </div>
                <div class="sig-step">
                    <div class="sig-step-num">2</div>
                    <div class="sig-step-text">
                        <h4>Anında Fiyat Alın</h4>
                        <p>Nippon Sigorta sisteminden gerçek zamanlı fiyat hesaplanır, onaylamanız beklenir.</p>
                    </div>
                </div>
                <div class="sig-step">
                    <div class="sig-step-num">3</div>
                    <div class="sig-step-text">
                        <h4>Güvenli Ödeme Yapın</h4>
                        <p>Kredi kartıyla güvenli ödeme. Kartınızdan çekim onayladıktan sonra poliçe oluşturulur.</p>
                    </div>
                </div>
                <div class="sig-step">
                    <div class="sig-step-num">4</div>
                    <div class="sig-step-text">
                        <h4>Poliçeyi İndirin</h4>
                        <p>Poliçeniz hem <strong>e-postanıza</strong> iletilir hem de PDF linki <strong>SMS ile telefonunuza</strong> gönderilir. Seyahatte yanınızda bulundurun.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Superadmin'in girdiği tam içerik --}}
        @if($item->full_desc)
        <div class="sig-section">
            <div class="sig-section-title"><i class="bi bi-info-circle-fill"></i> Detaylı Bilgi</div>
            <div class="sig-full-desc">{!! $item->full_desc !!}</div>
        </div>
        @endif

        {{-- Kapsam Dışı --}}
        <div class="sig-section">
            <div class="sig-section-title"><i class="bi bi-x-circle-fill" style="color:#dc2626;"></i> Kapsam Dışı (Genel)</div>
            <div class="sig-exclusion">
                <ul>
                    <li>Önceden bilinen kronik hastalıklar (poliçe başlangıcından önce teşhis konulmuş)</li>
                    <li>Kasıtlı yaralanma veya intihar girişimi</li>
                    <li>Savaş, isyan, terör olayları kaynaklı hasarlar</li>
                    <li>Alkol veya uyuşturucu etkisi altında gerçekleşen olaylar</li>
                    <li>Profesyonel sporlar veya tehlikeli aktiviteler (ek paket gerekmez)</li>
                </ul>
            </div>
        </div>

    </main>

    {{-- Sidebar --}}
    <aside class="sig-sidebar">

        <div class="sig-info-card">
            <div class="sig-info-card-title"><i class="bi bi-info-circle-fill"></i> Ürün Bilgisi</div>
            @php
            $subtypeLabels = [
                'seyahat_tc'       => 'TC Kimlikli Yurtdışı Sigortası',
                'seyahat_pasaport' => 'Pasaportlu Yurtdışı Sigortası',
                'seyahat_toplu'    => 'Toplu Grup Sigortası',
            ];
            @endphp
            <div class="sig-info-row">
                <span class="label">Tür</span>
                <span class="value">{{ $subtypeLabels[$item->product_subtype] ?? 'Seyahat Sigortası' }}</span>
            </div>
            <div class="sig-info-row">
                <span class="label">Geçerlilik</span>
                <span class="value">Tüm Dünya</span>
            </div>
            <div class="sig-info-row">
                <span class="label">Sigorta Şirketi</span>
                <span class="value">Nippon Sigorta</span>
            </div>
            <div class="sig-info-row">
                <span class="label">Fiyat</span>
                <span class="value" style="color:#0d9488;">Kişiye Özel</span>
            </div>
            <div class="sig-info-row">
                <span class="label">Poliçe Süresi</span>
                <span class="value">Seyahat Süresince</span>
            </div>
        </div>

        <div class="sig-info-card">
            <div class="sig-info-card-title"><i class="bi bi-question-circle-fill"></i> Sık Sorulan Sorular</div>
            <div style="font-size:.83rem;color:#4a5568;line-height:1.6;">
                <p style="margin-bottom:10px;"><strong>Poliçe ne zaman başlar?</strong><br>
                Ödeme onaylandıktan sonra belirttiğiniz seyahat başlangıç tarihinden itibaren geçerlidir.</p>
                <p style="margin-bottom:10px;"><strong>Pasaportlular kullanabilir mi?</strong><br>
                Evet, yabancı uyruklu ve pasaport numarasıyla da poliçe düzenlenebilir.</p>
                <p style="margin-bottom:0;"><strong>İptal etmek istersem?</strong><br>
                Seyahat başlangıç tarihinden önce iptal talebinde bulunabilirsiniz.</p>
            </div>
        </div>

        @if($sigortaAktif)
        <a href="{{ route('b2c.sigorta.create') }}" class="sig-cta-btn" style="display:block;text-align:center;">
            <i class="bi bi-shield-plus me-2"></i> Hemen Teklif Al
        </a>
        @endif

    </aside>
</div>

{{-- ── İlgili Ürünler ── --}}
@if($relatedItems->isNotEmpty())
<div class="sig-related">
    <h3>Benzer Hizmetler</h3>
    <div class="sig-related-grid">
        @foreach($relatedItems as $rel)
            @include('b2c.home._product-card', ['item' => $rel, 'savedIds' => []])
        @endforeach
    </div>
</div>
@endif

@endsection
