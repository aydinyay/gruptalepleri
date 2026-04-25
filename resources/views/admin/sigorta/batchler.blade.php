@extends('layouts.admin-sigorta')

@section('title', 'Toplu İşlemler')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Toplu Sigorta İşlemleri</h4>
        <a href="{{ route('admin.sigorta.index') }}" class="btn btn-sm btn-outline-secondary">← Poliçe Listesi</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>İşlem Adı</th>
                        <th>Acente</th>
                        <th>Kanal</th>
                        <th>Toplam</th>
                        <th>Tamamlanan</th>
                        <th>Hatalı</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batchler as $b)
                    <tr>
                        <td class="text-muted">{{ $b->id }}</td>
                        <td>{{ $b->islem_adi }}</td>
                        <td class="small text-muted">{{ $b->acente?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $b->kanal === 'b2c' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                {{ strtoupper($b->kanal) }}
                            </span>
                        </td>
                        <td>{{ $b->toplam }}</td>
                        <td class="text-success fw-bold">{{ $b->tamamlanan }}</td>
                        <td class="{{ $b->basarisiz > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $b->basarisiz }}</td>
                        <td>
                            @php
                            $renkler = ['bekliyor'=>'secondary','isleniyor'=>'warning','tamamlandi'=>'success','hata'=>'danger'];
                            @endphp
                            <span class="badge bg-{{ $renkler[$b->durum] ?? 'secondary' }}">{{ $b->durum }}</span>
                        </td>
                        <td class="small text-muted">{{ $b->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">Toplu işlem bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batchler->hasPages())
        <div class="card-footer">{{ $batchler->links() }}</div>
        @endif
    </div>
</div>
@endsection
