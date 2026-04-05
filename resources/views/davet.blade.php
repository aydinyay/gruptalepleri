<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daveti Kabul Et — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<div class="container" style="max-width: 480px;">
    <div class="card shadow-sm mt-5">
        <div class="card-body p-4">
            <h4 class="mb-1 fw-bold">GrupTalepleri'ne Hoş Geldiniz</h4>
            <p class="text-muted mb-4">
                Hesabınızı aktifleştirmek için adınızı ve şifrenizi belirleyin.<br>
                <span class="small">Davet adresi: <strong>{{ $calisan->email }}</strong></span>
            </p>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="post" action="{{ route('davet.kabul', $token) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Adınız Soyadınız</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="Ayşe Yılmaz">
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="mb-4">
                    <label class="form-label">Şifre Tekrar</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Hesabı Aktifleştir</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
