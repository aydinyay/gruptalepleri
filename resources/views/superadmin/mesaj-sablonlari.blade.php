<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>Mesaj Şablonları — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/jodit@4.2.32/es2021/jodit.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .sablon-card { background:#fff; border:none; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:16px; overflow:hidden; transition:box-shadow .2s; }
        .sablon-card:hover { box-shadow:0 4px 20px rgba(0,0,0,0.10); }
        .sablon-name { font-weight:700; font-size:0.95rem; color:#1a1a2e; }
        .sablon-preview-konu { font-size:0.8rem; color:#6b7280; margin-top:2px; }
        .var-pill {
            display:inline-block; padding:3px 10px; border-radius:20px;
            font-size:0.78rem; font-weight:600; font-family:monospace;
            background:#fff3f5; color:#e94560; border:1px solid #fcc;
            cursor:pointer; transition:background .15s; white-space:nowrap; user-select:none;
        }
        .var-pill:hover { background:#e94560; color:#fff; border-color:#e94560; }
        .var-pill.link-var { background:#eff6ff; color:#3b82f6; border-color:#bcd0f7; }
        .var-pill.link-var:hover { background:#3b82f6; color:#fff; border-color:#3b82f6; }
        .sms-counter { font-size:0.78rem; color:#9ca3af; }
        .sms-counter.warn   { color:#f59e0b; font-weight:600; }
        .sms-counter.danger { color:#ef4444; font-weight:600; }
        /* Jodit overrides */
        .jodit-container { border-radius:8px !important; }
        .jodit-toolbar { border-radius:8px 8px 0 0 !important; }
    </style>
</head>
<body>
<x-navbar-superadmin active="mesaj-sablonlari" />

<div class="container-fluid py-3 px-4" style="max-width:1100px;">

    {{-- Başlık --}}
    <div class="d-flex align-items-start align-items-md-center flex-column flex-md-row gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>Mesaj Şablonları
                <span class="badge bg-secondary fw-normal ms-1">{{ $sablonlar->count() }}</span>
            </h5>
            <div class="text-muted small mt-1">Toplu email ve SMS gönderimleri için şablon havuzu</div>
        </div>
        <div class="ms-md-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniSablonModal">
                <i class="fas fa-plus me-1"></i>Yeni Şablon
            </button>
        </div>
    </div>

    {{-- Değişkenler Bilgi Kutusu --}}
    <div class="card border-0 mb-4" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:12px;">
        <div class="card-body py-3 px-4">
            <div class="text-white fw-semibold mb-2" style="font-size:0.82rem;letter-spacing:.5px;text-transform:uppercase;">
                <i class="fas fa-code me-1 text-danger"></i> Kullanılabilir Değişkenler
                <span class="text-muted fw-normal ms-2" style="font-size:0.75rem;text-transform:none;">(tıklayarak kopyala)</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="var-pill"      onclick="copyVar(this)" title="Firmanın ticaret ünvanı">{acente_adi}</span>
                <span class="var-pill"      onclick="copyVar(this)" title="Yetkili kişi adı">{yetkili_adi}</span>
                <span class="var-pill"      onclick="copyVar(this)" title="Kullanıcı adı">{ad}</span>
                <span class="var-pill link-var" onclick="copyVar(this)" title="Dashboard linki">{platform_linki}</span>
                <span class="var-pill link-var" onclick="copyVar(this)" title="Giriş sayfası">{giris_linki}</span>
                <span class="var-pill link-var" onclick="copyVar(this)" title="Yeni talep oluşturma">{talep_ac_linki}</span>
                <span class="var-pill link-var" onclick="copyVar(this)" title="Şifre sıfırlama sayfası">{sifre_yenile_linki}</span>
                <span class="var-pill link-var" onclick="copyVar(this)" title="E-posta abonelik iptali">{unsubscribe_linki}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if($sablonlar->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-file-alt fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">Henüz şablon yok. "Yeni Şablon" ile başlayın.</p>
        </div>
    @else
        @foreach($sablonlar as $sablon)
        <div class="sablon-card">
            <div class="p-3 d-flex align-items-center gap-2">
                <div class="flex-grow-1">
                    <div class="sablon-name">{{ $sablon->sablon_adi }}</div>
                    @if($sablon->email_konu)
                    <div class="sablon-preview-konu"><i class="fas fa-envelope me-1 opacity-40"></i>{{ Str::limit($sablon->email_konu, 80) }}</div>
                    @endif
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    @foreach($sablon->kanallar ?? [] as $kanal)
                        @if($kanal==='email') <span class="badge bg-primary"><i class="fas fa-envelope me-1"></i>Email</span>
                        @elseif($kanal==='sms') <span class="badge bg-success"><i class="fas fa-comment-sms me-1"></i>SMS</span>
                        @elseif($kanal==='push') <span class="badge bg-warning text-dark"><i class="fas fa-bell me-1"></i>Push</span>
                        @endif
                    @endforeach
                </div>
                <div class="d-flex gap-2 ms-2">
                    <a href="{{ route('superadmin.mesaj.sablonlari.onizle', $sablon) }}" target="_blank"
                       class="btn btn-sm btn-outline-info"><i class="fas fa-eye me-1"></i>Önizle</a>
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="collapse"
                            data-bs-target="#sablon-{{ $sablon->id }}">
                        <i class="fas fa-edit me-1"></i>Düzenle
                    </button>
                    <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.sil', $sablon) }}"
                          onsubmit="return confirm('Bu şablonu silmek istiyor musunuz?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>

            <div class="collapse" id="sablon-{{ $sablon->id }}">
                <div class="border-top" style="background:#fafbfc;">
                    <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.guncelle', $sablon) }}" class="p-4">
                        @csrf @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Şablon Adı <span class="text-danger">*</span></label>
                                <input type="text" name="sablon_adi" class="form-control" value="{{ $sablon->sablon_adi }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email Konusu</label>
                                <input type="text" name="email_konu" class="form-control" value="{{ $sablon->email_konu }}"
                                       placeholder="{ad}, grubunuzu bekliyor!">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Kanallar</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kanallar[]" value="email"
                                               id="k-email-{{ $sablon->id }}" {{ in_array('email',$sablon->kanallar??[]) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="k-email-{{ $sablon->id }}"><i class="fas fa-envelope text-primary me-1"></i>Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kanallar[]" value="sms"
                                               id="k-sms-{{ $sablon->id }}" {{ in_array('sms',$sablon->kanallar??[]) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="k-sms-{{ $sablon->id }}"><i class="fas fa-comment-sms text-success me-1"></i>SMS</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kanallar[]" value="push"
                                               id="k-push-{{ $sablon->id }}" {{ in_array('push',$sablon->kanallar??[]) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="k-push-{{ $sablon->id }}"><i class="fas fa-bell text-warning me-1"></i>Push</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold small">Email Gövdesi</label>
                                <textarea name="email_govde" id="jodit-{{ $sablon->id }}" class="jodit-target">{{ $sablon->email_govde }}</textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small d-flex justify-content-between">
                                    SMS Metni
                                    <span class="sms-counter" id="sc-{{ $sablon->id }}">{{ strlen($sablon->sms_govde ?? '') }}/500</span>
                                </label>
                                <textarea name="sms_govde" class="form-control font-monospace sms-area"
                                          id="sms-{{ $sablon->id }}" rows="10" maxlength="500"
                                          placeholder="SMS içeriği...">{{ $sablon->sms_govde }}</textarea>
                                <div class="form-text"><i class="fas fa-info-circle me-1"></i>160 karakter = 1 SMS &nbsp;·&nbsp; Türkçe: 70 karakter = 1 SMS</div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="collapse" data-bs-target="#sablon-{{ $sablon->id }}">İptal</button>
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Kaydet</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @endif

</div>

{{-- YENİ ŞABLON MODAL --}}
<div class="modal fade" id="yeniSablonModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus text-primary me-2"></i>Yeni Şablon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.kaydet') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Şablon Adı <span class="text-danger">*</span></label>
                            <input type="text" name="sablon_adi" class="form-control" required placeholder="Örn: 30 Gün Hareketsiz Hatırlatma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email Konusu</label>
                            <input type="text" name="email_konu" class="form-control" placeholder="{ad}, sizi özledik!">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Kanallar</label>
                            <div class="d-flex gap-4">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="kanallar[]" value="email" id="yeni-email" checked><label class="form-check-label" for="yeni-email"><i class="fas fa-envelope text-primary me-1"></i>Email</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="kanallar[]" value="sms" id="yeni-sms"><label class="form-check-label" for="yeni-sms"><i class="fas fa-comment-sms text-success me-1"></i>SMS</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="kanallar[]" value="push" id="yeni-push"><label class="form-check-label" for="yeni-push"><i class="fas fa-bell text-warning me-1"></i>Push</label></div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">Email Gövdesi</label>
                            <textarea name="email_govde" id="jodit-yeni" class="jodit-target" rows="10"></textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small d-flex justify-content-between">
                                SMS Metni <span class="sms-counter" id="sc-yeni">0/500</span>
                            </label>
                            <textarea name="sms_govde" class="form-control font-monospace sms-area"
                                      id="sms-yeni" rows="10" maxlength="500" placeholder="SMS içeriği..."></textarea>
                            <div class="form-text"><i class="fas fa-info-circle me-1"></i>160 karakter = 1 SMS &nbsp;·&nbsp; Türkçe: 70 karakter = 1 SMS</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="copyToast" class="toast align-items-center text-bg-dark border-0 py-1" role="alert">
        <div class="d-flex">
            <div class="toast-body py-2"><i class="fas fa-clipboard-check me-2 text-success"></i>Kopyalandı</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jodit@4.2.32/es2021/jodit.min.js"></script>
@include('admin.partials.theme-script')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const UPLOAD_URL = '{{ route("superadmin.upload-email-image") }}';

// ── Jodit config ─────────────────────────────────────────────────────
const joditConfig = {
    height: 380,
    language: 'en',
    placeholder: 'Email içeriğini yazın...',
    buttons: [
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'ul', 'ol', '|',
        'outdent', 'indent', '|',
        'font', 'fontsize', 'brush', '|',
        'paragraph', '|',
        'image', 'table', 'link', 'hr', '|',
        'left', 'center', 'right', 'justify', '|',
        'undo', 'redo', '|',
        'source', 'fullsize',
    ],
    uploader: {
        url: UPLOAD_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        filesVariableName: () => 'image',
        isSuccess: (resp) => resp.error === 0,
        getMsg: (resp) => resp.message || 'Hata',
        process: (resp) => ({
            files: resp.files || [],
            path: resp.path || '',
            baseurl: resp.baseurl || '',
            error: resp.error,
            msg: resp.message,
        }),
        defaultHandlerSuccess: function(data) {
            if (data.files && data.files.length) {
                this.j.s.insertImage(data.files[0]);
            }
        },
    },
    filebrowser: { ajax: { url: UPLOAD_URL } },
    toolbarAdaptive: false,
    showCharsCounter: false,
    showWordsCounter: false,
    showXPathInStatusbar: false,
};

const editors = {}; // id → Jodit instance

function initJodit(id) {
    if (editors[id]) return;
    const el = document.getElementById(id);
    if (!el) return;
    editors[id] = Jodit.make(el, joditConfig);
}

// Collapse açılınca init
document.querySelectorAll('[id^="sablon-"]').forEach(function(el) {
    el.addEventListener('show.bs.collapse', function() {
        const ta = this.querySelector('.jodit-target');
        if (ta) initJodit(ta.id);
    });
});

// Modal açılınca init
document.getElementById('yeniSablonModal').addEventListener('shown.bs.modal', function() {
    initJodit('jodit-yeni');
});

// ── SMS sayacı ────────────────────────────────────────────────────────
function initSmsCounter(taId, counterId) {
    const ta = document.getElementById(taId);
    const c  = document.getElementById(counterId);
    if (!ta || !c) return;
    const update = () => {
        const len = ta.value.length;
        c.textContent = len + '/500';
        c.className = 'sms-counter' + (len > 450 ? ' danger' : len > 350 ? ' warn' : '');
    };
    ta.addEventListener('input', update);
    update();
}

@foreach($sablonlar as $sablon)
initSmsCounter('sms-{{ $sablon->id }}', 'sc-{{ $sablon->id }}');
@endforeach
initSmsCounter('sms-yeni', 'sc-yeni');

// ── Değişken kopyala ──────────────────────────────────────────────────
const copyToast = new bootstrap.Toast(document.getElementById('copyToast'), { delay: 1500 });
function copyVar(el) {
    navigator.clipboard.writeText(el.textContent.trim()).then(() => copyToast.show());
}
</script>
</body>
</html>
