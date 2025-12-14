<?php
// backend-php/api/create_project.php
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

try {
    $database = new Database();
    $db = $database->getConnection();

    // Ensure table exists (idempotent)
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // We suppress error here if table creation fails due to permissions, assuming it might exist
    try {
        $db->exec($sql);
    } catch (PDOException $pe) {
        error_log("Project Table Create Warning: " . $pe->getMessage());
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data)) {
        throw new Exception("No input data provided");
    }

    if (empty($data['user_id'])) {
        // Fallback to user ID 1 if not provided
        $data['user_id'] = 1;
    }

    if (empty($data['name'])) {
        throw new Exception("Project Name is required");
    }

    $query = "INSERT INTO projects (user_id, name, description) VALUES (:uid, :name, :desc)";
    $stmt = $db->prepare($query);

    $desc = $data['description'] ?? '';

    $stmt->bindParam(':uid', $data['user_id']);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':desc', $desc);

    try {
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "id" => $db->lastInsertId(),
                "message" => "Project created successfully"
            ]);
        } else {
            // Should be caught by catch block if PDO throws, but if not:
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Database insert failed: " . $errorInfo[2]);
        }
    } catch (PDOException $e) {
        // Check for Foreign Key constraint failure (1452)
        if ($e->getCode() == 23000 || $e->errorInfo[1] == 1452) {
            // Retry with user_id = 1
            error_log("Invalid User ID provided. Retrying with User ID 1.");
            $stmt->bindValue(':uid', 1);
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "id" => $db->lastInsertId(),
                    "message" => "Project created successfully (assigned to default user)"
                ]);
                exit;
            }
        }
        throw $e;
    }

} catch (Exception $e) {
    error_log("Create Project Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>