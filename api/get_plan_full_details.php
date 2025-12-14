<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';

// Disable display_errors to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die(json_encode(['error' => 'No ID specified']));

// 1. Get Plan Basic Info
$query = "SELECT * FROM plans WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    http_response_code(404);
    echo json_encode(['error' => 'Plan not found']);
    exit;
}

// 2. Get Plan Days (Logged Work)
$queryDays = "SELECT * FROM plan_days WHERE plan_id = :id ORDER BY date ASC";
$stmtDays = $db->prepare($queryDays);
$stmtDays->bindParam(':id', $id);
$stmtDays->execute();
$loggedDays = [];
while ($row = $stmtDays->fetch(PDO::FETCH_ASSOC)) {
    $loggedDays[$row['date']] = $row;
}

// 3. Construct Schedule and Calculate Stats
$startDate = new DateTime($plan['start_date']);
$endDate = new DateTime($plan['end_date']);
$now = new DateTime();
$todayDate = $now->format('Y-m-d');

$schedule = [];
$totalDays = $startDate->diff($endDate)->days + 1;
$daysPassed = 0;
$totalWordsLogged = 0;
$currentStreak = 0;
$longestStreak = 0;
$tempStreak = 0;
$runningExpected = 0;
$dailyGoal = ceil($plan['goal_amount'] / $totalDays);

$period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->modify('+1 day'));
$idx = 0;

foreach ($period as $dt) {
    $idx++;
    $dateStr = $dt->format('Y-m-d');
    $isToday = ($dateStr === $todayDate);

    // Get existing data or default
    $dayData = isset($loggedDays[$dateStr]) ? $loggedDays[$dateStr] : ['logged' => 0, 'target' => 0];

    $actual = (int) $dayData['logged'];
    $totalWordsLogged += $actual;
    $runningExpected += $dailyGoal; // Linear progression

    // Streak Logic (if actual > 0)
    if ($actual > 0) {
        $tempStreak++;
    } else {
        if ($dateStr < $todayDate) {
            $longestStreak = max($longestStreak, $tempStreak);
            $tempStreak = 0;
        }
    }

    // Build Schedule Row
    $schedule[] = [
        'num' => $idx,
        'date' => $dateStr,
        'day_name' => $dt->format('D'),
        'target_count' => $dailyGoal, // Simple daily target
        'actual_count' => $actual,
        'expected_progress' => min($runningExpected, $plan['goal_amount']),
        'actual_progress' => $totalWordsLogged,
        'work_left' => max(0, $plan['goal_amount'] - $totalWordsLogged),
        'is_today' => $isToday,
        'is_editable' => ($dateStr <= $todayDate),
        'db_id' => isset($dayData['id']) ? $dayData['id'] : null
    ];

    if ($dateStr <= $todayDate) {
        $daysPassed++;
    }
}
// Final streak check
$longestStreak = max($longestStreak, $tempStreak);
// Current streak is tempStreak if the last day counted was yesterday or today
$currentStreak = $tempStreak;

// 4. Final Plan Object
$wordsLeft = max(0, (int) $plan['goal_amount'] - $totalWordsLogged);
$percent = $plan['goal_amount'] > 0 ? round(($totalWordsLogged / $plan['goal_amount']) * 100) : 0;
$avgDaily = $daysPassed > 0 ? round($totalWordsLogged / $daysPassed) : 0;

$response = [
    'id' => $plan['id'],
    'title' => $plan['name'],
    'total_word_count' => (int) $plan['goal_amount'],
    'total_days' => $totalDays,
    'start_date' => $plan['start_date'],
    'end_date' => $plan['end_date'],
    'words_complete' => $totalWordsLogged,
    'words_left' => $wordsLeft,
    'days_left' => max(0, $totalDays - $daysPassed),
    'progress_percent' => $percent,
    'avg_daily_words' => $avgDaily,
    'current_streak' => $currentStreak,
    'longest_streak' => $longestStreak,
    'schedule' => $schedule
];

echo json_encode($response);
?>