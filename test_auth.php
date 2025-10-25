<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test - ERGON</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧭 ERGON Authentication Test</h1>
    
    <div class="test-section">
        <h2>Current Session Status</h2>
        <?php
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            echo "<span class='success'>✅ User is logged in</span><br>";
            echo "<span class='info'>User ID: " . $_SESSION['user_id'] . "</span><br>";
            echo "<span class='info'>Name: " . ($_SESSION['user_name'] ?? 'Not set') . "</span><br>";
            echo "<span class='info'>Role: " . ($_SESSION['role'] ?? 'Not set') . "</span><br>";
            echo "<span class='info'>Login Time: " . (isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'Not set') . "</span><br>";
            echo "<span class='info'>Last Activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'Not set') . "</span><br>";
        } else {
            echo "<span class='error'>❌ User is not logged in</span><br>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test Actions</h2>
        <button onclick="testLogin()">Test Login</button>
        <button onclick="testDashboard()">Test Dashboard Access</button>
        <button onclick="testLogout()">Test Logout</button>
        <button onclick="checkAuthAPI()">Check Auth API</button>
    </div>
    
    <div class="test-section">
        <h2>Test Results</h2>
        <div id="results"></div>
    </div>
    
    <script>
    function showResult(message, type = 'info') {
        const results = document.getElementById('results');
        const div = document.createElement('div');
        div.className = type;
        div.innerHTML = new Date().toLocaleTimeString() + ': ' + message;
        results.appendChild(div);
    }
    
    function testLogin() {
        showResult('Testing login...', 'info');
        
        const formData = new FormData();
        formData.append('email', 'info@athenas.co.in');
        formData.append('password', 'admin123');
        
        fetch('/ergon/login', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult('✅ Login successful: ' + data.message, 'success');
            } else {
                showResult('❌ Login failed: ' + (data.error || data.message), 'error');
            }
        })
        .catch(error => {
            showResult('❌ Login error: ' + error.message, 'error');
        });
    }
    
    function testDashboard() {
        showResult('Testing dashboard access...', 'info');
        
        fetch('/ergon/dashboard')
        .then(response => {
            if (response.ok) {
                showResult('✅ Dashboard accessible', 'success');
            } else if (response.status === 302) {
                showResult('❌ Dashboard redirected (not authenticated)', 'error');
            } else {
                showResult('❌ Dashboard access failed: ' + response.status, 'error');
            }
        })
        .catch(error => {
            showResult('❌ Dashboard test error: ' + error.message, 'error');
        });
    }
    
    function testLogout() {
        showResult('Testing logout...', 'info');
        
        fetch('/ergon/logout', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult('✅ Logout successful: ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showResult('❌ Logout failed: ' + (data.error || data.message), 'error');
            }
        })
        .catch(error => {
            showResult('❌ Logout error: ' + error.message, 'error');
        });
    }
    
    function checkAuthAPI() {
        showResult('Checking auth API...', 'info');
        
        fetch('/ergon/api/check-auth.php')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                showResult('✅ Auth API: User authenticated - ' + data.user.name + ' (' + data.user.role + ')', 'success');
            } else {
                showResult('❌ Auth API: User not authenticated', 'error');
            }
        })
        .catch(error => {
            showResult('❌ Auth API error: ' + error.message, 'error');
        });
    }
    </script>
    
    <div class="test-section">
        <h2>Navigation</h2>
        <a href="/ergon/login">Go to Login</a> | 
        <a href="/ergon/dashboard">Go to Dashboard</a> |
        <a href="/ergon/verify_login.php">Verify Login</a>
    </div>
    
</body>
</html>