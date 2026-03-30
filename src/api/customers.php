<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../api/dashboard/prefix-resolver.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();

    $prefix = $_GET['prefix'] ?? '';
    if (empty($prefix)) throw new Exception('Prefix is required');

    $companyId = resolveCompanyId($prefix, $db);
    if (!$companyId) {
        echo json_encode(['success' => true, 'customers' => []]);
        exit;
    }

    // Get distinct customers who have invoices for this company
    $stmt = $db->prepare("SELECT DISTINCT i.customer_id AS id, COALESCE(c.customer_name, i.customer_id) AS display_name
        FROM finance_invoices i
        LEFT JOIN finance_customers c ON c.customer_id = i.customer_id
        WHERE i.company_id = ?
        ORDER BY display_name");
    $stmt->execute([$companyId]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'customers' => $customers]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
