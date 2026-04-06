<!DOCTYPE html>
<html lang="tr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<title>{{ $title }}</title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
  /* Reset */
  body, table, td, p, a, li { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
  img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
  body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #edf0f5; }

  /* Body content from editor */
  .email-body-content { font-size: 15px; color: #374151; line-height: 1.8; font-family: 'Segoe UI', Arial, sans-serif; }
  .email-body-content p { margin: 0 0 16px; }
  .email-body-content h1, .email-body-content h2, .email-body-content h3 { color: #1a1a2e; margin: 0 0 12px; }
  .email-body-content a { color: #e94560; text-decoration: underline; }
  .email-body-content ul, .email-body-content ol { margin: 0 0 16px; padding-left: 24px; }
  .email-body-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 8px 0; }
  .email-body-content table { border-collapse: collapse; width: 100%; }
  .email-body-content blockquote { border-left: 4px solid #e94560; margin: 16px 0; padding: 12px 20px; background: #fdf2f4; border-radius: 0 8px 8px 0; color: #6b7280; }

  /* Responsive */
  @media only screen and (max-width: 640px) {
    .email-wrapper { width: 100% !important; }
    .email-content-pad { padding: 28px 20px !important; }
    .email-header-pad { padding: 20px 20px 24px !important; }
    .email-hero-pad { padding: 28px 20px !important; }
    .email-footer-pad { padding: 28px 20px !important; }
    .email-title { font-size: 18px !important; }
    .email-logo-text { font-size: 18px !important; }
    .email-tagline { display: none !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:#edf0f5;">

{{-- Preheader (görünmez, email önizlemesinde çıkar) --}}
<div style="display:none;font-size:1px;color:#edf0f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
  {{ Str::limit(strip_tags($body ?? ''), 140) }}
</div>

{{-- Outer table --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#edf0f5;">
<tr><td align="center" style="padding: 40px 16px 48px;">

  {{-- Email card --}}
  <table class="email-wrapper" width="600" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.10);">

    {{-- ══════════════════════════════════════════ --}}
    {{-- HEADER — Logo Bar (koyu lacivert)          --}}
    {{-- ══════════════════════════════════════════ --}}
    <tr>
      <td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 55%,#0f3460 100%);padding:0;">

        {{-- Logo Bar --}}
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td class="email-header-pad" style="padding:24px 40px 20px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td valign="middle">
                    <span class="email-logo-text" style="font-family:'Segoe UI',Arial,sans-serif;font-size:21px;font-weight:900;color:#e94560;letter-spacing:0.3px;">✈ Grup</span><span class="email-logo-text" style="font-family:'Segoe UI',Arial,sans-serif;font-size:21px;font-weight:900;color:#ffffff;letter-spacing:0.3px;">Talepleri</span>
                  </td>
                  <td align="right" valign="middle" class="email-tagline">
                    <span style="font-family:'Segoe UI',Arial,sans-serif;font-size:9px;font-weight:700;color:#4d6082;letter-spacing:2.5px;text-transform:uppercase;">GRUP SEYAHATİ PLATFORMU</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          {{-- Thin separator --}}
          <tr><td style="height:1px;background:rgba(255,255,255,0.06);"></td></tr>
        </table>

        {{-- Hero Banner (kırmızı) --}}
        <table width="100%" cellpadding="0" cellspacing="0" border="0"
               style="background:linear-gradient(135deg,#e94560 0%,#c73652 100%);">
          <tr>
            <td class="email-hero-pad" style="padding:36px 40px 32px;text-align:center;">
              @php $showEmoji = !empty($emoji) && $emoji !== '📢'; @endphp
              @if($showEmoji)
              <div style="font-size:52px;line-height:1;margin-bottom:14px;">{{ $emoji }}</div>
              @endif
              <h1 class="email-title" style="margin:0;font-size:22px;font-weight:800;color:#ffffff;line-height:1.45;font-family:'Segoe UI',Arial,sans-serif;text-shadow:0 1px 3px rgba(0,0,0,0.15);">
                {{ $title }}
              </h1>
            </td>
          </tr>
          {{-- Curved bottom edge simulation --}}
          <tr><td style="height:3px;background:linear-gradient(90deg,rgba(255,255,255,0.08),rgba(255,255,255,0),rgba(255,255,255,0.08));"></td></tr>
        </table>

      </td>
    </tr>

    {{-- ══════════════════════════════════════════ --}}
    {{-- BODY                                       --}}
    {{-- ══════════════════════════════════════════ --}}
    <tr>
      <td class="email-content-pad" style="padding:40px 40px 32px;">

        <div class="email-body-content">
          @php $isHtml = preg_match('/<[^<]+>/', $body ?? ''); @endphp
          @if($isHtml)
            {!! $body !!}
          @else
            {!! nl2br(e($body)) !!}
          @endif
        </div>

        {{-- Gönderen --}}
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
          <tr><td style="height:1px;background-color:#f3f4f6;"></td></tr>
        </table>
        <p style="margin:16px 0 0;font-size:12px;color:#9ca3af;font-family:'Segoe UI',Arial,sans-serif;">
          <strong style="color:#6b7280;">{{ $sender }}</strong> tarafından GrupTalepleri üzerinden gönderildi.
        </p>

      </td>
    </tr>

    {{-- ══════════════════════════════════════════ --}}
    {{-- FOOTER                                     --}}
    {{-- ══════════════════════════════════════════ --}}
    <tr>
      <td class="email-footer-pad" style="background-color:#f8fafc;padding:32px 40px;text-align:center;border-top:1px solid #e9ecef;">

        {{-- Footer Logo --}}
        <p style="margin:0 0 4px;">
          <span style="font-family:'Segoe UI',Arial,sans-serif;font-size:15px;font-weight:900;color:#1a1a2e;">✈ Grup</span><span style="font-family:'Segoe UI',Arial,sans-serif;font-size:15px;font-weight:900;color:#e94560;">Talepleri</span>
        </p>
        <p style="margin:0 0 20px;font-family:'Segoe UI',Arial,sans-serif;font-size:9px;font-weight:700;color:#9ca3af;letter-spacing:2px;text-transform:uppercase;">
          TÜRKİYE'NİN GRUP SEYAHATİ PLATFORMU
        </p>

        {{-- Footer Links --}}
        <p style="margin:0 0 20px;">
          <a href="{{ url('/') }}" style="color:#6b7280;text-decoration:none;font-size:12px;font-family:'Segoe UI',Arial,sans-serif;">gruptalepleri.com</a>
          <span style="color:#d1d5db;margin:0 10px;font-size:12px;">·</span>
          <a href="mailto:destek@gruptalepleri.com" style="color:#6b7280;text-decoration:none;font-size:12px;font-family:'Segoe UI',Arial,sans-serif;">destek@gruptalepleri.com</a>
          <span style="color:#d1d5db;margin:0 10px;font-size:12px;">·</span>
          <a href="{{ route('login') }}" style="color:#6b7280;text-decoration:none;font-size:12px;font-family:'Segoe UI',Arial,sans-serif;">Giriş Yap</a>
        </p>

        {{-- Divider --}}
        <table width="80%" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 20px;">
          <tr><td style="height:1px;background-color:#e9ecef;"></td></tr>
        </table>

        {{-- Copyright --}}
        <p style="margin:0 0 10px;font-size:11px;color:#adb5bd;line-height:1.6;font-family:'Segoe UI',Arial,sans-serif;">
          © {{ date('Y') }} GrupTalepleri &nbsp;·&nbsp; Tüm hakları saklıdır.<br>
          Bu e-posta, sistemde kayıtlı e-posta adresinize gönderilmiştir.
        </p>

        {{-- Unsubscribe --}}
        @if(!empty($unsubscribeUrl))
        <p style="margin:0;">
          <a href="{{ $unsubscribeUrl }}"
             style="color:#c9cfd8;text-decoration:underline;font-size:11px;font-family:'Segoe UI',Arial,sans-serif;">
            E-posta bildirimlerini durdurmak için tıklayın
          </a>
        </p>
        @endif

      </td>
    </tr>

    {{-- Bottom accent bar --}}
    <tr>
      <td style="height:5px;background:linear-gradient(90deg,#1a1a2e 0%,#e94560 50%,#1a1a2e 100%);"></td>
    </tr>

  </table>

</td></tr>
</table>

</body>
</html>
