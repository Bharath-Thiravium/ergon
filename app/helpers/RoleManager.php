<?php

class RoleManager {
    private static $permissions = [
        'owner' => [
            'tasks.create', 'tasks.edit', 'tasks.delete', 'tasks.assign',
            'tasks.approve', 'tasks.escalate', 'users.manage', 'reports.view'
        ],
        'admin' => [
            'tasks.create', 'tasks.edit', 'tasks.assign', 'tasks.approve',
            'tasks.escalate', 'reports.view'
        ],
        'manager' => [
            'tasks.create', 'tasks.edit', 'tasks.assign', 'tasks.approve'
        ],
        'user' => [
            'tasks.update', 'tasks.view'
        ]
    ];
    
    public static function hasPermission($role, $permission) {
        return in_array($permission, self::$permissions[$role] ?? []);
    }
    
    public static function canManageTasks($role) {
        return in_array($role, ['owner', 'admin', 'manager']);
    }
    
    public static function canApprove($role) {
        return in_array($role, ['owner', 'admin', 'manager']);
    }
    
    public static function getTaskStates() {
        return [
            'draft' => 'Draft',
            'assigned' => 'Assigned', 
            'in_progress' => 'In Progress',
            'review' => 'Under Review',
            'completed' => 'Completed',
            'blocked' => 'Blocked'
        ];
    }
    
    public static function canTransition($currentState, $newState, $role) {
        $transitions = [
            'draft' => ['assigned'],
            'assigned' => ['in_progress', 'blocked'],
            'in_progress' => ['review', 'blocked', 'completed'],
            'review' => ['completed', 'in_progress'],
            'blocked' => ['assigned', 'in_progress'],
            'completed' => []
        ];
        
        if (!in_array($newState, $transitions[$currentState] ?? [])) {
            return false;
        }
        
        // Role-based transition rules
        if ($newState === 'completed' && $currentState === 'review') {
            return self::canApprove($role);
        }
        
        return true;
    }
}
?>