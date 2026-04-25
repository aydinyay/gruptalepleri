@extends('layouts.acente-sigorta')

@section('title', 'Sigorta Poliçelerim')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">🛡 Sigorta Poliçelerim</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('acente.sigorta.kar-raporu') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-chart-bar"></i> Kâr Raporum
            </a>
            <a href="{{ route('acente.sigorta.mbf') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-alt"></i> MBF
            </a>
            <a href="{{ route('acente.sigorta.toplu') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-users"></i> Toplu
            </a>
            <a href="{{ route('acente.sigorta.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Yeni Poliçe
            </a>
        </div>
    </div>

    {{-- Filtreler --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">TC / Pasaport</label>
                    <input type="text" name="kimlik" value="{{ request('kimlik') }}" class="form-control form-control-sm" placeholder="TC No">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Poliçe No</label>
                    <input type="text" name="police_no" value="{{ request('police_no') }}" class="form-control form-control-sm" placeholder="Poliçe No">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Durum</label>
                    <select name="durum" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <option value="tamamlandi" @selected(request('durum')==='tamamlandi')>Tamamlandı</option>
                        <option value="police_isleniyor" @selected(request('durum')==='police_isleniyor')>İşleniyor</option>
                        <option value="iptal" @selected(request('durum')==='iptal')>İptal</option>
                        <option value="hata" @selected(request('durum')==='hata')>Hata</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Başlangıç (min)</label>
                    <input type="date" name="tarih_bas" value="{{ request('tarih_bas') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Başlangıç (max)</label>
                    <input type="date" name="tarih_bit" value="{{ request('tarih_bit') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-fill">Filtrele</button>
                    <a href="{{ route('acente.sigorta.index') }}" class="btn btn-outline-secondary btn-sm">Sıfırla</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Poliçe No</th>
                            <th>Sigortalı</th>
                            <th>TC / Pasaport</th>
                            <th>Ülke</th>
                            <th>Seyahat</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policeler as $p)
                        <tr>
                            <td>
                                @if($p->police_no)
                                    <span class="fw-mono text-primary">{{ $p->police_no }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $p->sigortali_adi }} {{ $p->sigortali_soyadi }}</td>
                            <td class="fw-mono small">{{ $p->sigortali_kimlik }}</td>
                            <td>{{ $p->gidilecek_ulke }}</td>
                            <td class="small">
                                {{ $p->baslangic_tarihi?->format('d.m.Y') }}
                                @if($p->bitis_tarihi)
                                    → {{ $p->bitis_tarihi->format('d.m.Y') }}
                                @endif
                            </td>
                            <td class="fw-bold">
                                {{ number_format($p->satilan_fiyat_tl, 2) }} ₺
                            </td>
                            <td>@include('acente.sigorta._durum_badge', ['durum' => $p->durum])</td>
                            <td>
                                <a href="{{ route('acente.sigorta.show', $p) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-shield-alt fa-2x mb-2 d-block"></i>
                                Henüz poliçe yok.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($policeler->hasPages())
        <div class="card-footer">{{ $policeler->links() }}</div>
        @endif
    </div>
</div>
@endsection
