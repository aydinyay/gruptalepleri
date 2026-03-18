# GrupTalepleri Android (WebView Shell)

Bu uygulama mevcut `gruptalepleri.com` sistemini Android kabugu icinde calistirir.

## V1 Ozellikleri

- WebView ile siteyi uygulama icinde acar
- Push notification altyapisi (Firebase Messaging)
- Dosya yukleme destegi
- Kamera ile fotograf cekip forma ekleme destegi
- Pull-to-refresh
- Harici linkleri tarayicida acma (tel/mailto/intent)

## Klasor

- `mobile-android/`

## Kurulum

1. Android Studio ile `mobile-android` klasorunu acin.
2. Gradle sync yapin.
3. `app/build.gradle.kts` icindeki `WEB_BASE_URL` degerini gerekirse degistirin.
   - Uretim: `https://gruptalepleri.com`
   - Lokal test icin: `http://10.0.2.2` veya uygun host

## Push (FCM) Kurulumu

1. Firebase Console'da Android app olusturun (`com.gruptalepleri.mobile`).
2. `google-services.json` dosyasini `mobile-android/app/` altina koyun.
3. Tekrar Gradle sync yapin.

Not: `google-services.json` yoksa uygulama yine calisir; push ozelligi aktif olmaz.

## Bildirimden URL Acma

FCM data payload ornegi:

```json
{
  "to": "<fcm_token>",
  "data": {
    "title": "Yeni Teklif",
    "body": "Talebinize yeni teklif geldi.",
    "url": "https://gruptalepleri.com/acente/talep/ABC123"
  }
}
```

## Web tarafi JS bridge

Uygulama icinde su obje vardir:

- `window.GTPMobile.getPlatform()` -> `"android"`
- `window.GTPMobile.getPushToken()` -> push token (varsa)

Ayrica push token hazir oldugunda:

- `window` uzerinden `gtp:pushToken` olayi tetiklenir.

```js
window.addEventListener("gtp:pushToken", (e) => {
  console.log("Push token:", e.detail.token);
});
```

## Bilinen Sinirlar (V1)

- Native ekran yok, ana akis web ekranlarindan gelir.
- Uygulama ici offline senaryo yok.
- Push token server'a otomatik gonderim backend endpoint baglantisi gerektirir (V2).
