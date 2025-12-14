<?php
// Direct test of create_plan API
header("Content-Type: application/json; charset=UTF-8");

// Simulate a POST request to create_plan.php
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create test data
$testData = [
    'user_id' => 1,
    'name' => 'Test Plan',
    'goal_amount' => 50000,
    'start_date' => '2025-12-10',
    'end_date' => '2025-12-31',
    'strategy' => 'steady',
    'content_type' => 'Novel',
    'activity_type' => 'Writing',
    'intensity' => 'average'
];

// Set up the input stream
$_POST = $testData;
file_put_contents('php://input', json_encode($testData));

// Include and execute the create_plan.php
ob_start();
try {
    include 'api/create_plan.php';
    $output = ob_get_clean();
    echo "SUCCESS OUTPUT:\n";
    echo $output;
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>