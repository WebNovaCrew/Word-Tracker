<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$baseDir = realpath(__DIR__ . '/..');
require_once $baseDir . '/config.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : null;

try {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : null;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : null;

    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM checklist_items WHERE checklist_id = c.id) as item_count,
              (SELECT COUNT(*) FROM checklist_items WHERE checklist_id = c.id AND is_done = 1) as completed_count
              FROM checklists c WHERE 1=1";

    $countQuery = "SELECT COUNT(*) as total FROM checklists c WHERE 1=1";

    $params = [];

    if ($user_id) {
        $query .= " AND c.user_id = :user_id";
        $countQuery .= " AND c.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }

    if ($plan_id) {
        $query .= " AND c.plan_id = :plan_id";
        $countQuery .= " AND c.plan_id = :plan_id";
        $params[':plan_id'] = $plan_id;
    }

    $query .= " ORDER BY c.created_at DESC";

    // Pagination
    $total_items = 0;
    $total_pages = 0;

    if ($page && $limit) {
        // Get Total Count first
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total_items = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_items / $limit);

        // Apply Limit/Offset
        $offset = ($page - 1) * $limit;
        $query .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $db->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    if ($page && $limit) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    $checklists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each checklist
    foreach ($checklists as &$checklist) {
        $itemQuery = "SELECT id, checklist_id, item_text as text, is_done, sort_order FROM checklist_items WHERE checklist_id = :checklist_id ORDER BY sort_order ASC";
        $itemStmt = $db->prepare($itemQuery);
        $itemStmt->bindParam(':checklist_id', $checklist['id']);
        $itemStmt->execute();
        $checklist['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $response = [
        "success" => true,
        "checklists" => $checklists
    ];

    if ($page && $limit) {
        $response["pagination"] = [
            "total_items" => $total_items,
            "current_page" => $page,
            "total_pages" => $total_pages,
            "limit" => $limit
        ];
    } else {
        // Legacy: Just return list. Optionally add total count if useful, but strictly following legacy structure is safer.
    }

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Get Checklists Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to fetch checklists",
        "error" => $e->getMessage()
    ]);
}