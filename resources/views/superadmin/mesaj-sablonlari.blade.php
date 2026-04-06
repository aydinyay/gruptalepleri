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
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .sablon-card { background: #fff; border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 16px; overflow: hidden; transition: box-shadow .2s; }
        .sablon-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.10); }
        .sablon-header { padding: 14px 20px; display: flex; align-items: center; gap: 10px; background: #fff; cursor: default; }
        .sablon-name { font-weight: 700; font-size: 0.95rem; color: #1a1a2e; }
        .sablon-preview-konu { font-size: 0.8rem; color: #6b7280; margin-top: 2px; }
        .var-pill {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 600; font-family: monospace;
            background: #fff3f5; color: #e94560; border: 1px solid #fcc; cursor: pointer;
            transition: background .15s; white-space: nowrap;
        }
        .var-pill:hover { background: #e94560; color: #fff; border-color: #e94560; }
        .var-pill.link-var { background: #eff6ff; color: #3b82f6; border-color: #bcd0f7; }
        .var-pill.link-var:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .sms-counter { font-size: 0.78rem; color: #9ca3af; }
        .sms-counter.warn { color: #f59e0b; }
        .sms-counter.danger { color: #ef4444; }
        .note-editor { border-radius: 8px !important; overflow: hidden; }
        .note-toolbar { background: #f8f9fa !important; border-bottom: 1px solid #e9ecef !important; }
        .note-editable { min-height: 200px; font-size: 14px; line-height: 1.7; }
    </style>
</head>
<body>
<x-navbar-superadmin active="mesaj-sablonlari" />

<div class="container-fluid py-3 px-4" style="max-width: 1100px;">

    {{-- Başlık --}}
    <div class="d-flex align-items-start align-items-md-center flex-column flex-md-row gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-0"><i class="fas fa-file-alt text-primary me-2"></i>Mesaj Şablonları <span class="badge bg-secondary fw-normal ms-1">{{ $sablonlar->count() }}</span></h5>
            <div class="text-muted small mt-1">Toplu email ve SMS gönderimleri için şablon havuzu</div>
        </div>
        <div class="ms-md-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniSablonModal">
                <i class="fas fa-plus me-1"></i>Yeni Şablon
            </button>
        </div>
    </div>

    {{-- Değişkenler Bilgi Kutusu --}}
    <div class="card border-0 mb-4" style="background: linear-gradient(135deg,#1a1a2e,#16213e); border-radius: 12px;">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3 flex-wrap">
                <div>
                    <div class="text-white fw-semibold mb-2" style="font-size:0.82rem; letter-spacing:.5px; text-transform:uppercase;">
                        <i class="fas fa-code me-1 text-danger"></i> Kullanılabilir Değişkenler
                        <span class="text-muted fw-normal ms-2" style="font-size:0.75rem;text-transform:none;">(tıklayarak kopyala)</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="var-pill" onclick="copyVar(this)" title="Firmanın ticaret ünvanı">{acente_adi}</span>
                        <span class="var-pill" onclick="copyVar(this)" title="Yetkili kişi adı">{yetkili_adi}</span>
                        <span class="var-pill" onclick="copyVar(this)" title="Kullanıcı adı">{ad}</span>
                        <span class="var-pill link-var" onclick="copyVar(this)" title="Platforma giriş linki">{platform_linki}</span>
                        <span class="var-pill link-var" onclick="copyVar(this)" title="Giriş sayfası">{giris_linki}</span>
                        <span class="var-pill link-var" onclick="copyVar(this)" title="Yeni talep oluşturma">{talep_ac_linki}</span>
                        <span class="var-pill link-var" onclick="copyVar(this)" title="E-posta abonelik iptali">{unsubscribe_linki}</span>
                    </div>
                </div>
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
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">Henüz şablon yok. "Yeni Şablon" butonuyla başlayın.</p>
        </div>
    @else
        @foreach($sablonlar as $sablon)
        <div class="sablon-card">
            {{-- Kart başlığı --}}
            <div class="sablon-header">
                <div class="flex-grow-1">
                    <div class="sablon-name">{{ $sablon->sablon_adi }}</div>
                    @if($sablon->email_konu)
                    <div class="sablon-preview-konu"><i class="fas fa-envelope me-1 opacity-50"></i>{{ Str::limit($sablon->email_konu, 70) }}</div>
                    @endif
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    @foreach($sablon->kanallar ?? [] as $kanal)
                        @if($kanal === 'email')
                            <span class="badge bg-primary"><i class="fas fa-envelope me-1"></i>Email</span>
                        @elseif($kanal === 'sms')
                            <span class="badge bg-success"><i class="fas fa-comment-sms me-1"></i>SMS</span>
                        @elseif($kanal === 'push')
                            <span class="badge bg-warning text-dark"><i class="fas fa-bell me-1"></i>Push</span>
                        @endif
                    @endforeach
                </div>
                <div class="d-flex gap-2 ms-2">
                    <a href="{{ route('superadmin.mesaj.sablonlari.onizle', $sablon) }}" target="_blank"
                       class="btn btn-sm btn-outline-info" title="Email önizleme">
                        <i class="fas fa-eye me-1"></i>Önizle
                    </a>
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="collapse"
                            data-bs-target="#sablon-{{ $sablon->id }}"
                            title="Düzenle">
                        <i class="fas fa-edit me-1"></i>Düzenle
                    </button>
                    <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.sil', $sablon) }}"
                          onsubmit="return confirm('Bu şablonu silmek istiyor musunuz?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Sil"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>

            {{-- Düzenleme formu (collapse) --}}
            <div class="collapse" id="sablon-{{ $sablon->id }}">
                <div class="border-top" style="background:#fafbfc;">
                    <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.guncelle', $sablon) }}"
                          class="sablon-form p-4">
                        @csrf @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Şablon Adı <span class="text-danger">*</span></label>
                                <input type="text" name="sablon_adi" class="form-control"
                                       value="{{ $sablon->sablon_adi }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email Konusu</label>
                                <input type="text" name="email_konu" class="form-control"
                                       value="{{ $sablon->email_konu }}"
                                       placeholder="Değişken kullanabilirsiniz: {ad}, {acente_adi}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Kanallar</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kanallar[]" value="email"
                                               id="k-email-{{ $sablon->id }}"
                                               {{ in_array('email', $sablon->kanallar ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="k-email-{{ $sablon->id }}">
                                            <i class="fas fa-envelope text-primary me-1"></i>Email
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kanallar[]" value="sms"
                                               id="k-sms-{{ $sablon->id }}"
                                               {{ in_array('sms', $sablon->kanallar ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="k-sms-{{ $sablon->id }}">
                                            <i class="fas fa-comment-sms text-success me-1"></i>SMS
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kanallar[]" value="push"
                                               id="k-push-{{ $sablon->id }}"
                                               {{ in_array('push', $sablon->kanallar ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="k-push-{{ $sablon->id }}">
                                            <i class="fas fa-bell text-warning me-1"></i>Push
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Email Gövdesi --}}
                            <div class="col-md-7">
                                <label class="form-label fw-semibold small">Email Gövdesi</label>
                                <textarea name="email_govde"
                                          class="form-control email-editor"
                                          id="editor-{{ $sablon->id }}"
                                          rows="8">{{ $sablon->email_govde }}</textarea>
                            </div>

                            {{-- SMS Metni --}}
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small d-flex justify-content-between">
                                    SMS Metni
                                    <span class="sms-counter" id="sc-{{ $sablon->id }}">{{ strlen($sablon->sms_govde ?? '') }}/500</span>
                                </label>
                                <textarea name="sms_govde"
                                          class="form-control sms-area font-monospace"
                                          id="sms-{{ $sablon->id }}"
                                          rows="8" maxlength="500"
                                          placeholder="SMS içeriği... (değişken: {acente_adi}, {ad})">{{ $sablon->sms_govde }}</textarea>
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>160 karakter = 1 SMS. Türkçe karakter 70 karakter = 1 SMS.
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#sablon-{{ $sablon->id }}">
                                    İptal
                                </button>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-save me-1"></i>Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @endif

</div>

{{-- ════════════════════════════════════════ --}}
{{-- YENİ ŞABLON MODAL                       --}}
{{-- ════════════════════════════════════════ --}}
<div class="modal fade" id="yeniSablonModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus text-primary me-2"></i>Yeni Şablon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.kaydet') }}" class="sablon-form">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Şablon Adı <span class="text-danger">*</span></label>
                            <input type="text" name="sablon_adi" class="form-control" required
                                   placeholder="Örn: 30 Gün Hareketsiz Hatırlatma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email Konusu</label>
                            <input type="text" name="email_konu" class="form-control"
                                   placeholder="{ad}, sizi özledik! Yeni teklifler sizi bekliyor.">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Kanallar</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kanallar[]" value="email" id="yeni-email" checked>
                                    <label class="form-check-label" for="yeni-email"><i class="fas fa-envelope text-primary me-1"></i>Email</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kanallar[]" value="sms" id="yeni-sms">
                                    <label class="form-check-label" for="yeni-sms"><i class="fas fa-comment-sms text-success me-1"></i>SMS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kanallar[]" value="push" id="yeni-push">
                                    <label class="form-check-label" for="yeni-push"><i class="fas fa-bell text-warning me-1"></i>Push</label>
                                </div>
                            </div>
                        </div>

                        {{-- Email Gövdesi --}}
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">Email Gövdesi</label>
                            <textarea name="email_govde" class="form-control email-editor" id="editor-yeni" rows="10"
                                      placeholder="Email içeriği..."></textarea>
                        </div>

                        {{-- SMS --}}
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small d-flex justify-content-between">
                                SMS Metni
                                <span class="sms-counter" id="sc-yeni">0/500</span>
                            </label>
                            <textarea name="sms_govde" class="form-control sms-area font-monospace" id="sms-yeni"
                                      rows="10" maxlength="500"
                                      placeholder="SMS içeriği..."></textarea>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>160 karakter = 1 SMS. Türkçe karakter 70 karakter = 1 SMS.
                            </div>
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

{{-- Toast: kopyalandı --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="copyToast" class="toast align-items-center text-bg-dark border-0 py-1" role="alert">
        <div class="d-flex">
            <div class="toast-body py-2"><i class="fas fa-clipboard-check me-2 text-success"></i>Kopyalandı</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/lang/summernote-tr-TR.min.js"></script>
@include('admin.partials.theme-script')

<script>
// ── Summernote config ───────────────────────────────────────────────
var summernoteConfig = {
    height: 260,
    lang: 'tr-TR',
    toolbar: [
        ['style',  ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
        ['font',   ['fontname', 'fontsize']],
        ['color',  ['color']],
        ['para',   ['ul', 'ol', 'paragraph', 'height']],
        ['table',  ['table']],
        ['insert', ['link', 'picture', 'hr']],
        ['view',   ['fullscreen', 'codeview']],
    ],
    fontNames: ['Arial', 'Helvetica', 'Georgia', 'Tahoma', 'Trebuchet MS', 'Verdana', 'Segoe UI'],
    fontSizes: ['12', '13', '14', '15', '16', '18', '20', '24'],
    callbacks: {
        onImageUpload: function(files) {
            uploadEditorImage(files[0], $(this));
        }
    }
};

function initEditor($el) {
    if ($el.next('.note-editor').length) return; // zaten başlatılmış
    $el.summernote(summernoteConfig);
}

// Collapse açılınca init
document.querySelectorAll('.collapse').forEach(function(el) {
    el.addEventListener('show.bs.collapse', function() {
        var $ta = $(this).find('.email-editor');
        if ($ta.length) initEditor($ta);
    });
});

// Modal açılınca init
$('#yeniSablonModal').on('shown.bs.modal', function() {
    initEditor($('#editor-yeni'));
});

// ── Resim yükleme ───────────────────────────────────────────────────
function uploadEditorImage(file, $editor) {
    var fd = new FormData();
    fd.append('image', file);
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    $.ajax({
        url: '{{ route("superadmin.upload-email-image") }}',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(data) {
            $editor.summernote('insertImage', data.url, function($img) {
                $img.css({ 'max-width': '100%', 'height': 'auto' });
            });
        },
        error: function() {
            alert('Resim yüklenemedi. Maksimum boyut: 3 MB. İzin verilen: JPG, PNG, GIF, WebP.');
        }
    });
}

// ── SMS karakter sayacı ─────────────────────────────────────────────
function initSmsCounter(textareaId, counterId) {
    var $ta = $('#' + textareaId);
    var $c  = $('#' + counterId);
    if (!$ta.length || !$c.length) return;
    function update() {
        var len = $ta.val().length;
        $c.text(len + '/500').removeClass('warn danger');
        if (len > 450) $c.addClass('danger');
        else if (len > 350) $c.addClass('warn');
    }
    $ta.on('input', update);
    update();
}

@foreach($sablonlar as $sablon)
initSmsCounter('sms-{{ $sablon->id }}', 'sc-{{ $sablon->id }}');
@endforeach
initSmsCounter('sms-yeni', 'sc-yeni');

// ── Değişken kopyala ────────────────────────────────────────────────
var copyToast = new bootstrap.Toast(document.getElementById('copyToast'), { delay: 1500 });

function copyVar(el) {
    var text = el.textContent.trim();
    navigator.clipboard.writeText(text).then(function() {
        copyToast.show();
    });
}

// ── Form submit: Summernote değerini textarea'ya yaz ────────────────
$(document).on('submit', '.sablon-form', function() {
    $(this).find('.email-editor').each(function() {
        if ($(this).next('.note-editor').length) {
            // Summernote aktifse HTML değerini textarea'ya al
            // (Summernote bunu otomatik yapıyor ama garanti için)
        }
    });
});
</script>
</body>
</html>
