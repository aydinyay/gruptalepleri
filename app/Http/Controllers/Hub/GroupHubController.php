<?php

namespace App\Http\Controllers\Hub;

use App\Http\Controllers\Controller;
use App\Models\CharterPresetPackage;
use App\Models\SistemAyar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class GroupHubController extends Controller
{
    private const CHARTER_PRESET_SETTINGS_KEY = 'charter_preset_packages_json';
    private const CHARTER_DEFAULT_HERO_IMAGE = '/images/charter/default-jet.svg';

    public function acente(string $group)
    {
        return $this->renderHub('acente', $group);
    }

    public function admin(string $group)
    {
        return $this->renderHub('admin', $group);
    }

    public function superadmin(string $group)
    {
        return $this->renderHub('superadmin', $group);
    }

    private function renderHub(string $role, string $group)
    {
        $catalog = $this->catalog();
        $roleCatalog = $catalog[$role] ?? [];
        abort_unless(isset($roleCatalog[$group]), 404);

        $hub = $roleCatalog[$group];

        $links = collect($hub['links'] ?? [])
            ->map(function (array $item): array {
                return [
                    'label' => (string) ($item['label'] ?? 'Link'),
                    'icon' => (string) ($item['icon'] ?? 'fas fa-link'),
                    'url' => $this->resolveUrl($item),
                ];
            })
            ->values()
            ->all();

        $cards = collect($hub['cards'] ?? [])
            ->map(function (array $item): array {
                return [
                    'tag' => (string) ($item['tag'] ?? 'Vitrin'),
                    'title' => (string) ($item['title'] ?? 'Kart'),
                    'description' => (string) ($item['description'] ?? ''),
                    'icon' => (string) ($item['icon'] ?? 'fas fa-star'),
                    'theme' => (string) ($item['theme'] ?? 'theme-indigo'),
                    'url' => $this->resolveUrl($item),
                ];
            })
            ->values()
            ->all();

        $showPremiumPackages = in_array($role, ['acente', 'superadmin'], true) && $group === 'charter';
        $premiumPackages = $showPremiumPackages ? $this->loadCharterPresetPackages($role) : [];

        return view('hubs.group', [
            'role' => $role,
            'navbarComponent' => $this->navbarComponent($role),
            'active' => (string) ($hub['active'] ?? ''),
            'title' => (string) ($hub['title'] ?? 'Merkez'),
            'subtitle' => (string) ($hub['subtitle'] ?? ''),
            'links' => $links,
            'cards' => $cards,
            'showPremiumPackages' => $showPremiumPackages,
            'premiumPackages' => $premiumPackages,
        ]);
    }

    private function resolveUrl(array $item): string
    {
        if (! empty($item['url'])) {
            return (string) $item['url'];
        }

        $routeName = (string) ($item['route'] ?? '');
        if ($routeName !== '' && Route::has($routeName)) {
            return route($routeName, (array) ($item['params'] ?? []));
        }

        return '#';
    }

    private function navbarComponent(string $role): string
    {
        return match ($role) {
            'superadmin' => 'navbar-superadmin',
            'admin' => 'navbar-admin',
            default => 'navbar-acente',
        };
    }

    private function loadCharterPresetPackages(string $role): array
    {
        if (Schema::hasTable('charter_preset_packages')) {
            return CharterPresetPackage::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(function (CharterPresetPackage $package) use ($role): array {
                    return $this->mapPresetPackage([
                        'code' => $package->code,
                        'title' => $package->title,
                        'summary' => $package->summary,
                        'from_iata' => $package->from_iata,
                        'to_iata' => $package->to_iata,
                        'aircraft_label' => $package->aircraft_label,
                        'suggested_pax' => $package->suggested_pax,
                        'price' => $package->price,
                        'currency' => $package->currency,
                        'hero_image_url' => $package->hero_image_url,
                        'highlights_json' => (array) ($package->highlights_json ?? []),
                    ], $role);
                })
                ->values()
                ->all();
        }

        $raw = (string) SistemAyar::get(self::CHARTER_PRESET_SETTINGS_KEY, '[]');
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn ($item) => (array) $item)
            ->filter(fn (array $item): bool => (bool) ($item['is_active'] ?? false))
            ->sortBy(fn (array $item): string => sprintf(
                '%05d-%s',
                max(0, (int) ($item['sort_order'] ?? 100)),
                strtolower((string) ($item['code'] ?? ''))
            ))
            ->values()
            ->map(fn (array $item): array => $this->mapPresetPackage($item, $role))
            ->all();
    }

    private function mapPresetPackage(array $item, string $role): array
    {
        $highlights = collect((array) ($item['highlights_json'] ?? []))
            ->filter(fn ($highlight) => is_string($highlight) && trim($highlight) !== '')
            ->map(fn ($highlight) => trim((string) $highlight))
            ->take(4)
            ->values()
            ->all();

        $cta = $this->resolvePresetPackageCta($role);

        $heroImageUrl = trim((string) ($item['hero_image_url'] ?? ''));
        if ($heroImageUrl === '') {
            $heroImageUrl = self::CHARTER_DEFAULT_HERO_IMAGE;
        }

        $summary = trim((string) ($item['summary'] ?? ''));
        if ($summary === '') {
            $summary = 'Hizli teklif icin hazir charter paketi.';
        }
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            $title = 'Hazir Paket';
        }

        return [
            'code' => strtolower(trim((string) ($item['code'] ?? ''))),
            'title' => $title,
            'summary' => $summary,
            'from_iata' => strtoupper(trim((string) ($item['from_iata'] ?? '---'))),
            'to_iata' => strtoupper(trim((string) ($item['to_iata'] ?? '---'))),
            'aircraft_label' => trim((string) ($item['aircraft_label'] ?? 'Jet sinifi')),
            'pax' => max(1, (int) ($item['suggested_pax'] ?? 1)),
            'price' => (float) ($item['price'] ?? 0),
            'currency' => strtoupper(trim((string) ($item['currency'] ?? 'EUR'))),
            'hero_image_url' => $heroImageUrl,
            'highlights' => $highlights,
            'cta_label' => $cta['label'],
            'cta_url' => $cta['url'],
        ];
    }

    /**
     * @return array{label:string,url:string}
     */
    private function resolvePresetPackageCta(string $role): array
    {
        if ($role === 'superadmin') {
            return [
                'label' => 'Paket Yonetimine Git',
                'url' => route('superadmin.charter.packages.index'),
            ];
        }

        return [
            'label' => 'Rezervasyon Yap',
            'url' => route('acente.charter.create'),
        ];
    }

    private function catalog(): array
    {
        return [
            'acente' => [
                'talepler' => [
                    'active' => 'dashboard',
                    'title' => 'Talepler Merkezi',
                    'subtitle' => 'Tum talep akislarini tek noktadan yonetin ve hizli yeni talep olusturun.',
                    'links' => [
                        ['label' => 'Taleplerim', 'route' => 'acente.dashboard', 'icon' => 'fas fa-clipboard-list'],
                        ['label' => 'Klasik Talep', 'route' => 'acente.requests.create', 'icon' => 'fas fa-file-circle-plus'],
                    ],
                    'cards' => [
                        ['tag' => 'Operasyon', 'title' => 'Aktif Talep Panosu', 'description' => 'Acilmis tum talepleri tek listede izleyin.', 'route' => 'acente.dashboard', 'icon' => 'fas fa-list-check', 'theme' => 'theme-indigo'],
                        ['tag' => 'Hizli Baslangic', 'title' => 'Yeni Klasik Talep', 'description' => 'Dakikalar icinde yeni grup talebi acin.', 'route' => 'acente.requests.create', 'icon' => 'fas fa-plus', 'theme' => 'theme-orange'],
                        ['tag' => 'Urun Gecisi', 'title' => 'Air Charter Merkezi', 'description' => 'Charter akisini vitrin ekranindan yonetin.', 'route' => 'acente.charter.hub', 'icon' => 'fas fa-plane-departure', 'theme' => 'theme-cyan'],
                    ],
                ],
                'charter' => [
                    'active' => 'charter',
                    'title' => 'Air Charter Merkezi',
                    'subtitle' => 'Charter operasyonu, talep acilisi ve hazir paket akislarini tek sayfada toplayin.',
                    'links' => [
                        ['label' => 'Charter Talepleri', 'route' => 'acente.charter.index', 'icon' => 'fas fa-clipboard-list'],
                        ['label' => 'Yeni Charter Talebi', 'route' => 'acente.charter.create', 'icon' => 'fas fa-plus-circle'],
                    ],
                    'cards' => [
                        ['tag' => 'Hazir Paket', 'title' => 'Hazir Charter Paketleri', 'description' => 'Vitrin kartindan dogrudan charter talep akisini baslatin.', 'route' => 'acente.charter.create', 'icon' => 'fas fa-box-open', 'theme' => 'theme-indigo'],
                        ['tag' => 'Takip', 'title' => 'Charter Taleplerim', 'description' => 'Acilmis taleplerinizi durumlariyla birlikte gorun.', 'route' => 'acente.charter.index', 'icon' => 'fas fa-route', 'theme' => 'theme-purple'],
                        ['tag' => 'Yardim', 'title' => 'Talep Rehberi', 'description' => 'Formu doldurmadan once adimlari hizla inceleyin.', 'route' => 'acente.charter.advisory', 'icon' => 'fas fa-circle-info', 'theme' => 'theme-cyan'],
                    ],
                ],
                'leisure' => [
                    'active' => 'dinner-cruise',
                    'title' => 'Leisure Merkezi',
                    'subtitle' => 'Dinner Cruise ve Yacht Charter urunlerini vitrin kartlarla sunun.',
                    'links' => [
                        ['label' => 'Dinner Cruise', 'route' => 'acente.dinner-cruise.catalog', 'icon' => 'fas fa-utensils'],
                        ['label' => 'Yacht Charter', 'route' => 'acente.yacht-charter.catalog', 'icon' => 'fas fa-ship'],
                    ],
                    'cards' => [
                        ['tag' => 'Urun Vitrini', 'title' => 'Dinner Cruise Koleksiyonu', 'description' => 'Paketleri katalog gibi sunup talebe donusturun.', 'route' => 'acente.dinner-cruise.catalog', 'icon' => 'fas fa-water', 'theme' => 'theme-cyan'],
                        ['tag' => 'Premium', 'title' => 'Yacht Charter Koleksiyonu', 'description' => 'Yat deneyimlerini paket odakli sergileyin.', 'route' => 'acente.yacht-charter.catalog', 'icon' => 'fas fa-anchor', 'theme' => 'theme-indigo'],
                        ['tag' => 'Hizli Talep', 'title' => 'Yeni Dinner Talebi', 'description' => 'Urun vitrinden dogrudan yeni talep acin.', 'route' => 'acente.dinner-cruise.create', 'icon' => 'fas fa-plus', 'theme' => 'theme-orange'],
                    ],
                ],
                'finance' => [
                    'active' => 'finance',
                    'title' => 'Finans Merkezi',
                    'subtitle' => 'Odeme, dekont ve tahsilat akislarini tek merkezden izleyin.',
                    'links' => [
                        ['label' => 'Finans Paneli', 'route' => 'acente.finance.index', 'icon' => 'fas fa-wallet'],
                    ],
                    'cards' => [
                        ['tag' => 'Kontrol', 'title' => 'Finans Ozeti', 'description' => 'Toplam bakiye ve durumlari tek bakista gorun.', 'route' => 'acente.finance.index', 'icon' => 'fas fa-coins', 'theme' => 'theme-indigo'],
                        ['tag' => 'Dekont', 'title' => 'Dekont Bildirimi', 'description' => 'Odeme dekontlarini hizli sekilde iletin.', 'route' => 'acente.finance.index', 'icon' => 'fas fa-receipt', 'theme' => 'theme-cyan'],
                        ['tag' => 'Baglanti', 'title' => 'Charter Odeme Takibi', 'description' => 'Charter tarafindaki odeme sureclerini acin.', 'route' => 'acente.charter.index', 'icon' => 'fas fa-plane', 'theme' => 'theme-purple'],
                    ],
                ],
                'hesap' => [
                    'active' => 'hesap',
                    'title' => 'Hesap Merkezi',
                    'subtitle' => 'Profil ve hesap ayarlarinizi guvenli bir merkezde yonetin.',
                    'links' => [
                        ['label' => 'Profil', 'route' => 'acente.profil', 'icon' => 'fas fa-user-cog'],
                    ],
                    'cards' => [
                        ['tag' => 'Hesap', 'title' => 'Profil Bilgilerim', 'description' => 'Iletisim ve profil bilgilerinizi guncelleyin.', 'route' => 'acente.profil', 'icon' => 'fas fa-id-card', 'theme' => 'theme-indigo'],
                        ['tag' => 'Guvenlik', 'title' => 'Sifre Guvenligi', 'description' => 'Sifre islemlerini profil ekranindan yonetin.', 'route' => 'acente.profil', 'icon' => 'fas fa-shield-halved', 'theme' => 'theme-purple'],
                        ['tag' => 'Tercih', 'title' => 'Kisisel Tercihler', 'description' => 'Kullanici deneyimini size gore optimize edin.', 'route' => 'acente.profil', 'icon' => 'fas fa-sliders', 'theme' => 'theme-cyan'],
                    ],
                ],
            ],

            'admin' => [
                'talepler' => [
                    'active' => 'dashboard',
                    'title' => 'Talepler Merkezi',
                    'subtitle' => 'Panel, grup talepleri ve eski sistem arsivini tek merkezden yonetin.',
                    'links' => [
                        ['label' => 'Panel', 'route' => 'admin.dashboard', 'icon' => 'fas fa-home'],
                        ['label' => 'Grup Talepleri', 'route' => 'admin.requests.index', 'icon' => 'fas fa-clipboard-list'],
                        ['label' => 'Eski Sistem Arsiv', 'route' => 'admin.eski-sistem', 'icon' => 'fas fa-box-archive'],
                    ],
                    'cards' => [
                        ['tag' => 'Kontrol', 'title' => 'Operasyon Paneli', 'description' => 'Gunluk akis ve kritik metrikleri izleyin.', 'route' => 'admin.dashboard', 'icon' => 'fas fa-gauge-high', 'theme' => 'theme-indigo'],
                        ['tag' => 'Takip', 'title' => 'Tum Talepler', 'description' => 'Acilmis tum grup taleplerine hizli erisin.', 'route' => 'admin.requests.index', 'icon' => 'fas fa-list', 'theme' => 'theme-cyan'],
                        ['tag' => 'Arsiv', 'title' => 'Eski Sistem Karsilastirma', 'description' => 'Gecmis kayitlari yeni sistemle yan yana inceleyin.', 'route' => 'admin.eski-sistem', 'icon' => 'fas fa-folder-open', 'theme' => 'theme-orange'],
                    ],
                ],
                'charter' => [
                    'active' => 'charter',
                    'title' => 'Air Charter Merkezi',
                    'subtitle' => 'Admin charter operasyonunu tek vitrinde yonetin.',
                    'links' => [
                        ['label' => 'Charter Talepleri', 'route' => 'admin.charter.index', 'icon' => 'fas fa-plane-departure'],
                    ],
                    'cards' => [
                        ['tag' => 'Operasyon', 'title' => 'Charter Talep Havuzu', 'description' => 'Tum charter taleplerini durum bazli yonetin.', 'route' => 'admin.charter.index', 'icon' => 'fas fa-route', 'theme' => 'theme-indigo'],
                        ['tag' => 'Finans Baglantisi', 'title' => 'Charter Finans Takibi', 'description' => 'Odeme ve tahsilat akislarina hizla gecin.', 'route' => 'admin.finance.index', 'icon' => 'fas fa-credit-card', 'theme' => 'theme-purple'],
                        ['tag' => 'Yanit Akisi', 'title' => 'Hizli Yanit Entegrasyonu', 'description' => 'Talep mesajlarini hizli yanit akisiyla birlestirin.', 'route' => 'admin.quick-reply.index', 'icon' => 'fas fa-reply', 'theme' => 'theme-cyan'],
                    ],
                ],
                'leisure' => [
                    'active' => 'dinner-cruise',
                    'title' => 'Leisure Merkezi',
                    'subtitle' => 'Dinner Cruise ve Yacht Charter operasyonunu tek vitrine toplayin.',
                    'links' => [
                        ['label' => 'Dinner Cruise', 'route' => 'admin.dinner-cruise.index', 'icon' => 'fas fa-utensils'],
                        ['label' => 'Yacht Charter', 'route' => 'admin.yacht-charter.index', 'icon' => 'fas fa-ship'],
                    ],
                    'cards' => [
                        ['tag' => 'Operasyon', 'title' => 'Dinner Cruise Talepleri', 'description' => 'Dinner operasyonunu detayli yonetin.', 'route' => 'admin.dinner-cruise.index', 'icon' => 'fas fa-water', 'theme' => 'theme-cyan'],
                        ['tag' => 'Operasyon', 'title' => 'Yacht Charter Talepleri', 'description' => 'Yacht sureclerini tek panelden yonetin.', 'route' => 'admin.yacht-charter.index', 'icon' => 'fas fa-anchor', 'theme' => 'theme-indigo'],
                        ['tag' => 'Finans', 'title' => 'Leisure Finans Baglantisi', 'description' => 'Leisure odemelerini finans paneline tasiyin.', 'route' => 'admin.finance.index', 'icon' => 'fas fa-wallet', 'theme' => 'theme-purple'],
                    ],
                ],
                'finance' => [
                    'active' => 'finance',
                    'title' => 'Finans Merkezi',
                    'subtitle' => 'Finans islemleri, dekont kontrolu ve planlar tek merkezde.',
                    'links' => [
                        ['label' => 'Finans Paneli', 'route' => 'admin.finance.index', 'icon' => 'fas fa-coins'],
                    ],
                    'cards' => [
                        ['tag' => 'Ozet', 'title' => 'Finans Kontrol Paneli', 'description' => 'Genel finans durumunu anlik takip edin.', 'route' => 'admin.finance.index', 'icon' => 'fas fa-chart-line', 'theme' => 'theme-indigo'],
                        ['tag' => 'Dekont', 'title' => 'Dekont Yonetimi', 'description' => 'Gelen dekontlari hizli sekilde dogrulayin.', 'route' => 'admin.finance.receipts.index', 'icon' => 'fas fa-file-invoice-dollar', 'theme' => 'theme-cyan'],
                        ['tag' => 'Baglanti', 'title' => 'Charter Finans Kapi', 'description' => 'Charter odeme akisina finans merkezinden erisin.', 'route' => 'admin.charter.index', 'icon' => 'fas fa-plane', 'theme' => 'theme-purple'],
                    ],
                ],
                'iletisim' => [
                    'active' => 'quick-reply',
                    'title' => 'Iletisim Merkezi',
                    'subtitle' => 'Hizli yanit ve duyuru sureclerini tek noktadan yonetin.',
                    'links' => [
                        ['label' => 'Hizli Yanit', 'route' => 'admin.quick-reply.index', 'icon' => 'fas fa-reply'],
                        ['label' => 'Duyuru', 'route' => 'admin.broadcast.create', 'icon' => 'fas fa-bullhorn'],
                    ],
                    'cards' => [
                        ['tag' => 'Yanit', 'title' => 'Hizli Yanit Merkezi', 'description' => 'Talep mesajlarini AI destekli hizli cevaplayin.', 'route' => 'admin.quick-reply.index', 'icon' => 'fas fa-paper-plane', 'theme' => 'theme-indigo'],
                        ['tag' => 'Duyuru', 'title' => 'Yeni Duyuru Olustur', 'description' => 'Acentelere toplu duyuru akislarini baslatin.', 'route' => 'admin.broadcast.create', 'icon' => 'fas fa-bullhorn', 'theme' => 'theme-orange'],
                        ['tag' => 'Operasyon', 'title' => 'Talepten Iletisime Gecis', 'description' => 'Talep listesi ile iletisim ekranini birlikte kullanin.', 'route' => 'admin.requests.index', 'icon' => 'fas fa-link', 'theme' => 'theme-cyan'],
                    ],
                ],
                'hesap' => [
                    'active' => 'hesap',
                    'title' => 'Hesap Merkezi',
                    'subtitle' => 'Admin profil ve guvenlik ayarlarini tek ekrandan yonetin.',
                    'links' => [
                        ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'fas fa-user-cog'],
                    ],
                    'cards' => [
                        ['tag' => 'Profil', 'title' => 'Profil Ayarlari', 'description' => 'Kisisel bilgilerinizi guncel tutun.', 'route' => 'profile.edit', 'icon' => 'fas fa-id-card', 'theme' => 'theme-indigo'],
                        ['tag' => 'Guvenlik', 'title' => 'Sifre ve Guvenlik', 'description' => 'Hesap guvenligini duzenli kontrol edin.', 'route' => 'profile.edit', 'icon' => 'fas fa-shield-halved', 'theme' => 'theme-purple'],
                        ['tag' => 'Tercih', 'title' => 'Tema ve Kullanici Tercihleri', 'description' => 'Arayuz tercihlerinizi profil uzerinden yonetin.', 'route' => 'profile.edit', 'icon' => 'fas fa-sliders', 'theme' => 'theme-cyan'],
                    ],
                ],
            ],

            'superadmin' => [
                'yonetim' => [
                    'active' => 'dashboard',
                    'title' => 'Yonetim Merkezi',
                    'subtitle' => 'Panel, acente yonetimi ve kampanya akislarini tek merkezde toplayin.',
                    'links' => [
                        ['label' => 'Panel', 'route' => 'superadmin.dashboard', 'icon' => 'fas fa-home'],
                        ['label' => 'Acenteler', 'route' => 'superadmin.acenteler', 'icon' => 'fas fa-building'],
                        ['label' => 'TURSAB Kampanyasi', 'route' => 'superadmin.tursab.kampanya', 'icon' => 'fas fa-envelope-open-text'],
                    ],
                    'cards' => [
                        ['tag' => 'Kontrol', 'title' => 'Sistem Kontrol Paneli', 'description' => 'Tum operasyon metriklerini tek bakista gorun.', 'route' => 'superadmin.dashboard', 'icon' => 'fas fa-gauge-high', 'theme' => 'theme-indigo'],
                        ['tag' => 'Yonetim', 'title' => 'Acenta Yonetimi', 'description' => 'Acente hesaplari ve rollerini yonetin.', 'route' => 'superadmin.acenteler', 'icon' => 'fas fa-users-gear', 'theme' => 'theme-cyan'],
                        ['tag' => 'Kampanya', 'title' => 'TURSAB Davet Kampanyasi', 'description' => 'Yeni acente kazanimi icin kampanya akislarini yonetin.', 'route' => 'superadmin.tursab.kampanya', 'icon' => 'fas fa-paper-plane', 'theme' => 'theme-orange'],
                    ],
                ],
                'charter' => [
                    'active' => 'charter',
                    'title' => 'Air Charter Merkezi',
                    'subtitle' => 'Superadmin charter operasyonu, paket yonetimi ve RFQ tedarik yapisi.',
                    'links' => [
                        ['label' => 'Charter Talepleri', 'route' => 'superadmin.charter.index', 'icon' => 'fas fa-plane-departure'],
                        ['label' => 'Hazir Paketler', 'route' => 'superadmin.charter.packages.index', 'icon' => 'fas fa-box-open'],
                        ['label' => 'RFQ Tedarikciler', 'route' => 'superadmin.charter.rfq-suppliers.index', 'icon' => 'fas fa-paper-plane'],
                    ],
                    'cards' => [
                        ['tag' => 'Operasyon', 'title' => 'Charter Talep Havuzu', 'description' => 'Tum charter taleplerini ust seviyeden yonetin.', 'route' => 'superadmin.charter.index', 'icon' => 'fas fa-route', 'theme' => 'theme-indigo'],
                        ['tag' => 'Urun', 'title' => 'Hazir Paket Yonetimi', 'description' => 'Preset paketleri vitrin ve operasyon icin duzenleyin.', 'route' => 'superadmin.charter.packages.index', 'icon' => 'fas fa-boxes-stacked', 'theme' => 'theme-purple'],
                        ['tag' => 'Tedarik', 'title' => 'RFQ Tedarik Agi', 'description' => 'Tedarikci havuzunu ve limitlerini optimize edin.', 'route' => 'superadmin.charter.rfq-suppliers.index', 'icon' => 'fas fa-network-wired', 'theme' => 'theme-cyan'],
                    ],
                ],
                'leisure' => [
                    'active' => 'dinner-cruise',
                    'title' => 'Leisure Merkezi',
                    'subtitle' => 'Leisure operasyonu ve urun ayarlari tek merkezde.',
                    'links' => [
                        ['label' => 'Dinner Cruise', 'route' => 'superadmin.dinner-cruise.index', 'icon' => 'fas fa-utensils'],
                        ['label' => 'Yacht Charter', 'route' => 'superadmin.yacht-charter.index', 'icon' => 'fas fa-ship'],
                        ['label' => 'Leisure Ayarlari', 'route' => 'superadmin.leisure.settings.index', 'icon' => 'fas fa-sliders'],
                    ],
                    'cards' => [
                        ['tag' => 'Operasyon', 'title' => 'Dinner Cruise Operasyon', 'description' => 'Dinner taleplerini operasyon adimlariyla yonetin.', 'route' => 'superadmin.dinner-cruise.index', 'icon' => 'fas fa-water', 'theme' => 'theme-cyan'],
                        ['tag' => 'Operasyon', 'title' => 'Yacht Charter Operasyon', 'description' => 'Yacht sureclerini tek panelden optimize edin.', 'route' => 'superadmin.yacht-charter.index', 'icon' => 'fas fa-anchor', 'theme' => 'theme-indigo'],
                        ['tag' => 'Ayar', 'title' => 'Leisure Product Settings', 'description' => 'Paket, ekstra ve medya varliklarini duzenleyin.', 'route' => 'superadmin.leisure.settings.index', 'icon' => 'fas fa-screwdriver-wrench', 'theme' => 'theme-purple'],
                    ],
                ],
                'finance' => [
                    'active' => 'finance',
                    'title' => 'Finans Merkezi',
                    'subtitle' => 'Superadmin finans kontrolu, dekont ve odeme planlarini birlestirin.',
                    'links' => [
                        ['label' => 'Finans Paneli', 'route' => 'superadmin.finance.index', 'icon' => 'fas fa-wallet'],
                        ['label' => 'Dekontlar', 'route' => 'superadmin.finance.receipts.index', 'icon' => 'fas fa-receipt'],
                    ],
                    'cards' => [
                        ['tag' => 'Ozet', 'title' => 'Finans Kontrol Paneli', 'description' => 'Butun finans metriklerini anlik takip edin.', 'route' => 'superadmin.finance.index', 'icon' => 'fas fa-chart-pie', 'theme' => 'theme-indigo'],
                        ['tag' => 'Dekont', 'title' => 'Dekont Yonetimi', 'description' => 'Tum dekont surecini tek merkezden denetleyin.', 'route' => 'superadmin.finance.receipts.index', 'icon' => 'fas fa-file-invoice', 'theme' => 'theme-cyan'],
                        ['tag' => 'Planlama', 'title' => 'Iade ve Odeme Planlari', 'description' => 'Plan, iade ve manuel kayit akislarini yonetin.', 'route' => 'superadmin.finance.index', 'icon' => 'fas fa-calendar-check', 'theme' => 'theme-purple'],
                    ],
                ],
                'iletisim' => [
                    'active' => 'quick-reply',
                    'title' => 'Iletisim Merkezi',
                    'subtitle' => 'Hizli yanit, broadcast ve SMS raporlarini tek vitrinde birlestirin.',
                    'links' => [
                        ['label' => 'Hizli Yanit', 'route' => 'superadmin.quick-reply.index', 'icon' => 'fas fa-reply'],
                        ['label' => 'Broadcast Gecmisi', 'route' => 'superadmin.broadcast.gecmisi', 'icon' => 'fas fa-bullhorn'],
                        ['label' => 'SMS Raporlar', 'route' => 'superadmin.sms.raporlar', 'icon' => 'fas fa-chart-line'],
                    ],
                    'cards' => [
                        ['tag' => 'Yanit', 'title' => 'Hizli Yanit Merkezi', 'description' => 'Mesaj akislarini merkezi sekilde yonetin.', 'route' => 'superadmin.quick-reply.index', 'icon' => 'fas fa-paper-plane', 'theme' => 'theme-indigo'],
                        ['tag' => 'Broadcast', 'title' => 'Broadcast Gecmisi', 'description' => 'Toplu duyuru gecmisini denetleyin.', 'route' => 'superadmin.broadcast.gecmisi', 'icon' => 'fas fa-bullhorn', 'theme' => 'theme-orange'],
                        ['tag' => 'SMS', 'title' => 'SMS Rapor Merkezi', 'description' => 'Teslimat ve hata raporlarini tek yerden gorun.', 'route' => 'superadmin.sms.raporlar', 'icon' => 'fas fa-comment-sms', 'theme' => 'theme-cyan'],
                    ],
                ],
                'sistem' => [
                    'active' => 'site-ayarlar',
                    'title' => 'Sistem Merkezi',
                    'subtitle' => 'Site ayarlari, SMS konfigurasyonu ve AI kutlama ayarlarini merkezden yonetin.',
                    'links' => [
                        ['label' => 'Site Ayarlari', 'route' => 'superadmin.site.ayarlar', 'icon' => 'fas fa-cogs'],
                        ['label' => 'SMS Ayarlari', 'route' => 'superadmin.sms.ayarlar', 'icon' => 'fas fa-sms'],
                        ['label' => 'AI Kutlama', 'route' => 'superadmin.site.ayarlar', 'params' => ['sekme' => 'ai'], 'icon' => 'fas fa-wand-magic-sparkles'],
                    ],
                    'cards' => [
                        ['tag' => 'Konfigurasyon', 'title' => 'Site Ayarlari Merkezi', 'description' => 'Genel sistem davranislarini duzenleyin.', 'route' => 'superadmin.site.ayarlar', 'icon' => 'fas fa-sliders', 'theme' => 'theme-indigo'],
                        ['tag' => 'SMS', 'title' => 'SMS Ayar Konsolu', 'description' => 'Provider ve gonderim ayarlarini yonetin.', 'route' => 'superadmin.sms.ayarlar', 'icon' => 'fas fa-sms', 'theme' => 'theme-cyan'],
                        ['tag' => 'AI', 'title' => 'AI Kutlama Ayarlari', 'description' => 'AI kampanya ve vitrin akisini yonetin.', 'route' => 'superadmin.site.ayarlar', 'params' => ['sekme' => 'ai'], 'icon' => 'fas fa-wand-magic-sparkles', 'theme' => 'theme-purple'],
                    ],
                ],
            ],
        ];
    }
}
