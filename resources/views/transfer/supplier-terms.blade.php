<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Tedarikci Sozlesme Onayi - GrupTalepleri</title>
    @include('acente.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .gt-transfer-terms-card {
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, .08);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }
        .gt-transfer-terms-text {
            white-space: pre-line;
            line-height: 1.6;
            font-size: .96rem;
        }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="transfer" />

<div class="container py-4">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card gt-transfer-terms-card">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h1 class="h4 fw-bold mb-1">Transfer tedarikci sozlesme onayi</h1>
                    <p class="text-muted mb-0">Paneli acabilmek icin guncel sozlesme versiyonunu onaylamaniz gerekir.</p>
                </div>
                <span class="badge text-bg-primary">Versiyon {{ $termsVersion }}</span>
            </div>

            <div class="border rounded p-3 bg-light gt-transfer-terms-text mb-3">{{ $termsText }}</div>

            <form method="POST" action="{{ route('acente.transfer.supplier.terms.accept') }}">
                @csrf
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="acceptTerms" name="accept_terms" required>
                    <label class="form-check-label" for="acceptTerms">
                        Sozlesmeyi okudum ve kabul ediyorum.
                    </label>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Onayi tamamla</button>
                    <a href="{{ route('acente.transfer.index') }}" class="btn btn-outline-secondary">Transfer aramaya don</a>
                </div>
            </form>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
</body>
</html>

