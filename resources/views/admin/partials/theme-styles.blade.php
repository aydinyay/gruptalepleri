{{-- Anti-flash: sayfa yüklenmeden tema uygulanır, flash önlenir --}}
<script>
try {
    var t = localStorage.getItem('site_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
} catch(e) {
    document.documentElement.setAttribute('data-theme', 'dark');
}
</script>

<style>
/* ═══════════════════════════════════════
   ADMİN TEMA SİSTEMİ — koyu / açık
   ═══════════════════════════════════════ */

/* ─── KOYU TEMA ─── */
html[data-theme="dark"] body { background: #1a1a2e !important; color: #e0e0e0 !important; }

/* Kartlar */
html[data-theme="dark"] .card           { background: #16213e !important; border-color: #2a2a4e !important; color: #e0e0e0; }
html[data-theme="dark"] .card-header    { background: #0f172a !important; border-color: #2a2a4e !important; color: #e0e0e0 !important; }
html[data-theme="dark"] .card-body      { color: #e0e0e0; }

/* Modal */
html[data-theme="dark"] .modal-content  { background: #16213e !important; border-color: #2a2a4e !important; color: #e0e0e0 !important; }
html[data-theme="dark"] .modal-header,
html[data-theme="dark"] .modal-footer   { border-color: #2a2a4e !important; }
html[data-theme="dark"] .modal-body     { color: #e0e0e0; }

/* Başlıklar */
html[data-theme="dark"] h1, html[data-theme="dark"] h2,
html[data-theme="dark"] h3, html[data-theme="dark"] h4,
html[data-theme="dark"] h5, html[data-theme="dark"] h6 { color: #e0e0e0; }

/* Tablolar — Bootstrap 5 CSS değişkenleri + !important */
html[data-theme="dark"] .table {
    --bs-table-bg: #1e1e3a;
    --bs-table-border-color: #2a2a4e;
    --bs-table-hover-bg: #2a2a4e;
    --bs-table-color: #e0e0e0;
    --bs-table-striped-bg: #1e1e3a;
}
html[data-theme="dark"] .table-light {
    --bs-table-bg: #1a2744;
    --bs-table-color: #bbb;
    --bs-table-border-color: #2a2a4e;
}
html[data-theme="dark"] .table thead tr      { background: #0d6efd !important; }
html[data-theme="dark"] .table thead th      { color: #fff !important; border-color: #2a2a4e !important; }
html[data-theme="dark"] .table tbody tr      { background-color: #1e1e3a !important; }
html[data-theme="dark"] .table tbody tr:hover { background-color: #2a2a4e !important; }
html[data-theme="dark"] .table td,
html[data-theme="dark"] .table th            { border-color: #2a2a4e !important; color: #e0e0e0 !important; background-color: transparent !important; }

/* Form elemanları — background-color kullan, background shorthand kullanma (SVG ok'u sıfırlar) */
html[data-theme="dark"] input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="range"]),
html[data-theme="dark"] select,
html[data-theme="dark"] textarea             { background-color: #0f0f23 !important; border-color: #3a3a5e !important; color: #e0e0e0 !important; }
html[data-theme="dark"] input::placeholder,
html[data-theme="dark"] textarea::placeholder { color: #5a5a7a; }
html[data-theme="dark"] input:focus,
html[data-theme="dark"] select:focus,
html[data-theme="dark"] textarea:focus       { background-color: #0f0f23 !important; color: #e0e0e0 !important; border-color: #0d6efd !important; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25) !important; }
html[data-theme="dark"] .input-group-text   { background-color: #0f0f23 !important; border-color: #3a3a5e !important; color: #7a7a9a !important; }
/* form-select ok SVG'sini açık renge çevir */
html[data-theme="dark"] .form-select        { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23aaaaaa' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important; }

/* Renkler */
html[data-theme="dark"] .text-muted         { color: #7a7a9a !important; }
html[data-theme="dark"] .text-dark          { color: #e0e0e0 !important; }
html[data-theme="dark"] .section-title      { color: #7a7a9a !important; }

/* Kenarlıklar */
html[data-theme="dark"] .border-bottom, html[data-theme="dark"] .border-top,
html[data-theme="dark"] .border-start,  html[data-theme="dark"] .border-end,
html[data-theme="dark"] .border         { border-color: #2a2a4e !important; }
html[data-theme="dark"] .border-secondary { border-color: #3a3a5e !important; }

/* Alert'ler */
html[data-theme="dark"] .alert-warning  { background: #3a2e00 !important; border-color: #5a4800 !important; color: #ffc107 !important; }
html[data-theme="dark"] .alert-success  { background: #0a2e1a !important; border-color: #145a2e !important; color: #28a745 !important; }
html[data-theme="dark"] .alert-danger   { background: #2e0a0a !important; border-color: #5a1414 !important; color: #dc3545 !important; }
html[data-theme="dark"] .alert-info     { background: #0a1f3a !important; border-color: #144a6e !important; color: #17a2b8 !important; }

/* Pagination */
html[data-theme="dark"] .pagination .page-link            { background: #16213e !important; border-color: #2a2a4e !important; color: #e0e0e0 !important; }
html[data-theme="dark"] .pagination .page-item.active .page-link { background: #0d6efd !important; border-color: #0d6efd !important; }
html[data-theme="dark"] .pagination .page-link:hover      { background: #2a2a4e !important; }

/* Arka planlar */
html[data-theme="dark"] .bg-white        { background: #16213e !important; }
html[data-theme="dark"] .bg-light        { background: #1e1e3a !important; }
html[data-theme="dark"] .badge.bg-light  { background: #2a2a4e !important; color: #e0e0e0 !important; }
html[data-theme="dark"] .badge.text-dark { color: #e0e0e0 !important; }

/* Dropdown */
html[data-theme="dark"] .dropdown-menu  { background: #16213e !important; border-color: #2a2a4e !important; }
html[data-theme="dark"] .dropdown-item  { color: #e0e0e0 !important; }
html[data-theme="dark"] .dropdown-item:hover { background: #2a2a4e !important; }

/* ── Dashboard özel ── */
html[data-theme="dark"] .page-header        { background: #0f0f23 !important; }
html[data-theme="dark"] .bekleyen-card      { background: #1e1e3a !important; }
html[data-theme="dark"] .bekleyen-card .gtpnr { color: #4db8ff !important; }
html[data-theme="dark"] .bekleyen-card .meta  { color: #7a7a9a !important; }
html[data-theme="dark"] .quick-btn          { background: #1e1e3a !important; border-color: #2a2a4e !important; color: #ccc !important; }
html[data-theme="dark"] .quick-btn:hover    { border-color: #e94560 !important; color: #e94560 !important; background: #2a1a1f !important; }
html[data-theme="dark"] .opsiyon-row        { border-bottom-color: #2a2a4e !important; }
html[data-theme="dark"] .stat-icon          { filter: brightness(0.7) saturate(1.4); }

/* ── Broadcast özel ── */
html[data-theme="dark"] .channel-card            { background: #1e1e3a !important; border-color: #2a2a4e !important; color: #e0e0e0; }
html[data-theme="dark"] .channel-card:hover      { background: #2a2a4e !important; border-color: #4a4a6e !important; }
html[data-theme="dark"] .channel-card.active-push  { background: #1a1a3a !important; border-color: #6366f1 !important; }
html[data-theme="dark"] .channel-card.active-sms   { background: #2a1e0a !important; border-color: #f59e0b !important; }
html[data-theme="dark"] .channel-card.active-email { background: #0a2a1a !important; border-color: #10b981 !important; }
html[data-theme="dark"] .channel-card .ch-desc     { color: #7a7a9a !important; }

/* ─── AÇIK TEMA (Bootstrap default — minimal override) ─── */
html[data-theme="light"] body { background: #f0f2f5 !important; color: #212529 !important; }

/* ─── TEMA TOGGLE BUTONU (navbar'da) ─── */
.theme-toggle-btn {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.25);
    color: rgba(255,255,255,0.85);
    border-radius: 6px;
    padding: 4px 10px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
    white-space: nowrap;
}
.theme-toggle-btn:hover { background: rgba(255,255,255,0.22); color: #fff; }
</style>
