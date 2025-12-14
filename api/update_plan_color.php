<?php
// backend-php/api/update_plan_color.php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Plan.php';

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->color)) {
    if ($plan->updateColor($data->id, $data->color)) {
        http_response_code(200);
        echo json_encode(array("message" => "Plan color updated."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to update plan color."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
}
?>