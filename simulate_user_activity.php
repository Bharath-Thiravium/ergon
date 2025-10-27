<?php
/**
 * User Activity Simulator
 * Simulates real user interactions with gamification system
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Gamification.php';

echo "<h1>🎭 User Activity Simulator</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.activity { background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 3px solid #007cba; }
.success { color: #28a745; }
.info { color: #007cba; }
.warning { color: #ffc107; }
</style>";

$db = Database::connect();
$gamification = new Gamification();

// Simulate Alice completing a task
echo "<div class='activity'>";
echo "<h3>👩‍💻 Alice Johnson - Completing Urgent Task</h3>";

// Get Alice's user ID first
$stmt = $db->prepare("SELECT id FROM users WHERE employee_id = 'EMP002'");
$stmt->execute();
$aliceId = $stmt->fetchColumn();

if (!$aliceId) {
    echo "<p class='error'>❌ Alice not found - run dummy_data.sql first</p>";
    exit;
}

// Add new task for Alice
$stmt = $db->prepare("
    INSERT INTO daily_plans (user_id, plan_date, title, description, priority, estimated_hours, status, progress, actual_hours) 
    VALUES (?, CURDATE(), 'Security Patch Implementation', 'Apply critical security patches to production servers', 'urgent', 2.0, 'completed', 100, 2.5)
");
$stmt->execute([$aliceId]);
$taskId = $db->lastInsertId();

echo "<p class='info'>📋 New urgent task created: Security Patch Implementation</p>";

// Get Alice's actual user ID
$stmt = $db->prepare("SELECT id FROM users WHERE employee_id = 'EMP002'");
$stmt->execute();
$aliceId = $stmt->fetchColumn();

if ($aliceId) {
    // Award points for urgent task completion
    $points = 15; // Urgent task points
    $gamification->addPoints($aliceId, $points, 'Urgent task completed', 'task', $taskId);
    
    echo "<p class='success'>💎 +$points points awarded for urgent task completion</p>";
    
    // Check Alice's total points
    $alicePoints = $gamification->getTotalPoints($aliceId);
    echo "<p class='info'>🏆 Alice's total points: $alicePoints</p>";
} else {
    echo "<p class='error'>❌ Alice not found in database</p>";
}

// Check Alice's current badges
$badges = $gamification->getUserBadges($aliceId);
echo "<p class='info'>🎖️ Alice's badges: " . count($badges) . " earned</p>";

echo "</div>";

// Simulate Bob's productivity milestone
echo "<div class='activity'>";
echo "<h3>👨‍💼 Bob Smith - Achieving Productivity Milestone</h3>";

// Get Bob's user ID
$stmt = $db->prepare("SELECT id FROM users WHERE employee_id = 'EMP003'");
$stmt->execute();
$bobId = $stmt->fetchColumn();

if ($bobId) {
    // Update Bob's productivity score
    $stmt = $db->prepare("
        UPDATE daily_workflow_status 
        SET productivity_score = 95.0, total_completed_tasks = 1, total_actual_hours = 2.8 
        WHERE user_id = ? AND workflow_date = CURDATE()
    ");
    $stmt->execute([$bobId]);
    
    echo "<p class='success'>📈 Bob achieved 95% productivity score today</p>";
    
    // Check if Bob qualifies for Productivity Pro badge
    $stmt = $db->prepare("SELECT AVG(productivity_score) as avg_score FROM daily_workflow_status WHERE user_id = ?");
    $stmt->execute([$bobId]);
    $avgScore = $stmt->fetchColumn() ?? 0;
    
    if ($avgScore >= 90) {
        // Award Productivity Pro badge if not already earned
        $stmt = $db->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 3");
        $stmt->execute([$bobId]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 3)");
            $stmt->execute([$bobId]);
            echo "<p class='success'>🚀 Bob earned 'Productivity Pro' badge!</p>";
        }
    }
    
    echo "<p class='info'>📊 Bob's average productivity: " . round($avgScore, 1) . "%</p>";
} else {
    echo "<p class='error'>❌ Bob not found in database</p>";
}

echo "</div>";

// Simulate Carol's streak achievement
echo "<div class='activity'>";
echo "<h3>👩‍🎨 Carol Davis - Building Task Completion Streak</h3>";

// Add completed tasks for Carol over multiple days
$dates = [
    DATE('Y-m-d'),
    DATE('Y-m-d', strtotime('-1 day')),
    DATE('Y-m-d', strtotime('-2 days')),
    DATE('Y-m-d', strtotime('-3 days')),
    DATE('Y-m-d', strtotime('-4 days'))
];

// Get Carol's user ID
$stmt = $db->prepare("SELECT id FROM users WHERE employee_id = 'EMP004'");
$stmt->execute();
$carolId = $stmt->fetchColumn();

if ($carolId) {
    foreach ($dates as $index => $date) {
        $stmt = $db->prepare("
            INSERT IGNORE INTO daily_plans (user_id, plan_date, title, description, priority, estimated_hours, status, progress, actual_hours) 
            VALUES (?, ?, ?, 'Daily marketing task completion', 'medium', 2.0, 'completed', 100, 2.0)
        ");
        $taskTitle = "Marketing Task Day " . ($index + 1);
        $stmt->execute([$carolId, $date, $taskTitle]);
        
        // Award points for each completed task
        if ($stmt->rowCount() > 0) {
            $gamification->addPoints($carolId, 5, 'Daily task completed', 'task', $db->lastInsertId());
        }
    }
    
    echo "<p class='success'>🔥 Carol completed tasks for 5 consecutive days</p>";
    
    // Check if Carol earned streak badge
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 5");
    $stmt->execute([$carolId]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $db->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 5)");
        $stmt->execute([$carolId]);
        echo "<p class='success'>🔥 Carol earned 'Consistent Performer' badge!</p>";
    }
    
    // Update Carol's total points
    $carolPoints = $gamification->getTotalPoints($carolId);
    echo "<p class='info'>💎 Carol's total points: $carolPoints</p>";
} else {
    echo "<p class='error'>❌ Carol not found in database</p>";
}

echo "</div>";

// Show updated leaderboard
echo "<div class='activity'>";
echo "<h3>🏅 Updated Leaderboard</h3>";

$leaderboard = $gamification->getLeaderboard(5);

echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f2f2f2;'><th style='border: 1px solid #ddd; padding: 8px;'>Rank</th><th style='border: 1px solid #ddd; padding: 8px;'>Name</th><th style='border: 1px solid #ddd; padding: 8px;'>Points</th><th style='border: 1px solid #ddd; padding: 8px;'>Badges</th></tr>";

$rank = 1;
foreach ($leaderboard as $leader) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_badges ub JOIN users u ON ub.user_id = u.id WHERE u.name = ?");
    $stmt->execute([$leader['name']]);
    $badgeCount = $stmt->fetchColumn();
    
    $rankIcon = match($rank) {
        1 => '🥇',
        2 => '🥈',
        3 => '🥉',
        default => "#$rank"
    };
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'><strong>$rankIcon</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$leader['name']}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'><strong>{$leader['total_points']}</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>$badgeCount</td>";
    echo "</tr>";
    
    $rank++;
}
echo "</table>";

echo "</div>";

// Show badge achievements summary
echo "<div class='activity'>";
echo "<h3>🎖️ Recent Badge Achievements</h3>";

$stmt = $db->query("
    SELECT u.name, bd.icon, bd.name as badge_name, ub.awarded_on
    FROM user_badges ub
    JOIN users u ON ub.user_id = u.id
    JOIN badge_definitions bd ON ub.badge_id = bd.id
    WHERE ub.awarded_on >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY ub.awarded_on DESC
");
$recentBadges = $stmt->fetchAll();

if (empty($recentBadges)) {
    echo "<p class='warning'>⚠️ No recent badge achievements</p>";
} else {
    foreach ($recentBadges as $badge) {
        echo "<p class='success'>{$badge['icon']} <strong>{$badge['name']}</strong> earned '{$badge['badge_name']}' badge!</p>";
    }
}

echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>🎯 Simulation Complete!</h3>";
echo "<p style='color: #155724; margin-bottom: 0;'>The gamification system is actively engaging users through:</p>";
echo "<ul style='color: #155724;'>";
echo "<li>✅ Automatic point awards for task completion</li>";
echo "<li>✅ Badge achievements for milestones</li>";
echo "<li>✅ Dynamic leaderboard updates</li>";
echo "<li>✅ Productivity score tracking</li>";
echo "<li>✅ Streak recognition system</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px; color: #666;'>";
echo "🎭 <strong>User Activity Simulation Complete</strong> - " . date('Y-m-d H:i:s');
echo "</p>";
?>