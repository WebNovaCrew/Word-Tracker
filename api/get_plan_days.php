<?php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';

// Access-Control headers are handled in config.php

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["message" => "Missing user_id parameter."]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "
        SELECT 
            pd.date, 
            SUM(pd.target) as total_target
        FROM plan_days pd
        JOIN plans p ON pd.plan_id = p.id
        WHERE p.user_id = :user_id
        GROUP BY pd.date
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $days = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(["success" => true, "data" => $days]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>