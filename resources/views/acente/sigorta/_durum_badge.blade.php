@php
$renkler = [
    'teklif_gonderildi' => 'secondary',
    'teklif_alindi'     => 'info',
    'police_isleniyor'  => 'warning',
    'tamamlandi'        => 'success',
    'iptal_bekliyor'    => 'warning',
    'iptal'             => 'danger',
    'hata'              => 'danger',
];
$etiketler = [
    'teklif_gonderildi' => 'Teklif Gönderildi',
    'teklif_alindi'     => 'Teklif Alındı',
    'police_isleniyor'  => 'İşleniyor',
    'tamamlandi'        => 'Tamamlandı',
    'iptal_bekliyor'    => 'İptal Bekleniyor',
    'iptal'             => 'İptal',
    'hata'              => 'Hata',
];
$renk = $renkler[$durum] ?? 'secondary';
$etiket = $etiketler[$durum] ?? $durum;
@endphp
<span class="badge bg-{{ $renk }}">{{ $etiket }}</span>
