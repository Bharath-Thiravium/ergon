<?php
/**
 * Security Testing Script
 * Access via: http://localhost/ergon/test_security.php
 */

require_once __DIR__ . '/app/services/SecurityService.php';
require_once __DIR__ . '/app/services/EmailService.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Security Test - Ergon</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔐 Security Features Test</h1>
    
    <div class="test">
        <h3>1. Rate Limiting Test</h3>
        <p>Click rapidly to test rate limiting (10 requests per 5 minutes):</p>
        <button onclick="testRateLimit()">Test Rate Limit</button>
        <div id="rateLimitResult"></div>
    </div>
    
    <div class="test">
        <h3>2. Account Lockout Test</h3>
        <p>Test account lockout after failed login attempts:</p>
        <form onsubmit="testLogin(event)">
            <input type="email" placeholder="Email" id="testEmail" value="test@example.com">
            <input type="password" placeholder="Wrong Password" id="testPassword" value="wrongpassword">
            <button type="submit">Test Failed Login</button>
        </form>
        <div id="loginResult"></div>
    </div>
    
    <div class="test">
        <h3>3. Password Reset Test</h3>
        <p>Test password reset email functionality:</p>
        <form onsubmit="testPasswordReset(event)">
            <input type="email" placeholder="Email" id="resetEmail" value="test@example.com">
            <button type="submit">Test Password Reset</button>
        </form>
        <div id="resetResult"></div>
    </div>
    
    <div class="test">
        <h3>4. Database Tables Check</h3>
        <button onclick="checkTables()">Check Security Tables</button>
        <div id="tablesResult"></div>
    </div>

    <script>
    let rateLimitCount = 0;
    
    function testRateLimit() {
        rateLimitCount++;
        fetch('/ergon/login', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=test@test.com&password=test'
        })
        .then(response => {
            const result = document.getElementById('rateLimitResult');
            if (response.status === 429) {
                result.innerHTML = `<div class="success">✅ SECURITY WORKING! Rate limiting blocked after ${rateLimitCount} attempts</div>`;
                return;
            }
            return response.json().catch(() => ({ error: 'Invalid response format' }));
        })
        .then(data => {
            if (data) {
                const result = document.getElementById('rateLimitResult');
                result.innerHTML = `<div>Attempt ${rateLimitCount}: ${data.error || data.message || 'Request processed'}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('rateLimitResult').innerHTML = `<div class="error">Attempt ${rateLimitCount}: ${error.message}</div>`;
        });
    }
    
    function testLogin(event) {
        event.preventDefault();
        const email = document.getElementById('testEmail').value;
        const password = document.getElementById('testPassword').value;
        
        fetch('/ergon/login', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `email=${email}&password=${password}`
        })
        .then(response => {
            const result = document.getElementById('loginResult');
            if (response.status === 423) {
                result.innerHTML = `<div class="success">✅ SECURITY WORKING! Account lockout active (Status: ${response.status})</div>`;
                return;
            }
            return response.json().catch(() => ({ error: 'Invalid response' }));
        })
        .then(data => {
            if (data) {
                const result = document.getElementById('loginResult');
                result.innerHTML = `<div>Login attempt: ${data.error || data.message || 'Processed'}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('loginResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
        });
    }
    
    function testPasswordReset(event) {
        event.preventDefault();
        const email = document.getElementById('resetEmail').value;
        
        fetch('/ergon/auth/forgot-password', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `email=${email}`
        })
        .then(response => response.json().catch(() => ({ error: 'Invalid response format' })))
        .then(data => {
            const result = document.getElementById('resetResult');
            if (data.success) {
                result.innerHTML = `<div class="success">✅ Password reset working! ${data.message}</div>`;
            } else {
                result.innerHTML = `<div class="error">❌ ${data.error || 'Request failed'}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('resetResult').innerHTML = `<div class="error">Error: ${error.message}</div>`;
        });
    }
    
    function checkTables() {
        // This would need a backend endpoint to check tables
        document.getElementById('tablesResult').innerHTML = 
            '<div class="success">✅ Check your database for: login_attempts, rate_limit_log, password_change_log tables</div>';
    }
    </script>
</body>
</html>