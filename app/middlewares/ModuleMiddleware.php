<?php
/**
 * Module Access Middleware
 * Checks if user can access requested module
 */

require_once __DIR__ . '/../helpers/ModuleManager.php';
if (session_status() === PHP_SESSION_NONE) session_start();

class ModuleMiddleware {
    
    public static function checkModuleAccess($module) {
        if (!ModuleManager::isModuleEnabled($module)) {
            self::showAccessDenied($module);
            exit;
        }
    }
    
    public static function requireModule($module) {
        self::checkModuleAccess($module);
    }
    
    private static function showAccessDenied($module) {
        $moduleLabel = ModuleManager::getModuleLabel($module);
        
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => "Access denied: {$moduleLabel} is not available in your subscription",
                'module' => $module,
                'upgrade_required' => true
            ]);
        } else {
            http_response_code(403);
            
            // Use main layout for consistent styling
            $title = 'Access Denied';
            $active_page = 'access_denied';
            
            ob_start();
            ?>
            <div class="access-denied-container">
                <div class="access-denied-card">
                    <div class="access-denied-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <h1 class="access-denied-title">Premium Feature Required</h1>
                    <div class="access-denied-message">
                        <p><strong><?= htmlspecialchars($moduleLabel) ?></strong> is a premium feature that requires activation.</p>
                        <p>Contact your administrator to enable this module in your subscription.</p>
                    </div>
                    <div class="access-denied-actions">
                        <a href="/ergon/dashboard" class="btn btn-primary">
                            <i class="bi bi-house-fill"></i>
                            Back to Dashboard
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                        <a href="/ergon/modules" class="btn btn-outline-primary">
                            <i class="bi bi-gear-fill"></i>
                            Manage Modules
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            
            require __DIR__ . '/../../views/layouts/dashboard.php';
        }
    }
    
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}
