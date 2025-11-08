// Notification Fix - Prevent page loading in dropdown
document.addEventListener('DOMContentLoaded', function() {
    // Fix notification dropdown behavior
    const notificationDropdown = document.getElementById('notificationDropdown');
    const viewAllLink = document.querySelector('.view-all-link');
    
    if (viewAllLink) {
        viewAllLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close dropdown
            if (notificationDropdown) {
                notificationDropdown.style.display = 'none';
            }
            
            // Navigate to notifications page
            window.location.href = '/ergon/notifications';
        });
    }
    
    // Fix mark as read buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('mark-read-btn')) {
            e.preventDefault();
            const id = e.target.dataset.id;
            markNotificationAsRead(id);
        }
        
        if (e.target.classList.contains('mark-all-read-btn')) {
            e.preventDefault();
            markAllNotificationsAsRead();
        }
    });
});

function markNotificationAsRead(id) {
    fetch('/ergon/api/notifications/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI without reload
            const notificationItem = document.querySelector(`[data-notification-id="${id}"]`);
            if (notificationItem) {
                notificationItem.classList.add('read');
            }
            updateNotificationBadge();
        } else {
            console.error('Failed to mark as read:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function markAllNotificationsAsRead() {
    fetch('/ergon/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI without reload
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.add('read');
            });
            updateNotificationBadge();
        } else {
            console.error('Failed to mark all as read:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateNotificationBadge() {
    fetch('/ergon/api/notifications/unread-count')
    .then(response => response.json())
    .then(data => {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = data.count || 0;
            badge.style.display = data.count > 0 ? 'block' : 'none';
        }
    })
    .catch(error => {
        console.error('Error updating badge:', error);
    });
}