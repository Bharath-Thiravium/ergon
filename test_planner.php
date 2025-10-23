<?php
// Simple test to check if planner controller works
session_start();

// Mock session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
$_SESSION['user_name'] = 'Test User';

require_once 'app/controllers/PlannerController.php';

try {
    $controller = new PlannerController();
    echo "PlannerController loaded successfully!\n";
    
    // Test calendar method
    ob_start();
    $controller->calendar();
    $output = ob_get_clean();
    
    if (strpos($output, 'Daily Planner Calendar') !== false) {
        echo "Calendar view working!\n";
    } else {
        echo "Calendar view has issues\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>