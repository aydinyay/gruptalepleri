@extends('layouts.admin-sigorta')

@section('title', 'Poliçe Detayı — Admin')

@section('content')
<div class="container py-4" style="max-width:900px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Poliçe #{{ $police->id }}</h4>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="badge {{ $police->kanal === 'b2c' ? 'bg-warning text-dark' : 'bg-primary' }}">
                {{ strtoupper($police->kanal) }}
            </span>
            @include('acente.sigorta._durum_badge', ['durum' => $police->durum])
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-bold bg-light py-2">Sigortalı</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted">Ad Soyad</th><td>{{ $police->sigortali_adi }} {{ $police->sigortali_soyadi }}</td></tr>
                        <tr><th class="text-muted">Kimlik</th><td class="fw-mono">{{ $police->sigortali_kimlik }} ({{ $police->kimlik_tipi }})</td></tr>
                        <tr><th class="text-muted">Doğum</th><td>{{ $police->sigortali_dogum?->format('d.m.Y') }}</td></tr>
                        <tr><th class="text-muted">Ülke</th><td>{{ $police->gidilecek_ulke }}</td></tr>
                        <tr><th class="text-muted">Seyahat</th><td>{{ $police->baslangic_tarihi?->format('d.m.Y') }} → {{ $police->bitis_tarihi?->format('d.m.Y') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-bold bg-light py-2">Poliçe & Fiyat</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted">Poliçe No</th><td class="fw-mono text-primary">{{ $police->police_no ?: '—' }}</td></tr>
                        <tr><th class="text-muted">Ürün</th><td>{{ $police->paonet_urun_kodu }}</td></tr>
                        <tr><th class="text-muted">Acente</th><td>{{ $police->acente?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">API Fiyat</th><td>{{ $police->api_doviz_tutar }} {{ $police->api_doviz_turu }} × {{ $police->api_kur }}</td></tr>
                        <tr><th class="text-muted">Maliyet</th><td class="text-muted">{{ number_format($police->maliyet_tl, 2) }} ₺</td></tr>
                        <tr><th class="text-muted">Satış</th><td class="fw-bold">{{ number_format($police->satilan_fiyat_tl, 2) }} ₺</td></tr>
                        <tr><th class="text-muted">Net Kâr</th><td class="text-success fw-bold">{{ number_format($police->net_kar_tl, 2) }} ₺</td></tr>
                        <tr><th class="text-muted">Markup</th><td>%{{ $police->markup_yuzde }} + %{{ $police->kur_tamponu_yuzde }} tampon</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF Belgeler --}}
    @if($police->durum === 'tamamlandi')
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold bg-light py-2">Belgeler</div>
        <div class="card-body d-flex flex-wrap gap-2">
            @if($police->pdf_link)
            <a href="{{ route('admin.sigorta.belge', [$police, 'police']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Poliçe PDF
            </a>
            @endif
            @if($police->makbuz_link)
            <a href="{{ route('admin.sigorta.belge', [$police, 'makbuz']) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-receipt me-1"></i> Makbuz
            </a>
            @endif
            @if($police->sertifika_link)
            <a href="{{ route('admin.sigorta.belge', [$police, 'sertifika']) }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fas fa-certificate me-1"></i> Sertifika (TR)
            </a>
            @endif
            @if($police->ing_sertifika_link)
            <a href="{{ route('admin.sigorta.belge', [$police, 'ing-sertifika']) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-certificate me-1"></i> Sertifika (EN)
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Batch bilgisi --}}
    @if($police->batchJob)
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold bg-light py-2">Batch İşlem</div>
        <div class="card-body">
            <p class="mb-1"><strong>Batch ID:</strong> {{ $police->batchJob->id }}</p>
            <p class="mb-0"><strong>İşlem Adı:</strong> {{ $police->batchJob->islem_adi }}</p>
        </div>
    </div>
    @endif

    {{-- Hata --}}
    @if($police->hata_mesaji)
    <div class="alert alert-danger mt-4">
        <strong>Hata:</strong> {{ $police->hata_mesaji }}
    </div>
    @endif

    {{-- Admin Manuel Durum Düzeltme --}}
    <div class="card shadow-sm mt-4 border-warning">
        <div class="card-header fw-bold bg-warning bg-opacity-10 py-2">
            <i class="fas fa-tools me-2 text-warning"></i>Manuel Durum Güncelleme
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('admin.sigorta.durum-degistir', $police) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Yeni Durum</label>
                    <select name="durum" class="form-select form-select-sm" required>
                        @foreach(['odeme_bekleniyor','odeme_basarisiz','police_isleniyor','tamamlandi','iptal_bekliyor','iptal','hata'] as $d)
                        <option value="{{ $d }}" {{ $police->durum === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Poliçe No (opsiyonel)</label>
                    <input type="text" name="police_no" class="form-control form-control-sm"
                        value="{{ $police->police_no }}" placeholder="PAO-Net'ten gelen no">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Not / Hata Mesajı</label>
                    <input type="text" name="not" class="form-control form-control-sm"
                        placeholder="Admin notu..." maxlength="500">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-warning btn-sm w-100"
                        onclick="return confirm('Durumu güncellemek istediğinizden emin misiniz?')">
                        <i class="fas fa-save me-1"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
