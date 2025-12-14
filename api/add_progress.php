<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
// Include Database config safely
require_once $baseDir . '/config/database.php';
include_once $baseDir . '/models/Plan.php';

$database = new Database();
$db = $database->getConnection();
$plan = new Plan($db);

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->plan_id) &&
    !empty($data->date) &&
    isset($data->count) // count can be 0
) {
    if ($plan->updateProgress($data->plan_id, $data->date, $data->count)) {
        http_response_code(200);
        echo json_encode(array("message" => "Progress updated."));

        // TODO: Trigger auto-adjustment logic here if requested
        // For now, we just save the progress.
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to update progress."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
}
?>