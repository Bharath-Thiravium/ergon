// Instant theme application - prevents flashing
(function() {
    const theme = localStorage.getItem('ergon_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.className = 'theme-' + theme;
    document.body.setAttribute('data-theme', theme);
    
    // Force immediate style application
    if (theme === 'dark') {
        document.documentElement.style.setProperty('--bg-primary', '#1a1a1a');
        document.documentElement.style.setProperty('--text-primary', '#ffffff');
    }
})();