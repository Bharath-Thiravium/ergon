<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();

    $rows = $db->query("SELECT company_prefix FROM finance_companies ORDER BY company_prefix")->fetchAll(PDO::FETCH_COLUMN);

    // Build simple prefix tree for the letter-selector UI
    $tree = [];
    foreach ($rows as $p) {
        $key = substr($p, 0, 2);
        if (!isset($tree[$key])) $tree[$key] = [];
        if (strlen($p) > 2) $tree[$key][] = substr($p, 2, 1);
    }
    foreach ($tree as &$v) $v = array_values(array_unique($v));

    echo json_encode(['success' => true, 'prefixes' => $rows, 'prefix_tree' => $tree]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
