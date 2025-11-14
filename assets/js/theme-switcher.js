// Theme Switcher - Minimal
class ThemeSwitcher {
    constructor() {
        this.init();
    }
    
    init() {
        const theme = localStorage.getItem('theme') || 'light';
        this.setTheme(theme);
    }
    
    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    }
    
    toggle() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = current === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.themeSwitcher = new ThemeSwitcher();
});