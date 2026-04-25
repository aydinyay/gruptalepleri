@extends('layouts.acente-sigorta')

@section('title', 'Toplu Sigorta — Sonuç')

@section('content')
<div class="container py-4" style="max-width:800px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('acente.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Toplu Sigorta — Poliçe Üretimi</h4>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1">{{ $batch->islem_adi }}</h6>
                    <small class="text-muted">Batch #{{ $batch->id }} — {{ $batch->toplam }} kişi</small>
                </div>
                <span id="durum-badge" class="badge bg-primary fs-6">
                    @if($batch->durum === 'tamamlandi') Tamamlandı
                    @elseif($batch->durum === 'hata') Hata
                    @else İşleniyor...
                    @endif
                </span>
            </div>

            <div class="progress mb-2" style="height:22px">
                <div id="ilerleme-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                    role="progressbar"
                    style="width:{{ $batch->toplam > 0 ? round(($batch->tamamlanan/$batch->toplam)*100) : 0 }}%">
                </div>
            </div>
            <p id="ilerleme-metin" class="text-muted small">
                {{ $batch->tamamlanan }} başarılı / {{ $batch->basarisiz }} hatalı / {{ $batch->toplam }} toplam
            </p>

            <div id="bitti-panel" class="{{ in_array($batch->durum, ['tamamlandi','hata']) ? '' : 'd-none' }} mt-3">
                @if($batch->durum === 'tamamlandi')
                <div class="alert alert-success mb-2">
                    <i class="fas fa-check-circle me-2"></i>
                    Tüm poliçeler işlendi!
                    <a href="{{ route('acente.sigorta.index') }}" class="alert-link ms-2">Poliçe listesine git →</a>
                </div>
                @elseif($batch->durum === 'hata')
                <div class="alert alert-danger mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ödeme başarısız — poliçe düzenlenmedi.
                    <a href="{{ route('acente.sigorta.toplu') }}" class="alert-link ms-2">Yeniden dene</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if(!in_array($batch->durum, ['tamamlandi', 'hata']))
<script>
const csrf    = document.querySelector('meta[name="csrf-token"]').content;
const batchId = {{ $batch->id }};
const toplam  = {{ $batch->toplam }};

async function pollUret() {
    try {
        const res  = await fetch(`/acente/sigorta/toplu/${batchId}/uret-poll`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const data = await res.json();

        const done = (data.tamamlanan || 0) + (data.basarisiz || 0);
        const pct  = toplam > 0 ? Math.round((done / toplam) * 100) : 0;
        document.getElementById('ilerleme-bar').style.width = pct + '%';
        document.getElementById('ilerleme-metin').textContent =
            `${data.tamamlanan} başarılı / ${data.basarisiz} hatalı / ${toplam} toplam`;

        if (data.tamamlandi) {
            document.getElementById('durum-badge').className = 'badge bg-success fs-6';
            document.getElementById('durum-badge').textContent = 'Tamamlandı';
            document.getElementById('ilerleme-bar').classList.remove('progress-bar-animated');
            document.getElementById('ilerleme-bar').style.width = '100%';
            document.getElementById('bitti-panel').innerHTML = `
                <div class="alert alert-success mb-2">
                    <i class="fas fa-check-circle me-2"></i>
                    Tüm poliçeler işlendi!
                    <a href="/acente/sigorta" class="alert-link ms-2">Poliçe listesine git →</a>
                </div>`;
            document.getElementById('bitti-panel').classList.remove('d-none');
        } else {
            setTimeout(pollUret, 3000);
        }
    } catch (_) {
        setTimeout(pollUret, 5000);
    }
}

setTimeout(pollUret, 2000);
</script>
@endif
@endpush
@endsection
