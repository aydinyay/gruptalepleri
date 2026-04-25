@extends('b2c.layouts.app')
@section('title', 'Hakkımızda — Grup Rezervasyonları')
@section('meta_description', 'Grup Rezervasyonları hakkında bilgi alın. Türkiye\'nin lider grup seyahat platformu.')

@push('head_styles')
<style>
.about-header {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 100%);
    padding: 3.5rem 0 3rem; text-align: center;
}
.about-section {
    max-width: 900px; margin: 0 auto; padding: 48px 24px;
}
.about-stat-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
    max-width: 900px; margin: 0 auto; padding: 0 24px 48px;
}
@@media (max-width: 640px) { .about-stat-grid { grid-template-columns: 1fr 1fr; } }
.about-stat-box {
    background: #fff; border: 1px solid #e8eef5; border-radius: 14px;
    padding: 24px 16px; text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.about-stat-num { font-size: 2rem; font-weight: 900; color: #1a3c6b; line-height: 1; }
.about-stat-label { font-size: .82rem; color: #718096; margin-top: 6px; }
.about-value-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
@@media (max-width: 540px) { .about-value-grid { grid-template-columns: 1fr; } }
.about-value-item {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 16px; background: #f7faff; border-radius: 10px;
    border: 1px solid #e8eef5;
}
.about-value-item i { font-size: 1.2rem; color: #1a3c6b; flex-shrink: 0; margin-top: 2px; }
</style>
@endpush

@section('content')

<div class="about-header">
    <div style="max-width:700px;margin:0 auto;padding:0 24px;">
        <div class="gyg-breadcrumb" style="background:transparent;border:none;padding:0 0 12px;justify-content:center;">
            <a href="{{ lroute('b2c.home') }}" style="color:rgba(255,255,255,.6);">Ana Sayfa</a>
            <span class="sep" style="color:rgba(255,255,255,.4);">›</span>
            <span style="color:rgba(255,255,255,.9);">Hakkımızda</span>
        </div>
        <h1 style="color:#fff;font-size:2rem;font-weight:900;margin:0 0 12px;">
            Türkiye'nin Grup Seyahat Platformu
        </h1>
        <p style="color:rgba(255,255,255,.75);font-size:1rem;line-height:1.7;margin:0;">
            Transfer'den charter'a, dinner cruise'dan tur paketlerine — grup seyahatini kolaylaştırmak için buradayız.
        </p>
    </div>
</div>

{{-- İstatistikler --}}
<div style="background:#f7f9fc;padding:32px 0;">
    <div class="about-stat-grid">
        <div class="about-stat-box"><div class="about-stat-num">14.000+</div><div class="about-stat-label">Mutlu Müşteri</div></div>
        <div class="about-stat-box"><div class="about-stat-num">4.8/5</div><div class="about-stat-label">Ortalama Puan</div></div>
        <div class="about-stat-box"><div class="about-stat-num">50+</div><div class="about-stat-label">Hizmet Kategorisi</div></div>
        <div class="about-stat-box"><div class="about-stat-num">7/24</div><div class="about-stat-label">Müşteri Desteği</div></div>
    </div>
</div>

{{-- Kim olduğumuz --}}
<div class="about-section">
    <h2 style="font-size:1.4rem;font-weight:800;color:#1a202c;margin-bottom:14px;">Biz Kimiz?</h2>
    <p style="color:#4a5568;line-height:1.8;margin-bottom:16px;">
        Grup Rezervasyonları, seyahat ve turizm sektöründeki on yıllık deneyimden doğmuş bir platformdur. B2B acenteler, tur operatörleri ve kurumsal müşterilere verdiğimiz hizmetleri, artık son kullanıcıyla da buluşturuyoruz.
    </p>
    <p style="color:#4a5568;line-height:1.8;">
        Transfer, özel jet charter, dinner cruise, yat kiralama, tur paketleri ve vize danışmanlığı — tüm bu hizmetleri tek çatı altında topluyor, karşılaştırmalı fiyatlandırma ve şeffaf süreç yönetimiyle sunuyoruz.
    </p>

    <h2 style="font-size:1.4rem;font-weight:800;color:#1a202c;margin-top:36px;margin-bottom:16px;">Değerlerimiz</h2>
    <div class="about-value-grid">
        <div class="about-value-item"><i class="bi bi-shield-check-fill"></i><div><div style="font-weight:700;color:#1a202c;margin-bottom:3px;">Güvenilirlik</div><div style="font-size:.87rem;color:#718096;line-height:1.5;">Her rezervasyon onaylanmış, denetlenmiş tedarikçilerden gelir.</div></div></div>
        <div class="about-value-item"><i class="bi bi-eye-fill"></i><div><div style="font-weight:700;color:#1a202c;margin-bottom:3px;">Şeffaflık</div><div style="font-size:.87rem;color:#718096;line-height:1.5;">Gizli ücret yok. Fiyat gördüğünüz fiyattır.</div></div></div>
        <div class="about-value-item"><i class="bi bi-headset"></i><div><div style="font-weight:700;color:#1a202c;margin-bottom:3px;">Destek</div><div style="font-size:.87rem;color:#718096;line-height:1.5;">7/24 operasyon desteği ile her an yanınızdayız.</div></div></div>
        <div class="about-value-item"><i class="bi bi-star-fill"></i><div><div style="font-weight:700;color:#1a202c;margin-bottom:3px;">Kalite</div><div style="font-size:.87rem;color:#718096;line-height:1.5;">Her hizmet kalite standartlarımıza uygun denetlenir.</div></div></div>
    </div>

    <div style="margin-top:40px;text-align:center;">
        <a href="{{ lroute('b2c.catalog.index') }}"
           style="display:inline-block;padding:14px 36px;background:#1a3c6b;color:#fff;border-radius:10px;font-weight:700;font-size:1rem;text-decoration:none;margin-right:12px;">
            Hizmetleri Keşfet
        </a>
        <a href="{{ lroute('b2c.iletisim') }}"
           style="display:inline-block;padding:14px 32px;border:2px solid #1a3c6b;color:#1a3c6b;border-radius:10px;font-weight:700;font-size:1rem;text-decoration:none;">
            İletişime Geç
        </a>
    </div>
</div>
@endsection
