<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->title) && !empty($data->status)) {
    $query = "UPDATE plans SET title = :title, status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":title", $data->title);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Plan updated."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update plan."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Required: id, title, status"]);
}
?>