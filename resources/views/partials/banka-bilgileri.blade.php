{{--
    Banka Bilgileri Partial
    Params:
      $gtpnr    — (optional) talep numarası, açıklamaya eklenir
      $compact  — (optional, bool) dar kart stili için
--}}
@php
use App\Models\SistemAyar;

$bankalar = [];
for ($i = 1; $i <= 4; $i++) {
    $iban = (string) SistemAyar::get("banka_iban_{$i}", $i === 1 ? SistemAyar::get('banka_iban', '') : '');
    if (empty(trim($iban))) continue;
    $bankalar[] = [
        'doviz' => SistemAyar::get("banka_doviz_{$i}", 'TRY'),
        'adi'   => SistemAyar::get("banka_adi_{$i}",   SistemAyar::get('banka_adi', '')),
        'sube'  => SistemAyar::get("banka_sube_{$i}",  SistemAyar::get('banka_sube', '')),
        'sahip' => SistemAyar::get("banka_hesap_sahibi_{$i}", SistemAyar::get('banka_hesap_sahibi', '')),
        'iban'  => 'TR' . $iban,
        'not'   => SistemAyar::get("banka_aciklama_{$i}", SistemAyar::get('banka_aciklama', '')),
    ];
}

$gtpnr   = $gtpnr ?? null;
$compact = $compact ?? false;
@endphp

@if(count($bankalar))
<div class="{{ $compact ? 'mb-3' : 'card shadow-sm mb-4' }}">
    @if(!$compact)
    <div class="card-header d-flex align-items-center gap-2 py-2 px-3" style="background:linear-gradient(90deg,#0f2544,#1a3c6e);color:#fff;">
        <i class="fas fa-university"></i>
        <span class="fw-semibold" style="font-size:.93rem;">Havale / EFT Banka Bilgileri</span>
    </div>
    <div class="card-body p-3">
    @endif

    @if($gtpnr)
    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.84rem;">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Havale / EFT açıklamasına mutlaka <strong>{{ $gtpnr }}</strong> yazınız.
    </div>
    @else
    <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.84rem;">
        <i class="fas fa-info-circle me-1"></i>
        Havale / EFT açıklamasına <strong>talep numaranızı (GTPNR)</strong> yazmayı unutmayın.
    </div>
    @endif

    <div class="row g-3">
    @foreach($bankalar as $idx => $b)
    @php
        $ibanId = 'iban-' . $idx . '-' . rand(1000,9999);
        $renkler = ['#1a3a1a','#1a2a3a','#3a1a1a','#2a1a3a'];
        $renk = $renkler[$idx % count($renkler)];
    @endphp
    <div class="col-md-{{ count($bankalar) === 1 ? '12' : '6' }}">
        <div class="rounded-3 p-3 h-100 position-relative"
             style="border:1px solid rgba(0,0,0,.1);background:{{ $renk }}08;border-left:4px solid #0f2544;">

            {{-- Başlık --}}
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <span class="badge bg-primary me-1" style="font-size:.7rem;">{{ $idx === 0 ? 'Ana Hesap' : ($idx+1).'. Hesap' }}</span>
                    <span class="badge" style="background:#e8a020;font-size:.7rem;">{{ $b['doviz'] }}</span>
                </div>
                <small class="text-muted" style="font-size:.72rem;">{{ $b['adi'] }}</small>
            </div>

            {{-- Bilgiler --}}
            <table class="w-100" style="font-size:.83rem;border-collapse:collapse;">
                @if($b['sahip'])
                <tr>
                    <td style="color:#6c757d;width:38%;padding:2px 0;">Hesap Sahibi</td>
                    <td class="fw-semibold" style="padding:2px 0;">{{ $b['sahip'] }}</td>
                </tr>
                @endif
                @if($b['adi'])
                <tr>
                    <td style="color:#6c757d;padding:2px 0;">Banka</td>
                    <td style="padding:2px 0;">{{ $b['adi'] }}{{ $b['sube'] ? ' / '.$b['sube'] : '' }}</td>
                </tr>
                @endif
                <tr>
                    <td style="color:#6c757d;padding:4px 0 2px;">IBAN</td>
                    <td style="padding:4px 0 2px;">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <code id="{{ $ibanId }}" style="font-size:.8rem;word-break:break-all;">{{ $b['iban'] }}</code>
                            <button type="button"
                                    onclick="kopyala('{{ $ibanId }}', this)"
                                    class="btn btn-outline-secondary btn-sm py-0 px-2"
                                    style="font-size:.7rem;">
                                <i class="fas fa-copy me-1"></i>Kopyala
                            </button>
                        </div>
                    </td>
                </tr>
            </table>

            @if($b['not'])
            <div class="mt-2 pt-2 border-top" style="font-size:.75rem;color:#6c757d;">
                <i class="fas fa-sticky-note me-1"></i>{{ $b['not'] }}
            </div>
            @endif
        </div>
    </div>
    @endforeach
    </div>

    @if(!$compact)
    </div>
    @endif
</div>
@else
<div class="alert alert-secondary py-2 mb-3" style="font-size:.84rem;">
    <i class="fas fa-university me-1"></i>
    Banka bilgileri henüz girilmemiş. Lütfen operasyon ekibimizle iletişime geçin.
</div>
@endif

<script>
function kopyala(id, btn) {
    var text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Kopyalandı';
        btn.classList.replace('btn-outline-secondary','btn-success');
        setTimeout(function(){ btn.innerHTML = orig; btn.classList.replace('btn-success','btn-outline-secondary'); }, 2000);
    });
}
</script>
