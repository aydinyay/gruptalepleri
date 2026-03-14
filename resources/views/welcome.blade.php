<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GrupTalepleri — Grup Uçuş Talep Platformu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
    --navy:#1a1a2e;
    --navy2:#16213e;
    --red:#e94560;
    --red2:#c73652;
    --gold:#f5a623;
    --light:#f0f2f5;
    --white:#ffffff;
    --gray:#6c757d;
}
body{font-family:'Barlow',sans-serif;background:var(--white);color:var(--navy);overflow-x:hidden;}
a{text-decoration:none;color:inherit;}

/* NAV */
nav{
    background:var(--navy);
    padding:0 5%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    height:64px;
    position:sticky;top:0;z-index:100;
    box-shadow:0 2px 20px rgba(0,0,0,0.3);
}
.logo{font-family:'Barlow Condensed',sans-serif;font-size:1.6rem;font-weight:800;color:var(--red);letter-spacing:1px;}
.logo span{color:var(--white);}
.nav-links{display:flex;gap:1.5rem;align-items:center;}
.nav-links a.nav-item{color:rgba(255,255,255,0.75);font-size:0.9rem;font-weight:500;transition:color 0.2s;}
.nav-links a.nav-item:hover{color:var(--white);}
@media(max-width:768px){.nav-links a.nav-item{display:none;}}
.btn-nav-login{
    background:transparent;
    border:1.5px solid rgba(255,255,255,0.4);
    color:var(--white);
    padding:7px 20px;border-radius:6px;
    font-size:0.85rem;font-weight:600;
    transition:all 0.2s;
}
.btn-nav-login:hover{border-color:var(--red);color:var(--red);}
.btn-nav-register{
    background:var(--red);
    color:var(--white);
    padding:7px 20px;border-radius:6px;
    font-size:0.85rem;font-weight:600;
    transition:all 0.2s;
}
.btn-nav-register:hover{background:var(--red2);}

/* HERO */
.hero{
    background:linear-gradient(135deg,var(--navy) 0%,var(--navy2) 60%,#0f3460 100%);
    min-height:88vh;
    display:flex;
    align-items:center;
    padding:5rem 5%;
    position:relative;
    overflow:hidden;
}
.hero::before{
    content:'';
    position:absolute;
    top:-100px;right:-100px;
    width:600px;height:600px;
    background:radial-gradient(circle,rgba(233,69,96,0.12) 0%,transparent 70%);
    pointer-events:none;
}
.hero-content{max-width:620px;position:relative;z-index:2;}
.hero-badge{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(233,69,96,0.15);
    border:1px solid rgba(233,69,96,0.35);
    color:var(--red);
    padding:6px 16px;border-radius:50px;
    font-size:0.8rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;
    margin-bottom:1.5rem;
}
.pulse{width:6px;height:6px;background:var(--red);border-radius:50%;animation:pulse 1.5s infinite;display:inline-block;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.5;transform:scale(1.3);}}
.hero h1{
    font-family:'Barlow Condensed',sans-serif;
    font-size:clamp(2.8rem,5vw,4.2rem);
    font-weight:800;
    color:var(--white);
    line-height:1.05;
    margin-bottom:1.2rem;
    letter-spacing:-0.5px;
}
.hero h1 span{color:var(--red);}
.hero p{
    color:rgba(255,255,255,0.7);
    font-size:1.1rem;
    line-height:1.7;
    margin-bottom:2rem;
    max-width:520px;
}
.btn-primary-custom{
    background:var(--red);color:var(--white);
    padding:14px 32px;border-radius:8px;
    font-size:0.95rem;font-weight:600;
    border:none;cursor:pointer;
    transition:all 0.2s;display:inline-flex;align-items:center;gap:8px;
}
.btn-primary-custom:hover{background:var(--red2);transform:translateY(-1px);color:var(--white);}
.btn-outline-custom{
    background:transparent;color:var(--white);
    padding:14px 32px;border-radius:8px;
    font-size:0.95rem;font-weight:600;
    border:1.5px solid rgba(255,255,255,0.35);
    cursor:pointer;transition:all 0.2s;
    display:inline-flex;align-items:center;gap:8px;
}
.btn-outline-custom:hover{border-color:var(--white);background:rgba(255,255,255,0.07);color:var(--white);}
.hero-stats{
    display:flex;gap:2rem;margin-top:3rem;
    padding-top:2rem;
    border-top:1px solid rgba(255,255,255,0.1);
    flex-wrap:wrap;
}
.stat-num{font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:800;color:var(--white);}
.stat-num span{color:var(--red);}
.stat-label{font-size:0.78rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px;margin-top:2px;}

