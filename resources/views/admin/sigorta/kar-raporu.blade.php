@extends('layouts.app')

@section('title', 'Sigorta Kâr Raporu')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">📊 Sigorta Kâr Raporu</h4>
        <a href="{{ route('admin.sigorta.index') }}" class="btn btn-sm btn-outline-secondary">← Poliçe Listesi</a>
    </div>

    {{-- Dönem Seçici --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <select name="donem" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="gun" @selected($donem==='gun')>Günlük</option>
                        <option value="ay" @selected($donem==='ay')>Aylık</option>
                        <option value="yil" @selected($donem==='yil')>Yıllık</option>
                    </select>
                </div>
                @if($donem === 'gun')
                <div class="col-auto">
                    <input type="date" name="tarih" value="{{ request('tarih', today()->format('Y-m-d')) }}" class="form-control form-control-sm">
                </div>
                @else
                <div class="col-auto">
                    <select name="ay" class="form-select form-select-sm">
                        @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" @selected($ay==$m)>{{ $m }}. Ay</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="yil" class="form-select form-select-sm">
                        @foreach(range(2025, now()->year) as $y)
                        <option value="{{ $y }}" @selected($yil==$y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">Filtrele</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Özet Kartları --}}
    <div class="row g-3 mb-4">
        @foreach($ozet as $o)
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">
                            <span class="badge {{ $o->kanal === 'b2c' ? 'bg-warning text-dark' : 'bg-primary' }} me-1">
                                {{ strtoupper($o->kanal) }}
                            </span>
                            Kanal
                        </h6>
                        <span class="badge bg-secondary">{{ $o->adet }} poliçe</span>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <div class="text-muted small">Maliyet</div>
                            <div class="fw-bold">{{ number_format($o->toplam_maliyet, 0) }} ₺</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Satış</div>
                            <div class="fw-bold text-primary">{{ number_format($o->toplam_satis, 0) }} ₺</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Net Kâr</div>
                            <div class="fw-bold text-success">{{ number_format($o->toplam_kar, 0) }} ₺</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        @if($ozet->isEmpty())
        <div class="col-12">
            <div class="alert alert-info">Bu dönemde tamamlanan poliçe bulunamadı.</div>
        </div>
        @endif
    </div>

    {{-- Günlük Grafik Tablosu --}}
    @if($gunluk->isNotEmpty())
    <div class="card shadow-sm">
        <div class="card-header fw-bold bg-light py-2">Günlük Kırılım</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Poliçe Adedi</th>
                        <th>Net Kâr</th>
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
    @endif
</div>
@endsection
