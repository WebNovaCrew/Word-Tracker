<?php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Plan.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Initialize Database
$database = new Database();
$db = $database->getConnection();

// Get raw POST data
$data = json_decode(file_get_contents("php://input"));

// Check if data is empty
if (empty($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "status" => "error", "message" => "No data provided."]);
    exit();
}

// Validate required fields
// We accept both 'name'/'plan_name' and 'goal_amount'/'goal' to be flexible
$user_id = $data->user_id ?? null;
$plan_name = $data->name ?? ($data->plan_name ?? null);
$goal_amount = $data->goal_amount ?? ($data->goal ?? null);
$start_date = $data->start_date ?? null;
$end_date = $data->end_date ?? null;
$content_type = $data->content_type ?? 'Novel'; // Default
$activity_type = $data->activity_type ?? 'Writing'; // Default
$strategy = $data->strategy ?? 'steady';
$intensity = $data->intensity ?? 'average';

if (!$user_id || !$plan_name || !$goal_amount || !$start_date || !$end_date) {
    error_log("Create Plan Error: Missing required fields. Data: " . json_encode($data));
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Missing required fields.",
        "received" => $data
    ]);
    exit();
}

try {
    // 1. Insert Plan into 'plans' table
// Note: Using actual schema column names: name, goal_amount
    $query = "INSERT INTO plans
(user_id, name, content_type, activity_type, start_date, end_date, goal_amount, strategy, intensity)
VALUES
(:user_id, :name, :content_type, :activity_type, :start_date, :end_date, :goal_amount, :strategy, :intensity)";

    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":name", $plan_name);
    $stmt->bindParam(":content_type", $content_type);
    $stmt->bindParam(":activity_type", $activity_type);
    $stmt->bindParam(":start_date", $start_date);
    $stmt->bindParam(":end_date", $end_date);
    $stmt->bindParam(":goal_amount", $goal_amount);
    $stmt->bindParam(":strategy", $strategy);
    $stmt->bindParam(":intensity", $intensity);

    if ($stmt->execute()) {
        $plan_id = $db->lastInsertId();

        // 2. Generate Daily Schedule in 'plan_days' table
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day')); // Include end date

        $days = iterator_count($period);
        $daily_target = ($days > 0) ? ceil($goal_amount / $days) : $goal_amount;

        // Prepare insert for plan_days
        $day_query = "INSERT INTO plan_days (plan_id, date, target, logged) VALUES (:plan_id, :date, :target, 0)";
        $day_stmt = $db->prepare($day_query);

        foreach ($period as $dt) {
            $current_date = $dt->format("Y-m-d");
            $day_stmt->bindParam(":plan_id", $plan_id);
            $day_stmt->bindParam(":date", $current_date);
            $day_stmt->bindParam(":target", $daily_target);
            $day_stmt->execute();
        }

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Plan saved successfully",
            "plan_id" => $plan_id
        ]);
    } else {
        throw new Exception("Failed to execute insert query.");
    }

} catch (Exception $e) {
    error_log("Create Plan Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Error saving plan: " . $e->getMessage()
    ]);
}