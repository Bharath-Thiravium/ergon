<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $db = Database::connect();
    
    echo "<h2>Debug Specific Follow-up Issue</h2>";
    echo "<p>Current session user_id: " . $_SESSION['user_id'] . "</p>";
    
    // Check the specific follow-up record
    echo "<h3>Follow-up ID 21 Details:</h3>";
    $stmt = $db->prepare("SELECT * FROM followups WHERE id = 21");
    $stmt->execute();
    $followup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($followup) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($followup as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? '') . "</td></tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Issue:</strong> Follow-up user_id = {$followup['user_id']}, Session user_id = {$_SESSION['user_id']}</p>";
        
        if ($followup['user_id'] != $_SESSION['user_id']) {
            echo "<p style='color: red;'>❌ User ID mismatch! This follow-up belongs to user {$followup['user_id']}, but you are logged in as user {$_SESSION['user_id']}</p>";
        } else {
            echo "<p style='color: green;'>✅ User ID matches</p>";
        }
    } else {
        echo "<p>Follow-up ID 21 not found</p>";
    }
    
    // Check all follow-ups for current user
    echo "<h3>All Follow-ups for Current User ({$_SESSION['user_id']}):</h3>";
    $stmt = $db->prepare("SELECT id, user_id, title, company_name, follow_up_date, status FROM followups WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userFollowups)) {
        echo "<p>No follow-ups found for current user</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Company</th><th>Date</th><th>Status</th></tr>";
        foreach ($userFollowups as $f) {
            echo "<tr>";
            echo "<td>{$f['id']}</td>";
            echo "<td>{$f['user_id']}</td>";
            echo "<td>" . htmlspecialchars($f['title']) . "</td>";
            echo "<td>" . htmlspecialchars($f['company_name'] ?? '') . "</td>";
            echo "<td>{$f['follow_up_date']}</td>";
            echo "<td>{$f['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check who user 16 is
    echo "<h3>User 16 Details:</h3>";
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = 16");
    $stmt->execute();
    $user16 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user16) {
        echo "<p>User 16: " . htmlspecialchars($user16['name']) . " (" . htmlspecialchars($user16['email']) . ") - Role: " . htmlspecialchars($user16['role']) . "</p>";
    } else {
        echo "<p>User 16 not found</p>";
    }
    
    // Check current user details
    echo "<h3>Current User Details:</h3>";
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentUser) {
        echo "<p>Current User: " . htmlspecialchars($currentUser['name']) . " (" . htmlspecialchars($currentUser['email']) . ") - Role: " . htmlspecialchars($currentUser['role']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<h3>Solutions:</h3>
<p>If you need to see follow-up ID 21:</p>
<ol>
    <li><strong>Login as user 16</strong> - The follow-up belongs to that user</li>
    <li><strong>Transfer ownership</strong> - Update the follow-up to your user ID</li>
    <li><strong>Admin view</strong> - Modify the controller to show all follow-ups for admins</li>
</ol>

<form method="POST">
    <button type="submit" name="transfer_to_current" value="1">Transfer Follow-up 21 to Current User</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_to_current'])) {
    try {
        $stmt = $db->prepare("UPDATE followups SET user_id = ? WHERE id = 21");
        $result = $stmt->execute([$_SESSION['user_id']]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Follow-up 21 transferred to current user</p>";
            echo "<p><a href='/ergon/followups'>View Follow-ups</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to transfer follow-up</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<p><a href="/ergon/followups">Back to Follow-ups</a></p>