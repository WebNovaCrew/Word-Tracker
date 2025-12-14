<?php
// backend-php/test_delete_flow.php
require_once 'config.php';
require_once 'models/Plan.php';

$database = new Database();
$db = $database->getConnection();
$plan = new Plan($db);

// 1. Create a dummy user if not exists (to avoid FK error)
$stmt = $db->query("SELECT id FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user ? $user['id'] : 0;

if (!$user_id) {
    echo "No users found. Creating temp user.\n";
    $db->prepare("INSERT INTO users (username, email, password_hash) VALUES ('testuser', 'test@test.com', 'hash')")->execute();
    $user_id = $db->lastInsertId();
}

// 2. Create a plan
$plan->user_id = $user_id;
$plan->name = "Delete Me " . time();
$plan->start_date = date('Y-m-d');
$plan->end_date = date('Y-m-d', strtotime('+1 day'));
$plan->goal_amount = 1000;

if ($plan->create()) {
    echo "Plan Created: ID " . $plan->id . "\n";

    // 3. Try database delete directly first
    if ($plan->delete($plan->id)) {
        echo "Database Delete Method: SUCCESS\n";
    } else {
        echo "Database Delete Method: FAILED\n";
        print_r($db->errorInfo());
    }

    // 4. Create another to test API
    $plan->create();
    echo "Plan Re-Created for API Test: ID " . $plan->id . "\n";

    $url = 'http://localhost:8000/api/delete_plan.php';
    $data = json_encode(['id' => $plan->id]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "API Response Code: " . $httpCode . "\n";
    echo "API Response Body: " . $response . "\n";

} else {
    echo "Failed to create test plan.\n";
}
?>