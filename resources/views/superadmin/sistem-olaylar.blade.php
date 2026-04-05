<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@include('admin.partials.theme-styles')
<title>Sistem Olayları — Superadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
.var-chip {
    display: inline-block;
    background: #e9ecef;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 1px 7px;
    font-size: .78rem;
    font-family: monospace;
    cursor: pointer;
    transition: background .15s;
}
.var-chip:hover { background: #d0d9e6; }
.govde-textarea { min-height: 300px; font-family: monospace; font-size: .83rem; }
.sms-textarea { min-height: 90px; font-family: monospace; font-size: .83rem; }
.olay-row.active-edit { background: #fff8e1; }
</style>
</head>
<body>
<x-navbar-superadmin active="sistem-olaylar" />

<div class="container-fluid px-4 py-4" style="max-width:1200px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-bolt text-warning me-2"></i>Sistem Olay Şablonları</h4>
        <button class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="collapse" data-bs-target="#yeniOlayForm">
            <i class="fas fa-plus me-1"></i>Yeni Olay Ekle
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- YENİ OLAY FORMU --}}
    <div class="collapse mb-4 {{ $errors->has('olay_kodu') ? 'show' : '' }}" id="yeniOlayForm">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white fw-semibold">Yeni Olay Tanımla</div>
            <div class="card-body">
                <form method="POST" action="{{ route('superadmin.sistem.olaylar.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Olay Kodu <span class="text-danger">*</span></label>
                            <input type="text" name="olay_kodu" class="form-control form-control-sm"
                                   placeholder="teklif_eklendi" value="{{ old('olay_kodu') }}"
                                   pattern="[a-z_]+" title="Sadece küçük harf ve alt çizgi">
                            <div class="form-text">Küçük harf, alt çizgi. Değiştirilemez.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Olay Adı <span class="text-danger">*</span></label>
                            <input type="text" name="olay_adi" class="form-control form-control-sm"
                                   placeholder="Teklif Eklendi (Acenteye)" value="{{ old('olay_adi') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Alıcı</label>
                            <select name="alici" class="form-select form-select-sm">
                                <option value="acente">Acente</option>
                                <option value="admin">Admin</option>
                                <option value="her_ikisi">Her İkisi</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Değişkenler</label>
                            <input type="text" name="degiskenler" class="form-control form-control-sm"
                                   placeholder="gtpnr, acente_adi, link" value="{{ old('degiskenler') }}">
                            <div class="form-text">Virgülle ayır</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Konusu</label>
                            <input type="text" name="email_konu" class="form-control form-control-sm"
                                   placeholder="{gtpnr} için teklifiniz hazır" value="{{ old('email_konu') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMS Metni</label>
                            <input type="text" name="sms_govde" class="form-control form-control-sm"
                                   placeholder="{gtpnr} talebiniz güncellendi." value="{{ old('sms_govde') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email Gövdesi (HTML)</label>
                            <textarea name="email_govde" class="form-control govde-textarea"
                                      placeholder="<p>Merhaba {ad_soyad},</p>...">{{ old('email_govde') }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-plus me-1"></i>Ekle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- OLAY LİSTESİ --}}
    @php
        $aliciRenk = ['acente' => 'primary', 'admin' => 'dark', 'her_ikisi' => 'purple'];
        $aliciAd   = ['acente' => 'Acente', 'admin' => 'Admin', 'her_ikisi' => 'Her İkisi'];
    @endphp

    @foreach($olaylar as $olay)
    @php $acik = isset($duzenlenecek) && $duzenlenecek->id === $olay->id; @endphp
    <div class="card shadow-sm mb-3 olay-row {{ $acik ? 'active-edit' : '' }}">
        <div class="card-header d-flex align-items-center gap-2 py-2">
            <i class="fas fa-bolt text-warning"></i>
            <span class="fw-semibold">{{ $olay->olay_adi }}</span>
            <code class="text-muted small ms-1">{{ $olay->olay_kodu }}</code>
            <span class="badge bg-{{ $aliciRenk[$olay->alici] ?? 'secondary' }} ms-1">
                {{ $aliciAd[$olay->alici] ?? $olay->alici }}
            </span>
            @if($olay->email_govde)
                <span class="badge bg-info text-dark ms-1"><i class="fas fa-pencil me-1"></i>Özel Email</span>
            @else
                <span class="badge bg-light text-muted ms-1">Varsayılan Email</span>
            @endif
            @if($olay->sms_govde)
                <span class="badge bg-success ms-1"><i class="fas fa-pencil me-1"></i>Özel SMS</span>
            @else
                <span class="badge bg-light text-muted ms-1">Varsayılan SMS</span>
            @endif
            <div class="ms-auto d-flex gap-2 align-items-center">
                {{-- Aktif/Pasif göstergeleri --}}
                <span class="small text-muted">
                    <i class="fas fa-envelope {{ $olay->email_aktif ? 'text-info' : 'text-secondary' }}"></i>
                    <i class="fas fa-sms {{ $olay->sms_aktif ? 'text-success' : 'text-secondary' }} ms-1"></i>
                </span>
                <a href="{{ route('superadmin.sistem.olaylar.edit', $olay) }}"
                   class="btn btn-sm btn-outline-secondary py-0 px-2">
                    <i class="fas fa-edit me-1"></i>Düzenle
                </a>
                @if($olay->email_govde || $olay->sms_govde)
                <form method="POST" action="{{ route('superadmin.sistem.olaylar.sifirla', $olay) }}"
                      onsubmit="return confirm('Özel şablon silinip varsayılana dönülsün mü?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning py-0 px-2">
                        <i class="fas fa-undo me-1"></i>Sıfırla
                    </button>
                </form>
                @endif
            </div>
        </div>

        @if($acik)
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.sistem.olaylar.update', $olay) }}">
                @csrf @method('PUT')

                {{-- Değişken chip'leri --}}
                @if($olay->degiskenler)
                <div class="mb-3">
                    <span class="text-muted small me-2">Kullanılabilir değişkenler (tıkla → kopyala):</span>
                    @foreach($olay->degiskenler as $degisken)
                    <span class="var-chip me-1 mb-1" onclick="kopyala('{{'{'}}{{ $degisken }}{{'}'}}')">
                        {{'{'}}{{ $degisken }}{{'}'}}
                    </span>
                    @endforeach
                </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Olay Adı</label>
                        <input type="text" name="olay_adi" class="form-control form-control-sm"
                               value="{{ old('olay_adi', $olay->olay_adi) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Değişkenler <span class="text-muted small">(virgülle)</span></label>
                        <input type="text" name="degiskenler" class="form-control form-control-sm"
                               value="{{ old('degiskenler', implode(', ', $olay->degiskenler ?? [])) }}">
                    </div>

                    {{-- EMAIL --}}
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="fw-semibold"><i class="fas fa-envelope text-info me-1"></i>Email</span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="email_aktif" value="1"
                                       id="emailAktif_{{ $olay->id }}" {{ $olay->email_aktif ? 'checked' : '' }}>
                                <label class="form-check-label small" for="emailAktif_{{ $olay->id }}">Aktif</label>
                            </div>
                        </div>
                        <input type="text" name="email_konu" class="form-control form-control-sm mb-2"
                               placeholder="Email konusu (boşsa varsayılan konu kullanılır)"
                               value="{{ old('email_konu', $olay->email_konu) }}">
                        <textarea name="email_govde" class="form-control govde-textarea"
                                  placeholder="HTML gövde — boş bırakılırsa varsayılan Blade şablonu kullanılır">{{ old('email_govde', $olay->email_govde) }}</textarea>
                        <div class="form-text">HTML desteklenir. Boş bırakılırsa sistem şablonu kullanılır.</div>
                    </div>

                    {{-- SMS --}}
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="fw-semibold"><i class="fas fa-sms text-success me-1"></i>SMS</span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="sms_aktif" value="1"
                                       id="smsAktif_{{ $olay->id }}" {{ $olay->sms_aktif ? 'checked' : '' }}>
                                <label class="form-check-label small" for="smsAktif_{{ $olay->id }}">Aktif</label>
                            </div>
                        </div>
                        <textarea name="sms_govde" class="form-control sms-textarea"
                                  placeholder="SMS metni — boş bırakılırsa varsayılan kullanılır. Max 500 karakter."
                                  maxlength="500"
                                  oninput="document.getElementById('smsCount_{{ $olay->id }}').textContent = this.value.length + '/500'">{{ old('sms_govde', $olay->sms_govde) }}</textarea>
                        <div class="form-text d-flex justify-content-between">
                            <span>Boş bırakılırsa sistem SMS'i kullanılır.</span>
                            <span id="smsCount_{{ $olay->id }}" class="text-muted">{{ strlen($olay->sms_govde ?? '') }}/500</span>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save me-1"></i>Kaydet
                        </button>
                        <a href="{{ route('superadmin.sistem.olaylar') }}" class="btn btn-outline-secondary btn-sm">İptal</a>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>
    @endforeach

    <div class="text-muted small mt-3">
        <i class="fas fa-info-circle me-1"></i>
        Boş bırakılan alanlar için sistem, <code>resources/views/emails/</code> klasöründeki varsayılan Blade şablonlarını kullanır.
        SMS için controller'daki sabit metne düşer.
    </div>
</div>

<div id="kopyalaToast" style="position:fixed;bottom:20px;right:20px;display:none;z-index:9999;">
    <div class="alert alert-success py-2 px-3 shadow-sm mb-0">
        <i class="fas fa-check me-1"></i>Panoya kopyalandı
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function kopyala(metin) {
    navigator.clipboard.writeText(metin).then(function() {
        var toast = document.getElementById('kopyalaToast');
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, 1500);
    });
}
</script>
</body>
</html>
