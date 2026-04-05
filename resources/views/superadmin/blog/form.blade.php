<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@include('admin.partials.theme-styles')
<title>{{ isset($blog) ? 'Yazıyı Düzenle' : 'Yeni Yazı' }} — Blog</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
#icerikEditor { min-height: 420px; font-family: monospace; font-size: .88rem; }
.char-count { font-size: .75rem; color: #6c757d; }
</style>
</head>
<body>
<x-navbar-superadmin active="blog" />
<div class="container py-4" style="max-width:900px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('superadmin.blog.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Geri
        </a>
        <h4 class="mb-0 fw-bold">
            {{ isset($blog) ? 'Yazıyı Düzenle' : 'Yeni Blog Yazısı' }}
        </h4>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ isset($blog) ? route('superadmin.blog.update', $blog) : route('superadmin.blog.store') }}">
        @csrf
        @if(isset($blog)) @method('PUT') @endif

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Temel Bilgiler</div>
            <div class="card-body row g-3">

                <div class="col-12">
                    <label class="form-label fw-semibold">Başlık <span class="text-danger">*</span></label>
                    <input type="text" name="baslik" class="form-control"
                           value="{{ old('baslik', $blog->baslik ?? '') }}" required
                           oninput="otomatikSlug(this.value)">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="kategori_id" class="form-select">
                        <option value="">— Kategori seçin —</option>
                        @foreach($kategoriler as $k)
                        <option value="{{ $k->id }}"
                            {{ old('kategori_id', $blog->kategori_id ?? '') == $k->id ? 'selected' : '' }}>
                            {{ $k->ad }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Durum</label>
                    <select name="durum" class="form-select">
                        <option value="taslak" {{ old('durum', $blog->durum ?? 'taslak') === 'taslak' ? 'selected' : '' }}>Taslak</option>
                        <option value="yayinda" {{ old('durum', $blog->durum ?? '') === 'yayinda' ? 'selected' : '' }}>Yayında</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Özet <span class="text-danger">*</span> <span class="char-count" id="ozetCount"></span></label>
                    <textarea name="ozet" class="form-control" rows="3" maxlength="500"
                              oninput="charCount(this,'ozetCount',500)" required>{{ old('ozet', $blog->ozet ?? '') }}</textarea>
                    <div class="form-text">Liste sayfasında ve sosyal paylaşımlarda görünür. Max 500 karakter.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Kapak Görseli URL</label>
                    <input type="url" name="kapak_gorseli" class="form-control"
                           value="{{ old('kapak_gorseli', $blog->kapak_gorseli ?? '') }}"
                           placeholder="https://...">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Yayın Tarihi</label>
                    <input type="datetime-local" name="yayinlanma_tarihi" class="form-control"
                           value="{{ old('yayinlanma_tarihi', isset($blog->yayinlanma_tarihi) ? $blog->yayinlanma_tarihi->format('Y-m-d\TH:i') : '') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Yazar</label>
                    <input type="text" name="yazar" class="form-control"
                           value="{{ old('yazar', $blog->yazar ?? 'GrupTalepleri Editör') }}">
                </div>

            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">İçerik <span class="text-danger">*</span></div>
            <div class="card-body p-0">
                <textarea name="icerik" id="icerikEditor" class="form-control border-0 rounded-0"
                          required>{{ old('icerik', $blog->icerik ?? '') }}</textarea>
            </div>
            <div class="card-footer">
                <small class="text-muted">HTML desteklenir. &lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt; vb. kullanabilirsiniz.</small>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">SEO (isteğe bağlı)</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label">Meta Başlık <span class="char-count" id="metaBaslikCount"></span></label>
                    <input type="text" name="meta_baslik" class="form-control" maxlength="255"
                           oninput="charCount(this,'metaBaslikCount',60)"
                           value="{{ old('meta_baslik', $blog->meta_baslik ?? '') }}"
                           placeholder="Boş bırakılırsa başlık kullanılır">
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Açıklama <span class="char-count" id="metaAciklamaCount"></span></label>
                    <textarea name="meta_aciklama" class="form-control" rows="2" maxlength="320"
                              oninput="charCount(this,'metaAciklamaCount',160)"
                              placeholder="Boş bırakılırsa özet kullanılır">{{ old('meta_aciklama', $blog->meta_aciklama ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-1"></i>{{ isset($blog) ? 'Güncelle' : 'Yayımla' }}
            </button>
            <a href="{{ route('superadmin.blog.index') }}" class="btn btn-outline-secondary">İptal</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function charCount(el, id, max) {
    var len = el.value.length;
    var span = document.getElementById(id);
    span.textContent = len + '/' + max;
    span.style.color = len > max ? '#dc3545' : '#6c757d';
}
function otomatikSlug(v) {}
// Init counts
document.addEventListener('DOMContentLoaded', function() {
    ['ozet','meta_baslik','meta_aciklama'].forEach(function(n) {
        var el = document.querySelector('[name="'+n+'"]');
        if (!el) return;
        var map = {ozet:'ozetCount',meta_baslik:'metaBaslikCount',meta_aciklama:'metaAciklamaCount'};
        var max = {ozet:500,meta_baslik:60,meta_aciklama:160};
        charCount(el, map[n], max[n]);
    });
});
</script>
</body>
</html>
