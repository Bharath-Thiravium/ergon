// CSS Preloader - Prevents FOUC by preloading stylesheets
(function() {
    'use strict';
    
    // Apply theme immediately
    const theme = localStorage.getItem('ergon_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.className = 'theme-' + theme;
    
    // Add loading class to prevent FOUC
    document.documentElement.classList.add('loading');
    
    // CSS files to preload
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
    
    function onCSSLoad() {
        loadedCount++;
        if (loadedCount === totalFiles) {
            // All CSS loaded, remove loading state
            document.documentElement.classList.remove('loading');
            document.documentElement.classList.add('loaded');
            
            // Dispatch custom event for other scripts
            if (typeof CustomEvent !== 'undefined') {
                document.dispatchEvent(new CustomEvent('cssLoaded'));
            }
        }
    }
    
    function preloadCSS(href) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = href + '?v=' + Date.now();
        link.onload = function() {
            // Convert preload to stylesheet
            this.rel = 'stylesheet';
            onCSSLoad();
        };
        link.onerror = function() {
            console.warn('Failed to load CSS:', href);
            onCSSLoad(); // Continue even if one fails
        };
        
        document.head.appendChild(link);
    }
    
    // Start preloading CSS files
    cssFiles.forEach(preloadCSS);
    
    // Fallback timeout to prevent infinite loading
    setTimeout(function() {
        if (loadedCount < totalFiles) {
            console.warn('CSS loading timeout, forcing display');
            document.documentElement.classList.remove('loading');
            document.documentElement.classList.add('loaded');
        }
    }, 3000);
    
})();