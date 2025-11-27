<?php
class InputValidator {
    public static function validateId($id) {
        if (!is_numeric($id) || (int)$id <= 0 || (int)$id > PHP_INT_MAX) {
            throw new InvalidArgumentException('Invalid ID format');
        }
        return (int)$id;
    }
    
    public static function validateAction($action) {
        $allowed = ['mark-read', 'mark-all-read', 'mark-selected-read', 'get-unread-count'];
        if (!in_array($action, $allowed, true)) {
            throw new InvalidArgumentException('Invalid action');
        }
        return $action;
    }
    
    public static function validateIds($ids) {
        if (!is_array($ids) || empty($ids)) {
            throw new InvalidArgumentException('Invalid IDs array');
        }
        
        $validated = array_map([self::class, 'validateId'], $ids);
        return array_filter($validated, fn($id) => $id > 0);
    }
}
?>