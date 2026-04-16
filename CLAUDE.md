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

### Branch merge nasıl yapılır:
Claude Code terminal üzerinden `git merge` ile local'de merge yapıp push'lar:
```bash
git checkout main
git merge <feature-branch>
git push origin main
```

---

## Proje Kuralları

- Acente controller'larında `auth()->user()` değil `$this->acenteActor()` kullan
- Eski sistem sayfalarına (finans arşivi, TÜRSAB, muhasebe) dokunma
- Her deployment öncesi onay al (local değişiklikler için onay gerekmez)
