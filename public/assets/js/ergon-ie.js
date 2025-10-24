// ERGON Core JavaScript Functions - IE Compatible
// Essential functions for all pages

// Global ERGON object
window.ERGON = {
    baseUrl: '/ergon',
    apiUrl: '/ergon/api',
    
    // Utility functions
    utils: {
        // Show toast notification
        showToast: function(message, type) {
            type = type || 'info';
            var toast = document.createElement('div');
            toast.className = 'toast toast--' + type;
            toast.innerHTML = '<div class="toast__content"><span class="toast__icon">' + 
                this.getToastIcon(type) + '</span><span class="toast__message">' + message + '</span></div>';
            
            // Add toast styles if not already added
            if (!document.getElementById('toast-styles')) {
                var styles = document.createElement('style');
                styles.id = 'toast-styles';
                styles.innerHTML = '.toast{position:fixed;top:20px;right:20px;z-index:9999;background:white;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:16px;min-width:300px;max-width:500px;border-left:4px solid #3b82f6}.toast--success{border-left-color:#059669}.toast--error{border-left-color:#dc2626}.toast--warning{border-left-color:#d97706}.toast__content{display:flex;align-items:center;gap:12px}.toast__icon{font-size:18px}.toast__message{flex:1;color:#374151}';
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(toast);
            setTimeout(function() { 
                if (toast.parentNode) toast.parentNode.removeChild(toast); 
            }, 4000);
        },
        
        getToastIcon: function(type) {
            var icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            return icons[type] || icons.info;
        },
        
        // Confirm dialog
        confirm: function(message, callback) {
            if (window.confirm(message)) {
                callback();
            }
        }
    },
    
    // API helper functions
    api: {
        // POST request
        post: function(endpoint, data) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ERGON.apiUrl + endpoint, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            return new Promise(function(resolve, reject) {
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                resolve(JSON.parse(xhr.responseText));
                            } catch (e) {
                                resolve(xhr.responseText);
                            }
                        } else {
                            reject(new Error('HTTP error! status: ' + xhr.status));
                        }
                    }
                };
                xhr.onerror = function() {
                    reject(new Error('Network error'));
                };
                xhr.send(JSON.stringify(data));
            });
        }
    },
    
    // UI helpers
    ui: {
        // Show loading state
        showLoading: function(element, text) {
            text = text || 'Loading...';
            var originalContent = element.innerHTML;
            element.setAttribute('data-original-content', originalContent);
            element.innerHTML = '<span class="loading-spinner"></span> ' + text;
            element.disabled = true;
        },
        
        // Hide loading state
        hideLoading: function(element) {
            var originalContent = element.getAttribute('data-original-content');
            if (originalContent) {
                element.innerHTML = originalContent;
                element.removeAttribute('data-original-content');
            }
            element.disabled = false;
        }
    }
};

// Page-specific functions
window.ERGON.pages = {
    users: {
        resetPassword: function(userId, userName) {
            ERGON.utils.confirm('Reset password for ' + userName + '? This will generate a new temporary password.', function() {
                var btn = event.target;
                ERGON.ui.showLoading(btn, 'Resetting...');
                
                ERGON.api.post('/users/reset-password', { user_id: userId })
                    .then(function(data) {
                        ERGON.ui.hideLoading(btn);
                        if (data.success) {
                            ERGON.utils.showToast('Password reset successful! New password: ' + data.temp_password, 'success');
                        } else {
                            ERGON.utils.showToast('Password reset failed: ' + data.error, 'error');
                        }
                    }, function(error) {
                        ERGON.ui.hideLoading(btn);
                        ERGON.utils.showToast('Error: ' + error.message, 'error');
                    });
            });
        },
        
        deleteUser: function(userId, userName) {
            var action = prompt('Choose action for ' + userName + ':\n\n1. Type \'inactive\' to mark as inactive (resigned)\n2. Type \'delete\' to permanently delete (mistaken entry)\n\nEnter your choice:');
            
            if (action === 'inactive') {
                ERGON.utils.confirm('Mark ' + userName + ' as inactive? This will disable their access but keep their data.', function() {
                    ERGON.api.post('/users/inactive/' + userId, {})
                        .then(function(data) {
                            if (data.success) {
                                ERGON.utils.showToast('User marked as inactive!', 'success');
                                setTimeout(function() { location.reload(); }, 1000);
                            } else {
                                ERGON.utils.showToast('Failed: ' + data.error, 'error');
                            }
                        });
                });
            } else if (action === 'delete') {
                ERGON.utils.confirm('PERMANENTLY DELETE ' + userName + '? This cannot be undone and will remove all their data.', function() {
                    ERGON.api.post('/users/delete/' + userId, {})
                        .then(function(data) {
                            if (data.success) {
                                ERGON.utils.showToast('User permanently deleted!', 'success');
                                setTimeout(function() { location.reload(); }, 1000);
                            } else {
                                ERGON.utils.showToast('Delete failed: ' + data.error, 'error');
                            }
                        });
                });
            }
        }
    },
    
    planner: {
        toggleTaskSource: function() {
            var source = document.getElementById('taskSource').value;
            var plannedSection = document.getElementById('plannedTaskSection');
            var adhocSection = document.getElementById('adhocTaskSection');
            var planSelect = document.querySelector('[name="plan_id"]');
            var adhocTitle = document.querySelector('[name="adhoc_title"]');
            
            if (source === 'planned') {
                plannedSection.style.display = 'block';
                adhocSection.style.display = 'none';
                if (planSelect) planSelect.required = true;
                if (adhocTitle) adhocTitle.required = false;
            } else {
                plannedSection.style.display = 'none';
                adhocSection.style.display = 'block';
                if (planSelect) planSelect.required = false;
                if (adhocTitle) adhocTitle.required = true;
            }
        }
    }
};

// Make functions globally available
window.resetPassword = ERGON.pages.users.resetPassword;
window.deleteUser = ERGON.pages.users.deleteUser;
window.toggleTaskSource = ERGON.pages.planner.toggleTaskSource;

// Common event handlers
if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', function() {
        // Add loading spinner styles
        if (!document.getElementById('loading-styles')) {
            var styles = document.createElement('style');
            styles.id = 'loading-styles';
            styles.innerHTML = '.loading-spinner{display:inline-block;width:16px;height:16px;border:2px solid #f3f3f3;border-top:2px solid #3498db;border-radius:50%}.form-control.error{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,0.1)}';
            document.head.appendChild(styles);
        }
    });
}