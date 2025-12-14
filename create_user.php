<?php
require_once 'db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, "Method not allowed. Use POST.");
}

// Get input data (supports both JSON and form data)
$input = getJSONInput();
$username = isset($input['username']) ? trim($input['username']) : (isset($_POST['username']) ? trim($_POST['username']) : '');
$email = isset($input['email']) ? trim($input['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');
$password = isset($input['password']) ? $input['password'] : (isset($_POST['password']) ? $_POST['password'] : '');

// Validation
if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    sendResponse(false, "Username, email, and password are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    sendResponse(false, "Invalid email format.");
}

if (strlen($password) < 6) {
    http_response_code(400);
    sendResponse(false, "Password must be at least 6 characters.");
}

try {
    $db = getDBConnection();

    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        http_response_code(409);
        sendResponse(false, "Email already registered.");
    }

    // Check if username already exists
    $checkQuery2 = "SELECT id FROM users WHERE username = :username LIMIT 1";
    $checkStmt2 = $db->prepare($checkQuery2);
    $checkStmt2->bindParam(':username', $username);
    $checkStmt2->execute();

    if ($checkStmt2->fetch()) {
        http_response_code(409);
        sendResponse(false, "Username already taken.");
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $query = "INSERT INTO users (username, email, password_hash, created_at) VALUES (:username, :email, :password_hash, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->execute();

    $userId = $db->lastInsertId();

    http_response_code(201);
    sendResponse(true, "User registered successfully.", [
        "user_id" => $userId,
        "username" => $username,
        "email" => $email
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(false, "Database error: " . $e->getMessage());
}
?>