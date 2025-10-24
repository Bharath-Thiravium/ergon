<?php
/**
 * QUICK AUDIT CHECK - Analyze localhost files for Hostinger deployment
 */

echo "🔍 ERGON QUICK AUDIT CHECK\n";
echo str_repeat("=", 50) . "\n\n";

// Check critical files
$files = [
    'public/assets/css/ergon.css' => 'Main CSS file',
    'app/views/layouts/dashboard.php' => 'Dashboard layout',
    'app/views/owner/dashboard.php' => 'Owner dashboard',
    'public/assets/css/sidebar-scroll.css' => 'Sidebar scroll CSS',
    'public/assets/js/sidebar-scroll.js' => 'Sidebar scroll JS'
];

echo "📁 FILE STATUS:\n";
foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "✅ {$desc}: " . number_format($size) . " bytes (Modified: {$modified})\n";
    } else {
        echo "❌ {$desc}: MISSING\n";
    }
}

echo "\n🎨 CSS FEATURES CHECK:\n";
if (file_exists('public/assets/css/ergon.css')) {
    $css = file_get_contents('public/assets/css/ergon.css');
    
    $features = [
        'sidebar__controls' => 'Profile controls at bottom',
        'notification-dropdown' => 'Notification system',
        'profile-menu' => 'Profile dropdown',
        'ergon-calendar' => 'Calendar component',
        'card__body--scrollable' => 'Scrollable cards',
        '[data-theme="dark"]' => 'Dark theme support',
        'kpi-card' => 'Enhanced KPI cards',
        'mobile-menu-toggle' => 'Mobile responsiveness'
    ];
    
    foreach ($features as $selector => $desc) {
        if (strpos($css, $selector) !== false) {
            echo "✅ {$desc}\n";
        } else {
            echo "❌ {$desc} - MISSING\n";
        }
    }
    
    $lines = substr_count($css, "\n");
    echo "\n📊 CSS Stats: {$lines} lines, " . number_format(strlen($css)) . " bytes\n";
} else {
    echo "❌ Cannot check CSS features - file missing\n";
}

echo "\n📄 LAYOUT TEMPLATE CHECK:\n";
if (file_exists('app/views/layouts/dashboard.php')) {
    $layout = file_get_contents('app/views/layouts/dashboard.php');
    
    $layoutFeatures = [
        'sidebar__controls' => 'Sidebar controls HTML',
        'toggleTheme()' => 'Theme toggle function',
        'toggleNotifications()' => 'Notification function',
        'sidebar-scroll.js' => 'Sidebar scroll script',
        'mobile-menu-toggle' => 'Mobile menu button'
    ];
    
    foreach ($layoutFeatures as $feature => $desc) {
        if (strpos($layout, $feature) !== false) {
            echo "✅ {$desc}\n";
        } else {
            echo "❌ {$desc} - MISSING\n";
        }
    }
} else {
    echo "❌ Cannot check layout features - file missing\n";
}

echo "\n🚀 DEPLOYMENT RECOMMENDATION:\n";
echo "Based on audit findings:\n";

if (file_exists('public/assets/css/ergon.css')) {
    $cssSize = filesize('public/assets/css/ergon.css');
    if ($cssSize > 50000) {
        echo "✅ LOCALHOST CSS IS ADVANCED ({$cssSize} bytes)\n";
        echo "📤 UPLOAD TO HOSTINGER IMMEDIATELY\n";
    }
}

echo "\n📋 CRITICAL UPLOAD ORDER:\n";
echo "1. public/assets/css/ergon.css (HIGHEST PRIORITY)\n";
echo "2. app/views/layouts/dashboard.php\n";
echo "3. public/assets/css/sidebar-scroll.css\n";
echo "4. public/assets/js/sidebar-scroll.js\n";
echo "5. app/views/owner/dashboard.php\n";

echo "\n🔧 POST-UPLOAD VERIFICATION:\n";
echo "1. Visit: https://athenas.co.in/ergon/dashboard\n";
echo "2. Check sidebar has profile controls at bottom\n";
echo "3. Verify Recent Activities card scrolls\n";
echo "4. Test theme toggle (moon/sun icon)\n";
echo "5. Check mobile responsiveness\n";
echo "6. Open browser console - check for errors\n";

echo "\n⚠️ BACKUP PLAN:\n";
echo "Before uploading, rename existing Hostinger files to .backup\n";
echo "If issues occur, restore .backup files\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "QUICK AUDIT COMPLETED\n";
echo str_repeat("=", 50) . "\n";
?>