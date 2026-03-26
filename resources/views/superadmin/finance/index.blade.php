<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Finans Merkezi - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-superadmin active="finance" />

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Finans Core Kontrol Merkezi</h3>
            <p class="text-muted mb-0">Tum rollerin odeme/tahsilat akislarini merkezi olarak izleyin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.finance.receipts.index') }}" class="btn btn-sm btn-outline-warning">Dekont Onaylari</a>
            <a href="{{ route('superadmin.finance.index') }}" class="btn btn-sm btn-outline-secondary">Yenile</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($coreReady)
        @include('finance.partials.agency-balance-overview')
    @endif

    @if($coreReady)
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-semibold">Serbest Tahsilat Ac</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('superadmin.finance.manual-record.store') }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label mb-1">Acente (opsiyonel)</label>
                                <select name="agency_user_id" class="form-select form-select-sm">
                                    <option value="">Seciniz</option>
                                    @foreach($agencyUsers as $agencyUser)
                                        <option value="{{ $agencyUser->id }}">{{ $agencyUser->name }} ({{ $agencyUser->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Baslik</label>
                                <input type="text" name="title" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Toplam</label>
                                <input type="number" step="0.01" min="0" name="gross_amount" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Para Birimi</label>
                                <input type="text" name="currency" value="TRY" class="form-control form-control-sm" maxlength="8" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Vade Tarihi</label>
                                <input type="date" name="due_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Not</label>
                                <input type="text" name="notes" class="form-control form-control-sm" maxlength="2000">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Tahsilat Kaydi Olustur</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-semibold">Manuel Finans Islemi</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('superadmin.finance.manual-transaction.store') }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label mb-1">Finans Kaydi</label>
                                <select name="finance_record_id" class="form-select form-select-sm" required>
                                    <option value="">Seciniz</option>
                                    @foreach($openRecords as $recordOption)
                                        <option value="{{ $recordOption->id }}">
                                            #{{ $recordOption->id }} {{ $recordOption->document_ref ?: '-' }} - {{ number_format((float) $recordOption->remaining_amount, 2, ',', '.') }} {{ $recordOption->currency }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Tutar</label>
                                <input type="number" step="0.01" min="0.01" name="gross_amount" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Kur</label>
                                <input type="text" name="currency" value="TRY" class="form-control form-control-sm" maxlength="8" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Yon</label>
                                <select name="direction" class="form-select form-select-sm" required>
                                    <option value="in">Tahsilat (in)</option>
                                    <option value="out">Cikis (out)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Yontem</label>
                                <select name="method" class="form-select form-select-sm" required>
                                    @foreach(['manual','card','bank_transfer','eft','cash','other'] as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Durum</label>
                                <select name="status" class="form-select form-select-sm" required>
                                    @foreach(['approved','pending','awaiting_validation','rejected','cancelled'] as $state)
                                        <option value="{{ $state }}">{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Islem Tarihi</label>
                                <input type="date" name="payment_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-3">
                                <label class="form-label mb-1">Masraf</label>
                                <input type="number" step="0.01" min="0" name="fee_amount" value="0" class="form-control form-control-sm">
                            </div>
                            <div class="col-3">
                                <label class="form-label mb-1">Komisyon</label>
                                <input type="number" step="0.01" min="0" name="commission_amount" value="0" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Not</label>
                                <input type="text" name="notes" class="form-control form-control-sm" maxlength="2000">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Finans Islemi Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-semibold">Iade Kaydi</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('superadmin.finance.refund.store') }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label mb-1">Finans Kaydi</label>
                                <select name="finance_record_id" class="form-select form-select-sm" required>
                                    <option value="">Seciniz</option>
                                    @foreach($openRecords as $recordOption)
                                        <option value="{{ $recordOption->id }}">
                                            #{{ $recordOption->id }} {{ $recordOption->document_ref ?: '-' }} - Odenen: {{ number_format((float) $recordOption->paid_amount, 2, ',', '.') }} {{ $recordOption->currency }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Iade Tutar</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Kur</label>
                                <input type="text" name="currency" value="TRY" class="form-control form-control-sm" maxlength="8" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Yontem</label>
                                <select name="method" class="form-select form-select-sm" required>
                                    @foreach(['manual','card','bank_transfer','eft','cash','other'] as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Iade Tarihi</label>
                                <input type="date" name="payment_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="processNowSuperadmin" name="process_now">
                                    <label class="form-check-label" for="processNowSuperadmin">
                                        Hemen isle (out transaction olustur)
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Gerekce</label>
                                <input type="text" name="reason" class="form-control form-control-sm" maxlength="2000">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">Iade Kaydi Olustur</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @include('finance.partials.payment-plan-admin')
    @endif

    @include('finance.partials.overview')
</div>

@include('admin.partials.theme-script')
</body>
</html>
