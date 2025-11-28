<?php
class InputValidator {
    public static function validateAction($action) {
        return in_array($action, ['mark-read', 'mark-all-read', 'mark-selected-read', 'get-unread-count']) ? $action : '';
    }
    
    public static function validateId($id) {
        return max(0, (int)$id);
    }
    
    public static function validateIds($ids) {
        if (!is_array($ids)) return [];
        return array_map('intval', array_filter($ids, 'is_numeric'));
    }
}
?>