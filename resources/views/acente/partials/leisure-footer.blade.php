@php
    $s = fn(string $k, string $d = '') => (string) \App\Models\SistemAyar::get($k, $d);
    $sirket = [
        'unvan'         => $s('sirket_unvan',         'Grup Talepleri Turizm San. ve Tic. Ltd. Şti.'),
        'vkn'           => $s('sirket_vkn',           '4110477529'),
        'vergi_dairesi' => $s('sirket_vergi_dairesi', 'Beyoğlu VD'),
        'mersis_no'     => $s('sirket_mersis_no',     '0411047752900001'),
        'adres'         => $s('sirket_adres',         'İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli / İstanbul'),
        'telefon'       => $s('sirket_telefon',       '+90 535 415 47 99'),
        'cep'           => $s('sirket_cep',           ''),
        'eposta'        => $s('sirket_eposta',        'destek@gruptalepleri.com'),
        'tursab_no'     => $s('sirket_tursab_no',     '12572'),
        'tursab_grup'   => $s('sirket_tursab_grup',   'A'),
        'instagram'     => $s('sirket_instagram',     'grup.talepleri'),
        'facebook'      => $s('sirket_facebook',      ''),
        'twitter'       => $s('sirket_twitter',       ''),
        'linkedin'      => $s('sirket_linkedin',      ''),
    ];