/* Hero Visual Cards */
.hero-visual{
    position:absolute;right:5%;top:50%;transform:translateY(-50%);
    width:380px;
    display:grid;grid-template-columns:1fr 1fr;gap:12px;
    z-index:2;
}
@media(max-width:1024px){.hero-visual{display:none;}}
.mini-card{
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px;padding:16px;
}
.mini-card.accent{border-color:rgba(233,69,96,0.4);background:rgba(233,69,96,0.08);}
.mini-card.wide{grid-column:1/3;}
.mini-label{font-size:0.68rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;}
.mini-value{font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:700;color:var(--white);}
.mini-sub{font-size:0.72rem;color:rgba(255,255,255,0.5);margin-top:4px;}
.mini-iata{font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:700;color:var(--white);}
.mini-arrow{color:var(--red);font-size:0.9rem;margin:0 4px;}
.status-dot{display:flex;align-items:center;gap:5px;font-size:0.7rem;color:rgba(255,255,255,0.5);margin-top:8px;}
.dot{width:6px;height:6px;border-radius:50%;display:inline-block;}
.dot-green{background:#28a745;}
.dot-yellow{background:var(--gold);}
.dot-red{background:var(--red);}
.progress-bar-mini{height:3px;background:rgba(255,255,255,0.1);border-radius:2px;margin-top:8px;}
.progress-fill-mini{height:100%;background:var(--red);border-radius:2px;width:65%;}

/* NASIL ÇALIŞIR */
.how-section{background:var(--light);padding:5rem 5%;}
.section-label{font-size:0.75rem;font-weight:700;color:var(--red);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.8rem;}
.section-title{font-family:'Barlow Condensed',sans-serif;font-size:clamp(1.8rem,3vw,2.6rem);font-weight:800;color:var(--navy);line-height:1.1;margin-bottom:0.8rem;}
.section-sub{color:var(--gray);font-size:1rem;line-height:1.7;max-width:540px;}
.steps-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;margin-top:3rem;}
.step-card{position:relative;}
.step-num{
    width:48px;height:48px;
    background:var(--navy);color:var(--white);
    font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:800;
    border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:1rem;
}
.step-card:not(:last-child)::after{
    content:'';
    position:absolute;
    top:24px;left:60px;right:-20px;height:2px;
    background:repeating-linear-gradient(90deg,rgba(233,69,96,0.4) 0,rgba(233,69,96,0.4) 6px,transparent 6px,transparent 12px);
}
@media(max-width:768px){.step-card::after{display:none;}}
.step-card h3{font-size:1rem;font-weight:600;color:var(--navy);margin-bottom:0.4rem;}
.step-card p{font-size:0.875rem;color:var(--gray);line-height:1.6;}

/* NEDEN BİZ */
.why-section{background:var(--navy);padding:5rem 5%;}
.why-section .section-title{color:var(--white);}
.why-section .section-sub{color:rgba(255,255,255,0.6);}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;margin-top:3rem;}
.feature-card{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:14px;padding:1.5rem;
    transition:all 0.25s;
}
.feature-card:hover{background:rgba(233,69,96,0.08);border-color:rgba(233,69,96,0.3);transform:translateY(-3px);}
.feature-icon{
    width:44px;height:44px;
    background:rgba(233,69,96,0.15);
    border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:1rem;font-size:1.1rem;color:var(--red);
}
.feature-card h3{font-size:1rem;font-weight:600;color:var(--white);margin-bottom:0.5rem;}
.feature-card p{font-size:0.85rem;color:rgba(255,255,255,0.55);line-height:1.6;}

