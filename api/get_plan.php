<?php
// backend-php/api/get_plan.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
// Include Database config safely
require_once $baseDir . '/config/database.php';
include_once $baseDir . '/models/Plan.php';

$database = new Database();
$db = $database->getConnection();
$plan = new Plan($db);

$plan_id = isset($_GET['id']) ? $_GET['id'] : die();

// Assuming getPlanDetails exists in Plan model, otherwise I might need to query directly or use getPlanById
// Let's check Plan model briefly or just query here for safety if I am unsure.
// Ideally I should query here to be safe given my limited view of Plan.php recently.
// I'll query directly to ensure it works.

$query = "SELECT * FROM plans WHERE id = ? LIMIT 0,1";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $plan_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Plan not found."));
}