## Deploy Talimatı (Zorunlu)

Bu repoda canlıya çıkış için **GitHub Actions** kullanılmaktadır.

- Workflow: `.github/workflows/deploy.yml`
- Tetikleme: `main` branch push
- Yöntem: FTP deploy (otomatik)

### Zorunlu çalışma kuralı

1. Canlıya alma için varsayılan yol: `main`e push -> GitHub Actions sonucu kontrol -> gerekli cache clear.
2. **cPanel üzerinden manuel "Update from Remote" isteme** sadece GitHub Actions devre dışıysa veya deploy fail olmuşsa kullanılabilir.
3. Kullanıcıyı gereksiz manuel deploy adımlarına yönlendirme.

