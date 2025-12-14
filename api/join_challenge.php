<?php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';

$data = json_decode(file_get_contents("php://input"));

// Check if invite code or challenge id is present
if (empty($data->user_id) && (empty($data->challenge_id) && empty($data->invite_code))) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing user_id or challenge identification."]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // If invite_code provided, resolve to challenge_id
    if (!empty($data->invite_code)) {
        $codeQuery = "SELECT id, is_public FROM group_challenges WHERE invite_code = :code OR invite_code = UPPER(:code)";
        $codeStmt = $db->prepare($codeQuery);
        $codeStmt->bindParam(':code', $data->invite_code);
        $codeStmt->execute();

        if ($codeStmt->rowCount() > 0) {
            $row = $codeStmt->fetch(PDO::FETCH_ASSOC);
            $data->challenge_id = $row['id'];
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Invalid invite code."]);
            exit();
        }
    }

    // Check if already joined
    $checkQuery = "SELECT id FROM challenge_participants WHERE user_id = :user_id AND challenge_id = :challenge_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":user_id", $data->user_id);
    $checkStmt->bindParam(":challenge_id", $data->challenge_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        http_response_code(400); // Or 200 with message "Already joined"
        echo json_encode(["success" => false, "message" => "You have already joined this challenge."]);
        exit();
    }

    // Join
    $query = "INSERT INTO challenge_participants (user_id, challenge_id) VALUES (:user_id, :challenge_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->bindParam(":challenge_id", $data->challenge_id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Successfully joined the challenge!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to join challenge."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>