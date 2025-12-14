<?php
// backend-php/config/cors.php

function handleCors()
{
    // Check if headers have already been sent by index.php or another source
    if (headers_sent()) {
        return;
    }

    // Allowed origins for CORS
    $allowedOrigins = [
        'http://localhost:4200',           // Local Angular dev
        'http://localhost',                // Local XAMPP
        'http://localhost:8000',           // Alternative local port
        'https://word-tracker.vercel.app', // Vercel production (update with your actual domain)
        // NOTE: Railway domains (*.railway.app) are handled via pattern match below
    ];

    // Get the origin from the request
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    // Check if origin is allowed or if we're in development
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (getenv('RAILWAY_ENVIRONMENT') || strpos($origin, 'railway.app') !== false) {
        // Allow Railway domains in production
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (empty($origin) || $origin === 'null') {
        // For direct API access (testing)
        header("Access-Control-Allow-Origin: *");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // Cache preflight for 1 day

    // Handle Preflight Options Request immediately
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}
?>