<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->challenge_id) ||
    empty($data->user_id) ||
    !isset($data->word_count) ||
    empty($data->date)
) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data."]);
    exit;
}

try {
    $db->beginTransaction();

    // Ensure tables exist (Self-healing)
    try {
        $checkTbl = "SELECT 1 FROM challenge_logs LIMIT 1";
        $db->query($checkTbl);
    } catch (PDOException $e) {
        // Create challenge_logs
        $createLogsSql = "CREATE TABLE IF NOT EXISTS challenge_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            challenge_id INT NOT NULL,
            user_id INT NOT NULL,
            log_date DATE NOT NULL,
            word_count INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (challenge_id) REFERENCES group_challenges(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_daily_log (challenge_id, user_id, log_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->exec($createLogsSql);
    }

    // Ensure participants table exists too
    try {
        $checkPart = "SELECT 1 FROM challenge_participants LIMIT 1";
        $db->query($checkPart);
    } catch (PDOException $e) {
        $createPartSql = "CREATE TABLE IF NOT EXISTS challenge_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            challenge_id INT NOT NULL,
            user_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            current_progress INT DEFAULT 0,
            FOREIGN KEY (challenge_id) REFERENCES group_challenges(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participation (challenge_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->exec($createPartSql);
    }

    // 1. Upsert Daily Log
    $checkSql = "SELECT word_count FROM challenge_logs WHERE challenge_id = :cid AND user_id = :uid AND log_date = :date";
    $stmtCheck = $db->prepare($checkSql);
    $stmtCheck->bindParam(':cid', $data->challenge_id);
    $stmtCheck->bindParam(':uid', $data->user_id);
    $stmtCheck->bindParam(':date', $data->date);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $new_count = $row['word_count'] + $data->word_count;

        $updateSql = "UPDATE challenge_logs SET word_count = :count WHERE challenge_id = :cid AND user_id = :uid AND log_date = :date";
        $stmtUpdate = $db->prepare($updateSql);
        $stmtUpdate->bindParam(':count', $new_count);
        $stmtUpdate->bindParam(':cid', $data->challenge_id);
        $stmtUpdate->bindParam(':uid', $data->user_id);
        $stmtUpdate->bindParam(':date', $data->date);
        $stmtUpdate->execute();
    } else {
        $insertSql = "INSERT INTO challenge_logs (challenge_id, user_id, log_date, word_count) VALUES (:cid, :uid, :date, :count)";
        $stmtInsert = $db->prepare($insertSql);
        $stmtInsert->bindParam(':cid', $data->challenge_id);
        $stmtInsert->bindParam(':uid', $data->user_id);
        $stmtInsert->bindParam(':date', $data->date);
        $stmtInsert->bindParam(':count', $data->word_count);
        $stmtInsert->execute();
    }

    // 2. Update Total Progress in Participants Table
    // Calculate new total
    $sumSql = "SELECT SUM(word_count) as total FROM challenge_logs WHERE challenge_id = :cid AND user_id = :uid";
    $stmtSum = $db->prepare($sumSql);
    $stmtSum->bindParam(':cid', $data->challenge_id);
    $stmtSum->bindParam(':uid', $data->user_id);
    $stmtSum->execute();
    $rowSum = $stmtSum->fetch(PDO::FETCH_ASSOC);
    // Explicitly cast to integer to ensure numeric context
    $total = isset($rowSum['total']) ? (int) $rowSum['total'] : 0;

    // Use a SELECT to check existence first, to avoid rowCount() ambiguity (state vs change)
    $checkPartSql = "SELECT 1 FROM challenge_participants WHERE challenge_id = :cid AND user_id = :uid";
    $stmtCheckPart = $db->prepare($checkPartSql);
    $stmtCheckPart->bindParam(':cid', $data->challenge_id);
    $stmtCheckPart->bindParam(':uid', $data->user_id);
    $stmtCheckPart->execute();

    if ($stmtCheckPart->rowCount() > 0) {
        $updatePart = "UPDATE challenge_participants SET current_progress = :total 
                       WHERE challenge_id = :cid AND user_id = :uid";
        $stmtPart = $db->prepare($updatePart);
        $stmtPart->bindParam(':total', $total);
        $stmtPart->bindParam(':cid', $data->challenge_id);
        $stmtPart->bindParam(':uid', $data->user_id);
        $stmtPart->execute();
    } else {
        $insertPart = "INSERT INTO challenge_participants (challenge_id, user_id, current_progress) 
                       VALUES (:cid, :uid, :total)";
        $stmtInsertPart = $db->prepare($insertPart);
        $stmtInsertPart->bindParam(':cid', $data->challenge_id);
        $stmtInsertPart->bindParam(':uid', $data->user_id);
        $stmtInsertPart->bindParam(':total', $total);
        $stmtInsertPart->execute();
    }

    $db->commit();
    echo json_encode([
        "success" => true,
        "message" => "Progress updated successfully.",
        "new_total" => $total
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "message" => "Error updating progress.",
        "error" => $e->getMessage()
    ]);
}
?>