<?php
/**
 * POST /ergon/api/parse-whatsapp
 * Accepts raw pasted text, returns structured JSON.
 * Accessible to: owner, admin, user (all authenticated roles).
 */

require_once __DIR__ . '/../app/config/session.php';

header('Content-Type: application/json');

// Auth check — any logged-in user
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../app/services/WhatsAppParser.php';

$input = json_decode(file_get_contents('php://input'), true);
$raw   = $input['text'] ?? ($_POST['text'] ?? '');

if (empty(trim($raw))) {
    echo json_encode(['success' => false, 'error' => 'No text provided']);
    exit;
}

$parsed = WhatsAppParser::parse($raw);
$errors = WhatsAppParser::validate($parsed);

echo json_encode([
    'success'        => empty($errors),
    'errors'         => $errors,
    'is_whatsapp'    => $parsed['is_whatsapp'],
    'work_done'      => $parsed['work_done'],
    'materials_used' => $parsed['materials_used'],
    'issues_faced'   => $parsed['issues_faced'],
    'raw_cleaned'    => $parsed['raw_cleaned'],
]);
