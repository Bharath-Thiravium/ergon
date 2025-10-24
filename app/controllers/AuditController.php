<?php
class AuditController {
    public function index() {
        $data = [];
        $data['audit_results'] = $this->performAudit();
        
        $title = 'System Audit';
        $active_page = 'audit';
        
        ob_start();
        include __DIR__ . '/../views/audit/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/dashboard.php';
    }
    
    private function performAudit() {
        $results = [];
        
        // Environment Info
        $results['environment'] = [
            'host' => $_SERVER['HTTP_HOST'],
            'is_localhost' => strpos($_SERVER['HTTP_HOST'], 'localhost') !== false,
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Critical Files Check
        $criticalFiles = [
            'public/assets/css/ergon.css',
            'public/assets/css/sidebar-scroll.css',
            'public/assets/js/sidebar-scroll.js',
            'app/views/layouts/dashboard.php',
            'app/views/owner/dashboard.php',
            'app/views/attendance/index.php'
        ];
        
        $results['files'] = [];
        foreach ($criticalFiles as $file) {
            $fullPath = __DIR__ . '/../../' . $file;
            $results['files'][$file] = [
                'exists' => file_exists($fullPath),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'modified' => file_exists($fullPath) ? date('Y-m-d H:i:s', filemtime($fullPath)) : 'N/A',
                'hash' => file_exists($fullPath) ? substr(md5_file($fullPath), 0, 8) : 'N/A'
            ];
        }
        
        // CSS Features Check
        $cssFile = __DIR__ . '/../../public/assets/css/ergon.css';
        $results['css_features'] = [];
        if (file_exists($cssFile)) {
            $css = file_get_contents($cssFile);
            $features = [
                'Sidebar Scroll Fix' => 'will-change: scroll-position',
                'Smooth Scrolling' => 'scroll-behavior: smooth',
                'Account Hiding' => 'Hide Account section',
                'Table Styles' => 'table-responsive',
                'Scrollable Cards' => 'card__body--scrollable'
            ];
            
            foreach ($features as $name => $needle) {
                $results['css_features'][$name] = strpos($css, $needle) !== false;
            }
        }
        
        // Layout Check
        $layoutFile = __DIR__ . '/../views/layouts/dashboard.php';
        $results['layout_features'] = [];
        if (file_exists($layoutFile)) {
            $layout = file_get_contents($layoutFile);
            $results['layout_features'] = [
                'account_removed' => strpos($layout, 'My Profile') === false,
                'sidebar_js_included' => strpos($layout, 'sidebar-scroll.js') !== false,
                'navigation_role' => strpos($layout, 'role="navigation"') !== false
            ];
            
            // Extract CSS version
            if (preg_match('/ergon\.css\?v=([^"]+)/', $layout, $matches)) {
                $results['css_version'] = $matches[1];
            }
        }
        
        return $results;
    }
}