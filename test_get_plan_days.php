<?php
$userId = 2; // Testing with User ID 2 (known to exist)

$url = "http://localhost:8000/api/get_plan_days.php?user_id=" . $userId;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_VERBOSE, true); 
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "URL: $url\n";
echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "...\n";
?>