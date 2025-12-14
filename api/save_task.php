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

if (
    !empty($data->plan_id) &&
    !empty($data->text)
) {
    $task->plan_id = $data->plan_id;
    $task->text = $data->text;
    $task->date = !empty($data->date) ? $data->date : null;
    $task->order_index = isset($data->order_index) ? $data->order_index : 0;
    $task->is_completed = isset($data->is_completed) ? $data->is_completed : 0;

    if (isset($data->id)) {
        $task->id = $data->id;
        if ($task->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Task updated.", "id" => $task->id));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update task."));
        }
    } else {
        if ($task->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Task created.", "id" => $task->id));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create task."));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to save task. Data is incomplete."));
}