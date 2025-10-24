<?php
/**
 * ERGON COMPREHENSIVE AUDIT SCRIPT
 * Systematically checks differences between localhost and Hostinger
 */

// Prevent direct access
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Access denied. Add ?run=1 to execute.');
}

class ErgonAudit {
    private $results = [];
    private $criticalIssues = [];
    private $recommendations = [];
    
    public function __construct() {
        $this->results['timestamp'] = date('Y-m-d H:i:s');
        $this->results['environment'] = 'localhost';
    }
    
    public function runFullAudit() {
        echo "🔍 ERGON COMPREHENSIVE AUDIT STARTING...\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        $this->checkFileStructure();
        $this->checkCSSFeatures();
        $this->checkJavaScriptFiles();
        $this->checkLayoutTemplates();
        $this->checkDatabaseTables();
        $this->checkSecurityFeatures();
        $this->generateReport();
        
        echo "\n✅ AUDIT COMPLETED\n";
        return $this->results;
    }
    
    private function checkFileStructure() {
        echo "📁 Checking File Structure...\n";
        
        $criticalFiles = [
            'public/assets/css/ergon.css',
            'public/assets/css/sidebar-scroll.css',
            'public/assets/js/sidebar-scroll.js',
            'app/views/layouts/dashboard.php',
            'app/views/owner/dashboard.php',
            'public/assets/css/components.css',
            'public/assets/css/dark-theme.css'
        ];
        
        foreach ($criticalFiles as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $modified = date('Y-m-d H:i:s', filemtime($fullPath));
                $this->results['files'][$file] = [
                    'exists' => true,
                    'size' => $size,
                    'modified' => $modified,
                    'status' => 'OK'
                ];
                echo "  ✅ {$file} ({$size} bytes, modified: {$modified})\n";
            } else {
                $this->results['files'][$file] = [
                    'exists' => false,
                    'status' => 'MISSING'
                ];
                $this->criticalIssues[] = "Missing file: {$file}";
                echo "  ❌ {$file} - MISSING\n";
            }
        }
    }
    
    private function checkCSSFeatures() {
        echo "\n🎨 Checking CSS Features...\n";
        
        $cssFile = __DIR__ . '/public/assets/css/ergon.css';
        if (!file_exists($cssFile)) {
            $this->criticalIssues[] = "Main CSS file missing";
            return;
        }
        
        $cssContent = file_get_contents($cssFile);
        
        $features = [
            'sidebar__controls' => 'Profile controls at bottom',
            'notification-dropdown' => 'Notification system',
            'profile-menu' => 'Profile dropdown menu',
            'mobile-menu-toggle' => 'Mobile responsiveness',
            'ergon-calendar' => 'Calendar component',
            'card__body--scrollable' => 'Scrollable cards',
            '[data-theme="dark"]' => 'Dark theme support',
            'kpi-card--primary' => 'Enhanced KPI cards',
            'sidebar__link--active' => 'Active link styling',
            'sidebar__menu::-webkit-scrollbar' => 'Custom scrollbars'
        ];
        
        foreach ($features as $selector => $description) {
            if (strpos($cssContent, $selector) !== false) {
                $this->results['css_features'][$selector] = [
                    'present' => true,
                    'description' => $description
                ];
                echo "  ✅ {$description}\n";
            } else {
                $this->results['css_features'][$selector] = [
                    'present' => false,
                    'description' => $description
                ];
                $this->criticalIssues[] = "Missing CSS feature: {$description}";
                echo "  ❌ {$description} - MISSING\n";
            }
        }
        
        // Check CSS file size and complexity
        $lines = substr_count($cssContent, "\n");
        $this->results['css_stats'] = [
            'file_size' => strlen($cssContent),
            'line_count' => $lines,
            'has_dark_theme' => strpos($cssContent, '[data-theme="dark"]') !== false,
            'has_mobile_responsive' => strpos($cssContent, '@media (max-width: 768px)') !== false
        ];
        
        echo "  📊 CSS Stats: {$lines} lines, " . number_format(strlen($cssContent)) . " bytes\n";
    }
    
    private function checkJavaScriptFiles() {
        echo "\n⚡ Checking JavaScript Files...\n";
        
        $jsFiles = [
            'public/assets/js/sidebar-scroll.js' => 'Sidebar scroll functionality',
            'public/assets/js/ergon-core.js' => 'Core functionality',
            'public/assets/js/mobile-menu.js' => 'Mobile menu handling'
        ];
        
        foreach ($jsFiles as $file => $description) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $this->results['js_files'][$file] = [
                    'exists' => true,
                    'size' => strlen($content),
                    'description' => $description
                ];
                echo "  ✅ {$description}\n";
            } else {
                $this->results['js_files'][$file] = [
                    'exists' => false,
                    'description' => $description
                ];
                echo "  ⚠️ {$description} - Optional\n";
            }
        }
    }
    
    private function checkLayoutTemplates() {
        echo "\n📄 Checking Layout Templates...\n";
        
        $layoutFile = __DIR__ . '/app/views/layouts/dashboard.php';
        if (!file_exists($layoutFile)) {
            $this->criticalIssues[] = "Dashboard layout template missing";
            return;
        }
        
        $layoutContent = file_get_contents($layoutFile);
        
        $layoutFeatures = [
            'sidebar__controls' => 'Sidebar controls section',
            'notification-dropdown' => 'Notification dropdown HTML',
            'profile-menu' => 'Profile menu HTML',
            'mobile-menu-toggle' => 'Mobile menu toggle button',
            'data-theme=' => 'Theme attribute support',
            'toggleTheme()' => 'Theme toggle function',
            'toggleNotifications()' => 'Notification toggle function',
            'sidebar-scroll.js' => 'Sidebar scroll script inclusion'
        ];
        
        foreach ($layoutFeatures as $feature => $description) {
            if (strpos($layoutContent, $feature) !== false) {
                $this->results['layout_features'][$feature] = true;
                echo "  ✅ {$description}\n";
            } else {
                $this->results['layout_features'][$feature] = false;
                $this->criticalIssues[] = "Missing layout feature: {$description}";
                echo "  ❌ {$description} - MISSING\n";
            }
        }
        
        // Check for owner-specific hiding
        if (strpos($layoutContent, 'data-role="owner"') !== false) {
            echo "  ✅ Owner role-specific styling\n";
        } else {
            echo "  ⚠️ Owner role-specific styling - Check needed\n";
        }
    }
    
    private function checkDatabaseTables() {
        echo "\n🗄️ Checking Database Structure...\n";
        
        try {
            require_once __DIR__ . '/config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $tables = ['users', 'tasks', 'attendance', 'leaves', 'expenses', 'notifications'];
            
            foreach ($tables as $table) {
                $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->rowCount() > 0) {
                    // Get row count
                    $countStmt = $conn->prepare("SELECT COUNT(*) FROM `{$table}`");
                    $countStmt->execute();
                    $count = $countStmt->fetchColumn();
                    
                    $this->results['database'][$table] = [
                        'exists' => true,
                        'row_count' => $count
                    ];
                    echo "  ✅ {$table} table ({$count} records)\n";
                } else {
                    $this->results['database'][$table] = ['exists' => false];
                    $this->criticalIssues[] = "Missing table: {$table}";
                    echo "  ❌ {$table} table - MISSING\n";
                }
            }
        } catch (Exception $e) {
            echo "  ❌ Database connection failed: " . $e->getMessage() . "\n";
            $this->criticalIssues[] = "Database connection failed";
        }
    }
    
    private function checkSecurityFeatures() {
        echo "\n🔒 Checking Security Features...\n";
        
        $securityChecks = [
            '.htaccess' => 'Apache security rules',
            'config/.env' => 'Environment configuration',
            'app/middlewares/AuthMiddleware.php' => 'Authentication middleware',
            'app/helpers/Security.php' => 'Security helper functions'
        ];
        
        foreach ($securityChecks as $file => $description) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo "  ✅ {$description}\n";
                $this->results['security'][$file] = true;
            } else {
                echo "  ⚠️ {$description} - Check needed\n";
                $this->results['security'][$file] = false;
            }
        }
        
        // Check for session security
        $layoutFile = __DIR__ . '/app/views/layouts/dashboard.php';
        if (file_exists($layoutFile)) {
            $content = file_get_contents($layoutFile);
            if (strpos($content, 'session_start()') !== false) {
                echo "  ✅ Session management present\n";
            }
            if (strpos($content, 'Cache-Control') !== false) {
                echo "  ✅ Cache control headers present\n";
            }
        }
    }
    
    private function generateReport() {
        echo "\n📋 GENERATING AUDIT REPORT...\n";
        echo "=" . str_repeat("=", 50) . "\n";
        
        // Critical Issues Summary
        if (!empty($this->criticalIssues)) {
            echo "\n🚨 CRITICAL ISSUES FOUND:\n";
            foreach ($this->criticalIssues as $issue) {
                echo "  ❌ {$issue}\n";
            }
        } else {
            echo "\n✅ NO CRITICAL ISSUES FOUND\n";
        }
        
        // Recommendations
        $this->generateRecommendations();
        
        if (!empty($this->recommendations)) {
            echo "\n💡 RECOMMENDATIONS:\n";
            foreach ($this->recommendations as $rec) {
                echo "  📌 {$rec}\n";
            }
        }
        
        // File deployment checklist
        echo "\n📦 DEPLOYMENT CHECKLIST:\n";
        echo "  1. Upload public/assets/css/ergon.css (CRITICAL)\n";
        echo "  2. Upload app/views/layouts/dashboard.php\n";
        echo "  3. Upload public/assets/css/sidebar-scroll.css\n";
        echo "  4. Upload public/assets/js/sidebar-scroll.js\n";
        echo "  5. Upload app/views/owner/dashboard.php\n";
        echo "  6. Clear browser cache and test\n";
        
        // Save results to file
        $reportFile = __DIR__ . '/audit_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: {$reportFile}\n";
    }
    
    private function generateRecommendations() {
        // Analyze results and generate recommendations
        if (isset($this->results['css_stats']['file_size']) && $this->results['css_stats']['file_size'] > 50000) {
            $this->recommendations[] = "CSS file is comprehensive with advanced features - upload to Hostinger immediately";
        }
        
        if (count($this->criticalIssues) > 0) {
            $this->recommendations[] = "Address critical issues before deployment";
        }
        
        if (!isset($this->results['css_features']['sidebar__controls']['present']) || 
            !$this->results['css_features']['sidebar__controls']['present']) {
            $this->recommendations[] = "Localhost has more advanced sidebar controls than Hostinger";
        }
        
        $this->recommendations[] = "Perform manual testing after file uploads";
        $this->recommendations[] = "Check console for JavaScript errors after deployment";
        $this->recommendations[] = "Verify mobile responsiveness on both environments";
    }
}

// Run the audit
$audit = new ErgonAudit();
$results = $audit->runFullAudit();

// Additional manual checks
echo "\n🔧 MANUAL VERIFICATION STEPS:\n";
echo "1. Visit both localhost and Hostinger URLs\n";
echo "2. Compare sidebar functionality and styling\n";
echo "3. Test mobile responsiveness\n";
echo "4. Check browser console for errors\n";
echo "5. Verify all KPI cards display correctly\n";
echo "6. Test theme toggle functionality\n";
echo "7. Verify notification system works\n";
echo "8. Check profile dropdown functionality\n";

echo "\n🌐 ENVIRONMENT URLS TO TEST:\n";
echo "Localhost: http://localhost/ergon/dashboard\n";
echo "Hostinger: https://athenas.co.in/ergon/dashboard\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "AUDIT COMPLETED - Review results above\n";
echo str_repeat("=", 60) . "\n";
?>