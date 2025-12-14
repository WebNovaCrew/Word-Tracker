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

// Logging Function
// Logging Function - Use standard error log to avoid file permission issues breaking JSON
function logDebug($message)
{
    error_log('[CreateChallenge] ' . $message);
}

function generateInviteCode($length = 6)
{
    return strtoupper(substr(bin2hex(random_bytes($length)), 0, $length));
}

// Instantiate DB & Connect
$database = new Database();
$conn = $database->getConnection();

logDebug("Request received");

// Ensure tables exist (PDO syntax)
try {
    // Update table definition to include invite_code
    $sql1 = "CREATE TABLE IF NOT EXISTS group_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        creator_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        goal_type VARCHAR(50) DEFAULT 'word_count',
        goal_amount INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_public BOOLEAN DEFAULT TRUE,
        invite_code VARCHAR(10) UNIQUE, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql1);

    // Add invite_code column if it doesn't exist (migration for existing table)
    try {
        $checkCol = "SHOW COLUMNS FROM group_challenges LIKE 'invite_code'";
        $stmt = $conn->query($checkCol);
        if ($stmt->rowCount() == 0) {
            $alter = "ALTER TABLE group_challenges ADD COLUMN invite_code VARCHAR(10) UNIQUE";
            $conn->exec($alter);
        }
    } catch (Exception $e) {
        logDebug("Column check/alter error (ignorable if exists): " . $e->getMessage());
    }

    $sql2 = "CREATE TABLE IF NOT EXISTS challenge_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        challenge_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        current_progress INT DEFAULT 0,
        FOREIGN KEY (challenge_id) REFERENCES group_challenges(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_participation (challenge_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql2);

    // Challenge Logs Table for Daily Progress
    $sql3 = "CREATE TABLE IF NOT EXISTS challenge_logs (
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
    $conn->exec($sql3);

} catch (PDOException $e) {
    logDebug("Table setup error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database setup failed: ' . $e->getMessage()]);
    exit;
}

// Read Input
$rawInput = file_get_contents("php://input");
logDebug("Raw Input: " . $rawInput);

$data = json_decode($rawInput, true);

if (!$data) {
    logDebug("JSON Decode failed: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input JSON']);
    exit;
}

$creator_id = $data['creator_id'] ?? 1;
logDebug("Creator ID: " . $creator_id);

if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
    logDebug("Missing fields. Name: " . ($data['name'] ?? 'NULL') . ", Start: " . ($data['start_date'] ?? 'NULL'));
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $invite_code = generateInviteCode();
    $is_public = (!empty($data['is_public']) && $data['is_public'] !== 'false') ? 1 : 0;

    // If public, maybe no invite code needed? Or always generate one just in case.
    // Let's always generate one.

    $sql = "INSERT INTO group_challenges (creator_id, name, description, goal_type, goal_amount, start_date, end_date, is_public, invite_code) 
            VALUES (:creator_id, :name, :description, :goal_type, :goal_amount, :start_date, :end_date, :is_public, :invite_code)";

    $stmt = $conn->prepare($sql);

    logDebug("Preparing insert with Public: " . $is_public);

    // Bind Params (PDO)
    $stmt->bindParam(':creator_id', $creator_id);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':goal_type', $data['goal_type']);
    $stmt->bindParam(':goal_amount', $data['goal_amount']);
    $stmt->bindParam(':start_date', $data['start_date']);
    $stmt->bindParam(':end_date', $data['end_date']);
    $stmt->bindParam(':is_public', $is_public);
    $stmt->bindParam(':invite_code', $invite_code);

    if ($stmt->execute()) {
        $challenge_id = $conn->lastInsertId();
        logDebug("Challenge Created! ID: " . $challenge_id);

        // Auto-join creator
        try {
            $join_sql = "INSERT INTO challenge_participants (challenge_id, user_id) VALUES (:cid, :uid)";
            $join_stmt = $conn->prepare($join_sql);
            $join_stmt->bindParam(':cid', $challenge_id);
            $join_stmt->bindParam(':uid', $creator_id);
            $join_stmt->execute();
            logDebug("Creator auto-joined.");
        } catch (Exception $e) {
            logDebug("Auto-join failed (non-fatal): " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'id' => $challenge_id,
            'invite_code' => $invite_code,
            'message' => 'Challenge created successfully'
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        logDebug("Execute failed: " . print_r($errorInfo, true));
        throw new Exception("Execute failed: " . implode(" ", $errorInfo));
    }
} catch (Exception $e) {
    logDebug("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>