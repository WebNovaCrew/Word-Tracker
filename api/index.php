<?php
// backend-php/api/index.php

// 1. Handle CORS
// We need to include cors.php. Since we are in /api/, config is in ../config/
$configPath = __DIR__ . '/../config/cors.php';
if (file_exists($configPath)) {
    require_once $configPath;
    handleCors();
} else {
    // Fallback CORS if config missing
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
}

// 2. Return JSON Response
header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "message" => "Word Tracker API is running!",
    "environment" => "development",
    "timestamp" => date('c')
]);
exit;
?>