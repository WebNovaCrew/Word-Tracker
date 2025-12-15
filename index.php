<?php
// backend-php/index.php

// 1. Init Configuration
require_once 'config/cors.php';
require_once 'config/database.php';

// Handle Preflight and CORS headers
handleCors();

// 2. Parse URL to determine API Endpoint
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($request_uri, '/');

// Handle root path - show API info
if (empty($path) || $path === '') {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Word Tracker API is running",
        "version" => "1.0",
        "endpoints" => [
            "health" => "/api/ping.php",
            "auth" => [
                "login" => "/api/login.php",
                "register" => "/api/register.php"
            ],
            "plans" => "/api/get_plans.php",
            "database_init" => "/init_railway_db.php"
        ]
    ]);
    exit;
}

// Extract filename from path
$pathParts = explode('/', $path);
$filename = end($pathParts);

// If no extension, assume .php
if (strpos($filename, '.') === false) {
    $filename .= '.php';
}

// Security: Prevent directory traversal
$filename = basename($filename);

// Check if it's a direct file in root (like init_railway_db.php)
$rootFile = __DIR__ . '/' . $filename;
if (file_exists($rootFile) && !is_dir($rootFile)) {
    require $rootFile;
    exit;
}

// Check API directory
$apiFile = __DIR__ . '/api/' . $filename;
if (file_exists($apiFile)) {
    require $apiFile;
    exit;
}

// 3. 404 - Endpoint not found
header('Content-Type: application/json');
http_response_code(404);
echo json_encode([
    "status" => "error",
    "message" => "Endpoint not found",
    "path" => $request_uri,
    "requested_file" => $filename
]);
exit;
?>