/**
 * Authentication Guard - Prevents back button access after logout
 */

(function() {
    'use strict';
    
    // Check if user is logged in by checking session storage
    function isLoggedIn() {
        return sessionStorage.getItem('ergon_logged_in') === 'true';
    }
    
    // Set login status
    function setLoginStatus(status) {
        if (status) {
            sessionStorage.setItem('ergon_logged_in', 'true');
            localStorage.setItem('ergon_last_activity', Date.now().toString());
        } else {
            sessionStorage.removeItem('ergon_logged_in');
            localStorage.removeItem('ergon_last_activity');
            
            // Clear all possible storage
            sessionStorage.clear();
            
            // Clear browser history to prevent back button
            if (window.history && window.history.pushState) {
                window.history.pushState(null, null, '/ergon/login');
                window.addEventListener('popstate', function() {
                    window.history.pushState(null, null, '/ergon/login');
                    window.location.href = '/ergon/login';
                });
            }
        }
    }
    
    // Check if page requires authentication
    function requiresAuth() {
        const path = window.location.pathname;
        const authRequiredPaths = ['/dashboard', '/owner/', '/admin/', '/user/'];
        return authRequiredPaths.some(authPath => path.includes(authPath));
    }
    
    // Redirect to login if not authenticated
    function redirectToLogin() {
        window.location.replace('/ergon/login');
    }
    
    // Initialize auth guard
    function initAuthGuard() {
        // If logged in, update activity timestamp
        if (isLoggedIn()) {
            localStorage.setItem('ergon_last_activity', Date.now().toString());
        }
    }
    
    // Handle successful login
    window.ergonLogin = function() {
        setLoginStatus(true);
    };
    
    // Handle logout
    window.ergonLogout = function() {
        setLoginStatus(false);
        
        // Clear all storage
        sessionStorage.clear();
        localStorage.clear();
        
        // Clear browser cache
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    caches.delete(name);
                });
            });
        }
        
        // Force page reload to clear any cached content
        window.location.replace('/ergon/login');
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAuthGuard);
    } else {
        initAuthGuard();
    }
    
    // Check session timeout every 30 seconds
    setInterval(function() {
        if (isLoggedIn()) {
            const lastActivity = parseInt(localStorage.getItem('ergon_last_activity') || '0');
            const now = Date.now();
            const timeout = 60 * 60 * 1000; // 1 hour in milliseconds
            
            if (now - lastActivity > timeout) {
                setLoginStatus(false);
                alert('Your session has expired. Please login again.');
                redirectToLogin();
            }
        }
    }, 30000);
    
})();