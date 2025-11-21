// User status check for automatic logout
(function() {
    function checkUserStatus() {
        fetch('/ergon/api/check-auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.active) {
                if (data.role_changed) {
                    alert('Your role has been changed. You will be logged out to apply new permissions.');
                } else {
                    alert('Your account has been deactivated. You will be logged out.');
                }
                window.location.href = '/ergon/logout';
            }
        })
        .catch(() => {
            // Silent fail - network issues shouldn't force logout
        });
    }
    
    // Check status every 30 seconds
    setInterval(checkUserStatus, 30000);
    
    // Check on page focus
    window.addEventListener('focus', checkUserStatus);
})();