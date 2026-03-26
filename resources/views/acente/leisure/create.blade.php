<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $productType === 'dinner_cruise' ? 'Dinner Cruise Talebi' : 'Yacht Charter Talebi' }} - GrupTalepleri</title>
    @include('acente.partials.theme-styles')
    <style>
        .leisure-create-page {
            --bg: linear-gradient(180deg, #f5f7fb 0%, #eef3f8 42%, #f8fafc 100%);
            --shell: rgba(255, 255, 255, .88);
            --border: rgba(148, 163, 184, .22);
            --muted: #64748b;
            --heading: #0f172a;
            --surface-shadow: 0 26px 60px rgba(15, 23, 42, .08);
            background: var(--bg);
            min-height: 100vh;
        }
        .leisure-create-page .page-shell { padding: 1.5rem 0 3rem; }
        .leisure-create-page .hero-card,
        .leisure-create-page .builder-card,
        .leisure-create-page .aside-card,
        .leisure-create-page .field-card,
        .leisure-create-page .package-card,
        .leisure-create-page .scene-card {
            background: var(--shell);
            border: 1px solid var(--border);
            box-shadow: var(--surface-shadow);
            backdrop-filter: blur(18px);
        }
        .leisure-create-page .hero-card {
            border-radius: 30px;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(250, 204, 21, .2), transparent 26%),
                radial-gradient(circle at left center, rgba(14, 165, 233, .18), transparent 28%),
                linear-gradient(135deg, #081225 0%, #10264d 46%, #142d56 100%);
            color: #f8fafc;
        }
        .leisure-create-page .eyebrow,
        .leisure-create-page .hero-chip,
        .leisure-create-page .mini-pill,
        .leisure-create-page .scene-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
        }
        .leisure-create-page .eyebrow {
            padding: .45rem .8rem;
            background: rgba(255, 255, 255, .12);
            color: rgba(255, 255, 255, .92);
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .leisure-create-page .hero-title {
            margin: 1rem 0 .9rem;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2rem, 4.8vw, 3.8rem);
            line-height: .98;
            letter-spacing: -.03em;
        }
        .leisure-create-page .hero-copy {
            max-width: 58ch;
            color: rgba(226, 232, 240, .88);
            line-height: 1.8;
            margin-bottom: 1.2rem;
        }
        .leisure-create-page .hero-chip-row,
        .leisure-create-page .package-grid,
        .leisure-create-page .scene-grid,
        .leisure-create-page .summary-list,
        .leisure-create-page .extra-grid { display: grid; gap: .9rem; }
        .leisure-create-page .hero-chip-row { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 1rem; }
        .leisure-create-page .hero-chip { padding: .85rem 1rem; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .12); color: #f8fafc; }
        .leisure-create-page .hero-actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 1.35rem; }
        .leisure-create-page .hero-btn,
        .leisure-create-page .ghost-btn,
        .leisure-create-page .submit-btn,
        .leisure-create-page .aside-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: 999px;
            padding: .9rem 1.2rem;
            text-decoration: none;
            font-weight: 700;
            border: 0;
        }
        .leisure-create-page .hero-btn,
        .leisure-create-page .submit-btn { color: #fff; background: linear-gradient(135deg, #f97316, #ea580c); box-shadow: 0 16px 32px rgba(234, 88, 12, .24); }
        .leisure-create-page .ghost-btn,
        .leisure-create-page .aside-link { color: #f8fafc; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .14); }
        .leisure-create-page .builder-card,
        .leisure-create-page .aside-card { border-radius: 28px; }
        .leisure-create-page .section-kicker { margin-bottom: .55rem; font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #0f766e; }
        .leisure-create-page .section-title { margin: 0; color: var(--heading); font-size: 1.35rem; font-weight: 800; }
        .leisure-create-page .section-copy,
        .leisure-create-page .aside-copy { margin: .35rem 0 0; color: var(--muted); line-height: 1.75; }
        .leisure-create-page .field-card { border-radius: 22px; padding: 1.1rem; }
        .leisure-create-page .field-card label { display: block; margin-bottom: .5rem; font-size: .86rem; font-weight: 700; color: #0f172a; }
        .leisure-create-page .package-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: .9rem; }
        .leisure-create-page .package-card { border-radius: 24px; overflow: hidden; text-align: left; padding: 0; cursor: pointer; transition: transform .2s ease, border-color .2s ease; }
        .leisure-create-page .package-card:hover { transform: translateY(-2px); }
        .leisure-create-page .package-card.active { border-color: rgba(37, 99, 235, .35); box-shadow: 0 20px 45px rgba(37, 99, 235, .12); }
        .leisure-create-page .package-visual { min-height: 165px; padding: 1rem; color: #fff; display: flex; flex-direction: column; justify-content: space-between; }
        .leisure-create-page .package-card.standard .package-visual { background: linear-gradient(135deg, #0f766e, #0f172a 72%); }
        .leisure-create-page .package-card.vip .package-visual { background: linear-gradient(135deg, #7c2d12, #1e293b 68%, #be123c); }
        .leisure-create-page .package-card.premium .package-visual { background: linear-gradient(135deg, #312e81, #111827 65%, #0369a1); }
        .leisure-create-page .package-body { padding: 1rem; }
        .leisure-create-page .package-name { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 1.5rem; }
        .leisure-create-page .package-summary { color: var(--muted); line-height: 1.7; margin: 0; }
        .leisure-create-page .mini-pill { padding: .4rem .7rem; background: rgba(255, 255, 255, .14); color: #fff; }
        .leisure-create-page .grid-3,
        .leisure-create-page .grid-2,
        .leisure-create-page .extra-grid { display: grid; gap: 1rem; }
        .leisure-create-page .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .leisure-create-page .grid-2,
        .leisure-create-page .extra-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .leisure-create-page .extra-option { border: 1px solid rgba(148, 163, 184, .2); border-radius: 18px; padding: .95rem; background: rgba(248, 250, 252, .78); }
        .leisure-create-page .extra-option.included { background: rgba(240, 253, 244, .94); border-color: rgba(34, 197, 94, .22); }
        .leisure-create-page .aside-card { padding: 1.25rem; position: sticky; top: 1rem; }
        .leisure-create-page .summary-list { margin-top: 1rem; }
        .leisure-create-page .summary-item,
        .leisure-create-page .scene-card { border-radius: 20px; padding: 1rem; }
        .leisure-create-page .summary-item { background: rgba(248, 250, 252, .8); border: 1px solid rgba(148, 163, 184, .18); }
        .leisure-create-page .summary-item strong,
        .leisure-create-page .scene-title { display: block; color: #0f172a; margin-bottom: .35rem; }
        .leisure-create-page .scene-grid { margin-top: 1rem; }
        .leisure-create-page .scene-card.scene-a { background: linear-gradient(135deg, #0f172a, #082f49 72%, #38bdf8); color: #e2e8f0; }
        .leisure-create-page .scene-card.scene-b { background: linear-gradient(135deg, #4c1d95, #1e1b4b 70%, #ec4899); color: #fce7f3; }
        .leisure-create-page .scene-pill { padding: .38rem .68rem; background: rgba(255, 255, 255, .14); color: inherit; }
        .leisure-create-page .aside-footer { display: flex; flex-direction: column; gap: .8rem; margin-top: 1.2rem; }
        html[data-theme="dark"] .leisure-create-page {
            --bg: linear-gradient(180deg, #08111f 0%, #0b1629 45%, #091120 100%);
            --shell: rgba(10, 20, 37, .9);
            --border: rgba(59, 130, 246, .18);
            --muted: #9fb2d9;
            --heading: #eff6ff;
        }
        html[data-theme="dark"] .leisure-create-page .field-card label,
        html[data-theme="dark"] .leisure-create-page .summary-item strong,
        html[data-theme="dark"] .leisure-create-page .scene-title { color: #f8fafc; }
        html[data-theme="dark"] .leisure-create-page .extra-option { background: rgba(15, 23, 42, .86); }
        html[data-theme="dark"] .leisure-create-page .extra-option.included { background: rgba(6, 78, 59, .24); }
        html[data-theme="dark"] .leisure-create-page .summary-item { background: rgba(15, 23, 42, .82); }
        @media (max-width: 991.98px) {
            .leisure-create-page .hero-chip-row,
            .leisure-create-page .package-grid,
            .leisure-create-page .grid-3,
            .leisure-create-page .grid-2,
            .leisure-create-page .extra-grid { grid-template-columns: 1fr; }
            .leisure-create-page .aside-card { position: static; }
        }
    </style>
</head>
<body class="theme-scope leisure-create-page">
<x-navbar-acente :active="$productType === 'dinner_cruise' ? 'dinner-cruise' : 'yacht-charter'" />

@php
    $isDinner = $productType === 'dinner_cruise';
    $title = $isDinner ? 'Bosphorus Dinner Cruise Builder' : 'Yacht Charter Builder';
    $description = $isDinner
        ? 'Paket seviyesini secin, misafir deneyimini sekillendirin ve talebi daha premium bir akista operasyon ekibine iletin.'
        : 'Etkinlik tipini, sureyi ve marina planini urun mantigiyla birlestirip daha guclu bir yacht charter talebi olusturun.';
    $dinnerDefaults = old('dinner', []);
    $yachtDefaults = old('yacht', []);
    $selectedExtras = collect(old('extra_option_codes', []))->filter()->values()->all();
    $packageLevel = old('package_level', request('package_level', 'standard'));
    $defaultIncluded = $extraOptions->where('default_included', true)->values();
@endphp

<div class="container page-shell">
    <div class="hero-card card border-0 mb-4">
        <div class="card-body p-4 p-xl-5">
            <span class="eyebrow"><i class="fas {{ $isDinner ? 'fa-utensils' : 'fa-ship' }}" aria-hidden="true"></i>{{ $isDinner ? 'Dinner Cruise Request Builder' : 'Yacht Charter Request Builder' }}</span>
            <h1 class="hero-title">{{ $title }}</h1>
            <p class="hero-copy">{{ $description }}</p>
            <div class="hero-chip-row">
                <div class="hero-chip"><i class="fas fa-layer-group" aria-hidden="true"></i>{{ $packageTemplates->count() }} paket seviyesi</div>
                <div class="hero-chip"><i class="fas fa-gem" aria-hidden="true"></i>{{ $defaultIncluded->count() }} varsayilan servis</div>
                <div class="hero-chip"><i class="fas fa-bolt" aria-hidden="true"></i>Tek ekranda hizli talep akisi</div>
            </div>
            <div class="hero-actions">
                <a href="{{ route($routePrefix . '.index') }}" class="ghost-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i>Koleksiyona don</a>
                <a href="{{ $isDinner ? route('acente.yacht-charter.create') : route('acente.dinner-cruise.create') }}" class="ghost-btn"><i class="fas fa-compass" aria-hidden="true"></i>{{ $isDinner ? 'Yacht tarafina gec' : 'Dinner tarafina gec' }}</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="builder-card card border-0">
                <div class="card-body p-4 p-xl-5">
                    <form method="POST" action="{{ route($routePrefix . '.store') }}">
                        @csrf
                        <div class="section-kicker">1. Paket secimi</div>
                        <h2 class="section-title">Hangi urun tonu ile ilerliyorsunuz?</h2>
                        <p class="section-copy">Paket seviyesi once secilir, diger bilgiler bu deneyimi destekleyecek sekilde tamamlanir.</p>
                        <input type="hidden" name="package_level" id="packageLevelInput" value="{{ $packageLevel }}">

                        <div class="package-grid">
                            @foreach($packageTemplates as $template)
                                <button type="button" class="package-card {{ $template->level }} package-choice {{ $packageLevel === $template->level ? 'active' : '' }}" data-level="{{ $template->level }}">
                                    <div class="package-visual">
                                        <span class="mini-pill"><i class="fas fa-star" aria-hidden="true"></i>{{ strtoupper($template->level) }}</span>
                                        <h3 class="package-name">{{ $template->name_tr }}</h3>
                                    </div>
                                    <div class="package-body">
                                        <p class="package-summary">{{ $template->summary_tr }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        <div class="section-kicker mt-4">2. Temel bilgiler</div>
                        <div class="grid-3 mt-3">
                            <div class="field-card"><label>Tarih <span class="text-danger">*</span></label><input type="date" name="service_date" class="form-control" value="{{ old('service_date') }}" required></div>
                            <div class="field-card"><label>Kisi sayisi <span class="text-danger">*</span></label><input type="number" min="1" max="500" name="guest_count" class="form-control" value="{{ old('guest_count') }}" required></div>
                            <div class="field-card"><label>Dil <span class="text-danger">*</span></label><select name="language_preference" class="form-select" required><option value="tr" @selected(old('language_preference', 'tr') === 'tr')>Turkce</option><option value="en" @selected(old('language_preference') === 'en')>English</option></select></div>
                        </div>

                        <div class="grid-3 mt-3">
                            <div class="field-card"><label>Transfer</label><select name="transfer_required" id="transferRequiredSelect" class="form-select"><option value="0" @selected(old('transfer_required') == '0')>Yok</option><option value="1" @selected(old('transfer_required') == '1')>Evet</option></select><div class="aside-copy mt-2">Shuttle transfer varsayilan olarak dahil kabul edilir.</div></div>
                            <div class="field-card"><label>Alkol tercihi</label><select name="alcohol_preference" class="form-select"><option value="">Seciniz</option><option value="alkolsuz" @selected(old('alcohol_preference') === 'alkolsuz')>Alkolsuz</option><option value="sinirli_alkollu" @selected(old('alcohol_preference') === 'sinirli_alkollu')>Sinirli alkollu</option><option value="premium" @selected(old('alcohol_preference') === 'premium')>Premium</option></select></div>
                            <div class="field-card"><label>Menu tercihi</label><input type="text" name="menu_preference" class="form-control" value="{{ old('menu_preference') }}" placeholder="Balik, et, vejetaryen vb."></div>
                        </div>

                        <div id="transferFields" class="{{ old('transfer_required') == '1' ? '' : 'd-none' }}">
                            <div class="section-kicker mt-4">3. Transfer bilgileri</div>
                            <div class="grid-2 mt-3">
                                <div class="field-card"><label>Otel adi</label><input type="text" name="hotel_name" class="form-control" value="{{ old('hotel_name') }}" placeholder="Pickup alinacak otel"></div>
                                <div class="field-card"><label>Bolge</label><select name="transfer_region" class="form-select"><option value="">Seciniz</option>@foreach($regions as $region)<option value="{{ $region }}" @selected(old('transfer_region') === $region)>{{ $region }}</option>@endforeach</select></div>
                                <div class="field-card"><label>Yolcu adi</label><input type="text" name="guest_name" class="form-control" value="{{ old('guest_name') }}" placeholder="Pickup koordinasyon kisisi"></div>
                                <div class="field-card"><label>Telefon</label><input type="text" name="guest_phone" class="form-control" value="{{ old('guest_phone') }}" placeholder="+90 5xx xxx xx xx"></div>
                            </div>
                        </div>

                        <div class="section-kicker mt-4">4. {{ $isDinner ? 'Dinner cruise' : 'Yacht charter' }} detaylari</div>
                        <div class="grid-3 mt-3">
                            @if($isDinner)
                                <div class="field-card"><label>Seans / boarding saati</label><input type="text" name="dinner[session_time]" class="form-control" value="{{ $dinnerDefaults['session_time'] ?? '' }}" placeholder="19:30 boarding / 20:00 baslangic"></div>
                                <div class="field-card"><label>Iskele</label><input type="text" name="dinner[pier_name]" class="form-control" value="{{ $dinnerDefaults['pier_name'] ?? '' }}" placeholder="Kabatas, Eminonu vb."></div>
                                <div class="field-card"><label>Kutlama tipi</label><input type="text" name="dinner[celebration_type]" class="form-control" value="{{ $dinnerDefaults['celebration_type'] ?? '' }}" placeholder="Dogum gunu, kurumsal vb."></div>
                                <div class="field-card"><label>Yetiskin</label><input type="number" min="0" name="dinner[adult_count]" class="form-control" value="{{ $dinnerDefaults['adult_count'] ?? '' }}"></div>
                                <div class="field-card"><label>Cocuk</label><input type="number" min="0" name="dinner[child_count]" class="form-control" value="{{ $dinnerDefaults['child_count'] ?? '' }}"></div>
                                <div class="field-card"><label>Bebek</label><input type="number" min="0" name="dinner[infant_count]" class="form-control" value="{{ $dinnerDefaults['infant_count'] ?? '' }}"></div>
                                <div class="field-card" style="grid-column: 1 / -1;"><label>Cruise tipi</label><select name="dinner[shared_cruise]" class="form-select"><option value="1" @selected(($dinnerDefaults['shared_cruise'] ?? '1') == '1')>Shared / ortak duzen</option><option value="0" @selected(($dinnerDefaults['shared_cruise'] ?? null) == '0')>Ozel masa / private duzen</option></select></div>
                            @else
                                <div class="field-card"><label>Baslangic saati</label><input type="time" name="yacht[start_time]" class="form-control" value="{{ $yachtDefaults['start_time'] ?? '' }}"></div>
                                <div class="field-card"><label>Sure (saat)</label><input type="number" min="1" max="24" name="yacht[duration_hours]" class="form-control" value="{{ $yachtDefaults['duration_hours'] ?? '' }}"></div>
                                <div class="field-card"><label>Marina / kalkis noktasi</label><input type="text" name="yacht[marina_name]" class="form-control" value="{{ $yachtDefaults['marina_name'] ?? '' }}" placeholder="Kurucesme, Bebek vb."></div>
                                <div class="field-card"><label>Rota plani</label><input type="text" name="yacht[route_plan]" class="form-control" value="{{ $yachtDefaults['route_plan'] ?? '' }}" placeholder="Bogaz turu, Adalar, gun batimi rotasi"></div>
                                <div class="field-card"><label>Etkinlik tipi</label><input type="text" name="yacht[event_type]" class="form-control" value="{{ $yachtDefaults['event_type'] ?? '' }}" placeholder="Kurumsal, kutlama"></div>
                                <div class="field-card"><label>Tekne stili</label><input type="text" name="yacht[vessel_style]" class="form-control" value="{{ $yachtDefaults['vessel_style'] ?? '' }}" placeholder="Modern, klasik, mega yacht"></div>
                            @endif
                        </div>

                        <div class="section-kicker mt-4">5. Ekstralar ve notlar</div>
                        <div class="extra-grid mt-3">
                            @foreach($extraOptions as $option)
                                @php($checked = $option->default_included || in_array($option->code, $selectedExtras, true))
                                <label class="extra-option {{ $option->default_included ? 'included' : '' }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="extra_option_codes[]" value="{{ $option->code }}" @checked($checked) @disabled($option->default_included)>
                                        @if($option->default_included)
                                            <input type="hidden" name="extra_option_codes[]" value="{{ $option->code }}">
                                        @endif
                                        <span class="form-check-label fw-bold">{{ $option->title_tr }}</span>
                                    </div>
                                    @if($option->description_tr)
                                        <div class="aside-copy mt-2">{{ $option->description_tr }}</div>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <div class="grid-2 mt-3">
                            <div class="field-card"><label>Milliyet / dil bilgisi</label><input type="text" name="nationality" class="form-control" value="{{ old('nationality') }}" placeholder="TR, EN, AR, DE veya ulke bilgisi"></div>
                            <div class="field-card"><label>Ekstra talepler</label><input type="text" name="extra_requests" class="form-control" value="{{ old('extra_requests') }}" placeholder="DJ, fotograf, susleme, ozel masa vb."></div>
                        </div>
                        <div class="field-card mt-3"><label>Not</label><textarea name="notes" rows="3" class="form-control" placeholder="Musteriye donerken kullanmak istediginiz satis notlari veya operasyonel notlar">{{ old('notes') }}</textarea></div>

                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mt-4">
                            <div class="aside-copy">Talep olustuktan sonra teklif, transfer, operasyon ve finans takibi ayni panelde devam eder.</div>
                            <button type="submit" class="submit-btn">{{ $isDinner ? 'Dinner cruise talebini kaydet' : 'Yacht charter talebini kaydet' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="aside-card">
                <div class="section-kicker">{{ $isDinner ? 'Live sales guide' : 'Charter prep guide' }}</div>
                <h3 class="section-title">{{ $isDinner ? 'Daha premium bir ilk izlenim verin' : 'Tekliften once urun tonunu kurun' }}</h3>
                <p class="aside-copy mt-2">{{ $isDinner ? 'Dinner cruise satilirken en kritik alanlar tarih, kisi sayisi, masa duzeni ve transfer bilgisidir.' : 'Yacht charter tarafinda sure, rota, marina ve etkinlik tipi teklif hizini belirler.' }}</p>
                <div class="summary-list">
                    <div class="summary-item"><strong>Paket mantigi</strong>Paket seviyesi ve ekstra talepler ne kadar netse, size donen teklif dili de o kadar guclu olur.</div>
                    <div class="summary-item"><strong>Varsayilan servisler</strong>@if($defaultIncluded->isNotEmpty()){{ $defaultIncluded->pluck('title_tr')->join(', ') }}@else Transfer ve temel servisler teklifte netlesir.@endif</div>
                    <div class="summary-item"><strong>Operasyon hizi</strong>Eksik alan birakabilirsiniz ama seans, marina, sure ve transfer bilgisi netlesirse donus hizi belirgin artar.</div>
                </div>
                <div class="scene-grid">
                    <div class="scene-card scene-a"><span class="scene-pill"><i class="fas fa-water" aria-hidden="true"></i>{{ $isDinner ? 'Bosphorus mood' : 'Open deck mood' }}</span><strong class="scene-title mt-3">{{ $isDinner ? 'Masa, show ve guverte algisi' : 'Rota, marina ve vessel algisi' }}</strong><div class="aside-copy text-white-50">{{ $isDinner ? 'Misafirin ne satin aldigini once hissettirmek, formu doldurmaktan daha ikna edicidir.' : 'Yat secimini teknik bir formdan cikarip deneyim odakli bir briefe donusturun.' }}</div></div>
                    <div class="scene-card scene-b"><span class="scene-pill"><i class="fas fa-sparkles" aria-hidden="true"></i>Upsell ready</span><strong class="scene-title mt-3">Ekstra deneyimleri unutmayin</strong><div class="aside-copy text-white-50">DJ, photo/video, susleme veya VIP transfer gibi kalemler teklifin degerini hizla buyutur.</div></div>
                </div>
                <div class="aside-footer">
                    <a href="{{ route($routePrefix . '.index') }}" class="aside-link"><i class="fas fa-compass" aria-hidden="true"></i>Koleksiyon vitrini</a>
                    <a href="{{ route($routePrefix . '.index') }}#recent-requests" class="aside-link"><i class="fas fa-folder-open" aria-hidden="true"></i>Son talepler</a>
                </div>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
<script>
(() => {
    const transferSelect = document.getElementById('transferRequiredSelect');
    const transferFields = document.getElementById('transferFields');
    const packageInput = document.getElementById('packageLevelInput');
    const packageChoices = document.querySelectorAll('.package-choice');
    const syncTransfer = () => {
        if (!transferSelect || !transferFields) return;
        transferFields.classList.toggle('d-none', transferSelect.value !== '1');
    };
    transferSelect?.addEventListener('change', syncTransfer);
    syncTransfer();
    packageChoices.forEach((card) => {
        card.addEventListener('click', () => {
            packageChoices.forEach((item) => item.classList.remove('active'));
            card.classList.add('active');
            if (packageInput) packageInput.value = card.dataset.level || 'standard';
        });
    });
})();
</script>
</body>
</html>
