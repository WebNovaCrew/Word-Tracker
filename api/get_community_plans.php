<?php
// backend-php/api/get_community_plans.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Plan.php';

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);

// Optional filters
$activity = isset($_GET['activity']) ? $_GET['activity'] : null;
$content = isset($_GET['content']) ? $_GET['content'] : null;

// Note: The model method currently doesn't support filtering by activity/content directly in SQL for simplicity,
// but we can filter in PHP or update the model. Given the requirement for "Activity" and "Content" filters,
// and that these map to 'subtitle' (Activity + Content) or specific fields if we parsed them.
// In the current schema, 'subtitle' holds "Activity + Content". 
// Let's assume we filter on the client side or fetch more and filter.
// Or better, let's update the model later if needed. For now, let's fetch the recent ones.

$stmt = $plan->getCommunityPlans(50); // Fetch a bit more to allow client filtering
$num = $stmt->rowCount();

$plans_arr = array();
$plans_arr["records"] = array();

if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        // Map database columns to expected variables
        // $goal_amount comes from extract($row)
        $total_word_count = isset($goal_amount) ? (int) $goal_amount : 0;

        // $content_type comes from extract($row)
        $subtitle = isset($content_type) ? $content_type : 'Unknown';

        // $activity_type comes from extract($row)
        $act_type = isset($activity_type) ? $activity_type : 'Writing';

        // Fetch graph data & Calculate completed amount
        $history = $plan->getPlanProgressHistory($id);
        $graph_data = [];
        $cumulative = 0;
        $completed_amount = 0;

        foreach ($history as $day) {
            $day_val = (int) $day['actual_count'];
            $cumulative += $day_val;
            $completed_amount += $day_val;
            $graph_data[] = $cumulative; // Graph shows progress over time? Or daily?
            // Sparkline usually shows activity over time.
            // If we want "Spiky" graph like mocks, using daily "actual_count" is better than cumulative (which is monotonic increasing).
            // Let's use daily count for the sparkline.
            // $graph_data[] = $day_val; 
            // Re-reading mock: "Spiky" -> daily amounts.
        }

        // Let's redefine graph_data to be daily values for sparkline
        $graph_data = [];
        foreach ($history as $day) {
            $graph_data[] = (int) $day['actual_count'];
        }

        // Calculate progress %
        $percent_done = ($total_word_count > 0) ? ($completed_amount / $total_word_count) * 100 : 0;

        // Color (Schema doesn't have display_settings, maybe add random or default)
        // Check if color_code exists in row? Not in schema shown earlier.
        $color_code = '#3b82f6'; // Default blue

        $plan_item = array(
            "id" => $id,
            "title" => $name, // 'name' column
            "goal_amount" => $total_word_count,
            "goal_unit" => 'words', // Default
            "progress_percent" => round($percent_done, 1),
            "activity_type" => $act_type,
            "content_type" => $subtitle,
            "creator_username" => isset($username) && $username ? $username : "Anonymous",
            "graph_data" => $graph_data,
            "updated_at" => isset($updated_at) ? $updated_at : null,
            "color_code" => $color_code
        );

        array_push($plans_arr["records"], $plan_item);
    }
}

http_response_code(200);
echo json_encode($plans_arr);
?>