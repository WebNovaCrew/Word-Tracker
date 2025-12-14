<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

error_log("Update Checklist Item: " . print_r($data, true));

if (!empty($data->id)) {
    try {
        $updates = [];
        $params = [':id' => $data->id];

        if (isset($data->item_text)) {
            $updates[] = "item_text = :item_text";
            $params[':item_text'] = $data->item_text;
        }

        if (isset($data->is_done)) {
            $updates[] = "is_done = :is_done";
            $params[':is_done'] = $data->is_done ? 1 : 0;
        }

        if (isset($data->sort_order)) {
            $updates[] = "sort_order = :sort_order";
            $params[':sort_order'] = $data->sort_order;
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No fields to update"]);
            exit;
        }

        $query = "UPDATE checklist_items SET " . implode(", ", $updates) . " WHERE id = :id";
        $stmt = $db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Checklist item updated successfully"
        ]);

    } catch (Exception $e) {
        error_log("Update Checklist Item Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to update checklist item",
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