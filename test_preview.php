<?php
// backend-php/test_preview.php
require_once 'config.php';
// require_once 'core/Algorithm.php'; // Not needed for curl test

$data = [
    'total_word_count' => 50000,
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'algorithm_type' => 'steady',
    'strategy_intensity' => 'average'
];

$ch = curl_init('http://localhost:8000/api/preview_plan.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . substr($response, 0, 500) . "...\n";
?>