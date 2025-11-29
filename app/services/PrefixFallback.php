<?php

require_once __DIR__ . '/../config/database.php';

class PrefixFallback {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getLatestActivePrefix() {
        $stmt = $this->db->query("
            SELECT company_prefix 
            FROM dashboard_stats 
            WHERE total_revenue > 0
            ORDER BY generated_at DESC 
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['company_prefix'] ?? 'BKGE';
    }
}