/* KİMLER İÇİN */
.audience-section{background:var(--white);padding:5rem 5%;}
.audience-grid{display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:3rem;}
@media(max-width:768px){.audience-grid{grid-template-columns:1fr;}}
.audience-card{border-radius:16px;padding:2rem;border:1.5px solid transparent;transition:all 0.25s;}
.audience-card.acente{background:#f0f4ff;border-color:#dde6ff;}
.audience-card.kurumsal{background:#fff5f0;border-color:#ffe0d5;}
.audience-card:hover{transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,0.08);}
.audience-type{font-size:0.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:1rem;}
.audience-card.acente .audience-type{color:#3b5bdb;}
.audience-card.kurumsal .audience-type{color:var(--red2);}
.audience-card h3{font-family:'Barlow Condensed',sans-serif;font-size:1.5rem;font-weight:800;color:var(--navy);margin-bottom:0.8rem;}
.audience-card p{font-size:0.9rem;color:var(--gray);line-height:1.65;margin-bottom:1.2rem;}
.audience-list{list-style:none;display:flex;flex-direction:column;gap:0.5rem;padding:0;}
.audience-list li{font-size:0.875rem;color:var(--navy);display:flex;align-items:center;gap:8px;}
.check-icon{color:var(--red);font-weight:700;}
.btn-acente{
    background:var(--navy);color:var(--white);
    padding:11px 24px;border-radius:8px;
    font-size:0.875rem;font-weight:600;
    border:none;cursor:pointer;margin-top:1.5rem;
    display:inline-block;transition:all 0.2s;
}
.btn-acente:hover{background:var(--red);color:var(--white);}
.btn-kurumsal{
    background:var(--red);color:var(--white);
    padding:11px 24px;border-radius:8px;
    font-size:0.875rem;font-weight:600;
    border:none;cursor:pointer;margin-top:1.5rem;
    display:inline-block;transition:all 0.2s;
}
.btn-kurumsal:hover{background:var(--red2);color:var(--white);}

/* CTA BAND */
.cta-band{
    background:linear-gradient(135deg,var(--red) 0%,var(--red2) 100%);
    padding:4.5rem 5%;text-align:center;
}
.cta-band h2{font-family:'Barlow Condensed',sans-serif;font-size:clamp(1.8rem,3vw,2.8rem);font-weight:800;color:var(--white);margin-bottom:0.8rem;}
.cta-band p{color:rgba(255,255,255,0.85);font-size:1rem;margin-bottom:2rem;}
.btn-white{
    background:var(--white);color:var(--red);
    padding:14px 40px;border-radius:8px;
    font-weight:700;font-size:1rem;
    border:none;cursor:pointer;
    transition:all 0.2s;display:inline-block;
}
.btn-white:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.2);color:var(--red);}

/* FOOTER */
footer{
    background:var(--navy2);
    padding:2rem 5%;
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;
}
.footer-logo{font-family:'Barlow Condensed',sans-serif;font-size:1.3rem;font-weight:800;color:var(--red);}
.footer-logo span{color:var(--white);}
.footer-text{font-size:0.8rem;color:rgba(255,255,255,0.35);}
</style>
</head>
<body>

{{-- NAVİGASYON --}}
<nav>
    <a href="/" class="logo">✈ Grup<span>Talepleri</span></a>
    <div class="nav-links">
        <a href="#nasil" class="nav-item">Nasıl Çalışır</a>
        <a href="#neden" class="nav-item">Neden Biz</a>
        <a href="#kimler" class="nav-item">Kimler İçin</a>
        <a href="{{ route('login') }}" class="btn-nav-login">Giriş Yap</a>
        <a href="{{ route('register') }}" class="btn-nav-register">Üye Ol</a>
    </div>
</nav>

