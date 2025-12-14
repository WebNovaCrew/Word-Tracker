<?php
$testUrl = "http://localhost:8000/api/create_plan.php";
$testData = [
    'user_id' => 1,
    'name' => 'Test API Plan',
    'goal_amount' => 50000,
    'start_date' => '2025-12-10',
    'end_date' => '2025-12-31',
    'strategy' => 'steady',
    'content_type' => 'Novel',
    'activity_type' => 'Writing',
    'intensity' => 'average'
];

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
echo $response . "\n";
?>