<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Dekont Dogrulama - Finans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
@if(auth()->user()->role === 'superadmin')
    <x-navbar-superadmin active="finance" />
@else
    <x-navbar-admin active="finance" />
@endif

@php
    $isSuperadmin = auth()->user()->role === 'superadmin';
    $indexRoute = $isSuperadmin ? 'superadmin.finance.receipts.index' : 'admin.finance.receipts.index';
    $updateRoute = $isSuperadmin ? 'superadmin.finance.receipts.update' : 'admin.finance.receipts.update';
    $financeRoute = $isSuperadmin ? 'superadmin.finance.index' : 'admin.finance.index';
@endphp

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="mb-1"><i class="fas fa-file-invoice-dollar me-2 text-warning"></i>Dekont Dogrulama</h3>
            <p class="text-muted mb-0">Acentelerin havale/EFT bildirimlerini buradan onaylayabilirsiniz.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route($financeRoute) }}" class="btn btn-sm btn-outline-secondary">Finans Ozet</a>
        </div>
    </div>

    @if(!$coreReady)
        <div class="alert alert-warning">Finance receipt tablolari henuz hazir degil.</div>
    @else
        <form method="get" action="{{ route($indexRoute) }}" class="d-flex flex-wrap gap-2 mb-3">
            <select name="status" class="form-select form-select-sm" style="min-width: 220px;">
                <option value="">Tum Durumlar</option>
                @foreach(['pending', 'matched', 'needs_review', 'rejected', 'insufficient_data'] as $option)
                    <option value="{{ $option }}" @selected(($status ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filtrele</button>
            <a href="{{ route($indexRoute) }}" class="btn btn-sm btn-outline-secondary">Temizle</a>
        </form>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Acenta</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                        <th>Banka</th>
                        <th>Dekont</th>
                        <th>Durum</th>
                        <th>Aksiyon</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td>{{ $submission->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $submission->agencyUser->name ?? '-' }}</div>
                                <small class="text-muted">{{ $submission->agencyUser->email ?? '-' }}</small>
                            </td>
                            <td>{{ number_format((float) $submission->amount, 2, ',', '.') }} {{ $submission->currency }}</td>
                            <td>{{ optional($submission->payment_date)->format('d.m.Y') ?: '-' }}</td>
                            <td>
                                <div>{{ $submission->bank_name ?: '-' }}</div>
                                <small class="text-muted">{{ $submission->sender_reference ?: '-' }}</small>
                            </td>
                            <td>
                                @if($submission->receipt_path)
                                    <a href="{{ $submission->receipt_path }}" target="_blank" class="btn btn-sm btn-outline-primary">Gor</a>
                                @else
                                    <span class="text-muted">Yok</span>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $submission->status }}</span></td>
                            <td>
                                <form method="post" action="{{ route($updateRoute, $submission) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('patch')
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="matched">Onayla</option>
                                        <option value="needs_review">Incele</option>
                                        <option value="insufficient_data">Eksik Bilgi</option>
                                        <option value="rejected">Reddet</option>
                                    </select>
                                    <input type="text" name="review_note" class="form-control form-control-sm" placeholder="Not">
                                    <button class="btn btn-sm btn-success">Kaydet</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Dekont bildirimi bulunamadi.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($submissions, 'links'))
                <div class="card-footer">{{ $submissions->links() }}</div>
            @endif
        </div>
    @endif
</div>

@include('admin.partials.theme-script')
</body>
</html>
