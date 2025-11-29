<?php
header('Content-Type: application/json');
echo json_encode(['status' => 'working', 'timestamp' => date('c')]);
?>