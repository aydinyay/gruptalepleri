@extends('layouts.admin')

@section('content')
<div class="container py-4" style="max-width:600px;">
    <div class="card border-warning">
        <div class="card-header bg-warning text-dark fw-bold">
            🗂 Eski Sistem Arşivi
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Eski sistemdeki (gruprez1_vt) talep detaylarını görüntülemek için GTPNR numarasını girin.</p>

            <form action="" method="GET" id="gtpnr-form">
                <div class="input-group">
                    <input type="text"
                           name="q"
                           id="gtpnr-input"
                           class="form-control form-control-lg text-uppercase"
                           placeholder="Örn: AJTLX"
                           value="{{ $gtpnr ?? '' }}"
                           autocomplete="off"
                           autofocus>
                    <button class="btn btn-warning fw-bold" type="submit">Ara</button>
                </div>
            </form>

            @if(!empty($hata))
                <div class="alert alert-danger mt-3">{{ $hata }}</div>
            @endif
        </div>
    </div>
</div>

<script>
document.getElementById('gtpnr-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const val = document.getElementById('gtpnr-input').value.trim().toUpperCase();
    if (val) window.location.href = '{{ url("admin/eski-sistem") }}/' + val;
});
</script>
@endsection
