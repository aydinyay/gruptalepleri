<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }}</title>
<style>
  body { margin:0; padding:0; background:#f0f2f5; font-family:'Segoe UI',Arial,sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:32px 40px; text-align:center; }
  .header .logo { font-size:1.5rem; font-weight:800; color:#e94560; letter-spacing:1px; }
  .header .logo span { color:#fff; }
  .header h2 { color:#fff; font-size:1.1rem; font-weight:400; margin:8px 0 0; opacity:0.75; }
  .body { padding:36px 40px; }
  .emoji-block { font-size:3rem; text-align:center; margin-bottom:16px; }
  .title { font-size:1.4rem; font-weight:800; color:#1a1a2e; text-align:center; margin-bottom:24px; }
  .message { font-size:0.98rem; color:#374151; line-height:1.75; background:#f8f9fa; border-left:4px solid #e94560; border-radius:0 8px 8px 0; padding:20px 24px; white-space:pre-wrap; }
  .sender { margin-top:24px; font-size:0.8rem; color:#9ca3af; text-align:center; }
  .footer { background:#f8f9fa; padding:20px 40px; text-align:center; font-size:0.78rem; color:#adb5bd; border-top:1px solid #e9ecef; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo">✈ Grup<span>Talepleri</span></div>
    <h2>Duyuru</h2>
  </div>
  <div class="body">
    <div class="emoji-block">{{ $emoji }}</div>
    <div class="title">{{ $title }}</div>
    <div class="message">{{ $message }}</div>
    <div class="sender">Gönderen: <strong>{{ $sender }}</strong></div>
  </div>
  <div class="footer">
    Bu e-posta GrupTalepleri platformu tarafından gönderilmiştir.
  </div>
</div>
</body>
</html>
