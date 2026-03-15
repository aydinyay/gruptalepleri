<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Opsiyon Uyarısı</title>
<style>
  body { margin:0; padding:0; background:#f0f2f5; font-family:'Segoe UI',Arial,sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:32px 40px; text-align:center; }
  .header .logo { font-size:1.5rem; font-weight:800; color:#e94560; letter-spacing:1px; }
  .header .logo span { color:#fff; }
  .header h2 { color:#fff; font-size:1.1rem; font-weight:400; margin:8px 0 0; opacity:0.75; }
  .body { padding:36px 40px; }
  .badge { display:inline-block; background:#f59e0b; color:#fff; font-size:0.75rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:4px 12px; border-radius:50px; margin-bottom:20px; }
  .gtpnr { font-size:2rem; font-weight:800; color:#1a1a2e; letter-spacing:2px; margin:0 0 24px; }
  .countdown { background:#fff8e1; border:2px solid #f59e0b; border-radius:10px; padding:18px 24px; margin-bottom:24px; text-align:center; }
  .countdown .saat { font-size:3rem; font-weight:900; color:#f59e0b; line-height:1; }
  .countdown .saat-label { font-size:0.85rem; color:#92400e; font-weight:600; margin-top:4px; }
  .info-table { width:100%; border-collapse:collapse; margin-bottom:28px; }
  .info-table tr td { padding:10px 14px; font-size:0.92rem; }
  .info-table tr td:first-child { color:#6c757d; font-weight:600; width:38%; }
  .info-table tr td:last-child { color:#1a1a2e; font-weight:500; }
  .info-table tr:nth-child(odd) td { background:#f8f9fa; }
  .info-table tr:first-child td:first-child { border-radius:8px 0 0 0; }
  .info-table tr:first-child td:last-child { border-radius:0 8px 0 0; }
  .btn { display:inline-block; background:#f59e0b; color:#fff; text-decoration:none; padding:14px 32px; border-radius:8px; font-weight:700; font-size:0.95rem; margin-top:8px; }
  .footer { background:#f8f9fa; padding:20px 40px; text-align:center; font-size:0.78rem; color:#adb5bd; border-top:1px solid #e9ecef; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo">✈ Grup<span>Talepleri</span></div>
    <h2>Opsiyon Süresi Uyarısı</h2>
  </div>
  <div class="body">
    <div class="badge">⚠️ Opsiyon Uyarısı</div>
    <div class="gtpnr">{{ $gtpnr }}</div>
    <div class="countdown">
      <div class="saat">{{ $saatKaldi }}</div>
      <div class="saat-label">SAAT KALDI</div>
    </div>
    <table class="info-table">
      <tr><td>Havayolu</td><td><strong>{{ $airline }}</strong></td></tr>
      <tr><td>Opsiyon Bitiş</td><td><strong>{{ $opsiyonBitis }}</strong></td></tr>
    </table>
    <a href="{{ $adminUrl }}" class="btn">Talebi İncele →</a>
  </div>
  <div class="footer">
    Bu e-posta GrupTalepleri platformu tarafından otomatik olarak gönderilmiştir.
  </div>
</div>
</body>
</html>
