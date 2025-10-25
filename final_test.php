<!DOCTYPE html>
<html>
<head>
    <title>Final Authentication Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        button { margin: 5px; padding: 10px; }
    </style>
</head>
<body>
    <h1>🧭 Final Authentication Test</h1>
    
    <div class="test">
        <h2>Step 1: Login Test</h2>
        <button onclick="doLogin()">Login with info@athenas.co.in</button>
        <div id="login-result"></div>
    </div>
    
    <div class="test">
        <h2>Step 2: Check Session</h2>
        <button onclick="checkSession()">Check Current Session</button>
        <div id="session-result"></div>
    </div>
    
    <div class="test">
        <h2>Step 3: Test Dashboard Access</h2>
        <button onclick="testDashboard()">Try Dashboard Access</button>
        <div id="dashboard-result"></div>
    </div>
    
    <div class="test">
        <h2>Step 4: Logout Test</h2>
        <button onclick="doLogout()">Logout</button>
        <div id="logout-result"></div>
    </div>

    <script>
    function showResult(elementId, message, type = 'info') {
        const element = document.getElementById(elementId);
        element.innerHTML = `<span class="${type}">${message}</span>`;
    }

    function doLogin() {
        showResult('login-result', 'Logging in...', 'info');
        
        const formData = new FormData();
        formData.append('email', 'info@athenas.co.in');
        formData.append('password', 'admin123');
        
        fetch('/ergon/login', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult('login-result', '✅ Login successful: ' + data.message, 'success');
                setTimeout(() => checkSession(), 1000);
            } else {
                showResult('login-result', '❌ Login failed: ' + (data.error || data.message), 'error');
            }
        })
        .catch(error => {
            showResult('login-result', '❌ Login error: ' + error.message, 'error');
        });
    }

    function checkSession() {
        showResult('session-result', 'Checking session...', 'info');
        
        fetch('/ergon/api/check-auth.php', {
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                showResult('session-result', 
                    `✅ Session valid: ${data.user.name} (${data.user.role})`, 'success');
            } else {
                showResult('session-result', '❌ Session invalid or expired', 'error');
            }
        })
        .catch(error => {
            showResult('session-result', '❌ Session check error: ' + error.message, 'error');
        });
    }

    function testDashboard() {
        showResult('dashboard-result', 'Testing dashboard access...', 'info');
        
        fetch('/ergon/dashboard', {
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.ok) {
                showResult('dashboard-result', '✅ Dashboard accessible', 'success');
            } else if (response.redirected) {
                showResult('dashboard-result', '❌ Dashboard redirected (not authenticated)', 'error');
            } else {
                showResult('dashboard-result', '❌ Dashboard access failed: ' + response.status, 'error');
            }
        })
        .catch(error => {
            showResult('dashboard-result', '❌ Dashboard test error: ' + error.message, 'error');
        });
    }

    function doLogout() {
        showResult('logout-result', 'Logging out...', 'info');
        
        fetch('/ergon/logout', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult('logout-result', '✅ Logout successful: ' + data.message, 'success');
                setTimeout(() => checkSession(), 1000);
            } else {
                showResult('logout-result', '❌ Logout failed: ' + (data.error || data.message), 'error');
            }
        })
        .catch(error => {
            showResult('logout-result', '❌ Logout error: ' + error.message, 'error');
        });
    }
    </script>
</body>
</html>