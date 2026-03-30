<?php
/**
 * Navigation Helper
 * Handles menu items based on module access
 */

require_once __DIR__ . '/ModuleManager.php';

class NavigationHelper {
    
    public static function getMenuItems($userRole = 'user') {
        $allMenuItems = [
            'dashboard' => [
                'label' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => '🏠',
                'module' => 'dashboard'
            ],
            'attendance' => [
                'label' => 'Attendance',
                'url' => '/attendance',
                'icon' => '⏰',
                'module' => 'attendance'
            ],
            'leaves' => [
                'label' => 'Leaves',
                'url' => '/leaves',
                'icon' => '🏖️',
                'module' => 'leaves'
            ],
            'advances' => [
                'label' => 'Advances',
                'url' => '/advances',
                'icon' => '💰',
                'module' => 'advances'
            ],
            'expenses' => [
                'label' => 'Expenses',
                'url' => '/expenses',
                'icon' => '🧾',
                'module' => 'expenses'
            ],
            'tasks' => [
                'label' => 'Tasks',
                'url' => '/tasks',
                'icon' => '✅',
                'module' => 'tasks'
            ],
            'projects' => [
                'label' => 'Projects',
                'url' => '/project-management',
                'icon' => '📋',
                'module' => 'projects'
            ],
            'users' => [
                'label' => 'Users',
                'url' => '/users',
                'icon' => '👥',
                'module' => 'users',
                'roles' => ['admin', 'owner']
            ],
            'departments' => [
                'label' => 'Departments',
                'url' => '/departments',
                'icon' => '🏢',
                'module' => 'departments',
                'roles' => ['admin', 'owner']
            ],
            'reports' => [
                'label' => 'Reports',
                'url' => '/reports',
                'icon' => '📊',
                'module' => 'reports',
                'roles' => ['admin', 'owner']
            ],
            'finance' => [
                'label' => 'Finance',
                'url' => '/finance',
                'icon' => '💳',
                'module' => 'finance',
                'roles' => ['admin', 'owner']
            ],
            'followups' => [
                'label' => 'Follow-ups',
                'url' => '/contacts/followups',
                'icon' => '📞',
                'module' => 'followups'
            ],
            'notifications' => [
                'label' => 'Notifications',
                'url' => '/notifications',
                'icon' => '🔔',
                'module' => 'notifications'
            ]
        ];
        
        $accessibleItems = [];
        
        foreach ($allMenuItems as $key => $item) {
            // Check role access
            if (isset($item['roles']) && !in_array($userRole, $item['roles'])) {
                continue;
            }
            
            // Check module access
            if (ModuleManager::isModuleEnabled($item['module'])) {
                $accessibleItems[$key] = $item;
            } else {
                // Add as disabled item for premium modules
                $item['disabled'] = true;
                $item['upgrade_required'] = true;
                $accessibleItems[$key] = $item;
            }
        }
        
        return $accessibleItems;
    }
    
    public static function renderMenuItem($item, $currentPage = '') {
        $isActive = $currentPage === $item['url'] || strpos($_SERVER['REQUEST_URI'], $item['url']) === 0;
        $activeClass = $isActive ? 'nav-item--active' : '';
        
        if (isset($item['disabled']) && $item['disabled']) {
            return sprintf(
                '<li class="nav-item nav-item--disabled %s" title="Upgrade required">
                    <span class="nav-link nav-link--disabled">
                        <span class="nav-icon">%s</span>
                        <span class="nav-text">%s</span>
                        <span class="nav-badge">🔒</span>
                    </span>
                </li>',
                $activeClass,
                $item['icon'],
                $item['label']
            );
        }
        
        return sprintf(
            '<li class="nav-item %s">
                <a href="/ergon%s" class="nav-link">
                    <span class="nav-icon">%s</span>
                    <span class="nav-text">%s</span>
                </a>
            </li>',
            $activeClass,
            $item['url'],
            $item['icon'],
            $item['label']
        );
    }
}
