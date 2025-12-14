<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    $query = "DELETE FROM plans WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Plan deleted."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete plan."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Missing id."]);
}
?>