<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));
$item_id = isset($data->id) ? $data->id : (isset($_GET['id']) ? $_GET['id'] : null);

if ($item_id) {
    try {
        $query = "DELETE FROM checklist_items WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Checklist item deleted successfully"
        ]);

    } catch (Exception $e) {
        error_log("Delete Checklist Item Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to delete checklist item",
            "error" => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing item ID"
    ]);
}
?>