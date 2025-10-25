<?php
/**
 * Apply Security Updates to All Controllers
 * This script adds CSRF protection and secure session management to all controllers
 */

$controllersDir = __DIR__ . '/app/controllers/';
$controllers = glob($controllersDir . '*.php');

$updatedControllers = [];
$skippedControllers = [];

foreach ($controllers as $controllerFile) {
    $filename = basename($controllerFile);
    
    // Skip already updated controllers
    if (in_array($filename, ['TasksController.php', 'AttendanceController.php', 'AuthController.php', 'ExpenseController.php'])) {
        $skippedControllers[] = $filename . ' (already updated)';
        continue;
    }
    
    $content = file_get_contents($controllerFile);
    $originalContent = $content;
    
    // Check if already has Security helper
    if (strpos($content, "require_once __DIR__ . '/../helpers/Security.php';") !== false) {
        $skippedControllers[] = $filename . ' (already has Security helper)';
        continue;
    }
    
    // Add Security and SessionManager includes after the first require_once
    $pattern = '/^(<\?php\s*(?:\/\*.*?\*\/\s*)?(?:require_once[^;]+;))/ms';
    if (preg_match($pattern, $content, $matches)) {
        $replacement = $matches[1] . "\nrequire_once __DIR__ . '/../helpers/Security.php';\nrequire_once __DIR__ . '/../helpers/SessionManager.php';";
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Add SessionManager::start() to constructor
    $content = preg_replace(
        '/public function __construct\(\) \{/',
        "public function __construct() {\n        SessionManager::start();",
        $content
    );
    
    // Add SessionManager::requireLogin() to public methods that don't have session checks
    $content = preg_replace_callback(
        '/public function (\w+)\([^)]*\) \{([^}]*(?:\{[^}]*\}[^}]*)*)\}/s',
        function($matches) {
            $methodName = $matches[1];
            $methodBody = $matches[2];
            
            // Skip certain methods
            if (in_array($methodName, ['__construct', 'login', 'logout', 'showLogin'])) {
                return $matches[0];
            }
            
            // Skip if already has session management
            if (strpos($methodBody, 'SessionManager::') !== false || 
                strpos($methodBody, 'session_start') !== false ||
                strpos($methodBody, '$_SESSION') !== false) {
                return $matches[0];
            }
            
            return "public function {$methodName}({$matches[1]}) {\n        SessionManager::requireLogin();\n        {$methodBody}}";
        },
        $content
    );
    
    // Add CSRF validation to POST methods
    $content = preg_replace_callback(
        '/if \(\$_SERVER\[\'REQUEST_METHOD\'\] === \'POST\'\) \{/',
        function($matches) {
            return $matches[0] . "\n            // Validate CSRF token\n            if (!Security::validateCSRFToken(\$_POST['csrf_token'] ?? '')) {\n                http_response_code(403);\n                die('CSRF validation failed');\n            }\n";
        },
        $content
    );
    
    // Sanitize POST inputs
    $content = preg_replace(
        '/\$_POST\[\'(\w+)\'\](?!\s*\?\?)/',
        "Security::sanitizeString(\$_POST['$1'])",
        $content
    );
    
    // Only write if content changed
    if ($content !== $originalContent) {
        file_put_contents($controllerFile, $content);
        $updatedControllers[] = $filename;
    } else {
        $skippedControllers[] = $filename . ' (no changes needed)';
    }
}

echo "Security Updates Applied\n";
echo "=======================\n\n";

echo "Updated Controllers (" . count($updatedControllers) . "):\n";
foreach ($updatedControllers as $controller) {
    echo "✅ " . $controller . "\n";
}

echo "\nSkipped Controllers (" . count($skippedControllers) . "):\n";
foreach ($skippedControllers as $controller) {
    echo "⏭️ " . $controller . "\n";
}

echo "\n✅ Security updates completed!\n";
echo "\nNext steps:\n";
echo "1. Add CSRF tokens to remaining forms\n";
echo "2. Test all forms and AJAX requests\n";
echo "3. Update any custom JavaScript to include CSRF tokens\n";
?>