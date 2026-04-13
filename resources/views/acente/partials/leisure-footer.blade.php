@php
    $footerPhone = \App\Models\SistemAyar::get('sirket_whatsapp', '905354154799');
    $footerPhone = preg_replace('/[^0-9]/', '', (string) $footerPhone);
@endphp
<footer style="background:var(--card);border-top:1px solid var(--brd);margin-top:3rem;padding:2rem 0 1.5rem;">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div style="font-weight:800;font-size:1rem;color:var(--txt);">GrupTalepleri</div>
                <div style="font-size:.78rem;color:var(--muted);margin-top:.25rem;">B2B Leisure &amp; Group Travel Platform</div>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center" style="font-size:.82rem;">
                <a href="{{ route('acente.leisure.hub') }}" style="color:var(--muted);text-decoration:none;">
                    <i class="fas fa-compass fa-xs me-1"></i>Leisure Merkezi
                </a>
                <a href="{{ route('acente.dinner-cruise.catalog') }}" style="color:var(--muted);text-decoration:none;">
                    <i class="fas fa-utensils fa-xs me-1"></i>Dinner Cruise
                </a>
                <a href="{{ route('acente.yacht-charter.catalog') }}" style="color:var(--muted);text-decoration:none;">
                    <i class="fas fa-ship fa-xs me-1"></i>Yat Charter
                </a>
                @if($footerPhone)
                <a href="https://wa.me/{{ $footerPhone }}" target="_blank" rel="noopener"
                   style="color:#25d366;text-decoration:none;font-weight:600;">
                    <i class="fab fa-whatsapp me-1"></i>Destek
                </a>
                @endif
            </div>
            <div style="font-size:.74rem;color:var(--muted);">
                &copy; {{ date('Y') }} GrupTalepleri. Tüm hakları saklıdır.
            </div>
        </div>
    </div>
</footer>
