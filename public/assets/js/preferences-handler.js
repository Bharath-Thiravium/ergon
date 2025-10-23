// Preferences Handler
document.addEventListener('DOMContentLoaded', function() {
    const theme = document.body.dataset.theme;
    const layout = document.body.dataset.layout;
    const lang = document.body.dataset.lang;
    
    // Apply theme
    if (theme === 'auto') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.body.dataset.theme = prefersDark ? 'dark' : 'light';
    }
    
    // Apply layout preferences
    if (layout === 'compact') {
        document.body.classList.add('layout-compact');
    } else if (layout === 'expanded') {
        document.body.classList.add('layout-expanded');
    }
    
    // Apply language preferences
    if (lang !== 'en') {
        document.documentElement.lang = lang;
    }
    
    // Show preference applied notification
    if (window.location.search.includes('success=updated')) {
        showPreferenceNotification();
    }
});

// Theme toggle function
function toggleTheme() {
    const currentTheme = document.body.dataset.theme;
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    // Update UI immediately
    document.body.dataset.theme = newTheme;
    
    // Update icon
    const icon = document.getElementById('themeIcon');
    if (icon) {
        icon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    }
    
    // Save to server
    fetch('/ergon/api/update-preference', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({key: 'theme', value: newTheme})
    }).catch(e => console.log('Theme save failed:', e));
}

function showPreferenceNotification() {
    const notification = document.createElement('div');
    notification.className = 'preference-notification';
    notification.innerHTML = '✅ Preferences saved and applied successfully!';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #4caf50;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .layout-compact .sidebar { 
        width: 70px; 
        transition: width 0.3s ease;
    }
    .layout-compact .sidebar:hover { 
        width: 250px; 
    }
    .layout-compact .sidebar__brand { 
        justify-content: center; 
        padding: 16px 8px;
    }
    .layout-compact .sidebar__brand span:last-child { 
        display: none; 
    }
    .layout-compact .sidebar:hover .sidebar__brand span:last-child { 
        display: inline; 
    }
    .layout-compact .sidebar__header h3 { 
        display: none; 
    }
    .layout-compact .sidebar:hover .sidebar__header h3 { 
        display: block; 
    }
    .layout-compact .sidebar__menu .sidebar__link { 
        justify-content: center; 
        padding: 12px 8px;
    }
    .layout-compact .sidebar__menu .sidebar__link span:not(.sidebar__icon) { 
        display: none; 
    }
    .layout-compact .sidebar:hover .sidebar__menu .sidebar__link { 
        justify-content: flex-start; 
        padding: 12px 16px;
    }
    .layout-compact .sidebar:hover .sidebar__menu .sidebar__link span:not(.sidebar__icon) { 
        display: inline; 
    }
    .layout-compact .main-content { 
        margin-left: 70px; 
        transition: margin-left 0.3s ease;
    }
    .layout-compact .sidebar__divider { 
        display: none; 
    }
    .layout-compact .sidebar:hover .sidebar__divider { 
        display: block; 
    }
    
    .layout-expanded .kpi-card { min-height: 140px; }
    .layout-expanded .card { padding: 24px; }
`;
document.head.appendChild(style);