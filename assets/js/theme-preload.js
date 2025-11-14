// Instant theme application - prevents flashing
(function() {
    const theme = localStorage.getItem('ergon_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.className = 'theme-' + theme;
})();