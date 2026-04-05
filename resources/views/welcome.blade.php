<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- SEO --}}
<title>GrupTalepleri — Grup Uçuş ve Charter Talep Platformu | TÜRSAB A Grubu</title>
<meta name="description" content="Turizm acenteleri ve kurumsal şirketler için grup uçuş talep platformu. Charter, tarifeli grup ve özel uçuş tekliflerinizi hızlıca alın. TÜRSAB A Grubu belgeli. Şişli, İstanbul.">
<meta name="keywords" content="grup uçuş, charter uçuş, grup bilet, grup charter, grup seyahat, turizm acentesi, TÜRSAB, grup talep, toplu bilet, grup rezervasyon, İstanbul, uçuş talebi">
<meta name="author" content="Grup Talepleri Turizm San. ve Tic. Ltd. Şti.">
<meta name="robots" content="index, follow">
<meta name="google-site-verification" content="oJBcfHCB3GVznFvX2GiILkM76tc4pm0z2lQibnY0Hws">
<link rel="canonical" href="https://gruptalepleri.com">

{{-- Open Graph (Facebook, WhatsApp, LinkedIn paylaşımlarında görünen önizleme) --}}
<meta property="og:type" content="website">
<meta property="og:url" content="https://gruptalepleri.com">
<meta property="og:title" content="GrupTalepleri — Grup Uçuş ve Charter Talep Platformu">
<meta property="og:description" content="Turizm acenteleri için grup uçuş talep platformu. Charter ve tarifeli grup uçuş tekliflerinizi hızlıca alın. TÜRSAB A Grubu belgeli.">
<meta property="og:image" content="https://gruptalepleri.com/og-image.png">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="GrupTalepleri">

