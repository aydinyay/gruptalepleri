@extends('layouts.acente-sigorta')

@section('title', 'Sigorta Kâr Raporunuz')

@section('content')
<div class="container py-4" style="max-width:800px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('acente.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">📊 Sigorta Kâr Raporunuz</h4>
    </div>

    {{-- Bu Ay --}}
    <h6 class="text-muted mb-3">Bu Ay — {{ now()->format('F Y') }}</h6>
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Poliçe Adedi</div>
                <div class="fs-3 fw-bold text-primary">{{ $buAy->adet ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Toplam Satış</div>
                <div class="fs-3 fw-bold">{{ number_format($buAy->toplam_satis ?? 0, 0) }} ₺</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center py-3 border-success">
                <div class="text-muted small mb-1">Net Kâr</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($buAy->toplam_kar ?? 0, 0) }} ₺</div>
            </div>
        </div>
    </div>

    {{-- Bu Yıl --}}
    <h6 class="text-muted mb-3">Bu Yıl — {{ now()->year }}</h6>
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm text-center py-3 bg-light">
                <div class="text-muted small mb-1">Poliçe Adedi</div>
                <div class="fs-4 fw-bold text-primary">{{ $buYil->adet ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center py-3 bg-light">
                <div class="text-muted small mb-1">Toplam Satış</div>
                <div class="fs-4 fw-bold">{{ number_format($buYil->toplam_satis ?? 0, 0) }} ₺</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center py-3 bg-light border-success">
                <div class="text-muted small mb-1">Net Kâr</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($buYil->toplam_kar ?? 0, 0) }} ₺</div>
            </div>
        </div>
    </div>

    {{-- Günlük Kırılım (Bu Ay) --}}
    @if($gunluk->isNotEmpty())
    <div class="card shadow-sm">
        <div class="card-header fw-bold bg-light py-2">{{ now()->format('F') }} Günlük Kırılım</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Poliçe</th>
                        <th>Kâr</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gunluk as $g)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($g->tarih)->format('d.m.Y') }}</td>
                        <td>{{ $g->adet }}</td>
                        <td class="text-success fw-bold">{{ number_format($g->kar, 2) }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Toplam</td>
                        <td>{{ $gunluk->sum('adet') }}</td>
                        <td class="text-success">{{ number_format($gunluk->sum('kar'), 2) }} ₺</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @else
    <div class="alert alert-info">Bu ay henüz tamamlanmış poliçe bulunmuyor.</div>
    @endif
</div>
@endsection
