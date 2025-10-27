<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $followups = $db->query("SELECT * FROM followups WHERE user_id = 1")->fetchAll();
    
    echo "<h1>Follow-ups</h1>";
    echo "<p>Found " . count($followups) . " follow-ups</p>";
    
    if (empty($followups)) {
        echo "<p>No follow-ups yet. <a href='#' onclick='createSample()'>Create sample</a></p>";
    }
    
    foreach ($followups as $f) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
        echo "<h3>" . htmlspecialchars($f['title']) . "</h3>";
        echo "<p>Company: " . htmlspecialchars($f['company_name']) . "</p>";
        echo "<p>Date: " . $f['follow_up_date'] . "</p>";
        echo "<p>Status: " . $f['status'] . "</p>";
        echo "</div>";
    }
    
    if ($_POST['action'] ?? '' === 'create') {
        $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, contact_person, follow_up_date, original_date) VALUES (1, ?, ?, ?, ?, ?)");
        $date = date('Y-m-d', strtotime('+1 day'));
        $stmt->execute(['Sample Follow-up', 'ABC Company', 'John Doe', $date, $date]);
        header('Location: simple_followups.php');
        exit;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<script>
function createSample() {
    var form = document.createElement('form');
    form.method = 'POST';
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'action';
    input.value = 'create';
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>