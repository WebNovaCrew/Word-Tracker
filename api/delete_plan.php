<?php
// backend-php/api/delete_plan.php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Plan.php';

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);


// Log the request for debugging
$input = file_get_contents("php://input");
file_put_contents('debug_delete.txt', date('Y-m-d H:i:s') . " - Received: " . $input . "\n", FILE_APPEND);

$data = json_decode($input);

if (!empty($data->id)) {
    // Log the ID we're trying to delete
    file_put_contents('debug_delete.txt', date('Y-m-d H:i:s') . " - Deleting ID: " . $data->id . "\n", FILE_APPEND);

    if ($plan->delete($data->id)) {
        http_response_code(200);
        echo json_encode(array("message" => "Plan deleted."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to delete plan."));
        // Log failure
        file_put_contents('debug_delete.txt', date('Y-m-d H:i:s') . " - Failed to delete ID: " . $data->id . "\n", FILE_APPEND);
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data. ID is missing."));
    file_put_contents('debug_delete.txt', date('Y-m-d H:i:s') . " - Missing ID in request\n", FILE_APPEND);
}
?>