{{-- Twitter / X Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="GrupTalepleri — Grup Uçuş ve Charter Talep Platformu">
<meta name="twitter:description" content="Turizm acenteleri için grup uçuş talep platformu. Charter ve tarifeli grup uçuş tekliflerinizi hızlıca alın.">
<meta name="twitter:image" content="https://gruptalepleri.com/og-image.png">

{{-- Instagram profil bağlantısı --}}
<meta property="og:see_also" content="https://www.instagram.com/grup.talepleri">

{{-- JSON-LD 1: TravelAgency + WebSite --}}
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'        => 'TravelAgency',
            '@id'          => 'https://gruptalepleri.com/#organization',
            'name'         => $sirket['unvan'],
            'alternateName'=> 'GrupTalepleri',
            'url'          => 'https://gruptalepleri.com',
            'logo'         => ['@type'=>'ImageObject','url'=>'https://gruptalepleri.com/og-image.png'],
            'description'  => 'Turizm acenteleri ve kurumsal şirketler için grup uçuş, charter ve özel seyahat talep platformu. TÜRSAB A Grubu belgeli.',
            'address'      => ['@type'=>'PostalAddress','streetAddress'=>$sirket['adres'],'addressLocality'=>'Şişli','addressRegion'=>'İstanbul','addressCountry'=>'TR'],
            'geo'          => ['@type'=>'GeoCoordinates','latitude'=>'41.0532','longitude'=>'28.9868'],
            'telephone'    => preg_replace('/[^0-9+]/','',$sirket['telefon']),
            'email'        => $sirket['eposta'],
            'sameAs'       => ['https://www.instagram.com/'.$sirket['instagram']],
            'areaServed'   => ['@type'=>'Country','name'=>'Turkey'],
            'priceRange'   => '₺₺',
            'openingHoursSpecification' => [
                ['@type'=>'OpeningHoursSpecification','dayOfWeek'=>['Monday','Tuesday','Wednesday','Thursday','Friday'],'opens'=>'09:00','closes'=>'18:00'],
            ],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name'  => 'Grup Seyahat Hizmetleri',
                'itemListElement' => [
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Grup Uçuş Talebi','description'=>'Tarifeli gruplar için rekabetçi teklifler ve GTPNR takip sistemi.']],
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Charter Uçak Kiralama','description'=>'Tam kabin charter kiralama, paket tur grupları için özel çözümler.']],
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Özel Jet Kiralama','description'=>'VIP ve kurumsal seyahatler için özel jet temini ve rezervasyonu.']],
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Helikopter Kiralama','description'=>'VIP transfer, gezi ve iş seyahati için helikopter çözümleri.']],
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Havalimanı Transfer','description'=>'Havalimanı, otel ve etkinlik transferleri için VIP araç temini.']],
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Yat Kiralama','description'=>'Tekne kiralama, mavi yolculuk ve özel yat organizasyonları.']],
                    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Dinner Cruise','description'=>'Boğaz ve koy turları, özel akşam yemeği etkinlikleri.']],
                ],
            ],
        ],
        [
            '@type'           => 'WebSite',
            '@id'             => 'https://gruptalepleri.com/#website',
            'url'             => 'https://gruptalepleri.com',
            'name'            => 'GrupTalepleri',
            'description'     => 'Turizm acenteleri için grup uçuş ve charter talep platformu.',
            'publisher'       => ['@id'=>'https://gruptalepleri.com/#organization'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => ['@type'=>'EntryPoint','urlTemplate'=>'https://gruptalepleri.com/grup-talepleri?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>

{{-- JSON-LD 2: FAQPage --}}
<script type="application/ld+json">{!! json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => [
        ['@type'=>'Question','name'=>'Grup uçuş talebi nasıl yapılır?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Ücretsiz üye olup giriş yaptıktan sonra "Yeni Talep" butonuna tıklayın. Güzergah, tarih, yolcu sayısı ve tercihlerinizi girin. Sistem size otomatik bir GTPNR numarası atar ve operasyon ekibimiz en kısa sürede teklif girer.']],
        ['@type'=>'Question','name'=>'Minimum kaç kişiyle grup bileti alınabilir?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Tarifeli uçuşlarda genel kural olarak minimum 10 kişiden itibaren grup tarifesi uygulanır. Charter uçuşlarda ise uçak kapasitesine göre değişir; tek koltuktan tam kabin kiralamaya kadar her ölçekte çözüm sunulmaktadır.']],
        ['@type'=>'Question','name'=>'TÜRSAB belgeli acente misiniz?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Evet. Grup Talepleri Turizm San. ve Tic. Ltd. Şti. olarak TÜRSAB A Grubu seyahat acentası belgesine sahibiz. Belge No: '.$sirket['tursab_no'].'.']],
        ['@type'=>'Question','name'=>'Teklif ne kadar sürede gelir?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Tarifeli grup taleplerinde genellikle 2-4 saat, charter taleplerinde ise güzergah ve tarihe göre 4-24 saat içinde teklif iletilmektedir. Acil operasyonlar için telefon veya WhatsApp ile doğrudan iletişime geçebilirsiniz.']],
        ['@type'=>'Question','name'=>'Üyelik ücretli mi?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Hayır. GrupTalepleri platformuna üyelik tamamen ücretsizdir. Kayıt olduktan sonra hemen talep oluşturmaya başlayabilirsiniz.']],
        ['@type'=>'Question','name'=>'Yurt dışı grup uçuşları için de hizmet veriyor musunuz?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Evet. Yurt içi güzergahların yanı sıra Avrupa, Orta Doğu, Uzak Doğu ve tüm uluslararası destinasyonlara grup uçuş talebi oluşturabilirsiniz. Charter uçuşlarda dünya genelinde operasyon desteği sağlanmaktadır.']],
        ['@type'=>'Question','name'=>'Ödeme nasıl yapılır?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Teklif onaylandıktan sonra havale/EFT veya kredi kartı ile ödeme yapılabilmektedir. Büyük tutarlı operasyonlarda depozito + bakiye şeklinde taksitli ödeme planı oluşturulabilmektedir.']],
        ['@type'=>'Question','name'=>'Dinner Cruise için nasıl teklif alabilirim?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Platforma giriş yaparak Leisure menüsünden Dinner Cruise seçeneğini seçin. Tarih, oturum (akşam/gece), kişi sayısı ve menü tercihlerinizi girin. Net B2B fiyatlarla anlık teklif alırsınız.']],
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>

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

