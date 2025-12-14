<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

$database = new Database();
$db = $database->getConnection();

$challenge_id = isset($_GET['id']) ? $_GET['id'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$challenge_id) {
    http_response_code(400);
    echo json_encode(["message" => "Challenge ID is required."]);
    exit;
}

try {
    // 1. Get Challenge Info
    // Use LEFT JOIN to allow loading challenges even if creator account is deleted
    $query = "SELECT c.*, COALESCE(u.username, 'Unknown') as creator_name 
              FROM group_challenges c
              LEFT JOIN users u ON c.creator_id = u.id
              WHERE c.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $challenge_id);
    $stmt->execute();
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        http_response_code(404);
        echo json_encode(["message" => "Challenge not found."]);
        exit;
    }

    // Determine status
    $today = date('Y-m-d');
    if ($today < $challenge['start_date']) {
        $challenge['status'] = 'Upcoming';
    } elseif ($today > $challenge['end_date']) {
        $challenge['status'] = 'Completed';
    } else {
        $challenge['status'] = 'Ongoing';
    }

    // 2. Get Participants (Leaderboard)
    // We sum up logs for accuracy, or use the cache column. Let's use cache for now but recalculate if needed.
    // Actually, let's join users table.
    $queryPart = "SELECT cp.*, u.username, u.email 
                  FROM challenge_participants cp
                  JOIN users u ON cp.user_id = u.id
                  WHERE cp.challenge_id = :id
                  ORDER BY cp.current_progress DESC";
    $stmtPart = $db->prepare($queryPart);
    $stmtPart->bindParam(':id', $challenge_id);
    $stmtPart->execute();
    $participants = $stmtPart->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get User's Daily Logs (if user_id provided)
    $userLogs = [];
    $userTotal = 0;
    if ($user_id) {
        try {
            $queryLogs = "SELECT * FROM challenge_logs 
                          WHERE challenge_id = :cid AND user_id = :uid 
                          ORDER BY log_date DESC";
            $stmtLogs = $db->prepare($queryLogs);
            $stmtLogs->bindParam(':cid', $challenge_id);
            $stmtLogs->bindParam(':uid', $user_id);
            $stmtLogs->execute();
            $userLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If table doesn't exist, create it and ignoring the error for this request (return empty logs)
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
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
        }


        // Get total from participants array efficiently
        foreach ($participants as $p) {
            if ($p['user_id'] == $user_id) {
                $userTotal = $p['current_progress'];
                break;
            }
        }
    }

    echo json_encode([
        "success" => true,
        "challenge" => $challenge,
        "participants" => $participants,
        "user_logs" => $userLogs,
        "user_progress" => $userTotal
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Error fetching details.",
        "error" => $e->getMessage()
    ]);
}
?>