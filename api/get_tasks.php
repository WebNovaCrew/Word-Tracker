<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Task.php';

$database = new Database();
$db = $database->getConnection();

$task = new Task($db);

$plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : die();

$stmt = $task->getTasksByPlan($plan_id);
$num = $stmt->rowCount();

$tasks_arr = array();
$tasks_arr["records"] = array();

if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $task_item = array(
            "id" => $id,
            "plan_id" => $plan_id,
            "text" => $text,
            "date" => $date,
            "order_index" => $order_index,
            "is_completed" => (bool) $is_completed
        );
        array_push($tasks_arr["records"], $task_item);
    }
}

echo json_encode($tasks_arr);