<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$plan_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$plan_id) {
    http_response_code(400);
    echo json_encode(["message" => "Missing id parameter."]);
    exit();
}

// Get plan
$query = "SELECT * FROM plans WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $plan_id);
$stmt->execute();

$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    http_response_code(404);
    echo json_encode(["message" => "Plan not found."]);
    exit();
}

// Get plan days
$queryDays = "SELECT * FROM plan_days WHERE plan_id = :plan_id ORDER BY date ASC";
$stmtDays = $db->prepare($queryDays);
$stmtDays->bindParam(":plan_id", $plan_id);
$stmtDays->execute();

$plan['schedule'] = $stmtDays->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode($plan);
?>