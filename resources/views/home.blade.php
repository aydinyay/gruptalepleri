<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrupTalepleri — Grup Uçuş Talep Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-dark: #1a1a2e;
            --brand-red: #e94560;
            --brand-mid: #16213e;
        }

        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* NAVBAR */
        .navbar {
            background: var(--brand-dark) !important;
        }
        .navbar-brand {
            color: var(--brand-red) !important;
            font-weight: 700;
            font-size: 1.3rem;
        }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-mid) 60%, #0f3460 100%);
            color: white;
            padding: 90px 0 70px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(233,69,96,.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(15,52,96,.4) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero h1 {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.2;
        }
        .hero h1 span {
            color: var(--brand-red);
        }
        .hero p.lead {
            color: rgba(255,255,255,.75);
            font-size: 1.1rem;
        }
        .btn-brand {
            background: var(--brand-red);
            border: none;
            color: white;
            padding: .65rem 1.8rem;
            border-radius: 6px;
            font-weight: 600;
            transition: background .2s, transform .15s;
        }
        .btn-brand:hover {
            background: #c73652;
            color: white;
            transform: translateY(-1px);
        }
        .btn-outline-light-custom {
            border: 2px solid rgba(255,255,255,.35);
            color: white;
            padding: .6rem 1.6rem;
            border-radius: 6px;
            font-weight: 500;
            background: transparent;
            transition: border-color .2s, background .2s;
        }
        .btn-outline-light-custom:hover {
            border-color: white;
            background: rgba(255,255,255,.08);
            color: white;
        }

        /* STATS BAR */
        .stats-bar {
            background: white;
            border-bottom: 1px solid #e9ecef;
        }
        .stat-item {
            text-align: center;
            padding: 20px 10px;
            border-right: 1px solid #f0f2f5;
        }
        .stat-item:last-child { border-right: none; }
        .stat-item .num {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--brand-dark);
        }
        .stat-item .lbl {
            font-size: .8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        /* FEATURES */
        .section-title {
            font-weight: 800;
            color: var(--brand-dark);
        }
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 28px 24px;
            border: 1px solid #e9ecef;
            transition: box-shadow .2s, transform .2s;
            height: 100%;
        }
        .feature-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
            transform: translateY(-3px);
        }
        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            background: #fff0f3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--brand-red);
            margin-bottom: 16px;
        }
        .feature-card h5 {
            font-weight: 700;
            color: var(--brand-dark);
            font-size: 1rem;
        }
        .feature-card p {
            color: #6c757d;
            font-size: .9rem;
            margin: 0;
        }

        /* FLOW STEPS */
        .flow-step {
            text-align: center;
        }
        .flow-num {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: var(--brand-red);
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .flow-step h6 { font-weight: 700; color: var(--brand-dark); }
        .flow-step p { color: #6c757d; font-size: .85rem; margin: 0; }
        .flow-divider {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, var(--brand-red), rgba(233,69,96,.2));
            margin-top: 22px;
        }

        /* STATUS BADGES */
        .status-demo .badge { font-size: .8rem; padding: .45em .9em; border-radius: 6px; }

        /* CTA SECTION */
        .cta-section {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-mid));
            color: white;
            border-radius: 16px;
        }

        /* FOOTER */
        footer {
            background: var(--brand-dark);
            color: rgba(255,255,255,.55);
            font-size: .85rem;
        }
        footer a { color: rgba(255,255,255,.55); text-decoration: none; }
        footer a:hover { color: var(--brand-red); }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-plane-departure me-2"></i>GrupTalepleri
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
            </a>
        </div>
    </div>
</nav>

