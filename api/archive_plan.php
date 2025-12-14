<?php
// backend-php/api/archive_plan.php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Plan.php';

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);


// Log the request for debugging
$input = file_get_contents("php://input");
file_put_contents('debug_archive.txt', date('Y-m-d H:i:s') . " - Received: " . $input . "\n", FILE_APPEND);

$data = json_decode($input);

if (!empty($data->id)) {
    $shouldArchive = isset($data->archive) ? (bool) $data->archive : true;

    if ($plan->archive($data->id, $shouldArchive)) {
        http_response_code(200);
        echo json_encode(array("message" => "Plan archived."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to archive plan."));
        file_put_contents('debug_archive.txt', date('Y-m-d H:i:s') . " - Failed to archive ID: " . $data->id . "\n", FILE_APPEND);
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
    file_put_contents('debug_archive.txt', date('Y-m-d H:i:s') . " - Missing ID\n", FILE_APPEND);
}
?>