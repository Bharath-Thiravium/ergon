<?php
// Returns array of company_prefix strings from finance_companies MySQL table.
// Falls back to hardcoded list if table not available.
try {
    if (!isset($db)) {
        require_once __DIR__ . '/../../../app/config/database.php';
        $db = Database::connect();
    }
    $rows = $db->query('SELECT company_prefix FROM finance_companies ORDER BY company_prefix')->fetchAll(PDO::FETCH_COLUMN);
    return $rows ?: ['TC','SE','BKC','AS','BKGE','PGEL'];
} catch (Exception $e) {
    return ['TC','SE','BKC','AS','BKGE','PGEL'];
}
