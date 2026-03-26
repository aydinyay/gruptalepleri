<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GrupTalepleri Daveti</title>
<style>
  body { margin:0; padding:0; background:#f0f2f5; font-family:'Segoe UI',Arial,sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:32px 40px; text-align:center; }
  .header .logo { font-size:1.5rem; font-weight:800; color:#e94560; letter-spacing:1px; }
  .header .logo span { color:#fff; }
  .header h2 { color:#fff; font-size:1.1rem; font-weight:400; margin:8px 0 0; opacity:0.75; }
  .body { padding:36px 40px; }
  .badge { display:inline-block; background:#e94560; color:#fff; font-size:0.75rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:4px 12px; border-radius:50px; margin-bottom:20px; }
  .company { font-size:1.4rem; font-weight:800; color:#1a1a2e; margin:0 0 20px; }
  .desc { font-size:0.95rem; color:#495057; line-height:1.7; margin-bottom:24px; }
  .highlight { background:#f8f9fa; border-left:4px solid #e94560; padding:14px 18px; border-radius:0 8px 8px 0; margin-bottom:24px; font-size:0.88rem; color:#495057; }
  .highlight strong { color:#1a1a2e; }
  .btn { display:inline-block; background:#e94560; color:#fff; text-decoration:none; padding:14px 32px; border-radius:8px; font-weight:700; font-size:0.95rem; }
  .footer { background:#f8f9fa; padding:20px 40px; text-align:center; font-size:0.78rem; color:#adb5bd; border-top:1px solid #e9ecef; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo">✈ Grup<span>Talepleri</span></div>
    <h2>Platforma Davet</h2>
  </div>
  <div class="body">
    <div class="badge">✉ Özel Davet</div>
    <div class="company">Sayın {{ $acenteUnvani }},</div>
    <div class="desc">
      <strong>GrupTalepleri.com</strong> platformuna davet edildiniz.<br><br>
      Grup uçuş taleplerini kolayca yönetmek, havayollarından teklifler almak
      ve operasyonlarınızı tek ekrandan takip etmek için aşağıdaki butona tıklayarak
      ücretsiz kayıt olabilirsiniz.
    </div>
    @if($belgeNo)
    <div class="highlight">
      <strong>TÜRSAB Belge No:</strong> {{ $belgeNo }}<br>
      Kayıt sırasında bu numarayı girerek firma bilgilerinizi otomatik doldurabilirsiniz.
    </div>
    @endif
    <a href="{{ $kayitUrl }}" class="btn">Hemen Kayıt Ol →</a>
  </div>
  <div class="footer">
    Bu davet GrupTalepleri ekibi tarafından gönderilmiştir · <a href="{{ $kayitUrl }}" style="color:#adb5bd;">gruptalepleri.com</a>
  </div>
</div>
</body>
</html>
