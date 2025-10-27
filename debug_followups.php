<?php
// Debug Follow-ups System
session_start();

// Set a test user session if none exists
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = 'Test User';
}

require_once __DIR__ . '/app/models/Followup.php';

try {
    $followupModel = new Followup();
    $date = date('Y-m-d');
    
    $data = [
        'followups' => $followupModel->getByUser($_SESSION['user_id'], $date),
        'upcoming' => $followupModel->getUpcoming($_SESSION['user_id']),
        'overdue' => $followupModel->getOverdue($_SESSION['user_id']),
        'selectedDate' => $date,
        'active_page' => 'followups'
    ];
    
    $title = 'Follow-ups';
    
    ob_start();
    include __DIR__ . '/views/followups/index.php';
    $content = ob_get_clean();
    include __DIR__ . '/views/layouts/dashboard.php';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>