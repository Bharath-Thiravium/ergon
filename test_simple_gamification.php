<?php
// Simple test for gamification routes
session_start();

// Mock session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';
$_SESSION['user_name'] = 'Test User';

echo "<h2>Testing Gamification Routes</h2>";

// Test individual route
echo "<h3>Testing Individual Route</h3>";
echo "<a href='/ergon/gamification/individual' target='_blank'>Test Individual Dashboard</a><br><br>";

// Test team competition route  
echo "<h3>Testing Team Competition Route</h3>";
echo "<a href='/ergon/gamification/team-competition' target='_blank'>Test Team Competition Dashboard</a><br><br>";

echo "<h3>Expected Behavior:</h3>";
echo "<ul>";
echo "<li>Both links should load without 500 errors</li>";
echo "<li>If database tables are missing, should show 'being set up' message</li>";
echo "<li>If database tables exist, should show actual data</li>";
echo "</ul>";

echo "<h3>Navigation Links:</h3>";
echo "<ul>";
echo "<li>Individual: Overview → My Performance (for users)</li>";
echo "<li>Team Competition: Overview → Competition (for all roles)</li>";
echo "</ul>";
?>