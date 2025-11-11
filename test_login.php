<?php
session_start();

// Simple test login - sets up a valid session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['role'] = 'user';
$_SESSION['last_activity'] = time();

echo "<h2>Test Login Successful!</h2>";
echo "<p>Session has been set up for testing:</p>";
echo "<ul>";
echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
echo "<li>Username: " . $_SESSION['username'] . "</li>";
echo "<li>Role: " . $_SESSION['role'] . "</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='/ergon/add_test_task.php'>Add some test tasks</a></li>";
echo "<li><a href='/ergon/daily-workflow/morning-planner?debug=1'>View Morning Planner (Debug)</a></li>";
echo "<li><a href='/ergon/daily-workflow/morning-planner'>View Morning Planner (Normal)</a></li>";
echo "</ol>";

echo "<p><strong>Note:</strong> This is for testing only. In production, use the proper login system.</p>";
?>