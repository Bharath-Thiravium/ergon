<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once __DIR__ . '/../app/config/database.php';
$db = Database::connect();

$projects = $db->query("SELECT id, name, location_title FROM projects WHERE status='active' ORDER BY name")
               ->fetchAll(PDO::FETCH_ASSOC);

$users = $db->query("SELECT id, name, designation, department FROM users WHERE status='active' ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['projects' => $projects, 'users' => $users]);
