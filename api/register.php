<?php
// backend-php/api/register.php

$baseDir = realpath(__DIR__ . '/..');
// Include Database config safely
require_once $baseDir . '/config/database.php';

// Instantiate DB & Connect
$database = new Database();
$db = $database->getConnection();

// Get input data
$data = json_decode(file_get_contents("php://input"));

$username = isset($data->username) ? trim($data->username) : '';
$email = isset($data->email) ? trim($data->email) : '';
$password = isset($data->password) ? $data->password : '';

// Validation
if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "All fields are required."
    ]);
    exit;
}

try {
    // Check if email exists
    $checkQuery = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($checkQuery);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(400); // Bad Request
        echo json_encode([
            "success" => false,
            "message" => "Email already exists."
        ]);
        exit;
    }

    // Insert User
    $query = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
    $stmt = $db->prepare($query);

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "User created successfully."
        ]);
    } else {
        throw new Exception("Execute failed");
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Registration error."
    ]);
}
?>