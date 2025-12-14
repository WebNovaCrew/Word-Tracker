<?php
// router.php - Custom router for PHP built-in server on Railway

$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading slash and get filename
$filename = ltrim($request_path, '/');

// Check if it's a direct .php file request
if (preg_match('/\.php$/', $filename)) {
    $filepath = __DIR__ . '/' . $filename;

    // If the file exists, include it directly
    if (file_exists($filepath) && is_file($filepath)) {
        require $filepath;
        exit;
    }
}

// For all other requests (including /api/*), use the index.php router
require_once __DIR__ . '/index.php';