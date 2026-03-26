<script>
(function () {
    const STORAGE = 'site_theme';

    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        const icon = document.getElementById('themeToggleIcon');
        if (icon) icon.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        localStorage.setItem(STORAGE, t);
    }

    // Navbar butonundan çağrılır
    window.adminToggleTheme = function () {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    };

    // Sayfa yüklenince localStorage'dan uygula
    applyTheme(localStorage.getItem(STORAGE) || 'dark');
})();
</script>
