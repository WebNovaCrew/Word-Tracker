<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
require_once $baseDir . '/config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

error_log("Create Checklist Data: " . print_r($data, true));

// Validate required fields
if (!empty($data->user_id) && !empty($data->name)) {
    try {
        $db->beginTransaction();

        // Create checklist
        $query = "INSERT INTO checklists (user_id, plan_id, name) VALUES (:user_id, :plan_id, :name)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $data->user_id);
        $plan_id = isset($data->plan_id) ? $data->plan_id : null;
        $stmt->bindParam(":plan_id", $plan_id);
        $stmt->bindParam(":name", $data->name);
        $stmt->execute();

        $checklist_id = $db->lastInsertId();

        // Insert checklist items if provided
        if (isset($data->items) && is_array($data->items)) {
            $queryItems = "INSERT INTO checklist_items (checklist_id, item_text, is_done, sort_order) 
                          VALUES (:checklist_id, :item_text, :is_done, :sort_order)";
            $stmtItems = $db->prepare($queryItems);

            foreach ($data->items as $index => $item) {
                $stmtItems->bindParam(":checklist_id", $checklist_id);
                $item_text = is_object($item) ? $item->text : $item;
                $stmtItems->bindParam(":item_text", $item_text);
                $is_done = is_object($item) && isset($item->is_done) ? $item->is_done : 0;
                $stmtItems->bindParam(":is_done", $is_done);
                $stmtItems->bindParam(":sort_order", $index);
                $stmtItems->execute();
            }
        }

        $db->commit();
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Checklist created successfully!",
            "checklist_id" => $checklist_id
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Create Checklist Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to create checklist.",
            "error" => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: user_id and name are required."
    ]);
}