/* STATS BAND */
.stats-band{
    background:linear-gradient(135deg,#0f3460 0%,var(--navy2) 50%,var(--navy) 100%);
    padding:5rem 5%;
    position:relative;overflow:hidden;
}
.stats-band::before{
    content:'';position:absolute;top:-150px;left:50%;transform:translateX(-50%);
    width:800px;height:500px;
    background:radial-gradient(ellipse,rgba(233,69,96,0.1) 0%,transparent 70%);
    pointer-events:none;
}
.stats-band-title{
    text-align:center;margin-bottom:3.5rem;
}
.stats-band-title .section-label{color:var(--red);}
.stats-band-title .section-title{color:var(--white);margin-bottom:0.5rem;}
.stats-band-title p{color:rgba(255,255,255,0.55);font-size:0.95rem;}
.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:1.5rem;
    position:relative;z-index:1;
}
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.stats-grid{grid-template-columns:1fr 1fr;gap:1rem;}}
.stat-card{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:16px;
    padding:2rem 1.5rem;
    text-align:center;
    transition:all 0.3s;
    position:relative;overflow:hidden;
}
.stat-card:hover{
    background:rgba(233,69,96,0.1);
    border-color:rgba(233,69,96,0.35);
    transform:translateY(-4px);
}
.stat-card::after{
    content:'';
    position:absolute;bottom:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,transparent,var(--red),transparent);
    opacity:0;transition:opacity 0.3s;
}
.stat-card:hover::after{opacity:1;}
.stat-icon{
    width:52px;height:52px;
    background:rgba(233,69,96,0.15);
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 1.2rem;
    font-size:1.3rem;color:var(--red);
}
.stat-big{
    font-family:'Barlow Condensed',sans-serif;
    font-size:clamp(2.4rem,4vw,3.2rem);
    font-weight:800;
    color:var(--white);
    line-height:1;
    margin-bottom:0.4rem;
    letter-spacing:-1px;
}
.stat-big .stat-suffix{color:var(--red);font-size:0.7em;}
.stat-desc{font-size:0.9rem;color:rgba(255,255,255,0.7);font-weight:600;margin-bottom:0.3rem;}
.stat-sub{font-size:0.75rem;color:rgba(255,255,255,0.35);line-height:1.4;}
.stats-divider{
    display:flex;align-items:center;justify-content:center;gap:1.5rem;
    margin-top:3rem;padding-top:2.5rem;
    border-top:1px solid rgba(255,255,255,0.08);
    flex-wrap:wrap;gap:2rem;
}
.divider-stat{text-align:center;}
.divider-num{font-family:'Barlow Condensed',sans-serif;font-size:1.6rem;font-weight:800;color:var(--white);}
.divider-num span{color:var(--red);}
.divider-label{font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-top:2px;}
.divider-sep{width:1px;height:40px;background:rgba(255,255,255,0.1);}
@media(max-width:600px){.divider-sep{display:none;}.stats-divider{gap:1.5rem;}}

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
/* SSS */
.sss-section{background:var(--light);padding:5rem 5%;}
.sss-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:3rem;max-width:960px;margin-left:auto;margin-right:auto;}
@media(max-width:768px){.sss-grid{grid-template-columns:1fr;}}
.sss-item{background:#fff;border-radius:12px;border:1px solid #e9ecef;overflow:hidden;}
.sss-q{width:100%;background:none;border:none;text-align:left;padding:1.1rem 1.3rem;font-size:.95rem;font-weight:600;color:var(--navy);cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem;line-height:1.4;}
.sss-q i{flex-shrink:0;transition:transform .25s;color:var(--red);}
.sss-q[aria-expanded="true"] i{transform:rotate(45deg);}
.sss-a{display:none;padding:.1rem 1.3rem 1.1rem;font-size:.88rem;color:var(--gray);line-height:1.7;}
.sss-item.open .sss-a{display:block;}

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

/* HİZMETLER */
.hizmetler-section{background:var(--white);padding:5rem 5%;}
.hizmetler-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.5rem;margin-top:3rem;}
.hizmet-card{
    border:1.5px solid #e8ecf0;
    border-radius:14px;padding:1.8rem 1.2rem;
    text-align:center;
    transition:all 0.25s;
    color:inherit;
    display:block;
}
.hizmet-card:hover{
    border-color:var(--red);
    box-shadow:0 8px 32px rgba(233,69,96,0.12);
    transform:translateY(-4px);
    color:inherit;
}
.hizmet-icon{
    width:58px;height:58px;
    background:rgba(233,69,96,0.08);
    border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 1.1rem;
    font-size:1.4rem;color:var(--red);
    transition:all 0.25s;
}
.hizmet-card:hover .hizmet-icon{background:var(--red);color:var(--white);}
.hizmet-card h3{font-size:0.92rem;font-weight:700;color:var(--navy);margin-bottom:0.4rem;}
.hizmet-card p{font-size:0.78rem;color:var(--gray);line-height:1.5;margin-bottom:0.9rem;}
.hizmet-link{font-size:0.78rem;color:var(--red);font-weight:600;display:inline-flex;align-items:center;gap:4px;}
.hizmet-link i{font-size:0.65rem;transition:transform 0.2s;}
.hizmet-card:hover .hizmet-link i{transform:translateX(3px);}

