<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test database connection
include_once 'config.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo json_encode([
        "status" => "success",
        "message" => "Database connected successfully",
        "server_running" => true,
        "timestamp" => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "server_running" => true
    ], JSON_PRETTY_PRINT);
}
?>