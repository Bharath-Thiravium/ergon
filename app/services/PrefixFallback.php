<?php

class PrefixFallback {
    private static $fallbackSequence = ['BKGE', 'SE', 'TC', 'BKC'];
    private static $validPrefixPattern = '/^[A-Z]{2,4}$/';
    
    public static function validate($prefix) {
        if (empty($prefix)) {
            return null;
        }
        
        $prefix = strtoupper(trim($prefix));
        
        if (!preg_match(self::$validPrefixPattern, $prefix)) {
            return null;
        }
        
        return $prefix;
    }
    
    public static function validateAndFallback($prefix) {
        // Try to validate the provided prefix first
        $validatedPrefix = self::validate($prefix);
        
        if ($validatedPrefix && self::hasRecentData($validatedPrefix)) {
            return $validatedPrefix;
        }
        
        // If no valid prefix or no recent data, try fallback sequence
        foreach (self::$fallbackSequence as $fallbackPrefix) {
            if (self::hasRecentData($fallbackPrefix)) {
                Logger::info("Prefix fallback applied", [
                    'requested' => $prefix,
                    'fallback_to' => $fallbackPrefix
                ]);
                return $fallbackPrefix;
            }
        }
        
        // If no prefix has recent data, return the first in sequence
        Logger::warning("No prefix has recent data, using default", [
            'requested' => $prefix,
            'default' => self::$fallbackSequence[0]
        ]);
        
        return self::$fallbackSequence[0];
    }
    
    private static function hasRecentData($prefix, $hoursThreshold = 24) {
        try {
            $config = require_once __DIR__ . '/../config/database.php';
            $mysql = $config['mysql'];
            
            $pdo = new PDO(
                "mysql:host={$mysql['host']};port={$mysql['port']};dbname={$mysql['database']};charset=utf8mb4",
                $mysql['username'],
                $mysql['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) as count 
                 FROM dashboard_stats 
                 WHERE company_prefix = ? 
                 AND generated_at > DATE_SUB(NOW(), INTERVAL ? HOUR)'
            );
            
            $stmt->execute([$prefix, $hoursThreshold]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            Logger::error("Failed to check recent data for prefix", [
                'prefix' => $prefix,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public static function getAllValidPrefixes() {
        return self::$fallbackSequence;
    }
    
    public static function addPrefix($prefix) {
        $validatedPrefix = self::validate($prefix);
        if ($validatedPrefix && !in_array($validatedPrefix, self::$fallbackSequence)) {
            self::$fallbackSequence[] = $validatedPrefix;
            return true;
        }
        return false;
    }
}