{{-- HERO --}}
<section class="hero">
    <div class="container position-relative" style="z-index:1">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="mb-3">
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(233,69,96,.2);color:#e94560;font-size:.8rem;font-weight:600;border:1px solid rgba(233,69,96,.3)">
                        <i class="fas fa-bolt me-1"></i>Hızlı · Güvenilir · Profesyonel
                    </span>
                </div>
                <h1 class="mb-3">
                    Grup Uçuş Taleplerini<br>
                    <span>Akıllıca</span> Yönetin
                </h1>
                <p class="lead mb-4">
                    Seyahat acenteleri için özel geliştirilmiş grup charter talep platformu.<br>
                    Talebinizi oluşturun, teklifleri karşılaştırın, anında bildirim alın.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('login') }}" class="btn btn-brand">
                        <i class="fas fa-sign-in-alt me-2"></i>Panele Giriş Yap
                    </a>
                    <a href="https://wa.me/905324262630" target="_blank" class="btn-outline-light-custom btn">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp Destek
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-end">
                <div style="position:relative;">
                    {{-- Dekoratif uçuş kartı --}}
                    <div class="p-4 rounded-3" style="background:rgba(255,255,255,.07);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.12);min-width:300px">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span style="color:rgba(255,255,255,.6);font-size:.8rem">TALEP #GTP-2024-001</span>
                            <span class="badge" style="background:#198754">Biletlendi</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="text-center">
                                <div style="font-size:1.4rem;font-weight:800">IST</div>
                                <div style="color:rgba(255,255,255,.5);font-size:.75rem">İstanbul</div>
                            </div>
                            <div class="flex-grow-1 text-center">
                                <div style="color:var(--brand-red);font-size:.75rem">Direkt</div>
                                <div style="border-top:2px dashed rgba(255,255,255,.2);margin:4px 0;position:relative">
                                    <i class="fas fa-plane" style="position:absolute;top:-9px;left:50%;transform:translateX(-50%);font-size:.8rem;color:var(--brand-red)"></i>
                                </div>
                                <div style="color:rgba(255,255,255,.5);font-size:.75rem">3h 20m</div>
                            </div>
                            <div class="text-center">
                                <div style="font-size:1.4rem;font-weight:800">DXB</div>
                                <div style="color:rgba(255,255,255,.5);font-size:.75rem">Dubai</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between" style="color:rgba(255,255,255,.6);font-size:.8rem">
                            <span><i class="fas fa-users me-1"></i>150 PAX</span>
                            <span><i class="fas fa-calendar me-1"></i>15 Haz 2025</span>
                        </div>
                    </div>
                    {{-- Küçük bildirim kartı --}}
                    <div class="p-2 px-3 rounded-2 d-flex align-items-center gap-2"
                         style="background:white;position:absolute;bottom:-18px;right:-10px;box-shadow:0 4px 16px rgba(0,0,0,.15);min-width:190px">
                        <div style="width:34px;height:34px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;color:#198754;font-size:.9rem;flex-shrink:0">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <div style="font-size:.8rem;font-weight:700;color:#1a1a2e">Teklif Onaylandı!</div>
                            <div style="font-size:.72rem;color:#6c757d">₺42.500 · 2 dk önce</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- STATS BAR --}}
<div class="stats-bar shadow-sm">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3 stat-item">
                <div class="num">500+</div>
                <div class="lbl">Grup Talebi</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="num">120+</div>
                <div class="lbl">Aktif Acente</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="num">98%</div>
                <div class="lbl">Müşteri Memnuniyeti</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="num">24/7</div>
                <div class="lbl">Destek</div>
            </div>
        </div>
    </div>
</div>

{{-- ÖZELLIKLER --}}
<section class="py-5 my-3">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Neden GrupTalepleri?</h2>
            <p class="text-muted">Grup rezervasyonlarını yönetmenin en kolay yolu</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h5>Anında Teklif Alma</h5>
                    <p>Talebiniz oluşturulduktan sonra uzman ekibimiz en kısa sürede sizinle iletişime geçer ve rekabetçi fiyat teklifleri sunar.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-route"></i></div>
                    <h5>Çoklu Segment Desteği</h5>
                    <p>Tek yön, gidiş-dönüş veya çoklu bacaklı uçuşları tek talep üzerinden kolayca yönetin. Karmaşık rotalar artık sorun değil.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h5>Anlık Durum Takibi</h5>
                    <p>Talebinizin hangi aşamada olduğunu gerçek zamanlı takip edin. Beklemede'den Biletlendi'ye her adımda bildirim alın.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <h5>Harita Görünümü</h5>
                    <p>Tüm aktif uçuş rotalarınızı interaktif harita üzerinde görün. Segment bazlı filtreleme ile istediğinize odaklanın.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-robot"></i></div>
                    <h5>AI Destekli Analiz</h5>
                    <p>Yapay zeka destekli talep analizi ile rotanız otomatik olarak ayrıştırılır, segment bilgileri doğru şekilde doldurulur.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fab fa-whatsapp"></i></div>
                    <h5>WhatsApp Entegrasyonu</h5>
                    <p>Taleplerinizi WhatsApp üzerinden hızla iletebilir, destek ekibimizle doğrudan iletişime geçebilirsiniz.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- NASIL ÇALIŞIR --}}
<section class="py-5" style="background:white;border-top:1px solid #e9ecef;border-bottom:1px solid #e9ecef">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Nasıl Çalışır?</h2>
            <p class="text-muted">4 adımda grup uçuşunuzu rezerve edin</p>
        </div>
        <div class="d-flex align-items-start gap-0 flex-wrap flex-md-nowrap">
            <div class="flow-step flex-fill px-3 mb-4 mb-md-0">
                <div class="flow-num">1</div>
                <h6>Talep Oluştur</h6>
                <p>Rota, tarih ve yolcu sayısını girerek talebinizi gönderin.</p>
            </div>
            <div class="flow-divider d-none d-md-block mt-1"></div>
            <div class="flow-step flex-fill px-3 mb-4 mb-md-0">
                <div class="flow-num">2</div>
                <h6>Teklif Al</h6>
                <p>Uzman ekibimiz charter ve tarifeli seçenekleri fiyatlandırır.</p>
            </div>
            <div class="flow-divider d-none d-md-block mt-1"></div>
            <div class="flow-step flex-fill px-3 mb-4 mb-md-0">
                <div class="flow-num">3</div>
                <h6>Onay Ver</h6>
                <p>Teklifleri karşılaştırın, uygun olanı tek tıkla onaylayın.</p>
            </div>
            <div class="flow-divider d-none d-md-block mt-1"></div>
            <div class="flow-step flex-fill px-3">
                <div class="flow-num">4</div>
                <h6>Bilet Al</h6>
                <p>Depozito ve ödeme sonrası biletleriniz hazırlanır.</p>
            </div>
        </div>
    </div>
