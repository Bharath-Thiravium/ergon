<?php
/**
 * ERGON CRITICAL FILES DEPLOYMENT HELPER
 * Generates deployment commands and file comparison
 */

// Prevent direct access
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Access denied. Add ?run=1 to execute.');
}

class DeploymentHelper {
    private $criticalFiles = [
        'public/assets/css/ergon.css' => 'Main CSS with all advanced features',
        'app/views/layouts/dashboard.php' => 'Dashboard layout template',
        'public/assets/css/sidebar-scroll.css' => 'Sidebar scroll styling',
        'public/assets/js/sidebar-scroll.js' => 'Sidebar scroll functionality',
        'app/views/owner/dashboard.php' => 'Owner dashboard with scrollable cards'
    ];
    
    public function generateDeploymentPlan() {
        echo "🚀 ERGON DEPLOYMENT PLAN\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        echo "📋 CRITICAL FILES TO UPLOAD (Priority Order):\n\n";
        
        $priority = 1;
        foreach ($this->criticalFiles as $file => $description) {
            echo "{$priority}. {$file}\n";
            echo "   📝 {$description}\n";
            
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $modified = date('Y-m-d H:i:s', filemtime($fullPath));
                echo "   📊 Size: " . number_format($size) . " bytes, Modified: {$modified}\n";
                
                // Show first few lines for verification
                if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                    $content = file_get_contents($fullPath);
                    $firstLine = strtok($content, "\n");
                    echo "   🔍 First line: " . substr($firstLine, 0, 80) . "...\n";
                }
            } else {
                echo "   ❌ FILE NOT FOUND!\n";
            }
            echo "\n";
            $priority++;
        }
        
        $this->generateUploadCommands();
        $this->generateVerificationSteps();
        $this->generateRollbackPlan();
    }
    
    private function generateUploadCommands() {
        echo "📤 UPLOAD COMMANDS (via FTP/cPanel File Manager):\n\n";
        
        foreach ($this->criticalFiles as $file => $description) {
            echo "Upload: {$file}\n";
            echo "To: /public_html/ergon/{$file}\n";
            echo "Action: Overwrite existing file\n";
            echo "Backup: Rename existing to {$file}.backup\n\n";
        }
        
        echo "🔧 ALTERNATIVE - cPanel File Manager Steps:\n";
        echo "1. Login to cPanel\n";
        echo "2. Open File Manager\n";
        echo "3. Navigate to /public_html/ergon/\n";
        echo "4. For each file above:\n";
        echo "   - Rename existing file to .backup\n";
        echo "   - Upload new file from localhost\n";
        echo "   - Set permissions to 644\n\n";
    }
    
    private function generateVerificationSteps() {
        echo "✅ POST-DEPLOYMENT VERIFICATION:\n\n";
        
        echo "1. 🌐 URL Tests:\n";
        echo "   - https://athenas.co.in/ergon/dashboard\n";
        echo "   - Check if page loads without errors\n";
        echo "   - Verify CSS is loading (check Network tab)\n\n";
        
        echo "2. 🎨 Visual Verification:\n";
        echo "   - Sidebar has profile controls at bottom\n";
        echo "   - KPI cards have enhanced styling\n";
        echo "   - Recent Activities card is scrollable\n";
        echo "   - Mobile menu toggle appears on small screens\n\n";
        
        echo "3. 🔧 Functional Tests:\n";
        echo "   - Click theme toggle (moon/sun icon)\n";
        echo "   - Click notification bell\n";
        echo "   - Click profile dropdown\n";
        echo "   - Test sidebar scrolling\n";
        echo "   - Test mobile responsiveness\n\n";
        
        echo "4. 🐛 Error Checking:\n";
        echo "   - Open browser console (F12)\n";
        echo "   - Check for JavaScript errors\n";
        echo "   - Check for 404 errors on CSS/JS files\n";
        echo "   - Verify no PHP warnings/errors\n\n";
    }
    
    private function generateRollbackPlan() {
        echo "🔄 ROLLBACK PLAN (if issues occur):\n\n";
        
        echo "If deployment causes issues:\n";
        echo "1. Rename uploaded files to .new\n";
        echo "2. Rename .backup files back to original names\n";
        echo "3. Clear browser cache (Ctrl+F5)\n";
        echo "4. Test functionality\n\n";
        
        echo "Emergency restore commands:\n";
        foreach ($this->criticalFiles as $file => $description) {
            echo "mv {$file}.backup {$file}\n";
        }
        echo "\n";
    }
    
    public function compareWithHostinger() {
        echo "🔍 LOCALHOST vs HOSTINGER COMPARISON:\n\n";
        
        echo "📊 Expected Improvements After Upload:\n";
        echo "✅ Enhanced KPI cards with hover effects\n";
        echo "✅ Dark theme support system\n";
        echo "✅ Better mobile responsiveness\n";
        echo "✅ Profile dropdown with avatar system\n";
        echo "✅ Notification center functionality\n";
        echo "✅ Advanced sidebar controls\n";
        echo "✅ Ergon calendar component\n";
        echo "✅ Enhanced form styling\n";
        echo "✅ Scrollable Recent Activities card\n";
        echo "✅ Smooth sidebar scrolling\n\n";
        
        echo "⚠️ Features Hostinger Currently Has (may be lost):\n";
        echo "- Header component with navigation\n";
        echo "- Breadcrumb system\n";
        echo "- Some layout refinements\n\n";
        
        echo "💡 Recommendation: Localhost CSS is MORE ADVANCED\n";
        echo "   Upload localhost files to get better functionality\n\n";
    }
    
    public function generateFileHashes() {
        echo "🔐 FILE INTEGRITY HASHES (for verification):\n\n";
        
        foreach ($this->criticalFiles as $file => $description) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                $hash = md5_file($fullPath);
                $size = filesize($fullPath);
                echo "{$file}:\n";
                echo "  MD5: {$hash}\n";
                echo "  Size: {$size} bytes\n\n";
            }
        }
        
        echo "Use these hashes to verify files uploaded correctly\n";
        echo "Compare with: md5sum filename on server\n\n";
    }
}

// Run the deployment helper
$helper = new DeploymentHelper();
$helper->generateDeploymentPlan();
$helper->compareWithHostinger();
$helper->generateFileHashes();

echo "🎯 QUICK ACTION SUMMARY:\n";
echo "1. Backup existing Hostinger files\n";
echo "2. Upload 5 critical files from localhost\n";
echo "3. Test functionality immediately\n";
echo "4. Rollback if any issues\n\n";

echo "⏰ Estimated deployment time: 10-15 minutes\n";
echo "🎯 Expected result: Hostinger will match localhost features\n\n";

echo str_repeat("=", 60) . "\n";
echo "DEPLOYMENT PLAN READY - Execute when ready\n";
echo str_repeat("=", 60) . "\n";
?>