// Simple attendance page auto-refresh
if (window.location.pathname.includes('/attendance')) {
    // Auto-refresh on viewport changes
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            window.location.reload();
        }, 500);
    });
    
    // Auto-refresh on orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            window.location.reload();
        }, 300);
    });
    
    // Intercept all fetch requests and reload after success
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            if (response.ok && args[0].includes('simple_attendance.php')) {
                response.clone().json().then(data => {
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                }).catch(() => {});
            }
            return response;
        });
    };
}