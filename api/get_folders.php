<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Folder.php';

$database = new Database();
$db = $database->getConnection();

$folder = new Folder($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$folder->user_id = $user_id;
$stmt = $folder->getFolders();
$num = $stmt->rowCount();

if ($num > 0) {
    $folders_arr = array();
    $folders_arr["records"] = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $folder_item = array(
            "id" => $id,
            "name" => $name,
            "created_at" => $created_at
        );
        array_push($folders_arr["records"], $folder_item);
    }
    http_response_code(200);
    echo json_encode($folders_arr);
} else {
    http_response_code(200);
    echo json_encode(array("records" => []));
}
?>