/* FOOTER */
footer{background:var(--navy2);padding:3rem 5% 1.5rem;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:2.5rem;padding-bottom:2rem;border-bottom:1px solid rgba(255,255,255,0.08);}
@media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:1.5rem;}}
.footer-logo{font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:800;color:var(--red);}
.footer-logo span{color:var(--white);}
.footer-col-title{font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.4);margin-bottom:0.8rem;}
.footer-col p,.footer-col a{font-size:0.82rem;color:rgba(255,255,255,0.55);line-height:1.7;display:block;transition:color 0.2s;}
.footer-col a:hover{color:var(--red);}
.footer-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:5px 10px;font-size:0.75rem;color:rgba(255,255,255,0.6);margin-top:8px;}
.footer-badge strong{color:var(--white);}
.footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;padding-top:1.2rem;}
.footer-text{font-size:0.75rem;color:rgba(255,255,255,0.25);}
.footer-vergi{font-size:0.72rem;color:rgba(255,255,255,0.2);}
</style>
</head>
<body>

<x-ai-kutlama-widget />

{{-- NAVİGASYON --}}
<nav>
    <a href="/" class="logo">✈ Grup<span>Talepleri</span></a>
    <div class="nav-links">
        <a href="{{ route('marketing.grup-talepleri') }}" class="nav-item">Grup Talepleri</a>
        <a href="#hizmetler" class="nav-item">Hizmetler</a>
        <a href="#nasil" class="nav-item">Nasıl Çalışır</a>
        <a href="#neden" class="nav-item">Neden Biz</a>
        <a href="#kimler" class="nav-item">Kimler İçin</a>
        <a href="#sss" class="nav-item">SSS</a>
        <a href="/acente-tanitim.html" class="nav-item">Platform Tanıtımı</a>
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
                <div class="stat-num">{{ number_format($stats['toplam_grup'], 0, ',', '.') }}<span>+</span></div>
                <div class="stat-label">İşlenen Talep</div>
            </div>
            <div>
                <div class="stat-num">{{ number_format($stats['toplam_yolcu'], 0, ',', '.') }}<span>+</span></div>
                <div class="stat-label">Hizmet Verilen Yolcu</div>
            </div>
            <div>
                <div class="stat-num">{{ $stats['toplam_ulke'] }}<span>+</span></div>
                <div class="stat-label">Ülke</div>
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

