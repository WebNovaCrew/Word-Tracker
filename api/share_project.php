<?php
// backend-php/api/share_project.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['project_id']) || empty($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID and Email are required']);
    exit;
}

try {
    // 1. Find user by email
    $stmtUser = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmtUser->bindParam(':email', $data['email']);
    $stmtUser->execute();

    if ($stmtUser->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User with this email not found']);
        exit;
    }

    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $targetUserId = $user['id'];

    // 2. Check if already shared
    $stmtCheck = $conn->prepare("SELECT id FROM project_shares WHERE project_id = :pid AND user_id = :uid");
    $stmtCheck->bindParam(':pid', $data['project_id']);
    $stmtCheck->bindParam(':uid', $targetUserId);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Project already shared with this user']);
        exit;
    }

    // 3. Share the project
    $stmtShare = $conn->prepare("INSERT INTO project_shares (project_id, user_id, permission_level) VALUES (:pid, :uid, 'view')");
    $stmtShare->bindParam(':pid', $data['project_id']);
    $stmtShare->bindParam(':uid', $targetUserId);

    if ($stmtShare->execute()) {
        echo json_encode(['success' => true, 'message' => 'Project shared successfully']);
    } else {
        throw new Exception("Failed to share project");
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>