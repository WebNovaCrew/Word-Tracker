<?php
// backend-php/api/ping.php
require_once __DIR__ . '/../config/cors.php';
handleCors();

header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'message' => 'Backend is reachable']);
?>