<?php
// backend-php/index.php

// 1. Init Configuration
require_once 'config/cors.php';
require_once 'config/database.php';

// Handle Preflight and CORS headers
handleCors();

// 2. Parse URL to determine API Endpoint
// Request URI comes in like /api/login or /word-tracker/backend-php/api/login depending on host
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($request_uri, '/'));

// Universal Router
// Routes requests from /word-tracker/endpoint.php to /backend-php/api/endpoint.php
// OR /endpoint.php to api/endpoint.php if served from root

$path = parse_url($request_uri, PHP_URL_PATH);
$filename = basename($path); // e.g. login.php or login

// If no extension, assume .php
if (strpos($filename, '.') === false) {
    $filename .= '.php';
}

// Security: Prevent directory traversal
$filename = basename($filename);

$apiFile = __DIR__ . '/api/' . $filename;

if (file_exists($apiFile)) {
    require $apiFile;
} else {
    // Check if it's a known mapping
    http_response_code(404);
    echo json_encode([
        "message" => "Endpoint not found: " . $filename,
        "debug_path" => $apiFile
    ]);
}

// 3. Fallback / 404
// Since we are a Backend-Only API now, we do NOT serve frontend files or HTML.
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    "message" => "API Endpoint not found",
    "status" => "error",
    "path" => $request_uri
]);
?>