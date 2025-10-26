<?php
class RoleManager {
    
    public static function hasPermission($userRole, $permission) {
        $permissions = [
            'owner' => ['*'],
            'admin' => ['users.view', 'users.create', 'users.edit', 'tasks.*', 'attendance.*', 'leaves.approve', 'expenses.approve'],
            'user' => ['tasks.view', 'attendance.own', 'leaves.create', 'expenses.create', 'profile.*']
        ];
        
        if (!isset($permissions[$userRole])) {
            return false;
        }
        
        $userPermissions = $permissions[$userRole];
        
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        foreach ($userPermissions as $perm) {
            if ($perm === $permission) {
                return true;
            }
            
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public static function canAccess($userRole, $resource, $action = 'view') {
        return self::hasPermission($userRole, $resource . '.' . $action);
    }
    
    public static function getRoleHierarchy() {
        return [
            'owner' => 3,
            'admin' => 2,
            'user' => 1
        ];
    }
    
    public static function isHigherRole($role1, $role2) {
        $hierarchy = self::getRoleHierarchy();
        return ($hierarchy[$role1] ?? 0) > ($hierarchy[$role2] ?? 0);
    }
}
?>