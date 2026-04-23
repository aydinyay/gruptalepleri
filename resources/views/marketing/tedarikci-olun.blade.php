<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tedarikçi Olun — GrupTalepleri</title>
<meta name="description" content="Hizmetlerinizi GrupTalepleri platformuna ekleyin. 500+ acenteye B2B, gruprezervasyonlari.com üzerinden tüketicilere B2C fiyattan satın. Ücretsiz başvuru.">
<link rel="canonical" href="https://gruptalepleri.com/tedarikci-olun">
<meta property="og:title" content="Tedarikçi Olun — GrupTalepleri">
<meta property="og:description" content="Transfer, yat, tur, dinner cruise hizmetlerinizi platforma ekleyin. İki kanalda satış — acentelere B2B, tüketicilere B2C.">
<meta property="og:type" content="website">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--red:#e53e3e;--navy:#1a1a2e;--navy2:#16213e;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Barlow',sans-serif;color:var(--navy);background:#fff;overflow-x:hidden;}
a{text-decoration:none;}

/* NAV */
nav{background:var(--navy);padding:0 5%;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:100;}
.nav-logo{font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:800;color:var(--red);}
.nav-logo span{color:#fff;}
.nav-links{display:flex;gap:1.25rem;align-items:center;}
.nav-link-item{color:rgba(255,255,255,.7);font-size:.88rem;font-weight:500;transition:color .2s;}
.nav-link-item:hover{color:#fff;}
.btn-nav-login{border:1px solid rgba(255,255,255,.3);color:rgba(255,255,255,.8);padding:.4rem 1rem;border-radius:6px;font-size:.85rem;font-weight:600;transition:all .2s;}
.btn-nav-login:hover{border-color:#fff;color:#fff;}
.btn-nav-red{background:var(--red);color:#fff;padding:.4rem 1.1rem;border-radius:6px;font-size:.85rem;font-weight:700;transition:opacity .2s;}
.btn-nav-red:hover{opacity:.88;color:#fff;}

/* HERO */
.td-hero{background:linear-gradient(135deg,#0b1f42 0%,#1a3c6b 60%,#0e2d5a 100%);padding:5.5rem 5% 4rem;text-align:center;}
.td-hero .eyebrow{font-size:.75rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#f59e0b;margin-bottom:1rem;display:block;}
.td-hero h1{font-family:'Barlow Condensed',sans-serif;font-size:clamp(2.2rem,5vw,3.5rem);font-weight:800;color:#fff;line-height:1.1;margin-bottom:1.1rem;}
.td-hero h1 span{color:#f59e0b;}
.td-hero p{color:rgba(255,255,255,.75);font-size:1.1rem;max-width:600px;margin:0 auto 2rem;line-height:1.7;}
.td-hero-btns{display:flex;gap:.9rem;justify-content:center;flex-wrap:wrap;}
.btn-wa{background:#25d366;color:#fff;padding:.8rem 2rem;border-radius:8px;font-weight:700;font-size:1rem;transition:opacity .2s;}
.btn-wa:hover{opacity:.88;color:#fff;}
.btn-red-lg{background:var(--red);color:#fff;padding:.8rem 2rem;border-radius:8px;font-weight:700;font-size:1rem;transition:opacity .2s;}
.btn-red-lg:hover{opacity:.88;color:#fff;}
.hero-stats{display:flex;gap:2.5rem;justify-content:center;flex-wrap:wrap;margin-top:3rem;padding-top:2.5rem;border-top:1px solid rgba(255,255,255,.12);}
.hero-stat{text-align:center;}
.hero-stat strong{display:block;font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:800;color:#fff;line-height:1;}
.hero-stat span{font-size:.8rem;color:rgba(255,255,255,.55);margin-top:.2rem;display:block;}

/* CHANNELS */
.channels-section{padding:5rem 5%;background:#f6f8fc;}
.section-label{font-size:.75rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--red);margin-bottom:.6rem;}
.section-title{font-family:'Barlow Condensed',sans-serif;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;color:var(--navy);margin-bottom:.5rem;line-height:1.1;}
.section-sub{color:#666;font-size:1rem;margin-bottom:3rem;max-width:580px;}
.channels-grid{display:grid;grid-template-columns:1fr 60px 1fr;gap:1.5rem;align-items:stretch;margin-bottom:2rem;}
.channel-card{border-radius:16px;padding:2.5rem 2rem;position:relative;overflow:hidden;}
.channel-b2b{background:var(--navy);color:#fff;}
.channel-b2c{background:linear-gradient(135deg,#065f46,#047857);color:#fff;}
.channel-tag{display:inline-block;font-size:.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:4px 12px;border-radius:50px;margin-bottom:1rem;}
.channel-b2b .channel-tag{background:rgba(96,165,250,.2);color:#60a5fa;}
.channel-b2c .channel-tag{background:rgba(52,211,153,.2);color:#34d399;}
.channel-card h3{font-size:1.5rem;font-weight:800;margin-bottom:.5rem;}
.channel-card p{font-size:.9rem;opacity:.75;line-height:1.6;margin-bottom:1.25rem;}
.channel-domain{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:50px;padding:5px 14px;font-size:.78rem;font-weight:600;color:rgba(255,255,255,.85);}
.channel-features{margin-top:1.5rem;display:flex;flex-direction:column;gap:.5rem;}
.channel-feature{display:flex;align-items:center;gap:.5rem;font-size:.83rem;opacity:.8;}
.channel-feature i{color:#f59e0b;font-size:.75rem;}
.channel-plus{display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:900;color:#1a3c6b;opacity:.3;}
@media(max-width:768px){.channels-grid{grid-template-columns:1fr;}.channel-plus{transform:rotate(90deg);height:40px;}}

/* CATEGORIES */
.cats-section{padding:4rem 5%;background:#fff;}
.cats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-top:2.5rem;}
.cat-card{border:1px solid #e8edf5;border-radius:12px;padding:1.25rem 1rem;text-align:center;transition:box-shadow .2s,transform .2s;}
.cat-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.09);transform:translateY(-2px);}
.cat-icon{font-size:1.6rem;color:var(--red);margin-bottom:.5rem;}
.cat-name{font-size:.85rem;font-weight:700;color:var(--navy);margin-bottom:.2rem;}
.cat-desc{font-size:.72rem;color:#888;}

/* BENEFITS */
.benefits-section{padding:5rem 5%;background:#f6f8fc;}
.benefits-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:2.5rem;}
.benefit-card{background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:1.75rem 1.5rem;}
.benefit-card i{font-size:1.6rem;margin-bottom:.75rem;display:block;}
.benefit-card h4{font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:.4rem;}
.benefit-card p{font-size:.83rem;color:#555;line-height:1.6;margin:0;}
@media(max-width:768px){.benefits-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px){.benefits-grid{grid-template-columns:1fr;}}

/* STEPS */
.steps-section{padding:5rem 5%;background:#fff;}
.steps-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-top:2.5rem;}
.step-card{text-align:center;padding:1.5rem 1rem;}
.step-num{width:44px;height:44px;border-radius:50%;background:rgba(229,62,62,.1);border:2px solid var(--red);color:var(--red);font-weight:800;font-size:1rem;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;}
.step-card h4{font-size:.95rem;font-weight:700;color:var(--navy);margin-bottom:.4rem;}
.step-card p{font-size:.8rem;color:#666;line-height:1.5;margin:0;}
@media(max-width:768px){.steps-grid{grid-template-columns:repeat(2,1fr);}}

/* WHO */
.who-section{padding:4rem 5%;background:var(--navy);}
.who-section .section-title{color:#fff;}
.who-section .section-label{color:#f59e0b;}
.who-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-top:2.5rem;}
.who-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:1.25rem 1rem;display:flex;align-items:flex-start;gap:.75rem;}
.who-card i{font-size:1.3rem;color:#f59e0b;flex-shrink:0;margin-top:.1rem;}
.who-card h4{font-size:.85rem;font-weight:700;color:#fff;margin-bottom:.2rem;}
.who-card p{font-size:.75rem;color:rgba(255,255,255,.55);margin:0;}

/* FAQ */
.faq-section{padding:5rem 5%;background:#f6f8fc;}
.faq-list{max-width:780px;margin:2.5rem auto 0;}
.faq-item{background:#fff;border:1px solid #e8edf5;border-radius:12px;margin-bottom:.75rem;overflow:hidden;}
.faq-q{padding:1.1rem 1.5rem;font-weight:700;font-size:.92rem;color:var(--navy);cursor:pointer;display:flex;justify-content:space-between;align-items:center;}
.faq-q:hover{color:var(--red);}
.faq-a{padding:0 1.5rem 1.1rem;font-size:.85rem;color:#555;line-height:1.65;display:none;}
.faq-item.open .faq-a{display:block;}
.faq-item.open .faq-q{color:var(--red);}

/* FINAL CTA */
.final-cta{background:linear-gradient(135deg,#0b1f42,#1a3c6b);padding:5rem 5%;text-align:center;}
.final-cta h2{font-family:'Barlow Condensed',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:800;color:#fff;margin-bottom:.75rem;}
.final-cta p{color:rgba(255,255,255,.7);font-size:1rem;margin-bottom:2rem;}
.final-cta-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;}
.contact-bar{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:12px;display:inline-flex;align-items:center;gap:1.5rem;padding:1rem 2rem;margin-top:2rem;flex-wrap:wrap;justify-content:center;}
.contact-item{display:flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.7);font-size:.85rem;}
.contact-item a{color:#fff;font-weight:600;}
.contact-item a:hover{color:#f59e0b;}

/* FOOTER */
footer{background:#0b1428;padding:2rem 5%;text-align:center;}
footer p{font-size:.78rem;color:rgba(255,255,255,.3);}
footer a{color:rgba(255,255,255,.5);font-size:.78rem;}
footer a:hover{color:#fff;}
</style>
</head>
<body>

<nav>
    <a href="/" class="nav-logo">✈ Grup<span>Talepleri</span></a>
    <div class="nav-links">
        <a href="/#katalog" class="nav-link-item">Katalog</a>
        <a href="/#pazaryeri" class="nav-link-item">Platform</a>
        <a href="/blog" class="nav-link-item">Blog</a>
        <a href="{{ route('login') }}" class="btn-nav-login">Giriş Yap</a>
        <a href="{{ route('register') }}" class="btn-nav-red">Üye Ol</a>
    </div>
</nav>

{{-- HERO --}}
<section class="td-hero">
    <span class="eyebrow">Tedarikçi Programı</span>
    <h1>Hizmetinizi Bir Kez Ekleyin,<br><span>İki Kanaldan Kazanın</span></h1>
    <p>Transfer, yat kiralama, dinner cruise, tur veya başka hizmetleriniz mi var? Platformumuza ekleyin — hem 500+ acenteye B2B fiyattan satın, hem de gruprezervasyonlari.com vitrinde tüketicilere görünsün.</p>
    <div class="td-hero-btns">
        <a href="https://wa.me/905354154799?text=Merhaba%2C%20GrupTalepleri%27nde%20tedarik%C3%A7i%20olmak%20istiyorum." class="btn-wa" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp me-2"></i>WhatsApp ile Başvur
        </a>
        <a href="{{ route('register') }}" class="btn-red-lg">
            <i class="fas fa-user-plus me-2"></i>Ücretsiz Kayıt
        </a>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><strong>500+</strong><span>Aktif Acente</span></div>
        <div class="hero-stat"><strong>2</strong><span>Satış Kanalı</span></div>
        <div class="hero-stat"><strong>10+</strong><span>Hizmet Kategorisi</span></div>
        <div class="hero-stat"><strong>0 TL</strong><span>Başvuru Ücreti</span></div>
    </div>
</section>

{{-- İKİ KANAL --}}
<section class="channels-section">
    <div class="section-label">İki Kanal, Tek Panel</div>
    <div class="section-title">B2B + B2C: Aynı Ürün, İki Gelir Akışı</div>
    <div class="section-sub">Tek bir ürün kaydıyla hem sektör profesyonellerine hem de bireysel müşterilere ulaşın.</div>
    <div class="channels-grid">
        <div class="channel-card channel-b2b">
            <span class="channel-tag">B2B — Acente Kanalı</span>
            <h3>gruptalepleri.com</h3>
            <p>Türkiye genelindeki turizm acenteleri, seyahat şirketleri ve kurumsal operatörler ürününüzü net (B2B) fiyatlardan satın alır ve kendi müşterilerine satar.</p>
            <span class="channel-domain"><i class="fas fa-lock"></i> Sadece kayıtlı acentelere görünür</span>
            <div class="channel-features">
                <div class="channel-feature"><i class="fas fa-check"></i> B2B net fiyatlar — marj acentede kalır</div>
                <div class="channel-feature"><i class="fas fa-check"></i> Talep & rezervasyon yönetimi</div>
                <div class="channel-feature"><i class="fas fa-check"></i> WhatsApp/SMS bildirim entegrasyonu</div>
                <div class="channel-feature"><i class="fas fa-check"></i> Anlık talep takibi</div>
            </div>
        </div>
        <div class="channel-plus">+</div>
        <div class="channel-card channel-b2c">
            <span class="channel-tag">B2C — Tüketici Kanalı</span>
            <h3>gruprezervasyonlari.com</h3>
            <p>Superadmin onayıyla ürününüz Türkiye'nin grup seyahat vitrininde yayına alınır. Bireysel müşteriler, aileler ve kurumsal gruplar direkt rezervasyon yapar.</p>
            <span class="channel-domain"><i class="fas fa-globe"></i> Herkese açık vitrin</span>
            <div class="channel-features">
                <div class="channel-feature"><i class="fas fa-check"></i> B2C perakende fiyatlar</div>
                <div class="channel-feature"><i class="fas fa-check"></i> SEO & organik trafik dahil</div>
                <div class="channel-feature"><i class="fas fa-check"></i> Favori listesi, yorum & puan</div>
                <div class="channel-feature"><i class="fas fa-check"></i> Mobil uyumlu vitrin sayfası</div>
            </div>
        </div>
    </div>
</section>

{{-- KATEGORİLER --}}
<section class="cats-section">
    <div class="section-label">Kategoriler</div>
    <div class="section-title">Hangi Hizmetleri Ekleyebilirsiniz?</div>
    <div class="cats-grid">
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-car-side"></i></div><div class="cat-name">Havalimanı Transferi</div><div class="cat-desc">VIP, minibüs, otobüs</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-van-shuttle"></i></div><div class="cat-name">Şehirlerarası Transfer</div><div class="cat-desc">Şehir arası grup ulaşımı</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-ship"></i></div><div class="cat-name">Yat Kiralama</div><div class="cat-desc">Özel yat, gulet, tekne</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-anchor"></i></div><div class="cat-name">Dinner Cruise</div><div class="cat-desc">Boğaz turu, gece etkinliği</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-map-location-dot"></i></div><div class="cat-name">Günübirlik Tur</div><div class="cat-desc">Kapadokya, Sapanca, Bursa…</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-masks-theater"></i></div><div class="cat-name">Etkinlik & Gösteri</div><div class="cat-desc">Türk gecesi, konser, show</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-jet-fighter"></i></div><div class="cat-name">Özel Jet</div><div class="cat-desc">VIP & kurumsal sefer</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-helicopter"></i></div><div class="cat-name">Helikopter Turu</div><div class="cat-desc">Şehir & doğa turu</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-building"></i></div><div class="cat-name">Otel & Apart</div><div class="cat-desc">Grup konaklaması</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-wine-glass"></i></div><div class="cat-name">Gastronomi</div><div class="cat-desc">Tadım, gurme deneyim</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-passport"></i></div><div class="cat-name">Vize Hizmetleri</div><div class="cat-desc">Schengen, turist, iş</div></div>
        <div class="cat-card"><div class="cat-icon"><i class="fas fa-plus-circle"></i></div><div class="cat-name">Diğer</div><div class="cat-desc">Her türlü seyahat hizmeti</div></div>
    </div>
</section>

{{-- FAYDALAR --}}
<section class="benefits-section">
    <div class="section-label">Neden GrupTalepleri?</div>
    <div class="section-title">Tedarikçi Olmanın 6 Avantajı</div>
    <div class="benefits-grid">
        <div class="benefit-card">
            <i class="fas fa-hand-holding-usd" style="color:#f59e0b;"></i>
            <h4>Siz Fiyat Belirlersiniz</h4>
            <p>B2B ve B2C fiyatlarınızı ayrı ayrı siz girersiniz. Platform araya girmez, marjınız sizde kalır.</p>
        </div>
        <div class="benefit-card">
            <i class="fas fa-users" style="color:#3b82f6;"></i>
            <h4>500+ Acenteye Ulaşın</h4>
            <p>Platforma kayıtlı tüm acenteler ürününüzü katalogda görür. Tek acente yerine yüzlercesine aynı anda satış yapın.</p>
        </div>
        <div class="benefit-card">
            <i class="fas fa-globe" style="color:#10b981;"></i>
            <h4>B2C Vitrin Dahil</h4>
            <p>gruprezervasyonlari.com'da yayına alındığında organik arama trafiği ve doğrudan tüketici rezervasyonları gelir.</p>
        </div>
        <div class="benefit-card">
            <i class="fas fa-bell" style="color:#8b5cf6;"></i>
            <h4>Anlık Bildirim</h4>
            <p>Her yeni talep ve rezervasyon için WhatsApp ve SMS bildirimi alırsınız. Hiçbir talebi kaçırmazsınız.</p>
        </div>
        <div class="benefit-card">
            <i class="fas fa-chart-bar" style="color:#e53e3e;"></i>
            <h4>Panel Üzerinden Yönetim</h4>
            <p>Gelen talepler, teklifler ve rezervasyonlar tek panelde. Ayrıca sisteme entegre olmak için API desteği mevcut.</p>
        </div>
        <div class="benefit-card">
            <i class="fas fa-rocket" style="color:#f97316;"></i>
            <h4>Hızlı Yayın</h4>
            <p>Başvurunuz onaylandıktan sonra ürünleriniz 24 saat içinde her iki kanalda aktif olur.</p>
        </div>
    </div>
</section>

{{-- ADIMLAR --}}
<section class="steps-section">
    <div class="section-label">Başvuru Süreci</div>
    <div class="section-title">4 Adımda Tedarikçi Olun</div>
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-num">1</div>
            <h4>Ücretsiz Kayıt</h4>
            <p>Acente hesabı açın. Kayıt tamamen ücretsiz, birkaç dakika sürer.</p>
        </div>
        <div class="step-card">
            <div class="step-num">2</div>
            <h4>Tedarikçi Başvurusu</h4>
            <p>WhatsApp veya e-posta ile bize ulaşın. Hangi hizmetleri eklemek istediğinizi bildirin.</p>
        </div>
        <div class="step-card">
            <div class="step-num">3</div>
            <h4>Ürün Yükleme</h4>
            <p>Ekibimiz görsel, fiyat ve açıklamalarınızı sisteme girer. Siz sadece bilgileri iletirsiniz.</p>
        </div>
        <div class="step-card">
            <div class="step-num">4</div>
            <h4>Satış Başlar</h4>
            <p>B2B acentelere ve B2C tüketicilere aynı anda görünmeye başlarsınız. Talepler anında gelir.</p>
        </div>
    </div>
</section>

{{-- KİMLER BAŞVURABİLİR --}}
<section class="who-section">
    <div class="section-label">Kimler Başvurabilir?</div>
    <div class="section-title">Her Ölçekteki Turizm Hizmet Sağlayıcısı</div>
    <div class="who-grid">
        <div class="who-card"><i class="fas fa-car"></i><div><h4>Transfer Firmaları</h4><p>VIP araç, minibüs, otobüs filoları</p></div></div>
        <div class="who-card"><i class="fas fa-ship"></i><div><h4>Yat & Tekne İşletmeleri</h4><p>Gulet, yat, tekne kiralama</p></div></div>
        <div class="who-card"><i class="fas fa-route"></i><div><h4>Tur Operatörleri</h4><p>Günübirlik ve çok günlü turlar</p></div></div>
        <div class="who-card"><i class="fas fa-utensils"></i><div><h4>Dinner Cruise & Restoran</h4><p>Akşam etkinlikleri, özel davet</p></div></div>
        <div class="who-card"><i class="fas fa-hotel"></i><div><h4>Otel & Apart Tesisler</h4><p>Grup konaklaması, kiralama</p></div></div>
        <div class="who-card"><i class="fas fa-jet-fighter"></i><div><h4>Özel Jet & Helikopter</h4><p>VIP seyahat operatörleri</p></div></div>
        <div class="who-card"><i class="fas fa-wine-glass-alt"></i><div><h4>Deneyim & Aktivite</h4><p>Tadım, atölye, spor aktiviteleri</p></div></div>
        <div class="who-card"><i class="fas fa-passport"></i><div><h4>Vize Danışmanları</h4><p>Vize başvuru ve danışmanlık</p></div></div>
    </div>
</section>

{{-- SSS --}}
<section class="faq-section">
    <div class="section-label">Sık Sorulan Sorular</div>
    <div class="section-title" style="text-align:center;">Aklınızdaki Sorular</div>
    <div class="faq-list">
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Tedarikçi olmak ücretli mi? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-a">Hayır. Platforma tedarikçi olarak katılmak tamamen ücretsizdir. Başvuru, ürün yükleme veya yayınlama için herhangi bir ücret talep edilmez.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">B2B ve B2C fiyatları nasıl belirlenir? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-a">Fiyatları siz belirlersiniz. Acentelere göstermek istediğiniz B2B net fiyatı ve tüketicilere göstermek istediğiniz B2C perakende fiyatını ayrı ayrı girebilirsiniz. Platform bu fiyatlara herhangi bir komisyon eklemez.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Ürünlerimi kendim mi ekleyeceğim? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-a">Hayır, ürünlerinizi ekibimiz sisteme girer. Siz sadece görsel, açıklama ve fiyat bilgilerini iletirsiniz. Bu sayede her ürün standart kalitede ve SEO uyumlu şekilde yayına alınır.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Kaç ürün ekleyebilirim? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-a">Sunduğunuz hizmet sayısı kadar ürün ekleyebilirsiniz. Her ürün ayrı bir katalog kartı olarak görünür.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">gruprezervasyonlari.com'da yayına alınmak zorunlu mu? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-a">Hayır. Sadece B2B kanalında (acentelere) görünmek de tercih edebilirsiniz. B2C yayını superadmin onayıyla yapılır ve her ürün için ayrı ayrı kontrol edilebilir.</div>
        </div>
        <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Talepler nasıl iletilir? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-a">Ürününüze yönelik her talep ve rezervasyon, WhatsApp bildirimi ve panel üzerinden size iletilir. Cevap süreniz ve kabul/ret kararı tamamen size aittir.</div>
        </div>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="final-cta">
    <h2>Tedarikçi Olmaya Hazır mısınız?</h2>
    <p>500+ acenteye ve binlerce tüketiciye hizmetlerinizi sunmaya bugün başlayın. Ücretsiz.</p>
    <div class="final-cta-btns">
        <a href="https://wa.me/905354154799?text=Merhaba%2C%20GrupTalepleri%27nde%20tedarik%C3%A7i%20olmak%20istiyorum." class="btn-wa" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp me-2"></i>WhatsApp ile Başvur
        </a>
        <a href="{{ route('register') }}" class="btn-red-lg">
            <i class="fas fa-user-plus me-2"></i>Ücretsiz Kayıt Ol
        </a>
    </div>
    <div class="contact-bar">
        <div class="contact-item"><i class="fas fa-envelope me-1"></i> <a href="mailto:destek@gruptalepleri.com">destek@gruptalepleri.com</a></div>
        <div class="contact-item"><i class="fas fa-phone me-1"></i> <a href="tel:+905354154799">+90 535 415 47 99</a></div>
        <div class="contact-item"><i class="fab fa-whatsapp me-1"></i> <a href="https://wa.me/905354154799" target="_blank" rel="noopener">WhatsApp</a></div>
    </div>
</section>

<footer>
    <p style="margin-bottom:.5rem;"><a href="/">GrupTalepleri.com</a> &nbsp;·&nbsp; <a href="/gizlilik">Gizlilik Politikası</a> &nbsp;·&nbsp; <a href="/kvkk">KVKK</a></p>
    <p>&copy; {{ date('Y') }} Grup Talepleri Turizm San. ve Tic. Ltd. Şti. — TÜRSAB A Grubu No: 12572</p>
</footer>

<script>
function toggleFaq(el) {
    var item = el.parentElement;
    item.classList.toggle('open');
}
</script>
</body>
</html>
