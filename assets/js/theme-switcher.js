class ThemeSwitcher {
    constructor() {
        this.currentTheme = 'light';
        this.init();
    }
    
    init() {
        this.loadTheme();
        this.bindEvents();
    }
    
    loadTheme() {
        const savedTheme = localStorage.getItem('ergon_theme') || document.body.getAttribute('data-theme');
        this.currentTheme = savedTheme || 'light';
        
        this.applyTheme(this.currentTheme);
        this.updateToggleButton(this.currentTheme);
    }
    
    applyTheme(theme) {
        this.currentTheme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        
        // Apply theme to main content areas
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.setAttribute('data-theme', theme);
        }
        
        // Apply to all UI components
        const selectors = [
            '.card', '.card__header', '.card__body', '.card__footer',
            '.table', '.table th', '.table td',
            '.form-control', '.form-label',
            '.modal', '.modal-content', '.modal-header', '.modal-body', '.modal-footer',
            '.btn', '.btn--secondary',
            '.kpi-card', '.admin-card', '.user-card',
            '.alert', '.badge', '.notification-item',
            '.page-title', '.empty-state',
            'input', 'textarea', 'select', 'button'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.setAttribute('data-theme', theme);
            });
        });
        
        // Force repaint
        document.body.style.display = 'none';
        document.body.offsetHeight; // Trigger reflow
        document.body.style.display = '';
        
        localStorage.setItem('ergon_theme', theme);
        this.saveToServer(theme);
    }
    
    saveToServer(theme) {
        if (typeof fetch !== 'undefined') {
            fetch('/ergon/api/update-preference', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    key: 'theme',
                    value: theme
                })
            }).catch(err => console.log('Theme save failed:', err));
        }
    }
    
    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
        this.updateToggleButton(newTheme);
    }
    
    updateToggleButton(theme) {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        }
        
        // Also update header background for dark theme
        const header = document.querySelector('.main-header');
        if (header) {
            header.setAttribute('data-theme', theme);
        }
    }
    
    bindEvents() {
        setTimeout(() => {
            const toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleTheme();
                });
            }
        }, 100);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeSwitcher = new ThemeSwitcher();
    });
} else {
    window.themeSwitcher = new ThemeSwitcher();
}