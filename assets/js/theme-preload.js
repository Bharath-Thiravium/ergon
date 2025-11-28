// Instant theme application - prevents flashing
(function() {
    const theme = localStorage.getItem('ergon_theme') || 'light';
    
    // Only set on documentElement to avoid body access issues
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.className = 'theme-' + theme;
    
    // Force immediate style application
    if (theme === 'dark') {
        document.documentElement.style.setProperty('--bg-primary', '#1a1a1a');
        document.documentElement.style.setProperty('--text-primary', '#ffffff');
    }
    
    // Set body theme after DOM loads
    function setBodyTheme() {
        if (document.body) {
            document.body.setAttribute('data-theme', theme);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setBodyTheme);
    } else {
        setBodyTheme();
    }
})();