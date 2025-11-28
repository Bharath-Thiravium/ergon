class ThemeSwitcher {
    constructor() {
        this.currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        this.init();
    }
    
    init() {
        this.updateToggleButton(this.currentTheme);
        this.bindEvents();
    }
    
    applyTheme(theme) {
        this.currentTheme = theme;
        
        // Apply theme to root elements
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.className = 'theme-' + theme;
        document.body.setAttribute('data-theme', theme);
        
        // Force repaint to ensure styles are applied
        document.body.offsetHeight;
        
        // Update toggle button icon
        this.updateToggleButton(theme);
        
        // Save preference
        localStorage.setItem('ergon_theme', theme);
        
        // Trigger custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }
    
    updateToggleButton(theme) {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        }
    }
    
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
    }
    
    bindEvents() {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeSwitcher = new ThemeSwitcher();
    });
} else {
    window.themeSwitcher = new ThemeSwitcher();
}