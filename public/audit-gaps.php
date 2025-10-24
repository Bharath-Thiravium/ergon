<?php
header('Content-Type: text/plain');
echo "=== ERGON LOCALHOST vs HOSTINGER GAP AUDIT ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Environment: " . (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ? 'LOCALHOST' : 'HOSTINGER') . "\n\n";

// File checksums and sizes
$criticalFiles = [
    'CSS Files' => [
        'public/assets/css/ergon.css',
        'public/assets/css/sidebar-scroll.css',
        'public/assets/css/components.css'
    ],
    'JavaScript Files' => [
        'public/assets/js/sidebar-scroll.js'
    ],
    'Layout Files' => [
        'app/views/layouts/dashboard.php'
    ],
    'View Files' => [
        'app/views/owner/dashboard.php',
        'app/views/attendance/index.php',
        'app/views/settings/index.php',
        'app/views/reports/index.php'
    ]
];

echo "=== FILE INTEGRITY CHECK ===\n";
foreach ($criticalFiles as $category => $files) {
    echo "\n[$category]\n";
    foreach ($files as $file) {
        $fullPath = __DIR__ . '/../' . $file;
        if (file_exists($fullPath)) {
            $size = filesize($fullPath);
            $modified = date('Y-m-d H:i:s', filemtime($fullPath));
            $hash = substr(md5_file($fullPath), 0, 8);
            echo "✓ $file | Size: {$size}b | Modified: $modified | Hash: $hash\n";
        } else {
            echo "✗ $file | MISSING\n";
        }
    }
}

// CSS Feature Detection
echo "\n=== CSS FEATURE DETECTION ===\n";
$cssFile = __DIR__ . '/assets/css/ergon.css';
if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    $features = [
        'Sidebar Scroll Fix' => 'will-change: scroll-position',
        'Smooth Scrolling' => 'scroll-behavior: smooth',
        'Stable Layout' => 'transform: none',
        'Account Hiding' => 'Hide Account section',
        'Table Styles' => 'table-responsive',
        'Scrollable Cards' => 'card__body--scrollable',
        'Alert Components' => 'alert--success'
    ];
    
    foreach ($features as $feature => $needle) {
        $status = strpos($css, $needle) !== false ? '✓ PRESENT' : '✗ MISSING';
        echo "$feature: $status\n";
    }
} else {
    echo "✗ Main CSS file missing\n";
}

// JavaScript Feature Detection
echo "\n=== JAVASCRIPT FEATURE DETECTION ===\n";
$jsFile = __DIR__ . '/assets/js/sidebar-scroll.js';
if (file_exists($jsFile)) {
    $js = file_get_contents($jsFile);
    $jsFeatures = [
        'Smooth Scroll Polyfill' => 'smoothScrollTo',
        'Active Item Detection' => 'scrollActiveIntoView',
        'Focus Prevention' => 'preventDefault',
        'Keyboard Navigation' => 'ArrowUp'
    ];
    
    foreach ($jsFeatures as $feature => $needle) {
        $status = strpos($js, $needle) !== false ? '✓ PRESENT' : '✗ MISSING';
        echo "$feature: $status\n";
    }
} else {
    echo "✗ Sidebar JS file missing\n";
}

// Layout Template Check
echo "\n=== LAYOUT TEMPLATE CHECK ===\n";
$layoutFile = __DIR__ . '/../app/views/layouts/dashboard.php';
if (file_exists($layoutFile)) {
    $layout = file_get_contents($layoutFile);
    $layoutFeatures = [
        'Account Section Removed' => 'Account</div>',
        'My Profile Link Removed' => 'My Profile',
        'Sidebar Scroll JS Included' => 'sidebar-scroll.js',
        'Navigation Role Added' => 'role="navigation"',
        'Aria Label Added' => 'aria-label',
        'CSS Cache Version' => 'ergon.css?v='
    ];
    
    foreach ($layoutFeatures as $feature => $needle) {
        $present = strpos($layout, $needle) !== false;
        if ($feature === 'Account Section Removed' || $feature === 'My Profile Link Removed') {
            $status = $present ? '✗ STILL PRESENT' : '✓ REMOVED';
        } else {
            $status = $present ? '✓ PRESENT' : '✗ MISSING';
        }
        echo "$feature: $status\n";
    }
    
    // Extract CSS version
    if (preg_match('/ergon\.css\?v=([^"]+)/', $layout, $matches)) {
        echo "CSS Version: {$matches[1]}\n";
    }
} else {
    echo "✗ Dashboard layout missing\n";
}

// Session & Environment Check
echo "\n=== ENVIRONMENT CHECK ===\n";
session_start();
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Path: " . __DIR__ . "\n";
echo "User Session: " . (isset($_SESSION['user_id']) ? 'Active (ID: ' . $_SESSION['user_id'] . ')' : 'None') . "\n";
echo "User Role: " . ($_SESSION['role'] ?? 'Not Set') . "\n";

// Database Connection Test
echo "\n=== DATABASE CONNECTION ===\n";
try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Database Connected | Users: {$result['count']}\n";
} catch (Exception $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
}

// Critical Differences Summary
echo "\n=== CRITICAL GAPS SUMMARY ===\n";
$gaps = [];

// Check for missing files
foreach ($criticalFiles as $category => $files) {
    foreach ($files as $file) {
        if (!file_exists(__DIR__ . '/../' . $file)) {
            $gaps[] = "Missing file: $file";
        }
    }
}

// Check for missing CSS features
if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    if (strpos($css, 'will-change: scroll-position') === false) {
        $gaps[] = "Missing sidebar scroll fix in CSS";
    }
    if (strpos($css, 'Hide Account section') === false) {
        $gaps[] = "Missing account hiding CSS";
    }
} else {
    $gaps[] = "Main CSS file missing";
}

// Check layout issues
if (file_exists($layoutFile)) {
    $layout = file_get_contents($layoutFile);
    if (strpos($layout, 'My Profile') !== false) {
        $gaps[] = "Account section still present in layout";
    }
    if (strpos($layout, 'sidebar-scroll.js') === false) {
        $gaps[] = "Sidebar scroll JS not included";
    }
}

if (empty($gaps)) {
    echo "✓ No critical gaps detected\n";
} else {
    foreach ($gaps as $i => $gap) {
        echo ($i + 1) . ". $gap\n";
    }
}

echo "\n=== DEPLOYMENT CHECKLIST ===\n";
echo "□ Upload latest ergon.css with sidebar fixes\n";
echo "□ Upload sidebar-scroll.css and sidebar-scroll.js\n";
echo "□ Upload updated dashboard.php layout\n";
echo "□ Upload fixed view files (owner/dashboard.php, attendance/index.php)\n";
echo "□ Clear browser cache (Ctrl+F5)\n";
echo "□ Test sidebar scrolling behavior\n";
echo "□ Verify Account section is hidden for owners\n";
echo "□ Check Recent Activities card is scrollable\n";

echo "\n=== QUICK VERIFICATION URLS ===\n";
$baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
echo "Dashboard: {$baseUrl}/ergon/dashboard\n";
echo "Settings: {$baseUrl}/ergon/settings\n";
echo "Reports: {$baseUrl}/ergon/reports\n";
echo "Attendance: {$baseUrl}/ergon/attendance\n";
?>