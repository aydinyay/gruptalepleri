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

## Proje Kuralları

- Acente controller'larında `auth()->user()` değil `$this->acenteActor()` kullan
- Eski sistem sayfalarına (finans arşivi, TÜRSAB, muhasebe) dokunma
- Her deployment öncesi onay al (local değişiklikler için onay gerekmez)
