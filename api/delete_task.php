<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Task.php';

$database = new Database();
$db = $database->getConnection();

$task = new Task($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    $task->id = $data->id;

    if ($task->delete()) {
        http_response_code(200);
        echo json_encode(array("message" => "Task deleted."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to delete task."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete task. ID is missing."));
}