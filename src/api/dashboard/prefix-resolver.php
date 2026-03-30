<?php
// Returns company_id (int) for a given prefix string, or null if not found.
// Falls back to LIKE match if exact match fails.
function resolveCompanyId(string $rawPrefix, PDO $db): ?int {
    $prefix = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $rawPrefix)));
    if (!$prefix) return null;

    $stmt = $db->prepare('SELECT company_id FROM finance_companies WHERE UPPER(company_prefix) = ? LIMIT 1');
    $stmt->execute([$prefix]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['company_id'];

    // Partial match — find first company whose prefix starts with input
    $stmt = $db->prepare('SELECT company_id FROM finance_companies WHERE UPPER(company_prefix) LIKE ? ORDER BY LENGTH(company_prefix) ASC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['company_id'] : null;
}
