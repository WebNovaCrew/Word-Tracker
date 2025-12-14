<?php
// backend-php/api/update_profile.php

// Explicit CORS to allow any origin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

try {
    // Get raw input
    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }

    if (empty($data->user_id)) {
        throw new Exception("user_id is required");
    }

    $database = new Database();
    $conn = $database->getConnection();

    // build query
    $updateFields = [];
    $params = [];

    // Username check
    if (isset($data->username) && trim($data->username) !== '') {
        $username = trim($data->username);

        // Check uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $data->user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Username already taken"]);
            exit;
        }

        $updateFields[] = "username = ?";
        $params[] = $username;
    }

    // Email check
    if (isset($data->email) && trim($data->email) !== '') {
        $email = trim($data->email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $data->user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Email already in use"]);
            exit;
        }

        $updateFields[] = "email = ?";
        $params[] = $email;
    }

    // Bio - allow empty string
    if (isset($data->bio)) {
        $updateFields[] = "bio = ?";
        $params[] = $data->bio;
    }

    if (empty($updateFields)) {
        throw new Exception("No fields to update");
    }

    // Append ID for WHERE clause
    $params[] = $data->user_id;

    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute($params)) {
        // Fetch fresh data to return
        $stmt = $conn->prepare("SELECT id, username, email, bio, created_at FROM users WHERE id = ?");
        $stmt->execute([$data->user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "message" => "Profile updated successfully",
            "user" => $user
        ]);
    } else {
        $error = $stmt->errorInfo();
        throw new Exception("Database error: " . $error[2]);
    }

} catch (Exception $e) {
    http_response_code(500); // Or 400 depending on error, but 500 is safe catch-all
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>