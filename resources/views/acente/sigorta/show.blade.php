@extends('layouts.acente-sigorta')

@section('title', 'Poliçe Detayı')

@section('content')
<div class="container py-4" style="max-width:800px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('acente.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Poliçe Detayı</h4>
        <div class="ms-auto">
            @include('acente.sigorta._durum_badge', ['durum' => $police->durum])
        </div>
    </div>

    {{-- Poliçe İsleniyor Durumu --}}
    @if($police->durum === 'police_isleniyor')
    <div class="alert alert-warning d-flex align-items-center gap-2" id="islenme-uyari">
        <div class="spinner-border spinner-border-sm text-warning"></div>
        Poliçe PAO-Net tarafından işleniyor, lütfen bekleyin...
    </div>
    <script>
    (function poll() {
        setTimeout(async function () {
            const res = await fetch('{{ route("acente.sigorta.police-uretim-durum", $police) }}', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.durum === 'tamamlandi') {
                location.reload();
            } else {
                poll();
            }
        }, 3000);
    })();
    </script>
    @endif

    <div class="row g-4">
        {{-- Sol kolon --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light py-2">Sigortalı Bilgileri</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted w-40">Ad Soyad</th><td>{{ $police->sigortali_adi }} {{ $police->sigortali_soyadi }}</td></tr>
                        <tr><th class="text-muted">Kimlik</th><td class="fw-mono">{{ $police->sigortali_kimlik }} <span class="badge bg-secondary ms-1">{{ strtoupper($police->kimlik_tipi) }}</span></td></tr>
                        <tr><th class="text-muted">Doğum</th><td>{{ $police->sigortali_dogum?->format('d.m.Y') }}</td></tr>
                        <tr><th class="text-muted">Ülke</th><td>{{ $police->gidilecek_ulke }}</td></tr>
                        <tr><th class="text-muted">Gidiş</th><td>{{ $police->baslangic_tarihi?->format('d.m.Y') }}</td></tr>
                        <tr><th class="text-muted">Dönüş</th><td>{{ $police->bitis_tarihi?->format('d.m.Y') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sağ kolon --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light py-2">Poliçe & Fiyat</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted w-40">Poliçe No</th><td class="fw-mono text-primary">{{ $police->police_no ?: '—' }}</td></tr>
                        <tr><th class="text-muted">Ürün</th><td>{{ $police->paonet_urun_kodu ?: '—' }}</td></tr>
                        <tr><th class="text-muted">API Fiyat</th><td>{{ $police->api_doviz_tutar }} {{ $police->api_doviz_turu }}</td></tr>
                        <tr><th class="text-muted">Kur</th><td>{{ $police->api_kur }}</td></tr>
                        <tr><th class="text-muted">Maliyet</th><td class="text-muted">{{ number_format($police->maliyet_tl, 2) }} ₺</td></tr>
                        <tr><th class="text-muted">Satış Fiyatı</th><td class="fw-bold text-success fs-5">{{ number_format($police->satilan_fiyat_tl, 2) }} ₺</td></tr>
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
            <a href="{{ route('acente.sigorta.belge', [$police, 'police']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Poliçe PDF
            </a>
            @endif
            @if($police->makbuz_link)
            <a href="{{ route('acente.sigorta.belge', [$police, 'makbuz']) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-receipt me-1"></i> Makbuz
            </a>
            @endif
            @if($police->sertifika_link)
            <a href="{{ route('acente.sigorta.belge', [$police, 'sertifika']) }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fas fa-certificate me-1"></i> Sertifika (TR)
            </a>
            @endif
            @if($police->ing_sertifika_link)
            <a href="{{ route('acente.sigorta.belge', [$police, 'ing-sertifika']) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-certificate me-1"></i> Sertifika (EN)
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- İptal Formu --}}
    @if($police->iptalEdilebilirMi())
    <div class="card shadow-sm mt-4 border-danger">
        <div class="card-header fw-bold text-danger bg-light py-2">Poliçe İptali</div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->has('iptal'))
            <div class="alert alert-danger">{{ $errors->first('iptal') }}</div>
            @endif
            <form method="POST" action="{{ route('acente.sigorta.iptal', $police) }}"
                onsubmit="return confirm('Bu poliçeyi iptal etmek istediğinizden emin misiniz?')">
                @csrf
                @method('DELETE')
                <div class="mb-3">
                    <label class="form-label">Mükerrer Poliçe No <span class="text-danger">*</span></label>
                    <input type="text" name="mukerrer_police" class="form-control" required
                        placeholder="Aynı sigortalıya ait aktif poliçe no">
                    <div class="form-text text-muted">PAO-Net mükerrer poliçe kuralı — iptal için zorunludur.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">İptal Nedeni <span class="text-danger">*</span></label>
                    <select name="iptal_nedeni" class="form-select" required>
                        <option value="">— Seçiniz —</option>
                        <option value="Mükerrer Poliçe">Mükerrer Poliçe</option>
                        <option value="Farklı Şirket Mükerrer">Farklı Şirket Mükerrer</option>
                        <option value="Tur İptali">Tur İptali</option>
                        <option value="Vize Başvurusu Yapılamaması">Vize Başvurusu Yapılamaması</option>
                        <option value="Vize Reddi">Vize Reddi</option>
                        <option value="Diğer">Diğer</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-ban me-1"></i> İptal Talebi Gönder
                </button>
            </form>
        </div>
    </div>
    @elseif($police->durum === 'iptal' || $police->durum === 'iptal_bekliyor')
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h6 class="fw-bold text-danger">İptal Bilgisi</h6>
            <p class="mb-1"><strong>Neden:</strong> {{ $police->iptal_nedeni ?? '—' }}</p>
            <p class="mb-0"><strong>Tarih:</strong> {{ $police->iptal_tarih?->format('d.m.Y H:i') ?? '—' }}</p>
        </div>
    </div>
    @endif
</div>
@endsection
