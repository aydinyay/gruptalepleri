<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Abonelikten Ayrıl — GrupTalepleri</title>
<style>
body { margin:0; padding:0; background:#f4f6f9; font-family:'Segoe UI',Arial,sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.card { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.09); max-width:440px; width:100%; padding:48px 40px; text-align:center; }
.logo { font-size:20px; font-weight:800; color:#0f2544; margin-bottom:32px; }
.logo span { color:#e8a020; }
.icon { font-size:40px; margin-bottom:16px; }
h2 { font-size:22px; font-weight:800; color:#0f2544; margin:0 0 12px; }
p { color:#6c757d; font-size:15px; line-height:1.65; margin:0 0 32px; }
.btn-danger { display:inline-block; background:#dc3545; color:#fff; text-decoration:none; font-weight:700; font-size:15px; padding:14px 36px; border-radius:10px; border:none; cursor:pointer; width:100%; }
.btn-danger:hover { background:#bb2d3b; }
.btn-back { display:inline-block; margin-top:14px; color:#6c757d; font-size:13px; text-decoration:underline; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">✈ Grup<span>Talepleri</span></div>
    <div class="icon">📧</div>
    <h2>Abonelikten Ayrıl?</h2>
    <p>
        <strong>{{ $user->email }}</strong> adresine gönderilen e-postaları
        artık almak istemiyorsanız lütfen aşağıdaki butona tıklayın.
    </p>
    <form method="POST" action="{{ URL::signedRoute('abonelik.iptal', ['user' => $user->id]) }}">
        @csrf
        <button type="submit" class="btn-danger">Ayrılmak İstiyorum</button>
    </form>
    <a href="/" class="btn-back">Vazgeç, ana sayfaya dön</a>
</div>
</body>
</html>
