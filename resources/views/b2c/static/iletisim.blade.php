@extends('b2c.layouts.app')
@section('title', 'İletişim — Grup Rezervasyonları')
@section('meta_description', 'Grup Rezervasyonları ile iletişime geçin. Transfer, tur, charter ve tüm seyahat hizmetleri için destek alın.')

@push('head_styles')
<style>
.contact-header {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 100%);
    padding: 3rem 0 2.5rem;
}
.contact-wrap {
    max-width: 1100px; margin: 0 auto;
    padding: 40px 24px 60px;
    display: grid; grid-template-columns: 1fr 1.4fr; gap: 32px;
}
@@media (max-width: 768px) {
    .contact-wrap { grid-template-columns: 1fr; }
}
.contact-info-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5; box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 28px; align-self: start;
}
.contact-info-title { font-size: 1.1rem; font-weight: 800; color: #1a202c; margin-bottom: 20px; }
.contact-item {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 14px 0; border-bottom: 1px solid #f0f4f8;
}
.contact-item:last-child { border-bottom: none; }
.contact-item-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: #ebf0fb; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.contact-item-icon i { font-size: 1.1rem; color: #1a3c6b; }
.contact-item-label { font-size: .8rem; color: #718096; margin-bottom: 2px; }
.contact-item-value { font-size: .93rem; font-weight: 600; color: #1a202c; }
.contact-item-value a { color: #1a3c6b; text-decoration: none; }
.contact-item-value a:hover { text-decoration: underline; }

.contact-form-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5; box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 28px;
}
.contact-form-title { font-size: 1.1rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.contact-form-sub { font-size: .88rem; color: #718096; margin-bottom: 20px; }
.contact-field { margin-bottom: 14px; }
.contact-label { display: block; font-size: .84rem; font-weight: 600; color: #2d3748; margin-bottom: 6px; }
.contact-input {
    width: 100%; padding: 10px 13px;
    border: 1.5px solid #e2e8f0; border-radius: 9px;
    font-size: .93rem; color: #1a202c; outline: none;
    transition: border-color .15s, box-shadow .15s; background: #fafbfc;
}
.contact-input:focus { border-color: #1a3c6b; box-shadow: 0 0 0 3px rgba(26,60,107,.09); background: #fff; }
.contact-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@@media (max-width: 480px) { .contact-row { grid-template-columns: 1fr; } }
.contact-submit {
    width: 100%; padding: 13px; background: #1a3c6b; color: #fff;
    border: none; border-radius: 10px; font-size: 1rem; font-weight: 700;
    cursor: pointer; transition: background .15s;
}
.contact-submit:hover { background: #152f56; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="contact-header">
    <div style="max-width:1100px;margin:0 auto;padding:0 24px;">
        <div class="gyg-breadcrumb" style="background:transparent;border:none;padding:0 0 12px;">
            <a href="{{ route('b2c.home') }}" style="color:rgba(255,255,255,.6);">Ana Sayfa</a>
            <span class="sep" style="color:rgba(255,255,255,.4);">›</span>
            <span style="color:rgba(255,255,255,.9);">İletişim</span>
        </div>
        <h1 style="color:#fff;font-size:1.8rem;font-weight:800;margin:0 0 8px;">Bizimle İletişime Geçin</h1>
        <p style="color:rgba(255,255,255,.7);font-size:.93rem;margin:0;">
            Sorularınız için buradayız. Ortalama yanıt süremiz 2 saattir.
        </p>
    </div>
</div>

<div class="contact-wrap">

    {{-- Sol: iletişim bilgileri --}}
    <div>
        <div class="contact-info-card">
            <div class="contact-info-title">İletişim Bilgileri</div>

            <div class="contact-item">
                <div class="contact-item-icon"><i class="bi bi-telephone-fill"></i></div>
                <div>
                    <div class="contact-item-label">Telefon</div>
                    <div class="contact-item-value">
                        <a href="tel:+902125550000">+90 (212) 555 00 00</a>
                    </div>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-item-icon"><i class="bi bi-envelope-fill"></i></div>
                <div>
                    <div class="contact-item-label">E-posta</div>
                    <div class="contact-item-value">
                        <a href="mailto:info@gruprezervasyonlari.com">info@gruprezervasyonlari.com</a>
                    </div>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-item-icon"><i class="bi bi-whatsapp"></i></div>
                <div>
                    <div class="contact-item-label">WhatsApp Destek</div>
                    <div class="contact-item-value">
                        <a href="https://wa.me/902125550000" target="_blank">WhatsApp ile yazın</a>
                    </div>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-item-icon"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <div class="contact-item-label">Çalışma Saatleri</div>
                    <div class="contact-item-value">
                        Hafta içi: 09:00 – 19:00<br>
                        <span style="font-size:.82rem;color:#718096;">Cumartesi: 10:00 – 17:00</span>
                    </div>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-item-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <div class="contact-item-label">Adres</div>
                    <div class="contact-item-value" style="font-weight:400;font-size:.88rem;color:#4a5568;">
                        İstanbul, Türkiye
                    </div>
                </div>
            </div>
        </div>

        {{-- Tedarikçi CTA --}}
        <div style="margin-top:16px;background:linear-gradient(135deg,#0f2444,#1a3c6b);border-radius:14px;padding:20px 24px;color:#fff;">
            <div style="font-size:.95rem;font-weight:700;margin-bottom:4px;">Tedarikçi misiniz?</div>
            <div style="font-size:.83rem;opacity:.75;margin-bottom:14px;">
                Platformumuza ürün ekleyin, müşteri kitlesiyle buluşun.
            </div>
            <a href="{{ route('b2c.supplier-apply.index') }}"
               style="display:inline-block;padding:9px 20px;background:#f4a418;color:#fff;border-radius:8px;font-size:.88rem;font-weight:700;text-decoration:none;">
                <i class="bi bi-building me-1"></i>İş Ortağı Olun
            </a>
        </div>
    </div>

    {{-- Sağ: iletişim formu --}}
    <div class="contact-form-card">
        <div class="contact-form-title">Mesaj Gönderin</div>
        <div class="contact-form-sub">En kısa sürede size dönüş yapacağız.</div>

        @if(session('contact_success'))
        <div style="background:#ebfbee;border:1px solid #9ae6b4;border-radius:9px;padding:12px 16px;margin-bottom:16px;font-size:.9rem;color:#276749;">
            <i class="bi bi-check-circle me-1"></i>Mesajınız iletildi, teşekkürler!
        </div>
        @endif

        <form method="POST" action="{{ route('b2c.iletisim') }}">
            @csrf
            <div class="contact-row contact-field">
                <div>
                    <label class="contact-label">Ad Soyad <span style="color:#e53e3e;">*</span></label>
                    <input type="text" name="name" class="contact-input" value="{{ old('name') }}" required placeholder="Adınız Soyadınız">
                    @error('name')<div style="color:#e53e3e;font-size:.8rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="contact-label">E-posta <span style="color:#e53e3e;">*</span></label>
                    <input type="email" name="email" class="contact-input" value="{{ old('email') }}" required placeholder="ornek@email.com">
                    @error('email')<div style="color:#e53e3e;font-size:.8rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="contact-field">
                <label class="contact-label">Telefon <span style="color:#a0aec0;font-weight:400;">(isteğe bağlı)</span></label>
                <input type="tel" name="phone" class="contact-input" value="{{ old('phone') }}" placeholder="+90 5XX XXX XX XX">
            </div>
            <div class="contact-field">
                <label class="contact-label">Konu <span style="color:#e53e3e;">*</span></label>
                <select name="subject" class="contact-input" required>
                    <option value="">Konu seçin…</option>
                    <option value="rezervasyon" {{ old('subject') === 'rezervasyon' ? 'selected' : '' }}>Rezervasyon / Sipariş</option>
                    <option value="fiyat" {{ old('subject') === 'fiyat' ? 'selected' : '' }}>Fiyat Talebi</option>
                    <option value="iptal" {{ old('subject') === 'iptal' ? 'selected' : '' }}>İptal / İade</option>
                    <option value="tedarikci" {{ old('subject') === 'tedarikci' ? 'selected' : '' }}>Tedarikçi Olmak İstiyorum</option>
                    <option value="diger" {{ old('subject') === 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div class="contact-field">
                <label class="contact-label">Mesaj <span style="color:#e53e3e;">*</span></label>
                <textarea name="message" class="contact-input" rows="5" required
                          placeholder="Nasıl yardımcı olabiliriz?">{{ old('message') }}</textarea>
                @error('message')<div style="color:#e53e3e;font-size:.8rem;margin-top:4px;">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="contact-submit">
                <i class="bi bi-send-fill me-2"></i>Mesajı Gönder
            </button>
        </form>
    </div>

</div>
@endsection
