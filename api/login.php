<?php
// backend-php/api/login.php

// Debug logging
function logDebug($msg)
{
    file_put_contents('debug_login.txt', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}
logDebug("Login Request Started");

$baseDir = realpath(__DIR__ . '/..');
logDebug("BaseDir: " . $baseDir);

// Include CORS and Database configs safely
require_once $baseDir . '/config/cors.php';
require_once $baseDir . '/config/database.php';

// Handle CORS if not already handled (e.g. if accessed directly)
handleCors();

logDebug("Config included");

// Instantiate DB & Connect
$database = new Database();
$db = $database->getConnection();
logDebug("DB Connected");

// Get input data
$rawInput = file_get_contents("php://input");
logDebug("Raw Input: " . $rawInput);

$data = json_decode($rawInput);

$email = isset($data->email) ? trim($data->email) : '';
$password = isset($data->password) ? $data->password : '';

// Validation
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required."
    ]);
    exit;
}

try {
    // Find user by email
    $query = "SELECT id, username, email, password_hash FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "User not found with this email."
        ]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid password."
        ]);
        exit;
    }

    // Success - return user data
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Successful login.",
        "data" => [
            "user_id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>