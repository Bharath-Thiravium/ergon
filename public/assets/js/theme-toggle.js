/**
 * Theme Toggle System for ERGON
 * Hostinger Production Ready
 */

class ThemeManager {
    constructor() {
        this.init();
    }

    init() {
        // Load saved theme or default to light
        const savedTheme = localStorage.getItem('ergon-theme') || 'light';
        this.setTheme(savedTheme);
        
        // Setup toggle button
        this.setupToggleButton();
        
        // Listen for system theme changes
        this.setupSystemThemeListener();
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('ergon-theme', theme);
        
        // Update toggle button icon
        const themeIcon = document.getElementById('themeIcon');
        if (themeIcon) {
            themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    setupToggleButton() {
        const toggleBtn = document.querySelector('.theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggleTheme());
        }
    }

    setupSystemThemeListener() {
        // Listen for system theme changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (e) => {
                // Only auto-switch if user hasn't manually set a preference
                if (!localStorage.getItem('ergon-theme-manual')) {
                    this.setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    // Mark theme as manually set
    setManualTheme(theme) {
        localStorage.setItem('ergon-theme-manual', 'true');
        this.setTheme(theme);
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.ergonTheme = new ThemeManager();
});

// Global functions for backward compatibility
function toggleTheme() {
    if (window.ergonTheme) {
        window.ergonTheme.setManualTheme(
            document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light'
        );
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}