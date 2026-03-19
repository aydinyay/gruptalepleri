<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('acente.partials.theme-styles')
    <title>Finans Merkezi - Acente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-acente active="finance" />

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1"><i class="fas fa-wallet me-2 text-primary"></i>Finans ve Tahsilat</h3>
            <p class="text-muted mb-0">Talep bazli ve genel odeme/tahsilat kayitlarinizi buradan takip edebilirsiniz.</p>
        </div>
    </div>

    @if($coreReady)
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Havale / EFT Dekont Bildirimi</div>
            <div class="card-body">
                <form method="post" action="{{ route('acente.finance.receipts.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Finans Kaydi</label>
                        <select name="finance_record_id" class="form-select" required>
                            <option value="">Seciniz</option>
                            @foreach($records as $record)
                                <option value="{{ $record->id }}">
                                    #{{ $record->id }} - {{ $record->document_ref ?: $record->title }} ({{ number_format((float) $record->remaining_amount, 2, ',', '.') }} {{ $record->currency }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tutar</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Para Birimi</label>
                        <input type="text" name="currency" class="form-control" value="TRY" maxlength="8" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Odeme Tarihi</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Banka</label>
                        <input type="text" name="bank_name" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gonderen Adi</label>
                        <input type="text" name="sender_name" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Referans / Aciklama</label>
                        <input type="text" name="sender_reference" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dekont Dosyasi</label>
                        <input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Not (Opsiyonel)</label>
                        <input type="text" name="note" class="form-control" maxlength="1000">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-upload me-1"></i>Dekontu Gonder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include('finance.partials.overview')

    @if($coreReady)
        <div class="card shadow-sm mt-4">
            <div class="card-header fw-semibold">Son Dekont Bildirimlerim</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kayit</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>Dekont</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td>{{ $submission->id }}</td>
                            <td>{{ $submission->record->document_ref ?? ('#'.$submission->finance_record_id) }}</td>
                            <td>{{ number_format((float) $submission->amount, 2, ',', '.') }} {{ $submission->currency }}</td>
                            <td>{{ optional($submission->payment_date)->format('d.m.Y') ?: '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $submission->status }}</span></td>
                            <td>
                                @if($submission->receipt_path)
                                    <a href="{{ $submission->receipt_path }}" target="_blank" class="btn btn-sm btn-outline-primary">Gor</a>
                                @else
                                    <span class="text-muted">Yok</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Henuz dekont bildirimi yok.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@include('acente.partials.theme-script')
</body>
</html>