{{-- STATS BAND --}}
<section class="stats-band">
    <div class="stats-band-title">
        <div class="section-label">Rakamlarla GrupTalepleri</div>
        <div class="section-title">Gerçek Operasyonlar, Gerçek Hacim</div>
        <p>Platformumuzdan geçen her talep, her yolcu, her destinasyon — canlı veritabanı verisi.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-big">
                <span class="counter" data-target="{{ $stats['toplam_grup'] }}">0</span><span class="stat-suffix">+</span>
            </div>
            <div class="stat-desc">Grup İşlemi Yapıldı</div>
            <div class="stat-sub">Platformdan geçen toplam<br>grup uçuş talebi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-big">
                <span class="counter" data-target="{{ $stats['toplam_yolcu'] }}">0</span><span class="stat-suffix">+</span>
            </div>
            <div class="stat-desc">Yolcuya Hizmet Verildi</div>
            <div class="stat-sub">Toplam işlenen<br>yolcu kapasitesi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-globe-europe"></i></div>
            <div class="stat-big">
                <span class="counter" data-target="{{ $stats['toplam_ulke'] }}">0</span><span class="stat-suffix">+</span>
            </div>
            <div class="stat-desc">Ülkeye Grup Gönderildi</div>
            <div class="stat-sub">5 kıtada aktif<br>destinasyon kapsamı</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stat-big">
                <span class="counter" data-target="{{ $stats['toplam_destinasyon'] }}">0</span><span class="stat-suffix">+</span>
            </div>
            <div class="stat-desc">Havalimanına Uçuş</div>
            <div class="stat-sub">Uçuş yapılan benzersiz<br>IATA destinasyonu</div>
        </div>
    </div>

    <div class="stats-divider">
        <div class="divider-stat">
            <div class="divider-num"><span class="counter" data-target="{{ $stats['toplam_ucus'] }}">0</span><span style="color:var(--red)">+</span></div>
            <div class="divider-label">Uçuş Gerçekleşti</div>
        </div>
        <div class="divider-sep"></div>
        <div class="divider-stat">
            <div class="divider-num"><span class="counter" data-target="24">0</span><span style="color:var(--red)">/7</span></div>
            <div class="divider-label">Operasyon Desteği</div>
        </div>
    </div>

    {{-- VERİTABANI KAPASİTESİ --}}
    <div style="margin-top:3rem;padding-top:2.5rem;border-top:1px solid rgba(255,255,255,0.08);">
        <div class="text-center mb-3">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.35);letter-spacing:2px;text-transform:uppercase;">Sistem Altyapısı</span>
        </div>
        <div class="stats-grid">
            <div class="stat-card" style="padding:1.2rem 1rem;">
                <div class="stat-icon" style="width:38px;height:38px;font-size:1rem;margin-bottom:0.8rem;"><i class="fas fa-plane-departure"></i></div>
                <div class="stat-big" style="font-size:2rem;">
                    <span class="counter" data-target="{{ $stats['airports'] }}">0</span><span class="stat-suffix">+</span>
                </div>
                <div class="stat-desc">Havalimanı</div>
                <div class="stat-sub">IATA kodlu, dünya geneli<br>arama ve doğrulama</div>
            </div>
            <div class="stat-card" style="padding:1.2rem 1rem;">
                <div class="stat-icon" style="width:38px;height:38px;font-size:1rem;margin-bottom:0.8rem;"><i class="fas fa-plane"></i></div>
                <div class="stat-big" style="font-size:2rem;">
                    <span class="counter" data-target="{{ $stats['airlines'] }}">0</span><span class="stat-suffix">+</span>
                </div>
                <div class="stat-desc">Havayolu</div>
                <div class="stat-sub">IATA / ICAO kodlu<br>global havayolu veritabanı</div>
            </div>
            <div class="stat-card" style="padding:1.2rem 1rem;">
                <div class="stat-icon" style="width:38px;height:38px;font-size:1rem;margin-bottom:0.8rem;"><i class="fas fa-globe"></i></div>
                <div class="stat-big" style="font-size:2rem;">
                    <span class="counter" data-target="{{ $stats['countries'] }}">0</span><span class="stat-suffix">+</span>
                </div>
                <div class="stat-desc">Ülke</div>
                <div class="stat-sub">Her kıtadan destinasyon<br>tek platformda</div>
            </div>
            <div class="stat-card" style="padding:1.2rem 1rem;">
                <div class="stat-icon" style="width:38px;height:38px;font-size:1rem;margin-bottom:0.8rem;"><i class="fas fa-building"></i></div>
                <div class="stat-big" style="font-size:2rem;">
                    <span class="counter" data-target="{{ $stats['large_airports'] }}">0</span><span class="stat-suffix">+</span>
                </div>
                <div class="stat-desc">Uluslararası Havalimanı</div>
                <div class="stat-sub">Büyük & uluslararası<br>kategorisinde</div>
            </div>
        </div>
    </div>
</section>

