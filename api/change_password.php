<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id) || empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing required fields: user_id, current_password, and new_password are required"
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$data->user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "User not found"
        ]);
        exit();
    }

    // Verify current password
    if (!password_verify($data->current_password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Current password is incorrect"
        ]);
        exit();
    }

    // Validate new password (minimum 6 characters)
    if (strlen($data->new_password) < 6) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "New password must be at least 6 characters long"
        ]);
        exit();
    }

    // Hash and update new password
    $new_password_hash = password_hash($data->new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_password_hash, $data->user_id]);

    error_log("Password updated for user_id: " . $data->user_id);

    echo json_encode([
        "success" => true,
        "message" => "Password updated successfully"
    ]);

} catch (PDOException $e) {
    error_log("Database error in change_password.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error occurred"
    ]);
}
?>