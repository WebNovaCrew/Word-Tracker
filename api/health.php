<?php
require_once __DIR__ . '/../config/database.php';

// Force JSON response
header('Content-Type: application/json');

try {
    // Attempt DB connection
    $db = new Database();
    $conn = $db->getConnection();

    // Simple query to ensure read access
    // We select 1 to keep overhead minimum
    $stmt = $conn->query("SELECT 1");
    $result = $stmt->fetch();

    echo json_encode([
        "status" => "healthy",
        "service" => "word-tracker-api",
        "timestamp" => time(),
        "database" => "connected"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "unhealthy",
        "error" => "Database connection failed: " . $e->getMessage()
    ]);
}
?>