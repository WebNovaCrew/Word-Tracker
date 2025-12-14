<?php
require_once 'db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, "Method not allowed. Use POST.");
}

// Get input data (supports both JSON and form data)
$input = getJSONInput();
$email = isset($input['email']) ? trim($input['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');
$password = isset($input['password']) ? $input['password'] : (isset($_POST['password']) ? $_POST['password'] : '');

// Validation
if (empty($email) || empty($password)) {
    http_response_code(400);
    sendResponse(false, "Email and password are required.");
}

try {
    $db = getDBConnection();

    // Find user by email
    $query = "SELECT id, username, email, password_hash FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        sendResponse(false, "User not found with this email.");
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        sendResponse(false, "Invalid password.");
    }

    // Success - return user data
    http_response_code(200);
    sendResponse(true, "Login successful.", [
        "user_id" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(false, "Database error: " . $e->getMessage());
}
?>