<?php
header("Content-Type: application/json; charset=UTF-8");

echo json_encode([
    "status" => "test",
    "message" => "Deployment test - version 2",
    "timestamp" => date('Y-m-d H:i:s'),
    "env_check" => [
        "MYSQLHOST" => getenv('MYSQLHOST') ?: 'NOT SET - using fallback: shuttle.proxy.rlwy.net',
        "MYSQLPORT" => getenv('MYSQLPORT') ?: 'NOT SET - using fallback: 3306',
        "MYSQLDATABASE" => getenv('MYSQLDATABASE') ?: 'NOT SET - using fallback: railway'
    ],
    "file_location" => __FILE__,
    "current_dir" => __DIR__
], JSON_PRETTY_PRINT);
?>