@endphp
<style>
.gt-site-footer{background:#16213e;padding:3rem 5% 1.5rem;margin-top:3rem;}
.gt-site-footer .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:2.5rem;padding-bottom:2rem;border-bottom:1px solid rgba(255,255,255,0.08);}
@media(max-width:768px){.gt-site-footer .footer-grid{grid-template-columns:1fr;gap:1.5rem;}}
.gt-site-footer .footer-logo{font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:800;color:#e94560;}
.gt-site-footer .footer-logo span{color:#ffffff;}
.gt-site-footer .footer-col-title{font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.4);margin-bottom:0.8rem;}
.gt-site-footer .footer-col p,.gt-site-footer .footer-col a{font-size:0.82rem;color:rgba(255,255,255,0.55);line-height:1.7;display:block;transition:color 0.2s;text-decoration:none;}
.gt-site-footer .footer-col a:hover{color:#e94560;}
.gt-site-footer .footer-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:5px 10px;font-size:0.75rem;color:rgba(255,255,255,0.6);margin-top:8px;}
.gt-site-footer .footer-badge strong{color:#ffffff;}
.gt-site-footer .footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;padding-top:1.2rem;}
.gt-site-footer .footer-text{font-size:0.75rem;color:rgba(255,255,255,0.25);}
.gt-site-footer .footer-vergi{font-size:0.72rem;color:rgba(255,255,255,0.2);}
</style>

<footer class="gt-site-footer">
    <div class="footer-grid">

        {{-- Sol: Şirket tanıtımı --}}
        <div class="footer-col">
            <div class="footer-logo mb-2">✈ Grup<span>Talepleri</span></div>
            <p style="margin-bottom:0.5rem;">Grup charter, tarifeli ve özel uçuş taleplerinizi tek platformda yönetin. Anlık teklif alın, operasyonunuzu hızlandırın.</p>
            <div class="footer-badge">
                <i class="fas fa-certificate" style="color:#f5a623;"></i>
                TÜRSAB {{ $sirket['tursab_grup'] }} Grubu &nbsp;·&nbsp; Belge No: <strong>{{ $sirket['tursab_no'] }}</strong>
            </div>

            {{-- E-posta aboneliği --}}
            <div style="margin-top:1.25rem;">
                <div style="font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin-bottom:.6rem;">
                    Duyurulardan haberdar olun
                </div>
                @if(session('abone_mesaj'))
                    <div style="font-size:.84rem;padding:.5rem .75rem;border-radius:.5rem;margin-bottom:.5rem;
                        background:{{ session('abone_durum') === 'ok' ? 'rgba(34,197,94,.15)' : 'rgba(251,191,36,.15)' }};
                        color:{{ session('abone_durum') === 'ok' ? '#86efac' : '#fde68a' }};">
                        {{ session('abone_mesaj') }}
                    </div>
                @endif
                <form action="{{ route('abone.store') }}" method="POST"
                      style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    @csrf
                    <input type="email" name="email" placeholder="E-posta adresiniz"
                           required
                           style="flex:1;min-width:0;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:#fff;font-size:.84rem;outline:none;">
                    <button type="submit"
                            style="padding:.5rem 1rem;border-radius:.5rem;border:none;background:#e8a020;color:#fff;font-size:.84rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                        Abone Ol
                    </button>
                </form>
            </div>
        </div>

        {{-- Orta: İletişim --}}
        <div class="footer-col">
            <div class="footer-col-title">İletişim</div>
            <p><i class="fas fa-map-marker-alt me-2" style="color:#e94560;width:14px;"></i>{{ $sirket['adres'] }}</p>
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sirket['telefon']) }}"><i class="fas fa-phone me-2" style="color:#e94560;width:14px;"></i>{{ $sirket['telefon'] }}</a>
            @if($sirket['cep'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $sirket['cep']) }}"><i class="fas fa-mobile-alt me-2" style="color:#e94560;width:14px;"></i>{{ $sirket['cep'] }}</a>@endif
            <a href="mailto:{{ $sirket['eposta'] }}" style="unicode-bidi:plaintext;"><i class="fas fa-envelope me-2" style="color:#e94560;width:14px;"></i>{{ $sirket['eposta'] }}</a>
            @php
                $sosyalMedya = [
                    ['url' => $sirket['instagram'] ? 'https://www.instagram.com/'.$sirket['instagram'] : '', 'icon' => 'fab fa-instagram', 'color' => '#e1306c', 'label' => '@'.$sirket['instagram']],
                    ['url' => $sirket['facebook'],  'icon' => 'fab fa-facebook',  'color' => '#1877f2', 'label' => 'Facebook'],
                    ['url' => $sirket['twitter']  ? 'https://x.com/'.$sirket['twitter'] : '', 'icon' => 'fab fa-x-twitter', 'color' => '#fff', 'label' => '@'.$sirket['twitter']],
                    ['url' => $sirket['linkedin'],  'icon' => 'fab fa-linkedin',  'color' => '#0a66c2', 'label' => 'LinkedIn'],
                ];
            @endphp
            <div style="margin-top:0.8rem;display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($sosyalMedya as $sm)
                    @if($sm['url'])
                    <a href="{{ $sm['url'] }}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:5px 10px;font-size:0.78rem;color:rgba(255,255,255,0.6);transition:all 0.2s;"
                       onmouseover="this.style.borderColor='rgba(233,69,96,0.5)';this.style.color='#fff';"
                       onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';this.style.color='rgba(255,255,255,0.6)';">
                        <i class="{{ $sm['icon'] }}" style="color:{{ $sm['color'] }};"></i> {{ $sm['label'] }}
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Sağ: Yasal / Fatura --}}
        <div class="footer-col">
            <div class="footer-col-title">Şirket Bilgileri</div>
            <p style="font-weight:600;color:rgba(255,255,255,0.75);">{{ $sirket['unvan'] }}</p>
            <p>Vergi Dairesi: {{ $sirket['vergi_dairesi'] }}</p>
            <p>Vergi No: {{ $sirket['vkn'] }}</p>
            @if($sirket['mersis_no'])<p>Mersis No: {{ $sirket['mersis_no'] }}</p>@endif
        </div>

    </div>

    <div class="footer-bottom">
        <div class="footer-text">© {{ date('Y') }} GrupTalepleri &nbsp;·&nbsp; Tüm hakları saklıdır &nbsp;·&nbsp; {{ $sirket['unvan'] }}</div>
        <div class="footer-vergi">TÜRSAB {{ $sirket['tursab_grup'] }} Grubu Belge No: {{ $sirket['tursab_no'] }} &nbsp;·&nbsp; Vergi No: {{ $sirket['vkn'] }}</div>
    </div>
</footer>
