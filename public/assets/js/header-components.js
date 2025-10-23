// Header Components JavaScript
let notificationDropdownOpen = false;
let profileDropdownOpen = false;

// Toggle Notifications
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    notificationDropdownOpen = !notificationDropdownOpen;
    
    if (notificationDropdownOpen) {
        dropdown.style.display = 'block';
        loadNotifications();
        closeProfile();
    } else {
        dropdown.style.display = 'none';
    }
}

// Toggle Profile
function toggleProfile() {
    const menu = document.getElementById('profileMenu');
    profileDropdownOpen = !profileDropdownOpen;
    
    if (profileDropdownOpen) {
        menu.style.display = 'block';
        closeNotifications();
    } else {
        menu.style.display = 'none';
    }
}

// Close dropdowns
function closeNotifications() {
    document.getElementById('notificationDropdown').style.display = 'none';
    notificationDropdownOpen = false;
}

function closeProfile() {
    document.getElementById('profileMenu').style.display = 'none';
    profileDropdownOpen = false;
}

// Load notifications
function loadNotifications() {
    const list = document.getElementById('notificationList');
    list.innerHTML = '<div class="notification-loading">Loading...</div>';
    
    fetch('/ergon/api/notifications/unread-count')
        .then(r => r.json())
        .then(data => {
            // Update badge
            const badge = document.getElementById('notificationBadge');
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'block' : 'none';
            
            // Load recent notifications (simplified)
            list.innerHTML = data.count > 0 ? 
                `<div class="notification-item-mini">
                    <div class="notification-content-mini">
                        <div class="notification-title-mini">You have ${data.count} unread notifications</div>
                        <div class="notification-time-mini">Click "View All" to see details</div>
                    </div>
                </div>` :
                '<div class="no-notifications">No new notifications</div>';
        })
        .catch(() => {
            list.innerHTML = '<div class="notification-error">Failed to load notifications</div>';
        });
}

// Update notification count periodically
function updateNotificationCount() {
    fetch('/ergon/api/notifications/unread-count')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'block' : 'none';
        })
        .catch(() => {});
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const notificationCenter = document.querySelector('.notification-center');
    const profileDropdown = document.querySelector('.profile-dropdown');
    
    if (!notificationCenter.contains(event.target)) {
        closeNotifications();
    }
    
    if (!profileDropdown.contains(event.target)) {
        closeProfile();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationCount();
    setInterval(updateNotificationCount, 30000); // Update every 30 seconds
});