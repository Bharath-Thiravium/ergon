// Planner Access Control - Hide timer buttons for Owner
document.addEventListener('DOMContentLoaded', function() {
    // Check if current user is Owner (User ID 1)
    const isOwner = window.currentUserId === 1 || window.userRole === 'owner';
    
    if (isOwner) {
        // Hide all planner timer buttons for Owner
        const plannerButtons = document.querySelectorAll('.timer-start-btn, .timer-pause-btn, .timer-resume-btn, .planner-action-btn');
        plannerButtons.forEach(btn => {
            btn.style.display = 'none';
        });
        
        // Hide planner navigation for Owner
        const plannerNav = document.querySelector('a[href*="daily-planner"], a[href*="planner"]');
        if (plannerNav) {
            plannerNav.style.display = 'none';
        }
        
        // Show message if Owner tries to access planner
        const plannerContainer = document.querySelector('.daily-planner-container, .planner-container');
        if (plannerContainer && window.location.href.includes('planner')) {
            plannerContainer.innerHTML = '<div class="access-denied"><h3>⚠️ Access Restricted</h3><p>Planner functions are available only for Admin and User roles.</p><a href="/ergon/tasks" class="btn btn-primary">Go to Tasks</a></div>';
        }
    }
});