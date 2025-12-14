<?php
// backend-php/api/get_plans.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$baseDir = realpath(__DIR__ . '/..');
// Include Database config safely
require_once $baseDir . '/config/database.php';
include_once $baseDir . '/models/Plan.php';

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$plan->user_id = $user_id;

// Check for pagination params
$page = isset($_GET['page']) ? (int) $_GET['page'] : null;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : null;
$is_archived = isset($_GET['is_archived']) && $_GET['is_archived'] === 'true' ? 1 : 0;

if ($page && $limit) {
    // Paginated Request
    $stmt = $plan->getPlansPaginated($user_id, $page, $limit, $is_archived);
    $total_items = $plan->countPlans($user_id, $is_archived); // Get total for this filter
    $total_pages = ceil($total_items / $limit);
} else {
    // Legacy / All Request (e.g. for Profile stats)
    $stmt = $plan->getPlansWithProgress();
    // Use row count as total (ignoring pagination)
    $total_items = $stmt->rowCount();
    $total_pages = 1;
    $page = 1;
}

$num = $stmt->rowCount();

if ($num > 0) {
    $plans_arr = array();
    $plans_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // extract($row); // Avoid extract to be safe/clear

        // Calculate days left
        $days_left = 0;
        if (isset($row['end_date'])) {
            $today = new DateTime();
            $end = new DateTime($row['end_date']);
            $days_left_interval = $today->diff($end);
            $days_left = (int) $days_left_interval->format("%r%a");
        }

        $row_goal = isset($row['goal_amount']) ? $row['goal_amount'] : 0;
        $row_completed = isset($row['completed_amount']) ? $row['completed_amount'] : 0;

        $percent_done = ($row_goal > 0) ? ($row_completed / $row_goal) * 100 : 0;
        $what_is_left = $row_goal - $row_completed;

        $plan_item = array(
            "id" => $row['id'],
            "user_id" => $row['user_id'],
            "plan_name" => $row['name'], // Mapped to name
            "goal_amount" => $row_goal,
            "start_date" => $row['start_date'],
            "end_date" => $row['end_date'],
            "completed_amount" => $row_completed,
            "strategy" => isset($row['strategy']) ? $row['strategy'] : 'steady',
            "what_is_left" => $what_is_left,
            "percent_done" => round($percent_done, 1),
            "days_left" => $days_left,
            "folder_id" => isset($row['folder_id']) ? $row['folder_id'] : null,
            "is_archived" => isset($row['is_archived']) ? (bool) $row['is_archived'] : false,
            "color_code" => isset($row['color_code']) ? $row['color_code'] : '#3b82f6',
            "status" => (isset($row['is_archived']) && $row['is_archived']) ? 'archived' : (($days_left < 0) ? "Ended" : "In Progress"),
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        );

        array_push($plans_arr["records"], $plan_item);
    }

    http_response_code(200);
    // Add pagination metadata
    $response = [
        "records" => $plans_arr["records"],
        "pagination" => [
            "total_items" => $total_items,
            "current_page" => $page,
            "total_pages" => $total_pages,
            "limit" => $limit
        ]
    ];

    http_response_code(200);
    echo json_encode($response);
} else {
    http_response_code(200);
    echo json_encode(array("records" => []));
}