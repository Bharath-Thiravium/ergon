/**
 * Enhanced Notifications JavaScript
 * Handles all notification functionality with proper error handling
 */

class NotificationManager {
    constructor() {
        this.apiEndpoint = '/ergon/api/notifications_unified.php';
        this.fallbackEndpoint = '/ergon/api/notifications.php';
        this.retryCount = 0;
        this.maxRetries = 3;
        this.cache = new Map();
        this.lastFetch = 0;
        this.cacheTimeout = 30000; // 30 seconds
        
        this.init();
    }
    
    init() {
        // Initialize notification system
        this.updateBadge();
        this.setupEventListeners();
        
        // Auto-refresh every 60 seconds
        setInterval(() => {
            this.updateBadge();
        }, 60000);
    }
    
    setupEventListeners() {
        // Handle notification dropdown toggle
        const notificationBtn = document.querySelector('[onclick*="toggleNotifications"]');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        // Handle clicks outside dropdown to close it
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown && !e.target.closest('#notificationDropdown') && !e.target.closest('[onclick*="toggleNotifications"]')) {
                dropdown.style.display = 'none';
            }
        });
    }
    
    async makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            defaultOptions.headers['X-CSRF-Token'] = csrfToken.getAttribute('content');
        }
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.retryCount = 0; // Reset retry count on success
            return data;
            
        } catch (error) {
            console.warn('Request failed:', error.message);
            
            // Retry with fallback endpoint if primary fails
            if (this.retryCount < this.maxRetries && url === this.apiEndpoint) {
                this.retryCount++;
                console.log(`Retrying with fallback endpoint (attempt ${this.retryCount})`);
                return this.makeRequest(this.fallbackEndpoint, options);
            }
            
            throw error;
        }
    }
    
    async fetchNotifications(options = {}) {
        const now = Date.now();
        const cacheKey = JSON.stringify(options);
        
        // Check cache first
        if (this.cache.has(cacheKey) && (now - this.lastFetch) < this.cacheTimeout) {
            return this.cache.get(cacheKey);
        }
        
        try {
            const params = new URLSearchParams(options);
            const url = `${this.apiEndpoint}?${params}`;
            
            const data = await this.makeRequest(url);
            
            if (data.success) {
                this.cache.set(cacheKey, data);
                this.lastFetch = now;
                return data;
            } else {
                throw new Error(data.error || 'Failed to fetch notifications');
            }
            
        } catch (error) {
            console.error('Failed to fetch notifications:', error.message);
            this.showError('Failed to load notifications');
            return { success: false, notifications: [], unread_count: 0 };
        }
    }
    
    async updateBadge() {
        try {
            const data = await this.makeRequest(this.apiEndpoint, {
                method: 'POST',
                body: JSON.stringify({ action: 'get-unread-count' })
            });
            
            if (data.success) {
                this.setBadgeCount(data.unread_count);
            }
        } catch (error) {
            console.warn('Failed to update notification badge:', error.message);
        }
    }
    
    setBadgeCount(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = count || 0;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            badge.classList.toggle('has-notifications', count > 0);
        }
    }
    
    async toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        const isVisible = dropdown.style.display === 'block';
        
        if (isVisible) {
            dropdown.style.display = 'none';
        } else {
            // Position dropdown
            const button = document.querySelector('[onclick*="toggleNotifications"]');
            if (button) {
                const rect = button.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.zIndex = '10000';
            }
            
            dropdown.style.display = 'block';
            await this.loadDropdownNotifications();
        }
    }
    
    async loadDropdownNotifications() {
        const list = document.getElementById('notificationList');
        if (!list) return;
        
        // Show loading state
        list.innerHTML = '<div class="notification-loading">Loading notifications...</div>';
        
        try {
            const data = await this.fetchNotifications({ limit: 10 });
            
            if (data.success && data.notifications && data.notifications.length > 0) {
                list.innerHTML = data.notifications.map(notif => this.renderNotificationItem(notif)).join('');
            } else {
                list.innerHTML = '<div class="notification-loading">No notifications</div>';
            }
            
        } catch (error) {
            list.innerHTML = '<div class="notification-loading">Failed to load notifications</div>';
        }
    }
    
    renderNotificationItem(notification) {
        const time = this.formatTime(notification.created_at);
        const title = this.escapeHtml(notification.title || 'Notification');
        const message = this.escapeHtml(notification.message || '');
        const link = this.getNotificationLink(notification);
        
        return `
            <a href="${this.escapeHtml(link)}" class="notification-item" onclick="notificationManager.closeDropdown()">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
                <div class="notification-time">${time}</div>
            </a>
        `;
    }
    
    getNotificationLink(notification) {
        const baseUrl = '/ergon';
        const refType = notification.reference_type || notification.module_name;
        const refId = notification.reference_id;
        
        // Use action_url if available
        if (notification.action_url) {
            return notification.action_url;
        }
        
        // Generate link based on reference type
        switch (refType) {
            case 'task':
            case 'tasks':
                return refId ? `${baseUrl}/tasks/view/${refId}` : `${baseUrl}/tasks`;
            case 'leave':
            case 'leaves':
                return refId ? `${baseUrl}/leaves/view/${refId}` : `${baseUrl}/leaves`;
            case 'expense':
            case 'expenses':
                return refId ? `${baseUrl}/expenses/view/${refId}` : `${baseUrl}/expenses`;
            case 'advance':
            case 'advances':
                return refId ? `${baseUrl}/advances/view/${refId}` : `${baseUrl}/advances`;
            default:
                return `${baseUrl}/notifications`;
        }
    }
    
    formatTime(dateStr) {
        try {
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            
            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes}m ago`;
            
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours}h ago`;
            
            const days = Math.floor(hours / 24);
            if (days < 7) return `${days}d ago`;
            
            return date.toLocaleDateString();
        } catch (error) {
            return 'Recently';
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const data = await this.makeRequest(this.apiEndpoint, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'mark-read',
                    id: parseInt(notificationId)
                })
            });
            
            if (data.success) {
                // Update UI
                const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (row) {
                    row.classList.remove('notification--unread');
                    row.classList.add('notification--read');
                    
                    const badge = row.querySelector('.badge--warning');
                    if (badge) badge.remove();
                    
                    const button = row.querySelector('.ab-btn--success');
                    if (button) button.remove();
                }
                
                // Update badge
                this.setBadgeCount(data.unread_count);
                this.showSuccess('Notification marked as read');
            } else {
                throw new Error(data.error || 'Failed to mark as read');
            }
            
        } catch (error) {
            console.error('Mark as read failed:', error.message);
            this.showError('Failed to mark notification as read');
        }
    }
    
    async markAllAsRead() {
        try {
            const data = await this.makeRequest(this.apiEndpoint, {
                method: 'POST',
                body: JSON.stringify({ action: 'mark-all-read' })
            });
            
            if (data.success) {
                // Update UI
                const unreadRows = document.querySelectorAll('.notification--unread');
                unreadRows.forEach(row => {
                    row.classList.remove('notification--unread');
                    row.classList.add('notification--read');
                    
                    const badge = row.querySelector('.badge--warning');
                    if (badge) badge.remove();
                    
                    const button = row.querySelector('.ab-btn--success');
                    if (button) button.remove();
                });
                
                // Update badge
                this.setBadgeCount(0);
                this.showSuccess('All notifications marked as read');
            } else {
                throw new Error(data.error || 'Failed to mark all as read');
            }
            
        } catch (error) {
            console.error('Mark all as read failed:', error.message);
            this.showError('Failed to mark all notifications as read');
        }
    }
    
    async markSelectedAsRead() {
        const selected = document.querySelectorAll('.notification-checkbox:checked');
        const ids = Array.from(selected).map(cb => parseInt(cb.value)).filter(id => id > 0);
        
        if (ids.length === 0) {
            this.showWarning('No notifications selected');
            return;
        }
        
        try {
            const data = await this.makeRequest(this.apiEndpoint, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'mark-selected-read',
                    ids: ids
                })
            });
            
            if (data.success) {
                // Update UI for selected notifications
                selected.forEach(cb => {
                    const row = cb.closest('tr');
                    if (row) {
                        row.classList.remove('notification--unread');
                        row.classList.add('notification--read');
                        
                        const badge = row.querySelector('.badge--warning');
                        if (badge) badge.remove();
                        
                        const button = row.querySelector('.ab-btn--success');
                        if (button) button.remove();
                        
                        cb.checked = false;
                    }
                });
                
                // Update badge
                this.setBadgeCount(data.unread_count);
                this.showSuccess(`${data.marked_count} notifications marked as read`);
                
                // Update button states
                this.updateMarkSelectedButton();
            } else {
                throw new Error(data.error || 'Failed to mark selected as read');
            }
            
        } catch (error) {
            console.error('Mark selected as read failed:', error.message);
            this.showError('Failed to mark selected notifications as read');
        }
    }
    
    toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.notification-checkbox');
        
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        
        this.updateMarkSelectedButton();
    }
    
    updateMarkSelectedButton() {
        const selected = document.querySelectorAll('.notification-checkbox:checked');
        const button = document.getElementById('markSelectedBtn');
        
        if (button) {
            button.disabled = selected.length === 0;
        }
    }
    
    showSuccess(message) {
        this.showMessage(message, 'success');
    }
    
    showError(message) {
        this.showMessage(message, 'error');
    }
    
    showWarning(message) {
        this.showMessage(message, 'warning');
    }
    
    showMessage(message, type = 'info') {
        // Try to use existing message modal
        if (typeof showMessage === 'function') {
            showMessage(message, type);
            return;
        }
        
        // Fallback to simple alert
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        alert(`${icons[type] || icons.info} ${message}`);
    }
    
    // Clear cache when needed
    clearCache() {
        this.cache.clear();
        this.lastFetch = 0;
    }
}

// Initialize notification manager when DOM is ready
let notificationManager;

document.addEventListener('DOMContentLoaded', function() {
    notificationManager = new NotificationManager();
    
    // Make functions globally available for backward compatibility
    window.markAsRead = (id) => notificationManager.markAsRead(id);
    window.markAllAsRead = () => notificationManager.markAllAsRead();
    window.markSelectedAsRead = () => notificationManager.markSelectedAsRead();
    window.toggleSelectAll = () => notificationManager.toggleSelectAll();
    window.updateMarkSelectedButton = () => notificationManager.updateMarkSelectedButton();
    window.toggleNotifications = (e) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        notificationManager.toggleDropdown();
    };
    window.navigateToNotifications = (e) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        notificationManager.closeDropdown();
        setTimeout(() => {
            window.location.href = '/ergon/notifications';
        }, 100);
    };
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}