@extends('layouts.admin-sigorta')

@section('title', 'Tüm Sigorta Poliçeleri')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">🛡 Sigorta Poliçeleri</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sigorta.markup') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sliders-h me-1"></i> Markup Ayarları
            </a>
            <a href="{{ route('admin.sigorta.kar-raporu') }}" class="btn btn-sm btn-outline-success">
                <i class="fas fa-chart-bar me-1"></i> Kâr Raporu
            </a>
            <a href="{{ route('admin.sigorta.batchler') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-list me-1"></i> Batch İşlemler
            </a>
            <button type="button" id="btn-iptal-kontrol" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-sync me-1"></i> İptal Kontrol
            </button>
        </div>
    </div>

    {{-- Filtreler --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <input type="text" name="kimlik" value="{{ request('kimlik') }}"
                        class="form-control form-control-sm" placeholder="TC / Pasaport">
                </div>
                <div class="col-md-2">
                    <input type="text" name="police_no" value="{{ request('police_no') }}"
                        class="form-control form-control-sm" placeholder="Poliçe No">
                </div>
                <div class="col-md-1">
                    <select name="kanal" class="form-select form-select-sm">
                        <option value="">Kanal</option>
                        <option value="b2b" @selected(request('kanal')==='b2b')>B2B</option>
                        <option value="b2c" @selected(request('kanal')==='b2c')>B2C</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="durum" class="form-select form-select-sm">
                        <option value="">Durum</option>
                        <option value="tamamlandi" @selected(request('durum')==='tamamlandi')>Tamamlandı</option>
                        <option value="police_isleniyor" @selected(request('durum')==='police_isleniyor')>İşleniyor</option>
                        <option value="iptal" @selected(request('durum')==='iptal')>İptal</option>
                        <option value="hata" @selected(request('durum')==='hata')>Hata</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="tarih_bas" value="{{ request('tarih_bas') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <input type="date" name="tarih_bit" value="{{ request('tarih_bit') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-fill">Ara</button>
                    <a href="{{ route('admin.sigorta.index') }}" class="btn btn-outline-secondary btn-sm">×</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Poliçe No</th>
                            <th>Sigortalı</th>
                            <th>TC / Pasaport</th>
                            <th>Ülke</th>
                            <th>Seyahat</th>
                            <th>Kanal</th>
                            <th>Acente</th>
                            <th>Maliyet</th>
                            <th>Satış</th>
                            <th>Kâr</th>
                            <th>Durum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policeler as $p)
                        <tr>
                            <td class="text-muted">{{ $p->id }}</td>
                            <td class="fw-mono text-primary">{{ $p->police_no ?: '—' }}</td>
                            <td>{{ $p->sigortali_adi }} {{ $p->sigortali_soyadi }}</td>
                            <td class="fw-mono">{{ $p->sigortali_kimlik }}</td>
                            <td>{{ $p->gidilecek_ulke }}</td>
                            <td>
                                {{ $p->baslangic_tarihi?->format('d.m.Y') }}
                                @if($p->bitis_tarihi) → {{ $p->bitis_tarihi->format('d.m.Y') }} @endif
                            </td>
                            <td>
                                <span class="badge {{ $p->kanal === 'b2c' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                    {{ strtoupper($p->kanal) }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $p->acente?->name ?? '—' }}</td>
                            <td class="text-muted">{{ number_format($p->maliyet_tl, 2) }} ₺</td>
                            <td class="fw-bold">{{ number_format($p->satilan_fiyat_tl, 2) }} ₺</td>
                            <td class="text-success fw-bold">{{ number_format($p->net_kar_tl, 2) }} ₺</td>
                            <td>@include('acente.sigorta._durum_badge', ['durum' => $p->durum])</td>
                            <td>
                                <a href="{{ route('admin.sigorta.show', $p) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="13" class="text-center text-muted py-5">Poliçe bulunamadı.</td></tr>
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

@push('scripts')
<script>
document.getElementById('btn-iptal-kontrol')?.addEventListener('click', async function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Kontrol ediliyor...';

    try {
        const res  = await fetch('{{ route("admin.sigorta.iptal-kontrol") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await res.json();

        const msg = `İptal kontrolü tamamlandı: ${data.kontrol} poliçe kontrol edildi, ${data.guncellenen} iptal işlendi.`;
        const alert = document.createElement('div');
        alert.className = 'alert alert-info alert-dismissible fade show mt-3';
        alert.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.querySelector('.container-fluid').prepend(alert);
    } catch (e) {
        alert('Bağlantı hatası.');
    }

    this.disabled = false;
    this.innerHTML = '<i class="fas fa-sync me-1"></i> İptal Kontrol';
});
</script>
@endpush
@endsection
