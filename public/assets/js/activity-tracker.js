// Smart Activity Tracker for IT Department
class ActivityTracker {
    constructor() {
        this.isActive = true;
        this.lastActivity = Date.now();
        this.pingInterval = 5 * 60 * 1000; // 5 minutes
        this.idleThreshold = 10 * 60 * 1000; // 10 minutes
        this.init();
    }
    
    init() {
        // Only track for IT department users
        if (this.isDepartment('IT')) {
            this.startTracking();
            this.bindEvents();
        }
    }
    
    isDepartment(dept) {
        // Check if user is from IT department (flexible matching)
        const userDept = document.body.dataset.userDepartment || '';
        return userDept.includes(dept) || userDept.includes('Information') || userDept.includes('Technology');
    }
    
    startTracking() {
        // Send periodic pings
        setInterval(() => {
            this.sendPing();
        }, this.pingInterval);
        
        // Check for idle state
        setInterval(() => {
            this.checkIdleState();
        }, 30000); // Check every 30 seconds
    }
    
    bindEvents() {
        // Track user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, () => {
                this.updateActivity();
            }, true);
        });
        
        // Track page visibility
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.logActivity('break_start', 'User switched away from application');
            } else {
                this.logActivity('break_end', 'User returned to application');
                this.updateActivity();
            }
        });
    }
    
    updateActivity() {
        this.lastActivity = Date.now();
        if (!this.isActive) {
            this.isActive = true;
            this.logActivity('break_end', 'User became active');
        }
    }
    
    checkIdleState() {
        const now = Date.now();
        const timeSinceLastActivity = now - this.lastActivity;
        
        if (timeSinceLastActivity > this.idleThreshold && this.isActive) {
            this.isActive = false;
            this.logActivity('break_start', 'User became idle');
        }
    }
    
    sendPing() {
        if (this.isActive) {
            this.logActivity('system_ping', 'Active user ping');
        }
    }
    
    logActivity(type, description) {
        fetch('/ergon/api/activity-log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                activity_type: type,
                description: description,
                is_active: this.isActive
            })
        }).catch(err => {
            // Silent fail - don't interrupt user work
            console.debug('Activity log failed:', err);
        });
    }
}

// Initialize tracker when page loads
document.addEventListener('DOMContentLoaded', () => {
    new ActivityTracker();
});