{{-- HERO --}}
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <span class="pulse"></span>
            B2B Grup Uçuş Platformu
        </div>
        <h1>Grup Uçuş Taleplerini<br><span>Profesyonelce</span> Yönetin</h1>
        <p>Turizm acenteleri ve kurumsal şirketler için tasarlandı. Grup charter, tarifeli ve özel uçuş taleplerinizi tek platformda takip edin, anlık teklif alın.</p>
        <div class="d-flex gap-3 flex-wrap">
            <a href="{{ route('register') }}" class="btn-primary-custom">
                <i class="fas fa-play" style="font-size:0.75rem;"></i> Hemen Başlayın
            </a>
            <a href="#nasil" class="btn-outline-custom">
                Nasıl Çalışır?
            </a>
        </div>
        <div class="hero-stats">
            <div>
                <div class="stat-num">1.200<span>+</span></div>
                <div class="stat-label">İşlenen Talep</div>
            </div>
            <div>
                <div class="stat-num">48<span>s</span></div>
                <div class="stat-label">Ort. Teklif Süresi</div>
            </div>
            <div>
                <div class="stat-num">%97</div>
                <div class="stat-label">Müşteri Memnuniyeti</div>
            </div>
        </div>
    </div>

    {{-- Sağ taraf dekoratif kartlar --}}
    <div class="hero-visual">
        <div class="mini-card wide">
            <div class="mini-label">Aktif Talep</div>
            <div class="d-flex align-items-center">
                <div class="mini-iata">SAW</div>
                <div class="mini-arrow">→</div>
                <div class="mini-iata">ASR</div>
            </div>
            <div class="mini-sub">26 Pax · 11 Nisan 2026</div>
            <div class="progress-bar-mini"><div class="progress-fill-mini"></div></div>
            <div class="status-dot"><span class="dot dot-yellow"></span> Teklif Bekleniyor</div>
        </div>
        <div class="mini-card accent">
            <div class="mini-label">Yeni Teklif</div>
            <div class="mini-value">4.234 TL</div>
            <div class="mini-sub">Kişi başı · AJET</div>
            <div class="status-dot"><span class="dot dot-green"></span> Onay bekliyor</div>
        </div>
        <div class="mini-card">
            <div class="mini-label">Opsiyon</div>
            <div class="mini-value" style="color:var(--gold);">19g 17s</div>
            <div class="mini-sub">Kaldı</div>
            <div class="status-dot"><span class="dot dot-green"></span> Aktif</div>
        </div>
        <div class="mini-card wide">
            <div class="mini-label">IST → LHR · TK</div>
            <div class="d-flex align-items-center">
                <div class="mini-iata">IST</div>
                <div class="mini-arrow">→</div>
                <div class="mini-iata">LHR</div>
            </div>
            <div class="mini-sub">42 Pax · Biletlendi</div>
            <div class="status-dot"><span class="dot dot-green"></span> Tamamlandı</div>
        </div>
    </div>
</section>

{{-- NASIL ÇALIŞIR --}}
<section class="how-section" id="nasil">
    <div class="section-label">Nasıl Çalışır</div>
    <div class="section-title">4 Adımda Grup Uçuşunuz Hazır</div>
    <div class="section-sub">Karmaşık operasyonları basitleştiriyoruz. Siz talebi oluşturun, gerisini biz halledelim.</div>
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-num">1</div>
            <h3>Üye Olun</h3>
            <p>Acente veya kurumsal hesabınızı dakikalar içinde oluşturun. Hemen aktif olun.</p>
        </div>
        <div class="step-card">
            <div class="step-num">2</div>
            <h3>Talep Oluşturun</h3>
            <p>Rota, tarih, yolcu sayısı ve tercihlerinizi belirtin. Sistem GTPNR numaranızı otomatik atar.</p>
        </div>
        <div class="step-card">
            <div class="step-num">3</div>
            <h3>Teklif Alın</h3>
            <p>Operasyon ekibimiz en kısa sürede en uygun havayolu tekliflerini sisteme yükler.</p>
        </div>
        <div class="step-card">
            <div class="step-num">4</div>
            <h3>Onaylayın</h3>
            <p>Teklifi WhatsApp veya sistem üzerinden kabul edin. Biletleme sürecini canlı takip edin.</p>
        </div>
    </div>
</section>

