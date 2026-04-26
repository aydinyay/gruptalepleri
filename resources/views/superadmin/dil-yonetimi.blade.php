<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yabancı Diller — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
.tool-card { background:#fff; border-radius:12px; border:1px solid #e9ecef; padding:1.5rem; height:100%; transition:box-shadow .2s; }
.tool-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.08); }
.tool-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:1rem; }
.lang-badge { display:inline-block; background:#e9ecef; border-radius:6px; padding:3px 8px; font-size:.72rem; font-weight:600; margin:2px; }
.step-num { width:24px; height:24px; background:#0f2544; color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; flex-shrink:0; }
</style>
</head>
<body>

<x-navbar-superadmin active="dil-yonetimi" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-globe me-2" style="color:#e8a020;"></i>Yabancı Diller & Çeviri Yönetimi</h5>
        <p>Katalog, blog ve arayüz metinlerinin çok dil yönetimi</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- Desteklenen diller --}}
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4" style="border-radius:10px;">
        <i class="fas fa-info-circle fa-lg"></i>
        <div>
            <strong>Desteklenen 8 dil:</strong>
            <span class="lang-badge">🇹🇷 Türkçe</span>
            <span class="lang-badge">🇬🇧 İngilizce</span>
            <span class="lang-badge">🇸🇦 Arapça</span>
            <span class="lang-badge">🇷🇺 Rusça</span>
            <span class="lang-badge">🇩🇪 Almanca</span>
            <span class="lang-badge">🇫🇷 Fransızca</span>
            <span class="lang-badge">🇮🇷 Farsça</span>
            <span class="lang-badge">🇨🇳 Çince</span>
            &nbsp;— Gemini AI ile otomatik çeviri yapılır.
        </div>
    </div>

    {{-- Yeni ürün/blog ekleyince ne yapacaksın --}}
    <div class="card mb-4" style="border-radius:12px; border:2px solid #e8a020;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb me-2" style="color:#e8a020;"></i>Yeni Ürün veya Blog Yazısı Ekledikten Sonra</h6>
            <div class="d-flex flex-wrap gap-3">
                <div class="d-flex align-items-start gap-2">
                    <span class="step-num mt-1">1</span>
                    <span class="text-muted" style="font-size:.85rem;">Superadmin'den ürünü / blog yazısını Türkçe olarak ekle</span>
                </div>
                <i class="fas fa-arrow-right text-muted mt-1"></i>
                <div class="d-flex align-items-start gap-2">
                    <span class="step-num mt-1">2</span>
                    <span class="text-muted" style="font-size:.85rem;">Aşağıdan ilgili çeviri sayfasına git</span>
                </div>
                <i class="fas fa-arrow-right text-muted mt-1"></i>
                <div class="d-flex align-items-start gap-2">
                    <span class="step-num mt-1">3</span>
                    <span class="text-muted" style="font-size:.85rem;"><strong>"Çevrilmemişleri Çevir"</strong> butonuna bas — sistem sadece yeniyi bulur, gerisini atlar</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Katalog Çevirisi --}}
        <div class="col-md-4">
            <div class="tool-card">
                <div class="tool-icon" style="background:#e3f2fd;">
                    <i class="fas fa-boxes-stacked" style="color:#1976d2;"></i>
                </div>
                <h6 class="fw-bold mb-1">B2C Katalog Çevirisi</h6>
                <p class="text-muted" style="font-size:.83rem;">
                    Tüm B2C vitrin ürünlerini (başlık, açıklama, meta) 7 dile çevirir.
                    Yeni ürün ekledikten sonra "Çevrilmemişleri Çevir" yeterli.
                </p>
                <div class="d-grid mt-3">
                    <a href="{{ route('superadmin.b2c.catalog.bulk-translate') }}" class="btn btn-primary">
                        <i class="fas fa-language me-1"></i> Katalog Çevirisine Git
                    </a>
                </div>
            </div>
        </div>

        {{-- Blog Çevirisi --}}
        <div class="col-md-4">
            <div class="tool-card">
                <div class="tool-icon" style="background:#e8f5e9;">
                    <i class="fas fa-newspaper" style="color:#388e3c;"></i>
                </div>
                <h6 class="fw-bold mb-1">Blog Yazıları Çevirisi</h6>
                <p class="text-muted" style="font-size:.83rem;">
                    Yayında tüm blog yazılarını (başlık, özet, içerik, meta) 7 dile çevirir.
                    Yeni yazı ekledikten sonra "Çevrilmemişleri Çevir" yeterli.
                </p>
                <div class="d-grid mt-3">
                    <a href="{{ route('superadmin.blog.translate') }}" class="btn btn-success">
                        <i class="fas fa-language me-1"></i> Blog Çevirisine Git
                    </a>
                </div>
            </div>
        </div>

        {{-- UI Anahtar Çevirisi --}}
        <div class="col-md-4">
            <div class="tool-card">
                <div class="tool-icon" style="background:#fff3e0;">
                    <i class="fas fa-keyboard" style="color:#f57c00;"></i>
                </div>
                <h6 class="fw-bold mb-1">Arayüz Metinleri Çevirisi</h6>
                <p class="text-muted" style="font-size:.83rem;">
                    Butonlar, etiketler, navigasyon gibi sabit arayüz metinlerini çevirir.
                    Yalnızca yeni dil eklendiğinde veya yeni anahtar eklendiğinde kullan.
                </p>
                <div class="d-grid mt-3">
                    <button class="btn btn-warning" onclick="runUiTranslate(this)">
                        <i class="fas fa-sync-alt me-1"></i> UI Metinlerini Çevir (Tüm Diller)
                    </button>
                </div>
                <div id="uiTranslateResult" class="mt-2" style="font-size:.8rem;display:none;"></div>
            </div>
        </div>

    </div>

    {{-- Dil Önizleme Linkleri --}}
    <div class="card mt-4" style="border-radius:12px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="fas fa-eye me-2 text-muted"></i>Siteyi Farklı Dillerde Önizle</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach([
                    'tr' => '🇹🇷 Türkçe',
                    'en' => '🇬🇧 İngilizce',
                    'ar' => '🇸🇦 Arapça',
                    'ru' => '🇷🇺 Rusça',
                    'de' => '🇩🇪 Almanca',
                    'fr' => '🇫🇷 Fransızca',
                    'fa' => '🇮🇷 Farsça',
                    'zh' => '🇨🇳 Çince',
                ] as $lc => $label)
                <a href="https://gruprezervasyonlari.com/{{ $lc === 'tr' ? '' : $lc }}"
                   target="_blank"
                   class="btn btn-sm btn-outline-secondary">
                    {{ $label }} <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem;"></i>
                </a>
                @endforeach
            </div>
        </div>
    </div>

</div>

<script>
async function runUiTranslate(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Çevriliyor...';
    const res = document.getElementById('uiTranslateResult');
    res.style.display = 'block';
    res.className = 'mt-2 text-muted';
    res.textContent = 'İstek gönderildi, sunucu işliyor...';
    try {
        const r = await fetch('/gitfix.php?t=grt2026fix&action=translate&lang=all');
        const text = await r.text();
        res.className = 'mt-2 text-success';
        res.textContent = '✓ Tamamlandı. ' + (text.length > 120 ? text.slice(0, 120) + '…' : text);
    } catch(e) {
        res.className = 'mt-2 text-danger';
        res.textContent = '✗ Hata: ' + e.message;
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> UI Metinlerini Çevir (Tüm Diller)';
}
</script>

</body>
</html>
