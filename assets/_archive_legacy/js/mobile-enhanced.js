/**
 * Enhanced Mobile Responsiveness JavaScript
 * Critical fixes for mobile interactions and viewport handling
 */

(function() {
    'use strict';
    
    // Device and viewport detection
    const viewport = {
        width: window.innerWidth,
        height: window.innerHeight,
        isMobile: window.innerWidth <= 768,
        isTablet: window.innerWidth > 768 && window.innerWidth <= 1024,
        isTouchDevice: 'ontouchstart' in window || navigator.maxTouchPoints > 0
    };
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initCriticalFixes();
        initTouchEnhancements();
        initViewportHandler();
    });
    
    /**
     * Critical mobile fixes
     */
    function initCriticalFixes() {
        // Fix viewport meta tag if missing or incorrect
        let viewportMeta = document.querySelector('meta[name="viewport"]');
        if (!viewportMeta) {
            viewportMeta = document.createElement('meta');
            viewportMeta.name = 'viewport';
            document.head.appendChild(viewportMeta);
        }
        viewportMeta.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes';
        
        // Add device classes
        document.body.classList.add(
            viewport.isMobile ? 'mobile-device' : 
            viewport.isTablet ? 'tablet-device' : 'desktop-device'
        );
        
        if (viewport.isTouchDevice) {
            document.body.classList.add('touch-device');
        }
        
        // Fix iOS Safari viewport height issue
        if (navigator.userAgent.includes('Safari') && !navigator.userAgent.includes('Chrome')) {
            const setVH = () => {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', vh + 'px');
            };
            setVH();
            window.addEventListener('resize', setVH);
            window.addEventListener('orientationchange', () => setTimeout(setVH, 100));
        }
        
        // Prevent zoom on double tap for iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
    }
    
    /**
     * Enhanced touch interactions
     */
    function initTouchEnhancements() {
        if (!viewport.isTouchDevice) return;
        
        // Add touch feedback to interactive elements
        const touchElements = document.querySelectorAll(
            '.btn, .ab-btn, .control-btn, .profile-btn, .nav-dropdown-btn, .nav-dropdown-item'
        );
        
        touchElements.forEach(element => {
            element.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            }, { passive: true });
            
            element.addEventListener('touchend', function() {
                const self = this;
                setTimeout(() => self.classList.remove('touch-active'), 150);
            }, { passive: true });
            
            element.addEventListener('touchcancel', function() {
                this.classList.remove('touch-active');
            }, { passive: true });
        });
        
        // Enhanced swipe gestures for mobile menu
        let startX = 0, startY = 0, startTime = 0;
        
        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
        }, { passive: true });
        
        document.addEventListener('touchend', function(e) {
            if (!startX || !startY) return;
            
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const diffX = endX - startX;
            const diffY = endY - startY;
            const diffTime = Date.now() - startTime;
            
            // Only process quick swipes
            if (diffTime > 300) return;
            
            // Horizontal swipe detection
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                const sidebar = document.querySelector('.sidebar');
                
                // Swipe right from left edge to open menu
                if (diffX > 0 && startX < 50 && !sidebar.classList.contains('mobile-open')) {
                    toggleMobileMenu();
                }
                // Swipe left to close menu
                else if (diffX < 0 && sidebar.classList.contains('mobile-open')) {
                    closeMobileMenu();
                }
            }
            
            startX = startY = 0;
        }, { passive: true });
    }
    
    /**
     * Viewport and orientation handling
     */
    function initViewportHandler() {
        let resizeTimer;
        
        function handleResize() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                viewport.width = window.innerWidth;
                viewport.height = window.innerHeight;
                viewport.isMobile = window.innerWidth <= 768;
                viewport.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
                
                // Update device classes
                document.body.className = document.body.className.replace(
                    /\b(mobile|tablet|desktop)-device\b/g, ''
                );
                document.body.classList.add(
                    viewport.isMobile ? 'mobile-device' : 
                    viewport.isTablet ? 'tablet-device' : 'desktop-device'
                );
                
                // Close mobile menu on desktop
                if (viewport.width > 1024) {
                    closeMobileMenu();
                }
                
                // Trigger user management persistence if on management page
                if (window.location.pathname.includes('/admin/management') && window.userManagementPersistence) {
                    window.userManagementPersistence.ensureDataInBothViews();
                    window.userManagementPersistence.ensureProperVisibility();
                }
                
            }, 250);
        }
        
        window.addEventListener('resize', handleResize);
        window.addEventListener('orientationchange', () => {
            setTimeout(handleResize, 100);
        });
    }
    
    /**
     * Enhanced mobile menu functions
     */
    window.toggleMobileMenu = function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('mobileOverlay');
        const body = document.body;
        
        if (!sidebar || !overlay) return;
        
        const isOpen = sidebar.classList.contains('mobile-open');
        
        if (isOpen) {
            closeMobileMenu();
        } else {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
            body.style.overflow = 'hidden';
        }
    };
    
    window.closeMobileMenu = function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('mobileOverlay');
        const body = document.body;
        
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
        body.style.overflow = '';
    };
    
    // Add CSS for enhanced interactions
    const style = document.createElement('style');
    style.textContent = `
        .touch-active {
            transform: scale(0.95);
            opacity: 0.8;
            transition: all 0.1s ease;
        }
        
        .keyboard-navigation *:focus {
            outline: 2px solid var(--primary) !important;
            outline-offset: 2px !important;
        }
        
        /* iOS specific fixes */
        @supports (-webkit-touch-callout: none) {
            .form-control {
                -webkit-appearance: none;
                border-radius: 8px;
            }
            
            .btn {
                -webkit-appearance: none;
                -webkit-touch-callout: none;
            }
        }
    `;
    document.head.appendChild(style);
    
})();
