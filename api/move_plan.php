<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// folder_id can be null (to move to root)
if (!empty($data->plan_id) && isset($data->folder_id)) {

    $query = "UPDATE plans SET folder_id = :folder_id WHERE id = :plan_id";
    $stmt = $db->prepare($query);

    $folder_id = $data->folder_id ? $data->folder_id : NULL; // Handle 0 or null

    $stmt->bindParam(":folder_id", $folder_id);
    $stmt->bindParam(":plan_id", $data->plan_id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Plan moved."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to move plan."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
}
?>