<?php
/**
 * Temporary Error Display - DELETE AFTER DEBUGGING
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Debug</title></head><body><pre>";

try {
    echo "1. Session config...\n";
    require_once __DIR__ . '/app/config/session.php';
    if (session_status() === PHP_SESSION_NONE) session_start();
    echo "   ✓\n";

    echo "2. Timezone...\n";
    date_default_timezone_set('Asia/Kolkata');
    echo "   ✓\n";

    echo "3. Environment...\n";
    require_once __DIR__ . '/app/config/environment.php';
    echo "   ✓ " . Environment::detect() . "\n";

    echo "4. Database...\n";
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "   ✓ connected\n";

    echo "5. ENV values read:\n";
    echo "   DB_NAME=" . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
    echo "   DB_USER=" . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";

    echo "6. Core files...\n";
    require_once __DIR__ . '/app/core/Controller.php';
    require_once __DIR__ . '/app/core/Session.php';
    require_once __DIR__ . '/app/core/Router.php';
    echo "   ✓\n";

    echo "7. Constants...\n";
    require_once __DIR__ . '/app/config/constants.php';
    echo "   ✓\n";

    echo "8. DatabaseHelper...\n";
    require_once __DIR__ . '/app/helpers/DatabaseHelper.php';
    echo "   ✓\n";

    echo "9. User model...\n";
    require_once __DIR__ . '/app/models/User.php';
    $u = new User();
    echo "   ✓\n";

    echo "10. SecurityService...\n";
    require_once __DIR__ . '/app/services/SecurityService.php';
    $s = new SecurityService();
    echo "   ✓\n";

    echo "11. AuthController...\n";
    require_once __DIR__ . '/app/controllers/AuthController.php';
    echo "   ✓\n";

    echo "12. Routes...\n";
    $router = new Router();
    require_once __DIR__ . '/app/config/routes.php';
    echo "   ✓\n";

    echo "\n✅ All bootstrap steps passed. Calling handleRequest()...\n";
    echo "</pre>";
    $router->handleRequest();

} catch (Throwable $e) {
    echo "\n❌ FAILED:\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "File    : " . $e->getFile() . "\n";
    echo "Line    : " . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre></body></html>";
}
?>
