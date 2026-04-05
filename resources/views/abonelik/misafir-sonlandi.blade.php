<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ isset($yenidenUye) ? 'Abonelik Başlatıldı' : 'Abonelik Sonlandırıldı' }} — GrupTalepleri</title>
<style>
body { margin:0; padding:0; background:#f4f6f9; font-family:'Segoe UI',Arial,sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.card { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.09); max-width:440px; width:100%; padding:48px 40px; text-align:center; }
.logo { font-size:20px; font-weight:800; color:#0f2544; margin-bottom:32px; }
.logo span { color:#e8a020; }
.icon { font-size:44px; margin-bottom:16px; }
h2 { font-size:22px; font-weight:800; color:#0f2544; margin:0 0 12px; }
p { color:#6c757d; font-size:15px; line-height:1.65; margin:0 0 28px; }
.btn-primary { display:block; background:#e8a020; color:#fff; text-decoration:none; font-weight:700; font-size:15px; padding:14px 36px; border-radius:10px; border:none; cursor:pointer; width:100%; }
.muted { font-size:13px; color:#adb5bd; margin-top:20px; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">✈ Grup<span>Talepleri</span></div>

    @if(isset($yenidenUye))
        <div class="icon">✅</div>
        <h2>Aboneliğiniz Başlatıldı</h2>
        <p><strong>{{ $abone->email }}</strong> adresine tekrar e-posta gönderilecektir.</p>
        <a href="/" class="btn-primary">Ana Sayfaya Dön</a>
    @else
        <div class="icon">✔️</div>
        <h2>E-posta Aboneliğiniz Sonlandırıldı</h2>
        <p><strong>{{ $abone->email }}</strong> adresine artık e-posta gönderilmeyecektir.</p>
        <p style="margin-bottom:16px;">E-posta aboneliğini tekrar başlatmak için aşağıdaki butona tıklayın.</p>
        <form method="POST" action="{{ route('abone.baslat.onayla', $abone->token) }}">
            @csrf
            <button type="submit" class="btn-primary">Üye Ol</button>
        </form>
    @endif

    <p class="muted">GrupTalepleri — Seyahat Acenteleri Platformu</p>
</div>
</body>
</html>
