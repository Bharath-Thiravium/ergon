<?php
/**
 * Quick Gamification Test - Minimal Version
 * Tests if gamification system is working
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🎮 Quick Gamification Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    echo "<p class='ok'>✅ Database connected</p>";
    
    // Test 1: Check tables exist
    $tables = ['users', 'user_points', 'badge_definitions', 'user_badges', 'daily_plans'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='ok'>✅ Table '$table' exists</p>";
        } else {
            echo "<p class='error'>❌ Table '$table' missing</p>";
        }
    }
    
    // Test 2: Check test users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE employee_id IN ('EMP002', 'EMP003', 'EMP004')");
    $userCount = $stmt->fetchColumn();
    echo "<p class='info'>👥 Test users found: $userCount</p>";
    
    // Test 3: Check points
    $stmt = $db->query("SELECT COUNT(*) FROM user_points");
    $pointCount = $stmt->fetchColumn();
    echo "<p class='info'>💎 Point transactions: $pointCount</p>";
    
    // Test 4: Check badges
    $stmt = $db->query("SELECT COUNT(*) FROM user_badges");
    $badgeCount = $stmt->fetchColumn();
    echo "<p class='info'>🏆 Badges awarded: $badgeCount</p>";
    
    // Test 5: Show leaderboard
    echo "<h3>🏅 Current Leaderboard:</h3>";
    $stmt = $db->query("SELECT name, total_points FROM users WHERE role = 'user' ORDER BY total_points DESC LIMIT 5");
    $leaders = $stmt->fetchAll();
    
    if (empty($leaders)) {
        echo "<p class='error'>❌ No users with points found - run dummy_data.sql</p>";
    } else {
        echo "<ol>";
        foreach ($leaders as $leader) {
            echo "<li>{$leader['name']}: {$leader['total_points']} points</li>";
        }
        echo "</ol>";
    }
    
    echo "<hr>";
    if ($userCount >= 3 && $pointCount > 0 && $badgeCount > 0) {
        echo "<h2 class='ok'>🎯 Gamification System: WORKING!</h2>";
        echo "<p>✅ Ready to test full system:</p>";
        echo "<ul>";
        echo "<li><a href='test_gamification.php'>Full System Test</a></li>";
        echo "<li><a href='simulate_user_activity.php'>Activity Simulation</a></li>";
        echo "<li><a href='login'>Login to ERGON</a></li>";
        echo "</ul>";
    } else {
        echo "<h2 class='error'>⚠️ Setup Incomplete</h2>";
        echo "<p>Please run the database setup files in phpMyAdmin:</p>";
        echo "<ol>";
        echo "<li>database/schema.sql</li>";
        echo "<li>database/daily_workflow_schema.sql</li>";
        echo "<li>database/gamification_schema.sql</li>";
        echo "<li>database/dummy_data.sql</li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>