{{-- HİZMETLERİMİZ --}}
<section class="hizmetler-section" id="hizmetler">
    <div class="section-label">Hizmetlerimiz</div>
    <div class="section-title">Tek Platformda Tüm Seyahat Hizmetleri</div>
    <div class="section-sub">Grup uçuşundan özel jet'e, transferden yat kiralamaya — tüm operasyonel ihtiyaçlarınız için tek çatı altında profesyonel çözümler.</div>
    <div class="hizmetler-grid">
        <a href="{{ route('register') }}" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-plane-departure"></i></div>
            <h3>Grup Uçuş Talebi</h3>
            <p>Tarifeli gruplar için rekabetçi teklifler, GTPNR takip sistemi.</p>
            <span class="hizmet-link">Talep Oluştur <i class="fas fa-arrow-right"></i></span>
        </a>
        <a href="/charter-ucak" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-plane-circle-check"></i></div>
            <h3>Charter Uçak</h3>
            <p>Tam kabin charter kiralama, paket tur grupları için özel çözümler.</p>
            <span class="hizmet-link">Detaylı Bilgi <i class="fas fa-arrow-right"></i></span>
        </a>
        <a href="/private-jet-kiralama" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-jet-fighter"></i></div>
            <h3>Özel Jet Kiralama</h3>
            <p>VIP ve kurumsal seyahatler için özel jet temini ve rezervasyonu.</p>
            <span class="hizmet-link">Detaylı Bilgi <i class="fas fa-arrow-right"></i></span>
        </a>
        <a href="/helikopter-kiralama" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-helicopter"></i></div>
            <h3>Helikopter Kiralama</h3>
            <p>VIP transfer, gezi ve iş seyahati için helikopter çözümleri.</p>
            <span class="hizmet-link">Detaylı Bilgi <i class="fas fa-arrow-right"></i></span>
        </a>
        <a href="{{ route('register') }}" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-car-side"></i></div>
            <h3>Transfer Hizmetleri</h3>
            <p>Havalimanı, otel ve etkinlik transferleri için VIP araç temini.</p>
            <span class="hizmet-link">Talep Oluştur <i class="fas fa-arrow-right"></i></span>
        </a>
        <a href="{{ route('register') }}" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-ship"></i></div>
            <h3>Yat Kiralama</h3>
            <p>Tekne kiralama, mavi yolculuk ve özel yat organizasyonları.</p>
            <span class="hizmet-link">Talep Oluştur <i class="fas fa-arrow-right"></i></span>
        </a>
        <a href="{{ route('register') }}" class="hizmet-card">
            <div class="hizmet-icon"><i class="fas fa-anchor"></i></div>
            <h3>Dinner Cruise</h3>
            <p>Boğaz ve koy turları, özel akşam yemeği etkinlikleri.</p>
            <span class="hizmet-link">Talep Oluştur <i class="fas fa-arrow-right"></i></span>
        </a>
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

