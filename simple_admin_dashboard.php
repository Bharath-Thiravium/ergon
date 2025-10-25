<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ergon/login');
    exit;
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin Dashboard - ERGON</title></head>";
echo "<body>";
echo "<h1>Admin Dashboard</h1>";
echo "<p>Welcome, " . htmlspecialchars($_SESSION['user_name'] ?? 'Admin') . "</p>";
echo "<p>Role: " . htmlspecialchars($_SESSION['role']) . "</p>";
echo "<p>User ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='/ergon/users'>Manage Users</a></li>";
echo "<li><a href='/ergon/tasks'>Manage Tasks</a></li>";
echo "<li><a href='/ergon/leaves'>Review Leaves</a></li>";
echo "<li><a href='/ergon/expenses'>Review Expenses</a></li>";
echo "<li><a href='/ergon/logout'>Logout</a></li>";
echo "</ul>";
echo "</body></html>";
?>