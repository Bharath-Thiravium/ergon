<?php
/**
 * Test Manual Attendance Route
 */

echo "<h1>🧪 Test Manual Attendance Route</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test 1: Check if route exists in routes.php
$routesContent = file_get_contents(__DIR__ . '/app/config/routes.php');
$hasManualRoute = strpos($routesContent, "'/attendance/manual'") !== false;

echo "<h2>1. Route Configuration Test</h2>";
echo "<span class='" . ($hasManualRoute ? 'success' : 'error') . "'>";
echo ($hasManualRoute ? '✅' : '❌') . " Manual route in routes.php: " . ($hasManualRoute ? 'EXISTS' : 'MISSING');
echo "</span><br>";

// Test 2: Check if AttendanceController has manual method
$controllerContent = file_get_contents(__DIR__ . '/app/controllers/AttendanceController.php');
$hasManualMethod = strpos($controllerContent, 'function manual(') !== false;

echo "<h2>2. Controller Method Test</h2>";
echo "<span class='" . ($hasManualMethod ? 'success' : 'error') . "'>";
echo ($hasManualMethod ? '✅' : '❌') . " Manual method in AttendanceController: " . ($hasManualMethod ? 'EXISTS' : 'MISSING');
echo "</span><br>";

// Test 3: Test the actual route with a simple POST request
echo "<h2>3. Route Access Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_manual'])) {
    echo "<span class='info'>📡 Testing POST request to /ergon/attendance/manual...</span><br>";
    
    // Simulate the manual attendance request
    $_POST['user_id'] = 1;
    $_POST['check_in'] = '09:00';
    $_POST['check_out'] = '';
    $_POST['date'] = date('Y-m-d');
    
    // Start output buffering to capture any output
    ob_start();
    
    try {
        // Include the router and test
        require_once __DIR__ . '/app/core/Router.php';
        require_once __DIR__ . '/app/core/Controller.php';
        
        $router = new Router();
        require_once __DIR__ . '/app/config/routes.php';
        
        // Simulate the request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/ergon/attendance/manual';
        
        $router->handleRequest();
        
        $output = ob_get_clean();
        echo "<span class='success'>✅ Route executed successfully</span><br>";
        echo "<div style='background:#f8f9fa;padding:10px;border-radius:4px;margin:10px 0;'>";
        echo "<strong>Response:</strong><br>" . htmlspecialchars($output);
        echo "</div>";
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "<span class='error'>❌ Route test failed: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_manual' value='1'>";
    echo "<button type='submit' style='background:#007bff;color:white;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;'>Test Manual Route</button>";
    echo "</form>";
}

// Test 4: JavaScript fetch test
echo "<h2>4. JavaScript Fetch Test</h2>";
echo "<button onclick='testManualFetch()' style='background:#28a745;color:white;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;'>Test JavaScript Fetch</button>";
echo "<div id='fetchResult' style='margin-top:10px;'></div>";

echo "<script>
function testManualFetch() {
    const resultDiv = document.getElementById('fetchResult');
    resultDiv.innerHTML = '<span style=\"color:blue;\">🔄 Testing fetch request...</span>';
    
    const formData = new FormData();
    formData.append('user_id', 1);
    formData.append('check_in', '09:00');
    formData.append('check_out', '');
    formData.append('date', '" . date('Y-m-d') . "');
    
    fetch('/ergon/attendance/manual', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: \${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text();
        }
    })
    .then(data => {
        console.log('Response data:', data);
        if (typeof data === 'object') {
            resultDiv.innerHTML = '<span style=\"color:green;\">✅ Success: ' + JSON.stringify(data) + '</span>';
        } else {
            resultDiv.innerHTML = '<span style=\"color:orange;\">⚠️ Non-JSON response: ' + data.substring(0, 200) + '</span>';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultDiv.innerHTML = '<span style=\"color:red;\">❌ Error: ' + error.message + '</span>';
    });
}
</script>";

echo "<h2>5. Quick Fixes</h2>";
echo "<p>If the route is not working:</p>";
echo "<ol>";
echo "<li>Ensure Apache mod_rewrite is enabled</li>";
echo "<li>Check .htaccess file exists and is readable</li>";
echo "<li>Verify AttendanceController.php has the manual() method</li>";
echo "<li>Check error logs for detailed error messages</li>";
echo "</ol>";

echo "<h3>Test URLs:</h3>";
echo "<ul>";
echo "<li><a href='/ergon/attendance' target='_blank'>Main Attendance Page</a></li>";
echo "<li><a href='/ergon/attendance/clock' target='_blank'>Clock In/Out Page</a></li>";
echo "</ul>";
?>