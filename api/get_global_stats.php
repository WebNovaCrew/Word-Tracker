<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$baseDir = realpath(__DIR__ . '/..');
require_once $baseDir . '/config.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit;
}

try {
    // 1. Get aggregate totals
    // Total logged work across all plans
    $totalQuery = "SELECT SUM(pd.logged) as total_logged 
                   FROM plan_days pd 
                   JOIN plans p ON pd.plan_id = p.id 
                   WHERE p.user_id = :uid";
    $stmt = $db->prepare($totalQuery);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $totalRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_logged = $totalRow['total_logged'] ? (int) $totalRow['total_logged'] : 0;

    // Total target? Maybe sum of all plan goals?
    $goalsQuery = "SELECT SUM(goal_amount) as total_goals FROM plans WHERE user_id = :uid";
    $stmt = $db->prepare($goalsQuery);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $goalsRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_goals = $goalsRow['total_goals'] ? (int) $goalsRow['total_goals'] : 0;

    // 2. Get daily data for chart
    $daysQuery = "SELECT pd.date, SUM(pd.target) as target, SUM(pd.logged) as logged 
                  FROM plan_days pd 
                  JOIN plans p ON pd.plan_id = p.id 
                  WHERE p.user_id = :uid 
                  GROUP BY pd.date 
                  ORDER BY pd.date ASC";
    $daysStmt = $db->prepare($daysQuery);
    $daysStmt->bindParam(':uid', $user_id);
    $daysStmt->execute();
    $days = $daysStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calculate statistics
    $days_completed = 0;
    $current_streak = 0;
    $longest_streak = 0;
    $temp_streak = 0;
    $today = date('Y-m-d');
    $started = false;
    $total_days_active = 0;
    $first_date = null;

    $daily_data = [];
    $cumulative = 0;

    foreach ($days as $day) {
        if (!$first_date)
            $first_date = $day['date'];

        $cumulative += $day['logged'];

        if ($day['logged'] > 0) {
            $days_completed++;
            $temp_streak++;
            if ($temp_streak > $longest_streak) {
                $longest_streak = $temp_streak;
            }
        } else {
            $temp_streak = 0;
        }

        // Current streak logic
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

        $daily_data[] = [
            'date' => $day['date'],
            'target' => (int) $day['target'],
            'logged' => (int) $day['logged'],
            'cumulative' => $cumulative
        ];
    }

    $days_elapsed = 0;
    if ($first_date) {
        $start = new DateTime($first_date);
        $now = new DateTime($today);
        $days_elapsed = $start->diff($now)->days;
    }

    $avg_per_day = $days_completed > 0 ? $total_logged / $days_completed : 0;
    $progress_percent = $total_goals > 0 ? ($total_logged / $total_goals) * 100 : 0;

    $response = [
        "success" => true,
        "plan" => [
            "name" => "All Projects", // Generic name for global view
            "goal_amount" => $total_goals,
            "start_date" => $first_date, // First activity
            "end_date" => null, // Continuous
        ],
        "stats" => [
            "total_logged" => $total_logged,
            "days_elapsed" => $days_elapsed,
            "days_remaining" => 0, // N/A for global
            "days_completed" => $days_completed,
            "remaining_work" => max(0, $total_goals - $total_logged),
            "progress_percent" => round($progress_percent, 1),
            "avg_per_day" => round($avg_per_day, 0),
            "daily_pace_needed" => 0, // N/A
            "current_streak" => $current_streak,
            "longest_streak" => $longest_streak,
            "total_days" => $days_elapsed
        ],
        "daily_data" => $daily_data
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error fetching global stats", "error" => $e->getMessage()]);
}
?>