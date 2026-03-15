<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eski Sistem Arşivi — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }</style>
</head>
<body>

<x-navbar-admin active="" />

<div class="container py-5" style="max-width:560px;">
    <div class="card border-warning shadow-sm">
        <div class="card-header bg-warning text-dark fw-bold fs-5">
            🗂 Eski Sistem Arşivi
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Eski sistemdeki (gruprez1_vt) talep detaylarını görüntülemek için GTPNR numarasını girin.</p>

            <form id="gtpnr-form">
                <div class="input-group input-group-lg">
                    <input type="text"
                           id="gtpnr-input"
                           class="form-control text-uppercase fw-bold"
                           placeholder="Örn: AJTLX"
                           value="{{ $gtpnr ?? '' }}"
                           autocomplete="off"
                           autofocus>
                    <button class="btn btn-warning fw-bold" type="submit">Ara</button>
                </div>
            </form>

            @if(!empty($hata))
                <div class="alert alert-danger mt-3 mb-0">{{ $hata }}</div>
            @endif
        </div>
    </div>
</div>

<script>
document.getElementById('gtpnr-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const val = document.getElementById('gtpnr-input').value.trim().toUpperCase();
    if (val) window.location.href = '{{ url("admin/eski-sistem") }}/' + encodeURIComponent(val);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
