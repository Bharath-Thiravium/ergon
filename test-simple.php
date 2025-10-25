<?php
// Simple test to check basic functionality
echo "<!DOCTYPE html><html><head><title>ERGON Test</title></head><body>";
echo "<h1>ERGON System Test</h1>";

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test session
try {
    session_start();
    echo "<p>✅ Session start: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Session start: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test core classes
try {
    require_once __DIR__ . '/app/core/Router.php';
    require_once __DIR__ . '/app/core/Controller.php';
    echo "<p>✅ Core classes: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Core classes: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test helpers
try {
    require_once __DIR__ . '/app/helpers/Security.php';
    require_once __DIR__ . '/app/helpers/SessionManager.php';
    echo "<p>✅ Helper classes: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Helper classes: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='/ergon/'>Go to Main Site</a></p>";
echo "</body></html>";
?>