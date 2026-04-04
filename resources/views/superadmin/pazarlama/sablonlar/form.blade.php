<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $sablon ? 'Şablon Düzenle' : 'Yeni Şablon' }} — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
#htmlEditor { width:100%; height:500px; font-family:monospace; font-size:0.82rem; border:1px solid #ced4da; border-radius:4px; padding:8px; }
.var-badge { cursor:pointer; font-size:0.75rem; }
</style>
</head>
<body>
<x-navbar-superadmin active="sablonlar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-file-code me-2" style="color:#ffc107;"></i>{{ $sablon ? 'Şablon Düzenle' : 'Yeni Şablon' }}</h5>
            <a href="{{ route('superadmin.sablonlar.index') }}" class="btn btn-sm btn-outline-light">← Geri</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST"
          action="{{ $sablon ? route('superadmin.sablonlar.update', $sablon) : route('superadmin.sablonlar.store') }}">
        @csrf
        @if($sablon) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold small">Şablon Adı *</label>
                                <input type="text" name="ad" class="form-control"
                                       value="{{ old('ad', $sablon?->ad) }}" required placeholder="ör: 23 Nisan Kutlaması">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Tip *</label>
                                <select name="tip" id="tipSelect" class="form-select" required onchange="tipDegisti()">
                                    <option value="email" {{ old('tip', $sablon?->tip) === 'email' ? 'selected' : '' }}>📧 Email</option>
                                    <option value="sms"   {{ old('tip', $sablon?->tip) === 'sms'   ? 'selected' : '' }}>📱 SMS</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1"
                                           {{ old('aktif', $sablon?->aktif ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="aktif">Aktif</label>
                                </div>
                            </div>
                        </div>

                        {{-- Email alanları --}}
                        <div id="emailAlanlari">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Email Konusu</label>
                                <input type="text" name="konu" class="form-control"
                                       value="{{ old('konu', $sablon?->konu) }}"
                                       placeholder="ör: Hayırlı 23 Nisan! 🎉">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold small d-flex justify-content-between">
                                    <span>HTML İçerik</span>
                                    <span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0"
                                                onclick="onizle()"><i class="fas fa-eye me-1"></i>Önizle</button>
                                    </span>
                                </label>
                                <textarea id="htmlEditor" name="html_icerik">{{ old('html_icerik', $sablon?->html_icerik) }}</textarea>
                            </div>
                        </div>

                        {{-- SMS alanları --}}
                        <div id="smsAlanlari" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">SMS Metni <span class="text-muted">(max 160 karakter)</span></label>
                                <textarea name="sms_icerik" id="smsMesaj" class="form-control" rows="4"
                                          maxlength="160" placeholder="SMS metnini buraya yazın...">{{ old('sms_icerik', $sablon?->sms_icerik) }}</textarea>
                                <div class="small text-muted mt-1"><span id="smsChar">0</span>/160</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header py-2 fw-semibold small">Kullanılabilir Değişkenler</div>
                    <div class="card-body py-2">
                        <p class="small text-muted mb-2">Tıklayarak editöre ekle:</p>
                        @foreach(['{{acente_adi}}','{{belge_no}}','{{kayit_url}}','{{il}}'] as $var)
                        <span class="badge bg-secondary var-badge me-1 mb-1" onclick="degiskenEkle('{{ $var }}')">{{ $var }}</span>
                        @endforeach
                        <hr class="my-2">
                        <p class="small text-muted mb-0">
                            <strong>{{acente_adi}}</strong> — Acente ünvanı<br>
                            <strong>{{belge_no}}</strong> — TÜRSAB belge no<br>
                            <strong>{{kayit_url}}</strong> — Kayıt linki (takipli)<br>
                            <strong>{{il}}</strong> — İl
                        </p>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="fas fa-save me-1"></i>{{ $sablon ? 'Güncelle' : 'Oluştur' }}
                    </button>
                    <a href="{{ route('superadmin.sablonlar.index') }}" class="btn btn-outline-secondary">İptal</a>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Önizleme Modal --}}
<div class="modal fade" id="onizleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">Email Önizleme</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="onizleFrame" style="width:100%;height:600px;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function tipDegisti() {
    const tip = document.getElementById('tipSelect').value;
    document.getElementById('emailAlanlari').style.display = tip === 'email' ? '' : 'none';
    document.getElementById('smsAlanlari').style.display   = tip === 'sms'   ? '' : 'none';
}

function degiskenEkle(v) {
    const tip = document.getElementById('tipSelect').value;
    if (tip === 'sms') {
        const ta = document.getElementById('smsMesaj');
        const pos = ta.selectionStart;
        ta.value = ta.value.slice(0, pos) + v + ta.value.slice(pos);
        updateSmsChar();
    } else {
        const editor = document.getElementById('htmlEditor');
        const pos = editor.selectionStart;
        editor.value = editor.value.slice(0, pos) + v + editor.value.slice(pos);
    }
}

function updateSmsChar() {
    document.getElementById('smsChar').textContent = document.getElementById('smsMesaj').value.length;
}

function onizle() {
    const html = document.getElementById('htmlEditor').value;
    const blob = new Blob([html], {type: 'text/html'});
    const url  = URL.createObjectURL(blob);
    document.getElementById('onizleFrame').src = url;
    new bootstrap.Modal(document.getElementById('onizleModal')).show();
}

document.getElementById('smsMesaj')?.addEventListener('input', updateSmsChar);
tipDegisti();
updateSmsChar();
</script>
</body>
</html>
