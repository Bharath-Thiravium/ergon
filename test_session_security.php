<?php
/**
 * Session Security Test Script
 * Tests login/logout session management and prevents unauthorized re-entry
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>🔐 Session Security Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test { margin: 10px 0; padding: 10px; border-radius: 5px; }
.pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
</style>";

// Test 1: Check current session status
echo "<div class='test info'>";
echo "<h3>Test 1: Current Session Status</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
echo "</div>";

// Test 2: Session validation
echo "<div class='test " . (isset($_SESSION['user_id']) ? 'pass' : 'fail') . "'>";
echo "<h3>Test 2: Session Validation</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Session is valid - User is logged in";
} else {
    echo "❌ Session is invalid - User is not logged in";
}
echo "</div>";

// Test 3: Protected page access test
echo "<div class='test info'>";
echo "<h3>Test 3: Protected Page Access</h3>";
echo "<a href='/ergon/users' target='_blank'>Test Users Page Access</a><br>";
echo "<a href='/ergon/reports' target='_blank'>Test Reports Page Access</a><br>";
echo "<a href='/ergon/settings' target='_blank'>Test Settings Page Access</a><br>";
echo "<small>These should redirect to login if session is invalid</small>";
echo "</div>";

// Test 4: Session check API
echo "<div class='test info'>";
echo "<h3>Test 4: Session Check API</h3>";
echo "<button onclick='checkSession()'>Check Session via API</button>";
echo "<div id='sessionResult'></div>";
echo "</div>";

// Test 5: Login test
echo "<div class='test info'>";
echo "<h3>Test 5: Login Test</h3>";
if (!isset($_SESSION['user_id'])) {
    echo "<button onclick='testLogin()'>Test Login</button><br>";
    echo "<small>This will redirect to login page</small>";
} else {
    echo "Already logged in";
}
echo "</div>";

// Test 6: Logout test
echo "<div class='test info'>";
echo "<h3>Test 6: Logout Test</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<button onclick='testLogout()'>Test Logout</button><br>";
    echo "<small>This will logout and test session destruction</small>";
} else {
    echo "Not logged in - cannot test logout";
}
echo "</div>";

// Test 7: Back button prevention test
echo "<div class='test info'>";
echo "<h3>Test 7: Back Button Prevention</h3>";
echo "<p>After logout, try using browser back button to return to this page.</p>";
echo "<p>Expected: Should be redirected to login page</p>";
echo "</div>";

// Test 8: Manual session destruction test
echo "<div class='test info'>";
echo "<h3>Test 8: Manual Session Destruction</h3>";
echo "<button onclick='destroySession()'>Destroy Session Manually</button>";
echo "<div id='destroyResult'></div>";
echo "</div>";

?>

<script>
function testLogin() {
    window.location.href = '/ergon/login';
}

function checkSession() {
    fetch('/ergon/api/check-session')
        .then(response => response.json())
        .then(data => {
            const result = document.getElementById('sessionResult');
            if (data.valid) {
                result.innerHTML = '<div class="test pass">✅ Session is valid</div>';
            } else {
                result.innerHTML = '<div class="test fail">❌ Session is invalid</div>';
            }
        })
        .catch(error => {
            document.getElementById('sessionResult').innerHTML = 
                '<div class="test fail">❌ Error checking session: ' + error + '</div>';
        });
}

function testLogout() {
    if (confirm('This will logout and test session destruction. Continue?')) {
        // Store current URL for back button test
        localStorage.setItem('preLogoutUrl', window.location.href);
        
        // Perform logout
        window.location.href = '/ergon/auth/logout';
    }
}

function destroySession() {
    fetch('/ergon/test-destroy-session', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('destroyResult').innerHTML = 
            '<div class="test info">' + data + '</div>';
        
        // Refresh page to see effect
        setTimeout(() => location.reload(), 2000);
    })
    .catch(error => {
        document.getElementById('destroyResult').innerHTML = 
            '<div class="test fail">Error: ' + error + '</div>';
    });
}

// Auto-check session every 10 seconds
setInterval(checkSession, 10000);

// Check if we came back from logout (back button test)
if (localStorage.getItem('preLogoutUrl') === window.location.href) {
    alert('⚠️ SECURITY ISSUE: Back button allowed access after logout!');
    localStorage.removeItem('preLogoutUrl');
}
</script>

<div class="test info">
    <h3>Instructions:</h3>
    <ol>
        <li>Run this test while logged in</li>
        <li>Click "Test Logout" to logout</li>
        <li>Try using browser back button to return</li>
        <li>Expected: Should be redirected to login page</li>
        <li>Try accessing protected pages directly</li>
        <li>Expected: Should be redirected to login page</li>
    </ol>
</div>

<?php
// Add test route for manual session destruction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/ergon/test-destroy-session') {
    session_unset();
    session_destroy();
    echo "Session destroyed manually";
    exit;
}
?>