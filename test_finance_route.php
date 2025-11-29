<?php
// Test finance route accessibility
echo "Testing finance route...\n";

// Test 1: Check if FinanceController exists
if (file_exists(__DIR__ . '/app/controllers/FinanceController.php')) {
    echo "✅ FinanceController.php exists\n";
} else {
    echo "❌ FinanceController.php missing\n";
}

// Test 2: Check if routes are configured
if (file_exists(__DIR__ . '/app/config/routes.php')) {
    $routes_content = file_get_contents(__DIR__ . '/app/config/routes.php');
    if (strpos($routes_content, "'/finance'") !== false) {
        echo "✅ Finance routes configured\n";
    } else {
        echo "❌ Finance routes not found\n";
    }
}

// Test 3: Check if dashboard view exists
if (file_exists(__DIR__ . '/views/finance/dashboard.php')) {
    echo "✅ Finance dashboard view exists\n";
} else {
    echo "❌ Finance dashboard view missing\n";
}

// Test 4: Test direct access
echo "\n🔗 Access URLs:\n";
echo "Direct: https://athenas.co.in/finance\n";
echo "With ergon: https://athenas.co.in/ergon/finance (this should work if ergon is the document root)\n";

// Test 5: Check current working directory
echo "\n📁 Current directory: " . __DIR__ . "\n";
echo "Document root should be: /path/to/ergon/\n";
?>