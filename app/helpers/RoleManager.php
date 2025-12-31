<?php
/**
 * Role Manager - Centralized Role-Based Access Control
 * ERGON - Employee Tracker & Task Manager
 */

class RoleManager {
    
    // Role hierarchy (higher number = more permissions)
    const ROLE_HIERARCHY = [
        'user' => 1,
        'admin' => 2,
        'system_admin' => 3,
        'owner' => 4
    ];
    
    // Module permissions by role
    const PERMISSIONS = [
        'user' => [
            'attendance' => ['view_own', 'create_own'],
            'tasks' => ['view_own', 'update_own'],
            'leaves' => ['view_own', 'create_own'],
            'expenses' => ['view_own', 'create_own'],
            'advances' => ['view_own', 'create_own'],
            'profile' => ['view_own', 'update_own'],
            'notifications' => ['view_own', 'mark_read']
        ],
        'admin' => [
            'attendance' => ['view_all', 'approve', 'override'],
            'tasks' => ['view_all', 'create', 'assign', 'update_all'],
            'leaves' => ['view_all', 'approve_first_level'],
            'expenses' => ['view_all', 'approve_first_level'],
            'advances' => ['view_all', 'approve_first_level'],
            'users' => ['view_department', 'create_user'],
            'departments' => ['view_own', 'manage_own'],
            'reports' => ['view_department']
        ],
        'system_admin' => [
            'attendance' => ['view_all', 'approve', 'override', 'manage_system'],
            'tasks' => ['view_all', 'create', 'assign', 'update_all', 'delete'],
            'leaves' => ['view_all', 'approve_first_level', 'manage_policies'],
            'expenses' => ['view_all', 'approve_first_level', 'manage_categories'],
            'advances' => ['view_all', 'approve_first_level', 'manage_limits'],
            'users' => ['view_all', 'create_all', 'manage_all'],
            'departments' => ['view_all', 'create', 'manage_all'],
            'system' => ['settings', 'maintenance', 'logs'],
            'reports' => ['view_all', 'export_all']
        ],
        'owner' => [
            'attendance' => ['view_all', 'approve', 'override', 'manage_system'],
            'tasks' => ['view_all', 'create', 'assign', 'update_all', 'delete'],
            'leaves' => ['view_all', 'final_approve', 'manage_policies'],
            'expenses' => ['view_all', 'final_approve', 'manage_categories'],
            'advances' => ['view_all', 'final_approve', 'manage_limits'],
            'users' => ['view_all', 'create_all', 'manage_all', 'assign_roles'],
            'departments' => ['view_all', 'create', 'manage_all'],
            'system' => ['full_access', 'settings', 'maintenance', 'logs'],
            'reports' => ['view_all', 'export_all', 'analytics']
        ]
    ];
    
    public static function hasPermission($role, $module, $action) {
        if (!isset(self::PERMISSIONS[$role][$module])) {
            return false;
        }
        return in_array($action, self::PERMISSIONS[$role][$module]);
    }
    
    public static function canApprove($role, $module, $level = 'first') {
        $permissions = self::PERMISSIONS[$role][$module] ?? [];
        
        if ($level === 'final') {
            return in_array('final_approve', $permissions);
        }
        
        return in_array('approve_first_level', $permissions) || 
               in_array('final_approve', $permissions);
    }
    
    public static function canCreate($role, $module) {
        return self::hasPermission($role, $module, 'create') || 
               self::hasPermission($role, $module, 'create_all');
    }
    
    public static function canViewAll($role, $module) {
        return self::hasPermission($role, $module, 'view_all');
    }
    
    public static function isSystemAdmin($role) {
        return $role === 'system_admin';
    }
    
    public static function isDepartmentAdmin($role) {
        return $role === 'admin';
    }
    
    public static function isOwner($role) {
        return $role === 'owner';
    }
    
    public static function getRoleLevel($role) {
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }
    
    public static function canManageRole($currentRole, $targetRole) {
        return self::getRoleLevel($currentRole) > self::getRoleLevel($targetRole);
    }
}
?>