{{-- SSS --}}
<section class="sss-section" id="sss">
    <div class="section-label" style="text-align:center;">SSS</div>
    <div class="section-title" style="text-align:center;">Sık Sorulan Sorular</div>
    <div class="section-sub" style="text-align:center;margin:0 auto;">Platformumuz hakkında merak ettikleriniz.</div>

    <div class="sss-grid">
        @php $sssler = [
            ['s'=>'Grup uçuş talebi nasıl yapılır?', 'c'=>'Ücretsiz üye olup giriş yaptıktan sonra "Yeni Talep" butonuna tıklayın. Güzergah, tarih, yolcu sayısı ve tercihlerinizi girin. Sistem size otomatik bir GTPNR numarası atar ve operasyon ekibimiz en kısa sürede teklif girer.'],
            ['s'=>'Minimum kaç kişiyle grup bileti alınabilir?', 'c'=>'Tarifeli uçuşlarda genel kural olarak minimum 10 kişiden itibaren grup tarifesi uygulanır. Charter uçuşlarda ise uçak kapasitesine göre değişir; tek koltuktan tam kabin kiralamaya kadar her ölçekte çözüm sunulmaktadır.'],
            ['s'=>'TÜRSAB belgeli acente misiniz?', 'c'=>'Evet. TÜRSAB A Grubu seyahat acentası belgesine sahibiz. Belge No: '.$sirket['tursab_no'].'. Tüm operasyonlarımız yasal güvence altındadır.'],
            ['s'=>'Teklif ne kadar sürede gelir?', 'c'=>'Tarifeli grup taleplerinde genellikle 2-4 saat, charter taleplerinde ise güzergah ve tarihe göre 4-24 saat içinde teklif iletilmektedir. Acil operasyonlar için doğrudan WhatsApp veya telefon ile iletişime geçebilirsiniz.'],
            ['s'=>'Üyelik ücretli mi?', 'c'=>'Hayır. Platforma üyelik tamamen ücretsizdir. Kayıt olduktan sonra hemen talep oluşturmaya başlayabilirsiniz.'],
            ['s'=>'Yurt dışı grup uçuşları için de hizmet veriyor musunuz?', 'c'=>'Evet. Yurt içinin yanı sıra Avrupa, Orta Doğu, Uzak Doğu ve tüm uluslararası destinasyonlara grup uçuş talebi oluşturabilirsiniz. Charter operasyonlarda dünya genelinde hizmet verilmektedir.'],
            ['s'=>'Ödeme nasıl yapılır?', 'c'=>'Teklif onaylandıktan sonra havale/EFT veya kredi kartı ile ödeme yapılabilmektedir. Büyük tutarlı operasyonlarda depozito + bakiye şeklinde taksitli ödeme planı oluşturulabilmektedir.'],
            ['s'=>'Dinner Cruise için nasıl teklif alabilirim?', 'c'=>'Platforma giriş yaparak Leisure menüsünden Dinner Cruise seçeneğini seçin. Tarih, oturum, kişi sayısı ve menü tercihlerinizi girin. Net B2B fiyatlarla anlık teklif alırsınız.'],
        ]; @endphp

        @foreach($sssler as $i => $sss)
        <div class="sss-item{{ $i === 0 ? ' open' : '' }}">
            <button class="sss-q" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" onclick="sssToggle(this)">
                {{ $sss['s'] }}
                <i class="fas fa-plus"></i>
            </button>
            <div class="sss-a">{{ $sss['c'] }}</div>
        </div>
        @endforeach
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
    <div class="footer-grid">

        {{-- Sol: Şirket tanıtımı --}}
        <div class="footer-col">
            <div class="footer-logo mb-2">✈ Grup<span>Talepleri</span></div>
            <p style="margin-bottom:0.5rem;">Grup charter, tarifeli ve özel uçuş taleplerinizi tek platformda yönetin. Anlık teklif alın, operasyonunuzu hızlandırın.</p>
            <div class="footer-badge">
                <i class="fas fa-certificate" style="color:#f5a623;"></i>
                TÜRSAB {{ $sirket['tursab_grup'] }} Grubu &nbsp;·&nbsp; Belge No: <strong>{{ $sirket['tursab_no'] }}</strong>
            </div>

            {{-- E-posta aboneliği --}}
            <div style="margin-top:1.25rem;">
                <div style="font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin-bottom:.6rem;">
                    Duyurulardan haberdar olun
                </div>
                @if(session('abone_mesaj'))
                    <div style="font-size:.84rem;padding:.5rem .75rem;border-radius:.5rem;margin-bottom:.5rem;
                        background:{{ session('abone_durum') === 'ok' ? 'rgba(34,197,94,.15)' : 'rgba(251,191,36,.15)' }};
                        color:{{ session('abone_durum') === 'ok' ? '#86efac' : '#fde68a' }};">
                        {{ session('abone_mesaj') }}
                    </div>
                @endif
                <form action="{{ route('abone.store') }}" method="POST"
                      style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    @csrf
                    <input type="email" name="email" placeholder="E-posta adresiniz"
                           required
                           style="flex:1;min-width:0;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:#fff;font-size:.84rem;outline:none;">
                    <button type="submit"
                            style="padding:.5rem 1rem;border-radius:.5rem;border:none;background:#e8a020;color:#fff;font-size:.84rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                        Abone Ol
                    </button>
                </form>
            </div>
        </div>

        {{-- Orta: İletişim --}}
        <div class="footer-col">
            <div class="footer-col-title">İletişim</div>
            <p><i class="fas fa-map-marker-alt me-2" style="color:var(--red);width:14px;"></i>{{ $sirket['adres'] }}</p>
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sirket['telefon']) }}"><i class="fas fa-phone me-2" style="color:var(--red);width:14px;"></i>{{ $sirket['telefon'] }}</a>
            @if($sirket['cep'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $sirket['cep']) }}"><i class="fas fa-mobile-alt me-2" style="color:var(--red);width:14px;"></i>{{ $sirket['cep'] }}</a>@endif
            <a href="mailto:{{ $sirket['eposta'] }}" style="unicode-bidi:plaintext;"><i class="fas fa-envelope me-2" style="color:var(--red);width:14px;"></i>{{ $sirket['eposta'] }}</a>
            @php
                $sosyalMedya = [
                    ['url' => $sirket['instagram'] ? 'https://www.instagram.com/'.$sirket['instagram'] : '', 'icon' => 'fab fa-instagram', 'color' => '#e1306c', 'label' => '@'.$sirket['instagram']],
                    ['url' => $sirket['facebook'],  'icon' => 'fab fa-facebook',  'color' => '#1877f2', 'label' => 'Facebook'],
                    ['url' => $sirket['twitter']  ? 'https://x.com/'.$sirket['twitter'] : '', 'icon' => 'fab fa-x-twitter', 'color' => '#fff', 'label' => '@'.$sirket['twitter']],
                    ['url' => $sirket['linkedin'],  'icon' => 'fab fa-linkedin',  'color' => '#0a66c2', 'label' => 'LinkedIn'],
                ];
            @endphp
            <div style="margin-top:0.8rem;display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($sosyalMedya as $sm)
                    @if($sm['url'])
                    <a href="{{ $sm['url'] }}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:5px 10px;font-size:0.78rem;color:rgba(255,255,255,0.6);transition:all 0.2s;"
                       onmouseover="this.style.borderColor='rgba(233,69,96,0.5)';this.style.color='#fff';"
                       onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';this.style.color='rgba(255,255,255,0.6)';">
                        <i class="{{ $sm['icon'] }}" style="color:{{ $sm['color'] }};"></i> {{ $sm['label'] }}
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Sağ: Yasal / Fatura --}}
        <div class="footer-col">
            <div class="footer-col-title">Şirket Bilgileri</div>
            <p style="font-weight:600;color:rgba(255,255,255,0.75);">{{ $sirket['unvan'] }}</p>
            <p>Vergi Dairesi: {{ $sirket['vergi_dairesi'] }}</p>
            <p>Vergi No: {{ $sirket['vkn'] }}</p>
            @if($sirket['mersis_no'])<p>Mersis No: {{ $sirket['mersis_no'] }}</p>@endif
        </div>

    </div>

    <div class="footer-bottom">
        <div class="footer-text">© {{ date('Y') }} GrupTalepleri &nbsp;·&nbsp; Tüm hakları saklıdır &nbsp;·&nbsp; {{ $sirket['unvan'] }}</div>
        <div class="footer-vergi">TÜRSAB {{ $sirket['tursab_grup'] }} Grubu Belge No: {{ $sirket['tursab_no'] }} &nbsp;·&nbsp; Vergi No: {{ $sirket['vkn'] }}</div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// SSS accordion
function sssToggle(btn) {
    const item = btn.closest('.sss-item');
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.sss-item.open').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.sss-q').setAttribute('aria-expanded','false');
    });
    if (!isOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded','true');
    }
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({behavior:'smooth'}); }
    });
});

// Counter animasyonu — sayı sıfırdan hedef değere animasyonla çıkar
function animateCounter(el) {
    const target   = parseInt(el.dataset.target, 10);
    const duration = 1800; // ms
    const start    = performance.now();

    function step(now) {
        const elapsed  = now - start;
        const progress = Math.min(elapsed / duration, 1);
        // ease-out cubic
        const eased    = 1 - Math.pow(1 - progress, 3);
        const current  = Math.round(eased * target);
        el.textContent = current.toLocaleString('tr-TR');
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

// IntersectionObserver ile görünür olunca başlat
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.dataset.animated) {
            entry.target.dataset.animated = '1';
            animateCounter(entry.target);
        }
    });
}, { threshold: 0.3 });

document.querySelectorAll('.counter').forEach(el => counterObserver.observe(el));
</script>
</body>
</html>
