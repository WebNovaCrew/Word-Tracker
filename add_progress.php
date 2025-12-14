<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->plan_id) && !empty($data->date) && isset($data->count)) {
    $query = "UPDATE plan_days SET actual_count = :count WHERE plan_id = :plan_id AND date = :date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":count", $data->count);
    $stmt->bindParam(":plan_id", $data->plan_id);
    $stmt->bindParam(":date", $data->date);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Progress updated."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update progress."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Required: plan_id, date, count"]);
}
?>