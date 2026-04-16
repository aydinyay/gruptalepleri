# GrupTalepleri — Claude Talimatları

## KESİN KURAL: Her işlem sonrası etkilenen yerleri tara

Her talimat veya istek tamamlandıktan sonra, commit yapmadan önce **mutlaka** şunu sor ve uygula:

> "Bu değişiklikten etkilenen başka yer var mı?" → Tara, bul, düzelt.

### Nasıl uygulanır:
- Düzeltilen pattern'ı (fonksiyon adı, değişken, yöntem) codebase genelinde grep ile ara
- Admin / Acente / Superadmin — üç paneli kontrol et
- Controller, Blade, Command, Service — hepsine bak
- Aynı hatayı taşıyan başka dosya varsa aynı PR'da düzelt

### Örnek:
`auth()->user()` → `acenteActor()` düzeltmesi yapıldığında:
```
grep -r "auth()->user()" app/Http/Controllers/Acente/
```
tüm sonuçları kontrol et, aynı anda düzelt.

---

## Deployment Mimarisi (KESİNLİKLE BİL)

### Nasıl çalışır:
```
local commit → git push origin main (127.0.0.1:51272 proxy) → gerçek GitHub main → Actions tetiklenir → gitfix.php → cPanel production
```

### Kritik noktalar:
- **Deploy mekanizması**: `.github/workflows/deploy.yml` → `git diff --name-only HEAD~1 HEAD` ile değişen dosyaları bulur → base64 ile `https://gruptalepleri.com/gitfix.php` endpoint'ine curl ile gönderir
- **Cache temizleme**: Actions sonu `curl "https://gruptalepleri.com/gitfix.php?t=grt2026fix"` ile cache'i siler
- **Proxy**: `http://local_proxy@127.0.0.1:51272/git/aydinyay/gruptalepleri` — gerçek GitHub'a senkronize eder, **main branch için çalışır**
- **Önemli**: Actions sadece `main` branch'e push'ta tetiklenir. Feature branch commit'leri deploy OLMAZ, önce main'e merge et!
- **patch-navbar / deploy-run.php GEREKSİZ**: Production sunucusu outbound HTTP yapmak zorunda değil. GitHub Actions halleder. patch-navbar'ı kullanma!
- **patch-dc.php GEREKSİZ**: Aynı sebep. Silindi.
- **Doğrulama**: GitHub Actions sekmesinden çalıştığını gör, `CACHE_CLEARED` yanıtı production'ın ayakta olduğunu gösterir

### Deployment sonrası teyit:
1. GitHub → Actions → son run yeşil mi?
2. `curl https://gruptalepleri.com/gitfix.php?t=grt2026fix` → `CACHE_CLEARED` dönmeli

---

## ZORUNLU KURAL: Paylaşımlı Hosting / cPanel

**Kullanıcı paylaşımlı cPanel hosting kullanıyor. SSH YOK. Terminal YOK.**

### Kesinlikle yapılmayacaklar:
- Kullanıcıya `php artisan migrate` komutu söyleme
- Kullanıcıya SSH veya terminal açmasını söyleme
- Kullanıcıya GitHub UI'dan "Pull Request merge et" söyleme
- Kullanıcıya cPanel'den terminal/komut çalıştırmasını söyleme

### Migration nasıl çalıştırılır:
Branch main'e merge edilip deploy bittikten sonra tarayıcıdan şu URL açılır:
```
https://gruptalepleri.com/gitfix.php?t=grt2026fix&action=migrate
```
Bu URL `php artisan migrate --force` komutunu çalıştırır ve sonucu ekrana yazar.

### Seeder nasıl çalıştırılır:
```
https://gruptalepleri.com/gitfix.php?t=grt2026fix&action=seed&class=SeederSınıfAdı
```
Örnek:
```
https://gruptalepleri.com/gitfix.php?t=grt2026fix&action=seed&class=BestawayB2cApprovalSeeder
```
**Güvenlik:** Sadece `gitfix.php` içindeki `$allowedSeeders` listesindeki seeder'lar çalışır.
Yeni seeder eklenecekse önce `$allowedSeeders` listesine eklenmeli, sonra deploy edilmeli.

