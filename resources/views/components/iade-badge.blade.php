@props(['talep', 'showForAcente' => false])

@php
    $goster = false;
    if (in_array(auth()->user()->role, ['admin', 'superadmin'])) {
        $goster = $talep->isIadede();
    } elseif ($showForAcente && auth()->user()->show_iade_badge) {
        $goster = $talep->isIadede();
    }
@endphp

@if($goster)
    <span class="badge ms-1" style="background:#fd7e14;font-size:0.68rem;">
        <i class="fas fa-undo me-1"></i>İadede
    </span>
@endif
