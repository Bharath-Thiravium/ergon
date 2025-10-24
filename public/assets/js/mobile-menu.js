/**
 * Mobile Menu Toggle for ERGON
 * Hostinger Production Ready
 */

class MobileMenu {
    constructor() {
        this.init();
    }

    init() {
        this.createMobileToggle();
        this.setupEventListeners();
        this.handleResize();
    }

    createMobileToggle() {
        // Add mobile menu toggle button to header
        const headerLeft = document.querySelector('.app-header-left');
        if (headerLeft && window.innerWidth <= 768) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-menu-toggle';
            toggleBtn.innerHTML = '☰';
            toggleBtn.setAttribute('aria-label', 'Toggle Menu');
            headerLeft.insertBefore(toggleBtn, headerLeft.firstChild);
        }
    }

    setupEventListeners() {
        // Toggle sidebar on mobile
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mobile-menu-toggle')) {
                this.toggleSidebar();
            }
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                sidebar && 
                sidebar.classList.contains('sidebar--open') &&
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                this.closeSidebar();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('sidebar--open');
        }
    }

    closeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.remove('sidebar--open');
        }
    }

    handleResize() {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (window.innerWidth > 768) {
            // Desktop: remove mobile classes and toggle
            if (sidebar) {
                sidebar.classList.remove('sidebar--open');
            }
            if (toggle) {
                toggle.remove();
            }
        } else {
            // Mobile: ensure toggle exists
            if (!toggle) {
                this.createMobileToggle();
            }
        }
    }
}

// Initialize mobile menu when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new MobileMenu();
});