### B2C Acente Onayı (Seeder'sız yol):
Superadmin paneli → `/superadmin/b2c/acenteler` → **"Direkt Ekle & Onayla"** butonu.
Başvuru beklemeden herhangi bir onaylı transfer tedarikçisini B2C'ye ekler.

### Branch merge nasıl yapılır:
Claude Code terminal üzerinden `git merge` ile local'de merge yapıp push'lar:
```bash
git checkout main
git merge <feature-branch>
git push origin main
```

---

## Blade Yazım Kuralları

### @php shorthand YASAK
`@php($var = expr)` sözdizimi production'da yanlış derleniyor (`<?php($var = expr)` üretiyor — boşluksuz, kapanış yok). **ASLA kullanma.**

**Doğru:** `@php $var = expr; @endphp`  
**Yanlış:** `@php($var = expr)`

### @media CSS yasak (CSS içinde)
`<style>` bloğu içindeki `@media` Blade direktifi olarak işleniyor. `@@media` kullan.

### Her Blade değişikliğinde kontrol et
Yeni Blade kodu yazıldığında codebase'de `@php(` ve raw `@media` tara:
```
grep -rn "@php(" resources/views/
grep -rn "^@media\|[^@]@media" resources/views/
```

---

## Proje Kuralları

- Acente controller'larında `auth()->user()` değil `$this->acenteActor()` kullan
- Eski sistem sayfalarına (finans arşivi, TÜRSAB, muhasebe) dokunma
- Her deployment öncesi onay al (local değişiklikler için onay gerekmez)

---

## Sistem Mimarisi — Kim Ne Yapıyor?

### Ürün/Hizmet Ekleme Yetkisi

| Modül | Kim ekler? | Acente rolü |
|---|---|---|
| Leisure paket şablonları | **Sadece Superadmin** (`/leisure-ayarlar`) | Hazır şablonlardan talep oluşturur |
| Transfer fiyat kuralları | **Sadece Superadmin** (`/superadmin/transfer/operasyon`) | Fiyatları görebilir, rezervasyon yapar |
| B2C Katalog (CatalogItem) | **Sadece Superadmin** (`/superadmin/b2c/katalog`) | Erişimi yok |
| Dinner Cruise / Yacht / Tur | **Sadece Superadmin** | Talep oluşturur, teklif bekler |

**Kural:** Sistemde hiçbir ürün/hizmet şablonu acente tarafından oluşturulamaz. Acenteler yalnızca hazır ürünleri kullanarak talep/rezervasyon yapar.

### İki Kanal Yapısı (Hedef Mimari)

```
Ürün/Hizmet (tek kaynak)
    ├── B2B: gruptalepleri.com/acente/...   → her zaman erişilebilir
    └── B2C: gruprezervasyonlari.com        → superadmin "Yayına Al" seçince aktif
```

- `catalog_items.is_published = true` → B2C vitrinde görünür
- `catalog_items.reference_type + reference_id` alanları mevcut: leisure/transfer kayıtlarına köprü için tasarlanmış ama henüz kullanılmıyor
- Hedef: Leisure/Transfer ürünleri otomatik CatalogItem oluşturulsun, superadmin tek dashboard'dan yönetsin

### Mevcut Tablolar ve Sahiplikleri

- `leisure_package_templates` — sistem geneli, sahibi yok (superadmin yönetir)
- `leisure_requests` — `user_id` = talep açan acente
- `catalog_items` — `owner_type` (platform/supplier), superadmin yönetir
- `transfer_pricing_rules` — tedarikçi bazlı, superadmin onaylar
- `b2c_agency_subscriptions` — onaylı B2C acenteleri
