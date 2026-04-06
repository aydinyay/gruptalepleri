<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Mesaj Şablonları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-superadmin active="mesaj-sablonlari" />

<div class="container-fluid py-3 px-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2 text-primary"></i>Mesaj Şablonları</h5>
        <span class="text-muted small ms-1">{{ $sablonlar->count() }} şablon</span>
        <div class="ms-auto d-flex gap-2">
            <div class="alert alert-info py-1 px-2 mb-0 small">
                <strong>Kullanılabilir değişkenler:</strong>
                <code class="ms-1">{acente_adi}</code>
                <code class="ms-1">{yetkili_adi}</code>
                <code class="ms-1">{ad}</code>
                <code class="ms-1">{platform_linki}</code>
                <span class="text-muted ms-1">— gönderimde otomatik kişiselleştirilir</span>
            </div>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#yeniSablonModal">
                <i class="fas fa-plus me-1"></i>Yeni Şablon
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if($sablonlar->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Henüz şablon yok. "Yeni Şablon" butonuyla başlayın.
        </div>
    @else
        <div class="row g-3">
            @foreach($sablonlar as $sablon)
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center gap-2 py-2">
                        <span class="fw-bold">{{ $sablon->sablon_adi }}</span>
                        <div class="ms-2 d-flex gap-1">
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
                        <div class="ms-auto d-flex gap-2">
                            <a href="{{ route('superadmin.mesaj.sablonlari.onizle', $sablon) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye me-1"></i>Önizle
                            </a>
                            <button class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#sablon-{{ $sablon->id }}">
                                <i class="fas fa-edit me-1"></i>Düzenle
                            </button>
                            <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.sil', $sablon) }}"
                                  onsubmit="return confirm('Bu şablonu silmek istediğinizden emin misiniz?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="collapse" id="sablon-{{ $sablon->id }}">
                        <div class="card-body">
                            <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.guncelle', $sablon) }}">
                                @csrf @method('PATCH')
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Şablon Adı</label>
                                        <input type="text" name="sablon_adi" class="form-control form-control-sm"
                                               value="{{ $sablon->sablon_adi }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email Konusu</label>
                                        <input type="text" name="email_konu" class="form-control form-control-sm"
                                               value="{{ $sablon->email_konu }}" placeholder="(Email seçiliyse doldurun)">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Kanallar</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="kanallar[]" value="email" id="kanal-email-{{ $sablon->id }}"
                                                       {{ in_array('email', $sablon->kanallar ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="kanal-email-{{ $sablon->id }}"><i class="fas fa-envelope text-primary me-1"></i>Email</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="kanallar[]" value="sms" id="kanal-sms-{{ $sablon->id }}"
                                                       {{ in_array('sms', $sablon->kanallar ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="kanal-sms-{{ $sablon->id }}"><i class="fas fa-comment-sms text-success me-1"></i>SMS</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="kanallar[]" value="push" id="kanal-push-{{ $sablon->id }}"
                                                       {{ in_array('push', $sablon->kanallar ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="kanal-push-{{ $sablon->id }}"><i class="fas fa-bell text-warning me-1"></i>Push</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email Gövdesi <small class="text-muted">(HTML veya düz metin)</small></label>
                                        <textarea name="email_govde" class="form-control form-control-sm" rows="6"
                                                  placeholder="Email içeriği...">{{ $sablon->email_govde }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">SMS Metni <small class="text-muted">(max 500 karakter)</small></label>
                                        <textarea name="sms_govde" class="form-control form-control-sm" rows="6"
                                                  maxlength="500" placeholder="SMS içeriği...">{{ $sablon->sms_govde }}</textarea>
                                        <div class="form-text">{{ strlen($sablon->sms_govde ?? '') }}/500 karakter</div>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-save me-1"></i>Kaydet
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @if($sablon->email_konu || $sablon->sms_govde)
                    <div class="card-footer py-2 small text-muted d-flex gap-4">
                        @if($sablon->email_konu)
                            <span><i class="fas fa-envelope me-1"></i>{{ Str::limit($sablon->email_konu, 60) }}</span>
                        @endif
                        @if($sablon->sms_govde)
                            <span><i class="fas fa-comment-sms me-1"></i>{{ Str::limit($sablon->sms_govde, 80) }}</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Yeni Şablon Modal --}}
<div class="modal fade" id="yeniSablonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Yeni Şablon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.mesaj.sablonlari.kaydet') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Şablon Adı <span class="text-danger">*</span></label>
                            <input type="text" name="sablon_adi" class="form-control" required placeholder="Örn: 30 Gün Hareketsiz Hatırlatma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Konusu</label>
                            <input type="text" name="email_konu" class="form-control" placeholder="Email konu satırı">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Kanallar</label>
                            <div class="d-flex gap-3">
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
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Gövdesi</label>
                            <textarea name="email_govde" class="form-control" rows="7" placeholder="Email içeriği (HTML veya düz metin)..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMS Metni <small class="text-muted">(max 500 karakter)</small></label>
                            <textarea name="sms_govde" class="form-control" rows="7" maxlength="500" placeholder="SMS içeriği..."></textarea>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
</body>
</html>
