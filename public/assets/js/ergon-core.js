// ERGON Core JavaScript Functions
// Essential functions for all pages

// Global ERGON object
window.ERGON = {
    baseUrl: '/ergon',
    apiUrl: '/ergon/api',
    
    // Utility functions
    utils: {
        // Show toast notification
        showToast: function(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast--${type}`;
            toast.innerHTML = `
                <div class="toast__content">
                    <span class="toast__icon">${this.getToastIcon(type)}</span>
                    <span class="toast__message">${message}</span>
                </div>
            `;
            
            // Add toast styles if not already added
            if (!document.getElementById('toast-styles')) {
                const styles = document.createElement('style');
                styles.id = 'toast-styles';
                styles.textContent = `
                    .toast {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 9999;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        padding: 16px;
                        min-width: 300px;
                        max-width: 500px;
                        border-left: 4px solid #3b82f6;
                        animation: slideIn 0.3s ease;
                    }
                    .toast--success { border-left-color: #059669; }
                    .toast--error { border-left-color: #dc2626; }
                    .toast--warning { border-left-color: #d97706; }
                    .toast__content {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }
                    .toast__icon { font-size: 18px; }
                    .toast__message { flex: 1; color: #374151; }
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        },
        
        getToastIcon: function(type) {
            const icons = {
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
        },
        
        // Format date
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        
        // Format time
        formatTime: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    },
    
    // API helper functions
    api: {
        // Generic API call
        call: function(endpoint, options = {}) {
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            const finalOptions = { ...defaultOptions, ...options };
            
            return fetch(ERGON.apiUrl + endpoint, finalOptions)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('API Error:', error);
                    ERGON.utils.showToast('Network error occurred', 'error');
                    throw error;
                });
        },
        
        // GET request
        get: function(endpoint) {
            return this.call(endpoint);
        },
        
        // POST request
        post: function(endpoint, data) {
            return this.call(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },
        
        // PUT request
        put: function(endpoint, data) {
            return this.call(endpoint, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },
        
        // DELETE request
        delete: function(endpoint) {
            return this.call(endpoint, {
                method: 'DELETE'
            });
        }
    },
    
    // Form helpers
    forms: {
        // Serialize form data
        serialize: function(form) {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            return data;
        },
        
        // Validate form
        validate: function(form) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            return isValid;
        },
        
        // Reset form
        reset: function(form) {
            form.reset();
            form.querySelectorAll('.error').forEach(field => {
                field.classList.remove('error');
            });
        }
    },
    
    // UI helpers
    ui: {
        // Show loading state
        showLoading: function(element, text = 'Loading...') {
            const originalContent = element.innerHTML;
            element.dataset.originalContent = originalContent;
            element.innerHTML = `<span class="loading-spinner"></span> ${text}`;
            element.disabled = true;
        },
        
        // Hide loading state
        hideLoading: function(element) {
            if (element.dataset.originalContent) {
                element.innerHTML = element.dataset.originalContent;
                delete element.dataset.originalContent;
            }
            element.disabled = false;
        },
        
        // Toggle element visibility
        toggle: function(element) {
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }
    }
};

// Common event handlers
document.addEventListener('DOMContentLoaded', function() {
    // Add loading spinner styles
    if (!document.getElementById('loading-styles')) {
        const styles = document.createElement('style');
        styles.id = 'loading-styles';
        styles.textContent = `
            .loading-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .form-control.error {
                border-color: #dc2626;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Add click handlers for common buttons
    document.addEventListener('click', function(e) {
        // Handle delete buttons
        if (e.target.matches('[data-action="delete"]')) {
            e.preventDefault();
            const message = e.target.dataset.message || 'Are you sure you want to delete this item?';
            ERGON.utils.confirm(message, function() {
                const url = e.target.href || e.target.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            });
        }
        
        // Handle form submit buttons
        if (e.target.matches('[data-action="submit"]')) {
            const form = e.target.closest('form');
            if (form && ERGON.forms.validate(form)) {
                ERGON.ui.showLoading(e.target, 'Submitting...');
            }
        }
    });
    
    // Handle form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.matches('.ajax-form')) {
            e.preventDefault();
            handleAjaxForm(form);
        }
    });
});

// AJAX form handler
function handleAjaxForm(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    const url = form.action || window.location.href;
    const method = form.method || 'POST';
    
    if (!ERGON.forms.validate(form)) {
        ERGON.utils.showToast('Please fill in all required fields', 'error');
        return;
    }
    
    ERGON.ui.showLoading(submitBtn, 'Submitting...');
    
    const formData = new FormData(form);
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ERGON.utils.showToast(data.message || 'Operation completed successfully', 'success');
            if (data.redirect) {
                setTimeout(() => window.location.href = data.redirect, 1000);
            } else if (data.reload) {
                setTimeout(() => window.location.reload(), 1000);
            }
        } else {
            ERGON.utils.showToast(data.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        ERGON.utils.showToast('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        ERGON.ui.hideLoading(submitBtn);
    });
}

// Additional utility functions for specific pages
window.ERGON.pages = {
    // User management functions
    users: {
        resetPassword: function(userId, userName) {
            ERGON.utils.confirm(`Reset password for ${userName}? This will generate a new temporary password.`, function() {
                const btn = event.target;
                ERGON.ui.showLoading(btn, 'Resetting...');
                
                ERGON.api.post('/users/reset-password', { user_id: userId })
                    .then(data => {
                        if (data.success) {
                            ERGON.utils.showToast(`Password reset successful! New password: ${data.temp_password}`, 'success');
                            // Auto-download credentials
                            const element = document.createElement('a');
                            const content = `ERGON Password Reset\n===================\n\nUser: ${userName}\nNew Password: ${data.temp_password}\n\nInstructions:\n1. User must login and reset password on first login\n2. Generated on: ${new Date().toLocaleString()}`;
                            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                            element.setAttribute('download', `password_reset_${userName.replace(/\s+/g, '_')}.txt`);
                            element.style.display = 'none';
                            document.body.appendChild(element);
                            element.click();
                            document.body.removeChild(element);
                        } else {
                            ERGON.utils.showToast('Password reset failed: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        ERGON.utils.showToast('Error: ' + error.message, 'error');
                    })
                    .finally(() => {
                        ERGON.ui.hideLoading(btn);
                    });
            });
        },
        
        deleteUser: function(userId, userName) {
            const action = prompt(`Choose action for ${userName}:\n\n1. Type 'inactive' to mark as inactive (resigned)\n2. Type 'delete' to permanently delete (mistaken entry)\n\nEnter your choice:`);
            
            if (action === 'inactive') {
                ERGON.utils.confirm(`Mark ${userName} as inactive? This will disable their access but keep their data.`, function() {
                    ERGON.api.post(`/users/inactive/${userId}`, {})
                        .then(data => {
                            if (data.success) {
                                ERGON.utils.showToast('User marked as inactive!', 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                ERGON.utils.showToast('Failed: ' + data.error, 'error');
                            }
                        });
                });
            } else if (action === 'delete') {
                ERGON.utils.confirm(`PERMANENTLY DELETE ${userName}? This cannot be undone and will remove all their data.`, function() {
                    ERGON.api.post(`/users/delete/${userId}`, {})
                        .then(data => {
                            if (data.success) {
                                ERGON.utils.showToast('User permanently deleted!', 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                ERGON.utils.showToast('Delete failed: ' + data.error, 'error');
                            }
                        });
                });
            }
        }
    },
    
    // Attendance functions
    attendance: {
        clockIn: function() {
            if (!navigator.geolocation) {
                ERGON.utils.showToast('Geolocation not supported by this browser', 'error');
                return;
            }
            
            const btn = event.target;
            ERGON.ui.showLoading(btn, 'Getting location...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const data = {
                        action: 'clock_in',
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        location_name: 'Office Location',
                        csrf_token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    };
                    
                    ERGON.api.post('/attendance/clock', data)
                        .then(response => {
                            if (response.success) {
                                ERGON.utils.showToast('Clocked in successfully!', 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                ERGON.utils.showToast('Clock in failed: ' + response.message, 'error');
                            }
                        })
                        .finally(() => {
                            ERGON.ui.hideLoading(btn);
                        });
                },
                function(error) {
                    ERGON.ui.hideLoading(btn);
                    ERGON.utils.showToast('Please allow location access to clock in', 'error');
                }
            );
        },
        
        clockOut: function() {
            const btn = event.target;
            ERGON.ui.showLoading(btn, 'Clocking out...');
            
            ERGON.api.post('/attendance/clock', { 
                action: 'clock_out',
                csrf_token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            })
                .then(response => {
                    if (response.success) {
                        ERGON.utils.showToast('Clocked out successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        ERGON.utils.showToast('Clock out failed: ' + response.message, 'error');
                    }
                })
                .finally(() => {
                    ERGON.ui.hideLoading(btn);
                });
        }
    },
    
    // Daily planner functions
    planner: {
        toggleTaskSource: function() {
            const source = document.getElementById('taskSource').value;
            const plannedSection = document.getElementById('plannedTaskSection');
            const adhocSection = document.getElementById('adhocTaskSection');
            const planSelect = document.querySelector('[name="plan_id"]');
            const adhocTitle = document.querySelector('[name="adhoc_title"]');
            
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
        },
        
        getLocation: function() {
            const btn = document.getElementById('getLocationBtn');
            const status = document.getElementById('locationStatus');
            
            if (!btn || !status) return;
            
            btn.disabled = true;
            btn.innerHTML = '📍 Getting Location...';
            status.textContent = 'Fetching GPS coordinates...';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const latField = document.getElementById('gpsLat');
                        const lngField = document.getElementById('gpsLng');
                        
                        if (latField) latField.value = position.coords.latitude;
                        if (lngField) lngField.value = position.coords.longitude;
                        
                        btn.innerHTML = '✅ Location Captured';
                        btn.className = 'btn btn--success';
                        status.textContent = `Location: ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
                    },
                    function(error) {
                        btn.disabled = false;
                        btn.innerHTML = '📍 Get GPS Location';
                        status.textContent = 'Location access denied or unavailable';
                        console.error('GPS Error:', error);
                    }
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '📍 Get GPS Location';
                status.textContent = 'GPS not supported by browser';
            }
        }
    }
};

// Make functions globally available for backward compatibility
window.resetPassword = ERGON.pages.users.resetPassword;
window.deleteUser = ERGON.pages.users.deleteUser;
window.toggleTaskSource = ERGON.pages.planner.toggleTaskSource;

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ERGON;
}