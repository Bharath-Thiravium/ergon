<?php

class DatabaseHelper
{
    public static function safeExec($db, $sql, $description = '')
    {
        try {
            // Use prepare/execute for better security
            $stmt = $db->prepare($sql);
            $stmt->execute();
            // Only log table creation and schema changes, not routine operations
            if ($description && (strpos($description, 'Create') !== false || strpos($description, 'Add column') !== false)) {
                error_log("Database operation successful: $description");
            }
            return true;
        } catch (Exception $e) {
            error_log("Database operation failed ($description): " . $e->getMessage());
            return false;
        }
    }
    
    public static function addColumnIfNotExists($db, $table, $column, $definition)
    {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            
            if ($stmt->rowCount() === 0) {
                $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
                return self::safeExec($db, $sql, "Add column $column to $table");
            }
            return true;
        } catch (Exception $e) {
            error_log("Column check failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function createTableIfNotExists($db, $tableName, $createSQL)
    {
        try {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            
            if ($stmt->rowCount() === 0) {
                return self::safeExec($db, $createSQL, "Create table $tableName");
            }
            return true;
        } catch (Exception $e) {
            error_log("Table creation check failed: " . $e->getMessage());
            return false;
        }
    }
}
