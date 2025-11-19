// Optimized CSS Loader - Single file approach for production
(function() {
    'use strict';
    
    // Apply theme immediately
    const theme = localStorage.getItem('ergon_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.className = 'theme-' + theme;
    
    // Add loading class
    document.documentElement.classList.add('loading');
    
    // Check if we're in production (athenas.co.in)
    const isProduction = window.location.hostname.includes('athenas.co.in');
    
    if (isProduction) {
        // Production: Load single minified CSS file
        loadCSS('/ergon/assets/css/ergon.production.min.css');
    } else {
        // Development: Load individual CSS files
        const cssFiles = [
            '/ergon/assets/css/ergon.css',
            '/ergon/assets/css/theme-enhanced.css',
            '/ergon/assets/css/utilities-new.css',
            '/ergon/assets/css/instant-theme.css',
            '/ergon/assets/css/global-tooltips.css',
            '/ergon/assets/css/action-button-clean.css',
            '/ergon/assets/css/responsive-mobile.css',
            '/ergon/assets/css/mobile-critical-fixes.css',
            '/ergon/assets/css/nav-simple-fix.css'
        ];
        
        let loadedCount = 0;
        const totalFiles = cssFiles.length;
        
        cssFiles.forEach(function(href) {
            loadCSS(href, function() {
                loadedCount++;
                if (loadedCount === totalFiles) {
                    finishLoading();
                }
            });
        });
    }
    
    function loadCSS(href, callback) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href + '?v=' + (isProduction ? '1.0.0' : Date.now());
        
        link.onload = function() {
            if (callback) callback();
            else finishLoading();
        };
        
        link.onerror = function() {
            console.warn('Failed to load CSS:', href);
            if (callback) callback();
            else finishLoading();
        };
        
        document.head.appendChild(link);
    }
    
    function finishLoading() {
        // Remove loading state with smooth transition
        document.documentElement.classList.remove('loading');
        document.documentElement.classList.add('loaded');
        
        // Dispatch event for other scripts
        if (typeof CustomEvent !== 'undefined') {
            document.dispatchEvent(new CustomEvent('cssLoaded'));
        }
    }
    
    // Fallback timeout
    setTimeout(finishLoading, 2000);
    
})();