{{-- NEDEN BİZ --}}
<section class="why-section" id="neden">
    <div class="section-label">Neden GrupTalepleri</div>
    <div class="section-title">Rakiplerden Farkımız</div>
    <div class="section-sub">Yalnızca bir rezervasyon aracı değil — uçtan uca operasyon yönetim platformu.</div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-plane"></i></div>
            <h3>Tüm Uçuş Tipleri</h3>
            <p>Charter, tarifeli grup, özel jet, dinner cruise ve yat taleplerinizi tek panelde yönetin.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-hourglass-half"></i></div>
            <h3>Canlı Opsiyon Sayacı</h3>
            <p>Teklif sürenizi saniye saniye takip edin. Kritik eşiklerde otomatik renk uyarısı alın.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-robot"></i></div>
            <h3>AI Operasyon Analizi</h3>
            <p>Yapay zeka ile havalimanı istihbaratı, transfer önerisi ve finansal değerlendirme alın.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-map-marked-alt"></i></div>
            <h3>Rota Haritası</h3>
            <p>Her talebinizde interaktif Google Maps rota görselleştirmesi otomatik oluşturulur.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fab fa-whatsapp"></i></div>
            <h3>WhatsApp Entegrasyonu</h3>
            <p>Tek tıkla operasyon ekibiyle bağlanın. Teklif kabulünü WhatsApp üzerinden yapın.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <h3>Ödeme Takibi</h3>
            <p>Depozito ve bakiye ödemelerinizi tarihleri, tutarları ve durumlarıyla şeffaf izleyin.</p>
        </div>
    </div>
</section>

{{-- KİMLER İÇİN --}}
<section class="audience-section" id="kimler">
    <div class="text-center">
        <div class="section-label">Kimler İçin</div>
        <div class="section-title mx-auto">İki Farklı Profil, Tek Platform</div>
        <div class="section-sub mx-auto text-center">Her iki tarafın da tam ihtiyacını karşılayacak şekilde tasarlandı.</div>
    </div>
    <div class="audience-grid">
        <div class="audience-card acente">
            <div class="audience-type">Turizm Acenteleri</div>
            <h3>Acentenizi Dijitale Taşıyın</h3>
            <p>Grup uçuş taleplerini kağıtsız, hızlı ve şeffaf yönetin. Müşterilerinize profesyonel deneyim sunun.</p>
            <ul class="audience-list">
                <li><span class="check-icon">✓</span> Tüm talepler tek ekranda</li>
                <li><span class="check-icon">✓</span> Anlık teklif bildirimleri</li>
                <li><span class="check-icon">✓</span> Opsiyon ve ödeme takibi</li>
                <li><span class="check-icon">✓</span> TURSAB lisans entegrasyonu</li>
            </ul>
            <a href="{{ route('register') }}" class="btn-acente">Acente Olarak Kayıt Ol</a>
        </div>
        <div class="audience-card kurumsal">
            <div class="audience-type">Kurumsal Müşteriler</div>
            <h3>Şirket Uçuşlarını Kolaylaştırın</h3>
            <p>Çalışan transferleri, incentive turlar ve kurumsal grup seyahatlerinizi profesyonelce organize edin.</p>
            <ul class="audience-list">
                <li><span class="check-icon">✓</span> Şeffaf fiyatlandırma</li>
                <li><span class="check-icon">✓</span> Dijital onay süreci</li>
                <li><span class="check-icon">✓</span> Raporlama ve takip</li>
                <li><span class="check-icon">✓</span> Özel operasyon desteği</li>
            </ul>
            <a href="{{ route('register') }}" class="btn-kurumsal">Kurumsal Talep Gönderin</a>
        </div>
    </div>
</section>

{{-- CTA BAND --}}
<div class="cta-band">
    <h2>Hemen Üye Olun, İlk Talebinizi Oluşturun</h2>
    <p>Ücretsiz kayıt · Kurulum gerektirmez · 5 dakikada aktif olun</p>
    <a href="{{ route('register') }}" class="btn-white">Ücretsiz Başlayın →</a>
</div>

{{-- FOOTER --}}
<footer>
    <div class="footer-logo">✈ Grup<span>Talepleri</span></div>
    <div class="footer-text">© {{ date('Y') }} GrupTalepleri · Tüm hakları saklıdır</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({behavior:'smooth'}); }
    });
});
</script>
</body>
</html>