</section>

{{-- TALEP DURUMLARI --}}
<section class="py-5 my-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <h2 class="section-title mb-3">Her Adım Şeffaf</h2>
                <p class="text-muted mb-4">Talebiniz süreç boyunca 6 farklı durum üzerinden izlenir. Nerede olduğunuzu her zaman bilirsiniz.</p>
                <div class="status-demo d-flex flex-wrap gap-2">
                    <span class="badge" style="background:#6c757d"><i class="fas fa-hourglass-half me-1"></i>Beklemede</span>
                    <span class="badge" style="background:#0d6efd"><i class="fas fa-spinner me-1"></i>İşlemde</span>
                    <span class="badge" style="background:#ffc107;color:#000"><i class="fas fa-tag me-1"></i>Fiyatlandırıldı</span>
                    <span class="badge" style="background:#6f42c1"><i class="fas fa-coins me-1"></i>Depozitoda</span>
                    <span class="badge" style="background:#198754"><i class="fas fa-ticket-alt me-1"></i>Biletlendi</span>
                    <span class="badge" style="background:#dc3545"><i class="fas fa-times-circle me-1"></i>Olumsuz</span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header" style="background:var(--brand-dark);color:white;font-weight:600">
                        <i class="fas fa-list-alt me-2" style="color:var(--brand-red)"></i>Örnek Talep Akışı
                    </div>
                    <div class="card-body p-0">
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div style="width:12px;height:12px;border-radius:50%;background:#6c757d;flex-shrink:0"></div>
                            <div class="ms-3 flex-grow-1">
                                <strong class="small">GTP-2024-042</strong>
                                <span class="text-muted small ms-2">IST → CDG · 85 PAX</span>
                            </div>
                            <span class="badge rounded-pill" style="background:#6c757d;font-size:.75rem">Beklemede</span>
                        </div>
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div style="width:12px;height:12px;border-radius:50%;background:#ffc107;flex-shrink:0"></div>
                            <div class="ms-3 flex-grow-1">
                                <strong class="small">GTP-2024-038</strong>
                                <span class="text-muted small ms-2">SAW → DXB · 200 PAX</span>
                            </div>
                            <span class="badge rounded-pill" style="background:#ffc107;color:#000;font-size:.75rem">Teklif Var</span>
                        </div>
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div style="width:12px;height:12px;border-radius:50%;background:#6f42c1;flex-shrink:0"></div>
                            <div class="ms-3 flex-grow-1">
                                <strong class="small">GTP-2024-031</strong>
                                <span class="text-muted small ms-2">ESB → LHR · 120 PAX</span>
                            </div>
                            <span class="badge rounded-pill" style="background:#6f42c1;font-size:.75rem">Depozitoda</span>
                        </div>
                        <div class="d-flex align-items-center p-3">
                            <div style="width:12px;height:12px;border-radius:50%;background:#198754;flex-shrink:0"></div>
                            <div class="ms-3 flex-grow-1">
                                <strong class="small">GTP-2024-025</strong>
                                <span class="text-muted small ms-2">IST → JFK · 180 PAX</span>
                            </div>
                            <span class="badge rounded-pill" style="background:#198754;font-size:.75rem">Biletlendi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-5 mb-4">
    <div class="container">
        <div class="cta-section p-5 text-center">
            <h2 class="fw-800 mb-2" style="font-weight:800">Hemen Başlayın</h2>
            <p class="mb-4" style="color:rgba(255,255,255,.7)">Acente hesabınızla giriş yapın ve ilk grub talebinizi oluşturun.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('login') }}" class="btn btn-brand btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                </a>
                <a href="https://wa.me/905324262630" target="_blank" class="btn btn-lg" style="background:rgba(255,255,255,.1);color:white;border:2px solid rgba(255,255,255,.3)">
                    <i class="fab fa-whatsapp me-2" style="color:#25d366"></i>Bize Yazın
                </a>
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="py-4">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div>
                <span style="color:var(--brand-red);font-weight:700"><i class="fas fa-plane-departure me-1"></i>GrupTalepleri</span>
                <span class="ms-3">© {{ date('Y') }} Tüm hakları saklıdır.</span>
            </div>
            <div class="d-flex gap-3">
                <a href="https://wa.me/905324262630" target="_blank"><i class="fab fa-whatsapp me-1"></i>WhatsApp</a>
                <a href="{{ route('login') }}"><i class="fas fa-lock me-1"></i>Giriş</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
