<?php
try {
    $pdo = new PDO(
        "mysql:host=" . getenv("MYSQLHOST") .
        ";port=" . getenv("MYSQLPORT") .
        ";dbname=" . getenv("MYSQLDATABASE"),
        getenv("MYSQLUSER"),
        getenv("MYSQLPASSWORD"),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo json_encode([
        "db" => "connected",
        "status" => "ok"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "db" => "failed",
        "error" => $e->getMessage()
    ]);
}
