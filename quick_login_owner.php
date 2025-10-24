<?php
session_start();

// Quick login for owner
$_SESSION['user_id'] = 1; // Assuming owner has ID 1
$_SESSION['role'] = 'owner';
$_SESSION['user_name'] = 'Owner';
$_SESSION['last_activity'] = time();

echo "<h2>Quick Owner Login</h2>";
echo "<p>✅ Logged in as Owner</p>";
echo "<p><a href='/ergon/admin/management'>Go to Admin Management</a></p>";
echo "<p><a href='/ergon/owner/dashboard'>Go to Owner Dashboard</a></p>";

echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>