@extends('b2c.layouts.app')

@section('title', 'Poliçeniz Hazırlanıyor')

@section('content')
<div class="container py-5" style="max-width:560px">
    <div class="card shadow-sm border-0 text-center">
        <div class="card-body p-5">

            {{-- İşleniyor --}}
            @if(in_array($police->durum, ['police_isleniyor', 'odeme_bekleniyor']))
            <div id="durum-isleniyor">
                <div class="spinner-border text-primary mb-4" style="width:3.5rem;height:3.5rem"></div>
                <h4 class="fw-bold">Ödemeniz Alındı</h4>
                <p class="text-muted">Poliçeniz hazırlanıyor. Tamamlandığında <strong>e-posta</strong> ve <strong>SMS</strong> ile bildirim alacaksınız.</p>
                <div class="progress mt-3" style="height:6px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                </div>
            </div>

            {{-- Tamamlandı (sayfa yenilenirse) --}}
            @elseif($police->durum === 'tamamlandi')
            <div id="durum-tamam">
                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                <h4 class="fw-bold">Poliçeniz Hazır!</h4>
                <p class="text-muted mb-1">Poliçe No: <strong>{{ $police->police_no }}</strong></p>
                <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                    <a href="{{ route('b2c.sigorta.belge', [$police, 'police']) }}" target="_blank" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> Poliçe PDF
                    </a>
                    @if($police->sertifika_link)
                    <a href="{{ route('b2c.sigorta.belge', [$police, 'sertifika']) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-certificate me-1"></i> Sertifika
                    </a>
                    @endif
                    <a href="{{ route('b2c.sigorta.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Yeni Poliçe
                    </a>
                </div>
            </div>

            {{-- Hata --}}
            @else
            <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
            <h5>Bir Sorun Oluştu</h5>
            <p class="text-muted small">{{ $police->hata_mesaji ?? 'Poliçe işlenirken hata oluştu.' }}</p>
            <a href="{{ route('b2c.sigorta.create') }}" class="btn btn-outline-secondary btn-sm mt-2">Tekrar Dene</a>
            @endif

        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        <i class="fas fa-lock me-1"></i> Ödemeniz güvenle alındı. PAO-Net / Nippon Sigorta.
    </p>
</div>
@endsection

@push('scripts')
@if(in_array($police->durum, ['police_isleniyor', 'odeme_bekleniyor']))
<script>
(function poll() {
    let n = 0;
    const iv = setInterval(async () => {
        n++;
        try {
            const res  = await fetch('{{ route("b2c.sigorta.durum-ajax", $police) }}');
            const data = await res.json();
            if (data.durum === 'tamamlandi') {
                clearInterval(iv);
                document.getElementById('durum-isleniyor').innerHTML = `
                    <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                    <h4 class="fw-bold">Poliçeniz Hazır!</h4>
                    <p class="text-muted mb-1">Poliçe No: <strong>${data.police_no || '—'}</strong></p>
                    <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                        <a href="{{ url('/sigorta/police') }}/${data.police_no ? '{{ $police->id }}' : '{{ $police->id }}'}/belge/police" target="_blank" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Poliçe PDF
                        </a>
                        <a href="{{ route('b2c.sigorta.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Yeni Poliçe
                        </a>
                    </div>`;
            } else if (data.durum === 'hata' || n >= 40) {
                clearInterval(iv);
                document.getElementById('durum-isleniyor').innerHTML =
                    '<i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i><h5>İşlem sürüyor</h5><p class="text-muted small">Poliçeniz sistemde işleniyor. Tamamlandığında <strong>e-posta</strong> ve <strong>SMS</strong> ile bildirim alacaksınız.</p>';
            }
        } catch (_) {}
    }, 3000);
})();
</script>
@endif
@endpush
