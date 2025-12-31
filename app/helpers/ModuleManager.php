<?php
/**
 * Module Manager
 * Handles module access control based on subscription
 */

class ModuleManager {
    private static $enabledModules = null;
    
    public static function isModuleEnabled($module) {
        $config = require __DIR__ . '/../config/modules.php';
        
        // Check role-based restrictions only for company_owner
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'company_owner' && isset($config['role_modules']['company_owner'])) {
            return in_array($module, $config['role_modules']['company_owner']);
        }
        
        // Basic modules are always enabled
        if (in_array($module, $config['basic_modules'])) {
            return true;
        }
        
        // Check if premium module is enabled
        $enabledModules = self::getEnabledModules();
        return in_array($module, $enabledModules);
    }
    
    public static function getEnabledModules() {
        if (self::$enabledModules === null) {
            $config = require __DIR__ . '/../config/modules.php';
            
            // Check role-based restrictions only for company_owner
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'company_owner' && isset($config['role_modules']['company_owner'])) {
                self::$enabledModules = $config['role_modules']['company_owner'];
                return self::$enabledModules;
            }
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $stmt = $db->query("SELECT module_name FROM enabled_modules WHERE status = 'active'");
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                self::$enabledModules = array_merge($config['basic_modules'], $result);
            } catch (Exception $e) {
                // Fallback to basic modules only
                self::$enabledModules = $config['basic_modules'];
            }
        }
        
        return self::$enabledModules;
    }
    
    public static function enableModule($module) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("INSERT INTO enabled_modules (module_name, status, enabled_at) VALUES (?, 'active', NOW()) ON DUPLICATE KEY UPDATE status = 'active', enabled_at = NOW()");
            $result = $stmt->execute([$module]);
            
            // Clear cache
            self::$enabledModules = null;
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function disableModule($module) {
        $config = require __DIR__ . '/../config/modules.php';
        
        // Cannot disable basic modules
        if (in_array($module, $config['basic_modules'])) {
            return false;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("UPDATE enabled_modules SET status = 'inactive' WHERE module_name = ?");
            $result = $stmt->execute([$module]);
            
            // Clear cache
            self::$enabledModules = null;
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function getAllModules() {
        $config = require __DIR__ . '/../config/modules.php';
        return array_merge($config['basic_modules'], $config['premium_modules']);
    }
    
    public static function getModuleLabel($module) {
        $config = require __DIR__ . '/../config/modules.php';
        return $config['module_labels'][$module] ?? ucfirst($module);
    }
    
    public static function isPremiumModule($module) {
        try {
            $config = require __DIR__ . '/../config/modules.php';
            return in_array($module, $config['premium_modules']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function isModuleDisabled($module) {
        try {
            return self::isPremiumModule($module) && !self::isModuleEnabled($module);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function clearCache() {
        self::$enabledModules = null;
    }
}
