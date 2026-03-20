<?php
/**
 * Temporary Error Display - DELETE AFTER DEBUGGING
 * This bypasses production error suppression to show the actual error
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Ergon Bootstrap...</h1>";
echo "<pre>";

try {
    echo "1. Loading session config...\n";
    require_once __DIR__ . '/app/config/session.php';
    echo "   ✓ Session config loaded\n\n";

    echo "2. Starting session...\n";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "   ✓ Session started: " . session_id() . "\n\n";

    echo "3. Setting timezone...\n";
    date_default_timezone_set('Asia/Kolkata');
    echo "   ✓ Timezone set\n\n";

    echo "4. Loading environment config...\n";
    require_once __DIR__ . '/app/config/environment.php';
    echo "   ✓ Environment: " . Environment::detect() . "\n\n";

    echo "5. Loading database config...\n";
    require_once __DIR__ . '/app/config/database.php';
    echo "   ✓ Database class loaded\n\n";

    echo "6. Testing database connection...\n";
    $db = Database::connect();
    echo "   ✓ Database connected\n\n";

    echo "7. Loading Router...\n";
    require_once __DIR__ . '/app/core/Router.php';
    echo "   ✓ Router loaded\n\n";

    echo "8. Loading Controller...\n";
    require_once __DIR__ . '/app/core/Controller.php';
    echo "   ✓ Controller loaded\n\n";

    echo "9. Initializing Router...\n";
    $router = new Router();
    echo "   ✓ Router initialized\n\n";

    echo "10. Loading routes...\n";
    require_once __DIR__ . '/app/config/routes.php';
    echo "   ✓ Routes loaded\n\n";

    echo "11. Handling request...\n";
    echo "   Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    echo "   Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n\n";

    $router->handleRequest();

    echo "\n✅ All steps completed successfully!\n";

} catch (Throwable $e) {
    echo "\n❌ ERROR at step:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
