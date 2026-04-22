// User status check for automatic logout
(function() {
    var started = false;

    function checkUserStatus() {
        fetch('/ergon/api/check-auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.active && data.authenticated === false) {
                if (data.role_changed) {
                    alert('Your role has been changed. You will be logged out to apply new permissions.');
                } else if (data.message === 'User deactivated') {
                    alert('Your account has been deactivated. You will be logged out.');
                } else {
                    // Not authenticated (session expired) — redirect silently
                    window.location.href = '/ergon/logout';
                    return;
                }
                window.location.href = '/ergon/logout';
            }
        })
        .catch(function() {
            // Silent fail - network issues should never force logout
        });
    }

    // Wait 10 seconds after page load before first check
    // This prevents false logout immediately after login redirect
    setTimeout(function() {
        started = true;
        checkUserStatus();
        // Then poll every 60 seconds
        setInterval(checkUserStatus, 60000);
    }, 10000);

    // Only check on focus if the page has been open for at least 10 seconds
    window.addEventListener('focus', function() {
        if (started) {
            checkUserStatus();
        }
    });
})();
