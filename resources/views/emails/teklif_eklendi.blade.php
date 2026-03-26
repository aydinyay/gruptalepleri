<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teklifiniz Hazır</title>
<style>
  body { margin:0; padding:0; background:#f0f2f5; font-family:'Segoe UI',Arial,sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:32px 40px; text-align:center; }
  .header .logo { font-size:1.5rem; font-weight:800; color:#e94560; letter-spacing:1px; }
  .header .logo span { color:#fff; }
  .header h2 { color:#fff; font-size:1.1rem; font-weight:400; margin:8px 0 0; opacity:0.75; }
  .body { padding:36px 40px; }
  .badge { display:inline-block; background:#198754; color:#fff; font-size:0.75rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:4px 12px; border-radius:50px; margin-bottom:20px; }
  .gtpnr { font-size:2rem; font-weight:800; color:#1a1a2e; letter-spacing:2px; margin:0 0 16px; }
  .airline-box { background:#f0f8f0; border:1px solid #c3e6cb; border-radius:10px; padding:16px 20px; margin-bottom:28px; }
  .airline-box .label { font-size:0.75rem; color:#6c757d; font-weight:600; text-transform:uppercase; letter-spacing:1px; }
  .airline-box .value { font-size:1.3rem; font-weight:800; color:#198754; margin-top:4px; }
  .info { font-size:0.92rem; color:#495057; line-height:1.7; margin-bottom:28px; }
  .btn { display:inline-block; background:#e94560; color:#fff; text-decoration:none; padding:14px 32px; border-radius:8px; font-weight:700; font-size:0.95rem; }
  .footer { background:#f8f9fa; padding:20px 40px; text-align:center; font-size:0.78rem; color:#adb5bd; border-top:1px solid #e9ecef; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo">✈ Grup<span>Talepleri</span></div>
    <h2>Talebiniz İçin Teklif Geldi</h2>
  </div>
  <div class="body">
    <div class="badge">✈️ Teklif Hazır</div>
    <div class="gtpnr">{{ $gtpnr }}</div>
    <div class="airline-box">
      <div class="label">Havayolu</div>
      <div class="value">{{ $airline }}</div>
    </div>
    <p class="info">
      <strong>{{ $gtpnr }}</strong> numaralı talebiniz için yeni bir teklif oluşturuldu.
      Teklif detaylarını incelemek ve kabul etmek için aşağıdaki butona tıklayın.
    </p>
    <a href="{{ $acenteUrl }}" class="btn">Teklifi İncele →</a>
  </div>
  <div class="footer">
    Bu e-posta GrupTalepleri platformu tarafından otomatik olarak gönderilmiştir.
  </div>
</div>
</body>
</html>
