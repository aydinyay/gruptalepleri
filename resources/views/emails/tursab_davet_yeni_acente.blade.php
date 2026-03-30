<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GrupTalepleri — Hayırlı Olsun!</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<!-- WRAPPER -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f9;padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.09);">

  <!-- HEADER -->
  <tr>
    <td style="background:linear-gradient(135deg,#0f2544 0%,#1a3c6e 100%);padding:36px 40px;text-align:center;">
      <div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.3px;margin-bottom:6px;">
        ✈ Grup<span style="color:#e8a020;">Talepleri</span><span style="color:#ffffff;">.com</span>
      </div>
      <div style="font-size:13px;color:rgba(255,255,255,0.55);font-weight:400;letter-spacing:0.5px;text-transform:uppercase;">
        Seyahat Acenteleri İçin Grup Talep Platformu
      </div>
    </td>
  </tr>

  <!-- BODY -->
  <tr>
    <td style="padding:36px 40px;">

      <!-- Badge -->
      <div style="margin-bottom:20px;">
        <span style="display:inline-block;background:#e8a020;color:#ffffff;font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;padding:4px 14px;border-radius:50px;">
          🎉 Hayırlı Olsun
        </span>
      </div>

      <!-- Kişisel selamlama -->
      <div style="font-size:20px;font-weight:800;color:#0f2544;margin-bottom:16px;">
        Sayın {{ $acenteUnvani }},
      </div>

      <!-- Açılış — AI kişisel paragraf veya varsayılan -->
      @if(!empty($aiParagraf))
      <p style="font-size:15px;color:#495057;line-height:1.75;margin:0 0 24px;">
        {!! nl2br(e($aiParagraf)) !!}
      </p>
      @else
      <p style="font-size:15px;color:#495057;line-height:1.75;margin:0 0 16px;">
        Yeni belge no'nuzla <strong>TÜRSAB & T.C. Kültür ve Turizm Bakanlığı</strong> kaydınızı tamamladığınızı görüyoruz.
      </p>
      <p style="font-size:15px;color:#0f2544;font-weight:700;line-height:1.75;margin:0 0 24px;">
        Bu, sektördeki en önemli adımdır.
      </p>
      @endif

      <!-- Soru kutusu -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
          <td style="background:#f8f9fa;border-left:4px solid #0f2544;border-radius:0 8px 8px 0;padding:18px 20px;">
            <div style="font-size:14px;color:#495057;line-height:1.8;margin-bottom:8px;">
              Yeni kurulan her acentanın karşılaştığı ilk gerçek soru ise aynıdır…
            </div>
            <div style="font-size:15px;font-weight:700;color:#0f2544;line-height:1.7;">
              İlk müşteriyi nereden bulacağım?<br>
              İlk grup rezervasyonunu nasıl alacağım?
            </div>
          </td>
        </tr>
      </table>

      <!-- Cevap -->
      <p style="font-size:15px;color:#495057;line-height:1.75;margin:0 0 8px;">
        <strong style="color:#0f2544;">GrupTalepleri</strong> tam olarak bu noktada devreye girer.
      </p>

      <!-- Madde listesi -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
          <td style="padding:6px 0;">
            <span style="font-size:16px;margin-right:10px;">📋</span>
            <span style="font-size:14px;color:#212529;">Sistemdeki <strong>hazır grup taleplerini</strong> görürsünüz</span>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 0;">
            <span style="font-size:16px;margin-right:10px;">✍️</span>
            <span style="font-size:14px;color:#212529;"><strong>Teklif verirsiniz</strong></span>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 0;">
            <span style="font-size:16px;margin-right:10px;">🏆</span>
            <span style="font-size:14px;color:#212529;"><strong>Operasyonu kazanırsınız</strong></span>
          </td>
        </tr>
      </table>

      <!-- Vurgu -->
      <p style="font-size:15px;color:#495057;line-height:1.75;margin:0 0 24px;">
        Yani müşteri aramazsınız…<br>
        <strong style="color:#0f2544;">Müşteri sizi bulur.</strong>
      </p>

      <!-- 3 Fayda kutusu -->
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:#e8a020;margin-bottom:12px;">
        Özellikle yeni kurulan acentalar için
      </div>
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        <tr>
          <td width="33%" style="padding:0 6px 0 0;vertical-align:top;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:#f4f6f9;border-radius:8px;border-top:3px solid #e8a020;padding:14px 14px;text-align:center;">
                  <div style="font-size:22px;margin-bottom:8px;">⏱️</div>
                  <div style="font-size:12px;font-weight:700;color:#0f2544;">Zaman<br>kazandırır</div>
                </td>
              </tr>
            </table>
          </td>
          <td width="33%" style="padding:0 3px;vertical-align:top;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:#f4f6f9;border-radius:8px;border-top:3px solid #e8a020;padding:14px 14px;text-align:center;">
                  <div style="font-size:22px;margin-bottom:8px;">💼</div>
                  <div style="font-size:12px;font-weight:700;color:#0f2544;">Doğrudan iş<br>fırsatı sunar</div>
                </td>
              </tr>
            </table>
          </td>
          <td width="33%" style="padding:0 0 0 6px;vertical-align:top;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:#f4f6f9;border-radius:8px;border-top:3px solid #e8a020;padding:14px 14px;text-align:center;">
                  <div style="font-size:22px;margin-bottom:8px;">🚀</div>
                  <div style="font-size:12px;font-weight:700;color:#0f2544;">Hızlı<br>başlangıç sağlar</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- Ana CTA -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        <tr>
          <td align="center">
            <a href="{{ $kayitUrl }}"
               style="display:inline-block;background:#e8a020;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;padding:16px 40px;border-radius:8px;letter-spacing:0.2px;">
              ★ Hemen Ücretsiz Kayıt Ol →
            </a>
          </td>
        </tr>
      </table>

      <!-- Ayırıcı -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        <tr>
          <td style="border-top:1px solid #f0f0f0;padding-top:28px;">

            <!-- 4 Hizmet başlığı -->
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:#e8a020;margin-bottom:12px;">
              4 Hizmet — Tek Platform
            </div>

            <!-- 4 Hizmet kartları -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:0;">
              <tr>
                <td width="50%" style="padding:0 8px 12px 0;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background:#f4f6f9;border-radius:8px;border-left:4px solid #e8a020;padding:14px 16px;">
                        <div style="font-size:20px;margin-bottom:6px;">✈️</div>
                        <div style="font-size:13px;font-weight:700;color:#0f2544;margin-bottom:4px;">Grup Uçuş Talepleri</div>
                        <div style="font-size:12px;color:#6c757d;line-height:1.5;">Tarifeli & charter bazlı talepler. Tek yön, gidiş-dönüş, çok bacaklı güzergahlar.</div>
                      </td>
                    </tr>
                  </table>
                </td>
                <td width="50%" style="padding:0 0 12px 8px;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background:#f4f6f9;border-radius:8px;border-left:4px solid #e8a020;padding:14px 16px;">
                        <div style="font-size:20px;margin-bottom:6px;">🛩️</div>
                        <div style="font-size:13px;font-weight:700;color:#0f2544;margin-bottom:4px;">Air Charter</div>
                        <div style="font-size:12px;color:#6c757d;line-height:1.5;">Özel jet, helikopter veya uçak kiralama. AI destekli anlık ön fiyat tahmini.</div>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td width="50%" style="padding:0 8px 0 0;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background:#f4f6f9;border-radius:8px;border-left:4px solid #e8a020;padding:14px 16px;">
                        <div style="font-size:20px;margin-bottom:6px;">🚢</div>
                        <div style="font-size:13px;font-weight:700;color:#0f2544;margin-bottom:4px;">Dinner Cruise</div>
                        <div style="font-size:12px;color:#6c757d;line-height:1.5;">Tarih, oturum, menü seçimiyle özel teklif. Türkçe / İngilizce PDF çıktısı.</div>
                      </td>
                    </tr>
                  </table>
                </td>
                <td width="50%" style="padding:0 0 0 8px;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background:#f4f6f9;border-radius:8px;border-left:4px solid #e8a020;padding:14px 16px;">
                        <div style="font-size:20px;margin-bottom:6px;">⛵</div>
                        <div style="font-size:13px;font-weight:700;color:#0f2544;margin-bottom:4px;">Yacht Charter</div>
                        <div style="font-size:12px;color:#6c757d;line-height:1.5;">Marina, süre ve etkinlik tipine göre yat kiralama talebi. Profesyonel teklif.</div>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- TÜRSAB Belge No kutusu -->
      @if($belgeNo)
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        <tr>
          <td style="background:#fff8f0;border-left:4px solid #e8a020;border-radius:0 8px 8px 0;padding:14px 18px;">
            <div style="font-size:13px;color:#495057;line-height:1.65;">
              <strong style="color:#0f2544;">TÜRSAB Belge No: {{ $belgeNo }}</strong><br>
              Kayıt sırasında bu numarayı girerek firma bilgilerinizi otomatik doldurabilirsiniz.
            </div>
          </td>
        </tr>
      </table>
      @endif

      <!-- İkincil link -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:4px;">
        <tr>
          <td align="center">
            <a href="https://gruptalepleri.com/acente-tanitim.html"
               style="font-size:13px;color:#1a3c6e;text-decoration:underline;">
              Platform hakkında detaylı bilgi için tanıtım dokümanını inceleyin →
            </a>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#0f2544;padding:24px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,0.1);">
            <span style="font-size:14px;font-weight:800;color:#ffffff;">✈ Grup<span style="color:#e8a020;">Talepleri</span>.com</span>
          </td>
        </tr>
        <tr>
          <td style="padding-top:12px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="font-size:11px;color:rgba(255,255,255,0.5);line-height:1.8;">
                  Group Ticket Turizm Seyahat Acentası<br>
                  İnönü Mah. Cumhuriyet Cad. No:93/12, Şişli / İstanbul<br>
                  <a href="mailto:destek@gruptalepleri.com" style="color:rgba(255,255,255,0.5);text-decoration:none;">destek@gruptalepleri.com</a>
                  &nbsp;·&nbsp; +90 535 415 47 99<br>
                  TÜRSAB A Grubu Seyahat Acentası — Belge No: 12572
                  &nbsp;·&nbsp; Vergi No: 4110477529
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding-top:12px;border-top:1px solid rgba(255,255,255,0.08);margin-top:12px;">
            <span style="font-size:10px;color:rgba(255,255,255,0.25);">
              Bu e-posta TÜRSAB üyesi seyahat acentelerine gönderilmektedir.
              Almak istemiyorsanız
              <a href="mailto:destek@gruptalepleri.com?subject=Listeden Çıkar" style="color:rgba(255,255,255,0.25);">buraya tıklayın</a>.
            </span>
          </td>
        </tr>
      </table>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
