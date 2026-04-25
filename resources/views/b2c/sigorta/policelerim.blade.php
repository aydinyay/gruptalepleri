@extends('b2c.layouts.app')

@section('title', 'Poliçelerim')

@section('content')
<div class="container py-5" style="max-width:860px">
    <h3 class="fw-bold mb-4">Sigorta Poliçelerim</h3>

    @if($policeler->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="fas fa-shield-alt fa-3x mb-3 opacity-25"></i>
        <p>Henüz bir sigorta poliçeniz bulunmuyor.</p>
        <a href="{{ route('b2c.sigorta.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Sigorta Yaptır
        </a>
    </div>
    @else
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sigortalı</th>
                        <th>Gidilecek Ülke</th>
                        <th>Seyahat Tarihleri</th>
                        <th>Poliçe No</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policeler as $p)
                    <tr>
                        <td class="fw-bold">{{ $p->sigortali_adi }} {{ $p->sigortali_soyadi }}</td>
                        <td>{{ $p->gidilecek_ulke }}</td>
                        <td class="small text-muted">
                            {{ \Carbon\Carbon::parse($p->baslangic_tarihi)->format('d.m.Y') }}
                            — {{ \Carbon\Carbon::parse($p->bitis_tarihi)->format('d.m.Y') }}
                        </td>
                        <td class="font-monospace small">{{ $p->police_no ?: '—' }}</td>
                        <td>{{ number_format($p->satilan_fiyat_tl, 2) }} ₺</td>
                        <td>
                            @if($p->durum === 'tamamlandi')
                                <span class="badge bg-success">Aktif</span>
                            @elseif(in_array($p->durum, ['police_isleniyor', 'odeme_bekleniyor']))
                                <span class="badge bg-warning text-dark">İşleniyor</span>
                            @elseif($p->durum === 'iptal')
                                <span class="badge bg-secondary">İptal</span>
                            @else
                                <span class="badge bg-danger">Hata</span>
                            @endif
                        </td>
                        <td>
                            @if($p->durum === 'tamamlandi' && $p->pdf_link)
                            <a href="{{ route('b2c.sigorta.belge', [$p, 'police']) }}" target="_blank"
                                class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            @elseif(in_array($p->durum, ['police_isleniyor', 'odeme_bekleniyor']))
                            <a href="{{ route('b2c.sigorta.durum', $p) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-clock me-1"></i> Takip
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($policeler->hasPages())
    <div class="mt-3">{{ $policeler->links() }}</div>
    @endif

    <div class="text-center mt-4">
        <a href="{{ route('b2c.sigorta.create') }}" class="btn btn-outline-primary">
            <i class="fas fa-plus me-2"></i> Yeni Poliçe
        </a>
    </div>
    @endif
</div>
@endsection
