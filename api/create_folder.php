<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Folder.php';

$database = new Database();
$db = $database->getConnection();
$folder = new Folder($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->name)) {
    $folder->user_id = $data->user_id;
    $folder->name = $data->name;

    if ($folder->create()) {
        http_response_code(201);
        echo json_encode(array("message" => "Folder created.", "id" => $folder->id));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to create folder."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
}
?>