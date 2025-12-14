<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$baseDir = realpath(__DIR__ . '/..');
require_once $baseDir . '/config.php';

$database = new Database();
$db = $database->getConnection();

$plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : null;

if (!$plan_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Plan ID is required"]);
    exit;
}

try {
    // Get plan details
    $planQuery = "SELECT * FROM plans WHERE id = :plan_id";
    $planStmt = $db->prepare($planQuery);
    $planStmt->bindParam(':plan_id', $plan_id);
    $planStmt->execute();
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Plan not found"]);
        exit;
    }

    // Get all plan days with progress
    $daysQuery = "SELECT date, target, logged FROM plan_days WHERE plan_id = :plan_id ORDER BY date ASC";
    $daysStmt = $db->prepare($daysQuery);
    $daysStmt->bindParam(':plan_id', $plan_id);
    $daysStmt->execute();
    $days = $daysStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_logged = 0;
    $total_target = 0;
    $days_completed = 0;
    $current_streak = 0;
    $longest_streak = 0;
    $temp_streak = 0;
    $today = date('Y-m-d');
    $started = false;

    $daily_data = [];

    foreach ($days as $day) {
        $total_logged += $day['logged'];
        $total_target += $day['target'];

        // Count days with progress
        if ($day['logged'] > 0) {
            $days_completed++;
            $temp_streak++;
            if ($temp_streak > $longest_streak) {
                $longest_streak = $temp_streak;
            }
        } else {
            $temp_streak = 0;
        }

        // Current streak (only count up to today)
        if ($day['date'] <= $today && $day['logged'] > 0) {
            if (!$started)
                $started = true;
        }
        if ($started && $day['date'] <= $today) {
            if ($day['logged'] > 0) {
                $current_streak++;
            } else {
                $current_streak = 0;
            }
        }

        // Format data for chart
        $daily_data[] = [
            'date' => $day['date'],
            'target' => (int) $day['target'],
            'logged' => (int) $day['logged'],
            'cumulative' => $total_logged
        ];
    }

    // Calculate remaining
    $start_date = new DateTime($plan['start_date']);
    $end_date = new DateTime($plan['end_date']);
    $today_date = new DateTime($today);

    $total_days = $start_date->diff($end_date)->days + 1;
    $days_elapsed = $start_date->diff($today_date)->days;
    if ($days_elapsed < 0)
        $days_elapsed = 0;
    if ($days_elapsed > $total_days)
        $days_elapsed = $total_days;

    $days_remaining = $total_days - $days_elapsed;
    if ($days_remaining < 0)
        $days_remaining = 0;

    $remaining_work = $plan['goal_amount'] - $total_logged;
    if ($remaining_work < 0)
        $remaining_work = 0;

    $progress_percent = $plan['goal_amount'] > 0 ? ($total_logged / $plan['goal_amount']) * 100 : 0;

    // Average per day
    $avg_per_day = $days_completed > 0 ? $total_logged / $days_completed : 0;
    $daily_pace_needed = $days_remaining > 0 ? $remaining_work / $days_remaining : 0;

    // Build response
    $response = [
        "success" => true,
        "plan" => [
            "id" => $plan['id'],
            "name" => $plan['name'],
            "goal_amount" => (int) $plan['goal_amount'],
            "start_date" => $plan['start_date'],
            "end_date" => $plan['end_date'],
            "strategy" => $plan['strategy'],
            "intensity" => $plan['intensity']
        ],
        "stats" => [
            "total_logged" => $total_logged,
            "total_target" => $total_target,
            "total_days" => $total_days,
            "days_elapsed" => $days_elapsed,
            "days_remaining" => $days_remaining,
            "days_completed" => $days_completed,
            "remaining_work" => $remaining_work,
            "progress_percent" => round($progress_percent, 1),
            "avg_per_day" => round($avg_per_day, 0),
            "daily_pace_needed" => round($daily_pace_needed, 0),
            "current_streak" => $current_streak,
            "longest_streak" => $longest_streak
        ],
        "daily_data" => $daily_data
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Get Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to fetch stats",
        "error" => $e->getMessage()
    ]);
}