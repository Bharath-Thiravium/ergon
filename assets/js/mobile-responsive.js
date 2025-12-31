/**
 * Mobile Responsive JavaScript Enhancements
 * Handles mobile-specific interactions and responsive behaviors
 */

(function() {
    'use strict';
    
    // Device detection
    const isMobile = window.innerWidth <= 768;
    const isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    // Initialize mobile features
    document.addEventListener('DOMContentLoaded', function() {
        initMobileFeatures();
        initTouchEnhancements();
        initResponsiveTable();
        initMobileNavigation();
        initViewportHandler();
    });
    
    /**
     * Initialize mobile-specific features
     */
    function initMobileFeatures() {
        // Add mobile class to body
        if (isMobile) {
            document.body.classList.add('mobile-device');
        }
        if (isTablet) {
            document.body.classList.add('tablet-device');
        }
        if (isTouchDevice) {
            document.body.classList.add('touch-device');
        }
        
        // Handle orientation changes
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                window.location.reload();
            }, 100);
        });
        
        // Prevent zoom on double tap for iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }
    
    /**
     * Initialize touch enhancements
     */
    function initTouchEnhancements() {
        if (!isTouchDevice) return;
        
        // Add touch feedback to buttons
        const touchElements = document.querySelectorAll('.btn, .ab-btn, .control-btn, .profile-btn');
        touchElements.forEach(function(element) {
            element.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            });
            
            element.addEventListener('touchend', function() {
                const self = this;
                setTimeout(function() {
                    self.classList.remove('touch-active');
                }, 150);
            });
        });
        
        // Improve dropdown touch handling
        const dropdownBtns = document.querySelectorAll('.nav-dropdown-btn');
        dropdownBtns.forEach(function(btn) {
            btn.addEventListener('touchstart', function(e) {
                e.preventDefault();
                const dropdownId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                toggleDropdown(dropdownId);
            });
        });
    }
    
    /**
     * Initialize responsive table features
     */
    function initResponsiveTable() {
        const tables = document.querySelectorAll('.table-responsive');
        
        tables.forEach(function(tableContainer) {
            const table = tableContainer.querySelector('table');
            if (!table) return;
            
            // Add scroll indicators
            if (isMobile) {
                tableContainer.classList.add('table-mobile-scroll');
                
                // Add horizontal scroll indicators
                const scrollIndicator = document.createElement('div');
                scrollIndicator.className = 'table-scroll-indicator';
                scrollIndicator.innerHTML = '<i class=\"bi bi-arrow-left-right\"></i> Scroll horizontally';
                tableContainer.appendChild(scrollIndicator);
                
                // Hide indicator after first scroll
                tableContainer.addEventListener('scroll', function() {
                    scrollIndicator.style.display = 'none';
                }, { once: true });
            }
            
            // Make table cells more touch-friendly
            const cells = table.querySelectorAll('td, th');
            cells.forEach(function(cell) {
                if (cell.textContent.length > 50 && isMobile) {
                    cell.setAttribute('title', cell.textContent);
                }
            });
        });
    }
    
    /**
     * Initialize mobile navigation
     */
    function initMobileNavigation() {
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const overlay = document.getElementById('mobileOverlay');
            
            if (sidebar && sidebar.classList.contains('mobile-open') && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                closeMobileMenu();
            }
        });
        
        // Handle swipe gestures for mobile menu
        if (isTouchDevice) {
            let startX = 0;
            let startY = 0;
            
            document.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            });
            
            document.addEventListener('touchmove', function(e) {
                if (!startX || !startY) return;
                
                const diffX = e.touches[0].clientX - startX;
                const diffY = e.touches[0].clientY - startY;
                
                // Horizontal swipe
                if (Math.abs(diffX) > Math.abs(diffY)) {
                    const sidebar = document.querySelector('.sidebar');
                    
                    // Swipe right to open menu (from left edge)
                    if (diffX > 50 && startX < 50 && !sidebar.classList.contains('mobile-open')) {\n                        toggleMobileMenu();\n                    }\n                    \n                    // Swipe left to close menu\n                    if (diffX < -50 && sidebar.classList.contains('mobile-open')) {\n                        closeMobileMenu();\n                    }\n                }\n                \n                startX = 0;\n                startY = 0;\n            });\n        }\n    }\n    \n    /**\n     * Handle viewport changes\n     */\n    function initViewportHandler() {\n        let resizeTimer;\n        \n        window.addEventListener('resize', function() {\n            clearTimeout(resizeTimer);\n            resizeTimer = setTimeout(function() {\n                handleViewportChange();\n            }, 250);\n        });\n        \n        function handleViewportChange() {\n            const newWidth = window.innerWidth;\n            \n            // Update device classes\n            document.body.classList.toggle('mobile-device', newWidth <= 768);\n            document.body.classList.toggle('tablet-device', newWidth > 768 && newWidth <= 1024);\n            \n            // Close mobile menu on desktop\n            if (newWidth > 1024) {\n                closeMobileMenu();\n            }\n            \n            // Reinitialize responsive tables\n            initResponsiveTable();\n        }\n    }\n    \n    /**\n     * Utility functions for mobile menu\n     */\n    window.toggleMobileMenu = function() {\n        const sidebar = document.querySelector('.sidebar');\n        const overlay = document.getElementById('mobileOverlay');\n        \n        if (sidebar && overlay) {\n            const isOpen = sidebar.classList.contains('mobile-open');\n            \n            sidebar.classList.toggle('mobile-open');\n            overlay.classList.toggle('active');\n            document.body.style.overflow = isOpen ? '' : 'hidden';\n            \n            // Add/remove backdrop blur\n            const mainContent = document.querySelector('.main-content');\n            if (mainContent) {\n                mainContent.style.filter = isOpen ? '' : 'blur(2px)';\n            }\n        }\n    };\n    \n    window.closeMobileMenu = function() {\n        const sidebar = document.querySelector('.sidebar');\n        const overlay = document.getElementById('mobileOverlay');\n        const mainContent = document.querySelector('.main-content');\n        \n        if (sidebar) sidebar.classList.remove('mobile-open');\n        if (overlay) overlay.classList.remove('active');\n        if (mainContent) mainContent.style.filter = '';\n        document.body.style.overflow = '';\n    };\n    \n    /**\n     * Enhanced modal handling for mobile\n     */\n    function initMobileModals() {\n        const modals = document.querySelectorAll('.modal');\n        \n        modals.forEach(function(modal) {\n            // Prevent background scroll when modal is open\n            const observer = new MutationObserver(function(mutations) {\n                mutations.forEach(function(mutation) {\n                    if (mutation.attributeName === 'style') {\n                        const isVisible = modal.style.display === 'block' || modal.style.display === 'flex';\n                        document.body.style.overflow = isVisible ? 'hidden' : '';\n                    }\n                });\n            });\n            \n            observer.observe(modal, { attributes: true });\n        });\n    }\n    \n    /**\n     * Improve form handling on mobile\n     */\n    function initMobileForms() {\n        // Prevent zoom on input focus for iOS\n        const inputs = document.querySelectorAll('input, select, textarea');\n        inputs.forEach(function(input) {\n            if (input.style.fontSize === '' || parseFloat(input.style.fontSize) < 16) {\n                input.style.fontSize = '16px';\n            }\n        });\n        \n        // Add better focus handling\n        inputs.forEach(function(input) {\n            input.addEventListener('focus', function() {\n                this.scrollIntoView({ behavior: 'smooth', block: 'center' });\n            });\n        });\n    }\n    \n    // Initialize additional mobile features\n    document.addEventListener('DOMContentLoaded', function() {\n        initMobileModals();\n        initMobileForms();\n    });\n    \n    // Add CSS for touch feedback\n    const style = document.createElement('style');\n    style.textContent = `\n        .touch-active {\n            transform: scale(0.95);\n            opacity: 0.8;\n            transition: all 0.1s ease;\n        }\n        \n        .table-scroll-indicator {\n            position: absolute;\n            bottom: -30px;\n            left: 50%;\n            transform: translateX(-50%);\n            font-size: 12px;\n            color: var(--text-muted);\n            background: var(--bg-primary);\n            padding: 4px 8px;\n            border-radius: 4px;\n            border: 1px solid var(--border-color);\n            white-space: nowrap;\n            z-index: 10;\n        }\n        \n        @media (max-width: 768px) {\n            .main-content.blur {\n                filter: blur(2px);\n                transition: filter 0.3s ease;\n            }\n        }\n    `;\n    document.head.appendChild(style);\n    \n})();
