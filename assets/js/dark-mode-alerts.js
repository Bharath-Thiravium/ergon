/* 🌙 Dark Mode Alert & Notification JavaScript Enhancements */

(function() {
    'use strict';

    // Enhanced showMessage function with Dark Mode support
    function enhancedShowMessage(message, type = 'success', title = null) {
        const modal = document.getElementById('universalModal');
        const icon = document.getElementById('universalIcon');
        const titleEl = document.getElementById('universalTitle');
        const messageEl = document.getElementById('universalMessage');
        
        if (!modal || !icon || !titleEl || !messageEl) {
            // Fallback to browser alert if modal elements not found
            alert(message);
            return;
        }
        
        const config = {
            success: { icon: '✅', title: title || 'Success!' },
            error: { icon: '❌', title: title || 'Error!' },
            warning: { icon: '⚠️', title: title || 'Warning!' },
            info: { icon: 'ℹ️', title: title || 'Information' }
        };
        
        const typeConfig = config[type] || config.success;
        icon.textContent = typeConfig.icon;
        titleEl.textContent = typeConfig.title;
        messageEl.textContent = message;
        
        // Apply Dark Mode class if needed
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                          document.body.classList.contains('theme-dark');
        
        modal.className = `universal-modal ${type} show`;
        if (isDarkMode) {
            modal.classList.add('theme-dark');
        }
        
        modal.style.display = 'flex';
        
        // Auto close after appropriate time
        const autoCloseTime = type === 'success' ? 4000 : 6000;
        setTimeout(() => {
            if (modal.classList.contains('show')) {
                closeUniversalModal();
            }
        }, autoCloseTime);
    }

    // Enhanced toast notification function
    function showToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        
        // Apply Dark Mode styling if needed
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                          document.body.classList.contains('theme-dark');
        
        if (isDarkMode) {
            toast.classList.add('theme-dark');
        }
        
        // Add icon based on type
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        toast.innerHTML = `
            <span style="margin-right: 8px;">${icons[type] || icons.success}</span>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Hide and remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    // Enhanced alert creation function
    function createAlert(message, type = 'info', container = null) {
        const alert = document.createElement('div');
        alert.className = `alert alert--${type}`;
        
        // Apply Dark Mode styling if needed
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                          document.body.classList.contains('theme-dark');
        
        if (isDarkMode) {
            alert.classList.add('theme-dark');
        }
        
        // Add icon and message
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        alert.innerHTML = `
            <span style="margin-right: 8px;">${icons[type] || icons.info}</span>
            <span>${message}</span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit; opacity: 0.7;">×</button>
        `;
        
        // Insert alert
        if (container) {
            container.insertBefore(alert, container.firstChild);
        } else {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.insertBefore(alert, mainContent.firstChild);
            } else {
                document.body.insertBefore(alert, document.body.firstChild);
            }
        }
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (document.body.contains(alert)) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
        
        return alert;
    }

    // Enhanced notification system
    function enhanceNotifications() {
        // Override native alert function
        const originalAlert = window.alert;
        window.alert = function(message) {
            if (typeof message === 'string' && message.startsWith('✅')) {
                enhancedShowMessage(message.replace('✅ ', ''), 'success');
            } else if (typeof message === 'string' && message.startsWith('❌')) {
                enhancedShowMessage(message.replace('❌ ', ''), 'error');
            } else if (typeof message === 'string' && message.startsWith('⚠️')) {
                enhancedShowMessage(message.replace('⚠️ ', ''), 'warning');
            } else {
                enhancedShowMessage(message, 'info');
            }
        };

        // Enhance existing notification functions
        if (window.showMessage) {
            window.showMessage = enhancedShowMessage;
        }
        
        // Add new utility functions
        window.showToast = showToast;
        window.createAlert = createAlert;
        window.showSuccess = (message, title) => enhancedShowMessage(message, 'success', title);
        window.showError = (message, title) => enhancedShowMessage(message, 'error', title);
        window.showWarning = (message, title) => enhancedShowMessage(message, 'warning', title);
        window.showInfo = (message, title) => enhancedShowMessage(message, 'info', title);
    }

    // Theme change observer
    function observeThemeChanges() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    updateExistingAlerts();
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }

    // Update existing alerts when theme changes
    function updateExistingAlerts() {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        
        // Update alerts
        document.querySelectorAll('.alert, .toast, .notification, .universal-modal').forEach(element => {
            if (isDarkMode) {
                element.classList.add('theme-dark');
            } else {
                element.classList.remove('theme-dark');
            }
        });
    }

    // Initialize when DOM is ready
    function initialize() {
        enhanceNotifications();
        observeThemeChanges();
        
        // Update existing alerts on load
        updateExistingAlerts();
        
        // Ensure all dynamically created alerts are visible
        const style = document.createElement('style');
        style.textContent = `
            .alert:not(.d-none):not([style*="display: none"]) {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        `;
        document.head.appendChild(style);
    }

    // Initialize immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

})();
